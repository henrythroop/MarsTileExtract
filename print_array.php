<?

// Print every element of an array, separated by \n.
//
// NB: Can also use built-in command print_r($array).  That explicitly
//     shows the keys, element #'s, etc.  print_r also works for object, string, etc.
//     'print readable'
//
// HBT DIANA 22-Dec-2009

function print_array($arr) {
  foreach ($arr as $element) {
    echo $element . "\n";
  }
}
?>
