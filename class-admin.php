<?php
/**
* Admin class for "YouTube Comments" Wordpress plugin
*/
class YouTubeCommentsAdmin {
	
	protected static $instance = null;
	private $settings_page;

	/**
	* Constructor
	*/
	function __construct() {}
	
	/**
	* Get Instance
	*/	
	public static function get_instance() {
		null === self::$instance and self::$instance = new self;
	 	return self::$instance;
	}		
	
	/**
	* Setup
	*/	
	public function setup() {
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_menu'));		
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));
	}
	
	/**
	* Register settings
	*/
	public function admin_init() {
		register_setting('yc_settings_group', 'yc_settings');
		add_settings_section('yc_settings_common', '', array($this, 'settings_fields'), 'yc_settings_page');
		add_settings_section('yc_settings_api', 'YouTube API', array($this, 'settings_fields'), 'yc_settings_page');
	}	
	
	/**
	* Admin menu
	*/	
	public function admin_menu() {
		$hook = add_options_page('YouTube Comments', 'YouTube Comments', 'manage_options', 'youtube-comments', array($this, 'settings_template'));
		add_action('load-' . $hook, array($this, 'settings_help')); 
	}	

	/**
	* Load settings help
	*/	
	public function settings_help() {
		$screen = get_current_screen();
		if ($screen->id != $this->settings_page) {
			return;
		}
		$screen->add_help_tab(array(
			'id'      => 'ppf-overview',
			'title'   => 'Overview',
			'content' => '<h1>Overview</h1><p>Searches post content for YouTube links and imports their comments.</p>'
		));
		$screen->add_help_tab(array(
			'id'      => 'ppf-shortcode',
			'title'   => 'Shortcodes',
			'content' => '<h1>Shortcodes</h1><p>Add the shortcode "[youtube-comments]" to a post or page containing videos.</p>'
		));	
		$screen->add_help_tab(array(
			'id'      => 'ppf-support',
			'title'   => 'Support',
			'content' => '<h1>Support</h1><p>Wordpress Profile:<br /><a href="http://profiles.wordpress.org/sydcode">http://www.freelancer.com.au/u/sydcode.html</a></p><p>Freelancer Profile:<br /><a href="http://www.freelancer.com.au/u/sydcode.html">http://www.freelancer.com.au/u/sydcode.html</a></p>'
		));			
	}

	/**
	* Load settings template
	*/
	public function settings_template() {
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		} else {
			include('template-settings.php');
		}
	}
	
	/**
	* Add settings link to plugin controls
	*/
	public function settings_link($links) { 
		$link = '<a href="options-general.php?page=youtube-comments">Settings</a>'; 
		array_unshift($links, $link); 
		return $links; 
	}

	/**
	* Register settings fields 
	*/
	public function settings_fields() {
		add_settings_field('yc_settings_comments', 'Show Comments', array($this, 'settings_comments'), 'yc_settings_page', 'yc_settings_common'); 
		add_settings_field('yc_settings_max_results', 'Request Comments', array($this, 'settings_max_results'), 'yc_settings_page', 'yc_settings_common'); 
		add_settings_field('yc_settings_post_comments', 'Post Comments', array($this, 'settings_post_comments'), 'yc_settings_page', 'yc_settings_common'); 
		add_settings_field('yc_settings_custom_fields', 'Custom Fields', array($this, 'settings_custom_fields'), 'yc_settings_page', 'yc_settings_common'); 
		add_settings_field('yc_settings_client_id', 'Client ID', array($this, 'settings_client_id'), 'yc_settings_page', 'yc_settings_api'); 
		add_settings_field('yc_settings_client_secret', 'Client Secret', array($this, 'settings_client_secret'), 'yc_settings_page', 'yc_settings_api'); 
		add_settings_field('yc_settings_api_key', 'API Key', array($this, 'settings_api_key'), 'yc_settings_page', 'yc_settings_api'); 
	}
	
	/**
	* Setting field for showing comments
	*/
	public function settings_comments() {
		$settings = get_option('yc_settings');
		$checked = empty($settings['show_all']) ? checked(0, 0, false) : checked($settings['show_all'], 0, false);
		echo "<label><input type='radio' name='yc_settings[show_all]' value='0' " . $checked . " />&nbsp;&nbsp;Shortcode</label><br />" . PHP_EOL;
		$checked = empty($settings['show_all']) ? '' : checked($settings['show_all'], 1, false);
		echo "<label><input type='radio' name='yc_settings[show_all]' value='1' " . $checked . " />&nbsp;&nbsp;Always</label>" . PHP_EOL;
		echo "<p class='description'>Choose when to show comments below videos.</p>" . PHP_EOL;
	}

	/**
	* Setting field for comment results
	*/
	public function settings_max_results() {
		$settings = get_option('yc_settings');
		$value = empty($settings['max_results']) ? '10' : $settings['max_results'];
		echo "<input type='text' name='yc_settings[max_results]' class='small-text' value='" . $value . "' />" . PHP_EOL;
		echo "<p class='description'>Maximum number of comments per request.</p>" . PHP_EOL;
	}		
	
	/**
	* Setting field for posting comments
	*/
	public function settings_post_comments() {
		$settings = get_option('yc_settings');
		$checked = empty($settings['post_comments']) ? '' : checked($settings['post_comments'], 1, false);
		echo "<label><input type='checkbox' name='yc_settings[post_comments]' value='1' " . $checked . " />&nbsp;&nbsp;Check to enable</label><br />" . PHP_EOL;
		echo "<p class='description'>Allow users to post comments on YouTube.</p>" . PHP_EOL;
	}
	
	/**
	* Setting field for custom fields
	*/
	public function settings_custom_fields() {
		$settings = get_option('yc_settings');
		$value = empty($settings['custom_fields']) ? '' : $settings['custom_fields'];
		echo "<input type='text' name='yc_settings[custom_fields]' class='regular-text' value='" . $value . "' />" . PHP_EOL;
		echo "<p class='description'>Enter the names of custom fields for videos.<br />Separate multiple names with commas.</p>" . PHP_EOL;
	}		

	/**
	* Setting field for client ID
	*/
	public function settings_client_id() {
		$settings = get_option('yc_settings');
		$value = empty($settings['client_id']) ? '' : $settings['client_id'];
		echo "<input type='text' name='yc_settings[client_id]' class='regular-text' value='" . $value . "' />" . PHP_EOL;
	}		
	
	/**
	* Setting field for client secret
	*/
	public function settings_client_secret() {
		$settings = get_option('yc_settings');
		$value = empty($settings['client_secret']) ? '' : $settings['client_secret'];
		echo "<input type='text' name='yc_settings[client_secret]' class='regular-text' value='" . $value . "' />" . PHP_EOL;
	}

	/**
	* Setting field for API key
	*/
	public function settings_api_key() {
		$settings = get_option('yc_settings');
		$value = empty($settings['api_key']) ? '' : $settings['api_key'];
		echo "<input type='text' name='yc_settings[api_key]' class='regular-text' value='" . $value . "' />" . PHP_EOL;
		echo "<p class='description'>Enter your credentials for the YouTube API.<br />";
		echo "Register <a href='https://developers.google.com/youtube/registering_an_application' target='_blank'>here</a> to get credentials.<br />";
		echo "Credentials are only used to post comments.</p>" . PHP_EOL;
	}	

} // End Class	