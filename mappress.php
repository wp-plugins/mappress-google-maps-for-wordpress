<?php
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 2.38.4
Author: Chris Richardson
Thanks to all the translators and to Matthias Stasiak for his wonderful icons (http://code.google.com/p/google-maps-icons/)
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/

@require_once dirname( __FILE__ ) . '/mappress_obj.php';
@require_once dirname( __FILE__ ) . '/mappress_poi.php';
@require_once dirname( __FILE__ ) . '/mappress_map.php';
@require_once dirname( __FILE__ ) . '/mappress_settings.php';
@require_once dirname( __FILE__ ) . '/mappress_updater.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro_settings.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_icons.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_widget.php';

class Mappress {
	const VERSION = '2.38.4';

	static
		$debug,
		$remote,
		$options,
		$baseurl,
		$basename,
		$basedir,
		$pages,
		$updater;

	function __construct()  {
		self::$options = Mappress_Options::get();
		self::$basename = plugin_basename(__FILE__);
		self::$baseurl = plugins_url('', __FILE__);
		self::$basedir = dirname(__FILE__);

		$this->debugging();

		// Create updater
		self::$updater = new Mappress_Updater(self::$basename);

		// Initialize icons
		if (class_exists('Mappress_Icons'))
			$icons = new Mappress_Icons();

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('init', array(&$this, 'init'));
		add_action('admin_init', array(&$this, 'admin_init'));

		add_shortcode('mappress', array(&$this, 'shortcode_map'));
		add_action('admin_notices', array(&$this, 'admin_notices'));

		// Ajax
		add_action('wp_ajax_mapp_map_save', array(&$this, 'ajax_map_save'));
		add_action('wp_ajax_mapp_map_delete', array(&$this, 'ajax_map_delete'));
		add_action('wp_ajax_mapp_map_timing', array(&$this, 'ajax_map_timing'));

		// Post hooks
		add_action('deleted_post', array(&$this, 'deleted_post'));

		// Filter to automatically add maps to post/page content
		add_filter('the_content', array(&$this, 'the_content'), 2);

		// Scripts and stylesheets
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_css'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_css'));

		// Output map data in footer
		add_action( 'wp_print_footer_scripts', array(&$this, 'print_maps'));
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_maps'));
	}

	// mp_errors -> PHP errors
	// mp_info -> phpinfo + dump
	// mp_dev -> use remote js
	// mp_debug -> debug mode
	// &mp_remote&mp_debug -> remote non-min
	function debugging() {
		global $wpdb;

		if (isset($_GET['mp_remote']))
			self::$remote = true;

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
			print_r(self::$options);
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

	function ajax_map_timing() {
		$this->ajax_response('OK');
	}

	function ajax_map_save() {
		ob_start();

		$mapdata = (isset($_POST['map'])) ? json_decode(stripslashes($_POST['map']), true) : null;
		$postid = (isset($_POST['postid'])) ? $_POST['postid'] : null;

		if (!$mapdata)
			$this->ajax_response(__('Internal error, map was missing.  Your data has not been saved!', 'mappress'));

		$map = new Mappress_Map($mapdata);
		$mapid = $map->save($postid);

		if ($mapid === false) {
			$this->ajax_response(__('Internal error - unable to save map.  Your data has not been saved!', 'mappress'));
		} else {
			do_action('mappress_map_save', $mapid); 	// Use for your own developments
			$this->ajax_response('OK', array('mapid' => $mapid));
		}
	}

	function ajax_map_delete() {
		ob_start();

		$mapid = (isset($_POST['mapid'])) ? $_POST['mapid'] : null;
		$result = Mappress_Map::delete($mapid);

		if (!$result) {
			$this->ajax_response(__("Internal error when deleting map ID '$mapid'!", 'mappress'));
		} else {
			do_action('mappress_map_delete', $mapid); 	// Use for your own developments
			$this->ajax_response('OK', array('mapid' => $mapid));
		}
	}

	function ajax_response($status, $data=null) {
		$output= ob_get_clean();
		header( "Content-Type: application/json" );
		$response = json_encode(array('status' => $status, 'data' => $data, 'output' => $output));
		die ($response);
	}

	/**
	* When a post is deleted, delete its map assignments
	*
	*/
	function deleted_post($postid) {
		Mappress_Map::delete_post_map($postid);
	}

	function admin_menu() {
		$pages = array();

		// Settings
		$settings = (class_exists('Mappress_Pro')) ? new Mappress_Pro_Settings() : new Mappress_Settings();
		self::$pages[] = add_menu_page('MapPress', 'MapPress', 'manage_options', 'mappress', array(&$settings, 'options_page'), self::$baseurl . '/images/mappress_pin_logo.png');
		// $pages[] = add_submenu_page('mappress', '', '', 'manage_options', 'mappress', array(&$settings, 'options_page'));
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

		$autodisplay = self::$options->autodisplay;

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

		$atts = $this->scrub_atts($atts);

		// Determine what to show
		$mapid = (isset($atts['mapid'])) ? $atts['mapid'] : null;

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
		$maps = Mappress_Map::get_post_map_list($post->ID);
		Mappress_Map::edit($maps, $post->ID);
	}

	// Scripts & styles for backend
	// CSS is always loaded from the plugin directory
	function admin_css($hook) {
		// Settings page
		if ($hook == self::$pages[0]) {
			wp_enqueue_script('postbox');
			wp_enqueue_script( 'farbtastic' );
			wp_enqueue_style('farbtastic');
			wp_enqueue_style('mappress', self::$baseurl . '/css/mappress.css', null, self::VERSION);
		}

		// Post / page edit
		if ($hook == 'edit.php' || $hook == 'post.php' || $hook == 'post-new.php')
			wp_enqueue_style('mappress', self::$baseurl . '/css/mappress.css', null, self::VERSION);
	}

	// Scripts & styles for frontend
	// CSS is loaded from: child theme, theme, or plugin directory
	function frontend_css() {
		// Don't load any CSS at all
		if (self::$options->noCSS)
			return;

		// Load the default CSS from the plugin directory
		wp_enqueue_style('mappress', self::$baseurl . '/css/mappress.css', null, self::VERSION);

		// If a 'mappress.css' exists in the theme directory, load that afterwards
		$file = "";
		if ( @file_exists( get_stylesheet_directory() . '/mappress.css' ) )
			$file = get_stylesheet_directory_uri() . '/mappress.css';
		elseif ( @file_exists( get_template_directory() . '/mappress.css' ) )
			$file = get_template_directory_uri() . '/mappress.css';

		if ($file)
			wp_enqueue_style('mappress-custom', $file, array('mappress'), self::VERSION);
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

		if ($current_version < '2.38.2') {
			self::$options->metaKeys = array(self::$options->metaKey);
			self::$options->save();
		}

		update_option('mappress_version', self::VERSION);
	}

	function admin_init() {
		// Add editing meta box to standard & custom post types
		foreach((array)self::$options->postTypes as $post_type)
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

		// Center - back compat for center_lat/center_lng
		if (isset($atts['center_lat']) && isset($atts['center_lng'])) {
			$atts['center'] = array('lat' => $atts['center_lat'], 'lng' => $atts['center_lng']);
			unset($atts['center_lat'], $atts['center_lng']);
		} elseif (isset($atts['center'])) {
			$latlng = explode(',', $atts['center']);
			if (count($latlng) == 2)
				$atts['center'] = array('lat' => $latlng[0], 'lng' => $latlng[1]);
			else
				unset($atts['center']);
		}

		// MapTypeIds
		if (isset($atts['maptypeids']))
			$atts['maptypeids'] = explode(',', $atts['maptypeids']);

		// Poi Links
		if (isset($atts['poilinks']))
			$atts['poilinks'] = explode(',', $atts['poilinks']);

		// Map links
		if (isset($atts['maplinks']))
			$atts['maplinks'] = explode(',', $atts['maplinks']);

		return $atts;
	}

	static function string_to_boolean($data) {
		if ($data === 'false')
			return false;

		if ($data === 'true')
			return true;

		if (is_array($data)) {
			foreach($data as &$datum)
				$datum = self::string_to_boolean($datum);
		}

		return $data;
	}

	static function boolean_to_string($data) {
		if ($data === false)
			return "false";
		if ($data === true)
			return "true";
		return $data;
	}

	/**
	* Output javascript
	*
	* @param mixed $script
	*/
	static function script($script) {
		return "\r\n<script type='text/javascript'>\r\n/* <![CDATA[ */\r\n$script\r\n/* ]]> */\r\n</script>\r\n";
	}

	function enqueue_map($map, $type='map', $options = null) {
		// Load scripts
		$this->load($type);

		// Queue
		$name = ($type == 'editor') ? $options->name : $map->name;
		$this->queue[$name] = (object) array('type' => $type, 'map' => $map, 'options' => $options );
	}

	function print_maps() {
		// If queue is empty there's nothing to do
		if (empty($this->queue))
			return;

		if (class_exists('Mappress_Pro'))
			$this->print_map_styles();

		foreach ($this->queue as $name => $item) {
			switch ($item->type) {
				case 'editor':
					$script = "var mapdata = " . json_encode($item->map) . ";"
						. "var options = " . json_encode($item->options) . ";"
						. "var mappEditor = new mapp.Editor(mapdata, options);";

					echo Mappress::script($script);
					break;

				case 'map' :
				default:
					$this->print_map($item->map);

					$script = "var mapdata = " . json_encode($item->map) . ";"
						. "var $name = new mapp.Map(mapdata);"
						. "$name.display();";

					echo Mappress::script($script);
					break;
			}
		}
	}

	function print_map($map) {
		// Last chance to alter map or pois before display
		do_action('mappress_map_display', $map);

		// Assign pois to map for template functions
		foreach($map->pois as $poi)
			$poi->map($map);

		// Sort the pois
		$map->sort_pois();

		// Get html
		foreach($map->pois as $poi)
			$poi->get_html();

		if (class_exists('Mappress_Pro'))
			$this->print_poi_list($map);

		if ($map->options->directions == 'inline')
			$this->print_directions($map);
	}

	function print_directions($map) {
		echo "<div id='{$map->name}_directions_' style='display:none'>";
		require(Mappress::$basedir . '/forms/map_directions.php');
		echo "</div>";
	}

	function load($type = '') {
		static $loaded;

		if ($loaded)
			return;
		else
			$loaded = true;

		$url = (self::$remote) ? "http://localhost/dev/wp-content/plugins/mappress-google-maps-for-wordpress/" : self::$baseurl;
		$js = (self::$debug) ? "$url/src" : "$url/js";

		$version = Mappress::VERSION;

		$min = (self::$debug) ? "" : ".min";

		if ($type == 'editor' || $type == 'poi')
			wp_enqueue_script('mappress_editor', "$js/mappress_editor$min.js", array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), $version, true);

		if ($type == 'map' && self::$options->dataTables) {
			wp_enqueue_script('mappress_datatables', "$js/DataTables/media/js/jquery.dataTables$min.js", array('jquery'), $version, true);
			wp_enqueue_style('mappress-datatables', "$js/DataTables/media/css/jquery.dataTables.css", null, '1.9.1');
			}

		$lib = ($type == 'editor') ? "&libraries=drawing" : "";
		wp_enqueue_script("mappress-gmaps", "https://maps.googleapis.com/maps/api/js?sensor=true$lib", null, null, true);

		if (self::$debug) {
			wp_enqueue_script('mappress', "$js/mappress.js", array('jquery'), $version, true);
			wp_enqueue_script('mappress', "$js/mappress_json.js", null, $version, true);
			wp_enqueue_script('mappress_colorpicker', "$js/mappress_colorpicker.js", null, $version, true);
			wp_enqueue_script('mappress_geocoding', "$js/mappress_geocoding.js", null, $version, true);
			wp_enqueue_script('mappress_infobox', "$js/mappress_infobox.js", null, $version, true);
			wp_enqueue_script('mappress_directions', "$js/mappress_directions.js", null, $version, true);
			wp_enqueue_script('mappress_icons', "$js/mappress_icons.js", null, $version, true);
		} else {
			wp_enqueue_script('mappress', "$js/mappress.min.js", array('jquery'), $version, true);
		}

		wp_localize_script('mappress', 'mappl10n', $this->l10n());
	}

	function l10n() {
		global $post;

		$l10n = array(
			'bicycling' => __('Bicycling', 'mappress'),
			'bike' => __('Bike', 'mappress'),
			'dir_not_found' => __('One of the addresses could not be found.', 'mappress'),
			'dir_zero_results' => __('Google cannot return directions between those addresses.  There is no route between them or the routing information is not available.', 'mappress'),
			'dir_default' => __('Unknown error, unable to return directions.  Status code = ', 'mappress'),
			'directions' => __('Directions', 'mappress'),
			'enter_address' => __('Enter address', 'mappress'),
			'kml_error' => __('Error reading KML file', 'mappress'),
			'loading' => __('Loading...', 'mappress'),
			'my_location' => __('My location', 'mappress'),
			'no_address' => __('No matching address', 'mappress'),
			'no_geolocate' => __('Unable to get your location', 'mappress'),
			'traffic' => __('Traffic', 'mappress'),
			'zoom' => __('Zoom', 'mappress')
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
				'my_icons' => __('My icons', 'mappress'),
				'save' => __('Save', 'mappress'),
				'shape' => __('Shape', 'mappress'),
				'standard_icons' => __('Standard icons', 'mappress'),
				'untitled' => __('Untitled', 'mappress')
			));
		}

		// Globals
		$l10n = array_merge($l10n, array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'ajaxErrors' => is_admin() || Mappress::$debug,
			'baseurl' => Mappress::$baseurl,
			'defaultIcon' => Mappress::$options->defaultIcon,
			'geolocation' => Mappress::$options->geolocation,
			'postid' => ($post) ? $post->ID : null
		));

		return $l10n;
	}

}  // End Mappress class

if (class_exists('Mappress_Pro'))
	$mappress = new Mappress_Pro();
else
	$mappress = new Mappress();
?>