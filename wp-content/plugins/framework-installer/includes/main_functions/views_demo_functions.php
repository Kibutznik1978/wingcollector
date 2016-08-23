<?php
function wpv_demo_views_init() {
	if (!defined('WPV_VERSION')) {	
		$embedded_views_path=WPVDEMO_ABSPATH . '/embedded-views/views.php';
		if (file_exists($embedded_views_path)) {
			require_once WPVDEMO_ABSPATH . '/embedded-views/views.php';
		}
	}
}

/**
 * Activation hook.
 */
function wpvdemo_activation_hook() {
	add_option('wpvdemo_do_activation_redirect', true);
}

/**
 * Inits Types embedded code.
 */
function wpvdemo_init_embedded_code() {
	if (!defined('WPCF_EMBEDDED_ABSPATH')) {	
		$embedded_types_path=WPVDEMO_ABSPATH . '/embedded-types/types.php';
		if (file_exists($embedded_types_path)) {
			require_once WPVDEMO_ABSPATH . '/embedded-types/types.php';
		}		
		//        wpcf_embedded_init();
	}
}

function wpvdemo_plugins_loaded_hook() {
	// Include embedded code.
	if (!defined('WPV_VERSION')) {	
		$embedded_views_path=WPVDEMO_ABSPATH . '/embedded-views/views.php';
		if (file_exists($embedded_views_path)) {
			//Exists
			require_once WPVDEMO_ABSPATH . '/embedded-views/views.php';
		}	
		
	}
}

/**
 * Init hook function.
 */
function wpvdemo_init_hook() {
	global $wpvdemo;

	wpvdemo_plugin_localization();

	$wpvdemo = get_option('wpvdemo');
	if (!empty($wpvdemo['title'])
	&& !empty($wpvdemo['tutorial_title'])
	&& !empty($wpvdemo['ID'])
	&& !empty($wpvdemo['tutorial_url'])
	) {
		
		/** Unified customized Dashboard display (Discover-WP and Standalone, same handler) */
		/** Since Framework installer 1.8.4 */	
		/** Hook to display message on dashboard is added here */
		/** Discover-WP and standalone handler */		
		
		
		//We will load if the following conditions are true
		//->non-multisite dashboard
		//->multisite dashboard but not the main site
		global $showdiscover_dashboard;
		$showdiscover_dashboard=false;
		if ((is_multisite()) && ((!(is_main_site())))) {
			$showdiscover_dashboard=true;
		}
		$we_are_standalone='yes';
		if ($showdiscover_dashboard) {
			$we_are_standalone='no';
		}
		
		$site_info = wpvdemo_get_site_settings( $wpvdemo['ID'] );
		$robot_icon_exported='no';
		if (isset($site_info->show_robot_icon)) {
			//Setting exported
			$robot_icon_exported='yes';			
		}		
		
		if ((!(is_multisite())) || ($showdiscover_dashboard)) {
			
			//Backend processing only
			if (is_admin()) {
				wp_enqueue_style( 'wpvdemo-standalone-dashboard-override-style', WPVDEMO_RELPATH . '/css/dashboard-override-style.css', array(), WPVDEMO_VERSION );		
				wp_enqueue_script( 'wpvdemo-standalone-dashboard-override-new', WPVDEMO_RELPATH . '/js/dashboard-override-new.js', array( 'jquery' ), WPVDEMO_VERSION );
				
				
				$are_you_sure = __('Are you sure','wpvdemo').'?';			
				$are_you_sure=esc_js($are_you_sure);
				
				wp_localize_script( 'wpvdemo-standalone-dashboard-override-new', 'fi_new_welcome_panel', 
					array(
						'are_you_sure_msg'   	=> $are_you_sure,
						'we_are_standalone'		=> $we_are_standalone,
						'robot_icon_exported' 	=> $robot_icon_exported
					) 
				);
				
				remove_action( 'welcome_panel', 'wp_welcome_panel' );
				add_action( 'welcome_panel', 'wpvdemo_new_welcome_panel' );
			}
		}
	
	}
	
	$wpvdemo['requirements'] = wpvdemo_check_requirements();
	if (empty($wpvdemo['requirements']['themes_dir_writeable'])
	&& empty($wpvdemo['requirements']['media_dir_writeable'])) {
		add_action('admin_notices',
		'wpvdemo_requirements_dirs_writeable_error_message');
	} else {
		if (empty($wpvdemo['requirements']['themes_dir_writeable'])) {
			add_action('admin_notices',
			'wpvdemo_requirements_themes_writeable_error_message');
		}
		if (empty($wpvdemo['requirements']['media_dir_writeable'])) {
			add_action('admin_notices',
			'wpvdemo_requirements_media_writeable_error_message');
		}
	}
	if (defined('WPVDEMO_WPCONTENTDIR')) {		
		if (empty($wpvdemo['requirements']['wpcontent_dir_writeable'])) {
		add_action('admin_notices', 'wpvdemo_requirements_wpcontent_writeable_error_message');
		} 
	}

	if (empty($wpvdemo['requirements']['zip'])) {
		add_action('admin_notices', 'wpvdemo_requirements_zip_error_message');
	}
	//Add check if allow_url_open is enabled
	if (empty($wpvdemo['requirements']['enabled_native_PHP_remote_parsing_functions'])) {
		add_action('admin_notices', 'wpvdemo_disabled_native_PHP_remote_parsing_functions_message');
	}
	if (get_option('wpvdemo_do_activation_redirect', false)) {
		delete_option('wpvdemo_do_activation_redirect');
		wp_redirect(admin_url() . 'admin.php?page=manage-refsites');
		exit;
	}
    if ( is_admin() ) {
    	if (!(is_multisite())) {
    		//Don't show installer menu in Discover WP multisite
        	add_action('admin_menu', 'appfw_setup_installer',15);
    	}
    }
}

//Render Installer packages
function appfw_installer_content()
{
	echo '<div class="wrap">';
	$config['repository'] = array(); // required
	WP_Installer_Show_Products($config);
	echo "</div>";
}

//Add submenu Installer to Framework installer
function appfw_setup_installer()
{
    add_submenu_page('manage-refsites', 'Installer', 'Installer', 'manage_options', 'installer', 'appfw_installer_content');
}

function wpvdemo_double_check_site_is_empty() {

	static $check = null;
	if ($check !== null) {
		return $check;
	}
	
	//Framework installer 1.7.1: Let's catch instances where importing a blank site
	$check_import_is_done= get_option('wpv_import_is_done');
	if ('yes' == $check_import_is_done) {
		//Import is done but the site is empty, could be importing Toolset starter blank site.
		//Let's ensure that database is resetted before user can do any imports
		//We will return FALSE immediately
		return FALSE;
			
	}

	$posts= wpvdemo_get_all_posts_for_blank_site();
	if (count($posts) == 2) {
		//Two posts, confirm if these are default WordPress post and page		 
		 $valid_posts=array();
		 foreach ($posts as $k=>$v) {
		 	$the_title=$v->post_title;	
		 	$helloworld_translated= __('Hello world!');
		 	$samplepage_translated= __('Sample Page');	 	
		 	if ((($helloworld_translated == $the_title)) || (($samplepage_translated == $the_title))) {
		 		$valid_posts[]=$the_title;
		 	}
		 }
		 $count_this=count($valid_posts);
		 if ($count_this===2) {
		 	return TRUE;
		 } else {
		 	return FALSE;
		 }
	} else {
         return FALSE;
	}	
	
	//Stay safe, return FALSE
	return FALSE;
}
//Special JS functions from embedded types
//START
function viewdemo_admin_add_js_settings( $id, $setting = '' ) {
	static $settings = array();
	$settings['wpcf_nonce_ajax_callback'] = '\'' . wp_create_nonce( 'execute' ) . '\'';
	$settings['wpcf_cookiedomain'] = '\'' . COOKIE_DOMAIN . '\'';
	$settings['wpcf_cookiepath'] = '\'' . COOKIEPATH . '\'';
	if ( $id == 'get' ) {
		$temp = $settings;
		$settings = array();
		return $temp;
	}
	$settings[$id] = $setting;
}

function viewsdemo_admin_render_js_settings() {
	$settings = viewdemo_admin_add_js_settings( 'get' );
	if ( empty( $settings ) ) {
		return '';
	}

	?>
    <script type="text/javascript">
        //<![CDATA[
    <?php
    foreach ( $settings as $id => $setting ) {
        if ( is_string( $setting ) ) {
            $setting = trim( $setting, '\'' );
            $setting = "'" . $setting . "'";
        } else {
            $setting = intval( $setting );
        }
        echo 'var ' . $id . ' = ' . $setting . ';' . "\r\n";
    }

    ?>
        //]]>
    </script>
    <?php
}
//END

/**
 * Gets settings from http://ref.wp-types.com/ or from local DB.
 * 
 * @return boolean 
 */
function wpvdemo_admin_get_sites_index($refresh_check = true) {

    $xml = get_option('wpvdemo-index-xml');
    $time = get_option('wpvdemo-refresh', 0);
    $config_file = defined('WPVDEMO_DEBUG') && WPVDEMO_DEBUG ? WPVDEMO_DOWNLOAD_URL . '/demos-index-debug.xml' : WPVDEMO_DOWNLOAD_URL . '/demos-index.xml';

    $wait = 43200;
    if(defined('WPVDEMO_DEBUG') && WPVDEMO_DEBUG) {
        $wait = 60;
    }
    
    	if (!$xml || ($refresh_check && time() - intval($time) > $wait)) {        
	
     	   //EMERSON: Use file_get_contents to fetch XML file.
     	   //Prevent issues like PHP Warning:  simplexml_load_string() Entity: line 24: parser error     	   
     	       	   
     	   $xml = wpv_remote_xml_get($config_file);        
      	  
      	  if ($xml) {    
      	  	         	
       	         update_option('wpvdemo-index-xml', $xml);
       	         update_option('wpvdemo-refresh', time());
       	         
       	         /** When a demo site is refreshed, refresh its version too. */
       	         delete_option('wpvdemo_refsite_installed_version_number');
        	    
        	} else {
        		if (ini_get('allow_url_fopen')) {
        	    echo wpvdemo_error_message('connect', true);
        	    return false;
        		}
        	}
    	}    
    	$sites_index = simplexml_load_string($xml);
    	return apply_filters('wpvdemo_sites_index', $sites_index);	
    
}

/**
 * Gets settings for site.
 * 
 * @param type $site_id
 * @return boolean 
 */
function wpvdemo_get_site_settings($site_id) {
    $sites = wpvdemo_admin_get_sites_index();
    if (empty($sites->site)) {
        return false;
    }
    foreach ($sites->site as $check_site) {
        if (intval($check_site->ID) == intval($site_id)) {
            return $check_site;
        }
    }
    return false;
}

/**
 * Checks required downloaded settings for each site.
 * 
 * @param type $settings
 * @return boolean 
 */
function wpvdemo_admin_check_required_site_settings($settings, $show_error = true, $objectified= true) {
    $required_settings = array('title', 'download_url', 'title', 'tagline', 'theme_url', 'tutorial_title', 'tutorial_url', 'fileupload_url', 'shortname');
    foreach ($required_settings as $setting) {
    	if ($objectified) {
	        if (empty($settings->$setting)) {	                
	            return false;
	        }
    	} elseif (!($objectified)) {
    		if (empty($settings[$setting])) {
    			return false;
    		}  		
    	}
    }
    return true;
}

/**
 * Admin menu page render. 
 */
function wpvdemo_admin_menu_import() {
    $settings = wpvdemo_admin_get_sites_index();    
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');    
    echo "\r\n" . '<div class="wrap" style="width:700px;">
	<div id="' . 'icon-wpvdemo' . '" class="icon32"><br /></div>
    <h2>' . 'Framework Installer' . '</h2>' . "\r\n";
    do_action('wpvdemo_start_demo_page');
    if (empty($settings->site)) {
    	if (ini_get('allow_url_fopen')) {    		
    	//return this error only if it make sense
        echo wpvdemo_error_message('data', true);
    	}
    } else {
        foreach ($settings->site as $site) {
            if (!wpvdemo_admin_check_required_site_settings($site)) {
                continue;
            }
            if (apply_filters('wpvdemo_hide_site_download', false, $site)) {
                continue;
            }

            if (isset($site->title)) {
                $site->title = wpv_demo_get_tutorial_text($site->title);
            }
            if (isset($site->tagline)) {
                $site->tagline = wpv_demo_get_tutorial_text($site->tagline);
            }
            if (isset($site->tutorial_title)) {
                $site->tutorial_title = wpv_demo_get_tutorial_text($site->tutorial_title);
            }
            if (isset($site->short_description)) {
                $site->short_description = wpv_demo_get_tutorial_text($site->short_description);
            }

            ob_start();
            
            echo '<h1>' . $site->title . '</h1>';
            echo '<div class="wrap" style="width:500px;">';
            if (isset($site->image)) {
                $site->image = wpvdemo_convert_to_cloud_url($site->image, $site);
                echo '<img src="' . $site->image . '" title="' . $site->title
                . '" alt="' . $site->title
                . '" style="border: 1px solid #DBDBDB; float: left; margin: 0 15px 15px 0;" />';
            }
            echo isset($site->short_description) ? wpautop(stripslashes($site->short_description)) : '';
            // Plugins
            if (!empty($site->plugins->plugin)) {

            	$display_plugins=wpvdemo_format_display_plugins($site->plugins->plugin,true);     	

                if (!empty($display_plugins)) {

                    $required_plugin_failed = array();
                    $optional_plugins=array();
                    $mode_of_import='';
                    $display_plugins=wpvdemo_check_if_wpml_will_be_skipped($display_plugins,$site->download_url,false,false,'');
                    
                    if (isset($display_plugins['mode_of_import'])) {
                    	$mode_of_import=$display_plugins['mode_of_import'];
                    }
                                        
                    if (isset($display_plugins['optional'])) {
                    	$optional_plugins=$display_plugins['optional'];
                    }
                    
                    if (isset($display_plugins['required'])) {
                    	$display_plugins=$display_plugins['required'];
                    }
                    
                    if (!empty($display_plugins)) {
                    	$required_plugin_output=wpv_demo_functionalized_display($display_plugins,'required');
                    	echo $required_plugin_output['html'];
                    	$required_plugin_failed=$required_plugin_output['required_plugin_failed'];
                    }
                    
                    if (!empty($optional_plugins)) {
                    	$optional_plugin_output=wpv_demo_functionalized_display($optional_plugins,'optional');
                    	echo $optional_plugin_output['html'];                    	
                    }                  
                    
                    //Show incompatible plugin notice
                    if ((!(empty($incompatible_plugins_notice_array))) && (!(is_multisite()))) {
                    	//Some incompatible plugins there, show notice
                    	echo '<p><strong>'.__('Warning:','wpvdemo').'</strong>'.'&nbsp;&nbsp;'.__('Some incompatible required plugins are found in your plugins directory. It is recommended to use the tested version indicated in red font for compatibility. Please do this before clicking the download button.','wpvdemo').'</p><br />';
                    }

                    if (!empty($required_plugin_failed)) {
                        echo '<p>' . sprintf(wpvdemo_error_message('required_plugins_disabled_download',
                                        true),
                                '</p><ul><li>' . implode('</li><li>',
                                        $required_plugin_failed) . '</li></ul>');
                    }
                }
            }
            echo '</div>';
            echo '<div style="clear:both;"></div>';

            $site_info = ob_get_clean();            
            echo apply_filters('wpvdemo_site_info', $site_info, intval($site->ID));

            ob_start(); 
            
            //Clear $required_plugin_failed array for any sites with no required plugins
            if (empty($site->plugins->plugin)) { 
            	$required_plugin_failed=array();
            } 
            
            $missing = apply_filters('installer_deps_missing', false);
            if (!($missing)) {
            	//all required plugins are installed and active
            	$disabled_check='';            	
            } else {
            	$disabled_check='disabled="disabled"';
            }
            
	        echo "<a $disabled_check";
	        echo !wpvdemo_download_requirements_failed() && empty($required_plugin_failed) ? 'href="' . $site->ID
	                . '" class="wpvdemo-download button-primary"' : 'href="#" class="button-primary" disabled="disabled"';          
	        echo ' id="wpvdemo-download-button-' . $site->ID . '">';
	        if (($mode_of_import=='nonwpml') && (!(is_multisite()))) {
	         	echo __('Download', 'wpvdemo') . '</a>';
	        } elseif (($mode_of_import=='wpml') && (!(is_multisite()))) {
	          	echo __('Download multilingual version', 'wpvdemo') . '</a>';            	
	        } else {
	           	echo __('Download', 'wpvdemo') . '</a>';
	        }            

            $download_button = ob_get_clean();
            echo apply_filters('wpvdemo_download_button', $download_button,
                    intval($site->ID));

            echo '<div id="wpvdemo-download-response-' . $site->ID
            . '" class="wpvdemo-download-response" style="clear:both;"></div>';
            echo '<div class="wpvdemo-download-loading" style="clear:both;">&nbsp;</div>';
        }
    }
    echo "\r\n" . '</div>' . "\r\n";

    do_action('wpvdemo_end_demo_page');
}

function wpv_demo_functionalized_display($display_plugins,$mode) {
	$html='';
	$required_plugin_failed=array();
	if ($mode=='required') {
	$html .= '<div style="clear:both;"></div><strong>'
			. __('Required plugins:', 'wpvdemo') . '</strong>'
					. '<ul style="list-style-type: square; list-style-position:inside;">';
	} elseif(($mode=='optional')) {
		
		$html .= '<div style="clear:both;"></div><strong>'
				. __('Optional plugins (For downloading multilingual version of the site):', 'wpvdemo') . '</strong>'
						. '<ul style="list-style-type: square; list-style-position:inside;">';		
		
	}
	
	$incompatible_plugins_notice_array=array();
	foreach ($display_plugins as $plugin) {
		// Skip Views and Types
		if (in_array(basename((string) $plugin->file),
				array('wp-views.php', 'wpcf.php'))) {
			continue;
		}
		$html .= '<li><a href="' . $plugin->url . '" target="_blank" title="'
				. $plugin->title . '">' . $plugin->title . '</a>';
		$active = false;
		$found = false;
	
		$plugin_file_string_active=(string) $plugin->file;
		$plugin_name_string_active=(string) $plugin->title;
	
		$available_plugin_parameters_active=array('Plugin_file_active'   =>$plugin_file_string_active,
				'Plugin_name_active'   =>$plugin_name_string_active
		);
	
		$active = wpvdemo_is_active_plugin($available_plugin_parameters_active);
		if (!$active) {
			 
			//Plugins not active, typical standalone Framework Installer import setup
			$plugin_file_string=(string) $plugin->file;
			$plugin_name_string=(string) $plugin->title;
			$plugin_version_string=(string) $plugin->plugin_version_tested;
			 
			$available_plugin_parameters=array(
					'Plugin_file'=>$plugin_file_string,
					'Plugin_name'=>$plugin_name_string,
					'Plugin_version'=>$plugin_version_string
			);
	
			$found = wpvdemo_is_available_plugin($available_plugin_parameters);
			 
			if ($found) {
				//Plugin is found
				if ((isset($found['compatibility'])) && (!(is_multisite()))) {
					$compatibility_result=$found['compatibility'];
	
					if ($compatibility_result=='yes') {
						 
						//Plugin fully compatible
						$html .= '&nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
						 
					} else {
						 
						//Plugin found but not the tested version
						if (isset($found['tested_version'])) {
							$tested_version=$found['tested_version'];
							$incompatible_plugins_notice_array[]=$tested_version;
							/*Version compare*/
							$version_compare_text=framework_installer_version_compare_strings($found);
							
							$html .= '&nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.'<font color="red">&nbsp;&nbsp;-'.$version_compare_text.' '.__('to tested version: ','wpvdemo').'&nbsp;'.$tested_version.'</font>';
						} else {
							$html .= '&nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
						}
					}
				} else {
					$html .= '&nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
				}
			} else {
				if(strpos($plugin->url, 'wordpress.org/extend/plugins/')){
					$plugin_name_url = str_replace("http://wordpress.org/extend/plugins/", '', substr($plugin->url, 0, -1));
					$required_plugin_failed[] = '<a href="'
							. $plugin->url . '" target="_blank" title="'
									. $plugin->title . '">' . $plugin->title . '</a>&nbsp;&nbsp;&nbsp;
                                            <a href="'.site_url().'/wp-admin/update.php?action=install-plugin&plugin='.$plugin_name_url.'&_wpnonce='.wp_create_nonce('install-plugin_'.$plugin_name_url).'" tutle="">Quick Install</a> &nbsp;&nbsp;&nbsp;
                                            <a class="thickbox" href="'.site_url().'/wp-admin/plugin-install.php?tab=plugin-information&plugin='.$plugin_name_url.'&TB_iframe=true&width=600&height=550" tutle="">Details</a>';
	
				}else{
					$required_plugin_failed[] = '<a href="'
							. $plugin->url . '" target="_blank" title="'
									. $plugin->title . '">' . $plugin->title . '</a>';
				}
				$html .= '&nbsp;<span style="color:Red;">' . wpvdemo_error_message('required_plugin_warning') . '</span>';
			}
		} else {
			$html .= '&nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
		}
		$html .= '</li>';
	}
	
	$html .= '</ul>';

	$output=array('html'=>$html,'required_plugin_failed'=>$required_plugin_failed);
	return $output;
}

function framework_installer_version_compare_strings($found) {   
	
	if ((isset($found['tested_version'])) && (isset($found['installed_version']))) {
	
	  $tested_version=$found['tested_version'];
	  $installed_Version=$found['installed_version'];
	  
	  if ((!(empty($tested_version))) && (!(empty($tested_version)))) {
	  	
	  	$version_compare_check=version_compare($tested_version, $installed_Version);
	  	
	  	if ($version_compare_check < 0) {
	  		
	  		//Plugin tested version is less than installed version
	  		return __('Downgrade','wpvdemo');
	  		
	  	} elseif ($version_compare_check > 0) {
	  		
	  		//Plugin tested version is greater than installed version
	  		return __('Upgrade','wpvdemo');
	  	}
	  	
	  } else {
	  	
	  	//Use general text
		return __('Update','wpvdemo');
		
	  }		
		
	} else {
		
		//Use general text
		return __('Update','wpvdemo');		
		
	}	
}
/**
 * Triggers import action.
 */
function wpvdemo_download() {
	
	global $frameworkinstaller;
    $wpvdemo = get_option('wpvdemo');
    if (!empty($wpvdemo['installed'])) {
        die();
    }
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],
                    'wpvdemo') && isset($_POST['site_id'])
            && isset($_POST['step'])) {
        require_once WPVDEMO_ABSPATH . '/includes/import.php';
        
        $versions='';            
        if (isset($_POST['version'])) {            	
            $versions= trim($_POST['version']);
        }

        //Standalone mode import OR Discover-WP
        /** Framework installer 1.8.2+ We now allow non-multilingual import of a site with multilingual version in DiscoverWP */
        $the_version_installed= get_option('wpvdemo_the_version_installed');
        if (!($the_version_installed)) {
            //Not yet defined
            update_option('wpvdemo_the_version_installed',$versions);
        }
   
        wpvdemo_import($_POST['site_id'], $_POST['step'],$versions);
    }
    die();
}

/**
 * Checks if site is fresh install.
 * 
 * @return boolean 
 */
function wpvdemo_check_if_blank_site() {
    static $check = null;
    if ($check !== null) {
        return $check;
    }
    
    //Framework Installer 1.7.1: Importing a blank site is not blank anymore.
    $check_import_is_done= get_option('wpv_import_is_done');
    if ('yes' == $check_import_is_done) {
    	//Import is done but the site is empty, could be importing Toolset starter blank site.
    	//Let's ensure that database is resetted before user can do any imports
    	//We will return FALSE immediately
    	add_action('admin_notices', 'wpvdemo_check_if_blank_site_message');
    	$check = false;
    	return FALSE;    		
    }        
    $posts= wpvdemo_get_all_posts_for_blank_site();    
    if (count($posts) > 2) {
        add_action('admin_notices', 'wpvdemo_check_if_blank_site_message');
        $check = false;
        return false;
    }
    $check = true;
    return true;
}

/**
 * Echoes importing posts progress. 
 */
function wpvdemo_get_post_count() {
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],
                    'wpvdemo_nonce')) {
        return false;
    }
    $total = get_option('wpvdemo-post-total');
    $count = get_option('wpvdemo-post-count');

    if ($count != '0') {
        echo sprintf(__('%s of %s', 'wpvdemo'), $count, $total) . ' ';
    }

    die();
}

/**
 * Checks requirements.
 * 
 * @return type 
 */
function wpvdemo_check_requirements() {
    $check = array();    
    if (defined('WPVDEMO_WPCONTENTDIR')) {
    	$user_wpcontent=WPVDEMO_WPCONTENTDIR;
    	$check['wpcontent_dir_writeable'] = is_writeable($user_wpcontent);
    }   
    /** THEMES DIRECTORY */
    //Define theme path
    $theme_directory_path=dirname(get_stylesheet_directory());
    $theme_directory_path=rtrim($theme_directory_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $theme_writable_boolean=is_writeable($theme_directory_path);
    $check['themes_dir_writeable'] = $theme_writable_boolean;
    
    /** UPLOADS DIRECTORY */
    $wp_upload_dir = wp_upload_dir();
    //Define uploads path
    $uploads_directory_path= $wp_upload_dir['basedir'];
    $uploads_directory_path=rtrim($uploads_directory_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $uploads_writable_boolean= is_writeable($uploads_directory_path);    
    $check['media_dir_writeable'] =$uploads_writable_boolean;
    
    /** BASIC PHP SERVER REQUIREMENTS */
    $check['zip'] = class_exists('ZipArchive');
    if (ini_get('allow_url_fopen')) {
    	$check['enabled_native_PHP_remote_parsing_functions']=true;
    } 
    return $check;
}

/**
 * Converts URLs.
 * 
 * @param type $value
 * @param type $upload_dir_url
 * @return type 
 */
function wpvdemo_convert_url($url, $settings) {
    // Check uploaded files and other files
    if (strpos($url, (string) $settings->fileupload_url) !== false) {
        $upload_dir = wp_upload_dir();
        $url = str_replace((string) $settings->fileupload_url,
                $upload_dir['baseurl'], $url);
    } else if (strpos($url, (string) $settings->site_url) !== false) {
        $url = str_replace((string) $settings->site_url, get_site_url(), $url);
    }
    return $url;
}

/**
 * Fixes menu items.
 * 
 * @global type $wpvdemo
 * @global type $wpdb
 * @param type $items
 * @param type $menu
 * @param type $args
 * @return type 
 */
function wpvdemo_wp_get_nav_menu_items_filter($items, $menu, $args) {
    global $wpvdemo, $wpdb;
    if (isset($wpvdemo['ID'])) {
        $settings = wpvdemo_get_site_settings($wpvdemo['ID']);
        if (!empty($settings)) {
            foreach ($items as $key => $item) {
                if (strpos($item->url, (string) $settings->site_url) === 0) {
                    $post_name = basename($item->url);
                    $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s",
                                    $post_name));
                    if ($post_id) {
                        $items[$key]->url = get_post_permalink($post_id);
                    }
                }
            }
        }
    }
    return $items;
}

/**
 * Activates plugins if necessary.
 * 
 * @param type $plugins 
 */

//IMPROVISED
function wpvdemo_activate_plugins($plugins,$activated_plugins_siteshortname) {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$errors = false;

	if (!is_array($plugins)) {
		$plugins = (array) $plugins;
	}
	$plugins= apply_filters('wpvdemo_activate_plugins', $plugins,$activated_plugins_siteshortname);
 	$handle_file=array();
	$handle_name=array();
	 
	foreach ($plugins['plugin_file_stream'] as $plugin) {

		$handle_file[]=$plugin;
		 
	}
	 
	foreach ($plugins['plugin_name_stream'] as $plugin_name) {
		 
		$handle_name[]=$plugin_name;
		 
	}
	 
	$plugins_array_forchecking=array_combine($handle_name,$handle_file);	 
	 
	foreach ($plugins_array_forchecking as $key=>$value) {
		//$value= plugin file path
		//$key= Plugin name
		 
		$required_active = wpvdemo_is_active_plugin(array('Plugin_file_active' => $value,'Plugin_name_active' =>$key));		
		if (!$required_active) {
			$available = wpvdemo_is_available_plugin(array('Plugin_file' => $value,'Plugin_name' =>$key));
			
			if ($available) {
				
     			//Retain this activate_plugin format to avoid downloading errors in Views commerce ML in localhost
     			$success = activate_plugin($available['file']);
				
				if (is_wp_error($success)) {
					$errors[$available['file']] = $success->get_error_message();
				} else {
					do_action('wpvdemo_activate_plugin', $available['file']);
				}

				//use original reference site shop permalink
				if (basename($available['file'])=='woocommerce.php') {		

					global $woocommerce;					
					
					if (is_object($woocommerce)) {

						//WooCommerce version 2.1.0 and beyond
						//Woocommerce 2.0 installation of pages, removing this warning										
						$activated_plugins_siteshortname_array =apply_filters('wpvdemo_deletewc_unneededoptions',array());
						if (in_array($activated_plugins_siteshortname,$activated_plugins_siteshortname_array)) {
							//Don't anymore create WooCommerce pages
							//Use the imported WooCommerce pages
							//We no longer need to install pages
							
							delete_option( '_wc_needs_pages' );
							delete_transient( '_wc_activation_redirect' );
										
						}																	
					}
					
					update_option( 'woocommerce_prepend_shop_page_to_products', 'no' );	
                    
                    //Set early to prevent PHP notices on Woocommerce
                    $transient_woocommerce_data=array('woocommerce_cache_excluded_uris');
					set_transient( 'woocommerce_cache_excluded_uris', $transient_woocommerce_data );
				}			
				//END
			}
		}
		 
	}
    
	return $errors;
}

/**
 * Checks if plugin is active.
 * 
 * @param type $plugin
 * @return boolean 
 */
function wpvdemo_is_active_plugin($plugin) {
	$pluginfile_active=$plugin['Plugin_file_active'];
	$plugin_title_active=$plugin['Plugin_name_active'];	
	
    $active_plugins = get_option('active_plugins', array());  
    
    // Checks exact match
    foreach ($active_plugins as $plugin_file) {
        if ($plugin_file == $pluginfile_active) {
            return array('match' => 'exact', 'file' => $plugin_file);
            break;
        }
    }
    // Checks similar match
    reset($active_plugins);    
    foreach ($active_plugins as $plugin_file) {
        if (basename($plugin_file) == basename($pluginfile_active)) {
        	if (basename($plugin_file) <> 'plugin.php') {
            return array('match' => 'similar', 'file' => $plugin_file);
            break;
            } else {
            	$plugin_realpath=dirname(WPVDEMO_ABSPATH).'/'.$plugin_file;
            	$handle = fopen($plugin_realpath, "r");
            	$contents = fread($handle,filesize($plugin_realpath));
            	$pieces = explode("\n", $contents);
            	$key = array_find('Plugin Name', $pieces);
            	$value=trim($pieces[$key]);
            	$value_exploded=explode(":",$value);;
            	$plugin_name_extract=$value_exploded[1];
            	//This is the actual plugin name of plugin.php found in plugins directory
            	$plugin_name_extract=trim($plugin_name_extract);
            	//This is the plugin name of exported file with plugin.php file name
            	$exported_plugin_title=trim($plugin_title_active);
            		if ($plugin_name_extract==$exported_plugin_title) {
            			return array('match' => 'similar', 'file' => $plugin_file);
            			break;
            		}
            	fclose($handle);            	
            }
        }         
    }
    return false;
}

/**
 * Checks if plugin is available.
 * 
 * @param type $plugin
 * @return boolean 
 */
function wpvdemo_is_available_plugin($plugin) {
	$pluginfile=$plugin['Plugin_file'];
	$plugin_title=$plugin['Plugin_name'];	
	
	if (isset($plugin['Plugin_version'])) {
		$plugin_version=$plugin['Plugin_version'];
	}
	
    $all_plugins = get_plugins();

    // Check exact match-changes OK
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if ($plugin_file == $pluginfile) {
        	if (!(isset($plugin_version))) {
        		
            	return array('match' => 'exact', 'file' => $plugin_file);
            	
        	} else {
        		
        		//Plugin version is set, check if compatible
        		//Get version of the exact match        
        		if (isset($plugin_data['Version'])) {		
        			$exact_match_plugin_version=$plugin_data['Version'];
        			if ($exact_match_plugin_version==$plugin_version) {
        				$compatibility='yes';
        			} else {
        				$compatibility='no';
        			}
        			return array('match' => 'exact', 'file' => $plugin_file,'compatibility'=>$compatibility,'tested_version'=>$plugin_version,'installed_version'=>$exact_match_plugin_version);
        		}
        	}
            break;
        }
    }
    // Check similar match
    reset($all_plugins);
    
    foreach ($all_plugins as $plugin_file => $plugin_data) {    	
        //Changes OK here
    	if (basename($plugin_file) == basename($pluginfile)) {
    		if (basename($plugin_file) <> 'plugin.php') {

            	if (!(isset($plugin_version))) {
            		
            		//Plugin versions not set
            		return array('match' => 'similar', 'file' => $plugin_file);    	
            	} else {
            		
            		//Plugin versions are set, check for compatibility
            		if (isset($plugin_data['Version'])) {
            			$similar_match_plugin_version=$plugin_data['Version'];
            			
            			if ($similar_match_plugin_version==$plugin_version) {
            				$compatibility='yes';
            			} else {
            				$compatibility='no';
            			}      
            			return array('match' => 'similar', 'file' => $plugin_file,'compatibility'=>$compatibility,'tested_version'=>$plugin_version,'installed_version'=>$similar_match_plugin_version);
            		}            		
            	}
            	
            	break;
    		} else {
            $plugin_realpath=dirname(WPVDEMO_ABSPATH).'/'.$plugin_file;
            $handle = fopen($plugin_realpath, "r");
            $contents = fread($handle,filesize($plugin_realpath));
            $pieces = explode("\n", $contents);
            $key = array_find('Plugin Name', $pieces);
            $value=trim($pieces[$key]);
            $value_exploded=explode(":",$value);;
            $plugin_name_extract=$value_exploded[1];
            //This is the actual plugin name of plugin.php found in plugins directory
            $plugin_name_extract=trim($plugin_name_extract);
            
            //New in 1.7
            //Let's checked if this is an embedded version of the plugin
            if (stripos($plugin_name_extract, 'Embedded') !== false) {
            	//Embedded
            	//Let's remove this word for matching purposes
            	$plugin_name_extract =str_replace("Embedded", "", $plugin_name_extract);
            	$plugin_name_extract =trim($plugin_name_extract);            	
            }
            
            //This is the plugin name of exported file with plugin.php file name
            $exported_plugin_title=trim($plugin_title);
            if ($plugin_name_extract==$exported_plugin_title) {
            	
            	if (!(isset($plugin_version))) {
            	   //Plugin version is not set
            		return array('match' => 'similar', 'file' => $plugin_file);
            	} else {
            		if (isset($plugin_data['Version'])) {
            			$another_similar_match_plugin_version=$plugin_data['Version'];
            			
            			if ($another_similar_match_plugin_version==$plugin_version) {
            				$compatibility='yes';
            			} else {
            				$compatibility='no';
            			}            			
            			return array('match' => 'similar', 'file' => $plugin_file,'compatibility'=>$compatibility,'tested_version'=>$plugin_version,'installed_version'=>$another_similar_match_plugin_version);
            		}           		
            	}
            	
            	break;            	
            }             
            fclose($handle);
    		}
        }
    
    }
    return false;
}
function array_find($needle, $haystack, $search_keys = false) {
	if(!is_array($haystack)) return false;
	foreach($haystack as $key=>$value) {
		$what = ($search_keys) ? $key : $value;
		if(strpos($what, $needle)!==false) return $key;
	}
	return false;
}

/**
 * Checks if plugin is allowed.
 * 
 * @param type $plugin
 * @return type 
 */
function wpvdemo_is_allowed_plugin($plugin,$plugin_element=array()) {
	
	global $frameworkinstaller;
	$original_exclusion=array('wp-views.php', 'wpcf.php', 'wordpress-importer.php', 'w3-total-cache.php', 'views-demo.php');	
	$plugin_name_basename= basename($plugin);
	
	if ($plugin_name_basename <> 'plugin.php') {	
		
		//Not plugin.php	
		if (in_array($plugin_name_basename,$original_exclusion)) {
			//In_array, return FALSE
			return FALSE;
		} else {
			return TRUE;
		}	
			
	} elseif ('plugin.php' ==$plugin_name_basename ) {
		
		//Special handling
		//This is a plugin.php file which is common to several plugins.
		//Let's retrieved the passed plugin name
		$plugin_element_title_canonical='';
		if (isset($plugin_element->title)) {
			$plugin_element_title=$plugin_element->title;
			if (!(empty($plugin_element_title))) {
				$plugin_element_title_canonical=$plugin_element_title;
			}
		} 
		if (!(empty($plugin_element_title_canonical))) {
			$plugin_name= $plugin_element_title_canonical;
			$plugin_name= (string)($plugin_name);
				
			//Additional exclusion after Toolset Required Embedded Implementation
			$new_exclusion =array(
					'CRED Frontend Editor',
					'Toolset CRED',
					'Module Manager',
					'Toolset Module Manager'
			);
			
			if (in_array($plugin_name,$new_exclusion)) {
				//in array return FALSE
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			//Default Allowed
			return TRUE;
		}
		
	}
    
}

/**
 * Converts to cloud URL.
 * 
 * @param type $url
 * @param type $settings
 * @return type 
 */
function wpvdemo_convert_to_cloud_url($url, $settings) {
    $real_url = str_replace($settings->fileupload_url,
            $settings->wp_upload_dir_url, $url);
    $url = str_replace($settings->site_url . '/', $settings->cloud_url . '/',
            $real_url);
    return $url;
}

/**
 * Checks if download requirements failed.
 * 
 * @global type $wpvdemo
 * @return boolean 
 */
function wpvdemo_download_requirements_failed() {
    global $wpvdemo;
    if (empty($wpvdemo['requirements']['themes_dir_writeable'])
            || empty($wpvdemo['requirements']['media_dir_writeable'])) {
        return true;
    }
    if (empty($wpvdemo['requirements']['wpcontent_dir_writeable'])) {
    	return true;
    } 
    if (!wpvdemo_check_if_blank_site()) {
        return true;
    }
    if (empty($wpvdemo['requirements']['zip'])) {
        return true;
    }
    return false;
}

// Localization
function wpvdemo_plugin_localization() {
    $locale = get_locale();
    load_textdomain('wpvdemo',
            WPVDEMO_ABSPATH . '/locale/views-demo-' . $locale . '.mo');
}

/**
 * Converts Types images URLs.
 * 
 * @global type $wpdb
 * @param type $settings 
 */
function wpvdemo_convert_types_images_url($settings) {
    global $wpdb;
    $fields = wpcf_admin_fields_get_fields();
    if (!empty($fields)) {
        foreach ($fields as $field) {
            if ($field['type'] == 'image' || $field['type'] == 'file') {
                $results = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key=%s",
                                wpcf_types_get_meta_prefix($field) . $field['slug']));
                if (!empty($results)) {
                    foreach ($results as $result) {
                        $new_url = apply_filters('types_images_convert_url',
                                wpvdemo_convert_url($result->meta_value,
                                        $settings), $result->meta_value);
                        $wpdb->update($wpdb->postmeta,
                                array('meta_value' => $new_url,
                                ), array('meta_id' => $result->meta_id),
                                array('%s'), array('%d')
                        );
                    }
                }
            }
        }
    }
}

//EMERSON: Disable some admin messages hooks
function wpv_demo_disable_admin_notices_demo() {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $sitepress;
	
	//Disable WPML admin notice
	remove_action('admin_notices', array($sitepress, 'help_admin_notice'));
	
	//Disable WooCommerce Views admin notice	
	$wpvdemo = get_option('wpvdemo');
	if (isset($wpvdemo['ID'])) {
		$wpvdemo_id=$wpvdemo['ID'];
		$wpvdemo_id=intval($wpvdemo_id);
	
		$the_sites= apply_filters('wpvdemo_disable_wcviews_admin_notice',array());
		
		if (in_array($wpvdemo_id,$the_sites)) {
			remove_action( 'admin_notices', 'wcviews_help_admin_notice' );
		}
	}

}

function customize_message_WP_reset($message) {

	global $frameworkinstaller;
	$can_support_unified_menu = $frameworkinstaller->wpvdemo_can_implement_unified_menu();
	if ( false === $can_support_unified_menu) {
		//Backward compatibility
		$wp_reset_url=admin_url().'admin.php?page=wpvdemo-reset';
	} else {
		//New reset screen		
		$wp_reset_url=admin_url().'admin.php?page=toolset-settings&tab=wpvdemo-reset';
	}
	
	$message = __("To be on the safe side, content import only works on fresh sites. We really don't want to accidentally delete content on live sites. To use this content importer again, please ","wpvdemo")."<a href='".$wp_reset_url."'>".__('reset this WordPress website database.','wpvdemo')."</a>";

	return $message;

}

/*EMERSON: Framework Installer remote XML parser*/
/*Use file_get_contents by default, or cURL if not available*/

function wpv_remote_xml_get($file) {

	$file_headers_wpvdemo = @get_headers($file);

	//Check if remote XML file exist, if yes then import
	if(strpos($file_headers_wpvdemo[0],'200 OK')) {
        
		    //Socket timeout for very slow connections
		    $context=stream_context_create(array('http'=>
			array(
				'timeout' => 1200 
			)
			));

			$xml_import_data=@file_get_contents($file,false,$context);

		if 	($xml_import_data) {

			return $xml_import_data;
				
		} else {
				
			return FALSE;
		}

	} else {

		return FALSE;

	}

}

/* WooCommerce Taxonomy*/
function register_color_taxonomy_bootcommerce() {
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$label='Color';
	$show_in_nav_menus=false;
	$hierarchical = true;
	
	$args= array(
			'hierarchical' 				=> 'true',
			'update_count_callback' 	=> '_update_post_term_count',
			'labels' => array(
					'name' 						=> $label,
					'singular_name' 			=> $label,
					'search_items' 				=> __( 'Search', 'woocommerce') . ' ' . $label,
					'all_items' 				=> __( 'All', 'woocommerce') . ' ' . $label,
					'parent_item' 				=> __( 'Parent', 'woocommerce') . ' ' . $label,
					'parent_item_colon' 		=> __( 'Parent', 'woocommerce') . ' ' . $label . ':',
					'edit_item' 				=> __( 'Edit', 'woocommerce') . ' ' . $label,
					'update_item' 				=> __( 'Update', 'woocommerce') . ' ' . $label,
					'add_new_item' 				=> __( 'Add New', 'woocommerce') . ' ' . $label,
					'new_item_name' 			=> __( 'New', 'woocommerce') . ' ' . $label
			),
			'show_ui' 					=> false,
			'query_var' 				=> true,
			'capabilities'			=> array(
					'manage_terms' 		=> 'manage_product_terms',
					'edit_terms' 		=> 'edit_product_terms',
					'delete_terms' 		=> 'delete_product_terms',
					'assign_terms' 		=> 'assign_product_terms',
			),
			'show_in_nav_menus' 		=> $show_in_nav_menus,
			'rewrite' 					=> array( 'slug' => 'color', 'with_front' => false, 'hierarchical' => $hierarchical ),
				);	
	
	//Selected registration of this taxonomy
	global $frameworkinstaller;
	$is_discoverwp=$frameworkinstaller->is_discoverwp();
	$sites_needing_this_taxonomy=apply_filters('wpvdemo_wc_create_color_taxonomy',array());

	if ($is_discoverwp) {
		//Case of Discover WP installation		
		if (isset($_POST['site_id'])) {
			$site_id_for_installation=  trim($_POST['site_id']);						
			if (in_array($site_id_for_installation,$sites_needing_this_taxonomy)) {				
				//This site requires this taxonomy, let's register
				register_taxonomy( 'pa_color', array('product'), $args );
			}
		}		
				
	}
		
	if ( defined('DOING_AJAX') && DOING_AJAX ) {
		//Doing AJAX, case of standalone import...
		if (isset($_POST['site_id'])) {
			$site_id_for_installation=  trim($_POST['site_id']);						
			if (in_array($site_id_for_installation,$sites_needing_this_taxonomy)) {				
				//This site requires this taxonomy, let's register
				register_taxonomy( 'pa_color', array('product'), $args );
			}
		}
	} 	
}

/* EMERSON: Check if WPML import will be skipped for importing multilingual sites but users does not have WPML plugins
 */

function wpvdemo_check_if_wpml_will_be_skipped($display_plugins=array(),$download_url='',$import=false,$silent_call=false,$passedversions='') {
   
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	
    //Download url
    $download_url=(string)$download_url . '/posts.xml';
    
    $parsed_url=parse_url($download_url);
    $get_path=$parsed_url['path'];    
    
    //Check if site has WPML implementation
    $has_wpml_implementation=wpvdemo_has_wpml_implementation($download_url,$import);
    
    //Prepare $display_plugins
    if (!(is_array($display_plugins))) {

		$display_plugins=$display_plugins->plugin;
	
    }
    
    if ($has_wpml_implementation) {
    	/** This site has WPML implementation */
    	
    	//Define the WPML plugins array
        
        //Framework Installer 1.8 -we add filter to customize plugins on a per site basis if needed
        //Standard multilingual sites at most requires 5 plugins (if it has WooCommerce support)
        $the_wpml_plugins=array(
        		'WPML Multilingual CMS',
        		'WPML Translation Management',
        		'WooCommerce Multilingual',
        		'WPML Media',
        		'WPML String Translation'
        );
        
        //Allow plugin requirements to be filtered
        do_action('wpvdemo_import_refsite_versions');
        $the_wpml_plugins=apply_filters('wpvdemo_wpml_plugin_requirements',$the_wpml_plugins,$get_path);
        
    	//Let's check if the user has all WPML plugins available   
    	$wpml_plugins_found=array();
     
		foreach ($display_plugins as $plugin) {

			$plugin_file_string=(string) $plugin->file;
			$plugin_name_string=(string) $plugin->title;
		
			if (in_array($plugin_name_string,$the_wpml_plugins)) {
          
       	   //Its a WPML plugin, check if user got this one.
			  $available_plugin_parameters=array('Plugin_file'=>$plugin_file_string,'Plugin_name'=>$plugin_name_string);
			  $found = wpvdemo_is_available_plugin($available_plugin_parameters);
		  
		 	 if ((is_array($found)) && (!(empty($found)))) {

          		$wpml_plugins_found[]=$plugin_name_string;

          	}
			}		
    	}
    
   	 	$check_result=wpvdemo_wpml_plugins_all_available($wpml_plugins_found,$the_wpml_plugins);

   	 	if ((isset($check_result['status'])) && ($check_result['status']=='all_true') && (isset($check_result['merge']))) {

            //All plugins there including WPML
             if (!($silent_call)) {
				
				//Let's checked if $versions are defined
				if ('wpml' == $passedversions) {
					
					//Default WPML import since all plugins are intact
					//$display_plugins_new in this case removes all WPML plugins but retains the required plugins.
					
					$display_plugins_new=wpvdemo_filter_wpml_plugins_from_list($display_plugins,$the_wpml_plugins);
					$the_merge=$check_result['merge'];
					$check_result=wpvdemo_convert_to_object_optional_plugins($the_merge,$display_plugins);
					$display_plugins_final=array('required'=>$display_plugins_new,'optional'=>$check_result,'mode_of_import'=>'wpml','activate'=>$display_plugins);
										
				} elseif ('nowpml' == $passedversions) {
					
					//Requesting non-WPML import even though WPML plugins are there
					//$display_plugins_new in this case removes all WPML plugins but retains the required plugins.
					$display_plugins_new=wpvdemo_filter_wpml_plugins_from_list($display_plugins,$the_wpml_plugins);
					$the_merge=$check_result['merge'];
					$check_result=wpvdemo_convert_to_object_optional_plugins($the_merge,$display_plugins);
					$display_plugins_final=array('required'=>$display_plugins_new,'optional'=>$check_result,'mode_of_import'=>'nonwpml','activate'=>$display_plugins_new);
					
				} else {	
					//$passedversions could be null				
					//Run default handling
					//$display_plugins_new in this case removes all WPML plugins but retains the required plugins.
					
					$display_plugins_new=wpvdemo_filter_wpml_plugins_from_list($display_plugins,$the_wpml_plugins);
					$the_merge=$check_result['merge'];
					$check_result=wpvdemo_convert_to_object_optional_plugins($the_merge,$display_plugins);
					$display_plugins_final=array('required'=>$display_plugins_new,'optional'=>$check_result,'mode_of_import'=>'wpml','activate'=>$display_plugins);					
				}
             	
                return $display_plugins_final;
             	
             } else {
                 
                //Return FALSE if there is WPML implementation
                return FALSE;
             }
        
		} elseif ((isset($check_result['status'])) && ($check_result['status']=='not_all')&& (isset($check_result['merge']))) {

            //User does not have all of it           
            $display_plugins_new=wpvdemo_filter_wpml_plugins_from_list($display_plugins,$the_wpml_plugins);   
           
            //Check if you want to filter posts.xml with non-WPML implementation            
            if (!($silent_call)) {
				
                //Append optional WPML components missing
                //Bring to object optional plugins
                $the_merge=$check_result['merge'];
                $check_result=wpvdemo_convert_to_object_optional_plugins($the_merge,$display_plugins);
                $display_plugins_final=array('required'=>$display_plugins_new,'optional'=>$check_result,'mode_of_import'=>'nonwpml','activate'=>$display_plugins_new);

            	return $display_plugins_final;
            	
            } else {
              
              //Return TRUE if importer will need to skip WPML
              return TRUE;

            }
        }
    } else {
            //site does not have WPML implementation, return usual plugin list
			if (!($silent_call)) {
                //Change to array compatible format
                $display_plugins_without_wpml=array();
                $display_plugins_without_wpml['activate']=$display_plugins;
                $display_plugins_without_wpml['required']=$display_plugins;
                $display_plugins_without_wpml['mode_of_import']='nonwpml';
                
            	return $display_plugins_without_wpml;
            } else {
                //Return TRUE no WPML
                return TRUE;
            }
    }   
}

function wpvdemo_convert_to_object_optional_plugins($check_result,$display_plugins) {
   
   foreach ($display_plugins as $k=>$plugin_elements) {
       $plugin_title=(string)$plugin_elements->title;
       if (!(in_array($plugin_title,$check_result))) {
		 //Not found, unset
		 unset($display_plugins[$k]);		 
       }
   }
   return $display_plugins;
}
function wpvdemo_filter_wpml_plugins_from_list($display_plugins,$the_wpml_plugins) {

   foreach ($display_plugins as $k=>$plugin_name) {

      $plugin_name_string=(string) $plugin_name->title;      
      if (in_array($plugin_name_string,$the_wpml_plugins)) {

		unset($display_plugins[$k]);
      }
   }
   
   return $display_plugins;
}

function wpvdemo_wpml_plugins_all_available($array1, $array2) {

	sort($array1);
	sort($array2);
	$the_merge=array_unique(array_merge($array2,$array1));
	
	if ($array1==$array2) {
       /*All plugins found*/	
		$result=array('status'=>'all_true','merge'=>$the_merge);
		return $result;
    } else {
       //Not all of them
       $result=array('status'=>'not_all','merge'=>$the_merge);
       return $result;
    }
	
}

function wpvdemo_has_wpml_implementation($file,$wpml_plugin_activation_check=false) {
	
	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	$parsed_url=parse_url($file);
	$get_path=$parsed_url['path'];

	//Define sites with WPML implementation	
	$has_wpml_array= apply_filters('wpdemo_sites_with_multilingual',array(),'export_xml_path');
	
	if (in_array($get_path,$has_wpml_array)) {

		//Site has WPML implementation
		$state=TRUE;
			
	} else {
			
		$state=FALSE;
			
	}
	
	if (!($wpml_plugin_activation_check)) {

        return $state;
    } else {

        if ((defined('ICL_SITEPRESS_VERSION')) && ($state))  { 
            
			return TRUE;

        } else {

            return FALSE;
            
        }    
    }
}

function wpvdemo_format_display_plugins($data,$check_allowed=true) {

	$display_plugins = array();
	if (isset($data)) {
		foreach ($data as $plugin) {
	        if ($check_allowed) {
				// Skip Views and Types
				$plugin_file_name=(string) $plugin->file;
				if (!wpvdemo_is_allowed_plugin($plugin_file_name,$plugin)) {
					continue;
				}
			}
			$display_plugins[] = $plugin;
		}
	}
	return $display_plugins;
}

function wpvdemo_refresh_rewrite_rules_on_firstload() {

	//Flushing rewrite rules once immediately after site first load after import
	$flush_rewrite_after_import=get_option('classifieds_flush_rewrite_after_import');
	$wcsites_flush_rewrite_after_import=get_option('wcsites_flush_rewrite_after_import');
	$import_done= get_option( 'wpv_import_is_done');

	$site_url=get_site_url();

	if (!($flush_rewrite_after_import)) {
	//Except classifieds

	    global $woocommerce;
	    
		//Run if import is done, flushing is still not performed and is using WooCommerce
		if (($import_done=='yes') && (!($wcsites_flush_rewrite_after_import)) && (is_object($woocommerce))) {
			//Not yet flushed
			global $wp_rewrite;
			$wp_rewrite->flush_rules(false);
			//Update option
			$success_updating=update_option('wcsites_flush_rewrite_after_import',$site_url);
		}
	}
}

function wpvdemo_remove_new_wpmlnotices() {
 
   global $WPML_Translation_Management;
   
   if (is_object($WPML_Translation_Management)) {
   	remove_action('admin_notices', array($WPML_Translation_Management, '_wpml_not_installed_warning'));
   }   
   
   //Remove Installer Nag   
   if (defined('WP_INSTALLER_VERSION')) {

      if (function_exists('WP_Installer')) {
      	$wpvdemo_installer_instance=WP_Installer();
      	
      	//Remove hook
      	remove_action('admin_notices', array($wpvdemo_installer_instance, 'show_site_key_nags'));
      }
   }
}
/*
 * Auto-resize images on the fly on specific sites
 * CREDIT: Benjamin Intal
 * From "OTF Regenerate Thumbnails" Plugin
 * https://wordpress.org/plugins/otf-regenerate-thumbnails/
 */

function wpvdemo_regen_thumbs_media_downsize( $out, $id, $size ) {

	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
	global $wpvdemo;
	$sites_requiring_regeneration= apply_filters('wpvdemo_required_thumbnail_regeneration',array());
	
	//Retrieve imported $ref site ID
	if (isset($wpvdemo['ID'])) {
		$refsite_id=$wpvdemo['ID'];
		$refsite_id=intval($refsite_id);
		if (in_array($refsite_id,$sites_requiring_regeneration)) {
			// Gather all the different image sizes of WP (thumbnail, medium, large) and,
			// all the theme/plugin-introduced sizes.
			global $_gambit_otf_regen_thumbs_all_image_sizes;
			if ( ! isset( $_gambit_otf_regen_thumbs_all_image_sizes ) ) {
				global $_wp_additional_image_sizes;
					
				$_gambit_otf_regen_thumbs_all_image_sizes = array();
				$interimSizes = get_intermediate_image_sizes();
					
				foreach ( $interimSizes as $sizeName ) {
					if ( in_array( $sizeName, array( 'thumbnail', 'medium', 'large' ) ) ) {
		
						$_gambit_otf_regen_thumbs_all_image_sizes[ $sizeName ]['width'] = get_option( $sizeName . '_size_w' );
						$_gambit_otf_regen_thumbs_all_image_sizes[ $sizeName ]['height'] = get_option( $sizeName . '_size_h' );
						$_gambit_otf_regen_thumbs_all_image_sizes[ $sizeName ]['crop'] = (bool) get_option( $sizeName . '_crop' );
		
					} elseif ( isset( $_wp_additional_image_sizes[ $sizeName ] ) ) {
		
						$_gambit_otf_regen_thumbs_all_image_sizes[ $sizeName ] = $_wp_additional_image_sizes[ $sizeName ];
					}
				}
			}
		
			// This now contains all the data that we have for all the image sizes
			$allSizes = $_gambit_otf_regen_thumbs_all_image_sizes;
		
			// If image size exists let WP serve it like normally
			$imagedata = wp_get_attachment_metadata( $id );
		
			// Image attachment doesn't exist
			if ( ! is_array( $imagedata ) ) {
				return false;
			}
		
			// If the size given is a string / a name of a size
			if ( is_string( $size ) ) {
					
				// If WP doesn't know about the image size name, then we can't really do any resizing of our own
				if ( empty( $allSizes[ $size ] ) ) {
					return false;
				}		
	
				// Resize the image
				$resized = image_make_intermediate_size(
						get_attached_file( $id ),
						$allSizes[ $size ]['width'],
						$allSizes[ $size ]['height'],
						$allSizes[ $size ]['crop']
				);
		
				// Resize somehow failed
				if ( ! $resized ) {
					return false;
				}
		
				// Save the new size in WP
				$imagedata['sizes'][ $size ] = $resized;
					
				// Save some additional info so that we'll know next time whether we've resized this before
				$imagedata['sizes'][ $size ]['width_query'] = $allSizes[ $size ]['width'];
				$imagedata['sizes'][ $size ]['height_query'] = $allSizes[ $size ]['height'];
					
				wp_update_attachment_metadata( $id, $imagedata );
		
				// Serve the resized image
				$att_url = wp_get_attachment_url( $id );
				return array( dirname( $att_url ) . '/' . $resized['file'], $resized['width'], $resized['height'], true );
		
		
				// If the size given is a custom array size
			} else if ( is_array( $size ) ) {
				$imagePath = get_attached_file( $id );
		
				// This would be the path of our resized image if the dimensions existed
				$imageExt = pathinfo( $imagePath, PATHINFO_EXTENSION );
				$imagePath = preg_replace( '/^(.*)\.' . $imageExt . '$/', sprintf( '$1-%sx%s.%s', $size[0], $size[1], $imageExt ) , $imagePath );
		
				$att_url = wp_get_attachment_url( $id );
		
				// If it already exists, serve it
				if ( file_exists( $imagePath ) ) {
					return array( dirname( $att_url ) . '/' . basename( $imagePath ), $size[0], $size[1], true );
				}
		
				// If not, resize the image...
				$resized = image_make_intermediate_size(
						get_attached_file( $id ),
						$size[0],
						$size[1],
						true
				);
		
				// Resize somehow failed
				if ( ! $resized ) {
					return false;
				}
		
				// Then serve it
				return array( dirname( $att_url ) . '/' . $resized['file'], $resized['width'], $resized['height'], true );
			}
		
			return false;
		}
	}
}
function wpvdemo_optional_plugins_activated_before_import($silent=false) {

	//This applies only to standalone mode import
		
	if (!(is_multisite())) {
		//Standalone
		if ( (defined('ICL_SITEPRESS_VERSION'))  ||
			 (defined('WPML_ST_VERSION')) ||
			 (defined('WPML_TM_VERSION')) ||
			 (defined('WCML_VERSION')) ||
			 (defined('WPML_MEDIA_VERSION'))
			) 
		{ 	
			//Let's double check if this site is empty!
			$site_is_empty_check=wpvdemo_double_check_site_is_empty();
			if ($site_is_empty_check) {
				//Here we have Framework installer activated AND the site is empty
				//But WPML plugins are activated, let's warn the user to deactivate this!
				add_action( 'admin_notices', 'wpvdemo_deactivate_optional_plugins_notice' );
				if ($silent) {
					return TRUE;
				}
			}
		} else {
			if ($silent) {
				return FALSE;
			}
		}
	} else {		
		//Multisite and in Discover-WP
		/** Framework intaller 1.8.2 +, WPML plugins are no longer network activated */
		if ( (defined('ICL_SITEPRESS_VERSION'))  ||
				(defined('WPML_ST_VERSION')) ||
				(defined('WPML_TM_VERSION')) ||
				(defined('WCML_VERSION')) ||
				(defined('WPML_MEDIA_VERSION'))
		)
		{
			if ($silent) {
				return TRUE;
			}
				
		} else {
			if ($silent) {
				return FALSE;
			}
		}
	}
}

function wpvdemo_deactivate_optional_plugins_notice() {
	?>
    <div class="error">
        <p>
			<?php _e( 'Some WPML plugins are <strong>activated</strong>! If you are importing a site using Framework Installer, please deactivate all of them first. Framework Installer will activate them automatically during import process if needed.', 'wpv-views' ); ?>
		</p>
    </div>
    <?php
}
function wpvdemo_retrieve_all_published_layouts() {

	global $wpdb;
	$posts_table= $wpdb->prefix."posts";
	$postid_array = $wpdb->get_results("SELECT ID FROM $posts_table WHERE post_type = 'dd_layouts' AND post_status='publish'", ARRAY_A);

	if (!(empty($postid_array))) {
		//Clean up
		$clean_ids=array();
		foreach ($postid_array as $k=>$v) {
			if ((is_array($v)) && (!(empty($v)))) {
				$clean_ids[]=reset($v);
			}
		}
		if (!(empty($clean_ids))) {
			return $clean_ids;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}
function wpvdemo_handle_dd_layouts_settings_custom_replacement($active_site_layouts,$original_host,$migration_redesigned_layouts_hostname,$parsed_host) {

	//dB query preparations
	global $wpdb;
	$post_meta_table= $wpdb->prefix."postmeta";
	$like = "%$original_host%";
	$search_query = "
	SELECT post_ID FROM $post_meta_table
	WHERE meta_key='dd_layouts_settings' AND meta_value LIKE %s
	";

	$layout_ids_containing_old_hostname = $wpdb->get_results($wpdb->prepare($search_query, $like),ARRAY_A);

	$clean_layouts_ids=array();

	if (!(empty($layout_ids_containing_old_hostname))) {
	//Clean up
			
		foreach ($layout_ids_containing_old_hostname as $k=>$v) {
			if ((is_array($v)) && (!(empty($v)))) {
			$clean_layouts_ids[]=reset($v);
			}
		}
	}

		/**
		* Loop through the clean_layouts_ids array and validate ids to be processed to make sure they belong to layouts
		*/

		$final_layouts_for_processing=array();
		if ((is_array($clean_layouts_ids)) && (!(empty($clean_layouts_ids)))) {
			
			foreach ($clean_layouts_ids as $k=>$layout_id_for_processing) {
	
				if (in_array($layout_id_for_processing,$active_site_layouts)) {
				$final_layouts_for_processing[]=$layout_id_for_processing;
				}
			}
		}

		//Process
		if ((is_array($final_layouts_for_processing)) && (!(empty($final_layouts_for_processing)))) {
			$success_results=array();
			//Loop through final layouts for processing and update hostname
			foreach ($final_layouts_for_processing as $k=>$id_for_processing) {
	
				//Get raw value from database
				$meta_value_serialized = get_post_meta( $id_for_processing, 'dd_layouts_settings', TRUE );
				if (wpvdemo_isJson($meta_value_serialized)) {
					if (is_string($meta_value_serialized)) {
						//Convert to array
						$meta_value_serialized=json_decode( $meta_value_serialized, true );
					}
				}				
				$replacement_result=migration_script_recursive_unserialize_replace( $migration_redesigned_layouts_hostname, $parsed_host, $meta_value_serialized, false, false);
		
				if ($replacement_result) {
				//Update back
				    global $wpddlayout;
					if (method_exists($wpddlayout,'save_layout_settings')) {
						//$replacement_result is an array
						WPDD_Layouts::save_layout_settings( $id_for_processing, $replacement_result );
					}
				}
			}
		
		}
}
function migration_script_recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false, $isRegEx = false ) {

	// some unseriliased data cannot be re-serialised eg. SimpleXMLElements
	
	try {
		
		if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
			$data = migration_script_recursive_unserialize_replace( $from, $to, $unserialized, true, $isRegEx );
		}

		elseif ( is_array( $data ) ) {
			$_tmp = array( );
			foreach ( $data as $key => $value ) {
				$_tmp[ $key ] = migration_script_recursive_unserialize_replace( $from, $to, $value, false, $isRegEx );
			}

			$data = $_tmp;
			unset( $_tmp );
		}

		else {
			if ( is_string( $data ) )
			{
				if ($isRegEx)
					$data = preg_replace( $from, $to, $data );
				else
					$data = str_replace( $from, $to, $data );

			}
		}

		if ( $serialised )
			return serialize( $data );

	} catch( Exception $error ) {

	}

	return $data;
}
function wpvdemo_isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}
function wpvdemo_disable_auto_reg_strings_wpml_wpv() {
 remove_action('init', 'wpv_register_wpml_strings_on_activation', 99);	
}
function wpvdemo_dont_auto_register_wpstrings() {
	$check_import_is_done = get_option('wpv_import_is_done');
	if ('yes' != $check_import_is_done) {
		remove_action('plugins_loaded', 'icl_st_init');
	}
}

//All purpose ID cleaner
//Returns FALSE if no ID processed
function wpvdemo_all_purpose_id_cleaner_func($postid_array) {

	if (!(empty($postid_array))) {
		//Clean up
		$clean_ids=array();
		foreach ($postid_array as $k=>$v) {
			if ((is_array($v)) && (!(empty($v)))) {
				$clean_ids[]=reset($v);
			}
		}
		if (!(empty($clean_ids))) {
			return $clean_ids;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

function wpvdemo_cleanup_wpml_plugins_array($plugin_name,$plugins_array) {
	//Search this plugin inside the plugins array
	$key = array_search($plugin_name, $plugins_array);
	
	if ($key) {
		
		//Plugins exists, unset
		unset($plugins_array[$key]);
		
		//Reorder
		$plugins_array=array_values($plugins_array);
		
		return $plugins_array;
	} else {
		return false;
	}	
	
}
/** General all purpose merging function for two ref sites (Layouts and without Layouts) */
function wpvdemo_server_side_merged_driver($aux_array,$merge_shortnames,$views_version,$layouts_version) {
	
	/**Step 1: Create an empty array of the merged site */
	$merged =array();
	
	/**Step 2: Define defaults */
	/** Defaults to non-Layouts version only */
	foreach ($aux_array as $k => $v) {
	
		//Retrieved shortname of this site
		$shortname=$v['shortname'];
		 
		if ($views_version == $shortname) {
			$merged=$v;
			break;
		}
	}
	
	/**Step 3: Add the Layouts version inside the non-Layouts version */
	foreach ($aux_array as $k => $v) {
	
		//Retrieved shortname of this site
		$shortname=$v['shortname'];
			
		if ($layouts_version == $shortname) {
			$merged['layouts_version']=$v;
			break;
		}
	}
	
	/**Step4: Retrieved the key index of each merged sites */
	$keys=array();
	foreach ($aux_array as $k => $v) {
	
		//Retrieved shortname of this site
		$shortname=$v['shortname'];
			
		if (in_array($shortname,$merge_shortnames)) {
			$keys[]=$k;
		}
	}
	/**Step5: Remove individual sites that are to be merged */
	foreach ($aux_array as $k => $v) {
	
		//Retrieved shortname of this site
		$shortname=$v['shortname'];
			
		if (in_array($shortname,$merge_shortnames)) {
			unset($aux_array[$k]);
		}
	}
	
	/**Step6: Insert the newly merged site */
	$insert_this=array($merged);
	$target_key=min($keys);
	array_splice($aux_array, $target_key, 0,$insert_this);
	$aux_array=array_values($aux_array);
	
	return $aux_array;
		
}
function wpvdemo_get_refsites_slug_func($file) {

	$dirname = basename ( dirname ( $file ) );

	return $dirname;
}
function wpvdemo_get_sites_index_as_arrays($sites) {
	
	$sites_array= json_decode(json_encode((array) $sites), 1);
	$sites_array= $sites_array['site'];
	
	return $sites_array;
}
function wpvdemo_get_all_posts_for_blank_site() {
	
	global $wpdb;
	$posts_table= $wpdb->prefix."posts";
	$sql = "SELECT * FROM $posts_table WHERE post_status NOT LIKE '%draft%'";
	$posts_objects = $wpdb->get_results($sql);	
	return $posts_objects;
}
function wpvdemo_woocommerce_is_active() {
	
	$is_active=false;
	global $woocommerce;

	if (( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) && (is_object($woocommerce))) {
		$is_active=true;
	}

	return $is_active;
}
function wpvdemo_wpml_is_active() {
	$is_active=false;
	if (( defined( 'ICL_SITEPRESS_VERSION' ) ) && ( defined( 'WPML_ST_VERSION' ) ) )
	{
		$active_languages= apply_filters( 'wpml_active_languages', NULL );
		if ((is_array($active_languages)) && (!(empty($active_languages)))) {
			$is_active=true;
		}
	}
	return $is_active;
}
function wpvdemo_wpml_is_enabled() {
	$is_enabled=false;
	
	if (( defined( 'ICL_SITEPRESS_VERSION' ) ) && ( defined( 'WPML_ST_VERSION' ) ) )
	{
		$active_plugins=apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if ((is_array($active_plugins)) && (!(empty($active_plugins)))) {
			foreach ($active_plugins as $k=>$v) {
				if ((strpos($v, 'sitepress.php') !== false)) {
					//Found
					return TRUE;
				}				
			}
		}
	}
	
	return $is_enabled;
}
function wpvdemo_layouts_is_active() {
	$is_active=false;
	if ( defined('WPDDL_VERSION') ) {
		global $wpddlayout;
		if (is_object($wpddlayout)) {
			$is_active=true;
		}
	}
	return $is_active;
}

function wpvdemo_cred_is_active() {
	$is_active=false;
	if ( defined('CRED_FE_VERSION') ) {
		//Constant defined, let's checked if we have CRED forms
		global $wpdb;
		$post_table_name = $wpdb->prefix . 'posts';
		$results = $wpdb->get_results ( "SELECT ID FROM $post_table_name WHERE post_type='cred-form'", ARRAY_A );
		if ((is_array($results)) && (!(empty($results)))) {
			$is_active=true;
		}
	}
	return $is_active;
}
function wpvdemo_cred_is_enabled() {
	$is_enabled=false;
	if ( defined('CRED_FE_VERSION') ) {
		//Constant defined, let's checked if we have CRED forms
		$is_enabled=true;
	}
	return $is_enabled;
}
function wpvdemo_determine_source_refsite() {
	
	$ret=false;
	if (defined ( 'WPVDEMO_DOWNLOAD_URL' )) {
		// Standalone import
		$download_url = WPVDEMO_DOWNLOAD_URL;
		if (! (empty ( $download_url ))) {
			// Download defined
			$parsed_url = parse_url ( $download_url );
			if (isset ( $parsed_url ['host'] )) {
				$original_host = $parsed_url ['host'];
				$ret=$original_host;
			}
		}
	}
	
	return $ret;
	
}
/**
 * Action removed on the wp_loaded hook that registers strings during import and
 * when used with FI, strings are already translated and completed in reference site.
 */
function wpvdemo_remove_runtime_st_registration() {

	if ( is_admin() ) {
		remove_action( 'wp_loaded', 'wpml_st_initialize_basic_strings' );
	}

}

function wpvdemo_remove_auto_register_types() {
	$check_import_is_done = get_option('wpv_import_is_done');
	if ('yes' != $check_import_is_done) {
		remove_filter( 'types_post_type', 'wpcf_wpml_post_types_translate', 10, 3 );
		remove_filter( 'types_taxonomy', 'wpcf_wpml_taxonomy_translate', 10, 3 );
		remove_all_filters( 'wpml_translate_single_string');
	}
}
/** Native function to check if the WordPress is installed via Bedrock Boilerplate
 * https://roots.io/bedrock/
 */
function wpvdemo_is_using_bedrock_boilerplate_framework() {
	$is_using=false;
	if (( defined('CONTENT_DIR') ) && ( defined('WP_ENV'))) {
		$contentdir=CONTENT_DIR;
		$environment=WP_ENV;
		if (('/app' == $contentdir) && (!(empty($environment)))) {
			$is_using=true;
		}		
	}
	return $is_using;	
}

/** Quick way to check if release notes exist in the site
 * 
 * @param string $url
 * @return boolean
 */
function wpvdemo_release_notes_exist( $url ) {
	
	$ch = curl_init($url);
	curl_setopt( $ch, CURLOPT_NOBODY, true); // set to HEAD request
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec( $ch );
	$valid = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 200;
	curl_close( $ch );
	
	return $valid;
	
}

/** Automatic release notes link in plugins.
 *  This works on its own
 *  No need to update any URLs or links during plugin release
 *  Links will be displayed on plugin row meta only if the plugin is officially released.
 *  @since 1.9.6
 * 
 */
function wpvdemo_plugin_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
	
	if ( ( defined('WPVDEMO_ABSPATH') ) && ( defined('WPVDEMO_VERSION') ) ) {
		if ( ( WPVDEMO_ABSPATH ) && ( WPVDEMO_VERSION ) ) {
			$this_plugin = basename( WPVDEMO_ABSPATH ) . '/views-demo.php';
			if ( $plugin_file == $this_plugin ) {
				//This is framework intaller
				$version_slug = 'frameworkinstaller-';
				$current_plugin_version = WPVDEMO_VERSION;
				$current_plugin_version_simplified = str_replace( '.', '-', $current_plugin_version );	
				
				//When releasing Framework installer, slug of version content should match with $article_slug 
				$article_slug = $version_slug.$current_plugin_version_simplified;
				$linktitle = 'Framework Installer'.' '.$current_plugin_version.' '.'release notes';
				
				//Raw URL
				//Override with Toolset domain constant if set
				if ( defined('WPVDEMO_TOOLSET_DOMAIN') ) {	
					if (WPVDEMO_TOOLSET_DOMAIN) {
						$raw_url = 'https://'.WPVDEMO_TOOLSET_DOMAIN.'/version/'.$article_slug.'/';						
						
						$wpvdemo_release_link = get_option( 'wpvdemo_release_link' );
						
						//We don't need to check if release notes exist anytime a user accesses a plugin page
						//Once the release note is proven to exist, we display
						$exists = false;
						if ( false === $wpvdemo_release_link) {
							//Option value not yet defined, we need to check- one time event only
							if ( wpvdemo_release_notes_exist( $raw_url ) ) {
								//Now exists
								$exists = true;
							}
						} elseif ( 'released' == $wpvdemo_release_link ) {
							$exists = true;
						}
						
						if ( $exists ) {							
							
							//Now released, we append this link.
							$url_with_ga = $raw_url.'?utm_source=frameworkinstallerplugin&utm_campaign=frameworkinstaller&utm_medium=release-notes-link&utm_term='.$linktitle;
							$plugin_meta[] = sprintf(
									'<a href="%s" target="_blank">%s</a>',
									$url_with_ga,
									$linktitle
									);
							if ( !($wpvdemo_release_link) ) {
								//We update to set this, one time event only.
								update_option( 'wpvdemo_release_link', 'released');
							}
							
						}
					}				
				}
			}
		}
	}

	return $plugin_meta;	

}