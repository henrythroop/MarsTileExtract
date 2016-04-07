<?php function array_extract_elements ($arr, $elements) {

// This function retrieves the elements of one array as specified by the 
// contents of another.  The following are similar:
//
// $out = array_extract_elements($arr, $elements);  [PHP]
//  out = arr[elements]				 ;  [IDL]
//
// There doesn't look like a way to do this in PHP w/o looping and extracting each element.
// Using $out = $arr[$elements] gives an error.
//
// HBT DIANA 29-Dec-2009

//   print "array_extract_elements: elements = " . sprint_array($elements) . "<br>";

  $out = array();

  foreach ($elements as $element) {
    $out[] = $arr[$element];
  }

  return $out;
}
