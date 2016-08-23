<?php

/**
 * Class WPV_Content_Template_Editor_Interface
 *
 * @since 2.1
 */
interface WPV_Content_Template_Editor_Interface {
	public function get_id();
	public function requirements_met();
	public function render( WPV_Content_Template $content_template );
}