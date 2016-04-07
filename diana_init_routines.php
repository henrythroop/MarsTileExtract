<?php

// Initialization for DIANA.  
// This routine gets called once.  Re-defining PHP functions causes an error.
//
// This routine just makes sure that all the functions that we need are actually loaded.
// There must be some way to get PHP to load these automatically, but I don't know what it is!
// It's not include_path.
//
// HBT DIANA 22-Dec-2009.

// Set up the files we need to load

// $path = '/Users/throop/diana/';
// set_include_path(get_include_path() . PATH_SEPARATOR . $path);
// 
// print "include_path = " . get_include_path() . "\n";

// The following inits are performed always.

   require("dindgen.php");
//    require("linkify.php");
   require("diana_create_filename_tile.php");
   require("print_array.php");
   require("sprint_array.php");
   require("diana_get_bins.php");
   require("where.php");
   require("diana_load_tiles.php");
   require("array_mult.php");
   require("array_add.php");
   require("diana_set_defaults.php");
   require("diana_read_tile.php");
   require("array_extract_elements.php");
   require("diana_filename_tile_to_coords.php");
   require("array_append.php");
//    require("draw_crosshairs.php");
//    require("diana_search_features.php");
//    require("diana_search_transactions.php");
//    require("diana_search_features_by_name.php");
//    require("diana_search_features_by_pos.php");
//    require("diana_search_features_by_pos_intelligent.php");
//    require("diana_search_features_by_featureid.php");
//    require("diana_pdp_num_to_name.php");
//    require("diana_ll2pdp.php");
//    require("diana_pdp2ll.php");
   require("diana_generate_image.php");
//    require("diana_generate_context_map.php");
//    require("diana_generate_thumbnail.php");
//    require("diana_generate_feature_list.php");
//    require("diana_generate_feature_list_short.php");
//    require("diana_update_feature.php");
//    require("diana_submit_feature.php");
//    require("diana_submit_transaction.php");
//    require("diana_validate_name.php");
//    require("diana_validate_feature.php");
//    require("diana_validate_region.php");
   require("diana_calc_zoom_for_feature.php");
//    require("remove_accents.php");
//    require("collapse_whitespace.php");
//    require("time_since.php");
//    require("diana_get_fact.php");
//    require("diana_delete_thumbnails.php");
//    require("diana_table_features_recent.php");
//    require("diana_table_features_mine.php");
//    require("diana_name_to_name_essential.php");
//    require("select_option.php");
//    require("diana_iau_date_to_date.php");
//    require("diana_set_feature_paid.php");
//    require("diana_set_feature_expired.php");
//    require("diana_set_feature_deleted.php");
//    require("diana_get_pdp_in_area.php");
   require("imagestring_double.php");
//    require("diana_pdp_nums_to_names.php");
   require("diana_fix_range_180.php");
//    require("diana_name_body_short.php");
   require("diana_midway_360.php");
//    require("diana_real_escape_string.php");
   require("range_robust.php");

?>
