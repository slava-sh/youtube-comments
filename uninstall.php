<?php
/* 
* Uninstall "YouTube Comments" Wordpress plugin 
*/

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('yc_settings');

// End