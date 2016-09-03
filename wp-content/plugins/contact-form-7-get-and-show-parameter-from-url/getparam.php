<?php
/**
 * Plugin Name: Contact Form 7 Get and Show Parameter from URL
 * Plugin URI: http://elementdesignllc.com/2011/11/contact-form-7-get-parameter-from-url-into-form-plugin/
 * Description: Get and Show Parameter from URL Contact Form 7 Plugin
 * Version: 0.9.7
 * Author: Chad Huntley
 * Author URI: http://URI_Of_The_Plugin_Author
 * License: GPL2
 */

/*  Copyright 2013  Chad Huntley  (email : chad@elementdesignllc.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_getparam' );

function wpcf7_add_shortcode_getparam() {
    if ( function_exists( 'wpcf7_add_shortcode' ) ) {
        wpcf7_add_shortcode( 'getparam', 'wpcf7_getparam_shortcode_handler', true );
        wpcf7_add_shortcode( 'showparam', 'wpcf7_showparam_shortcode_handler', true );
    }
}

function wpcf7_getparam_shortcode_handler($tag) {
    if (!is_array($tag)) return '';

    $name = $tag['name'];
    if (empty($name)) return '';

    $html = '<input type="hidden" name="' . $name . '" value="'. esc_attr( $_GET[$name] ) . '" />';
    return $html;
}

function wpcf7_showparam_shortcode_handler($tag) {
    if (!is_array($tag)) return '';

    $name = $tag['name'];
    if (empty($name)) return '';

    $html = esc_html( $_GET[$name] );
    return $html;
}