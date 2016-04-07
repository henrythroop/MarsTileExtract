<?php function diana_load_tiles($name_body, $x0_deg_in, $x1_deg_in, $y0_deg_in, $y1_deg_in, $zoom) {

// This takes a range of x and y, and loads all of the tile data specified
// 
// Required inputs:
//   x0_deg: left edge, in degrees
//   x1_deg: right edge, in degrees
//   y0_deg: bottom edge, in degrees
//   y1_deg: top edge, in degrees
//   zoom: 1 = full-res; 2 = half-res; etc.
// 
// The number of pixels is not required -- it can be computed directly from the inputs.
// 
// Q: What do we do if the requested range is beyond what is possible?  e.g., 91 deg north?
// A: We just return a blank region there, and keep working like normal.
// 
// 
// HBT DIANA 25-Nov-2009
//           23-Dec-2009 Converted to PHP

  $debug = true;
 
  include("diana_init.php");
  include("diana_init_" . $name_body . ".php");

//   print "diana_load_tiles: name_body = $name_body; name_body_short = " . diana_name_body_short($name_body);

// Initialize inputs

//    echo "diana_load_tiles: called w/ x0_deg_in = $x0_deg_in; x1_deg_in = $x1_deg_in, y0_deg_in=$y0_deg_in, y1_deg_in=$y1_deg_in<br>";

// Put the ranges in proper order.  That is, x0 = 300, x1 = 60, then this is OK!
// We convert that into x0=-60, so that at least x1 > x0.

  $x0_deg	= $x0_deg_in;
  $x1_deg	= $x1_deg_in;
  $y0_deg	= $y0_deg_in;
  $y1_deg	= $y1_deg_in;

  if ($x0_deg > $x1_deg) {
    $x0_deg	-= 360;
  }


// Calculate the actual width, in degrees, of the output.  This value is correct, indep. of wrapping.

  $dx_deg	= $x1_deg - $x0_deg;
  $dy_deg	= $y1_deg - $y0_deg;

// Calculate the actual width, in pixels, of the output.

  $dx_out_pix	= $dx_deg * $pix_per_deg / $zoom;
  $dy_out_pix	= $dy_deg * $pix_per_deg / $zoom;

  if ($debug) {
    printf("<br/> Starting diana_load_tiles(x0=%f, x1=%f, y0=%f, y1=%f, zoom=%f)<br/>\n", 
      $x0_deg_in, $x1_deg_in, $y0_deg_in, $y1_deg_in, $zoom);
    printf("<br/> diana_load_tiles: Creating image of size %f x %f pix, based on pix_per_deg = %f.<br/>\n", 
      $dx_out_pix, $dy_out_pix, $pix_per_deg);
  }

// Create the output array

   $arr_out	= imagecreatetruecolor($dx_out_pix, $dy_out_pix);

// Wrap the x values, as needed: x=-1 -> x=359.  Y values do not need forcing.

   $x0_deg	= fmod(((fmod($x0_deg, 360.0)) + 360.0), 360.0);		// Force to range 0..360
   $x1_deg	= fmod(((fmod($x1_deg, 360.0)) + 360.0), 360.0);		// Force to range 0..360

   printf("<br/> Starting diana_load_tiles(%f, %f, %f, %f, %f)<br/>", $x0_deg, $x1_deg, $y0_deg, $y1_deg, $zoom);

// Get a list of all the bins for this zoom level.

  diana_get_bins($name_body, $bin_tiles_x_deg, $bin_tiles_y_deg, "TILE DEGREES", $zoom);

// Calculate the width and height of each tile

  $bin_tiles_x_pix	= array_mult($bin_tiles_x_deg, $pix_per_deg/$zoom);
  $bin_tiles_y_pix	= array_mult($bin_tiles_y_deg, $pix_per_deg/$zoom);

  print "<br>bin_tiles_x_deg = $bin_tiles_x_deg";
  print "<br>bin_tiles_y_deg = $bin_tiles_y_deg";

// Calculate the width in pixels of the 0th tile (from bin 0 to bin 1).  This array is one element shorter than the 
// bin values array: In x dir, the 0th and final bins are equal (0 & 360 deg).
//                  In y dir, there is no wraparound from -90 to +90, so it does not make sense to define a width.
//
// PHP: See array_shift, array_push

  $dx_tile_pix	= array_pad(array(), count($bin_tiles_x_pix) - 1, 0);
  $dy_tile_pix	= array_pad(array(), count($bin_tiles_y_pix) - 1, 0);

  for ($i = 0; $i < count($dx_tile_pix); $i++){ $dx_tile_pix[$i] = $bin_tiles_x_pix[$i+1] - $bin_tiles_x_pix[$i]; }
  for ($i = 0; $i < count($dy_tile_pix); $i++){ $dy_tile_pix[$i] = $bin_tiles_y_pix[$i+1] - $bin_tiles_y_pix[$i]; }

  printf("<br>dx_tile_pix: " . sprint_array($dx_tile_pix) . "<br>");
  printf("<br>dy_tile_pix: " . sprint_array($dy_tile_pix) . "<br>");
    
// Calculate the number of tiles (which is one less than the number of bins)
// PHP: count() is same as sizex() or n_elements().

  $num_tiles_x	= count($bin_tiles_x_deg)-1;
  $num_tiles_y	= count($bin_tiles_y_deg)-1;

// Now figure out what bins our corners are in: get the bins for min & max, x & y.
// All these bins will be positive.
// These bin numbers are for the tiles (e.g., 0.125 deg, or 0.25 deg, etc.)
//
// PHP: max() is the highest value in the array.

  $delta		= 1e-5;

  $w 		= where($bin_tiles_y_deg, 'GE', $y0_deg);
//   print "x0_deg: $x0_deg; bin_tiles_x_deg: " . sprint_array($bin_tiles_x_deg) . "<br>";
//   print "y0_deg: $y0_deg; bin_tiles_y_deg: " . sprint_array($bin_tiles_y_deg) . "<br>";

//   print "W: " . sprint_array($w) . "<br>";

//   print "Calculating bin_x0: x0_deg = $x0_deg<br>";

   $bin_x0	= max(where($bin_tiles_x_deg, 'GE', $x0_deg));
   $bin_x1	= max(where($bin_tiles_x_deg, 'GE', $x1_deg-$delta))+1;
   $bin_y0	= max(where($bin_tiles_y_deg, 'GE', $y0_deg, 'bin_y0: '));
   $bin_y1	= max(where($bin_tiles_y_deg, 'GE', $y1_deg-$delta))+1;

//   print "binx0, x1, y0, y1: $bin_x0, $bin_x1, $bin_y0, $bin_y1<br>";
// 
//   print "bin_tiles_y_deg: " . sprint_array($bin_tiles_y_deg) . "<br>";
//   print "y1_deg - delta: " . ($y1_deg - $delta);

//   printf("x0_deg: $x0_deg; x1_deg: $x1_deg; y0_deg: $y0_deg; y1_deg: $y1_deg<br/>");
//   printf("bin_x0: $bin_x0; bin_x1: $bin_x1; bin_y0: $bin_y0; bin_y1: $bin_y1<br/>");

// XXX problem above: if y1_deg > 90, then bin_y1 can exceed the # of bins we actually have

// Set a flag to indicate whether we are wrapping around in the X direction.

  $is_wrap_x	= ($bin_x0 > $bin_x1);		//  ? 1 : 0;			// Flag

// Now make a list of all the x bins we need to load, and all the y bins.
// Note that we are omitting here the final bin, since we dont need to load that one.

// X bins case: if we have no wraparound -- just regular bins

  if (!($is_wrap_x)) {
    $bins_x	= range($bin_x0, $bin_x1-1, 1);	// A list of the bin numbers we will use in x dir
  }

// X bins case: if we have a wraparound
// This logic will not handle *double* wraparounds -- e.g., bins [1, 0, 1].  This happens easily for zoom = 1024.

  if ($is_wrap_x) {
//     bins_x	= [bin_x0 + dindgen(num_tiles_x - bin_x0), dindgen(bin_x1+1)]
    $bins_x	= range($bin_x0, $num_tiles_x-1, 1);
//     print_r($bins_x);
    $bins_x = array_append($bins_x, dindgen($bin_x1 + 1));
//     print "<br>array merged with dindgen(" . ($bin_x1 + 1) . ")<br>";
//     print_r($bins_x);
//     print_r(dindgen(2));
  }

  $bins_y	= range($bin_y0, $bin_y1-1, 1);			// XXX was bin_y1, not bin_y1-1

//   print, 'x0, x1, y0, y1: ' + st(x0_deg_in) + ' ' + st(x1_deg_in) + ' ' + st(y0_deg_in) + ' ' + st(y1_deg_in)

//   printf("<br>x0, x1, y0, y1: %f, %f, %f, %f<br/>", $x0_deg_in, $x1_deg_in, $y0_deg_in, $y1_deg_in);

//    printf("<br>bins_x: " . sprint_array($bins_x) . "<br>");
//    printf("<br>bins_y: " . sprint_array($bins_y));

//   bins_y	= dindgen(bin_y1-bin_y0) + bin_y0		; A list of the bin numbers we will use in y dir


// Create a large array, in which we will load all of our tiles, and put them all together next to each other in order.

// THIS VALUE FOR ARR_OUT_PRECROP reflects that not all tiles have the same width!

//  arr_out_precrop	= intarr(total(dx_tile_pix[bins_x]), total(dy_tile_pix[bins_y]))

//   print "diana_load_tiles: calling array_extract_elements on dx_tile_pix<br>";
  $sizex_precrop	= array_sum(array_extract_elements($dx_tile_pix, $bins_x));

//   print "diana_load_tiles: calling array_extract_elements on dy_tile_pix<br>";
  $sizey_precrop	= array_sum(array_extract_elements($dy_tile_pix, $bins_y));

  $arr_out_precrop	= imagecreatetruecolor($sizex_precrop, $sizey_precrop);

//   print "diana_load_tiles: created arr_out_precrop with size " . imagesx($arr_out_precrop) . " x " . imagesy($arr_out_precrop) . "<br/>";

// Now loop over all of the bins.  Each bin here corresponds to exactly one tile.

  $k	= 0;

   print "<br>diana_load_tiles: " . count($bins_x) . " x " . count($bins_y) . " tiles to load<br>";
   print "<br>x tiles have values " ;
   print_array($bin_tiles_x_deg);
   print "<br> of which we use elements ";
   print_array($bins_x);
   print "<br>y tiles have values " ;
   print_array($bin_tiles_y_deg);
   print "<br> of which we use elements ";
   print_array($bins_y);

  for ($i = 0; $i < count($bins_x); $i++){
    for ($j = 0; $j < count($bins_y); $j++){
      $x0_in_deg	= $bin_tiles_x_deg[$bins_x[$i]];
      $x1_in_deg	= $bin_tiles_x_deg[$bins_x[$i]+1];
      $y0_in_deg	= $bin_tiles_y_deg[$bins_y[$j]];
      $y1_in_deg	= $bin_tiles_y_deg[$bins_y[$j]+1];

// Create the proper filename, and read it

      if ($debug) {print "<hr>";}
      if ($debug) {print "<br><br><br>";}

      if ($debug) {print "<br>diana_load_tiles: name_body = $name_body, x0_in_deg = $x0_in_deg; " .
                         "x1_in_deg = $x1_in_deg, y0_in_deg = $y0_in_deg, y1_in_deg=$y1_in_deg, zoom = $zoom.\n";}

      $file_in	= diana_create_filename_tile($name_body, $x0_in_deg, $x1_in_deg, $y0_in_deg, $y1_in_deg, $zoom);

//       if ($debug) {print "<br>diana_load_tiles: bin_tiles_x_deg =<br>\n";}
//       if ($debug) {print_array($bin_tiles_x_deg);}
//       if ($debug) {print "<br>diana_load_tiles: bin_tiles_y_deg =<br>\n";}
//       if ($debug) {print_array($bin_tiles_y_deg);}

      if ($debug) {print "<br>diana_load_tiles: Created filename $file_in .\n";}
 
      $arr_in	= diana_read_tile($name_body, $file_in);			// Load 512x512 image.  If file missing, still works
// Create HTML to display the tile that we've just found, assuming it really exists on the filesystem.

      if ($debug) {print "<img src=" . str_replace("/Users/throop/Uwingu/Mars/", '', $file_in) . ">";}

// Now copy this array into the target array.  It goes into [left x pos of i'th bin : (left x pos of i+1 bin) -1 ]
// array_slice($arr, $offset, $length)

// If i==0 or j==0, then the x or y index for where this gets copied into the output array is zero, since it's at the top or left.
// If i or j isn't 0, then we count across so many pixels, summing the number of bins.

      if ($i == 0) { $x0_out_pix	= 0;}
//       if ($i >  0) then $x0_out_pix	= array_sum(($dx_tile_pix[$bins_x[0:$i-1]])) 
      if ($i >  0) { $x0_out_pix	= array_sum(
                                            array_extract_elements($dx_tile_pix, array_slice($bins_x, 0, $i))); }	// $i steps, which takes us over 0:i-1
      
      if ($i == 0) { $x1_out_pix	= $dx_tile_pix[$bins_x[0]]-1; }
//       if ($i >  0) then $x1_out_pix	= array_sum(($dx_tile_pix[$bins_x[0:$i]]))-1
      if ($i >  0) { $x1_out_pix	= array_sum(
                                            array_extract_elements($dx_tile_pix, array_slice($bins_x, 0, $i+1)))-1; }

      if ($j == 0) { $y0_out_pix	= 0; }
      $elements				= array_slice($bins_y, 0, $j+1);
//       print "<br>diana_load_tiles: elements = " . sprint_array($elements) . "; j = $j<br>";
//       print("diana_load_tiles: bins_y = " . sprint_array($bins_y) . "<br>");
//       print("diana_load_tiles: count(dy_tile_pix) = " . count($dy_tile_pix) . "</br>");

      if ($j >  0) { $y0_out_pix	= array_sum(
                                            array_extract_elements($dy_tile_pix,  array_slice($bins_y, 0, $j))); }
      
      if ($j == 0) { $y1_out_pix	= $dy_tile_pix[$bins_y[0]]-1; }
      if ($j >  0) { $y1_out_pix	= array_sum(
                                            array_extract_elements($dy_tile_pix, array_slice($bins_y, 0, $j+1)))-1; }

//       print "<br>";
//       print "i = $i; j = $j<br>";
//       print "x0_out_pix = $x0_out_pix; x1_out_pix = $x1_out_pix; y0_out_pix = $y0_out_pix; y1_out_pix = $y1_out_pix<br>";

//  Position is properly calculated.  But oops!  we want to place these counting not from LL corner, but from UL.

      $y0_out_pix	= $sizey_precrop - $y0_out_pix - ($y1_out_pix - $y0_out_pix);
//       print "diana_load_tiles: copying into y0_out_pix = $y0_out_pix, x0_out_pix = $x0_out_pix<br>";

//  bool imagecopy  ( resource $dst_im  , resource $src_im  , int $dst_x  , int $dst_y  , int $src_x  , int $src_y  , int $src_w  ,           int $src_h  )
//       if(imagecopy($arr_out_precrop, $arr_in,                $x0_out_pix,   $y0_out_pix,   0,           0,         $x1_out_pix-$x0_out_pix+1, $y1_out_pix-$y0_out_pix+1)){
      if(imagecopy($arr_out_precrop, $arr_in,                $x0_out_pix,   $y0_out_pix,   0,           0,         imagesx($arr_in), imagesy($arr_in))){
        imagedestroy($arr_in);
//         print "success: copied arr_in of size " . imagesx($arr_in) . " x " . imagesy($arr_in) . " into arr_out_precrop at pos $x0_out_pix, $y0_out_pix;<br/>";
// 	print "   source width = " . ($x1_out_pix-$x0_out_pix+1) . "; height = " . ($y1_out_pix-$y0_out_pix+1) . "<br/>";
      } else {
         print "diana_load_tiles: failure: not copied<br/>";
         print "deg_in: x0 = $x0_deg_in, x1 = $x1_deg_in, y0 = $y0_deg_in, y1 = $y1_deg_in, zoom = $zoom<br>";
	 print "COW!";
      }
    }
  }

// Now that we've loaded all the tiles, crop the output array into the proper array we want.

  $x0_deg_precrop = $bin_tiles_x_deg[$bins_x[0]];		// Get the min x value for all the data read in

  $y0_deg_precrop = ($bin_tiles_y_deg[$bins_y[0]]);		// Get the min y value for all the data read in

  $x0_out_pix	= ($x0_deg - $x0_deg_precrop) * $pix_per_deg / $zoom;
  $y0_out_pix	= ($y0_deg - $y0_deg_precrop) * $pix_per_deg / $zoom;

//   print "diana_load_tiles: cropping arr_out_precrop to x0_out_pix = $x0_out_pix, x1_out_pix = $x1_out_pix; y0_out_pix = $y0_out_pix; y1_out_pix=$y1_out_pix<br>";

// bool imagecopy  ( resource $dst_im  , resource $src_im  , $dst_x , $dst_y  , $src_x  ,   $src_y  ,                  int $src_w  , int $src_h  )
   imagecopy($arr_out, $arr_out_precrop,                     0,       0,       $x0_out_pix, imagesy($arr_out_precrop) - $y0_out_pix - $dy_out_pix, $dx_out_pix, $dy_out_pix);

//   print "diana_load_tiles: cropped arr_out_precrop to arr_out of size " . imagesx($arr_out) . " x " . imagesy($arr_out) . "<br>";

//   $arr	= arr_out_precrop[x0_out_pix:x0_out_pix + dx_out_pix-1, y0_out_pix:y0_out_pix + dy_out_pix-1];

//   imagejpeg($arr_out_precrop, "tmp/tmp.jpg", 90);

// Clear memory

  imagedestroy($arr_out_precrop);

// Return the image.  This is a resource, not a JPEG just yet.

  return $arr_out;

}
