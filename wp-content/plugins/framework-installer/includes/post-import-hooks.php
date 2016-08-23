<?php
/** POST IMPORT HOOKS HERE
 *  FUNCTIONS HOOKED HERE ARE FOUND IN /includes/main_functions/post-import-functions.php
 *  THESE HOOKS RUNS AFTER A CLEAN IMPORT
 *  USE THESE HOOKS FOR POST-IMPORT PROCESSING
 *  
 *  - START
 */

//In Discover-WP where WPML is network activated, we need to disable theme localization for non-multilingual imports
add_action('wpv_demo_import_finishing','wpvdemo_reset_wpml_nonmultilingual',10,1);

//After import, we need to update the URL of the Bootcommerce layout logout link
add_action('wpv_demo_import_finishing','wpvdemo_update_bcl_logout_link',10,1);

//After import, we need to update the URLs inside the Content of Simple Toolset Reference Site
add_action('wpv_demo_import_finishing','wpvdemo_simple_refsite_url_content',10,1);

//After import, we need to update to attachments to standalone since its different from multisite
add_action('wpv_demo_import_finishing','wpvdemo_simple_refsite_update_attachments',10,1);

//Let's assigned a unique My Account settings page for Classifieds layouts
add_action('wpv_demo_import_finishing','wpvdemo_classifieds_layouts_my_account_page',10,1);

//Let's set WooCommerce attributes for BootCommerce site
add_action('wpv_demo_import_finishing','wpvdemo_bootcommerce_layouts_attributes',10,1);
 
//Let's search and replace reference site URl inside Layouts settings
add_action('wpv_demo_import_finishing','wpvdemo_search_replace_hostnames_inside_layouts',18,1);

//Let's search and replace no image URL in Toolset Classifieds site with Layouts
add_action('wpv_demo_import_finishing','wpvdemo_search_replace_noimage_classifieds',19,1);

//Let's removed unneeded notices after import
add_action('wpv_demo_import_finishing','wpvdemo_remove_wpml_related_notices',10,1);

//Let's patch the issue on assigning 'where to display at' for Types 
add_action('wpv_demo_import_finishing','wpvdemo_whereto_display_field_groups',10,1);

//Let's double check the integrity of WPML settings after import for multilingual sites
add_action('wpv_demo_import_finishing','wpvdemo_wpmlsettings_integrity_check',30,1);

//Record the refsites origin slug for checking purposes
add_action('wpv_demo_import_finishing','wpvdemo_refsites_origin_slug',10,1);

//Import ICL adl settings for sites that requires this
add_action('wpv_demo_import_finishing','wpvdemo_import_icl_settings',50,1);

//Some sites requiring discussion settings to be imported
add_action('wpv_demo_import_finishing','wpvdemo_import_wp_discussionsettings_func',10,1);

//Some sites requiring reading settings to be imported
add_action('wpv_demo_import_finishing','wpvdemo_import_wp_readingsettings_func',10,1);

//Fix for any parametric issues inside Layouts after import
add_action('wpv_demo_import_finishing','wpvdemo_adjust_parametric_filter_settings_func',10,1);

//Adjust Toolset starter theme mods correctly after import
add_action('wpv_demo_import_finishing','wpvdemo_adjust_toolset_starter_mods_func',60,1);

//Adjust WooCommerce shop page Layouts assignment after import
add_action('wpv_demo_import_finishing','wpvdemo_adjust_woocommerce_shop_page_layouts_func',10,1);

//Adjust WooCommerce product image gallery IDs after import
add_action('wpv_demo_import_finishing','wpvdemo_adjust_woocommerce_productimage_gallery',10,1);

//Check WooCommerce product CT assignments after import
add_action('wpv_demo_import_finishing','wpvdemo_check_product_template_afterimport',99,1);

//After importing multilingual site, let's assign the user as the translator
add_action('wpv_demo_import_finishing','wpvdemo_assign_user_as_translator',10,1);

//After importing multilingual site, let's adjust the element_ids of post_dd_layouts
add_action('wpv_demo_import_finishing','wpvdemo_adjust_elementidspost_dd_layout',10,1);

//After importing multilingual site with CRED, let's adjust the domain_name_context_md5 of the strings that reflects new CRED form IDS
add_action('wpv_demo_import_finishing','wpvdemo_adjust_context_md5_cred',10,1);

//Let's replace any pre-import URLs not being handled...
add_action('wpv_demo_import_finishing','wpvdemo_universal_search_and_replace',200,1);

//Let's replace any pre-import URLs not being handled...
add_action('wpv_demo_import_finishing','wpvdemo_import_nav_menu_options_func',15,1);

//Let's update any menu terms used with Toolset Layouts custom menu widgets cell...
add_action('wpv_demo_import_finishing','wpvdemo_adjust_nav_menu_layouts_custom_menu_func',90,1);

//Let's update any menu terms used with WPML settings...
add_action('wpv_demo_import_finishing','wpvdemo_adjust_nav_menu_wpml_terms_func',99,1);

//Let's update any widgets used with WPML after import...
add_action('wpv_demo_import_finishing','wpvdemo_adjust_widget_body_text_func',5,1);

//Let's update any widgets used with WPML after import...
add_action('wpv_demo_import_finishing','wpvdemo_log_refsites_to_toolset',250,1);
/** POST IMPORT HOOKS HERE
 *
 * - END
 */