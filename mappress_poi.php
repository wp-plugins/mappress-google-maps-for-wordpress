<?php
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
		global $mappress, $post;

		if (class_exists('Mappress_Pro')) {
			// For linked mashups, set $post in the template to the underlying post
			// For single posts, $post is set to the current post (last one displayed in the loop) - so it's usually useless
			$_post = ($this->postid) ? get_post($this->postid) : $post;
			$html = apply_filters('mappress_poi_html', $mappress->get_template($this->map()->options->templatePoi, array('poi' => $this, 'post' => $_post)), $this);
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

		// Directions (not available for shapes, kml)
		if (empty($this->type)) {
			if (in_array('directions_to', $links) && $map->options->directions != 'none')
				$a[] = $this->get_directions_link(array('to' => $this));
			if (in_array('directions_from', $links) && $map->options->directions != 'none')
				$a[] = $this->get_directions_link(array('from' => $this, 'to' => ''));
		}

		// Zoom isn't available in poi list by default
		if (in_array('zoom', $links) && $context != 'poi_list')
			$a[] = $this->get_zoom_link();

		if (empty($a))
			return "";

		$html = implode('&nbsp;&nbsp', $a);
		$html = "<div style='clear:both'></div><div class='mapp-links'>$html</div>";
		return apply_filters('mappress_poi_links_html', $html, $context, $this);
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
}
?>