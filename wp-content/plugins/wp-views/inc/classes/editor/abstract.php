<?php

/**
 * Class WPV_Content_Template_Editor_Abstract
 *
 * @since 2.1
 */
abstract class WPV_Content_Template_Editor_Abstract implements WPV_Content_Template_Editor_Interface {

	/**
	 * Id of the editor (unique)
	 * @var string
	 */
	 protected $id;

	/**
	 * Name of the editor
	 * @var string
	 */
	 protected $name;

	/**
	 * Current content template object
	 * @var WPV_Content_Template
	 */
	protected $content_template;

	
	/**
	 * Return id of the editor
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Return name of the editor
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	
	/**
	 * This is hooked to action 'wpv_ct_editor_sections' which delivers WPV_Content_Template
	 * Not all editors needs WPV_Content_Template but our basic does
	 *
	 * @param WPV_Content_Template $content_template
	 *
	 * @return mixed
	 */
	public function render( WPV_Content_Template $content_template ) {
		$this->content_template = $content_template;
		return $this->html_output();
	}

	
	/**
	 * All requirements goes here (i.e. needed plugin)
	 * @return bool
	 */
	public function requirements_met() {
		return true;
	}

	
	/**
	 * Editors output
	 * @return mixed
	 */
	abstract protected function html_output();

	
	/**
	 * Check if editor is the current selected one.
	 *
	 * @return bool
	 */
	public function active() {
		$content_template_id  = wpv_getget( 'ct_id' );

		if( isset( $_GET['ct_editor_choice'] ) )
			update_post_meta( $content_template_id, 'wpv_ct_editor_choice', sanitize_text_field( $_GET['ct_editor_choice'] ) );

		$editor_choice = get_post_meta( $content_template_id, 'wpv_ct_editor_choice', true );

		if( $editor_choice == $this->get_id() )
			return true;
		
		return false;
	}
}