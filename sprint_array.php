<?

// Print every element of an array, separated by spaces, into a string.
//
// NB: Can also use built-in command print_r($array).  That explicitly
//     shows the keys, element #'s, etc.  print_r also works for object, string, etc.
//     'print readable'
//
// HBT DIANA 22-Dec-2009

function sprint_array($arr, $char = " ") {
  $out	= "";

  foreach ($arr as $element) {
    $out = $out . $char . $element;
  }

  return $out;
}
?>
