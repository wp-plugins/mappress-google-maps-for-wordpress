=== MapPress Easy Google Maps ===
Contributors: chrisvrichardson
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4339298
Tags: google maps,google,map,maps,easy,poi,mapping,mapper,gps,lat,lon,latitude,longitude,geocoder,geocoding,georss,geo rss,geo,v3,marker,mashup,mash,api,v3,buddypress,mashup,geo,wp-geo,geo mashup,simplemap,simple,wpml
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 2.19

MapPress is the easiest way to create great-looking Google Maps and driving directions in your blog.

== Description ==
MapPress adds an interactive map to the wordpress editing screens.  When editing a post or page just enter any addresses you'd like to map.

The plugin will automatically insert a great-looking interactive map into your blog. Your readers can get directions right in your blog and you can even create custom HTML for the map markers (including pictures, links, etc.)!

For even more features, try the [MapPress Pro Version](http://wpplugins.com/plugin/235/mappress-pro)

* What would you like to see next? [Take the Poll](http://www.wphostreviews.com/mappress).
* For questions and suggestions: [contact me](http://wphostreviews.com/chris-contact) using the web form or email me (chrisvrichardson@gmail.com)

= Key Features =
* MapPress is based on the latest Google maps API v3 - it's fast, optimized for mobile phones - and no API keys are required!
* WordPress 3.0 and MultiSite compatible
* Custom post types are supported
* Easily create maps right in the standard post edit and page edit screens
* Add markers for any address, place or latitude/longitude location, or drag markers where you want them
* Create custom text and HTML for the markers, including photos, links, etc.
* Street view supported
* Readers can get driving, walking and bicycling directions right in your blog.  Directions can be dragged to change waypoints or route
* Multiple maps can be created in a single post or page
* Real-time traffic
* New shortcodes with many parameters: "mapid" (to specify which map to show), "width" "height", "zoom", etc.
* Programming API to develop your own mapping plugins

= Pro Version Features =
* Get the [MapPress Pro Version](http://wpplugins.com/plugin/235/mappress-pro) for additional functionality
* Use different marker icons in your maps - over 200 standard icons included
* Use your own custom icons in your maps or download thousands of icons from the web
* Shortcodes and template tags for "mashups": easily create a "mashup" showing all of your map locations on a single map
* Mashups can automatically link to your blog posts and pages and they can display posts by category, date, tags, etc.
* MapPress widgets: add widgets to your sidebar to show a map or a mashup

[Home Page](http://www.wphostreviews.com/mappress) |
[Documentation](http://www.wphostreviews.com/mappress-documentation-144) |
[FAQ](http://www.wphostreviews.com/mappress-faq) |
[Support](http://www.wphostreviews.com/mappress-faq)

== Screenshots ==
1. Options screen
2. Visual map editor in posts and pages
3. Map displayed in your blog
4. Map directions

= Localization =
Please [Contact me](http://wphostreviews.com/chris-contact) if you'd like to provide a translation or an update.  Special thanks to:

* Spanish - Seymour
* Italian - Gianni D.
* Finnish - Jaska K.
* German - Stefan S. and Stevie
* Dutch	- Wouter K.
* Chinese / Taiwanese - Y.Chen
* Simplified Chinese - Yiwei
* Swedish - Mikael N.
* French - Sylvain C. and Jérôme
* Russian - Alexander C.
* Hungarian - Németh B.

== Upgrade Notice ==
If you're upgrading by copying the files please be sure to DEACTIVATE your old version, copy the files, then ACTIVATE the new version

== Installation ==

See full [installation intructions and Documentation](http://www.wphostreviews.com/mappress-documentation-144)
1. Unzip the files into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress`.  Be sure to put all of the files in this directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Upgrade ==

1. Deactivate your old MapPress version
1. Unzip the files into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress`.  Be sure to put all of the files in this directory.
1. Activate the new version through the 'Plugins' menu in WordPress
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Frequently Asked Questions ==

Please read the **[FAQ](http://www.wphostreviews.com/mappress-faq)**

== Screenshots ==

1. Options screen
2. Visual map editor in posts and pages
3. Edit map markers in the post editor
4. Get directions from any map marker

== Changelog ==
2.18
=
* Same as 2.17.  Trying a re-upload to fix the 404 errors in the wordpress repository

2.17
=
* Fixed: plugin was not reporting database tables correctly when table prefix was in upper case
* Fixed: zoom was wrong for only 1 POI if entered by lat/lng
* Fixed: multisite network activation implemented

2.16
=
* Set marker link color blue (some themes use white links); you can override in mappress.css ".mapp-overlay a"
* (Pro) Fixed: for mashups, WP editor replaced & with &amp; and defaults were not set correctly
* (Pro) Fixed: better title/directions URL handling for mashups & widget if POI was created using lat/lng instead of address

2.15
=
* Enhanced address correction for US/Foreign addresses
* (Pro) Fixed: bugs related to TurboCSV integration
* (Pro) Fixed: "my icons" click events

2.14
=
* Fixed: bug in 2.13 for lat/lng directions broke adding addresses to new maps

2.13
=
* Added: better user icon handling for Pro version

2.12
=
* Added: directions for lat/lng locations.  Just enter lat,lng in the from or to directions input box.
* Plugin version displayed in post/page edit metaboxes
* Simplified marker overlay layout and CSS; should help prevent scrollbars when displaying and editing map
* Added routines for TurboCSV integration

2.11
=
* Plugin version displayed in post/page edit metaboxes

2.10
=
* Fixed: marker body change lost when changing icon (Pro)

2.0.9
=
* Fixed: dragging didn't work until map was save
* Fixed: javascript warning when adding new POI
* Fixed: icon 'back' link didn't work (Pro)
* Fixed: icon reset after canceling icon selection (Pro)

2.0.8
=
* Fixed bug preventing saving some options as unchecked.

2.0.7
=
* You can now specify "center_lat" and "center_lng" in the shortcode to set the map center
* Fixed bug where zoom was not being set if provided in shortcode
* Fixed bug where directions link would not work
* Rewrote meta_key shortcode processing - will be available in Pro version
2.0.6
=
* Workaround added for prototype.js JSON bugs caused by other plugins including prototype library.  Prototype 1.6.1 breaks jQuery width(), height(), and JSON stringify for arrays
* Added additional debug info to find cases where plugin PHP JSON libraries have conflict
* Fixed an error in CSS class .mapp-overlay-links

2.0.4
=
* Added some missing strings for translations
* Added new option to the MapPress 'settings' screen to resize all maps at once.
* Widened lat/lng input
* Added support for WPML language settings (http://wpml.org)
* Converted custom CSS checkbox to an input field
* Settings should no longer be reset on upgrade

2.0.3
=
* Added warning about need to activate new plugin version

2.0.2
=
* Fixed: some PHP versions were giving error T_OBJECT_OPERATOR

2.0.1
=
* Fixed activation error for 2.0
* Added street view support
* Added keyboard shortcuts setting to enable/disable keyboard scrolling & zoom

2.0
=
* MapPress now uses Google maps API v3 - it's faster, optimized for mobile phones - and no more API keys!
* WordPress 3.0 and MultiSite compatible
* Multiple maps in a single post or page
* Custom post types support
* Optimized loading: javascript and CSS are loaded ONLY on pages with a page
* Maps can be generated from custom fields - you can even use [TurboCSV](http://wphostreviews.com/turbocsv) to upload maps from a spreadsheet
* Custom post types are fully supported
* Driving, walking and bicycling directions, and directions can be dragged to change waypoints or route
* Real-time traffic
* New shortcodes with many parameters: "mapid" (to specify which map to show), "width" "height", "zoom", etc.
* Programming API to develop your own mapping plugins
* Marker tooltips
