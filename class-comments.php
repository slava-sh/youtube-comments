<?php
/**
* Main class for "YouTube Comments" Wordpress plugin
*/
class YouTubeComments {
	
	protected static $instance = null;
	const PLUGIN_VERSION = '1.2.1';
	private $client = null;
	private $youtube = null;
	private $has_video = false;

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
		$settings = get_option('yc_settings');
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));		
		add_action('wp_ajax_get_comments', array($this, 'ajax_get_comments'));
		add_action('wp_ajax_nopriv_get_comments', array($this, 'ajax_get_comments'));		
		add_action('wp_ajax_post_comment', array($this, 'ajax_post_comment'));
		add_action('wp_ajax_nopriv_post_comment', array($this, 'ajax_post_comment'));				
		add_action('wp_ajax_post_logoff', array($this, 'ajax_post_logoff'));
		add_action('wp_ajax_nopriv_post_logoff', array($this, 'ajax_post_logoff'));				
		add_shortcode('youtube-comments', array($this, 'handle_shortcode'));	
		// Avoid adding container twice
		if (!empty($settings['show_all'])) {
			add_filter('the_content', array($this, 'filter_content'));
		}
		// Setup YouTube API for posting comments
		// Gdata used because latest API lacks support
		if (!empty($settings['post_comments'])) {
			$this->client = new Google_Client();
			$this->client->setClientId($settings['client_id']);
			$this->client->setClientSecret($settings['client_secret']);
			$this->client->setDeveloperKey($settings['api_key']);
			$this->client->setRedirectUri(site_url());
			$this->youtube = new Google_YoutubeService($this->client);
			add_action('template_redirect', array($this, 'template_redirect'));
		}		
	}
	
	/**
	* Add comments to post content
	*
	* @param string $content	
	* @return string
	*/
	public function filter_content($content) {
		if ($this->has_video) {
			$content .= $this->add_container();
		}
		return $content;
	}		

	/**
	* Handle shortcode
	*
	* @return string	
	*/
	public function handle_shortcode() {
		if (is_single() || is_page()) {
			$settings = get_option('yc_settings');
			if ($this->has_video && empty($settings['show_all'])) {
				return $this->add_container();
			} else {
				return ''; // Hide shortcode
			}
		}
	}
	
	/**
	* Add comments container
	*
	* @param string $video_id	
	* @return string
	*/
	public function add_container() {
		$_SESSION['redirect_url'] = $this->get_redirect_url();
		ob_start();
		include('template-container.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/**
	* Load stylesheet and script
	*/
	public function enqueue_scripts() {
		global $post;
		if (is_single() || is_page()) {	
			$video_id = $this->get_first_video($post->ID);
			if (empty($video_id)) {
				return;
			}
			$this->has_video = true;
			$settings = get_option('yc_settings');
			$results = empty($settings['max_results']) ? '10' : $settings['max_results'];
			wp_enqueue_style('youtube-comments', plugins_url('style.css', __FILE__), array(), self::PLUGIN_VERSION);	
			wp_enqueue_script('youtube-comments', plugins_url('script.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);	
			$vars = array(
				'ajaxURL' => admin_url('admin-ajax.php'),
				'videoID' => $video_id,
				'results' => $results
			);
			wp_localize_script('youtube-comments', 'youtubeComments', $vars);
		}
	}
	
	/**
	* Handle AJAX request for comments
	*/
	public function ajax_get_comments() {
		$video_id = $_POST['videoID'];
		$start_index = $_POST['startIndex'];
		$this->get_video_comments($video_id, $start_index);
		exit;
	}	
	
	/**
	* Handle AJAX request for posting comment
	*/
	public function ajax_post_comment() {
		if (!wp_verify_nonce($_POST['nonce'], 'post-comment')) {
			die('Error: Security check failed');
		}
		if (empty($_SESSION['access-token'])) {
			die('Error: No access token');
		}
		// Validate access token
		$token = json_decode($_SESSION['access-token']);
		if (!$this->validate_token($token->access_token)) {
			die('Error: Access token invalid');
		}
		$this->client->setAccessToken($_SESSION['access-token']);
		if ($this->client->isAccessTokenExpired()) {
			$this->client->refreshToken($token->refresh-token);
		}
		// Prepare XML content
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://gdata.youtube.com/schemas/2007">';
		$xml .= '<content>' . $_POST['comment'] . '</content></entry>';
		// Prepare headers
		$settings = get_option('yc_settings');
		$header = array();
		$header[] = 'POST /feeds/api/videos/' . $_POST['videoID'] . '/comments HTTP/1.1';
		$header[] = 'Host: gdata.youtube.com';
		$header[] = 'Content-Type: application/atom+xml';
		$header[] = 'Content-Length: ' . strlen($xml);
		$header[] = 'Authorization: Bearer ' . $token->access_token;
		$header[] = 'GData-Version: 2';
		$header[] = 'X-GData-Key: key=' . $settings['api_key'];
		// Post comment
		$url = 'http://gdata.youtube.com/feeds/api/videos/' . $_POST['videoID'] . '/comments';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
		curl_exec($curl);
		curl_close($curl);
		exit;
	}	

	/**
	* Handle AJAX request for logoff
	*/
	public function ajax_post_logoff() {
		if (!wp_verify_nonce($_POST['nonce'], 'post-comment')) {
			die('Error: Security check failed');
		}
		unset($_SESSION['access-token']);
		$this->client->revokeToken();
		exit;
	}
	
	/**
	* Validate access token
	*
	* @param string $access_token	
	* @return bool
	*/
	public function validate_token($access_token) {	
		$url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $access_token;
		$json = file_get_contents($url);
		if ($json) {
			$values = json_decode($json);
			if (empty($values->error)) {
				$settings = get_option('yc_settings');
				if (!empty($values->audience) && $values->audience == $settings['client_id']) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	* Search for first video ID
	*
	* @param integer $post_id	
	* @return string
	*/
	public function get_first_video($post_id) {
		// Custom field for "Automatic YouTube Video Posts" plugin
		if (function_exists('WP_ayvpp_init')) {
			$video_id = get_post_meta($post_id, '_tern_wp_youtube_video', true);
			if (!empty($video_id)) {
				return $video_id;
			}
		}
		// Custom field for "deTube" premium theme
		$video_url = get_post_meta($post_id, 'dp_video_url', true);
		if (!empty($video_url)) {
			$video_id = $this->parse_youtube_url($video_url);
			if (!empty($video_id)) {
				return $video_id;	
			}
		}
		// Other custom fields
		$settings = get_option('yc_settings');
		$fields = empty($settings['custom_fields']) ? '' : trim($settings['custom_fields']);
		if (!empty($fields)) {
			$fields = explode(',', $settings['custom_fields']);
			foreach ($fields as $key) {
				$key = trim($key);
				$value = get_post_meta($post_id, $key, true);
				$value = trim($value);
				if (!empty($value)) {
					// Custom field may contain URL or ID, so assume ID if not valid URL 
					$video_id = $this->parse_youtube_url($value);
					if (!empty($video_id)) {
						return $video_id;	
					} else {
						return $value;
					}
				}
			}
		}
		// Otherwise, search post content for URL
		$content = get_post_field('post_content', $post_id);
		$video_id = $this->parse_youtube_url($content);
		if (!empty($video_id)) {
			return $video_id;
		}
		return false;
	}	
	
	/**
	* Parse YouTube URL to get video ID
	*
	* @param string $url
	* @return string
	*/
	public function parse_youtube_url($url) {	
		$pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
		preg_match($pattern, $url, $match);	
		if (!empty($match[1])) {
			return $match[1];
		}
		return false;	
	}

	/**
	* Get video comments
	*
	* @param string $video_id	
	* @return array	
	*/
	public function get_video_comments($video_id, $start_index = 1) {
		$settings = get_option('yc_settings');
		$max_results = empty($settings['max_results']) ? '10' : $settings['max_results'];
		$query = '?start-index=' . $start_index . '&max-results=' . $max_results;
		$uri = 'http://gdata.youtube.com/feeds/api/videos/' . $video_id . '/comments' . $query;
		$comments = simplexml_load_file($uri);
		$open_search = $comments->children('openSearch', true);
		$total_results = $open_search->totalResults;
		include('template-comments.php');
	}		
	
	/**
	* Get time since comment posted
	*
	* @param string $time
	* @return string
	*/	
	function time_ago($time) {
		date_default_timezone_set('UTC');
		$diff = time() - strtotime($time); 
		$result = 'just now';
		$periods = array(
			'year' => 31556926,
			'month' => 2629744,
			'week' => 604800,
			'day' => 86400,
			'hour' => 3600,
			'minute' => 60,
			'second' => 1
		);
		foreach($periods as $name => $secs) {
			if ($secs <= $diff) {
				$value = floor($diff / $secs); 
				$result = $value . ' ' . $name . ($value == 1 ? '' : 's') . ' ago'; 
				break;
			}
		}
		return $result;
	}	
	
	/**
	* Get URL for redirection after authentication
	*
	* @return string	
	*/		
	function get_redirect_url() {
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
		$protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . $s;
		$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'.$_SERVER['SERVER_PORT']);
		$url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
		return filter_var($url, FILTER_SANITIZE_URL);
	}	
	
	/**
	* Handle redirection after authentication
	*/	
	function template_redirect() {	
		if (isset($_GET['state']) && isset($_GET['code'])) {
			if (strval($_SESSION['state']) !== strval($_GET['state'])) {
				die('The session state did not match.');
			}
			$this->client->authenticate($_GET['code']);
			$_SESSION['access-token'] = $this->client->getAccessToken();
			if (isset($_SESSION['redirect_url'])) {
				wp_safe_redirect($_SESSION['redirect_url']);
			}
		}
	}
	
} // End Class