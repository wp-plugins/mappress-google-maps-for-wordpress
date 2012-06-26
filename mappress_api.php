<?php

/**
* Generic object functions
*/
class Mappress_Obj {
	function __construct($atts=null) {
		$this->update($atts);
	}

	function update($atts=null) {
		if (!$atts)
			return;

		$obj_atts = get_object_vars($this);

		foreach ($obj_atts as $key => $value ) {
			$newvalue = (isset($atts[$key])) ? $atts[$key] : null;

			// Allow attributes to be all lowercase to handle shortcodes
			if ($newvalue === null) {
				$lkey = strtolower($key);
				$newvalue = (isset($atts[$lkey])) ? $atts[$lkey] : null;
			}

			if ($newvalue === null)
				continue;

			// Convert any string versions of true/false
			if ($newvalue === "true")
				$newvalue = true;
			if ($newvalue === "false")
				$newvalue = false;

			$this->$key = $newvalue;
		}
	}
} // End class Mappress_Obj

/**
* POIs
*/
class Mappress_Poi extends Mappress_Obj {
	var $address,
		$body = '',
		$correctedAddress,
		$iconid,
		$point = array('lat' => 0, 'lng' => 0),
		$poly,
		$kml,
		$title = '',
		$type,
		$viewport;              // array('sw' => array('lat' => 0, 'lng' => 0), 'ne' => array('lat' => 0, 'lng' => 0))

	// Not saved
	var $postid,
		$suppress;

	function __sleep() {
		return array('address', 'body', 'correctedAddress', 'iconid', 'point', 'poly', 'kml', 'title', 'type', 'viewport');
	}

	function __construct($atts) {
		parent::__construct($atts);
	}

	// Work-around for PHP issues with circular references (serialize, print_r, json_encode, etc.)
	function map($map = null) {
		static $_map;
		if ($map)
			$_map = $map;
		else
			return $_map;
	}

	/**
	* Geocode an address using http
	*
	* @param mixed $auto true = automatically update the poi, false = return raw geocoding results
	* @return true if auto=true and success | raw geocoding results if auto=false | WP_Error on failure
	*/
	function geocode($auto=true) {
		// If point was defined using only lat/lng then no geocoding
		if (!empty($this->point['lat']) && !empty($this->point['lng'])) {
			// Default title if empty
			if (empty($this->title))
				$this->title = $this->point['lat'] . ',' . $this->point['lng'];
			return;
		}

		$language = Mappress::$options->language;
		$country = Mappress::$options->country;

		$address = urlencode($this->address);
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&sensor=false&output=json";
		if ($country)
			$url .= "&region=$country";
		if ($language)
			$url .= "&language=$language";

		$response = wp_remote_get($url, array('sslverify' => false));

		// If auto=false, then return the RAW result
		if (!$auto)
			return $response;

		// Check for http error
		if (is_wp_error($response))
			return $response;

		if (!$response)
			return new WP_Error('geocode', sprintf(__('No geocoding response from Google: %s', 'mappress'), $response));

		//Decode response and automatically use first address
		$response = json_decode($response['body']);

		// Discard empty results
		foreach((array)$response->results as $key=>$result) {
			if(empty($result->formatted_address))
				unset($response->results[$key]);
		}

		$status = isset($response->status) ? $response->status : null;
		if ($status != 'OK')
			return new WP_Error('geocode', sprintf(__("Google cannot geocode address: %s, status: %s", 'mappress'), $this->address, $status));

		if (!$response  || !isset($response->results) || empty($response->results[0]) || !isset($response->results[0]))
			return new WP_Error('geocode', sprintf(__("No geocoding result for address: %s", 'mappress'), $this->address));

		$placemark = $response->results[0];

		// Point
		$this->point = array('lat' => $placemark->geometry->location->lat, 'lng' => $placemark->geometry->location->lng);

		// Viewport
		// As of 7/27/11, Google has suddenly stopped returning viewports for street addresses
		if (isset($placemark->geometry->viewport)) {
			$this->viewport = array(
				'sw' => array('lat' => $placemark->geometry->viewport->southwest->lat, 'lng' => $placemark->geometry->viewport->southwest->lng),
				'ne' => array('lat' => $placemark->geometry->viewport->northeast->lat, 'lng' => $placemark->geometry->viewport->northeast->lng)
			);
		} else {
			$this->viewport = null;
		}

		// Corrected address
		$this->correctedAddress = $placemark->formatted_address;
		$parsed = Mappress_Poi::parse_address($this->correctedAddress);

		// If the title and body are not populated then default them
		if (!$this->title && !$this->body) {
			$this->title = $parsed[0];
			if (isset($parsed[1]))
				$this->body = $parsed[1];
		}
	}

	/**
	* Parse an address.  It will split the address into 1 or 2 lines, as appropriate
	*
	* @param mixed $address
	* @return array $result - array containing 1 or 2 address lines
	*/
	static function parse_address($address) {
		// USA Addresses
		if (strstr($address, ', USA')) {
			// Remove 'USA'
			$address = str_replace(', USA', '', $address);

			// If there's exactly ONE comma left then return a single line, e.g. "New York, NY"
			if (substr_count($address, ',') == 1) {
				return array($address);
			}
		}

		// If NO commas then use a single line, e.g. "France" or "Ohio"
		if (!strpos($address, ','))
			return array($address);

		// Otherwise return first line from before first comma+space, second line after, e.g. "Paris, France" => "Paris<br>France"
		// Or "1 Main St, Brooklyn, NY" => "1 Main St<br>Brooklyn, NY"
		return array(
			substr($address, 0, strpos($address, ",")),
			substr($address, strpos($address, ",") + 2)
		);
	}

	function get_html() {
		global $mappress;
		global $post;

		if (class_exists('Mappress_Pro')) {
			$html = apply_filters('mappress_poi_html', $mappress->get_template($this->map()->options->templatePoi, array('poi' => $this)), $this);
		} else {
			$html = "<div class='mapp-iw'>" .
			"<div class='mapp-title'>" . $this->title . "</div>" .
			"<div>" . $this->body . "</div>" .
			"<div style='clear:both'></div>" .
			$this->get_links() .
			"</div>";
		}

		$this->html = $html;
	}

	/**
	* Return the title, or a permalink to the title if marker_link = true
	*
	*/
	function get_title_link() {
		if (!$this->postid)
			return $this->title;

		$map = $this->map();
		$post = get_post($this->postid);
		$title = $post->post_title;

		if ($map->options->marker_link)
			return "<a href='" . get_permalink($this->postid) . "'>$title</a>";

		return $title;
	}

	function get_body() {
		if ($this->map()->options->marker_link && $this->postid) {
			return apply_filters('mappress_poi_excerpt', '', $this->postid);
		} else {
			return $this->body;
		}
	}

	/**
	* Get links for poi in infowindow or poi list
	*
	* @param mixed $context - blank or 'poi' | 'poi_list'
	*/
	function get_links($context = '') {
		$map = $this->map();

		$links = apply_filters('mappress_poi_links', $map->options->poiLinks, $context, $this);

		$a = array();
		if (in_array('directions_to', $links) && $map->options->directions != 'none')
			$a[] = $this->get_directions_link(array('to' => $this));
		if (in_array('directions_from', $links) && $map->options->directions != 'none')
			$a[] = $this->get_directions_link(array('from' => $this, 'to' => ''));

		// Zoom isn't available in poi list by default
		if (in_array('zoom', $links) && $context != 'poi_list')
			$a[] = $this->get_zoom_link(array('context' => $context));

		if (empty($a))
			return "";

		$html = implode('&nbsp;&nbsp', $a);
		$html = "<div style='clear:both'></div><div class='mapp-links'>$html</div>";
		return apply_filters('mappress_poi_html_links', $html, $context, $this);
	}

	function get_icon() {
		$map = $this->map();
		$iconid = apply_filters('mapress_poi_iconid', $this->iconid, $this);
		return Mappress_Icons::get_icon($iconid, $map->options->defaultIcon);
	}

	/**
	* Get a directions link
	*
	* @param bool $from - 'from' poi object or a string address
	* @param bool $to - 'to' poi object or a string address
	* @param mixed $text
	*/
	function get_directions_link($args = '') {
		$map = $this->map();

		extract(wp_parse_args($args, array(
			'from' => '',
			'to' => $this,      // Default is 'to' current poi
			'focus' => true,
			'text' => __('Directions', 'mappress')
		)));

		// Convert objects to indexes, quote strings
		if (is_object($from)) {
			$i = array_search($from, $map->pois);
			$from = "{$map->name}.getPoi($i)";
		} else {
			$from = "\"$from\"";
		}

		if (is_object($to)) {
			$i = array_search($to, $map->pois);
			$to = "{$map->name}.getPoi($i)";
		} else {
			$to = "\"$to\"";
		}

		$link = "<a href='#' onclick = '{$map->name}.openDirections(%s, %s, $focus); return false;'>$text</a>";

		return sprintf($link, $from, $to);
	}

	/**
	* Get a link to open a poi and optionally zoom in on it
	*
	* $args:
	*   text - text to print for the link, default is poi title
	*   zoom - false (default) = no zoom | true = zoom in to viewport (ignored for lat/lng pois with no viewport) | number = set zoom (0-15)
	*
	* @param mixed $map - map on which the poi should be opened
	* @param mixed $args
	* @return mixed
	*/
	function get_open_link ($args = '') {
		$map = $this->map();
		extract(wp_parse_args($args, array(
			'text' => $this->title,
			'zoom' => null
		)));

		$i = array_search($this, $map->pois);
		$zoom = Mappress::boolean_to_string($zoom);
		return "<a href='#' onclick='{$map->name}.getPoi($i).open($zoom); return false;' >$text</a>";
	}

	function get_zoom_link ($args = '') {
		$map = $this->map();
		extract(wp_parse_args($args, array(
			'context' => '',
			'text' => __('Zoom', 'mappress'),
		)));

		$i = array_search($this, $map->pois);
		$click = "{$map->name}.getPoi($i).zoomIn(); return false;";
		return "<a href='#' onclick='$click'>$text</a>";
	}

	/**
	* Get poi thumbnail
	*
	* @param mixed $map
	* @param mixed $args - arguments to pass to WP get_the_post_thumbnail() function
	*/
	function get_thumbnail( $args = '' ) {
		if (!$this->postid)
			return '';

		$map = $this->map();
		if (!$map->options->marker_link || !$map->options->thumbs)
			return "";

		if (isset($args['size']))
			$size = $args['size'];
		else
			$size = ($map->options->thumbSize) ? $map->options->thumbSize : array($map->options->thumbWidth, $map->options->thumbHeight);

		return get_the_post_thumbnail($this->postid, $size, $args);
	}
} // End class Mappress_Poi



/**
* Map class
*/
class Mappress_Map extends Mappress_Obj {
	var $mapid,
		$width = 425,
		$height = 350,
		$zoom,
		$center = array('lat' => 0, 'lng' => 0),
		$mapTypeId = 'roadmap',
		$title = 'Untitled',
		$metaKey,
		$pois = array();

	// Not saved
	var $options,
		$name;

	function __sleep() {
		return array('mapid', 'width', 'height', 'zoom', 'center', 'mapTypeId', 'title', 'metaKey', 'pois');
	}

	function __construct($atts=null) {
		parent::__construct($atts);

		// Set the options
		$this->options = Mappress_Options::get();
		$this->options->update($atts);

		// For WPML (wpml.org): set the selected language if it wasn't specified in the options screen
		if (defined('ICL_LANGUAGE_CODE') && !$this->options->language)
			$this->options->language = ICL_LANGUAGE_CODE;

		// For qTranslate, pick up current language from that
		if (function_exists('qtrans_getLanguage') && !$options->language)
			$options->language = qtrans_getLanguage();

		// Convert POIs from arrays to objects if needed
		foreach((array)$this->pois as $index => $poi) {
			if (is_array($poi))
				$this->pois[$index] = new Mappress_Poi($poi);
		}
	}

	function db_create() {
		global $wpdb;
		$maps_table = $wpdb->prefix . 'mappress_maps';
		$posts_table = $wpdb->prefix . 'mappress_posts';

		$wpdb->show_errors(true);

		if ($wpdb->get_var("show tables like '$maps_table'") != $maps_table) {
			$result = $wpdb->query ("CREATE TABLE $maps_table (
									mapid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
									obj LONGTEXT)
									CHARACTER SET utf8;");
		}

		if ($wpdb->get_var("show tables like '$posts_table'") != $posts_table) {
			$result = $wpdb->query ("CREATE TABLE $posts_table (
									postid INT,
									mapid INT,
									PRIMARY KEY (postid, mapid) )
									CHARACTER SET utf8;");
		}

		$wpdb->show_errors(false);
	}

	/**
	* Get a map.
	*
	* @param mixed $mapid
	* @return mixed false if failure, or a map object on success
	*/
	static function get($mapid) {
		global $wpdb;
		$maps_table = $wpdb->prefix . 'mappress_maps';
		$result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $maps_table WHERE mapid = %d", $mapid) );  // May return FALSE or NULL

		if (!$result)
			return false;

		// Read the map data and construct a new map from it
		$mapdata = (array) unserialize($result->obj);
		$map = new Mappress_Map($mapdata);
		$map->mapid = $result->mapid;
		return $map;
	}

	/**
	* Returns ALL maps
	*
	* @return mixed false if failure, array of maps if success
	*
	*/
	function get_list() {
		global $wpdb;
		$maps_table = $wpdb->prefix . 'mappress_maps';
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $maps_table"));

		if ($results === false)
			return false;

		// Fix up mapid
		foreach ($results as $result) {
			$mapdata = (array) unserialize($result->obj);
			$map = new Mappress_Map($mapdata);
			$map->mapid = $result->mapid;
			$maps[] = $map;
		}

		return $maps;
	}

	function save($postid) {
		global $wpdb;
		$maps_table = $wpdb->prefix . 'mappress_maps';
		$posts_table = $wpdb->prefix . 'mappress_posts';

		$map = serialize($this);

		// Update map
		if (!$this->mapid) {
			// If no ID then autonumber
			$result = $wpdb->query($wpdb->prepare("INSERT INTO $maps_table (obj) VALUES(%s)", $map));
			$this->mapid = (int)$wpdb->get_var("SELECT LAST_INSERT_ID()");
		} else {
			// Id provided, so insert or update
			$result = $wpdb->query($wpdb->prepare("INSERT INTO $maps_table (mapid, obj) VALUES(%d, '%s') ON DUPLICATE KEY UPDATE obj = %s", $this->mapid, $map, $map));
		}

		if ($result === false || !$this->mapid)
			return false;

		// Update posts
		$result = $wpdb->query($wpdb->prepare("INSERT INTO $posts_table (postid, mapid) VALUES(%d, %d) ON DUPLICATE KEY UPDATE postid = %d, mapid = %d", $postid, $this->mapid,
			$postid, $this->mapid));

		if ($result === false)
			return false;

		$wpdb->query("COMMIT");
		return $this->mapid;
	}

	/**
	* Delete a map and all of its post assignments
	*
	* @param mixed $mapid
	*/
	function delete($mapid) {
		global $wpdb;
		$maps_table = $wpdb->prefix . 'mappress_maps';
		$posts_table = $wpdb->prefix . 'mappress_posts';

		// Delete from posts table
		$result = $wpdb->query($wpdb->prepare("DELETE FROM $posts_table WHERE mapid = %d", $mapid));
		if ($result === false)
			return false;

		$result = $wpdb->query($wpdb->prepare("DELETE FROM $maps_table WHERE mapid = %d", $mapid));
		if ($result === false)
			return false;

		$wpdb->query("COMMIT");
		return true;
	}

	/**
	* Delete a map assignment(s) for a post
	* If $mapid is null, then ALL maps will be removed from the post
	*
	* @param int $mapid Map to remove
	* @param int $postid Post to remove from
	* @return TRUE if map has been removed, FALSE if map wasn't assigned to the post
	*/
	function delete_post_map($postid, $mapid=null) {
		global $wpdb;
		$posts_table = $wpdb->prefix . 'mappress_posts';

		if (!$postid)
			return true;

		if ($mapid)
			$results = $wpdb->query($wpdb->prepare("DELETE FROM $posts_table WHERE postid = %d AND mapid = %d", $postid, $mapid));
		else
			$results = $wpdb->query($wpdb->prepare("DELETE FROM $posts_table WHERE postid = %d", $postid));

		$wpdb->query("COMMIT");

		if ($results === false)
			return false;

		return true;
	}

	/**
	* Find any map for the post that was created automatically from a custom field
	*
	* @param mixed $postid
	* @return Mappress_Map
	*/
	function get_post_meta_map ($postid) {
		global $wpdb;
		$posts_table = $wpdb->prefix . 'mappress_posts';

		// Search by meta_key
		$results = $wpdb->get_results($wpdb->prepare("SELECT mapid FROM $posts_table WHERE postid = %d", $postid));

		if ($results === false)
			return false;

		// Find which map, if any was generated automatically
		foreach($results as $key => $result) {
			$map = Mappress_Map::get($result->mapid);
			if ($map->metaKey)
				return $map;
		}
	}


	/**
	* Get a list of maps attached to the post
	*
	* @param int $postid Post for which to get the list
	* @return an array of all maps for the post or FALSE if no maps
	*/
	function get_post_map_list($postid) {
		global $wpdb;
		$posts_table = $wpdb->prefix . 'mappress_posts';

		$results = $wpdb->get_results($wpdb->prepare("SELECT postid, mapid FROM $posts_table WHERE postid = %d", $postid));

		if ($results === false)
			return false;

		// Get all of the maps
		$maps = array();
		foreach($results as $key => $result) {
			$maps[] = Mappress_Map::get($result->mapid);
		}
		return $maps;
	}

	function width() {
		return ( stripos($this->width, 'px') || strpos($this->width, '%')) ? $this->width : $this->width. 'px';
	}

	function height() {
		return ( stripos($this->height, 'px') || strpos($this->height, '%')) ? $this->height : $this->height. 'px';
	}


	/**
	* Display a map
	*
	* @param mixed $atts - override attributes.  Attributes applied from options -> map -> $atts
	*/
	function display($atts = null) {
		global $mappress;

		static $div = 0;

		$this->update($atts);
		$this->options->update($atts);

		// Assign a map name, if none was provided
		if (empty($this->name)) {
			$this->name = "mapp$div";
			$div++;
		}

		// Enqueue the map
		$mappress->enqueue_map($this);

		// Layout
		if (class_exists('Mappress_Pro'))
			return $mappress->get_template($this->options->template, array('map' => $this));

		ob_start();
		$map = $this;
		require(Mappress::$basedir . '/forms/map_layout.php');
		return ob_get_clean();
	}

	/**
	* Edit a set of maps for one post
	*
	* @param mixed $maps
	* @param mixed $postid
	*/
	static function edit($maps = null, $postid) {
		global $mappress;

		// Set options for editing
		$options = Mappress_Options::get();
		$options->directions = 'none';
		$options->editable = true;
		$options->initialOpenInfo = false;
		$options->iwType = 'ib';
		$options->mapTypeControl = true;
		$options->navigationControlOptions = array('style' => 0);
		$options->overviewMapControl = true;
		$options->overviewMapControlOptions = array('opened' => false);
		$options->postid = $postid;

		// All map types are allowed, including custom types
		$options->mapTypeIds = array_merge(array('roadmap', 'satellite', 'terrain', 'hybrid'), array_keys(Mappress::$options->styles));

		// Always show the map type control as a drop-down in the editor or there may not be space for them
		$options->mapTypeControlStyle = 2;

		// Enqueue the maps
		$mappress->enqueue_map($maps, 'editor', $options);

		// Editor
		require(Mappress::$basedir . '/forms/map_editor.php');
	}

	/**
	* Default action to sort the map
	*
	* @param mixed $map
	*/
	function sort_pois() {
		if (!$this->options->sort)
			return;

		usort($this->pois, function($a, $b) {
			if ($a->title > $b->title)
				return 1;

			if ($a->title < $b->title)
				return -1;

			return 0;
		});
		do_action('mappress_sort_pois', $this);
	}

	function get_border_style() {
		$style = '';

		$border = $this->options->border;
		if ($border['style']) {
			$style .= sprintf("border: %spx %s %s; ", $border['width'], $border['style'], $border['color']);

			if ($border['radius']) {
				$radius = $border['radius'] . 'px';
				$style .= " border-radius: $radius; -moz-border-radius: $radius; -webkit-border-radius: $radius; -o-border-radius:$radius ";
			}
		}

		if ($border['shadow'])
			$style .= " -moz-box-shadow: 10px 10px 5px #888; -webkit-box-shadow: 10px 10px 5px #888; box-shadow: 10px 10px 5px #888;";

		return $style;
	}

	function get_layout_style() {
		$style = $this->get_border_style();
		if ($this->options->hidden)
			$style .= ' display:none;';
		return $style;
	}

	function get_show_link($args = '') {
		extract(wp_parse_args($args, array(
			'text' => __('Show map', 'mappress')
		)));

		if (!$this->options->hidden)
			return '';

		$click = "{$this->name}.show(); return false;";
		return "<a href='#' onclick='$click'>$text</a>";
	}

	function get_center_link($args = '') {
		extract(wp_parse_args($args, array(
			'text' => __('Center map', 'mappress')
		)));

		$click = "{$this->name}.autoCenter(true); return false;";
		return "<a href='#' onclick='$click'>$text</a>";
	}

	function get_bigger_link($args = '') {
		extract(wp_parse_args($args, array(
			'big_text' => "&raquo; " . __('Bigger map', 'mappress'),
			'small_text' => "&laquo; " . __('Smaller map', 'mappress')
		)));

		$click = "{$this->name}.bigger(this, \"$big_text\", \"$small_text\"); return false;";
		return "<a href='#' onclick='$click'>$big_text</a>";
	}

	function get_links() {
		$links = (array) $this->options->mapLinks;
		$a = array();

		if (in_array('center', $links))
			$a[] = $this->get_center_link();
		if (in_array('bigger', $links))
			$a[] = $this->get_bigger_link();

		if (empty($a))
			return "";

		return implode('', $a);
	}


} // End class Mappress_Map
?>
