<?php
/*

Plugin Name: YouTube Comments
Description: This plugin finds YouTube links in post content and imports the video comments. 
Version: 1.2.1
Author: sydcode
Author URI: http://profiles.wordpress.org/sydcode

Requires: Wordpress 3.3+

Instructions:
Copy the "youtube-comments" folder to the "wp-content/plugins" folder.
Login and activate the plugin in the dashboard plugins panel.
Edit the settings using the link in the dashboard settings menu.
Sign up for a Google developer account to enable comment posting.
Use the shortcode "[youtube-comments]" to select certain posts or pages.

Support: 
http://profiles.wordpress.org/sydcode
http://www.freelancer.com.au/u/sydcode.html

*/

session_start();

// Load API classes
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_YouTubeService.php';

// Load main class
if (!class_exists('YouTubeComments')) {
	require(dirname(__FILE__)  . '/class-comments.php');
	add_action('plugins_loaded', array (YouTubeComments::get_instance(), 'setup'));
}

// Load admin class
if (!class_exists('YouTubeCommentsAdmin')) {
	require(dirname(__FILE__)  . '/class-admin.php');
	add_action('plugins_loaded', array (YouTubeCommentsAdmin::get_instance(), 'setup'));
}

// End