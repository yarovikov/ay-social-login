<?php
/**
* Plugin Name: AY Social Login
* Description: Simple Social Login Plugin via Facebook and VK
* Plugin URI:  https://github.com/yarovikov/ay-social-login
* Author URI:  https://yarovikov.com/
* Author:      Alexandr Yarovikov
* Text Domain: ay
* Domain Path: /languages
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Version:     1.1.0
*/


defined('ABSPATH') || exit;


require plugin_dir_path(__FILE__) . 'inc/class-aysl.php';


if (class_exists('AYSL')) {
    $AYSLPlugin = new AYSL();
}


register_activation_hook(__FILE__, array( $AYSLPlugin, 'activate' ));


register_deactivation_hook(__FILE__, array( $AYSLPlugin, 'deactivate' ));
