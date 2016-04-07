<?php function diana_generate_image($name_body, 
                                    $x0_deg, 
                                    $x1_deg, 
				    $y0_deg, 
				    $y1_deg, 
				    $zoom, 
				    $do_plot_craters, 
				    $do_plot_names, 
				    $do_plot_legend, 
				    $do_plot_crater_cross, 
				    $do_plot_scalebar,
				    $features, $file_out) {

//
// Generate an image.  This is the main way to generate data.
// The image is placed into in a named jpeg file.
//
// Inputs:
//
//  $name_body: name of the body plus any extension: Moon_SELENE, Moon_LRO, Mimas, etc.
//
//  $x0_deg, x1_deg, y0_deg, y1_deg: the corner positions, in degrees
//
//  $zoom: zoom level
//
//  $do_plot_* : flags to plot various features
//
//  $features: A list of features to plot (including their position, etc.)
//  $file_out: Name of a directory / file to put the result into.
//
// HBT uwingu 1-Jan-2010

// print "diana_generate_image: preparing to create file $file_out with do_plot_names = $do_plot_names;<br>";

//print "diana_generate_image: name_body=$name_body, x0_deg=$x0_deg, x1_deg=$x1_deg, y0_deg=$y0_deg, y1_deg=$y1_deg, zoom=$zoom, file_out=$file_out<br>\n";

// Initialize routine

include("diana_init.php");
include("diana_init_" . $name_body . ".php");

$pix_per_char	= 264/34.;		// Approximate, based on "supercalifragilisticexpealidotious"

$do_plot_names_pdp	= $do_plot_names;
// $do_plot_names_pdp	= 0;

// Calculate the output image size, in pixels.

$dx_out_deg	= $x1_deg - $x0_deg; if ($dx_out_deg < 0) {$dx_out_deg += 360;}
$dy_out_deg	= $y1_deg - $y0_deg; if ($dy_out_deg < 0) {$dy_out_deg += 360;}

$dx_out_pix	= $dx_out_deg * $pix_per_deg / $zoom;
$dy_out_pix	= $dy_out_deg * $pix_per_deg / $zoom;

// print "diana_generate_image: count(features) = " . count($features) . "<br>";
// print "diana_generate_image: called with feature=" . $features[0] . " (size " . count($features) . ") and do_plot_craters=$do_plot_craters.<br>";

$date = date('r');
$replot = 1;
if (isset($replot)) {

  $header = "data/tiles/";

// print "diana_generate_image.php: Calling diana_load_tiles with name_body=$name_body, x0_deg = $x0_deg; x1_deg = $x1_deg, y0_deg = $y0_deg, y1_deg = $y1_deg<br>";

  $im	= diana_load_tiles($name_body, $x0_deg, $x1_deg, $y0_deg, $y1_deg, $zoom); 

  if ($im) {
    $color_red   = imagecolorallocate($im, 255, 0, 0);
    $color_blue  = imagecolorallocate($im, 0, 0, 255);
    $color_yellow= imagecolorallocate($im, 0, 255, 255);
    $color_green = imagecolorallocate($im, 0, 255,   0);
    $color_black = imagecolorallocate($im, 0, 0, 0);
    $color_white = imagecolorallocate($im, 255, 255, 255);
    $color_province = imagecolorallocate($im, 204, 51,  204);		// purpleish
//     $color_province = imagecolorallocate($im, 204, 151,  204);		// purpleish
    $color_district = imagecolorallocate($im, 153, 255, 255);		// teal blue green
    $color_precinct = imagecolorallocate($im,   0,   0, 254);		// dark blue

// Process all the craters with centers on this image

    $num_features = count($features);

// Create an imagemap (for mouseovers).  This is done automatically, since it's often useful to do at the same time as creating an image.

    $file_map_out	= str_replace('.jpg', '.txt', $file_out);
    $fh_map_out 	= fopen($file_map_out, 'w');
//   print "writing to txt file $file_map_out<br>";
  
    fwrite($fh_map_out, "<map name=\"map1\">");

// Now go thru and figure out what P/D/P regions are here

    $do_plot_pdp = 0;			// Flag: Do we plot Province / District / Precint borders?
    $empty	 = array(0);

    if ($do_plot_pdp) {
        diana_get_pdp_in_area($name_body, $x0_deg, $x1_deg, $y0_deg, $y1_deg, $province_ids, $district_ids, $precinct_ids);

// Now loop over the precincts (since we want to plot the smallest boxes first, so borders of large ones overlay them).

      if ($zoom <= 16) {
        sort($precinct_ids, SORT_NUMERIC);		// Sort them, since diana_pdp_nums_to_names expects that.
        $precinct_names = diana_pdp_nums_to_names($name_body, 'precinct', $precinct_ids);
// 	print "diana_generate_image: print_r of precinct_names:<br>\n";
// 	print_r($precinct_names);
        for ($i = 0; $i < count($precinct_ids); $i++) {
          diana_pdp2ll($name_body, 0, 0, $precinct_ids[$i], $x0, $x1, $y0, $y1);		// left, right, bottom, top

// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
          $has_id	= strcmp($precinct_ids[$i], $precinct_names[$i]) !=0;
          if ($has_id) {

// x0_deg, y0_deg is the LL corner of the whole image.
            $x0_pix =               ($x0 - $x0_deg) * $pix_per_deg / $zoom;	// left
            $x1_pix =               ($x1 - $x0_deg) * $pix_per_deg / $zoom;	// right
            $y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $pix_per_deg / $zoom;	// bottom -- sign flip since increasing Y degrees -> decreasing y pixels
            $y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $pix_per_deg / $zoom;	// top -- sign flip since increasing Y degrees -> decreasing y pixels
            imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_precinct);	// order left, top, right, bottom
	    if ($do_plot_names_pdp) {
              imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($precinct_names[$i])/2, ($y1_pix+$y0_pix)/2, $precinct_names[$i], 
	                         $color_precinct, $color_white);
	    }
// 	    print "diana_generate_image: printed name " . $precinct_names[$i] . " for precint index $i, precinct number " . $precinct_ids[$i] . "<br>\n";
          }
        }
      }

// Now loop over the districts

      if ($zoom <= 64) {
        sort($district_ids, SORT_NUMERIC);
        $district_names = diana_pdp_nums_to_names($name_body, 'district', $district_ids);
        for ($i = 0; $i < count($district_ids); $i++) {
          diana_pdp2ll($name_body, 0, $district_ids[$i], 0, $x0, $x1, $y0, $y1);

// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
          $has_id	= strcmp($district_ids[$i], $district_names[$i]) !=0;
          if ($has_id) {
            $x0_pix =               ($x0 - $x0_deg) * $pix_per_deg / $zoom;
            $x1_pix =               ($x1 - $x0_deg) * $pix_per_deg / $zoom;
            $y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $pix_per_deg / $zoom;
            $y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $pix_per_deg / $zoom;
            imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_district);
	    if ($do_plot_names_pdp) {
              imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($district_names[$i])/2, ($y1_pix+$y0_pix)/2, $district_names[$i], 
	                       $color_white, $color_district);
	    }
          }
        }
      }

// Now loop over the provinces

      if ($zoom <= $zoom_max) {
        sort($province_ids, SORT_NUMERIC);
        $province_names = diana_pdp_nums_to_names($name_body, 'province', $province_ids);
        for ($i = 0; $i < count($province_ids); $i++) {
          diana_pdp2ll($name_body, $province_ids[$i], 0, 0, $x0, $x1, $y0, $y1);

// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
          $has_id	= strcmp($province_ids[$i], $province_names[$i]) !=0;
          if ($has_id) {
            $x0_pix =               ($x0 - $x0_deg) * $pix_per_deg / $zoom;
            $x1_pix =               ($x1 - $x0_deg) * $pix_per_deg / $zoom;
            $y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $pix_per_deg / $zoom;
            $y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $pix_per_deg / $zoom;
            imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_province);
	    if ($do_plot_names_pdp){
              imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($province_names[$i])/2, ($y1_pix+$y0_pix)/2, $province_names[$i], 
	                         $color_white, $color_province);
	    }
          }
        }
      }
    }

// Now loop over the features, and plot them

    for ($i=0; $i < $num_features; $i++){
      $row		= $features[$i];
      $center_x_deg	= $row['longitude_deg'];
      $center_y_deg	= $row['latitude_deg'];
      $name		= $row['name_latin'];
      $radius		= $row['radius_km'];
      $featureid	= $row['featureid'];
      $type 		= $row['type'];
//       $citation		= $row['citation'];
 
// Handle wraparound -- e.g., if we have craters at 5 deg, and are plotting 350 .. 10 deg.

      if ($center_x_deg < $x0_deg) { $center_x_deg += 360;}

      $center_x_pix	= ($center_x_deg - $x0_deg) * $pix_per_deg / $zoom;
      $center_y_pix	= imagesy($im) - ($center_y_deg - $y0_deg) * $pix_per_deg / $zoom;
      $radius_y_pix	= ($radius * $pix_per_km) / $zoom;
      $radius_x_pix	= ($radius * $pix_per_km / cos($center_y_deg * $d2r)) / $zoom;

      if ($do_plot_craters) {
//         print "plotting crater $featureid lon=$center_x_deg lat=$center_y_deg at center $center_x_pix, radius $radius_x_pix pix<br>";
        switch ($type) {
	  case 'Crater' : {
            imageellipse($im, $center_x_pix, $center_y_pix, $radius_x_pix*2-2, $radius_y_pix*2-2, $color_white);
            imageellipse($im, $center_x_pix, $center_y_pix, $radius_x_pix*2, $radius_y_pix*2, $color_blue);
	  }; break;

	}

        if ($do_plot_crater_cross) {
          draw_crosshairs($im, $center_x_pix-1, $center_y_pix-1, 10, 10, $color_white);
          draw_crosshairs($im, $center_x_pix+1, $center_y_pix+1, 10, 10, $color_white);
          draw_crosshairs($im, $center_x_pix-1, $center_y_pix+1, 10, 10, $color_white);
          draw_crosshairs($im, $center_x_pix+1, $center_y_pix-1, 10, 10, $color_white);
          draw_crosshairs($im, $center_x_pix, $center_y_pix, 10, 10, $color_blue);
        }
      }

// Print a line to the imagemap
//     print '<area shape=rectangle coords="366.27332,409.88621,5" nohref title="TYC2 1990-3784-1, Mag[BT,VT]=[4.78,4.77]">'

      fwrite($fh_map_out, "<area shape=rect coords=\"" . round($center_x_pix-2) . ", " . round($center_y_pix-2) . ", " . 
                                                         round($center_x_pix+2) . ", " . round($center_y_pix+2) . "\""  .
	        " nohref title=\"$name, $radius km\"> \n ");

//////////
// Plot the crater names next to (or below) the craters
//////////

      if (($do_plot_names) && (strcmp($type, 'Crater') == 0)) {
	$name_below	= 1;
	$name_right	= 0;
	if ($name_right) {
          $dy_name_pix = -7;
          $dx_name_pix =  10;
	} 
	if ($name_below) {
	  $dy_name_pix	= 4;
	  $dx_name_pix	= -1 * strlen($name) * $pix_per_char/2;
	}
	imagestring_double($im, 4, $center_x_pix + $dx_name_pix, $center_y_pix + $dy_name_pix, $name, $color_white, $color_black);

// 	imagettftext($im, 10, 30, 30, $color_white, 'arial',  "Angström");
//         imagettftext($im, 20, 0, 11, 21, $grey, $font, $text);
	
      }
    } // End loop over features

// Plot a legend on the image

//    $do_plot_legend = 1;

    if (($do_plot_legend)){
      $border_top = array(10, 10, 550, 10, 550, 40, 10, 40);		// Four points, 8 values
      imagefilledpolygon  ($im, $border_top, 4, $color_black);

      $km_per_deg_y	= (2. * $pi * $radius_body_km) / 360.;
      $km_per_deg_x	= (2. * $pi * $radius_body_km) / 360. * cos(($y0_deg+$y1_deg)/2. * d2r);

      $width_km	= $dx_out_pix * $deg_per_pix *$km_per_deg_x * $zoom;
      $x_center_deg = ($x1_deg + $x0_deg)/2;				// XXX Not right -- fix this.
      $y_center_deg = ($y1_deg + $y0_deg)/2;

      imagestring($im, 4, 10, 10, 
                  sprintf("Lon = %f .. %f   Lat = %f .. %f   ", 
                      $x0_deg, $x1_deg, $y0_deg, $y1_deg), 
		  $color_white);
      imagestring($im, 4, 10, 24,
                  sprintf("Zoom = %d   Width = %5.2f km", 
		      $zoom, $width_km), 
		  $color_white);

//       print "getting province<br>";
// Look up the numerical province id's

      diana_ll2pdp($name_body, $x_center_deg, $y_center_deg, $province, $district, $precinct);	

// Convert to names

      diana_pdp_num_to_name($name_body, $province, $district, $precinct, $name_province, $name_district, $name_precinct);

       imagestring($im, 4, 10, 38, 
                   sprintf("Province %s, District %s, Precinct %s", 
                       $name_province, $name_district, $name_precinct), 
 		  $color_white);
    }

  if ($do_plot_scalebar){
// Plot a scalebar in UL corner.  Make it go vertically, so we don't have a cos theta issue.

      $length_scalebar_pix 	= 150;
      $width_scalebar_pix 	= 4;
      $y0_scalebar_pix 		= 50;
      $y1_scalebar_pix		= $y0_scalebar_pix + $length_scalebar_pix;;
      $x0_scalebar_pix		= 30;
      $x1_scalebar_pix		= $x0_scalebar_pix + $width_scalebar_pix;
      $color_scalebar		= $color_red;

      for ($i=0; $i < $width_scalebar_pix; $i++){
// imageline x1 y1 x2 y2
        imageline($im, $x0_scalebar_pix+$i, $y0_scalebar_pix, $x0_scalebar_pix+$i, $y1_scalebar_pix, $color_scalebar);
      }
      imageline($im, $x0_scalebar_pix-3, $y0_scalebar_pix+1, $x1_scalebar_pix+3, $y0_scalebar_pix+1, $color_scalebar);
      imageline($im, $x0_scalebar_pix-3, $y1_scalebar_pix-1, $x1_scalebar_pix+3, $y1_scalebar_pix-1, $color_scalebar);
      imageline($im, $x0_scalebar_pix-3, $y0_scalebar_pix, $x1_scalebar_pix+3, $y0_scalebar_pix, $color_scalebar);
      imageline($im, $x0_scalebar_pix-3, $y1_scalebar_pix, $x1_scalebar_pix+3, $y1_scalebar_pix, $color_scalebar);

      $length_scalebar_deg	= $length_scalebar_pix * $deg_per_pix * $zoom;
      $length_scalebar_km		= $length_scalebar_pix * $km_per_pix * $zoom;

// Draw the text for the legend for the scalebar. Depending on flags, we want it to go either horizontally, or vertically.

      $do_legend_scalebar_horizontal = 0;
      if ($do_legend_scalebar_horizontal) {
        imagestring($im, 4, $x1_scalebar_pix + 7, ($y0_scalebar_pix+$y1_scalebar_pix)/2 + 5, 
                sprintf("%5.2f Deg", $length_scalebar_deg), $color_white);

        imagestring($im, 4, $x1_scalebar_pix + 7, ($y0_scalebar_pix+$y1_scalebar_pix)/2 - 15, 
                sprintf("%5.2f km", $length_scalebar_km), $color_white);
      }

      $do_legend_scalebar_vertical = 1;

      if ($do_legend_scalebar_vertical) {		// For vertical scalebar, imagestring will not rotate text. Instead, rotate image, 
        $im_rot = imagerotate($im, -90, 0);		// and then un-rotate it back.
	imagestring($im_rot, 34, 210, 3, 
                sprintf("%5.2f Deg = %5.2f km", $length_scalebar_deg, $length_scalebar_km), $color_white);
        $xcen = ($x0_deg + $x1_deg )/2 % 360;
	if ($x1_deg < $x0_deg) { $xcen += 180; }
	$xcen %= 360;
        $ycen = ($y0_deg + $y1_deg       )/2 % 360;
	imagestring($im_rot, 34, 11, 3, 
                sprintf("Pos %5.2f, %5.2f", $xcen, $ycen), $color_white);

        $im_rot2 = imagerotate($im_rot, 90, 0);

	$im	= $im_rot2;
      }
  }

// Plot crosshairs at image center

    $do_draw_crosshairs_center = 0;

    if ($do_draw_crosshairs_center) {
      draw_crosshairs($im, $dx_out_pix/2, $dy_out_pix/2, 40, 40, $color_red);
    }

//    print "current dir: " . getcwd() . "<br>";
//    print "dir_tmp: $dir_tmp<br>";
    if (imagejpeg($im, $file_out, 90)){
//      print "diana_generate_image: yes wrote file $file_out<br>";
    }
    else {
//      print "diana_generate_image: failed to write file $file_out<br>";
    }

    imagedestroy($im);

//     echo "<img src = \"tmp/test.jpg\">";

// Close the imagemap file
// NB: Don't put an extra carriage return in here, since it will affect spacing btwn main image and navigator 
// in inconsistent way.

    fwrite($fh_map_out, "</map>");
    fclose($fh_map_out);

  }
}
}
?>
