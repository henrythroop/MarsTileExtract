<?php function range_robust($min, $max, $step) {

// Function works just like range(), except if the binsize is larger than difference between
// min and max, it returns a two-element array, rather than range(), which gives an error and 
// returns I think an empty array during this case. 
//
// This is the main routine used to generate the boundareis of the bins that Uwingu uses.  It generates
// the inclusive boundaries of bins -- that is, including star and end locations.  
// range_robust(-90, 90, 50) = [-90,-40,10,60,90]
//
// This routine was not working right for a long time, which is why the n pole of Moon was inaccessible to 
// uwingu for a long time.  Fixed now.
//
// HBT Uwingu 5-Sep-2010

  $debug = 0;

  $range = $max-$min;
  if ($step > $range) {
    $out = array($min, $max);
    if ($debug) {
      print "range_robust(" . $min . " " . $max . " " . $step . "): ";
      print_array($out);
      print "<br>";
    }
  } else {
    $out = range($min, $max, $step);
  }

// Now see if the final bin is in the array, or not.  If it's not, it should be, and we put it there.

  if (max($out) != $max) {
    $out[] = $max;
  }

  return $out;
}

