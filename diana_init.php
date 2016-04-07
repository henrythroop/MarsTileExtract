<?php

// For right now, we just assume SELENE throughout this.  At some point, when necessary, we will change this
// assumption s.t. we can use other missions as well.
//
// This file gets read by all PHP files.
// Unlike defining functions, defining *variables* can be done over and over.
//
// HBT DIANA 22-Dec-2009

// Now initialize all of our constants and other variables

//   $dir_data 	= 'data/';
//   $dir_tiles	= $dir_data . 'tiles/';

//   $name_body			= 'Moon_SELENE';		// Ultimately this will be set on GET/PUT line
//   $short 			= substr($name_body . '_', 0, strpos($name_body . '_', '_'));
//   $name_body_short		= $short;

// Avoid warning for a case where I have a variable with the same name as a session variable.
// See http://stackoverflow.com/questions/175091/php-session-side-effect-warning-with-global-variables-as-a-source-of-data 
// for all the info.

  ini_set('session.bug_compat_warn', 0);
  ini_set('session.bug_compat_42', 0);

// Set timezone lest PHP complain...

  date_default_timezone_set("UTC");

  $dir_tiles_server		= '/data/de/' . $name_body . '/tiles/';
  $dir_tiles_development	= '/Users/throop/Uwingu/Mars/tiles/jpg/';

// Set up a tmp directory.  This is where all the images that I generate for the cruiser are stored.
// They can be deleted immediately after they are created one time.
// Also make a URL for tmp.  This is what goes in the <img> tag and cannot be a full path name.

  $dir_home_server		= '/var/www/de/';
  $dir_home_development		= '/Users/throop/DIANA/';
  $dir_tmp_server		= '/var/www/de/tmp/';
  $dir_tmp_development		= '/Users/throop/DIANA/tmp/';
  $url_tmp			= 'tmp/';		

// Set up a thumbnails directory

  $dir_thumbnails_development	= '/Users/throop/DIANA/thumbnails/';
  $dir_thumbnails_server	= '/var/www/de/thumbnails/';
  $url_thumbnails		= 'thumbnails/';

  $pi		= pi();
  $d2r		= $pi / 180.;
  $r2d		= 180. / $pi;

//   $dx_tile_pix	= 512;			// File size of the tiles.  Yes, they are 512x512, not 128x128.
//   $dy_tile_pix	= 512;

// Set the image size for the main DIANA output window

//   $dx_out_pix	= 671;
//   $dy_out_pix	= 426;

  $quality_tile_jpg	= 90;				// 0 .. 100.  Quality of the output jpeg files for tiles.

// Flags for type of tile to make

  $do_tile_jpg	= 1;
  $do_tile_img	= 0;

  $max_dn_tile_jpg	= 10000;				// Maximum DN value for a tile output.  That is,
  							// values of MAX_DN_TILE (or more) are scaled to 256 in the JPG.

// Set the intensity level for 'grey' color, to indicate missing data

  $dn_color_grey	= $max_dn_tile_jpg / 3;

  $dn_color_white	= 32766;				// INTARR value for white.

// Determine whether we are running from local development machine, or server

  $self_full = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

// Check if we are running on SOC, or locally on development Mac, and set a flag.

  $is_server  		= preg_match("/https{0,1}:\/\/www\.uwingu\.com/", $self_full);
  $is_development 	= preg_match("/http:\/\/throop/", $self_full);

  $is_development	= true;

  if ($is_server) {
    $dir_tiles 		= $dir_tiles_server;
    $dir_tmp		= $dir_tmp_server;
    $dir_thumbnails	= $dir_thumbnails_server;
    $dir_home		= $dir_home_server;
  }

  if ($is_development) {
    $dir_tiles 		= $dir_tiles_development;
    $dir_tmp		= $dir_tmp_development;
    $dir_thumbnails	= $dir_thumbnails_development;
    $dir_home		= $dir_home_development;
  }

?>
