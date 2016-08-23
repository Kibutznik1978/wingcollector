<?php
/* 
 * Messages.
 */

function wpvdemo_error_message($error, $wrap = false) {
    $messages = array(
        'connect' => sprintf(__('Connecting to %s failed', 'wpvdemo'),
                WPVDEMO_URL),
        'data' => __('Configuration data is corrupted', 'wpvdemo'),
        'site_configuration_missing' => __('Missing configuration data for %s',
                'wpvdemo'),
        'importing_types' => __('Error importing Types', 'wpvdemo'),
        'importing_views' => __('Error importing Views', 'wpvdemo'),
    	'wpml_tables_not_created_standalone' => __('WPML database tables are not created properly so we cannot proceed with the site installation. This is either due to your server configuration. Please reset this site, clear your browser cache or even better, restart your local server. Thanks.', 'wpvdemo'),
    	'wpml_tables_not_created_discoverwp' => __('WPML database tables are not created properly so we cannot proceed with the site installation. This is either due to server glitch, server under maintenance or server reboot. Please clear your browser and try importing this site again. Or try at some other time. Thanks.', 'wpvdemo'),
    	'importing_cred' => __('Error importing CRED', 'wpvdemo'),
    	'importing_wpml' => __('Error importing WPML', 'wpvdemo'),
    	'importing_classifieds_woocommerce' =>__('Error importing WooCommerce settings for Classifieds Site', 'wpvdemo'),
    	'importing_access' => __('Error importing Access', 'wpvdemo'),
    	'importing_classifieds_user_roles'=> __('Error importing user roles for Classifieds Site', 'wpvdemo'),
    	'importing_cred_custom_fields_classifieds' => __('Error importing CRED custom fields for Classifieds Site', 'wpvdemo'),
        'download_theme' => __('Error downloading theme %s', 'wpvdemo'),
        'download_theme_parent' => __('Error downloading parent theme %s',
                'wpvdemo'),
        'plugin_activation' => __('Error activating plugin %s: %s', 'wpvdemo'),
        'required_plugin_warning' => __('- not found in the plugins directory.'),
        'required_plugins_disabled_download' => __('You cannot download this demo because some required plugins are not available. Please download and place them in the Plugins directory, but do not activate the plugins.%s'),
    );
    if ($wrap && isset($messages[$error])) {

        switch ($error) {
            default:
                return '<div class="message error"><p>'
                    . $messages[$error]
                    . '</p></div>';
            
            case 'required_plugin_warning':
            case 'required_plugins_disabled_download':
                return '<div class="wpvdemo-error"><p>'
                    . $messages[$error]
                    . '</p></div>';
        }
    }
    return isset($messages[$error]) ? $messages[$error] : '';
}


/**
 * Admin notice. 
 */
function wpvdemo_check_if_blank_site_message() {
    $message = apply_filters('wpvdemo_blank_site_message',
            __("To be on the safe side, content import only works on fresh sites. We really don't want to accidentally delete content on live sites. To use this content importer, please install a fresh WordPress site and run it there.",
                    'wpvdemo'));
    if ($message != '') {
        echo '<div class="message error wpvdemo-warning"><p style="margin-left:40px;">'
                . $message . '</p></div>';
    }
}


/**
 * Admin notice. 
 */
function wpvdemo_requirements_themes_writeable_error_message() {
    echo '<div class="message error"><p>'
    . sprintf(__("The theme directory is not writable and the theme for the demo sites can’t be installed automatically. Please change the ownership of directory <strong>%s</strong> so that the web server can write to it.",
            'wpvdemo'), get_theme_root()) . '<br /><br /><a href="http://wp-types.com/documentation/views-demos-downloader/" target="_blank">' . __("Instructions for setting up demo sites",
            'wpvdemo') . '</a></p></div>';
}

/**
 * Admin notice. 
 */
function wpvdemo_requirements_media_writeable_error_message() {
    $wp_upload_dir = wp_upload_dir();
    if (isset($wp_upload_dir['error'])) {
        $wp_upload_dir['basedir'] = WP_CONTENT_DIR . '/uploads';
    }
    echo '<div class="message error"><p>'
    . sprintf(__("The media directory is not writable and the images for the demo sites can’t be installed. Please change the ownership of directory <strong>%s</strong> so that the web server can write to it.",
            'wpvdemo'), $wp_upload_dir['basedir']) . '<br /><br /><a href="http://wp-types.com/documentation/views-demos-downloader/" target="_blank">' . __("Instructions for setting up demo sites",
            'wpvdemo') . '</a></p></div>';
}

/**
 * Admin notice. 
 */
function wpvdemo_requirements_dirs_writeable_error_message() {
    $wp_upload_dir = wp_upload_dir();
    if (isset($wp_upload_dir['error'])) {
        $wp_upload_dir['basedir'] = WP_CONTENT_DIR . '/uploads';
    }
    echo '<div class="message error"><p>'
    . sprintf(__("The theme and media directories are not writable and the theme and images for the demo sites can’t be installed automatically. Please change the ownership of directory <strong>%s</strong> and <strong>%s</strong> so that the web server can write to them.",
            'wpvdemo'), get_theme_root(), $wp_upload_dir['basedir']) . '<br /><br /><a href="http://wp-types.com/documentation/views-demos-downloader/" target="_blank">' . __("Instructions for setting up demo sites",
            'wpvdemo') . '</a></p></div>';
}
function wpvdemo_requirements_wpcontent_writeable_error_message() {

	if (defined('WPVDEMO_WPCONTENTDIR')) {
		$user_wpcontent=WPVDEMO_WPCONTENTDIR;		
	
		echo '<div class="message error"><p>'
			. sprintf(__("The WordPress wp-content directory is not writable and the modules for the demo sites can’t be installed automatically. Please assign proper permissions and ownership of the directory <strong>%s</strong> so that the web server can write to them. If you have no idea about this, please check with your web host.",
					'wpvdemo'), $user_wpcontent).'<br /><br /><a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">' . __("Read WordPress guide on properly setting file permissions",
            'wpvdemo') . '</a></p></div>';
	
	}
}
/**
 * Admin notice. 
 */
function wpvdemo_requirements_zip_error_message() {
    echo '<div class="message error"><p>'
    . __("PHP ZipArchive extension missing.",
            'wpvdemo') . '</p></div>';
}
/**
 * Admin notice.
 */
function wpvdemo_disabled_native_PHP_remote_parsing_functions_message() {
	echo '<div class="message error"><p>'
			. __("Framework Installer plugin requires PHP allow_url_fopen to be enabled. Please enabled it in your php.ini. Contact your webhost if you are not sure how to change this.",
					'wpvdemo') . '</p></div>';
}
/**
 * Site title HTML
 */
function wpvdemo_generate_site_title_html($site_title,$wpvdemo) {
	
	$site_title_html=$site_title;
	if (isset($wpvdemo['tutorial_url'])) {
		$tut_url= $wpvdemo['tutorial_url'];
		if (is_string($tut_url)) {
			$tut_url=trim($tut_url);
			if (!(empty($tut_url))) {
				//Not empty, validate
				if ('#' != $tut_url) {
					//URL
					//Append Google analytics
					$tut_url= apply_filters('wpvdemo_filter_tutorial_url',$tut_url,'welcome-box',$site_title);
					$site_title_html='<a href="'.$tut_url.'">'.$site_title.'</a>';
				}
			}
		}
	}	
	return $site_title_html;
}
/**
 * One dashboard message to rule them all.
 * Customized Welcome Panel (Framework installer 1.8.2 ++ )
 * Merged with Discover-WP dashboard display since 1.8.4
 */
function wpvdemo_new_welcome_panel() {
	
		/**Check if we have dashboard HTML already generated */
		$wpvdemo_master_dashboard_html= get_option('wpvdemo_master_dashboard_html');
		
		if (!($wpvdemo_master_dashboard_html)) {
			//Dashboard HTML is still not generated
	        //Define data
	        global $wpvdemo;
	
	        if ( isset( $wpvdemo['ID'] ) ) {
	            $site_info = wpvdemo_get_site_settings( $wpvdemo['ID'] );
	            $site_title = $wpvdemo['title'];
	            $site_tut_intro_text = $site_info->tutorial_intro_text;
	            $site_tut_intro_text = (string)$site_tut_intro_text;
	            $site_tut_intro_text = str_replace( "\\", "", $site_tut_intro_text );
	            $toolset_helper_icon = WPVDEMO_RELPATH . '/images/Toolset-help-character.png';
	
	            /** Robot icon handler */
	            /** Start */
	            global $showdiscover_dashboard;
	            $robot_icon_exported=false;
	            if (isset($site_info->show_robot_icon)) {
	            	//Setting exported
	            	$robot_icon_exported=true;
	 				$robot_icon_class='wpvlive-robot';
	            } else {
	            	$robot_icon_class='wpvlive-norobot';
	            }
	            /** End */
	            //Retrieved message title
	            $message_title = $site_info->message_title;
	            $message_title = (string)$message_title;
	            $site_title_html= wpvdemo_generate_site_title_html($site_title,$wpvdemo);
	            
	            //Declare copyright notice
	            $copyrightnotice  = '<p>'.__('This site is now an exact copy of the Toolset reference site','wpvdemo').' - "'.$site_title_html.'". '.__('You can edit everything on this site and use it as the basis of your client projects.','wpvdemo').'</p>';
	            $copyrightnotice  .= '<p>'.__("Please note that text and graphics in this site needs to be completely replaced if you use it for a client site. The images used here are stock images that don't have a license for multi-site usage.","wpvdemo").'</p>';
	            
	            //Add text to disable Framework installer
	            $disable_framework_installer  = '<form id="wpvdemo_client_fi_confirmation_form" action="" method="post">';
	            $disable_framework_installer  .= '<label><input type="checkbox" id="wpvdemo_read_understand_checkbox" name="wpvdemo_fi_read_understand" value="yes"> '.__('I read and understand','wpvdemo').'</label>';            
	            $disable_framework_installer  .= '<input type="submit" id="wpvdemo_read_understand_button" class="button button-primary" value="Disable Framework Installer and customize this site"></form>';
	            
	            //Handle defaults
	            if ( empty( $site_tut_intro_text ) ) {
	                $site_tut_intro_text = '<p>' . __( "We've built this site using", "wpvlive" ) . ' ' . '<a href="http://wp-types.com/">Toolset</a>' . ' ' . __( "and no coding at all. It's fully functional with sample content and everything configured. You can edit content and experiment with new custom types, Views and View Templates.", "wpvlive" ) . '</p>';	               
	            }
	            $message_title = trim( $message_title );
	            if ( empty( $message_title ) ) {
	                $message_title = __( "Welcome to your test site!", "wpvlive" );
	            }
	
	            /**Let's generate the dashboard HTML given all the above information */
	            $the_dashboard_html  ='';
	            $the_dashboard_html .= '<div class="'.$robot_icon_class.'">';
	            $the_dashboard_html .= '<div  class="wpvlive-image">';
	            $the_dashboard_html .= '<img src="'.$toolset_helper_icon.'"/>';
	            $the_dashboard_html .= '</div>';
	            $the_dashboard_html .= '<div class="wpvlive-container">';
	            $the_dashboard_html .= '<h2 id="tut_header_panel" class="wpvlive-header">'.$message_title.'</h2>';
	            $the_dashboard_html .= '<div class="wpvlive-content">';
	            
	            //Allow short description text to be filtered
	            $site_tut_intro_text= apply_filters('wpvdemo_filter_tutorial_shortdescription',$site_tut_intro_text,'welcome-box','get-started');
	            if (!($showdiscover_dashboard)) {
	                //Standalone
	                $site_tut_intro_text .= $copyrightnotice;
	                $site_tut_intro_text .= $disable_framework_installer;
	            }
	            $the_dashboard_html .= $site_tut_intro_text; 
	            $the_dashboard_html .= '</div>';
	            $the_dashboard_html .= '</div>';
	            $the_dashboard_html .= '<a class="wpvlive-toggle expanded">Minimize<span class="dashicons dashicons-arrow-up"></span></a>';
		        $the_dashboard_html .= '</div>';
	
		        /**Let's run a search and replacement of any images or reference site URLS in these dashboard HTML
		         * And ensure they use local/target site paths 
		         */
		        
		        //Retrieve the media path equivalence
		        $wpvdemo_source_target_media_equivalence= get_option('wpvdemo_source_target_media_equivalence');
		        if ((is_array($wpvdemo_source_target_media_equivalence)) && (!(empty($wpvdemo_source_target_media_equivalence)))) {
		        	
		        	$source_media_path= key($wpvdemo_source_target_media_equivalence);
		        	$target_media_path = reset($wpvdemo_source_target_media_equivalence);	        	
		        	$the_dashboard_html= str_replace($source_media_path, $target_media_path, $the_dashboard_html);	        	
		        	
		        }
		        
		        /**Save to database so we won't be running these all the time*/
		        update_option('wpvdemo_master_dashboard_html',$the_dashboard_html);
		        
		        //Render
		        echo $the_dashboard_html;
	
		        
	        }
		} else {
			//Already generated, use it..
			echo $wpvdemo_master_dashboard_html;
		}
}