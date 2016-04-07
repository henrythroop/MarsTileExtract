<?php function diana_get_bins($name_body, &$bins_x, &$bins_y, $style, $binning = 1){

include("diana_init.php");
include("diana_init_" . $name_body . ".php");

// $style can be "RAW SELENE DEGREES" or "TILE DEGREES"

// pro diana_get_bins, RAW_SELENE=raw_selene, TILE=tile, bins_x, bins_y, BINNING=binning, DEGREES=degrees, PIXELS=pixels, $
//     DELTA=delta

// This is a simple utility routine that returns arrays with the X and Y bins.
// It's put here in a routine since it's used frequently.
//
// The 0/360/-90/90 borders are always included, even in the case of wide bins!
// This means that sometimes the bins have different widths -- e.g., [0, 128, 256, 360] for binning=1024
// 
//  BINNING: 1=full-res, 2=half-res, ... 1024.
//   Choosing different binning definitely changes the output here.
//   Note that 'binning' refers to the zoom value.  'bin' refers to the x and y positions of the tiles,
//   or images.  Thus, 'binning' and 'bin' refer to different things.

// This routine returns the values of the bin borders.  If we have 10 bins, then this routine returns 11 values!

// OPTION: For RAW SELENE DEGREES, return the results in 3-deg increments.  3 deg is the size of the SELENE TC files.

  if (strcmp($style, "RAW SELENE DEGREES") == 0) {
//     echo "USING RAW SELENE DEGREES<br/>\n";
    $bins_x	= range(0, 360, $deg_per_file);						//  0, 3, ... 357, 360
    $bins_y	= range(-90, 90, $deg_per_file); 					//  -90, -87... 84, 87, 90
    
  }

  $debug = true;

// OPTION: For TILE DEGREES, return the bins of the tiles. This depends on the value of $binning.  For $binning=1, they're in 0.125 deg increments.
// This whole thing is a bit tricky because of border conditions: we want nothing greater than +- 90, but we do want the edges themselves to be
// exactly at 90.  I have a simple algo in DIANA_GET_BINS.PRO (IDL) to do this, but for PHP version, I decided to just hardcode the array values.
// It might have been better to bring the algorithm over, but this works too.
// For small $binning values, the borders naturally fall exactly on +- 90 as it is, so for those we create the range algorithmically.

  if (strcmp($style, "TILE DEGREES") == 0) {
//     echo "USING TILE DEGREES<br/>\n";

//     $num_bins_x	= round((360 / $step_x_deg) + 0.499);				// # of bins at this zoom level
//     $num_bins_y	= floor(90 / $step_y_deg + 0.9999) * 2 + 1;			// # of bins at this zoom level

    print "diana_get_bins: dy_tile_pix/pix_per_deg * binning = " . $dy_tile_pix . " / " . $pix_per_deg . " * " . $binning . " = " . 
            $dy_tile_pix/$pix_per_deg*$binning . "<br>\n";


    switch ($binning) {
      case 1:    $bins_y = range_robust(-90, 90, $dy_tile_pix/$pix_per_deg * $binning);
                 $bins_x = range_robust(0,  360, $dx_tile_pix/$pix_per_deg * $binning);
                 break;

      case 2:    $bins_y = range_robust(-90, 90, $dy_tile_pix/$pix_per_deg * $binning);
                 $bins_x = range_robust(0,  360, $dx_tile_pix/$pix_per_deg * $binning);
                 break;

      case 4:    $bins_y = range_robust(-90, 90, $dy_tile_pix/$pix_per_deg * $binning);
                 $bins_x = range_robust(0,  360, $dx_tile_pix/$pix_per_deg * $binning);
                 break;

      case 8:    $bins_y = range_robust(-90, 90, $dy_tile_pix/$pix_per_deg * $binning);
                 $bins_x = range_robust(0,  360, $dx_tile_pix/$pix_per_deg * $binning);
                 break;

      case 16:   $bins_y = range_robust(-90, 90, $dy_tile_pix/$pix_per_deg * $binning);
                 $bins_x = range_robust(0,  360, $dx_tile_pix/$pix_per_deg * $binning);
                 break;
		 
      case 32:   $bins_x = array(0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 80, 84, 88, 92, 96, 100, 104, 108, 
                                 112, 116, 120, 124, 128, 132, 136, 140, 144, 148, 152, 156, 160, 164, 168, 172, 176, 180, 184, 188, 192, 196, 
				 200, 204, 208, 212, 216, 220, 224, 228, 232, 236, 240, 244, 248, 252, 256, 260, 264, 268, 272, 276, 280, 284, 
				 288, 292, 296, 300, 304, 308, 312, 316, 320, 324, 328, 332, 336, 340, 344, 348, 352, 356, 360);
                 $bins_y = array(-90, -88, -84, -80, -76, -72, -68, -64, -60, -56, -52, -48, -44, -40, -36, -32, -28, -24, -20, -16, -12, -8, -4, 
		                 0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 80, 84, 88, 90);
		 break;

      case 64:   $bins_x = array( 0, 8, 16, 24, 32, 40, 48, 56, 64, 72, 80, 88, 96, 104, 112, 120, 128, 136, 144, 152, 160, 168, 176, 184, 192, 
                                  200, 208, 216, 224, 232, 240, 248, 256, 264, 272, 280, 288, 296, 304, 312, 320, 328, 336, 344, 352, 360);
                 $bins_y = array( -90, -88, -80, -72, -64, -56, -48, -40, -32, -24, -16, -8, 0, 8, 16, 24, 32, 40, 48, 56, 64, 72, 80, 88, 90 );
	         break;

      case 128:  $bins_x = array(0, 16, 32, 48, 64, 80, 96, 112, 128, 144, 160, 176, 192, 208, 224, 240, 256, 272, 288, 304, 320, 336, 352, 360);
                 $bins_y = array(-90, -80, -64, -48, -32, -16, 0, 16, 32, 48, 64, 80, 90);
		 break;

      case 256:  $bins_x = array(0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 360);
                 $bins_y = array(-90, -64, -32, 0, 32, 64, 90);
		 break;

      case 512:  $bins_x = array(0, 64, 128, 192, 256, 320, 360);
                 $bins_y = array(-90, -64, 0, 64, 90);
		 break;

      case 1024: $bins_x = array(0, 128, 256, 360);
                 $bins_y = array(-90, 0, 90);
		 break;
    }
  }
}


//     $step_x_deg	= $dx_tile_pix / $pix_per_deg * $binning; 			// Tile dimensions
//     $step_y_deg	= $dy_tile_pix / $pix_per_deg * $binning; 			// Tile dimensions

//     $bin_tiles_x_deg	= dindgen($num_bins_x + 1) * ($dx_tile_pix/$pix_per_deg) * $binning;
//     $bin_tiles_x_deg	= ($bin_tiles_x_deg > 0) < 360;

//     $bin_tiles_x_deg	= range(0, 360, $dx_tile_pix/$pix_per_deg * $binning;
//     echo "bin_tiles_x_deg:";
//     print_array($bin_tiles_x_deg);

//     $bin_tiles_y_deg	= (1 + dindgen($num_bins_y/2)) * ($dy_tile_pix/$pix_per_deg) * $binning;
//     $bin_tiles_y_deg	= -array_reverse($bin_tiles_y_deg) +  0 + $bin_tiles_y_deg;
//     $bin_tiles_y_deg	= ($bin_tiles_y_deg > (-90)) < 90;
// 
//     $bins_x	= $bin_tiles_x_deg;				// 0, 0.5, 1.0, ... 359.5, 360
//     $bins_y	= $bin_tiles_y_deg;				// -90, -89.5, ..., 89, 89.5, 90
                               	 	 			// -90, -89.5, ..., 89, 89.5, 90
// pro other
//   diana_get_bins, /raw_selene, /degrees, x, y, binning=1
//   diana_get_bins, /tile, /degrees, x, y, binning=64
// end
// 
// ;     bin_tiles_y_deg	= [ reverse((-dindgen(num_tiles_y/2d) * (dy_tile_pix/pix_per_deg) * binnings[i])[1:*]), $
// ;                             dindgen(num_tiles_y/2d) * (dy_tile_pix/pix_per_deg) * binnings[i]]

