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

// Map functions
// pois = {address, lat, lon, comment}
function mapp(mapname, pois, zoom, defaultUI, tabbed, googlebar) {
     this.mapname = mapname;
     this.pois = pois;
     this.zoom = zoom;
     this.defaultUI = defaultUI;
     this.tabbed = tabbed;              // true=show tabbed directions
     this.div = document.getElementById(mapname);
     
     var mapOptions;     

     // Check that API loaded OK
     if (!GBrowserIsCompatible()) 
        return;

     if (typeof(GMap2) == 'undefined')
        return;
             
     // Set up the GoogleBar
     if (googlebar) {
        mapOptions = {
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
           
     this.map = new GMap2(this.div, mapOptions);     
     this.bounds = new GLatLngBounds();              
     this.gmarkers = [];

    // Add default UI controls or minimal controls
    if (defaultUI)
        this.map.setUIToDefault();
    else {
        var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
        this.map.addControl(new GSmallMapControl(), topRight);
    } 
    
    // Add GoogleBar
    if (googlebar)
        this.map.enableGoogleBar();    
                
    // Set a map center before adding markers
    this.map.setCenter(new GLatLng(0,0),0);        
    
    // If we only got a single poi, make an array out of it
    if (!(pois instanceof Array))
        pois = new Array(pois);
        

    // Create a marker and add an overlay for each poi
    for (var i = 0; i < pois.length; i++) {
        var point = new GLatLng(pois[i].lat, pois[i].lng);                    
        var marker = this.addMarker(point, i);
        this.map.addOverlay(marker);       

        // Each time a point is found, extent the bounds to include it
        this.bounds.extend(point);        
    }
      
    // If we had multiple POIs, zoom out and re-center to show them all, otherwise use the given zoom
    if (pois.length > 1) {
        this.map.setZoom(this.map.getBoundsZoomLevel(this.bounds) - 1);
        this.map.setCenter(this.bounds.getCenter());
    } else {
        this.map.setCenter(point, zoom);                
    }
}

mapp.prototype = {    
    // Method to add a marker    
    addMarker : function(point, i) {          
        var marker = new GMarker(point, G_DEFAULT_ICON);
        var html, htmlAddress, htmlDirections;
        
        this.gmarkers[i] = marker;
        
        // Show directions slightly differently based on whether they're tabbed or not
        if (this.tabbed) {
            htmlAddress = this.getDirections(i, 'address');
            htmlDirections = this.getDirections(i, 'to');
            GEvent.addListener(marker, "click", function() {
                marker.openInfoWindowTabsHtml([new GInfoWindowTab('Address', htmlAddress), new GInfoWindowTab('Directions', htmlDirections)]);
            });
        } else {
            html = this.getDirections(i, 'fromto');
            GEvent.addListener(marker, "click", function() {
                marker.openInfoWindowHtml(html);
            });
        }
        return marker;
    },

    switchDirections : function(i, fromto) {        
        if (this.tabbed) {
            htmlAddress = this.getDirections(i, 'address');
            htmlDirections = this.getDirections(i, fromto);            
            this.gmarkers[i].openInfoWindowTabsHtml( [new GInfoWindowTab('Address', htmlAddress), new GInfoWindowTab('Directions', htmlDirections)], {selectedTab:1});        
            
        } else {
            html = this.getDirections(i, fromto);
            this.gmarkers[i].openInfoWindowHtml(html);
        }
    },

    
    // HTML for directions
    // tabbed = false: show links for 'from' and 'to'
    // tabbed = true: no links
    getDirections : function(i, fromto) {
        
        var html = '';
        var htmlAddress = '';
        var htmlDirections;
        
        if (this.pois[i].comment)
            htmlAddress += this.pois[i].comment + "<br />";
        if (this.pois[i].address)
            htmlAddress += this.pois[i].address + "<br />";
         
        if (fromto == 'address') {
            return "<div class=\"mapp-overlay-div\">" + htmlAddress + "</div>";
        } 
        
        if (fromto == 'fromto') {
            htmlDirections = "Directions: "
                + "<a href=\"javascript:" + this.mapname + ".switchDirections(" + i + ", 'to') \">to here</a>"
                + " - "
                + "<a href=\"javascript:" + this.mapname + ".switchDirections(" + i + ", 'from')\">from here</a></div>";
        }
        
        if (fromto == 'to') {
            htmlDirections = 'Directions: <b>to here<\/b> - <a href="javascript:' + this.mapname + ".switchDirections(" + i + ", 'from')\" >from here</a>"
                 + '<form action="http://maps.google.com/maps" method="get" target="_blank">'
                 + '<input type="text" MAXLENGTH=40 name="saddr" id="saddr" value="" /><br>'
                 + '<INPUT value="Get Directions" TYPE="SUBMIT">' 
                 + '<input type="hidden" name="daddr" value="' + this.pois[i].address + '"/>';            
        }
        
        if (fromto == 'from') {
            htmlDirections = 'Directions: <a href="javascript:' + this.mapname + ".switchDirections(" + i + ", 'to')\" >to here</a> - <b>from here</b>"                        
                 + '<form action="http://maps.google.com/maps" method="get" target="_blank">'
                 + '<input type="text" MAXLENGTH=40 name="daddr" id="daddr" value="" /><br>'
                 + '<INPUT value="Get Directions" TYPE="SUBMIT">' 
                 + '<input type="hidden" name="saddr" value="' + this.pois[i].address + '"/>';
        }        

        if (this.tabbed)
            return "<div class=\"mapp-overlay-div\">" + htmlDirections + "</div>";                 
        else
            return "<div class=\"mapp-overlay-div\">" + htmlAddress + htmlDirections + "</div>";                 
    }
}