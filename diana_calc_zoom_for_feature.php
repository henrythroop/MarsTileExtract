<? function diana_calc_zoom_for_feature($x_deg, $y_deg, $radius_km, $pix_per_deg, $dx_pix, $dy_pix) {

// Function to calculate the zoom level required to properly dispaly a feature (ie, zoomed the max possible w/o clipping).
// Zoom is taken to be factors of two.
//
// NB: Right now not all input parameters are used.  In the future we may use them all, for more accurate calculations.
//
// HBT 23-Mar-2010 uwingu


// Make ratio # pixels of feature / # pixels of screen

  include('diana_init.php');

  $coslat = cos($y_deg * $d2r);

  $ratio_x = (2 * $radius_km * $pix_per_km / $coslat) / $dx_pix;	// Get ratio in X dir
  $ratio_y = (2 * $radius_km * $pix_per_km          ) / $dy_pix;	// Get ratio in Y dir

  $ratio = max($ratio_x, $ratio_y);

//   print "ratio = $ratio = " . 2 * $radius_km * $pix_per_km . " / $dy_pix = pix_features / pix_screen<br>";

								// Radius is in km, so doesn't matter if it's
								// X or Y radius.

  
  if  ($ratio  < 1)                      {$zoom = 1;}
  if (($ratio >= 1)   && ($ratio < 2))   {$zoom = 2;}
  if (($ratio >= 2)   && ($ratio < 4))   {$zoom = 4;}
  if (($ratio >= 4)   && ($ratio < 8))   {$zoom = 8;}
  if (($ratio >= 8)   && ($ratio < 16))  {$zoom = 16;}
  if (($ratio >= 16)  && ($ratio < 32))  {$zoom = 32;}
  if (($ratio >= 32)  && ($ratio < 64))  {$zoom = 64;}
  if (($ratio >= 64)  && ($ratio < 128)) {$zoom = 128;}
  if (($ratio >= 128) && ($ratio < 256)) {$zoom = 256;}
  if (($ratio >= 256) && ($ratio < 512)) {$zoom = 512;}
  if (($ratio >= 512) && ($ratio <1024)) {$zoom = 1024;}

  return $zoom;
}

