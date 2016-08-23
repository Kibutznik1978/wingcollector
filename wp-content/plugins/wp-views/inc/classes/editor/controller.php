<?php

/**
 * Class WPV_Content_Template_Editor_Controller
 *
 * @since 2.1
 */
class WPV_Content_Template_Editor_Controller {

	/**
	 * @var array WPV_Content_Template_Editor_Interface
	 */
	protected $editors;

	/**
	 * @var object WPV_Content_Template_Editor_Interface
	 */
	protected $active_editor;

	/**
	 * Function to add an editor
	 * if editor requirements are met the eidtor will be added to $this->editors
	 *
	 * @param WPV_Content_Template_Editor_Interface $editor
	 */
	public function add_editor( WPV_Content_Template_Editor_Interface $editor ) {
		if( $editor->requirements_met() )
			$this->editors[] = $editor;
	}

	/**
	 * Build the current active editor
	 */
	public function build() {
		$this->active_editor = $this->fetch_active_editor();
		$this->hooked_actions();
	}

	/**
	 * Returns active editor or if no active editor available
	 * it will returns our basic editor
	 *
	 * @return mixed|object|WPV_Content_Template_Editor_Basic
	 */
	protected function fetch_active_editor() {
		if( $this->active_editor !== null )
			return $this->active_editor;

		foreach( $this->editors as $editor ) {
			if( $editor->active() ) 
				return $editor;
		}

		// should not happen, but if so use the basic editor.
		return new WPV_Content_Template_Editor_Basic();
	}

	/**
	 * Return object instance (still no singleton)
	 * @return $this
	 */
	public function get_instance() {
		return $this;
	}

	/**
	 * All hooked actions and filters
	 */
	private function hooked_actions() {
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_scripts' ) );
		add_action( 'wpv_ct_editor_sections',   array( $this, 'render' ), 20 );
	}

	/**
	 * Scripts
	 */
	public function enqueue_scripts() {
		wp_localize_script( 'views-ct-editor-js', 'wpv_ct_editor_choice', $this->active_editor->get_id() );
	}

	/**
	 * This is hooked into action 'wpv_ct_editor_sections'
	 * @param $ct
	 */
	public function render( $ct ) {
		$control_editor_select = '';

		if( count( $this->editors ) > 0 ) {
			$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id='.$ct->id );
			
			$editor_switch_buttons = array();

			foreach( $this->editors as $editor ) {
				$active = $editor->get_id() == $this->active_editor->get_id()
					? ' button-primary'
					: '';

				$editor_switch_buttons[] = '<a class="button'.$active.'" href="'.$admin_url.'&ct_editor_choice='.$editor->get_id().'">'.$editor->get_name().'</a>';
			}

			$editor_switch_buttons = apply_filters( 'wpv_ct_control_switch_editor_buttons', $editor_switch_buttons );

			if( count( $editor_switch_buttons ) > 1 ) {
				$control_editor_select .= '<div class="wpv-ct-control-switch-editor">';
				//$control_editor_select .= __( 'Select Editor: ', 'wpv-views' );
				$control_editor_select .= join( ' ', array_reverse( $editor_switch_buttons ) );
				$control_editor_select .= '</div>';
			}
		}

		wpv_ct_editor_render_section(
			__( 'Template', 'wpv-views' ),
			'js-wpv-content-section',
			$control_editor_select . $this->active_editor->render( $ct ),
			true,
			'',
			'',
			array( 'section' => 'content_section', 'pointer_slug' => 'ptr_section' )
		);
	}
}