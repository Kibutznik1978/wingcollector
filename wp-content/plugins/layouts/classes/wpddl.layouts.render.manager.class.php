<?php
class WPDD_Layouts_RenderManager{
    private static $instance;
    private $render_errors;
    private $attachment_markup = '';

    private function __construct(  ){
        $this->render_errors = array();

        if( !is_admin() ){
            add_action('wp_head', array($this,'wpddl_frontend_header_init'));
            add_action('wpddl_before_header', array($this, 'before_header_hook'));
            add_filter('ddl_render_cell_content', array(&$this,'fix_attachment_body'), 10, 3 );
            add_filter( 'ddl_render_cell_content', array(&$this, 'fix_cred_link_content_template_when_form_displays'), 10, 3 );
            add_filter('prepend_attachment', array(&$this, 'attachment_handler'), 999);
            add_action( 'ddl_before_frontend_render_cell', array(&$this, 'prevent_CRED_duplication_generic'), 1001, 2 );
            add_action('ddl_before_frontend_render_cell', array(&$this,'prevent_CRED_duplication_content_template'), 8, 2 );
            add_filter('ddl-content-template-cell-do_shortcode', array(&$this, 'prevent_cred_recursion'), 10, 2);
            add_action('get_header', array(&$this, 'fix_for_woocommerce_genesis'), 1 );
            add_filter('ddl_render_cell_content', array(&$this, 'message_if_menu_is_not_assigned'),11,2);
            add_filter('ddl-is_ddlayout_assigned', array(&$this, 'fix_cred_preview_render'), 99, 1 );
            add_filter( 'ddl-template_include-force-option', array(&$this, 'template_include_force_option'), 99, 1 );

            // Fix for 'Toolset Starter' theme.
            // When WooCommerce is enabled, the '/shop' page does not recognize the assigned layout.
            if( 'toolset starter' == strtolower( wp_get_theme() )) {
                add_action('template_redirect', array(&$this, 'fix_for_toolset_starter_wc_redirect'), 999);
            }
        }
    }

    public function template_include_force_option( $option ){
        if( $_GET && ( isset( $_GET['cred_form_preview'] ) || isset( $_GET['cred_user_form_preview'] ) ) ){
            $option = 2;
        }

        return $option;
    }

    public function fix_cred_preview_render( $bool ){
        if( $_GET && ( isset( $_GET['cred_form_preview'] ) || isset( $_GET['cred_user_form_preview'] ) ) ){
            $bool = apply_filters( 'ddl-template_include_force_render', false );
        }

        return $bool;
    }

    function fix_for_toolset_starter_wc_include($template) {
        // Only when WC is enabled/active.
        if( $this->is_woocommerce_enabled() ) {
            // 'Toolset Starter' theme's page.php already has the cases to check the assigned layout.
            // We just need to enforce or override WC's default handling for /shop and Product pages,
            // to use page.php.
            $new_template = locate_template( array( 'page.php' ) );

            if ( '' != $new_template ) {
                return $new_template ;
            }
        }

        return $template;
    }

    function fix_for_toolset_starter_wc_redirect() {
        add_filter('template_include', array(&$this, 'fix_for_toolset_starter_wc_include'), 99, 1);
    }

    /*
     * Add message if menu cell is palaced but menu is not assigned
     */
    function message_if_menu_is_not_assigned($content, $cell){
	
	$available_types_of_cells = array('menu-cell', 'avada-menu','avada-secondary-menu','2016-header-menu','divi-primary-navigation','genesis-menu','primary','secondary','navbar');
	if (
	    (in_array($cell->get_cell_type(), $available_types_of_cells) && trim(strip_tags($content)) === '')
	    /* This case below covers only situation when we have menu-cell placed on layout but menu is not assigned or created (or have 0 items inside)
	     * Problem with this cell is that content output is not empty even if you don't have any items inside menu, 
	     * this is very rare situation but it is covered
	     */ 
	    || (count(get_terms('nav_menu')) === 0 && $cell->get_cell_type() === 'menu-cell')
	) {
	    $alert_message = '<p>' . sprintf(__('You currently have no menus assigned to this theme location. Go to %sAppearance -> Menus -> Manage Locations%s and assign a menu to appropriate location.', 'ddl-layouts'), '<a href="' . admin_url() . 'nav-menus.php?action=locations">', '</a>') . '</p>';
	    $content = '<div class="alert alert-warning">' . $alert_message . '</div>';
	}
	return $content;
    }

    /**
     * WooCommerce / Generic Fixes
     */
    public function is_woocommerce_enabled() {
        if( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            return true;
        } else {
            return false;
        }
    }

    public function is_woocommerce_shop() {
        if( $this->is_woocommerce_enabled() ) {
            // Check if 'Shop' page has a separate layout assigned.
            $shop_page_id = get_option( 'woocommerce_shop_page_id' );
            $layout_selected = get_post_meta( $shop_page_id, WPDDL_LAYOUTS_META_KEY, true );
        }

        // If it's 'Shop' page and has a separate layout assigned.
        if( isset( $layout_selected ) && $layout_selected && function_exists( 'is_shop' ) && is_shop() ) {
            return $layout_selected;
        } else {
            return false;
        }
    }

    public function is_woocommerce_product() {
        if( $this->is_woocommerce_enabled() && function_exists('is_product') && is_product() ) {
            return true;
        } else {
            return false;
        }
    }

    public function is_woocommerce_archive() {
        if(
            $this->is_woocommerce_enabled() && (
                ( function_exists('is_product_category') && is_product_category() ) ||
                ( function_exists('is_product_taxonomy') && is_product_taxonomy() ) ||
                ( function_exists('is_product_tag') && is_product_tag() )
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Woocommerce/Genesis Fix
     */
    public function fix_for_woocommerce_genesis(){
        $obj = $this->get_queried_object();

        if( function_exists( 'is_shop' ) && is_shop() && $this->is_wp_post_object( $obj )  ){
            $layout = get_post_meta( $obj->ID, WPDDL_LAYOUTS_META_KEY, true );

            if( $layout ){
                add_filter( 'ddl-is_ddlayout_assigned', array(&$this, 'return_true'), 10, 1 );
                add_filter('get_layout_id_for_render', array(&$this, 'return_layout_id'), 10, 2 );
            }
        }

    }

    public function return_true( $bool ){
        return true;
    }

    public function return_layout_id( $id, $args ){
        $obj = $this->get_queried_object();
        if( $this->is_wp_post_object( $obj ) ){
            return get_post_meta( $obj->ID, WPDDL_LAYOUTS_META_KEY, true );
        }
        return $id;
    }

    public function prevent_cred_recursion( $content, $cell ){

        if( class_exists('CRED_Helper') && strpos($content, '[cred_') !== false){
            $content =  str_replace( '[', '[[', $content );
            $content =  str_replace( ']', ']]', $content );
        }

        return $content;
    }

    function is_wp_post_object( $post ){
        return 'object' === gettype( $post ) && get_class( $post ) === 'WP_Post';
    }


    function fix_attachment_body( $content, $cell, $renderer ){
        global $post;

        // Do not render attachment post type posts' bodies automatically
        if( $this->is_wp_post_object( $post ) && $post->post_type === 'attachment' && $this->attachment_markup ){
            $content = WPDD_Utils::str_replace_once( $this->attachment_markup , '', $content);
        }
        return $content;
    }

    /**
     * @param $cell
     * @param $renderer
     * Prevents Visual Editor cells to render CRED
     */
    public function prevent_CRED_duplication_generic($cell, $renderer){
        if( ( isset( $_GET['cred-edit-form']) || isset( $_GET['cred-edit-user-form'] ) ) &&
            class_exists('CRED_Helper')
        ){
            if( $cell->get_cell_type( ) === 'cell-text' && $cell->content_content_contains( array( 'cred_link_form', 'cred_link_user_form' ) ) ){
                $this->hide_cred_cred_links($_GET);
            } else {
                remove_filter('the_content', array('CRED_Helper', 'replaceContentWithForm'), 1000);
            }
        }
    }
    
    public function fix_cred_link_content_template_when_form_displays( $content, $cell, $renderer ){
        if( ( isset( $_GET['cred-edit-form']) || isset( $_GET['cred-edit-user-form'] ) ) &&
            class_exists('CRED_Helper')
        ){

            if( $cell->get_cell_type( ) === 'cell-content-template' && WPDD_Utils::string_contanins_strings( $content, array( '?cred-edit-user-form=', '?cred-edit-form=' ) ) ){
                $content = $this->hide_cred_cred_links($_GET, false).$content;
            } 
        }

        return $content;
    }

    function hide_cred_cred_links( $get, $echo = true ){
        $selector = '';
        if( isset($get['cred-edit-form']) ){
            $selector = 'cred-edit-form';
        } elseif ( isset($get['cred-edit-user-form']) ){
            $selector = 'cred-edit-user-form';
        }

        if( $selector !== '' ):
            ob_start();?>
            <style type="text/css">
                <!--
                a[href*="<?php echo $selector;?>"]{display:none;}
                -->
            </style>
            <?php
            if( $echo ){
                echo ob_get_clean();
            } else {
                return ob_get_clean();
            }
        endif;
        return '';
    }

    /**
     * @param $cell
     * @param $renderer
     * This is equivalent for CT cell preventing the_content filter to be applied if necessary
     */
    public function prevent_CRED_duplication_content_template( $cell, $renderer ){
        $content = $cell->get_content();
        $what_page = isset( $content['page'] ) && $content['page'] ? $content['page'] : '';
        if( isset( $_GET['cred-edit-form']) &&
            class_exists('CRED_Helper') &&
            $cell->get_cell_type() === 'cell-content-template' &&
            ( $cell->check_if_cell_renders_post_content( ) === false ||
                $what_page == 'this_page' )
        ){
            add_filter( 'wpv_filter_wpv_render_view_template_force_suppress_filters', array(&$this, 'wpv_render_view_template_force_suppress_filters_callback' ), 8, 5 );

        }
    }

    public function wpv_render_view_template_force_suppress_filters_callback( $bool, $ct_post, $post_in, $current_user_in, $args ){
        return true;
    }

    function attachment_handler($html){
        $this->attachment_markup = $html;
        return $html;
    }

    function get_layout_renderer( $layout, $args )
    {
        $manager = new WPDD_layout_render_manager($layout );
        $renderer = $manager->get_renderer( );
        // set properties  and callbacks dynamically to current renderer
        if( is_array($args) && count($args) > 0 )
        {
            $renderer->set_layout_arguments( $args );
        }
        return $renderer;
    }

    function get_query_post_if_any( $queried_object)
    {
        return 'object' === gettype( $queried_object ) && get_class( $queried_object ) === 'WP_Post' ? $queried_object : null;
    }

    function get_queried_object()
    {
        global $wp_query;
        $queried_object = $wp_query->get_queried_object();
        return $queried_object;
    }

    function get_layout_id_for_render( $layout, $args = null )
    {
        global $wpddlayout;

        $options = is_null( $args ) === false && is_array( $args ) === true ? (object) $args : false;

        $allow_overrides = $options && property_exists( $options, 'allow_overrides' ) ? $options->allow_overrides : true;

        $id = 0;

        if ($layout) {
            $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout);
        }

        if( $allow_overrides === true ){
            // If it's 'Shop' page and has a separate layout assigned.
            if( false !== $layout_selected = $this->is_woocommerce_shop() ) {
                global $post;

                // WC Hack: if there's no product added, but a layout is assigned to 'shop' page.
                // This hack prevents falling into a PHP Notice.
                $tmpPostType = 'page';

                if( isset( $post ) ) {
                    $tmpPostType = $post->post_type;
                }

                $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                $option = $wpddlayout->post_types_manager->get_layout_to_type_object($tmpPostType);

                if (is_object($option) && property_exists($option, 'layout_id') && (int)$option->layout_id === (int)$id) {
                    $id = $option->layout_id;
                }
            } 
            elseif( $this->is_woocommerce_product() ) { // If product page
                global $post;

                if( $post !== null )
                {

                    $post_id = $post->ID;
                    $layout_selected = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

                    if ( $layout_selected ) {
                        $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                        $option = $wpddlayout->post_types_manager->get_layout_to_type_object($post->post_type);

                        if( is_object( $option ) && property_exists( $option, 'layout_id') && (int) $option->layout_id === (int) $id )
                        {
                            $id = $option->layout_id;
                        }
                    }
                }
            } 
            elseif( $this->is_woocommerce_archive() ) { // If Product archive (i.e. post type archive, category, tag or tax)
                $term =  $this->get_queried_object();
                if ( $term && property_exists( $term, 'taxonomy' ) && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy);
                }
            // when blog is front
            }

            elseif( is_front_page() && is_home() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG) ){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);

            // when blog is not front
            } 
            elseif ((is_home()) && (!(is_front_page())) && (!(is_page())) && ($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG)) && !get_option( 'page_for_posts' )) {
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);
            } 
            elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG) && is_home() && (!(is_front_page())) && get_option( 'page_for_posts' )){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG);
            }
            elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME) && is_front_page() && (!(is_home())) && get_option('page_on_front')){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME);
            }
            elseif ( is_post_type_archive()  ) {

                $post_type_object = $this->get_queried_object();

                if ( $post_type_object && property_exists( $post_type_object, 'public' ) && $post_type_object->public && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name);
                }elseif ($post_type_object && property_exists($post_type_object, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy)) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy );
                }
            }
            elseif ( is_archive() && ( is_tax() || is_category() || is_tag() ) ) {

                $term =  $this->get_queried_object();
                if ( $term && property_exists( $term, 'taxonomy' ) && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy);
                }

            }
            // Check other archives
            elseif ( is_search()  && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH);
            }
            elseif ( is_author() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR ) ) {
                $author = WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR;
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( $author );
            }
            elseif ( is_year() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR);
            }
            elseif ( is_month() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH);
            }
            elseif ( is_day() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY);
            }
            elseif( is_404() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 ) )
            {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 );
            }
            elseif( is_front_page() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_on_front' ) != 0 ) {
                // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
                $static_page_for_posts = get_option( 'page_on_front' );
                $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
                if ( $layout_selected ) {
                    $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                }
            }
            elseif( is_home() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_for_posts' ) != 0 ) {
                // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
                $static_page_for_posts = get_option( 'page_for_posts' );
                $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
                if ( $layout_selected ) {
                    $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                }
            }
            else{

                global $post;

                if( $post !== null && is_singular() )
                {
                    $post_id = $post->ID;

                    $layout_selected = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

                    if ( $layout_selected ) {

                        $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);

                        $option = $wpddlayout->post_types_manager->get_layout_to_type_object($post->post_type);

                        if( is_object( $option ) && property_exists( $option, 'layout_id') && (int) $option->layout_id === (int) $id )
                        {
                            $id = $option->layout_id;
                        }
                    }
                }
            }
        }

        return apply_filters('get_layout_id_for_render', (int) $id, $layout );
    }

    function get_layout_content_for_render( $layout, $args )
    {
        $id = $this->get_layout_id_for_render( $layout, $args );

        $content = '';

        if ($id) {

            // Check for preview mode
            $layout = $this->get_rendered_layout($id);

            if ($layout) {
                $content = $this->get_rendered_layout_content( $layout, $args );
            } else {
                $content = '<p>' . __('Please check the layout you are trying to render actually exists.', 'ddl-layouts') . '</p>';
            }
        } else {
            if (!$layout) {
                $content = '<p>' . __('You need to select a layout for this page. The layout selection is available in the page editor.', 'ddl-layouts') . '</p>';
            }
        }

        return apply_filters('get_layout_content_for_render', $content, $this, $layout, $args );
    }

    private function get_rendered_layout_content( $layout, $args ){
        $renderer = $this->get_layout_renderer( $layout, $args );
        //$renderer = new WPDD_layout_render($layout);
        $content = $renderer->render( );

        $render_errors = $this->get_render_errors();
        if (sizeof($render_errors)) {
            $content .= '<p class="alert alert-error"><strong>' . __('There were errors while rendering this layout.', 'ddl-layouts') . '</strong></p>';
            foreach($render_errors as $error) {
                $content .= '<p class="alert alert-error">' . $error . '</p>';
            }
        }
        return $content;
    }

    public function get_rendered_layout( $id ){
        global $wpddlayout;
        $layout = null;
        $old_id = $id;
        if (isset($_GET['layout_id'])) {
            $id = $_GET['layout_id'];
        }

        if( isset( $_POST['layout_preview'] ) && $_POST['layout_preview'] ){

            $json_parser = new WPDD_json2layout();
            $layout = $json_parser->json_decode( stripslashes( $_POST['layout_preview'] ) );
        }
        else{
            $layout = $wpddlayout->get_layout_from_id($id);
            if (!$layout && isset($_GET['layout_id'])) {
                if ($id != $old_id) {
                    $layout = $wpddlayout->get_layout_from_id($old_id);
                }
            }
        }
        return $layout;
    }

    function wpddl_frontend_header_init(){
        global $wpddlayout;

        $wpddlayout->header_added = TRUE;

        $queried_object = $this->get_queried_object();
        $post = $this->get_query_post_if_any( $queried_object);


        if( null === $post ) return;
        // if there is a css enqueue it here
        $post_id = $post->ID;

        $layout_selected = get_post_meta($post_id, WPDDL_LAYOUTS_META_KEY, true);

        if( $layout_selected ){
            $id = $wpddlayout->get_post_ID_by_slug( $layout_selected, WPDDL_LAYOUTS_POST_TYPE );
            $header_content = get_post_meta($id, 'dd_layouts_header');
            echo isset($header_content[0]) ? $header_content[0] : '';
        }
    }

    function before_header_hook(){
        if (isset($_GET['layout_id'])) {
            $layout_selected = $_GET['layout_id'];
        } else {
            $post_id = get_the_ID();
            $layout_selected = WPDD_Layouts::get_layout_settings( $post_id, false );
        }
        if($layout_selected>0){
            //$layout_content = get_post_meta($layout_selected, WPDDL_LAYOUTS_SETTINGS);

            $layout_content =  WPDD_Layouts::get_layout_settings_raw_not_cached( $layout_selected, false );

            if (sizeof($layout_content) > 0) {
                $test = new WPDD_json2layout();
                $layout = $test->json_decode($layout_content[0]);
                $manager = new WPDD_layout_render_manager($layout);
                $renderer = $manager->get_renderer( );
                $html = $renderer->render_to_html();

                echo $html;
            }
        }
    }

    function record_render_error($data) {
        if ( !in_array($data, $this->render_errors) ) {
            $this->render_errors[] = $data;
        }
    }

    function get_render_errors() {
        return $this->render_errors;
    }

    public function item_has_ddlayout_assigned()
    {
        global $wpddlayout;

        // If it's 'Shop' page and has a separate layout assigned.
        if( $this->is_woocommerce_shop() ) {
            return true;
        } 
        elseif ( $this->is_woocommerce_product() ) { // If product page
            return true;
        } 
        elseif ( $this->is_woocommerce_archive() ) { // If Product archive (i.e. post type archive, category, tag or tax)
            return true;
        }
        elseif( is_front_page() && is_home() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG) ){
            return true;
        // when blog is not front
        } elseif ((is_home()) && (!(is_front_page())) && (!(is_page())) && ($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG)) && !get_option( 'page_for_posts' )) {
            return true;
        } 
        elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG) && is_home() && (!(is_front_page())) && get_option( 'page_for_posts' )){
            return true;
        }
        elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME) && is_front_page() && (!(is_home())) && get_option('page_on_front')){
            return true;
        }
        elseif ( is_post_type_archive()) {
            $post_type_object = $this->get_queried_object();

            if ($post_type_object && property_exists($post_type_object, 'public') && $post_type_object->public && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX . $post_type_object->name)) {
                return true;
            } elseif ($post_type_object && property_exists($post_type_object, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy)) {
                return true;
            }
            
        } 
        elseif (is_archive() && (is_tax() || is_category() || is_tag())) {
            $term = $this->get_queried_object();
            if ($term && property_exists($term, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $term->taxonomy)) {
                return true;
            }
        } // Check other archives
        elseif (is_search() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_SEARCH)) {

            return true;
        } 
        elseif (is_author() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR)) {
            return true;
        } 
        elseif (is_year() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_YEAR)) {

            return true;
        } 
        elseif (is_month() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_MONTH)) {

            return true;
        } 
        elseif (is_day() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_DAY)) {

            return true;
        } 
        elseif (is_404() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_404)) {
            return true;
        }
        elseif( is_front_page() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_on_front' ) != 0 ) {
            // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
            $static_page_for_posts = get_option( 'page_on_front' );
            $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
            if ( $layout_selected ) {
                return true;
            }
        }
        elseif( is_home() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_for_posts' ) != 0 ) {
            // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
            $static_page_for_posts = get_option( 'page_for_posts' );
            $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
            if ( $layout_selected ) {
                return true;
            }
        }
        else {
            global $post;

            if( $this->is_wp_post_object( $post ) && is_singular() ){

                $assigned_template = get_post_meta($post->ID, WPDDL_LAYOUTS_META_KEY, true);

                if ( !$assigned_template ) {
                    return false;
                }

                return $assigned_template !== 'none';
            }
        }
        return false;
    }


    public static function getInstance(  )
    {
        if (!self::$instance)
        {
            self::$instance = new WPDD_Layouts_RenderManager(  );
        }

        return self::$instance;
    }
}