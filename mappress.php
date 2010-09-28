<?php
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 2.0.1
Author: Chris Richardson
Thanks to Matthias Stasiak for some icons (http://code.google.com/p/google-maps-icons/) and to all the translators!
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/


class Mappress {
	var $version = '2.0.1';
	var $debug = false;
	var $basename;

	function mappress()  {
		$this->debugging();

		$this->basename = plugin_basename(__FILE__);
		load_plugin_textdomain('mappress', false, dirname($this->basename) . '/languages');

		register_activation_hook(__FILE__, array(&$this, 'activation'));

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_shortcode('mappress', array(&$this, 'shortcode_map'));
		add_action('admin_notices', array(&$this, 'admin_notices'));

		// Ajax
		add_action('wp_ajax_mapp_save', array(&$this, 'ajax_save'));
		add_action('wp_ajax_mapp_delete', array(&$this, 'ajax_delete'));
		add_action('wp_ajax_mapp_create', array(&$this, 'ajax_create'));
		add_action('admin_init', array(&$this, 'admin_init'));

		// Post hooks
		add_action('deleted_post', array(&$this, 'deleted_post'));
		add_action('save_post', array(&$this, 'save_post'));

		// Automatic display
		add_filter('the_content', array(&$this, 'the_content'));
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
			echo "<br/><b>options</b><br/>";
			$options = Mappress_Options::get();
			print_r($options);
			echo "<br/><b>maps</b><br/>";
			$maps = Mappress_Map::get_list();
			print_r($maps);
			echo "<br/><b>maps-posts</b><br/>";
			$postmaps = Mappress_Map::get_post_map_list();
			print_r($postmaps);
			echo "<br/><b>posts</b><br/>";
			$posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts"));
			print_r($posts);
			echo "<br/><b>phpinfo</b><br/>";
			phpinfo();
		}
	}

	function get_version() {
		return $this->version;
	}

	function ajax_save() {
		$mapdata = (isset($_POST['map'])) ? json_decode(stripslashes($_POST['map']), true) : null;
		$postid = (isset($_POST['postid'])) ? $_POST['postid'] : null;

		if (!$mapdata)
			$this->ajax_response(__('Internal error, map was missing.  Your data has not been saved!', 'mappress'));

		$map = new Mappress_Map($mapdata);
		$mapid = $map->save($postid);
		if ($mapid === false)
			$this->ajax_response(__('Internal error - unable to save map.  Your data has not been saved!', 'mappress'));
		else
			$this->ajax_response('OK', $mapid);
	}

	function ajax_delete() {
		$mapid = (isset($_POST['mapid'])) ? $_POST['mapid'] : null;

		// Try to read the map
		$map = Mappress_Map::get($mapid);

		// If map is already deleted then return without error, otherwise attempt to delete it
		if ($map && $map->delete() === false)
			$this->ajax_response(__("Internal error when deleting map ID '$mapid'!", 'mappress'));
		else
			$this->ajax_response('OK', $mapid);
	}


	function ajax_create() {
		$postid = (isset($_POST['postid'])) ? $_POST['postid'] : null;

		$map = new Mappress_Map();
		$map->title = 'Untitled';
		$this->ajax_response('OK', array('map' => $map));
	}

	function ajax_response($status, $data=null) {
		header( "Content-Type: application/json" );
		$response = json_encode(array('status' => $status, 'data' => $data));

		die ($response);
	}

	/**
	* Hook for post delete.  Delete all map assignments for the post.
	*
	*/
	function deleted_post($postid) {
		$maps = Mappress_Map::get_post_map_list($postid);

		if (!$maps || empty($maps))
			return;

		foreach ($maps as $map)
			$map->delete();
	}

	function save_post($post_id) {
		global $wpdb;

		$metafield = Mappress_Options::get()->metafield;
		if (!$metafield || empty($metafield))
			return;

		// Sadly, get_post_meta doesn't return a sorted list, which makes it pretty useless for updates.
		// It's used here only because save_post is called twice; when get_post_meta returns null the update can be skipped
		$metapois = get_post_meta($post_id, $metafield, false);
		if (!$metapois)
			return;

		// A custom select has to be used to order the data, which unfortunately bypasses the cache
		$metapois = $wpdb->get_col( $wpdb->prepare ("SELECT meta_value FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key = '$metafield' ORDER BY meta_id") );

		$pois = array();

		if ($metapois) {
			foreach($metapois as $metapoi) {
				$s_poi = $metapoi;
				$poi = shortcode_parse_atts($metapoi);  // Parse POI string in an array, like a shortcode

				// Geocode if needed, and convert POI back to a string
				if ( !isset($poi['point_lat']) || !isset($poi['point_lng']) ) {
					$response = $this->geocode($poi['address']);
					if ($response && !is_wp_error($response) && isset($response['status']) && $response['status'] == 'OK') {

						$s_poi = 'address="' . $poi['address'] . '"';
						$title = (isset($poi['title'])) ? $poi['title'] : $poi['address']; // Set default title if needed
						$s_poi .= ' title="' . $title. '"';


						$placemark = $response['results'][0];
						if ($placemark) {
							$s_poi .= ' correctedaddress="' . $placemark['formatted_address'] . '"';
							$s_poi .= ' point_lat="' . $placemark['geometry']['location']['lat'] . '"';
							$s_poi .= ' point_lng="' . $placemark['geometry']['location']['lng'] . '"';
							$s_poi .= ' viewport_sw_lat="' . $placemark['geometry']['viewport']['southwest']['lat'] . '"';
							$s_poi .= ' viewport_sw_lng="' . $placemark['geometry']['viewport']['southwest']['lng'] . '"';
							$s_poi .= ' viewport_ne_lat="' . $placemark['geometry']['viewport']['northeast']['lat'] . '"';
							$s_poi .= ' viewport_ne_lng="' . $placemark['geometry']['viewport']['northeast']['lng'] . '"';
						}
					}
				}

				$pois[] = $s_poi;
			}
		}

		// Delete and re-add all key values (it's the only way to update multiple values for same key)
		delete_post_meta($post_id, $metafield);
		foreach($pois as $poi)
			add_post_meta($post_id, $metafield, $poi, false);
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

		// If we're just getting an excerpt don't attempt to add the map to it
		if ($wp_current_filter[0] == 'get_the_excerpt')
			return $content;

		$autodisplay = Mappress_Options::get()->autodisplay;

		// No auto display
		if (!$autodisplay || $autodisplay == 'none')
			return $content;

		// Don't autodisplay if the post already contains a MapPress shortcode
		if (stristr($content, '[mappress') !== false || stristr($content, '[mashup') !== false)
			return $content;

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
	* Map a shortcode in a post.  Called by WordPress shortcode processor.
	*
	* @param mixed $atts - shortcode attributes
	*/
	function shortcode_map($atts='') {
		global $post;

		// No feeds
		if (is_feed())
			return;

		$mapid = (isset($atts['mapid'])) ? $atts['mapid'] : null;
		$source = (isset($atts['source'])) ? $atts['source'] : null;

		// If a mapid was provided then show that map only if it's attached to the post
		if ($mapid) {
			$map = Mappress_Map::get_post_map($post->ID, $mapid);

		} else if ($source == 'custom') {
			$map = new Mappress_Map();
			$map->pois = $this->get_meta_pois($post->ID);
			// Turn on autocenter unless it was turned off explicitly
			if (!isset($atts['autocenter']))
				$atts['autocenter'] = true;

		} else {
			// No mapid, no meta field, try to show the first map attached to the post
			$maps = Mappress_Map::get_post_map_list($post->ID);
			if (empty($maps))
				return;
			$map = $maps[0];
		}

		if (!$map)
			return;

		return $map->display($atts);
	}

	/**
	* Get all metadata POIs for a post.
	* Because the metadata format has flattened arrays a POI object can't be created directly from it
	* Returns array of POI objects or empty array
	*
	*/
	function get_meta_pois($post_id) {
		$pois = array();
		$metafield = Mappress_Options::get()->metafield;

		if (!$metafield || empty($metafield))   // If metafield blank/null WP will select ALL metadata
			return;

		$metapois = get_post_meta($post_id, $metafield);

		if (!$metapois)
			return false;

		foreach((array)$metapois as $metapoi) {
			// Convert POI string to array
			$atts = shortcode_parse_atts($metapoi);

			$poi = new Mappress_Poi($atts);

			if (isset($atts['point_lat']) && isset($atts['point_lng'])) {
				$poi->point = array('lat' => $atts['point_lat'], 'lng' => $atts['point_lng']);
			}

			if (isset($atts['viewport_sw_lat']) && isset($atts['viewport_sw_lng']) && isset($atts['viewport_ne_lat'])
			&& isset($atts['viewport_ne_lng'])) {
				$poi->viewport = array(
					'sw' => array('lat' => $atts['viewport_sw_lat'], 'lng' => $atts['viewport_sw_lng']),
					'ne' => array('lat' => $atts['viewport_ne_lat'], 'lng' => $atts['viewport_ne_lng'])
				);
			}
			$pois[] = $poi;
		}

		return $pois;
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
	* Add admin menu and admin scripts/stylesheets
	* Admin script - post edit and options page
	* Content script - content (and also post-edit map)
	* CSS - content, plugins, post-edit
	*
	*/
	function admin_menu() {
		$options = Mappress_Options::get();

		// Add menu
		$mypage = add_options_page('MapPress', 'MapPress', 'manage_options', 'mappress', array(&$this, 'options_page'));

		// Add meta box to standard & custom post types
		if ($options) {
			foreach((array)$options->postTypes as $post_type)
				add_meta_box('mappress', 'MapPress', array($this, 'meta_box'), $post_type, 'normal', 'high');
		}

		// Add edit scripts
		add_action("admin_print_scripts-$mypage", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_scripts-post.php", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_scripts-post-new.php", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_scripts-page.php", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_scripts-page-new.php", array(&$this, 'admin_print_scripts'));
	}

	function admin_print_scripts() {
		// May be required for loading jquery UI components because of the way WP splits them.  Example:
		// wp_enqueue_script('jqeury-ui-dialog')
	}

	/**
	* Geocode an address using http
	*
	* @param string $address
	* @param mixed $api_key
	* @param mixed $country
	* @return mixed
	*/
	function geocode($address) {
		$language = Mappress_Options::get()->language;
		$country = Mappress_Options::get()->country;
		$address = urlencode($address);

		$url = "http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&output=json";
		if ($country)
			$url .= "&region=$country";
		if ($language)
			$url .= "&language=$language";

		$response = wp_remote_get($url);

		// Decode the result as associative array
		if (is_wp_error($response))
			return $response;
		else
			return json_decode($response['body'], true);
	}

	function activation() {
		global $wpdb;

		// Create database tables if they don't exist
		Mappress_Map::db_create();

		// Delete any current options
		delete_option('mappress_options');

		// Check if database upgrade is needed
		$current_version = get_option('mappress_version');
		update_option('mappress_version', $this->version);

		if ($current_version >= $this->version)
			return;

		if (!$current_version)
			$this->activation_171();
	}

	function activation_171() {
		global $wpdb;

		// Read all post metadata
		$sql = "SELECT m.post_id, p.post_title FROM $wpdb->postmeta m, $wpdb->posts p "
			. " WHERE m.meta_key = '_mapp_pois' AND m.post_id = p.id AND m.meta_value != ''";
		$results = $wpdb->get_results($sql);

		// Convert maps and pois
		foreach ((array)$results as $post) {
			// Get original metadata
			$mapdata = get_post_meta($post->post_id, '_mapp_map', true);
			$poidata = get_post_meta($post->post_id, '_mapp_pois', true);

			$pois = array();
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

			// Convert map types
			$mapTypeId = $mapdata['maptype'];
			if ($mapTypeId != 'roadmap' && $mapTypeId != 'satellite' && $mapTypeId != 'terrain' && $mapTypeId != 'hybrid')
				$mapTypeId = 'roadmap';
			else
				$mapTypeId = strtolower($mapTypeId);

			// Creaate map object
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
		if ($options) {
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
		add_settings_section('mappress_settings', __('Settings', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('autodisplay', __('Automatic map display', 'mappress'), array(&$this, 'set_autodisplay'), 'mappress', 'mappress_settings');
		add_settings_field('directions', __('Directions', 'mappress'), array(&$this, 'set_directions'), 'mappress', 'mappress_settings');
		add_settings_field('mapTypeControl', __('Map types', 'mappress'), array(&$this, 'set_map_type_control'), 'mappress', 'mappress_settings');
		add_settings_field('streetViewControl', __('Street View', 'mappress'), array(&$this, 'set_streetview_control'), 'mappress', 'mappress_settings');
		add_settings_field('scrollwheel', __('Scroll wheel zoom', 'mappress'), array(&$this, 'set_scrollwheel'), 'mappress', 'mappress_settings');
		add_settings_field('keyboard', __('Keyboard shortcuts', 'mappress'), array(&$this, 'set_keyboard_shortcuts'), 'mappress', 'mappress_settings');
		add_settings_field('initialOpenInfo', __('Open first marker', 'mappress'), array(&$this, 'set_initial_open_info'), 'mappress', 'mappress_settings');
		add_settings_field('traffic', __('Show traffic button', 'mappress'), array(&$this, 'set_traffic'), 'mappress', 'mappress_settings');
		add_settings_field('tooltips', __('Tooltips', 'mappress'), array(&$this, 'set_tooltips'), 'mappress', 'mappress_settings');
		add_settings_field('alignment', __('Map alignment', 'mappress'), array(&$this, 'set_alignment'), 'mappress', 'mappress_settings');
		add_settings_field('language', __('Language', 'mappress'), array(&$this, 'set_language'), 'mappress', 'mappress_settings');
		add_settings_field('country', __('Country', 'mappress'), array(&$this, 'set_country'), 'mappress', 'mappress_settings');
		add_settings_field('postTypes', __('Post types', 'mappress'), array(&$this, 'set_post_types'), 'mappress', 'mappress_settings');
		add_settings_field('customCSS', __('Custom CSS', 'mappress'), array(&$this, 'set_custom_css'), 'mappress', 'mappress_settings');
		add_settings_field('metafield', __('Map custom field', 'mappress'), array(&$this, 'set_metafield'), 'mappress', 'mappress_settings');
	}

	function set_options($input) {
		// Force checkboxes to boolean
		$input['mapTypeControl'] = (isset($input['mapTypeControl'])) ? true : false;
		$input['streetViewControl'] = (isset($input['streetViewControl'])) ? true : false;
		$input['scrollwheel'] = (isset($input['scrollwheel'])) ? true : false;
		$input['keyboardShortcuts'] = (isset($input['keyboardShortcuts'])) ? true : false;
		$input['initialOpenInfo'] = (isset($input['initialOpenInfo'])) ? true : false;
		$input['traffic'] = (isset($input['traffic'])) ? true : false;
		$input['tooltips'] = (isset($input['tooltips'])) ? true : false;
		$input['customCSS'] = (isset($input['customCSS'])) ? true : false;
		return $input;
	}

	function section_settings() {
	}

	function set_country() {
		$country = Mappress_Options::get()->country;
		$cctld_link = '<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("country code", 'mappress') . '</a>';

		printf(__(' Enter a %s to use when searching (leave blank for USA): ', 'mappress'), $cctld_link);
		echo "<input type='text' size='2' name='mappress_options[country]' value='$country' />";
	}

	function set_scrollwheel() {
		$scrollwheel = Mappress_Options::get()->scrollwheel;
		$checked = ($scrollwheel) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[scrollwheel]' $checked />";
		_e(' Enable zoom with the mouse scroll wheel', 'mappress');
	}

	function set_keyboard_shortcuts() {
		$keyboard_shortcuts = Mappress_Options::get()->keyboardShortcuts;

		echo "<input type='checkbox' name='mappress_options[keyboardShortcuts]' " . checked($keyboard_shortcuts, true, false) . " />";
		_e(' Enable keyboard panning and zooming', 'mappress');
	}

	function set_language() {
		$language = Mappress_Options::get()->language;
		$lang_link = '<a target="_blank" href="http://code.google.com/apis/maps/faq.html#languagesupport">' . __("language", 'mappress') . '</a>';

		printf(__(' Use a specific %s for map controls (default is the browser language setting): ', 'mappress'), $lang_link);
		echo "<input type='text' size='2' name='mappress_options[language]' value='$language' />";

	}

	function set_map_type_control() {
		$map_type_control = Mappress_Options::get()->mapTypeControl;
		$checked = ($map_type_control) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[mapTypeControl]' $checked />";
		_e (' Allow your readers to change the map type (street, satellite, or hybrid)', 'mappress');
	}

	function set_streetview_control() {
		$street_view_control = Mappress_Options::get()->streetViewControl;

		echo "<input type='checkbox' name='mappress_options[streetViewControl]' " . checked($street_view_control, true, false) . " />";
		_e (' Display the street view control "peg man"', 'mappress');
	}

	function set_directions() {
		$directions = Mappress_Options::get()->directions;

		// For upgraders only
		if ($directions === true)
			$directions = 'inline';
		if ($directions === false)
			$directions = 'none';

		echo "<input type='radio' name='mappress_options[directions]' value='inline' " . checked($directions, 'inline', false) . "/>";
		echo __('Inline (in your blog)', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[directions]' value='google' " . checked($directions, 'google', false) . "/>";
		echo __('Google', 'mappress');

		echo "&nbsp;&nbsp;";
		echo "<input type='radio' name='mappress_options[directions]' value='none' "  . checked($directions, 'none', false) . " />";
		echo __('None', 'mappress');

		echo "<br/><i>" . __("Select 'Google' if directions aren't displaying properly in your theme") . "</i>";
	}

	function set_initial_open_info() {
		$initial_open = Mappress_Options::get()->initialOpenInfo;
		$checked = ($initial_open) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[initialOpenInfo]' $checked />";
		_e(' Automatically open the first marker when a map is displayed', 'mappress');
	}

	function set_traffic() {
		$traffic = Mappress_Options::get()->traffic;
		$checked = ($traffic) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[traffic]' $checked />";
		_e(' Show a button for real-time traffic conditions', 'mappress');
	}

	function set_tooltips() {
		$tooltips = Mappress_Options::get()->tooltips;
		$checked = ($tooltips) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[tooltips]' $checked />";
		_e(' Show marker titles as a "tooltip" on mouse-over.  Switch this off if you use HTML in your marker titles', 'mappress');
	}

	function set_alignment() {
		$alignment = Mappress_Options::get()->alignment;

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
	}

	function set_autodisplay() {
		$autodisplay = Mappress_Options::get()->autodisplay;

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
		$post_types = Mappress_Options::get()->postTypes;
		$all_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
		$all_post_types[] = 'post';
		$all_post_types[] = 'page';
		$codex_link = "<a href='http://codex.wordpress.org/Custom_Post_Types'>" . __('post types', 'mappress') . "</a>";

		echo sprintf(__("Mark the %s where you want to use MapPress:", "mappress"), $codex_link) . "<br/>";

		foreach ($all_post_types  as $post_type ) {
			$checked = (in_array($post_type, (array)$post_types)) ? " checked='checked' " : "";
			echo "<input type='checkbox' name='mappress_options[postTypes][]' value='$post_type' $checked />$post_type ";
		}
	}

	function set_custom_css() {
		$custom_css = Mappress_Options::get()->customCSS;
		$checked = ($custom_css) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[customCSS]' $checked />";
			echo sprintf(__(" Include your own CSS.  You must create a file named %s in the MapPress %s sub-directory", 'mappress'), "<code>custom.css</code>", "<code>/css</code>");
	}

	function set_metafield() {
		global $wpdb;

		$x = Mappress_Options::get();
		$metafield = Mappress_Options::get()->metafield;
		$codex_link = "<a href='http://codex.wordpress.org/Custom_Fields'>" . __('custom field') . "</a>";


		printf(__('Map addresses from a %s : ', 'mappress'), $codex_link);

		// Get list of custom fields from all posts; ignore hidden fields
		$meta_keys = $wpdb->get_col( "SELECT meta_key FROM $wpdb->postmeta
			WHERE meta_key NOT LIKE ('\_%')
			GROUP BY meta_key
			ORDER BY meta_id DESC" );

		if ($meta_keys)
			natcasesort($meta_keys);

		$meta_keys = array_merge(array(""), $meta_keys);  // Add an empty entry
		echo "<select name='mappress_options[metafield]'>";
		foreach ((array) $meta_keys as $meta_key)
			echo "<option " . selected($meta_key, $metafield) . " value='$meta_key'>$meta_key</option>";
		echo "</select>";
	}

	/**
	* Options page
	*
	*/

	function options_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>
				<?php _e("MapPress", 'mappress'); ?>
				<span style='float:right;font-size: 12px'>
					<?php echo __(' Version: ', 'mappress') . $this->version; ?>
					| <a target='_blank' href='http://wphostreviews.com/mappress/mappress-documentation-144'><?php _e('Documentation', 'mappress')?></a>
					| <a target='_blank' href='http://wphostreviews.com/mappress/chris-contact'><?php _e('Report a bug', 'mappress')?></a>
				</span>
			</h2>


			<h3><?php _e('Donate', 'mappress'); ?></h3>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_s-xclick" />
				<input type="hidden" name="hosted_button_id" value="4339298" />
				<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
				<h4><?php echo __("Please make a donation today!", 'mappress') ?></h4>
			</form>
			<hr/>


			<form action="options.php" method="post">
				<?php settings_fields('mappress'); ?>
				<?php do_settings_sections('mappress'); ?>
				<br/>

				<input name='submit' type='submit' class='button-primary' value='<?php _e("Save Changes", 'mappress'); ?>' />
			</form>
		</div>
		<?php
	}

	// Sanity checks via notices
	function admin_notices() {
		global $pagenow;
		$error =  "<div id='error' class='error'><p>%s</p></div>";

		if (get_bloginfo('version') < "3.0") {
			echo sprintf($error, __("WARNING: MapPress requires WordPress 3.0 or higher.  Please upgrade before using MapPress.", 'mappress'));
			return;
		}

		if (class_exists('WPGeo')) {
			echo sprintf($error, __("WARNING: MapPress is not compatible with the WP-Geo plugin - your RSS feeds will not work.  Please deactivate or uninstall WP-Geo before using MapPress.", 'mappress'));
			return;
		}
	}

}  // End Mappress class

@include_once dirname( __FILE__ ) . '/mappress_api.php';
@include_once dirname( __FILE__ ) . '/pro/mappress_pro.php';
if (class_exists('Mappress_Pro'))
	$mappress = new Mappress_Pro();
else
	$mappress = new Mappress();
?>