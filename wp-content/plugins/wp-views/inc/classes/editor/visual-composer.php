<?php

/**
 * Class WPV_Content_Template_Editor_Visual_Composer
 *
 * @since 2.1
 */
class WPV_Content_Template_Editor_Visual_Composer extends WPV_Content_Template_Editor_Abstract {

	/**
	 * Do not change
	 * @var string
	 */
	protected $id = 'vc';

	/**
	 * Name
	 * @var string
	 */
	protected $name = 'Visual Composer';

	/**
	 * Holds the Visual Composer editor object
	 * @var $editor Vc_Backend_Editor
	 */
	public $editor;

	/**
	 * Visual Composer editor object needs a WP_Post object
	 * @var $post WP_Post
	 */
	private $post;

	/**
	 * Minimum Version
	 * @var string version number
	 */
	protected $minimum_version = '4.11';

	/**
	 * Log for rendered css
	 * used to make sure not printing vc custom css twice
	 *
	 * @var array of CT ids, of which VC's custom css is already rendered
	 */
	private $log_rendered_css;

	/**
	 * Check if all requirements are met, which are needed to run Visual Composer
	 * @inheritdoc
	 * @return bool
	 */
	public function requirements_met() {
		// false when Visual Composer is not active
		if( ! defined( 'WPB_VC_VERSION' ) )
			return false;

		// version too low
		// Todo generalise prove of version and move to abstract for all editors
		if( version_compare( WPB_VC_VERSION, $this->minimum_version ) < 0 ) {
			add_filter( 'wpv_ct_control_switch_editor_buttons', array( $this, 'add_disabled_button' ) );
			return false;
		}

		// load display resources
		$this->load_display_resources();

		// check for functions used
		if(
			! function_exists( 'vc_user_access' )
			|| ! class_exists( 'Vc_Shortcodes_Manager' )
			|| ! method_exists( 'Vc_Manager', 'backendEditor' )
		)
			return false;

		// don't show VC if user role is not allowed to use the backend editor
		if( ! vc_user_access()->part( 'backend_editor' )->can()->get() )
			return false;

		// false if page is not admin.php or admin-ajax.php
		global $pagenow;
		if( $pagenow != 'admin.php' && $pagenow != 'admin-ajax.php' )
			return false;

		// false if page not ct-editor
		$page = wpv_getget( 'page' );
		if( $pagenow == 'admin.php' && $page != WPV_CT_EDITOR_PAGE_NAME )
			return false;

		// false on ajax
		if( $pagenow == 'admin-ajax.php' ) {
			$this->on_ajax_call();

			return false;
		}

		// Visual Composer editor object needs an WP_POST object to work correctly
		if( ! $this->setup_post_type() )
			return false;

		if( $this->active() )
			$this->bootstrap();

		return true;
	}

	/**
	 * If version requirements does not met, we show a hint.
	 *
	 * @param $buttons
	 * @return array
	 */
	public function add_disabled_button( $buttons ) {
		$buttons[] = '<button class="button-secondary" onClick="javascript:alert( jQuery( this ).attr( \'title\' ) );" title="' . sprintf( __( 'Version %s or higher required', 'wpv-views' ), $this->minimum_version ) . '">' . $this->name . '</button>';
		$buttons = array_reverse( $buttons );
		return $buttons;
	}

	/**
	 * Boostrap our editor class
	 * will be called when $this->active() = true;
	 */
	private function bootstrap() {
		$this->hook_actions();
	}

	/**
	 * All hooked actions and filters
	 */
	private function hook_actions() {
		// this allows Views "Fields and Views" dialogs to load without a post type
		add_filter( 'wpv_filter_dialog_for_editors_requires_post', '__return_false' );
		add_action( 'admin_init', array( $this, 'setup' ) );
		add_filter( 'vc_check_post_type_validation', array( $this, 'validate_post_type' ), 10, 2 );
		add_action( 'admin_print_scripts', array( &$this->editor, 'enqueueEditorScripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_scripts' ) );
		add_action( 'admin_print_scripts', array( Vc_Shortcodes_Manager::getInstance(), 'buildShortcodesAssets',), 1 );
	}

	/**
	 * Setup the editor
	 * called on action 'admin_init'
	 */
	public function setup() {
		// Disable Visual Composers Frontend Editor
		vc_disable_frontend();

		// Get backend editor object through VC_Manager (vc di container)
		global $vc_manager;
		$this->editor = $vc_manager->backendEditor();

		// VC_Backend_Editor->render() registers all needed scripts
		// the "real" render came later in $this->html_output();
		$this->editor->render( $this->post->post_type );
	}

	/**
	 * Load display resources
	 * This is the only function which will be fired on frontend
	 */
	private function load_display_resources() {
		// make sure all vc shortcodes are loaded (needed for ajax pagination)
		if ( method_exists( 'WPBMap', 'addAllMappedShortcodes' ) )
			WPBMap::addAllMappedShortcodes();

		add_action( 'the_content', array( $this, 'render_custom_css' ) );
	}

	/**
	 * Visual Composer stores custom css as postmeta.
	 * We need to check if current post has content_template and if so apply the custom css.
	 * Hooked to the_content
	 *
	 * @param $content
	 * @return mixed
	 */
	public function render_custom_css( $content ) {
		if(
			method_exists( 'Vc_Base', 'addPageCustomCss' )
			&& method_exists( 'Vc_Base', 'addShortcodesCustomCss' )
		) {
			$content_template = get_post_meta( get_the_ID(), '_views_template', true );

			if( $content_template && ! isset( $this->log_rendered_css[$content_template] ) ) {
				$vcbase = new Vc_Base();
				$vcbase->addPageCustomCss( $content_template );
				$vcbase->addShortcodesCustomCss( $content_template );
				$this->log_rendered_css[$content_template] = true;
			}
		}
		return $content;
	}

	/**
	 * Setup the current post type
	 * @return bool
	 */
	protected function setup_post_type() {
		if( $this->setup_post_type_by_global() )
			return true;

		if( $this->check_content_template_as_post() )
			return true;

		return false;
	}

	/**
	 * Check for global $post and use it, otherwise return false
	 * @return bool
	 */
	private function setup_post_type_by_global() {
		global $post;

		if( ! $post instanceof WP_Post )
			return false;

		$this->post = $post;
		return true;
	}


	/**
	 * Check for content template ($_GET['ct_id']) and use it, otherwise return false
	 * @return bool
	 */
	protected function check_content_template_as_post() {
		$content_template_id  = wpv_getget( 'ct_id' );

		if( ! $content_template_id )
			return false;

		$content_template_obj = get_post( $content_template_id );
		if( $content_template_obj === null )
			return false;

		$this->post = $content_template_obj;
		return true;
	}

	/**
	 * We need to add Views type of content templates
	 * to the allowed types of Visual Composer
	 *
	 * called on filter 'vc_check_post_type_validation'
	 *
	 * @param $default
	 * @param $type
	 *
	 * @return bool
	 */
	public function validate_post_type( $default, $type ) {
		if( $type == $this->post->post_type )
			return true;

		return $default;
	}

	/**
	 * On Ajax call
	 */
	private function on_ajax_call() {
		// this adds the [Fields and Views] to editor of visual composers text element
		if( isset( $_POST['action'] ) && $_POST['action'] == 'vc_edit_form' ) {
			add_filter( 'wpv_filter_dialog_for_editors_requires_post', '__return_false' );
		}
	}

	/**
	 * We need some custom scripts ( &styles )
	 * called on 'admin_print_scripts'
	 */
	public function print_scripts() {

		// disable the 100% and fixed vc editor navigation when scrolling down
		$output = '
		<style type="text/css">
			body.toolset_page_ct-editor .composer-switch {
				display:none;
			}
			body.toolset_page_ct-editor .wpv-settings-section,
			body.toolset_page_ct-editor .wpv-setting-container {
				max-width: 96% !important;
			}
			
			body.toolset_page_ct-editor .wpv-setting-container .wpv-settings-header {
				width: 15% !important;
			}
			
			.wpv-setting {
				width: 84%;
			}
			
			.wpv-mightlong-list li {
				min-width: 21%;
			}

			body.toolset_page_ct-editor .js-wpv-content-section .wpv-settings-header {
				display: block;
			}
			
			body.toolset_page_ct-editor .wpv-ct-control-switch-editor {
				padding-left: 105px;
			}
			
			body.toolset_page_ct-editor .js-wpv-content-section .wpv-setting {
				width: 100% !important;
			}
			
			.vc_subnav-fixed{
				position:relative !important;
				top:auto !important;
				left:auto !important;
				z-index: 1 !important;
				padding-left:0 !important;
			}
		</style>';

		// disable our backbone extension due to conflicts with vc (see util.js)
		$output .= "<script>var ToolsetDisableBackboneExtension = '1';</script>";
		echo preg_replace('/\v(?:[\v\h]+)/', '', $output );
	}

	/**
	 * The Editor HTML Output
	 *
	 * @return string
	 */
	protected function html_output(){
		ob_start(); ?>
			<div style="display: none;">
				<input type="hidden" id="post_ID" name="post_ID" value="<?php echo $this->post->ID; ?>">
				<textarea cols="30" rows="10" id="wpv_content" name="wpv_content" data-bind="textInput: postContentAccepted"></textarea>
				<?php wp_editor(  $this->post->post_content, 'content', array( 'media_buttons' => true ) ); ?>
			</div>

			<div id="wpb_visual_composer" style="padding-bottom: 5px; background: #fff;"><?php $this->editor->renderEditor( $this->post ); ?></div>
		<?php
		$script = "<script>
				jQuery( window ).load( function( ) {
					/* no fullscreen, no vc save button */
					jQuery( '#vc_navbar .vc_save-backend, #vc_fullscreen-button' ).remove();

					/* show vc editor */
					vc.app.show();
					vc.app.status = 'shown';
					
					var viewsBasicTextarea 		 = jQuery( '#wpv_content' );
					var wordpressDefaultTextarea = jQuery( '#content' );
					
					/* Visual Composer fires the 'sync' event everytime something is changed */
					/* we use this to enable button 'Save all sections at once' if content has changed */
					vc.shortcodes.on( 'sync', function() {
						if( wordpressDefaultTextarea.val() != viewsBasicTextarea.val() ) {
							viewsBasicTextarea.val( wordpressDefaultTextarea.val() );

							WPViews.ct_edit_screen.vm.postContentAccepted = function(){ return wordpressDefaultTextarea.val() };
							WPViews.ct_edit_screen.vm.propertyChangeByComparator( 'postContent', _.isEqual );
						}
					} );
				} );</script>";
				echo preg_replace('/\v(?:[\v\h]+)/', '', $script);
			$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}