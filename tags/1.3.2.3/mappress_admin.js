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
        var currentRow = jQuery(this).parent().parent('tr');
        
        // If table has > 1 rows then delete this row
        if (jQuery('TR', currentRow.parent()).size() > 1)
            currentRow.remove();
            
        // If table has only 1 row then clear it - we need at least 1 row for clone() later
        // Clear means: erase <input> contents, <p> contents, and blank text for <a> links
        else {
            jQuery("input", currentRow).val("");
            jQuery("p,a", currentRow).text("");
        }

    });
    
    // "Edit button"
    jQuery("[name=mapp_poi_edit]").click(function () {
        var currentRow = jQuery(this).parent().parent('tr');               
        var address = jQuery("#mapp_poi_address", currentRow).val();
        var caption = jQuery("#mapp_poi_caption", currentRow).val();    

        // Set the input field to the edited row
        jQuery("#mapp_input_address").val(address);
        jQuery("#mapp_input_caption").val(caption);
        
        // Delete the edited row
        jQuery(currentRow).remove();
    });
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
    mappGeocoder.getLocations(address, mappParseAddress);    
}

// Check the geocoded address.  If it's ok, add a row.
function mappParseAddress(response) {
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
    
    // Add the new row
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