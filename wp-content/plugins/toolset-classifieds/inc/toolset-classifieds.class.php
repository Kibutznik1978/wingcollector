<?php

if ( !class_exists( 'Toolset_Classifieds' ) ) {

    class Toolset_Classifieds
    {
        protected $plugin_slug = 'toolset-classifieds';
        protected $text_domain = 'toolset_classifieds';

        protected $_Class_Toolset_Classifieds_MessageSystem = NULL;
        protected $_Class_Toolset_Classifieds_PackageOrder = NULL;
        protected $_Class_CRED_WPML_Integration = NULL;
        protected $_implements_WPML = true;

        public function __construct()
        {
            add_action( 'plugins_loaded', array( $this, 'init'), 11 );

            if ( is_admin() ) {

            }

            if ( defined('TOOLSET_CLASSIFIEDS_MESSAGE_SYSTEM') && TOOLSET_CLASSIFIEDS_MESSAGE_SYSTEM ) {
                require_once TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/message_system.class.php';
                $this->_Class_Toolset_Classifieds_MessageSystem = new Toolset_Classifieds_MessageSystem;
            }

            if ( defined('TOOLSET_CLASSIFIEDS_PACKAGE_ORDER') && TOOLSET_CLASSIFIEDS_PACKAGE_ORDER ) {
                require_once TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/package_order.class.php';
                $this->_Class_Toolset_Classifieds_PackageOrder = new Toolset_Classifieds_PackageOrder();
            }

            if ( defined('TOOLSET_CLASSIFIEDS_WPML') && TOOLSET_CLASSIFIEDS_WPML ) {
                $this->_implements_WPML = true;
                global $Class_CRED_WPML_Integration;
                if (is_object($Class_CRED_WPML_Integration)) {
                    $this->_Class_CRED_WPML_Integration = $Class_CRED_WPML_Integration;
                }
            }

        }

        function init() {
            // Check if WPML is active.
            if ( defined( 'ICL_SITEPRESS_VERSION' ) || ( defined ('ICL_PLUGIN_INACTIVE') && !ICL_PLUGIN_INACTIVE ) ) {
                // Check if CRED WPML integration is active. If not display warning message
                if (!defined('TOOLSET_EXT_CRED_WPML_VERSION')) {
                    if (!function_exists('is_multisite') || !is_multisite()) {
                    	//Not multisite e.g. Discover-WP
                        add_action('admin_notices', array($this, '_no_cred_wpml_integration'));                        
                        return false;
                    }
                }
            }

            /* general usage and initial conditions */
            add_action( 'init', array( $this, 'classifieds_register_session' ) );
            add_action( 'init',array( $this, 'classifieds_remove_get_filtered_comments_wcml' ), 999);
	        add_action( 'get_layout_id_for_render', array( $this, 'get_layout_id_for_render_callback' ), 888, 2 );

            //Custom shortcodes
            add_shortcode('login-form', array($this, 'classifieds_login_form'));           
            add_shortcode('lost-password-form', array($this, 'classifieds_lost_password_form'));
	        add_shortcode('classifieds_logout_url', array($this, 'classifieds_logout_shortcode_func'));
            add_shortcode('classifieds-currency', array($this, 'classifieds_get_woocommerce_currency'));
            add_shortcode('classifieds-userdata', array($this, 'classifieds_get_userdata'));
            add_shortcode('classifieds-uploads-path', array($this, 'classifieds_uploads_path_func'));
            add_shortcode('classifieds-contact-advertiser', array($this, 'classifieds_contactadvertiser_shortcode_func'));
            add_shortcode('classifieds-page-url', array($this, 'classifieds_get_page_by_title'));
            
            /*Views temporary patch to make the parametric search form interpret the url parameter
            passed and have the same category pre-selected

            -Remove this hook, once a permanent fix is added to Views.
            */
            add_action('wp_loaded', array($this, 'classifieds_fix_parametric_form_category_preselected'),999);

            //Update Types Access custom groups
            add_action('wp_loaded', array($this, 'classifieds_refresh_taccess_after_import'),999);

            //CRED related hooks
            add_action('cred_save_data_form_edit-product',  array($this, 'classifieds_cred_save_data_edit_product_function'),10,2);
            add_action('cred_commerce_after_send_notifications_form_add-new-free-ad', array($this, 'classifieds_cred_commerce_after_send_notifications_add_new_free_ad'),10,1);
            add_action('cred_commerce_after_send_notifications_form_add-new-premium-ad', array($this, 'classifieds_cred_commerce_after_send_notifications_add_new_premium_ad'),10,1);
            add_action('cred_submit_complete_form_edit-product',array($this, 'classifieds_cred_duplicate_entries_to_all_languages'),10,2);
            add_shortcode('classifieds-listing-info', array($this, 'classifieds_get_listing_info_by_url_param'));

            //WooCommerce
            add_filter( 'woocommerce_order_item_name', array($this,'classifieds_remove_product_link_in_order' ),10,2);
            add_filter( 'woocommerce_login_redirect', array($this,'classifieds_woocommerce_login_redirect' ),10,2);
            remove_filter( 'show_admin_bar', 'wc_disable_admin_bar', 10, 1 );
            remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
            add_filter( 'show_admin_bar',array( $this,'classifieds_show_admin_bar_loggedin' ),10,1);

            //Filter to exclude listings without expiration from being included in search
            add_filter( 'the_posts',array( $this,'classifieds_searchfilter' ), 99, 1 );

            //Customize locations link in single listing page to link with Views parametric query string.
            add_filter('term_link',array($this,'classifieds_customize_locations_tax_link'),99,3);
            
            //Fix WooCommerce 2.3.4 - Menu voices take a different name
            add_action('wp',array($this, 'remove_filter_for_wc_endpoint_title'),999);
            
            //Upgrade subscriber role to custom role after completed orders            
            add_action('cred_commerce_after_order_completed_form_add-new-ad-package', array($this, 'classifieds_upgrade_subscriber_role'), 10, 1);
            add_action('cred_commerce_after_order_completed_form_add-new-free-ad', array($this, 'classifieds_upgrade_subscriber_role'), 10, 1);
            add_action('cred_commerce_after_order_completed_form_add-new-premium-ad', array($this, 'classifieds_upgrade_subscriber_role'), 10, 1);

            //Auto-assign layouts when new listings are created         
            /** Free Ads */   
            add_action('cred_save_data_form_add-new-free-ad', array($this, 'classifieds_auto_assign_layouts_listings'),10,2);
            
            /** Premium ads */
            add_action('cred_save_data_form_add-new-premium-ad', array($this, 'classifieds_auto_assign_layouts_listings'),10,2);
            
            /** Add another premium ads from package */
            add_action('cred_save_data_form_add-another-premium-ad', array($this, 'classifieds_auto_assign_layouts_listings'),10,2);
            
            //Auto-assign layotus when new messages are created
            add_action('cred_save_data_form_new-message', array($this, 'classifieds_auto_assign_layouts_for_messages'),10,2); 
            add_action('cred_save_data_form_reply-message', array($this, 'classifieds_auto_assign_layouts_for_messages'),10,2);
            
            //Backward compatibility
            add_action('ddl-layouts-render-start-post-content',array(&$this,'preventOverrideLayoutsContentTemplate'), 1 );
            add_action('ddl-layouts-render-end-post-content',array(&$this,'restoreOverrideLayoutsContentTemplate'), 1 );
            
        }
        
        public function preventOverrideLayoutsContentTemplate(){
        	global $WPV_templates,$post;
        	if (is_object($post)) {
        		$post_type=$post->post_type;
        		if ('message' == $post_type) {
        			remove_filter('the_content', array($WPV_templates, 'the_content'), 1);
        		}
        	}
        }
        
        public function restoreOverrideLayoutsContentTemplate(){
        	global $WPV_templates,$post;        	
        	if (is_object($post)) {
        		$post_type=$post->post_type;
        		if ('message' == $post_type) {
        			add_filter('the_content', array($WPV_templates, 'the_content'), 1, 1);
        		}
        	}
        }
        
        function _no_cred_wpml_integration() {
            if ($this->implements_WPML()) {
                ?>
                <div class="message error">
                    <p><?php printf(__('Toolset Classifieds plugin is enabled but not effective. It requires <a href="%s">CRED and WPML Integration</a> in order to work.', $this->text_domain),
                            'https://wpml.org/'); ?></p></div>
            <?php
            }
        }

        public function implements_message_system() {

            return false;

            if (is_object($this->_Class_Toolset_Classifieds_MessageSystem)) {
                return true;
            }
            return false;

        }

        public function implements_package_order() {

            if (is_object($this->_Class_Toolset_Classifieds_PackageOrder)) {
                return true;
            }
            return false;

        }

        public function implements_WPML() {

            return $this->_implements_WPML;

        }
        
        public function get_slug_by_layoutid($layout_id) {        
        	
        	global $wpdb;
        
        	//Posts table
        	$posts_table= $wpdb->prefix."posts";
        
        	//Query post table for the layout slug
        	$layout_slug = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM $posts_table WHERE ID = %d AND post_type ='dd_layouts'",$layout_id));
        		
        	if (!(empty($layout_slug))) {
        		return $layout_slug;
        	} else {
        		return FALSE;
        	}
        }
        
        public function layouts_get_id_by_slug($layouts_slug) {
        	global $wpdb;
        	$posts_table=$wpdb->prefix."posts";
        	$layouts_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $posts_table WHERE 	post_name = %s AND post_type ='dd_layouts'",$layouts_slug));
        
        	if (!(empty($layouts_id))) {
        		return $layouts_id;
        	} else {
        		return FALSE;
        	}
        }
        
	    public function get_layout_id_for_render_callback( $id, $args ){

	    	/** So that these pages or layouts will still work after import */   	    	
	    	/** This filter passes an ID */
	    	/** But to make this compatible after import, we need to check by slugs instead of ID */
	    	
	    	$equivalent_slug_of_id= $this->get_slug_by_layoutid($id);	    	
	    	
		    if ( !is_user_logged_in() ){
			    switch ($equivalent_slug_of_id){
				    case 'my-account': 
				    	
				    	/** Let's return the 'login' layout */
				    	$id = $this->layouts_get_id_by_slug('login');
				    	$id= intval($id);                        
                        break;
                        
				    case 'regular-page-login-required': 
				    	
                        global $wp;
                        if ( isset( $wp->query_vars['lost-password'] ) ) {

                            /** Let's return 'lost-password' layout */
                            $id = $this->layouts_get_id_by_slug('lost-password');
                            $id= intval($id);

                        } else {
                        	
				    		/** Let's return the 'login' layout */
				    		$id = $this->layouts_get_id_by_slug('login');
				    		$id= intval($id);  
                        }
                        
                        break;
			    }
		    }
		    
		    /** CRED form handling, use slugs instead of IDS */		    
		    /** Check if 'cred-edit-form' is set in $_GET */
		    
		    if( isset($_GET['cred-edit-form'])) {

		    	/** Set,retrieved */
		    	$cred_edit_form_passed= $_GET['cred-edit-form'];
		    	$cred_edit_form_id =intval($cred_edit_form_passed);
		    	if ($cred_edit_form_id > 0) {

		    		/** let's get the slug of this form ID */
		    		$equivalent_slug_of_form= $this->get_formslug_by_id($cred_edit_form_id);
		    		
		    		if ('edit-product' == $equivalent_slug_of_form) {
		    			
		    			/** Return copy-of-ad-single Layouts */
		    			$id = $this->layouts_get_id_by_slug('copy-of-ad-single');	
		    			$id= intval($id);	    			
		    		}		    		
		    	}		    	
		    }
		    
		    //Let's catch attachments	
		   	    
		    global $post;
		    if (is_object($post)) {
		       if (isset($post->post_type))	{		       	
		       	$posttype=$post->post_type;
		       	if ('attachment' == $posttype) {
		       		remove_filter( 'the_content', 'prepend_attachment' );
		       		//Attachment
		       		$id_layout = $this->layouts_get_id_by_slug('attachment');
		       		$id_layout= intval($id_layout);
		       		if ($id_layout > 0 ) {
		       			$id =$id_layout;
		       		}		       		
		       	}
		       }		    	
		    }
		    
		    return $id;
		}
		
		public function get_formslug_by_id($form_id) {
			 
			global $wpdb;
		
			//Posts table
			$posts_table= $wpdb->prefix."posts";			
			$form_slug = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM $posts_table WHERE ID = %d AND post_type ='cred-form'",$form_id));
		
			if (!(empty($form_slug))) {
				return $form_slug;
			} else {
				return FALSE;
			}
		}
		
        public function classifieds_register_session() {
            if (!session_id())
                @session_start();

            //Flushing rewrite rules once immediately after site first load after import
            $flush_rewrite_after_import = get_option( 'classifieds_flush_rewrite_after_import' );
            $import_done = get_option( 'wpv_import_is_done' );

            $site_url = get_site_url();
            //Run if import is done AND not yet flushed
            if (($site_url != $flush_rewrite_after_import) && ($import_done == 'yes')) {
                //Not yet flushed
                global $wp_rewrite;
                $wp_rewrite->flush_rules(false);
                //Update option
                $success_updating = update_option( 'classifieds_flush_rewrite_after_import', $site_url );
            }
        }

        public function classifieds_remove_get_filtered_comments_wcml() {

            if (!(is_user_logged_in())) {
                //Remove this filter when running WPML and user is not logged-in
                global $woocommerce_wpml;
                if (is_object($woocommerce_wpml)) {
                    $order_class = $woocommerce_wpml->orders;
                    remove_filter('the_comments', array($order_class, 'get_filtered_comments'));
                }
            }
        }

        /**
         * Auxiliary Method: Returns the canonical ID of the slug
         * @param $slug
         * @param $post_type
         * @return bool|mixed|null|string
         */
        public function classifieds_aux_query_special_pages($slug,$post_type) {

            global $wpdb;
            $sql = "
		SELECT ID, post_name, post_parent, post_type
		FROM $wpdb->posts
		WHERE post_name IN ($slug)
		AND post_type IN ($post_type)
		";

            $pages = $wpdb->get_results( $sql, OBJECT_K );
            $en_post_id ='';

            //Handle WPML multilingual implementation
            if ((is_array($pages)) && (!(empty($pages)))) {
                $result_qty=count($pages);
                if ($result_qty > 1) {
                    //Check for multilingual pages
                    if (function_exists('icl_object_id')) {

                        //Get post ID in English, original language
                        $top_result= reset($pages);
                        if (isset($top_result->ID)) {

                            $post_id=$top_result->ID;
                            //Return the English ID
                            $en_post_id=icl_object_id($post_id, $post_type, true,'en');

                        }

                    }
                } elseif (1 == $result_qty ) {

                    //Non-multilingual
                    $top_result= reset($pages);
                    if (isset($top_result->ID)) {
                        $en_post_id =$top_result->ID;
                    }
                }

            }

            return $en_post_id;
        }

        /**
         * Auxiliary Method: Query Methods, returns permalink
         * @param $args
         * @param $posttype
         * @return string
         */
        public function classifieds_wp_query_custom($args,$posttype) {

            $url ='';
            $result = new WP_Query( $args );

            if ((is_object($result)) && (!empty($result))) {

                $post_count= $result->post_count;
                //Can be only one
                if (1 == $post_count) {
                    if (isset($result->post->ID)) {
                        $id=$result->post->ID;
                        $translated_page_id = $this->_classifieds_lang_id($id,$posttype);
                        $url = esc_url(get_permalink($translated_page_id));
                    }
                }
            }
            return $url;
        }

        /**
         * Outputs Logout URL in account pages
         * usage: [classifieds_logout_url]
         * Emerson: Revised with multilingual support (since 0.3.2)
         * @param $atts
         * @return string
         */
        public function classifieds_logout_shortcode_func($atts) {
        	
        	$after_import_logout_url='#';
        	
        	// Step1, Get WooCommerce My Account page ID
        	$myaccount_pageid= get_option ( 'woocommerce_myaccount_page_id' );
        	$myaccount_pageid= intval($myaccount_pageid);
        	
        	// Step2, get WooCommerce logout endpoint
        	$wc_logout_endpoint= get_option( 'woocommerce_logout_endpoint' );
        	$wc_logout_endpoint=trim($wc_logout_endpoint);
        		
        	if (($myaccount_pageid > 0) && (!(empty($wc_logout_endpoint)))) {
        		
        		global $wpdb;
        		
        		//Non-multilingual backward compatibility, default English
        		$wc_my_account_url = get_permalink ( $myaccount_pageid );
        		$wc_my_account_url=strtok($wc_my_account_url,'?');
        		$wc_my_account_url = rtrim ( $wc_my_account_url, '/' );
        		$after_import_logout_url = $wc_my_account_url . '/'.$wc_logout_endpoint.'/';
        		
        		//Step3, get current lang
        		$original_lang= 'en';
        		
        		//Multilingual support
        		if (( defined('ICL_LANGUAGE_CODE') ) && (($this->implements_WPML()))) {
        			$current_lang=ICL_LANGUAGE_CODE;        			
        			if ($current_lang != $original_lang) {
        				$k = $current_lang;
        				
     					//Non-english pages, go further..   
        				$translated_logout_endpoint= $wc_logout_endpoint;
        				$strings_table = $wpdb->prefix.'icl_strings';
        				$string_translation_table = $wpdb->prefix.'icl_string_translations';
        							 
        				//Step 8: Get the equivalent translated My Account page ID
        				$translated_myaccount_page_id= apply_filters( 'wpml_object_id', $myaccount_pageid, 'page', FALSE, $k );
        							 
        				//Step 10: Get equivalent translated My Account permalink in this language
        				$translated_wc_my_account_url = get_permalink ( $translated_myaccount_page_id );
        				$translated_wc_my_account_url=strtok($translated_wc_my_account_url,'?');
        				$translated_wc_my_account_url = rtrim ( $translated_wc_my_account_url, '/' );
        				        						 
        				//Step12: Compose URL
        				$logout_string_id_orig = $wpdb->get_var($wpdb->prepare("SELECT id FROM $strings_table WHERE context='WordPress' AND value = %s",	$wc_logout_endpoint));
        				$logout_string_id_new = $wpdb->get_var($wpdb->prepare("SELECT id FROM $strings_table WHERE context='WooCommerce Endpoints' AND value = %s",	$wc_logout_endpoint));
        				
        				$logout_string_id_orig = intval($logout_string_id_orig);
        				$logout_string_id_new = intval($logout_string_id_new);
        				
        				if ($logout_string_id_orig > 0) {
        					//Backward compatibility, support old context if its available
        					$logout_string_id = $logout_string_id_orig;
        				} else {
        					//Use 'WooCommerce Endpoints' context
        					$logout_string_id = $logout_string_id_new;
        				}
        				$logout_string_id =intval($logout_string_id );
        				$translated_logout_endpoint_db = $wpdb->get_var($wpdb->prepare("SELECT value FROM $string_translation_table WHERE string_id=%d",$logout_string_id));
        				
        				if ((isset($translated_logout_endpoint_db)) && (!(empty($translated_logout_endpoint_db)))) {
        					$translated_logout_endpoint = $translated_logout_endpoint_db;        					
        				}        				
        				$after_import_logout_url = $translated_wc_my_account_url . '/'. $translated_logout_endpoint.'/';			
          			}        			
        		}
        	}
        	return $after_import_logout_url;       	
        	
        }

	    /**
	     * restrict access to logged in users in some pages
	     * @param array $atts
	     * @param null $content
	     * @return string
	     */
	    public function classifieds_login_form($atts=array(), $content=null) {
		    ob_start();
		    wc_get_template( 'myaccount/form-login.php' );
		    return ob_get_clean();
	    }

        /**
         * @return string
         */
        public static function classifieds_lost_password_form() {

            ob_start();
            WC_Shortcode_My_Account::lost_password();
            return ob_get_clean();
        }

        //for adding a currency symbol and description to the 'add new product' CRED form as defined in WooCommerce
        public function classifieds_get_woocommerce_currency($atts) {
            extract(
	            shortcode_atts( array(), $atts )
            );
            $result = '';
	        if (!isset($atts['get'])){
		        $atts['get'] = 'currency';
	        }
            switch ($atts['get']){
                case 'currency_symbol':
                    $result = get_woocommerce_currency_symbol();
                    break;
                case 'currency':
                    $result = get_woocommerce_currency();
                    break;
            }
            return $result;
        }

        //show the user info based on an email
        public function classifieds_get_userdata($atts) {
            extract(
	            shortcode_atts( array(), $atts )
            );
            $result = '';
	        if (!isset($atts['field_name'])){
		        $atts['field_name'] = 'display_name';
	        }
	        if (!isset($atts['email_user'])){
		        $atts['email_user'] = '';
	        }
            $user = get_user_by('email', $atts['email_user']);
            if ($user){
                switch ($atts['field_name']){
                    case 'display_name':
                        $result = $user->display_name;
                        break;
                }
            }
            return $result;
        }

        /**
         * WPML integration - auxiliary functions
         *
         **/
        //get IDs by language
        protected function _classifieds_lang_id($id='', $type='') {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                return $Class_CRED_WPML_Integration->wpml_lang_id($id, $type);
            } else {
                return $id;
            }
        }
        // Links to specific elements
        protected function _classifieds_link_to_element($element_id, $element_type='page', $link_text='', $optional_parameters=array(), $anchor='', $echoit = false) {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                return $Class_CRED_WPML_Integration->wpml_link_to_element($element_id, $element_type, $link_text, $optional_parameters, $anchor, $echoit);
            } else {
                $out = '';
                if('page' == $element_type){
                    $out = '<a href="'.get_permalink($element_id).'">';
                    if($anchor){
                        $out .= $anchor;
                    }else{
                        $out .= get_the_title($element_id);
                    }
                    $out .= '</a>';
                }
                return $out;
            }
        }

        //Classifieds copy custom field values to all translations
        /*Use this if you notice some glitches in WPML custom field values copy mode*/
        protected function _classifieds_copy_cf_values_to_translations($post_id, $cf_value, $cf_db_name, $post_type) {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                $Class_CRED_WPML_Integration->wpml_copy_cf_values_to_translations($post_id, $cf_value, $cf_db_name, $post_type);
            }
        }

        //Copy term values to all translations
        public function _classifieds_copy_term_values_to_translations($post_id, $updated_terms, $taxonomy, $post_type) {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                $Class_CRED_WPML_Integration->wpml_copy_term_values_to_translations($post_id, $updated_terms, $taxonomy, $post_type);
            }
        }

        // reset duplicate flag so a post/page/custom post can be translated independently
        protected function _classifieds_reset_duplicate_flag($post_id) {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                $Class_CRED_WPML_Integration->wpml_reset_duplicate_flag($post_id);
            }
        }

        protected function _classifieds_duplicate_on_publish($post_id) {
            global $Class_CRED_WPML_Integration;
            if (is_object($Class_CRED_WPML_Integration)) {
                //duplicate post to WPML translation
                $Class_CRED_WPML_Integration->wpml_duplicate_on_publish($post_id);

                $listing_location_updated_terms = wp_get_object_terms($post_id, 'location');
                $Class_CRED_WPML_Integration->wpml_copy_term_values_to_translations($post_id, $listing_location_updated_terms, 'location', 'listing');
            }
        }

        public function classifieds_fix_parametric_form_category_preselected() {

            global $wpdb;
            $posttable=$wpdb->prefix."posts";

            //Get ID of Ad list View
            $ad_list_view_id = $wpdb->get_var("SELECT ID FROM $posttable WHERE post_name='ads-list' AND post_type='view'");

            if (isset($ad_list_view_id)) {
                $view_setting_adlist=get_post_meta($ad_list_view_id,'_wpv_settings',TRUE);
                if (!(isset($view_setting_adlist['taxonomy-location-attribute-url-format']))) {
                    $view_setting_adlist['taxonomy-location-attribute-url-format']=array('0'=>'slug');
                    $success_updating=update_post_meta($ad_list_view_id, '_wpv_settings', $view_setting_adlist);
                }
                if (!(isset($view_setting_adlist['taxonomy-listing_cat-attribute-url-format']))) {
                    $view_setting_adlist['taxonomy-listing_cat-attribute-url-format']=array('0'=>'slug');
                    $success_updating=update_post_meta($ad_list_view_id, '_wpv_settings', $view_setting_adlist);
                }

            }

        }
        //Refresh Types Access custom groups on first load
        public function classifieds_refresh_taccess_after_import() {

            if (defined('ICL_SITEPRESS_VERSION')) {

                $custom_groups_updated=get_option('classifieds_access_custom_group_updated');

                if ($custom_groups_updated != 'yes') {
                    //Run on WPML versions only
                    //Get existing custom groups available for Access
                    global $wpdb;
                    $post_meta_table = $wpdb->prefix.'postmeta';
                    $results = $wpdb->get_results("SELECT post_id,meta_value FROM $post_meta_table WHERE meta_key='_wpcf_access_group'", ARRAY_A);

                    if (!(empty($results))) {

                        $custom_groups_array=array();

                        foreach ($results as $key=>$inner_array) {
                            $custom_groups_array[$inner_array['post_id']]=$inner_array['meta_value'];
                        }
                        $dummy=$custom_groups_array;

                        if ((is_array($custom_groups_array)) && (!(empty($custom_groups_array)))) {

                            $cf_db_name='_wpcf_access_group';
                            $post_type='message';

                            //Loop and set
                            foreach ($custom_groups_array as $post_id=>$cf_value) {
                                $this->_classifieds_copy_cf_values_to_translations($post_id,$cf_value,$cf_db_name,$post_type);
                            }

                            update_option('classifieds_access_custom_group_updated', 'yes' );
                        }
                    }
                }
            }
        }

        /**
         * create and edit listings specific functions
         *
         **/
        public function classifieds_cred_save_data_edit_product_function($post_id, $form_data){
            //set the post to translate independently
            $this->_classifieds_reset_duplicate_flag($post_id);

            //update price fields when updating on translated form
            if (isset($_POST['wpcf-price'])) {
                $price_posted=$_POST['wpcf-price'];
                $this->_classifieds_copy_cf_values_to_translations($post_id,$price_posted,'wpcf-price','listing');
            }

        }
        //duplicate posts created with CRED for WPML translation after order complete
        public function classifieds_cred_commerce_after_send_notifications_add_new_free_ad($data){
            //check if the status of the WooCommerce order is completed
            if (isset($data['new_status']) && 'completed' == $data['new_status']) {
                if (isset($data['cred_meta'][0]['cred_post_id'])) {
                    //duplicate post to WPML translation
                    $this->_classifieds_duplicate_on_publish($data['cred_meta'][0]['cred_post_id']);
                }
            }
        }
        public function classifieds_cred_commerce_after_send_notifications_add_new_premium_ad($data){
            //check if the status of the WooCommerce order is completed
            if (isset($data['new_status']) && 'completed' == $data['new_status']) {
                if (isset($data['cred_meta'][0]['cred_post_id'])) {
                    //duplicate post to WPML translation
                    $this->_classifieds_duplicate_on_publish($data['cred_meta'][0]['cred_post_id']);
                }
            }
        }

        /*After CRED successfully saves listing to dB, run this for WPML version of Classifieds*/
        public function classifieds_cred_duplicate_entries_to_all_languages($post_id,$form_data)
        {
            //Get image fields updated
            $image_fields_updated = get_post_meta($post_id, 'wpcf-image', FALSE);
            $this->_classifieds_copy_cf_values_to_translations($post_id, $image_fields_updated, 'wpcf-image', 'listing');

            //Expiration date updated
            $expirydate_fields_updated = get_post_meta($post_id, 'wpcf-expiry-date', FALSE);
            $this->_classifieds_copy_cf_values_to_translations($post_id, $expirydate_fields_updated, 'wpcf-expiry-date', 'listing');

            //Featured image updated
            $featuredimage_fields_updated = get_post_meta($post_id, '_thumbnail_id', FALSE);
            $this->_classifieds_copy_cf_values_to_translations($post_id, $featuredimage_fields_updated, '_thumbnail_id', 'listing');

            //Get terms updated with the translation
            $listing_location_updated_terms = wp_get_object_terms($post_id, 'location');
            $listing_categories_updated_terms = wp_get_object_terms($post_id, 'listing_cat');
            $this->_classifieds_copy_term_values_to_translations($post_id, $listing_location_updated_terms, 'location', 'listing');
            $this->_classifieds_copy_term_values_to_translations($post_id, $listing_categories_updated_terms, 'listing_cat', 'listing');
        }

        public function classifieds_remove_product_link_in_order($item_link,$item) {

            global $woocommerce;

            if (is_object($woocommerce)) {

                //Get Item name
                if (isset($item['name'])) {
                    $item_name=$item['name'];

                    //Remove link return only item name
                    $item_link=$item_name;
                    return $item_link;

                } else {

                    return $item_link;
                }

            }

            return $item_link;
        }

        //Custom redirect to the WooCommerce login page
        public function classifieds_woocommerce_login_redirect($redirect_to,$user) {

            //Get checkout URL
            global $woocommerce;
            $checkout_url = $woocommerce->cart->get_checkout_url();

            //Don't redirect login at checkout page because it can break transactions
            if ($checkout_url==$redirect_to) {

                //return unfiltered $redirect_to
                return $redirect_to;

            } else {

                $the_page = get_page_by_title('My Account');
                $translated_page_id = $this->_classifieds_lang_id($the_page->ID,'page');
                $redirect_to = esc_url(get_permalink($translated_page_id));
                return $redirect_to;

            }

        }

        public function __DEPPREC__classifieds_woocommerce_order_again_button( $order ) {

            global $woocommerce;

            if (is_object($woocommerce)) {

                $atts=array('page'=>'add-new-ad','type'=>'url');
                $add_new_ad_url=$this->classifieds_get_page_by_title($atts);

                if ( ! $order || $order->status != 'completed' )
                    return;

                ?>
                <p class="order-again">
                    <a href="<?php echo $add_new_ad_url; ?>" class="button"><?php _e( 'Order Again', 'woocommerce' ); ?></a>
                </p>
            <?php
            }
        }

        //Show admin bar only to customers and administrators
        public function classifieds_show_admin_bar_loggedin($show_admin_bar) {

            global $current_user;

            if (is_object($current_user)) {

                //User defined
                //Get user roles
                $user_role_array=$current_user->roles;

                if ( (in_array('subscriber',$user_role_array)) ||
                    (in_array('customer',$user_role_array)) ||
                    (in_array('administrator',$user_role_array))) {

                    //User is an admin or customer, show admin bar
                    $show_admin_bar=true;
                    return $show_admin_bar;

                } else {

                    //Don't show admin bar
                    $show_admin_bar=false;
                    return $show_admin_bar;
                }

            } else {

                //Don't show admin bar to unconfirmed user role
                $show_admin_bar=false;
                return $show_admin_bar;
            }
        }

        //Customize locations link in single listing page to link with Views parametric query string.
        public function classifieds_customize_locations_tax_link($termlink, $term, $taxonomy) {

            global $post;

            if (is_object($post)) {

                //Get post type of the rendered page
                $rendered_post_type=$post->post_type;

                //Check if its a listing post type
                if ($rendered_post_type=='listing') {

                    $scope_taxonomies=array('location','listing_cat');
                    $taxonomy_custom_query_strings=array('location'=>'?loc=','listing_cat'=>'?listing-cat=');

                    //Check if its a location taxonomy
                    if (in_array($taxonomy,$scope_taxonomies)) {

                        /*Add query string to location $termlink*/
                        $query_string_phrase=$taxonomy_custom_query_strings[$taxonomy];

                        //Retrieve slug to pass
                        $slug_passed=$term->slug;

                        $termlink=$termlink.$query_string_phrase.$slug_passed;

                        return $termlink;

                    } else {
                        //return unfiltered
                        return $termlink;
                    }
                } else {
                    //return unfiltered
                    return $termlink;
                }
            } else {
                //return unfiltered
                return $termlink;
            }
        }

        public function classifieds_searchfilter($search_results) {

            //Run only on search pages
            if (is_search()) {
                if ((!empty($search_results)) && (is_array($search_results))) {
                    foreach ($search_results as $k=>$results) {

                        //Get post type and ID
                        if ((isset($results->post_type)) && (isset($results->ID))) {

                            $post_type=$results->post_type;
                            $post_id=$results->ID;

                            if ($post_type=='listing') {
                                $expiry_date=get_post_meta( $post_id, 'wpcf-expiry-date', TRUE );
                                if (empty($expiry_date)) {

                                    //This listing has no expiry date,remove
                                    unset($search_results[$k]);
                                }
                            }
                        }
                    }
                    //Return modified result
                    $search_results=array_values($search_results);
                    return $search_results;

                } else {

                    //Nevertheless return
                    return $search_results;
                }
            } else {
                //Not search, return unfiltered
                return $search_results;
            }

        }

        /**
         * get parent_listing_id from the URL parameter and get listing information
         * @param $atts
         * @param $content
         * @return bool|int|string
         */
        public function classifieds_get_listing_info_by_url_param($atts, $content) {
            if (!empty($_GET['listing-id'])) {
                $listing_id = intval($_GET['listing-id']);
            }
            extract(
                shortcode_atts(array(), $atts)
            );

            if ( !isset( $atts['info'] ) ){
                $atts['info'] = 'id';
            }
            switch ($atts['info']){
                case 'url':
                    $query = get_permalink($listing_id);
                    break;
                case 'title':
                    $query = get_the_title($listing_id);
                    break;
                case 'id':
                    $query = $listing_id;
                    break;
                case 'customer':
                    if (isset($_SESSION['classifieds_msg_post_id'])) {
                        $message_id = intval($_SESSION['classifieds_msg_post_id']);
                        $firstname = get_post_meta($message_id, 'wpcf-from-firstname', true);
                        $lastname = get_post_meta($message_id, 'wpcf-from-lastname', true);
                        $query = trim($firstname. ' ' .$lastname);
                    } else {
                        $query = '';
                    }
                    break;
            }
            return $query;
        }

        /**
         * API functions support
         **/
        public function classifieds_check_if_subscription_is_still_valid($user_id='', $type='', $object=NULL) {
            if ($this->implements_package_order()) {
                return $this->_Class_Toolset_Classifieds_PackageOrder->classifieds_check_if_subscription_is_still_valid($user_id, $type, $object);
            } else {
                return false;
            }
        }
        function classifieds_verify_if_user_is_ad_package_client($type='', $object=NULL) {
            if ($this->implements_package_order()) {
                return $this->_Class_Toolset_Classifieds_PackageOrder->classifieds_verify_if_user_is_ad_package_client('', $type, $object);
            } else {
                return false;
            }
        }
        public function classifieds_func_return_active_package_of_user($type='', $object=NULL) {
            if ($this->implements_package_order()) {
                return $this->_Class_Toolset_Classifieds_PackageOrder->classifieds_func_return_active_package_of_user($type, $object);
            } else {
                return 0;
            }
        }
        public function classifieds_func_check_if_ad_package_empty($type='', $object=NULL) {
            if ($this->implements_package_order()) {
                return $this->_Class_Toolset_Classifieds_PackageOrder->classifieds_func_check_if_ad_package_empty($type, $object);
            } else {
                return true;
            }
        }
        public function classifieds_check_if_wpml_is_running($type='', $object=NULL) {
            if ($this->implements_WPML()) {
                if ( defined( 'ICL_SITEPRESS_VERSION' ) && (!defined( 'ICL_PLUGIN_INACTIVE' ) || !ICL_PLUGIN_INACTIVE) ) {
                    return true;
                }
            } else {
                return false;
            }
        }
        
        public function classifieds_uploads_path_func($atts) {
        	
        	$upload_dir = wp_upload_dir();
        	$base_url = $upload_dir['baseurl'];
        	$base_url = rtrim($base_url, '/') . '/';
        	
        	return $base_url;
        	
        }
        
        public function remove_filter_for_wc_endpoint_title() {        	
				
			remove_filter( 'the_title', 'wc_page_endpoint_title' );			

        }
        
        /** We upgrade subscriber role to customer role upon successful order */
        public function classifieds_upgrade_subscriber_role($data) {
        	 
        	global $wpdb;
        	$table_prefix= $wpdb->prefix;
        	 
        
        	if (isset($data['user_id'])) {
        		$user_id = $data['user_id'];
        
        		/** Get current role */
        		$user_cap_key = $table_prefix.'capabilities';
        		$current_role= get_user_meta($user_id, $user_cap_key, TRUE);
        
        		/** Get current user level */
        		$user_level_key = $table_prefix.'user_level';
        		$current_userlevel= get_user_meta($user_id, $user_level_key, TRUE);
        
        		/** Verify subscriber roles*/
        		if (isset($current_role['subscriber'])) {
        			 
        			/** Client is originally a subscriber */
        			unset($current_role['subscriber']);
        
        			/** Assign a customer role */
        			$current_role['customer'] =true;
        			 
        			update_user_meta( $user_id, $user_cap_key, $current_role);
        		}
        
        		/** Verify user levels*/
        		if (!(empty($user_level_key))) {
        			/** Not an empty string, has value */
        			/** Client is originally a subscriber */
        			 
        			$current_userlevel = intval($current_userlevel);
        			 
        			if ($current_userlevel < 1) {
        				/** Less than 0, subscriber user level
        				 /** Assign a new user level */
        				$current_userlevel = 1;
        				update_user_meta( $user_id, $user_level_key, $current_userlevel);
        			}
        		}
        	}
        	 
        }
        
        /** Auto-assign 'ad-single' layout to new Listings */        
        function classifieds_auto_assign_layouts_listings($post_id,$the_form) {
        
        	//Use the Layouts plugin global variable for accessing its methods
        	global $wpddlayout;
        
        	$layout_slug='ad-single';
        	
        	if (isset($wpddlayout->post_types_manager)) {
        		$post_manager_layout_instance=$wpddlayout->post_types_manager;
        		
        		//Get instance of Layouts Post Type Manager
        		if (is_object($post_manager_layout_instance)) {
        			$abspath=ABSPATH;
        			include_once(ABSPATH . 'wp-admin/includes/theme.php');
        
        			update_post_meta($post_id, '_layouts_template', $layout_slug);
        			$the_posts=get_post($post_id);
        
        			if (is_object($the_posts)) {
        
        				/** Update the PHP template automatically associated with this Layout */
        
        				//Get post type of $the_posts
        				$post_type_passed=$the_posts->post_type;
        
        				//Get Layout PHP Template using Layouts method
        				$layout_php_template=$post_manager_layout_instance->get_layout_template_for_post_type( $post_type_passed );
        
        				//Update to page meta
        				update_post_meta($post_id, '_wp_page_template', $layout_php_template );
        			}
        		}
        	}
        } 
        
        /** Auto-assign 'single-message' layout to new Messages */
        function classifieds_auto_assign_layouts_for_messages($post_id,$the_form) {
        
        	//Use the Layouts plugin global variable for accessing its methods
        	global $wpddlayout;
        
        	$layout_slug='single-message';
        	 
        	if (isset($wpddlayout->post_types_manager)) {
        		$post_manager_layout_instance=$wpddlayout->post_types_manager;
        
        		//Get instance of Layouts Post Type Manager
        		if (is_object($post_manager_layout_instance)) {
        			$abspath=ABSPATH;
        			include_once(ABSPATH . 'wp-admin/includes/theme.php');
        
        			update_post_meta($post_id, '_layouts_template', $layout_slug);
        			$the_posts=get_post($post_id);
        
        			if (is_object($the_posts)) {
        
        				/** Update the PHP template automatically associated with this Layout */
        
        				//Get post type of $the_posts
        				$post_type_passed=$the_posts->post_type;
        
        				//Get Layout PHP Template using Layouts method
        				$layout_php_template=$post_manager_layout_instance->get_layout_template_for_post_type( $post_type_passed );
        
        				//Update to page meta
        				update_post_meta($post_id, '_wp_page_template', $layout_php_template );
        			}
        		}
        	}
        }
        
        /** EMERSON (since version 0.3.2)
         *  Improvements:
         *  Revise to be independent of page titles to allow clients to use whatever title they want for these My Account pages links.
         *  Before these 'titles' are hardcoded to this code
         *  Making it difficult to customize in actual applications.
         *  The only thing unchanged is the slug which cannot be edited, since the code will search the post / page object based on its slug
         *  This functions requires 'page' which is the page slug of parent-page/sub-page combination. (e.g. my-account/my-account-settings)
         *  And 'type' which is either a 'url' or 'link' output. 
         *  Without these requirements; the shortcode will output blank.
         */
        
        public function classifieds_get_page_by_title($atts){
        	
        	$ret='';
        	
        	//Step1, check if $atts is set
        	if ((is_array($atts)) && (!(empty($atts)))) {
        		
        		//We are set here...
        		//Step2, define parameters
        		$page='';
        		if (isset($atts['page'])) {
        			$page=$atts['page'];
        			$page=trim($page);        			
        		}
        		
        		$type='';
        		if (isset($atts['type'])) {
        			$type=$atts['type'];
        			$type=trim($type);
        		}

        		if ((!(empty($page))) && (!(empty($type)))) {
        			
        			//Step3, get post or page object by its slug
        			$the_page= get_page_by_path($page);
        			
        			if (is_object($the_page)) {
        				
        				//Get output
        				if ('url' == $type) {
        					
        					//Backward multilingual and non-multilingual compatible.
        					$translated_page_id = $this->_classifieds_lang_id($the_page->ID,'page');
        					$out = esc_url(get_permalink($translated_page_id));
        				} else {
        					$out = $this->_classifieds_link_to_element($the_page->ID,'page', false);
        				}       				
        				
        				if (!(empty($out))) {
        					
        					$ret=$out;
        				}       				
        			}        			
        		}
        	}
        	
        	return $ret;

        }       
        
    }

    $Class_Toolset_Classifieds = new Toolset_Classifieds();

    /**
     * API functions
     **/
    function classifieds_check_if_subscription_is_still_valid($user_id='', $type='', $object=NULL) {
        global $Class_Toolset_Classifieds;
        return $Class_Toolset_Classifieds->classifieds_check_if_subscription_is_still_valid($user_id, $type, $object);
    }
    function classifieds_verify_if_user_is_ad_package_client($type='', $object=NULL) {
        global $Class_Toolset_Classifieds;
        return $Class_Toolset_Classifieds->classifieds_verify_if_user_is_ad_package_client('', $type, $object);
    }
    function classifieds_func_return_active_package_of_user($type='', $object=NULL) {
        global $Class_Toolset_Classifieds;
        return $Class_Toolset_Classifieds->classifieds_func_return_active_package_of_user($type, $object);
    }
    function classifieds_func_check_if_ad_package_empty($type='', $object=NULL) {
        global $Class_Toolset_Classifieds;
        return $Class_Toolset_Classifieds->classifieds_func_check_if_ad_package_empty($type, $object);
    }

}

?>