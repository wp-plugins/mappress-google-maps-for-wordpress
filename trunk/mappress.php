<?php                                                                                                                                                
/*
Plugin Name: MapPress Easy Google Maps
Plugin URI: http://www.wphostreviews.com/mappress
Author URI: http://www.wphostreviews.com/mappress
Description: MapPress makes it easy to insert Google Maps in WordPress posts and pages.
Version: 1.2
Author: Chris Richardson
*/

/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Thsi program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// ----------------------------------------------------------------------------------
// Class mappress - plugin class
// ----------------------------------------------------------------------------------
class mappress {
    var $plugin_name = "MapPress";
    var $prefix = 'mappress';
    var $version = '1.2';
    var $help_link = 'http://www.wphostreviews.com/mappress';
    var $map_options = array ('api_key'=>'', 'country'=>'US', 'width'=>400, 'height'=>300, 'zoom'=>15, 'defaultui'=>1, 'directions'=>1, 'tabbed'=>1);
    var $plugin_options = array('no_help'=>0);    
    var $debug = false;
    var $div_num = 0;    // Current map <div>
    
    function mappress()  {        
        global $wpdb, $wp_version;
        
        // Define constants for pre-2.6 compatibility
        if ( ! defined( 'WP_CONTENT_URL' ) )
              define( 'WP_CONTENT_URL', $this->get_array_option( 'siteurl' ) . '/wp-content' );
        if ( ! defined( 'WP_CONTENT_DIR' ) )
              define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
        if ( ! defined( 'WP_PLUGIN_URL' ) )
              define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins/' );
        if ( ! defined( 'WP_PLUGIN_DIR' ) )
              define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
            
        // Localization 
        if( version_compare( $wp_version, '2.7', '>=') ) 
            load_plugin_textdomain($this->prefix, false, $this->prefix);    
        else
            load_plugin_textdomain($this->prefix, "wp-content/plugins/$this->prefix");        

        $result = $this->get_array_option('help_link');
        if (!$result)
            $this->update_array_option('help_link', $this->help_link);
        else
            $this->help_link = $result;
    
        // Notices
        add_action('admin_notices', array(&$this, 'hook_admin_notices'), 5);

        // Add admin menu     
        add_action('admin_menu', array(&$this, 'hook_admin_menu'));
                
        // Scripts and stylesheet
        add_action('init', array(&$this, 'hook_init'));
        add_action('admin_init', array(&$this, 'hook_admin_init'));        
        
        // Use '?mapp_debug=y' to turn on debug mode
        if (isset($_GET['mapp_debug'])) 
            $this->debug = true;

        // Shortcode processing
        add_shortcode($this->prefix, array(&$this, 'map_shortcodes'));        
        
        // Admin form to insert shortcodes
        add_action('admin_menu', array(&$this, 'hook_admin_menu'));        
    }

    /**
    * Add admin menu and admin scripts/stylesheets
    * 
    */
    function hook_admin_menu() {
        // Add menu
        $mypage = add_submenu_page('plugins.php', $this->plugin_name, $this->plugin_name, 8, __FILE__, array(&$this, 'admin_menu'));       
        
        // Add scripts / styles specific only to OUR plugin
        add_action("admin_print_scripts-$mypage", array(&$this, 'hook_admin_print_scripts'));        
        add_action("admin_print_styles-$mypage", array(&$this, 'hook_admin_print_styles'));
        
        // Post edit shortcode boxes
        add_meta_box($this->prefix, $this->plugin_name, array(&$this, 'insert_shortcodes'), 'post', 'normal', 'high');
        add_meta_box($this->prefix, $this->plugin_name, array($this, 'insert_shortcodes'), 'page', 'normal', 'high');        
    }
    
    /**
    * Scripts and stylesheets for content pages
    */
    function hook_init() {
        // Suppress maps in feeds and admin pages
        if (is_feed() || is_admin())
            return;
            
        $key = $this->get_array_option('api_key');
        if (!empty($key))
            wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&amp;v=2&amp;key=$key");            
        wp_enqueue_script($this->prefix, $this->plugin_url($this->prefix . '.js'), FALSE, $this->version);
        wp_localize_script($this->prefix, $this->prefix . 'l10n', array('key' => $this->get_array_option('api_key')) );    
        
        // Stylesheet
        if(function_exists('wp_enqueue_style'))
            wp_enqueue_style($this->prefix, $this->plugin_url("$this->prefix.css"), FALSE, $this->version);  
        else 
            add_action('wp_head', array(&$this, 'hook_head')); 
    }
    
    /**
    * Scripts & stylesheets for ALL admin pages
    * 
    */
    function hook_admin_init() {
        // Scripts
        wp_enqueue_script('mappadmin', $this->plugin_url($this->prefix . '_admin.js'), FALSE, $this->version);                

        // Stylesheets
        if(function_exists('wp_enqueue_style'))
            wp_enqueue_style($this->prefix, $this->plugin_url("$this->prefix.css"), FALSE, $this->version);  
        else 
            add_action('wp_head', array(&$this, 'hook_head'));                     
    }

    /**
    * Scripts only for our admin pages
    * 
    */
    function hook_admin_print_scripts() {
        // We need maps API to validate the key on options page
        $key = $this->get_array_option('api_key');
        if (empty($key))
            $key = $_POST['api_key'];   // Might be just saving it now
        if (!empty($key))
            wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&amp;v=2&amp;key=$key");
    }    
    
    /** 
    * Stylesheets only for our admin pages
    * 
    */
    function hook_admin_print_styles() {
    }
    
    /**
    * Stylesheets for older (pre 2.7) versions
    */
    function hook_head() {
        $url = $this->plugin_url("$this->prefix.css");
        echo "<link rel='stylesheet' href='$url' type='text/css' media='screen' />\n";  
    }

    function hook_admin_notices() {
        // If API key isn't entered yet, gripe about it 
        $api_key = $this->get_array_option('api_key');

        if (empty($api_key) || !isset($api_key))
            $api_key = $_POST['api_key'];

        if (empty($api_key))
            echo "<div id='error' class='error'><p>$this->plugin_name isn't ready yet.  Please enter your Google Maps API Key on the MapPress options screen.</p></div>"    ;
            
        $this->get_help();            
    }
                               
    /**
    * Draw a map for all custom fields in a post, or multiple posts
    *       
    * @param mixed $id - a single post ID or an array of IDs
    * @param mixed $args - function arguments
    */
    function map_post($id='', $args = '') {
        global $post;

        if (empty($id))
            $id = $post->ID;            
                    
        // If we have a single post, convert it to an array
        if (!is_array($id))
            $id = array($id);
        
        // Loop through post array and read the POIs from custom fields
        foreach ($id as $key=>$current_id)
            $addresses = get_post_meta($current_id, 'address', false);
        
        $output = $this->map_address($args, $addresses);
        return $output;
    }

    /**
    * Draw a map for all the shortcodes in a single post
    * 
    * @param mixed $atts - shortcode attributes
    */
    function map_shortcodes($atts) {
        // Pull out addresses into an array
        if (!empty($atts['address']))
            $addresses[0] = $atts['address'];
            
        for ($i=1; $i<=20; $i++) {
            if (!empty($atts["address$i"]))
                $addresses[$i] = $atts["address$i"];            
        }
        
        // Get the map HTML
        $output = $this->map_address($atts, $addresses);
        return $output;
    }    

    /**
    * Map a single address
    * 
    * @param mixed $args - arguments
    * @param mixed $addresses - array of addresses to map
    */
    function map_address($args, $addresses) {
        
        // Get the defaults for any missing options
        $defaults = $this->map_options;
        foreach ($defaults as $key=>$value) {
            $option = $this->get_array_option($key);
            if ($option !== false)
                $defaults[$key] = $this->get_array_option($key);
        }

        // Now merge the passed arguments over the defaults
        $args = shortcode_atts($defaults, $args);        
        
        // Create a map object
        $map = new mpmap($args); 
        $map->poweredby = "<div class='mapp-poweredby-div'>Map powered by <a href='$this->help_link'>$this->plugin_name</a></div>";        
                
        // Parse the 'address' arguments
        if (!empty($addresses)) {
            foreach ($addresses as $address) {
            
            // Split address by ":".  We expect: {street address:comment:lat:lng}
            $result = preg_split("/[:]+/", $address);
            $street_addr = $result[0];
            $comment = $result[1];
            $lat = $result[2];
            $lng = $result[3];
            $map->add_poi($street_addr, $comment, $lat, $lng);
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
            return plugins_url("$this->prefix/$path");
        else
            return WP_PLUGIN_URL . "$this->prefix/$path";
    }
    
    /**
    * Get option value.  Options are stored under a single key
    */
    function get_array_option($option) {
        $options = get_option($this->prefix);        
        return $options[$option];         
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
    * Get help link
    * 
    */
    function get_help() {
        global $wp_version, $title;
         
        if ($this->get_array_option('no_help'))
            return;
        else {
            if (stristr($title, 'plugin') || stristr($title, $this->prefix))        
            echo ($this->get_array_option('help_msg'));
        }
            
        // Check for help max once/day
        $result = $this->get_array_option('help_check');
        $today = date('Ymd', time());
        if ($result == $today)
            return;
        else
            $this->update_array_option('help_check', $today);
                             
        $host = 'wphostreviews.com';  
        $path = '/help.php';  
        $port = 80;
        $request = 'plugin=' . urlencode($this->prefix) . '&plugin_version=' . urlencode($this->version) . '&check=yes&src=' . urlencode(get_option('home')) . '&version=' . urlencode($wp_version);         
        $http = "POST $path HTTP/1.0\r\n";
        $http .= "Host: $host\r\n";
        $http .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
        $http .= "Content-Length: " . strlen($request) . "\r\n";
        $http .= "User-Agent: WordPress/$wp_version | $this->plugin_name" . '/' . $this->version . "\r\n";
        $http .= "\r\n";
        $http .= $request;

        $fp = @fsockopen($host, $port, $errno, $errstr, 3);  // Timeout = 3 seconds
        if( $fp === false) {
            if ($this->debug == true) 
                echo "<div id='error' class='error'><p>Internal error in $this->plugin_name: errno = $errno  string = $errstr </p></div>";             
            return;
        }
        
        fwrite($fp, $http);
        stream_set_timeout($fp, 2);         
        $info = stream_get_meta_data($fp);  
              
        while ( !feof($fp) && (!$info['timed out'])) {
            $response .= fgets($fp, 1160); 
            $info = stream_get_meta_data($fp);        
        }        
        fclose($fp);
        // Headers in response[0], body in response[1]
        $response = explode("\r\n\r\n", $response, 2); 

        if (!is_array($response) || count($response) < 2 || empty($response[1])) {
            if ($this->debug)
                echo "<div id='error' class='error'><p>Invalid response: " . var_export($response, true) . "</p></div>";
            return;
        }           

        $args= explode("\r\n", $response[1], 2);
        if ($args[0] == 'invalid request') {
            $this->update_array_option('help_msg', '');
            return;
        }
            
        if (!empty($args[0])) 
            $this->update_array_option('help_link', $args[0]);

        if (!empty($args[1]))
            $this->update_array_option('help_msg', "<div id='error' class='error'><p>" . $args[1] . "</p></div>");
    }                        

    /**
    * Options page
    *     
    */
    function admin_menu() {
        $defaults = array_merge($this->map_options, $this->plugin_options);
    
        if ( !current_user_can('manage_options') ) 
            die ( __( "ACCESS DENIED: You don't have permission to do this.", $this->plugin_name) );

	    if (isset($_POST['save'])) {    
            check_admin_referer($this->prefix);
            foreach($defaults as $key=>$default) {
                $new_value = strip_tags(mysql_real_escape_string ($_POST[$key]));
                $this->update_array_option($key, $new_value);
            }                
            $message = __('Settings saved', $this->prefix);                        
	    }
        
        // Get api key
        $api_key = $this->get_array_option('api_key');
        
        // Set default values if options aren't set
        foreach($defaults as $key=>$default) {
            $current_value = $this->get_array_option($key);
            if (empty($current_value) && $current_value != "0")
                $this->update_array_option($key, $default);
        }
        
        $google_api_link = "http://code.google.com/apis/maps/signup.html";
        $api_missing = __('Please enter your API key.  Need an API key?  Get one %s', $this->prefix) . '<a target="_blank" href="' . $google_api_link . '">' . __('here', $this->prefix);        
        $api_incompatible = __("MapPress could not load google maps.  Either your browser is incompatible or your API key is invalid.  Need an API key?  Get one ", $this->prefix)
                            . '<a target="_blank" href="' . $google_api_link . '">' . __('here', $this->prefix);
        $cctld_link = __('Google uses country codes called "ccTLDs" as a hint when finding addresses. For example, the USA is "US". ', $this->prefix)                                                                                                      
                    . '<br>(<a target="_blank" href="http://en.wikipedia.org/wiki/CcTLD#List_of_ccTLDs">' . __("what's my country code?", $this->prefix) . '</a>)';
        $customfield_link = "<a target='_blank' href='$this->help_link'>" . __('custom field', $this->prefix) . '</a>';
        $shortcode_link = "<a target='_blank' href='$this->help_link'>" . __('shortcodes', $this->prefix) . '</a>';
        $help_link = "<a target='_blank' href='$this->help_link'>" . $this->plugin_name . __(' help', $this->prefix) . '</a>';                                      
        $help_msg = $this->get_array_option('help_msg');
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>        
            <h2><?php echo $this->plugin_name . ' Options' ?></h2>
            <?php $this->show_messages($message, $error); ?>            
            <div><?php echo $help_link ?></div>
            <h3><?php _e('Map defaults', $this->prefix);?></h3>

            <form method="post" action="">                  
                <?php wp_nonce_field($this->prefix); ?>
                
                <table class="form-table">    
                    <tr valign='top'>
                        <th scope='row'><?php _e('Google Maps API key', $this->prefix) ?></th>
                        <td id='api_block'><input type='text' id='api_key' name='api_key' size='100' value='<?php echo $this->get_array_option('api_key'); ?>'/>
                        <p id='api_message'></p>
                        </td>
                    </td>                        
                    <script type='text/javascript'>
                        mappCheckAPI(<?php echo "'$api_missing', '$api_incompatible'" ?>)
                    </script>
                </table>
                
                <table class="form-table">                                    
                    <?php $this->option_string(__('Default country code', $this->prefix), 'country', '', 2, $cctld_link); ?>                
                    <?php $this->option_string(__('Map width', $this->prefix), 'width', '', 2, __('Enter a value in pixels (default is 400)', $this->prefix)); ?>
                    <?php $this->option_string(__('Map height', $this->prefix), 'height', '', 2, __('Enter a value in pixels (default is 300)', $this->prefix)); ?>
                    <?php $this->option_string(__('Map zoom (1-20)', $this->prefix), 'zoom', '', 2, __('1=fully zoomed out, 20=fully zoomed in (default is 15)', $this->prefix)); ?>
                    <?php $this->option_checkbox(__('Snazzy map controls?', $this->prefix), 'defaultui', '', __('Check this box to use the snazzy (but cluttered) Google map controls.  Uncheck it for a simple map with only pan/zoom controls', $this->prefix)); ?>
                    <?php $this->option_checkbox(__('Tabbed directions?', $this->prefix), 'tabbed', '', __('Check this box to have the address and directions appear in separate tabs.  Try it out to see what you like best.', $this->prefix)); ?>                    
                    <?php if(!empty($help_msg)) $this->option_checkbox(__('No messages?', $this->prefix), 'no_help'); ?>                    
                </table>                    
                <p class="submit"><input type="submit" class="submit" name="save" value="<?php _e('Save Changes', $this->prefix) ?>"></p>
            </form>
        </div>
        <p><small>&copy; 2009, <a href="<?php echo $this->help_link?>">C. Richardson</a></small></p>
    </div>	    
    <?php        
    }


    /**
    * Shortcode form for post edit screen
    * 
    */
    function insert_shortcodes() {                
?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Map options:', $this->prefix) ?>
                    <br /><?php _e('(leave blank for default)', $this->prefix)?>
                </th>
                <td>
                    <input type="text" size="2" name="mapp_width" id="mapp_width" value=""/> <?php _e('Width ', $this->prefix)?>
                    <?php echo '(' . $this->get_array_option('width') . ')' ?>
                    <br />
                    <input type="text" size="2" name="mapp_height" value="" id="mapp_height" /> <?php _e('Height ', $this->prefix)?>
                    <?php echo '(' . $this->get_array_option('height') . ')' ?>                    
                    <br />
                    <input type="text" size="2" name="mapp_zoom" id="mapp_zoom" value=""/> <?php _e('Zoom (1-20) ', $this->prefix)?>
                    <?php echo '(' . $this->get_array_option('zoom') . ')' ?>                    
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Address:', $this->prefix)?></th>
                <td>
                    <input type="text" size="40" name="mapp_address" id="mapp_address" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Comment:')?></th>
                <td>
                    <input type="text" size="40" name="mapp_comment" id="mapp_comment"/>
                    <br />(displays above the address in the map marker)
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="button" onclick="return mappClearShortCode(this.form);" value="<?php _e('Clear'); ?>" />        
            <input type="button" onclick="return mappInsertShortCode(this.form);" value="<?php _e('Insert map in editor &raquo;'); ?>" />
        </p>
        <p id="mapp_message">&nbsp;</p>
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
    function option_string($label, $name, $value='', $size = 90, $comment='', $class='') {
        if (empty($value))
            $value = $this->get_array_option($name);    
        
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
        if (empty($value))
            $value = $this->get_array_option($name);    

        echo "<tr valign='top'><th scope='row'>" . $label . "</th>";    
        echo "<td>";
                
        foreach ($keys as $key => $description) {
            if ($key == $value) 
                $checked = "checked";
            else
                $checked = "";               
            echo "<input type='radio' id='$name' name='$name' value='" . htmlentities($key) . "' $checked />" . $description . "<br>";            
        }
        echo $comment . "<br>";
        echo "</td></tr>"; 
    }

    /**
    * Options - display option as a checkbox
    */
    function option_checkbox($label, $name, $value='', $comment='') {
        if (empty($value))
            $value = $this->get_array_option($name);            
        if ($value)
            $checked = "checked";
        echo "<tr valign='top'><th scope='row'>" . $label . "</th>";    
        echo "<td><input type='hidden' id='$name' name='$name' value='0' /><input type='checkbox' name='$name' value='1' $checked />";
        echo " $comment</td></tr>";          
    }
    
}  // End plugin class 


// -----------------------------------------------------------------------------------
// Class mpmap - google API interface class
// -----------------------------------------------------------------------------------
class mpmap {
    var $api_key,
        $country,           // Top-level country ccTLD code, used a geocoding hint
        $pois = array(),    // Each poi is an array {lat, lng, address, comment}
        $width = 0,
        $height = 0,
        $zoom = 0,
        $defaultui = 0,
        $poweredby = "",
        $directions = 0,
        $tabbed = 0;

    function mpmap($args) {        
        $this->api_key = $args['api_key'];
        $this->country = $args['country'];
        $this->width = (int)$args['width'];
        $this->height = (int)$args['height'];
        $this->zoom = (int)$args['zoom'];
        if (!empty($args['defaultui']))
            $this->defaultui = $args['defaultui'];
        if (!empty($args['directions']))
            $this->directions = $args['directions'];
        if (!empty($args['tabbed']))
            $this->tabbed = $args['tabbed'];
    }

    /**
    * Draw current map
    * 
    * @param mixed $map_name
    */
    function draw($map_name) {
        // Geocode the pois if it hasn't been done yet
        $this->geocode();
        
        // Return javascript to output the map
        $map .= "\r\n";
        $map .= "<div id='$map_name' class='mapp-div' style='width:{$this->width}px;height:{$this->height}px;'></div>";                    
        $map .= "\r\n";
        $map .= "<script type='text/javascript'>\r\n";
        $map .= "   pois = new Array();\r\n";
        
        // Display POIs
        foreach($this->pois as $poi) {
            $address = htmlentities($poi['address']);
            $lat = $poi['lat'];
            $lng = $poi['lng'];
            $comment = htmlentities($poi['comment']);            
            $map .= "   p=new Object(); p.address=\"$address\"; p.lat=\"$lat\"; p.lng=\"$lng\"; p.comment=\"$comment\"; pois.push(p);\r\n";
        }

        $map .= "   var $map_name = new mapp('$map_name', pois, $this->zoom, $this->defaultui, $this->directions, $this->tabbed)\r\n";
        $map .= "</script>\r\n";

        // Add powered by message
        $map .= $this->poweredby;
        return $map;   
    }
        
    /** 
    * Add a poi
    * 
    * @param mixed $address
    * @param mixed $comment
    * @param mixed $lat
    * @param mixed $lng
    */
    function add_poi($address='', $comment='', $lat='', $lng='') {
        
        // If we don't have either an address or a lat+lng, give up
        if (empty($address) && (empty($lat) || empty($lng)))
            return FALSE;
            
        // If no comment provided, default it to address if the poi is already geocoded
        if (empty($comment) && !empty($lat) && !empty($lng))
            $comment = $address;
        
        // If we had lat+lng, use it.  Otherwise, use the address (we'll geocode it later)
        if (!empty($lat) && !empty($lng))                         
            $poi = array('lat'=>$lat, 'lng'=>$lng, 'address'=>'', 'comment'=>$comment);
        else
            $poi = array('lat'=>0, 'lng'=>0, 'address'=>$address, 'comment'=>$comment);
        
        // Add poi to our collection
        $this->pois[] = $poi;
    }
    
    /**
    * Remove a poi
    * 
    * @param mixed $lat
    * @param mixed $lng
    */
    function remove_point($lat, $lng) {
        foreach ($this->$pois as $key=>$poi)
            if ($poi['lat'] == $lat && $poi['lng'] == $lng)
                array_splice($this->pois, $key, 1);
    }
                
    /**
    * Remove a poi by address
    * 
    * @param mixed $address
    */
    function remove_address($address) {
        foreach ($this->$pois as $key=>$poi)
            if ($poi['address'] == $address)
                array_splice($this->pois, $key, 1);        
    }

    /**
    * Geocodes all the pois
    */  
    function geocode() {                        
        // Loop thorugh the pois.  
        foreach($this->pois as $key=>$poi) {
            if (!empty($poi['lat']) && !empty($poi['lng']))
                continue;
            
            $address = urlencode($poi['address']);
            $url = "http://maps.google.com/maps/geo?q=$address&output=json&oe=utf8&sensor=true&key=$this->api_key";                        
            $geocode = fopen($url,"r");
            if ($geocode === FALSE)
                return FALSE;
        
            unset($geocode_data);
            while (($line = fgets($geocode, 2000)) !== FALSE) {
                $geocode_data .= $line;
            }
            fclose ($geocode);
            
            // Decode the result
            $result = json_decode($geocode_data);
            
            // Use first address possibility
            $placemark = $result->Placemark[0];
                                    
            // Set POI lat/lng
            $poi['lat'] = $placemark->Point->coordinates[1];           
            $poi['lng'] = $placemark->Point->coordinates[0];
                                      
            // Update the poi
            $this->pois[$key] = $poi;
        }        
    }    
} // End class mpmap

// Create new instance of the plugin
$mappress = new mappress();
?>