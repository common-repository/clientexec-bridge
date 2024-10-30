<?php
/*
 Plugin Name: ClientExec Bridge
 Plugin URI: http://www.zingiri.com
 Description: ClientExec Bridge is a plugin that integrates the powerfull ClientExec support and billing software with Wordpress.

 Author: Zingiri
 Version: 1.0.0
 Author URI: http://www.zingiri.com/
 */

require(dirname(__FILE__).'/bridge.init.php');
register_deactivation_hook(__FILE__,'clientexec_bridge_deactivate');