=== MapPress Easy Google Maps ===
Contributors: chrisvrichardson
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4339298
Tags: google maps,google,map,maps,easy,poi,mapping,mapper,gps,lat,lon,latitude,longitude
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 1.6.1

MapPress is the easiest way to create great-looking Google Maps and driving directions in your blog.

== Description ==

MapPress adds an interactive map to the wordpress editing screens.  When editing a post or page just enter any addresses you'd like to map.

The plugin will automatically insert a great-looking interactive map into your blog. Your readers can get directions right in your blog and you can even create custom HTML for the map markers (including pictures, links, etc.)!

* What features would you like to see next? [Take the Poll](http://www.wphostreviews.com/mappress).
* For questions and suggestions: [contact me](http://wphostreviews.com/chris-contact) using the web form or email me (chrisvrichardson@gmail.com)

= VERSION 1.7 BETA RELEASED =
The best map plugin is getting even better!  Try out the latest beta:
[Download MapPress BETA Now!](http://wphostreviews.com/mappress/mappress-beta)

= New Beta Features =
* MapPress is now based on Google maps API v3.  The new API is many times faster - and no more API keys!
* Driving, walking and bicycling directions
* Directions are draggable - just drag the line to change your route!
* Real-time traffic
* Multiple maps in a single post or page
* A new shortcode with parameters: "mapid" (to specify which map to show), "width" "height", "zoom", etc.
* Custom post types support
* Marker tooltips
* WordPress 3.0 and MultiSite compatible

= Key Features =
* Easily create maps right in the standard post edit and page edit screens
* Add markers for any address, place or latitude/longitude location, or drag markers where you want them
* Create your own custom text and HTML for the markers, including photos, links, etc.
* Your readers can zoom and scroll maps and get driving directions right on your blog
* WYSIWYG map preview during editing
* Edit map markers using full HTML - embed photos, links, etc. into your markers!
* Support for custom post types - include maps even in your own types
* Automatic address correction
* It's fast!  Javascript and CSS are loaded only on pages that have a map

[Home Page](http://www.wphostreviews.com/mappress) |
[Documentation](http://www.wphostreviews.com/mappress-documentation-144) |
[FAQ](http://www.wphostreviews.com/mappress-faq) |
[Support](http://www.wphostreviews.com/mappress-faq)

== Screenshots ==
1. Options screen
2. Visual map editor in posts and pages
3. Edit map markers in the post editor
4. Get directions from any map marker
5. Inline directions are displayed right in your blog

= Localization =
Please [Contact me](http://wphostreviews.com/chris-contact) if you'd like to provide a translation or an update.  Special thanks to:

* Finnish - Jaska K.
* German - Stefan S. and Stevie
* Dutch	- Wouter K.
* Chinese / Taiwanese - Y.Chen
* Simplified Chinese - Yiwei
* Swedish - Mikael N.

== Installation ==

See full [installation intructions and Documentation](http://www.wphostreviews.com/mappress-documentation-144)

1. Unzip into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress.zip`.  Be sure to put all of the files in this directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enter your Google Maps API key and other options using the the 'MapPress' menu - it's right under the standard 'Settings' menu.
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Frequently Asked Questions ==

Please read the **[FAQ](http://www.wphostreviews.com/mappress-faq)**

== Screenshots ==

1. Options screen
2. Visual map editor in posts and pages
3. Edit map markers in the post editor
4. Get directions from any map marker
5. Inline directions are displayed right in your blog

== Changelog ==
1.7.5
=
* Added pro features including multiple icons, mashups and widgets
* Updated language POT file
1.7.4
=
* Fixed bug prevent saving settings - switched options to array because of bug in WP settings API
* Fixed POI list overflow in Firefox
* Sped up load times with minified json2.js
1.7.3
=
* Fixed bug preventing map save during post publish/previews
1.7.2
=
* Map editing is now many times faster (reduced # of ajax calls)
* Directions are now draggable, just drag the directions line to change the route
* HTML is now stripped from marker titles when displayed in tooltips
* Updated zoom functionality
* Option added to suppress tooltips completely
* Traffic button is now highlighted when traffic is "on"
* "get directions" button is default button on the directions form
* You can now remove/include the MapPress metabox from standard posts and pages as well as custom post types

1.7.1
=
* Added traffic button, custom posts, multiple maps per post,
* Removed: the 'default map type' option - you must specify it on each map.  Check your maps after upgrading.
* Removed: the 'address format' option.  Your markers will show corrected addresses by default, but you can change them if needed.
* Google maps API V3 is now being used
* Options screen is now 3.0 and multisite compatible
* API keys are no longer required
* Map data is now stored in database tables (wp_mappress_maps and wp_mappress_posts) rather than post metadata
* All editing operations now use AJAX

1.6.1
=
* Removed the GoogleBar, it's just wasting space on the map.  Tell me if you need this option restored!
* Removed the automatic centering checkbox in post edit screen.  Click the 'center map' button to center instead.
* Added donate links

1.6
=
* Fixed plugin URL retrieval, localization and warnings when running in WP_DEBUG
* Default GoogleBar to off for new installations


1.5 - New Features
=
* SPEED!  Javascript is now compressed and loads ONLY on pages with a map
* You can now edit marker titles and body using full HTML!  Look for a visual editor in the next version...
* Removed 'directions' tabs and went back to links.  This just seems simpler to me, but let me know if you object.
* You can now add markers by lat/lng.  Use the fields to precisely enter the location or, if you want the marker to 'snap' to the nearest street address, then enter the lat/lng in the address field instead, e.g. "-35.03, -32.001"
* Directions link display has been enhanced
* Added option for address correction.  If you choose 'as entered' the addresses will appear just as you enter them.  Choose 'corrected' to display a corrected version from Google.  For example, the corrected version of "1 infinite loop" is "1 Infinite Loop, Cupertino, CA, 95014, USA".  Look for more correction options in the next version...
* Internationalization has been improved and all visible texts should now be available for translation
* Changed 'caption' to 'title'.  If you have CSS assigned to class "mapp-overlay-caption" please change it to "mapp-overlay-title"
* By default, MapPress will zoom your map to show all markers when you save the map.  If you don't want this function, you can set a checkbox to manually set the map center and zoom
* You can set the map type (hybrid, street, etc.) by selecting it in the post-edit or page-edit screen.  Whatever you select is what will be displayed. * Markers are now draggable during editing
* Option added to force map language - this is useful if, for example, your blog is in Spanish but many your readers have their browsers defaulted to English.  Set the option to force Google to display all map controls in that language.
* Option added to turn mouse wheel scrolling on/off
* We now have WYSIWYG map preview during post editing - map shows exactly as it'll appear in your blog
* When requesting directions, MapPress will replace invalid directions with the nearest match if it's an obvious match.  For example "1 infnte loop, coopertino" will be replaed with "1 Infinite Loop, Cupertino, CA".
* For less obvious matches, MapPress will provide "did you mean: " links.  For example, entering "ab" will result in a link "did you mean: Alberta, Canada""
1.5.8.x
=
* Bug fixes that prevented map display!
* Added additional debugging information
* Fixed: bug where json_encode fails in older versions of php
1.5.1 - 1.5.7
=
* Fixed: bug where foreign characters, accents or single quotes could prevent map display
* Fixed: when editing an infowindow after editing the page/post text a message "are you sure you want to navigate away..." would appear
* Fixed: when editing an infowindow in IE8 the cursor position jumped around (this is actually an IE8 bug, but I've implemented a workaround)
* Fixed: CSS issues for Firefox/IE7 display
1.4.2
=
* Additional fixes to support PHP 4

1.4.1
=
* Added internationalization; language files are in the 'languages' directory

1.4
=
* Added PHP 4 support
* New minimap in post edit

1.3.2
=
* Easy entry of multiple addresses
* Multiple marker icons
* Address checking and correction
* Edit maps without changing shortcodes
* High-speed geocoding with 500% faster map save and display

1.2.4
=
* Added GoogleBar feature
* Improved CSS to prevent issues with some custom themes

1.2.2 (2009-04-03) and 1.2.3
=
* Added JSON library for PHP4
* Fixed naming error when plugin extracted to wrong directory

1.2.1 (2009-04-02)
=
* removed '%s' text from options screen
* enhanced check to suppress mappress javascript on other admin pages
* fixed multiple messages when api key invalid

1.2 (2009-04-01)
=
* Added support for multiple markers
* Easier driving directions

1.1 (2009-03-15)
=
* Several bug fixes; adjusted map zoom

1.0 (2009-02-01)
=
* Initial version
