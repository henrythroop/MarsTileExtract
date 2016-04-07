<?php function array_append($arr1, $arr2) {
  $out = $arr1;

  foreach ($arr2 as $element) {
    $out[] = $element;
  }

  return $out;
}
