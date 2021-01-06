<?php

function aysl_options_fields()
{
    echo '<h2>'.__('Facebook Client ID', 'ay').'</h2>';
    echo "<p><input type='text' name='fb_client_id' value='" . get_option('fb_client_id') . "' />";

    echo '<h2>'.__('Facebook Client Secret', 'ay').'</h2>';
    echo "<p><input type='password' name='fb_client_secret' value='" . get_option('fb_client_secret') . "' />";
    
    echo '<br /><br />';

    echo '<h2>'.__('VK Client ID', 'ay').'</h2>';
    echo "<p><input type='text' name='vk_client_id' value='" . get_option('vk_client_id') . "' />";

    echo '<h2>'.__('VK Client Secret', 'ay').'</h2>';
    echo "<p><input type='password' name='vk_client_secret' value='" . get_option('vk_client_secret') . "' />";
}


function aysl_options_page()
{
    if (!isset($_REQUEST['settings-updated'])) {
        $_REQUEST['settings-updated'] = false;
    }
    
    echo '<div class="wrap theme-info aysl-options">';
    echo '<h1>' .__('AY Social Login Options', 'ay'). '</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('aysl_options_settings');
    do_settings_sections('aysl_options_options');
        
    echo '<input name="submit" class="button-primary" type="submit" value="' . __('Сохранить') . '" />';
    echo '</form>';
    echo '<br /><p class="description"><a target="_blank" href="https://github.com/yarovikov/ay-social-login">Github</a>';
    echo '</div>';
}
