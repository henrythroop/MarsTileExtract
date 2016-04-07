<?php function diana_set_defaults(&$lon, &$lat, &$zoom) {

// Set default center position, etc. for DIANA
// Variables are passed by reference (not value), so that they are set and returned to caller.
//
// HBT DIANA 28-Dec-2009

  $lon_default	= 2;
  $lat_default	= 0;
  $zoom_default = 1;

  if (!isset($lon)) { $lon = $lon_default; }
  if (!isset($lat)) { $lat = $lat_default; }
  if (!isset($zoom)) { $zoom = $zoom_default; }

}

