<?php
/*
This class is used to extract tiles and build an image of a specific, labeled crater. Its intended use
is for generating crater images for certificates and other instances where an image of a specific crater
is needed. The code is based on the DIANA functions originally written by Henry Throop.

This class is not used as part of the Uwingu Mars map itself.

TJMB 11/24/2014
*/

// Avoid warning for a case where I have a variable with the same name as a session variable.
// See http://stackoverflow.com/questions/175091/php-session-side-effect-warning-with-global-variables-as-a-source-of-data 
ini_set('session.bug_compat_warn', 0);
ini_set('session.bug_compat_42', 0);

// Set timezone lest PHP complain...
date_default_timezone_set("UTC");

class MarsTileExtract {

	// Initialize all of our constants and other variables
// 	var $dir_tiles = '/mnt/tiles_mars_aug14/';
	var $dir_tiles = '/Users/throop/Uwingu/Mars/tiles/jpg/';

	// Set up a tmp directory.  This is where all the images that I generate for the cruiser are stored.
	// They can be deleted immediately after they are created one time.
	// Also make a URL for tmp. This is what goes in the <img> tag and cannot be a full path name.

// 	var $dir_home = '/mnt/www/';
// 	var $dir_tmp = '/var/www/certificate/maptmp/';

	var $dir_home = '/Users/throop/DIANA/';
	var $dir_tmp = 'Users/throop/DIANA/tmp/';
	var $url_tmp = 'tmp/';		

	// Set up a thumbnails directory
 	var $dir_thumbnails	= '/Users/throop/DIANA/MarsTileExtract/TB_Feb15/certificate/mapimg/';
//  	var $dir_thumbnails	= '/Users/throop/DIANA/thumbnails/';
	var $url_thumbnails = 'certificate/mapimg/';

	// Variables for diameter/radius conversions
	var $d2r; // = M_PI / 180 = 0.017453292519943;
	var $r2d; // = 180 / M_PI = 57.295779513082;

	// Flags for type of tile to make and output quality
	var $do_tile_img = 0;
	var $do_tile_jpg = 1;
	var $quality_tile_jpg = 90;		// 0 .. 100.  Quality of the output jpeg files for tiles.
	var $max_dn_tile_jpg = 10000;	// Maximum DN value for a tile output.  That is,
	  								// values of MAX_DN_TILE (or more) are scaled to 256 in the JPG.
	var $dn_color_grey;				// Set the intensity level for 'grey' color, to indicate missing data
	var $dn_color_white	= 32766;	// INTARR value for white.

	// Do any body-specific setup here: directories, etc.
	var $name_body = 'Mars_UT';
	var $name_body_short = 'Mars';
	var $file_header	= "mars";	

	// set tile dimensions
	var $dx_tile_pix = 384;
	var $dy_tile_pix = 384;

	// Mean equatorial radius of mars (from Wikipedia)
	var $radius_body_km	= 3389.5;

	var $pix_per_deg = 512;
	var $deg_per_pix;

	var $pix_per_km;
	var $km_per_pix;

	var $y_max_deg = 90;
	var $y_min_deg = -90;

	var $x_min_deg = 0;
	var $x_max_deg = 360;

	// Set the image size
	var $zoom_min = 1;
	var $zoom_max = 2;

	// Set the Province/District/Precinct sizes, in degrees
	// 45 x 45 deg -> 32 regions on body.

	var $dx_province = 45;
	var $dy_province = 45;

	var $dx_district = 15;
	var $dy_district = 15;

	var $dx_precinct = 5;
	var $dy_precinct = 5;


	// Set some variable values using other defined variables
/*	public function __construct() {		
		$d2r = M_PI /180;	// diameter to radius calculation
		$r2d = 180 / M_PI;	// radius to diameter calculation

		// Set the intensity level for 'grey' color, to indicate missing data
		$dn_color_grey = $this->max_dn_tile_jpg/3;

		$deg_per_pix = 1/$this->pix_per_deg;

		$pix_per_km	= $this->pix_per_deg * 360 / (2 * M_PI * $this->radius_body_km);

		$km_per_pix	= 1/$this->pix_per_km;
	}

*/
	// Begin class functions

	function dindgen($num){
		// DINDGEN Works just like IDL's.
		// HBT DIANA 21-Dec-2009

		return range(0, $num-1, 1);		// Low, High, [Step]
	} // end dindgen


	function diana_create_filename_tile($name_body, $x0, $x1, $y0, $y1, $binning) {  // , QUIET=quiet, JPG=jpg, IMG=img

		// This returns the entire path for a tile file; e.g., 'data/tiles/3/X000.125_000.250/TC_EVE_02_X000.125......B0002.img'
		// This routine should be used every time we create or read a tile. 
		//
		// HBT DIANA 25-Nov-2009
		// Updated for MarsNameBW 11-Aug-2014
		//
		// Usually this will be JPG: they are smaller, and have the x/y dimensions encoded within them.
		// IMG files are larger, have no explicit size (just list of bytes), but they do store 16 bits/pix, not 8.

                print "d_c_f_t called with x0 = $x0, x1 = $x1, y0 = $y0, y1 = $y1, binning = $binning <br>\n";
		print "name_body = $name_body<br>\n";

		$extension = ".jpg"; // This routine works only for JPGs, not for IMGs as well.

		$x0_str = str_replace(" ", "0", sprintf($x0));  // To print "3.5" and "3.75" with an auto-width, like I want, no args to sprintf
		$y0_str = str_replace(" ", "0", sprintf($y0));

		$x1_str = str_replace(" ", "0", sprintf("%f", $x1));
		$y1_str = str_replace(" ", "0", sprintf("%f", $y1));

		$y0_str  = str_replace('0-', '-0', $y0_str);
		$y1_str  = str_replace('0-', '-0', $y1_str);
		$y0_str  = str_replace('_0-', '_-0', $y0_str);
		$y1_str  = str_replace('_0-', '_-0', $y1_str);

		$x0_str  = str_replace('0-', '-0', $x0_str);
		$x1_str  = str_replace('0-', '-0', $x1_str);
		$x0_str  = str_replace('_0-', '_-0', $x0_str);
		$x1_str  = str_replace('_0-', '_-0', $x1_str);

		// Trim trailing 0's on RHS. Except we don't want to trim a *single* zero down to '0'
		$x1_str = rtrim($x1_str, '0'); if (strlen($x1_str) == 0) { $x1_str = "0"; }
		$x0_str = rtrim($x0_str, '0'); if (strlen($x0_str) == 0) { $x0_str = "0"; }
		$y1_str = rtrim($y1_str, '0');
		$y0_str = rtrim($y0_str, '0');

		$x1_str = rtrim($x1_str, '.'); 
		$x0_str = rtrim($x0_str, '.'); 
		$y1_str = rtrim($y1_str, '.');
		$y0_str = rtrim($y0_str, '.');

		$bin_str1	= sprintf("%d", $binning);
		$bin_str2	= sprintf("%5d", $binning);
		$bin_str2	= str_replace(" ", "0", $bin_str2);

		$x_str 	= $x0_str . "_" . $x1_str;
		$y_str 	= $y0_str . "_" . $y1_str;
		 
		// Create the directory name ('/lat30_lon120'). 
		// Careful: the latitude here is the latitude of the *bottom*, not the lat of the *top*, which is used in the filename
		// ** This was the problem 12-Feb-2015. For some reason the modulo operator % works a bit funny in PHP -- different for negatives
		// than positives. Thus the bin # was being computed improperly for negative $y0 (off-by-one). 

		if ($y0 >= 0) { $bin_lat = ($y0) - (($y0) % 30);     } // Divide into bins of 30 deg
		if ($y0 < 0)  { $bin_lat = ($y0) - (($y0) % 30) - 30;} // Divide into bins of 30 deg
		$bin_lon = $x0 - ($x0 % 60);                           // Divide into bins of 60 deg
                
		print "bin_lat = $bin_lat<br>\n";
		print "y0 = $y0; y0 % 30 = " ; print ($y0 % 30); print  "<br>\n";

		$bin_lat_str = str_replace(" ", "0", sprintf("%2d", abs($bin_lat)));
		if ($bin_lat < 0) { 
			$bin_lat_str = "-" . $bin_lat_str;
		}

		$bin_lon_str = str_replace(" ", "0", sprintf("%3d", $bin_lon));

		//   print "diana_create_filename_tile: dir_tiles = $this->dir_tiles";
		switch ($name_body) {
			case "Moon_SELENE":
				// /Volumes/UwinguExternal/data/Moon_SELENE/tiles/4/X022.500_023.000/TC_EVE_02_X022.500_023.000__Y-03.000_-02.500_B00004.jpg
				$file = $this->dir_tiles . $bin_str1 . "/X" . $x_str . "/TC_EVE_02_X" . $x_str . 
				"__Y" . $y_str . "_B" . $bin_str2 . $extension;
				break;

			case "Mimas":
			// /Volumes/UwinguExternal/data/Mimas/tiles/4/Mimas_X347.883_360.000__Y173.996_180.000_B00004.jpg
			$file = $this->dir_tiles . $bin_str1 .                "/Mimas_X" . $x_str . "__Y" . $y_str . "_B" . $bin_str2 . $extension;
			break;

			case "Mars_BW": // BatWorks version of Mars Namer. I will have a new case for the LeafletJS tiles soon.
			case "Mars_UT":	// BatWorks version of Mars Namer. I will have a new case for the LeafletJS tiles soon.
				// For Mars_BW: Tiles are named "mars_merged_<scale>_<lat_top>_<lon_left>.png" .
				// e.g., /Users/throop/Uwingu/Mars/tiles/jpg/lat30_lon120/mars_merged_8_60_174.jpg
				$file = $this->dir_tiles . "lat" . $bin_lat_str . "_lon" . $bin_lon_str . '/' . 
				"mars_merged_" . $bin_str1 . "_" . $y1_str . "_" . $x0_str . ".jpg";
				break;
		}

		print "diana_create_filename_tile: $file\n";
		return $file;

	} // end diana_create_filename_tile


	function print_array($arr) {
		// Print every element of an array, separated by \n.
		// NB: Can also use built-in command print_r($array). That explicitly shows the keys,
		//	   element #'s, etc. print_r also works for object, string, etc. 'print readable'
		// HBT DIANA 22-Dec-2009
		foreach ($arr as $element) {
			echo $element . "\n";
		}
	} // end print_array

	function diana_get_bins($name_body, &$bins_x, &$bins_y, $style, $binning = 1){
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
			// echo "USING TILE DEGREES<br/>\n";

			// $num_bins_x	= round((360 / $step_x_deg) + 0.499);				// # of bins at this zoom level
			// $num_bins_y	= floor(90 / $step_y_deg + 0.9999) * 2 + 1;			// # of bins at this zoom level

		    print "diana_get_bins: dy_tile_pix/pix_per_deg * binning = " . $this->dy_tile_pix . " / " . $this->pix_per_deg . " * " . $binning . " = " . 
		            $this->dy_tile_pix/$this->pix_per_deg*$binning . "<br>\n";


		    switch ($binning) {
				// Added 0.5 case, consolidated cases 0.5-16 into one - TJMB 12/15/2014
				case 0.5:
				case 1:
				case 2:
				case 4:
				case 8:
				case 16:
					$bins_y = $this->range_robust(-90, 90, $this->dy_tile_pix/$this->pix_per_deg * $binning);
					$bins_x = $this->range_robust(0,  360, $this->dx_tile_pix/$this->pix_per_deg * $binning);
					break;

				case 32:
					$bins_x = array(0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 80, 84, 88, 92, 96, 100, 104, 108, 
									112, 116, 120, 124, 128, 132, 136, 140, 144, 148, 152, 156, 160, 164, 168, 172, 176, 180, 184, 188, 192, 196, 
									200, 204, 208, 212, 216, 220, 224, 228, 232, 236, 240, 244, 248, 252, 256, 260, 264, 268, 272, 276, 280, 284, 
									288, 292, 296, 300, 304, 308, 312, 316, 320, 324, 328, 332, 336, 340, 344, 348, 352, 356, 360);
					$bins_y = array(-90, -88, -84, -80, -76, -72, -68, -64, -60, -56, -52, -48, -44, -40, -36, -32, -28, -24, -20, -16, -12, -8, -4, 
									0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 80, 84, 88, 90);
					break;

				case 64:
					$bins_x = array( 0, 8, 16, 24, 32, 40, 48, 56, 64, 72, 80, 88, 96, 104, 112, 120, 128, 136, 144, 152, 160, 168, 176, 184, 192, 
									200, 208, 216, 224, 232, 240, 248, 256, 264, 272, 280, 288, 296, 304, 312, 320, 328, 336, 344, 352, 360);
					$bins_y = array( -90, -88, -80, -72, -64, -56, -48, -40, -32, -24, -16, -8, 0, 8, 16, 24, 32, 40, 48, 56, 64, 72, 80, 88, 90 );
					break;

				case 128:
					$bins_x = array(0, 16, 32, 48, 64, 80, 96, 112, 128, 144, 160, 176, 192, 208, 224, 240, 256, 272, 288, 304, 320, 336, 352, 360);
					$bins_y = array(-90, -80, -64, -48, -32, -16, 0, 16, 32, 48, 64, 80, 90);
					break;

				case 256:
					$bins_x = array(0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 360);
					$bins_y = array(-90, -64, -32, 0, 32, 64, 90);
					break;

				case 512:
					$bins_x = array(0, 64, 128, 192, 256, 320, 360);
					$bins_y = array(-90, -64, 0, 64, 90);
					break;

				case 1024:
					$bins_x = array(0, 128, 256, 360);
					$bins_y = array(-90, 0, 90);
					break;
			}
		}

		echo "<hr><p>diana_get_bins debug:</p>";
		echo "<p>Name Body: $name_body</p>";
		echo "<p>Bins X: ".print_r($bins_x,1)."</p>";
		echo "<p>Bins Y: ".print_r($bins_y,1)."</p>";
		echo "<p>Style: $style</p>";
		echo "<p>Binning: $binning</p><hr>";

	} // end diana_get_bins


	function where($arr, $comparison, $val, $tag = '') {
		// Approximates some of the functionality of where() in IDL.
		//
		// Example: $elements = where($arr, 'GE', 2)    // Returns indices of $arr where $arr >= 2
		//
		// Note that in IDL, it's valid to compare an arry and value in any order (e.g., where(arr ge 6) or $this->where(val gt vals)).
		// But in this PHP function, the array must always come first.
		//
		// HBT DIANA 32-Dec-2009

		$n = count($arr);

		$out = array();

		switch ($comparison) {
			case 'GE' : 

				for ($i=0; $i < $n; $i++) {
					if ($val >= $arr[$i]) {
						$out[] = $i;
					}
				}
				break;
		}

		return $out;
	} // where


	function diana_load_tiles($name_body, $x0_deg_in, $x1_deg_in, $y0_deg_in, $y1_deg_in, $zoom) {

		// This takes a range of x and y, and loads all of the tile data specified
		// 
		// Required inputs:
		//   x0_deg: left edge, in degrees
		//   x1_deg: right edge, in degrees
		//   y0_deg: bottom edge, in degrees
		//   y1_deg: top edge, in degrees
		//   zoom: 1 = full-res; 2 = half-res; etc.
		// 
		// The number of pixels is not required -- it can be computed directly from the inputs.
		// 
		// Q: What do we do if the requested range is beyond what is possible?  e.g., 91 deg north?
		// A: We just return a blank region there, and keep working like normal.
		// 
		// 
		// HBT DIANA 25-Nov-2009
		//           23-Dec-2009 Converted to PHP

		$debug = true;

		// Initialize inputs
		// Put the ranges in proper order.  That is, x0 = 300, x1 = 60, then this is OK!
		// We convert that into x0=-60, so that at least x1 > x0.

		$x0_deg	= $x0_deg_in;
		$x1_deg	= $x1_deg_in;
		$y0_deg	= $y0_deg_in;
		$y1_deg	= $y1_deg_in;

		if ($x0_deg > $x1_deg) {
			$x0_deg	-= 360;
		}


		// Calculate the actual width, in degrees, of the output.  This value is correct, indep. of wrapping.
		$dx_deg	= $x1_deg - $x0_deg;
		$dy_deg	= $y1_deg - $y0_deg;

		// Calculate the actual width, in pixels, of the output.
		$dx_out_pix	= $dx_deg * $this->pix_per_deg / $zoom;
		$dy_out_pix	= $dy_deg * $this->pix_per_deg / $zoom;

		if ($debug) {
			printf("<br/> <br> Starting diana_load_tiles(x0=%f, x1=%f, y0=%f, y1=%f, zoom=%f)<br/>\n", 
				$x0_deg_in, $x1_deg_in, $y0_deg_in, $y1_deg_in, $zoom);
			printf("<br/> diana_load_tiles: Creating image of size %f x %f pix, based on pix_per_deg = %f.<br/>\n", 
				$dx_out_pix, $dy_out_pix, $this->pix_per_deg);
		}

		// Create the output array
		$arr_out	= imagecreatetruecolor($dx_out_pix, $dy_out_pix);

		// Wrap the x values, as needed: x=-1 -> x=359.  Y values do not need forcing.
		$x0_deg	= fmod(((fmod($x0_deg, 360.0)) + 360.0), 360.0);		// Force to range 0..360
		$x1_deg	= fmod(((fmod($x1_deg, 360.0)) + 360.0), 360.0);		// Force to range 0..360

		// Get a list of all the bins for this zoom level.
		$this->diana_get_bins($name_body, $bin_tiles_x_deg, $bin_tiles_y_deg, "TILE DEGREES", $zoom);

		// Calculate the width and height of each tile
		echo "<p>PIX/DEG = $this->pix_per_deg</p>";
		echo "<p>ZOOM = $zoom</p>";
		$multiplier = ($this->pix_per_deg/$zoom);
		echo "<p>MULTIPLIER = $multiplier</p>";
		echo "<p>XDEG = " . print_r($bin_tiles_x_deg,1) ."</p>";


		$bin_tiles_x_pix	= $this->array_mult($bin_tiles_x_deg, ($this->pix_per_deg/$zoom));
		$bin_tiles_y_pix	= $this->array_mult($bin_tiles_y_deg, ($this->pix_per_deg/$zoom));

		// Calculate the width in pixels of the 0th tile (from bin 0 to bin 1).  This array is one element shorter than the 
		// bin values array: In x dir, the 0th and final bins are equal (0 & 360 deg).
		//                  In y dir, there is no wraparound from -90 to +90, so it does not make sense to define a width.
		//
		// PHP: See array_shift, array_push

		$this->dx_tile_pix	= array_pad(array(), count($bin_tiles_x_pix) - 1, 0);
		$this->dy_tile_pix	= array_pad(array(), count($bin_tiles_y_pix) - 1, 0);

		for ($i = 0; $i < count($this->dx_tile_pix); $i++){ $this->dx_tile_pix[$i] = $bin_tiles_x_pix[$i+1] - $bin_tiles_x_pix[$i]; }
		for ($i = 0; $i < count($this->dy_tile_pix); $i++){ $this->dy_tile_pix[$i] = $bin_tiles_y_pix[$i+1] - $bin_tiles_y_pix[$i]; }
	    
		// Calculate the number of tiles (which is one less than the number of bins)
		// PHP: count() is same as sizex() or n_elements().

		$num_tiles_x	= count($bin_tiles_x_deg)-1;
		$num_tiles_y	= count($bin_tiles_y_deg)-1;

		// Now figure out what bins our corners are in: get the bins for min & max, x & y.
		// All these bins will be positive.
		// These bin numbers are for the tiles (e.g., 0.125 deg, or 0.25 deg, etc.)
		//
		// PHP: max() is the highest value in the array.

		$delta		= 1e-5;

		$w 		= $this->where($bin_tiles_y_deg, 'GE', $y0_deg);

		$bin_x0	= max($this->where($bin_tiles_x_deg, 'GE', $x0_deg));
		$bin_x1	= max($this->where($bin_tiles_x_deg, 'GE', $x1_deg-$delta))+1;
		$bin_y0	= max($this->where($bin_tiles_y_deg, 'GE', $y0_deg, 'bin_y0: '));
		$bin_y1	= max($this->where($bin_tiles_y_deg, 'GE', $y1_deg-$delta))+1;

		// XXX problem above: if y1_deg > 90, then bin_y1 can exceed the # of bins we actually have
		// Set a flag to indicate whether we are wrapping around in the X direction.

		$is_wrap_x	= ($bin_x0 > $bin_x1);		//  ? 1 : 0;			// Flag

		// Now make a list of all the x bins we need to load, and all the y bins.
		// Note that we are omitting here the final bin, since we dont need to load that one.

		// X bins case: if we have no wraparound -- just regular bins
		if (!($is_wrap_x)) {
			$bins_x	= range($bin_x0, $bin_x1-1, 1);	// A list of the bin numbers we will use in x dir
		}

		// X bins case: if we have a wraparound
		// This logic will not handle *double* wraparounds -- e.g., bins [1, 0, 1].  This happens easily for zoom = 1024.
		if ($is_wrap_x) {
			$bins_x	= range($bin_x0, $num_tiles_x-1, 1);
			$bins_x = $this->array_append($bins_x, $this->dindgen($bin_x1 + 1));
		}

		$bins_y	= range($bin_y0, $bin_y1-1, 1);			// XXX was bin_y1, not bin_y1-1

		// Create a large array, in which we will load all of our tiles, and put them all together next to each other in order.
		// THIS VALUE FOR ARR_OUT_PRECROP reflects that not all tiles have the same width!
		$sizex_precrop	= array_sum($this->array_extract_elements($this->dx_tile_pix, $bins_x));

		// print "diana_load_tiles: calling array_extract_elements on dy_tile_pix<br>";
		$sizey_precrop	= array_sum($this->array_extract_elements($this->dy_tile_pix, $bins_y));

		$arr_out_precrop	= imagecreatetruecolor($sizex_precrop, $sizey_precrop);

		// print "diana_load_tiles: created arr_out_precrop with size " . imagesx($arr_out_precrop) . " x " . imagesy($arr_out_precrop) . "<br/>";

		// Now loop over all of the bins.  Each bin here corresponds to exactly one tile.
		$k = 0;

		for ($i = 0; $i < count($bins_x); $i++){
			for ($j = 0; $j < count($bins_y); $j++){
				$x0_in_deg	= $bin_tiles_x_deg[$bins_x[$i]];
				$x1_in_deg	= $bin_tiles_x_deg[$bins_x[$i]+1];
				$y0_in_deg	= $bin_tiles_y_deg[$bins_y[$j]];
				$y1_in_deg	= $bin_tiles_y_deg[$bins_y[$j]+1];

				// Create the proper filename, and read it
				$file_in = $this->diana_create_filename_tile($name_body, $x0_in_deg, $x1_in_deg, $y0_in_deg, $y1_in_deg, $zoom);

				if ($debug) {print "<br>diana_load_tiles: bin_tiles_x_deg =<br>\n";}
				if ($debug) {print "<br>diana_load_tiles: bin_tiles_y_deg =<br>\n";}
				if ($debug) {print "<br>diana_load_tiles: name_body = $name_body, x0_in_deg = $x0_in_deg; " .
					"x1_in_deg = $x1_in_deg, y0_in_deg = $y0_in_deg, y1_in_deg=$y1_in_deg, zoom = $zoom.<br/>\n";}
				if ($debug) {print "<br>diana_load_tiles: Created filename $file_in .\n";}
	 
				$arr_in	= $this->diana_read_tile($name_body, $file_in);			// Load 512x512 image.  If file missing, still works

				// Now copy this array into the target array.  It goes into [left x pos of i'th bin : (left x pos of i+1 bin) -1 ]
				// array_slice($arr, $offset, $length)

				// If i==0 or j==0, then the x or y index for where this gets copied into the output array is zero, since it's at the top or left.
				// If i or j isn't 0, then we count across so many pixels, summing the number of bins.

				if ($i == 0) { $x0_out_pix	= 0;}
				if ($i >  0) { $x0_out_pix	= array_sum(
					$this->array_extract_elements($this->dx_tile_pix, array_slice($bins_x, 0, $i))); }	// $i steps, which takes us over 0:i-1
	      
				if ($i == 0) { $x1_out_pix	= $this->dx_tile_pix[$bins_x[0]]-1; }

				if ($i >  0) { $x1_out_pix	= array_sum(
					$this->array_extract_elements($this->dx_tile_pix, array_slice($bins_x, 0, $i+1)))-1; }

				if ($j == 0) { $y0_out_pix	= 0; }
				$elements = array_slice($bins_y, 0, $j+1);

				if ($j >  0) { $y0_out_pix	= array_sum(
					$this->array_extract_elements($this->dy_tile_pix,  array_slice($bins_y, 0, $j))); }
	      
				if ($j == 0) { $y1_out_pix	= $this->dy_tile_pix[$bins_y[0]]-1; }
				if ($j >  0) { $y1_out_pix	= array_sum(
			        $this->array_extract_elements($this->dy_tile_pix, array_slice($bins_y, 0, $j+1)))-1; }

				//  Position is properly calculated.  But oops!  we want to place these counting not from LL corner, but from UL.
				$y0_out_pix	= $sizey_precrop - $y0_out_pix - ($y1_out_pix - $y0_out_pix);

				if(imagecopy($arr_out_precrop, $arr_in, $x0_out_pix, $y0_out_pix, 0, 0, imagesx($arr_in), imagesy($arr_in))) {
					imagedestroy($arr_in);
				}
				else {
					print "diana_load_tiles: failure: not copied<br/>";
					print "deg_in: x0 = $x0_deg_in, x1 = $x1_deg_in, y0 = $y0_deg_in, y1 = $y1_deg_in, zoom = $zoom<br>";
				}
			}
		}

		// Now that we've loaded all the tiles, crop the output array into the proper array we want.
		$x0_deg_precrop = $bin_tiles_x_deg[$bins_x[0]];		// Get the min x value for all the data read in
		$y0_deg_precrop = ($bin_tiles_y_deg[$bins_y[0]]);	// Get the min y value for all the data read in
		$x0_out_pix	= ($x0_deg - $x0_deg_precrop) * $this->pix_per_deg / $zoom;
		$y0_out_pix	= ($y0_deg - $y0_deg_precrop) * $this->pix_per_deg / $zoom;

		imagecopy($arr_out, $arr_out_precrop, 0, 0, $x0_out_pix, imagesy($arr_out_precrop) - $y0_out_pix - $dy_out_pix, $dx_out_pix, $dy_out_pix);

		// Clear memory
		imagedestroy($arr_out_precrop);

		// Return the image.  This is a resource, not a JPEG just yet.
		return $arr_out;

	} // end diana_load_tiles


	function array_mult($arr, $val) {
		// Multiply an array by a constant.
		// This makes a copy of the array.  The array is passed in by value, not reference.
		// So, the original array does *not* change.
		//
		// HBT DIANA 23-Dec-2009

		$out = $arr;

		foreach ($out as &$elem) { 
			$elem *= $val;
		}
		return $out;
	} // end array_mult

	function array_add($arr, $val) {
		/************** DOES NOT APPEAR TO BE USED, MAY BE ABLE TO EXCLUED FROM FINAL CLASS *************/

		// Adds a constant to an array.
		// This makes a copy of the array.  The array is passed in by value, not reference.
		// So, the original array does *not* change.
		//
		// HBT DIANA 23-Dec-2009

		$out = $arr;

		foreach ($out as $elem) {
			$elem += $val;
		}

		return $out;
	} // end array_add

	function diana_set_defaults(&$lon, &$lat, &$zoom) {
		/************** DOES NOT APPEAR TO BE USED, MAY BE ABLE TO EXCLUED FROM FINAL CLASS *************/
		// Set default center position, etc. for DIANA
		// Variables are passed by reference (not value), so that they are set and returned to caller.
		//
		// HBT DIANA 28-Dec-2009

		$lon_default	= 2;
		$lat_default	= 0;
		$zoom_default = 1;

		if (!isset($lon)) { $lon = $lon_default; }
		if (!isset($lat)) { $lat = $lat_default; }
		if (!isset($zoom)) { $zoom = $zoom_default; }
	} // diana_set_defaults

	function diana_read_tile($name_body, $file_in){

		// This routine opens a tile file, and returns it in a GD image resource.  
		// If the file doesn't exist, then it retuns a blank image of the same size,
		// which may have a rectangular border drawn around it.
		//
		// HBT DIANA 27-Nov-2009
		//           29-Dec-2009 Converted to PHP
		//
		// First calculate the image size, based on the positions given in the filename
		$this->diana_filename_tile_to_coords($name_body, $file_in, $x0, $x1, $y0, $y1, $binning, $dx_pix, $dy_pix);

		// Get the filename (not the whole path) so we can plot it on the image
		$file_short = substr(strrchr($file_in, '/'), 1);

		if (file_exists($file_in)){
			// For found tiles
			$im = imagecreatefromjpeg($file_in);
			$color_text = imagecolorallocate($im, 233, 14, 91);
			$color_white = imagecolorallocate($im, 255, 255, 255);
			return $im;
		} else {
			// For missing tiles
			$im = imagecreatetruecolor($dx_pix, $dy_pix);
			$color_border = imagecolorallocate($im, 233, 14, 91);
			$color_white = imagecolorallocate($im, 255, 255, 255);

			// Write some text which refers to the missing imagery. Having this here would have alleviated so many KC comments.
			$junk = imagestring($im, 4, $dx_pix/2, $dy_pix/2, 'No imagery at this location', $color_white);
			$junk = imagestring($im, 4, $dx_pix/2, 0,         'No imagery at this location', $color_white);

			// Now put a (red) rectangular border around missing tiles.  To remove this, comment it out
			$do_border_around_missing_tiles	= 0;

			if ($do_border_around_missing_tiles) {
				imagerectangle($im, 1, 1, $dx_pix-3, $dy_pix-3, $color_border);
			}

			return $im;
		}
	} // end diana_read_tile

	function array_extract_elements($arr, $elements) {

		// This function retrieves the elements of one array as specified by the 
		// contents of another.  The following are similar:
		//
		// $out = $this->array_extract_elements($arr, $elements);  [PHP]
		//  out = arr[elements]				 ;  [IDL]
		//
		// There doesn't look like a way to do this in PHP w/o looping and extracting each element.
		// Using $out = $arr[$elements] gives an error.
		//
		// HBT DIANA 29-Dec-2009

		$out = array();

		foreach ($elements as $element) {
			$out[] = $arr[$element];
		}

		return $out;
	} // end array_extract_elements


	function diana_filename_tile_to_coords($name_body, $file, &$x0, &$x1, &$y0, &$y1, &$binning, &$dx_pix, &$dy_pix) {

		// Decodes a SELENE filename.
		// 
		//  file: A filename, such as 'data/tiles/8/X359.000_360.000/TC_EVE_02_X359.000_360.000__Y-01.000_000.000_B00008.jpg'
		// 
		// Required arguments:
		//    x0, x1, y0, y1: Values of the four corners of the image, in degrees
		//    binning: binning.  1, 2, 4... etc.
		// 
		// Optional arguments:
		// 
		//  dx_pix, dy_pix: The actual # of pixels of the file.  This is *computed* from the filename, not derived from the file itself.
		// 
		// 
		// HBT DIANA 10-Dec-2009
		//           29-Dec-2009 Rewritten in PHP.  Functions: strpos(haystack, needle), strlen(), substr( $string  , int $start  [, int $length  ] )
		//           12-Aug-2014. Rewritten for BatWorks Mars engine.
		//

		// For Mars_BW: Tiles are named "mars_merged_<scale>_<lat_top>_<lon_left>.png" .

		$debug = true;

		// Start by chopping off everything up to the final "/" -- that is, chop of all directories, etc.

		echo '<p>Attempting to convert filname '. $file .' to coords</p>';

		$file_ary = explode('/', $file);
		$pos_str = end($file_ary);

		print "<br>\n";
		print "dfttc: file = $file<br>\n";
		print "dfttc: pos_str = $pos_str<br>\n";

		$pos_parts = explode("_", $pos_str);
		$x0_str = $pos_parts[3];		// Left
		$y1_str = $pos_parts[4];		// Top
		$binning = $pos_parts[2];

		echo '<p>'.$y1_str.' - '.'('.$this->dy_tile_pix[$binning].' / '.$this->pix_per_deg.' / '.$binning.')</p>'; // Bottom
		$y0 = $y1_str - ($this->dy_tile_pix[$binning] / $this->pix_per_deg / $binning);	// Bottom
		$y0_str = sprintf($y0);

		$x1 = $x0_str + ($this->dx_tile_pix[$binning] / $this->pix_per_deg / $binning);	// Right
		$x1_str = sprintf($x1);

		// Remove any leading zeros from the y dimensions ('Y0-0.125', etc.)
		$x0		= $x0_str * 1.;
		$x1		= $x1_str * 1.;
		$y0		= $y0_str * 1.;
		$y1		= $y1_str * 1.;

	    print "dfttc: x0 = $x0<br>";
	    print "dfttc: x1 = $x1<br>";
	    print "dfttc: y0 = $y0<br>";
	    print "dfttc: y1 = $y1<br>";
	    print "dfttc: binning = $binning<br>";

		$dx_pix	= ($x1-$x0) * $this->pix_per_deg / $binning;
		$dy_pix	= ($y1-$y0) * $this->pix_per_deg / $binning;

		if ($dx_pix < 0) { $dx_pix += 360. * $this->pix_per_deg;}
		if ($dy_pix < 0) { $dy_pix += 360. * $this->pix_per_deg;}
	} // end diana_filename_tile_to_coords


	function array_append($arr1, $arr2) {
		$out = $arr1;

		foreach ($arr2 as $element) {
			$out[] = $element;
		}

		return $out;
	} // array_append


	function diana_generate_image($name_body, $x0_deg, $x1_deg, $y0_deg, $y1_deg, $zoom, $do_plot_craters, $do_plot_names, $do_plot_legend, $do_plot_crater_cross, $do_plot_scalebar, $features, $file_out) {
		// Generate an image.  This is the main way to generate data.
		// The image is placed into in a named jpeg file.
		//
		// Inputs:
		//  $name_body: name of the body plus any extension: Moon_SELENE, Moon_LRO, Mimas, etc.
		//  $x0_deg, x1_deg, y0_deg, y1_deg: the corner positions, in degrees
		//  $zoom: zoom level
		//  $do_plot_* : flags to plot various features
		//  $features: A list of features to plot (including their position, etc.)
		//  $file_out: Name of a directory / file to put the result into.
		//
		// HBT uwingu 1-Jan-2010

		// Initialize routine

		$pix_per_char	= 264/34.;		// Approximate, based on "supercalifragilisticexpealidotious"

		$do_plot_names_pdp	= $do_plot_names;
		// $do_plot_names_pdp	= 0;

		// Calculate the output image size, in pixels.

		$dx_out_deg	= $x1_deg - $x0_deg; if ($dx_out_deg < 0) {$dx_out_deg += 360;}
		$dy_out_deg	= $y1_deg - $y0_deg; if ($dy_out_deg < 0) {$dy_out_deg += 360;}

		$dx_out_pix	= $dx_out_deg * $this->pix_per_deg / $zoom;
		$dy_out_pix	= $dy_out_deg * $this->pix_per_deg / $zoom;

		// print "diana_generate_image: count(features) = " . count($features) . "<br>";
		// print "diana_generate_image: called with feature=" . $features[0] . " (size " . count($features) . ") and do_plot_craters=$do_plot_craters.<br>";

		$date = date('r');
		$replot = 1;

		if (isset($replot)) {

			$header = "data/tiles/";

			// print "diana_generate_image.php: Calling diana_load_tiles with name_body=$name_body, x0_deg = $x0_deg; x1_deg = $x1_deg, y0_deg = $y0_deg, y1_deg = $y1_deg<br>";
			$im	= $this->diana_load_tiles($name_body, $x0_deg, $x1_deg, $y0_deg, $y1_deg, $zoom); 

			if($im) {

				$color_red   = imagecolorallocate($im, 255, 0, 0);
				$color_blue  = imagecolorallocate($im, 0, 0, 255);
				$color_yellow= imagecolorallocate($im, 0, 255, 255);
				$color_green = imagecolorallocate($im, 0, 255,   0);
				$color_black = imagecolorallocate($im, 0, 0, 0);
				$color_white = imagecolorallocate($im, 255, 255, 255);
				$color_province = imagecolorallocate($im, 204, 51,  204);		// purpleish
				// $color_province = imagecolorallocate($im, 204, 151,  204);		// purpleish
				$color_district = imagecolorallocate($im, 153, 255, 255);		// teal blue green
				$color_precinct = imagecolorallocate($im,   0,   0, 254);		// dark blue

				// Process all the craters with centers on this image
				$num_features = count($features);

				// Create an imagemap (for mouseovers).  This is done automatically, since it's often useful to do at the same time as creating an image.
				$file_map_out	= str_replace('.jpg', '.txt', $file_out);
				$fh_map_out 	= fopen($file_map_out, 'w');
				//   print "writing to txt file $file_map_out<br>";
				fwrite($fh_map_out, "<map name=\"map1\">");

				// Now go thru and figure out what P/D/P regions are here
				$do_plot_pdp = 0;			// Flag: Do we plot Province / District / Precint borders?
				$empty	 = array(0);

				if ($do_plot_pdp) {
					diana_get_pdp_in_area($name_body, $x0_deg, $x1_deg, $y0_deg, $y1_deg, $province_ids, $district_ids, $precinct_ids);

					// Now loop over the precincts (since we want to plot the smallest boxes first, so borders of large ones overlay them).
					if ($zoom <= 16) {
						sort($precinct_ids, SORT_NUMERIC);		// Sort them, since diana_pdp_nums_to_names expects that.
						$precinct_names = diana_pdp_nums_to_names($name_body, 'precinct', $precinct_ids);
						for ($i = 0; $i < count($precinct_ids); $i++) {
							diana_pdp2ll($name_body, 0, 0, $precinct_ids[$i], $x0, $x1, $y0, $y1);		// left, right, bottom, top

							// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
							$has_id	= strcmp($precinct_ids[$i], $precinct_names[$i]) !=0;
							if ($has_id) {
								// x0_deg, y0_deg is the LL corner of the whole image.
								$x0_pix =               ($x0 - $x0_deg) * $this->pix_per_deg / $zoom;	// left
								$x1_pix =               ($x1 - $x0_deg) * $this->pix_per_deg / $zoom;	// right
								$y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $this->pix_per_deg / $zoom;	// bottom -- sign flip since increasing Y degrees -> decreasing y pixels
								$y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $this->pix_per_deg / $zoom;	// top -- sign flip since increasing Y degrees -> decreasing y pixels
								imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_precinct);	// order left, top, right, bottom
								if ($do_plot_names_pdp) {
									$this->imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($precinct_names[$i])/2, ($y1_pix+$y0_pix)/2, $precinct_names[$i], 
										$color_precinct, $color_white);
							    }
							}
						}
					}

					// Now loop over the districts
					if ($zoom <= 64) {
						sort($district_ids, SORT_NUMERIC);
						$district_names = diana_pdp_nums_to_names($name_body, 'district', $district_ids);
						for ($i = 0; $i < count($district_ids); $i++) {
							diana_pdp2ll($name_body, 0, $district_ids[$i], 0, $x0, $x1, $y0, $y1);

							// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
							$has_id	= strcmp($district_ids[$i], $district_names[$i]) !=0;
							if ($has_id) {
								$x0_pix =               ($x0 - $x0_deg) * $this->pix_per_deg / $zoom;
								$x1_pix =               ($x1 - $x0_deg) * $this->pix_per_deg / $zoom;
								$y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $this->pix_per_deg / $zoom;
								$y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $this->pix_per_deg / $zoom;
								imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_district);
								if ($do_plot_names_pdp) {
									$this->imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($district_names[$i])/2, ($y1_pix+$y0_pix)/2, $district_names[$i], 
										$color_white, $color_district);
								}
							}
						}
					}

					// Now loop over the provinces
					if ($zoom <= $this->zoom_max) {
						sort($province_ids, SORT_NUMERIC);
						$province_names = diana_pdp_nums_to_names($name_body, 'province', $province_ids);
						for ($i = 0; $i < count($province_ids); $i++) {
							diana_pdp2ll($name_body, $province_ids[$i], 0, 0, $x0, $x1, $y0, $y1);
							// Check if there is an ID defined for this region.  Plot the region only if it has a defined name
							$has_id	= strcmp($province_ids[$i], $province_names[$i]) !=0;
							if ($has_id) {
								$x0_pix =               ($x0 - $x0_deg) * $this->pix_per_deg / $zoom;
								$x1_pix =               ($x1 - $x0_deg) * $this->pix_per_deg / $zoom;
								$y0_pix = $dy_out_pix - ($y0 - $y0_deg) * $this->pix_per_deg / $zoom;
								$y1_pix = $dy_out_pix - ($y1 - $y0_deg) * $this->pix_per_deg / $zoom;
								imagerectangle($im, $x0_pix+0, $y1_pix+0, $x1_pix-0, $y0_pix-0, $color_province);
								if ($do_plot_names_pdp){
									$this->imagestring_double($im, 4, ($x0_pix+$x1_pix)/2 - $pix_per_char*strlen($province_names[$i])/2, ($y1_pix+$y0_pix)/2, $province_names[$i], 
										$color_white, $color_province);
								}
							}
						}
					}
				}

				// Now loop over the features, and plot them
				for ($i=0; $i < $num_features; $i++){
					$row			= $features[$i];
					$center_x_deg	= $row['longitude_deg'];
					$center_y_deg	= $row['latitude_deg'];
					$name			= $row['name_latin'];
					$radius			= $row['radius_km'];
					$featureid		= $row['featureid'];
					$type 			= $row['type'];
 
					// Handle wraparound -- e.g., if we have craters at 5 deg, and are plotting 350 .. 10 deg.
					if ($center_x_deg < $x0_deg) { $center_x_deg += 360;}

					$center_x_pix	= ($center_x_deg - $x0_deg) * $this->pix_per_deg / $zoom;
					$center_y_pix	= imagesy($im) - ($center_y_deg - $y0_deg) * $this->pix_per_deg / $zoom;
					$radius_y_pix	= ($radius * $this->pix_per_km) / $zoom;
					$radius_x_pix	= ($radius * $this->pix_per_km / cos($center_y_deg * $this->d2r)) / $zoom;

					if ($do_plot_craters) {
						switch ($type) {
							case 'Crater' : {
								imageellipse($im, $center_x_pix, $center_y_pix, $radius_x_pix*2-2, $radius_y_pix*2-2, $color_white);
								imageellipse($im, $center_x_pix, $center_y_pix, $radius_x_pix*2, $radius_y_pix*2, $color_blue);
							}; break;
						}

						if ($do_plot_crater_cross) {
							draw_crosshairs($im, $center_x_pix-1, $center_y_pix-1, 10, 10, $color_white);
							draw_crosshairs($im, $center_x_pix+1, $center_y_pix+1, 10, 10, $color_white);
							draw_crosshairs($im, $center_x_pix-1, $center_y_pix+1, 10, 10, $color_white);
							draw_crosshairs($im, $center_x_pix+1, $center_y_pix-1, 10, 10, $color_white);
							draw_crosshairs($im, $center_x_pix, $center_y_pix, 10, 10, $color_blue);
						}
					}

					// Print a line to the imagemap
					fwrite($fh_map_out, "<area shape=rect coords=\"" . round($center_x_pix-2) . ", " . round($center_y_pix-2) . ", " . 
					                                                 round($center_x_pix+2) . ", " . round($center_y_pix+2) . "\""  .
																	" nohref title=\"$name, $radius km\"> \n ");

					//////////
					// Plot the crater names next to (or below) the craters
					//////////
					if (($do_plot_names) && (strcmp($type, 'Crater') == 0)) {
						$name_below	= 1;
						$name_right	= 0;
						if ($name_right) {
							$dy_name_pix = -7;
							$dx_name_pix =  10;
						} 
						if ($name_below) {
							$dy_name_pix	= 4;
							$dx_name_pix	= -1 * strlen($name) * $pix_per_char/2;
						}
						$this->imagestring_double($im, 4, $center_x_pix + $dx_name_pix, $center_y_pix + $dy_name_pix, $name, $color_white, $color_black);
					}
				} // End loop over features

				// Plot a legend on the image
				//    $do_plot_legend = 1;
				if (($do_plot_legend)){
					$border_top = array(10, 10, 550, 10, 550, 40, 10, 40);		// Four points, 8 values
					imagefilledpolygon  ($im, $border_top, 4, $color_black);

					$km_per_deg_y	= (2. * $this->pi * $this->radius_body_km) / 360.;
					$km_per_deg_x	= (2. * $this->pi * $this->radius_body_km) / 360. * cos(($y0_deg+$y1_deg)/2. * d2r);

					$width_km	= $dx_out_pix * $this->deg_per_pix *$km_per_deg_x * $zoom;
					$x_center_deg = ($x1_deg + $x0_deg)/2;				// XXX Not right -- fix this.
					$y_center_deg = ($y1_deg + $y0_deg)/2;

					imagestring($im, 4, 10, 10, 
						sprintf("Lon = %f .. %f   Lat = %f .. %f   ", 
							$x0_deg, $x1_deg, $y0_deg, $y1_deg), 
					$color_white);
					imagestring($im, 4, 10, 24,
						sprintf("Zoom = %d   Width = %5.2f km", 
							$zoom, $width_km), 
						$color_white);

					// Look up the numerical province id's
					diana_ll2pdp($name_body, $x_center_deg, $y_center_deg, $province, $district, $precinct);	

					// Convert to names
					diana_pdp_num_to_name($name_body, $province, $district, $precinct, $name_province, $name_district, $name_precinct);
					imagestring($im, 4, 10, 38, 
						sprintf("Province %s, District %s, Precinct %s", 
							$name_province, $name_district, $name_precinct), 
						$color_white);
				}

				if ($do_plot_scalebar){
					// Plot a scalebar in UL corner.  Make it go vertically, so we don't have a cos theta issue.

					$length_scalebar_pix 	= 150;
					$width_scalebar_pix 	= 4;
					$y0_scalebar_pix 		= 50;
					$y1_scalebar_pix		= $y0_scalebar_pix + $length_scalebar_pix;;
					$x0_scalebar_pix		= 30;
					$x1_scalebar_pix		= $x0_scalebar_pix + $width_scalebar_pix;
					$color_scalebar		= $color_red;

					for ($i=0; $i < $width_scalebar_pix; $i++){
						// imageline x1 y1 x2 y2
						imageline($im, $x0_scalebar_pix+$i, $y0_scalebar_pix, $x0_scalebar_pix+$i, $y1_scalebar_pix, $color_scalebar);
					}
					imageline($im, $x0_scalebar_pix-3, $y0_scalebar_pix+1, $x1_scalebar_pix+3, $y0_scalebar_pix+1, $color_scalebar);
					imageline($im, $x0_scalebar_pix-3, $y1_scalebar_pix-1, $x1_scalebar_pix+3, $y1_scalebar_pix-1, $color_scalebar);
					imageline($im, $x0_scalebar_pix-3, $y0_scalebar_pix, $x1_scalebar_pix+3, $y0_scalebar_pix, $color_scalebar);
					imageline($im, $x0_scalebar_pix-3, $y1_scalebar_pix, $x1_scalebar_pix+3, $y1_scalebar_pix, $color_scalebar);

					$length_scalebar_deg	= $length_scalebar_pix * $this->deg_per_pix * $zoom;
					$length_scalebar_km		= $length_scalebar_pix * $this->km_per_pix * $zoom;

					// Draw the text for the legend for the scalebar. Depending on flags, we want it to go either horizontally, or vertically.
					$do_legend_scalebar_horizontal = 0;
					if ($do_legend_scalebar_horizontal) {
						imagestring($im, 4, $x1_scalebar_pix + 7, ($y0_scalebar_pix+$y1_scalebar_pix)/2 + 5, 
							sprintf("%5.2f Deg", $length_scalebar_deg), $color_white);

						imagestring($im, 4, $x1_scalebar_pix + 7, ($y0_scalebar_pix+$y1_scalebar_pix)/2 - 15, 
							sprintf("%5.2f km", $length_scalebar_km), $color_white);
					}

					$do_legend_scalebar_vertical = 1;

					if ($do_legend_scalebar_vertical) {		// For vertical scalebar, imagestring will not rotate text. Instead, rotate image, 
						$im_rot = imagerotate($im, -90, 0);		// and then un-rotate it back.
						imagestring($im_rot, 34, 210, 3, 
							sprintf("%5.2f Deg = %5.2f km", $length_scalebar_deg, $length_scalebar_km), $color_white);
						$xcen = ($x0_deg + $x1_deg )/2 % 360;
						if ($x1_deg < $x0_deg) { $xcen += 180; }
						$xcen %= 360;
						$ycen = ($y0_deg + $y1_deg       )/2 % 360;
						imagestring($im_rot, 34, 11, 3, 
							sprintf("Pos %5.2f, %5.2f", $xcen, $ycen), $color_white);

						$im_rot2 = imagerotate($im_rot, 90, 0);

						$im	= $im_rot2;
					}
				}

				// Plot crosshairs at image center
				$do_draw_crosshairs_center = 0;

				if ($do_draw_crosshairs_center) {
					draw_crosshairs($im, $dx_out_pix/2, $dy_out_pix/2, 40, 40, $color_red);
				}

				// if (imagejpeg($im, $file_out, 90)){
				// 	print "diana_generate_image: yes wrote file $file_out<br>";
				// }
				// else {
				// 	print "diana_generate_image: failed to write file $file_out<br>";
				// }

			    imagedestroy($im);
				// Close the imagemap file
				// NB: Don't put an extra carriage return in here, since it will affect spacing btwn main image and navigator 
				// in inconsistent way.

				fwrite($fh_map_out, "</map>");
				fclose($fh_map_out);

			}
		}
	} // end diana_generate_image


	function diana_calc_zoom_for_feature($x_deg, $y_deg, $radius_km, $pix_per_deg, $dx_pix, $dy_pix) {
		/********* DOES NOT APPEAR TO BE USED **********/
		// Function to calculate the zoom level required to properly dispaly a feature (ie, zoomed the max possible w/o clipping).
		// Zoom is taken to be factors of two.
		//
		// NB: Right now not all input parameters are used.  In the future we may use them all, for more accurate calculations.
		//
		// HBT 23-Mar-2010 uwingu

		// Make ratio # pixels of feature / # pixels of screen
		$coslat = cos($y_deg * $this->d2r);

		$ratio_x = (2 * $radius_km * $this->pix_per_km / $coslat) / $dx_pix;	// Get ratio in X dir
		$ratio_y = (2 * $radius_km * $this->pix_per_km          ) / $dy_pix;	// Get ratio in Y dir

		$ratio = max($ratio_x, $ratio_y);

		//   print "ratio = $ratio = " . 2 * $radius_km * $this->pix_per_km . " / $dy_pix = pix_features / pix_screen<br>";

		// Radius is in km, so doesn't matter if it's
		// X or Y radius.
		if  ($ratio  < 1)                      {$zoom = 1;}
		if (($ratio >= 1)   && ($ratio < 2))   {$zoom = 2;}
		if (($ratio >= 2)   && ($ratio < 4))   {$zoom = 4;}
		if (($ratio >= 4)   && ($ratio < 8))   {$zoom = 8;}
		if (($ratio >= 8)   && ($ratio < 16))  {$zoom = 16;}
		if (($ratio >= 16)  && ($ratio < 32))  {$zoom = 32;}
		if (($ratio >= 32)  && ($ratio < 64))  {$zoom = 64;}
		if (($ratio >= 64)  && ($ratio < 128)) {$zoom = 128;}
		if (($ratio >= 128) && ($ratio < 256)) {$zoom = 256;}
		if (($ratio >= 256) && ($ratio < 512)) {$zoom = 512;}
		if (($ratio >= 512) && ($ratio <1024)) {$zoom = 1024;}

		return $zoom;
	} // end diana_calc_zoom_for_feature


	function imagestring_double($im, $font, $x, $y, $string, $color1, $color2) {
		// Prints a string to an image using GD 'imagestring' function.
		// However, also prints a 'background' image in (usually) contrasting color.  For instance, print black text, with a 'background' halo of white text.
		// This makes the text visible regardless of what's behind it. Sort of like subtitles on foreign films.
		//
		// HBT uwingu 13-May-2010.

		imagestring($im, $font, $x+1, $y, $string, $color2);
		imagestring($im, $font, $x-1, $y, $string, $color2);
		imagestring($im, $font, $x, $y+1, $string, $color2);
		imagestring($im, $font, $x, $y-1, $string, $color2);
		imagestring($im, $font, $x, $y, $string, $color1);
	} // end imagestring_double


	function dropshadow_text($im, $font, $x, $y, $string, $color1, $color2) {
		// Prints a string to an image using GD 'imagettftext' function.
		// Renders string twice to create a drop shadow effect
		//
		// TJMB uwingu 19-Jan-2015

		$fontpath = "/Applications/Air Display Host.app/Contents/Resources/Fonts/OpenSans-Semibold.ttf";
//  		$fontpath = '/mnt/www/uwingu/fonts/OpenSans-Semibold.ttf'
 		imagettftext($im, $font, 0, $x+2, $y+2, $color2, $fontpath, $string);
 		imagettftext($im, $font, 0, $x, $y, $color1, $fontpath, $string);

	} // end imagestring_double


	function diana_fix_range_180( $angle_in ) {
		// Input:  Numerical value, such as a latitude or longitude
		// Output: Same value, in range -180 .. 180.  
		//
		// HBT Uwingu 16-Aug-2010

		$angle_out = fmod($angle_in + 720., 360);	// Make sure it's positive to start
		// Put it in range 0 .. 360.
		// NB: PHP "%" is integer mod.
		//     Need to use fmod() for a floating point mod.

		if ($angle_out > 180) { $angle_out -= 360;} 

		return ($angle_out);
	} // end diana_fix_range_180


	function diana_midway_360($x1, $x2) {
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
	} // end diana_midway_360


	function range_robust($min, $max, $step) {
		// Function works just like range(), except if the binsize is larger than difference between
		// min and max, it returns a two-element array, rather than range(), which gives an error and 
		// returns I think an empty array during this case. 
		//
		// This is the main routine used to generate the boundareis of the bins that Uwingu uses.  It generates
		// the inclusive boundaries of bins -- that is, including star and end locations.  
		// $this->range_robust(-90, 90, 50) = [-90,-40,10,60,90]
		//
		// This routine was not working right for a long time, which is why the n pole of Moon was inaccessible to 
		// uwingu for a long time.  Fixed now.
		//
		// HBT Uwingu 5-Sep-2010

		$debug = 0;

		$range = $max-$min;
		if ($step > $range) {
			$out = array($min, $max);
			if ($debug) {
				print "range_robust(" . $min . " " . $max . " " . $step . "): ";
				$this->print_array($out);
				print "<br>";
				}
		} else {
			$out = range($min, $max, $step);
		}

		// Now see if the final bin is in the array, or not.  If it's not, it should be, and we put it there.
		if (max($out) != $max) {
			$out[] = $max;
		}

		return $out;
	} // end range_robust


	function generateCraterImg($lon_crater=163.10, $lat_crater=30.91, $diam_crater=90.2, $name_crater='Sample', $dx_image_pix=700, $dy_image_pix=500) {
		# This function will extract an arbitrary portion of the map, and highlight a single crater on it.
		# The idea is that this routine is a far more direct way of making the image than using 
		# PhantomJS to generate it by simulating a browser instance.

		# What do we want for the certificate? Most likely:
		#  Fixed height (e.g., 500 pixels)
		#  Fixed width (e.g., 700 pixels)
		#  Inputs: lon, lat, diameter, name
		#
		# My code must calculate appropriate zoom level, and borders of the box.
		#
		# HBT 14-Aug-2014.

		// INPUT VALUES.
		// Set these appropriately to tell the code which crater to plot.
		// These six values are the input to the routine.

		// $lon_crater Degrees East. Wikipedia lists West.
		// $lat_crater Degrees North
		// $diam_crater Diameter in km
		// $name_crater Name to label crater with

		// $dx_image_pix Output size
		// $dy_image_pix Output size

		// END INPUT VALUES //

		// Initialize
		include("diana_init.php");
		include("diana_init_routines.php");
		include("diana_init_Mars_BW.php");

		// Define the zoom levels available in the tileset
		$levels_zoom = [0.5, 1, 2, 4, 8, 10, 20, 40];

		// Calculate crater width, in pixels, at zoom 1
		$dy_crater_pix = $diam_crater * $this->pix_per_km;
		$dx_crater_pix = $diam_crater * $this->pix_per_km / cos(deg2rad($lat_crater));

		print "XXXdx_crater_pix = $dx_crater_pix at zoom 1\n<br>";
		print "dy_crater_pix = $dy_crater_pix at zoom 1\n<br>";

		// Calculate zoom level so as to match crater width with image output width
		$i = 0;
		$zoom = $levels_zoom[$i];

		while ($dx_crater_pix / $zoom > $dx_image_pix) {
			$zoom = $levels_zoom[$i++];
			print "At zoom level $zoom, crater width  = " . $dx_crater_pix / $zoom . " pixels\n<br>";
			print "At zoom level $zoom, crater height = " . $dy_crater_pix / $zoom . " pixels\n<br>";
		}

		// Based on zoom level and image width, define the lon/lat borders
		// Get the image
		// Set the input parameters. These can all be changed, and are just test values.

		$image_quality = 90;			// JPEG quality of output

		$dlat_crater = $diam_crater * $this->pix_per_km * $this->deg_per_pix ;	                // Degrees. Full height.
		$dlon_crater = $diam_crater * $this->pix_per_km * $this->deg_per_pix / cos(deg2rad($lat_crater));	// Degrees. Full width.

		$dlat_image = $dy_image_pix * $this->deg_per_pix * $zoom;
		$dlon_image = $dx_image_pix * $this->deg_per_pix * $zoom / cos(deg2rad($lat_crater));
		$dlon_image = $dx_image_pix * $this->deg_per_pix * $zoom; // / cos(deg2rad($lat_crater));

		print "dlat_crater = $dlat_crater\n<br>";
		print "dlat_image = $dlat_image\n<br>";
		print "dy_image_pix = $dy_image_pix\n<br>";
		print "dy_crater_pix = $dy_crater_pix at zoom 1; " . $dy_crater_pix / $zoom . " at zoom $zoom<br>\n";

		print "dlon_crater = $dlon_crater\n<br>";
		print "dlon_image = $dlon_image\n<br>";
		print "dx_crater_pix = $dx_crater_pix at zoom 1; " . $dx_crater_pix / $zoom . " at zoom $zoom<br>\n";
		print "dx_image_pix = $dx_image_pix\n<br>";

		// Calc the coordinates of the edges of the region we will retrieve

		$lat0 = $lat_crater - $dlat_image / 2.;
		$lat1 = $lat_crater + $dlat_image / 2.;

		$lon0 = $lon_crater - $dlon_image / 2.;
		$lon1 = $lon_crater + $dlon_image / 2.;

		// Retrieve the image

		$image = $this->diana_load_tiles('Mars_BW', $lon0, $lon1, $lat0, $lat1, $zoom);  // x0, x1, y0, y1

		print "\n\n";

		print "Retreived image dimensions = x:" . imagesx($image) . " y:" . imagesy($image) . "\n";

		// Calc the position of the crater

		$x_crater_pix = imagesx($image)/2.;			// Center of crater at center of image
		$y_crater_pix = imagesy($image)/2.;			// Center of crater at center of image

		$dx_crater_pix = $dlon_crater / $this->deg_per_pix / $zoom;
		$dy_crater_pix = $dlat_crater / $this->deg_per_pix / $zoom;

		// Calc the position of the text

		$x_text_pix = $x_crater_pix;
		$y_text_pix = $y_crater_pix + $dy_crater_pix/2 + 30;

		# Draw an ellipse

		$color_white = imagecolorallocate($image, 255, 255, 255);
		$color_red   = imagecolorallocate($image, 255, 0, 0);
		$color_blue  = imagecolorallocate($image, 0, 0, 255);
		$color_yellow= imagecolorallocate($image, 0, 255, 255);
		$color_green = imagecolorallocate($image, 0, 255,   0);
		$color_black = imagecolorallocate($image, 0, 0, 0);
		$color_white = imagecolorallocate($image, 255, 255, 255);
		$color_province = imagecolorallocate($image, 204, 51,  204);		// purpleish
		//     $color_province = imagecolorallocate($im, 204, 151,  204);		// purpleish
		$color_district = imagecolorallocate($image, 153, 255, 255);		// teal blue green
		$color_precinct = imagecolorallocate($image,   0,   0, 254);		// dark blue

		imageellipse($image, $x_crater_pix, $y_crater_pix, $dx_crater_pix, $dy_crater_pix, $color_white);

		$this->imagestring_double($image, 10, $x_text_pix, $y_text_pix, $name_crater, $color_white, $color_black);

		print "\n";

		$file_out = "test.png";

		imagejpeg($image, $file_out, $image_quality);
		echo "Wrote: " . $file_out . "\n";

	}

}
?>
