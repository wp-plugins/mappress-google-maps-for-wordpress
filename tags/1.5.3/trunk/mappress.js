/*
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
*/

// Mapp class
function mapp(mapname, pois, width, height, zoom, bigZoom, mapTypes, initialMapType, googlebar, initialOpenInfo, traffic, streetview) {
    this.map;
    this.mapname = mapname;
    this.pois = pois;
    this.width = parseInt(width);
    this.height = parseInt(height);
    this.zoom = parseInt(zoom);
    this.bigZoom = bigZoom;
    this.mapTypes = mapTypes;
    this.initialMapType = initialMapType;
    this.googlebar = googlebar;
    this.traffic = 0;
    this.streetview = 0;
    this.mapDiv = document.getElementById(mapname);
    this.streetDiv = document.getElementById(mapname + '_street_div');
    this.streetOuterDiv = document.getElementById(mapname + '_street_outer_div');
    this.directionsDiv = document.getElementById(mapname + '_directions_div');
    this.directionsOuterDiv = document.getElementById(mapname + '_directions_outer_div');
    this.initialOpenInfo = initialOpenInfo;
    this.directions;
    this.streetviewPanorama;                                     
    this.markers = [];
    this.markerTabs = [];
    this.bounds;
    this.mapOptions;   
    
    // Load and unload for IE
    var me = this;
    if (document.all && window.attachEvent) { 
        window.attachEvent("onload", function () { me.display(); });
        window.attachEvent("onunload", GUnload);
    // Non-IE load and unload            
    } else if (window.addEventListener) { 
        window.addEventListener("load", function () { me.display(); }, false);
        window.addEventListener("unload", GUnload, false);
    }
}    

     
mapp.prototype = {     
    display : function() {                     
        // Check that API loaded OK
        if (!GBrowserIsCompatible()) 
            return;

        if (typeof(GMap2) == 'undefined')
            return;
        
        // Initialize options
        this.mapOptions = {};
                 
        // Set up the GoogleBar
        if (this.googlebar == true) {
            this.mapOptions = {
                googleBarOptions : {
                    style : "new",
                    adsOptions: {
                        client: "partner-pub-4213977717412159",
                        channel: "mappress",
                        adsafe: "high"
                    }           
                }
            }
        }
        
        // Force size - normally, map should assume size of containing <div>; however, some plugins, e.g. tabs, use hidden <divs> which mess up the map
        this.mapOptions.size = new GSize(this.width, this.height);
        
        // Create map object and set bounds   
        this.map = new GMap2(this.mapDiv, this.mapOptions);     
        this.bounds = new GLatLngBounds();              

        // Get default UI settings
        var ui = this.map.getDefaultUI();
        
        // Suppress the defaults for zoom and map types no matter what
        ui.controls.maptypecontrol = false;
        ui.controls.largemapcontrol3d = false
        ui.controls.smallzoomcontrol3d = false;

        // If user wants map types then show the small dropdown 
        if (this.mapTypes == true) 
            ui.controls.menumaptypecontrol = true;

        // Set big or small zoom based on options
        if (this.bigZoom == true)
            ui.controls.largemapcontrol3d = true;
        else
            ui.controls.smallzoomcontrol3d = true;

        // Add our custom UI        
        this.map.setUI(ui);

        // Add GoogleBar
        if (this.googlebar == true)
            this.map.enableGoogleBar();    
                    
        // Set a map center - MUST be done to initialize the map
        this.map.setCenter(new GLatLng(0,0),0);        
        
        // If we only got a single poi, make an array out of it
        if (!(this.pois instanceof Array))
            this.pois = new Array(this.pois);
            
        // Create a marker for each poi
        for (var i = 0; i < this.pois.length; i++) {
            var point = new GLatLng(this.pois[i].lat, this.pois[i].lng);        
            this.addMarker(i, point);
            
            // Each time a point is found, extend the bounds to include it
            this.bounds.extend(point);        
        }
        
        // If user has requested it, open infoWindow initially
        if (this.initialOpenInfo == true)
            this.markers[0].openInfoWindowTabsHtml( [this.markerTabs[0].address, this.markerTabs[0].to] );
                  
        // Center window and zoom
        this.center();              
            
        // Traffic control; set distance from right-hand side based on whether map types are being displayed
        if (this.traffic == true) {
            if (this.mapTypes == true)
                this.map.addControl(new ExtMapTypeControl({showMapTypes: false, posRight: 100, showTraffic: true, showTrafficKey: true, showMore: false}));    
            else
                this.map.addControl(new ExtMapTypeControl({showMapTypes: false, posRight: 10, showTraffic: true, showTrafficKey: true, showMore: false}));        
        }
        
        // Set map type, if provided
        switch (this.initialMapType) {
            case 'normal':
                this.map.setMapType(G_NORMAL_MAP);
                break;
            case 'satellite':
                this.map.setMapType(G_SATELLITE_MAP);
                break;
            case 'hybrid':
                this.map.setMapType(G_HYBRID_MAP);
                break;
            case 'physical':
                this.map.setMapType(G_PHYSICAL_MAP);
                break;
        } 
    },
                                
    directionsSwitch : function(i, fromto) {   
        if (fromto == 'from')
            this.markers[i].openInfoWindowTabsHtml( [this.markerTabs[i].address, this.markerTabs[i].from], {selectedTab:1});        
        else
            this.markers[i].openInfoWindowTabsHtml( [this.markerTabs[i].address, this.markerTabs[i].to], {selectedTab:1});        
    },
    
    //
    // Show directions
    // 'srcForm' = a source form, such as the infoWindow directions form.  If provided, the 'saddr' and 'daddr' fields will be copied from there to 
    // the main directions form before the directions are retrieved.
    //
    directionsShow : function(srcForm) {     
        // Get the elements of the main directions form        
        var saddr = document.getElementById(this.mapname + '_saddr');
        var daddr = document.getElementById(this.mapname + '_daddr');
        var saddrCorrected = document.getElementById(this.mapname + '_saddr_corrected');
        var daddrCorrected = document.getElementById(this.mapname + '_daddr_corrected');
             
        // Close any existing street views and directions
        this.streetviewClose();
        this.directionsClose();

        // Close the currently open infoWindow        
        this.map.closeInfoWindow();
        
        // Hide all markers when directions are opened
        for ( var i = 0; i < this.markers.length; i++ )
            this.markers[i].hide();
            
        // Copy the field values from the source form to the directions form
        if (srcForm) {
            saddr.value = srcForm.saddr.value;
            daddr.value = srcForm.daddr.value;
        }
                
        // Clear any error class from the source/dest address fields
        saddr.className = 'mapp-address';
        daddr.className = 'mapp-address';
        
        // Capture check function name for closure
        var me = this;

        // Validate the source/dest address.  Note that directions.load() is called *regardless* of any address errors
        mappGeocoder.getLocations(saddr.value, function(response) {
            me.addressCheck(response, saddr, saddrCorrected)
        });
        
        mappGeocoder.getLocations(daddr.value, function(response) {
            me.addressCheck(response, daddr, daddrCorrected)
        });
                
        // Display the directions <div>
        this.directionsOuterDiv.style.display = 'block';  
        
        // Create directions object & load directions
        this.directions = new GDirections(this.map, this.directionsDiv);
        this.directions.load("from: " + saddr.value + " to: " + daddr.value );
        
        // Process errors; 'this' = directions object
        GEvent.addListener(this.directions, "error", function() {
            switch (this.getStatus().code) {
                case 400:
                    alert(mappressl10n.dir_400);
                    break;
                case 500:
                    alert(mappressl10n.dir_500);
                    break;
                case 601:
                    alert(mappressl10n.dir_601);
                    break;
                case 602:
                    alert(mappressl10n.dir_602);
                    break;
                case 603:
                    alert(mappressl10n.dir_603);
                    break;
                case 604:
                    alert(mappressl10n.dir_604);
                    break;
                case 610:
                    alert(mappressl10n.dir_610);
                    break;
                case 620:
                    alert(mappressl10n.dir_620);
                    break;
                default:
                    alert(mappressl10n.dir_default) + getStatus().code;
                    break;
            }            
        } );                  
    },
            
    // 
    // Print directions
    // 'form' = the main directions form
    directionsPrint : function() {
        // Get the elements of the main directions form        
        var saddr = document.getElementById(this.mapname + '_saddr');
        var daddr = document.getElementById(this.mapname + '_daddr');

        var url = 'http://maps.google.com';
        
        url += '?daddr=' + daddr.value;
        url += '&saddr=' + saddr.value;
        url += '&pw=2';
                
        window.open(url)
    },
    
    directionsClose : function() {
        if (this.directions) 
            this.directions.clear();
        this.directionsOuterDiv.style.display = 'none';    
        
        // Restore our markers when directions are closed
        for ( var i = 0; i < this.markers.length; i++ )
            this.markers[i].show();            
    },
    
    streetviewShow : function(i) {        
        // Close any existing street views and directions
        this.streetviewClose();
        this.directionsClose();
        
        // Set options and create street view
        var streetviewOptions = { latlng : this.markers[i].getLatLng() };          
        this.streetviewPanorama = new GStreetviewPanorama(this.streetDiv, streetviewOptions);
        
        GEvent.addListener(this.streetviewPanorama, "error", this.streetviewError);
        
        // Note: there's currently no way to tell if street view creation was successful
        // Waiting for google to fix the 'initialized' event on GStreetviewPanorama
        // For now, just assume it was successful

        // Display street view <div>
        this.streetOuterDiv.style.display = 'block';
        
    },

    streetviewClose : function() {
        if (this.streetviewPanorama) 
            this.streetviewPanorama.remove();
        this.streetOuterDiv.style.display = 'none';
    },
    
    addressCheck : function(response, addr, addr_corrected) {
        if (response == null || response.Placemark == null || response.Status.code != 200) {
            addr.className = 'mapp-address-error';
            addr_corrected.innerHTML = mappressl10n.no_address;
            return false;
        }
        
        if (response.Placemark.length > 1) {
            addr.className = 'mapp-address-error';
            addr_corrected.innerHTML = mappressl10n.did_you_mean + response.Placemark[0].address;
            return false;
        } 
        
        // No error; note that addr_corrected will be a <p> tag or similar, so we use .innerHTML, not .value
        addr_corrected.innerHTML = response.Placemark[0].address;    
        return true;
    },
                

    streetviewError : function(errorCode) {
        switch (errorCode) {
            case 603:
                alert(mappressl10n.street_603);
                break;
            case 600:
                alert(mappressl10n.street_600);
                break;
            default:
                alert(mappressl10n.street_default);
                break;
        }
    },

    // HTML for directions
    getDirectionsHTML : function(i, fromto) {       
        var html = '';
        var htmlAddress;
        var htmlDirections;
        var htmlStreetview;
        
        // Create the address HTML
        htmlAddress = "<div class=\"mapp-overlay-caption\">" + this.pois[i].caption + "</div>";
        htmlAddress += this.pois[i].address;
        
        if (fromto == 'address') {                 
            htmlStreetview = "<a href=\"javascript:" + this.mapname + ".streetviewShow(" + i + ")\">" + mappressl10n.street_view + "</a>";            
            if (this.streetview == true)
                return "<div class=\"mapp-overlay-div\">" + htmlAddress + "<br />" + htmlStreetview + "</div>";
            else
                return "<div class=\"mapp-overlay-div\">" + htmlAddress + "</div>";
        } 
        
        if (fromto == 'fromto') {
            htmlDirections = "Directions: "
                + "<a href=\"javascript:" + this.mapname + ".directionsSwitch(" + i + ", 'to') \">" + mappressl10n.to_here + "</a>"
                + " - "
                + "<a href=\"javascript:" + this.mapname + ".directionsSwitch(" + i + ", 'from')\">" + mappressl10n.from_here + "</a></div>";
        }
        
        if (fromto == 'to') {
            // Note that form onSubmit is used to block the form from re-submitting current page; we want form formatting, but not the submit
            htmlDirections = 'Directions: <b>to here<\/b> - <a href="javascript:' + this.mapname + ".directionsSwitch(" + i + ", 'from')\" >" + mappressl10n.from_here + "</a>"
                 + '<form onSubmit=\"return false\">'
                 + '<input type="text" id="saddr" value="" /><br>'
                 + '<input type="hidden" id="daddr" value="' + this.pois[i].address + '"/>'
                 + "<input type=\"submit\" onclick=\"" + this.mapname + ".directionsShow(form)\" value=\"" + mappressl10n.get_directions + "\" />"
                 + '</form>';
        }
        
        if (fromto == 'from') {
            // Note that form onSubmit is used to block the form from re-submitting current page; we want form formatting, but not the submit
            htmlDirections = 'Directions: <a href="javascript:' + this.mapname + ".directionsSwitch(" + i + ", 'to')\" >" + mappressl10n.to_here + "</a> - <b>" + mappressl10n.from_here + "</b>"                        
                 + '<form onSubmit=\"return false\">'
                 + '<input type="text" id="daddr" value="" /><br>'
                 + '<input type="hidden" id="saddr" value="' + this.pois[i].address + '"/>'
                 + "<input type=\"button\" onclick=\"" + this.mapname + ".directionsShow(form)\" value=\"" + mappressl10n.get_directions + "\" />"                 
                 + '</form>';                     
        }        

        return "<div class=\"mapp-overlay-div\">" + htmlDirections + "</div>";                 
    },
    

    // Re-center map
    center : function () {
        // If we had multiple POIs, zoom out and re-center to show them all
        if (this.markers.length > 1) {
            this.map.setZoom(this.map.getBoundsZoomLevel(this.bounds) - 1);
            this.map.setCenter(this.bounds.getCenter());
        // If only one POI, center on it and use the user-selected zoom            
        } else 
            this.map.setCenter(this.markers[0].getLatLng(), this.zoom);                        
    },

    // Method to add a marker    
    addMarker : function(i, point) {          
        var marker;
        var markerOptions;
        var html, htmlAddress, htmlDirections;        
        
        // Set icon if provided, otherwise use default
        if (this.pois[i].icon != '')
            markerOptions = {icon:mappIcons[this.pois[i].icon]};
            
        // Create a marker.              
        this.markers[i] = new GMarker(point, markerOptions);
        
        // Store the tabs to global array
        this.markerTabs[i] = new Object();
        this.markerTabs[i].address = new GInfoWindowTab ('Address', this.getDirectionsHTML(i, 'address'));
        this.markerTabs[i].from = new GInfoWindowTab ('Directions', this.getDirectionsHTML(i, 'from'));
        this.markerTabs[i].to = new GInfoWindowTab ('Directions', this.getDirectionsHTML(i, 'to'));
        
        // Save local variables for the function closure below
        var tabs = this.markerTabs[i];
        var marker = this.markers[i];
                
        // Create click event to open info window
        me = this;
        GEvent.addListener(marker, "click", function() {
            me.streetviewClose();
            me.directionsClose();
            marker.openInfoWindowTabsHtml( [tabs.address, tabs.to] );            
        });
        
        // Create a listener to close the street view when the infoWindow is closed or another one's clicked
        GEvent.addListener(marker, "infowindowclose", function() {
            me.streetviewClose();
            me.directionsClose();            
        } );                             
        
        this.map.addOverlay(this.markers[i]);     
    }       
}

