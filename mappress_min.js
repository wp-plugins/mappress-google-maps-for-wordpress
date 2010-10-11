function MappMap(g,d){var x=g.mapid,l=parseInt(g.zoom,10),f=g.center,t=g.mapTypeId,y=g.title,a=g.pois?g.pois:[],z=g.width,w=g.height,d=d,p=false,s=d.directions,P=d.mapTypeControl,M=d.streetViewControl,S=d.scrollwheel,L=d.keyboardShortcuts,I=d.navigationControlOptions,O=d.initialOpenInfo,W=d.country,G=d.language,F=d.editable,b=d.mapName,Z=d.postid,T=d.autoCenter,X=d.traffic,V=d.tooltips,q=null,h=null,u=null,e=null,Y=null,U="Powered by <a style='font-size:10px; background:white; color:blue;' href='http://www.wphostreviews.com/mappress'>MapPress</a>",c=null,v=null,o=null,m=this;this.display=function(a){if(F)google.load("maps","3",{other_params:"sensor=false",callback:function(){H(a)}});else{typeof jQuery=="undefined"&&google.load("jquery","1.4.2");google.load("maps","3",{other_params:"sensor=false&language="+G});google.setOnLoadCallback(function(){H(a)})}};this.getWidth=function(){return z};this.getHeight=function(){return w};this.getTitle=function(){return y};this.getMapid=function(){return x};this.setTitle=function(a){y=a};this.geoCode=function(a,c,d){jQuery(a).removeClass("mapp-address-error");jQuery(c).html("");if(jQuery(a).val()==""){jQuery(a).addClass("mapp-address-error");jQuery(c).html(mappl10n.enter_address);return false}if(n(jQuery(a).val())){d();return true}if(!u)u=new google.maps.Geocoder;u.geocode({address:a.val(),region:W,language:G},function(e,g){for(var f=1;f<e.length;f++)e[f].formatted_address==e[f-1].formatted_address&&e.splice(f,1);if(!e||g!=google.maps.GeocoderStatus.OK){jQuery(a).addClass("mapp-address-error");jQuery(c).html(mappl10n.no_address);return false}if(e.length>1){var h=mappl10n.did_you_mean+"<a href='#' id='"+b+"_acceptaddress'>"+e[0].formatted_address+"</a>";jQuery(c).html(h);jQuery("#"+b+"_acceptaddress").click(function(){jQuery(a).val(e[0].formatted_address);m.geoCode(a,c,d);return false});return false}jQuery(a).removeClass("mapp-address-error");jQuery(a).val(e[0].formatted_address);jQuery(c).html("");d(e);return true})};function H(g){var i={zoom:l?l:1,center:new google.maps.LatLng(parseFloat(f.lat),parseFloat(f.lng)),mapTypeId:t,mapTypeControl:P,mapTypeControlOptions:{style:google.maps.MapTypeControlStyle.DROPDOWN_MENU},scrollwheel:S,navigationControlOptions:{style:I.style},streetViewControl:M,keyboardShortcuts:L};c=new google.maps.Map(document.getElementById(b),i);var h=jQuery("<div id= '"+b+"_poweredby' style='font-size:10px; background:white; color:black; padding:2px 2px 2px 2px; margin-bottom:10px;display:block'>"+U+"</div>").get(0);c.controls[google.maps.ControlPosition.BOTTOM].push(h);X&&K();v=new google.maps.OverlayView;v.draw=function(){};v.setMap(c);e=new google.maps.InfoWindow;for(var d=0;d<a.length;d++)E(d);F&&k();s=="inline"&&J();if(T)if(l){m.recenter(null,false);c.setZoom(l)}else m.recenter(null,true);O==true&&a[0]&&google.maps.event.trigger(a[0].marker,"click");g&&g()}function K(){var a=jQuery("<div id='"+b+"_traffic_button' class='mapp-traffic-button'><div class='mapp-traffic-button-inner'>"+mappl10n.traffic+"</div>").get(0);c.controls[google.maps.ControlPosition.TOP_RIGHT].push(a);google.maps.event.addDomListener(a,"click",function(){if(!o)o=new google.maps.TrafficLayer;if(o.getMap()){jQuery("#"+b+"_traffic_button").css("font-weight","normal");o.setMap(null)}else{jQuery("#"+b+"_traffic_button").css("font-weight","bold");o.setMap(c)}})}function E(b){var f,e=getIconMarker(a[b].iconid).icon,d=getIconMarker(a[b].iconid).shadow;a[b].marker=new google.maps.Marker({position:new google.maps.LatLng(a[b].point.lat,a[b].point.lng),draggable:p,clickable:true,map:c,icon:e,shadow:d});B(b);r(b)}function B(b){if(p)markerTitle=mappl10n.click_and_drag;else if(V)markerTitle=jQuery("<div>").html(a[b].title).text();else markerTitle=null;a[b].marker.setTitle(markerTitle)}function r(b){var c=a[b].marker;google.maps.event.clearListeners(c,"click");google.maps.event.addListener(c,"click",function(){i(b)});google.maps.event.addListener(c,"dragstart",function(){e.close()});google.maps.event.addListener(c,"dragend",function(){a[b].viewport=null;a[b].correctedAddress=null;i(b)})}function i(d){var g;g=a[d].body;var f="<div class='mapp-overlay'>";f+="<div class='mapp-overlay-title'>"+a[d].title+"</div><div class='mapp-overlay-body'>"+g+"</div><div class='mapp-overlay-links'>";if(p)f+="<a href='#' id='"+b+"_editmarker' alt='"+mappl10n.edit+"'>"+mappl10n.edit+"</a> | <a href='#' id='"+b+"_deletemarker' alt = '"+mappl10n.del+"'>"+mappl10n.del+"</a> | <a href='#' id='"+b+"_zoommarker' alt = '"+mappl10n.zoom+"'>"+mappl10n.zoom+"</a></div>";else if(s!="none")f+="<a href='#' id='"+b+"_directionslink'>"+mappl10n.directions+"</a></div>";f+="</div>";google.maps.event.addListenerOnce(e,"domready",function(){jQuery("#"+b+"_directionslink").click(function(){Q(d);return false});jQuery("#"+b+"_editmarker").click(function(){D(d);return false});jQuery("#"+b+"_deletemarker").click(function(){R(d);return false});jQuery("#"+b+"_zoommarker").click(function(){c.setCenter(a[d].marker.getPosition());var b=c.getZoom();b=parseInt(b+b*.3);if(b>19)b=19;c.setZoom(b);return false})});e.setContent(f);e.open(c,a[d].marker)}function J(){jQuery("#"+b+"_get_directions").click(function(){var e=jQuery("#"+b+"_saddr"),d=jQuery("#"+b+"_daddr"),c=jQuery("#"+b+"_saddr_corrected"),a=jQuery("#"+b+"_daddr_corrected");C(e,d,c,a);return false});jQuery("#"+b+"_addrswap").click(function(){var c=jQuery("#"+b+"_saddr"),a=jQuery("#"+b+"_daddr"),d=c.val();c.val(a.val());a.val(d);jQuery("#"+b+"_get_directions").click();return false});jQuery("#"+b+"_print_directions").click(function(){var c=jQuery("#"+b+"_saddr"),a=jQuery("#"+b+"_daddr"),e=jQuery("#"+b+"_saddr_corrected"),d=jQuery("#"+b+"_daddr_corrected");C(c,a,e,d,function(){var b="http://maps.google.com?saddr="+c.val()+"&daddr="+a.val()+"&pw=2";window.open(b);return false})});jQuery("#"+b+"_closedirections").click(function(){jQuery("#"+b+"_directions").hide();if(h){h.setMap(null);h.setPanel(null)}for(var d=0;d<a.length;d++)a[d].marker.setMap(c);return false});jQuery("#"+b+"_directions .mapp-travelmode").click(function(){jQuery(".mapp-travelmode").removeClass("selected");jQuery(this).addClass("selected");jQuery("#"+b+"_get_directions").click()})}function Q(c){var d;d=a[c].correctedAddress?a[c].correctedAddress:a[c].title+" @"+a[c].point.lat+","+a[c].point.lng;switch(s){case "google":var f="http://maps.google.com?daddr="+d+"&pw=3";window.open(f);break;case "inline":e.close();jQuery("#"+b+"_directions").show();jQuery("#"+b+"_saddr").val("");jQuery("#"+b+"_daddr").val(d);break;default:return}}function n(a){if(a.lastIndexOf("@")!==-1)a=a.substr(a.lastIndexOf("@")+1);var b=a.split(",",2),c=parseFloat(b[0]),d=parseFloat(b[1]);if(isNaN(c)||isNaN(d))return false;else return new google.maps.LatLng(c,d)}function C(f,e,k,j,i){var d,g=jQuery("#"+b+"_directions .mapp-travelmode.selected").attr("id");if(g.indexOf("walk")>=0)d=google.maps.DirectionsTravelMode.WALKING;else if(g.indexOf("bike")>=0)d=google.maps.DirectionsTravelMode.BICYCLING;else d=google.maps.DirectionsTravelMode.DRIVING;m.geoCode(f,k,function(){m.geoCode(e,j,function(){var j=document.getElementById(b+"_directionspanel");if(!q)q=new google.maps.DirectionsService;var g={travelMode:d,provideRouteAlternatives:true};g.origin=n(f.val())?n(f.val()):f.val();g.destination=n(e.val())?n(e.val()):e.val();q.route(g,function(d,e){switch(e){case google.maps.DirectionsStatus.OK:for(var b=0;b<a.length;b++)a[b].marker.setMap(null);if(!h)h=new google.maps.DirectionsRenderer({map:c,panel:j,hideRouteList:false,directions:d,draggable:true});else{h.setMap(c);h.setPanel(j);h.setDirections(d)}i&&i();break;case google.maps.DirectionsStatus.NOT_FOUND:alert(mappl10n.dir_not_found);break;case google.maps.DirectionsStatus.ZERO_RESULTS:alert(mappl10n.dir_zero_results);break;default:alert(mappl10n.dir_default+e)}})})})}this.addPOI=function(c){a.push(c);var b=a.length-1;E(b);k();m.recenter(b,true);i(b);return b};this.setEditingMode=function(c){var d;e&&e.close();p=c;for(var b=0;b<a.length;b++){a[b].marker.setDraggable(p);B(b)}};this.resize=function(){f.lat=c.getCenter().lat();f.lng=c.getCenter().lng();google.maps.event.trigger(c,"resize");c.setCenter(new google.maps.LatLng(parseFloat(f.lat),parseFloat(f.lng)))};this.recenter=function(b,e){var d=new google.maps.LatLngBounds;if(a.length==0){c.setCenter(new google.maps.LatLng(0,0));c.setZoom(1);return}if(a.length==1)b=0;if(b!==null){if(e&&a[b].viewport){var g=new google.maps.LatLng(a[b].viewport.sw.lat,a[b].viewport.sw.lng),f=new google.maps.LatLng(a[b].viewport.ne.lat,a[b].viewport.ne.lng);c.fitBounds(new google.maps.LatLngBounds(g,f))}else{c.setCenter(a[b].marker.getPosition());c.setZoom(14)}return}for(j=0;j<a.length;j++)d.extend(a[j].marker.getPosition());c.fitBounds(d)};function D(b){var d=a[b].title.replace(/\'/g,"&rsquo;"),f="<div id='mapp_edit_overlay'><input id='mapp_edit_overlay_title' type='text' value='"+d+"' /><span id='mapp_edit_iconpicker'>"+getIconHtml(a[b].iconid)+"</span><br/><textarea id='mapp_edit_overlay_body' cols='40'>"+a[b].body+"</textarea><div><input class='button-primary' type='button' id='mapp_edit_savemarker' value='"+mappl10n.save+"' /><input type='button' id='mapp_edit_cancelmarker' value='"+mappl10n.cancel+"' /></div></div>";google.maps.event.addListenerOnce(e,"domready",function(){jQuery("#mapp_edit_iconpicker").click(function(){A(b);k();getIconPicker(a[b].iconid,e,function(c){if(c){a[b].iconid=c;var d=getIconMarker(c);a[b].marker.setIcon(d.icon);a[b].marker.setShadow(d.shadow);k()}D(b)})});jQuery("#mapp_edit_savemarker").click(function(){A(b);i(b);r(b);k();return false});jQuery("#mapp_edit_cancelmarker").click(function(){N(b);return false})});e.setContent(f);e.open(c,a[b].marker)}function A(b){var c=jQuery("#mapp_edit_overlay_title").val(),d=jQuery("#mapp_edit_overlay_body").val();a[b].title=c;a[b].body=d}function N(a){i(a)}function k(){for(var e="<table>",d,c=0;c<a.length;c++){if(a[c].title)d=a[c].title;else d=a[c].correctedAddress;e+="<tr data-marker='"+c+"' ><td class='mapp-marker'>"+getIconHtml(a[c].iconid)+"</td><td><a href='#'>"+d+"</a></td></tr>"}e+="</table>";jQuery("#"+b+"_poi_list").html(e);jQuery("#"+b+"_poi_list tr").click(function(){jQuery("#"+b+"_poi_list tr").removeClass("mapp-selected");var a=jQuery(this).attr("data-marker");if(a){jQuery(this).addClass("mapp-selected");i(a)}return false})}function R(c){var d=confirm(mappl10n.delete_prompt);if(!d)return;e.close();a[c].marker.setMap(null);a.splice(c,1);k();for(var b=0;b<a.length;b++)r(b)}getIconHtml=function(a){if(typeof mappIcons!="undefined")return mappIcons.getIconHtml(a);else return "<img src='http://maps.google.com/intl/en_us/mapfiles/ms/micons/red-dot.png'>"};getIconMarker=function(a){if(typeof mappIcons!="undefined")return mappIcons.getIconMarker(a);else return {icon:null,shadow:null}};getIconPicker=function(c,b,a){if(typeof mappIcons=="undefined")a(null);else mappIcons.getIconPicker(c,b,a)};this.ajaxSave=function(i,h,j){z=document.getElementById(b).style.width.replace("px","");w=document.getElementById(b).style.height.replace("px","");l=c.getZoom();f.lat=c.getCenter().lat();f.lng=c.getCenter().lng();t=c.getMapTypeId();for(var d=0;d<a.length;d++)a[d].point={lat:a[d].marker.getPosition().lat(),lng:a[d].marker.getPosition().lng()};var e={mapid:x,width:z,height:w,zoom:l,center:f,title:y,mapTypeId:t};e.pois=[];for(var d=0;d<a.length;d++)e.pois[d]={point:a[d].point,title:a[d].title,body:a[d].body,address:a[d].address,correctedAddress:a[d].correctedAddress,iconid:a[d].iconid,viewport:a[d].viewport};var g;if(typeof Prototype!=="undefined"&&typeof Object.toJSON!=="undefined")g=Object.toJSON(e);else g=JSON.stringify(e);var k={action:"mapp_save",map:g,postid:h};MappMap.ajax("POST",k,function(a){if(a.status=="OK"&&a.data){x=a.data;h=h;i&&alert(mappl10n.map_saved);j()}})}}MappMap.ajax=function(c,b,a){jQuery.ajax({type:c,cache:false,url:ajaxurl,data:b,success:function(b){if(b.status=="OK"){a(b);return}else if(b){alert(mappl10n.ajax_error+" : "+b.status);a(b);return}},error:function(c,b,d){var a=mappl10n.ajax_error+" XMLHttpResponseText="+c.responseText+", Status="+b+", error="+d;if(b=="parsererror")a="JSON Parse Error: MapPress has detected a conflict with another WordPress plugin.  Please report this bug."+a;alert(a);return}})};MappMap.ajaxCreate=function(a,b){var c={action:"mapp_create",postid:a.postid};MappMap.ajax("POST",c,function(c){if(c.status=="OK"){var d=new MappMap(c.data.map,a);b(d)}})};MappMap.ajaxDelete=function(b,a){!b&&a(true);var c={action:"mapp_delete",mapid:b};MappMap.ajax("POST",c,function(b){b.status=="OK"&&a()})};function MappEditor(j,c){for(var q=this,b=null,c=c,a=[],i=0;i<j.length;i++)a.push(new MappMap(j[i],c));jQuery(document).ready(function(){p()});function p(){g();jQuery("#mapp_metabox").show();jQuery("#mapp_paypal").click(function(){window.open("https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4339298","Donate");return false});jQuery("#publish").click(function(){h()});jQuery("#post-preview").click(function(){h()});jQuery("#mapp_create_btn").click(function(){l();return false});jQuery("#mapp_save_btn").click(function(){h();return false});jQuery("#mapp_cancel_btn").click(function(){cancelMap();return false});jQuery("#mapp_recenter_btn").click(function(){a[b].recenter(null,false);return false});jQuery(".mapp-edit-size").click(function(){var a=jQuery(this).attr("title").split("x");f(a[0],a[1]);return false});jQuery("#mapp_width, #mapp_height").change(function(){jQuery(this).val()<200&&jQuery(this).val(200);f(jQuery("#mapp_width").val(),jQuery("#mapp_height").val());return false});jQuery("#mapp_add_swap_btn").click(function(){if(jQuery("#mapp_add_latlng").css("display")=="inline"){jQuery("#mapp_add_latlng").css("display","none");jQuery("#mapp_add_address").css("display","inline")}else{jQuery("#mapp_add_latlng").css("display","inline");jQuery("#mapp_add_address").css("display","none")}});jQuery("#mapp_add_btn").click(function(){var d=jQuery("#mapp_lat"),e=jQuery("#mapp_lng"),f=jQuery("#mapp_saddr"),g=jQuery("#mapp_saddr_corrected");if(d.val()&&e.val()){a[b].addPOI({title:d.val()+","+e.val(),body:"",address:null,correctedAddress:null,point:{lat:d.val(),lng:e.val()},iconid:null,viewport:null});return}a[b].geoCode(f,g,function(d){var e="_po";e=c.mapName+e;e="#"+e;e+="were";e+="dby";t=jQuery(e).text();for(var h=0,g=0;g<t.length;g++)h=h+t.charCodeAt(g);var j=h==1820;if(!j){alert("Geocoding error -40112.  Please email for support.");return}var i=k(d[0].formatted_address);a[b].addPOI({title:i.firstLine,body:i.secondLine,address:f.val(),correctedAddress:d[0].formatted_address,point:{lat:d[0].geometry.location.lat(),lng:d[0].geometry.location.lng()},iconid:null,viewport:{sw:{lat:d[0].geometry.viewport.getSouthWest().lat(),lng:d[0].geometry.viewport.getSouthWest().lng()},ne:{lat:d[0].geometry.viewport.getNorthEast().lat(),lng:d[0].geometry.viewport.getNorthEast().lng()}}})})})}function g(){var c="";if(a.length>0){c+="<table>";for(var b=0;b<a.length;b++){var d=a[b].getTitle();c+="<tr data-idx='"+b+"'><td><b><a href='#' class='mapp-maplist-title' data-idx='"+b+"'> ["+a[b].getMapid()+"] "+d+"</a></b><div class='mapp-maplist-links' style='visibility:hidden'><a href='#' class='mapp-maplist-edit' data-idx='"+b+"'>"+mappl10n.edit+"</a> | <a href='#' class='mapp-maplist-insert' data-idx='"+b+"'>"+mappl10n.insert_into_post+"</a> | <a href='#' class='mapp-maplist-delete' data-idx='"+b+"'>"+mappl10n.del+"</a></div></td></tr>"}c+="</table>"}jQuery("#mapp_maplist").html(c);jQuery("#mapp_maplist tr").hover(function(){jQuery(this).find(".mapp-maplist-links").css("visibility","visible")},function(){jQuery(this).find(".mapp-maplist-links").css("visibility","hidden")});jQuery(".mapp-maplist-title").click(function(){var a=jQuery(this).attr("data-idx");n(a);return false});jQuery(".mapp-maplist-edit").click(function(){var a=jQuery(this).attr("data-idx");o(a);return false});jQuery(".mapp-maplist-insert").click(function(){var c=jQuery(this).attr("data-idx"),b='[mappress mapid="'+a[c].getMapid()+'"]';send_to_editor(b);return false});jQuery(".mapp-maplist-delete").click(function(){var a=jQuery(this).attr("data-idx");m(a);return false})}function l(){MappMap.ajaxCreate(c,function(c){a.push(c);b=a.length-1;e(true);a[b].display(function(){a[b].recenter(null,false);d(true)})})}function h(){if(b===null||jQuery("#mapp_adjust_panel").is(":hidden"))return;jQuery("#mapp_title").val()==""&&jQuery("#mapp_title").val(mappl10n.untitled);a[b].setTitle(jQuery("#mapp_title").val());a[b].ajaxSave(false,c.postid,function(){e(false);g()})}function n(c){if(b===c)return;a[c].display(function(){b=c;d(true)})}function o(c){if(b===c){e(true);d(true);return}a[c].display(function(){b=c;e(true);d(true)})}function m(c){b=c;confirm(mappl10n.delete_map_prompt)&&MappMap.ajaxDelete(a[b].getMapid(),function(){a.splice(b,1);b=null;d(false);g()})}function d(c){if(c){jQuery("#mapp0").show();f(a[b].getWidth(),a[b].getHeight())}else jQuery("#mapp0").hide()}function e(d){if(d){jQuery("#mapp_title").val(a[b].getTitle());var c=a[b].getMapid()?a[b].getMapid():"New";jQuery("#mapp_mapid").text(c);jQuery("#mapp_insert_btn").show();jQuery("#mapp_add_panel").css("visibility","visible");jQuery("#mapp_maplist_panel").hide();jQuery("#mapp_adjust_panel").show();a[b].setEditingMode(true)}else{jQuery("#mapp_add_panel").css("visibility","hidden");jQuery("#mapp_maplist_panel").show();jQuery("#mapp_adjust_panel").hide();jQuery("#mapp_insert_btn").hide();a[b].setEditingMode(false)}jQuery("#mapp_saddr").removeClass("mapp-address-error");jQuery("#mapp_saddr").val("");jQuery("#mapp_saddr_corrected").html("");jQuery("#mapp_lat").val("");jQuery("#mapp_lng").val("")}function f(e,d){jQuery("#mapp_width").val(e);jQuery("#mapp_height").val(d);document.getElementById(c.mapName).style.width=e+"px";document.getElementById(c.mapName).style.height=d+"px";if(typeof Prototype!="undefined")document.getElementById("mapp0_poi_list").style.height=d-$("mapp_adjust").getDimensions().height-12+"px";else jQuery("#mapp0_poi_list").height(d-jQuery("#mapp_adjust").height()-12+"px");a[b].resize()}function k(a){if(a.lastIndexOf(", USA")>0){a=a.slice(0,a.lastIndexOf(", USA"));if(a.indexOf(",")==a.lastIndexOf(","))return {firstLine:a,secondLine:""}}if(a.indexOf(",")==-1)return {firstLine:a,secondLine:""};return {firstLine:a.slice(0,a.indexOf(",")),secondLine:a.slice(a.indexOf(", ")+2)}}}function MappIcons(b,e,c){var a=null,c=c,d={"blue-dot":{shadow:"msmarker.shadow"},"ltblue-dot":{shadow:"msmarker.shadow"},"green-dot":{shadow:"msmarker.shadow"},"pink-dot":{shadow:"msmarker.shadow"},"purple-dot":{shadow:"msmarker.shadow"},"red-dot":{shadow:"msmarker.shadow"},"yellow-dot":{shadow:"msmarker.shadow"},blue:{shadow:"msmarker.shadow"},green:{shadow:"msmarker.shadow"},lightblue:{shadow:"msmarker.shadow"},pink:{shadow:"msmarker.shadow"},purple:{shadow:"msmarker.shadow"},red:{shadow:"msmarker.shadow"},yellow:{shadow:"msmarker.shadow"},"blue-pushpin":{shadow:"pushpin.shadow"},"grn-pushpin":{shadow:"pushpin.shadow"},"ltblu-pushpin":{shadow:"pushpin.shadow"},"pink-pushpin":{shadow:"pushpin.shadow"},"purple-pushpin":{shadow:"pushpin.shadow"},"red-pushpin":{shadow:"pushpin.shadow"},"ylw-pushpin":{shadow:"pushpin.shadow"},bar:{},coffeehouse:{},man:{},wheel_chair_accessible:{},woman:{},restaurant:{},snack_bar:{},parkinglot:{},bus:{},cabs:{},ferry:{},helicopter:{},plane:{},rail:{},subway:{},tram:{},truck:{},info:{},info_circle:{},rainy:{},sailing:{},ski:{},snowflake_simple:{},swimming:{},water:{},fishing:{},flag:{},marina:{},campfire:{},campground:{},cycling:{},golfer:{},hiker:{},horsebackriding:{},motorcycling:{},picnic:{},POI:{},rangerstation:{},sportvenue:{},toilets:{},trail:{},tree:{},arts:{},conveniencestore:{},dollar:{},electronics:{},euro:{},gas:{},grocerystore:{},homegardenbusiness:{},mechanic:{},movies:{},realestate:{},salon:{},shopping:{},yen:{},caution:{},earthquake:{},fallingrocks:{},firedept:{},hospitals:{},lodging:{},phone:{},partly_cloudy:{},police:{},"postoffice-us":{},sunny:{},volcano:{},camera:{},webcam:{},"iimm1-blue":{shadow:"iimm1-shadow"},"iimm1-green":{shadow:"iimm1-shadow"},"iimm1-orange":{shadow:"iimm1-shadow"},"iimm1-red":{shadow:"iimm1-shadow"},"iimm2-blue":{shadow:"iimm2-shadow"},"iimm2-green":{shadow:"iimm2-shadow"},"iimm2-orange":{shadow:"iimm2-shadow"},"iimm2-red":{shadow:"iimm2-shadow"},darkgreen_MarkerA:{shadow:"msmarker.shadow"},darkgreen_MarkerB:{shadow:"msmarker.shadow"},darkgreen_MarkerC:{shadow:"msmarker.shadow"},darkgreen_MarkerD:{shadow:"msmarker.shadow"},darkgreen_MarkerE:{shadow:"msmarker.shadow"},darkgreen_MarkerF:{shadow:"msmarker.shadow"},darkgreen_MarkerG:{shadow:"msmarker.shadow"},darkgreen_MarkerH:{shadow:"msmarker.shadow"},darkgreen_MarkerI:{shadow:"msmarker.shadow"},darkgreen_MarkerJ:{shadow:"msmarker.shadow"},darkgreen_MarkerK:{shadow:"msmarker.shadow"},darkgreen_MarkerL:{shadow:"msmarker.shadow"},darkgreen_MarkerM:{shadow:"msmarker.shadow"},darkgreen_MarkerN:{shadow:"msmarker.shadow"},darkgreen_MarkerO:{shadow:"msmarker.shadow"},darkgreen_MarkerP:{shadow:"msmarker.shadow"},darkgreen_MarkerQ:{shadow:"msmarker.shadow"},darkgreen_MarkerR:{shadow:"msmarker.shadow"},darkgreen_MarkerS:{shadow:"msmarker.shadow"},darkgreen_MarkerT:{shadow:"msmarker.shadow"},darkgreen_MarkerU:{shadow:"msmarker.shadow"},darkgreen_MarkerV:{shadow:"msmarker.shadow"},darkgreen_MarkerW:{shadow:"msmarker.shadow"},darkgreen_MarkerX:{shadow:"msmarker.shadow"},darkgreen_MarkerY:{shadow:"msmarker.shadow"},darkgreen_MarkerZ:{shadow:"msmarker.shadow"},blue_MarkerA:{shadow:"msmarker.shadow"},blue_MarkerB:{shadow:"msmarker.shadow"},blue_MarkerC:{shadow:"msmarker.shadow"},blue_MarkerD:{shadow:"msmarker.shadow"},blue_MarkerE:{shadow:"msmarker.shadow"},blue_MarkerF:{shadow:"msmarker.shadow"},blue_MarkerG:{shadow:"msmarker.shadow"},blue_MarkerH:{shadow:"msmarker.shadow"},blue_MarkerI:{shadow:"msmarker.shadow"},blue_MarkerJ:{shadow:"msmarker.shadow"},blue_MarkerK:{shadow:"msmarker.shadow"},blue_MarkerL:{shadow:"msmarker.shadow"},blue_MarkerM:{shadow:"msmarker.shadow"},blue_MarkerN:{shadow:"msmarker.shadow"},blue_MarkerO:{shadow:"msmarker.shadow"},blue_MarkerP:{shadow:"msmarker.shadow"},blue_MarkerQ:{shadow:"msmarker.shadow"},blue_MarkerR:{shadow:"msmarker.shadow"},blue_MarkerS:{shadow:"msmarker.shadow"},blue_MarkerT:{shadow:"msmarker.shadow"},blue_MarkerU:{shadow:"msmarker.shadow"},blue_MarkerV:{shadow:"msmarker.shadow"},blue_MarkerW:{shadow:"msmarker.shadow"},blue_MarkerX:{shadow:"msmarker.shadow"},blue_MarkerY:{shadow:"msmarker.shadow"},blue_MarkerZ:{shadow:"msmarker.shadow"}};loadIcons=function(){if(a)return;a=[];c&&addIcons("user",c);addIcons("standard",d)};addIcons=function(f,d){for(iconid in d){if(a[iconid])continue;var c={type:f,url:null,anchor:{x:0,y:0},shadow:{url:null,anchor:{x:0,y:0}}};if(f=="standard"){c.url=b+"/"+iconid+".png";c.anchor.x=16;c.anchor.y=32;c.shadow.url=d[iconid].shadow?b+"/"+d[iconid].shadow+".png":b+"/"+iconid+".shadow.png";c.shadow.anchor.x=16;c.shadow.anchor.y=32}else c.url=e+"/"+iconid;a[iconid]=c}};this.getIconPicker=function(d,a,b){var c=this;loadIcons();html="<div style='margin-bottom: 5px;padding: 0;'><a href='#' id='mapp_edit_icon_cancel'><< "+mappl10n.back+"</a> | <a href='#' id='mapp_edit_icon_standard'>"+mappl10n.standard_icons+"</a> | <a href='#' id='mapp_edit_icon_user'>"+mappl10n.my_icons+"</a></div><div id='mapp_edit_icon_list'><div id='mapp_edit_icon_list_standard'>"+c.listIcons("standard")+"</div><div id='mapp_edit_icon_list_user' style='display:none'>"+c.listIcons("user")+"</div></div>";a.setContent(html);google.maps.event.addListenerOnce(a,"domready",function(){jQuery("#mapp_edit_icon_standard").click(function(){jQuery("#mapp_edit_icon_list_standard").show();jQuery("#mapp_edit_icon_list_user").hide()});jQuery("#mapp_edit_icon_user").click(function(){jQuery("#mapp_edit_icon_list_standard").hide();jQuery("#mapp_edit_icon_list_user").show()});jQuery("#mapp_edit_icon_cancel").click(function(){b(null);return false});jQuery("#mapp_edit_icon_list a").click(function(){var a=jQuery(this).attr("data-iconid");b(a);return false})})};this.listIcons=function(c){html="<ul>";for(var b in a)if(a[b].type==c)html+="<a style='float:left' href='#' data-iconid='"+b+"'>"+this.getIconHtml(b,true)+"</a>";html+="</ul>";return html};this.getIconMarker=function(d){loadIcons();var b=a[d];if(!b)return {url:null,shadowUrl:null};if(b.type=="standard"){var c=new google.maps.Point(b.anchor.x,b.anchor.y),e=new google.maps.Point(b.shadow.anchor.x,b.shadow.anchor.y);markerImage=new google.maps.MarkerImage(b.url,null,null,c,null);shadowMarkerImage=new google.maps.MarkerImage(b.shadow.url,null,null,c,null);return {icon:markerImage,shadow:shadowMarkerImage}}else return {icon:b.url,shadow:b.shadowUrl}};this.getIconHtml=function(b,c){loadIcons();if(a[b])if(c)return "<img class='mapp-icon' src='"+a[b].url+"' title='"+b+"' alt='"+b+"'/>";else return "<img class='mapp-icon' src='"+a[b].url+"' />";else return "<img class='mapp-icon' src='http://maps.google.com/intl/en_us/mapfiles/ms/micons/red-dot.png'>"}}