<?php
class Mappress_Map extends Mappress_Obj {
	var $mapid,
		$width = 425,
		$height = 350,
		$zoom,
		$center = array('lat' => 0, 'lng' => 0),
		$mapTypeId = 'roadmap',
		$title = 'Untitled',
		$metaKey,
		$query,
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
		$options->mapTypeIds = null;
		$options->navigationControlOptions = array('style' => 0);
		$options->overviewMapControl = true;
		$options->overviewMapControlOptions = array('opened' => false);
		$options->postid = $postid;

		// Always show the map type control as a drop-down in the editor or there may not be space for them
		$options->mapTypeControlStyle = 2;

		// Enqueue the maps
		$mappress->enqueue_map($maps, 'editor', $options);

		// Editor
		require(Mappress::$basedir . '/forms/map_editor.php');
	}

	/**
	* Compare two POIs - needed because WordPress only uses PHP 5.2, so no anonymous functions can be used
	*
	* @param mixed $a
	* @param mixed $b
	* @return mixed
	*/
	static function compare_pois($a, $b) {
		if ($a->title > $b->title)
			return 1;

		if ($a->title < $b->title)
			return -1;

		return 0;
	}

	/**
	* Default action to sort the map
	*
	* @param mixed $map
	*/
	function sort_pois() {
		if (!$this->options->sort)
			return;

		usort($this->pois, array('Mappress_Map', 'compare_pois'));
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

	function get_reset_link($args = '') {
		extract(wp_parse_args($args, array(
			'text' => __('Reset map', 'mappress')
		)));

		$click = "{$this->name}.reset(); return false;";
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
		if (in_array('reset', $links))
			$a[] = $this->get_reset_link();
		if (in_array('bigger', $links))
			$a[] = $this->get_bigger_link();

		if (empty($a))
			return "";

		return implode('', $a);
	}
}
?>