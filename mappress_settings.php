<?php
/**
* Options
*/
class Mappress_Options extends Mappress_Obj {
	var $alignment = 'default',
		$autodisplay = 'top',
		$bicycling = false,
		$border = array('style' => 'solid', 'width' => 1, 'radius' => 0, 'color' => '#000000', 'shadow' => false),
		$connect,                       // Connect the pois: null | 'line'
		$country,
		$dataTables = false,     // true | false | settings (defaults are: array('bFilter' => false, 'bPaginate' => false))
		$defaultIcon,
		$demoMap = true,
		$directions = 'inline',                             // inline | google | none
		$directionsServer = 'https://maps.google.com',
		$directionsUnits = '',
		$editable = false,
		$geolocation = true,
		$hidden = false,
		$initialBicycling = false,
		$initialOpenDirections = false,
		$initialOpenInfo = false,
		$initialTraffic = false,        // Initial setting for traffic checkbox (true = checked)
		$iwType = 'iw',                 // iw | ib | none
		$keyboardShortcuts = true,
		$language,
		$mapLinks = array(),            // Links for the map: center | bigger
		$mapSizes = array(array('label' => null, 'width' => 300, 'height' => 300), array('label' => null, 'width' => 425, 'height' => 350), array('label' => null, 'width' => 640, 'height' => 480)),
		$mapTypeControl = true,
		$mapTypeControlStyle = 0,   // 0=default, 1=horizontal, 2=dropdown
		$mapTypeId,                 // Default map type
		$mapTypeIds = array('roadmap', 'satellite', 'terrain', 'hybrid'),
		$marker_link = true,        // Link pois to underlying post for mashups
		$maxZoom,
		$minZoom,
		$metaKey,                   // Deprecated, left temporarily for back-compat
		$metaKeyAddress = array(),  // Array of custom field names, e.g. ('city', 'state', 'zip')
		$metaKeyShortcode,
		$metaKeyLat,                // Custom field name for lat
		$metaKeyLng,                // Custom field name for lng
		$metaKeyErrors,
		$metaSyncSave = true,
		$metaSyncUpdate = false,
		$name,
		$noCSS,
		$overviewMapControl = true,
		$overviewMapControlOpened = false,
		$panControl = false,
		$poiLinks = array('directions_to', 'zoom'), // Links for pois: directions_from | directions_to | zoom
		$poiList = false,
		$postTypes = array('post', 'page'),
		$rotateControl = true,
		$scaleControl = false,
		$scrollwheel = false,
		$sort = true,
		$streetViewControl = true,
		$style,                     // Default custom style
		$styles = array(),          // Array of styles: array('name' => name, 'json' => json)
		$template = 'map_layout',
		$templateDirections = 'map_directions',
		$templatePoi = 'map_poi',
		$templatePoiList = "map_poi_list",
		$thumbs = true,
		$thumbSize,
		$thumbWidth = 64,
		$thumbHeight = 64,
		$tilt = 45,                 // Set to 45 for 45-degree map imagery, 0 to turn it off
		$tooltips = true,
		$traffic = false,
		$zoomControl = true,
		$zoomControlStyle = 0       // 0=default, 1=small, 2=large, 4=android
		;

	function __construct($options = '') {
		$this->update($options);
	}

	// Options are saved as array because WP settings API is fussy about objects
	static function get() {
		$options = get_option('mappress_options');
		return new Mappress_Options($options);
	}

	function save() {
		return update_option('mappress_options', get_object_vars($this));
	}
}      // End class Mappress_Options


/**
* Options menu display
*/
class Mappress_Settings {

	var $options;

	function __construct() {
		$this->options = Mappress_Options::get();
		add_action('admin_init', array(&$this, 'admin_init'));
	}

	function admin_init() {
		register_setting('mappress', 'mappress_options', array($this, 'set_options'));

		add_settings_section('basic_settings', __('Basic Settings', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('autodisplay', __('Automatic map display', 'mappress'), array(&$this, 'set_autodisplay'), 'mappress', 'basic_settings');
		add_settings_field('postTypes', __('Post types', 'mappress'), array(&$this, 'set_post_types'), 'mappress', 'basic_settings');
		add_settings_field('directions', __('Directions', 'mappress'), array(&$this, 'set_directions'), 'mappress', 'basic_settings');
		add_settings_field('poiList', __('POI list', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'basic_settings');
		add_settings_field('scrollwheel', __('Scroll wheel zoom', 'mappress'), array(&$this, 'set_scrollwheel'), 'mappress', 'basic_settings');
		add_settings_field('keyboard', __('Keyboard shortcuts', 'mappress'), array(&$this, 'set_keyboard_shortcuts'), 'mappress', 'basic_settings');
		add_settings_field('initialOpenInfo', __('Open first POI', 'mappress'), array(&$this, 'set_initial_open_info'), 'mappress', 'basic_settings');
		add_settings_field('tooltips', __('Tooltips', 'mappress'), array(&$this, 'set_tooltips'), 'mappress', 'basic_settings');

		add_settings_section('map_controls_settings', __('Map Controls', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('mapControls', __('Map controls', 'mappress'), array(&$this, 'set_map_controls'), 'mappress', 'map_controls_settings');
		add_settings_field('mapTypeIds', __('Map Types', 'mappress'), array(&$this, 'set_map_type_ids'), 'mappress', 'map_controls_settings');

		add_settings_section('appearance_settings', __('Appearance', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('noCSS', __('Turn off CSS', 'mappress'), array(&$this, 'set_no_css'), 'mappress', 'appearance_settings');
		add_settings_field('alignment', __('Map alignment', 'mappress'), array(&$this, 'set_alignment'), 'mappress', 'appearance_settings');
		add_settings_field('border', __('Map border', 'mappress'), array(&$this, 'set_border'), 'mappress', 'appearance_settings');
		add_settings_field('borderColor', __('Map border color', 'mappress'), array(&$this, 'set_border_color'), 'mappress', 'appearance_settings');
		add_settings_field('mapLinks', __('Map links', 'mappress'), array(&$this, 'set_map_links'), 'mappress', 'appearance_settings');
		add_settings_field('poiLinks', __('POI links', 'mappress'), array(&$this, 'set_poi_links'), 'mappress', 'appearance_settings');
		add_settings_field('defaultIcon', __('Default icon', 'mappress'), array($this, 'set_pro'), 'mappress', 'appearance_settings');
		add_settings_field('dataTables', __('Use datatables', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'appearance_settings');
		add_settings_field('thumbs', __('Thumbnails', 'mappress'), array($this, 'set_pro'), 'mappress', 'appearance_settings');
		add_settings_field('thumbSize', __('Thumbnail size', 'mappress'), array($this, 'set_pro'), 'mappress', 'appearance_settings');

		add_settings_section('styled_maps_settings', __('Styled Maps', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('styles', __('Style', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'styled_maps_settings');
		add_settings_field('style', __('Default style', 'mappress'), array($this, 'set_pro'), 'mappress', 'styled_maps_settings');

		add_settings_section('localization_settings', __('Localization', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('language', __('Language', 'mappress'), array(&$this, 'set_language'), 'mappress', 'localization_settings');
		add_settings_field('country', __('Country', 'mappress'), array(&$this, 'set_country'), 'mappress', 'localization_settings');
		add_settings_field('directionsServer', __('Directions server', 'mappress'), array(&$this, 'set_directions_server'), 'mappress', 'localization_settings');
		add_settings_field('directionsUnits', __('Directions units', 'mappress'), array(&$this, 'set_directions_units'), 'mappress', 'localization_settings');

		add_settings_section('geocoding_settings', __('Geocoding', 'mappress'), array(&$this, 'geocoding_section'), 'mappress');
		add_settings_field('metaKeyAddress', __('Address fields', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'geocoding_settings');
		add_settings_field('metaKeyLatLng', __('Lat/lng fields', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'geocoding_settings');
		add_settings_field('metaKeyErrors', __('Errors field', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'geocoding_settings');
		add_settings_field('metaKeySyncSave', __('Update on publish', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'geocoding_settings');
		// Not clear if anyone is still using this
		// add_settings_field('metaKeySyncUpdate', __('Update on change', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'geocoding_settings');

		add_settings_section('misc_settings', __('Miscellaneous', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('mapSizes', __('Map sizes', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'misc_settings');
		add_settings_field('forceresize', __('Force resize', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'misc_settings');
	}

	function set_options($input) {
		global $mappress;

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
			$item = Mappress::string_to_boolean($item);

		// For arrays passed as checkboxes set empty array for no selection
		// Note: for maptypeids, if no checkboxes are set it will revert back to the default
		$input['poiLinks'] = (isset($input['poiLinks'])) ? $input['poiLinks'] : array();
		$input['mapLinks'] = (isset($input['mapLinks'])) ? $input['mapLinks'] : array();
		$input['postTypes'] = (isset($input['postTypes'])) ? $input['postTypes'] : array();

		return $input;
	}

	function section_settings() {}

	function geocoding_section() {
		echo "<p>";
		echo __("Use the settings below to automatically create maps from custom fields containing addresses or lat/lng data.", 'mappress');
		echo "</p>";
	}

	function set_country() {
		$country = $this->options->country;
		$cctld_link = '<a style="vertical-align:text-bottom" target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("country code", 'mappress') . '</a>';

		printf(__('Enter a %s to use when searching (leave blank for USA)', 'mappress'), $cctld_link);
		echo ": <input type='text' size='2' name='mappress_options[country]' value='$country' />";
	}

	function set_directions_server() {
		$directions_server = $this->options->directionsServer;

		echo __('Enter a google server URL for directions/printing', 'mappress');
		echo ": <input type='text' size='20' name='mappress_options[directionsServer]' value='$directions_server' />";
	}

	function set_directions_units() {
		$units = array('' => __('(Default)', 'mappress'), 0 => __('Metric (kilometers)', 'mappress'), 1 => __('Imperial (miles)', 'mappress'));
		echo self::dropdown($units, $this->options->directionsUnits, 'mappress_options[directionsUnits]');
	}

	function set_scrollwheel() {
		echo self::checkbox($this->options->scrollwheel, 'mappress_options[scrollwheel]', __('Enable zoom with the mouse scroll wheel', 'mappress'));
	}

	function set_keyboard_shortcuts() {
		echo self::checkbox($this->options->keyboardShortcuts, 'mappress_options[keyboardShortcuts]', __('Enable keyboard panning and zooming', 'mappress'));
	}

	function set_language() {
		$language = $this->options->language;

		$lang_link = '<a style="vertical-align:text-bottom" target="_blank" href="http://code.google.com/apis/maps/faq.html#languagesupport">' . __("language", 'mappress') . '</a>';

		printf(__('Use a specific %s for map controls and geocoding', 'mappress'), $lang_link);
		echo ": <input type='text' size='2' name='mappress_options[language]' value='$language' />";

	}

	function set_map_controls() {

		$map_type_styles = array(
			'0' => __('Default', 'mappress'),
			'1' => __('Horizontal bar', 'mappress'),
			'2' => __('Dropdown menu', 'mappress')
		);

		$zoom_styles = array(
			'0' => __('Default', 'mappress'),
			'1' => __('Small', 'mappress'),
			'2' => __('Large', 'mappress'),
			'4' => __('Android', 'mappress')
		);

		$map_type_control = self::checkbox($this->options->mapTypeControl, 'mappress_options[mapTypeControl]');
		$map_type_control_style = self::radio($map_type_styles, $this->options->mapTypeControlStyle, 'mappress_options[mapTypeControlStyle]');
		$pan_control = self::checkbox($this->options->panControl, 'mappress_options[panControl]');
		$zoom_control = self::checkbox($this->options->zoomControl, 'mappress_options[zoomControl]');
		$zoom_control_style = self::radio($zoom_styles, $this->options->zoomControlStyle, 'mappress_options[zoomControlStyle]');
		$streetview_control = self::checkbox($this->options->streetViewControl, 'mappress_options[streetViewControl]');
		$scale_control = self::checkbox($this->options->scaleControl, 'mappress_options[scaleControl]');
		$overview_map_control = self::checkbox($this->options->overviewMapControl, 'mappress_options[overviewMapControl]');
		$overview_map_control_opened = self::checkbox($this->options->overviewMapControlOpened, 'mappress_options[overviewMapControlOpened]',  __('Open initially', 'mappress'));
		$traffic = self::checkbox($this->options->traffic, 'mappress_options[traffic]');
		$initial_traffic = self::checkbox($this->options->initialTraffic, 'mappress_options[initialTraffic]', __('Checked initially', 'mappress'));
		$bicycling = self::checkbox($this->options->bicycling, 'mappress_options[bicycling]');
		$initial_bicycling = self::checkbox($this->options->initialBicycling, 'mappress_options[initialBicycling]', __('Checked initially', 'mappress'));

		$headers = array(__('Control', 'mappress'), __('Enable'), __('Style', 'mappress'));
		$rows = array();
		$rows = array(
			array(__('Map type control', 'mappress'), $map_type_control, $map_type_control_style ),
			array(__('Pan control', 'mappress'), $pan_control, '' ),
			array(__('Zoom control', 'mappress'), $zoom_control, $zoom_control_style ),
			array(__('Street view "peg man"', 'mappress'), $streetview_control, '' ),
			array(__('Scale control', 'mappress'), $scale_control, '' ),
			array(__('Overview map', 'mappress'), $overview_map_control, $overview_map_control_opened ),
			array(__('Traffic', 'mappress'), $traffic, $initial_traffic ),
			array(__('Bike routes', 'mappress'), $bicycling, $initial_bicycling ),
		);
		echo self::table($headers, $rows);
	}

	function set_map_type_ids() {
		$labels = array(
			'roadmap' => __('Road map', 'mappress'),
			'satellite' => __('Satellite', 'mappress'),
			'terrain' => __('Terrain', 'mappress'),
			'hybrid' => __('Hybrid', 'mappress'),
		);

		foreach ($this->options->styles as $name => $json)
			$labels[$name] = $name;

		echo self::checkbox_list($this->options->mapTypeIds, 'mappress_options[mapTypeIds][]', $labels);
	}

	function set_directions() {
		$directions = $this->options->directions;

		$directions_types = array(
			'inline' => __('Inline (in your blog)', 'mappress'),
			'google' => __('Google', 'mappress'),
			'none' => __('None', 'mappress')
		);

		echo self::radio($directions_types, $directions, 'mappress_options[directions]');
		echo "<br/><i>" . __("Select 'Google' if directions aren't displaying properly in your theme", 'mappress') . "</i>";
	}

	function set_initial_open_info() {
		echo self::checkbox($this->options->initialOpenInfo, 'mappress_options[initialOpenInfo]', __('Automatically open the first POI when a map is displayed', 'mappress'));
	}

	function set_bicycling() {
		echo self::checkbox($this->options->bicycling, 'mappress_options[bicycling]');
		_e('Show control', 'mappress');

		echo "&nbsp;&nbsp;";
		echo self::checkbox($this->options->initialBicycling, 'mappress_options[initialBicycling]');
		_e ('Enabled by default', 'mappress');
	}

	function set_traffic() {
		echo self::checkbox($this->options->traffic, 'mappress_options[traffic]');
		_e('Show control', 'mappress');

		echo "&nbsp;&nbsp;";
		echo self::checkbox($this->options->initialTraffic, 'mappress_options[initialTraffic]');
		_e ('Enabled by default', 'mappress');
	}


	function set_tooltips() {
		echo self::checkbox($this->options->tooltips, 'mappress_options[tooltips]', __('Show POI titles as a "tooltip" on mouse-over', 'mappress'));
	}

	function set_no_css() {
		echo self::checkbox($this->options->noCSS, 'mappress_options[noCSS]', sprintf(__("Don't load the %s stylesheet", 'mappress'), '<code>mappress.css</code>'));
	}

	function set_alignment() {
		$image = "<img src='" . Mappress::$baseurl . "/images/%s' style='vertical-align:middle' />";

		$alignments = array(
			'default' => __('Default', 'mappress'),
			'center' => sprintf($image, 'justify_center.png') . __('Center', 'mappress'),
			'left' => sprintf($image, 'justify_left.png') . __('Left', 'mappress'),
			'right' => sprintf($image, 'justify_right.png') . __('Right', 'mappress')
		);

		echo self::radio($alignments, $this->options->alignment, 'mappress_options[alignment]');
		return;
	}

	function set_border() {
		$border = $this->options->border;

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

		$widths = array();
		for ($i = 1; $i <= 20; $i++)
			$widths[$i] = $i . "px";
		echo "&nbsp; " . __("Width", 'mappress') . ":";
		echo self::dropdown($widths, $border['width'], 'mappress_options[border][width]', array('none' => true));

		// Corners
		for ($i = 1; $i <= 10; $i++)
			$radii[$i] = $i . "px";
		echo "&nbsp; " . __("Corner radius", 'mappress') . ":";
		echo self::dropdown($radii, $border['radius'], 'mappress_options[border][radius]', array('none' => true));
	}

	function set_border_color() {
		$border = $this->options->border;

		// Border color
		echo __("Color", 'mappress');
		echo ": <input type='text' id='mappress_border_color' name='mappress_options[border][color]' value='" . $border['color'] . "' size='10'/>";

		// Color wheel
		$script = "jQuery(document).ready(function() {
			jQuery('#mappress_border_color').mappColorpicker({});
		});";
		echo Mappress::script($script);

		// Shadow
		echo self::checkbox($this->options->border['shadow'], 'mappress_options[border][shadow]', __("Display shadow", 'mappress'));
	}

	function set_map_links() {
		$labels = array(
			'bigger' => __('Bigger map', 'mappress'),
			'center' => __('Center map', 'mappress')
		);
		echo self::checkbox_list($this->options->mapLinks, 'mappress_options[mapLinks][]', $labels);
	}

	function set_poi_links() {
		$labels = array(
			'zoom' => __('Zoom', 'mappress'),
			'directions_to' => __('Directions to', 'mappress'),
			'directions_from' => __('Directions from', 'mappress')
		);
		echo self::checkbox_list($this->options->poiLinks, 'mappress_options[poiLinks][]', $labels);
	}

	function set_autodisplay() {
		$autos = array(
			'top' => __('Top of post', 'mappress'),
			'bottom' => __('Bottom of post', 'mappress'),
			'none' => __('No automatic display', 'mappress')
		);

		echo self::radio($autos, $this->options->autodisplay, "mappress_options[autodisplay]");
	}

	function set_post_types() {
		$labels = array(
			'post' => __('Posts', 'mappress'),
			'page' => __('Pages', 'mappress'),
		);

		$custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
		foreach ($custom_post_types as $name => $type)
			$labels[$name] = $type->label;
		echo self::checkbox_list($this->options->postTypes, 'mappress_options[postTypes][]', $labels);
		return;
	}

	function set_pro() {
		$pro_link = "<a href='http://wphostreviews.com/product/mappress' title='MapPress Pro'>MapPress Pro</a>";
		echo "<b>" . sprintf(__("This setting requires %s", 'mappress'), $pro_link) . "</b>";
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
			echo "<li>" . __('No new items', 'mappress') . "</li>";
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
		$rate_link = "<a href='http://wordpress.org/extend/plugins/mappress-easy-google-maps'>" . __('Rate it 5 Stars', 'mappress') . "</a>";
		echo "<ul>";
		echo "<li>" . sprintf(__('%s on WordPress.org', 'mappress'), $rate_link) . "</li>";
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

	function metabox_demo($object, $metabox) {
		$poi = new Mappress_Poi(array(
			"title" => sprintf("<a href='http://www.wphostreviews.com/mappress'>%s</a>", __("MapPress", 'mappress')),
			"body" => __("Easy Google Maps", 'mappress'),
			"point" => array('lat' => 37.370157, 'lng' => -119.333496)
		));
		$poi->geocode();
		$pois = array($poi);
		$map = new Mappress_Map(array("width" => "100%", "height" => 300, "pois" => $pois, "poiList" => false, "zoom" => 4));

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
		?>
		<div class="wrap">

			<h2>
				<a target='_blank' href='http://wphostreviews.com/mappress'><img alt='MapPress' title='MapPress' src='<?php echo plugins_url('images/mappress_logo_med.png', __FILE__);?>'></a>
				<span style='font-size: 12px'><?php echo Mappress::get_support_links(); ?></span>
			</h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<?php
						// Output sidebar metaboxes
						if (!class_exists('Mappress_Pro'))
							add_meta_box('metabox_like', __('Like this plugin?', 'mappress'), array(&$this, 'metabox_like'), 'mappress_sidebar', 'side', 'core');

						add_meta_box('metabox_rss', __('MapPress News', 'mappress'), array(&$this, 'metabox_rss'), 'mappress_sidebar', 'side', 'core');

						if ($this->options->demoMap)
							add_meta_box('metabox_demo', __('Sample Map', 'mappress'), array(&$this, 'metabox_demo'), 'mappress_sidebar', 'side', 'core');

						do_meta_boxes('mappress_sidebar', 'side', null);
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

	/**
	* Show a dropdown list
	*
	* $args values:
	*   id ('') - HTML id for the dropdown field
	*   title = HTML title for the field
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
			'class' => null,
			'multiple' => false,
			'select_attr' => ""
		);

		if (!is_array($data))
			return;

		if (empty($data))
			$data = array();

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
				$none = '&nbsp;';
			$data = array('' => $none) + $data;    // Note that array_merge() won't work because it renumbers indexes!
		}

		if (!$id)
			$id = $name;

		$name = ($name) ? "name='$name'" : "";
		$id = ($id) ? "id='$id'" : "";
		$class = ($class) ? "class='$class'" : "";
		$multiple = ($multiple) ? "multiple='multiple'" : "";

		$html = "<select $name $id $class $multiple $select_attr>";

		foreach ((array)$data as $key => $description) {
			$key = esc_attr($key);
			$description = esc_attr($description);

			$html .= "<option value='$key' " . selected($selected, $key, false) . ">$description</option>";
		}
		$html .= "</select>";
		return $html;
	}

	/**
	* Boolean checkbox
	*
	* @param mixed $value - field value
	* @param mixed $name - field name
	* @param mixed $label - field label
	* @param mixed $checked - value to check against (true will set the checkbox only if the value is true)
	*/
	static function checkbox($value, $name, $label = '', $checked = true) {
		return "<input type='hidden' name='$name' value='false' /><label><input type='checkbox' name='$name' value='true' " . checked($value, $checked, false) . " /> $label </label>";
	}

	/**
	* List checkbox
	*
	* @param mixed $value - current field values
	* @param mixed $name - field name
	* @param mixed $labels - array of (key => label) for all possible values
	*/
	static function checkbox_list($values, $name, $labels) {

		$html = "";
		if (empty($values))
			$values = array();

		foreach ($labels as $key => $label) {
			$checked = (in_array($key, $values)) ? "checked='checked'" : "";
			$html .= "<label><input type='checkbox' name='$name' value='$key' " . $checked . " /> $label</label>&nbsp;&nbsp;";
		}

		return $html;
	}

	/**
	* Generate a set of radio buttons
	*
	* @param mixed $values - array of key=>description pairs
	* @param mixed $name
	* @param mixed $checked
	* @return mixed
	*/
	static function radio($values, $checked, $name) {

		$name = ($name) ? "name='$name'" : "";
		$html = "";

		// If the value is an array, loop through it and print each key => description
		foreach ((array)$values as $key => $description) {
			$key = esc_attr($key);
			$html .= "<label><input type='radio' $name value='$key' " . checked($checked, $key, false) . "/> $description &nbsp;&nbsp;</label>";
		}
		return $html;
	}

	/**
	* Outputs a table
	*
	* @param mixed array $headers - array of column header strings
	* @param mixed array $rows - array of rows
	*/
	static function table($headers, $rows, $args = '') {
		$defaults = array(
			'table_class' => 'mapp-table',
			'table_style' => '',
		);

		extract(wp_parse_args($args, $defaults));

		$html = "<table class='$table_class' style='$table_style'><thead><tr>";

		foreach ((array)$headers as $header)
			$html .= "<th>$header</th>";
		$html .= "</tr></thead><tbody>";

		foreach ((array)$rows as $row) {
			$html .= "<tr>";
			foreach ((array)$row as $col)
				$html .= "<td>$col</td>";
			$html .= "</tr>";
		}
		$html .= "</tbody></table>";
		return $html;
	}

	/**
	* Get a list of custom fields
	*
	*/
	static function get_meta_keys() {
		global $wpdb;
		$keys = $wpdb->get_col( "
			SELECT DISTINCT meta_key
			FROM $wpdb->postmeta
			WHERE meta_key NOT in ('_edit_last', '_edit_lock', '_encloseme', '_pingme', '_thumbnail_id')
			AND meta_key NOT LIKE ('\_wp%')"
		);
		$meta_keys = is_array($keys) ? array_combine($keys, $keys) : array();
		return $meta_keys;
	}
} // End class Mappress_Settings
?>
