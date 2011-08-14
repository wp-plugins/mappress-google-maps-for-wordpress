<?php
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 2.37
Author: Chris Richardson
Thanks to all the translators and to Matthias Stasiak for some icons (http://code.google.com/p/google-maps-icons/)
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/


class Mappress {
	var $version = '2.37',
		$debug = false,
		$basename,
		$baseurl,
		$basepath,
		$pagehook,
		$updater;

	function mappress()  {
		$options = Mappress_Options::get();

		$this->debugging();

		$this->basename = plugin_basename(__FILE__);
		$this->baseurl = plugins_url('', __FILE__);
		$this->basepath = dirname(__FILE__);

		// Create updater
		$this->updater = new Mappress_Updater($this->basename);

		add_action('init', array(&$this, 'init'));
		add_action('admin_init', array(&$this, 'admin_init'));

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_shortcode('mappress', array(&$this, 'shortcode_map'));
		add_action('admin_notices', array(&$this, 'admin_notices'));

		// Ajax
		add_action('wp_ajax_mapp_map_save', array(&$this, 'ajax_map_save'));
		add_action('wp_ajax_mapp_map_delete', array(&$this, 'ajax_map_delete'));
		add_action('wp_ajax_mapp_map_create', array(&$this, 'ajax_map_create'));

		// Post hooks
		add_action('deleted_post', array(&$this, 'deleted_post'));

		// GeoRSS feeds
		if ($options->geoRSS) {
			add_action( 'rss2_ns', array( &$this, 'rss_ns' ) );
			add_action( 'atom_ns', array( &$this, 'rss_ns' ) );
			add_action( 'rdf_ns', array( &$this, 'rss_ns' ) );
			add_action( 'rdf_item', array( &$this, 'rss_item' ) );
			add_action( 'rss_item', array( &$this, 'rss_item' ) );
			add_action( 'rss2_item', array( &$this, 'rss_item' ) );
			add_action( 'atom_entry', array( &$this, 'rss_item' ) );
		}

		// Filters
		add_filter('the_content', array(&$this, 'the_content'), 2);
		add_filter('mapp_directions_html', array('Mappress_Map', '_mapp_directions_html'), 10, 3);
	}

	// mp_errors -> PHP errors
	// mp_info -> phpinfo + dump
	// mp_remote -> use local js
	// mp_debug -> debug mode - use non-min scripts
	// &mp_remote&mp_debug -> remote non-min
	function debugging() {
		global $wpdb;

		if (isset($_GET['mp_debug']))
			$this->debug = true;

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
			echo "<b>Plugin version</b> " . $this->get_version();
			echo "<br/><b>options</b><br/>";
			$options = Mappress_Options::get();
			print_r($options);
			echo "<br/><b>maps</b><br/>";
			$maps = Mappress_Map::get_list();
			print_r($maps);

			echo "<br/><b>legacy maps</b><br/>";
			$sql = "SELECT m.post_id, p.post_title FROM $wpdb->postmeta m, $wpdb->posts p "
				. " WHERE m.meta_key = '_mapp_pois' AND m.post_id = p.id AND m.meta_value != ''";
			$results = $wpdb->get_results($sql);
			foreach ((array)$results as $result) {
				// Get original metadata
				$mapdata = get_post_meta($result->post_id, '_mapp_map', true);
				$poidata = get_post_meta($result->post_id, '_mapp_pois', true);
				if ($mapdata === false)
					$mapdata = "Unable to parse mapdata";
				if ($poidata === false)
					$poidata = "Unable to parse poidata";

				if (!is_array($mapdata)) {
					echo "Mapdata is in string format! ";
					$mapdata = unserialize($mapdata);
				}
				if (!is_array($poidata)) {
					echo "Poidata is in string format! ";
					$poidata = unserialize($poidata);
				}

				echo "MAP for post $result->post_id ($result->post_title): " . print_r($mapdata, true) . "<br/>";
				echo "POIS for post $result->post_id ($result->post_title): " . print_r($poidata, true) . "<br/>";
			}

			echo "<br/><b>phpinfo</b><br/>";
			phpinfo();
		}

		if (isset($_GET['mp_force_upgrade'])) {
			$maps_table = $wpdb->prefix . 'mappress_maps';
			$posts_table = $wpdb->prefix . 'mappress_posts';

			delete_option('mappress_version');
			delete_option('mappress_options');
			$result = $wpdb->query ("DROP TABLE $maps_table;");
			$result = $wpdb->query ("DROP TABLE $posts_table;");
		}
	}

	function get_version() {
		$version = __('Version', 'mappress') . ":" . $this->version;
		if (class_exists('Mappress_Pro'))
			$version .= " PRO";
		return $version;
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

	/**
	* Add admin menu
	*/
	function admin_menu() {
		$options = Mappress_Options::get();

		// Add menu
		$this->pagehook = add_options_page('MapPress', 'MapPress', 'manage_options', 'mappress', array(&$this, 'options_page'));

		// Add meta box to standard & custom post types
		if ($options) {
			foreach((array)$options->postTypes as $post_type)
				add_meta_box('mappress', 'MapPress', array($this, 'meta_box'), $post_type, 'normal', 'high');
		}

		// Add settings scripts
		add_action("admin_print_scripts-{$this->pagehook}", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_styles-{$this->pagehook}", array(&$this, 'admin_print_styles'));
	}

	/**
	* Scripts and styles for settings screen
	*
	*/
	function admin_print_scripts() {
		wp_enqueue_script('postbox');
        wp_enqueue_script( 'farbtastic' );  
	}                                 

	function admin_print_styles() {
		wp_enqueue_style('mappress', plugins_url('/css/mappress.css', __FILE__), null, $this->version);
        wp_enqueue_style('farbtastic');          
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
		load_plugin_textdomain('mappress', false, dirname($this->basename) . '/languages');

		// Create database tables if they don't exist
		Mappress_Map::db_create();

		// Check if database upgrade is needed
		$current_version = get_option('mappress_version');
		update_option('mappress_version', $this->version);

		if (!$current_version)
			$this->activation_171();
	}

	/**
	* Upgrade from version 1.7.1 and older
	*
	*/
	function activation_171() {
		global $wpdb;

		// Read all posts with map metadata
		$sql = "SELECT m.post_id, p.post_title FROM $wpdb->postmeta m, $wpdb->posts p "
			. " WHERE m.meta_key = '_mapp_pois' AND m.post_id = p.id AND m.meta_value != ''";
		$results = $wpdb->get_results($sql);

		// Convert maps and pois
		foreach ((array)$results as $post) {
			// Get original metadata
			$mapdata = get_post_meta($post->post_id, '_mapp_map', true);
			$poidata = get_post_meta($post->post_id, '_mapp_pois', true);

			// For some reason, some folks had serialized strings in metadata.  Fix if we're forcing upgrade.
			if (isset($_GET['mp_force_upgrade'])) {
				if (!is_array($mapdata))
					$mapdata = unserialize($mapdata);
				if (!is_array($poidata))
					$poidata = unserialize($poidata);

				echo "MAP for post $post->post_id ($post->post_title): " . print_r($mapdata, true) . "<br/>";
				echo "POIS for post $post->post_id ($post->post_title): " . print_r($poidata, true) . "<br/>";

				if (!$mapdata || !$poidata)
					continue;
			}

			$pois = array();
			if ($poidata) {
				foreach((array)$poidata as $poi) {
					// New POI format
					$pois[] = new Mappress_Poi(array(
						'point' => array('lat' => $poi['lat'], 'lng' => $poi['lng']),
						'title' => isset($poi['caption']) ? $poi['caption'] : '',
						'body' => isset($poi['body']) ? $poi['body'] : '',
						'address' => $poi['address'],
						'correctedAddress' => $poi['corrected_address'],
						'iconid' => null,
						'viewport' => array(
							'sw' => array('lat' => $poi['boundsbox']['south'], 'lng' => $poi['boundsbox']['west']),
							'ne' => array('lat' => $poi['boundsbox']['north'], 'lng' => $poi['boundsbox']['east'])
						)
					));
				}
			}

			// Convert map types
			$mapTypeId = $mapdata['maptype'];
			if ($mapTypeId != 'roadmap' && $mapTypeId != 'satellite' && $mapTypeId != 'terrain' && $mapTypeId != 'hybrid')
				$mapTypeId = 'roadmap';
			else
				$mapTypeId = strtolower($mapTypeId);

			// Create map object
			$map = new Mappress_Map(array(
				'id' => null,
				'width' => $mapdata['width'],
				'height' => $mapdata['height'],
				'zoom' => $mapdata['zoom'],
				'center' => array('lat' => $mapdata['center_lat'], 'lng' => $mapdata['center_lng']),
				'mapTypeId' => $mapTypeId,
				'pois' => $pois
			));


			// Only save maps that have pois
			$result = $map->save($post->post_id);
			if (!$result)
				die("Unable to save new maps data");
		}

		// Convert options
		$options = get_option('mappress');
		if ($options && isset($options['map_options'])) {
			$options = $options['map_options'];

			$new_options = new Mappress_Options(array(
				'directions' => (isset($options['directions']) && $options['directions']) ? 'inline' : 'none',
				'mapTypeControl' => (isset($options['maptypes']) && $options['maptypes']) ? true : false,
				'scrollwheel' => (isset($options['scrollwheel_zoom']) && $options['scrollwheel_zoom']) ? true : false,
				'initialOpenInfo' => (isset($options['open_info']) && $options['open_info']) ? true : false,
				'country' => (isset($options['country']) && !empty($options['country'])) ? $options['country'] : null,
				'language' => (isset($options['language']) && !empty($options['language'])) ? $options['language'] : null,
			));
		} else {
			$new_options = new Mappress_Options();
		}

		// Save under new key
		$new_options->save();
	}


	function admin_init() {           
		register_setting('mappress', 'mappress_options', array($this, 'set_options'));

		add_settings_section('basic_settings', __('Basic Settings', 'mappress'), array(&$this, 'section_settings'), 'mappress');
        add_settings_field('demoMap', __('Show a sample map on this page', 'mappress'), array(&$this, 'set_demo_map'), 'mappress', 'basic_settings');        
		add_settings_field('autodisplay', __('Automatic map display', 'mappress'), array(&$this, 'set_autodisplay'), 'mappress', 'basic_settings');
		add_settings_field('postTypes', __('Post types', 'mappress'), array(&$this, 'set_post_types'), 'mappress', 'basic_settings');
		add_settings_field('directions', __('Directions', 'mappress'), array(&$this, 'set_directions'), 'mappress', 'basic_settings');
		add_settings_field('poiList', __('Marker list', 'mappress'), array(&$this, 'set_poi_list'), 'mappress', 'basic_settings');
		add_settings_field('mapTypeControl', __('Map types', 'mappress'), array(&$this, 'set_map_type_control'), 'mappress', 'basic_settings');
		add_settings_field('streetViewControl', __('Street View', 'mappress'), array(&$this, 'set_streetview_control'), 'mappress', 'basic_settings');
		add_settings_field('scrollwheel', __('Scroll wheel zoom', 'mappress'), array(&$this, 'set_scrollwheel'), 'mappress', 'basic_settings');
		add_settings_field('keyboard', __('Keyboard shortcuts', 'mappress'), array(&$this, 'set_keyboard_shortcuts'), 'mappress', 'basic_settings');
		add_settings_field('initialOpenInfo', __('Open first marker', 'mappress'), array(&$this, 'set_initial_open_info'), 'mappress', 'basic_settings');
		add_settings_field('traffic', __('Show traffic button', 'mappress'), array(&$this, 'set_traffic'), 'mappress', 'basic_settings');
		add_settings_field('tooltips', __('Tooltips', 'mappress'), array(&$this, 'set_tooltips'), 'mappress', 'basic_settings');
		add_settings_field('overviewMapControl', __('Overview map', 'mappress'), array(&$this, 'set_overview_map_control'), 'mappress', 'basic_settings');

        add_settings_section('css_settings', __('CSS Settings', 'mappress'), array(&$this, 'section_settings'), 'mappress');
        add_settings_field('alignment', __('Map alignment', 'mappress'), array(&$this, 'set_alignment'), 'mappress', 'css_settings');
        add_settings_field('border', __('Map border', 'mappress'), array(&$this, 'set_border'), 'mappress', 'css_settings');        
        // 0812customcss
        // add_settings_field('customCSS', __('Custom CSS', 'mappress'), array(&$this, 'set_custom_css'), 'mappress', 'misc_settings');
        
		add_settings_section('localization_settings', __('Localization', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('language', __('Language', 'mappress'), array(&$this, 'set_language'), 'mappress', 'localization_settings');
		add_settings_field('country', __('Country', 'mappress'), array(&$this, 'set_country'), 'mappress', 'localization_settings');
		add_settings_field('directionsServer', __('Directions server', 'mappress'), array(&$this, 'set_directions_server'), 'mappress', 'localization_settings');

		add_settings_section('template_settings', __('Templates', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('poiListTemplate', __('Marker list template', 'mappress'), array(&$this, 'set_poi_list_template'), 'mappress', 'template_settings');

		add_settings_section('custom_field_settings', __('Custom Fields', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('metaKey', __('Custom fields', 'mappress'), array(&$this, 'set_meta_key'), 'mappress', 'custom_field_settings');

		//@todo add_settings_section('georss_settings', __('GeoRSS', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		//@todo add_settings_field('geoRSS', __('GeoRSS', 'mappress'), array(&$this, 'set_georss'), 'mappress', 'georss_settings');

		add_settings_section('misc_settings', __('Micsellaneous', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('mapSizes', __('Map sizes', 'mappress'), array(&$this, 'set_map_sizes'), 'mappress', 'misc_settings');
		add_settings_field('forceresize', __('Force resize', 'mappress'), array(&$this, 'set_force_resize'), 'mappress', 'misc_settings');
		add_settings_field('link', __('MapPress link', 'mappress'), array(&$this, 'set_control'), 'mappress', 'misc_settings');
	}

	function set_options($input) {
		// If reset defaults was clicked
		if (isset($_POST['reset_defaults'])) {
			$options = new Mappress_Options();
			return get_object_vars($this);
		}

		// If resize was clicked then resize ALL maps
		if (isset($_POST['force_resize']) && $_POST['resize_from']['width'] && $_POST['resize_from']['height']
		&& $_POST['resize_to']['width'] && $_POST['resize_to']['height']) {
			$maps = Mappress_Map::get_list();
			foreach ($maps as $map) {
				if ($map->width == $_POST['resize_from']['width'] && $map->height == $_POST['resize_from']['height']) {
					$map->width = $_POST['resize_to']['width'];
					$map->height = $_POST['resize_to']['height'];
					$map->save($postid);
				}
			}
		}
        
        // If NO post types selected, set value to empty array
        if (!isset($input['postTypes']))
            $input['postTypes'] = array();

		// Force checkboxes to boolean
        foreach($input as &$item) 
            $item = self::convert_to_boolean($item);

		if (class_exists('Mappress_Pro')) {
			$input = $this->set_options_pro($input);
		} else {
			$input['control'] = true;
			unset($input['metaKey'], $input['metaSyncSave'], $input['metaSyncUpdate']);
		}
		return $input;
	}

	function section_settings() {
	}

	function set_country() {
		$options = Mappress_Options::get();
		$country = $options->country;
		$cctld_link = '<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("country code", 'mappress') . '</a>';

		printf(__('Enter a %s to use when searching (leave blank for USA)', 'mappress'), $cctld_link);
		echo ": <input type='text' size='2' name='mappress_options[country]' value='$country' />";
	}

	function set_directions_server() {
		$options = Mappress_Options::get();
		$directions_server = $options->directionsServer;

		echo __('Enter a google server URL for directions/printing');
		echo ": <input type='text' size='20' name='mappress_options[directionsServer]' value='$directions_server' />";
	}

	function set_scrollwheel() {
		$options = Mappress_Options::get();        
        echo self::checkbox($options->scrollwheel, 'mappress_options[scrollwheel]');
		_e('Enable zoom with the mouse scroll wheel', 'mappress');
	}

	function set_keyboard_shortcuts() {
		$options = Mappress_Options::get();
        echo self::checkbox($options->keyboardShortcuts, 'mappress_options[keyboardShortcuts]');
		_e('Enable keyboard panning and zooming', 'mappress');
	}

	function set_language() {
		$options = Mappress_Options::get();
		$language = $options->language;
		$lang_link = '<a target="_blank" href="http://code.google.com/apis/maps/faq.html#languagesupport">' . __("language", 'mappress') . '</a>';

		printf(__('Use a specific %s for map controls (defaults to browser language)', 'mappress'), $lang_link);
		echo ": <input type='text' size='2' name='mappress_options[language]' value='$language' />";

	}

	function set_map_type_control() {
		$options = Mappress_Options::get();
        echo self::checkbox($options->mapTypeControl, 'mappress_options[mapTypeControl]');
		_e ('Allow your readers to change the map type (street, satellite, or hybrid)', 'mappress');
	}

	function set_streetview_control() {
		$options = Mappress_Options::get();
        echo self::checkbox($options->streetViewControl, 'mappress_options[streetViewControl]');
		_e ('Display the street view control "peg man"', 'mappress');
	}

	function set_directions() {
		$options = Mappress_Options::get();
		$directions = $options->directions;

		echo "<input type='radio' name='mappress_options[directions]' value='inline' " . checked($directions, 'inline', false) . "/>";
		echo __('Inline (in your blog)', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[directions]' value='google' " . checked($directions, 'google', false) . "/>";
		echo __('Google', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[directions]' value='none' "  . checked($directions, 'none', false) . " />";
		echo __('None', 'mappress');

		echo "<br/><i>" . __("Select 'Google' if directions aren't displaying properly in your theme", 'mappress') . "</i>";
	}

	function set_poi_list() {
		$options = Mappress_Options::get();
		$pro_link = "<a href='http://wphostreviews.com/mappress/mappress' title='MapPress Pro'>MapPress Pro</a>";

		printf(__("This setting requires %s.", 'mappress'), $pro_link);
		echo " " . __("Show a list of markers under each map", 'mappress');
	}

	function set_initial_open_info() {
		$options = Mappress_Options::get();
        echo self::checkbox($options->initialOpenInfo, 'mappress_options[initialOpenInfo]');
		_e('Automatically open the first marker when a map is displayed', 'mappress');
	}

	function set_traffic() {
		$options = Mappress_Options::get();
        
		echo self::checkbox($options->traffic, 'mappress_options[traffic]');
		_e('Show a button for real-time traffic conditions', 'mappress');
        
        echo "<br/>" . self::checkbox($options->initialTraffic, 'mappress_options[initialTraffic]');
        _e("Set traffic 'on' by default", 'mappress');        
	}

	function set_tooltips() {
		$options = Mappress_Options::get();
        echo self::checkbox($options->tooltips, 'mappress_options[tooltips]');
		_e('Show marker titles as a "tooltip" on mouse-over', 'mappress');
	}

	function set_overview_map_control() {
		$options = Mappress_Options::get();
        
		echo self::checkbox($options->overviewMapControl, 'mappress_options[overviewMapControl]');
		_e('Show an overview map control in the bottom-right corner of the main map', 'mappress');
		
        echo "<br/>";
        echo self::checkbox($options->overviewMapControlOptions['opened'], 'mappress_options[overviewMapControlOptions][opened]');
		_e ('Automatically open the overview map', 'mappress');
	}

	function set_alignment() {
		$options = Mappress_Options::get();
		$alignment = $options->alignment;

		echo "<input type='radio' name='mappress_options[alignment]' value='default' " . checked($alignment, 'default', false) . "/>";
		echo __('Default', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[alignment]' value='center' " . checked($alignment, 'center', false) . "/>";
		echo "<img src='" . plugins_url('/images/justify_center.png', __FILE__) . "' style='vertical-align:middle' title='" . __('Center', 'mappress') . "' />";
		echo __('Center', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[alignment]' value='left' "  . checked($alignment, 'left', false) . " />";
		echo "<img src='" . plugins_url('/images/justify_left.png', __FILE__) . "' style='vertical-align:middle' title='" . __('Left', 'mappress') . "' />";
		echo __('Left', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[alignment]' value='right' "  . checked($alignment, 'right', false) . " />";
		echo "<img src='" . plugins_url('/images/justify_right.png', __FILE__) . "' style='vertical-align:middle' title='" . __('Right', 'mappress') . "' />";
		echo __('Right', 'mappress');
        
        echo "<br/><i>" . sprintf(__("You may override this with CSS class %s in your theme's %s", 'mappress'), "<code>.mapp-container</code>", "<code>styles.css</code>")  . "</i>";        
	}

    function set_border() {
        $options = Mappress_Options::get();        
        $border = $options->border;
        
        $border_styles = array(
            '-none-' => '', 
            __('solid', 'mappress') => 'solid', 
            __('dashed', 'mappress') => 'dashed', 
            __('dotted', 'mappress') => 'dotted', 
            __('double', 'mappress') => 'double', 
            __('groove', 'mappress') => 'groove', 
            __('inset', 'mappress') => 'inset', 
            __('outset', 'mappress') => 'outset'
        );

        // Border style
        echo __("Style", 'mappress') . ": <select name='mappress_options[border][style]'>";
        foreach ($border_styles as $label => $value)
            echo "<option " . selected($value, $border['style'], false) . " value='$value'>$label</option>";
        echo "</select>";

        // Border width        
        for ($i = 0; $i <= 20; $i++)
            $widths[] = $i . "px";
        echo "&nbsp;&nbsp;" . __("Width", 'mappress') . ": <select name='mappress_options[border][width]'>";
        foreach ($widths as $width)
            echo "<option " . selected($width, $border['width'], false) . " value='$width'>$width</option>";
        echo "</select>";

        // Border color
        echo "&nbsp;&nbsp;" . __("Color", 'mappress');
        echo ": <input type='text' id='mappress_border_color' name='mappress_options[border][color]' value='" . $border['color'] . "' size='10'/>";
        
        echo "<br/><i>" . sprintf(__("You may override this with CSS class %s in your theme's %s", 'mappress'), "<code>.mapp-container</code>", "<code>styles.css</code>")  . "</i>";

        // Color wheel                        
        echo "<div id='mappress_border_color_picker'></div>
            <script type='text/javascript'>
                    jQuery(document).ready(function() {
                        jQuery('#mappress_border_color_picker').hide();   
                        jQuery('#mappress_border_color_picker').farbtastic('#mappress_border_color');
                        jQuery('#mappress_border_color').click(function(){
                            jQuery('#mappress_border_color_picker').slideToggle();
                        });  
                    });
            </script>
        ";        
    }
    
    function set_demo_map() {
        $options = Mappress_Options::get();

        echo self::checkbox($options->demoMap, 'mappress_options[demoMap]');
        _e('Show a sample map on this page', 'mappress');
    }
        
	function set_autodisplay() {
		$options = Mappress_Options::get();
		$autodisplay = $options->autodisplay;

		echo "<input type='radio' name='mappress_options[autodisplay]' value='top' " . checked($autodisplay, 'top', false) . "/>";
		echo __('Top of post', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[autodisplay]' value='bottom' " . checked($autodisplay, 'bottom', false) . "/>";
		echo __('Bottom of post', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[autodisplay]' value='none' " . checked($autodisplay, 'none', false) . "/>";
		echo __('No automatic display', 'mappress');
	}

	function set_post_types() {
		$options = Mappress_Options::get();
		$post_types = $options->postTypes;
		$all_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
		$all_post_types[] = 'post';
		$all_post_types[] = 'page';
		$codex_link = "<a href='http://codex.wordpress.org/Custom_Post_Types'>" . __('post types', 'mappress') . "</a>";

		echo sprintf(__("Mark the %s where the MapPress Editor should be available", "mappress"), $codex_link) . ": <br/>";
        
		foreach ($all_post_types  as $post_type ) {
			$checked = (in_array($post_type, (array)$post_types)) ? " checked='checked' " : "";
			// Translate standard types
			$label = $post_type;
			if ($label == 'post')
				$label = __('post', 'mappress');
			if ($label == 'page')
				$label = __('page', 'mappress');

			echo "<input type='checkbox' name='mappress_options[postTypes][]' value='$post_type' $checked />$label ";
		}
	}

	function set_force_resize() {
		$from = "<input type='text' size='2' name='resize_from[width]' value='' />"
			. "x<input type='text' size='2' name='resize_from[height]' value='' /> ";
		$to = "<input type='text' size='2' name='resize_to[width]]' value='' />"
			. "x<input type='text' size='2' name='resize_to[height]]' value='' /> ";
		echo __('Permanently resize existing maps:', 'mappress');
		echo "<br/>";
		printf(__('from %s to %s', 'mappress'), $from, $to);
		echo "<input type='submit' name='force_resize' class='button' value='" . __('Force Resize') . "' />";
	}

	function set_custom_css() {
		$options = Mappress_Options::get();
		$custom_css = $options->customCSS;

		// Older versions have true/false in the custom CSS value, ignore those cases
		if ($custom_css === true || $custom_css === false)
			$custom_css = "";

		echo __("Enter the URL for your CSS file", "mappress");
		echo ": <input type='text' size='30' name='mappress_options[customCSS]' value='$custom_css'/>";
	}

	function set_poi_list_template() {
		$pro_link = "<a href='http://wphostreviews.com/mappress/mappress' title='MapPress Pro'>MapPress Pro</a>";
		printf(__("This setting requires %s.", 'mappress'), $pro_link);
		echo " " . __("Set a template for the marker list", 'mappress');
	}

	function set_control() {
		$pro_link = "<a href='http://wphostreviews.com/mappress/mappress' title='MapPress Pro'>MapPress Pro</a>";

		printf(__("This setting requires %s.", 'mappress'), $pro_link);
		echo " " . __("Toggle the 'powered by' message", 'mappress');
	}

	function set_meta_key() {
		$pro_link = "<a href='http://wphostreviews.com/mappress/mappress' title='MapPress Pro'>MapPress Pro</a>";

		printf(__("This setting requires %s.", 'mappress'), $pro_link);
		echo " " . __("Automatically create maps from custom field data", 'mappress');
	}

	function set_map_sizes() {
		$pro_link = "<a href='http://wphostreviews.com/mappress/mappress' title='MapPress Pro'>MapPress Pro</a>";

		printf(__("This setting requires %s.", 'mappress'), $pro_link);
		echo " " . __("Set custom map sizes", 'mappress');
	}

	function set_georss() {
		$options = Mappress_Options::get();
		$checked = ($options->geoRSS) ? " checked='checked'" : "";

		$georss_title = __('simple GeoRSS', 'mappress');
		$georss_link = "<a href='http://www.georss.org/Main_Page' title='$georss_title'>$georss_title</a>";

        echo self::checkbox($options->geoRSS, 'mappress_options[geoRSS]');
		printf(__('Enable %s for your RSS feeds', 'mappress'), $georss_link);
		echo "<i> (beta - see readme.txt)</i>";
	}

	/**
	* RSS metabox
	*
	*/
	function metabox_rss() {
		$news_rss_url = 'http://www.wphostreviews.com/category/news/feed';
		$news_url = 'http://wphostreviews.com/category/news';

		include_once(ABSPATH . WPINC . '/feed.php');
		$rss = fetch_feed( $news_rss_url );

		if ( is_wp_error($rss) ) {
			echo "<li>" . __('No new items') . "</li>";
			return false;
		}

		$maxitems = $maxitems = $rss->get_item_quantity(5);
		$rss_items = $rss->get_items( 0, $maxitems );

		echo '<ul>';
		if ( !$rss_items ) {
			echo "<li>" . __('No new items') . "</li>";
		} else {
			foreach ( $rss_items as $item ) {
				echo '<li>'
					. '<a class="rsswidget" href="' . esc_url( $item->get_permalink() ). '">' . esc_html( $item->get_title() ) .'</a> '
					. '</li>';
			}
		}
		echo '</ul>';
		echo "<br/><img src='" . plugins_url('images/news.png', __FILE__) . "'/> <a href='$news_url'>" . __("Read More", 'mappress') . "</a>";
		echo "<br/><br/><img src='" . plugins_url('images/rss.png', __FILE__) . "'/> <a href='$news_rss_url'>" . __("Subscribe with RSS", 'mappress') . "</a>";
	}


	/**
	* Like metabox
	*
	*/
	function metabox_like() {
		$rate_link = "<a href='http://wordpress.org/extend/plugins/mappress-easy-google-maps'>" . __('5 Stars', 'mappress') . "</a>";
		echo "<ul>";
		echo "<li>" . __('Please take a moment to support future development ', 'mappress') . ':</li>';
		echo "<li>" . sprintf(__('* Rate it %s on WordPress.org', 'mappress'), $rate_link) . "</li>";
		echo "<li>" . __('* Make a donation') . "<br/>";
		echo "<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
			<input type='hidden' name='cmd' value='_s-xclick' />
			<input type='hidden' name='hosted_button_id' value='4339298' />
			<input type='image' src='https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif' border='0' name='submit' alt='PayPal - The safer, easier way to pay online!' />
			<img alt='' border='0' src='https://www.paypal.com/en_US/i/scr/pixel.gif' width='1' height='1' />
			</form>";
		echo "</li>";
		echo "<li>" . __('Thanks for your support!', 'mappress') . "</li>";
		echo "</ul>";
	}

	/**
	* Output a metabox for a settings section
	*
	* @param mixed $object - required by WP, but ignored, always null
	* @param mixed $metabox - arguments for the metabox
	*/
	function metabox_settings($object, $metabox) {
		global $wp_settings_fields;

		$page = $metabox['args']['page'];
		$section = $metabox['args']['section'];

		call_user_func($section['callback'], $section);
		if ( !isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']]) )
			return;

		echo '<table class="form-table">';
		do_settings_fields($page, $section['id']);
		echo '</table>';
	}

    // Defer this until 'click to display map' is implemented - too slow right now
    function metabox_demo($object, $metabox) {
        $options = Mappress_Options::get();                   
        
        $poi = new Mappress_Poi(array("title" => sprintf("<a href='http://www.wphostreviews.com/mappress'>%s</a>", __("MapPress", 'mappress')), "body" => "", "address" => "California"));
        $poi->geocode();
        $pois = array($poi);
        
        $map = new Mappress_Map(array("width" => "100%", "height" => 300, "pois" => $pois));

        // Display the map
        // Note that the alignment options "left", "center", etc. cause the map to not display properly in the metabox, so force it off        
        echo $map->display(array("alignment" => "default"));
    }

	/**
	* Replacement for standard WP do_settings_sections() function.
	* This version creates a metabox for each settings section instead of just outputting the section to the screen
	*
	*/
	function do_settings_sections($page) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
			return;

		// Add a metabox for each settings section
		foreach ( (array) $wp_settings_sections[$page] as $section ) {
			add_meta_box('metabox_' . $section['id'], $section['title'], array(&$this, 'metabox_settings'), 'mappress', 'normal', 'high', array('page' => 'mappress', 'section' => $section));
		}

		// Display all the registered metaboxes
		do_meta_boxes('mappress', 'normal', null);
	}

	/**
	* Options page
	*
	*/
	function options_page() {
        $options = Mappress_Options::get();
        
		?>
		<div class="wrap">

			<h2>
				<a target='_blank' href='http://wphostreviews.com/mappress'><img alt='MapPress' title='MapPress' src='<?php echo plugins_url('images/mappress_logo_med.png', __FILE__);?>'></a>
				<span style='font-size: 12px'>
					<?php echo $this->get_version(); ?>
					| <a target='_blank' href='http://wphostreviews.com/mappress/mappress-documentation'><?php _e('Documentation', 'mappress')?></a>
					| <a target='_blank' href='http://wphostreviews.com/mappress/chris-contact'><?php _e('Report a bug', 'mappress')?></a>
				</span>
			</h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<?php
						// Output sidebar metaboxes
						if (!class_exists('Mappress_Pro'))
							add_meta_box('metabox_like', __('Like this plugin?', 'mappress'), array(&$this, 'metabox_like'), $this->pagehook, 'side', 'core');
                        
                        add_meta_box('metabox_rss', __('MapPress News', 'mappress'), array(&$this, 'metabox_rss'), $this->pagehook, 'side', 'core');
                        
                        if ($options->demoMap)
                            add_meta_box('metabox_demo', __('Sample Map', 'mappress'), array(&$this, 'metabox_demo'), $this->pagehook, 'side', 'core');
                            
						do_meta_boxes($this->pagehook, 'side', null);
					?>
				</div>

				<div id="post-body">
					<div id="post-body-content" class="has-sidebar-content">
						<form action="options.php" method="post">
							<?php
								// Nonces needed to remember metabox open/closed settings
								wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
								wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

								// Output the option settings as metaboxes
								settings_fields('mappress');
								$this->do_settings_sections('mappress');
							?>
							<br/>

							<input name='submit' type='submit' class='button-primary' value='<?php _e("Save Changes", 'mappress'); ?>' />
							<input name='reset_defaults' type='submit' class='button' value='<?php _e("Reset Defaults", 'mappress'); ?>' />
						</form>
					</div>
				</div>
			</div>
		</div>
		<script type='text/javascript'>
			jQuery(document).ready( function() {
				// Initialize metaboxes
				postboxes.add_postbox_toggles('mappress');
			});
		</script>
		<?php
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

		if (get_bloginfo('version') < "3.0") {
			echo sprintf($error, __("WARNING: MapPress requires WordPress 3.0 or higher.  Please upgrade before using MapPress.", 'mappress'));
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

	function rss_ns() {
		echo 'xmlns:georss="http://www.georss.org/georss"';
	}

	function rss_item() {
		global $post;

		if (!is_feed())
			return;

		$maps = get_post_maps($post->ID);
		foreach ($maps as $map) {
			foreach ($map->pois as $poi) {
				echo '<georss:point>' . $poi->point['lat'] . ' ' . $poi->point['lng'] . '</georss:point>';
			}
		}
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

    /**
    * Show a checkbox
    * 
    * @param mixed $data
    * @param mixed $name
    */
    static function checkbox($data, $name) {
        $html = "<input type='hidden' name='$name' value='false' />";
        $html .= "<input type='checkbox' name='$name' value='true' " . checked($data, true, false) . " />";
        return $html;
    }    
    
    static function convert_to_boolean($data) {
        if ($data == 'false')
            return false;

        if ($data == 'true')
            return true;

        if (is_array($data)) {
            foreach($data as &$datum)
                $datum = self::convert_to_boolean($datum);
        }
        
        return $data;
    }
}  // End Mappress class

@include_once dirname( __FILE__ ) . '/mappress_api.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro.php';
@include_once dirname( __FILE__ ) . '/mappress_updater.php';
if (class_exists('Mappress_Pro'))
	$mappress = new Mappress_Pro();
else
	$mappress = new Mappress();
?>