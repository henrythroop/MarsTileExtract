<?php function imagestring_double($im, $font, $x, $y, $string, $color1, $color2) {

// Prints a string to an image using GD 'imagestring' function.
// However, also prints a 'background' image in (usually) contrasting color.  For instance, print black text, with a 'background' halo of white text.
// This makes the text visible regardless of what's behind it.  Sort of like subtitles on foreign films.
//
// HBT uwingu 13-May-2010.

  imagestring($im, $font, $x+1, $y, $string, $color2);
  imagestring($im, $font, $x-1, $y, $string, $color2);
  imagestring($im, $font, $x, $y+1, $string, $color2);
  imagestring($im, $font, $x, $y-1, $string, $color2);

  imagestring($im, $font, $x, $y, $string, $color1);

}

