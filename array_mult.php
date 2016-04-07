<?php function array_mult($arr, $val) {

// Multiply an array by a constant.
// This makes a copy of the array.  The array is passed in by value, not reference.
// So, the original array does *not* change.
//
// HBT DIANA 23-Dec-2009

//   printf("array_mult called with arr of length " . count($arr) . " and val $val.<br/>");
  $out = $arr;

//   printf("array_mult: arr = " . sprint_array($arr) . "<br><br>");
//   printf("array_mult: out = " . sprint_array($out) . "<br><br>");

  foreach ($out as &$elem) { 
//     printf ("Was $elem; will now be " . $elem * $val . "<br/>");
    $elem *= $val; }

//   print_r($arr);
//   print "<br>";
//   print_r($out);
//   printf("array_mult: out = " . sprint_array($out) . "<br><br>");
  return $out;
}
