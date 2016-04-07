<?php

// This code gets run after diana_init.php, any time we are using Mars imagery.
// This code also gets run *by itself*, so it should define any variables it needs.
//
// HBT Uwingu 18-Sep-2010
// Redone 11-Aug-2014

// Do any body-specific setup here: directories, etc.

  $name_body			= 'Mars_BW';
  $name_body_short		= 'Mars';

  $dir_tiles_server		= '/data/de/' . $name_body . '/tiles/';
  $dir_tiles_development	= '/Users/throop/Uwingu/Mars/tiles/jpg/';

// Sample pathname as generated on my 
// /Users/throop/Uwingu/Mars/tiles/jpg/lat-30_lon000/mars_merged_1_-25.5_9.jpg

  $file_header	= "mars";	

  $dx_tile_pix	= 384;			// BW tiles are 384 x 384
  $dy_tile_pix	= 384;

  $radius_body_km	= 3389.5;		// Mean equatorial radius from Wikipedia

//  $pix_per_km	= $pix_per_file / $deg_per_file * 360. / (2. * $pi * $radius_mimas_km);

// Mars images: At scale 1, images are 384 x 384  and  0.75 deg x 0.75 deg -- see my e-mail to Tom 20-Jul-2014
// That means 512 pix/degree.

  $pix_per_deg	= 512.;
  $deg_per_pix	= 1/$pix_per_deg;

  $pix_per_km	= $pix_per_deg * 360 / (2 * pi() * $radius_body_km);
  $km_per_pix	= 1./$pix_per_km;

  $y_max_deg	= 90;
  $y_min_deg	= -90;

  $x_min_deg	= 0;
  $x_max_deg	= 360;

// Set the image size

  $zoom_min	= 1;
  $zoom_max	= 2.;

// Set the P/D/P sizes, in degrees
// 45 x 45 deg -> 32 regions on body.
  
  $dx_province	= 45;
  $dy_province	= 45;

  $dx_district	= 15;
  $dy_district	= 15;
  
  $dx_precinct	= 5;
  $dy_precinct	= 5;

?>
