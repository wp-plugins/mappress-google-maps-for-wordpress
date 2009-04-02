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

// Admin functions
function mappCheckAPI(api_missing, api_incompatible) {
    var message;
    var css;
    
    var api_key = document.getElementById('api_key');
    var api_message = document.getElementById('api_message');
    var api_block = document.getElementById('api_block');
    
    if (api_key.value == "") {
        api_block.className = 'api_error';
        api_message.innerHTML = api_missing;
        return;
    }

    if (typeof GBrowserIsCompatible == 'function' && GBrowserIsCompatible())
        return;

    api_block.className = 'api_error';
    api_message.innerHTML = api_incompatible;
}

function mappClearShortCode () {
    document.getElementById('mapp_width').value = '';
    document.getElementById('mapp_height').value = '';
    document.getElementById('mapp_zoom').value = '';
    document.getElementById("mapp_address").value = '';
    document.getElementById('mapp_comment').value = '';
}

function mappInsertShortCode ()
{
    var width = document.getElementById('mapp_width').value;
    var height = document.getElementById('mapp_height').value;
    var zoom = document.getElementById('mapp_zoom').value;
    var address = document.getElementById("mapp_address").value;
    var comment = document.getElementById('mapp_comment').value;
    
    var shortcode = '[mappress ';
    if (width)
        shortcode += 'width="' + width + '" ';
    if (height)
        shortcode += 'height="' + height + '" ';
    if (zoom)
        shortcode += 'zoom="' + zoom + '" ';
    
    shortcode += 'address="' + address;
    if (comment)
        shortcode += ' : ' + comment;    
    shortcode += '" ]';
    
    send_to_editor(shortcode);
    return false;
}
