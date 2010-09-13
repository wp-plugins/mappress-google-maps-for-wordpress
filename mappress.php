<?php
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 1.7.3
Author: Chris Richardson
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/


class Mappress {
	var $version = '1.7.3';
	var $debug = false;
	var $basename;

	function mappress()  {
		$this->debugging();

		// Fix for bug in beta 1.7.2 only - reset options
		if ($this->version == '1.7.3' && get_option('mappress_version') < '1.7.3') {
			update_option('mappress_version', '1.7.3');
			delete_option('mappress_options');
		}

		$this->basename = plugin_basename(__FILE__);
		load_plugin_textdomain('mappress', false, dirname($this->basename) . '/languages');

		register_activation_hook(__FILE__, array(&$this, 'activation'));

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_shortcode('mappress', array(&$this, 'map_shortcodes'));
		add_action('admin_notices', array(&$this, 'admin_notices'));

		// Ajax
		add_action('wp_ajax_mapp_save', array(&$this, 'ajax_save'));
		add_action('wp_ajax_mapp_delete', array(&$this, 'ajax_delete'));
		add_action('wp_ajax_mapp_create', array(&$this, 'ajax_create'));
		add_action('admin_init', array(&$this, 'admin_init'));

		// Deleted posts
		add_action('deleted_post', array(&$this, 'deleted_post'));

	}

	// mp_debug=
	// errors -> PHP errors
	// info -> phpinfo + dump
	// anything else -> use local js
	function debugging() {
		global $wpdb;

		if ($this->debug)
			return;

		$this->debug = (isset($_GET['mp_debug'])) ? $_GET['mp_debug'] : null;

		switch ($this->debug) {
			case 'errors':
				error_reporting(E_ALL);
				ini_set('error_reporting', E_ALL);
				ini_set('display_errors','On');
				$wpdb->show_errors();
				break;

			case 'dump':
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
				break;

			default:
				break;
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

	/**
	* Map a shortcode in a post.  Called by WordPress shortcode processor.
	*
	* @param mixed $atts - shortcode attributes
	*/
	function map_shortcodes($atts='') {
		global $post;

		// This plugin doesn't work for feeds!
		if (is_feed())
			return;

		$mapid = (isset($atts['mapid'])) ? $atts['mapid'] : null;

		// If a mapid was provided then show that map only if it's attached to the post
		if ($mapid) {
			$map = Mappress_Map::get_post_map($post->ID, $mapid);
		// If no mapid show the first map attached to the post
		} else {
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
		// Required for loading jquery UI components because of the way WP splits them.  Example:
		// wp_enqueue_script('jqeury-ui-dialog')
	}


	function activation() {
		global $wpdb;

		// See if upgrade needed
		$current_version = get_option('mappress_version');
		update_option('mappress_version', $this->version);
		if ($current_version >= $this->version || $current_version >= '1.7.1')
			return;

		// Create database tables
		Mappress_Map::db_create();

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
				'directions' => (isset($options['directions']) && $options['directions']) ? true : false,
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
		add_settings_field('directions', __('Directions', 'mappress'), array(&$this, 'set_directions'), 'mappress', 'mappress_settings');
		add_settings_field('mapTypeControl', __('Map types', 'mappress'), array(&$this, 'set_map_type_control'), 'mappress', 'mappress_settings');
		add_settings_field('scrollwheel', __('Scroll wheel zoom', 'mappress'), array(&$this, 'set_scrollwheel'), 'mappress', 'mappress_settings');
		add_settings_field('initialOpenInfo', __('Open first marker', 'mappress'), array(&$this, 'set_initial_open_info'), 'mappress', 'mappress_settings');
		add_settings_field('traffic', __('Show traffic button', 'mappress'), array(&$this, 'set_traffic'), 'mappress', 'mappress_settings');
		add_settings_field('tooltips', __('Tooltips', 'mappress'), array(&$this, 'set_tooltips'), 'mappress', 'mappress_settings');
		add_settings_field('language', __('Language', 'mappress'), array(&$this, 'set_language'), 'mappress', 'mappress_settings');
		add_settings_field('country', __('Country', 'mappress'), array(&$this, 'set_country'), 'mappress', 'mappress_settings');
		add_settings_field('postTypes', __('Custom post types', 'mappress'), array(&$this, 'set_post_types'), 'mappress', 'mappress_settings');
		add_settings_field('customCSS', __('Custom CSS', 'mappress'), array(&$this, 'set_custom_css'), 'mappress', 'mappress_settings');
	}

	function set_options($input) {
		// Force checkboxes to boolean
		$input['directions'] = (isset($input['directions'])) ? true : false;
		$input['mapTypeControl'] = (isset($input['mapTypeControl'])) ? true : false;
		$input['scrollwheel'] = (isset($input['scrollwheel'])) ? true : false;
		$input['initialOpenInfo'] = (isset($input['initialOpenInfo'])) ? true : false;
		$input['traffic'] = (isset($input['traffic'])) ? true : false;
		$input['tooltips'] = (isset($input['tooltips'])) ? true : false;
		$input['customCSS'] = (isset($input['customCSS'])) ? true : false;
		return new Mappress_Options($input);
	}

	function section_settings() {
	}

	function set_country() {
		$country = Mappress_Options::get()->country;
		$cctld_link = '<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("country code", 'mappress') . '</a>';

		echo "<input type='text' size='2' name='mappress_options[country]' value='$country' />";
		printf(__(' Enter a %s to use as a default when searching for addresses (leave blank for USA)', 'mappress'), $cctld_link);
	}

	function set_scrollwheel() {
		$scrollwheel = Mappress_Options::get()->scrollwheel;
		$checked = ($scrollwheel) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[scrollwheel]' $checked />";
		_e(' Enable zooming with the mouse scroll wheel', 'mappress');
	}

	function set_language() {
		$language = Mappress_Options::get()->language;
		$lang_link = '<a target="_blank" href="http://code.google.com/apis/maps/faq.html#languagesupport">' . __("language", 'mappress') . '</a>';

		echo "<input type='text' size='2' name='mappress_options[language]' value='$language' />";
		printf(__(' Force Google to use a specific %s for map controls (if blank Google uses the browser language setting)', 'mappress'), $lang_link);
	}

	function set_map_type_control() {
		$map_type_control = Mappress_Options::get()->mapTypeControl;
		$checked = ($map_type_control) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[mapTypeControl]' $checked />";
		_e (' Allow your readers to change the map type (map types are street, satellite, or hybrid)', 'mappress');
	}

	function set_directions() {
		$directions = Mappress_Options::get()->directions;
		$checked = ($directions) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[directions]' $checked />";
		_e (' Let users to get directions from a map', 'mappress');
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
		_e(' Show marker titles as a "tooltip" on mouse-over.  You may want to switch this off if you use HTML in your marker titles', 'mappress');
	}

	function set_post_types() {
		$post_types = Mappress_Options::get()->postTypes;
		$all_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
		$all_post_types[] = 'post';
		$all_post_types[] = 'page';
		$codex_link = "<a href='http://codex.wordpress.org/Custom_Post_Types'>" . __('post types', 'mappress') . "</a>";

		echo sprintf(__("Mark the %s where you want to use MapPress.  Check 'post' and 'page' to use MapPress in standard posts/pages:", "mappress"), $codex_link) . "<br/>";

		foreach ($all_post_types  as $post_type ) {
			$checked = (in_array($post_type, (array)$post_types)) ? " checked='checked' " : "";
			echo "<input type='checkbox' name='mappress_options[postTypes][]' value='$post_type' $checked />$post_type ";
		}
	}

	function set_custom_css() {
		$custom_css = Mappress_Options::get()->customCSS;
		$checked = ($custom_css) ? " checked='checked'" : "";

		echo "<input type='checkbox' name='mappress_options[customCSS]' $checked />";
			echo sprintf(__(" Include your own CSS file.  You must create a file named %s in the %s directory", 'mappress'), "<code>custom.css</code>", "<code>/mappress/css</code>");
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
				<h4><?php echo __("If you find MapPress useful, please make a donation today!", 'mappress') ?></h4>
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


	/**
	* Options - display option as a field
	*/

	/**
	* Options - display option as a radiobutton
	*/
	function option_radiobutton($label, $name, $value='', $keys, $comment='') {
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td>";

		foreach ((array)$keys as $key => $description) {
			if ($key == $value)
				$checked = "checked";
			else
				$checked = "";
			echo "<input type='radio' id='$name' name='$name' value='" . htmlentities($key, ENT_QUOTES, 'UTF-8') . "' $checked />" . $description . "<br>";
		}
		echo $comment . "<br>";
		echo "</td></tr>";
	}

	/**
	* Options - display option as a checkbox
	*/
	function option_checkbox($label, $name, $value='', $comment='') {
		if ($value)
			$checked = "checked='checked'";
		else
			$checked = "";
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td><input type='hidden' id='$name' name='$name' value='0' /><input type='checkbox' name='$name' value='1' $checked />";
		echo " $comment</td></tr>";
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
@include_once dirname( __FILE__ ) . '/pro/pro.php';
if (class_exists('Mappress_Pro'))
	$mappress = new MappressPro();
else
	$mappress = new Mappress();
?>
