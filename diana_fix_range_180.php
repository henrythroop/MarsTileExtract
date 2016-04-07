<?php function diana_fix_range_180( $angle_in ) {

// Input:  Numerical value, such as a latitude or longitude
// Output: Same value, in range -180 .. 180.  
//
// HBT Uwingu 16-Aug-2010

  $angle_out = fmod($angle_in + 720., 360);	// Make sure it's positive to start
  						// Put it in range 0 .. 360.
						// NB: PHP "%" is integer mod.
						//     Need to use fmod() for a floating point mod.

  if ($angle_out > 180) { $angle_out -= 360;} 

  return ($angle_out);

}
