<?php
/**
* Plugin Name: AY Social Login
* Description: Simple Social Login Plugin via Facebook and VK
* Plugin URI:  https://yarovikov.com/
* Author URI:  https://yarovikov.com/
* Author:      Alexandr Yarovikov
* Text Domain: ay
* Domain Path: /languages
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Version:     1.0.1
*/


defined( 'ABSPATH' ) || exit;


class AYSocialLogin {
	
	private $client_id_fb = '';
	private $client_secret_fb = '';
	
	private $client_id_vk = '';
	private $client_secret_vk = '';	
	
	
	function __construct() {		
		add_action( 'init', array( $this, 'virtual_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'ay_sl_scripts' ) );
		add_action( 'get_header', array( $this, 'o_s' ) );		
		add_action( 'template_redirect', array( $this, 'sl_redirect' ) );		
		add_action( 'sl_form', array( $this, 'sl_form' ) );
		add_action( 'sl_action', array( $this, 'sl_action' ) );
	}
	
	
	function activate() {					
		$this->virtual_page();		
		flush_rewrite_rules();		
	}
	
	
	function deactivate() {		
		flush_rewrite_rules();		
	}
	
	
	// add virtual page for fb redirect link
	function virtual_page() {		
		add_rewrite_endpoint( 'sl', EP_ROOT );		
	}
	
	
	function ay_sl_scripts() {			
		wp_enqueue_style( 'ay-social-login', plugin_dir_url( __FILE__ ) . 'ay-social-login.css', null, '1.0.1' );
	}
	
	
	// fix start session header sent error because wp_set_auth_cookie
	function o_s() {		
		ob_start();		
	}

	
	function sl_redirect() {	   
	    global $wp_query;
	    
	    if ( ! isset( $wp_query->query_vars['sl'] ) ) {	        
	        return;	    
	    }
	    
	    if ( isset( $_GET['sl'] ) ) { 		    	    	
	    	do_action( 'sl_action' );	    	    	
	    }	    
	    else {		    
		    wp_redirect( home_url() );	    
	    }	    
	    die;
	}
	
	
	// get and insert user
	function sl_action() {	
		
		session_start();			
		
		global $user;									
		
		$redirect_uri_fb = home_url( 'sl/?sl=fb' );
		$redirect_uri_vk = home_url( 'sl/?sl=vk' );
		
		
		if ( isset( $_GET['code'] ) && $_GET['code'] && isset( $_GET['sl'] ) ) {
	 	
		 	if ( $_GET['sl'] == 'fb' ) {
		 		
				$params_fb = array(
					'client_id'     => $this->client_id_fb,
					'redirect_uri'  => $redirect_uri_fb,
					'client_secret' => $this->client_secret_fb,
					'code'          => $_GET['code'] 
				);
		 		
				$tokenresponse = wp_remote_get( 'https://graph.facebook.com/v4.0/oauth/access_token?' . http_build_query( $params_fb ) );
		 		
				$token = json_decode( wp_remote_retrieve_body( $tokenresponse ) );
				
				if ( isset( $token->error ) ) {
					echo __( 'Response error' , 'ay' );
					echo '<p><a href="' . $_SESSION['url'] . '">'. __( 'Back to post', 'ay' ) .'</a>';
				}
								 		
				if ( isset( $token->access_token ))  {
		 		
					$params_fb = array(
						'access_token'	=> $token->access_token,
						'fields'		=> 'id,name,picture,email,locale,first_name,last_name',
		
					);
		 		
					$useresponse = wp_remote_get( 'https://graph.facebook.com/v4.0/me' . '?' . urldecode( http_build_query( $params_fb ) ) );			
		 		
					$ay_user = json_decode( wp_remote_retrieve_body( $useresponse ) );
					
					$ay_user_email = $ay_user->email;
					$ay_user_id = $ay_user->id;
					$ay_user_first_name = $ay_user->first_name;
					$ay_user_last_name = $ay_user->last_name;
					$ay_user_avatar = 'https://graph.facebook.com/' . $ay_user_id . '/picture?type=large';
					$ay_user_link = '';	
					
				}				
				
			}		
			
			if ( $_GET['sl'] == 'vk' ) {
				
				$params_vk = array(
					'client_id'     => $this->client_id_vk,
					'redirect_uri'  => $redirect_uri_vk,
					'client_secret' => $this->client_secret_vk,
					'code'          => $_GET['code'],
				);
				
				$tokenresponse = wp_remote_get( 'https://oauth.vk.com/access_token?' . http_build_query( $params_vk ) );
		 		
				$token = json_decode( wp_remote_retrieve_body( $tokenresponse ) );
				
				if ( isset( $token->error ) ) {
					echo __( 'Response error', 'ay' );
					echo '<p><a href="' . $_SESSION['url'] . '">'. __( 'Back to post', 'ay' ) .'</a>';
				}
				
				if ( isset( $token->access_token ) )  {
		 		
					$params_vk = array(
						'v' => '5.89',
						'access_token'	=> $token->access_token,
						'fields'		=> 'id,photo_100,first_name,last_name',
					);
		 		
					$useresponse = wp_remote_get( 'https://api.vk.com/method/users.get' . '?' . urldecode( http_build_query( $params_vk ) ) );			
		 		
					$ay_user = json_decode( wp_remote_retrieve_body( $useresponse ) );
					
					// get email from token response
					if( isset( $token->email ) ) {
						$ay_user_email = $token->email;								
					}
					else {		
						get_header();			
						echo '<div class="sl__notice">';
						echo __( 'Need your e-mail for login', 'ay' );
						echo '<a href="' . $_SESSION['url'] . '">' . __( 'Back to post' , 'ay' ) . '</a>';
						echo '</div>';
						get_footer();
						exit;
					}
					
					$response = $ay_user->response;
					
					foreach ( $response as $user_item ) {
					    $ay_user_id = $user_item->id;
					    $ay_user_first_name = $user_item->first_name;
					    $ay_user_last_name = $user_item->last_name;
						$ay_user_avatar = $user_item->photo_100;
						$ay_user_link = 'https://vk.com/id' . $ay_user_id;
					}				
													
				}
				
			}
					 				 			
			if ( isset( $ay_user_id ) && isset( $ay_user_email ) ) {
													
				if ( !email_exists( $ay_user_email ) ) {				
		 
					$userdata = array(
						'user_login'  =>  $ay_user_email,
						'user_pass'   =>  wp_generate_password(),
						'user_email' => $ay_user_email,
						'first_name' => $ay_user_first_name,
						'last_name' => $ay_user_last_name,
						'user_url' => $ay_user_link
					);
					
					$user_id = wp_insert_user( $userdata );	 
					
					// wsl_current_user_image get avatar from mysql if you used wp social login earler
					update_user_meta( $user_id, 'wsl_current_user_image', $ay_user_avatar );						
		 
				} 
				else {
																	
					$user = get_user_by( 'email', $ay_user_email );
					$user_id = $user->ID;	
					update_user_meta( $user_id, 'wsl_current_user_image', $ay_user_avatar );				
				
				}
		 
				if ( $user_id ) {
					wp_set_auth_cookie( $user_id, true );					
					wp_redirect( $_SESSION['url'] );								
					exit;
				}
		 
			}
			
		}
	
	}
	
	
	function sl_form() {	
		
		session_start();
		
		global $post;				
		
		// page for redirect after login
		if ( !isset( $_GET['code'] ) ) {
			$_SESSION['url'] = get_permalink( $post->ID );
		}
						
		$redirect_uri_fb = home_url( 'sl/?sl=fb' );
		$redirect_uri_vk = home_url( 'sl/?sl=vk' );
		
		$params_fb = array(
			'client_id'     => $this->client_id_fb,
			'redirect_uri'  => $redirect_uri_fb,
			'response_type' => 'code',
			'scope'         => 'email'
		);
		
		$params_vk = array(
			'client_id'     => $this->client_id_vk,
			'redirect_uri'  => $redirect_uri_vk,
			'response_type' => 'code',
			'v'             => '5.89',
			'scope'         => 'email',
		);
		
		$login_url_fb = 'https://www.facebook.com/v3.2/dialog/oauth?' . urldecode( http_build_query( $params_fb ) );
		$login_url_vk = 'http://oauth.vk.com/authorize?' . urldecode( http_build_query( $params_vk ) );
		
		?>
			
		
		<div class="sl">
    	
    		<a class="sl__icon sl__icon_fb" href="<?php echo $login_url_fb; ?>"></a>
    		<a class="sl__icon sl__icon_vk" href="<?php echo $login_url_vk; ?>"></a>
		
		</div>
	
	<?php	
	}
	
}


if ( class_exists( 'AYSocialLogin' ) ) {
	$AYSocialLoginPlugin = new AYSocialLogin();
}


register_activation_hook( __FILE__, array( $AYSocialLoginPlugin, 'activate' ) );

register_deactivation_hook( __FILE__, array( $AYSocialLoginPlugin, 'deactivate' ) );