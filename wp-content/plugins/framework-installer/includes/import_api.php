<?php
/**
 * HANDLES ALL CONFIGURATIONS AND CUSTOMIZATIONS OF IMPORT PROCESSES ON A PER SITE BASIS
 * USE THIS API TO PREVENT HARD-CODING SPECIAL SITE HANDLING PROCEDURES INSIDE CORE IMPORT FILES.  
 */

/** OK DOKI-ADDING MULTILINGUAL SUPPORT TO SITES -START */
/** Sites with multilingual versions */
add_filter('wpdemo_sites_with_multilingual','wpdemo_sites_with_multilingual_func',10,2);
function wpdemo_sites_with_multilingual_func($output_array,$return_format) {

	if (is_array($output_array)) {

		//Define sites with this implementation by site shortnames
		$site_shortnames=		array('bc','bcl','cl','bre','rtl','rtv','tcl','rel'); 
		$site_export_xml_path= 	array(	'/_wpv_demo/bootcommerce/posts.xml',
										'/_wpv_demo/bootcommerce-layouts/posts.xml',
									  	'/_wpv_demo/classifieds/posts.xml',
										'/_wpv_demo/bootstrap-estate/posts.xml',
										'/_wpv_demo/refsite-theme-layouts/posts.xml',				
										'/_wpv_demo/refsite-theme-views/posts.xml',
										'/_wpv_demo/classifieds-layouts/posts.xml',
										'/_wpv_demo/bootstrap-estate-layouts/posts.xml'
									);
		
		//Return format
		$return_array= array(
				'shortnames' 		=> $site_shortnames,
				'export_xml_path'	=> $site_export_xml_path
		);
		
		if (isset($return_array[$return_format])) {
			
			$output_array =$return_array[$return_format];
		}
	}

	return $output_array;

}

/** OK DOKI-Customize different refsites needs of WPML plugins */
add_filter('wpvdemo_wpml_plugin_requirements','wpvdemo_wpml_plugin_requirements_func',10,2);
function wpvdemo_wpml_plugin_requirements_func($the_wpml_plugins,$get_path) {
	global $wpvdemo_bootstrap_estate_original_version;
	if ($get_path) {
		if ('/_wpv_demo/bootstrap-estate/posts.xml' == $get_path) {
			
			//Bootstrap estate site
			//Does not require WooCommerce multilingual	
			if (wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins)) {
				$the_wpml_plugins=wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins);				
			}			
			
		} elseif ('/_wpv_demo/refsite-theme-views/posts.xml' == $get_path) {
			
			//My Company multilingual
			//Does not require WCML, TM					
			if (wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins)) {
				$the_wpml_plugins=wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins);
			}
			if (wpvdemo_cleanup_wpml_plugins_array('WPML Translation Management',$the_wpml_plugins)) {
				$the_wpml_plugins=wpvdemo_cleanup_wpml_plugins_array('WPML Translation Management',$the_wpml_plugins);
			}
									
		} elseif ('/_wpv_demo/bootstrap-estate-layouts/posts.xml' == $get_path) {
			
			//Bootstap estate layouts multilingual
			//Does not require WCML					
			if (wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins)) {
				$the_wpml_plugins=wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins);
			}
						
		} elseif ('/_wpv_demo/refsite-theme-layouts/posts.xml' == $get_path) {
			
			//Does not require WCML			
			if (wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins)) {
				$the_wpml_plugins=wpvdemo_cleanup_wpml_plugins_array('WooCommerce Multilingual',$the_wpml_plugins);
			}		
		} elseif (('/_wpv_demo/classifieds/posts.xml' == $get_path) || ('/_wpv_demo/classifieds-layouts/posts.xml' == $get_path)) {
			
			//Special, require CRED and WPML integration
			if (!(in_array('Toolset CRED WPML Integration',$the_wpml_plugins))) {		
				$the_wpml_plugins[]='Toolset CRED WPML Integration';	
			}	
		} 
	}

	/** Unfiltered plugin lists 5 plugins */
	return $the_wpml_plugins;
	
	
}

/** ADDING MULTILINGUAL SUPPORT TO SITES -END */
	
/** OK DOKI-Sites that require importing of WooCommerce product category images */
add_filter('wpvdemo_do_import_wc_product_cat','wpvdemo_do_import_wc_product_func',10,2);
function wpvdemo_do_import_wc_product_func($xml_filename,$site_short_name) {
	
	//Array of site that requires WooCommerce categories to be exporter => XML file name used
	$data= array(
			'bcl' => 'bootcommerce_layouts_woocommerce_cat_images.xml',
			'bc'  => 'bootcommerce_woocommerce_cat_images.xml',
			'wt'  => 'woocommerce_tutorial_setting_woocommerce_cat_images.xml',
			'wtd' => 'woocommerce_tutorial_demo_woocommerce_cat_images.xml'
	);
	
	if (isset($data[$site_short_name])) {
		//Site has implementation
		$xml_filename= $data[$site_short_name];
	}
	
	return $xml_filename;
}

/** OK DOKI-Old Bootstrap sites with import support */
add_filter('wpdemo_old_bootstrap_sites','wpvdemo_old_bootstrap_sites_func',10,1);
function wpvdemo_old_bootstrap_sites_func($site_shortnames) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($site_shortnames)) {
		$site_shortnames=array(
				'bt' =>array(
						'settings_anchor'			=>	'bootstrap_plain',
						'optionname' 				=>	'bootstrap_theme',
						'header_menu_anchor' 		=>   'All Pages',
						'header_menu_option_name' 	=> 	'bootstrap-theme'
				),
				'bre'=>array(
						'settings_anchor'			=>  'bootstrap_realestate',
						'optionname'				=>  'toolset_real_estate',
						'header_menu_anchor'		=>  'Header',
						'header_menu_option_name'	=>  'toolset-real-estate'
				)
		);
		
		/** Framework Installer 1.9.9 + */
		/** No more backward compatibility Old Bootstrap Estate */
		$wpvdemo_bootstrap_estate_original_version = false;
		
		if ( is_bool( $wpvdemo_bootstrap_estate_original_version ) ) {
			if ( false === $wpvdemo_bootstrap_estate_original_version ) {
				//Redesign bootstrap estate, we need to remove this old Bootstrap configuration
				unset( $site_shortnames['bre'] );				
			}
		}
	
	}
	return $site_shortnames;	
}

/** OK DOKI-Old Bootstrap sites with WooCommerce import support */
add_filter('wpdemo_old_bootstrap_sites_with_ecommerce','wpdemo_old_bootstrap_sites_with_ecommerce_func',10,1);
function wpdemo_old_bootstrap_sites_with_ecommerce_func($site_shortnames) {

	if (is_array($site_shortnames)) {
		
		$site_shortnames = array (
				'cl' => array(
						'importwcviews' 			=> true,
						'woocommerce_import_file'	=> 'classifieds_woocommerce.xml'
						),
				'bc' =>  array(
						'importwcviews' 			=> true,
						'woocommerce_import_file'	=> 'bootcommerce_woocommerce.xml'
						),
				'bcl' => array(
						'importwcviews' 			=> true,
						'woocommerce_import_file'	=> 'bootcommerce_layouts_woocommerce.xml'
						),
				'tcl' => array(
						'importwcviews' 			=> false,
						'woocommerce_import_file'	=> 'classifieds_woocommerce.xml'
				),
				/** WOOCOMMERCE TUTORIALS */
				'wt' => array(
						'importwcviews' 			=> false,
						'woocommerce_import_file'	=> 'woocommerce_tutorial_setting_woocommerce.xml'
				),
				'wtd' => array(
						'importwcviews' 			=> true,
						'woocommerce_import_file'	=> 'woocommerce_tutorial_demo_woocommerce.xml'
				),
		);

	}
	return $site_shortnames;
}

/** OK DOKI-Filter sites with imported Access user roles */
add_filter('wpvdemo_import_user_roles','wpvdemo_import_user_roles_func',10,2);
function wpvdemo_import_user_roles_func($data_array,$shortnames) {
	global $wpvdemo_bootstrap_estate_original_version;
	if ((is_array($data_array)) && (!(empty($shortnames)))) {

		$data_array = array (
				'cl' => 'classifieds_user_roles.xml',
				'tcl'=> 'classifieds_user_roles.xml',
				'bre'=> 'bootstrap_estate_user_roles.xml',
				'rel'=> 'bootstrap_estate_layouts_user_roles.xml',
				'ctt'=> 'cred_tutorials_user_roles.xml',
				'ctc'=> 'cred_tutorials_demo_user_roles.xml'
				);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap, we need to remove since the original Bootstrap estate site does not have Access
				unset($data_array['bre']);				
			}
		}
	}
	return $data_array;
}

/** OK DOKI-Filter sites that needs configuration of CRED notification settings after import */
add_filter('wpvdemo_config_crednotification','wpvdemo_config_crednotification_func',10,2);
function wpvdemo_config_crednotification_func($bool,$shortnames) {

    $data_array=array(
    		'cl' =>true,
    		'tcl'=>true
    );
    
    if (isset($data_array[$shortnames])) {
    	$bool=$data_array[$shortnames];
    }
	
	return $bool;
}

/** OK DOKI-Filter sites that needs import of CRED custom fields */
add_filter('wpvdemo_import_cred_custom_fields','wpvdemo_import_cred_custom_fields_func',10,2);
function wpvdemo_import_cred_custom_fields_func($bool,$shortnames) {

	$data_array=array(
			'cl' =>true,
			'tcl'=>true
	);

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs to turn off wizard for WooCommerce Views */
add_filter('wpvdemo_turnoff_wcviews_wizard','wpvdemo_turnoff_wcviews_wizard_func',10,2);
function wpvdemo_turnoff_wcviews_wizard_func($bool,$shortnames) {

	$data_array=array();

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs to turn off admin notices for WooCommerce Views */
add_filter('wpvdemo_turnoff_wc_adminnotices','wpvdemo_turnoff_wc_adminnotices_func',10,2);
function wpvdemo_turnoff_wc_adminnotices_func($bool,$shortnames) {

	$data_array=array(
			'cl' =>true,
			'bc' =>true,
			'bcl'=>true,
			'tcl'=>true,
			/** WOOCOMMERCE TUTORIALS */
			'wt' =>true,
			'wtd'=>true
	);

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs manual fix of Views taxonomies */
add_filter('wpvdemo_manual_fix_views_taxonomies','wpvdemo_manual_fix_views_taxonomies_func',10,2);
function wpvdemo_manual_fix_views_taxonomies_func($bool,$shortnames) {
	global $wpvdemo_bootstrap_estate_original_version;
	
	$data_array=array(
			'bre' =>true
	);

	if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
		if (!($wpvdemo_bootstrap_estate_original_version)) {
			//Redesign bootstrap estate does not anymore needs this
			unset($data_array['bre']);
		}
	}
	
	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs manual fix of home URL */
add_filter('wpvdemo_manual_update_home_url','wpvdemo_manual_update_home_url_func',10,2);
function wpvdemo_manual_update_home_url_func($bool,$shortnames) {

	$data_array=array(
			'bt' =>true
	);

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs manual fix of image URLs */
add_filter('wpvdemo_manual_fix_of_image_urls','wpvdemo_manual_fix_of_image_urls_func',10,2);
function wpvdemo_manual_fix_of_image_urls_func($bool,$shortnames) {

	$data_array=array(
			'bt' =>true
	);

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs to have empty sidebar widgets */
add_filter('wpvdemo_manual_empty_sidebar_widgets','wpvdemo_manual_empty_sidebar_widgets_func',10,2);
function wpvdemo_manual_empty_sidebar_widgets_func($bool,$shortnames) {

	$data_array=array();

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs to have auto add header menu */
add_filter('wpvdemo_manual_autoaddheadermenu','wpvdemo_manual_autoaddheadermenu_func',10,2);
function wpvdemo_manual_autoaddheadermenu_func($bool,$shortnames) {

	$data_array=array();

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter sites that needs to have special pages configuration */
add_filter('wpvdemo_manual_special_wc_pages_configuration','wpvdemo_manual_special_wc_pages_configuration_func',10,2);
function wpvdemo_manual_special_wc_pages_configuration_func($bool,$shortnames) {

	$data_array=array(			
			'tcl'=>true,
			'cl' =>true
			
	);

	if (isset($data_array[$shortnames])) {
		$bool=$data_array[$shortnames];
	}

	return $bool;
}

/** OK DOKI-Filter permalink structure */
add_filter('wpvdemo_filter_permalink_structure_orig_bootstrap','wpvdemo_filter_permalink_structure_orig_bootstrap_func',10,2);
function wpvdemo_filter_permalink_structure_orig_bootstrap_func($permalink_structure,$shortnames) {

	$data_array=array(
			'bc' =>'/%postname%/',
			'bcl'=>'/%year%/%monthnum%/%postname%/'
				
	);

	if (isset($data_array[$shortnames])) {
		$permalink_structure=$data_array[$shortnames];
	}

	return $permalink_structure;
}

/** OK DOKI-Array of legacy sites */
add_filter('wpvdemo_legacy_sites','wpvdemo_legacy_sites_func',10,2);
function wpvdemo_legacy_sites_func($bool,$shortname) {

	$legacy_sites=array(
				're',
				'br',
				'ws'				
	);
	
	if (in_array($shortname,$legacy_sites)) {
		$bool=true;
	}
	
	return $bool;
}

/** OK DOKI-Filter sites requiring manual fix of product comparison functionality in WooCommerce */
add_filter('wpvdemo_manual_fix_product_comparison','wpvdemo_manual_fix_product_comparison_func',10,2);
function wpvdemo_manual_fix_product_comparison_func($bool,$shortname) {
	
	$include=array();
	
	if (in_array($shortname,$include)) {
		$bool=true;
	}
	
	return $bool;	
}
/**
 * START -REFERENCE SITE MERGING CONFIGURATIONS
 */
/** OK DOKI-Merge My Company sites into one presentation in manage sites */
add_filter('wpvdemo_merge_refsites','wpvdemo_merge_refsites_company_sites',10,1);
function wpvdemo_merge_refsites_company_sites($aux_array) {
	
	/**Define sites to be merged ,should only be two of them */
	$merge_shortnames= array('rtv','rtl');
	$views_version= 'rtv';
	$layouts_version = 'rtl';
	
	/**Call the merger
	 */
	$aux_array=wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version);

	return $aux_array;
}

/** OK DOKI-Merge BootCommerce sites into one presentation in manage sites */
add_filter('wpvdemo_merge_refsites','wpvdemo_merge_refsites_bootcommerce_sites',15,1);
function wpvdemo_merge_refsites_bootcommerce_sites($aux_array) {

	/**Define sites to be merged ,should only be two of them */
	$merge_shortnames= array('bc','bcl');
	$views_version= 'bc';
	$layouts_version = 'bcl';

	/**Call the merger
	 */
	$aux_array=wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version);

	return $aux_array;
}

/** OK DOKI-Merge Classifieds sites into one presentation in manage sites */
add_filter('wpvdemo_merge_refsites','wpvdemo_merge_refsites_classifieds_sites',20,1);
function wpvdemo_merge_refsites_classifieds_sites($aux_array) {

	/**Define sites to be merged ,should only be two of them */
	$merge_shortnames= array('cl','tcl');
	$views_version= 'cl';
	$layouts_version = 'tcl';

	/**Call the merger
	 */
	$aux_array=wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version);

	return $aux_array;
}

/** OK DOKI- Merge Bootstrap estate sites into one presentation in manage sites */
add_filter('wpvdemo_merge_refsites','wpvdemo_merge_refsites_bootstrapestate_sites',25,1);
function wpvdemo_merge_refsites_bootstrapestate_sites($aux_array) {

	/**Define sites to be merged ,should only be two of them */
	$merge_shortnames= array('bre','rel');
	$views_version= 'bre';
	$layouts_version = 'rel';

	/**Call the merger
	 */
	$aux_array=wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version);

	return $aux_array;
}

/** OK DOKI Magazine sites into one presentation in manage sites */
add_filter('wpvdemo_merge_refsites','wpvdemo_merge_refsites_magazine_sites',30,1);
function wpvdemo_merge_refsites_magazine_sites($aux_array) {

	/**Define sites to be merged ,should only be two of them */
	$merge_shortnames= array('bmv','bm');
	$views_version= 'bmv';
	$layouts_version = 'bm';

	/**Call the merger
	 */
	$aux_array=wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version);

	return $aux_array;
}

/** OK DOKI-Filter merged site installation in Discover WP multisite */
/** Used in Discover-WP Live Registration Plugin */
add_filter('wpvdemo_get_merged_site_equivalent','wpvdemo_get_merged_site_equivalent_func',10,2);
function wpvdemo_get_merged_site_equivalent_func($site_shortname,$flip) {

	//Array of site imported and its merged equivalent if any
	$site_shortname=(string)$site_shortname;
	$data=array(
			'rtl' => 'rtv',
			'bcl' => 'bc',
			'tcl' => 'cl',
			'rel' => 'bre',
			'bm'  => 'bmv'
	);

	if ($flip) {
		$data=array_flip($data);
	}
	
	if (isset($data[$site_shortname])) {
		//Has merged equivalents
		$site_shortname=$data[$site_shortname];
	}

	return $site_shortname;
}
/**
 * END -REFERENCE SITE MERGING CONFIGURATIONS
 */
/** OK DOKI-Filter sites requiring CRED Commerce */
add_filter('wpvdemo_import_cred_commerce_settings','wpvdemo_import_cred_commerce_settings_func',10,1);
function wpvdemo_import_cred_commerce_settings_func($shortnames_array) {
	if (is_array($shortnames_array)) {
		$shortnames_array=array('tcl','cl');
	}
	return $shortnames_array;
}

/** OK DOKI-Filter sites requiring activation of Types/Views full version for modules import */
add_filter('wpvdemo_special_types_views_fullactivation','wpvdemo_special_types_views_fullactivation_func',10,1);
function wpvdemo_special_types_views_fullactivation_func($shortnames_array) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($shortnames_array)) {
		$shortnames_array=array (
			'bre',
			'ws',
			'tcl' 
		);
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if (!($wpvdemo_bootstrap_estate_original_version)) {
				//Redesign bootstrap estate does not anymore needs this.				
				$shortnames_array = array_diff($shortnames_array, array('bre'));
			}
		}		
	}
	return $shortnames_array;
}

/** OK DOKI-Filter ecommerce sites that we need to delete any unneeded WooCommerce options during plugin activation */
add_filter('wpvdemo_deletewc_unneededoptions','wpvdemo_deletewc_unneededoptions_func',10,1);
function wpvdemo_deletewc_unneededoptions_func($shortnames_array) {
	if (is_array($shortnames_array)) {
		$shortnames_array = array( 'cl','vc','bc','tcl', 'bcl', 'wt', 'wtd' );
	}
	return $shortnames_array;
}
	
/** OK DOKI-Filter ecommerce sites that registering of color taxonomy */
add_filter('wpvdemo_wc_create_color_taxonomy','wpvdemo_wc_create_color_taxonomy_func',10,1);	
function wpvdemo_wc_create_color_taxonomy_func($site_ids) {
	if (is_array($site_ids)) {
		$site_ids = array('48','58', '62','63');
	}
	return $site_ids;
}

/** OK DOKI-sites that needs to have WC Views admin notices disabled after import */
add_filter('wpvdemo_disable_wcviews_admin_notice','wpvdemo_disable_wcviews_admin_notice_func',10,1);
function wpvdemo_disable_wcviews_admin_notice_func($sites) {
	if (is_array($sites)) {
		$sites = array(				
				8,				
				57
		);
	}
	return $sites;
}
/** OK DOKI-Filter sites that are using special plugins but already network activated in Discover WP multisite */
add_filter('wpvdemo_sites_already_network_activated','wpvdemo_sites_already_network_activated_func',10,1);
function wpvdemo_sites_already_network_activated_func($sites) {
	
	if (is_array($sites)) {
		
		$sites=array('cl','tcl'); 
		
	}
	
	return $sites;
}
/** OK DOKI-Filter sites with plugins required already network activated */
add_filter('wpvdemo_discoversite_already_network_activated','wpvdemo_site_already_network_activated_func',10,1);
function wpvdemo_site_already_network_activated_func($sites) {

	if (is_array($sites)) {
		//Authorized training sites, live and development.
		$sites = array(
				'discover-wp.com',
				'discover-wp.dev',
				'views-live-demo.local',
				'discover-wp.tld',
				'discover.host',				
				'dev.discover-wp.tld',
				'dev.discover-wp.com'
				);
	}

	return $sites;
}
/** OK DOKI-Filter plugins in Discover-WP that are already network activated */
add_filter('wpvdemo_plugins_already_network_activated','wpvdemo_plugins_already_network_activated_func',10,2);
function wpvdemo_plugins_already_network_activated_func($network_activated_plugins,$ret_format) {

	if (is_array($network_activated_plugins)) {

		if ('plugin_path' == $ret_format) {

			$network_activated_plugins=array(
					'cred-trunk/plugin.php',
					'types-access/types-access.php',
			);
			 
		} elseif ('plugin_names' == $ret_format) {

			global $wpvdemo_bootstrap_estate_original_version;
			if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
				//Boolean variable detected
				
				if ($wpvdemo_bootstrap_estate_original_version) {
					//Original bootstrap site imported, this is not yet required
					//Original Bootstrap estate site does not require new plugin names
					$network_activated_plugins=array(
							'CRED Frontend Editor',
							'Access',
					);					
					
					
				} else {
					
					//New bootstrap estate here
					//In sync with the release of new Toolset plugin versions with new names
					$network_activated_plugins=array(
							'Toolset CRED',
							'Toolset Access',
					);				
					
				}
			}			
		}
	}

	return $network_activated_plugins;
}

/** OK DOKI-Filter sites requiring an update on WPML string packages after import */
/** Typically are Layouts sites with multilingual implementation */
add_filter('wpvdemo_wpml_string_packages_update','wpvdemo_wpml_string_packages_update_func',10,1);
function wpvdemo_wpml_string_packages_update_func($sites) {

	if (is_array($sites)) {
		
		$sites=array(				
		'refsite-theme-layouts',
		'bootcommerce-layouts',
		'classifieds-layouts',
		'bootstrap-estate-layouts'
		);
	}

	return $sites;
}
/** POST IMPORT HOOKS SIMPLIFIED */
/** OK DOKI-Filter sites requiring manual search and replace of image URLs after import */
/** For sites with hardcoded multisite urls from the refsites inside the post_content*/
add_filter('wpvdemo_postimport_replace_urls','wpvdemo_postimport_replace_urls_func',10,1);
function wpvdemo_postimport_replace_urls_func($sites) {

	if (is_array($sites)) {

		$sites=array('views-tutorial-demo',
					 'views-tutorial',
					 'refsite-theme-layouts',					 				
					 'refsite-theme-views',
				     'magazine-views',
					 'bootstrap-estate-layouts',
					  /** WOOCOMMERCE TUTORIALS */
					 'woocommerce-tutorial',
					 'woocommerce-tutorial-demo',
					  /** CRED TUTORIALS */
					  'cred-tutorials',
					  'cred-tutorials-demo'					 
				);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring ICL adl settings to be imported */
add_filter('wpvdemo_site_requires_icladlsettings','wpvdemo_site_requires_icladlsettings_func',10,1);
function wpvdemo_site_requires_icladlsettings_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {

		$sites=array(
				'refsite-theme-layouts',
				'refsite-theme-views',
				'bootstrap-estate',
				'bootcommerce',
				'bootcommerce-layouts',
				'bootstrap-estate-layouts',
				'classifieds',
				'classifieds-layouts'			
		);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap, this is not yet required				
				$sites = array_diff($sites, array('bootstrap-estate'));
			}
		}
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring discussion settings to be imported */
add_filter('wpvdemo_site_requires_discussion_settings','wpvdemo_site_requires_discussion_settings_func',10,1);
function wpvdemo_site_requires_discussion_settings_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'views-tutorial',
				'views-tutorial-demo',
				'cred-tutorials',
				'cred-tutorials-demo'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring reading settings to be imported */
add_filter('wpvdemo_site_requires_reading_settings','wpvdemo_site_requires_reading_settings_func',10,1);
function wpvdemo_site_requires_reading_settings_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'woocommerce-tutorial-demo',
				'woocommerce-tutorial',
				'cred-tutorials',
				'cred-tutorials-demo'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring Log out link adjustments */
add_filter('wpvdemo_update_bcl_logout_link','wpvdemo_update_bcl_logout_link_func',10,1);
function wpvdemo_update_bcl_logout_link_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'bootcommerce',
				'bootcommerce-layouts'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring WooCommerce My Account page adjustments */
add_filter('wpvdemo_classifieds_layouts_my_account_page','wpvdemo_classifieds_layouts_my_account_page_func',10,1);
function wpvdemo_classifieds_layouts_my_account_page_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'classifieds-layouts',
				'classifieds'
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring search and replace of no image default */
add_filter('wpvdemo_search_replace_noimage_classifieds','wpvdemo_search_replace_noimage_classifieds_func',10,1);
function wpvdemo_search_replace_noimage_classifieds_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'classifieds-layouts',
				'classifieds'
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring search and replace hostnames inside Toolset Layouts content */
add_filter('wpvdemo_search_replace_hostnames_inside_layouts','wpvdemo_search_replace_hostnames_inside_layouts_func',10,1);
function wpvdemo_search_replace_hostnames_inside_layouts_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'bootcommerce-layouts',
				'refsite-theme-layouts',
				'bootstrap-estate-layouts',
				'bootmag',
				'classifieds-layouts'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring update of attachments */
add_filter('wpvdemo_simple_refsite_update_attachments','wpvdemo_simple_refsite_update_attachments_func',10,1);
function wpvdemo_simple_refsite_update_attachments_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'refsite-theme-views',
				'refsite-theme-layouts',
				'bootcommerce-layouts',
				'magazine-views',
				'bootstrap-estate-layouts',
				'bootmag',
				'classifieds',
				'classifieds-layouts'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring update of WooCommerce attributes */
add_filter('wpvdemo_bootcommerce_layouts_attributes','wpvdemo_bootcommerce_layouts_attributes_func',10,1);
function wpvdemo_bootcommerce_layouts_attributes_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'bootcommerce-layouts',
				'bootcommerce',
				'woocommerce-tutorial',
				'woocommerce-tutorial-demo'				
		);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring regeneration of thumbnails after import */
add_filter('wpvdemo_required_thumbnail_regeneration','wpvdemo_required_thumbnail_regeneration_func',10,1);
function wpvdemo_required_thumbnail_regeneration_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {
		$sites = array(
				8,				
				57,				
				58,
				53,
				48,
				/** WOOCOMMERCE TUTORIALS */
				62,
				63,
				64,
				65
		);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap site imported, this is not yet required
				$sites = array_diff($sites, array(53));
			}
		}
		
	}
	return $sites;
}
/** OK DOKI-Filter default processes that should be displayed on import processes*/
/** Should match array keys used in refsite_custom_import_process_steps() function */
add_filter('wpvdemo_default_import_processes','wpvdemo_default_import_processes_func',10,1);
function wpvdemo_default_import_processes_func($import_process_steps) {
	
	if (is_array($import_process_steps)) {
		
		$import_process_steps = array(
				'types_import',
				'posts_import',
				'views_import',
				'theme_import',
				'general_import_settings'				
		);
	}
	return $import_process_steps;
}
/** OK DOKI-Filter inactive import processes*/
/** Should match array keys used in refsite_custom_import_process_steps() function */
add_filter('wpvdemo_inactive_import_processes','wpvdemo_inactive_import_processes_func',10,1);
function wpvdemo_inactive_import_processes_func($inactive_processes) {
	if (is_array($inactive_processes)) {

		$inactive_processes = array(
				'inline_doc_import',
		);
	}
	return $inactive_processes;
}
/** OK DOKI-Filter sites requiring CRED*/
/** This is quick version of querying sites with CRED without using wp_remote_get */
add_filter('wpvdemo_refsites_require_cred','wpvdemo_refsites_require_cred_func',10,1);
function wpvdemo_refsites_require_cred_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {
		$sites = array(8,30,50,53,56,57,59,64,66,67);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap site imported, this is not yet required
				$sites = array_diff($sites, array(53));
			}
		}
		
	}
	
	return $sites;
}
/** OK DOKI-Filter sites requiring Access*/
/** This is quick version of querying sites with Access without using wp_remote_get */
add_filter('wpvdemo_refsites_require_access','wpvdemo_refsites_require_access_func',10,1);
function wpvdemo_refsites_require_access_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {
		$sites = array(8,53,57,64,66,67);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap site imported, this is not yet required
				$sites = array_diff($sites, array(53));
			}
		}
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring module manager*/
/** This is quick version of querying sites with module manager modules for import without using wp_remote_get */
add_filter('wpvdemo_refsites_has_modules_to_import','wpvdemo_refsites_has_modules_to_import_func',10,1);
function wpvdemo_refsites_has_modules_to_import_func($sites) {
	if (is_array($sites)) {
		$sites = array(48);
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring Layouts*/
/** This is quick version of querying sites with Layouts for import without using wp_remote_get */
add_filter('wpvdemo_refsites_require_layouts','wpvdemo_refsites_require_layouts_func',10,1);
function wpvdemo_refsites_require_layouts_func($sites) {
	if (is_array($sites)) {
		$sites = array(57,58,60,64,40);
	}

	return $sites;
}
/** OK DOKI-Filter plugins without pages*/
add_filter('wpvdemo_plugins_without_page','wpvdemo_plugins_without_page_func',10,1);
function wpvdemo_plugins_without_page_func($the_plugins_passed) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($the_plugins_passed)) {

		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				//Original bootstrap estate site, Google maps addon is not yet needed this time
				//Using old plugin names
				//Possible values of origin: wptypescom, wpmlorg
				$the_plugins_passed = array(
						'CRED Commerce' =>array(
								'slug'=>'cred-commerce/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset Comment Validator' =>array(
								'slug'=>'toolset-comment-validator/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset Classifieds' =>array(
								'slug'=>'toolset-classifieds/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset - Google Maps Addon' =>array(
								'slug'=>'toolset-google-maps-addon/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset CRED WPML Integration' =>array(
								'slug'=>'toolset-cred-wpml-integration/#changelog',
								'origin'=>'wptypescom'
						)
				);				

				unset($the_plugins_passed['Toolset - Google Maps Addon']);
			} else {
				//New Bootstrap estate site, in sync with the release of new Toolset plugin versions
				//With updated plugin names
				
				$the_plugins_passed = array(
						'Toolset CRED Commerce' =>array(
								'slug'=>'cred-commerce/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset Comment Validator' =>array(
								'slug'=>'toolset-comment-validator/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset Classifieds' =>array(
								'slug'=>'toolset-classifieds/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset Maps' =>array(
								'slug'=>'toolset-google-maps-addon/#changelog',
								'origin'=>'wptypescom'
						),
						'Toolset CRED WPML Integration' =>array(
								'slug'=>'toolset-cred-wpml-integration/#changelog',
								'origin'=>'wptypescom'
						)
				);
				
			}
		}
	}

	return $the_plugins_passed;
}

/** OK DOKI-Filter refsite sequence*/
add_filter('wpvdemo_refsites_order_sequence','wpvdemo_refsites_order_sequence_func',10,2);
function wpvdemo_refsites_order_sequence_func($sequence,$refsite_objects) {

	//Retrieve the refsite
	if (is_object($refsite_objects)) {
		if (isset($refsite_objects->shortname)) {
			$refsite_shortname=$refsite_objects->shortname;
			if ((!(empty($refsite_shortname))) && (is_string($refsite_shortname))) {
				//Check if has sorting sequence setting
				$sorting_sequence_setting_array=array(
						//Views tutorial - training
						'ai'  => 1,
						//Views tutorial - complete
						'abc' => 2,
						//WooCommerce tutorial training
						'wt'  => 3,
						//WooCommerce tutorial complete
						'wtd' => 4,
						//CRED tutorial - training
						'ctt' => 5,
						//CRED tutorial - complete
						'ctc' => 6,						
						//Company site
						'rtv' => 7,
						//Company site with Layouts
						'rtl' => 8,
						//WooCommerce with Views
						'bc'  => 9,
						//WooCommerce with layouts
						'bcl' => 10,
						//Classifieds with Views
						'cl'  => 11,
						//Classifieds with Layouts
						'tcl' => 12,
						//Real estate
						'bre' => 13,
						//Real estate layouts
						'rel' => 14,
						//Magazine with layouts
						'bm'  => 15,
						//Magazine with Views
						'bmv' => 16,
						//Blank site
						'tbs' => 17
				);
				if (isset($sorting_sequence_setting_array[$refsite_shortname])) {
					$sequence= $sorting_sequence_setting_array[$refsite_shortname];
					$sequence = intval($sequence);
				}
			}
		}
	}
	
	return $sequence;
}
/** OK DOKI-Manual parametric adjustments if needed */
add_filter('wpvdemo_site_requires_parametric_filter_adjustment','wpvdemo_site_requires_parametric_filter_adjustment_func',10,1);
function wpvdemo_site_requires_parametric_filter_adjustment_func($sites) {
	if (is_array($sites)) {

		$sites=array(
				//refsite slug
				'bootcommerce-layouts' => array(
						//Layout slug
						'home' => array(
									//Cell name    =>  Target page slug
									'Single widget' => 'parametric-search'
								),
						'single-product'  => array(						
									//Cell name    =>  Target page slug
									'Single widget' => 'parametric-search'
								),
						),
				'classifieds-layouts' => array(
						//Layout slug
						'home' => array(
								//Cell name    =>  Target page slug
								'Single widget' => 'parametric-ads-search'
						),						
						'ad-single' => array(
								//Cell name    =>  Target page slug
								'Sidebar Seach Form' => 'parametric-ads-search'
						)
				)				
				);	
			
	}

	return $sites;
}
/** OK DOKI-Filter sites requiring update of Layouts WooCommerce shop page */
add_filter('wpvdemo_update_wc_shoppage_layouts','wpvdemo_update_wc_shoppage_layouts_func',10,1);
function wpvdemo_update_wc_shoppage_layouts_func($sites) {

	if (is_array($sites)) {

		$sites=array(
				'bootcommerce-layouts'
		);
	}

	return $sites;
}
/** OK DOKI-Sites with large set of multilingual strings*/
add_filter('wpvdemo_large_sites_standalone','wpvdemo_large_sites_standalone_func',10,1);
function wpvdemo_large_sites_standalone_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {

		$sites=array(
				'bootcommerce',
				'bootcommerce-layouts',
				'classifieds',
				'bootstrap-estate',
				'classifieds-layouts',
				'bootstrap-estate-layouts',
				'refsite-theme-views',
				'refsite-theme-layouts'
		);
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
				
				//Original bootstrap estate site does not still need this
				$sites = array_diff($sites, array('bootstrap-estate'));
			}
		}
	}

	return $sites;
}
/** OK DOKI-Ensure premium product translated slug is 'premium' for styling consistency */
/** Use WordPress core API filter: 'wp_unique_post_slug' */
add_filter( 'wp_unique_post_slug', 'wpvdemo_filter_premium_slug_classifieds_layouts',10,6 );
function wpvdemo_filter_premium_slug_classifieds_layouts($the_slug, $the_post_ID, $the_post_status, $the_post_type, $the_post_parent, $the_original_slug) {
	
	/** Conditions for filtering (ALL of them should be true):
	 * Import is not done 
	 * CRED is enabled
	 * WooCommerce is activated
	 * Multilingual import
	 * Post type is product
	 * Original slug is 'premium'
	 * $the_slug is not equal to $the_original_slug
	 * 
	 */	
	$import_not_yet_done=false;
	$check_import_is_done = get_option ( 'wpv_import_is_done' );
	if ('yes' != $check_import_is_done) {
		/** Import is not yet done */
		$import_not_yet_done=true;
	}
	
	if (($import_not_yet_done) && (wpvdemo_cred_is_enabled()) && (wpvdemo_woocommerce_is_active()) &&
	   (wpvdemo_wpml_is_enabled()) && ('product' == $the_post_type) && ('premium' == $the_original_slug) &&
	   ($the_slug !=$the_original_slug)) {
	   	//Always 'premium'
	   	$the_slug ='premium';
	}
	
	
	return $the_slug;
}
/** OK DOKI-Search and replace detailed CRED context inside its own CRED post body */
/** Example: cred-form-Add Apartment-262 created by:
 * 
 */
add_filter('wpvdemo_search_replace_detailed_context_credbody','wpvdemo_search_replace_detailed_context_credbody_func',10,1);
function wpvdemo_search_replace_detailed_context_credbody_func($sites) {
	global $wpvdemo_bootstrap_estate_original_version;
	if (is_array($sites)) {

		$sites=array(
				'bootstrap-estate',
				'bootstrap-estate-layouts'
		);
		
		if (is_bool($wpvdemo_bootstrap_estate_original_version)) {
			if ($wpvdemo_bootstrap_estate_original_version) {
		
				//Original bootstrap estate site does not still need this
				$sites = array_diff($sites, array('bootstrap-estate'));
			}
		}
		
	}

	return $sites;
}

/** OK DOKI-Filter sites requiring CRED User Forms */
add_filter('wpvdemo_import_cred_user_forms','wpvdemo_import_cred_user_forms_func',10,1);
function wpvdemo_import_cred_user_forms_func($shortnames_array) {
	if (is_array($shortnames_array)) {
		$shortnames_array=array( 'ctc' );
	}
	return $shortnames_array;
}