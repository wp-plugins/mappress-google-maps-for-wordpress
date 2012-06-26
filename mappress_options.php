<?php
/**
* Options
*/
class Mappress_Options extends Mappress_Obj {
	var $alignment = 'default',
		$autodisplay = 'top',
		$bicycling = false,
		$border = array('style' => null, 'width' => 1, 'radius' => 0, 'color' => '#000000', 'shadow' => false),
		$country,
		$defaultIcon,
		$demoMap = true,
		$directions = 'inline',                             // inline | google | none
		$directionsServer = 'maps.google.com',
		$directionsUnits = '',
		$editable = false,
		$initialBicycling = false,
		$initialOpenDirections = false,
		$initialOpenInfo = false,
		$initialTraffic = false,        // Initial setting for traffic checkbox (true = checked)
		$keyboardShortcuts = true,
		$language,
		$mapName,
		$mapSizes = array(array('label' => null, 'width' => 300, 'height' => 300), array('label' => null, 'width' => 425, 'height' => 350), array('label' => null, 'width' => 640, 'height' => 480)),
		$mapTypeControl = true,
		$mapTypeControlStyle = 0,   // 0=default, 1=horizontal, 2=dropdown
		$maxZoom,
		$minZoom,
		$metaKey,
		$metaKeyErrors,
		$metaSyncSave = true,
		$metaSyncUpdate = true,
		$overviewMapControl = true,
		$overviewMapControlOpened = false,
		$panControl = true,
		$poiList = false,
		$poiListTemplate = "<td class='mapp-marker'>[icon]</td><td><b>[title]</b>[directions]</td>",
		$postid,
		$postTypes = array('post', 'page'),
		$rotateControl = true,
		$scaleControl = false,
		$scrollwheel = false,
		$streetViewControl = true,
		$tooltips = true,
		$traffic = false,
		$zoomControl = true,
		$zoomControlStyle = 0   // 0=default, 1=small, 2=large, 4=android
		;

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
		// Register menu settings
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_init', array(&$this, 'admin_init'));
	}

	function admin_menu() {
		// Add menu
		$pagehook = add_options_page('MapPress', 'MapPress', 'manage_options', 'mappress', array(&$this, 'options_page'));

		// Add settings scripts
		add_action("admin_print_scripts-{$pagehook}", array(&$this, 'admin_print_scripts'));
		add_action("admin_print_styles-{$pagehook}", array(&$this, 'admin_print_styles'));
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
		wp_enqueue_style('mappress', plugins_url('/css/mappress.css', __FILE__), null, Mappress::VERSION);
		wp_enqueue_style('farbtastic');
	}

	function admin_init() {
		$this->options = Mappress_Options::get();

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

		add_settings_section('map_style_settings', __('Map Style', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('defaultIcon', __('Default icon', 'mappress'), array($this, 'set_pro'), 'mappress', 'map_style_settings');
		add_settings_field('alignment', __('Map alignment', 'mappress'), array(&$this, 'set_alignment'), 'mappress', 'map_style_settings');
		add_settings_field('border', __('Map border', 'mappress'), array(&$this, 'set_border'), 'mappress', 'map_style_settings');

		add_settings_section('localization_settings', __('Localization', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('language', __('Language', 'mappress'), array(&$this, 'set_language'), 'mappress', 'localization_settings');
		add_settings_field('country', __('Country', 'mappress'), array(&$this, 'set_country'), 'mappress', 'localization_settings');
		add_settings_field('directionsServer', __('Directions server', 'mappress'), array(&$this, 'set_directions_server'), 'mappress', 'localization_settings');
		add_settings_field('directionsUnits', __('Directions units', 'mappress'), array(&$this, 'set_directions_units'), 'mappress', 'localization_settings');

		add_settings_section('template_settings', __('Templates', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('poiListTemplate', __('POI list template', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'template_settings');

		add_settings_section('custom_field_settings', __('Custom Fields', 'mappress'), array(&$this, 'section_settings'), 'mappress');
		add_settings_field('metaKey', __('Custom fields', 'mappress'), array(&$this, 'set_pro'), 'mappress', 'custom_field_settings');

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
			$item = self::convert_to_boolean($item);

		if (!class_exists('Mappress_Pro')) {
			$input['control'] = true;
			unset($input['metaKey'], $input['metaSyncSave'], $input['metaSyncUpdate']);
		}
		return $input;
	}

	function section_settings() {}

	function set_country() {
		$country = $this->options->country;
		$cctld_link = '<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("country code", 'mappress') . '</a>';

		printf(__('Enter a %s to use when searching (leave blank for USA)', 'mappress'), $cctld_link);
		echo ": <input type='text' size='2' name='mappress_options[country]' value='$country' />";
	}

	function set_directions_server() {
		$directions_server = $this->options->directionsServer;

		echo __('Enter a google server URL for directions/printing');
		echo ": <input type='text' size='20' name='mappress_options[directionsServer]' value='$directions_server' />";
	}

	function set_directions_units() {
		$units = array('' => __('(Default)', 'mappress'), 0 => __('Metric (kilometers)', 'mappress'), 1 => __('Imperial (miles)', 'mappress'));
		echo self::dropdown($units, $this->options->directionsUnits, 'mappress_options[directionsUnits]');
	}

	function set_scrollwheel() {
		echo self::checkbox($this->options->scrollwheel, 'mappress_options[scrollwheel]');
		_e('Enable zoom with the mouse scroll wheel', 'mappress');
	}

	function set_keyboard_shortcuts() {
		echo self::checkbox($this->options->keyboardShortcuts, 'mappress_options[keyboardShortcuts]');
		_e('Enable keyboard panning and zooming', 'mappress');
	}

	function set_language() {
		$language = $this->options->language;
		$lang_link = '<a target="_blank" href="http://code.google.com/apis/maps/faq.html#languagesupport">' . __("language", 'mappress') . '</a>';

		printf(__('Use a specific %s for map controls (defaults to browser language)', 'mappress'), $lang_link);
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
		$overview_map_control_opened = self::checkbox($this->options->overviewMapControlOpened, 'mappress_options[overviewMapControlOpened]') . __('Open initially');
		$traffic = self::checkbox($this->options->traffic, 'mappress_options[traffic]');
		$initial_traffic = self::checkbox($this->options->initialTraffic, 'mappress_options[initialTraffic]') . __('Checked initially');
		$bicycling = self::checkbox($this->options->bicycling, 'mappress_options[bicycling]');
		$initial_bicycling = self::checkbox($this->options->initialBicycling, 'mappress_options[initialBicycling]') . __('Checked initially');

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

	function set_directions() {
		$directions = $this->options->directions;

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

	function set_initial_open_info() {
		echo self::checkbox($this->options->initialOpenInfo, 'mappress_options[initialOpenInfo]');
		_e('Automatically open the first POI when a map is displayed', 'mappress');
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
		echo self::checkbox($this->options->tooltips, 'mappress_options[tooltips]');
		_e('Show POI titles as a "tooltip" on mouse-over', 'mappress');
	}

	function set_alignment() {
		$alignment = $this->options->alignment;

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

		echo "<br/><i>" . sprintf(__("Choose 'default' to override this with CSS class %s in your theme's %s", 'mappress'), "<code>.mapp-container</code>", "<code>styles.css</code>")  . "</i>";
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

		// Border width
		for ($i = 1; $i <= 20; $i++)
			$widths[] = $i . "px";
		echo "&nbsp; " . __("Width", 'mappress') . ": <select name='mappress_options[border][width]'>";
		foreach ($widths as $width)
			echo "<option " . selected($width, $border['width'], false) . " value='$width'>$width</option>";
		echo "</select>";

		// Corners
		for ($i = 1; $i <= 10; $i++)
			$radii[$i] = $i . "px";
		echo "&nbsp; " . __("Corner radius", 'mappress');
		echo self::dropdown($radii, $border['radius'], 'mappress_options[border][radius]', array('none' => true));

		// Border color
		echo "&nbsp; " . __("Color", 'mappress');
		echo ": <input type='text' id='mappress_border_color' name='mappress_options[border][color]' value='" . $border['color'] . "' size='10'/>";

		// Shadow
		echo "&nbsp; " . self::checkbox($border['shadow'], 'mappress_options[border][shadow]');
		echo "&nbsp;" . __("Shadow", 'mappress');

		echo "<br/><i>" . sprintf(__("Choose -none- to override settings with CSS class %s in your theme's %s", 'mappress'), "<code>.mapp-canvas-panel</code>", "<code>styles.css</code>")  . "</i>";

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

	function set_autodisplay() {
		$autodisplay = $this->options->autodisplay;

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
		$post_types = $this->options->postTypes;
		$all_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
		$all_post_types[] = 'post';
		$all_post_types[] = 'page';

		echo __("Post types where the MapPress Editor should be available", "mappress") . ": <br/>";

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
		global $mappress;
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

	static function checkbox($data, $name) {
		$html = "<input type='hidden' name='$name' value='false' />";
		$html .= "<input type='checkbox' name='$name' value='true' " . checked($data, true, false) . " />";
		return $html;
	}

	/**
	* Generate a set of radio buttons
	*
	* $args values:
	*    layout => h | v - layout the radio buttons horizontally (default) or vertically

	*
	* @param array  $data  - array of (key => description) to display.  If description is itself an array, only the first column is used
	* @param string $selected - currently selected value
	* @param string $name - HTML field name
	*/
	static function radio($data, $selected, $name='', $args=null) {
		$defaults = array(
			'layout' => 'h'
		);

		if (!is_array($data) || empty($data))
			return "";

		extract(wp_parse_args($args, $defaults));

		$name = ($name) ? "name='$name'" : "";

		$html = "";
		foreach ((array)$data as $key => $description) {
			$key = esc_attr($key);
			$description = esc_attr($description);

			$html .= "<input type='radio' $name value='$key' " . checked($selected, $key, false) . " />$description &nbsp;&nbsp;";
			if (!$layout == 'v')
				$html .= "<br/>";
		}
		return $html;
	}

	/**
	* Outputs a table
	*
	* @param mixed array $headers - array of column header strings
	* @param mixed array $rows - array of rows
	* @param mixed $div_class - a class to apply to the table's <div>
	* @param mixed $table_class - a class to apply to the <table>
	*/
	function table($headers, $rows, $table_class='mapp-table') {
		$html = "<table class='$table_class'><thead><tr>";

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


	static function convert_to_boolean($data) {
		if ($data === 'false')
			return false;

		if ($data === 'true')
			return true;

		if (is_array($data)) {
			foreach($data as &$datum)
				$datum = self::convert_to_boolean($datum);
		}

		return $data;
	}
} // End class Mappress_Options_Menu
?>