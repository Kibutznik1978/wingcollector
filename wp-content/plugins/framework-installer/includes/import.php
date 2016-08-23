<?php
if (defined('WPVDEMO_DEBUG')) {	
	$wpvdemo_debug_mode=WPVDEMO_DEBUG;
	if (!($wpvdemo_debug_mode)) {		
		error_reporting(0);
	}	
}
/*
 * Import functions.
 */

$wpvdemo_import = null;

/**
 * Imports site.
 * 
 * @global type $wpvdemo_import
 * @global type $wpdb
 * @param type $site_id
 * @param type $step 
 */
function wpvdemo_import($site_id, $step,$versions='') {
	
	global $frameworkinstaller;
	do_action('wpvdemo_import_refsite_versions');
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';	
    if (!wpvdemo_is_safe_mode()) {
        set_time_limit(0);
    }

    if ($step == 1 && !wpvdemo_check_if_blank_site()) {
        wpvdemo_check_if_blank_site_message();
        die();
    }
	update_option('wpcf_strings_translation_initialized',1);
    $step = intval($step);
    $sites = wpvdemo_admin_get_sites_index(false);
    if (empty($sites->site)) {
        echo wpvdemo_error_message('data', true);
        die();
    }
    $settings = false;
    foreach ($sites->site as $check_site) {
        if ($check_site->ID == $site_id) {
            $settings = $check_site;
        }
    }

    if (empty($settings) || !wpvdemo_admin_check_required_site_settings($settings)) {
        echo wpvdemo_error_message('data', true);
        die();
    }

    /** At this point $settings exist and defined */
    /** Run one time only */
    if (is_object($frameworkinstaller)) {
    	if (method_exists($frameworkinstaller,'is_discoverwp')) {
    		$is_discover= $frameworkinstaller->is_discoverwp();
    		if ($is_discover) {
    			
    			//In Discover WP, let's reset any sitepress settings before the import
    			//Let's do this only for non-multilingual imports
    			$icl_temp_settings_deleted=get_option('icl_temp_settings_deleted');
    			if ((isset($settings->download_url)) && (!($icl_temp_settings_deleted))) {
    				//Downloads URL exist and temp settings not yet defined
    				$download_url_temp=$settings->download_url;
    				if (!(empty($download_url_temp))) {
    					$download_url_temp =(string)$download_url_temp;
    					$post_download_url=$download_url_temp.'/posts.xml';
    					if (function_exists('wpvdemo_has_wpml_implementation')) {
    						$has_wpml= wpvdemo_has_wpml_implementation($post_download_url,false);
    						if (!($has_wpml)) {
    							//Non multilingual imports
    							//To avoid corrupting the import, delete this.
    							delete_option('icl_sitepress_settings');
    							update_option('icl_temp_settings_deleted','yes');
    						} else {
    							//Multilingual imports
    							//Check if we are using WPML 3.2
    							$wpml_three_two=$frameworkinstaller->wpvdemo_if_using_wpml_three_two();
    							if ($wpml_three_two) {
    								//Using WPML 3.2 +, delete this setting before importing.
    								$wpvdemo_sitepress_settings_set= get_option('wpvdemo_sitepress_settings_set');
    								if (!($wpvdemo_sitepress_settings_set)) {
    									delete_option('icl_sitepress_settings');
    								}    								    								
    							}
    						}
    						
    					}
    				}
    			}

    		}
    	}
    }   
    
    $import_settings = (object) array(
                'fetch_attachments' => true,
                'download_theme' => true,
                'activate_plugins' => true,
    );
    $import_settings = apply_filters('wpvdemo_import_settings', $import_settings);

    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
    require_once WPV_PATH_EMBEDDED . '/inc/wpv-import-export-embedded.php';

    do_action('wpvdemo_import_before_step_' . $step, $settings);
    
    //WP 3.5.2 &WP3.6 compatibility on testing reference sites locally
    if (defined('WPVDEMO_LOCALHOST_MODE')) {
    		
    	add_filter( 'http_request_args', 'wpdemo_localhost_reference_site' );
    		
    }    
  
    //Add hook for auto-activating Types and Views plugin for sites with modules import
    add_action('wpv_demo_activate_types_views_after_import','wpvdemo_activate_types_views_modules_import', 11, 1);
    
	//Require post-import hooks processing
    require_once WPVDEMO_ABSPATH . '/includes/post-import-hooks.php';
    
    switch ($step) {
        case 1:     

            update_option('wpvdemo-post-count', 0);
            update_option('wpvdemo-post-total', 0);

            // Delete default posts
            wp_delete_post(1, true);
            wp_delete_post(2, true);
            // Import Types and initialize post types!
            
            $success = wpvdemo_import_types($settings->download_url);            
            if (!$success) {
                echo wpvdemo_error_message('importing_types', true);
                die();
            }
            
            // Activate plugins
            ob_start();  
            $original_plugin_lists=wpvdemo_format_display_plugins($settings->plugins->plugin,false);

            
            /** Framework installer 1.8.2: We allow non-multilingual import of a multilingual site in Discover-WP */
			/** Let's pass version regardless if its standalone or Discover-WP */
            $the_final_plugins=wpvdemo_check_if_wpml_will_be_skipped($original_plugin_lists,$settings->download_url,false,false,$versions); 
            $the_final_plugins=$the_final_plugins['activate'];
            if (!empty($the_final_plugins)) {
                $plugins = array();
                foreach ($the_final_plugins as $plugin) {
                    // Skip Views and Types
                    if (!wpvdemo_is_allowed_plugin((string) $plugin->file)) {
                        continue;
                    }
                  
                    $plugins['plugin_file_stream'][]=(string) $plugin->file;
                    $plugins['plugin_name_stream'][]=(string) $plugin->title;
                }

                //Get site shortname for additional options
                $activated_plugins_siteshortname=$settings->shortname;
                
                //Don't activate plugins that are already network activated in Discover-wp multisite                  
                $inclusive_sites_for_check= apply_filters('wpvdemo_sites_already_network_activated',array());
                if ((in_array($activated_plugins_siteshortname, $inclusive_sites_for_check)) && (is_multisite())) {	
                	$site_complete_url= get_site_url();
                	
                	// get host name from URL
                	preg_match('@^(?:http://)?([^/]+)@i',$site_complete_url, $preg_matches);
                	$host_match = $preg_matches[1];
                	
                	// get last two segments of host name
                	preg_match('/[^.]+\.[^.]+$/', $host_match, $preg_matches);                	
                	
                	$site_host=$preg_matches[0];
                	$already_network_activated=apply_filters('wpvdemo_discoversite_already_network_activated',array());                	
                	if (in_array($site_host, $already_network_activated)) {
                		
                	    //Site is either discover-wp.com or discover-wp.dev
                	    //Exclude CRED and Types in plugins array
                	    
                		foreach ($plugins['plugin_file_stream'] as $filestream_key=>$filestream_value) {
                			
                			/** Framework installer 1.8.2 + WPML plugins are no longer network activated on Discover-WP */
                			$already_network_activated_plugins= apply_filters('wpvdemo_plugins_already_network_activated',array(),'plugin_path');
                			
                			if (in_array($filestream_value, $already_network_activated_plugins)) {
                				//Remove from activation
                				unset($plugins['plugin_file_stream'][$filestream_key]);                				
                				
                			}          				            			
                		}
                		
                		foreach ($plugins['plugin_name_stream'] as $namestream_key=>$namestream_value) {

                			$already_network_activated_plugin_name= apply_filters('wpvdemo_plugins_already_network_activated',array(),'plugin_names');                			
                			if (in_array($namestream_value, $already_network_activated_plugin_name)) {
                				unset($plugins['plugin_name_stream'][$namestream_key]);
                			
                			}             			
                		}	
                	}
                	
                } 
                add_action( 'activated_plugin', 'wpv_demo_remove_wpml_recently_activated_option',1000,2);                
                $errors = wpvdemo_activate_plugins($plugins,$activated_plugins_siteshortname);
                if (!empty($errors)) {
                    ob_end_clean();
                    echo '<div class="message error"><p>';
                    foreach ($errors as $plugin => $error) {
                        echo sprintf(wpvdemo_error_message('plugin_activation'),
                                $plugin, $error) . '<br />';
                    }
                    echo '</p></div>';
                    die();
                }
            }                       
            ob_end_clean();

            break;

        case 2:
        	        	
        	// Import Posts
            // Allow updating post count
            session_write_close();
            ob_start();
            //Disable Kses filters as well, issues on importing Classifieds WooCommerce custom functionality
            if (function_exists('kses_remove_filters')) {
            	kses_remove_filters();
            }            
            wpvdemo_import_posts($settings, $import_settings);
            ob_end_clean();   
                     
            break;
            

        case 3:
            // Import Views
            
            // EMERSON fix: Disable filters to correctly import Views containing HTML tags in post_content        	
        	if (function_exists('kses_remove_filters')) {
        	kses_remove_filters();
        	}
        	
            $success = wpvdemo_import_views($settings->download_url, $settings);
            if (!($success)) {
                echo sprintf(wpvdemo_error_message('importing_views', true));
                die();
            }
            // Update post relationships
            global $wpvdemo_import;
            if (!empty($wpvdemo_import->processed_posts)) {
                foreach ($wpvdemo_import->processed_posts as $old_id => $new_id) {
                    $post = get_post($new_id);
                    if (!empty($post)) {
                        $update_posts = get_posts('meta_key=_wpcf_belongs_'
                                . $post->post_type . '_id&meta_value='
                                . $old_id . '&numberposts=-1&post_type=any');
                        if (!empty($update_posts)) {
                            foreach ($update_posts as $update_post) {
                                update_post_meta($update_post->ID,
                                        '_wpcf_belongs_'
                                        . $post->post_type . '_id', $new_id);
                            }
                        }
                    }
                }
            }
            
            break;

        case 4:
            // Import CRED forms
            
            //EMERSON: Disable Filters to allow important of HTML tags in CRED Form Content
        	if (function_exists('kses_remove_filters')) {
        		kses_remove_filters();
        	}
            $success = wpvdemo_import_cred($settings->download_url, $settings);
            
            break;
            
        case 5:
           	// Import Types Access
           	
           	$success = wpvdemo_import_access($settings->download_url, $settings);
            
           	break;

        case 6:
            // Import WPML settings and strings
            
            $success = wpvdemo_import_wpml($settings->download_url, $settings);
           
            break;
        // START :Import Inline documentation and settings
        case 7:        	
        	
        	$success = inline_doc_content_import($settings->download_url, $settings);
        	
        	break;       	
        // END :Import Inline documentation and settings      
        case 8:
        	
        	// Download theme
        	$template = basename($settings->theme_url, '.zip');
        	$stylesheet = basename($settings->theme_url, '.zip');
        	$success = true;
        	
        	if (is_multisite()) {
        		//Re-define
        		$import_settings->download_theme=true;
        	}
        	
        	if ($import_settings->download_theme) {
        		$success = wpvdemo_download_theme($settings->theme_url);
        		if (!$success) {
        			echo sprintf(wpvdemo_error_message('download_theme', true),
        					$settings->theme_url);
        			die();
        		} else if ($success && !empty($settings->theme_parent_url)) {
        			$success = wpvdemo_download_theme($settings->theme_parent_url);
        			if (!$success) {
        				echo sprintf(wpvdemo_error_message('download_theme_parent',
        						true), $settings->theme_parent_url);
        				die();
        			}
        			$template = basename($settings->theme_parent_url, '.zip');
        		}
        	} else {
        		if (!empty($settings->theme_parent_url)) {
        			$template = basename($settings->theme_parent_url, '.zip');
        		}
        	}
        	if ($success) {
        		switch_theme($template, $stylesheet);
        	}      	

        	// Set homepage and posts page settings
        	if (!empty($settings->show_on_front)) {
        		update_option('show_on_front', (string) $settings->show_on_front);
        		if ((string) $settings->show_on_front == 'page') {
        			global $wpdb;
        			if (!empty($settings->page_on_front)) {
        			$page = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='page'",
        				(string) $settings->page_on_front));
        				if ($page) {
        				update_option('page_on_front', $page);
        				}
        			}
        			if (!empty($settings->page_for_posts)) {
        			$page = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='page'",
        					(string) $settings->page_for_posts));
        					if ($page) {
        							update_option('page_for_posts', $page);
        					}
        			}
        		}
        	}         	

            break;        	
        
        case 9:
        	
        	if (function_exists('kses_remove_filters')) {
        		kses_remove_filters();
        	}
        	//START: Module manager import
        	$success = module_manager_views_demo_import($settings->download_url, $settings);        	 
        	break;
        	// END : Module manager import
        	
        case 10:
        /** Layouts import */
             
            if (function_exists('kses_remove_filters')) {
                kses_remove_filters();
            }
            
            //START: Layouts import
            $success = framework_installer_layouts_import($settings->download_url, $settings);
            break;
            // END : Layouts import
            
        case 11:
                 
            /** Finalizing the rest of the settings */  
        	//Bootmag site, unique import settings
        	 
        	$bootmagsite_title=$settings->shortname;
        	$bootmagsite_title=(string)$bootmagsite_title;
        	
        	/** Call API for implemented sites */
        	$bootstrap_sites_shortname_array =apply_filters('wpdemo_old_bootstrap_sites',array());
        	
        	/**START OF MAJOR REVISION */
        	if (array_key_exists($bootmagsite_title,$bootstrap_sites_shortname_array)) {
        		
        		/** THIS BLOCK DEALS WITH THE ORIGINAL BOOTSTRAP SITES */        		
        		//Import options framework
        		if (!empty($settings->optionsframework)) {
        			 
        			$options_framework_array = array();
        			foreach ($settings->optionsframework as $mods) {
        				foreach ($mods as $mod_name => $mod_value) {
        					if ((string) $mod_name=='id') {        	
        						$options_framework_array[(string) $mod_name] = (string)$mod_value;
        	
        					} else {        	
        						$options_framework_array[(string) $mod_name] = $mod_value;
        	
        					}
        				}
        			}
        			 
        			//Get known options
        			$bootmag_known_options_imported=$options_framework_array['knownoptions'];
        			 
        			//Rename to the correct keys after import
        			$new_options_framework_array=array();
        			 
        			foreach ($bootmag_known_options_imported as $key=>$value) {
        				 
        				$new_options_framework_array[] =(string)$value;
        			}
        			 
        			//Delete previous options
        			unset($options_framework_array['knownoptions']);
        				
        			//Add new known options with correct keys
        			$options_framework_array['knownoptions']=$new_options_framework_array;
        			 
        			//Update theme option settings in database
        			update_option('optionsframework', $options_framework_array);
        			
        		}
        		 
        		//Import Bootstrap theme settings
        	
        		//Get Bootstrap template        		
        		$bootmag_optionsframework_imported= get_option('optionsframework');
        		$bootmag_stylesheet_template=$bootmag_optionsframework_imported['id'];        		
        		
        		if (!empty($settings->$bootstrap_sites_shortname_array[$bootmagsite_title]['settings_anchor'])) {
        			 
        			//This line handles all old bootstrap sites        			
        			$theme_anchor=$bootstrap_sites_shortname_array[$bootmagsite_title]['settings_anchor'];
        			$optionname_theme=$bootstrap_sites_shortname_array[$bootmagsite_title]['optionname'];
        			$bootmag_import_data=$settings->$theme_anchor;
        			$bootmag_theme_options_settings=array();
        			 
        			//Convert Bootmag settings to array
        			if (function_exists('bootmag_xml2array')) {
        				$bootmag_theme_options_settings=bootmag_xml2array($bootmag_import_data);
        				$bootmag_theme_options_settings_final=$bootmag_theme_options_settings[$theme_anchor];
        		
        			}
        			        			 
        			update_option($optionname_theme, $bootmag_theme_options_settings_final);
        			
        			
        			//Removing of unused classified sites widget to prevent template distortion after import
        			//Only applicable to some sites
        			if (isset($bootstrap_sites_shortname_array[$bootmagsite_title]['update_sites_widget'])) {
        				$update_sites_widget=$bootstrap_sites_shortname_array[$bootmagsite_title]['update_sites_widget'];
        				if ($update_sites_widget) {
        					update_options_classified_widgets();
        				}
        			} 			
        		}	
        	}
        		//Import WooCommerce Settings for Classifieds Site
        		//Only applicable to some sites
        		$success_import_classifieds_woocommerce=wpvdemo_import_classifieds_woocommerce($settings->download_url, $settings);
        			 
        		if (!$success_import_classifieds_woocommerce) {
        			echo wpvdemo_error_message('importing_classifieds_woocommerce', true);
        			die();
        		}

        		//Import WooCommerce Views settings for Classifieds site
        		$success_import_classifieds_woocommerce_views=wpvdemo_import_woocommerce_views($settings->download_url, $settings);

        		//Import user roles for classifieds site
        		global $wpdb;
        		$define_table_prefix=$wpdb->prefix;
        			 
        		$success_import_classified_user_roles=wpvdemo_import_classifieds_user_roles($settings->download_url, $define_table_prefix,$settings);
        		if (!$success_import_classified_user_roles) {
        			echo wpvdemo_error_message('importing_classifieds_user_roles', true);
        			die();
        		}

        		//Configure CRED notification settings for CRED commerce
        		wpvdemo_config_notification_classifieds_site($settings->download_url, $define_table_prefix,$settings);

        		//Import CRED custom fields for Classifieds site

        		$import_cred_cf=apply_filters('wpvdemo_import_cred_custom_fields',false,$bootmagsite_title);
        		if ($import_cred_cf) {
	        		$check_if_credcustomfields_exist=get_option('__CRED_CUSTOM_FIELDS');
	        		if (!($check_if_credcustomfields_exist)) {
	        			 
	        			//options does not exist, import
	        			$success_classifieds_credcustomfields=wpvdemo_import_classifieds_credcustomfields($settings->download_url, $settings);
	        			if (!$success_classifieds_credcustomfields) {
	        				echo wpvdemo_error_message('importing_cred_custom_fields_classifieds', true);
	        				die();
	        			}
	        		}
        		} 
        		if (array_key_exists($bootmagsite_title,$bootstrap_sites_shortname_array)) {      						
        			if ((isset($bootstrap_sites_shortname_array[$bootmagsite_title]['header_menu_anchor'])) &&
        			   (isset($bootstrap_sites_shortname_array[$bootmagsite_title]['header_menu_option_name'])))
        			 {

	        			//Fixed Bootstrap Classifieds no dropdown issue on navigation
	        			$header_menu_anchor=$bootstrap_sites_shortname_array[$bootmagsite_title]['header_menu_anchor'];
	        			$header_menu_option_name=$bootstrap_sites_shortname_array[$bootmagsite_title]['header_menu_option_name'];
	        			$option_name_db_name='theme_mods_'.$header_menu_option_name;
	        			$nav_term_information=get_term_by('name', $header_menu_anchor, 'nav_menu');
	        			 
	        			//Get term_id of main navigation
	        			$term_id_nav = $nav_term_information->term_id;
	        			$theme_mods_bootstrap_classifieds = array( 0=>false, 'nav_menu_locations'=>array( 'header-menu'=>$term_id_nav ) );
	        			update_option( $option_name_db_name, $theme_mods_bootstrap_classifieds );
        			
        			}     			 
        		}	

        		//Don't run wizard on WooCommerce Views 2.1 after import
        		$wpvdemo_turnoff_wcviews_wizard=apply_filters('wpvdemo_turnoff_wcviews_wizard',false,$bootmagsite_title);
        		if ($wpvdemo_turnoff_wcviews_wizard) {
        			update_option( 'wc_views_user_completed_wizard', 'yes');
        		}

        		//Turn off any unneeded wc admin notices        			
        		wpvdemo_after_import_turnoffwc_notices($settings);      				

        		//Manual fix for Views taxonomies (current limitation in auto-export in reference site)
        		$wpvdemo_turnoff_wc_adminnotices= apply_filters('wpvdemo_manual_fix_views_taxonomies',false,$bootmagsite_title);
        		if ($wpvdemo_turnoff_wc_adminnotices) {
        			bootstrap_estate_fix_properties_taxonomiesview_settings();
        		}  

        		//Manual update of home URL if needed
        		$wpvdemo_manual_update_home_url= apply_filters('wpvdemo_manual_update_home_url',false,$bootmagsite_title);
        		if ($wpvdemo_manual_update_home_url) {
        			bootstrap_vanilla_update_home_url();
        		} 
        			
        		//Manual fix of image URLs
        		$wpvdemo_manual_fix_of_image_urls= apply_filters('wpvdemo_manual_fix_of_image_urls',false,$bootmagsite_title);
        		if ($wpvdemo_manual_fix_of_image_urls) {
        			$site_url_imported_vanilla = get_bloginfo('url');
        			global $frameworkinstaller;
        			$is_discoverwp_import= $frameworkinstaller->is_discoverwp();
        			if (!($is_discoverwp_import)) {
        				fix_image_urls_bootstrap_vanilla_standalone($site_url_imported_vanilla);
        			}        				
        		}
        		        			
        		//Whether the site has empty sidebar widgets 
        		$wpvdemo_manual_empty_sidebar_widgets= apply_filters('wpvdemo_manual_empty_sidebar_widgets',false,$bootmagsite_title);
        		if ($wpvdemo_manual_empty_sidebar_widgets) {
        			wpvdemo_old_bootstrap_clear_all_sidebar_widgets();
        		}
        			
        		//For multilingual ecommerce, whether to auto-add header menu
        		$wpvdemo_manual_autoaddheadermenu= apply_filters('wpvdemo_manual_autoaddheadermenu',false,$bootmagsite_title);
        		if ($wpvdemo_manual_autoaddheadermenu) {
        			wpvdemo_auto_addheadermenu();
        		}

        		//Whether to run special configuration of WooCommerce pages after import
        		//Import and configure BootCommerce WooCommerce setting pages
        		setup_woocommerce_setting_pages_bootcommerce($settings);

        		//Set permalinks for bootmag site        		
        		$legacy_sites=apply_filters('wpvdemo_legacy_sites',false,$bootmagsite_title);

        		if (!($legacy_sites)) {
	        		global $wp_rewrite;
	        		require_once(ABSPATH . 'wp-admin/includes/misc.php');
	        		require_once(ABSPATH . 'wp-admin/includes/file.php');
	        	
	        		// Prepare WordPress Rewrite object in case it hasn't been initialized yet
	        		if (empty($wp_rewrite) || !($wp_rewrite instanceof WP_Rewrite))
	        		{
	        			$wp_rewrite = new WP_Rewrite();
	        		}
	        	
	        		// Define default permalink structure
	        		$permalink_structure = '/%year%/%monthnum%/%day%/%postname%/';
	        		
	        		//Filter Permalink structure for special sites
	        		$permalink_structure = apply_filters('wpvdemo_filter_permalink_structure_orig_bootstrap',$permalink_structure,$bootmagsite_title);
	        		$wp_rewrite->set_permalink_structure($permalink_structure);
	        	
	        		// Recreate rewrite rules
	        		$wp_rewrite->flush_rules();
        		}        		 

        		if (array_key_exists($bootmagsite_title,$bootstrap_sites_shortname_array)) {
	        		//Import default grid XML for updated Bootstrap theme
	        		wpbootstrap_default_grid_xml_import();
        		}
	
                /**START: THIS BLOCK DEALS WITH NON-BOOTSTRAP OLD SITES */
        		if (!(array_key_exists($bootmagsite_title,$bootstrap_sites_shortname_array))) {
	        		//Not a bootmag site, normal theme option settings import
	        		$template = basename($settings->theme_url, '.zip');
	        		$stylesheet = basename($settings->theme_url, '.zip');
	        		
	        		// Set theme mods
	        		if (!empty($settings->theme_mods)) {
	        			$update_mods = array();
	        			foreach ($settings->theme_mods as $mods) {
	        				foreach ($mods as $mod_name => $mod_value) {
	        					$mod_value = wpvdemo_convert_url((string) $mod_value,$settings);
	        					$update_mods[(string) $mod_name] = $mod_value;
	        				}
	        			}
	        			update_option('theme_mods_' . $stylesheet, $update_mods);
	        		}
	        	
	        	
	        		// Set menus
	        		if (!empty($settings->menus)) {
	        			$menus = array();
	        			foreach ($settings->menus as $position => $menu) {
	        				foreach ($menu as $menu_position => $menu_name) {
	        					$menu_obj = wp_get_nav_menu_object((string) $menu_name);
	        					if (!empty($menu_obj->term_id)) {
	        						$menus[(string) $menu_position] = $menu_obj->term_id;
	        					} else {
	        						$menus[(string) $menu_position] = 0;
	        					}
	        				}
	        			}
	        			set_theme_mod('nav_menu_locations', $menus);
	        		}
    			}
        		/**END: THIS BLOCK DEALS WITH NON-BOOTSTRAP OLD SITES */
        	     
        	// Set title and tagline
        	
        	global $WPML_String_Translation;
        	if ((is_object($WPML_String_Translation)) && (isset($WPML_String_Translation))) {
        			 
        		remove_filter('pre_update_option_blogname', array($WPML_String_Translation, 'pre_update_option_blogname'), 5, 2);
        		remove_filter('pre_update_option_blogdescription', array($WPML_String_Translation, 'pre_update_option_blogdescription'), 5, 2);
        	}
        	        	
        	update_option('blogname', (string) $settings->title);
        	update_option('blogdescription',
        	wpvdemo_convert_url((string) $settings->tagline, $settings));
        	
            // Import widgets
            if (!empty($settings->sidebars_widgets)) {
                
            	wpvdemo_import_widgets($settings->sidebars_widgets);

            }
            $demo_settings = array();
            $demo_settings['ID'] = (string) $settings->ID;
            $demo_settings['title'] = (string) $settings->title;
            $demo_settings['tutorial_title'] = (string) $settings->tutorial_title;
            $demo_settings['tutorial_url'] = (string) $settings->tutorial_url;
            $demo_settings['installed'] = 1;
            update_option('wpvdemo', $demo_settings);            

            //Fix product comparison slug for BootCommerce site
            $manual_prod_comparison_fix=apply_filters('wpvdemo_manual_fix_product_comparison',false,$bootmagsite_title);
            
            if ($manual_prod_comparison_fix) {
            	/*Fix product comparison slug*/
            	//Execute only when downloading the WPML version of the site
            	if (defined('ICL_SITEPRESS_VERSION')) {
            		$results_updating_postslug=bootcommerce_fix_productcomparison_afterimport();
            	}
            	
            	//Refresh permalinks after import
            	global $wp_rewrite;
            	$wp_rewrite->flush_rules(false);
            }
            
            //Indicate import is done
            update_option( 'wpv_import_is_done', 'yes');            
                       
            //Activate Types and Views plugin for some sites
            do_action('wpv_demo_activate_types_views_after_import',$settings->shortname);
            remove_action('wpv_demo_activate_types_views_after_import','wpvdemo_activate_types_views_modules_import',99,1);
            
            //delete_option('wpvdemo_check_if_blank');
            echo '<br /><strong>' . __('Done!', 'wpvdemo') . '</strong> <span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span><br /><br />';
            if (function_exists('wp_get_theme')) {
                $theme = wp_get_theme();
                $theme_name = $theme->Name;
            } else {
                $theme_name = get_current_theme();
            }
            printf(__("The reference site was successfully imported. We've activated the theme: %s. This test site should look the same as our reference site.",
                            'wpvdemo'), $theme_name);
            echo '<br /><br />';
            $links = array();
            
            if (!defined('WPVLIVE_VERSION')) {
            	//Standalone import mode
            	$links[] = sprintf(__("%sProceed to the WordPress admin%s", 'wpvdemo'),
            			'<a class="done_import_links" href="' . admin_url() . '" title="'
            			. 'Go to the Dashboard' . '">', '</a>');            	
            }
            
            $is_using_bedrock=wpvdemo_is_using_bedrock_boilerplate_framework();  
            //Defaults to get_site_url
            $frontend_site_url=get_site_url();
            if ($is_using_bedrock) {
            	//When using bedrock this is overriden with WP_HOME
            	//Use this value when set and defined
            	if ( defined('WP_HOME') ) {
            		$wp_home=WP_HOME;
            		if (!(empty($wp_home))) {
            			$frontend_site_url=$wp_home;
            		}
            	}
            }
            $links[] = sprintf(__("%sVisit the site's front-end%s", 'wpvdemo'),
                    '<a target="_blank" class="done_import_links" href="' . $frontend_site_url . '" title="'
                    . get_bloginfo('name') . '">', '</a>');
            if (!empty($settings->tutorial_title) && !empty($settings->tutorial_url)) {
            	//Not empty, let's checked if its set
            	$tutorial_title_after_import= $settings->tutorial_title;
            	$tutorial_url_after_import= $settings->tutorial_url;            	
            	
            	/** Allow tutorial URL to be filtered. */
            	/** Some application includes Google analytics arguments to be added to tutorial URL if pointing to wp-types.com */            	
            	$settings->tutorial_url= apply_filters('wpvdemo_filter_tutorial_url',$tutorial_url_after_import,'post-setup-box','view-site-tutorial');
            	$tutorial_title_after_import= (string)$tutorial_title_after_import;
            	$tutorial_url_after_import =(string)$tutorial_url_after_import;
            	if (('#' == $tutorial_title_after_import) || ('#' == $tutorial_url_after_import)) {
            		//Don't show tutorial links
            	} else { 
                $links[] = sprintf(__('%sView site tutorial%s', 'wpvdemo'),
                        '<a target="_blank" class="done_import_links" href="' . (string) $settings->tutorial_url
                        . '" target="_blank" title="'
                        . (string) $settings->tutorial_title . '">',
                        '</a>');
            	}
            }
            $links = apply_filters('wpvdemo_complete_links', $links);            
            if (isset($settings->download_url)) {
            	$download_url=$settings->download_url;
            	if (!(empty($download_url))) {
            		$download_url =(string)$download_url;
            		$post_download_url_temp=$download_url.'/posts.xml';
            		do_action('wpv_demo_import_finishing',$post_download_url_temp);
            		delete_option('wpcf_strings_translation_initialized');
            	}
            }
            echo implode(' ', $links);
            echo '<script type="text/javascript"></script>';
            break;

        default:
            break;
    }

    if ($step < 11) {
        wpvdemo_import_next_step_js($site_id, $step);
    }

    do_action('wpvdemo_import_after_step_' . $step, $settings);
}

//Add auxiliary import functions here needed by the main import function
require_once WPVDEMO_ABSPATH . '/includes/main_functions/import-functions.php';

//Add functions hooked by post import hooks here
require_once WPVDEMO_ABSPATH . '/includes/main_functions/post-import-functions.php';