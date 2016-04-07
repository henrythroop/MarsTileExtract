<?php
error_reporting( -1 );
ini_set( 'display_errors', 1 );

require_once('mapping/uwingu-mars-tile-extract.class.php');
$marstile = new MarsTileExtract;

  # This is just a skeleton 'test' routine to call diana_load_tiles.
  # That is the routine to extract an arbitrary portion of the map, and highlight a single crater on it.
  # The idea is that this routine is a far more direct way of making the image than using 
  # PhantomJS to generate it by simulating a browser instance.

  # What do we want for the certificate? Most likely:
  #  Fixed height (e.g., 500 pixels)
  #  Fixed width (e.g., 700 pixels)
  #  Inputs: lon, lat, diameter, name
  #
  # My code must calculate appropriate zoom level, and borders of the box.
  #
  # HBT 14-Aug-2014.




/**************************************************************************************
INPUT VALUES.
Set these appropriately to tell the code which crater to plot.
These six values are the input to the routine.
**************************************************************************************/
  /**--------------------------------------------------------------------------------
  Test Crater from Henry's code - this one works
  --------------------------------------------------------------------------------**/
  $lon_crater = 163.10;     // Degrees East. Wikipedia lists West.
  $lat_crater = 30.91;      // Degrees North
  $diam_crater = 90.2;      // Diameter in km
  $name_crater = 'Addams';   // Name to label crater with

  /**--------------------------------------------------------------------------------
  Unnamed Small Crater near "Adams" - this one works
  --------------------------------------------------------------------------------**/
//   $lon_crater = 162.45;     // Degrees East. Wikipedia lists West.
//   $lat_crater = 29.43;      // Degrees North
//   $diam_crater = 3.60;      // Diameter in km
//   $name_crater = 'Unnamed Small Near Adams';   // Name to label crater with

  /**--------------------------------------------------------------------------------
  A Customer-named crater also near "Adams" - this works too
  --------------------------------------------------------------------------------**/
//   $lon_crater = 165.13;     // Degrees East. Wikipedia lists West.
//   $lat_crater = 27.62;      // Degrees North
//   $diam_crater = 4.29;      // Diameter in km
//   $name_crater = 'Kiki Sheppard\'s Crater';   // Name to label crater with

  /**--------------------------------------------------------------------------------
  "Lockyer", an IAU-named crater located southwest of "Adams" -- works
  --------------------------------------------------------------------------------**/
//   $lon_crater = 160.53;     // Degrees East
//   $lat_crater = 27.84;      // Degrees North
//   $diam_crater = 71.4;     // Diameter in km
//   $name_crater = 'Lockyer';    // Name to label crater with

  /**--------------------------------------------------------------------------------
  "Tombaugh", an IAU-named crater located further southwest of "Adams" - works
  --------------------------------------------------------------------------------**/
//    $lon_crater = 161.92;     // Degrees East
//    $lat_crater = 3.56;      // Degrees North
//    $diam_crater = 59.84;     // Diameter in km
//    $name_crater = 'Tombaugh';    // Name to label crater with

  /**--------------------------------------------------------------------------------
  Customer-named crater - FAILURE: No imagery at this location
  --------------------------------------------------------------------------------**/
    $lon_crater = 57.59;			// Degrees East
    $lat_crater = -23.69;			// Degrees North
    $diam_crater = 2.38;			// Diameter in km
    $name_crater = 'Adams Danger Zone';		// Name to label crater with

  /**--------------------------------------------------------------------------------
  Customer-named crater - FAILURE: Incorrect tile (crater not visible) and missing tile to north
  --------------------------------------------------------------------------------**/
//     $lon_crater = 159.38;			// Degrees East
//     $lat_crater = 29.31;			// Degrees North // XXX This was a typo: was -29.31, but should be +29.31
//     $diam_crater = 8.56;			// Diameter in km
//     $name_crater = 'The Thomas Robert Masters Crater';		// Name to label crater with

  /**--------------------------------------------------------------------------------
  HBT test -- something at lat -50.
  --------------------------------------------------------------------------------**/
//     $lon_crater = 115.53;			// Degrees East
//     $lat_crater = -52.76;			// Degrees North 
//     $diam_crater = 3.89;			// Diameter in km
//     $name_crater = 'Unnamed';		// Name to label crater with

  /**--------------------------------------------------------------------------------
  HBT test -- something at lat -67. This is beyond bounds of Stuart's map, but we still have images here.
  --------------------------------------------------------------------------------**/
//      $lon_crater = 146.38;			// Degrees East
//      $lat_crater = -67.34;			// Degrees North 
//      $diam_crater = 9.89;			// Diameter in km
//      $name_crater = 'Unnamed';		// Name to label crater with




  /* Dimensions for output image */
  $dx_image_pix = 280;			// Output size
  $dy_image_pix = 280;			// Output size

// END INPUT VALUES //


// Begin debug output
  print '
    <!doctype html>
    <html>
    <head>
      <title>Mars Map Generator Test</title>
      <style>
        body {
          font-family: sans-serif;
          margin:0;
        }
        header {
          background-color: #fff;
          box-shadow: 0 10px 10px #fff;
          left: 0;
          padding: 0 2em;
          position: fixed;
          right: 0;
          top: 0;
          z-index: 900;
        }
        #output-debug {
          font-family: monospace;
          padding: 6em 2em 6em 361px;
          position: relative;
          right: 0;
        }
        #output-img {
          position:fixed;
          top:6em;
          left:2em;
        }
      </style>
    </head>
    <body>
    <header><h1>Mars Map Generator Test</h1></header>
    <div id="output-debug"><h2>Debug Output</h2>
  ';
// Define the zoom levels available in the tileset

  echo "<h2>name_crater = $name_crater</h2><br><br>";
  $levels_zoom = [1, 2, 4, 8, 10, 20, 40];

// Calculate crater width, in pixels, at zoom 1

	$marstile->d2r = M_PI /180;	// diameter to radius calculation
	$marstile->r2d = 180 / M_PI;	// radius to diameter calculation

	// Set the intensity level for 'grey' color, to indicate missing data
	$marstile->dn_color_grey = $marstile->max_dn_tile_jpg/3;

	$marstile->deg_per_pix = 1/$marstile->pix_per_deg;

	$marstile->pix_per_km	= $marstile->pix_per_deg * 360 / (2 * M_PI * $marstile->radius_body_km);

	$marstile->km_per_pix	= 1/$marstile->pix_per_km;





  $dy_crater_pix = $diam_crater * $marstile->pix_per_km;
  $dx_crater_pix = $diam_crater * $marstile->pix_per_km / cos(deg2rad($lat_crater));

  print "dx_crater_pix = $dx_crater_pix at zoom 1\n<br>";
  print "dy_crater_pix = $dy_crater_pix at zoom 1\n<br>";

// Calculate zoom level so as to match crater width with image output width

  if($diam_crater > 50) {
    $i = 2;
  }
  elseif($diam_crater > 100) {
    $i = 3;
  }
  else {
    $i = 0;
  }
  $zoom = $levels_zoom[$i];

  while ($dx_crater_pix / $zoom > $dx_image_pix) {
    $zoom = $levels_zoom[$i++];
    print "At zoom level $zoom, crater width  = " . $dx_crater_pix / $zoom . " pixels\n<br>";
    print "At zoom level $zoom, crater height = " . $dy_crater_pix / $zoom . " pixels\n<br>";
  }

// Based on zoom level and image width, define the lon/lat borders

// Get the image

// Set the input parameters. These can all be changed, and are just test values.

  $image_quality = 90;			// JPEG quality of output

  $dlat_crater = $diam_crater * $marstile->pix_per_km * $marstile->deg_per_pix ;	                // Degrees. Full height.
  $dlon_crater = $diam_crater * $marstile->pix_per_km * $marstile->deg_per_pix / cos(deg2rad($lat_crater));	// Degrees. Full width.

  $dlat_image = $dy_image_pix * $marstile->deg_per_pix * $zoom;
  $dlon_image = $dx_image_pix * $marstile->deg_per_pix * $zoom / cos(deg2rad($lat_crater));
  $dlon_image = $dx_image_pix * $marstile->deg_per_pix * $zoom; // / cos(deg2rad($lat_crater));

  print "dlat_crater = $dlat_crater\n<br>";
  print "dlat_image = $dlat_image\n<br>";
  print "dy_image_pix = $dy_image_pix\n<br>";
  print "dy_crater_pix = $dy_crater_pix at zoom 1; " . $dy_crater_pix / $zoom . " at zoom $zoom\n<br>";

  print "dlon_crater = $dlon_crater\n<br>";
  print "dlon_image = $dlon_image\n<br>";
  print "dx_crater_pix = $dx_crater_pix at zoom 1; " . $dx_crater_pix / $zoom . " at zoom $zoom\n<br>";
  print "dx_image_pix = $dx_image_pix\n";

// Calc the coordinates of the edges of the region we will retrieve

  $lat0 = $lat_crater - $dlat_image / 2.;
  $lat1 = $lat_crater + $dlat_image / 2.;

  $lon0 = $lon_crater - $dlon_image / 2.;
  $lon1 = $lon_crater + $dlon_image / 2.;

// Retrieve the image

  $image = $marstile->diana_load_tiles('Mars_UT', $lon0, $lon1, $lat0, $lat1, $zoom);  // x0, x1, y0, y1
  
  print "\n\n";

  print "Retreived image dimensions = x:" . imagesx($image) . " y:" . imagesy($image) . "\n";

// Calc the position of the crater

  $x_crater_pix = imagesx($image)/2.;			// Center of crater at center of image
  $y_crater_pix = imagesy($image)/2.;			// Center of crater at center of image

  $dx_crater_pix = ($dlon_crater / $marstile->deg_per_pix / $zoom) + 14;
  $dy_crater_pix = ($dlat_crater / $marstile->deg_per_pix / $zoom) + 14;

// Calc the position of the text (use with imagestring_double version)
  $font = 12;
  $fw = imagefontwidth($font);		// width of a character
  $l = strlen($name_crater);		// number of characters
  $tw = $l * $fw;					// text width


  // $x_text_pix = $x_crater_pix; // Henry's original
  $x_text_pix = ($dx_image_pix - $tw)/2;
  $y_text_pix = $y_crater_pix + $dy_crater_pix/2 + 22;

# Draw an ellipse

  $color_white = imagecolorallocate($image, 255, 255, 255);
  $color_red   = imagecolorallocate($image, 255, 0, 0);
  $color_blue  = imagecolorallocate($image, 0, 0, 255);
  $color_yellow= imagecolorallocate($image, 0, 255, 255);
  $color_green = imagecolorallocate($image, 0, 255,   0);
  $color_black = imagecolorallocate($image, 0, 0, 0);
  $color_white = imagecolorallocate($image, 255, 255, 255);
  $color_province = imagecolorallocate($image, 204, 51,  204);		// purpleish
  $color_district = imagecolorallocate($image, 153, 255, 255);		// teal blue green
  $color_precinct = imagecolorallocate($image,   0,   0, 254);		// dark blue

  imagesetthickness($image, 3); // set line thickness for ellipse
  // imageellipse($image, $x_crater_pix, $y_crater_pix, $dx_crater_pix, $dy_crater_pix, $color_white);
  // Use imagearc to draw ellipse around crater instead of imageellipse since imagearc honors the set thickness
  imagearc($image, $x_crater_pix, $y_crater_pix, $dx_crater_pix, $dy_crater_pix, 0, 359.9, $color_white);

  // $marstile->imagestring_double($image, $font, $x_text_pix, $y_text_pix, $name_crater, $color_white, $color_black);
  $marstile->dropshadow_text($image, $font, $x_text_pix, $y_text_pix, $name_crater, $color_white, $color_black);

  print "\n";

  $file_out = 'test_'.date('Y-m-d_his').'.png';

  imagejpeg($image, $marstile->dir_thumbnails.$file_out, $image_quality);
  echo '<div>Wrote: ' . $file_out . '</div>';
  echo '</div>';
  echo '<div id="output-img"><img src="/'.$marstile->url_thumbnails.$file_out.'"></div>';
  echo '</body></html>'
?>
