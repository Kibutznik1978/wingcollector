<?php
/** IMPORT FUNCTIONS HERE
 *  THESE FUNCTIONS ARE AUXILIARY FUNCTIONS NEEDED DURING ACTUAL IMPORT PROCESS
 */


/**
 * Renders JS that triggers next import step.
 *
 * @param type $site_id
 * @param type $step
 */
function wpvdemo_import_next_step_js($site_id, $step) {
	$step = intval ( $step ) + 1;
	$process_count=get_option('wpvdemo_importprocess_count');
	if (!($process_count)) {
		$process_count='';
	}
	echo '<script type="text/javascript">wpvdemoDownloadStep(' . $site_id . ', ' . $step . ', ' . $process_count .');</script>';
		
}

/**
 * Imports types.
 *
 * @param type $baseurl        	
 * @return boolean
 */
function wpvdemo_import_types($baseurl) {
	$_POST ['overwrite-groups'] = 1;
	$_POST ['overwrite-fields'] = 1;
	$_POST ['overwrite-types'] = 1;
	$_POST ['overwrite-tax'] = 1;
	// $_POST['delete-groups'] = 0;
	// $_POST['delete-fields'] = 0;
	// $_POST['delete-types'] = 0;
	// $_POST['delete-tax'] = 0;
	$_POST ['post_relationship'] = 1;
	$file = $baseurl . '/types.xml';
	
	// Parse remote XML
	$data = wpv_remote_xml_get ( $file );
	if (! ($data)) {
		return false;
	}
	
	// Parameter wpvdemo is added in Types 1.3 to prevent errors in import Types fields to reference sites
	$success = wpcf_admin_import_data ( $data, false, 'wpvdemo' );
	if ($success === false) {
		return false;
	}
	return true;
}
function wpvdemo_view_imported_hook($old_view_id, $new_view_id) {
	global $wpvdemo_import;
	$wpvdemo_import->processed_posts [$old_view_id] = $new_view_id;
}

/**
 * Imports views.
 *
 * @global type $wpdb
 * @param type $baseurl        	
 * @return type
 */
function wpvdemo_import_views($baseurl, $settings) {
	global $wpdb, $wpvdemo_import;
	
	define ( 'WP_LOAD_IMPORTERS', true );
	require_once WPVDEMO_ABSPATH . '/class.importer.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/comment.php';
	$wpvdemo_import = new WPVDemo_Importer ( $settings->site_url );
	
	$_POST ['view-templates-overwrite'] = 'on';
	// $_POST['view-templates-delete'] = 'on';
	$_POST ['views-overwrite'] = 'on';
	// $_POST['views-delete'] = 'on';
	$_POST ['view-settings-overwrite'] = 'on'; // isset;
	$file = $baseurl . '/views.xml';
	
	// Parse remote XML
	$data = wpv_remote_xml_get ( $file );
	if (! ($data)) {
		return false;
	}
	
	$xml = simplexml_load_string ( $data );
	$import_data = wpv_admin_import_export_simplexml2array ( $xml );
	
	/**
	 * In Views 1.8, success value of wpv_admin_import_view_templates and wpv_admin_import_views is now TRUE
	 * Before it was false, let's handle this
	 */
	$views_version_one_eight_above = FALSE;
	
	if (defined ( 'WPV_VERSION' )) {
		
		$views_version_used = WPV_VERSION;
		if (version_compare ( $views_version_used, '1.8', '<' )) {
			$views_version_one_eight_above = FALSE;
		} else {
			$views_version_one_eight_above = TRUE;
		}
	}
	
	// import view templates first.
	$error = wpv_admin_import_view_templates ( $import_data );
	
	if ($views_version_one_eight_above) {
		// Modern Views
		if (! ($error)) {
			return $error;
		}
	} else {
		if ($error) {
			return $error;
		}
	}
	
	// import views next.
	add_action ( 'wpv_view_imported', 'wpvdemo_view_imported_hook', 10, 2 );
	$error = wpv_admin_import_views ( $import_data );
	
	if ($views_version_one_eight_above) {
		// Modern Views
		if (! ($error)) {
			return $error;
		}
	} else {
		if ($error) {
			return $error;
		}
	}
	
	$wpvdemo_import->process_wpml ();
	
	remove_action ( 'wpv_view_imported', 'wpvdemo_view_imported_hook', 10, 2 );
	
	// Import views settings next.
	$settings_args = array ();
	$settings_args ['view-settings-overwrite'] = true;
	$error = wpv_admin_import_settings ( $import_data, $settings_args );
	if ($views_version_one_eight_above) {
		// Modern Views
		if (! ($error)) {
			return $error;
		}
	} else {
		if ($error) {
			return $error;
		}
	}
	
	// Update template IDs in posts
	if (! empty ( $import_data ['view-templates'] )) {
		// check for a single view template
		$view_templates = $import_data ['view-templates'] ['view-template'];
		
		// check for a single view template
		if (! isset ( $view_templates [0] )) {
			$view_templates = array (
					$view_templates 
			);
		}
		
		$ct_template_equivalence=array();
		$post_template_equivalence=array();
		
		foreach ( $view_templates as $view_template ) {
			
			/** ID in $view_template is OLD */
			/** $view_template_id is NEW */
			
			$old_ct_id=$view_template['ID'];			
			$view_template_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'view-template' AND post_name = %s", $view_template ['post_name'] ) );
			
			/** Track for latest processing */
			$old_ct_id =intval($old_ct_id);
			$new_ct_id= intval($view_template_id);
			
			if (($old_ct_id > 0) && ($new_ct_id >0)) {
				$ct_template_equivalence[$old_ct_id] =$new_ct_id;
			}			
			
			if (! empty ( $view_template_id )) {
				
				/** Retrieved posts still assigned with this old Content Template $view_template ['ID'] from ref sites */				
				$posts = get_posts ( 'meta_key=_views_template&meta_value=' . $view_template ['ID'] . '&numberposts=-1&post_type=any' );
				
				if (! empty ( $posts )) {

					foreach ( $posts as $post ) {
						
						$post_id_assigned=$post->ID;
						
						/** Finally we assigned this post with the new and updated Content Template ID */
						/** Before we assigned CT, let's verify if this post already has an updated CT assigned */
						
						if (!(isset($post_template_equivalence[$post_id_assigned]))) {
							
							/**Not yet assigned, proceed */
							update_post_meta ( $post->ID, '_views_template', $view_template_id );
							
							/** At this point, posts now has updated CT */
							/** To prevent being overwritten with the other CT, let's tracked the post_id and its newly assigned/updated CT */							
							$post_template_equivalence[$post_id_assigned]=$view_template_id;							
						}
					}
				}
			}
		}
		
		//Loop ends
		update_option('wpv_demo_processed_ct_ids',$ct_template_equivalence);
		update_option('wpv_demo_template_equivalence',$post_template_equivalence);
	}
	return true;
}

/*
 * START: INLINE DOCUMENTATION IMPORT INTEGRATION
 *
 */
function inline_doc_content_import($baseurl, $settings) {
	$file = $baseurl . '/inlinedoc.xml';
	$file_headers_inline_doc = @get_headers ( $file );
	
	// Check if inline doc XML exist, if yes then import
	if (strpos ( $file_headers_inline_doc [0], '200 OK' )) {
		$xml_import_data = file_get_contents ( $file );
		
		if (class_exists ( 'Class_Inline_Documentation' )) {
			$Class_Inline_Documentation = new Class_Inline_Documentation ();
			$Class_Inline_Documentation->inline_doc_import ( $xml_import_data );
		}
	}
	return true;
}
/*
 * END: INLINE DOCUMENTATION IMPORT INTEGRATION
 *
 */

/*
 * START: MODULE MANAGER IMPORT INTEGRATION
 *
 */
function module_manager_views_demo_import($baseurl, $settings) {
	$all_plugins_found = get_plugins ();
	$compatible_types_views_found = wpvdemo_get_types_views_pluginlist ( $all_plugins_found );
	$compatible_types_views_found_in_pluginsdir = false;
	
	if ((is_array ( $compatible_types_views_found )) && (! (empty ( $compatible_types_views_found )))) {
		$compatible_types_views_found_in_pluginsdir = true;
	}
	
	// Import modules only if using compatible Types and Views in plugins directory;
	// Just as it was when importing modules manually.
	
	if ((class_exists ( 'ModuleManager' )) && ($compatible_types_views_found_in_pluginsdir)) {
		// Module manager plugin class exists
		
		$module_manager_plugin_path = MODMAN_PLUGIN_PATH;
		$embedded_module_library_install_class_path = $module_manager_plugin_path . '/library/Class_Install_Library.php';
		require_once ($embedded_module_library_install_class_path);
		$Class_Install_Library = new Class_Install_Library ();
		
		if (! empty ( $settings->modules )) {
			
			$modules_data = $settings->modules;
			
			foreach ( $modules_data as $modules_imported ) {
				
				foreach ( $modules_imported as $key => $exported_modules ) {
					
					$modules_exported_path = $exported_modules->path;
					
					// Define the URL location of the modules zip file
					$file = ( string ) $modules_exported_path;
					$parameters ['module_path'] = $file;
					
					$imported_id_in_database = $Class_Install_Library->mm_automatic_install_wc_views ( $parameters );
					// Don't bombard server with request.
					sleep ( 2 );
				}
			}
		}
	}
}

/*
 * END: MODULE MANAGER IMPORT INTEGRATION
 *
 */
/**
 * Imports cred.
 *
 * @global type $wpdb
 * @param type $baseurl        	
 * @return type
 */
function wpvdemo_import_cred($baseurl, $settings) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $wpdb;
	
	if (defined ( 'CRED_FE_VERSION' )) {
		
		$file = $baseurl . '/cred.xml';
		
		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		
		$cred_evaluate_shortname = (string)$settings->shortname;
		
		//Call filter for sites requiring CRED Commerce
		$sites_requiring_cred_commerce=apply_filters('wpvdemo_import_cred_commerce_settings',array());
		
		if (in_array($cred_evaluate_shortname,$sites_requiring_cred_commerce)) {		
			
			// Importing Classifieds site, enable CRED commerce
			
			$cred_commerce_path_required = get_cred_commerce_plugin_path_import ();
			
			if (! (empty ( $cred_commerce_path_required ))) {
				
				require_once $cred_commerce_path_required;
				CRED_Commerce::init ( 'woocommerce', true );
				
				$result = cred_import_xml_from_string ( $data, array (
						'overwrite_forms' => 1,
						'overwrite_settings' => 1,
						'overwrite_custom_fields' => 1 
				) );
				
				if (false === $result || is_wp_error ( $result ))
					return (false === $result) ? __ ( 'Error during CRED import', 'wpvdemo' ) : $result->get_error_message ( $result->get_error_code () );
			} else {
				
				die ( 'It seems you really do not have CRED commerce in your plugins directory.' );
			}
		} else {
			
			// Import CRED normally
			
			$result = cred_import_xml_from_string ( $data );
			
			if (false === $result || is_wp_error ( $result ))
				return (false === $result) ? __ ( 'Error during CRED import', 'wpvdemo' ) : $result->get_error_message ( $result->get_error_code () );
			
				//Call filter for sites requiring import of CRED User Forms
				$sites_requiring_cred_user_forms = apply_filters( 'wpvdemo_import_cred_user_forms',array() );
				if (in_array( $cred_evaluate_shortname , $sites_requiring_cred_user_forms ) ) {
					//Import CRED User forms for this site
					$cred_user_form_export_file = $baseurl . '/cred_user_forms.xml';
					
					// Parse remote XML
					$cred_user_form_exported_data = wpv_remote_xml_get ( $cred_user_form_export_file );
					if (! ( $cred_user_form_exported_data ) ) {
						return false;
					}
					
					//Call CRED User form Import functions API
					$cred_user_form_result = cred_user_import_xml_from_string( $cred_user_form_exported_data );
				}			
		}
	}
	
	return true;
}
function get_cred_commerce_plugin_path_import() {
	
	// Get active plugins
	$active_plugins_cred_commerce = get_option ( 'active_plugins', array () );
	
	$probable_credcommerce_path = array ();
	
	// Loop through array and find CRED commerce plugin possible path
	foreach ( $active_plugins_cred_commerce as $k => $v ) {
		
		if (strpos ( $v, 'plugin.php' )) {
			
			$probable_credcommerce_path [] = dirname ( WPVDEMO_ABSPATH ) . '/' . $v;
		}
	}
	if (! (empty ( $probable_credcommerce_path ))) {
		// Loop through the $probable_credcommerce_path and find the exact CRED commerce path
		foreach ( $probable_credcommerce_path as $key => $value ) {
			
			$cred_commerce_handle = fopen ( $value, "r" );
			$cred_commerce_contents = fread ( $cred_commerce_handle, filesize ( $value ) );
			$cred_commerce_pieces = explode ( "\n", $cred_commerce_contents );
			$cred_commerce_key = array_find ( 'Plugin Name', $cred_commerce_pieces );
			$cred_commerce_value = trim ( $cred_commerce_pieces [$cred_commerce_key] );
			$cred_commerce_value_exploded = explode ( ":", $cred_commerce_value );
			;
			$cred_commerce_plugin_name_extract = $cred_commerce_value_exploded [1];
			
			// This is the actual plugin name of plugin.php found in plugins directory
			$cred_commerce_plugin_name_extract = trim ( $cred_commerce_plugin_name_extract );
			
			//Backward compatibility to old CRED Commerce plugin name
			if (('CRED Commerce' == $cred_commerce_plugin_name_extract ) || ('Toolset CRED Commerce' == $cred_commerce_plugin_name_extract )) {
				
				// Check first if file exists
				if (file_exists ( $value )) {
					
					return $value;
				} else {
					
					return '';
				}
			}
		}
	} else {
		
		return '';
	}
}
/**
 * Imports Types Access
 */
function wpvdemo_import_access($baseurl, $settings) {
	global $wpdb;
	
	if (defined ( 'TACCESS_VERSION' ) && function_exists ( 'taccess_import' )) {
		
		$file = $baseurl . '/access.xml';
		
		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		
		$options_access = array ();
		
		//Validate $data make sure its not empty
		$xml = simplexml_load_string ( $data );
		$validated_import_data = wpv_admin_import_export_simplexml2array ( $xml );
		if (!array_filter($validated_import_data)) {
			
			//We have empty values, don't do anything, just return to normal import processes
			return true;
		}
		
		//No empty values here, proceed..
		$result = taccess_import ( $data, $options_access );
		if (false === $result || is_wp_error ( $result ))
			return (false === $result) ? __ ( 'Error during Access import', 'wpvdemo' ) : $result->get_error_message ( $result->get_error_code () );
	}
	
	return true;
}

/**
 * Imports Classifieds Site and BootCommerce WooCommerce settings
 */
function wpvdemo_import_classifieds_woocommerce($baseurl, $settings) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $wpdb;	
	$site_imported_shortname = ( string ) $settings->shortname;
	$array_of_woocommerce_export_files=apply_filters('wpdemo_old_bootstrap_sites_with_ecommerce',array());
	
	if (isset ( $array_of_woocommerce_export_files [$site_imported_shortname]['woocommerce_import_file'] )) {
		
		$woocommerce_export_file_name = $array_of_woocommerce_export_files [$site_imported_shortname]['woocommerce_import_file'];
		$file = $baseurl . '/' . $woocommerce_export_file_name;
		
		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		
		$xml = simplexml_load_string ( $data );
		$import_data = wpv_admin_import_export_simplexml2array ( $xml );
		
		// Loop through the settings and update WooCommerce options
		foreach ( $import_data as $key => $value ) {
			update_option ( $key, $value );
		}
		
		// Define WooCommerce shop page ID
		$existing_shop_page = get_option ( 'woocommerce_shop_page_id' );
		if ((! ($existing_shop_page)) || (empty ( $existing_shop_page ))) {
			
			// Shop page not yet defined
			$posttable = $wpdb->posts;
			$shop_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='shop' AND post_type='page'" );
			if (! (empty ( $shop_page_id ))) {
				update_option ( 'woocommerce_shop_page_id', $shop_page_id );
			}
		}
		// Finishing touches
		global $current_user;
		$user_current_email = $current_user->user_email;
		
		if ($user_current_email) {
			
			update_option ( 'woocommerce_stock_email_recipient', $user_current_email );
			update_option ( 'woocommerce_new_order_email_recipient', $user_current_email );
		}
	}
	return true;
}
/**
 * Imports Classifieds Site and BootCommerce WooCommerce Views settings
 */
function wpvdemo_import_woocommerce_views($baseurl, $settings) {
	
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $Class_WooCommerce_Views;	
	$site_imported_shortname = ( string ) $settings->shortname;
	
	//Call API for old Bootstrap sites with WooCommerce
	$array_of_woocommerce_export_files=apply_filters('wpdemo_old_bootstrap_sites_with_ecommerce',array());
	
	if (isset ( $array_of_woocommerce_export_files [$site_imported_shortname]['importwcviews'] )) {
		
		//Check if we need to import WC Views settings
		$need_to_import=$array_of_woocommerce_export_files [$site_imported_shortname]['importwcviews'];
		
		if ($need_to_import) {
			//Need to import..proceed.
			//This one does not change, its fixed at the export end.
			$woocommerce_export_file_name='woocommerce_views.xml';
			$file = $baseurl . '/' . $woocommerce_export_file_name;
			
			// Parse remote XML
			$data = wpv_remote_xml_get ( $file );
			if (! ($data)) {
				return false;
			}
			
			// Parse to XML
			$xml = simplexml_load_string ( $data );
			
			// Pass to WooCommerce Views import method
			if (is_object ( $Class_WooCommerce_Views )) {
				if (method_exists ( $Class_WooCommerce_Views, 'wcviews_import_settings' )) {
					// Import method exist, import data
					$Class_WooCommerce_Views->wcviews_import_settings ( $xml );
				}
			}
		}
	}
	
	return true;
}
/**
 * Imports Classifieds CRED custom field settings
 */
function wpvdemo_import_classifieds_credcustomfields($baseurl, $settings) {
	global $wpdb;
	
	$file = $baseurl . '/classifieds_credcustomfields.xml';
	
	// Parse remote XML
	$data = wpv_remote_xml_get ( $file );
	if (! ($data)) {
		return false;
	}
	
	$xml = simplexml_load_string ( $data );
	$import_data = wpv_admin_import_export_simplexml2array ( $xml );
	
	// Loop through the settings and update WooCommerce options
	foreach ( $import_data as $key => $value ) {
		update_option ( $key, $value );
	}
	
	return true;
}

/**
 * Imports Classifieds Site User Roles
 */
function wpvdemo_import_classifieds_user_roles($baseurl, $define_table_prefix,$settings) {
	
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $wpdb;
	$site_imported_shortname = ( string ) $settings->shortname;
	$user_role_implementation=apply_filters('wpvdemo_import_user_roles',array(),$site_imported_shortname);
	if (isset($user_role_implementation[$site_imported_shortname])) { 
		//This site has user role implementation
		$xml_file_name= $user_role_implementation[$site_imported_shortname];
		$file = $baseurl . '/'.$xml_file_name;
		
		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		
		$xml = simplexml_load_string ( $data );
		$import_data = wpv_admin_import_export_simplexml2array ( $xml );
		
		$user_role_option_name = $define_table_prefix . 'user_roles';
		
		$import_user_role_settings = array ();
		
		foreach ( $import_data as $key => $value ) {
			
			$import_user_role_settings = $value;
		}
		
		// Save the imported role settings to the database
		update_option ( $user_role_option_name, $import_user_role_settings );
	}
	return true;
}
function wpvdemo_config_notification_classifieds_site($baseurl, $define_table_prefix,$settings) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$site_imported_shortname = ( string ) $settings->shortname;
	global $wpdb;
	global $current_user;
	
	$need_config=apply_filters('wpvdemo_config_crednotification',false,$site_imported_shortname);
	
	if ($need_config) {
		
		$post_table_name = $define_table_prefix . 'posts';
		
		// Define CRED forms array with notifications
		/* Framework Installer 1.5.3 -updated with Classifieds site new Add package forms */
		$cred_forms_array_name = array (
				0 => 'add-new-free-ad',
				1 => 'add-new-premium-ad',
				2 => 'edit-product',
				3 => 'add-another-premium-ad',
				4 => 'add-new-ad-package' 
		);
		
		// Query database for Post ID of these forms given post_name and "cred-form" post_type
		$cred_form_id_db = array ();
		
		foreach ( $cred_forms_array_name as $k => $v ) {
			
			$cred_form_id_db [] = $wpdb->get_var ( $wpdb->prepare ( "SELECT ID FROM $post_table_name WHERE post_name = %s AND post_type='cred-form'", $v ) );
		}
		
		// Get the notification array given ID
		
		$cred_notification_big_array = array ();
		foreach ( $cred_form_id_db as $key => $value ) {
			
			$cred_notification_big_array [$value] = get_post_meta ( $value, '_cred_notification', TRUE );
		}
		
		// Define current user email
		
		$replacement_email = $current_user->user_email;
		
		// Serialize each one of big notification array
		$serialized_version = array ();
		foreach ( $cred_notification_big_array as $k_serialize => $v_serialize ) {
			
			$serialized_version [$k_serialize] = serialize ( $v_serialize );
		}
		
		// Search and replace email with user email
		$replaced_serialized_array = array ();
		foreach ( $serialized_version as $k_replace => $v_replace ) {
			
			$replaced_serialized = str_replace ( 'classifieds_website@mailinator.com', $replacement_email, $v_replace );
			// $replaced_serialized_array[$k_replace] = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $replaced_serialized);
			$replaced_serialized_array [$k_replace] = preg_replace_callback ( '!s:(\d+):"(.*?)";!', function ($matches) {
				if (! empty ( $matches [2] )) {
					return 's:' . strlen ( $matches [2] ) . ':"' . $matches [2] . '";';
				}
				return $matches [0];
			}, $replaced_serialized );
		}
		
		// Convert back to PHP array
		$unserialized_array = array ();
		foreach ( $replaced_serialized_array as $k_unserialized => $v_unserialized ) {
			
			$unserialized_array [$k_unserialized] = unserialize ( $v_unserialized );
		}
		
		// Update post meta
		$updated_value_array = array ();
		foreach ( $unserialized_array as $k_updated_value => $v_updated_value ) {
			
			update_post_meta ( $k_updated_value, '_cred_notification', $v_updated_value );
		}
	}
}
/**
 * Imports WPML settings and strings.
 *
 * @global type $wpdb
 * @param type $baseurl        	
 * @return type
 */
function wpvdemo_import_wpml($baseurl, $settings) {
	global $frameworkinstaller;
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$baseurl_memory=$baseurl;
	$baseurl_memory=(string)($baseurl_memory);
	$baseurl_name=basename($baseurl_memory);
	$large_sites= apply_filters('wpvdemo_large_sites_standalone',array());
	if (in_array($baseurl_name,$large_sites)) {		
		$is_system_site=$frameworkinstaller->is_discoverwp();
		if (!($is_system_site)) {			
			//Attempt to adjust to 256M to prevent memory issues during import
			@ini_set('memory_limit', '256M');		
		}
	}
	
	global $wpdb;
	$wpdb->suppress_errors = true;
	$file = $baseurl . '/wpml.xml';
	// Prevent notices in reference sites when it does not have WPML implementation
	$file_headers_wpml = @get_headers ( $file );

	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_headers_wpml [0], '200 OK' ))) {

		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		// Read only if it exist
		$xml = simplexml_load_string ( $data );

		// We can use the Views function to convert to an array
		$data = wpv_admin_import_export_simplexml2array ( $xml );

		// Fix array indexes

		if ((is_array ( $data )) && (! (empty ( $data )))) {
				
			// Read array data if defined and not empty
			if (isset($data ['translation-management'] ['__custom_fields_readonly_config_prev'] ['item'])) {	
				$data ['translation-management'] ['__custom_fields_readonly_config_prev'] = $data ['translation-management'] ['__custom_fields_readonly_config_prev'] ['item'];
			}
			if (isset($data ['translation-management'] ['custom_fields_readonly_config'] ['item'])) {
				$data ['translation-management'] ['custom_fields_readonly_config'] = $data ['translation-management'] ['custom_fields_readonly_config'] ['item'];
			}			
				
			// Compatibility with WPML 2.9.3 exporter
			if (isset ( $data ['languages_order'] )) {
				foreach ( $data ['languages_order'] as $key_import_order => $value_import_order ) {
					$data ['languages_order'] [] = $value_import_order;
					unset ( $data ['languages_order'] [$key_import_order] );
				}
			}
				
			if (isset ( $data ['st'] ['theme_localization_domains'] )) {
				foreach ( $data ['st'] ['theme_localization_domains'] as $key1_import_order => $value1_import_order ) {
					$data ['st'] ['theme_localization_domains'] [] = $value1_import_order;
					unset ( $data ['st'] ['theme_localization_domains'] [$key1_import_order] );
				}
			}
				
			// Compatibility with WPML 3.2.3 exporter
			if (isset ( $data ['st'] ['plugin_localization_domains'] )) {
				foreach ( $data ['st'] ['plugin_localization_domains'] as $key2_import_order => $value2_import_order ) {
					//We need to use the correct key
					$key2_import_order_updated = str_replace('796', '/', $key2_import_order);
					
					//Restore the correct key
					$data ['st'] ['plugin_localization_domains'] [$key2_import_order_updated] = $value2_import_order;
					
					//Remove the old key
					unset ( $data ['st'] ['plugin_localization_domains'] [$key2_import_order] );
				}
			}	
			
			//Update for WPML 3.2.7 exporter compatibility
			if (isset ( $data ['translation-management']['custom_fields_translation_custom_readonly'] )) {
				foreach ( $data ['translation-management']['custom_fields_translation_custom_readonly'] as $key3_import_order => $value3_import_order ) {
					$data ['translation-management']['custom_fields_translation_custom_readonly'] [] = $value3_import_order;
					unset ( $data ['translation-management']['custom_fields_translation_custom_readonly'] [$key3_import_order] );
				}
			}	
				
			// Set the active langauges.
			global $wpdb;
			foreach ( $data ['wpv_active_languages'] as $code => $active ) {
				$wpdb->query ( $wpdb->prepare ( "UPDATE {$wpdb->prefix}icl_languages SET active=%d WHERE code='%s'", $active, $code ) );
			}
				
			unset ( $data ['wpv_active_languages'] );
				
			update_option ( 'icl_sitepress_settings', $data );
			update_option ( 'icl_sitepress_backup_settings', $data);
			update_option ( 'wpvdemo_sitepress_settings_set', 'yes');				
			global $sitepress;

			if ((isset($sitepress->icl_translations_cache)) && (is_object($sitepress->icl_translations_cache))) {
				$var_icl_translations_cache=$sitepress->icl_translations_cache;
				if (method_exists($var_icl_translations_cache,'clear')) {
					$var_icl_translations_cache->clear();
				}
			}
				
			if ((isset($sitepress->icl_locale_cache)) && (is_object($sitepress->icl_locale_cache))) {
				$var_icl_locale_cache= $sitepress->icl_locale_cache;
				if (method_exists($var_icl_locale_cache,'clear')) {
					$var_icl_locale_cache->clear();
				}
			}
				
			if ((isset($sitepress->icl_flag_cache)) && (is_object($sitepress->icl_flag_cache))) {
				$var_icl_flag_cache=$sitepress->icl_flag_cache;
				if (method_exists($var_icl_flag_cache,'clear')) {
					$var_icl_flag_cache->clear();
				}
			}

			if ((isset($sitepress->icl_language_name_cache)) && (is_object($sitepress->icl_language_name_cache))) {
				$var_icl_language_name_cache=$sitepress->icl_language_name_cache;
				if (method_exists($var_icl_language_name_cache,'clear')) {
					$var_icl_language_name_cache->clear();
				}
			}
				
			if ((isset($sitepress->icl_term_taxonomy_cache)) && (is_object($sitepress->icl_term_taxonomy_cache))) {
				$var_icl_term_taxonomy_cache= $sitepress->icl_term_taxonomy_cache;
				if (method_exists($var_icl_term_taxonomy_cache,'clear')) {
					$var_icl_term_taxonomy_cache->clear();
				}
			}

			$file = $baseurl . '/wpml_strings.xml.zip';
				
			$data_strings_translation = wpv_remote_xml_get ( $file );
			if (! ($data_strings_translation)) {
				return false;
			}
				
			$tmp_name = tempnam ( "tmp", "zip" );
			$handle = fopen ( $tmp_name, 'w' );
			fwrite ( $handle, $data_strings_translation );
			fclose ( $handle );
				
			$zip = zip_open ( $tmp_name );
			if (is_resource ( $zip )) {
				while ( ($zip_entry = zip_read ( $zip )) !== false ) {
					if (zip_entry_name ( $zip_entry ) == 'wpml_strings.xml') {
						$data = @zip_entry_read ( $zip_entry, zip_entry_filesize ( $zip_entry ) );
					}
				}
				zip_close ( $zip );
				unlink ( $tmp_name );
			} else {
				$file = $baseurl . '/wpml_strings.xml';
				$data = wpv_remote_xml_get ( $file );
				if (! (data)) {
					return false;
				}
			}
				
			$xml = simplexml_load_string ( $data );
			
			// We can use the Views function to convert to an array
			$data = wpv_admin_import_export_simplexml2array ( $xml );
				
			/** Framework Installer 1.8.2: We need to clear string translation tables so it will be entirely the same with the ref sites. */
			/** Compatibility with WPML 3.2.3 */
			/** START */
			if (!(empty($data))) {				 
				
				$wpml_icl_strings_target=$wpdb->prefix.'icl_strings';
				 
				//Clear translations table in advance
				$wpdb->query(
						"
						DELETE FROM $wpml_icl_strings_target
						WHERE id > 0
						"
				);
						 
			}		
			/** END */
			
			foreach ( $data ['strings'] ['item'] as $string ) {
				$wpdb->insert ( $wpdb->prefix . 'icl_strings', $string );
			}
				
			foreach ( $data ['translations'] ['item'] as $string ) {

				// Fix warning: mysql_real_escape_string() expects parameter 1 to be string on Views commerce ML
				// Make sure all inserted values are in string format

				$array_free_string = array ();

				foreach ( $string as $k => $v ) {
						
					if (is_array ( $v )) {

						$array_free_string [$k] = serialize ( $v );
					} else {

						$array_free_string [$k] = $v;
					}
				}

				// Now insert
				$wpdb->insert ( $wpdb->prefix . 'icl_string_translations', $array_free_string, array (
						'%d',
						'%d',
						'%s',
						'%d',
						'%s',
						'%d',
						'%s'
				) );
			}
				
			// Special processing for CRED forms context after import
			// IDS will change after import and the context needs to be updated as well
				
			if (defined ( 'CRED_FE_VERSION' )) {
				// CRED plugin enabled, probably has forms
				wpv_demo_cred_forms_context_update_after_import ();
			}
		}
	}

	// Import WPML locale map settings

	$file_locale_map_url = $baseurl . '/wpml_locale_settings.xml';
	$file_locale_map_headers = @get_headers ( $file_locale_map_url );

	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_locale_map_headers [0], '200 OK' ))) {

		// Parse remote XML
		$data_wpml_locale = wpv_remote_xml_get ( $file_locale_map_url );

		if (! ($data_wpml_locale)) {
			return false;
		}

		$xml_locale_settings = simplexml_load_string ( $data_wpml_locale );
		$import_data_wpml_locale_map = wpv_admin_import_export_simplexml2array ( $xml_locale_settings );

		// Prepare data
		foreach ( $import_data_wpml_locale_map as $key_map => $values_map ) {
			$import_data_wpml_locale_map [] = $values_map;
			unset ( $import_data_wpml_locale_map [$key_map] );
		}

		// Loop through the settings and insert to database
		foreach ( $import_data_wpml_locale_map as $map_locale_settings ) {
			$wpdb->insert ( $wpdb->prefix . 'icl_locale_map', $map_locale_settings );
		}
	}

	// Import icl translations status
	$file_icl_translations_status_url = $baseurl . '/wpml_translations_status_export.xml';
	$file_icl_translations_status_headers = @get_headers ( $file_icl_translations_status_url );

	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_icl_translations_status_headers [0], '200 OK' ))) {

		// Parse remote XML
		$data_wpml_icl_translations_status = wpv_remote_xml_get ( $file_icl_translations_status_url );

		if (! ($data_wpml_icl_translations_status)) {
			return false;
		}

		$xml_icl_translations_status_settings = simplexml_load_string ( $data_wpml_icl_translations_status );
		$import_data_wpml_icl_translations_status = wpv_admin_import_export_simplexml2array ( $xml_icl_translations_status_settings );

		// Prepare data
		foreach ( $import_data_wpml_icl_translations_status as $key_status => $values_status ) {
			$import_data_wpml_icl_translations_status [] = $values_status;
			unset ( $import_data_wpml_icl_translations_status [$key_status] );
		}

		// Loop through the settings and insert to database
		foreach ( $import_data_wpml_icl_translations_status as $icl_translations_status_settings_array ) {
			$wpdb->insert ( $wpdb->prefix . 'icl_translation_status', $icl_translations_status_settings_array );
		}
	}

	// Import icl translation jobs
	$file_translationjobs_url = $baseurl . '/wpml_translationjobs_settings.xml';
	$file_translationjobs_headers = @get_headers ($file_translationjobs_url );
	
	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_translationjobs_headers [0], '200 OK' ))) {
	
		// Parse remote XML
		$data_wpml_translationjobs= wpv_remote_xml_get ( $file_translationjobs_url );
	
		if (! ($data_wpml_translationjobs)) {
			return false;
		}
	
		$xml_translationjobs_settings = simplexml_load_string ( $data_wpml_translationjobs );
		$import_data_wpml_translationjobs = wpv_admin_import_export_simplexml2array ( $xml_translationjobs_settings );
	
		// Prepare data
		foreach ( $import_data_wpml_translationjobs as $key_tj => $values_tj ) {
			$import_data_wpml_translationjobs [] = $values_tj;
			unset ( $import_data_wpml_translationjobs [$key_tj] );
		}
	
		// Loop through the settings and insert to database
		foreach ( $import_data_wpml_translationjobs as $the_translation_job_settings ) {
			$translation_job_rev_data= $the_translation_job_settings['revision'];
			if ( null == $translation_job_rev_data ) {
				//This is null
				//Remove from array, so it will use the correct NULL default value in the table.
				unset($the_translation_job_settings['revision']);
			}
			$wpdb->insert ( $wpdb->prefix . 'icl_translate_job', $the_translation_job_settings );
		}
	}
	// Import icl translation batches
	$file_translationbatches_url = $baseurl . '/wpml_translationbatches_settings.xml';
	$file_translationbatches_headers = @get_headers ($file_translationbatches_url );
	
	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_translationbatches_headers[0], '200 OK' ))) {
	
		// Parse remote XML
		$data_wpml_translationbatches= wpv_remote_xml_get ( $file_translationbatches_url );
	
		if (! ($data_wpml_translationbatches)) {
			return false;
		}
	
		$xml_translationbatches_settings = simplexml_load_string ( $data_wpml_translationbatches );
		$import_data_wpml_translationbatches = wpv_admin_import_export_simplexml2array ($xml_translationbatches_settings );
	
		// Prepare data
		foreach ( $import_data_wpml_translationbatches as $key_tb => $values_tb ) {
			$import_data_wpml_translationbatches [] = $values_tb;
			unset ( $import_data_wpml_translationbatches [$key_tb] );
		}
	
		// Loop through the settings and insert to database
		foreach ( $import_data_wpml_translationbatches as $the_translation_batches_settings ) {
			$tp_id=$the_translation_batches_settings['tp_id'];
			$ts_url=$the_translation_batches_settings['ts_url'];
			if (( null == $tp_id) && ($tp_id !==0)) {
				//Remove from array, so it will use the correct NULL default value in the table.
				unset($the_translation_batches_settings['tp_id']);
			}
			if ((null == $ts_url) && ($ts_url !==0)) {
				//Remove from array, so it will use the correct NULL default value in the table.
				unset($the_translation_batches_settings['ts_url']);
			}
						
			$wpdb->insert ( $wpdb->prefix . 'icl_translation_batches', $the_translation_batches_settings );
		}
	}

	// Import icl translate table data
	$file_icl_translate_url = $baseurl . '/wpml_icl_translate_settings.xml';
	$file_icl_translate_headers = @get_headers ($file_icl_translate_url );
	
	if ((defined ( 'ICL_SITEPRESS_VERSION' )) && (strpos ( $file_icl_translate_headers[0], '200 OK' ))) {
	
		// Parse remote XML
		$data_wpml_icl_translate= wpv_remote_xml_get ( $file_icl_translate_url );
	
		if (! ($data_wpml_icl_translate)) {
			return false;
		}
	
		$xml_icl_translate_settings = simplexml_load_string ( $data_wpml_icl_translate );
		$import_data_wpml_icl_translate = wpv_admin_import_export_simplexml2array ($xml_icl_translate_settings );
	
		// Prepare data
		foreach ( $import_data_wpml_icl_translate as $key_iclt => $values_iclt ) {
			$import_data_wpml_icl_translate [] = $values_iclt;
			unset ( $import_data_wpml_icl_translate [$key_iclt] );
		}
	
		// Loop through the settings and insert to database
		foreach ( $import_data_wpml_icl_translate as $the_icl_translate_settings ) {	
			$wpdb->insert ( $wpdb->prefix . 'icl_translate', $the_icl_translate_settings );
		}
	}	

	//Align icl_translation ids to imported ids
	$frameworkinstaller->wpvdemo_align_original_id_after_multilingual_import();
	
	return true;
}

/**
 * Imports posts.
 *
 * @global WPVDemo_Importer $wpvdemo_import
 * @param type $baseurl        	
 * @param type $site_url        	
 */
function wpvdemo_import_posts($settings, $import_settings) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $frameworkinstaller;
	$is_discover_verify = false;
	if (method_exists ( $frameworkinstaller, 'is_discoverwp' )) {
		$is_discover_verify = $frameworkinstaller->is_discoverwp ();
	}	
	define ( 'WP_LOAD_IMPORTERS', true );
	require_once WPVDEMO_ABSPATH . '/class.importer.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/comment.php';
	remove_action ( 'admin_init', 'wordpress_importer_init', 10, 1 );
	global $wpvdemo_import;
	$wpvdemo_import = new WPVDemo_Importer ( $settings->site_url );
	$wpvdemo_import->fetch_attachments = $import_settings->fetch_attachments;
	$wpvdemo_import->current_site_settings = $settings;
	$original_plugin_lists = wpvdemo_format_display_plugins ( $settings->plugins->plugin, false );
	$skip_wpml_during_post_import = wpvdemo_check_if_wpml_will_be_skipped ( $original_plugin_lists, $settings->download_url, false, true, '' );
	
	//Call API for sites with multilingual implementation
	$eligible_sites= apply_filters('wpdemo_sites_with_multilingual',array(),'shortnames');	
	$current_site = ( string ) $settings->shortname;
	
	if (in_array ( $current_site, $eligible_sites )) {		

		// WPML sites for importing
		/** Framework intaller 1.8.2+ in Discover-WP , it will now allow non-multilingual import of a multilingual sites */
		
		// Standalone or Discover-WP
		$the_version_installed = get_option ( 'wpvdemo_the_version_installed' );
		
		// Let's checked the version to be installed based on the plugins activated and what the user wants
		if ('nowpml' == $the_version_installed) {
			// Override $skip_wpml_during_post_import
			$skip_wpml_during_post_import = true;
		}

		if (!($skip_wpml_during_post_import)) {
			/** Added WPML tables requisite check */
			/** Bail out early if something went wrong with creating WPML database tables, so as not to waste time on users end. */
			if (method_exists($frameworkinstaller,'wpvdemo_validate_icl_required_db_tables')) {
				$wpml_tables_exist=$frameworkinstaller->wpvdemo_validate_icl_required_db_tables();
				if (!($wpml_tables_exist)) {
					if (!($is_discover_verify)) {
						//Show standalone errors.
						echo sprintf(wpvdemo_error_message('wpml_tables_not_created_standalone', true));
						die();
					} else {
						//Show discover-wp errors.
						echo sprintf(wpvdemo_error_message('wpml_tables_not_created_discoverwp', true));
						die();
					}
				}
			}		
		}
		
		if ($skip_wpml_during_post_import) {
			$file = $settings->download_url . '/posts_no_wpml.xml';
		} else {
			$file = $settings->download_url . '/posts.xml';
		}
	} else {
		
		$file = $settings->download_url . '/posts.xml';
	}
	
	$wpvdemo_import->dispatch ( $file );
}

/**
 * Downloads theme.
 *
 * @param type $url        	
 * @return boolean
 */
function wpvdemo_download_theme($url) {
	$themes_dir = dirname ( get_stylesheet_directory () );
	if (! is_writeable ( $themes_dir )) {
		wpvdemo_requirements_themes_writeable_error_message ();
		return false;
	}
	// EMERSON: Rewrite theme download function
	// Prevent any issues like error in downloading theme
	// Using wp_get_http before caused some issues with WP 3.6
	
	// Define the URL location of the theme zip file in reference site
	$file = ( string ) $url;
	
	// Define download path to local
	$new_file = $themes_dir . '/' . basename ( $url );
	
	// File headers
	$file_headers_theme_zip = @get_headers ( $file );
	
	// Delete existing theme if to update it
	$info = pathinfo ( $new_file );
	$template_name_install = $info ['filename'];
	$check_if_theme_exist = $info ['dirname'] . '/' . $template_name_install;
	
	if (file_exists ( $check_if_theme_exist )) {
		// Make sure we can delete
		// Make an exception for Multisite
		if (! (is_multisite ())) {
			chmod_R ( $check_if_theme_exist, 0777, 0777 );
			
			// Now delete
			rmdir_recursive ( $check_if_theme_exist );
		}
	}
	
	// Download theme file
	// Don't open if zip does not exist
	
	if (strpos ( $file_headers_theme_zip [0], '200 OK' )) {
		
		// For multisite like discover-wp, dont re-download theme if already exists
		// Do this for standalone installation only
		
		if ((! (is_multisite ())) || (! (file_exists ( $check_if_theme_exist )))) {
			
			// Set context
			$context = stream_context_create ( array (
					'http' => array (
							'timeout' => 1200 
					) 
			) );
			$success = file_put_contents ( $new_file, fopen ( $file, 'r', false, $context ) );
			
			if ($success) {
				// Unzip
				$is_zip = $info ['extension'] == 'zip' ? true : false;
				if ($is_zip) {
					$zip = new ZipArchive ();
					$res = $zip->open ( $new_file );
					if ($res === TRUE) {
						$zip->extractTo ( $themes_dir );
						$zip->close ();
						unlink ( $new_file );
						return true;
					} else {
						echo __ ( 'Unable to open zip file', 'wpcf' ) . '<br />';
					}
				}
				unlink ( $new_file );
			}
		}
		return true;
	} else {
		
		echo __ ( 'Unable to fetch zip file', 'wpvdemo' ) . '<br />';
	}
	// END
	
	return false;
}

/**
 * Imports widgets.
 *
 * @global type $wpdb
 * @param type $widgets        	
 */
function wpvdemo_import_widgets($widgets) {
	global $wpdb;
	$current_sidebars = get_option ( 'sidebars_widgets' );
	if (! empty ( $widgets->sidebars->sidebar )) {
		foreach ( $widgets->sidebars->sidebar as $sidebar ) {
			$current_sidebars [( string ) $sidebar->name] = array ();
			foreach ( $sidebar->widgets->widget as $widget ) {
				$current_sidebars [( string ) $sidebar->name] [] = ( string ) $widget;
			}
		}
		update_option ( 'sidebars_widgets', $current_sidebars );
	}
	if (! empty ( $widgets->widgets->widget )) {
		$update_widgets = array ();
		foreach ( $widgets->widgets->widget as $widget ) {
			$widget_name = 'widget_' . ( string ) $widget->type;
			$widget_index = ( int ) $widget->type_index;
			$update_widgets [$widget_name] ['_multiwidget'] = ( int ) $widget->_multiwidget;
			// If widget is views
			if ((( string ) $widget->type == 'wp_views' || ( string ) $widget->type == 'wp_views_filter') && ! empty ( $widget->view_post_name )) {
				$view_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'view' AND post_name = %s", ( string ) $widget->view_post_name ) );
				if (! empty ( $view_id )) {
					$widget->value->view = $view_id;
				}
			}
			if (( string ) $widget->type == 'wp_views_filter' && ! empty ( $widget->target_post_name )) {
				$target_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name = %s", ( string ) $widget->target_post_name ) );
				if (! empty ( $target_id )) {
					$widget->value->target_id = $target_id;
				}
			}
			foreach ( $widget->value as $value ) {
				foreach ( $value as $value_title => $value_data ) {
					$update_widgets [$widget_name] [$widget_index] [( string ) $value_title] = ( string ) $value_data;
				}
			}
		}
		foreach ( $update_widgets as $widget_title => $widget_data ) {
			if ('widget_nav_menu' == $widget_title) {
				$widget_data =wpvdemo_adjust_widgetnav_menu_ids($widget_data);
			}			
			update_option ( $widget_title, $widget_data );
		}
	}
}

function wpvdemo_adjust_widgetnav_menu_ids($widget_data) {
	
	//Step1: Retrieved the processed terms
	$the_processed_terms= get_option('wpvdemo_processed_terms_imported');
	if ((!(empty($the_processed_terms))) && (is_array($the_processed_terms))) {
		if ((!(empty($widget_data))) && (is_array($widget_data))) {
			foreach ($widget_data as $k=>$v) {
				if (isset($v['nav_menu'])) {
					$nav_menu= $v['nav_menu'];
					$nav_menu_int=intval($nav_menu);
					if ($nav_menu_int > 0) {
						if (isset($the_processed_terms[$nav_menu])) {
							$nav_menu_imported=$the_processed_terms[$nav_menu];
							$nav_menu_int_imported=intval($nav_menu_imported);
							if ($nav_menu_int_imported > 0) {
								$widget_data[$k]['nav_menu']=$nav_menu_imported;
							}
						}
					}
					
				}
			}
		}
	}
	return $widget_data;	
}

/**
 * Checks safe_mode.
 *
 * @return boolean
 */
function wpvdemo_is_safe_mode() {
	$my_boolean = ini_get ( 'safe_mode' );
	if (( int ) $my_boolean > 0) {
		$my_boolean = true;
	} else {
		$my_lowered_boolean = strtolower ( $my_boolean );
		if ($my_lowered_boolean === "true" || $my_lowered_boolean === "on" || $my_lowered_boolean === "yes") {
			$my_boolean = true;
		} else {
			$my_boolean = false;
		}
	}
	return $my_boolean;
}
function bootmag_xml2array($xml) {
	$arr = array ();
	
	foreach ( $xml as $element ) {
		$tag = $element->getName ();
		$e = get_object_vars ( $element );
		if (! empty ( $e )) {
			$arr [$tag] = $element instanceof SimpleXMLElement ? bootmag_xml2array ( $element ) : $e;
		} else {
			$arr [$tag] = trim ( $element );
		}
	}
	
	return $arr;
}
function update_options_classified_widgets() {
	
	// Define widget variables for import
	$import_widget_categories = array (
			'_multiwidget' => '1' 
	);
	$import_widget_search = array (
			'_multiwidget' => '1' 
	);
	$import_widget_recent_posts = array (
			'_multiwidget' => '1' 
	);
	$import_widget_recent_comments = array (
			'_multiwidget' => '1' 
	);
	$import_widget_archives = array (
			'_multiwidget' => '1' 
	);
	$import_widget_meta = array (
			'_multiwidget' => '1' 
	);
	$import_sidebars_widgets = array (
			'wp_inactive_widgets' => array (),
			'header_sidebar' => array (),
			'center_foot_sidebar' => array (),
			'foot_sidebar_1' => array (),
			'array_version' => '3' 
	);
	
	// Update options
	update_option ( 'widget_categories', $import_widget_categories );
	update_option ( 'widget_search', $import_widget_search );
	update_option ( 'widget_recent-posts', $import_widget_recent_posts );
	update_option ( 'widget_recent-comments', $import_widget_recent_comments );
	update_option ( 'widget_archives', $import_widget_archives );
	update_option ( 'widget_meta', $import_widget_meta );
	update_option ( 'sidebars_widgets', $import_sidebars_widgets );
}

function wpvdemo_old_bootstrap_clear_all_sidebar_widgets() {
	//Views tutorial reference site has no sidebar widgets
	$no_sidebar_widgets_array=array('wp_inactive_widgets'=>array(),'sidebar'=>array(),'header-widgets'=>array(),'footer-widgets'=>array(),'array_version'=>3);
	update_option('sidebars_widgets',$no_sidebar_widgets_array);
}
function wpbootstrap_default_grid_xml_import() {
	$layout_xml_path = WPVDEMO_ABSPATH . '/includes/layout-grid.xml';
	$xml2array_bootstrap_path = WPVDEMO_ABSPATH . '/includes/XML2Array.class.php';
	
	if ((file_exists ( $layout_xml_path )) && (file_exists ( $xml2array_bootstrap_path ))) {
		
		$bootstrap_xml_grid_list_exist = get_option ( 'bootstrap_xml_grid_list' );
		if (! (class_exists ( 'XML2Array' ))) {
			require_once $xml2array_bootstrap_path;
		}
		if (empty ( $bootstrap_xml_grid_list_exist )) {
			$file = file_get_contents ( $layout_xml_path );
			if ($file == true) {
				$grid_array = XML2Array::createArray ( $file );
				add_option ( 'bootstrap_xml_grid_list', $grid_array ["GridList"], '', 'yes' );
			}
		}
	}
}
function wpvdemo_auto_addheadermenu() {
	//Fixed header menu not imported with WPML
	$menu_array_wpml=array(0=>false,'auto_add'=>array());
	$success_updating_menu_array_wpml=update_option( 'nav_menu_options', $menu_array_wpml );
}
function wpdemo_localhost_reference_site($r) {
	$r ['reject_unsafe_urls'] = false;
	
	return $r;
}
function fix_image_urls_bootstrap_vanilla_standalone($site_url_imported_vanilla) {
	global $wpdb;
	
	$problem_image_path = $site_url_imported_vanilla . '/files';
	$uploads_constants_of_this_site = wp_upload_dir ();
	$correct_uploads_url_image_path = $uploads_constants_of_this_site ['baseurl'];
	
	// search and replace
	
	$success_replace = $wpdb->query ( $wpdb->prepare ( "
		UPDATE $wpdb->posts
		SET post_content = replace(post_content,'%s','%s')
		", $problem_image_path, $correct_uploads_url_image_path ) );
}

/* Recursive CHMOD for theme directory and files */
function chmod_R($path, $filemode, $dirmode) {
	if (is_dir ( $path )) {
		if (! chmod ( $path, $dirmode )) {
			$dirmode_str = decoct ( $dirmode );
			print "Failed applying filemode '$dirmode_str' on directory '$path'\n";
			print "  `-> the directory '$path' will be skipped from recursive chmod\n";
			return;
		}
		$dh = opendir ( $path );
		while ( ($file = readdir ( $dh )) !== false ) {
			if ($file != '.' && $file != '..') { // skip self and parent pointing directories
				$fullpath = $path . '/' . $file;
				chmod_R ( $fullpath, $filemode, $dirmode );
			}
		}
		closedir ( $dh );
	} else {
		if (is_link ( $path )) {
			print "link '$path' is skipped\n";
			return;
		}
		if (! chmod ( $path, $filemode )) {
			$filemode_str = decoct ( $filemode );
			print "Failed applying filemode '$filemode_str' on file '$path'\n";
			return;
		}
	}
}

/* Recursive delete for theme directory and files */
function rmdir_recursive($dir) {
	foreach ( scandir ( $dir ) as $file ) {
		if ('.' === $file || '..' === $file)
			continue;
		if (is_dir ( "$dir/$file" ))
			rmdir_recursive ( "$dir/$file" );
		else
			unlink ( "$dir/$file" );
	}
	rmdir ( $dir );
}

/* Turn off WC admin notices after import */
function wpvdemo_after_import_turnoffwc_notices($settings) {
	$site_imported_shortname = ( string ) $settings->shortname;
	$wpvdemo_turnoff_wc_adminnotices= apply_filters('wpvdemo_turnoff_wc_adminnotices',false,$site_imported_shortname);
	if ($wpvdemo_turnoff_wc_adminnotices) {
		$notices = array();
		update_option( 'woocommerce_admin_notices', $notices );	
	}
}

/* Setup WooCommerce pages for BootCommerce site */
function setup_woocommerce_setting_pages_bootcommerce($settings) {
	
	$site_imported_shortname = (string) $settings->shortname;
	//Check site that need this
	$wpvdemo_manual_special_wc_pages_configuration= apply_filters('wpvdemo_manual_special_wc_pages_configuration',false,$site_imported_shortname);
	
	if ($wpvdemo_manual_special_wc_pages_configuration) {

		global $wpdb;
		$posttable = $wpdb->posts;
		
		/* Retrieve page IDs of different WooCommerce setting pages */
		
		// Get cart page ID
		$cart_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='cart' AND post_type='page'" );
		
		// Get checkout page ID
		$checkout_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='checkout' AND post_type='page'" );
		
		// Get pay page ID
		$pay_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='pay' AND post_type='page'" );
		
		// Get order received page ID
		$orderreceived_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='order-received' AND post_type='page'" );
		
		// My account page ID
		$myaccount_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='my-account' AND post_type='page'" );
		
		// Edit address page ID
		$editaddress_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='edit-address' AND post_type='page'" );
		
		// Edit view-order page ID
		$vieworder_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='view-order' AND post_type='page'" );
		
		// Edit address page ID
		$changepassword_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='change-password' AND post_type='page'" );
		
		// Edit address page ID
		$lostpassword_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='lost-password' AND post_type='page'" );
		
		// Get shop page ID
		$shop_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='shop' AND post_type='page'" );
		
		/* Update WooCommerce setting options with these ID */
		
		$success_cart_page_id = update_option ( 'woocommerce_cart_page_id', $cart_page_id );
		$success_checkout_page_id = update_option ( 'woocommerce_checkout_page_id', $checkout_page_id );
		$success_pay_page_id = update_option ( 'woocommerce_pay_page_id', $pay_page_id );
		$success_orderreceived_page_id = update_option ( 'woocommerce_thanks_page_id', $orderreceived_page_id );
		$success_myaccount_page_id = update_option ( 'woocommerce_myaccount_page_id', $myaccount_page_id );
		$success_editaddress_page_id = update_option ( 'woocommerce_edit_address_page_id', $editaddress_page_id );
		$success_vieworder_page_id = update_option ( 'woocommerce_view_order_page_id', $vieworder_page_id );
		$success_changepassword_page_id = update_option ( 'woocommerce_change_password_page_id', $changepassword_page_id );
		$success_lostpassword_page_id = update_option ( 'woocommerce_lost_password_page_id', $lostpassword_page_id );
		$success_shop_page_id = update_option ( 'woocommerce_shop_page_id', $shop_page_id );
		
		/* Fix issues on Featured products with variation */
		
		// Get term "featured"
		$terms_featured = get_term_by ( 'slug', 'featured', 'product_cat', ARRAY_A );
		
		// Get post id of the "slider" view
		$slider_view_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='slider' AND post_type='view'" );
		
		if ((isset ( $slider_view_id )) && (isset ( $terms_featured ))) {
			// Get view settings for this view
			$slider_view_settings = get_post_meta ( $slider_view_id, '_wpv_settings', TRUE );
			
			// Get "featured" term id
			$featured_term_id = $terms_featured ['term_id'];
			
			// Define featured taxonomy view for insertion
			
			$slider_view_settings ['tax_input_product_cat'] = array (
					0 => $featured_term_id 
			);
			
			// Update back
			$success_updating_view_slider = update_post_meta ( $slider_view_id, '_wpv_settings', $slider_view_settings );
		}
		
		/* Fix issues on WooCommerce color attributes not imported */
		
		// Define WooCommerce product attributes
		$wc_attribute = array (
				'attribute_label' => 'Color',
				'attribute_name' => 'color',
				'attribute_type' => 'select',
				'attribute_orderby' => 'menu_order' 
		);
		
		$success_wc_attribute_insert = $wpdb->insert ( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $wc_attribute );
		
		// Clear transient
		delete_option ( '_transient_wc_attribute_taxonomies' );
		
		// Enable Display 'Languages' as the widget title
		$bootstrap_commerce_wpml_settings = get_option ( 'icl_sitepress_settings' );
		$bootstrap_commerce_wpml_settings ['icl_widget_title_show'] = 1;
		$success_updating_lang_widget_title = update_option ( 'icl_sitepress_settings', $bootstrap_commerce_wpml_settings );
	}
}

/* WordPress default importer won't allow having two post_names in the same post type */
/* But WPML and WCML allows this in the reference site having two exact post_names in same post type but different translation */
/* After import we need to adjust the post name back to its original so the product comparison feature will work */
function bootcommerce_fix_productcomparison_afterimport() {
	global $wpdb;
	$posttable = $wpdb->posts;
	$correct_slug = 'product-comparison';
	
	// Get ID for original product comparison post
	$product_comparison_page_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_title='Product Comparison' AND post_type='page'" );
	
	// Get the translated ID in Spanish
	$translated_id = icl_object_id ( $product_comparison_page_id, 'page', false, 'es' );
	
	// wp_update_post cannot be used here since it won't allow inserting two post_names in same post type
	
	$success_updating_postname = $wpdb->query ( $wpdb->prepare ( "UPDATE $posttable SET post_name = %s WHERE ID = %d", $correct_slug, $translated_id ) );
	
	if ($success_updating_postname) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/* Function to update CRED string translation context to the correct form ID after import */
function wpv_demo_cred_forms_context_update_after_import() {
	
	// CRED plugin is activated, check if forms exists
	global $wpdb;
	
	$post_table_name = $wpdb->prefix . 'posts';
	$icl_strings_table_name = $wpdb->prefix . 'icl_strings';
	
	// Retrieve updated form IDs and the post title from the post table
	$results = $wpdb->get_results ( "SELECT ID,post_title FROM $post_table_name WHERE post_type='cred-form'", ARRAY_A );
	
	$master_context_array=array();
	
	if ($results) {
		// Forms exists, prepare data
		$updated_cred_form_id_array = array ();
		
		foreach ( $results as $key => $inner_array ) {
			$updated_cred_form_id_array [$inner_array ['post_title']] = $inner_array ['ID'];
		}
		
		// Loop through each CRED forms array and check if context exists on translation table then update
		foreach ( $updated_cred_form_id_array as $k => $v ) {
			
			// Formulate context to search
			$context_to_search = 'cred-form-' . $k . '-';
			$like = "%$context_to_search%";
			$sql_query = "SELECT DISTINCT context,id FROM $icl_strings_table_name WHERE context LIKE %s";
			
			// Get results from icl strings table
			$string_results_query = $wpdb->get_results ( $wpdb->prepare ( $sql_query, $like ), ARRAY_A );
			
			if (is_array($string_results_query)) {
				foreach ($string_results_query as $string_k=>$string_v) {
					
					// Queried context exists
					$old_context_retrieved =$string_v['context'];
					
					// Queried string ID
					$associated_string_id =$string_v['id'];
					
					// Formulate new context
					$new_context_for_updating = $context_to_search . $v;
					$master_context_array[$old_context_retrieved]=$new_context_for_updating;
					
					// Update
					$wpdb->query ( $wpdb->prepare ( "UPDATE $icl_strings_table_name SET context=%s WHERE id=%d", $new_context_for_updating, $associated_string_id ) );
				}
			}
		}
		//Loops done, update.
		update_option('wpvdemo_processed_cred_context_import',$master_context_array);
	}
}

/* Function to remove the option just_reactivated when importing multilingual sites with WPML 3.1.4+ to prevent fatal error */
function wpv_demo_remove_wpml_recently_activated_option($plugin, $network_activated) {
	
	if (wpvdemo_wpml_is_enabled()) {
		
		// Get activated WPML plugin folder
		$wpml_plugin_folder = ICL_PLUGIN_FOLDER;
		
		// Get plugin folder name of passed $plugin variable in the hook
		$activated_wpml_plugin_folder = dirname ( $plugin );
		
		if ($activated_wpml_plugin_folder == $wpml_plugin_folder) {
			
			$iclsettings = get_option ( 'icl_sitepress_settings' );
			if ($iclsettings) {
				$iclsettings ['just_reactivated'] = 0;
				update_option ( 'icl_sitepress_settings', $iclsettings );
			}
		}
	}
}

// Retrieve original translation status before refresh
function wpv_demo_original_translation_status_before_refresh() {
	global $wpdb;
	
	$post_table_name = $wpdb->prefix . 'posts';
	$icl_strings_table_name = $wpdb->prefix . 'icl_strings';
	
	// Retrieve updated form IDs and the post title from the post table
	$results = $wpdb->get_results ( "SELECT ID,post_title FROM $post_table_name WHERE post_type='cred-form'", ARRAY_A );
	
	if ($results) {
		// Forms exists, prepare data
		$updated_cred_form_id_array = array ();
		
		foreach ( $results as $key => $inner_array ) {
			$updated_cred_form_id_array [$inner_array ['post_title']] = $inner_array ['ID'];
		}
		
		// Loop through each CRED forms array and retrieve original translation status
		$original_translation_status_array = array ();
		foreach ( $updated_cred_form_id_array as $k => $v ) {
			
			// Formulate context to search
			$context_to_search = 'cred-form-' . $k . '-';
			$like = "%$context_to_search%";
			$sql_query = "SELECT id,status FROM $icl_strings_table_name WHERE context LIKE %s";
			
			// Get results from icl strings table
			$string_results_query = $wpdb->get_results ( $wpdb->prepare ( $sql_query, $like ), ARRAY_A );
			if (! (empty ( $string_results_query ))) {
				
				foreach ( $string_results_query as $k => $orig_array_info ) {
					
					$string_trans_id = $orig_array_info ['id'];
					$string_orig_status = $orig_array_info ['status'];
					
					$original_translation_status_array [$string_trans_id] = $string_orig_status;
				}
			}
		}
		return $original_translation_status_array;
	}
}
// Restore original translation status
function wpv_demo_restore_original_translation_status($original_translation_status) {
	if ((is_array ( $original_translation_status )) && (! (empty ( $original_translation_status )))) {
		
		global $wpdb;
		$icl_strings_table_name = $wpdb->prefix . 'icl_strings';
		$icl_string_translations_table_name = $wpdb->prefix . 'icl_string_translations';
		
		// Loop and update
		foreach ( $original_translation_status as $id => $translation_status_value ) {
			$wpdb->query ( $wpdb->prepare ( "UPDATE $icl_strings_table_name SET status=%d WHERE id=%d", $translation_status_value, $id ) );
			$wpdb->query ( $wpdb->prepare ( "UPDATE $icl_string_translations_table_name SET status=%d WHERE string_id=%d", $translation_status_value, $id ) );
		}
	}
}
// Special activation of Types and Views plugin for modules import
function wpvdemo_activate_types_views_modules_import($site_imported) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$site_imported = ( string ) $site_imported;
	
	// Inclusive sites are sites with modules to be imported.
	// Activate Types and Views so overall modules functionality will be rendered correctly before import
	$inclusive_sites= apply_filters('wpvdemo_special_types_views_fullactivation',array());
	
	// Don't do this in discover-wp multisite and if not an inclusive site
	if ((! (is_multisite ())) && (in_array ( $site_imported, $inclusive_sites ))) {
		
		// Get all plugins
		$all_plugins_found = get_plugins ();
		$compatible_types_views_found = wpvdemo_get_types_views_pluginlist ( $all_plugins_found );
		
		if ((is_array ( $compatible_types_views_found )) && (! (empty ( $compatible_types_views_found )))) {
			
			// Compatible version of Types and Views is found in plugins directory
			$executing_dir = plugin_dir_path ( __FILE__ );
			$plugins_directory = dirname ( dirname ( $executing_dir ) );
			
			foreach ( $compatible_types_views_found as $plugin_path => $plugin_name ) {
				
				$complete_plugin_path = $plugins_directory . '/' . $plugin_path;
				$success = activate_plugin ( $plugin_path, $redirect = '', $network_wide = false, $silent = true );
			}
		}
	}
}

// Sort Types and Views plugin from the plugin list
function wpvdemo_get_types_views_pluginlist($all_plugins) {
	$compatible_types_views = array ();
	
	// Get Types and Views embedded version running
	if ((defined ( 'WPCF_VERSION' )) && (defined ( 'WPV_VERSION' ))) {
		$types_version_embedded = WPCF_VERSION;
		$views_version_embedded = WPV_VERSION;
		if ((is_array ( $all_plugins )) && (! (empty ( $all_plugins )))) {
			
			$compatible_types_views = array ();
			foreach ( $all_plugins as $plugin_path => $plugin_details ) {
				
				$plugin_basename = basename ( $plugin_path );
				
				// This is a Views or a Types plugin
				// Check if version is compatible
				if ($plugin_basename == 'wpcf.php') {
					
					$version_in_plugins_directory = $plugin_details ['Version'];
					if ($version_in_plugins_directory == $types_version_embedded) {
						// Compatible Types plugin version
						$compatible_types_views [$plugin_path] = $plugin_details ['Name'];
					}
				} elseif ($plugin_basename == 'wp-views.php') {
					
					$version_in_plugins_directory = $plugin_details ['Version'];
					if ($version_in_plugins_directory == $views_version_embedded) {
						// Compatible Views plugin version
						$compatible_types_views [$plugin_path] = $plugin_details ['Name'];
					}
				}
			}
		}
	}
	return $compatible_types_views;
}
// Bootstrap Estate query settings for Views using property taxonomies
function bootstrap_estate_fix_properties_taxonomiesview_settings() {
	global $wpdb;
	$posttable = $wpdb->prefix . "posts";
	
	// Get ID of Sidebar – Sponsored Property View
	$sponsored_property_view_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='sidebar-sponsored-property' AND post_type='view'" );
	
	// Get ID of featured-slider View
	$featured_slider_view_id = $wpdb->get_var ( "SELECT ID FROM $posttable WHERE post_name='featured-slider' AND post_type='view'" );
	
	// Get term IDs
	$sidebar_term = get_term_by ( 'slug', 'sidebar', 'property-categories', ARRAY_A );
	
	$sidebar_term_id = '';
	$featured_term_id = '';
	
	if (isset ( $sidebar_term ['term_id'] )) {
		$sidebar_term_id = $sidebar_term ['term_id'];
	}
	
	$featured_term = get_term_by ( 'slug', 'featured', 'property-categories', ARRAY_A );
	
	if (isset ( $featured_term ['term_id'] )) {
		$featured_term_id = $featured_term ['term_id'];
	}
	
	// Sidebar sponsored view fix
	if ((isset ( $sponsored_property_view_id )) && (! (empty ( $featured_term_id ))) && (! (empty ( $sidebar_term_id )))) {
		$view_setting_sponsored = get_post_meta ( $sponsored_property_view_id, '_wpv_settings', TRUE );
		if (! (isset ( $view_setting_sponsored ['taxonomy-property-categories-attribute-url-format'] ))) {
			$view_setting_sponsored ['taxonomy-property-categories-attribute-url-format'] = array (
					'0' => 'slug' 
			);
			$success_updating_attribute_format = update_post_meta ( $sponsored_property_view_id, '_wpv_settings', $view_setting_sponsored );
		}
		if (! (isset ( $view_setting_sponsored ['tax_input_property-categories'] ))) {
			$view_setting_sponsored ['tax_input_property-categories'] = array (
					'0' => $sidebar_term_id 
			);
			$success_updating_term_id = update_post_meta ( $sponsored_property_view_id, '_wpv_settings', $view_setting_sponsored );
		}
	}
	// Featured view fix
	if ((isset ( $featured_slider_view_id )) && (! (empty ( $featured_term_id ))) && (! (empty ( $sidebar_term_id )))) {
		$view_setting_featured = get_post_meta ( $featured_slider_view_id, '_wpv_settings', TRUE );
		if (! (isset ( $view_setting_featured ['taxonomy-property-categories-attribute-url-format'] ))) {
			$view_setting_featured ['taxonomy-property-categories-attribute-url-format'] = array (
					'0' => 'slug' 
			);
			$success_updating_featured_att_format = update_post_meta ( $featured_slider_view_id, '_wpv_settings', $view_setting_featured );
		}
		if (! (isset ( $view_setting_featured ['tax_input_property-categories'] ))) {
			$view_setting_featured ['tax_input_property-categories'] = array (
					'0' => $featured_term_id 
			);
			$success_updating_term_id_featured = update_post_meta ( $featured_slider_view_id, '_wpv_settings', $view_setting_featured );
		}
	}
}
/**
 * Framework installer layouts import
 */
function framework_installer_layouts_import($baseurl, $settings) {
	$file = $baseurl . '/layouts_archives_export.xml';
	$file_headers_layouts = @get_headers ( $file );
	
	$file_main = $baseurl . '/theme_dd_layouts.zip';
	$file_headers_main = @get_headers ( $file_main );
	
	// Let's import the main Layouts
	// wpvdemo_download_layouts_zip_from_ref
	
	if (strpos ( $file_headers_main [0], '200 OK' )) {
		// Exists
		// Let's download the zip and process
		wpvdemo_download_layouts_zip_from_ref_and_import ( $file_main );
	}
	
	// Check if layouts archive export XML exist, if yes then import
	if (strpos ( $file_headers_layouts [0], '200 OK' )) {
		// Exists
		// Parse remote XML
		$data = wpv_remote_xml_get ( $file );
		if (! ($data)) {
			return false;
		}
		
		$xml = simplexml_load_string ( $data );
		$import_data = wpv_admin_import_export_simplexml2array ( $xml );
		
		// Loop through the settings convert the Layouts slugs back to actual Layout ids in imported site
		foreach ( $import_data as $key => $layout_slug ) {
			
			// Remove incompatible data format
			unset ( $import_data [$key] );
			
			// Get ID equivalent of this Layout slug
			$layout_id = wpvdemo_get_layoutid_by_slug ( $layout_slug );
			
			// Re-assigned
			if (! (empty ( $layout_id ))) {
				$import_data [$key] = $layout_id;
			}
		}
		
		// Done looping, update this setting to the database
		// Delete any "ddlayouts_options" option that exist
		delete_option ( 'ddlayouts_options' );
		
		// Update option
		update_option ( 'ddlayouts_options', $import_data );
	}
	return true;
}
/**
 * Aux function to convert given Layout slugs to ids
 */
function wpvdemo_get_layoutid_by_slug($layouts_slug) {
	global $wpdb;
	$posts_table = $wpdb->prefix . "posts";
	$layouts_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT ID FROM $posts_table WHERE  post_name = %s AND post_type ='dd_layouts'", $layouts_slug ) );
	
	if (! (empty ( $layouts_id ))) {
		return $layouts_id;
	} else {
		return FALSE;
	}
}

// Returns TRUE if user is using WooCommmerce Views version that supports importing
function using_updated_wc_views_import() {
	global $Class_WooCommerce_Views;
	
	if (is_object ( $Class_WooCommerce_Views )) {
		if (method_exists ( $Class_WooCommerce_Views, 'wcviews_import_settings' )) {
			// Import method exist, import data
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

function bootstrap_vanilla_update_home_url() {
	
	global $wpdb;
	@$home_url_id_vanilla= $wpdb->get_var("select ID from $wpdb->posts where post_name='home-2'");
	$site_url_imported_vanilla = get_bloginfo('url');
	$success_meta_update=update_post_meta($home_url_id_vanilla, '_menu_item_url', $site_url_imported_vanilla);
	
}
/**
 * Downloads Layouts zip.
 *
 * @param type $url        	
 * @return boolean
 */
function wpvdemo_download_layouts_zip_from_ref_and_import($url) {
	global $wpddlayout, $wpddlayout_theme, $frameworkinstaller;
	
	if ((is_object ( $wpddlayout )) && (is_object ( $wpddlayout_theme ))) {
		// Layouts plugin activated
		$uploads_directory = wp_upload_dir ();
		$theme_layouts_dir = $uploads_directory ['path'];
		$theme_layouts_dir_path = $theme_layouts_dir . '/theme-dd-layouts';
		
		if (! is_writeable ( $theme_layouts_dir )) {
			wpvdemo_requirements_themes_writeable_error_message ();
			return false;
		}
		
		if (file_exists ( $theme_layouts_dir_path ) && is_dir ( $theme_layouts_dir_path )) {
			
			// Theme dd layouts directory exists!
			/**
			 * To ensure we are only importing Layouts that belongs to the site being imported.
			 * Let's clear the theme-dd-layouts of all existing layout files there before we actually unzipped the new import
			 */
			
			// Get all file names
			$files = glob ( "$theme_layouts_dir_path/*" );
			if ((is_array ( $files )) && (! (empty ( $files )))) {
				// OK we have files to delete, proceed.
				foreach ( $files as $file_to_delete ) {
					// Iterate files
					if (is_file ( $file_to_delete )) {
						// Delete file
						unlink ( $file_to_delete );
					}
				}
			}
		}
		
		// Define the URL location of the theme layous dir zip file in reference site
		$file = ( string ) $url;
		
		// Define download path to local
		$new_file = $theme_layouts_dir . '/' . basename ( $url );
		
		// File headers
		$file_headers_theme_zip = @get_headers ( $file );
		
		// Download layouts zip file
		// Don't open if zip does not exist
		
		if (strpos ( $file_headers_theme_zip [0], '200 OK' )) {
			
			// Set context
			$context = stream_context_create ( array (
					'http' => array (
							'timeout' => 1200 
					) 
			) );
			
			$success = file_put_contents ( $new_file, fopen ( $file, 'r', false, $context ) );
			
			if ($success) {
				// Unzip
				
				$zip = new ZipArchive ();
				$res = $zip->open ( $new_file );
				if ($res === TRUE) {
					$zip->extractTo ( $theme_layouts_dir );
					$zip->close ();
					unlink ( $new_file );
				} else {
					echo __ ( 'Unable to open zip file', 'wpcf' ) . '<br />';
				}
				
				// At this stage, its extracted, let's check if it exists
				if (file_exists ( $theme_layouts_dir_path ) && is_dir ( $theme_layouts_dir_path )) {
					// Files exists
					if (method_exists ( $frameworkinstaller, 'wpvdemo_identify_layouts_posttype_and_import' )) {
						
						// We are now ready to import Layouts, do it here.
						$frameworkinstaller->wpvdemo_identify_layouts_posttype_and_import ( $theme_layouts_dir_path, $file );
					}
				}
			}
			return true;
		} else {
			
			echo __ ( 'Unable to fetch zip file', 'wpvdemo' ) . '<br />';
		}
		// END
	}
	return false;
}