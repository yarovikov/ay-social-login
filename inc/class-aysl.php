<?php

class AYSL
{
    private $fb_client_id;
    private $fb_client_secret;
    
    private $vk_client_id;
    private $vk_client_secret;
    
    
    public function __construct()
    {
        $this->vk_client_id = get_option('vk_client_id');
        $this->vk_client_secret = get_option('vk_client_secret');
        $this->fb_client_id = get_option('fb_client_id');
        $this->fb_client_secret = get_option('fb_client_secret');
        $this->load_dependencies();
        add_action('init', array( $this, 'virtual_page' ));
        add_action('wp_enqueue_scripts', array( $this, 'ay_sl_scripts' ));
        add_action('admin_enqueue_scripts', array( $this, 'admin_ay_sl_scripts' ));
        add_action('get_header', array( $this, 'o_s' ));
        add_action('template_redirect', array( $this, 'sl_redirect' ));
        add_action('sl_form', array( $this, 'sl_form' ));
        add_action('sl_action', array( $this, 'sl_action' ));
    }
    
    
    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'inc/class-admin-aysl.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'inc/aysl-functions.php';
    }
    
    
    public function activate()
    {
        $this->virtual_page();
        flush_rewrite_rules();
    }
    
    
    public function deactivate()
    {
        flush_rewrite_rules();
    }
    
    
    // virtual page for redirect link
    public function virtual_page()
    {
        add_rewrite_endpoint('sl', EP_ROOT);
    }
    
    
    public function ay_sl_scripts()
    {
        wp_enqueue_style('ay-social-login', plugin_dir_url(dirname(__FILE__)) . 'assets/main.min.css', null, filemtime(dirname(__FILE__)) . 'assets/main.min.css');
    }
    
    public function admin_ay_sl_scripts()
    {
        wp_enqueue_style('admin-ay-social-login', plugin_dir_url(dirname(__FILE__)) . 'assets/admin.min.css', null, filemtime(dirname(__FILE__)) . 'assets/admin.min.css');
    }
    
    
    // fix start session header sent error (wp_set_auth_cookie)
    public function o_s()
    {
        ob_start();
    }

    
    public function sl_redirect()
    {
        global $wp_query;
        // check virtual page
        if (! isset($wp_query->query_vars['sl'])) {
            return;
        }
        // check redirect
        if (isset($_GET['sl'])) {
            do_action('sl_action');
        } else {
            wp_redirect(home_url());
        }
        die;
    }
    
    
    public static function copy_user_image_to_wp($avatar_url = false, $source = false)
    {
        if (!$avatar_url || !$source) {
            return;
        }
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        if ($source === 'vk') {
            $tmp = download_url($avatar_url);
            preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $avatar_url, $matches);
            $url_filename = basename($matches[0]);
            $url_type = wp_check_filetype($url_filename);
            if (!empty($filename)) {
                $filename = sanitize_file_name($filename);
                $tmppath = pathinfo($tmp);
                $new = $tmppath['dirname'] . "/". $filename . "." . $tmppath['extension'];
                rename($tmp, $new);
                $tmp = $new;
            }
            $file_array['tmp_name'] = $tmp;
            if (!empty($filename)) {
                $file_array['name'] = $filename . "." . $url_type['ext'];
            } else {
                $file_array['name'] = $url_filename;
            }
        }
        if ($source === 'fb') {
            ob_start();
            $upload_dir = wp_upload_dir();
            $upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;
            $hashed_filename = md5(microtime()) . '.jpeg';
            $image_upload = file_put_contents($upload_path . $hashed_filename, $avatar_url);
            $file_array = array();
            $file_array['tmp_name'] = $upload_path . $hashed_filename;
            $file['type'] = 'image/jpeg';
            $file_array['name'] = $hashed_filename;
        }
        $att_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($att_id)) {
            @unlink($file_array['tmp_name']);
            return $att_id;
        }
        return $att_id;
    }
    
    
    public function get_user_data_from_vk()
    {
        $ay_user_data = array();
        $params = array(
            'client_id'  => $this->vk_client_id,
            'redirect_uri' => home_url('sl/?sl=vk'),
            'client_secret' => $this->vk_client_secret,
            'code' => $_GET['code'],
        );
        $tokenresponse = wp_remote_get('https://oauth.vk.com/access_token?' . http_build_query($params));
        $token = json_decode(wp_remote_retrieve_body($tokenresponse));
        if (isset($token->error)) {
            echo __('Response error', 'ay');
        }
        if (isset($token->access_token)) {
            $params = array(
                'v' => '5.126',
                'access_token' => $token->access_token,
                'fields' => 'id,photo_200,first_name,last_name',
            );
            $response = wp_remote_get('https://api.vk.com/method/users.get' . '?' . urldecode(http_build_query($params)));
            $response = json_decode(wp_remote_retrieve_body($response));
            if (isset($token->email)) {
                $ay_user_email = $token->email;
            }
            $response = $response->response;
            foreach ($response as $ay_user_item) {
                $ay_user_id = $ay_user_item->id;
                $ay_user_first_name = $ay_user_item->first_name;
                $ay_user_last_name = $ay_user_item->last_name;
            }
            $ay_user_avatar = AYSL::copy_user_image_to_wp($ay_user_item->photo_200, 'vk');
            $ay_user_avatar = wp_get_attachment_image_url($ay_user_avatar);
            $ay_user_data = array(
                'user_login' => $ay_user_email,
                'user_pass' => wp_generate_password(),
                'user_email' => $ay_user_email,
                'first_name' => $ay_user_first_name,
                'last_name' => $ay_user_last_name,
                'avatar_url' => $ay_user_avatar,
            );
        }
        return $ay_user_data;
    }
    
    
    public function get_user_data_from_fb()
    {
        ob_start();
        $ay_user_data = array();
        $params = array(
            'client_id'  => $this->fb_client_id,
            'redirect_uri' => home_url('sl/?sl=fb'),
            'client_secret' => $this->fb_client_secret,
            'code' => $_GET['code'],
        );
        $tokenresponse = wp_remote_get('https://graph.facebook.com/v4.0/oauth/access_token?' . http_build_query($params));
        $token = json_decode(wp_remote_retrieve_body($tokenresponse));
        if (isset($token->error)) {
            echo __('Response error', 'ay');
        }
        if (isset($token->access_token)) {
            $params = array(
                'access_token'	=> $token->access_token,
                'fields'		=> 'id,name,picture,email,locale,first_name,last_name',

            );
            $response = wp_remote_get('https://graph.facebook.com/v9.0/me' . '?' . urldecode(http_build_query($params)));
            $ay_user = json_decode(wp_remote_retrieve_body($response));
            $ay_user_avatar = file_get_contents('https://graph.facebook.com/' . $ay_user->id . '/picture?type=large');
            $ay_user_avatar_id = AYSL::copy_user_image_to_wp($ay_user_avatar, 'fb');
            $ay_user_avatar = wp_get_attachment_image_url($ay_user_avatar_id);
            $ay_user_data = array(
                'user_login' => $ay_user->email,
                'user_pass' => wp_generate_password(),
                'user_email' => $ay_user->email,
                'first_name' => $ay_user->first_name,
                'last_name' => $ay_user->last_name,
                'avatar_url' => $ay_user_avatar,
            );
        }
        ob_end_flush();
        return $ay_user_data;
    }
    
    
    public function get_user_data()
    {
        $ay_user_data = array();
        if ($_GET['sl'] == 'vk') {
            $ay_user_data = $this->get_user_data_from_vk();
        }
        if ($_GET['sl'] == 'fb') {
            $ay_user_data = $this->get_user_data_from_fb();
        }
        return $ay_user_data;
    }
    
    
    public function sl_action($ay_user_data = array())
    {
        session_start();
        global $post;
        if (!isset($_GET['code'])) {
            $_SESSION['url'] = get_permalink($post->ID);
        }
        $ay_user_data = $this->get_user_data();
        if (empty($ay_user_data)) {
            return;
        }
        $ay_user_avatar = $ay_user_data['avatar_url'];
        $data = array(
            'user_login'  =>  $ay_user_data['user_email'],
            'user_pass'   =>  wp_generate_password(),
            'user_email' => $ay_user_data['user_email'],
            'first_name' => $ay_user_data['first_name'],
            'last_name' => $ay_user_data['last_name'],
        );
        if (!email_exists($ay_user_data['user_email'])) {
            $ay_user_id = wp_insert_user($data);
            update_user_meta($ay_user_id, 'wsl_current_user_image', $ay_user_avatar);
        } else {
            $ay_user = get_user_by('email', $data['user_email']);
            $ay_user_id = $ay_user->ID;
            update_user_meta($ay_user_id, 'wsl_current_user_image', $ay_user_avatar);
        }
        if ($ay_user_id) {
            wp_set_auth_cookie($ay_user_id, true);
            wp_redirect($_SESSION['url']);
            exit;
        }
    }
    
    
    public function sl_form()
    {
        session_start();
        global $post;
        if (!isset($_GET['code'])) {
            $_SESSION['url'] = get_permalink($post->ID);
        }
        $redirect_uri_fb = home_url('sl/?sl=fb');
        $redirect_uri_vk = home_url('sl/?sl=vk');
        $params_fb = array(
            'client_id'     => $this->fb_client_id,
            'redirect_uri'  => $redirect_uri_fb,
            'response_type' => 'code',
            'scope'         => 'email'
        );
        $params_vk = array(
            'client_id'     => $this->vk_client_id,
            'redirect_uri'  => $redirect_uri_vk,
            'response_type' => 'code',
            'v'             => '5.126',
            'scope'         => 'email',
        );
        $login_url_fb = 'https://www.facebook.com/v9.0/dialog/oauth?' . urldecode(http_build_query($params_fb));
        $login_url_vk = 'http://oauth.vk.com/authorize?' . urldecode(http_build_query($params_vk));
        echo '<div class="sl">';
        echo '<a class="sl__icon sl__icon_fb" rel="nofollow" href="' . $login_url_fb . '"></a>';
        echo '<a class="sl__icon sl__icon_vk" rel="nofollow" href="' . $login_url_vk . '"></a>';
        echo '</div>';
    }
}
