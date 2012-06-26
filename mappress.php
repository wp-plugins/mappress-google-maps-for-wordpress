<?php
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 2.38.1
Author: Chris Richardson
Thanks to all the translators and to Matthias Stasiak for his wonderful icons (http://code.google.com/p/google-maps-icons/)
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/

@require_once dirname( __FILE__ ) . '/mappress_api.php';
@require_once dirname( __FILE__ ) . '/mappress_options.php';
@include_once dirname( __FILE__ ) . '/mappress_updater.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro_settings.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_icons.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_widget.php';

class Mappress {
	const VERSION = '2.38.1';

	static
		$debug = false,
		$options,
		$baseurl,
		$basename,
		$basepath,
		$updater,
		$queue;

	function __construct()  {
		self::$options = Mappress_Options::get();
		self::$basename = plugin_basename(__FILE__);
		self::$baseurl = plugins_url('', __FILE__);
		self::$basepath = dirname(__FILE__);

		$this->debugging();

		// Create updater
		self::$updater = new Mappress_Updater(self::$basename);

		// Initialize icons
		if (class_exists('Mappress_Icons'))
			$icons = new Mappress_Icons();

		add_action('init', array(&$this, 'init'));
		add_action('admin_init', array(&$this, 'admin_init'));

		// Options menu
		if (class_exists('Mappress_Pro'))
			$settings = new MapPress_Pro_Settings();
		else
			$settings = new MapPress_Settings();

		add_shortcode('mappress', array(&$this, 'shortcode_map'));
		add_action('admin_notices', array(&$this, 'admin_notices'));

		// Ajax
		add_action('wp_ajax_mapp_map_save', array(&$this, 'ajax_map_save'));
		add_action('wp_ajax_mapp_map_delete', array(&$this, 'ajax_map_delete'));
		add_action('wp_ajax_mapp_map_create', array(&$this, 'ajax_map_create'));

		// Post hooks
		add_action('deleted_post', array(&$this, 'deleted_post'));

		// Filter to automatically add maps to post/page content
		add_filter('the_content', array(&$this, 'the_content'), 2);

		// Filter to alter map POIs before display
		add_filter('mapp_map_pois', array('Mappress_Map', '_mapp_map_pois'), 10, 3);

		// Filter to generate the directions panel before display
		add_filter('mapp_directions_html', array('Mappress_Map', '_mapp_directions_html'), 10, 3);

		// Scripts and stylesheets
		add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

		// Output map data in footer
		add_action( 'wp_print_footer_scripts', array(&$this, 'print_maps'));
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_maps'));
	}

	// mp_errors -> PHP errors
	// mp_info -> phpinfo + dump
	// mp_remote -> use local js
	// mp_debug -> debug mode - use non-min scripts
	// &mp_remote&mp_debug -> remote non-min
	function debugging() {
		global $wpdb;

		if (isset($_GET['mp_debug']))
			self::$debug = true;

		if (isset($_GET['mp_errors'])) {
			error_reporting(E_ALL);
			ini_set('error_reporting', E_ALL);
			ini_set('display_errors','On');
			$wpdb->show_errors();
		}

		if (isset($_GET['mp_info'])) {
			$bloginfo = array('version', 'language', 'stylesheet_url', 'wpurl', 'url');
			echo "<br/><b>bloginfo</b><br/>";
			foreach ($bloginfo as $key=>$info)
				echo "$info: " . bloginfo($info) . "<br/>";
			echo "<b>Plugin version</b> " . $this->get_version_string();
			echo "<br/><b>options</b><br/>";
			$options = Mappress_Options::get();
			print_r($options);
			echo "<br/><b>phpinfo</b><br/>";
			phpinfo();
		}
	}

	static function get_version_string() {
		$version = __('Version', 'mappress') . ":" . self::VERSION;
		if (class_exists('Mappress_Pro'))
			$version .= " PRO";
		return $version;
	}

	static function get_support_links() {
		echo self::get_version_string();
		echo " | <a target='_blank' href='http://wphostreviews.com/mappress/mappress-documentation'>" . __('Documentation', 'mappress') . "</a>";
		echo " | <a target='_blank' href='http://wphostreviews.com/forums/'>" . __('Support', 'mappress') . "</a>";
		echo " | <a target='_blank' href='http://wphostreviews.com/chris-contact'>" . __('Contact', 'mappress') . "</a>";

		if (!class_exists('Mappress_Pro'))
			echo "&nbsp;&nbsp;<a class='button-primary' href='http://wphostreviews.com/mappress' target='_blank'>" . __('Upgrade to MapPress Pro', 'mappress') . "</a>";
	}

	function ajax_map_save() {
		$mapdata = (isset($_POST['map'])) ? json_decode(stripslashes($_POST['map']), true) : null;
		$postid = (isset($_POST['postid'])) ? $_POST['postid'] : null;

		if (!$mapdata)
			$this->ajax_response(__('Internal error, map was missing.  Your data has not been saved!', 'mappress'));

		$map = new Mappress_Map($mapdata);

		$mapid = $map->save($postid);
		if ($mapid === false) {
			$this->ajax_response(__('Internal error - unable to save map.  Your data has not been saved!', 'mappress'));
		} else {
			do_action('mapp_map_save', $mapid); 	// Use for your own developments
			$this->ajax_response('OK', $mapid);
		}
	}

	function ajax_map_delete() {
		$mapid = (isset($_POST['mapid'])) ? $_POST['mapid'] : null;

		if (Mappress_Map::delete($mapid) === false) {
			$this->ajax_response(__("Internal error when deleting map ID '$mapid'!", 'mappress'));
		} else {
			do_action('mapp_map_delete', $mapid); 	// Use for your own developments
			$this->ajax_response('OK', $mapid);
		}
	}

	function ajax_map_create() {
		$postid = (isset($_POST['postid'])) ? $_POST['postid'] : null;

		$map = new Mappress_Map();
		$map->title = __('Untitled', 'mappress');

		do_action('mapp_map_create', $map);			// Use for your own developments
		$this->ajax_response('OK', array('map' => $map));
	}

	function ajax_response($status, $data=null) {
		header( "Content-Type: application/json" );
		$response = json_encode(array('status' => $status, 'data' => $data));
		die ($response);
	}

	/**
	* When a post is deleted, delete its map assignments
	*
	*/
	function deleted_post($postid) {
		Mappress_Map::delete_post_map($postid);
	}

	/**
	* Automatic map display.
	* If set, the [mappress] shortcode will be prepended/appended to the post body, once for each map
	* The shortcode is used so it can be filtered - for example WordPress will remove it in excerpts by default.
	*
	* @param mixed $content
	*/
	function the_content($content="") {
		global $post;
		global $wp_current_filter;
		static $last_post_id;

		$options = Mappress_Options::get();
		$autodisplay = $options->autodisplay;

		// No auto display
		if (!$autodisplay || $autodisplay == 'none')
			return $content;

		// Don't add the shortcode for feeds or admin screens
		if (is_feed() || is_admin())
			return $content;

		// If this is an excerpt don't attempt to add the map to it
		if (in_array('get_the_excerpt', $wp_current_filter))
			return $content;

		// Don't auto display if the post already contains a MapPress shortcode
		if (stristr($content, '[mappress') !== false || stristr($content, '[mashup') !== false)
			return $content;

		// Don't auto display more than once for the same post (some other plugins call the_content() filter multiple times for same post ID)
		if ($post->ID && $last_post_id == $post->ID)
			return $content;
		else
			$last_post_id = $post->ID;

		// Get maps associated with post
		$maps = Mappress_Map::get_post_map_list($post->ID);
		if (empty($maps))
			return $content;

		// Add the shortcode once for each map
		$shortcodes = "";
		foreach($maps as $map)
			$shortcodes .= '<p>[mappress mapid="' . $map->mapid . '"]</p>';

		if ($autodisplay == 'top')
			return $shortcodes . $content;
		else
			return $content . $shortcodes;
	}

	/**
	* Map a shortcode in a post.
	*
	* @param mixed $atts - shortcode attributes
	*/
	function shortcode_map($atts='') {
		global $post;

		// No feeds
		if (is_feed())
			return;

		// Try to protect against Relevanssi, which calls do_shortcode() in the post editor...
		if (is_admin())
			return;

		$options = Mappress_Options::get();
		$atts = $this->scrub_atts($atts);

		// Determine what to show
		$mapid = (isset($atts['mapid'])) ? $atts['mapid'] : null;
		$meta_key = $options->metaKey;

		if ($mapid) {
			// Show map by mapid
			$map = Mappress_Map::get($mapid);
		} else {
			// Get the first map attached to the post
			$maps = Mappress_Map::get_post_map_list($post->ID);
			$map = (isset ($maps[0]) ? $maps[0] : false);
		}

		if (!$map)
			return;

		return $map->display($atts);
	}

	/**
	* Post edit
	*
	* @param mixed $post
	*/
	function meta_box($post) {
		global $post;

		$maps = Mappress_Map::get_post_map_list($post->ID);
		Mappress_Map::edit($maps, $post->ID);
	}

	// Scripts & styles for backend
	function admin_enqueue_scripts($class) {
		$this->enqueue_css();
	}

	// Scripts & styles for frontend
	function wp_enqueue_scripts() {
		$this->enqueue_css();
	}

	function enqueue_css() {
		// Always include the standard styles
		wp_enqueue_style('mappress', self::$baseurl . '/css/mappress.css', null, self::VERSION);
	}


	static function enqueue_map($map, $options, $type='') {
		self::load($type);

		// If the same name is queued multiple times it will be overwritten
		self::$queue[$options->mapName] = (object) array('type' => $type, 'map' => $map, 'options' => $options );
	}

	function print_maps() {

		// If queue is empty there's nothing to do
		if (empty(self::$queue))
			return;

		foreach (self::$queue as $name => $item) {

			switch ($item->type) {

				case 'editor':
					$script = "var mapdata = " . json_encode($item->map) . ";"
						. "var options = " . json_encode($item->options) . ";"
						. "var mappEditor = new mapp.Editor(mapdata, options);";

					echo self::script($script);
					break;

				case 'map' :
				default:
					$script = "var mapdata = " . json_encode($item->map) . ";"
						. "var options = " . json_encode($item->options) . ";"
						. "var $name = new mapp.Map(mapdata, options);"
						. "$name.display();";

					echo self::script($script);
					break;
			}
		}
	}

	static function load($type = '') {
		static $loaded;

		if ($loaded)
			return;
		else
			$loaded = true;

		wp_enqueue_script("mappress-gmaps", "https://maps.googleapis.com/maps/api/js?sensor=true", null, null, true);

		$min = (self::$debug) ? "" : ".min";
		$js = (isset($_GET['mp_remote'])) ? "http://localhost/dev/wp-content/plugins/mappress-google-maps-for-wordpress/js" : plugins_url('/js', __FILE__);

		wp_enqueue_script('mappress', "$js/mappress$min.js", array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), self::VERSION, true);
		wp_localize_script('mappress', 'mappl10n', self::l10n());
	}

	static function l10n() {
		global $post;

		$l10n = array(
			'bicycling' => __('Bicycling', 'mappress'),
			'bike' => __('Bike', 'mappress'),
			'dir_not_found' => __('One of the addresses could not be found.', 'mappress'),
			'dir_zero_results' => __('Google cannot return directions between those addresses.  There is no route between them or the routing information is not available.', 'mappress'),
			'dir_default' => __('Unknown error, unable to return directions.  Status code = ', 'mappress'),
			'directions' => __('Directions', 'mappress'),
			'enter_address' => __('Enter address', 'mappress'),
			'loading' => __('Loading...', 'mappress'),
			'no_address' => __('No matching address', 'mappress'),
			'traffic' => __('Traffic', 'mappress')
		);

		if (is_admin()) {
			$l10n = array_merge($l10n, array(
				'ajax_error' => __('Error: AJAX failed!  ', 'mappress'),
				'back' => __('Back', 'mappress'),
				'cancel' => __('Cancel', 'mappress'),
				'click_and_drag' => __('Click & drag to move', 'mappress'),
				'click_to_change' => __('Click to change', 'mappress'),
				'create_map' => __('Create a new map', 'mappress'),
				'del' => __('Delete', 'mappress'),
				'delete_prompt' => __('Delete this POI?', 'mappress'),
				'delete_map_prompt' => __('Delete this map?', 'mappress'),
				'edit' => __('Edit', 'mappress'),
				'insert_into_post' => __('Insert into post', 'mappress'),
				'map_id' => __('Map ID', 'mappress'),
				'map_not_found' => __('Map not found, was it deleted?'),
				'maps_in_post' => __('Maps in this post', 'mappress'),
				'my_icons' => __('My icons', 'mappress'),
				'no_maps_in_post' => __('There are no maps yet for this post', 'mappress'),
				'save' => __('Save', 'mappress'),
				'shape' => __('Shape', 'mappress'),
				'standard_icons' => __('Standard icons', 'mappress'),
				'untitled' => __('Untitled', 'mappress'),
				'zoom' => __('Zoom', 'mappress')
			));
		}

		// Globals
		$l10n = array_merge($l10n, array(
			'postid' => (isset($post)) ? $post->ID : null,
			'baseurl' => self::$baseurl,
			'defaultIcon' => self::$options->defaultIcon,
			'ajaxurl' => admin_url('admin-ajax.php'),
			'ajaxErrors' => is_admin() || self::$debug                   // Only show AJAX errors on admin side unless debugging
		));

		if (class_exists('Mappress_Icons')) {
			$l10n = array_merge($l10n, array(
				'icons' => Mappress_Icons::$user_icons,
				'iconsUrl' => Mappress_Icons::$icons_url,
				'standardIconsUrl' => Mappress_Icons::$standard_icons_url
			));
		}
		return $l10n;
	}


	/**
	* There are several WP bugs that prevent correct activation in multisitie:
	*   http://core.trac.wordpress.org/ticket/14170
	*   http://core.trac.wordpress.org/ticket/14718)
	* These bugs have been open for months.  A workaround is to just 'activate' the plugin whenever it runs
	* (the tables are only created if they don't exist already)
	*
	*/
	function init() {
		// Load text domain
		load_plugin_textdomain('mappress', false, dirname(self::$basename) . '/languages');

		// Create database tables if they don't exist
		Mappress_Map::db_create();

		// Check if database upgrade is needed
		$current_version = get_option('mappress_version');
		update_option('mappress_version', self::VERSION);
	}

	function admin_init() {
		$options = Mappress_Options::get();

		// Add editing meta box to standard & custom post types
		foreach((array)$options->postTypes as $post_type)
			add_meta_box('mappress', 'MapPress', array($this, 'meta_box'), $post_type, 'normal', 'high');

	}

	// Sanity checks via notices
	function admin_notices() {
		global $wpdb;
		$error =  "<div id='error' class='error'><p>%s</p></div>";

		$map_table = $wpdb->prefix . "mappress_maps";
		$result = $wpdb->get_var("show tables like '$map_table'");

		if (strtolower($result) != strtolower($map_table)) {
			echo sprintf($error, __("MapPress database tables are missing.  Please deactivate the plugin and activate it again to fix this.", 'mappress'));
			return;
		}

		if (get_bloginfo('version') < "3.2") {
			echo sprintf($error, __("WARNING: MapPress now requires WordPress 3.2 or higher.  Please upgrade before using MapPress.", 'mappress'));
			return;
		}

		if (class_exists('WPGeo')) {
			echo sprintf($error, __("WARNING: MapPress is not compatible with the WP-Geo plugin.  Please deactivate or uninstall WP-Geo before using MapPress.", 'mappress'));
			return;
		}
	}

	/**
	* Scrub attributes
	* The WordPress shortcode API passes shortcode attributes in lowercase and with boolean values as strings (e.g. "true")
	* It's also impossible to pass array attributes without using a serialized array
	* This function converts atts to lowercase, replaces boolean strings with booleans, and creates arrays from 'flattened' attributes
	* Like center, point, viewport, etc.
	*
	* Returns empty array if $atts is empty or not an array
	*/
	function scrub_atts($atts=null) {
		if (!$atts || !is_array($atts))
			return array();

		// WP unfortunately passes booleans as strings
		foreach((array)$atts as $key => $value) {
			if ($value === "true")
				$atts[$key] = true;
			if ($value === "false")
				$atts[$key] = false;
		}

		// Shortcode attributes are lowercase so convert everything to lowercase
		$atts = array_change_key_case($atts);

		// Array attributes are 'flattened' when passed via shortcode
		// Point
		if (isset($atts['point_lat']) && isset($atts['point_lng'])) {
			$atts['point'] = array('lat' => $atts['point_lat'], 'lng' => $atts['point_lng']);
			unset($atts['point_lat'], $atts['point_lng']);
		}

		// Viewport
		if (isset($atts['viewport_sw_lat']) && isset($atts['viewport_sw_lng']) && isset($atts['viewport_ne_lat'])
		&& isset($atts['viewport_ne_lng'])) {
			$atts['viewport'] = array(
				'sw' => array('lat' => $atts['viewport_sw_lat'], 'lng' => $atts['viewport_sw_lng']),
				'ne' => array('lat' => $atts['viewport_ne_lat'], 'lng' => $atts['viewport_ne_lng'])
			);
			unset($atts['viewport_sw_lat'], $atts['viewport_sw_lng'], $atts['viewport_ne_lat'], $atts['viewport_ne_lng']);
		}

		// Center
		if (isset($atts['center_lat']) && isset($atts['center_lng'])) {
			$atts['center'] = array('lat' => $atts['center_lat'], 'lng' => $atts['center_lng']);
			unset($atts['center_lat'], $atts['center_lng']);
		}

		// OverviewMapControlOptions
		if (isset($atts['initialopenoverviewmap']) && $atts['initialopenoverviewmap'] == true) {
			$atts['overviewmapcontroloptions']['opened'] = true;
		}

		return $atts;
	}

	/**
	* Output javascript
	*
	* @param mixed $script
	*/
	static function script($script) {
		return "\r\n<script type='text/javascript'>\r\n/* <![CDATA[ */\r\n$script\r\n/* ]]> */\r\n</script>\r\n";
	}


	/**
	* Show a dropdown list
	*
	* $args values:
	*   id ('') - HTML id for the dropdown field
	*   selected (null) - currently selected key value
	*   ksort (true) - sort the array by keys, ascending
	*   asort (false) - sort the array by values, ascending
	*   none (false) - add a blank entry; set to true to use '' or provide a string (like '-none-')
	*   select_attr - string to apply to the <select> tag, e.g. "DISABLED"
	*
	* @param array  $data  - array of (key => description) to display.  If description is itself an array, only the first column is used
	* @param string $selected - currently selected value
	* @param string $name - HTML field name
	* @param mixed  $args - arguments to modify the display
	*
	*/
	static function dropdown($data, $selected, $name='', $args=null) {
		$defaults = array(
			'id' => $name,
			'asort' => false,
			'ksort' => false,
			'none' => false,
			'select_attr' => ""
		);

		if (!is_array($data) || empty($data))
			return;

		// Data is in key => value format.  If value is itself an array, use only the 1st column
		foreach($data as $key => &$value) {
			if (is_array($value))
				$value = array_shift($value);
		}

		extract(wp_parse_args($args, $defaults));

		if ($asort)
			asort($data);
		if ($ksort)
			ksort($data);

		// If 'none' arg provided, prepend a blank entry
		if ($none) {
			if ($none === true)
				$none = '';
			$data = array('' => $none) + $data;    // Note that array_merge() won't work because it renumbers indexes!
		}

		if (!$id)
			$id = $name;

		$name = ($name) ? "name='$name'" : "";
		$id = ($id) ? "id='$id'" : "";

		$html = "<select $name $id $select_attr>";

		foreach ((array)$data as $key => $description) {
			$key = esc_attr($key);
			$description = esc_attr($description);

			$html .= "<option value='$key' " . selected($selected, $key, false) . ">$description</option>";
		}
		$html .= "</select>";
		return $html;
	}
}  // End Mappress class

if (class_exists('Mappress_Pro'))
	$mappress = new Mappress_Pro();
else
	$mappress = new Mappress();
?>