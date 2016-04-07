<? function diana_midway_360($x1, $x2) 

{

// Returns the midway point between two longitudes.  Works properly over a 0-360 boundary.
// e.g., diana_midway_360(359,2) = 0.5
//
// HBT Uwingu 19-Oct-2010

  if ($x1 < $x2) {
    $out = ($x1 + $x2) / 2.;
  } 

  if ($x1 > $x2) {
    $out	= ($x1 + 360 + $x2) / 2.;
  }

  if ($out >= 360) {$out -= 360;}
  if ($out <    0) {$out += 360;}

  return $out;
}
?>
