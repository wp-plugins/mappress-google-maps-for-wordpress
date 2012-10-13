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
	* @return true if auto=true and success | WP_Error on failure
	*/
	function geocode($auto=true) {
		if (!class_exists('Mappress_Pro'))
			return new WP_Error('geocode', 'MapPress Pro required for geocoding', 'mappress');

		// If point has a lat/lng then no geocoding, but set address, title (3.0)
		if (!empty($this->point['lat']) && !empty($this->point['lng'])) {
			if ($this->address)
				$this->correctedAddress = $this->address;

			$this->viewport = null;

		} else {
			$location = Mappress::$geocoders->geocode($this->address);

			if (is_wp_error($location))
				return $location;

			$this->point = array('lat' => $location->lat, 'lng' => $location->lng);
			$this->correctedAddress = $location->corrected_address;
			$this->viewport = $location->viewport;
		}

		// Guess a default title / body - use address if available or lat, lng if not
		if (empty($this->title) && empty($this->body)) {
			if ($this->correctedAddress) {
				$parsed = Mappress::$geocoders->parse_address($this->correctedAddress);
				$this->title = $parsed[0];
				$this->body = (isset($parsed[1])) ? $parsed[1] : "";
			} else {
				$this->title = $this->point['lat'] . ',' . $this->point['lng'];
			}
		}
	}

	function get_html() {
		global $mappress, $post;

		if (class_exists('Mappress_Pro')) {
			$html = $mappress->get_template($this->map()->options->templatePoi, array('poi' => $this));
			$html = apply_filters('mappress_poi_html', $html, $this);
		} else {
			$html = "<div class='mapp-title'>" . $this->title . "</div>" .
			"<div class='mapp-body'>" . $this->body . "</div>" .
			$this->get_links();
		}
		$this->html = $html;
	}

	/**
	* Get the linked post, if any
	*/
	function get_post() {
		if (!$this->postid)
			return null;

		return get_post($this->postid);
	}

	/**
	* Return the title, or a permalink to the title if marker_link = true
	*
	*/
	function get_title_link() {

		$title = $this->get_title();

		if ($this->postid) {
			return "<a href='" . get_permalink($this->postid) . "'>$title</a>";
		} else {
			return $title;
		}
	}

	function get_title() {
		if ($this->postid) {
			$post = get_post($this->postid);
			return $post->post_title;
		} else {
			return $this->title;
		}
	}

	function get_body() {
		if ($this->postid) {
			$post = get_post($this->postid);
			return apply_filters('mappress_poi_excerpt', '', $this);
		} else {
			return $this->body;
		}
	}

	function get_custom($field, $single = true) {
		if (!$this->postid)
			return "";

		return get_post_meta($this->postid, $key, $single);
	}

	/**
	* Get the formatted address as HTML
	* A <br> tag is inserted between the first line and subsequent lines
	*
	*/
	function get_address() {
		$parsed = Mappress::$geocoders->parse_address($this->correctedAddress);
		$html = "";

		if ($parsed) {
			$html = $address[0];
			if (isset($address[1]))
				$html .= "<br/>" . $address[1];
		}
		return $html;
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
				$a[] = $this->get_directions_link(array('to' => $this, 'text' => __('Directions to', 'mappress')));
			if (in_array('directions_from', $links) && $map->options->directions != 'none')
				$a[] = $this->get_directions_link(array('from' => $this, 'to' => '', 'text' => __('Directions from')));
		}

		// Zoom isn't available in poi list by default
		if (in_array('zoom', $links) && $context != 'poi_list')
			$a[] = $this->get_zoom_link();

		if (empty($a))
			return "";

		$html = implode('&nbsp;&nbsp', $a);
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
			'title' => $this->get_title(),
			'zoom' => null
		)));

		$i = array_search($this, $map->pois);
		$zoom = Mappress::boolean_to_string($zoom);
		return "<a href='#' onclick='{$map->name}.getPoi($i).open($zoom); return false;' >$title</a>";
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