<?php
function diana_create_filename_tile($name_body, $x0, $x1, $y0, $y1, $binning) {  // , QUIET=quiet, JPG=jpg, IMG=img

// This returns the entire path to a tile file; e.g., 'data/tiles/3/X000.125_000.250/TC_EVE_02_X000.125......B0002.img'
// This routine should be used every time we create or read a tile. 
//
// HBT DIANA 25-Nov-2009
// Updated for MarsNameBW 11-Aug-2014
//
// Usually this will be JPG: they are smaller, and have the x/y dimensions encoded within them.
// IMG files are larger, have no explicit size (just list of bytes), but they do store 16 bits/pix, not 8.

  include('diana_init.php');

  $extension = ".jpg";		// This routine works only for JPGs, not for IMGs as well.

//   $str 	= string('X' , x0, '_', x1, '__Y', y0,  '_', y1,  '_B', binning, $
//                  format = '(A, F7.3,  A,  F7.3, A,   F7.3, A,  F7.3, A,   I5)')

// str_replace  ( mixed $search  , mixed $replace  , mixed $subject  [, int &$count  ] )

  $x0_str = str_replace(" ", "0", sprintf($x0));  // To print "3.5" and "3.75" with an auto-width, like I want, no args to sprintf
  $y0_str = str_replace(" ", "0", sprintf($y0));

  $x1_str = str_replace(" ", "0", sprintf("%f", $x1));
  $y1_str = str_replace(" ", "0", sprintf("%f", $y1));

//   print "y0_str = $y0_str OLD";
  $y0_str  = str_replace('0-', '-0', $y0_str);
  $y1_str  = str_replace('0-', '-0', $y1_str);
  $y0_str  = str_replace('_0-', '_-0', $y0_str);
  $y1_str  = str_replace('_0-', '_-0', $y1_str);
//   print "y0_str = $y0_str NEW";

  $x0_str  = str_replace('0-', '-0', $x0_str);
  $x1_str  = str_replace('0-', '-0', $x1_str);
  $x0_str  = str_replace('_0-', '_-0', $x0_str);
  $x1_str  = str_replace('_0-', '_-0', $x1_str);

  print "<br>x0_str = $x0_str; x1_str = $x1_str</br>";

// Trim trailing 0's on RHS. Except, ugh, we don't want to trim a *single* zero down to '0'
// 'rtrim' trims on rhs of string. It removes any whitespace, *plus* the character passed.
//
// Bug fixed 24-Nov-2015: rtrim() was being applied too aggressively, s.t. '30' was being truncated to '3', and thus
// the improper bin was being computed. Solved this by applying rtrim() only if there is a '.' found in the string.

  if (strpos($x1_str, ".")){ $x1_str = rtrim($x1_str, '0'); if (strlen($x1_str) == 0) { $x1_str = "0"; } }
  if (strpos($x0_str, ".")){ $x0_str = rtrim($x0_str, '0'); if (strlen($x0_str) == 0) { $x0_str = "0"; } }
  if (strpos($y1_str, ".")){ $y1_str = rtrim($y1_str, '0'); }
  if (strpos($y0_str, ".")){ $y0_str = rtrim($y0_str, '0'); }

  $x1_str = rtrim($x1_str, '.'); 
  $x0_str = rtrim($x0_str, '.'); 
  $y1_str = rtrim($y1_str, '.');
  $y0_str = rtrim($y0_str, '.');

  $bin_str1	= sprintf("%d", $binning);
  $bin_str2	= sprintf("%5d", $binning);
  $bin_str2	= str_replace(" ", "0", $bin_str2);

  $x_str 	= $x0_str . "_" . $x1_str;
  $y_str 	= $y0_str . "_" . $y1_str;
 

// Create the directory name ('/lat30_lon120'). 
// Careful: the latitude here is the latitude of the *bottom*, not the lat of the *top*, which is used in the filename
//
// ** This was the problem 12-Feb-2015. For some reason the modulo operator % works a bit funny in PHP -- different for negatives
// than positives. Thus the bin # was being computed improperly for negative $y0 (off-by-one). 

if ($y0 >= 0) { $bin_lat = ($y0) - (($y0) % 30);     } // Divide into bins of 30 deg
if ($y0 < 0)  { $bin_lat = ($y0) - (($y0) % 30) - 30;} // Divide into bins of 30 deg
$bin_lon = $x0 - ($x0 % 60);                           // Divide into bins of 60 deg

//   $bin_lat = $y0 - ($y0 % 30); // Divide into bins of 30 deg
//   $bin_lon = $x0 - ($x0 % 60); // Divide into bins of 60 deg
 
  $bin_lat_str = str_replace(" ", "0", sprintf("%2d", abs($bin_lat)));
  if ($bin_lat < 0) { 
    $bin_lat_str = "-" . $bin_lat_str;
  }

  $bin_lon_str = str_replace(" ", "0", sprintf("%3d", $bin_lon));

//   print "diana_create_filename_tile: dir_tiles = $dir_tiles";

  switch ($name_body) {
    case "Moon_SELENE":
// /Volumes/UwinguExternal/data/Moon_SELENE/tiles/4/X022.500_023.000/TC_EVE_02_X022.500_023.000__Y-03.000_-02.500_B00004.jpg
       $file = $dir_tiles . $bin_str1 . "/X" . $x_str . "/TC_EVE_02_X" . $x_str . 
         "__Y" . $y_str . "_B" . $bin_str2 . $extension;
        break;
    case "Mimas":
// /Volumes/UwinguExternal/data/Mimas/tiles/4/Mimas_X347.883_360.000__Y173.996_180.000_B00004.jpg
        $file = $dir_tiles . $bin_str1 .                "/Mimas_X" . $x_str . "__Y" . $y_str . "_B" . $bin_str2 . $extension;
        break;
    case "Mars_BW":	// BatWorks version of Mars Namer. I will have a new case for the LeafletJS tiles soon.

// For Mars_BW: Tiles are named "mars_merged_<scale>_<lat_top>_<lon_left>.png" .

// e.g., /Users/throop/Uwingu/Mars/tiles/jpg/lat30_lon120/mars_merged_8_60_174.jpg

        $file = $dir_tiles . "lat" . $bin_lat_str . "_lon" . $bin_lon_str . '/' . 
	  "mars_merged_" . $bin_str1 . "_" . $y1_str . "_" . $x0_str . ".jpg";
	break;
  }

  print "<br>diana_create_filename_tile: $file\n";

  return $file;
}

?>
