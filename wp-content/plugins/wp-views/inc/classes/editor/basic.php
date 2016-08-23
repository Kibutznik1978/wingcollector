<?php

/**
 * Class WPV_Content_Template_Editor_Basic
 *
 * @since 2.1
 */
class WPV_Content_Template_Editor_Basic extends WPV_Content_Template_Editor_Abstract {

	/**
	 * Do not change
	 * @var string
	 */
	protected $id = 'basic';

	/**
	 * Name
	 * @var string
	 */
	protected $name = 'Basic';


	/**
	 * our basic editor has no special requirements
	 */
	public function requirements_met() {
		return true;
	}

	/**
	 * The Editor HTML Output
	 * this code was previously in section-content.php and is a 1:1 copy.
	 * @return string
	 */
	protected function html_output(){
		ob_start(); ?>
		<div class="js-code-editor code-editor content-editor" data-name="complete-output-editor">
			<div class="code-editor-toolbar js-code-editor-toolbar">
				<ul>
					<?php
					$menus_to_add = array(
						'post',						// wpv-post shortcodes plus non-Types fields under their own section
						'post-extended',			// generic shortcodes extended in the Basic section
						'post-fields-placeholder',	// non-Types fields on demand
						'user',						// basic user data
						'body-view-templates',		// Content Templates
						'post-view',				// Views listing posts
						'taxonomy-view',			// all available Views listing terms
						'user-view'					// all available Views listing users
					);
					do_action( 'wpv_views_fields_button', 'wpv_content', $menus_to_add );

					// Needed so CRED displays a button instead of a fake anchor tag
					if( wpv_ct_editor_is_cred_button_supported() ) {
						define("CT_INLINE", "1");
						do_action('wpv_cred_forms_button', 'wpv_content');
					}

					wpv_ct_editor_content_add_media_button( $this->content_template->id, 'wpv_content' );
					?>
				</ul>
			</div>
			<!--suppress HtmlFormInputWithoutLabel -->
        <textarea cols="30" rows="10" id="wpv_content" name="wpv_content"
                  data-bind="textInput: postContentAccepted"></textarea>

			<!--
				CSS editor
			-->
			<div class="wpv-editor-metadata-toggle" data-bind="click: toggleCssEditor">
            <span class="wpv-toggle-toggler-icon">
                <i data-bind="attr: { class: isCssEditorExpanded() ? 'icon-caret-up fa fa-caret-up icon-large fa-lg' : 'icon-caret-down fa fa-caret-down icon-large fa-lg' }"></i>
            </span>
				<i class="icon-pushpin fa fa-thumb-tack" data-bind="widthToggleVisible: isCssPinVisible"></i>
				<strong><?php _e( 'CSS editor', 'wpv-views' ); ?></strong>
			</div>
			<div class="wpv-ct-assets-inline-editor"
			     data-bind="editorVisible: isCssEditorExpanded"
			     data-target-editor="css">
				<!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="name" id="wpv_template_extra_css"
                      data-bind="textInput: templateCssAccepted"></textarea>
			</div>

			<!--
				JS editor
			-->
			<div class="wpv-editor-metadata-toggle" data-bind="click: toggleJsEditor">
            <span class="wpv-toggle-toggler-icon">
                <i data-bind="attr: { class: isJsEditorExpanded() ? 'icon-caret-up fa fa-caret-up icon-large fa-lg' : 'icon-caret-down fa fa-caret-down icon-large fa-lg' }"></i>
            </span>
				<i class="icon-pushpin fa fa-thumb-tack" data-bind="widthToggleVisible: isJsPinVisible"></i>
				<strong><?php _e( 'JS editor', 'wpv-views' ); ?></strong>
			</div>
			<div class="wpv-ct-assets-inline-editor"
			     data-bind="editorVisible: isJsEditorExpanded"
			     data-target-editor="js">
				<!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="name" id="wpv_template_extra_js"
                      data-bind="textInput: templateJsAccepted"></textarea>
			</div>

			<?php wpv_formatting_help_content_template(); ?>
		</div>

		<p class="update-button-wrap">
        <span class="update-action-wrap">
            <span class="js-wpv-message-container"></span>
            <span class="spinner ajax-loader" data-bind="spinnerActive: isContentSectionUpdating"></span>
        </span>
			<button data-bind="
                enable: isContentSectionUpdateNeeded,
                attr: { class: isContentSectionUpdateNeeded() ? 'button-primary' : 'button-secondary' },
                click: contentSectionUpdate">
				<?php _e( 'Update', 'wpv-views' ); ?>
			</button>
		</p>

		<?php

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}