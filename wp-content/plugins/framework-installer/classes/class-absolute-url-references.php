<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * EMERSON: The URL reference class
 * This class will be used to provide URL and hostname adjustments of the reference site installation.
 * @since    1.8.7
*/

class Framework_Installer_URL_Reference_Class {	
	
	public function __construct(){
		
	}
	
	/**
	 * Returns something like: ref.wp-types.tld/classifieds-layouts/files/
	 * @param $file
	 */
	
	public function wpvdemo_generate_reference_blogs_dir_files_url($file) {
		 
		$blogs_dir_url='';
		$original_host = $this->wpvdemo_get_master_source_referencesite();	
		if (!(empty($original_host))) {				
			$refsite_slug = wpvdemo_get_refsites_slug_func($file);
		
			// Formulate blogs.dir files directory version (multisite version)
			$blogs_dir_url = $original_host . '/'.$refsite_slug.'/files';
		}
		return $blogs_dir_url;
		
	}
	
	/**
	 * Returns something like testplatform.local/wp-content/uploads/ for standalone
	 * Or subdomain.discover-wp.com/files/ for Discover-WP installation	 * 
	 */
	
	public function wpvdemo_generate_target_site_uploads_url() {
		
		$base_url_clean ='';
		
		$uploads_constants_of_this_site = wp_upload_dir ();
		$base_url = $uploads_constants_of_this_site ['baseurl'];
		$base_url = str_replace( parse_url( $base_url, PHP_URL_SCHEME ) . '://', '', $base_url );
		
		if (!(empty($base_url))) {
			$base_url_clean = $base_url;
		}

		return $base_url_clean;
	}

	/**
	 * Returns something like ref.wp-types.tld/classifieds-layouts	 
	 */
	
	public function wpvdemo_source_refsite_url() {
		
		$refsite_url_clean='';
		$wpvdemo_url = $this->wpvdemo_get_master_source_referencesite();			
		$refsite_slug=get_option('wpvdemo_refsites_origin_slug');
			
		if (($refsite_slug) && (!(empty($wpvdemo_url)))) {
			$refsite_url_clean = $wpvdemo_url.'/'.$refsite_slug;
		}

		return $refsite_url_clean;
	}
	
	/**
	 * Returns the target site URL
	 */
	
	public function wpvdemo_get_site_url() {
		
		$get_site_url_clean='';
		$get_site_url= site_url();
		$get_site_url_clean = str_replace( parse_url( $get_site_url, PHP_URL_SCHEME ) . '://', '', $get_site_url );
		
		return $get_site_url_clean;
	}
	
	/**
	 * Returns the canonical reference site source domain
	 * e.g. ref.wp-types.com
	 */
	
	public function wpvdemo_get_master_source_referencesite() {
		
		$original_host= '';
		if (defined ( 'WPVDEMO_DOWNLOAD_URL' )) {
			$download_url = WPVDEMO_DOWNLOAD_URL;
			if (! (empty ( $download_url ))) {
					
				// Download defined
				$parsed_url = parse_url ( $download_url );
				if (isset ( $parsed_url ['host'] )) {
					$original_host = $parsed_url ['host'];
		
				}
			}
		}
		return $original_host;		
	}
}
