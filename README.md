# AY Social Login — Simple Social Login Plugin.

This plugin allow your website visitors to register and login via Facebook or Vkontakte.

### Installation ###

1. Download and activate the plugin

2. You must have the client id and the client secret parameters filled in ay-social-login.php
```
private $client_id_fb = '';
private $client_secret_fb = '';
private $client_id_vk = '';
private $client_secret_vk = '';	
```
3. You must have properly configured web applications. Use for redirect url the link like this
```
https://yoursite/sl/?sl=fb
```
4. Use this to display the socials buttons
```
<?php do_action('sl_form'); ?>
```

You can test the plugin on my second website https://yarovikov.ru/engine-anatomy/

### Screenshots ###

![00](https://user-images.githubusercontent.com/30932012/64392008-9c05ce80-d053-11e9-8b55-33c53cea7880.jpg)
