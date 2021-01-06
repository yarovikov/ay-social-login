<?php

class AYSLAdmin
{
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'aysl_options_settings' ));
        add_action('admin_init', array( $this, 'aysl_options_register_settings' ));
    }
    
    
    public static function aysl_options_settings()
    {
        add_submenu_page(
            'options-general.php',
            __('AY Social Login', 'ay'),
            __('AY Social Login', 'ay'),
            'manage_options',
            'ay-sl-options.php',
            'aysl_options_page'
        );
    }
    
    
    public static function aysl_options_register_settings()
    {
        add_settings_section('aysl_options_settings', '', '', 'aysl_options_options');
        add_settings_field('more_options_o', '', 'aysl_options_fields', 'aysl_options_options', 'aysl_options_settings');
        register_setting('aysl_options_settings', 'fb_client_id', 'sanitize_wysiwyg');
        register_setting('aysl_options_settings', 'fb_client_secret', 'sanitize_wysiwyg');
        register_setting('aysl_options_settings', 'vk_client_id', 'sanitize_wysiwyg');
        register_setting('aysl_options_settings', 'vk_client_secret', 'sanitize_wysiwyg');
    }
}

return new AYSLAdmin();
