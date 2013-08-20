=== YouTube Comments ===
Contributors: sydcode
Donate link: 
Tags: YouTube, video, comments
Requires at least: 3.3
Tested up to: 3.5.2
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin finds YouTube links in post content and imports the video comments.

== Description ==

This plugin finds YouTube links in post content and imports the video comments.
Users may also post comments to the video after signing into YouTube.

== Installation ==

1. Upload the `youtube-comments` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit the settings using the link in the dashboard settings menu.
4. Sign up for a Google developer account to enable comment posting.
5. Use the shortcode "[youtube-comments]" to select certain posts or pages.

== Screenshots ==

1. Comments box below video

2. Settings panel

== Frequently Asked Questions ==

= 1. Why didn't my comment appear immediately? =

Posted comments are often delayed by a short time, even though they appear immediantly on YouTube.

= 2. Why am I still logged into YouTube after signing out? =

Sign out only cancels the ability to post comments. Users can only sign out completely from YouTube/Google.

= 3. Do I need to signup for a developer account? =

A developer account is only required to post comments. You can show comments without a developer account.

= 4. Why do I get an error message "redirect_uri_mismatch" when signing in? =

The redirect URI must exactly match your site's URL. An extra trailing slash is enough to cause that error.

== Upgrade Notice ==

No upgrades available.

== Changelog ==

= 1.0.0 =
* First release

= 1.1.0 =
* Added support for "Automatic YouTube Video Posts" plugin.
* Fixed bug in comment display.
* Updated the readme.