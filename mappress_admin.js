/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
*/

// *********************************************************************
// Admin functions for options screen
// *********************************************************************

// Check if API is valid
function mappCheckAPI() {
	var apiKey = document.getElementById('api_key');
	var apiMessage = document.getElementById('api_message');
	var apiBlock = document.getElementById('api_block');
	var googleLink = '<a target="_blank" href="http://code.google.com/apis/maps/signup.html">' + mappressl10n.here + '</a>';
	
	if (apiKey.value == "") {
		apiBlock.className = 'api_error';
		apiMessage.innerHTML = mappressl10n.api_missing + googleLink;
		return;
	}

	if (typeof GBrowserIsCompatible == 'function' && GBrowserIsCompatible())
		return;

	apiBlock.className = 'api_error';
	apiMessage.innerHTML = mappressl10n.api_incompatible + googleLink;
}

// Icon picker
jQuery(document).ready(function() {
	// Create icon dialog
	jQuery('#mapp_icon_list').dialog({title : mappressl10n.select_icon, autoOpen : false, modal : true, resizable: true, width : "50%", height : "auto"});

	// Add click event when user picks an icon within the dialog
	jQuery('#mapp_icon_list').click(function(e) {
		// Change the default icon if something was selected
		jQuery('#default_icon').val(e.target.id);
		jQuery('#icon_picker').attr('src', e.target.src);
		jQuery('#mapp_icon_list').dialog('close');
	});
	
	// Add click event to open the dialog
	jQuery('#icon_picker').click(function() {
		jQuery('#mapp_icon_list').dialog('open');
	});	
});

// *********************************************************************
// Admin functions for post edit screen
// *********************************************************************

// Admin screen initalization
jQuery(document).ready(function($){
});

// Insert mappress shortcode in post
function mappInsertShortCode () {
	shortcode = "[mappress]";
	send_to_editor(shortcode);
	return false;
}

// Geocode current row; if successful, add new row
function mappAddMarker() {
	var address = jQuery("#mapp_input_address").val();

	// Do nothing if address was blank
	if (!address) {
		var message = jQuery("#mapp_message");
		message.text(mappressl10n.enter_address); 
		message.removeClass("updated fade error");        
		message.addClass("updated fade");        
		return;
	}
	
	// Pass address to geocoder
	mappGeocoder.getLocations(address, mappAddGeocodedMarker);    
}

// Check the geocoded address.  If it's ok, add a row.
function mappAddGeocodedMarker(response) {
	var address = jQuery("#mapp_input_address").val();
	var caption = jQuery("#mapp_input_caption").val();
	var message = jQuery("#mapp_message");
	
	// Check response for errors
	if (!response || response.Status.code != 200) {
		message.text(mappressl10n.no_address); 
		jQuery("#mapp_message").removeClass("updated fade error");        
		message.addClass("error");
		return; 
	}
	
	// Response was ok, get the geocoded address values
	var place = response.Placemark[0];
	var point = new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]);
	
	// Just confirm that we got lat/lng
	if (!point.lat() || !point.lng()) {
		alert ("Mappress internal error in geocoding!")
		return;
	}
	
	// Check if point already exists; if so, don't add it
	if (adminMap.checkPoint(point)) {
		message.text(mappressl10n.address_exists + place.address);
		message.removeClass("updated fade");
		message.addClass("error")
		return;
	}

	// Add the new address to the minimap
	var poi = {address : address, corrected_address : place.address, lat : point.lat(), lng : point.lng(), caption : '', body : '',  icon : '' };
	var i = adminMap.addMarker(poi, true);
	adminMap.centerOnMarker(i, true);
	
	// Update the list of POIs
	adminMap.listMarkers();

	// Clear the input fields
	jQuery("#mapp_input_address").val("");
}

// Editor minimap
function minimapp(pois, width, height, addressFormat, zoom) {
	this.map;
	this.mapDiv = document.getElementById("admin-map-div");
	this.bounds;
	this.pois = [];	
	this.width = parseInt(width);
	this.height = parseInt(height);
	this.zoom = parseInt(zoom);
	this.addressFormat = addressFormat;
	
	// Load and unload for IE
	var me = this;
	if (document.all && window.attachEvent) { 
		window.attachEvent("onload", function () { me.display(pois); });
		window.attachEvent("onunload", GUnload);
	// Non-IE load and unload            
	} else if (window.addEventListener) { 
		window.addEventListener("load", function () { me.display(pois); }, false);
		window.addEventListener("unload", GUnload, false);
	}
}    
 
minimapp.prototype = {     

	display : function(pois) {   
		var mapOptions = {}; 
		
		// Check that API loaded OK
		if (!GBrowserIsCompatible() || typeof(GMap2) == 'undefined') 
			return;

		// Create map object and set bounds; the setCenter call is mandatory!
		this.map = new GMap2(this.mapDiv, mapOptions);     
		this.map.setCenter(new GLatLng(0,0),0);        

		// Get default UI settings and adjust them
		var ui = this.map.getDefaultUI();
		ui.controls.maptypecontrol = false;
		ui.controls.largemapcontrol3d = false
		ui.controls.smallzoomcontrol3d = true;
		this.map.setUI(ui);

		// Turn the POIs into an array
		if (!(pois instanceof Array))
			pois = new Array(pois);
						
		// Create a marker for each poi
		len = pois.length;
		for (var i = 0; i < len; i++) 
			this.addMarker(pois[i], false);

		// Center and zoom
		this.center(true);
		
		// List POIs
		this.listMarkers();		
	},

	// Method to add a marker    
	addMarker : function(poi, render) {          
		var marker;
		var point;
		var markerOptions;
		var i;
		
		// Close any open infowindows
		this.map.closeInfoWindow();

		// Set icon if provided, otherwise use default
		if (poi.icon != '')
			markerOptions = {icon:mappIcons[poi.icon]};

		// Create a marker.
		var point = new GLatLng(poi.lat, poi.lng);
		var marker = new GMarker(point, markerOptions);

		// Assign a marker number and save it to our array
		poi.marker = marker;        
		this.pois.push(poi);
		i = this.pois.length - 1;

		this.map.addOverlay(marker);

		// Open the infowindow if requested		
		if (render)
			this.renderMarker(i);
			
		return i;
	},

	renderMarker : function (i) {
		var caption = this.pois[i].caption;
		var body = this.formatAddress(i);
		
		var html    = "<div class=\"mapp-overlay-div\">" 
					+ "<div class=\"mapp-overlay-title\">" + caption + "</div>"
					+ "<div class=\"mapp-overlay-body\">" + body + "</div>"
					+ "</div>"
					+ "<br/><a href=\"javascript:adminMap.editMarker('" + i + "')\" alt=\"" + mappressl10n.edit + "\">" + mappressl10n.edit + "</a>"
					+ " | <a href=\"javascript:adminMap.deleteMarker('" + i + "')\">" + mappressl10n.del + "</a>";
					
		// Center on the marker
		this.centerOnMarker(i, false);
		this.pois[i].marker.openInfoWindowHtml(html);        		
	},
	
	editMarker : function (i) {               
		var width = this.map.getSize().width / 2;
		var caption = this.pois[i].caption;
		var body = this.formatAddress(i)
				
		var html    = "<div style=\"text-align: left; width: " + width + "px\">"
					+ mappressl10n.title + ": <input id=\"markerCaption\" value=\"" + caption + "\" style=\"width: 100%\" />"
					+ "<br/><textarea id=\"markerBody\" rows=\"4\" style=\"width: 100%\">" + body + "</textarea>"
					+ "<br/><input type=\"button\" name=\"saveEditMarker\" value=\"" + mappressl10n.save + "\" onclick=\"adminMap.saveEditMarker('" + i + "')\" />"
					+ "<input type=\"button\" name=\"cancelEditMarker\" value=\"" + mappressl10n.cancel + "\" onclick=\"adminMap.cancelEditMarker('" + i + "')\" />"
					+ "</div>";
		this.pois[i].marker.openInfoWindowHtml(html);
	},
	
	formatAddress : function (i) {
		var body = this.pois[i].body;
				
		// If body is empty default it to corrected address in the selected format
		if (!body) {
			switch (this.addressFormat) {
				case 'ENTERED':
					body = this.pois[i].address;
					break;
				case 'CORRECTED':
					body = this.pois[i].corrected_address;
					break;
				case 'NOCOUNTRY':
					body = this.pois[i].corrected_address;
					if (body.lastIndexOf(',') > 0)
						body = body.slice(0, body.lastIndexOf(','));
					break;
				case 'NOUSA':
					body = this.pois[i].corrected_address;
					if (body.lastIndexOf(', USA') > 0)
						body = body.slice(0, body.lastIndexOf(', USA'));
					break;					
				default:
					body = this.pois[i].address;
					break;
			}
		}				
		return body;
	},

	saveEditMarker : function(i) {
		// Read the edited values
		var caption = jQuery("#markerCaption").val();
		var body = jQuery("#markerBody").val();        
		
		// Update POI
		this.pois[i].caption = caption;
		this.pois[i].body = body;
		
		this.listMarkers();		  
		this.renderMarker(i);
	},

	cancelEditMarker : function(i) {
		this.renderMarker(i);
	},
		
	listMarkers : function () {
		var html;
		html        =   '<table id="mapp_poi_table" style="width: 100%;background-color:whitesmoke"> \r\n'
					+   '<tbody>';
		
		for (i = 0; i < this.pois.length; i++ ) {
			// Bind the POI's marker click event
			this.addMarkerClick(this, this.pois[i].marker, i);		
			
			// Add the POI to list
			html    +=  '<tr style="padding: 0 0 0 0">'
					+   '<td style="width: 80%">'
					+   '<a id="mapp_poi_label" name="mapp_poi_label" style="width:90%; margin 0 0 0 0;" href="javascript:adminMap.renderMarker(' + i + ');">';
			
			// Label text is caption + corrected address
			if (this.pois[i].caption)
				html += "<b>" + this.pois[i].caption + "</b> : " + this.pois[i].corrected_address;
			else
				html += this.pois[i].corrected_address;

			html 	+=	'</a>'
					+   '<input type="hidden" name="mapp_poi_address[]" id="mapp_poi_address" value="' + this.pois[i].address + '" />'
					+   '<input type="hidden" name="mapp_poi_corrected_address[]" id="mapp_poi_corrected_address" value="' + this.pois[i].corrected_address + '" />'
					+   '<input type="hidden" name="mapp_poi_caption[]" id="mapp_poi_caption" value="' + this.pois[i].caption + '" />'
					+   '<input type="hidden" name="mapp_poi_body[]" id="mapp_poi_body" value="' + this.pois[i].body + '" />'
					+   '<input type="hidden" name="mapp_poi_lat[]" id="mapp_poi_lat" value="' + this.pois[i].lat + '" />'	
					+   '<input type="hidden" name="mapp_poi_lng[]" id="mapp_poi_lng" value="' + this.pois[i].lng + '" />'
					+   '</td>'
					+   '</tr>';
		}
		
		html        +=  '</tbody>'
					+   '</table>';
					
		// List the POIs
		jQuery("#admin_poi_div").html(html);        
	},
		
	addMarkerClick : function(me, marker, i) {
		// Clear and re-create click event to open info window
		GEvent.clearListeners(marker, "click");
		GEvent.addListener(marker, "click", function() {
			me.renderMarker(i);
		});
	},
	
	deleteMarker : function(i) {
		// Confirm we want to delete
		var result = confirm(mappressl10n.delete_this_marker);
		if (!result)
			return;
		
		// Adjust map.  Close any open infowindows, delete the marker's overlay
		this.map.closeInfoWindow();
		this.map.removeOverlay(this.pois[i].marker);
		
		// Remove the marker from our POI array 
		this.pois.splice(i, 1);

		// List POIs
		this.listMarkers();
	},
	
	checkPoint : function(p) {
		for (i=0; i < this.pois.length; i++) {
			if (this.pois[i].lat == p.lat() && this.pois[i].lng == p.lng())
				return true;
		}
		return false;
	},

	// Re-center on a specific marker; optionally re-zoom
	centerOnMarker : function(i, zoom) {
		var center = this.pois[i].marker.getLatLng();
		this.map.setCenter(center);					

		if (zoom)
			this.setZoom();
	},	

	// Re-center between all markers; optionally re-zoom
	center : function(zoom) {
		this.bounds = new GLatLngBounds();
		for (i=0; i< this.pois.length; i++) {
			this.bounds.extend(this.pois[i].marker.getLatLng());
		}      
			
		this.map.setCenter(this.bounds.getCenter());
		if (zoom)
			this.setZoom();
	},
	
	setZoom : function() {
		// If we have no POIs then zoom all the way out
		if (this.pois.length == 0) {
			this.map.setZoom(1);
			return;
		}

		// Auto-zoom, but limit to 15
		else {
			var autoZoom = this.map.getBoundsZoomLevel(this.bounds);
			if (autoZoom > 15)
				autoZoom = 15;
			this.map.setZoom(autoZoom);
		}
	},	
}


//        GEvent.addListener(marker, "click", function() {            
//            var html = "<div id='mce_editor1'></div>"; 
//            var opts = {maxTitle : 'bigtitle', maxContent : html};
//            marker.openInfoWindowHtml(html, opts);
//            
//        });
//        var info = this.map.getInfoWindow();        
//        GEvent.addListener(marker, "infowindowopen", function() { 
//            info.maximize();            
//        }); 
//        GEvent.addListener(info, "maximizeend", function() {
//            tinyMCE.execCommand('mceAddControl', false, 'mce_editor1'); 
//            tinyMCE.execCommand('mceFocus', false, 'mce_editor1'); 
//            
//        });      
//        GEvent.addListener(marker, "infowindowbeforeclose", function() { 
//            tinyMCE.execCommand('mceFocus', false, 'mce_editor1'); 
//            tinyMCE.execCommand('mceRemoveControl', false, 'mce_editor1'); 
//        });         
		
