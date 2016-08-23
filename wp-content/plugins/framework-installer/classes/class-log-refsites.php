<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * EMERSON: The refsite log class
 * This class will be used to log installed reference sites to remote Toolset server.
 * @since    1.9.5
*/

class Framework_Installer_Log_Refsites_Class {	
	
	public function __construct(){
		
	}

	/**
	 * Returns something like: 'Classifieds with Layouts' or any official ref site name
	 * @param none
	 * @return boolean
	 */
	
	public function wpvdemo_exclude_implementation() {
		
		global $frameworkinstaller;
		$implemented = true;
		
		//No implementation if WPVDEMO_DONT_LOG_TESTS is set to TRUE
		if ( defined('WPVDEMO_DONT_LOG_TESTS') ) {
			if ( true === WPVDEMO_DONT_LOG_TESTS) {
				$implemented =false;
				return $implemented;
			}
		}
		
		//No implementation on Discover-WP multisite
		$is_discoverwp = $frameworkinstaller->is_discoverwp();
		
		if ($is_discoverwp) {
			$implemented =false;
			return $implemented;			
		}
		
		return $implemented;		
	}
	
	/**
	 * Returns something like: 'Classifieds with Layouts' or any official ref site name
	 * @param $file
	 * @return string | boolean
	 */
	
	public function wpvdemo_generate_site_name_given_file($file) {
		
		global $frameworkinstaller;
		$ret = false;
		
		//Get slug from file
		$refsite_slug = wpvdemo_get_refsites_slug_func($file);
		
		//Get ID from refsite slug
		$refsite_id = $frameworkinstaller->wpvdemo_get_id_given_refsiteslug( $refsite_slug );
		
		//Finally get sitename given site ID
		$refsite_name = $frameworkinstaller->wpvdemo_get_sitespecific_info_given_id( $refsite_id , 'title' );
		
		if (( is_string( $refsite_name ) ) && ( !(empty( $refsite_name ))) ) {
			
			$ret = $refsite_name;
		}
		
		return $ret;
		
	}
	
	/**
	 * Returns something like: 'multilingual', 'non-multilingual'
	 * @param none
	 * @return string 
	 */
	
	public function wpvdemo_get_language_version_installed() {
		
		$version='non-multilingual';
		
		if (wpvdemo_wpml_is_enabled()) {
			$version='multilingual';
		}
		
		return $version;
	}
	
	/**
	 * Post data to remote server
	 */
	
	public function wpvdemo_remote_post_data($refsite_type_name,$refsite_language_version,$refsite_target_url ) {		
		
		if ( ( is_string( $refsite_type_name ) ) && ( is_string( $refsite_language_version) ) && ( is_string( $refsite_target_url ) ) ) {
			
			//Generate $args
			
			$args['body'] = array(
					'action'    			=> 'framework_installer_logrefsites',
					'refsite_name'  		=> $refsite_type_name,
					'refsite_langversion'  	=> $refsite_language_version,
					'refsite_target_url'    => $refsite_target_url
			);
			
			//API URL
			if ( defined('WPVDEMO_REMOTE_LOG_URL') ) {
				
				$api_url= WPVDEMO_REMOTE_LOG_URL;
				$response = wp_remote_post( $api_url, $args );				
				
			}		
				
		}

		
	}

}
