<?php
/*
Plugin Name: Paul Google Maps Coordinates
Plugin URI: http://www.paolocantoni.com/google-maps
Description: This plugin is made to find the latitude and longitude draggin the marker
Author: Paolo Cantoni
Version: 1.0.1
Author URI: http://www.paolocantoni.com

Copyright 2007 Paolo Cantoni (email : paul@paolocantoni.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('GOOGLE_KEY', '');
define('LAT_DEFAULT', '46.214384');
define('LON_DEFAULT', '13.208816');

//carico il file di configurazione per aggiungere le azioni
if (!function_exists('add_action')) require_once('../../../wp-config.php');

//carico il file css
add_action('wp_head', 'paul_google_maps_coordinates_header');

function paul_google_maps_coordinates_header() {
  echo "\n<link rel='stylesheet' href='".get_option('siteurl')."/wp-content/plugins/paul_google_maps_coordinates/paul_google_maps_coordinates.css' type='text/css' media='screen' />\n";
  echo "<link rel='stylesheet' href='http://www.google.com/uds/css/gsearch.css' type='text/css' media='screen' />\n";
  echo "<link rel='stylesheet' href='http://www.google.com/uds/solutions/localsearch/gmlocalsearch.css' type='text/css' media='screen' />\n";
  echo "<script type='text/javascript' src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=".GOOGLE_KEY."'></script>\n";
  echo "<script type='text/javascript' src='http://www.google.com/uds/api?file=uds.js&amp;v=1.0'></script>\n";
  echo "<script type='text/javascript' src='http://www.google.com/uds/solutions/localsearch/gmlocalsearch.js'></script>\n";
}

//pagina
function paul_google_maps_coordinates() 
{
  if(isset($_POST['invia']))
  {
    $address = urlencode($_POST['indirizzo']." ".$_POST['cap']." ".$_POST['comune']." ".$_POST['provincia']." ".$_POST['regione']);
    $url = "http://maps.google.com/maps/geo?q=".$address."&output=csv&key=".GOOGLE_KEY;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    if (strstr($data,'200'))
    {
      $data = explode(",",$data);
      $precision = $data[1];
      $lat = $data[2];
      $lon = $data[3];
    }
  }
  else
  {
    $lat = LAT_DEFAULT;
    $lon = LON_DEFAULT;
  }
  echo "<div id='paul_google_maps_coordinates'>";

    echo "<div id='map'>";
      echo "<span id='loading'>Loading...</span>";
    echo "</div>";
    echo "<div id='message'>Lat: ".$lat."<br/>Lon: ".$lon."</div>";

    echo "<form action='".dirname(__FILE)."' method='post'>";
    echo "<p>";
      echo "<label>".__("Indirizzo","paul_google_maps_coordinates")."</label>";
      echo "<input type='text' name='indirizzo' value='".$_POST['indirizzo']."' size=40 />";
    echo "</p>";
    echo "<p>";
      echo "<label>".__("CAP","paul_google_maps_coordinates")."</label>";
      echo "<input type='text' name='cap' value='".$_POST['cap']."' size=5 />";
    echo "</p>";
    echo "<p>";
      echo "<label>".__("Comune","paul_google_maps_coordinates")."</label>";
      echo "<input type='text' name='comune' value='".$_POST['comune']."' size=40 />";
    echo "</p>";
    echo "<p>";
      echo "<label>".__("Provincia","paul_google_maps_coordinates")."</label>";
      echo "<input type='text' name='provincia' value='".$_POST['provincia']."' size=40 />";
    echo "</p>";
    echo "<p>";
      echo "<label>".__("Regione","paul_google_maps_coordinates")."</label>";
      echo "<input type='text' name='regione' value='".$_POST['regione']."' size=40 />";
    echo "</p>";
    echo "<br/><input type='submit' name='invia' id='invia' value='".__("Trova Coordinate","paul_google_maps_coordinates")."' />";
    echo "</form>";
  echo "</div>";

?>
<script type="text/javascript">

//<![CDATA[
  if (GBrowserIsCompatible()) 
  {
    var map = new GMap2(document.getElementById("map")); //creo la mappa
    map.addControl(new GLargeMapControl()); //aggiungo il controllo dello zoom
    //map.addControl(new GMapTypeControl()); //aggiungo il controllo del tipo di mappa
    var center = new GLatLng(<?php echo $lat.",".$lon; ?>); //creo il punto centrale della mappa
    map.setCenter(center, 14); //setto il punto centrale ed il livello di zoom
    map.addControl(new google.maps.LocalSearch(), new GControlPosition(G_ANCHOR_BOTTOM_RIGHT, new GSize(5,20))); //aggiungo e posiziono il campo di ricerca

    //creo l'icona di base e setto i vari parametri
    var baseIcon = new GIcon();
    baseIcon.shadow = "http://www.google.com/mapfiles/shadow50.png";
    baseIcon.iconSize = new GSize(20, 34);
    baseIcon.shadowSize = new GSize(37, 34);
    baseIcon.iconAnchor = new GPoint(9, 34);
    baseIcon.infoWindowAnchor = new GPoint(9, 2);
    baseIcon.infoShadowAnchor = new GPoint(18, 25);
    var letteredIcon = new GIcon(baseIcon);  
    letteredIcon.image = "http://www.google.com/mapfiles/markerP.png";  

    var marker = new GMarker(center, {draggable: true, icon: letteredIcon});//creo il marker draggabile
    GEvent.addListener(marker, "dragstart", function() { map.closeInfoWindow(); });
    GEvent.addListener(marker, "dragend", function() {
      latLon = this.getLatLng().toString().replace("(","").replace(")","").split(","); //recupero i valori di latitudine e longitudine e li separo 
      document.getElementById("message").innerHTML = "Lat: "+latLon[0]+"<br/>Lon: "+latLon[1]; //aggiorno il div con i dati di lat e lon
      marker.openInfoWindowHtml("Lat: "+latLon[0]+"<br/>Lon: "+latLon[1]); //creo il box informativo
    });
    GEvent.addListener(marker, "click", function() {
      latLon = this.getLatLng().toString().replace("(","").replace(")","").split(","); //recupero i valori di latitudine e longitudine e li separo 
      document.getElementById("message").innerHTML = "Lat: "+latLon[0]+"<br/>Lon: "+latLon[1]; //aggiorno il div con i dati di lat e lon
      marker.openInfoWindowHtml("Lat: "+latLon[0]+"<br/>Lon: "+latLon[1]); //creo il box informativo
    });
    map.addOverlay(marker); //inserisco il marker nella mappa*/
  }
  else {
    alert("Spiacenti, le Api di Google Maps non sono compatibili con questo browser.");
  }
//]]>
</script>
<?php
}

add_filter('the_content', 'paul_google_maps_coordinates_place', '7');
add_filter('the_excerpt', 'paul_google_maps_coordinates_place', '7');
function paul_google_maps_coordinates_place($content){
	if(!is_feed()) {
		$content = preg_replace("/\[paul_google_maps_coordinates\]/ie", "paul_google_maps_coordinates()",$content,1);
    return $content;
	}
}

?>
