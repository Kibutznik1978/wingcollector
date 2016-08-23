<?php
class Toolset_Framework_Installer {
    function __construct(){

    	//Support for Toolset unified menu with backward compatibility
        add_action( 'admin_menu', array( $this, 'add_reference_sites_administration_page' ),10 );
        add_filter( 'toolset_filter_register_menu_pages', array(&$this,'wpvdemo_unified_menu'), 100 );

        // Register style sheet.
        add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );

        // Ajax calls
        add_action( 'wp_ajax_refsite_install', array( $this, 'refsite_install' ) );
        
        //Customized importing process steps
        add_action('wp_ajax_refsite_custom_import_process_steps', array(&$this,'refsite_custom_import_process_steps'));

        //Installer overrides
        add_filter('installer_plugin_name_override', array( $this, 'installer_plugin_name_override_func' ),10,1);

        //User is not connected to Internet
        add_action('admin_notices', array($this, 'wpvdemo_user_internet_warning'));

        //User is on multisite
        add_action('admin_notices', array($this, 'wpvdemo_no_support_multisite_warning'));

        //WPMl 3.2 unneeded notices
        add_action('wp_loaded', array($this,'wpvdemo_remove_wpml_notices_discoverroot_site'));
        
        //WPML 3.2 Handle string packages import adjustments
        add_action('wp_loaded', array($this,'wpvdemo_adjust_string_packages_func'),25);
        add_action('wp_loaded', array($this,'wpvdemo_adjust_completed_string_packages_func'),35);
        add_action('wp_loaded', array($this,'wpvdemo_configure_string_packages_func'),50);
        add_action('wp_loaded', array($this,'wpvdemo_adjust_original_id_icltranslate'),75);       
        add_action('wp_loaded', array($this,'wpvdemo_search_replace_layouts_string_context'),99);

        //WPML 3.2 Discover WP pre-setup support
        add_action('wp_loaded', array($this,'wpvdemo_wpml_presetup_support'));
            
        //Location of deps.xml file
        add_filter('installer_ep_config_files',array($this,'wpvdemo_deps_xml_location'),10,1);
        
        //Deactivate Framework installer when user agrees to the terms at dashboard
        add_action( 'admin_init',array($this,'wpvdemo_deactivate_framework_installer_standalone'),10,1);
        add_action( 'deactivated_plugin',array($this,'wpvdemo_deactivated_framework_installer_notice'),10,2);   

        //review text and links for the required plugins
        add_filter( 'wpvdemo_filter_plugin_object_required',array($this,'wpvdemo_filter_plugin_object_func'),10,1);
        add_filter( 'wpvdemo_filter_plugin_object_optional',array($this,'wpvdemo_filter_plugin_object_func'),10,1);        
        
        //After importing a site clear the metaboxhidden_nav-menus so that it will show any custom menus set        
        add_filter('sanitize_user_meta_metaboxhidden_nav-menus', array($this,'wpvdemo_adjust_metaboxhiddennavmenus'), 10, 3);
        
        /** Google analytics arguments added to links pointing to wp-types.com */
        //Filter tutorial URL pointing to wp-types.com and add Google analytics arguments        
        add_filter('wpvdemo_filter_tutorial_url',array($this,'wpvdemo_filter_tutorial_url_func'),10,3);        
        
        //Filter tutorial short description that contains URL pointing to wp-types.com and add Google analytics arguments
        add_filter('wpvdemo_filter_tutorial_shortdescription',array($this,'wpvdemo_filter_tutorial_shortdescription_func'),10,3);
        
        //Refsite versions, backward compatibility and with new versions of Bootstrap Real estate site
        add_action('wpvdemo_import_refsite_versions',array($this,'wpvdemo_import_bootstrap_versions_func'),10);
        
        /**
         * EMERSON-Framework installer 1.9.1+
         * We removed the following WPML string translation filters so it won't interfere with strings import and
         * post import installation processes
         * 
         * Since all strings are already translated at the reference site server..
         * Framework installer may only need to install them and user does not to translate them.
         * Users may need to deactivate Framework installer and proceed to work with their custom site developments if they need to auto-register any theme/plugin related strings.
         * Then these filters are reactivated provided they use WPML string translation.
         */
        remove_filter( 'gettext', 'icl_sw_filters_gettext', 9, 3 );
        remove_filter( 'ngettext', 'icl_sw_filters_ngettext', 9, 5 );
        remove_filter( 'gettext_with_context', 'icl_sw_filters_gettext_with_context', 1, 4 );        
        add_action ('wp_loaded', array($this,'wpvdemo_reenable_string_filters_after_import'),5);
        
    }

    /**
     * Register and enqueue style sheet.
     */
    public function load_custom_wp_admin_style($hook) {
    	 
    	//Basic RTL support
    	if (is_rtl()) {
    		//RTL!
    		wp_register_style( 'refsites-style', WPVDEMO_RELPATH . '/css/refsites-rtl.css', array(), WPVDEMO_VERSION );
    	} else {
    		wp_register_style( 'refsites-style', WPVDEMO_RELPATH . '/css/refsites.css', array(), WPVDEMO_VERSION );
    	}
    	 
    	wp_register_script( 'refsites-script', WPVDEMO_RELPATH . '/js/refsites.js', array( 'wp-backbone' ), WPVDEMO_VERSION );
    	wp_register_script( 'fi-reloader-script', WPVDEMO_RELPATH . '/js/reload.js', array( 'jquery' ), WPVDEMO_VERSION );
    
    	$is_discoverwp=$this->is_discoverwp();
    	$test_string='';
    	/** DISCOVER-WP SAFARI BROWSER INCOMPATIBILITY */
    	/** START */
    	$discoverwp_now_installing_site='no';
    	$fix_redirection ='';
    	/** END */
    
    	if ($is_discoverwp) {
    		$is_discoverwp='discover-wp';
    		$current_user = wp_get_current_user();
    		if (isset($current_user->ID)) {
    			$user_id=$current_user->ID;
    			$string=$user_id.$is_discoverwp;
    			$test_string=md5($string);
    		}
    	}
    	$refsites_master_screen=admin_url().'admin.php?page=manage-refsites';
    	
    	//canonical screen
    	$current_screen_canonical = $this->wpvdemo_unified_current_screen();
    	if ( $current_screen_canonical == $hook) {
    		global $refsites;
    		$refsites = $this->prepare_refsites_for_js();
    		$refsites_unfiltered=$this->prepare_refsites_for_js(false);
    			
    		/** DISCOVER-WP SAFARI BROWSER INCOMPATIBILITY */
    		/** START */
    		/** Check for any installation URL */
    		if ($is_discoverwp) {
    			//Issue isolated only to Discover-WP
    			//We default to non-installation mode
    
    			$wpvlive_redirection_url= get_option('wpvlive_redirection_url');
    			if ( $wpvlive_redirection_url ) {
    					
    				/** We are currently installing.. */
    				$discoverwp_now_installing_site='yes';
    				$fix_redirection=$wpvlive_redirection_url;
    					
    				/**Delete this option */
    				delete_option('wpvlive_redirection_url');
    			}
    		}
    
    		wp_localize_script( 'refsites-script', '_refsitesSettings', array(
    		'refsites'   => $refsites,
    		'refsites_unfiltered'	 => $refsites_unfiltered,
    		'refsites_master_screen' => $refsites_master_screen,
    		'refsites_discoverwp_now_installing' => $discoverwp_now_installing_site,
    		'refsites_discoverwp_redirection_fix' => $fix_redirection,
    		'refsite_import_text_nonce' => wp_create_nonce('wpvdemo_import_text_custom'),    		
    		'target_verification' => $test_string,
    		'settings'   => array(
    		'adminUrl'      => parse_url( admin_url(), PHP_URL_PATH )
    		),
    		'l10n' => array(
    		'search'  => __( 'Search Available Refsites' ),
    		'searchPlaceholder' => __( 'Search available refsites...' ), // placeholder (no ellipsis)
    		)
    		) );
    			
    		/** END */
    			
    		//We are dealing with manage sites screen
    		$this->refsite_install_progress();
    		wp_enqueue_style('wpvdemo', WPVDEMO_RELPATH . '/css/basic.css', array(), WPVDEMO_VERSION);
    		wp_enqueue_style( 'refsites-style' );
    		wp_enqueue_script( 'refsites-script' );
    
    	}
    
    	//Framework Installer 1.7.3 revision
    	$hook ='no';
    	if ('toplevel_page_manage-refsites' == $hook) {
    		$hook='yes';
    	}
    
    	//Reloader
    	$refsite_redirecting_message = '';
    	$refsite_redirecting_message .= __('Framework Installer is ready for you to use. Refreshing and redirecting to the manage sites screen.');
    	$refsite_redirecting_message .= '<p></p>';
    
    	//Required activated, cannot be unchecked
    	$required_unchecked_message = __('This is a requirement for installing reference sites. It cannot be unchecked.');
    	$required_unchecked_message = esc_js($required_unchecked_message);
    
    	wp_localize_script ( 'fi-reloader-script', 'fi_reloader_settings', array (
    	'refsites_master_screen' => $refsites_master_screen,
    	'refsite_redirecting_message' =>$refsite_redirecting_message,
    	'refsite_required_unchecked_message' =>$required_unchecked_message,
    	'hook' => $hook
    	) );
    
    	//Enqueue all with some special checks
    	wp_enqueue_script( 'fi-reloader-script' );
    }

    /**
     * Reference sites administration panel.
     *
     * @package Framework Installer
     * @subpackage Administration
     */
    function add_reference_sites_administration_page(){

    	if ( ! ( $this->wpvdemo_can_implement_unified_menu() ) ) {
	    	$views_demo_icon=plugins_url('images/discover_wp_icon.png',dirname(__FILE__));
	        $page_title = __( 'Select a Reference Site to install', 'wpvdemo' );
	        $menu_title = __( 'Manage Sites', 'wpvdemo' );
	        $capability = 'manage_options';
	        $menu_slug = 'manage-refsites';
	
	        $manage_refsites_page=add_menu_page($page_title, $menu_title, $capability, $menu_slug, array( &$this, 'manage_reference_sites_admin_page' ),$views_demo_icon);
	        add_action('load-' . $manage_refsites_page,  array($this,'wpvdemo_admin_menu_import_hook'));
	        add_action('load-'.$manage_refsites_page, array($this, 'manage_reference_sites_add_help_tab'));
    	}
    }
    /**
     * Admin menu page hook.
     */
    function wpvdemo_admin_menu_import_hook() {

    	$importing_text='';

    	wp_enqueue_style('wpvdemo', WPVDEMO_RELPATH . '/css/basic.css', array(),
    	WPVDEMO_VERSION);
    	wp_enqueue_script('wpvdemo', WPVDEMO_RELPATH . '/js/basic.js',
    	array('jquery'), WPVDEMO_VERSION);
    	viewdemo_admin_add_js_settings('wpvdemo_nonce',
    	"'"
    			. wp_create_nonce('wpvdemo') . "'");
    			viewdemo_admin_add_js_settings('wpvdemo_download_step_one_txt',
    			"'"
    					. $importing_text . "'");
    					$site_is_empty_double_check=wpvdemo_double_check_site_is_empty();
    					if ($site_is_empty_double_check) {
    						$message=__('Are you sure you want to download this site?',
    								'wpvdemo');
    					} else {
    						//Not actually empty
    						$message=__('Your site has some sample content. The installer will remove that content and replace it will our own demo content.',
    								'wpvdemo');
    					}
    					viewdemo_admin_add_js_settings('wpvdemo_confirm_download_txt',
    					"'"
    							. esc_js($message) . "'");
    							wpvdemo_check_if_blank_site();

    							do_action('wpvdemo_admin_load');
    }
    function manage_reference_sites_add_help_tab(){
        $help_overview =
            '<p>' . __('Install complete Toolset reference sites and use as basis for your client work.') . '</p>' .
            '<p>' . __('Need to build complete e-commerce, magazine, real estate, classifieds and other complex sites? You can use the Toolset Framework Installer to speed up your development work.') . '</p>' .
            '<p>' . __('You will get a fully-functional site, with a theme, everything configured and working and even sample content. Then, edit whatever you need and deliver to your clients.') . '</p>';

        get_current_screen()->add_help_tab( array(
            'id'      => 'overview',
            'title'   => __('Overview'),
            'content' => $help_overview
        ) );

        $help_installing =
            '<p>' . sprintf(__('These are pre-built sites, created using <a href="%s" target="_blank">Toolset plugins</a>, which you can use as the basis for your projects.'), 'http://wp-types.com/') . '</p>' .
            '<p>'. __(' You will need to:') . '</p>' .
            '<ul><li>'. __('Create a blank WordPress site') . '</li>' .
            '<li>'. __('Install the Framework Installer plugin') . '</li>' .
            '<li>'. __('Choose a site and install it') . '</li></ul>';

        get_current_screen()->add_help_tab( array(
            'id'      => 'installing',
            'title'   => __('Downloading and Installing'),
            'content' => $help_installing
        ) );

        get_current_screen()->set_help_sidebar(
            '<p><strong>' . __('For more information:') . '</strong></p>' .
            '<p>' . __('<a href="http://wp-types.com/documentation/views-demos-downloader/" target="_blank">Documentation on how to use Framework Installer</a>') . '</p>' .
            '<p>' . __('<a href="http://wp-types.com/forums/" target="_blank">Support Forums</a>') . '</p>'
        );


    }
    function manage_reference_sites_admin_page(){
		global $refsites;        
        ?>
<div class="wrap">
	<h2><?php esc_html_e( 'Select a Reference Site to install' ); ?></h2>
			<?php do_action('wpvdemo_start_demo_page'); ?>
            <div class="refsite-browser">
		<div class="refsites">

                    <?php
                    /*
                     * This PHP is synchronized with the tmpl-refsite template below!
                     */                    
                    foreach ($refsites as $refsite) {
                        $refsite['actions'] = (array) $refsite['actions'];
                        $aria_action = esc_attr($refsite['ID'] . '-action');
                        $aria_name = esc_attr($refsite['ID'] . '-name');
	                    ?>
                        <div class="refsite" tabindex="0"
				aria-describedby="<?php echo $aria_action . ' ' . $aria_name; ?>"
				data-id="<?php echo $refsite['ID']; ?>">
                            <?php if (!empty($refsite['image'])) { ?>
                                <div class="refsite-screenshot">
                    <?php 
                    $image_check =$refsite['image'];
                    if (is_object($image_check)) {
                    	$image_check='#';
                    }
                    ?>
					<img src="<?php echo $image_check; ?>" alt="" />
				</div>
                            <?php } else { ?>
                                <div class="refsite-screenshot blank"></div>
                            <?php } ?>
                            <span class="more-details"
					id="<?php echo $aria_action; ?>"><?php _e('Reference Site Details', 'wpvdemo'); ?></span>

				<h3 class="refsite-name" id="<?php echo $aria_name; ?>"><?php echo $refsite['title']; ?></h3>

				<div class="refsite-actions">

                                <?php if ($refsite['installed']) { ?>
                                    <?php if ($refsite['actions']['customize'] && current_user_can('edit_refsite_options') && current_user_can('customize')) { ?>
                                        <a
						class="button button-primary customize load-customize hide-if-no-customize"
						href="<?php echo $refsite['actions']['customize']; ?>"><?php _e('Customize'); ?></a>
                                    <?php } ?>
                                <?php } else { ?>
                                    <a
						class="button button-secondary activate"
						href="<?php echo $refsite['actions']['install']; ?>"><?php _e('Install'); ?></a>
                                <?php } ?>

                            </div>
			</div>
                    <?php } ?>

                    <br class="clear" />
		</div>
	</div>
	<div class="refsite-overlay"></div>

</div>
<!-- .wrap -->

<?php
        /*
         * The tmpl-refsite template is synchronized with PHP above!
         */
        ?>
<script id="tmpl-refsite" type="text/template">
			<# if ( ! data.actions.required_activated_plugin_status ) { #>
				<# if (!(data.actions.is_discoverwp)) { #>
				<div class="refsite-inactive">
				<# } #>
			<# } #>
            <# if ( data.image ) { #>
                <div class="refsite-screenshot">
                    <img src="{{{ data.large_image }}}" alt="" />
	                <# if ( ! data.actions.required_activated_plugin_status ) { #>
						<# if (!(data.actions.is_discoverwp)) { #>
		                	<div class="refsite-inactive-info">
			            	    <?php _e( 'Installation requirements not met. Click for details', 'wpvdemo' ); ?>
		               		</div>
						<# } #>
	                <# } #>
                </div>
            <# } else { #>
                <div class="refsite-screenshot blank"></div>
	            <# if ( ! data.actions.required_activated_plugin_status ) { #>
                    <# if (!(data.actions.is_discoverwp)) { #>
		            	<div class="refsite-inactive-info">
			        	    <?php _e( 'Installation requirements not met. Click for details.', 'wpvdemo' ); ?>
		            	</div>
                   <# } #>
	            <# } #>
            <# } #>
            <span class="more-details" id="{{{ data.id }}}-action"><?php _e('Reference Site Details', 'wpvdemo'); ?></span>
            <h3 class="refsite-name" id="{{{ data.id }}}-name">{{{ data.title }}}</h3>
            <div class="refsite-actions">
                <# if ( data.active ) { #>

                <# } else { #>
					<# if ( data.actions.required_activated_plugin_status ) { #>
						<a class="button button-secondary activate" href="{{{ data.actions.activate }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
					<# } else { #>
						<# if (data.actions.is_discoverwp) { #>
							<a class="button button-secondary activate" href="{{{ data.actions.activate }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
						<# } else { #>
							<a class="button button-secondary activate" href="{{{ data.actions.activate }}}"><?php _e( 'Site Details', 'wpvdemo' ); ?></a>
						<# } #>
					<# } #>
                <# } #>
            </div>

            <# if ( ! data.actions.required_activated_plugin_status ) { #>
				<# if (!(data.actions.is_discoverwp)) { #>
		           </div>
				<# } #>
            <# } #>
</script>

<script id="tmpl-refsite-single" type="text/template">
            <# var compatibility_text = String('<?php _e( '%s to tested version: %s.', 'wpvdemo'); ?>').replace(' ', '&nbsp;'); #>
            <div class="refsite-backdrop"></div>
            <div class="refsite-wrap">
                <div class="refsite-header">
                    <button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous Reference Site' ); ?></span></button>
                    <button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next Reference Site' ); ?></span></button>
                    <button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close overlay' ); ?></span></button>
                </div>
                <div class="refsite-about">
					<div class="col-lg-6 col-xs-12">
						<?php
						/** DEFAULT REFSITE SCREENSHOT PRESENTATION */
						?>

	                    <div class="refsite-screenshot" id="refsite-screenshot-{{{ data.ID }}}">
	                        <# if ( data.image ) { #>
	                        <div class="screenshot"><img src="{{{ data.large_image }}}" alt="" /></div>
	                        <# } else { #>
	                        <div class="screenshot blank"></div>
	                        <# } #>
	                    </div>

						<?php
						/** MERGED REFSITE SCREENSHOT PRESENTATION */
						?>
						<# if (data.layouts_version) { #>
	                    <div class="refsite-screenshot" id="refsite-screenshot-{{{ data.layouts_version.ID }}}" style="display: none;">
	                        <# if ( data.layouts_version.image ) { #>
	                        <div class="screenshot"><img src="{{{ data.layouts_version.large_image }}}" alt="" /></div>
	                        <# } else { #>
	                        <div class="screenshot blank"></div>
	                        <# } #>
	                    </div>
						<# } #>
					</div>
	                <div class="col-lg-6 col-xs-12">
						<?php
						/** DEFAULT REFSITE INFO PRESENTATION */
						?>
	                    <div class="refsite-info" id="refsite-info-{{{ data.ID }}}">
	                        <h3 class="refsite-name">{{{ data.title }}}</h3>
	                        <h4 class="refsite-author"><?php printf( __( 'By %s' ), '{{{ data.authorAndURI }}}' ); ?></h4>

	                        <p class="refsite-description">{{{ data.short_description }}}</p>

	                    </div>

						<?php
						/** MERGED REFSITE INFO PRESENTATION */
						?>
						<# if (data.layouts_version) { #>
	                    <div class="refsite-info" id="refsite-info-{{{ data.layouts_version.ID }}}" style="display: none;">
	                        <h3 class="refsite-name">{{{ data.layouts_version.title }}}</h3>
	                        <h4 class="refsite-author"><?php printf( __( 'By %s' ), '{{{ data.layouts_version.authorAndURI }}}' ); ?></h4>

	                        <p class="refsite-description">{{{ data.layouts_version.short_description }}}</p>

	                    </div>
						<# } #>


						<?php
						/** OPTIONS TO SELECT DESIGN TOOLS AVAILABLE WHEN THE SITE VERSIONS ARE MERGED INTO ONE */
						?>
							<# if (data.layouts_version) { #>
								<?php
								/**Merged presentation*/
								?>
	                        <div class="refsite-versions">
	                            <h4 class="refsite-author"><?php echo __('Design tools:', 'wpvdemo'); ?></h4>
	                            <ul>
	                                <li>
										<label>
											<input type="radio" name="site_version" class="refsite-site-version" value="{{{ data.ID }}}" checked="checked" /> Only Views plugin
										<label>
									</li>
	                                <li>
										<label>
											<input type="radio" name="site_version" class="refsite-site-version" value="{{{ data.layouts_version.ID }}}" /> Layouts and Views plugins
										<label>
									</li>
	                            </ul>
	                        </div>
							<# } #>

	                    <# if ( Object.keys(data.versions).length > 1 ) { #>
							
	                        <div class="refsite-versions">
	                            <h4 class="refsite-author"><?php echo __('One language or multilingual:', 'wpvdemo'); ?></h4>
	                            <ul>
	                                <# var selected = true;
	                                    for (var version in data.versions) { #>
	                                <li><label><input type="radio" name="version" class="refsite-version" value="{{{ data.versions[version].name }}}"<# if (selected) { #> checked="checked"<# } #> /> {{{ data.versions[version].title }}}</label></li>
	                                <# selected = false
	                                    } #>
	                            </ul>
	                        </div>
							
	                    <# } else if ( Object.keys(data.versions).length == 1 ) {
	                        for (var version in data.versions) { #>
	                            <input type="hidden" name="version" value="{{{ data.versions[version].name }}}" />
	                        <# } #>
	                    <# } #>

	                    <div class="refsite-plugins" id="merge-plugin-{{{ data.ID }}}">
						<?php
						/** FOR DEFAULT PLUGIN PRESENTATION */
						?>
	                        <# if ( typeof data.plugins != 'undefined' ) { #>
	                            <# for (var version in data.versions) { #>
	                            <div class="refsite-version-plugins refsite-version-plugins-{{{ data.versions[version].name }}}" style="display: none;">
	                                <# if ( data.versions[version].plugins.length ) { #>
	                                <h4 class="refsite-author"><?php echo __('Required plugins:', 'wpvdemo'); ?></h4>
	                                <ul style="list-style-type: square; list-style-position:inside;">
	                                    <# jQuery.each( data.versions[version].plugins, function() {
	                                        var self = this;
	                                        var self_compatibility_text = '';
	                                        if (typeof self.compatibility_text == 'string' && typeof self.plugin_version_tested == 'string') {
	                                            var nth = 0;
	                                            self_compatibility_text = compatibility_text.replace(/(%s)/g, function(match, i, original) {
	                                                nth++;
	                                                if ( 1 == nth ) return self.compatibility_text;
	                                                if ( 2 == nth ) return self.plugin_version_tested;
	                                                return match;
	                                            });
	                                        } #>						
	                                    <li>
											<# if (!(self.url)) { #>
												{{{ self.title }}}
											<# } else { #>
												<a href="{{{ self.url }}}" target="_blank" title="{{{ self.title }}}">{{{ self.title }}}</a>
											<# } #>
	                                        <# if ( self.active ) { #>
	                                            &nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>
	                                            <# if ( !self.compatibility ) { #>
	                                                <font color="red">&nbsp;&nbsp;-&nbsp;{{{ self_compatibility_text }}}</font>
	                                                <# } #>
	                                        <# } else if ( self.found ) { #>
	                                            &nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>
	                                            <# if ( !self.compatibility ) { #>
	                                                <font color="red">&nbsp;&nbsp;-&nbsp;{{{ self_compatibility_text }}}</font>
	                                            <# } #>
	                                        <# } else { #>
												<# if (!(self.url)) { #>
                                                &nbsp;<span style="color:Red;"> {{{ self.found_custom_message }}}</span>
												<# } else { #>
	                                            &nbsp;<span style="color:Red;"><?php echo wpvdemo_error_message('required_plugin_warning'); ?></span><span style="color:Red;"> {{{ self.found_custom_message }}}</span>
	                                        	<# } #>
											<# } #>
	                                    </li>
	                                    <# }); #>
	                                </ul>
	                                <# } #>
	                            </div>
	                            <# } #>
	                        <# } #>
	                    </div>

						<# if (data.layouts_version) { #>
						<?php
						/** FOR MERGED PLUGIN PRESENTATION */
						?>
	                    <div class="refsite-plugins" id="merge-plugin-{{{ data.layouts_version.ID }}}" style="display: none;">
	                        <# if ( typeof data.layouts_version.plugins != 'undefined' ) { #>
	                            <# for (var version in data.layouts_version.versions) { #>
	                            <# if (( data.layouts_version.actions.has_multilingual )) { #>
									<div class="refsite-version-plugins refsite-version-plugins-{{{ data.layouts_version.versions[version].name }}}" style="display: none;">
								<# } else { #>
									<div class="refsite-version-plugins refsite-version-plugins-{{{ data.layouts_version.versions[version].name }}}">
								<# } #>	                            
	                                <# if ( data.layouts_version.versions[version].plugins.length ) { #>
	                                <h4 class="refsite-author"><?php echo __('Required plugins:', 'wpvdemo'); ?></h4>
	                                <ul style="list-style-type: square; list-style-position:inside;">
	                                    <# jQuery.each( data.layouts_version.versions[version].plugins, function() {
	                                        var self = this;
	                                        var self_compatibility_text = '';
	                                        if (typeof self.compatibility_text == 'string' && typeof self.plugin_version_tested == 'string') {
	                                            var nth = 0;
	                                            self_compatibility_text = compatibility_text.replace(/(%s)/g, function(match, i, original) {
	                                                nth++;
	                                                if ( 1 == nth ) return self.compatibility_text;
	                                                if ( 2 == nth ) return self.plugin_version_tested;
	                                                return match;
	                                            });
	                                        } #>
	                                    <li>
											<# if (!(self.url)) { #>
												{{{ self.title }}}
											<# } else { #>
												<a href="{{{ self.url }}}" target="_blank" title="{{{ self.title }}}">{{{ self.title }}}</a>
											<# } #>
	                                        <# if ( self.active ) { #>
	                                            &nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>
	                                            <# if ( !self.compatibility ) { #>
	                                                <font color="red">&nbsp;&nbsp;-&nbsp;{{{ self_compatibility_text }}}</font>
	                                                <# } #>
	                                        <# } else if ( self.found ) { #>
	                                            &nbsp;<span class="wpvdemo-green-check">&nbsp;&nbsp;&nbsp;&nbsp;</span>
	                                            <# if ( !self.compatibility ) { #>
	                                                <font color="red">&nbsp;&nbsp;-&nbsp;{{{ self_compatibility_text }}}</font>
	                                            <# } #>
	                                        <# } else { #>
												<# if (!(self.url)) { #>
                                                &nbsp;<span style="color:Red;"> {{{ self.found_custom_message }}}</span>
												<# } else { #>
	                                            &nbsp;<span style="color:Red;"><?php echo wpvdemo_error_message('required_plugin_warning'); ?></span><span style="color:Red;"> {{{ self.found_custom_message }}}</span>
												<# } #>
	                                        <# } #>
	                                    </li>
	                                    <# }); #>
	                                </ul>
	                                <# } #>
	                            </div>
	                            <# } #>
	                        <# } #>
	                    </div>
						<# } #>

						<?php
						/** FOR DEFAULT REFSITE INSTALL PROGRESS */
						?>
	                    <div id="refsite-install-progress-{{{ data.ID }}}" class="refsite-install-progress" style="display: none;">
							<h2 class="imported_site_title"></h2>
	                        <div id="wpvdemo-download-response-{{{ data.ID }}}" class="wpvdemo-download-response" style="clear:both;"></div>
	                        <div class="wpvdemo-download-loading" style="clear:both;">&nbsp;</div>
	                    </div>

						<?php
						/** FOR MERGED REFSITE INSTALL PROGRESS */
						?>
						<# if (data.layouts_version) { #>
	                    <div id="refsite-install-progress-{{{ data.layouts_version.ID }}}" class="refsite-install-progress" style="display: none;">
							<h2 class="imported_site_title"></h2>
	                        <div id="wpvdemo-download-response-{{{ data.layouts_version.ID }}}" class="wpvdemo-download-response" style="clear:both;"></div>
	                        <div class="wpvdemo-download-loading" style="clear:both;">&nbsp;</div>
	                    </div>
						<# } #>
					</div>
                </div>
				<?php
				/** FOR DEFAULT REFSITES ACTION PRESENTATION */
				?>
                <div class="refsite-actions" id="refsite-actions-{{{ data.ID }}}">
					<# if ( data.actions.required_activated_plugin_status ) { #>
						<# if (!(data.actions.is_discoverwp)) { #>
							<# if ((data.actions.has_all_wpml_plugins)) { #>
							<# } else { #>
								<# if (( data.actions.has_multilingual )) { #>
									<div class="wpmlversion_div_msg" style="line-height:1.8em;margin-bottom:30px;border-left:4px solid #dd3d36;padding: 1px 12px;background: none repeat scroll 0 0 #fff;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);">
  										{{{ data.actions.requisites_not_meet_msg }}}
									</div>
								<# } #>
							<# } #>
						<# } #>
					<# } else { #>
						<# if (!(data.actions.is_discoverwp)) { #>
						<div class="prequisite_error_div_msg" style="line-height:1.8em;margin-bottom:30px;border-left:4px solid #dd3d36;padding: 1px 12px;background: none repeat scroll 0 0 #fff;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);">
  							{{{ data.actions.requisites_not_meet_msg }}}
						</div>
						<# } #>
					<# } #>
                    <div class="active-refsite">
                    </div>
                    <div class="inactive-refsite">
                        <# if (( data.actions.install ) && (!(data.actions.is_discoverwp))) { #>

							<# if (!( data.actions.has_multilingual )) { #>

								<# if ( data.actions.required_activated_plugin_status ) { #>
                        			<a href="{{{ data.ID }}}" class="wpvdemo-download button-primary" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

							<# } else { #>

								<# if ( data.actions.required_activated_plugin_status ) { #>
                        			<a href="{{{ data.ID }}}" class="wpvdemo-download button-primary nowpmlversion" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary nowpmlversion" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

								<# if (( data.actions.required_activated_plugin_status ) && (data.actions.has_all_wpml_plugins)) { #>
                        			<a href="{{{ data.ID }}}" class="wpvdemo-download button-primary wpmlversion" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary wpmlversion" id="wpvdemo-download-button-{{{ data.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

							<# } #>

						<# } else if (data.actions.is_discoverwp) {#>
								{{{ data.actions.createsite }}}
						<# } #>
                        <# if ( data.actions.has_tutorial ) { #>
                        <a href="{{{ data.actions.tutorial }}}" target="_blank" class="wpvdemo-tutorial button-secondary" id="wpvdemo-tutorial-button-{{{ data.ID }}}" target="_blank"><?php _e( 'Tutorial', 'wpvdemo' ); ?></a>
                        <# } #>
                        <# if ( data.actions.preview ) { #>
                        <a href="{{{ data.actions.preview }}}" target="_blank" class="wpvdemo-preview button-secondary" id="wpvdemo-preview-button-{{{ data.ID }}}" target="_blank"><?php _e( 'Live Preview', 'wpvdemo' ); ?></a>
                        <# } #>
                    </div>
                </div>

				<# if (data.layouts_version) { #>
				<?php
				/** FOR MERGE REFSITES ACTION PRESENTATION */
				?>
                <div class="refsite-actions" id="refsite-actions-{{{ data.layouts_version.ID }}}" style="display: none;">
					<# if ( data.layouts_version.actions.required_activated_plugin_status ) { #>
						<# if (!(data.layouts_version.actions.is_discoverwp)) { #>
							<# if ((data.layouts_version.actions.has_all_wpml_plugins)) { #>
							<# } else { #>
								<# if (( data.layouts_version.actions.has_multilingual )) { #>
									<div class="wpmlversion_div_msg" style="line-height:1.8em;margin-bottom:30px;border-left:4px solid #dd3d36;padding: 1px 12px;background: none repeat scroll 0 0 #fff;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);">
  										{{{ data.layouts_version.actions.requisites_not_meet_msg }}}
									</div>
								<# } #>
							<# } #>
						<# } #>
					<# } else { #>
						<# if (!(data.layouts_version.actions.is_discoverwp)) { #>
						<div class="prequisite_error_div_msg" style="line-height:1.8em;margin-bottom:30px;border-left:4px solid #dd3d36;padding: 1px 12px;background: none repeat scroll 0 0 #fff;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);">
  							{{{ data.layouts_version.actions.requisites_not_meet_msg }}}
						</div>
						<# } #>
					<# } #>
                    <div class="active-refsite">
                    </div>
                    <div class="inactive-refsite">
                        <# if (( data.layouts_version.actions.install ) && (!(data.layouts_version.actions.is_discoverwp))) { #>

							<# if (!( data.layouts_version.actions.has_multilingual )) { #>

								<# if ( data.layouts_version.actions.required_activated_plugin_status ) { #>
                        			<a href="{{{ data.layouts_version.ID }}}" class="wpvdemo-download button-primary" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

							<# } else { #>

								<# if ( data.layouts_version.actions.required_activated_plugin_status ) { #>
                        			<a href="{{{ data.layouts_version.ID }}}" class="wpvdemo-download button-primary nowpmlversion" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary nowpmlversion" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

								<# if (( data.layouts_version.actions.required_activated_plugin_status ) && (data.layouts_version.actions.has_all_wpml_plugins)) { #>
                        			<a href="{{{ data.layouts_version.ID }}}" class="wpvdemo-download button-primary wpmlversion" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
                        		<# } else { #>
									<a disabled="disabled" class="button-primary wpmlversion" id="wpvdemo-download-button-{{{ data.layouts_version.ID }}}"><?php _e( 'Install', 'wpvdemo' ); ?></a>
								<# } #>

							<# } #>

						<# } else if (data.layouts_version.actions.is_discoverwp) {#>
								{{{ data.layouts_version.actions.createsite }}}
						<# } #>
                        <# if ( data.layouts_version.actions.has_tutorial ) { #>
                        <a href="{{{ data.layouts_version.actions.tutorial }}}" target="_blank" class="wpvdemo-tutorial button-secondary" id="wpvdemo-tutorial-button-{{{ data.layouts_version.ID }}}" target="_blank"><?php _e( 'Tutorial', 'wpvdemo' ); ?></a>
                        <# } #>
                        <# if ( data.layouts_version.actions.preview ) { #>
                        <a href="{{{ data.layouts_version.actions.preview }}}" target="_blank" class="wpvdemo-preview button-secondary" id="wpvdemo-preview-button-{{{ data.layouts_version.ID }}}" target="_blank"><?php _e( 'Live Preview', 'wpvdemo' ); ?></a>
                        <# } #>
                    </div>
                </div>
				<# } #>
            </div>
        </script>
<?php
    }
	function wpvdemo_force_install_activate_notice() {
		//We only want to show reset notices when the site is not empty
		//Check if the site is empty
		$site_is_empty=	wpvdemo_double_check_site_is_empty();
		
		//Check for new Toolset shared menu implemenation
		$unified_menu_implementation = $this->wpvdemo_can_implement_unified_menu();
		$reset_path = __('Manage sites -> Reset Demo Site','wpvdemo');
		
		if ( true === $unified_menu_implementation ) {
			$reset_path = __('Toolset -> Settings -> Reset Demo Site','wpvdemo');
			
		}
		
		if ($site_is_empty) {
			//Site is empty, don't show reset notices
			//Framework installer 1.8.2+, don't show anything
?>

<?php
		} else {
			//Site is not empty too, include reset notices
?>
<div class="error">
	<p><?php _e('Make sure you are running in a clean WordPress installation. 
			To reset your Website and Database from a previous installation, you can use the reset feature under','wpvdemo')
		?>
		<?php 
		echo '"'.$reset_path.'"';
		?>.
		</p>
</div>
<?php
		}
	}
	function prepare_refsites_for_js($filtered=true) {
		require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
		$refsites = $this->get_refsites ();
		$missing = apply_filters ( 'installer_deps_missing', false );
		
		/**
		 * Framework Installer 1.7.3 + New installer embedded method
		 */
		/**
		 * START
		 */
		$inactive = $this->wpvdemo_detect_inactive_plugins ();
		
		/**
		 * END
		 */
		if ((! ($missing)) && (! ($inactive))) {
			// all required plugins are installed and active
			$required_activated_plugin_status = true;
		} else {
			$required_activated_plugin_status = false;
			add_action ( 'admin_notices', array (
					$this,
					'wpvdemo_force_install_activate_notice' 
			), 9 );
		}
		$aux_array = array ();
		
		$is_discoverwp_multisite = $this->is_discoverwp ();
		
		foreach ( $refsites as $refsite ) {
			$not_found = array ();
			$not_found_wpml = array ();
			
			if (! $this->admin_check_required_site_settings ( $refsite )) {
				continue;
			}
			if (apply_filters ( 'wpvdemo_hide_site_download', false, $refsite )) {
				continue;
			}
			//On its first instance, save the exported refsite version
			$refsite->id = $refsite->ID;
			$refsite_id_versions=$refsite->id;			
			$this->wpvdemo_save_refsite_installed_version($refsite_id_versions);
			
			$display_plugins = array ();

			if (! empty ( $refsite->plugins->plugin )) {
				$display_plugins = wpvdemo_format_display_plugins ( $refsite->plugins->plugin, true );
				if (! empty ( $display_plugins )) {
					$display_plugins = wpvdemo_check_if_wpml_will_be_skipped ( $display_plugins, $refsite->download_url, false, false );
				}
			}
			$refsite = json_decode ( json_encode ( $refsite ) );
			if (! empty ( $refsite->plugins->plugin )) {
				$refsite->plugins = $refsite->plugins->plugin;
			}

			$refsite->slug = $refsite->shortname;
			
			/** Allow tutorial URL to be filtered. */
			/** Some application includes Google analytics arguments to be added to tutorial URL if pointing to wp-types.com */
			$refsite_tutorial_url= $refsite->tutorial_url;
			$refsite->tutorial_url= apply_filters('wpvdemo_filter_tutorial_url',$refsite_tutorial_url,'post-setup-box','tutorial');	

			/** Allow short_description to be filtered. */
			/** Some application includes Google analytics arguments to be added to tutorial URL if pointing to wp-types.com */	
			$refsite_tutorial_shortdescription= $refsite->short_description;
			$refsite->short_description= apply_filters('wpvdemo_filter_tutorial_shortdescription',$refsite_tutorial_shortdescription,'post-setup-box','tutorial');
			
			$shortname_passed = $refsite->slug;
			$admin_url_reference_sites = admin_url () . 'admin.php?page=manage-refsites';
			$installation_site_url = add_query_arg ( 'refsite', $shortname_passed, $admin_url_reference_sites );
			
			$versions = array (
					'nowpml' => array (
							'name' => 'nowpml',
							'title' => __ ( 'Only one language (English)', 'wpvdemo' ),
							'plugins' => array () 
					) 
			);
			if (! empty ( $display_plugins ['required'] )) {
				foreach ( $display_plugins ['required'] as $plugin ) {
					$plugin = json_decode ( json_encode ( $plugin ) );
					//Filter $plugin
					$plugin= apply_filters('wpvdemo_filter_plugin_object_required',$plugin);
					$available_plugin_parameters_active = array (
							'Plugin_file_active' => $plugin->file,
							'Plugin_name_active' => $plugin->title 
					);
					$active = wpvdemo_is_active_plugin ( $available_plugin_parameters_active );
					$available_plugin_parameters = array (
							'Plugin_file' => $plugin->file,
							'Plugin_name' => $plugin->title,
							'Plugin_version' => $plugin->plugin_version_tested 
					);
					$found = wpvdemo_is_available_plugin ( $available_plugin_parameters );
					$version_compare_text = framework_installer_version_compare_strings ( $found );
					$plugin->active = $active;
					$plugin->found = (empty ( $found ['match'] ) ? false : $found ['match']);
					$found_status = $plugin->found;
					if (! ($found_status)) {
						// Plugins not found!
						$not_found [] = $plugin->title;
						
						// If plugin is not found, we want to help users how to obtain it very easily
						// Analyze plugin origins
						$plugin_origin = $this->wpvdemo_analyze_plugin_origins ( $plugin );
						
						// NOTE: If its not on the three sites (wpml.org, wp-types.com and wordpress.org), an empty $plugin_origin will be returned

						// Origin exists
						// Let's compose relevant messages
						$relevant_help_message = $this->wpvdemo_where_to_get_required_plugins ( $plugin_origin, $plugin );
						if (! (empty ( $relevant_help_message ))) {
							// Has relevant message to return
							$plugin->found_custom_message = $relevant_help_message;
						} else {
							$plugin->found_custom_message = '';
						}
					}
					$plugin->compatibility = (! empty ( $found ['compatibility'] ) && 'yes' == $found ['compatibility']);
					$plugin->compatibility_text = $version_compare_text;
					$versions ['nowpml'] ['plugins'] [] = $plugin;
				}
			}
			
			if (! empty ( $display_plugins ['optional'] )) {
				$versions ['wpml'] = array (
						'name' => 'wpml',
						'title' => __ ( 'Multilingual with WPML', 'wpvdemo' ),
						'plugins' => $versions ['nowpml'] ['plugins'] 
				);
				foreach ( $display_plugins ['optional'] as $plugin ) {
					$plugin = json_decode ( json_encode ( $plugin ) );
					$plugin = apply_filters('wpvdemo_filter_plugin_object_optional',$plugin);					
					$available_plugin_parameters_active = array (
							'Plugin_file_active' => $plugin->file,
							'Plugin_name_active' => $plugin->title 
					);
					$active = wpvdemo_is_active_plugin ( $available_plugin_parameters_active );
					$available_plugin_parameters = array (
							'Plugin_file' => $plugin->file,
							'Plugin_name' => $plugin->title,
							'Plugin_version' => $plugin->plugin_version_tested 
					);
					$found = wpvdemo_is_available_plugin ( $available_plugin_parameters );
					$version_compare_text = framework_installer_version_compare_strings ( $found );
					$plugin->active = $active;
					$plugin->found = (empty ( $found ['match'] ) ? false : $found ['match']);
					$found_status_wpml = $plugin->found;
					if (! ($found_status_wpml)) {
						$not_found_wpml [] = $plugin->title;
						
						// If plugin is not found, we want to help users how to obtain it very easily
						// Analyze plugin origins
						$plugin_origin = $this->wpvdemo_analyze_plugin_origins ( $plugin );

						// NOTE: If its not on the three sites (wpml.org, wp-types.com and wordpress.org), an empty $plugin_origin will be returned
						$relevant_help_message = $this->wpvdemo_where_to_get_required_plugins ( $plugin_origin, $plugin );
						if (! (empty ( $relevant_help_message ))) {
							// Has relevant message to return
							$plugin->found_custom_message = $relevant_help_message;
						} else {
							$plugin->found_custom_message = '';
						}
					}
					
					$plugin->compatibility = (! empty ( $found ['compatibility'] ) && 'yes' == $found ['compatibility']);
					$plugin->compatibility_text = $version_compare_text;
					$versions ['wpml'] ['plugins'] [] = $plugin;
				}
			}
			
			$refsite->versions = ( object ) $versions;
			
			// TODO: is refsite installed ?
			// check option 'wpvdemo' to identify if each refsite is installed
			$refsite->installed = false;
			
			$refsite->display_plugins = $display_plugins;
			$refsite->authorAndURI = 'OnTheGoSystems';
			
			//Order of this refsite
			$refsite->sequence_data =apply_filters('wpvdemo_refsites_order_sequence',1000,$refsite);
			$refsite->actions = new stdClass ();
			$refsite->actions->install = ! $refsite->installed;
			$refsite->actions->activate = $installation_site_url;
			
			// Bind Installer $required_activated_plugin_status with special plugin requirements not covered with Installer
			if (isset ( $not_found )) {
				if (! (empty ( $not_found ))) {
					// OK some required pugins are not meet
					$final_required_activated_plugin_status = FALSE;
				} else {
					// Looks like special plugin requirements are meet
					if ($required_activated_plugin_status) {
						$final_required_activated_plugin_status = TRUE;
					} else {
						$final_required_activated_plugin_status = FALSE;
					}
				}
			}
			
			// Check if site is empty or not
			if (function_exists ( 'wpvdemo_double_check_site_is_empty' )) {
				
				$site_is_empty = wpvdemo_double_check_site_is_empty ();
				
				if (! ($site_is_empty)) {
					// Override
					$final_required_activated_plugin_status = false;
				}
			}
			
			// Check if site has WPML optional plugins activated before import
			if (function_exists ( 'wpvdemo_optional_plugins_activated_before_import' )) {
				
				$wpml_optional_plugins_activated = wpvdemo_optional_plugins_activated_before_import ( true );
				
				if ($wpml_optional_plugins_activated) {
					// Yes, some WPML plugins are activated.
					/** Framework installer 1.8.2+ WPML plugins are no longer network activated in Discover-WP */
					$final_required_activated_plugin_status = false;					
				}
			}
			
			// Check for permission related issues,etc.
			if (function_exists ( 'wpvdemo_check_requirements' )) {
				
				$requirements_overall = array ();
				$requirements_overall = wpvdemo_check_requirements ();
				if (! (empty ( $requirements_overall ))) {
					$has_permission_issues = array ();
					
					foreach ( $requirements_overall as $k => $v ) {
						if (! ($v)) {
							$has_permission_issues [] = 'permission_issues_found';
						}
					}
					
					if (! (empty ( $has_permission_issues ))) {
						// Override
						$final_required_activated_plugin_status = false;
					}
				}
			}
			
			// Override all plugin activation and installation status when the user is not connected to the Internet
			// Let's throw this flag only when the import is not yet done and not on Discover-WP
			if (! ($is_discoverwp_multisite)) {
				// Standalone, et's check if import is done.
				$check_import_is_done_connected = get_option ( 'wpv_import_is_done' );
				if ('yes' != $check_import_is_done_connected) {
					// Import not yet done
					// Let's check first if $final_required_activated_plugin_status is set to TRUE so we can override
					if ($final_required_activated_plugin_status) {
						
						// No issues with plugin, now let's check the internet connection
						if (! ($this->wpvdemo_user_internet_connected_referencesites ())) {
							// No connection, override
							$final_required_activated_plugin_status = false;
						}
					}
				}
			}
			if (! ($is_discoverwp_multisite)) {
				// Standalone, et's check if import is done.
				$check_import_is_done_connected = get_option ( 'wpv_import_is_done' );
				if ('yes' != $check_import_is_done_connected) {
					
					// Import not yet done
					// Let's check first if $final_required_activated_plugin_status is set to TRUE so we can override
					if ($final_required_activated_plugin_status) {
						
						// No issues with plugin, now let's check if the repository is not corrupted
						if (function_exists ( 'WP_Installer' )) {
							$repository_instance = WP_Installer ();
							if (is_object ( $repository_instance )) {
								if (isset ( $repository_instance->settings ['repositories'] )) {
									$the_installer_repo = $repository_instance->settings ['repositories'];
									if (empty ( $the_installer_repo )) {
										// Corrupted
										$final_required_activated_plugin_status = false;
										add_action ( 'admin_notices', array (
												$this,
												'wpvdemo_show_installer_corrupt_notices' 
										) );
									} else {										
									    //Here we have non-empty repo but let's checked if we have Toolset on it (requires for embedded downloading)
									    if (!(isset( $the_installer_repo['toolset']))) {
									    	$final_required_activated_plugin_status = false;
									    	add_action ( 'admin_notices', array (
									    			$this,
									    			'wpvdemo_show_installer_corrupt_notices'
									    	) );									    	
									    }										
									}
								} else {
									// Settings does not have repos, could be corrupted
									// Corrupted
									$final_required_activated_plugin_status = false;
									add_action ( 'admin_notices', array (
											$this,
											'wpvdemo_show_installer_corrupt_notices' 
									) );
									if (defined ( 'OTGS_DISABLE_AUTO_UPDATES' )) {
										if (OTGS_DISABLE_AUTO_UPDATES) {
											// This is disabled, that's why this setting is not set. Show warning.
											add_action ( 'admin_notices', array (
													$this,
													'wpvdemo_detect_installer_disable_auto_updates' 
											) );
										}
									}
								}
							}
						}
					}
				}
			}
			
			if (! ($this->wpdemo_not_multisite ())) {
				// Unsupported multisite
				if ($final_required_activated_plugin_status) {
					$final_required_activated_plugin_status = false;
				}
			}
			
			// Special handling for Discover WP multisite
			$refsite->actions->is_discoverwp = false;
			if ($is_discoverwp_multisite) {
				$refsite->actions->is_discoverwp = true;
				$refsite_id = $refsite->id;
				$site_id = intval ( $refsite_id );
				if ($site_id > 0) {
					$download_button_default = '<a href="#" class="button-primary" disabled="disabled" id="wpvdemo-download-button-' . $site_id . '">Download</a>';
					$download_button = apply_filters ( 'wpvdemo_download_button', $download_button_default, $site_id );
					$refsite->actions->createsite = $download_button;
				}
			}
			
			$refsite->actions->required_activated_plugin_status = $final_required_activated_plugin_status;
			$refsite->actions->preview = (empty ( $refsite->site_url ) ? false : $refsite->site_url);
			
			/**
			 * Don't show link to tutorial buttons if there is no tutorial for this site
			 */
			$refsite->actions->has_tutorial = false;
			
			if (isset ( $refsite->tutorial_url )) {
				// Set
				if (! (empty ( $refsite->tutorial_url ))) {
					// Has data
					$refsite_tutorial = $refsite->tutorial_url;
					$refsite_tutorial = trim ( $refsite_tutorial );
					
					if ('#' != $refsite_tutorial) {
						$refsite->actions->has_tutorial = true;
						$refsite->actions->tutorial = $refsite->tutorial_url;
					}
				}
			}
			
			// Catch multilingual reference sites
			$refsite->actions->has_multilingual = false;
			$refsite->actions->has_all_wpml_plugins = false;
			
			if (isset ( $display_plugins ['optional'] )) {
				$optional = $display_plugins ['optional'];
				if (! (empty ( $optional ))) {
					$refsite->actions->has_multilingual = true;
					
					if (isset ( $not_found_wpml )) {
						if (empty ( $not_found_wpml )) {
							// Not all WPML plugins are there
							$refsite->actions->has_all_wpml_plugins = true;
						}
					}
				}
			}
			// Have installation requisite messages translable
			if (! ($this->wpdemo_not_multisite ())) {
				
				$refsite->actions->requisites_not_meet_msg = __ ( 'Framework Installer is not yet tested to work in other multisites installation except Discover-wp.com. Please switch to single-site mode to import reference sites. ', 'wpvdemo' );
			} elseif (! ($required_activated_plugin_status)) {
				// Plugin requirements are not meet at all
				// We only want to display reset messages only when it make sense
				if ($site_is_empty) {
					// Site is empty, don't show reset messages
					$refsite->actions->requisites_not_meet_msg = __ ( "This demo site can't be installed. Please check that Views, Types, Module Manager and CRED Frontend-Editor have been installed and you are running in a clean WordPress installation.", "wpvdemo" );
				} else {
					// Site is not empty
					$refsite->actions->requisites_not_meet_msg = __ ( "This demo site can't be installed. Please check that Views, Types, Module Manager and CRED Frontend-Editor have been installed and you are running in a clean WordPress installation. To reset your Website and Database from a previous installation, you can use the reset feature under 'Toolset &ndash;&gt; Settings &ndash;&gt; Reset Demo Site'.", "wpvdemo" );
				}
			} elseif (($required_activated_plugin_status) && (! ($final_required_activated_plugin_status))) {
				// Here we have cases where plugins are installed but other requirements are not meet
				// Let's check if import is done
				$check_import_is_done = get_option ( 'wpv_import_is_done' );
				if ('yes' == $check_import_is_done) {
					// Import is already done, site is not empty
					$refsite->actions->requisites_not_meet_msg = __ ( "This demo site can't be installed. You need a fresh WordPress installation to install a demo site. To reset your Website and Database from a previous installation, you can use the reset feature under 'Toolset &ndash;&gt; Settings &ndash;&gt; Reset Demo Site'.", "wpvdemo" );
				} else {
					// Flagged messages for other issues
					// Here we need to show 'reset' messages only if necessary
					if ($site_is_empty) {
						// Yes site is empty, show reset messages does not make sense
						$refsite->actions->requisites_not_meet_msg = __ ( "This demo site can't be installed. Please ensure that you meet all plugin and site requirements before installing a demo site.", "wpvdemo" );
					} else {
						// Site is not empty
						// Let's show reset messages too
						$refsite->actions->requisites_not_meet_msg = __ ( "This demo site can't be installed. Please ensure that you meet all plugin and site requirements before installing a demo site. To reset your Website and Database from a previous installation, you can use the reset feature under 'Toolset &ndash;&gt; Settings &ndash;&gt; Reset Demo Site'.", "wpvdemo" );
					}
				}
			} else {
				// Here we have meet all plugin requirements (Types Views CRED and module manager
				// Let's check if this site is multilingual
				if (isset ( $refsite->actions->has_multilingual )) {
					
					$is_multilingual = $refsite->actions->has_multilingual;
					if ($is_multilingual) {
						// This site is multilingual, now let's checked if user has all the plugins installed and ready.
						if (isset ( $refsite->actions->has_all_wpml_plugins )) {
							$has_all_wpml_plugins_installation = $refsite->actions->has_all_wpml_plugins;
							if (! ($has_all_wpml_plugins_installation)) {
								// WPML plugins are not all installed
								$refsite->actions->requisites_not_meet_msg = __ ( "In order to proceed with the demo site installation for the multilingual version, the above mentioned plugins are required. Please download and have them available in your plugin directory.", "wpvdemo" );
							}
						}
					}
				}
			}
			
			$aux_array [] = ( array ) $refsite;
			;
		}

		//Customized sorting based on filter settings
		usort($aux_array, function ($a, $b) { return $a['sequence_data'] - $b['sequence_data']; });
		
		if ($filtered) {
			return apply_filters('wpvdemo_merge_refsites',$aux_array);
		} else {
		    return $aux_array;
		}
	}

    function is_discoverwp() {

    	$ret=false;    	
    	if (is_multisite()) {    		
    		//Load API
    		require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
    		    			
    		//Get authorized training sites dev and live
    		$authorized_training_sites=apply_filters('wpvdemo_discoversite_already_network_activated',array());    	
    		$network_main_url= network_site_url();
    		$parts = parse_url($network_main_url);    		
    		if (isset($parts['host'])) {    			
    			$the_host=$parts['host']; 
    			if (is_string($the_host)) {
    				$the_host=trim($the_host);
    				if (!(empty($the_host))) {
    					if (in_array($the_host,$authorized_training_sites)) {
    						$ret=true;
    					}
    				}   				
    			}
    			
    		}
    	}
    	
    	return $ret;
    }

    function get_refsites($refresh_check = true){

        $xml = get_option('wpvdemo-index-xml');
        $time = get_option('wpvdemo-refresh', 0);
        $config_file = defined('WPVDEMO_DEBUG') && WPVDEMO_DEBUG ? WPVDEMO_DOWNLOAD_URL . '/demos-index-debug.xml' : WPVDEMO_DOWNLOAD_URL . '/demos-index.xml';

        if (defined('WPVDEMO_CANONICAL_SITES')) {
        	if (WPVDEMO_CANONICAL_SITES) {
				//We may want debugging mode but we want only the canonical sites
        		$config_file = WPVDEMO_DOWNLOAD_URL . '/demos-index.xml';
        		
        	}
        }
        
        $wait = 43200;
        if(defined('WPVDEMO_DEBUG') && WPVDEMO_DEBUG) {
            $wait = 60;
        }

        if (!$xml || ($refresh_check && time() - intval($time) >$wait)) {

            //Use file_get_contents to fetch XML file.
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

    protected function admin_check_required_site_settings($settings, $show_error = true) {
        if (function_exists('wpvdemo_admin_check_required_site_settings')) {
            return wpvdemo_admin_check_required_site_settings($settings, $show_error);
        }
        return true;
    }

    function refsite_install_progress() {
        //if (function_exists('wpvdemo_admin_menu_import_hook')) {
            $this->wpvdemo_admin_menu_import_hook();
        //}
    }

    /**
     * Triggers import action.
     */
    function refsite_install() {
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
            //Standalone mode import or Discover-WP import
            /** Framework installer 1.8.2 + We allow non-multilingual import of a multilingual site version in Discover-WP */
            $the_version_installed= get_option('wpvdemo_the_version_installed');
            if (!($the_version_installed)) {
            	//Not yet define
            	update_option('wpvdemo_the_version_installed',$versions);
            } 
            
			//Delete any unnecessary WPML settings before doing import
			delete_option('icl_sitepress_settings');
            
			//Site id
			$site_id=$_POST['site_id'];			

			//Import starts here...
            wpvdemo_import($site_id, $_POST['step'],$versions);
        }
        die();
    }

    public function wpvdemo_save_refsite_installed_version($site_id) {
    	
    	$site_id=intval($site_id);
    	$wpvdemo_refsite_installed_version_number=get_option('wpvdemo_refsite_installed_version_number');
    	if ($site_id > 0) {
    		global $frameworkinstaller;
    		$array_refsite_version_imported=array();
    		
    		//Get shortname equivalent
    		$imported_site_shortname_data = $frameworkinstaller->wpvdemo_get_shortname_given_id($site_id);
    		if ((!(empty($imported_site_shortname_data))) && (!(isset($wpvdemo_refsite_installed_version_number[$imported_site_shortname_data])))) {    			
    			//Refsite version for this site is not yet set, proceed...
    			//Get download URL of this refsite
    			$download_url_refsite_global=$frameworkinstaller->wpvdemo_get_sitespecific_info_given_id($site_id,'download_url');
    			$xml_file_refsite_version = $download_url_refsite_global . '/refsite_versions_tracker.xml';
    			
    			// Parse remote XML
    			$data_refsite_version_imported = wpv_remote_xml_get ( $xml_file_refsite_version  );
    			if ($data_refsite_version_imported) {
    				$simplexml_refsite_version_imported = simplexml_load_string ( $data_refsite_version_imported );    			
    				$json_refsite_version_imported = json_encode($simplexml_refsite_version_imported);
    				$array_refsite_version_imported = json_decode($json_refsite_version_imported,TRUE);
    				if ((is_array($array_refsite_version_imported)) && (!(empty($array_refsite_version_imported)))) {
    					
    					//Refsite version imported, saved
    					$site_short_name_from_xml=key($array_refsite_version_imported);
    					$site_version_number_from_xml=reset($array_refsite_version_imported);
    					$wpvdemo_refsite_installed_version_number[$site_short_name_from_xml]=$site_version_number_from_xml;
    					update_option('wpvdemo_refsite_installed_version_number',$wpvdemo_refsite_installed_version_number);
    					
    					//Hook to define site versions global
    					do_action('wpvdemo_import_refsite_versions');
    				}    			
    			} 
    		}
    	}   	
    	
    }
    public function installer_plugin_name_override_func($name) {

		return $name;

    }
    //Import Layouts
    public function wpvdemo_identify_layouts_posttype_and_import($source_dir,$refsite_file) {

    	global $wpdb, $wpddlayout,$wpddlayout_theme;

    	if ((is_dir($source_dir)) &&  (method_exists($wpddlayout_theme,'layout_handle_save')))  {

    	    //Retrieved all Layout files inside the source directory
    		$layouts = glob($source_dir . '/*.ddl');

    		/** IMPORT LAYOUTS ASSIGNED TO POST TYPES FIRST */
			$importing_layouts_with_wpml=false;
			
			/** Prepare pre-import Layout IDS from reference site */
			if (( defined( 'ICL_SITEPRESS_VERSION' ) ) &&
					( defined('WPDDL_VERSION') ) &&
					( defined( 'WPML_ST_VERSION' ) ))
			{
				$importing_layouts_with_wpml=true;				
			}
			
			if ($importing_layouts_with_wpml) {	
				$refsite_url_base=dirname($refsite_file);
				$old_layouts_id_xml=$refsite_url_base.'/layouts_wpml_export_id.xml';
				
				//Define an array of OLD Layouts to NEW Layout (after import for tracking)
				$original_layouts_preimported=array();

				 // Parse remote XML
				 $data = wpv_remote_xml_get ( $old_layouts_id_xml );
				 
				 if ($data) {
				 	$layouts_old_id_xml = simplexml_load_string ( $data );
				 	$layouts_old_id_data = wpv_admin_import_export_simplexml2array ( $layouts_old_id_xml );
				 }		
			}
    		foreach ($layouts as $layout) {
    			//Let's loop and identify layouts assigned to post type

    			$file_details = pathinfo($layout);
    			$layout_name = $file_details['filename'];
    			$layout_json = file_get_contents($layout);
    			$layout_array = json_decode(str_replace('\\\"', '\"', $layout_json));
    			$layout_array= (array)$layout_array;
    			
    			if ((is_array($layout_array)) && (!(empty($layout_array)))) {

    				//Post Types
    				if(  isset($layout_array['post_types']) && count( $layout_array['post_types'] ) > 0 ){

    					//This layout is assigned to a post type
    					//Import
    					   $overwrite=TRUE;
    					   $delete= TRUE;
    					   $overwrite_assignment=TRUE;
    					   
    					   /** CALL LAYOUT IMPORT FUNCTION */
        				   $ret = $wpddlayout_theme->layout_handle_save( $layout_json, $layout_name, $overwrite, $delete, $overwrite_assignment );
        				   $id_ret=intval($ret);
        				   if ($id_ret > 0) {
								
								//Layout successfully imported and assigned to post type
	        				   	if ($importing_layouts_with_wpml) {
	        				   		
	        				   		//Retrieve slug of this layout
	        				   		$old_layouts_pt_slug=$layout_array['slug'];
	        				   		
	        				   		//Retrieved the old ID given this slug
	        				   		if (isset($layouts_old_id_data[$old_layouts_pt_slug])) {
	        				   			$old_layouts_pt_id=$layouts_old_id_data[$old_layouts_pt_slug];
	        				   			$old_layouts_pt_id =intval($old_layouts_pt_id);
	        				   			if ($old_layouts_pt_id > 0) {
	        				   				$original_layouts_preimported[$old_layouts_pt_id]=$id_ret;
	        				   			}	        				   			
	        				   		}
	        				   		
	        				   		
	        				   	}
        				   		
								//Delete from source directory
        				   		if(is_file($layout)) {
        				   		//Delete file
        				   			unlink($layout);
        				   		}
        				   }
    				}
    			}

    		}
    		/** LET'S IMPORT REMAININING LAYOUTS FOR SINGLE POSTS AND PAGES, ETC. */
    		$layouts = glob($source_dir . '/*.ddl');
    		foreach ($layouts as $layout) {
    			//Let's loop and identify layouts assigned to post type
    			
    			$file_details = pathinfo($layout);
    			$layout_name = $file_details['filename'];
    			$layout_json = file_get_contents($layout);
    			$layout_array = json_decode(str_replace('\\\"', '\"', $layout_json));
    			$layout_array= (array)$layout_array;

    			if ((is_array($layout_array)) && (!(empty($layout_array)))) {
    				
    				//Import
    				$overwrite=TRUE;
    				$delete= TRUE;
    				$overwrite_assignment=TRUE;
    				
    				 /** CALL LAYOUT IMPORT FUNCTION */
    				$ret = $wpddlayout_theme->layout_handle_save( $layout_json, $layout_name, $overwrite, $delete, $overwrite_assignment );
    				$id_ret=intval($ret);
    				if ($id_ret > 0) {
    					
    					if ($importing_layouts_with_wpml) {
    						//Track old layouts and new layouts equivalent
    						$old_layouts_single_slug=$layout_array['slug'];
    						
    						//Retrieved the old ID given this slug
    						if (isset($layouts_old_id_data[$old_layouts_single_slug])) {
    							$old_layouts_single_id=$layouts_old_id_data[$old_layouts_single_slug];
    							$old_layouts_single_id=intval($old_layouts_single_id);    							
    							$original_layouts_preimported[$old_layouts_single_id]=$id_ret;
    						}
    					}
    					    					
    					//Layout successfully imported and assigned to post type
    					//Delete from source directory
    					if(is_file($layout)) {
    						//Delete file
    						unlink($layout);
    					}
    				}
    			}

    		}
    		
    		//Track old layouts ID in database for later processing
    		update_option('wpvdemo_preimport_layoutsid',$original_layouts_preimported);
    		
    		/** LET'S IMPORT LAYOUTS CSS */
    		$layouts_css=  glob($source_dir . '/*.css');
    		foreach ($layouts_css as $layout_css) {

    			$data = file_get_contents( $layout_css );
    			$overwrite= TRUE;
    			/** CALL LAYOUT CSS IMPORT FUNCTION */
    			$save = $wpddlayout->css_manager->import_css( $data, $overwrite );
    			if (($save) && (is_file($layout_css))) {
    				//Delete file
    				unlink($layout_css);
    			}
    		}
    	}

    }
    public function wpvdemo_refsite_source() {
    	$source='ref.wp-types.com';
    	//Check for overrides
    	if (defined('WPVDEMO_DOWNLOAD_URL'))  {
    		$download_url= WPVDEMO_DOWNLOAD_URL;
    		if (!(empty($download_url))) {
    			//Download defined
    			$parsed_url= parse_url($download_url);
    			if (isset($parsed_url['host'])) {
    				$source= $parsed_url['host'];
    			}
    		}
    	}
    	return $source;
    }
    public function wpvdemo_refsite_child_site_source($file) {

    	$child_site_source='';
    	$original_host ='ref.wp-types.com';

    	//Check for overrides
    	if (defined('WPVDEMO_DOWNLOAD_URL'))  {
    		$download_url= WPVDEMO_DOWNLOAD_URL;
    		if (!(empty($download_url))) {
    			//Download defined
    			$parsed_url= parse_url($download_url);
    			if (isset($parsed_url['host'])) {
    				$original_host= $parsed_url['host'];

    			}
    		}
    	}
    	if (!(empty($file))) {
    		$dirname= basename(dirname($file));
    		$child_site_source= 'http://'.$original_host.'/'.$dirname;
    	}

    	return $child_site_source;
    }

    public function wpvdemo_user_internet_connected_referencesites() {

    	$connected=@fsockopen("d7j863fr5jhrr.cloudfront.net",80);

    	$is_conn=false;

    	if ($connected) {

    		$is_conn=true;

    	}

    	return $is_conn;

    }
    public function wpvdemo_user_internet_warning() {
    	$is_discover=$this->is_discoverwp();
    	if (!($is_discover)) {
    		//Let's not apply this check to Discover-wp
	    	if (!($this->wpvdemo_user_internet_connected_referencesites())) {
	?>
<div class="error">
	<p><?php _e('You need to be connected to the Internet to download demo sites. Or if you are sure you are connected, our servers is down at the moment. Please try again later.','wpvdemo');?>
				</p>
</div>
<?php
	    	}
    	}
    }
    public function wpvdemo_show_installer_corrupt_notices() {
    	$is_discover=$this->is_discoverwp();
    	if (!($is_discover)) {
    		//Let's not apply this check to Discover-wp
    			?>
<div class="error">
	<p><?php _e('It looks like your Installer settings in the database is corrupted. Please reset the site by going to "Toolset" -> "Settings -> "Reset demo site". This is required before you can install a new reference site.','wpvdemo');?>
    			</p>
</div>
<?php
    	 }
    }

    public function wpvdemo_analyze_plugin_origins($plugin) {

    	$origin='';

    	if (is_object($plugin)) {


    		//Get URL
    		if (isset($plugin->url)) {
    			$plugin_url =$plugin->url;
    			if (!(is_object($plugin_url))) {
    				//Not an object, safe to use
	    			$plugin_url = (string)$plugin_url;
	    			if (!(empty($plugin_url))) {
	    				if ((strpos($plugin_url, 'wpml.org') !== false)) {
	    					//WPML.org plugin
	    					$origin='wpmlorg';
	    				} elseif ((strpos($plugin_url, 'wordpress.org') !== false)) {
	    					//WordPress.org plugin
	    					$origin='wordpressorg';
	    				} elseif ((strpos($plugin_url, 'wp-types.com') !== false)) {
	    					$origin='wptypescom';
	    				}
	    			}
    			}
    		}

    	}
    	//If its not on the three sites, return empty origin for now.
    	return $origin;

    }

    public function wpvdemo_where_to_get_required_plugins($origins,$plugin) {

    	//Please go to your Toolset account, download the plugin and install it on the current site.
    	//Formulate account and specific URLs to obtain a copy of plugins
    	//Also at this point , all origins are defined so plugin->url should be set, since they are already validated previously
    	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
    	$wordpress_plugin_url='';
    	$help_message ='';
    	$no_plugins_page=false;

    	if ('wordpressorg' == $origins) {
    		//WordPress.org is very specific about its plugin page
    		$wordpress_plugin_url= $plugin->url;
    	}

    	$source_url = array(
    			'wpmlorg'      => 'https://wpml.org/account/downloads',
    			'wptypescom'   => 'https://wp-types.com/account/downloads/',
    			'wordpressorg'=> $wordpress_plugin_url
    	);

    	//Handle plugins without plugins page.
    	//Let's link to change log
    	if (isset($plugin->url)) {
    		$boolean_url_passed=$plugin->url;
    		if (!($boolean_url_passed)) {    				
    			$no_plugins_page=true;
    		}    		
    	}
    	
    	$source_text = array(
    			'wpmlorg'      => 'WPML Downloads',
    			'wptypescom'   => 'Toolset Downloads',
    			'wordpressorg'=> 'WordPress.org plugins page'
    	);

    	if (((isset($source_url[$origins])) && (isset($source_text[$origins]))) && (!($no_plugins_page))) {

			if 	(('wpmlorg' == $origins) || ('wptypescom' == $origins)) {
				//Toolset or WPML site
				$help_message= __('Please go to your','wpvdemo').' '.
				'<a style="color:red" target="_blank" href="'.$source_url[$origins].'">'.$source_text[$origins].'</a>'.' '.__('page','wpvdemo').', '.__('download the plugin and install it on the current site. Do not activate it yet','wpvdemo').'.';
			} elseif ('wordpressorg' == $origins) {
				//WordPress.org
				$help_message= __('Please go to the','wpvdemo').' '.
						'<a style="color:red" target="_blank" href="'.$source_url[$origins].'">'.$source_text[$origins].'</a>'.' '.', '.__('download the plugin and install it on the current site. Do not activate it yet','wpvdemo').'.';

			}
    	} elseif ($no_plugins_page) {
    		
    		//Retrieve plugin name
    		$plugin_name_passed=$plugin->title;
    		if (is_string($plugin_name_passed)) {
	    			$plugin_name_passed=trim($plugin_name_passed);
	    		
	    		//No plugins pages set. Let's get the affected plugin slugs
	    		$plugins_without_pages=apply_filters('wpvdemo_plugins_without_page',array());
	    		$given_downloads_url = array(
	    				'wpmlorg'      => 'https://wpml.org/download/',
	    				'wptypescom'   => 'https://wp-types.com/download/'
	    		);
	    		
	    		if (isset($plugins_without_pages[$plugin_name_passed])) {
	    			$changelog_slug= $plugins_without_pages[$plugin_name_passed]['slug'];
	    			$api_origins=$plugins_without_pages[$plugin_name_passed]['origin'];
	    			$baseref_downloads=$given_downloads_url[$api_origins];
	    			$complete_downloads_url = $baseref_downloads.$changelog_slug;
	    			$download_text=__('download','wpvdemo');
	    			$help_message='- '.'<a style="color:red" target="_blank" href="'.$complete_downloads_url.'">'.$download_text.'</a>';    			
	    		}
    		}   			
    	}

    	return $help_message;
    }

    public function wpvdemo_no_support_multisite_warning() {
    	$is_discover=$this->is_discoverwp();
    	if (!($is_discover)) {
    		//This is not discover-wp!, let's checked if we are on multisite
    		if (is_multisite()) {
    			//So we are on a multisite
    			?>
<div class="error">
	<p><?php _e('Framework Installer is not yet tested to work in other multisites installation except Discover-wp.com. Please switch to single-site mode to import reference sites.','wpvdemo');?>
    				</p>
</div>
<?php
    	    }
        }
    }

    public function wpdemo_not_multisite() {
    	$bool=true;
    	$is_discover=$this->is_discoverwp();
    	if (!($is_discover)) {
    		//This is not discover-wp!, let's checked if we are on multisite
    		if (is_multisite()) {
    			//So we are on a multisite
				$bool=false;
    	     }
    	}
    	return $bool;
    }
    
    public function wpvdemo_remove_wpml_notices_discoverroot_site() {
   		
   		$icl_admin_messages= get_option('icl_admin_messages');
   		if ($icl_admin_messages) {
   			if (((isset($icl_admin_messages['messages']['_st_string_in_wrong_context_warning']['hidden']))) &&
   			   ((isset($icl_admin_messages['messages']['3.2_upgrade_notice']['hidden']))))
   			{
   				//Set, ensure dismiss is TRUE
   				$icl_admin_messages['messages']['_st_string_in_wrong_context_warning']['hidden']=TRUE;
   				$icl_admin_messages['messages']['3.2_upgrade_notice']['hidden']=TRUE;
   				
   		    	//Let's update back
   				update_option('icl_admin_messages',$icl_admin_messages);
   				
   			} elseif (isset($icl_admin_messages['messages']['_st_string_in_wrong_context_warning']['hidden'])) {
   				
   				$icl_admin_messages['messages']['_st_string_in_wrong_context_warning']['hidden']=TRUE;
   				update_option('icl_admin_messages',$icl_admin_messages);
   			}
   		}   	 	
    }
    
    public function wpvdemo_if_using_wpml_three_two() {
    
    	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
    		$icl_sitepress_version=ICL_SITEPRESS_VERSION;
    		if (version_compare($icl_sitepress_version, '3.2', '<')) {
    
    			//WPML 3.2 and below
    			return FALSE;
    
    		} else {
    
    			//WPML 3.2 and beyond
    			return TRUE;
    		}
    	}
    	//Not activated, return FALSE
    	return FALSE;
    }
    
    public function wpvdemo_wpml_presetup_support() {
    	
    	//Let's check if we are on discover-wp
    	$is_discoverwp=$this->is_discoverwp();
    	if ($is_discoverwp) {
    	   //Let's check if we are done with the import
    		$check_import_is_done= get_option('wpv_import_is_done');
    		$wpml_three_two= $this->wpvdemo_if_using_wpml_three_two();
    		
    		if (('yes' == $check_import_is_done) && ($wpml_three_two)) {
		        //Import done
    			//Removing notice to avoid pre-setup issues
    			global $WPML_String_Translation;
    			remove_action( 'admin_notices', array( $WPML_String_Translation, '_wpml_not_installed_warning' ) );
    		}

    	}
    }
    
    //Returns TRUE if plugins are inactive otherwise FALSE
    public function wpvdemo_detect_inactive_plugins() {
    	$ret=FALSE;
    	if (function_exists('WP_Installer')) {
    		$repository_instance= WP_Installer();
    		if (is_object($repository_instance)) {
    			//Get installer embedded plugin instance
    			if (isset($repository_instance->installer_embedded_plugins)) {
    				$embedded_plugins_instance=$repository_instance->installer_embedded_plugins;
    				if (is_object($embedded_plugins_instance)) {
    					if (method_exists($embedded_plugins_instance,'get_inactive_plugins')) {
    						$inactive_plugins=$embedded_plugins_instance->get_inactive_plugins();
    						$count= count($inactive_plugins);
    						if ($count > 0) {
    					
    							$ret=TRUE;
    						}
    					} 					  					
    				}
    			} 
    		}
    	}
    	return $ret;
    	 
    }
    
    //Deps.xml file location
    public function wpvdemo_deps_xml_location($loc) {
    	 
    	if (defined('WPVDEMO_DEPS_XML_FILE')) {
    		$deps_xml= WPVDEMO_DEPS_XML_FILE;
    		if (file_exists($deps_xml)) {
    			//Configuration exists
    			if (!(in_array($deps_xml,$loc))) {
    				//Not in array
    				$loc[]=$deps_xml;
    			}
    		}
    	}
    	 
    	return $loc;
    }
    
    public function wpvdemo_detect_installer_disable_auto_updates() {
    	$is_discover=$this->is_discoverwp();
    	if (!($is_discover)) {
    		//Let's not apply this check to Discover-wp
    		?>
        <div class="error">
        	<p><?php _e('We detected that you are setting a constant OTGS_DISABLE_AUTO_UPDATES in your wp-config. Please delete this constant and reset the demo site again.','wpvdemo');?> 
            			</p>
        </div>
        <?php 			
        }	
    }
	public function wpvdemo_configure_string_packages_func() {

		require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
		 
		//Get sites requiring WPML string packages update after import
		$get_sites=apply_filters('wpvdemo_wpml_string_packages_update',array());
		 
		//Retrieve the origin of this imported site
		$origin= get_option('wpvdemo_refsites_origin_slug');
		 
		if (in_array($origin,$get_sites)) {
		
			//Check if the import is not completed
			$check_import_is_done= get_option('wpv_import_is_done');
	    	if ('yes' == $check_import_is_done) {
			    	 
			    	//Import is done but string packages are not yet updated
				    	if ((wpvdemo_wpml_is_active()) && (wpvdemo_layouts_is_active()))
				    	{
				    		//Step1.) Retrieved wpvdemo_mapped_translation_packages
				    		$wpvdemo_mapped_translation_packages= get_option( 'wpvdemo_mapped_translation_packages' );
				    		
				    		//Step2.) Retrieved string packages data from ref sites
				    		if (defined('WPVDEMO_DOWNLOAD_URL')) {
				    			$wpvdemo_download_url=WPVDEMO_DOWNLOAD_URL;
				    			$string_packages_xml_file= $wpvdemo_download_url.'/'.$origin.'/wpml_stringpackages_settings.xml';
				    			$string_packages_data=$this->wpvdemo_retrieved_string_packages_refsite($string_packages_xml_file);
				    			
				    		
				    		//Step3.) Get pre-imported layouts ID
				    			$pre_imported_layout_ids= get_option('wpvdemo_preimport_layoutsid');
				    			foreach ($wpvdemo_mapped_translation_packages as $k=>$v) {
				    				
				    				//Step4.) Get element ID of this package from the ref site given its trid
				    				if ((is_array($wpvdemo_mapped_translation_packages)) && (!(empty($wpvdemo_mapped_translation_packages)))) {

				    					$key_found=$k;
				    					if ($key_found) {
				    						//Step5.) Given the element ID of the package from the refsite, retrieve its old associated Layouts ID
				    						$key_found=intval($key_found);
											$associated_layouts_id=$this->wpvdemo_retrieved_associated_layouts_id_refsite($string_packages_data,$key_found);
											
											//Step6.) Given the old Layouts ID, retrieve the imported Layouts ID
											if (isset($pre_imported_layout_ids[$associated_layouts_id])) {
												
											   $imported_layouts_id= $pre_imported_layout_ids[$associated_layouts_id];
											   //Step7.) Given the imported layouts ID, retrieved the imported string package ID equivalent
											   global $wpdb;
											   $wpdb->suppress_errors = true;
											   $icl_string_packages_table= $wpdb->prefix."icl_string_packages";
											   $icl_translations_table= $wpdb->prefix."icl_translations";
											   $icl_translation_status_table= $wpdb->prefix."icl_translation_status";
											   $package_table_exist=$this->wpvdemo_icl_string_package_table_exist();
											   $translation_tables_exist=$this->wpvdemo_validate_icl_required_db_tables();
											   if (($package_table_exist) && ($translation_tables_exist)) {

												   $new_package_element_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $icl_string_packages_table WHERE name = %d",
												   		$imported_layouts_id));
												   
												   //Step8.) Given its new package element ID, retrieve the imported trid of this package
												   $new_trid= $wpdb->get_var($wpdb->prepare("SELECT trid FROM $icl_translations_table WHERE element_id = %d AND element_type='package_layout'",
												   		$new_package_element_id));
	
												   //Step9.) Update the old trid to this new trid
												   $old_trid= $v['map_trid'];
												   $new_trid_int=intval($new_trid);
												   if ($new_trid_int > 0) {										   
													   $trid_updated=$wpdb->query (
													   		$wpdb->prepare (
													   				"UPDATE $icl_translations_table SET trid=%d WHERE trid=%d",
													   				$new_trid,$old_trid
													   		)
													   );
													   //Step10.) Retrieve old translation ID associated with this old trid
													   $wpvdemo_mapped_translated_packages = get_option('wpvdemo_mapped_translated_packages');
													   
													   if (isset($wpvdemo_mapped_translated_packages[$old_trid])) {
													   	  $old_translation_id= $wpvdemo_mapped_translated_packages[$old_trid];
													   	  
													   	  //Step11.) Retrieved new translation ID
													   	  $new_translation_id= $wpdb->get_var($wpdb->prepare("SELECT translation_id FROM $icl_translations_table WHERE trid = %d AND source_language_code IS NOT NULL",
													   	  		$new_trid));											   	  
													   	
													   	  //Step12.) Update translation ID of the imported package items in translation status table													   	  
													   	  $status_id_updated=$wpdb->query (
													   	  		$wpdb->prepare (
													   	  				"UPDATE $icl_translation_status_table SET translation_id=%d WHERE translation_id=%d",
													   	  				$new_translation_id,$old_translation_id
													   	  		)
													   	  );											   	  
													   }
												   }
											}
											  							   
											}
				    					}
				    					
				    				}
				    				
				    			}
				    		}
				    		
				    	}
	    	}
		}		
		
	}
	public function wpvdemo_adjust_original_id_icltranslate() {
	
		require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
			
		//Get sites requiring WPML string packages update after import
		$get_sites=apply_filters('wpvdemo_wpml_string_packages_update',array());
			
		//Retrieve the origin of this imported site
		$origin= get_option('wpvdemo_refsites_origin_slug');
			
		if (in_array($origin,$get_sites)) {
	
			//Check if the import is not completed
			$check_import_is_done= get_option('wpv_import_is_done');
			if ('yes' == $check_import_is_done) {
				 
				//Import is done but string packages are not yet updated
				if ((wpvdemo_wpml_is_active()) && (wpvdemo_layouts_is_active()))
				{
					//Step1, get all packages ID
					global $wpdb;					
					$icl_string_packages_table= $wpdb->prefix."icl_string_packages";
					$package_table_exist=$this->wpvdemo_icl_string_package_table_exist();
					$translation_tables_exist=$this->wpvdemo_validate_icl_required_db_tables();					
					if (($package_table_exist) && ($translation_tables_exist)) {
						
						//Before we do any query we need to make sure this table exist.
						$package_ids = $wpdb->get_results ( "SELECT ID FROM $icl_string_packages_table", ARRAY_A );
						$package_ids_clean=wpvdemo_all_purpose_id_cleaner_func($package_ids);
		
						//Step2, loop each of the package IDs then get the translated IDS
						$icl_translation_table= $wpdb->prefix."icl_translations";
						$element_type='package_layout';
						if ((is_array($package_ids_clean)) && (!(empty($package_ids_clean)))) {
							
							//Before we loop, we need to check if these things are set.
							foreach ( $package_ids_clean as $k=>$package_id) {
								
								//Step3. Retrieve trid given this element type and package_id
								$associated_trid= $wpdb->get_var($wpdb->prepare("SELECT trid FROM $icl_translation_table WHERE element_id = %d AND element_type='package_layout'",
										$package_id));	
		
								//Step4. Retrieve translated IDs
								$translation_ids= $wpdb->get_results($wpdb->prepare("SELECT translation_id FROM $icl_translation_table WHERE trid = %d AND source_language_code IS NOT NULL",
										$associated_trid),ARRAY_A);	
								$translation_ids_clean=wpvdemo_all_purpose_id_cleaner_func($translation_ids);
								if ((is_array($translation_ids_clean)) && (!(empty($translation_ids_clean)))) {
									foreach ($translation_ids_clean as $kt=>$vt) {
			
										//Step5, Get rid associated with this translated ID
										$icl_translation_status= $wpdb->prefix."icl_translation_status";
										$rids= $wpdb->get_results($wpdb->prepare("SELECT rid FROM $icl_translation_status WHERE translation_id = %d",
												$vt),ARRAY_A);	
										$rids_clean=wpvdemo_all_purpose_id_cleaner_func($rids);
										
										//Step6, get associated job ids of this rid
										$icl_translation_jobs= $wpdb->prefix."icl_translate_job";
										foreach ($rids_clean as $krids=>$vrids) {
											$job_ids= $wpdb->get_results($wpdb->prepare("SELECT job_id FROM $icl_translation_jobs WHERE rid = %d",
													$vrids),ARRAY_A);	
											$job_ids_clean=wpvdemo_all_purpose_id_cleaner_func($job_ids);
											
											//Step7, Ensure 'original_id' is correct for this job ID
											$icl_translate_table= $wpdb->prefix."icl_translate";
											foreach ($job_ids_clean as $kj=>$vj) {																	
												$original_id_updated= $wpdb->query (
														$wpdb->prepare (
																"UPDATE $icl_translate_table SET field_data=%d WHERE job_id=%d AND field_type='original_id'",
																$package_id,$vj
														)
												);								
											}
										}
									
									}
								}
							}
						}
					}
					
				}
			}
		}
	
	}	
	public function wpvdemo_retrieved_associated_layouts_id_refsite($string_packages_data,$key_found) {
		
		if ((is_array($string_packages_data)) && (!(empty($string_packages_data)))) {
			foreach ($string_packages_data as $k_pack=>$v_pack) {
				$v_id= $v_pack['ID'];
				$v_id=intval($v_id);
				if ($v_id === $key_found) {
					$associated_layouts_id= $v_pack['name'];
					return $associated_layouts_id;
				}
			}
		}
				
	}
	public function wpvdemo_retrieved_string_packages_refsite($string_package_xml) {
		
		$file_stringpackages_map_headers = @get_headers ( $string_package_xml );
		
		if (strpos ( $file_stringpackages_map_headers [0], '200 OK' )) {
		
			// Parse remote XML
			$data_wpml_stringpackages= wpv_remote_xml_get ( $string_package_xml );
		
			if (! ($data_wpml_stringpackages)) {
				return false;
			}
		
			$xml_stringpackages_settings = simplexml_load_string ( $data_wpml_stringpackages );
			if ( ! ( function_exists( 'wpv_admin_import_export_simplexml2array' ) ) ) {
				if ( defined('WPV_PATH_EMBEDDED') ) {
					require_once WPV_PATH_EMBEDDED . '/inc/wpv-import-export-embedded.php';
				}		
			}
			$import_data_wpml_stringpackages_map = wpv_admin_import_export_simplexml2array ( $xml_stringpackages_settings );
		
			// Prepare data
			foreach ( $import_data_wpml_stringpackages_map as $key_map => $values_map ) {
				$import_data_wpml_stringpackages_map [] = $values_map;
				unset ( $import_data_wpml_stringpackages_map [$key_map] );
			}
			
			return $import_data_wpml_stringpackages_map;
		}
		
	}
	public function wpvdemo_adjust_completed_string_packages_func() {

		require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
			
		//Get sites requiring WPML string packages update after import
		$get_sites=apply_filters('wpvdemo_wpml_string_packages_update',array());
			
		//Retrieve the origin of this imported site
		$origin= get_option('wpvdemo_refsites_origin_slug');
			
		if (in_array($origin,$get_sites)) {
		
			//Check if the import is not completed
			$check_import_is_done= get_option('wpv_import_is_done');
			if ('yes' == $check_import_is_done) {					
				
				if ((wpvdemo_wpml_is_active()) && (wpvdemo_layouts_is_active()))
				{					
					global $wpdb;
					$imported_package_context= get_option('imported_package_context_processed');
					$old_new_context_processed = get_option('old_new_context_processed');
					$package_table_exist=$this->wpvdemo_icl_string_package_table_exist();
					$translation_tables_exist=$this->wpvdemo_validate_icl_required_db_tables();
					if (($package_table_exist) && ($translation_tables_exist)) {
						if (((is_array($imported_package_context)) && (!(empty($imported_package_context)))) &&
						   ((is_array($old_new_context_processed)) && (!(empty($old_new_context_processed)))))						 
						 {						  
						   $string_translation_table=$wpdb->prefix . 'icl_strings';
						   
						   //Step1. Loop through the imported package context
						   foreach ($imported_package_context as $correct_context=>$correct_string_package_id) {
							   	 
							   	 //Step2. Retrieve the strings associated with each string_package_id
							   	$related_strings = $wpdb->get_results(
							   			$wpdb->prepare(
							   					"SELECT id,context FROM $string_translation_table WHERE string_package_id = %d"
							   					,$correct_string_package_id),
							   			ARRAY_A
							   	);
							   	
							   	//Step3. Go over the related strings result and validate each context	
							   	if ((is_array($related_strings)) && (!(empty($related_strings)))) {
									foreach ($related_strings as $k_related => $v_related) {
										
										//Step4. Retrieve context as saved in db
										$context_at_db=$v_related['context'];
										$id_at_db= $v_related['id'];
										
										//Step5. Let's validate this context to see if this is aligned with the string package ID
										if ($correct_context != $context_at_db) {
										   
										   //Context mismatch found
										   //Let's find its correct equivalent
										   if (isset($old_new_context_processed[$context_at_db])) {
										   		//Equivalent is set
										   		$new_equivalent_context=$old_new_context_processed[$context_at_db];

										   		if (!(empty($new_equivalent_context))) {

										   			//Step6. Then we find its correct string package id equivalent
										   			$new_string_package_id=$imported_package_context[$new_equivalent_context];
										   			
										   			//Step7. Update
										   			$ids_updated = $wpdb->query (
										   					$wpdb->prepare (
										   							"UPDATE $string_translation_table SET string_package_id=%d WHERE id=%d",
										   							$new_string_package_id,$id_at_db
										   					)
										   			);
										   			$context_updated = $wpdb->query (
										   					$wpdb->prepare (
										   							"UPDATE $string_translation_table SET context=%s WHERE id=%d",
										   							$new_equivalent_context,$id_at_db
										   					)
										   			);										   											   											   			
										   		}									   	
										   }					   											
										}
										
									}							   		
							   	}
						   }
						}
					}
				}		
			}
		}		
	}
	
    public function wpvdemo_adjust_string_packages_func() {
    
    	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
    	
    	//Get sites requiring WPML string packages update after import
    	$get_sites=apply_filters('wpvdemo_wpml_string_packages_update',array());
    	
    	//Retrieve the origin of this imported site
    	$origin= get_option('wpvdemo_refsites_origin_slug');
    	
    	if (in_array($origin,$get_sites)) {
	    	//Sites covered with this implementation
	    	//This code runs on site with Layouts activated + WPML core + String Translation
	    	//Let's verify, check if an update is still not completed	    	
	    	
	    	//Check if the import is not completed
	    	$check_import_is_done= get_option('wpv_import_is_done');
	    	if ('yes' == $check_import_is_done) {
	    		
	    		//Import is done but string packages are not yet updated	    		
		    	if ((wpvdemo_wpml_is_active()) && (wpvdemo_layouts_is_active()))
		    	{
		    		//All needed resources loaded
		    		global $WPML_String_Translation, $wpddlayout,$wpdb;	    		
		    		$wpdb->hide_errors();
		    		if ((is_object($WPML_String_Translation)) &&
		    				(is_object($wpddlayout))) {	    						
		    					
		    					$icl_string_packages_table= $wpdb->prefix."icl_string_packages";
								$package_table_exist=$this->wpvdemo_icl_string_package_table_exist();
								
		    					//Retrieved the old and new layout IDS imported.
		    					$pre_imported_layout_ids= get_option('wpvdemo_preimport_layoutsid');
		    					
		    					if ( ($package_table_exist) && 	    						
		    						(!(empty($pre_imported_layout_ids)))) {
		    							
		    						//String packages table exist.
		    						//Retrieved layout packages now using post-imported Layout IDs
		    						$package_ids = $wpdb->get_results ( "SELECT name,ID FROM $icl_string_packages_table WHERE kind='Layout'", ARRAY_A );
		    						$package_ids_clean=$this->wpvdemo_cleanup_packageids($package_ids);
		    						$string_translation_table=$wpdb->prefix . 'icl_strings';
		    						
		    						//Loop through the old Layouts IDs and update context based on updated string packages imported
		    						if (!(empty($package_ids_clean))) {
		    							$processed_packages_log=array();
		    							$imported_package_context=array();
		    							$old_and_new_context=array();
			    						foreach ($pre_imported_layout_ids as $old_id=>$new_id) {
			    							
			    							if (in_array($new_id,$package_ids_clean)) {
			    								
			    								//The Layout is in string package package
			    								//Define old context
			    								$untranslated_context='layout-'.$new_id;    								
			    								$translated_context='layout-'.$old_id;

			    								//Get the package element id
			    								$package_element_id_imported= array_search($new_id,$package_ids_clean);
			    								
			    								//Retrieved all strings belonging to this Layouts context
			    								//Check status of untranslated strings to make sure they are unstranslated
			    								$check_untranslated_strings_array = $wpdb->get_results(
			    										$wpdb->prepare(
			    												"SELECT id,name,status,domain_name_context_md5 FROM $string_translation_table WHERE context = %s AND status < 10 AND status != 2"
			    												,$untranslated_context),
			    										ARRAY_A
			    										);
			    								
			    								//Cleanup and update			    								
			    								$strings_to_delete=array();
			    								if ((is_array($check_untranslated_strings_array)) && (!(empty($check_untranslated_strings_array)))) {
													
			    									foreach ($check_untranslated_strings_array as $k=>$v) {
			    										//Loop, check status and delete if not included in the import
			    										if ((isset($v['status'])) && ((isset($v['name'])))) {
			    											$untranslated_status =$v['status'];
			    											$string_name =$v['name'];
			    											$id_under_processing=$v['id'];
			    											$db_domain_name_context_md5= $v['domain_name_context_md5'];
			    											$untranslated_status =intval($untranslated_status);
			    											$imported_domain_name_contextmd5=md5($untranslated_context.$string_name);
			    												    											
			    											//Untranslated
			    											//Delete untranslated context
			    											//We delete if the context md5 is not correct OR not translated
			    											
			    											if (($imported_domain_name_contextmd5 != $db_domain_name_context_md5) || (($untranslated_status <10) && ($untranslated_status != 2))) {
				    											$processed_packages_log[] = $wpdb->query(
				    														$wpdb->prepare(
				    																"DELETE FROM $string_translation_table WHERE id= %d",
				    																$id_under_processing)
				    											);
	
				    											//Set updated context reflecting the new Layouts ID after import
				    											$processed_packages_log[]=$wpdb->query (
				    													$wpdb->prepare (
				    															"UPDATE $string_translation_table SET context=%s WHERE context=%s AND name = %s",
				    															$untranslated_context,$translated_context,$string_name
				    													)
				    											);
				    												
				    											//Update domain_name_context_md5 too
				    											$imported_domain_name_contextmd5=md5($untranslated_context.$string_name);
				    											$processed_packages_log[]=$wpdb->query (
				    													$wpdb->prepare (
				    															"UPDATE $string_translation_table SET domain_name_context_md5=%s WHERE context=%s AND name=%s",
				    															$imported_domain_name_contextmd5,$untranslated_context,$string_name
				    													)
				    											);
				    											
				    											//Update string_package_id also				    											
				    											$processed_packages_log[]=$wpdb->query (
				    													$wpdb->prepare (
				    															"UPDATE $string_translation_table SET string_package_id=%d WHERE context=%s AND name=%s",
				    															$package_element_id_imported,$untranslated_context,$string_name
				    													)
				    											);
				    											
				    											$imported_package_context[$untranslated_context]=$package_element_id_imported;
				    											$old_and_new_context[$translated_context] = $untranslated_context;				    															    											
			    											}    												
			    											
			    										}			    										
			    									}			    									
			    								}												
			    							}
			    						}
										$imported_package_context_processed=get_option('imported_package_context_processed');
			    						if (!($imported_package_context_processed)) {
			    							//This option does not yet exist
			    							update_option('imported_package_context_processed',$imported_package_context);			    							
			    						}
			    						$old_new_context_processed=get_option('old_new_context_processed');
			    						if (!($old_new_context_processed)) {
			    							//This option does not yet exist
			    							update_option('old_new_context_processed',$old_and_new_context);
			    						}			    									    						
		    						}				

		    					}
		    						
		    				}
		    	}
	    	}
    	}    
    }
    function wpvdemo_cleanup_packageids($package_ids) {
    	
    	$package_ids_clean=array();
    	if ((is_array($package_ids)) && (!(empty($package_ids)))) {
    		foreach ($package_ids as $k=>$v) {
    			$element_id=$v['ID'];
    			$layout_id=$v['name'];
    			$package_ids_clean[$element_id]=$layout_id;    			
    		}    		
    	}
    	return $package_ids_clean;
    }
    function wpvdemo_deactivate_framework_installer_standalone() {
    	
    	//Deactivate Framework installer
    	if (isset($_POST['wpvdemo_fi_read_understand'])) {
    		$wpvdemo_read_understand= trim($_POST['wpvdemo_fi_read_understand']);
    		if (!defined('WPVLIVE_VERSION')) {
    			//Scope of implementation is standalone only
    			if ('yes' == $wpvdemo_read_understand) {
    				if (defined('WPVDEMO_ABSPATH')) {
    					if ( current_user_can( 'activate_plugins' ) ) {
    						$fi_main_file= WPVDEMO_ABSPATH.DIRECTORY_SEPARATOR.'views-demo.php'; 
    						global $wpvdemo_plugin_basename;
    						$wpvdemo_plugin_basename= basename(WPVDEMO_ABSPATH);
    				 		deactivate_plugins( plugin_basename( $fi_main_file ) );    				 		
    					}
    				}    				
    			}
    		}
    	}   	
    }
    function wpvdemo_deactivated_framework_installer_notice($the_plugin,$the_network) {
    	global $wpvdemo_plugin_basename;
    	if (isset($wpvdemo_plugin_basename)) {    		
    		if (!defined('WPVLIVE_VERSION')) {
    			
    			//Scope of implementation is standalone only
    			//Get basename of $the_plugin
    			$passed_deactivated= dirname($the_plugin);
    			
    			if ($wpvdemo_plugin_basename == $passed_deactivated) {
    				
    				//We have just successfully deactivated Framework Installer, show notice to user.
    				add_action( 'admin_notices', array($this,'wpvdemo_fi_deactivation_notice' )); 
    			}
    		}   		
    	}    	
    }
    function wpvdemo_fi_deactivation_notice() {
    ?>
    	<div class="updated wpvdemo_framework_installer_deactivated">
    		<p><?php _e('Framework Installer is now deactivated. Refreshing...','wpvdemo');?></p>
    	</div>
    <?php        	
    }

    function refsite_custom_import_process_steps() {
    	
    	if (isset($_POST['the_nonce_import_text']) && wp_verify_nonce($_POST['the_nonce_import_text'],
    			'wpvdemo_import_text_custom') && isset($_POST['the_refsite_id'])) {

    			//Retrieve refsite ID
    			$refsite_id= $_POST['the_refsite_id'];
    			$refsite_id =intval($refsite_id);
    			
    			//Save refsite versions for configuration customizations
    			do_action('wpvdemo_import_refsite_versions');
    			
    			//Retrieve language settings
    			$language_settings= $_POST['language_settings_passed'];
    			
    			if ($refsite_id >0) {    		
    				
    				require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
    				
    				//Define given
    				$long_ajax_loading = ' <span class="wpvdemo-post-count" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span><span class="wpcf-ajax-loading-small" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span><span class="wpvdemo-green-check" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>';
    				$short_ajax_loading= ' <span class="wpcf-ajax-loading-small" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span><span class="wpvdemo-green-check" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>';
    				$types_ajax_loading= ' <span class="wpcf-ajax-loading-small">&nbsp;&nbsp;&nbsp;&nbsp;</span><span class="wpvdemo-green-check" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>';
    				
    				$default_text_array=array(
    						/** original step 1 */
    						'types_import' => array(
    								'the_text' 	   => 'Downloading and importing Types...',
    								'ajax_loading' => $types_ajax_loading
    								),
    						/** original step 2 */
    						'posts_import' => array(
    								'the_text' 	   => 'Downloading and importing Posts and Images...',
    								'ajax_loading' => $long_ajax_loading
    						),
    						/** original step 3 */
    						'views_import' => array(
    								'the_text' 	   => 'Downloading and importing Views...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 4 */
    						'cred_import' => array(
    								'the_text' 	   => 'Downloading and importing CRED forms...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 5 */
    						'access_import' => array(
    								'the_text' 	   => 'Downloading and importing Types Access...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 6 */
    						'wpml_import' => array(
    								'the_text' 	   => 'Downloading and importing WPML settings...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 7 */
    						'inline_doc_import' => array(
    								'the_text' 	   => 'Downloading inline documentation...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 8 */
    						'theme_import' => array(
    								'the_text' 	   => 'Downloading theme...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 9 */
    						'module_manager_import' => array(
    								'the_text' 	   => 'Downloading and importing module manager...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 10 */
    						'layouts_import' => array(
    								'the_text' 	   => 'Downloading and importing layouts...',
    								'ajax_loading' => $short_ajax_loading
    						),
    						/** original step 11 */
    						'general_import_settings' => array(
    								'the_text' 	   => 'Finalizing imported settings...',
    								'ajax_loading' => $short_ajax_loading
    						),
    				);
    				
    				//Filter default import processes
    				$the_default_processes= apply_filters('wpvdemo_default_import_processes', array());
    				
    				//Filter inactive processes
    				$the_inactive_processes= apply_filters('wpvdemo_inactive_import_processes', array());
    				
    				$needed_processes=array();
    				
    				//Loop through the import processes and we will see what is only needed for this site
    				foreach ($default_text_array as $import_process_key=>$import_process_text_details) {
    					
    				  //Check if this process is inactive
    				  if (in_array($import_process_key,$the_inactive_processes)) {    				  	
    				  	continue;
    				  }
    				  
    				  //Check if this process is default/required
    				  if (in_array($import_process_key,$the_default_processes)) {
    				  	$needed_processes[]=$import_process_key;    				  	
    				  }
    				  
    				  //CRED Implementation
    				  if ('cred_import' ==$import_process_key) {
    				  	$cred_sites= apply_filters('wpvdemo_refsites_require_cred', array());
    				  	if (in_array($refsite_id,$cred_sites)) {
    				  		$needed_processes[]=$import_process_key;    				  		
    				  	}
    				  }
    				  
    				  //Types Access implementation
    				  if ('access_import' ==$import_process_key) {
    				  	$access_sites= apply_filters('wpvdemo_refsites_require_access', array());
    				  	if (in_array($refsite_id,$access_sites)) {
    				  		$needed_processes[]=$import_process_key;
    				  	}
    				  } 
    				     				  
    				  //WPML implementation
    				  if ('wpml_import' ==$import_process_key) {
						if ('wpml' == $language_settings) {
							$needed_processes[]=$import_process_key;
						}
    				  }
    				  
    				  //Has modules to import
    				  if ('module_manager_import' == $import_process_key) {
    				  	$mm_sites= apply_filters('wpvdemo_refsites_has_modules_to_import', array());
    				  	if (in_array($refsite_id,$mm_sites)) {
    				  		$needed_processes[]=$import_process_key;
    				  	}
    				  }   	

    				  //Has layouts to import
    				  if ('layouts_import' == $import_process_key) {
    				  	$layout_sites= apply_filters('wpvdemo_refsites_require_layouts', array());
    				  	if (in_array($refsite_id,$layout_sites)) {
    				  		$needed_processes[]=$import_process_key;
    				  	}
    				  }    				      					
    				}
    				
    				$counter=1;
    				$importing_text='';
    				//Loop through the needed processes and compose $importing_text
    				foreach ($needed_processes as $k=>$v) {
    					$importing_text .= '<div id="wpvdemo_step_'.$counter.'">' .$counter.'. '.$default_text_array[$v]['the_text'].' '.$default_text_array[$v]['ajax_loading'];    					
    					//Increment
    					$counter++;
    				}
    				
    				$response['outputtext']='success';  
    				$response['importing_text_updated']=$importing_text;
    				$process_count=$counter-1; 
			 		update_option('wpvdemo_importprocess_count',$process_count);
    				echo json_encode($response);    				
    			}

    			die();    				
    	}    	
    }
    function wpvdemo_filter_plugin_object_func($plugin_object) {    	
    	
    	$plugin_url_ok=false;
    	if (isset($plugin_object->url)) {
    	   $plugin_url=$plugin_object->url;
    	   if (is_string($plugin_url)) {
				//String, check if its not empty
    	   		$plugin_url=trim($plugin_url);
    	   		if (!(empty($plugin_url))) {
    	   			//Not empty
    	   			$plugin_url_ok=true;
    	   			if (isset($plugin_object->title)) {
    	   				$plugin_title=$plugin_object->title;
    	   				if (is_string($plugin_title)) {
    	   					/** Allow plugin URL to be filtered. */
    	   					/** Some application includes Google analytics arguments to be added to tutorial URL if pointing to wp-types.com */
    	   					$plugin_object->url= apply_filters('wpvdemo_filter_tutorial_url',$plugin_url,'post-setup-box',$plugin_title);    	   					
    	   				}
    	   			}
    	   		}
    	   }
    	}
    	
    	if (!($plugin_url_ok)) {
    		//Not set
    		$plugin_object->url=false;
    	}
    	
    	return $plugin_object;
    	
    }
    
    function wpvdemo_get_site_id_given_shortname($shortname) {
    	
    	$ret=false;
    	global $frameworkinstaller;
    	$shortname= trim($shortname);
    	if (!(empty($shortname))) {
    		//Get refsites
    		$sites = $frameworkinstaller->get_refsites();
    		$sites = wpvdemo_get_sites_index_as_arrays($sites);
    		foreach ($sites as $k=>$v) {
    			 $shortname_analyzed=$v['shortname'];
    			 if ($shortname == $shortname_analyzed) {
    			 	$id= $v['ID']; 
    			 	$id= intval($id);
    			 	if ($id > 0) {
    			 		$ret=$id;
    			 		return $ret;
    			 	}
    			 }
    		}    		
    	}
		return $ret;
    }
    function wpvdemo_get_shortname_given_id($site_id) {
    	 
    	$ret=false;
    	global $frameworkinstaller;
    	$site_id= intval($site_id);
    	if ($site_id > 0) {
    		//Get refsites
    		$sites = $frameworkinstaller->get_refsites();
    		$sites = wpvdemo_get_sites_index_as_arrays($sites);
    		foreach ($sites as $k=>$v) {
    			$site_id_analyzed=$v['ID'];
    			$site_id_analyzed = intval($site_id_analyzed);
    			if ($site_id_analyzed === $site_id) {
    				$shortname= $v['shortname'];    				
    				if (!(empty($shortname))) {
    					$ret=$shortname;
    					return $ret;
    				}
    			}
    		}
    	}
    	return $ret;
    }
    function wpvdemo_get_sitespecific_info_given_id($site_id,$requested_info='shortname') {
    
    	$ret=false;
    	global $frameworkinstaller;
    	$site_id= intval($site_id);
    	if ($site_id > 0) {
    		//Get refsites
    		$sites = $frameworkinstaller->get_refsites();
    		$sites = wpvdemo_get_sites_index_as_arrays($sites);
    		foreach ($sites as $k=>$v) {
    			$site_id_analyzed=$v['ID'];
    			$site_id_analyzed = intval($site_id_analyzed);
    			if ($site_id_analyzed === $site_id) {    				
    				
    				if (isset($v[$requested_info])) {
    					$requested_data= $v[$requested_info];
    					if (!(empty($requested_data))) {
    						$ret=$requested_data;
    						return $ret;
    					}  					
    				}
    				

    			}
    		}
    	}
    	return $ret;
    }
    function wpvdemo_get_id_given_refsiteslug($refsiteslug) {
    
    	$ret=false;
    	global $frameworkinstaller;    	
    	if (!(empty($refsiteslug))) {
    		//Get refsites
    		$sites = $frameworkinstaller->get_refsites();
    		$sites = wpvdemo_get_sites_index_as_arrays($sites);
    		foreach ($sites as $k=>$v) {
    			$site_slug_analyzed=$v['site_url'];
    			$refsite_slug_canonical= basename($site_slug_analyzed);
    			if ($refsite_slug_canonical === $refsiteslug) {
    				//Found, get ID
    				$refsite_id= $v['ID'];
					$refsite_id= intval($refsite_id);
					if ($refsite_id >0 ) {
						return $refsite_id;
					}
    			}
    		}
    	}
    	return $ret;
    }
    function wpvdemo_search_replace_layouts_string_context() {
    	$refsite_slug=get_option('wpvdemo_refsites_origin_slug');
    	$check_import_is_done= get_option('wpv_import_is_done');
    	$wpv_search_replacement_context_done= get_option('wpv_search_replacement_context_done');
    	$completed_string_packages_update= get_option('wpvdemo_completed_string_packages_update');
    	if ((! (empty ( $refsite_slug ))) && ('yes' == $check_import_is_done) && (!($wpv_search_replacement_context_done)) && ($completed_string_packages_update)) {    
    		
    		//Two ingredients to get started: Layouts active and multilingual import
    		if ((wpvdemo_wpml_is_active()) && (wpvdemo_layouts_is_active())) {
    
    			if (defined ( 'WPVDEMO_DOWNLOAD_URL' )) {
    
    				$search_this=false;
    				$replace_with=false;
    
    				$download_url = WPVDEMO_DOWNLOAD_URL;
    				if (! (empty ( $download_url ))) {
    
    					// Download defined
    					$parsed_url = parse_url ( $download_url );
    					if (isset ( $parsed_url ['host'] )) {
    						$original_host = $parsed_url ['host'];    						
    						$search_this = 'http://' . $original_host . '/' . $refsite_slug . '/files';
    							
    						// Target
    						$uploads_constants_of_this_site = wp_upload_dir ();
    						$replace_with = $uploads_constants_of_this_site ['baseurl'];
    					}
    				}
    
    
    				//Step1, get layouts
    				$active_site_layouts = wpvdemo_retrieve_all_published_layouts ();
    
    				//Step2, loop and formulate context
    				if ((is_array($active_site_layouts)) && (!(empty($active_site_layouts))) && ($search_this) &&($replace_with)) {
    					$the_context=array();
    					foreach ($active_site_layouts as $k=>$v) {
    						$the_context[]='layout-'.$v;
    					}
    
    					//Step3, loop through each context and get all matching strings in table
    					global $wpdb;
    					$strings_table = $wpdb->prefix.'icl_strings';
    					$string_translation_table = $wpdb->prefix.'icl_string_translations';
    					foreach ($the_context as $k_context=>$v_context) {
    							
    						$package_strings = $wpdb->get_results(
    								$wpdb->prepare(
    										"SELECT id,value FROM $strings_table WHERE context = %s"
    										,$v_context),
    								ARRAY_A
    						);
    							
    						//Step4, loop through the matching strings and replace value
    						if ((is_array($package_strings)) && (!(empty($package_strings)))) {
    
    							foreach ($package_strings as $k_package => $v_package) {
    								$string_id=$v_package['id'];
    								$string_value=$v_package['value'];
    									
    								if ((is_string($string_value)) && (!(empty($string_value)))) {
    									//Step5, check if this value contains old hostname
    									if ((strpos($string_value, $search_this) !== false)) {
    										//Yes
    										//Search for old hostname and replace
    										$replacement_result=migration_script_recursive_unserialize_replace( $search_this, $replace_with, $string_value, false, false);
    
    										//Update back
    										$updated_strings_value=$wpdb->query (
    												$wpdb->prepare (
    														"UPDATE $strings_table SET value=%s WHERE id=%d",
    														$replacement_result,$string_id
    												)
    										);
    									}	
    									
    									//Step6 , do the same for the translation
    									$translated_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM $string_translation_table WHERE string_id = %d",
    											$string_id));
    									if ((strpos($translated_value, $search_this) !== false)) {
    									
	    									$replacement_result_translated=migration_script_recursive_unserialize_replace( $search_this, $replace_with, $translated_value, false, false);
	    									//Update back
	    									$updated_strings_translated_value=$wpdb->query (
	    											$wpdb->prepare (
	    													"UPDATE $string_translation_table SET value=%s WHERE string_id=%d",
	    													$replacement_result_translated,$string_id
	    											)
	    									);
    									}
    									
    								}
    
    							}
    							
    						}
    					}
    					update_option('wpv_search_replacement_context_done','yes');
    				}
    			}
    		}
    	}
    }
    function wpvdemo_align_original_id_after_multilingual_import() {

    	$wpvdemo_validate_icl_required_db_tables=$this->wpvdemo_validate_icl_required_db_tables();
    	if ((wpvdemo_wpml_is_enabled()) && ($wpvdemo_validate_icl_required_db_tables)) {
    		
    		/** Step1, get all job ids where original_id field_data value is not empty or null */
    		global $wpdb;    		
    		$icl_translate_table= $wpdb->prefix."icl_translate";
    		$icl_translate_job_table= $wpdb->prefix."icl_translate_job";
    		$icl_translation_status_table= $wpdb->prefix."icl_translation_status";
    		$icl_translations_table= $wpdb->prefix."icl_translations";
    		
			//icl_translate table exist
    		$job_ids = $wpdb->get_results ( "SELECT DISTINCT job_id FROM $icl_translate_table where field_data IS NOT NULL AND field_type='original_id'", ARRAY_A );
    		$job_ids_clean=wpvdemo_all_purpose_id_cleaner_func($job_ids);

    		/** Step2, Loop through each job ID*/
    		if ((is_array($job_ids_clean)) && (!(empty($job_ids_clean)))) {
    			foreach ($job_ids_clean as $k=>$job_id) {
    				
    				/** Step3, let's retrieved associated rid */   				
    				$rid_of_job_id = $wpdb->get_var($wpdb->prepare("SELECT rid FROM $icl_translate_job_table WHERE job_id = %d",
    						$job_id));
    				
    				/** Step4, given this rid, retrieve its translation ID */
    				$translation_id_of_rid = $wpdb->get_var($wpdb->prepare("SELECT translation_id FROM $icl_translation_status_table WHERE rid = %d",
    						$rid_of_job_id));
    				
    				/** Step5, given this translation id , retrieve its trid */
    				$trid = $wpdb->get_var($wpdb->prepare("SELECT trid FROM $icl_translations_table WHERE translation_id = %d",
    						$translation_id_of_rid));
    				
    				/** Step6, given this trid, retrieve its original element ID */
    				$original_element_id = $wpdb->get_var(
    						$wpdb->prepare(
    								"SELECT element_id FROM $icl_translations_table WHERE trid = %d AND source_language_code IS NULL AND element_type != 'package_layout'",
    						$trid));

    				$original_element_id=intval($original_element_id);
    				if ($original_element_id > 0) {
    					/** Step7, update this imported element ID to the icl_translate table */
    					$icl_translate_updated=$wpdb->query (
    							$wpdb->prepare (
    									"UPDATE $icl_translate_table SET field_data=%d WHERE job_id=%d AND field_type='original_id'",
    									$original_element_id,$job_id
    							)
    					);
    				}
    			}
    		}
    	}
    	
    }
    
    function wpvdemo_validate_icl_required_db_tables() {
    	
    	global $wpdb;
    	$validation=false;
    	
    	//Define icl_translate table
    	$icl_translate_table= $wpdb->prefix."icl_translate";
    	
    	//Define icl_translate_job table
    	$icl_translate_job_table= $wpdb->prefix."icl_translate_job";
    	
    	//Define icl_translation_status table
    	$icl_translation_status_table= $wpdb->prefix."icl_translation_status";
    	
    	//Define icl_translations table
    	$icl_translations_table= $wpdb->prefix."icl_translations";

    	$table_checks_array=array();
    	
    	//Check if icl_translate table exist
    	$icl_translate_table_existence= $wpdb->get_var("SHOW TABLES LIKE '$icl_translate_table'" );
    	if ($icl_translate_table == $icl_translate_table_existence) {    		
    		$table_checks_array[]=$icl_translate_table_existence;    		
    	}
    	//Check if icl_translate_job table exist
    	$icl_translate_job_table_existence= $wpdb->get_var("SHOW TABLES LIKE '$icl_translate_job_table'" );
    	if ($icl_translate_job_table == $icl_translate_job_table_existence) {
    		$table_checks_array[]=$icl_translate_job_table_existence;
    	} 
    	//Check if icl_translation_status table exist
    	$icl_translation_status_table_existence= $wpdb->get_var("SHOW TABLES LIKE '$icl_translation_status_table'" );
    	if ($icl_translation_status_table == $icl_translation_status_table_existence) {
    		$table_checks_array[]=$icl_translation_status_table_existence;
    	}
    	//Check if icl_translations table exist
    	$icl_translations_table_existence= $wpdb->get_var("SHOW TABLES LIKE '$icl_translations_table'" );
    	if ($icl_translations_table == $icl_translations_table_existence) {
    		$table_checks_array[]=$icl_translations_table_existence;
    	}

    	$tables_counted= count($table_checks_array);
    	
    	if (4 === $tables_counted) {
    		$validation=true;    	  	
    	}
    	return $validation;    	
    }
    function wpvdemo_icl_string_package_table_exist() {
    	$table_exist=false;
    	global $wpdb;
    	$icl_string_packages_table= $wpdb->prefix."icl_string_packages";
    	$check_existence= $wpdb->get_var("SHOW TABLES LIKE '$icl_string_packages_table'" );    	 
    	if ($icl_string_packages_table == $check_existence) {
			$table_exist=true;    		
    	}
    	return $table_exist;    	
    }
    function wpvdemo_adjust_metaboxhiddennavmenus($meta_valuex, $meta_keyx, $meta_typex ) {

    	//Check if the import is completed and not yet adjusted
    	$check_import_is_done= get_option('wpv_import_is_done');    	

    	if (('yes' == $check_import_is_done)) {
    		//Yes
    		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
    		{
    			//Doing ajax, don't do any processing
    			return $meta_valuex;
    		}
    		
    		if ((function_exists('wp_get_current_user')) && ('metaboxhidden_nav-menus' == $meta_keyx)) {
    			 
    			//Get Types custom post types
    			$cpt= get_option('wpcf-custom-types');
   			 
    			if (((is_array($cpt)) && (!(empty($cpt)))) &&
    					((is_array($meta_valuex)) && (!(empty($meta_valuex)))))
    			{
    				//Loop through these CPT
    				foreach ($cpt as $k=>$cpt_array) {
    					if (isset($cpt_array['slug'])) {
    						$cpt_slug=$cpt_array['slug'];
    						if (!(empty($cpt_slug))) {
    							$menu_to_search='add-'.$cpt_slug;
    		
    							//Let's checked if this nav menu is set to hidden
    							foreach ($meta_valuex as $k_hidden=>$v_hidden) {
    								if ($v_hidden == $menu_to_search) {
    									//This is hidden
    									unset($meta_valuex[$k_hidden]);
    								}
    							}
    						}
    					}
    				}
    				$meta_valuex=array_values($meta_valuex);    				  				
    			}
    		}
    	}
    	
    	return $meta_valuex;
    }
    function wpvdemo_filter_tutorial_url_func($tut_url,$location,$linktext) {
    	
		//Call the method
		$tut_url=$this->wpvdemo_append_google_analytics_arguments_to_url($tut_url,$location,$linktext);
        return $tut_url;	
    	
    }
    
    function wpvdemo_append_google_analytics_arguments_to_url($tut_url,$location,$linktext) {

    	$tut_url = (string)($tut_url);
    	$parsed_url= parse_url($tut_url);
    	if ((is_array( $parsed_url)) && (!(empty($parsed_url)))) {
	    	//Get host
	    	if (isset($parsed_url['host'])) {
	    	$host=$parsed_url['host'];
	    			$already_added=false;
	    
	    			//Get query part
	    			if (isset($parsed_url['query'])) {
	    			//Query part exist
	    			$querypart= $parsed_url['query'];
		    			if ((strpos($querypart, 'utm_medium=wpadmin') !== false)) {
		    				$already_added=true;
		    			}
	    			}
	    
	    			//We filter if host is 'wp-types.com' and arguments are not yet added
	    			if (('wp-types.com' == $host) && (!($already_added))) {
	    			//Filtering conditions satisfied
	    
	    			$google_analytics_arguments=$this->wpvdemo_formulate_google_analytics_arguments($location,$linktext);
	    			$tut_url = add_query_arg($google_analytics_arguments, $tut_url );
	    			}
	    
	    	}    			 
    	}
    	return $tut_url;
    			 
    }    
    function wpvdemo_formulate_google_analytics_arguments($location,$linktext) {
    	
    	/**
  		[ok]utm_source=discover-wp   (always)
 		[ok]utm_medium=wpadmin   (always, for links coming from WP admin pages)
 		[ok]utm_campaign=discover-wp   (always for discover-wp.com)
 		[ok]utm_content=welcome-box or post-setup-box   (this tells us where the link is placed) 
 		[ok]utm_term=get-started   (this need to be the text in the link being clicked) 				

		For local reference sites:
 		[ok]utm_source=local-ref-site
 		[ok]utm_campaign=framework-installer

		All other arguments are the same as for discover-wp.com   	
    	*/
    	
    	/** ANALYZE utm_source -default 'local-ref-site' */    	
    	
    	//Are we on discover-wp?
    	$is_discoverwp=$this->is_discoverwp();  
    	$utm_source = 'local-ref-site';
    	if ($is_discoverwp) {
    		//Yes
    		$utm_source='discover-wp';
    	}
    	
    	/** ANALYZE utm_medium -default 'wpadmin' */
    	
    	//Are we on admin sections?
    	$utm_medium= 'wpadmin';
    	if (!(is_admin())) {
    		$utm_medium ='frontend';
    	}
    	
    	/** ANALYZE utm_campaign -default 'framework-installer' */
    	$utm_campaign='framework-installer';
    	if ($is_discoverwp) {
    		//Yes
    		$utm_campaign='discover-wp';
    	}    	
    	
    	/** ANALYZE utm_content -default 'welcome-box' */
    	$utm_content ='welcome-box';
    	if (!(empty($location))) {
    		//Set
    		$utm_content = $location;
    	} 
    	
    	/** ANALYZE utm_term -default 'get-started' */
    	$utm_term= 'get-started';
    	
    	//Validated $linktext since this can be now EMPTY or blank
    	if ( is_string( $linktext ) ) {
    		if ( !(empty( $linktext ) ) ) {
    			//Link text is valid string and not empty
    			$linktext=sanitize_title($linktext);
    			$utm_term= $linktext;
    		}
    	}
    	
    	$google_analytics_arguments=array(
    			'utm_source' 	=> $utm_source,
    			'utm_medium' 	=> $utm_medium,
    			'utm_campaign'	=> $utm_campaign,
    			'utm_content'   => $utm_content,
    			'utm_term' 		=> $utm_term  			 
    	); 

    	return $google_analytics_arguments;
    }
    function wpvdemo_filter_tutorial_shortdescription_func( $tut_shortdescription, $location, $linktext ) {

    	//Bypass empty description
    	if (!( is_string( $tut_shortdescription ) )) {
    		$tut_shortdescription = '';
    		return  $tut_shortdescription;
    	}
    	$tut_shortdescription= trim( $tut_shortdescription );
    	
    	//Check if text has URL otherwise bypass
    	$haslink = strstr($tut_shortdescription, 'href');
    	if (($haslink) && (!(empty($tut_shortdescription)))) {
	    	if (!(empty($tut_shortdescription))) {
		    	$dom = new DOMDocument;	
		    	$dom->encoding = 'utf-8';
		    	@$dom->loadHTML(mb_convert_encoding($tut_shortdescription,'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		    	$links = $dom->getElementsByTagName('a');
		    	
		    	//Iterate over the extracted links and display their URLs
		    	$updated_array=0;
		    	foreach ($links as $link){
		    		//Get link text first
		    		$the_link_text= $link->nodeValue;
		    		$the_link_text= trim($the_link_text);
		    		
		    		//Get href
		    		$the_href= $link->getAttribute('href');
		    		
		    		/** Framework installer 2.0.2+ : Allows Google analytics arguments to be appended on links with images. */		    			
		    		//Add Google analytics arguments
		    		$new_url_with_arguments= $this->wpvdemo_append_google_analytics_arguments_to_url($the_href,$location,$the_link_text);
		    			
		    		if ($the_href != $new_url_with_arguments) {
		    			$updated_array++;
		    		}
		    		
		    		//Update back
		    		$link->setAttribute('href', $new_url_with_arguments);
		    			
		    		
		    	}
		    	if ($updated_array > 0) {
		    		//It's updated
		    		//$tut_shortdescription = @$dom->saveHTML();
		    		$tut_shortdescription = $dom->saveHTML($dom->documentElement);
		    	}
			}
    	}
    	return $tut_shortdescription;    	
    	
    }
    function wpvdemo_complete_all_translator_configurations($current_user_id) {
    	
    	//Generate language pairs
    	$language_pairs=$this->wpvdemo_generate_lang_pairs();
    	$current_user_id =intval($current_user_id);
    	
    	if ((!(empty($language_pairs))) && ($current_user_id > 0)) {
    		//Update to database
    		global $iclTranslationManagement;    		
    		if (is_object($iclTranslationManagement)) {    			  		   	
    			if (method_exists($iclTranslationManagement,'add_translator')) {
    				$iclTranslationManagement->add_translator( $current_user_id, $language_pairs );    				
    			}
    		}    		
    	}    	
    }
    function wpvdemo_generate_lang_pairs() {
    	
    	//Get active languages
    	$active_languages= apply_filters( 'wpml_active_languages', NULL );
    	$language_pairs=array();    	
    	$default_lang='en';
		   			
    	if ((!(empty($active_languages))) && (is_array($active_languages))) {
    		foreach ($active_languages as $k=>$v) {
    			if ($k != $default_lang) {
    				//Not in default language
    				$language_pairs[$default_lang][$k]= 1;
    			}
    		}
    	}   			
	
    	return $language_pairs;
    }
    
    function wpvdemo_generate_replaceable_hostnames($the_file) {
    	
    	$replaceable=array();
    	
    	if(!class_exists('Framework_Installer_URL_Reference_Class'))
    	{
    		require_once WPVDEMO_ABSPATH . '/classes/class-absolute-url-references.php';
    	}
    	
    	if(!isset($absolute_url_references))
    	{
    		//Instantiate class
    		$absolute_url_references= new Framework_Installer_URL_Reference_Class();    		
    		
    		/** Search replacement should start from the most specific to most general */    		
    		/** Start with images uploads directory, e.g.
    		 * ref.wp-types.tld/classifieds-layouts/files/ TO:
    		 * testplatform.local/wp-content/uploads/
    		 * Should be Discover-WP multisite compatible
    		 */
			
    		//Get original blogs dir
    		$original_ref_blogsdir=$absolute_url_references->wpvdemo_generate_reference_blogs_dir_files_url($the_file);
    		
    		//Get target images path
    		$target_media_path=$absolute_url_references->wpvdemo_generate_target_site_uploads_url();
    		
    		if ((!(empty($original_ref_blogsdir))) && (!(empty($target_media_path)))) {
    			
	    		$replaceable[] =	array(
				
						/** String to search in the database */
						'srch'  	 => $original_ref_blogsdir,
				
						/** String to replace */
						'rplc'		 => $target_media_path
				);
    		}
    		
    		//Save source and target media path for further retrieval
    		$wpvdemo_source_target_media_equivalence=array();
    		$wpvdemo_source_target_media_equivalence[$original_ref_blogsdir] = $target_media_path;
    		update_option('wpvdemo_source_target_media_equivalence',$wpvdemo_source_target_media_equivalence);
    		
    		/** Then we do a general search and replace of original vs target hostnames, e.g.
    		 * ref.wp-types.tld/classifieds-layouts TO:
    		 * testplatform.local
    		 * Should be Discover-WP multisite compatible
    		 */    		
    		
    		//Get source ref site URL
    		$original_refsite_url= $absolute_url_references->wpvdemo_source_refsite_url();
    		
    		//Get target URL
    		$installed_site_url= $absolute_url_references->wpvdemo_get_site_url();
    		
    		if ((!(empty($original_refsite_url))) && (!(empty($installed_site_url)))) {
    			$replaceable[] =	array(
    			
    					/** String to search in the database */
    					'srch'  	 => $original_refsite_url,
    			
    					/** String to replace */
    					'rplc'		 => $installed_site_url
    			);
    		}
    		
    	} 
    	
    	//Extendable by API if necessary
    	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';
    	$replaceable= apply_filters('wpvdemo_search_replace_directive',$replaceable);
    	
    	return $replaceable;  	

    }
    function wpvdemo_import_bootstrap_versions_func() {
    	
    	global $wpvdemo_bootstrap_estate_original_version;
    	$wpvdemo_refsite_installed_version_number=get_option('wpvdemo_refsite_installed_version_number');
    	
    	//START
    	$original_bootstrap_estate='notset';
    	if ($wpvdemo_refsite_installed_version_number) {
    		if (isset($wpvdemo_refsite_installed_version_number['bre'])) {
    			$bootstrap_estate_version_exported= $wpvdemo_refsite_installed_version_number['bre'];
    			if ('1.0' == $bootstrap_estate_version_exported) {
    				$wpvdemo_bootstrap_estate_original_version=true;
    			} else {
    				$wpvdemo_bootstrap_estate_original_version=false;
    			}
    		}
    	}    	
    }
    function wpvdemo_search_replace_outdated_cred_context_in_body($the_file) {
    	 
    	//Not all CRED sites needs this
    	$the_file=trim($the_file);
    	if (empty($the_file)) {
    	  return;	
    	}
    	//Get refsite slug
    	$refsite_slug = wpvdemo_get_refsites_slug_func($the_file);
    	require_once WPVDEMO_ABSPATH . '/includes/import_api.php';    	
    	$sites_covered= apply_filters('wpvdemo_search_replace_detailed_context_credbody',array()); 
    	
    	//Check if implemented
    	if (in_array($refsite_slug,$sites_covered)) {
    		
    		//Covered with this implementation
    		//Retrieved old and its equivalent new context
    		$wpvdemo_processed_cred_context_import=get_option('wpvdemo_processed_cred_context_import');
    		if ((is_array($wpvdemo_processed_cred_context_import)) && (!(empty($wpvdemo_processed_cred_context_import)))) {
    			global $wpdb;    			
    			$posts_table= $wpdb->prefix."posts";	
    			$table_to_search=array($posts_table);
    			if(!class_exists('Framework_Installer_Migration_Search_Replace'))
	    		{
	    			require_once WPVDEMO_ABSPATH . '/classes/class-migration_search_replace_tool.php';
	    		}
	    		
	    		if ((!isset($fi_migration_search_replace)))
	    		{
	    			//Instantiate class
	    			$fi_migration_search_replace= new Framework_Installer_Migration_Search_Replace();
	    		
	    			foreach ($wpvdemo_processed_cred_context_import as $old_context => $new_context) {
	    				
	    				
	    				$configuration =	array(
	    						'srch'  	 => $old_context,
	    						'rplc'		 => $new_context,
	    						'tables'	 => $table_to_search
	    				);
	    				
	    				$fi_migration_search_replace->Framework_Installer_Migration_Search_Replace($configuration);
	    			}
	    		}
    		}

    	}
    
    }
    function wpvdemo_reenable_string_filters_after_import() {
    
    	$check_import_is_done_connected = get_option ( 'wpv_import_is_done' );
    	if (('yes' == $check_import_is_done_connected) && (wpvdemo_wpml_is_active())) {
    		/** Import is done and WPML is setup, bring back these filters */
    		add_filter( 'gettext', 'icl_sw_filters_gettext', 9, 3 );
    		add_filter( 'ngettext', 'icl_sw_filters_ngettext', 9, 5 );
    		add_filter( 'gettext_with_context', 'icl_sw_filters_gettext_with_context', 1, 4 );
    		
    	}
    	
    }
    
    /**
     * Added support for Toolset unified menu checks.
     * Returns TRUE if existing Toolset common library can support unified menu
     * @return boolean
     */
    public function wpvdemo_can_implement_unified_menu() {
    	
    	$unified_menu = false;
    	
    	$is_discoverwp = $this->is_discoverwp();
    	
    	if ( false === $is_discoverwp ) {
    		//Standalone checks...
	    	
	    	$is_available = apply_filters( 'toolset_is_toolset_common_available', false );
	    	if ( TRUE === $is_available ) {
	    		$unified_menu = true;
	    	}
	    	
    	} else {
    		//Discover-WP multisite checks
    		global $live_demo_registration;
    		if ( is_object( $live_demo_registration ) ) {
	    		if ( method_exists( $live_demo_registration , 'wpvlive_can_implement_unified_menu' ) ) {
	    			$is_available = $live_demo_registration->wpvlive_can_implement_unified_menu();  
	    			if ( TRUE === $is_available ) {
		    			$unified_menu = true;
		    		}
	    		}
    		}
    	}
    
    	return $unified_menu;
    }
    /**
     * Register unified menu for Framework Installer
     * @param array $pages
     * @return array
     */
     
    public function wpvdemo_unified_menu( $pages ) {
    
    	//Add admin screen only when all required plugins are activated    	
    	$pages[] = array(
    			'slug'          => 'manage-refsites',
    			'menu_title'    => __( 'Reference sites', 'wpvdemo' ),
    			'page_title'    => __( 'Select a Reference Site to install', 'wpvdemo' ),
    			'callback'      => array( &$this, 'manage_reference_sites_admin_page' ),
    			'load_hook'   	=> array( &$this, 'wpvdemo_admin_menu_import_hook' ),
    			'contextual_help_hook'      => array( &$this, 'manage_reference_sites_add_help_tab' )
    	);

    	return $pages;
    } 
    
    /**
     * Added forward/backward compatibility on current screen usage in Framework Installer
     * For Toolset Unified Menu Implementation
     * Returns the correct canonical screen ID
     */
    private function wpvdemo_unified_current_screen() {
    
    	//Backward compatible screen ID
    	$canonical_screen_id = 'toplevel_page_manage-refsites';
    
    	//Check if this can support unified menu
    	$can_support_unified_menu = $this->wpvdemo_can_implement_unified_menu();
    
    	if ( $can_support_unified_menu ) {
    		//Yes, use an updated ID
    		$canonical_screen_id = 'toolset_page_manage-refsites' ;
    	}
    	
    	return $canonical_screen_id;    
    }	
    	
}
?>