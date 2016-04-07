<?php
function diana_read_tile($name_body, $file_in){

// This routine opens a tile file, and returns it in a GD image resource.  
// If the file doesn't exist, then it retuns a blank image of the same size,
// which may have a rectangular border drawn around it.
//
// HBT DIANA 27-Nov-2009
//           29-Dec-2009 Converted to PHP
//
// First calculate the image size, based on the positions given in the filename
// 

  diana_filename_tile_to_coords($name_body, $file_in, $x0, $x1, $y0, $y1, $binning, $dx_pix, $dy_pix);

//  print "diana_read_tile: file $file_in has x0=$x0, x1=$x1, y0=$y0, y1=$y1, dx_pix = $dx_pix, dy_pix = $dy_pix, binning=$binning<br>";

// Get the filename (not the whole path) so we can plot it on the image

  $file_short = substr(strrchr($file_in, '/'), 1);

  if (file_exists($file_in)){				// For found tiles
    $im = imagecreatefromjpeg($file_in);
    $color_text = imagecolorallocate($im, 233, 14, 91);
    $color_white = imagecolorallocate($im, 255, 255, 255);
    return $im;
  } else {						// For missing tiles
    $im = imagecreatetruecolor($dx_pix, $dy_pix);
    $color_border = imagecolorallocate($im, 233, 14, 91);
    $color_white = imagecolorallocate($im, 255, 255, 255);

// Write some text which refers to the missing imagery. Having this here would have alleviated so many KC comments.

    $junk = imagestring($im, 4, $dx_pix/2, $dy_pix/2, 'No imagery at this location', $color_white);
    $junk = imagestring($im, 4, $dx_pix/2, 0,         'No imagery at this location', $color_white);

// Now put a (red) rectangular border around missing tiles.  To remove this, comment it out

   $do_border_around_missing_tiles	= 0;

   if ($do_border_around_missing_tiles) {
     imagerectangle($im, 1, 1, $dx_pix-3, $dy_pix-3, $color_border);
   }

//     imagestring($im, 3, 0, 100, $file_short, $color_white);
    return $im;
  }
}
?>
