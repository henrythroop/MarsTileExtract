<?php function diana_filename_tile_to_coords ($name_body, $file, &$x0, &$x1, &$y0, &$y1, &$binning, &$dx_pix, &$dy_pix) {

// Decodes a SELENE filename.
// 
//  file: A filename, such as 'data/tiles/8/X359.000_360.000/TC_EVE_02_X359.000_360.000__Y-01.000_000.000_B00008.jpg'
// 
// Required arguments:
//    x0, x1, y0, y1: Values of the four corners of the image, in degrees
//    binning: binning.  1, 2, 4... etc.
// 
// Optional arguments:
// 
//  dx_pix, dy_pix: The actual # of pixels of the file.  This is *computed* from the filename, not derived from the file itself.
// 
// 
// HBT DIANA 10-Dec-2009
//           29-Dec-2009 Rewritten in PHP.  Functions: strpos(haystack, needle), strlen(), substr( $string  , int $start  [, int $length  ] )
//           12-Aug-2014. Rewritten for BatWorks Mars engine.
//

// For Mars_BW: Tiles are named "mars_merged_<scale>_<lat_top>_<lon_left>.png" .

include("diana_init.php");
include('diana_init_' . $name_body . '.php');

$debug = true;

// Start by chopping off everything up to the final "/" -- that is, chop of all directories, etc.

  $pos_str = end(explode("/", $file));

     print "<br>\n";
     print "dfttc: file = $file<br>\n";
     print "dfttc: pos_str = $pos_str<br>\n";

  $pos_parts = explode("_", $pos_str);
  $x0_str = $pos_parts[3];		// Left
  $y1_str = $pos_parts[4];		// Top
  $binning = $pos_parts[2];

  $y0 = $y1_str - ($dy_tile_pix / $pix_per_deg / $binning);	// Bottom
  $y0_str = sprintf($y0);

  $x1 = $x0_str + ($dx_tile_pix / $pix_per_deg / $binning);	// Right
  $x1_str = sprintf($x1);


//   $x0_str	= substr($coordstr, 1, 7);
//   $x1_str	= substr($coordstr, 9, 7);		// OK as is: always 3-digit, positive
//   $y0_str	= substr($coordstr, 19, 7);
//   $y1_str	= substr($coordstr, 27, 7);		// OK as is: always 3-digit, positive

// Remove any leading zeros from the y dimensions ('Y0-0.125', etc.)

//   if (substr($y0_str, 0, 1) == "0") { $y0_str = substr($y0_str, 1); }
//   if (substr($y1_str, 0, 1) == "0") { $y1_str = substr($y1_str, 1); }

  $x0		= $x0_str * 1.;
  $x1		= $x1_str * 1.;
  $y0		= $y0_str * 1.;
  $y1		= $y1_str * 1.;

    print "dfttc: x0 = $x0<br>";
    print "dfttc: x1 = $x1<br>";
    print "dfttc: y0 = $y0<br>";
    print "dfttc: y1 = $y1<br>";
    print "dfttc: binning = $binning<br>";

//   $binning	= substr($coordstr, 36, 5);

  $dx_pix	= ($x1-$x0) * $pix_per_deg / $binning;
  $dy_pix	= ($y1-$y0) * $pix_per_deg / $binning;
//   print "dfttc: dx_pix = $dx_pix.<br>"; 

  if ($dx_pix < 0) { $dx_pix += 360. * $pix_per_deg;}
  if ($dy_pix < 0) { $dy_pix += 360. * $pix_per_deg;}

}


