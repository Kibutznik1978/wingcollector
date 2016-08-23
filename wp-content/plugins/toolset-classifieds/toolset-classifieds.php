<?php
/*
 * Plugin Name: Toolset Classifieds
 * Plugin URI: https://wp-types.com/
 * Description: Toolset extension plugin to implement the functionality of classifieds.
 * Version: 0.3.5
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Text Domain: toolset_classifieds
 */

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if(defined('TOOLSET_EXT_CLASSIFIEDS_VERSION')) return;
define('TOOLSET_EXT_CLASSIFIEDS_VERSION', '0.3.5');
define('TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH', dirname(__FILE__));
define('TOOLSET_EXT_CLASSIFIEDS_PLUGIN_FOLDER', basename(TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH));

/**
 * defines Toolset Classifieds functionality options
 **/
// TODO: convert to settings option instead of constants
define('TOOLSET_CLASSIFIEDS_MESSAGE_SYSTEM', true);
define('TOOLSET_CLASSIFIEDS_PACKAGE_ORDER', true);

require TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/toolset-classifieds.class.php';

require TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/cred-wpml-integration-shortcode.php';
