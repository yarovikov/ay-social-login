# AY Social Login — Simple Social Login Plugin.

This plugin allow your website visitors to register and login via Facebook or Vkontakte.

### Installation ###

1. Download and activate the plugin

2. You must have the client id and the client secret parameters filled on the options page

3. You must have properly configured web applications. Use for redirect url the link like this
```
https://yoursite/sl/?sl=fb
```
4. Use this to display the socials buttons
```
<?php do_action('sl_form'); ?>
```
