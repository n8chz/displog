<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">


<head>
<title>
<?php
echo $_FILES["gpslogfile"]["name"];
?>
</title>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<style type="text/css">
body {
 padding:2em;
}
html, body, #map {
 width: 610px;
 height: 377px;
 margin: 0;
}
p {
 text-align:center;
}
</style>
<?php
$logname=$_FILES["gpslogfile"]["tmp_name"]; # http://us3.php.net/manual/en/function.move-uploaded-file.php 
move_uploaded_file($logname,"./$logname");
$gpslog=fopen($logname,"r");
fgets($gpslog); # throw away header line
$minlat=90;
$maxlat=-90;
$minlong=180;
$maxlong=-180;
$coords="[";
$first=true;
while (!feof($gpslog)) {
 $datapoint=fgets($gpslog);
 if ($datapoint) {
  $data=explode(",",$datapoint);
  $lat=$data[1];
  $long=$data[2];
  if (!$first) $coords .= ", ";
  $first=false;
  $coords .= "[$long, $lat]";
  if ($lat<$minlat) $minlat=$lat;
  if ($lat>$maxlat) $maxlat=$lat;
  if ($long<$minlong) $minlong=$long;
  if ($long>$maxlong) $maxlong=$long;
 }
}
$coords .= "]";
# set $lat,$long to middle of range
# $lat=($minlat+$maxlat)/2.0;
# $long=($minlong+$maxlong)/2.0;
# echo "Center point of bounding box: ($lat,$long)";
# Use http://openlayers.org/dev/examples/geojson.html for display
fclose($logname);
echo <<<EOT
<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
<script type="text/JavaScript">

// This script largely copied from http://jsfiddle.net/_DR_/Pkcaf/

// (Yup, it's cargo cult code)

// All code belongs to the poster [Piotr and Oskar] and no license is enforced.

// [jsfiddle.net] are not responsible or liable for any loss or damage of any kind during the usage of provided code.

function init() {
 map = new OpenLayers.Map("map");
 var ol = new OpenLayers.Layer.OSM();

 var fromProjection = new OpenLayers.Projection("EPSG:4326");   // Transform from WGS 1984
 var toProjection   = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection

 var points=$coords.map(function (z) {return new OpenLayers.Geometry.Point(z[0], z[1])});
 // alert(JSON.stringify(points));
 var styleMap=new OpenLayers.StyleMap({"strokeColor": "#3b3640"});
 var vector = new OpenLayers.Layer.Vector("vector layer",{"styleMap": styleMap});
 // see http://docs.openlayers.org/library/feature_styling.html
 vector.addFeatures(
  [
   new OpenLayers.Feature.Vector(
    new OpenLayers.Geometry.LineString(points).transform(fromProjection, toProjection)
   )
  ]
 );
 map.addLayers([ol,vector]);

 /* specify map by bounds rather than center */
 var bounds=new OpenLayers.Bounds();
 bounds.extend(new OpenLayers.LonLat($minlong,$minlat).transform(fromProjection, toProjection));
 bounds.extend(new OpenLayers.LonLat($maxlong,$maxlat).transform(fromProjection, toProjection));
 bounds.toBBOX();
 map.zoomToExtent(bounds);
}

</script>
EOT;
?>
</head>
<body onload="init();">
<p>Map showing trajectory in GPS Logger file</p>
<p id="map" class="smallmap"></p>
</body>
</html>

