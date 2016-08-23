<?php
/*
  Plugin Name: Toolset Framework Installer
  Plugin URI: http://wp-types.com/documentation/views-demos-downloader/?utm_source=local-ref-site&utm_medium=wpadmin&utm_term=visit-plugin-site&utm_content=plugins-page&utm_campaign=framework-installer
  Description: Download complete reference designs for Types and Views to your local test site.
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com
  Version: 2.0.2
 */
define('WPVDEMO_VERSION', '2.0.2');
define('WPVDEMO_ABSPATH', dirname(__FILE__));
define('WPVDEMO_WPCONTENTDIR',WP_CONTENT_DIR);
define('WPVDEMO_RELPATH', plugins_url() . '/' . basename(WPVDEMO_ABSPATH));
define('WPVDEMO_DEPS_XML_FILE',WPVDEMO_ABSPATH.DIRECTORY_SEPARATOR.'deps.xml');

if (!defined('WPVDEMO_URL')) {
    define('WPVDEMO_URL', 'http://ref.wp-types.com');
}
if (!defined('WPVDEMO_DOWNLOAD_URL')) {
    define('WPVDEMO_DOWNLOAD_URL', 'http://ref.wp-types.com/_wpv_demo');
}
if (!defined('WPVDEMO_DEBUG')) {
    define('WPVDEMO_DEBUG', false);
}
if (!(get_option('wpv_import_is_done'))) {
  if (defined('WPVDEMO_DEBUG')) {
  	if (!(WPVDEMO_DEBUG)) {
  		//Off
  		error_reporting(0);
  	}  	
  } else {
  	//Not in debug
  	error_reporting(0);
  }
  	
}

if (!defined('WPVDEMO_REMOTE_LOG_URL')) {
	define('WPVDEMO_REMOTE_LOG_URL', 'https://api.wp-types.com/');
}
if (!defined('WPVDEMO_TOOLSET_DOMAIN')) {
	define('WPVDEMO_TOOLSET_DOMAIN', 'wp-types.com');
}

// Add installer, exclude Discover WP multisite implementation
if (!(is_multisite())) {
	include dirname( __FILE__ ) . '/common/installer/loader.php';
	WP_Installer_Setup($wp_installer_instance,
	    array(
	        'plugins_install_tab' => '1',
	        'repositories_include' => array('wpml', 'toolset'),
            'high_priority' => true
	    ));
}
//The Basic Hooks
add_action('after_setup_theme', 'wpvdemo_init_embedded_code', 9999); // Original priority is 999
add_action('plugins_loaded', 'wpvdemo_plugins_loaded_hook', 2);
add_action( 'plugins_loaded', 'wpvdemo_remove_runtime_st_registration' ,35);
add_action('init', 'wpvdemo_init_hook');
add_action('init','register_color_taxonomy_bootcommerce',50);
add_action('init','wpvdemo_refresh_rewrite_rules_on_firstload',50);
add_action('wp_ajax_wpvdemo_download', 'wpvdemo_download');
add_action('wp_ajax_wpvdemo_post_count', 'wpvdemo_get_post_count');
add_filter('wp_get_nav_menu_items', 'wpvdemo_wp_get_nav_menu_items_filter', 10,3);
add_action( 'admin_head', 'viewsdemo_admin_render_js_settings' );
add_action('plugins_loaded', 'wpv_demo_views_init', 2);
add_action('plugins_loaded','wpv_demo_disable_admin_notices_demo');
add_action('plugins_loaded', 'wpvdemo_optional_plugins_activated_before_import');
add_action('after_setup_theme', 'wpvdemo_disable_auto_reg_strings_wpml_wpv');
add_action( 'wpml_loaded', 'wpvdemo_dont_auto_register_wpstrings',999 );
register_activation_hook(__FILE__, 'wpvdemo_activation_hook');
add_filter( 'image_downsize', 'wpvdemo_regen_thumbs_media_downsize', 10, 3 );
add_action( 'types_after_init', 'wpvdemo_remove_auto_register_types',99);
add_filter( 'plugin_row_meta', 'wpvdemo_plugin_plugin_row_meta', 10, 4 );

if (is_multisite()) {
	add_action('init','wpvdemo_remove_new_wpmlnotices',45);
}
if (!(is_multisite())) {
	add_filter('wpvdemo_blank_site_message','customize_message_WP_reset');
}

//Require files and functions
require_once WPVDEMO_ABSPATH . '/includes/messages.php';
require_once WPVDEMO_ABSPATH . '/view_demo_text.php';
require_once WPVDEMO_ABSPATH . '/class.wordpress_reset.php';
require_once WPVDEMO_ABSPATH . '/includes/main_functions/views_demo_functions.php';
require_once WPVDEMO_ABSPATH . '/classes/class-toolset-framework-installer.php';

add_action( 'init', 'init_framework_installer_plugin' );

function init_framework_installer_plugin()
{
    global $frameworkinstaller;
    $frameworkinstaller = new Toolset_Framework_Installer();
}