#!/usr/bin/php -q
<?php 

  # This is just a skeleton 'test' routine to call diana_load_tiles.
  # That is the routine to extract an arbitrary portion of the map, and highlight a single crater on it.
  # The idea is that this routine is a far more direct way of making the image than using 
//   # PhantomJS to generate it by simulating a browser instance.

  # What do we want for the certificate? Most likely:
  #  Fixed height (e.g., 500 pixels)
  #  Fixed width (e.g., 700 pixels)
  #  Inputs: lon, lat, diameter, name
  #
  # My code must calculate appropriate zoom level, and borders of the box.
  #
  # HBT 14-Aug-2014.

// INPUT VALUES.
// Set these appropriately to tell the code which crater to plot.
// These six values are the input to the routine.

// 31-Mar-2016 103.00 km. Lat: -9.07° N, Lon: 150.51° E [works]
//             8.04 km. Lat: -17.68° N, Lon: 179.88° E [works]
//             95.70 km. Lat: -19.60° N, Lon: 179.82° E [works]


// For testing 22-Mar-2016: Radius 1.3 km. Latitude: -17.82° N, Longitude: 180.10° E

  $lon_crater = 179.82;			// Degrees East. Wikipedia lists West.
  $lat_crater = -19.60;			// Degrees North
  $diam_crater = 95.70;		// Diameter in km
  $name_crater = "test"; // Name to label crater with

  // "This one is smaller"
  $diam_crater = 103.00;                  // Diameter in km
  $lat_crater = -9.07;                    // Degrees North
  $lon_crater = 150.51;                   // Degrees East. Wikipedia lists West.
  $name_crater = "This one is smaller";   // Name to label crater with

 $diam_crater = 103.00;                  // Diameter in km
 $lat_crater = -9.07;                    // Degrees North
 $lon_crater = 150.51;                   // Degrees East. Wikipedia lists West.
 $name_crater = "This one is smaller";   // Name to label crater with


  // "Testing Time"
//    $diam_crater = 8.04;                    // Diameter in km
//    $lat_crater = -17.68;                   // Degrees North
//    $lon_crater = 179.88;                   // Degrees East. Wikipedia lists West.
//    $name_crater = "Testing Time";          // Name to label crater with

  // "Test 2A 2016-03-20"
//    $diam_crater = 1.33;                    // Diameter in km
//    $lat_crater = -17.82;                   // Degrees North
//    $lon_crater = 180.10;                   // Degrees East. Wikipedia lists West.
//    $name_crater = "Test 2A 2016-03-20";    // Name to label crater with

  $dx_image_pix = 700;			// Output size
  $dy_image_pix = 700;			// Output size

// END INPUT VALUES //

//     $lon_crater = 171.29;			// Degrees East. Wikipedia lists West.
//     $lat_crater = 55.62;			// Degrees North
//     $diam_crater = 62.5;			// KM
//     $name_crater = "Stokes";

// Initialize

  include("diana_init.php");
  include("diana_init_routines.php");
  include("diana_init_Mars_BW.php");

// Define the zoom levels available in the tileset

//   $levels_zoom = [0.5, 1, 2, 4, 8, 10, 20, 40];
  $levels_zoom = [1, 2, 4, 8, 10, 20, 40];	// Temporarily disable zoom 0.5, as per testing 22-Mar-2016

// Calculate crater width, in pixels, at zoom 1

  $dy_crater_pix = $diam_crater * $pix_per_km;
  $dx_crater_pix = $diam_crater * $pix_per_km / cos(deg2rad($lat_crater));

  print " <br>\n";
  print "dx_crater_pix = $dx_crater_pix at zoom 1<br>\n";
  print "dy_crater_pix = $dy_crater_pix at zoom 1<br>\n";

// Calculate zoom level so as to match crater width with image output width

  $i = 0;
  $zoom = $levels_zoom[$i];

  while ($dx_crater_pix / $zoom > $dx_image_pix) {
    $zoom = $levels_zoom[$i++];
    print "At zoom level $zoom, crater width  = " . $dx_crater_pix / $zoom . " pixels<br>\n";
    print "At zoom level $zoom, crater height = " . $dy_crater_pix / $zoom . " pixels<br>\n";
  }

// Based on zoom level and image width, define the lon/lat borders

// Get the image

// Set the input parameters. These can all be changed, and are just test values.

  $image_quality = 90;			// JPEG quality of output

  $dlat_crater = $diam_crater * $pix_per_km * $deg_per_pix ;	                // Degrees. Full height.
  $dlon_crater = $diam_crater * $pix_per_km * $deg_per_pix / cos(deg2rad($lat_crater));	// Degrees. Full width.

  $dlat_image = $dy_image_pix * $deg_per_pix * $zoom;
  $dlon_image = $dx_image_pix * $deg_per_pix * $zoom / cos(deg2rad($lat_crater));
  $dlon_image = $dx_image_pix * $deg_per_pix * $zoom; // / cos(deg2rad($lat_crater));

  print "dlat_crater = $dlat_crater <br>\n";
  print "dlat_image = $dlat_image <br>\n";
  print "dy_image_pix = $dy_image_pix <br>\n";
  print "dy_crater_pix = $dy_crater_pix at zoom 1; " . $dy_crater_pix / $zoom . " at zoom $zoom <br>\n";

  print "dlon_crater = $dlon_crater <br>\n";
  print "dlon_image = $dlon_image <br>\n";
  print "dx_crater_pix = $dx_crater_pix at zoom 1; " . $dx_crater_pix / $zoom . " at zoom $zoom <br>\n";
  print "dx_image_pix = $dx_image_pix <br>\n";

// Calc the coordinates of the edges of the region we will retrieve

  $lat0 = $lat_crater - $dlat_image / 2.;
  $lat1 = $lat_crater + $dlat_image / 2.;

  $lon0 = $lon_crater - $dlon_image / 2.;
  $lon1 = $lon_crater + $dlon_image / 2.;

// XXX Ignore all the setup above. Instead, plug in raw coordinates.

//   $lon0 = 23.461250;		// degrees, x0
//   $lon1 = 34.398750;		// x1
//   $lat0 = -5.838750;		// y0
//   $lat1 = 5.098750;		// y1
//   $zoom = 16.00;

// Retrieve the image

  $image = diana_load_tiles('Mars_BW', $lon0, $lon1, $lat0, $lat1, $zoom);  // x0, x1, y0, y1
  
  print "\n\n";

  print "Retreived image dimensions = x:" . imagesx($image) . " y:" . imagesy($image) . "\n";

// Calc the position of the crater

  $x_crater_pix = imagesx($image)/2.;			// Center of crater at center of image
  $y_crater_pix = imagesy($image)/2.;			// Center of crater at center of image

  $dx_crater_pix = $dlon_crater / $deg_per_pix / $zoom;
  $dy_crater_pix = $dlat_crater / $deg_per_pix / $zoom;

// Calc the position of the text

  $x_text_pix = $x_crater_pix;
  $y_text_pix = $y_crater_pix + $dy_crater_pix/2 + 30;

# Draw an ellipse

  $color_white = imagecolorallocate($image, 255, 255, 255);
  $color_red   = imagecolorallocate($image, 255, 0, 0);
  $color_blue  = imagecolorallocate($image, 0, 0, 255);
  $color_yellow= imagecolorallocate($image, 0, 255, 255);
  $color_green = imagecolorallocate($image, 0, 255,   0);
  $color_black = imagecolorallocate($image, 0, 0, 0);
  $color_white = imagecolorallocate($image, 255, 255, 255);
  $color_province = imagecolorallocate($image, 204, 51,  204);		// purpleish
//     $color_province = imagecolorallocate($im, 204, 151,  204);		// purpleish
  $color_district = imagecolorallocate($image, 153, 255, 255);		// teal blue green
  $color_precinct = imagecolorallocate($image,   0,   0, 254);		// dark blue
  
  imageellipse($image, $x_crater_pix, $y_crater_pix, $dx_crater_pix, $dy_crater_pix, $color_white);

  imagestring_double($image, 10, $x_text_pix, $y_text_pix, $name_crater, $color_white, $color_black);

  print "\n";

  $file_out = "test.png";

  imagejpeg($image, $file_out, $image_quality);
  echo "Wrote: " . $file_out . "\n";

  echo "<hr><br><img src=$file_out><br>";

?>

