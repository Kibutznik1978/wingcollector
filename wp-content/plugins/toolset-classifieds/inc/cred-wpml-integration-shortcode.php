<?php
/**
 * provide default implementation of [wpml-post-languages] and [wpml-post-original-language] shortcodes for when
 * CRED WPML integration plugin is not active.
 */
if (!isset($cred_wpml_integration_sub_active)) {

    add_action('init', 'cred_wpml_integration_shortcodes', 100);

    $cred_wpml_integration_sub_active = true;

    if( !function_exists('cred_wpml_integration_shortcodes') )
    {
        function cred_wpml_integration_shortcodes() {
            global $Class_CRED_WPML_Integration;

            if (!isset($Class_CRED_WPML_Integration)) {
                // CRED WPML integration is not active
                // Add our own do nothing shortcode

                add_shortcode('wpml-post-languages', 'wpml_post_languages_shortcode');
                add_shortcode('wpml-post-original-language', 'wpml_post_original_language_shortcode');

            }
        }
    }

    if( !function_exists('wpml_post_languages_shortcode') )
    {
        function wpml_post_languages_shortcode($atts, $value) {
            // return un-processed.
            return do_shortcode($value);
        }
    }
    if( !function_exists('wpml_post_original_language_shortcode') )
    {
        function wpml_post_original_language_shortcode($atts, $value) {
            // return un-processed.
            return do_shortcode($value);
        }
    }
}