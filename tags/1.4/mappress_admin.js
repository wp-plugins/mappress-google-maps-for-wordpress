/*
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
*/

// *********************************************************************
// Admin functions
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

// Admin screen initalization
jQuery(document).ready(function($){
    mappAddEventHandlers();     
});

// Add onclick handler to all existing 'delete' links
function mappAddEventHandlers() {
    
    // "Delete" button
    jQuery("[name=mapp_poi_delete]").click(function () { 
        mappDelete(this);
    });
    
    // "Edit button"
    jQuery("[name=mapp_poi_edit]").click(function () {
        mappEdit(this);
    });
    
    // Event fired when the containing <div> is resized; used to re-center map
    jQuery(window).resize(function () {
        adminMap.center();
    });
    
    // Event for change in the 'show map' checkbox
    jQuery("#mapp_show").click(function () {
        var checked = jQuery("#mapp_show").attr('checked');
        if (checked)
            adminMap.show();
        else
            adminMap.hide();            
    });
    
}

function mappDelete(me) {
    var currentRow = jQuery(me).parent().parent('tr');
    
    // Remove the map poi and marker
    var lat = jQuery("#mapp_poi_lat", currentRow).val();
    var lng = jQuery("#mapp_poi_lng", currentRow).val();
    adminMap.deleteMarker(lat, lng);
    
    // If table has > 1 rows then delete this row
    if (jQuery('TR', currentRow.parent()).size() > 1)
        currentRow.remove();
        
    // If table has only 1 row then clear it instead of deleting it - we need at least 1 row for clone() later
    // Clear means: erase <input> contents, <p> contents, and blank text for <a> links
    else {
        jQuery("input", currentRow).val("");
        jQuery("p,a", currentRow).text("");
    }    
}

function mappEdit(me) {
    var currentRow = jQuery(me).parent().parent('tr');               
    var address = jQuery("#mapp_poi_address", currentRow).val();
    var caption = jQuery("#mapp_poi_caption", currentRow).val();    

    // Remove the map poi and marker
    var lat = jQuery("#mapp_poi_lat", currentRow).val();
    var lng = jQuery("#mapp_poi_lng", currentRow).val();
    adminMap.deleteMarker(lat, lng);
        
    // Set the input field to the edited row
    jQuery("#mapp_input_address").val(address);
    jQuery("#mapp_input_caption").val(caption);

    // If table has > 1 rows then delete this row
    if (jQuery('TR', currentRow.parent()).size() > 1)
        currentRow.remove();
        
    // If table has only 1 row then clear it instead of deleting it - we need at least 1 row for clone() later
    // Clear means: erase <input> contents, <p> contents, and blank text for <a> links
    else {
        jQuery("input", currentRow).val("");
        jQuery("p,a", currentRow).text("");
    }
}

// Insert mappress shortcode in post
function mappInsertShortCode () {
    shortcode = "[mappress]";
    send_to_editor(shortcode);
    return false;
}

// Clear the form
function mappClear() {
    jQuery("#mapp_input_address").val("");
    jQuery("#mapp_input_caption").val("");    
    jQuery("#mapp_message").text("");    
    jQuery("#mapp_message").removeClass("updated fade error");
}

// Geocode current row; if successful, add new row
function mappAddRow() {
    var address = jQuery("#mapp_input_address").val();
    mappGeocoder.getLocations(address, mappAddGeocodedRow);    
}

// Check the geocoded address.  If it's ok, add a row.
function mappAddGeocodedRow(response) {
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
        message.text("Address is already on the map : " + place.address);
        message.removeClass("updated fade");
        message.addClass("error")
        return;
    }
    
    
    // Get last row in table and clone it.  Note: tried clone(true) for this, but it intermittently fails
    var lastRow = jQuery("#mapp_poi_table > tbody > tr:last");    
    var clonedRow = jQuery(lastRow).clone();
    
    // Set the text for the <p> element to caption+address
    if (caption)
        label = "<b>" + caption + "</b>: " + address;
    else 
        label = address;
        
    jQuery("#mapp_poi_label", clonedRow).html(label);        
    
    // Make the 'delete' and 'edit' buttons visible by setting their text
    jQuery("#mapp_poi_delete", clonedRow).text("Delete");
    jQuery("#mapp_poi_edit", clonedRow).text("Edit");
    
    // Set the hidden fields to the geocoded values
    jQuery("#mapp_poi_address", clonedRow).val(address);
    jQuery("#mapp_poi_caption", clonedRow).val(caption);        
    jQuery("#mapp_poi_corrected_address", clonedRow).val(place.address);
    jQuery("#mapp_poi_lat", clonedRow).val(point.lat());
    jQuery("#mapp_poi_lng", clonedRow).val(point.lng());        

    // Add the new address to the minimap
    var poi = {address : address, corrected_address : place.address, lat : point.lat(), lng : point.lng(), caption : caption, icon : '' };
    var rownum = adminMap.addMarker(poi);
    
    // Record the map marker number in a hidden field
    jQuery("#mapp_poi_num", clonedRow).val(rownum);
    
    // Add the new row to our HTML table
    jQuery("#mapp_poi_table").append(clonedRow);
    
    // Clear the input fields
    jQuery("#mapp_input_address").val("");
    jQuery("#mapp_input_caption").val("");

    // Message that item was added
    message.text("Added: " + place.address);
    message.removeClass("error");
    message.addClass("updated fade")
    mappAddEventHandlers();
}

// Editor minimap
function minimapp(pois) {
    this.map;
    this.mapDiv = document.getElementById("admin-map-div");
    this.bounds;
    this.pois = [];
    
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

        // Create map object and set bounds   
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
            this.addMarker(pois[i]);
    },

    // Method to add a marker    
    addMarker : function(poi) {          
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
        point = new GLatLng(poi.lat, poi.lng);
        var marker = new GMarker(point, markerOptions);

        // Assign a marker number and save it to our array
        poi.marker = marker;        
        this.pois.push(poi);
                        
        // Create click event to open info window
        GEvent.addListener(marker, "click", function() {
            var html = "<div class=\"mapp-overlay-div\">" + "<div class=\"mapp-overlay-caption\">" + poi.caption + "</div>" + poi.address + "</div>";
            marker.openInfoWindowHtml(html);
        });
  
        this.map.addOverlay(marker);
            
        // Extend bounds to include the new poi
        if (!this.bounds)
            this.bounds = new GLatLngBounds(point, point);
        else 
            this.bounds.extend(point);             
            
        this.center();
        this.zoom();        
    },

    deleteMarker : function(lat, lng) {
        // Close any open infowindows
        this.map.closeInfoWindow();
        
        // Delete the marker overlay 
        for (i=0; i < this.pois.length; i++) {
            if (this.pois[i].lat == lat && this.pois[i].lng == lng) {
                this.map.removeOverlay(this.pois[i].marker);
                this.pois.splice(i, 1);
            }
        }
        
        // Reset bounds
        this.bounds = new GLatLngBounds();
        for (i=0; i < this.pois.length; i++) {
            point = new GLatLng(this.pois[i].lat, this.pois[i].lng);
            this.bounds.extend(point);
        }
    },
    
    checkPoint : function(p) {
        for (i=0; i < this.pois.length; i++) {
            if (this.pois[i].lat == p.lat() && this.pois[i].lng == p.lng())
                return true;
        }        
        
        return false;
    },
    
    center : function() {
        this.map.setCenter(this.bounds.getCenter());
    },
    
    zoom : function() {
        this.map.setZoom(this.map.getBoundsZoomLevel(this.bounds) - 1);
    },
    
    hide : function () {
        this.mapDiv.style.display = 'none';             
    },
    
    show : function () {
        this.mapDiv.style.display = 'block';
    }
}
