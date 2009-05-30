=== MapPress Easy Google Maps ===
Contributors: chrisvrichardson
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4339298
Tags: google maps,google,map,maps,easy,poi,mapping,mapper,gps,lat,lon,latitude,longitude
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 1.4.3

Easily add Google Maps and driving directions to your blog.

== Description ==

MapPress is the easiest way to add Google Maps and driving directions to your blog.  While editing a post just enter any addresseses you'd like to map.  The plugin will automatically insert a great-looking map with markers and directions right into your blog. 

= News =

* See the version notes below for detailed info about version 1.4.3
* What features would you like to see next? [Take the Poll](http://www.wphostreviews.com/mappress).
* Send me some feedback: [Contact me](http://wphostreviews.com/chris-contact) using the web form or email me (chrisvrichardson@gmail.com)

= Localization =
Please [Contact me](http://wphostreviews.com/chris-contact) if you'd like to provide a translation or an update.  Special thanks to:

* German - Stefan Schirmer and Stevie
* Dutch	- Wouter Kursten
* Chinese - Ya Chen

= Key Features: =
* NEW (1.4.3): Edit map marker titles by clicking on the map marker and choosing 'edit' - then enter any valid HTML
* NEW (1.4.3): Improved icon picker and more map icons
* NEW (1.4.3): Automatic zoom: by default MapPress will automatically zoom and center your map to show all mapped locations
* Multi-language support including German and Chinese
* Easily add multiple addresses to a single map
* Many different marker icons in different colors
* Tabbed directions - separate tabs for address and driving directions
* Inline directions appear right in your blog! Readers can even print them directly from your blog
* High-speed geocoding with 500% faster map display
* Edit maps interactively without complicated shortcodes
* Choose map types (street, terrain, satellite, or hybrid)
* GoogleBar mini-search box to find local businesses on the map
* Full range of map controls including zoom, pan, etc.

**[Download now!](http://www.wphostreviews.com/mappress)**

[Home Page](http://www.wphostreviews.com/mappress) | 
[Documentation](http://www.wphostreviews.com/mappress-documentation) |
[Screenshots](http://www.wphostreviews.com/mappress-screenshots) |
[FAQ](http://www.ratingpress.com/index.php/mappress-faq) |
[Support](http://www.wphostreviews.com/mappress-faq) 

Please note
=
* Version 1.3 and above are NOT backwards compatible with earlier versions of MapPress.  You must re-enter any existing maps.

== Installation ==

See full [installation intructions and Documentation](http://www.wphostreviews.com/mappress-documentation)

1. Unzip into a directory in `/wp-content/plugins/`, for example `/wp-content/plugins/mappress-google-maps-for-wordpress.zip`.  Be sure to put all of the files in this directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enter your Google Maps API key and other options using the the 'MapPress' menu - it's right under the standard 'Settings' menu.
1. That's it - now you'll see a MapPress meta box in in the 'edit posts' screen.  You can use it to add maps to your posts just by entering the address to display and an (optional) comment for that address.

== Frequently Asked Questions ==

Please read the **[FAQ](http://www.wphostreviews.com/mappress-faq)**

== Screenshots ==

[MapPress Screenshots](http://www.wphostreviews.com/mappress-screenshots)

== Version History ==
1.4.3
=
* Fixed bug where addresses containing single/double quotes could prevent map from rendering
* Added 'directions' option to show or suppress driving directions
* Internationalization has been improved and all visible texts should now be available for translation
* Changed 'caption' to 'title'.  If you have CSS assigned to class "mapp-overlay-caption" please change it to "mapp-overlay-title"
* Removed 'default zoom' option from options page.  By default MapPress will try to automatically set the zoom to show all of your markers.  If you want to override the zoom you must set a new zoom on each map individually.  
* Added option to edit marker titles directly on the map; any valid HTML can be entered for the title.
* Streamlined marker edit and display code; faster loading times, especially on admin screens
* Better icon-picker with popup window
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