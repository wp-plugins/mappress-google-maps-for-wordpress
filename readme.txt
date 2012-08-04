=== MapPress Easy Google Maps ===
Contributors: chrisvrichardson
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4339298
Tags: google maps,google,map,maps,easy,poi,mapping,mapper,gps,lat,lon,latitude,longitude,geocoder,geocoding,georss,geo rss,geo,v3,marker,mashup,mash,api,v3,buddypress,mashup,geo,wp-geo,geo mashup,simplemap,simple,wpml
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 2.38

MapPress is the most popular and easiest way to create great-looking Google Maps and driving directions in your blog.

== Description ==
MapPress adds an interactive map to the wordpress editing screens.  When editing a post or page just enter any addresses you'd like to map.

The plugin will automatically insert a great-looking interactive map into your blog. Your readers can get directions right in your blog and you can even create custom HTML for the map markers (including pictures, links, etc.)!

For even more features, try the [MapPress Pro Version](http://wphostreviews.com/mappress)

= News =
* The new beta versions (2.38.1 and higher) have dozens of new features like KML support, shape drawing, geoLocation capability and much more.  [Learn more](http://wphostreviews.com/category/news)

= Key Features =
* MapPress is based on the latest Google maps API v3 - it's fast, optimized for mobile phones - and no API keys are required!
* WordPress MultiSite compatible
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
* Get the [MapPress Pro Version](http://wphostreviews.com/mappress) for additional functionality
* Use different marker icons in your maps - over 200 standard icons included
* Use your own custom icons in your maps or download thousands of icons from the web
* Shortcodes and template tags for "mashups": easily create a "mashup" showing all of your map locations on a single map
* Mashups can automatically link to your blog posts and pages and they can display posts by category, date, tags, etc.
* MapPress widgets: add widgets to your sidebar to show a map or a mashup
* Display a clickable list of mapped icons and locations right under the map
* Remove the 'powered by' link

[Home Page](http://www.wphostreviews.com/mappress) |
[Documentation](http://www.wphostreviews.com/mappress-documentation) |
[FAQ](http://www.wphostreviews.com/mappress-faq) |
[Support](http://www.wphostreviews.com/mappress-faq)

== Screenshots ==
1. Options screen
2. More options
3. Visual map editor in WordPress post editor
4. Mashup shortcode in a post
5. Mashup in your blog
6. Street view of mashup location

= Localization =
Please [Contact me](http://wphostreviews.com/chris-contact) if you'd like to provide a translation or an update.  Special thanks to:

* Spanish - Seymour
* Italian - Gianni D.
* Finnish - Jaakko K.
* German - Stefan S. and Stevie
* Dutch	- Wouter K., Age
* Chinese / Taiwanese - Y.Chen
* Simplified Chinese - Yiwei
* Swedish - Mikael N.
* French - Sylvain C. and Jérôme
* Russian - Alexander C.
* Hungarian - Németh B.

== Installation ==

See full [installation intructions and Documentation](http://www.wphostreviews.com/mappress-documentation)
1. Unzip the files into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress`.  Be sure to put all of the files in this directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Upgrade ==

1. Deactivate your old MapPress version
1. Unzip the files into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress`.  Be sure to put all of the files in this directory.
1. Activate the new version through the 'Plugins' menu in WordPress
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Upgrade Notice 2.38.1 and Higher ==
1. When the plugin is activated it will create a directory in the WordPress uploads directory for custom icons.  You must move any custom icons to this directory:
`/wp-content/uploads/mappress/icons`

1. The map controls settings have been expanded and changed; please check your settings and verify that your shortcodes are using the new control names!
1. The map infoWindow (bubble) HTML and CSS has been simplified.  If you are using custom CSS please check the mappress.css file for the new settings.


== Frequently Asked Questions ==

Please read the **[FAQ](http://www.wphostreviews.com/mappress-faq)**

== Screenshots ==

1. Options screen
2. Visual map editor in posts and pages
3. Edit map markers in the post editor
4. Get directions from any map marker

== Changelog ==

2.38.5 beta
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: For mashups, the POI title was displaying instead of the post title in tooltips and the POI list
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: added !important modifier to CSS padding for post thumbnails
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: custom icons were flickering for a moment when clicked
* Fixed: when KML overlay markers were clicked the infoWindow was opening in the KML center, not on the clicked marker
* Fixed: the setting to turn tooltips on/off was not working



2.38.4 beta
* Added: an setting is now available to switch off the default CSS (mappress.css) completely (for example, if you plan to style everything in style.css instead)
* Changed: removed !important modifier from most of the CSS styles (some themes use very selective CSS, which causes rendering problems)
* Changed: the plugin will now search the theme and child theme directories for a 'mappress.css'.  If found, it will be loaded *after* the default mappress.css
* Fixed: some language texts were missing from the .po, they've been added
* Fixed: there was a bug in the shortcodes when using the syntax 'center="lat,lng"', this has been fixed.

2.38.3 beta
=
* Fixed: lat/lng pois were saving with a 1-pixel viewport (should be null)
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: some mashup queries were not working properly
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: maps based on custom fields were not generating correctly
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: queries now default to showing ALL matching posts (posts_per_page=-1) so it's no longer necessary to include "posts_per_page" in the query
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: if no map types are selected in the settings, ALL types are displayed (including custom styles)


2.38.2 beta
=
* Added: editor now supports polygons, polylines, circles and squares
* Added: geolocation is supported in the map editor and directions forms.  Click 'My location'.
* Added: POIs in the editor can now be sorted: just click and drag them to rearrange them.
* Added: in the editor, click on the map to see lat/lng below the map
* Added: Google '45-degree imagery' is now available for major cities.  By default it is on, but you can use parameter 'tilt' to set it off (tilt=0) or to change the angle: [mappress tilt="0"]
* Added: 'minzoom' and 'maxzoom' parameters (integer 0-15) - restricts map min/max zoom
* Added: maplink setting and shortcode parameter to provide links above the map
* Added: poilink setting and shortcode parameter to provide links in each poi
* Added: both qTranslate and WPML are supported for map controls and directions
* Added: a new "mapLinks" option adds links above the map.  [mappress mapLinks="center"] : add a 'center map' link, [mappress mapLinks="bigger"] : add a 'bigger map' link
* Added: a new "hidden" option initially hides the map and instead shows a 'show map' link
* Added: a new "poiLinks" option to specify the links for pois: use [mappress poiLinks="zoom"] for a 'zoom' link, "directions_to" and "directions_from" for directions
* Changed: map option "initialOpenOverviewMap" is now "overviewMapControlOpened". Set true to initially open the overview map control.
* Changed: the CSS class for pois has been changed from "mapp-overlay" to "mapp-iw", please update any custom styles
* Changed: the map border is now extended to the poi list and directions by default; to change back to the original behavior (just the map), change the "map_layout.php" template file
* Fixed: the directions form now shows a spinner while it's retrieving directions
* Fixed: the directions form should now correctly get the focus when opened (except when the "initialopendirections" parameter is used)

2.38.2 [MapPress Pro](http://wphostreviews.com/mappress) beta
=
* Added: templates are now available to change the map layout, poi list, and poi contents
* Added: a new dataTable option displays the poi list as a dataTable; use [mappress dataTable="true"] or specify an array of settings using PHP
* Added: mashup pois now display featured image thumbnails by default.  Use 'thumbs="false"' to turn this off.
* Added: parameter 'thumbSize' can be used to specify a size name, or 'thumbWidth' and 'thumbHeight' to force a thumbnail size
* Added: custom map styles are now supported in the settings screen
* Added: a setting and parameter 'style' are available to set the default maps to use a custom style
* Added: a setting and parameter 'mapTypeIds' are used to define which map types (including custom styles) are available
* Changed: mashup parameters 'marker_title' and 'marker_body' have been removed.  Use 'marker_link' instead (see below).
* Changed: if 'marker_link="true"' (the default) each poi will display the underlying post's thumbnail, excerpt and permalink.  If "false" the poi title and body will display as saved in the poi.
* Changed: widget parameters for 'marker_title' and 'marker_body' have been removed.
* Changed: mashup parameter 'show' and 'show_query' are deprecated.  Use 'query' instead.
* Changed: use parameter 'query' for mashup queries.  Specify an actual query or use "current" for current posts or "all" for all posts with maps.  For example: [mashup query="all"]
* Changed: parameter 'show_query' is now just 'query'.  Provide a query or leave it blank to map the currently displayed blog posts.
* Changed: parameter "map=true" is appended to all mashup queries, which optimizes the results to include only posts with maps


2.38.1 beta
=
* Added: bike routes control to highlight bike routes on the map
* Added: scale control to show distance on the map
* Added: new settings to precisely define which standard map controls should be displayed
* Added: directions units setting (metric or imperial)
* [MapPress Pro](http://wphostreviews.com/mappress) Added: default icon setting
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: a new directory is now used for custom icons `/wp-content/uploads/mappress/icons`
* Changed: the map name attribute 'mapName' is now just name, for example: [mappress name='mymap']
* Changed: all calls to Google now use https for compatibility with security-enabled blogs
* Changed: scripts are now loaded in the footer for better compatibility with other plugins
* Changed: map controls now better match google styling
* Fixed: maps should now render properly in jQuery-based tab controls.  See the FAQ for tips on how to fix non-jQuery tabs.

2.38
=
* Added: new options for borders and drop-shadow on map
* Changed: MapPress now requires WordPress 3.2 or higher (3.2 uses modern PHP and MySQL versions)
* Fixed: the wrong URL was provided for updating MapPress
* Fixed: screen was jumping to top when selecting custom icon
* Fixed: clicking the 'save' map button while editing a marker now saves the marker edits before the map is saved
* Fixed: search results were incorrect when querying multiple categories for mashups

2.37
=
* Removed: the "did you mean...?" address prompt.  Google changed the geocoder again and it is now returning multiple results even for a corrected input address.
* Removed: the "custom CSS" options was redundant and confusing to many users.  If you have custom CSS settings, please move them to your theme's style.css file instead.
* Added: border settings in options screen
* Added: clicking 'enter' in the map editor now correctly adds a location rather than publishing the post/page
* Changed: if you choose the option to link mashup title with the source post, clicking the title in the marker list will go directly to the post (previously, it opened that marker on the map)
* Changed: the container div is now sized to exactly the size of the map <div> (previously it was the default, usually 100% width)
* Changed: the cursor no longer jumps to the directions panel when it's opened - this was causing annoying scrolling on some pages
* Fixed: when setting the map to open the first marker, initially the map wasn't centering correctly
* Fixed: HTML error in settings screen for the map sizes
* Fixed: loading spinner centered

2.36
=
* Fixed: Google changed the geocoding response format on 7/27, which caused an error when adding locations in MapPress.

2.35
=
* Fixed: 'headers already sent' error (conflict with Relevanssi plugin).  If you are still receiving this error please [contact me](http://wphostreviews.com/chris-contact)

2.34
=
* Fixed: adjusted CSS to prevent scrollbars in marker InfoWindows ("bubbles")
* Fixed: unwanted highlighting in some themes when selecting from marker list


2.33
=
* Fixed: a bug in mappress.css CSS file was preventing the map from centering in some themes
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: icon picker was broken by a bug in 2.32


2.32
=
* Added: you can now enter lat/lng locations directly in the 'location' text field.  Use the format "title@lat,lng".  For example: Washington@38.902255,-77.036819
* Added: you can now set a "directions server".  If you have set directions to open from Google you can open a regional Google server (i.e. German server for Germany, etc.)
* Added: 45-degree angle viewing will soon become the default for satellite view (learn more here: http://goo.gl/y26D7).
* Added: overview map control for widgets, shortcodes and settings.  See the MapPress documentation or learn more here: http://goo.gl/y26D7)
* [MapPress Pro](http://wphostreviews.com/mappress) Added: new widget options, including map zoom, traffic and map type
* Added: new settings screen layout
* Added: you can specify up to 3 default map sizes instead of the default small/medium/large (in case you have custom sizes you want to use)
* Added: RSS and news widget on settings screen
* Added: on the settings screen you can now resize from and to specific sizes
* Added: when a marker is clicked on the map or marker list, it is now brought in 'front' of all other markers
* Added: new filters for your own developments: when a map is saved, deleted or create, and when the directions panel HTML is generated
* Removed: the 'autocenter' parameter is deprecated; if you want the map to automatically center, set the center to null or (0,0); to automatically zoom set the zoom to null
* Changed: updated the mappress.po file (you can use it as a .pot as well) and many of the plugin texts
* Changed: traffic button has been styled to look more like the Google buttons
* Changed: map type control should now adapt itself to screen size and map size automatically (horizontal bar or dropdown)
* Changed: when clicking on marker 'directions' link, marker infowindow is not closed until directions are requested
* Fixed: when requesting directions from a marker on the marker list, the marker infowindow will be opened
* Fixed: geocoding errors for some odd addresses could return blanks, these are now ignored
* Fixed: marker zooming in the editor now works better at all zoom levels

2.31
=
* Fixed: better network activation - plugin checks if it needs to create its tables whenever it runs
* Fixed: jQuery is now loaded in noConflict() mode to preserve compatibility with older plugins and themes
* Fixed: variable naming bug in geocode() method of the API
* Fixed: timing issue when publishing without saving first in Firefox

2.30
=
* Fixed: was loading JQ 1.4.2, should be 1.4.4 for WP3.1; also removed JQ load on admin screens

2.29
=
* Added: load jQuery 1.4.2 even if an obsolete version is loaded by another plugin or theme, this resolves somoe theme/plugin conflicts
* Changed: updated some of the obsolete language files.  Also plugin now loads the domain in the init() action
* Changed: added !important modifiers to CSS to resolve compatibility with some themes

2.28
=
* Fixed: unable to add new locations (broken by a change in the Pro version)

2.27
=
* Added: ability to show directions initially.  Use [mappress initialopendirections="true"] to use this feature.
* Changed: changed label "location list" to "marker list" (no functionality change, just the labels)
* Fixed: added missing texts for locationlization
* Fixed: added <p> tags around directions to support strict XHTML validation
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: the default marker list template now just shows [title] rather than [title] and [body]
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: the marker list template [body] tag now shows FULL HTML for the body.  Use [bodytext] to show the text with the HTML stripped out.
* [MapPress Pro](http://wphostreviews.com/mappress) Added: new widget options for showing directions and a marker list
* [MapPress Pro](http://wphostreviews.com/mappress) Added: editor now remembers last icon selected

2.26
=
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: bug in 2.25 caused markers to list incorrectly when editing
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: updated marker list display to show marker title + plain text of marker body (see docs for details)

2.25
=
* Added: "reset defaults" button on options screen
* Fixed: in some cases the mappress shortcode could appear in RSS feeds
* [MapPress Pro](http://wphostreviews.com/mappress) Changed: when saving empty custom address field, no map created
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: POI template function wasn't using user template
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: powered by link incorrectly labeled

2.24
=
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: error saving custom field name for errors

2.23
=
* Fixed: incorrect directions routing for foreign addresses, e.g. French
* Fixed: missing translation for some strings
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: directions link not working in marker list
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: incorrect overflow handling for POI list in IE8

2.22
=
* Fixed: warning on settings screen

2.20-2.21
=
* [MapPress Pro](http://wphostreviews.com/mappress) Added: setting for list of locations under map
* [MapPress Pro](http://wphostreviews.com/mappress) Added: setting to remove powered by link
* [MapPress Pro](http://wphostreviews.com/mappress) Added: extended automatic map creation for custom fields
* [MapPress Pro](http://wphostreviews.com/mappress) Added: extended query processing to allow array options

2.19
=
* [MapPress Pro](http://wphostreviews.com/mappress) Added: create maps from custom field metadata for [TurboCSV](http://wphostreviews.com/turbocsv)

*
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
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: for mashups, WP editor replaced & with &amp; and defaults were not set correctly
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: better title/directions URL handling for mashups & widget if POI was created using lat/lng instead of address

2.15
=
* Enhanced address correction for US/Foreign addresses
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: bugs related to TurboCSV integration
* [MapPress Pro](http://wphostreviews.com/mappress) Fixed: "my icons" click events

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
