<?php function array_add($arr, $val) {

// Adds a constant to an array.
// This makes a copy of the array.  The array is passed in by value, not reference.
// So, the original array does *not* change.
//
// HBT DIANA 23-Dec-2009

  $out = $arr;

  foreach ($out as $elem) { $elem += $val; }

  return $out;
}
