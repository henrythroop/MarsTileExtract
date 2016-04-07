<? function where($arr, $comparison, $val, $tag = '') {

// WHERE.PHP
//
// Approximates some of the functionality of where() in IDL.
//
// Example: $elements = where($arr, 'GE', 2)    // Returns indices of $arr where $arr >= 2
//
// Note that in IDL, it's valid to compare an arry and value in any order (e.g., where(arr ge 6) or where(val gt vals)).
// But in this PHP function, the array must always come first.
//
// HBT DIANA 32-Dec-2009

  $n = count($arr);

  $out = array();

  switch ($comparison) {
  case 'GE' : 

             for ($i=0; $i < $n; $i++) {
//  	        echo "$tag Comparing element #$i " . $arr[$i] . " with $val.<br/>";
                if ($val >= $arr[$i]) {
		  $out[] = $i;
		}
     	     }
	     break;
  }

  return $out;
}
?>
