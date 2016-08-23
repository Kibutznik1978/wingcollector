<?php

class WPDD_LayoutsListing
{

    private $args = array();
    private $layouts_query = null;
    private $layouts_list = array();
    private $count_what = '';
    private $mod_url = array();
    private $column_active = '';
    private $column_sort_to = 'ASC';
    private $column_sort_now = 'ASC';
    private $column_sort_date_to = 'DESC';
    private $column_sort_date_now = 'DESC';

    public static $OPTIONS_ALERT_TEXT;

    private static $instance;

    private $get_all;

    private function __construct()
    {

        self::$OPTIONS_ALERT_TEXT = __('Some changes are not saved. If you don\'t see the \'update\' button, scroll the list.', 'ddl-layouts');

        add_action('wp_ajax_set_layout_status', array(&$this, 'set_layout_status_callback'));
        add_action('wp_ajax_delete_layout_record', array(&$this, 'delete_layout_record_callback'));
        add_action('wp_ajax_change_layout_usage_box', array(&$this, 'set_change_layout_usage_box'));

        add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME, array(&$this, 'set_layouts_post_types_on_usage_change_js'));
        add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_layout_post_loop_cell_manager::POST_TYPES_LOOPS_NAME, array(&$this, 'set_layouts_archives_on_usage_change_js'));
        add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION, array(&$this, 'set_layouts_others_on_usage_change_js'));


        add_action('wp_ajax_get_ddl_listing_data', array(&$this, 'get_ddl_listing_data'));

        if (isset($_GET['page']) && $_GET['page'] == WPDDL_LAYOUTS_POST_TYPE) {
            add_action('admin_enqueue_scripts', array($this, 'listing_scripts'));
        }

        $this->get_all = DDL_GroupedLayouts::getInstance();
        add_action('wp_ajax_get_all_layouts_posts', array(&$this->get_all, 'get_all_layouts_posts'));
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDD_LayoutsListing();
        }

        return self::$instance;
    }

    public function print_single_posts_assigned_section($current)
    {
        global $wpddlayout;

        return $wpddlayout->individual_assignment_manager->return_assigned_layout_list_html($current);
    }

    public function init()
    {
        $this->set_mod_url();
        $this->set_args();
        $this->set_count_what();
        $this->set_count();
        $this->display_list();
    }

    public function set_change_layout_usage_box()
    {
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-select-set-change-nonce'], 'layout-select-set-change-nonce')) {
            $nonce = wp_create_nonce('layout-set-change-post-types-nonce');

            $html = $this->print_dialog_checkboxes($_POST['layout_id'], false, '');
            $send = wp_json_encode(array('message' => array('html_data' => $html, 'nonce' => $nonce, 'layout_id' => $_POST['layout_id'])));
        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function print_dialog_checkboxes($current = false, $do_not_show = false, $id = "", $show_ui = true)
    {
        $current = $current ? (int)$current : null;
        $html = '';
        $html .= $this->print_single_posts_assign_section($current);
        return apply_filters( 'ddl_get_change_dialog_html', $html, $current, $do_not_show, $id, $show_ui );
    }

    public function print_single_posts_assign_section($current)
    {
        ob_start();
        include WPDDL_GUI_ABSPATH . 'editor/templates/individual-posts.box.tpl.php';
        return ob_get_clean();
    }

    public function set_layouts_post_types_on_usage_change_js()
    {
        global $wpddlayout;

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {
            $post_types = isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_unique($_POST['post_types']) : array();

            if (isset($_POST['extras'])) {
                $extras = $_POST['extras'];

                if (isset($extras['post_types']) && count($extras['post_types']) > 0) {
                    $types_to_batch = $extras['post_types'];
                }
            }

            if (isset($extras) && isset($types_to_batch)) {
                $wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once($_POST['layout_id'], $post_types, $types_to_batch);

            } else {
                $wpddlayout->post_types_manager->handle_post_type_data_save($_POST['layout_id'], $post_types, count($post_types) === 0 );
            }

            $status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';

            $send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], '', $_POST);

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function set_layouts_archives_on_usage_change_js()
    {
        global $wpddlayout;

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {

            $default_archives = isset($_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_DEFAULT_LOOPS_NAME]) ? $_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_DEFAULT_LOOPS_NAME] : array();

            $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save($default_archives, $_POST['layout_id']);

            $status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';

            $send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], '', $_POST);

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function set_layouts_others_on_usage_change_js()
    {
        global $wpddlayout;

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {

            $others_section = isset($_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION]) ? $_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION] : array();

            $wpddlayout->layout_post_loop_cell_manager->handle_others_data_save($others_section, $_POST['layout_id']);

            $status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';

            $send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], '', $_POST);
        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function get_send( $status, $where = false, $layout_id = null, $message = array(), $args = array() )
    {

        $send = $this->set_up_send_data($status, $where, $layout_id, $message, $args);

        return $send;
    }

    public function set_up_send_data($status, $where = false, $layout_id = null, $message = array(), $args = array())
    {

        $Data = array('Data' => $this->get_grouped_layouts($status, $args));

        if ($where === 'editor' || $where === 'listing') {

            $message['dialog'] = $this->print_dialog_checkboxes($layout_id);

            if ($where === 'editor') {
                global $wpdd_gui_editor;
                $message['list'] = $wpdd_gui_editor->get_where_used_output($layout_id);
            }

        }

        $Data['message'] = $message;

        $send = wp_json_encode($Data);

        return $send;
    }

    public function get_ddl_listing_data()
    {
        // Clear any errors that may have been rendered that we don't have control of.
        if (ob_get_length()) {
            ob_clean();
        }

        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['ddl_listing_nonce'], 'ddl_listing_nonce')) {

            $data = $this->get_grouped_layouts($_POST['status'], $_POST);

            if (defined('JSON_UNESCAPED_UNICODE')) {
                $send = wp_json_encode(array('Data' => $data), JSON_UNESCAPED_UNICODE);
            } else {
                $send = wp_json_encode(array('Data' => $data));
            }
        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function get_grouped_layouts($status, $args = array())
    {
        $this->get_all->init($status);
        $this->layouts_query = $this->get_all->get_query();
        $this->layouts_list = $this->get_all->get_layouts();
        return $this->get_all->get_groups($args);
    }

    public function listing_scripts()
    {
        global $wpddlayout;

        //speed up ajax calls sensibly
        wp_deregister_script('heartbeat');
        wp_register_script('heartbeat', false);

        $localization_array = array(
            'res_path' => WPDDL_RES_RELPATH,
            'listing_lib_path' => WPDDL_GUI_RELPATH . "/listing/js/",
            'editor_lib_path' => WPDDL_GUI_RELPATH . "editor/js/",
            'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
            'ddl_listing_nonce' => wp_create_nonce('ddl_listing_nonce'),
            'ddl_listing_show_posts_nonce' => wp_create_nonce('ddl_listing_show_posts_nonce'),
            'ddl_listing_status' => isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish',
            'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
            'strings' => $this->get_listing_js_strings(),
            'is_listing_page' => true,
            'user_can_delete' => user_can_delete_layouts(),
            'user_can_assign' => user_can_assign_layouts(),
            'user_can_edit' => user_can_edit_layouts(),
            'user_can_create' => user_can_create_layouts(),
            'available_cell_types' => $wpddlayout->get_cell_types(),
            'toolset_cells_data' => WPDD_Utils::toolsetCellTypes(),
            'max_num_posts' => DDL_MAX_NUM_POSTS
        );

        $wpddlayout->enqueue_scripts(array('dd-listing-page-main', 'ddl-post-types'));
        $wpddlayout->localize_script('dd-listing-page-main', 'DDLayout_settings', array(
            'DDL_JS' => $localization_array,
            'DDL_OPN' => self::change_layout_dialog_options_name(),
            'items_per_page' => DDL_ITEMS_PER_PAGE
        ));
        $wpddlayout->enqueue_styles(array('views-pagination-style', 'dd-listing-page-style'));
    }

    public static function change_layout_dialog_options_name(){
        $dialog_option_names_array = apply_filters("ddl_change_dialog_options_names", array(
            'ARCHIVES_OPTION' => WPDD_layout_post_loop_cell_manager::POST_TYPES_LOOPS_NAME
        , 'POST_TYPES_OPTION' => WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME
        , 'OTHERS_OPTION' => WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION
        , 'INDIVIDUAL_POSTS_OPTION' => WPDD_Layouts_IndividualAssignmentManager::INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME
        , 'BULK_ASSIGN_POST_TYPES_OPTION' => WPDD_Layouts_PostTypesManager::POST_TYPES_APPLY_ALL_OPTION_NAME
        ) );

        return $dialog_option_names_array;
    }

    private function get_listing_js_strings()
    {
        return array(
            'is_a_parent_layout' => __("This layout has children. It can't be deleted.", 'ddl-layouts'),
            'is_a_parent_layout_and_cannot_be_changed' => __("This layout has children. You should assign one of its children to content and not this parent layout.", 'ddl-layouts'),
            'to_a_post_type' => __("This layout is assigned to a post type. It can't be deleted.", 'ddl-layouts'),
            'to_an_archive' => __("This layout is assigned to an archive. It can't be deleted.", 'ddl-layouts'),
            'to_archives' => __("This layout is assigned to %s archives. It can't be deleted.", 'ddl-layouts'),
            'to_post_types' => __('This layout is assigned to %s post types. It can\'t be deleted.', 'ddl-layouts'),
            'to_a_post_item' => __('This layout is assigned to a post. It can\'t be deleted.', 'ddl-layouts'),
            'to_posts_items' => __("This layout is assigned to %s posts. It can't be deleted.", 'ddl-layouts'),
            'no_more_pages' => __("This layout is already assigned to all pages.", 'ddl-layouts'),
            'no_more_posts' => __("This layout is already assigned to all posts items.", 'ddl-layouts'),
	    'no_more_pages_in_db' => __("No pages found.", 'ddl-layouts'),
            'no_more_posts_in_db' => __("No post items found.", 'ddl-layouts'),
            'user_no_caps' => __("You don't have permission to perform this action.", 'ddl-layouts'),
            'duplicate_dialog_title' => __("Toolset resources", 'ddl-layouts'),
            'duplicate_results_title' => __("Toolset duplicate resources summary", 'ddl-layouts'),
            'duplicate_result_message_all' => __("The duplicate that you created uses copies of Toolset elements. You can edit it freely. The original layout will not change when you edit the duplicate.", 'ddl-layouts'),
            'duplicate_result_message_some' => __("This duplicate layout uses some Toolset elements from the original layout. When you edit the layout, the original may change too, if you edit shared Toolset elements.", 'ddl-layouts'),
            'duplicate_anchor_text' => __("Show details of duplicate Toolset elements", 'ddl-layouts'),
            'duplicate_anchor_text_hide' => __("Hide details of duplicate Toolset elements", 'ddl-layouts'),
            'cancel' => __("Cancel", 'ddl-layouts'),
            'close' => __("Close", 'ddl-layouts'),
            'duplicate' => __("Duplicate", 'ddl-layouts'),
        );
    }

    private function set_args($args = array())
    {
        $defaults = array(
            'post_type' => WPDDL_LAYOUTS_POST_TYPE,
            'suppress_filters' => false,
            'posts_per_page' => DDL_ITEMS_PER_PAGE,
            'order' => 'ASC',
            'orderby' => 'title',
            'post_status' => isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish',
            'paged' => isset($_GET['paged']) ? $_GET['paged'] : 1
        );

        $this->args = wp_parse_args($args, $defaults);
    }

    private function set_mod_url($args = array())
    {
        $mod_url = array( // array of URL modifiers
            'orderby' => '',
            'order' => '',
            'search' => '',
            'items_per_page' => '',
            'paged' => '',
            'status' => ''
        );
        $this->mod_url = wp_parse_args($args, $mod_url);
    }

    private function found_posts()
    {
        return is_object($this->layouts_query) ? $this->layouts_query->found_posts : 0;
    }

    private function post_count()
    {
        return is_object($this->layouts_query) ? $this->layouts_query->post_count : 0;
    }

    private function get_layout_list()
    {
        return $this->layouts_list;
    }

    private function set_count()
    {
        global $wpdb;

        $this->count_published = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) from $wpdb->posts WHERE post_type = %s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));
        $this->count_trash = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) from $wpdb->posts WHERE post_type = %s AND post_status = 'trash'", WPDDL_LAYOUTS_POST_TYPE));
    }

    private function get_arg($arg)
    {
        return isset($this->args[$arg]) ? $this->args[$arg] : null;
    }

    private function get_count_published()
    {
        return $this->count_published;
    }

    private function get_count_trash()
    {
        return $this->count_trash;
    }

    private function get_count_what()
    {
        return $this->count_what;
    }

    private function set_count_what()
    {
        $this->count_what = $this->get_arg('post_status') == 'publish' ? 'trash' : 'publish';
    }


    private function display_list()
    {
        $status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';

        $data = $this->get_grouped_layouts($status);

        if (defined('JSON_UNESCAPED_UNICODE')) {
            $init_json = wp_json_encode(array('Data' => $data), JSON_UNESCAPED_UNICODE);
        } else {
            $init_json = wp_json_encode(array('Data' => $data));
        }

        $init_json_listing = base64_encode($init_json);

        include WPDDL_GUI_ABSPATH . 'templates/layouts_list_new.tpl.php';

        $this->load_js_templates();
    }

    private function load_js_templates()
    {
        WPDD_FileManager::include_files_from_dir(WPDDL_GUI_ABSPATH . "/listing/", "js/templates", $this);
    }

    public function set_layout_status_callback()
    {

        // Clear any errors that may have been rendered that we don't have control of.
        if (ob_get_length()) {
            ob_clean();
        }

        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-select-trash-nonce'], 'layout-select-trash-nonce')) {

            if( $_POST['status'] === 'trash' && user_can_delete_layouts() === false ){
                die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
            }

            $http_id = $_POST['layout_id'];
            $status = $_POST['status'];
            $current_page_status = isset($_POST['current_page_status']) ? $_POST['current_page_status'] : 'publish';

            if (is_array($http_id)) {
                $ids = $http_id;
            } else {
                $ids = array($http_id);
            }

            $message = array();

            foreach ($ids as $id) {
                $data = array(
                    'ID' => $id,
                    'post_status' => $status
                );

                $message[] = wp_update_post($data);
            }

            if( isset( $_POST['do_not_reload'] ) && $_POST['do_not_reload'] === 'yes' ){
                $send = wp_json_encode( array('Data' => array( 'message' => $message ) ) );
            } else {
                $send = $this->get_send($current_page_status, false, $http_id, $message, $_POST);
            }

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function delete_layout_record_callback()
    {

        // Clear any errors that may have been rendered that we don't have control of.
        if (ob_get_length()) {
            ob_clean();
        }

        global $wpddlayout;

        if( user_can_delete_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['layout-delete-layout-nonce'], 'layout-delete-layout-nonce')) {
            $layout_id = $_POST['layout_id'];
            $current_page_status = isset($_POST['current_page_status']) ? $_POST['current_page_status'] : 'trash';

            if (!is_array($layout_id)) {
                $layout_id = array($layout_id);
            }

            $message = array();

            foreach ($layout_id as $id) {
                $res = wp_delete_post($id, true);
                // if deleted clean from options
                if ($res !== false) {
                    $wpddlayout->post_types_manager->clean_layout_post_type_option($id);
                    $message[] = $res->ID;
                }

            }

            $send = $this->get_send($current_page_status, false, $layout_id, $message, $_POST);

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function get_all_layouts_posts()
    {
        $this->get_all->get_all_layouts_posts();
    }
}

class DDL_GroupedLayouts
{

    const NUMBER_OF_ITEMS = 5;

    private $loop_manager;
    private $post_types = array();
    private $to_single = array();
    private $not_assigned = array();
    private $to_loops = array();

    private $layouts = array();
    private $query;

    static $blacklist = array('post_parent',
                                'post_password',
                                'comment_count',
                                'comment_status',
                                'guid',
                                'menu_order',
                                'pinged',
                                'ping_status',
                                'post_author',
                                'post_content',
                                'post_content_filtered',
                                'post_date_gmt',
                                'post_excerpt',
                                'post_mime_type',
                                'post_modified',
                                'post_modified_gmt',
                                'to_ping');

    private static $instance;

    private function __construct()
    {
        global $wpddlayout;
        $this->loop_manager = $wpddlayout->layout_post_loop_cell_manager;
    }

    public function init( $status = 'publish' )
    {
        $args = array( "status" => $status,
            "order_by" => "date",
            "fields" => "all",
            "return_query" => true);

        $get_all = self::get_all_layouts_as_posts( $args );
        $this->query = $get_all->query;
        $this->set_layouts( $get_all->posts );
    }

    public function get_query()
    {
        return $this->query;
    }

    public function get_layouts()
    {
        return $this->layouts;
    }

    public function set_layouts( $layouts )
    {
        $this->layouts = $layouts;
    }

    public static function get_all_layouts_as_posts( $args = array( "status" => "publish",
            "order_by" => "date",
            "fields" => "all",
            "return_query" => false,
            "no_found_rows" => false,
            "update_post_term_cache" => true,
            "update_post_meta_cache" => true,
            "cache_results" => true,
            "order" => "DESC",
            "post_type" => WPDDL_LAYOUTS_POST_TYPE ) ) {


        $res = new stdClass();

        $args = wp_parse_args( $args, array( "status" => "publish",
            "order_by" => "date",
            "fields" => "all",
            "return_query" => false,
            "no_found_rows" => false,
            "update_post_term_cache" => true,
            "update_post_meta_cache" => true,
            "cache_results" => true,
            "order" => "DESC",
            "post_type" => WPDDL_LAYOUTS_POST_TYPE ) );

        $defaults = array(
            'post_type' => $args["post_type"],
            'suppress_filters' => false,
            'order' => $args["order"],
            'orderby' => $args["order_by"],
            'post_status' => $args["status"],
            'posts_per_page' => WPDDL_MAX_POSTS_OPTION_DEFAULT,
            'fields' => $args["fields"],
            'no_found_rows' => $args["no_found_rows"],
            // leave the terms alone we don't need them
            'update_post_term_cache' => $args["update_post_term_cache"],
            // leave the meta alone we don't need them
            'update_post_meta_cache' => $args["update_post_meta_cache"],
            // don't cache results
            'cache_results' => $args["cache_results"]
        );

        $res->query = new WP_Query($defaults);
        $res->posts = $res->query->posts;
        $res->query->posts = null;

        if ( $args['return_query'] ) {
            return $res;
        } else {
            return $res->posts;
        }

        return null;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DDL_GroupedLayouts();
        }

        return self::$instance;
    }

    public static function _filter_post($post, $black = false, $do_edit_link = true)
    {

        $blacklist = $black ? $black : array('post_parent',
                                            'post_password',
                                            'comment_count',
                                            'comment_status',
                                            'guid',
                                            'menu_order',
                                            'pinged',
                                            'ping_status',
                                            'post_author',
                                            'post_content',
                                            'post_content_filtered',
                                            'post_date_gmt',
                                            'post_excerpt',
                                            'post_mime_type',
                                            'post_modified',
                                            'post_modified_gmt',
                                            'to_ping');

        $post->post_name = urldecode($post->post_name);
        foreach ($blacklist as $remove) {
            unset($post->{$remove});
        }
        if( $do_edit_link ){
            $edit_link = get_edit_post_link($post->ID);
            if ($edit_link) {
                if( !$post->post_title || $post->post_title === '' ){
                    $post->post_title = sprintf( __('%sno title%s', 'ddl-layouts'), '&lpar;', '&rpar;' );
                }
                $post->post_title = '<a href="' . $edit_link . '">' . $post->post_title . '</a>';
            }
        }


        return $post;
    }

    private function _filter_layout_item($item)
    {
        foreach (self::$blacklist as $remove) {
            if( property_exists($item, $remove ) ){
                unset($item->{$remove});
            }
        }
        return $item;
    }


    function process_items_common_properties( $item, $layout, $args){

        $item = $this->_filter_layout_item($item);
        $item->show_posts = $this->set_number_of_items_for_posts($item, $args);
        $item->kind = 'Item';
        $item->post_name = urldecode($item->post_name);
        $item->id = $item->ID;
        $item->is_parent = $layout->has_child;
        $item->date_formatted = get_the_time(get_option('date_format'), $item->ID);
        $item->post_title = str_replace('\\\"', '\"', $item->post_title);
        $item->has_loop = property_exists($layout, 'has_loop') ? $layout->has_loop : false;
        $item->has_post_content_cell = property_exists($layout, 'has_post_content_cell') ? $layout->has_post_content_cell : false;

        if( WPDD_Utils::layout_has_one_of_type( json_encode( $layout ) ) ){
            $item->layout = $layout;
        }

        return $item;
    }

    function process_item_is_parent( $item, $layout ){

        if ($item->is_parent) {
            $item->children = self::get_children($layout, $this->layouts);
        }

        if (property_exists($layout, 'parent') && $layout->parent) {
            $parent = get_post(WPDD_Layouts::get_layout_parent($item->ID, $layout));
            $item->is_child = true;
            if (is_object($parent) && $parent->post_status == $item->post_status) {
                $item->parent = $parent->ID;
            }
        } else {
            $item->is_child = false;
        }

        return $item;
    }

    function process_item_post_types( $item, $types ){

        if ($types && !$item->is_parent) {
            $item->types = $types;
            $item->count = count($types);
            $this->post_types[] = (array)$item;
        }
        return $item;
    }

    function process_item_loops( $item, $loops ){

        if ($loops && !$item->is_parent) {
            $item->loops = $loops;
            $item->count = count($loops);
            $this->to_loops[] = (array)$item;
        }

        return $item;
    }

    function process_item_single_assignments( $item, $types, $loops, $args ){

        global $wpddlayout;

        $posts_ids = $this->get_posts_where_used($item, $types, $args);

        if (($posts_ids && count($posts_ids) > 0) && !$item->is_parent) {

            $yes = $this->show_in_single($types, $posts_ids, $args);

            $item->posts = $yes;

            // $item_posts_count = count( $item->posts );
            $total_count = $wpddlayout->get_where_used_count();

            if ($total_count > self::NUMBER_OF_ITEMS) {
                $item->show_more_button = true;
            }

            if (sizeof($item->posts) > 0) {
                $item->count = count( $item->posts );
                $this->to_single[] = (array)$item;
            }

        } elseif ($item->is_parent || (!$posts_ids && !$types && !$loops)) {
            $item->count = 0;
            $this->not_assigned[] = (array)$item;
        }

        return $item;
    }

    public function get_groups( $args = array() )
    {
        foreach ($this->layouts as $item) {

            $layout = WPDD_Layouts::get_layout_settings($item->ID, true);

            if ( $layout ) {

                if (property_exists($layout, 'has_child') === false) $layout->has_child = false;

                $item = $this->process_items_common_properties( $item, $layout, $args);

                $item = $this->process_item_is_parent( $item, $layout );

                $types = apply_filters( 'ddl-get_layout_post_types_object', $item->ID, true );

                $item = $this->process_item_post_types( $item, $types );

                $loops = $this->loop_manager->get_layout_loops_labels($item->ID);

                $item = $this->process_item_loops( $item, $loops );

                $item = $this->process_item_single_assignments( $item, $types, $loops, $args );

            }
        }

        $ret = $this->return_default_groups( $this->not_assigned, $this->to_single, $this->post_types, $this->to_loops );

        return apply_filters( 'ddl_get_layouts_listing_groups', $ret, $this );
    }


    public function build_groups_from_settings( $args = array('link'=>true) ){

        foreach( $this->layouts as $layout ){

            $item = $this->get_post_item_from_settings( $layout );

            if (property_exists($layout, 'has_child') === false) $layout->has_child = false;

            $item = $this->process_items_common_properties( $item, $layout, $args);

            $item = $this->process_item_is_parent( $item, $layout );

            $types = apply_filters( 'ddl-get_layout_post_types_object', $item->ID, false );

            if( is_array($types) ){
                $this->current = $item->ID;
                $types = array_map( array(&$this, 'get_archive_link'), $types);
            }

            $item = $this->process_item_post_types( $item, $types );

            $loops = $this->loop_manager->get_layout_loops($item->ID);

            if( is_array($loops) ){
                $loops = array_map( array(&$this, 'get_loop_display_object'), $loops );
            }

            $item = $this->process_item_loops( $item, $loops );

            $args['do_edit_link'] = false;
            $item = $this->process_item_single_assignments( $item, $types, $loops, $args );
        }

        return $this->return_default_groups_with_count( $this->not_assigned, $this->to_single, $this->post_types, $this->to_loops );
    }

    public function get_archive_link( $type ){
        $link = '';
        if( apply_filters('ddl-get_post_type_was_batched', $this->current, $type['post_type'])){
            $post = apply_filters( 'ddl-get_x_posts_of_type', $type['post_type'], $this->current, 1);
            if( isset($post[0]) && is_object($post[0]) ){
                $link = apply_filters( 'ddl-ddl_get_post_type_batched_preview_permalink', $type['post_type'], $post[0]->ID );
            }
        }
        $type['link'] = $link;
        return $type;
    }

    public function get_loop_display_object( $loop ){
        global $wpddlayout;
        return $wpddlayout->layout_post_loop_cell_manager->get_loop_display_object( $loop );
    }

    private function get_post_item_from_settings( $layout ){
        return (object) array(
            'ID' => $layout->id,
            'post_name' => $layout->slug,
            'post_title' => $layout->name,
            'post_date' => date("Y-m-d H:i:s"),
            'post_status' => 'publish',
            'post_type' => WPDDL_LAYOUTS_POST_TYPE
        );
    }

    private function return_default_groups($one, $two, $three, $four){

        return array(
            array(
                'id' => 1,
                'name' => __("Layouts not being used anywhere", 'ddl-layouts'),
                'kind' => 'Group',
                'items' => $one
            ),
            array(
                'id' => 2,
                'name' => __('Layouts being used to display single posts or pages', 'ddl-layouts'),
                'kind' => 'Group',
                'items' => $two
            ),
            array(
                'id' => 3,
                'name' => __('Layouts being used as templates for post types', 'ddl-layouts'),
                'kind' => 'Group',
                'items' => $three
            ),
            array(
                'id' => 4,
                'name' => __('Layouts being used to customize archives', 'ddl-layouts'),
                'kind' => 'Group',
                'items' => $four
            )
        );
    }

    public function return_default_groups_with_count( $one, $two, $three, $four ){
            $ret = $this->return_default_groups($one, $two, $three, $four);
            $ret['count_assignments'] = array_reduce($ret, array(&$this, 'count_children_items'), 0);
            $ret['count_children'] = array_reduce($ret, array(&$this, 'count_children'), 0);
            return $ret;
    }

    public function count_children_items(  $carry ,  $item ){
        $count = 0;
        if( isset($item['items']) && is_array($item['items']) ){
            foreach( $item['items'] as $current ){
                $count += $current['count'];
            }
            $carry += $count;
        }
        return $carry;
    }

    public function count_children( $carry ,  $item ){
        if( isset( $item['items'] ) && is_array( $item['items'] ) ){
            $carry += count( $item['items'] );
        }
        return $carry;
    }

    private function show_in_single($types, $posts_ids, $args = array() )
    {
        if (!$types) return $posts_ids;
        $post_types = is_array( $types ) ? array_map(array(&$this, 'map_layout_post_types_name'), $types) : array();
        $ret = array();
        foreach ($posts_ids as $post) {
            if (in_array($post->post_type, $post_types) === false) {
                if( isset( $args['link'] ) && $args['link'] === true ){
                    $post->link = get_permalink( $post->ID );
                }
                $ret[] = $post;
            }
        }
        return $ret;
    }

    private function get_post_ids($item, $types, $amount = self::NUMBER_OF_ITEMS)
    {

        global $wpddlayout;

        if ($types) {
            $post_types_to_query = $this->get_post_types_to_query($item->ID, $types);
            if (count($post_types_to_query) === 0) {
                return false;
            } else {
                $posts_ids = $wpddlayout->get_where_used($item->ID, $item->post_name, true, $amount, array('publish', 'draft', 'pending', 'future'), 'ids', $post_types_to_query, false);
            }
        } else {
            $posts_ids = $wpddlayout->get_where_used( $item->ID, $item->post_name, true, $amount, array( 'publish', 'draft', 'pending', 'private', 'future' ), 'ids', 'any', false );
        }

        return $posts_ids;
    }

    public function get_posts_where_used($layout, $post_types, $args = array())
    {

        $show_posts = isset($args['show_posts']) ? $args['show_posts'] : false;

        if ($show_posts && isset($show_posts[$layout->ID])) {
            $layout->show_posts = $show_posts[$layout->ID];
        }

        $posts_ids = $this->get_post_ids($layout, $post_types, (int)$layout->show_posts);

        if (!$posts_ids || count($posts_ids) === 0) return $posts_ids;

        return $this->set_layout_posts_and_return_them($posts_ids, $args);
    }

    private function set_number_of_items_for_posts($item, $args = array())
    {
        $ret = self::NUMBER_OF_ITEMS;

        $show_posts = isset($args['show_posts']) ? $args['show_posts'] : false;

        if ($show_posts && isset($show_posts[$item->ID])) {
            $ret = $show_posts[$item->ID];
        }
        return $ret;
    }

    public function get_all_layouts_posts()
    {

        if (ob_get_length()) {
            ob_clean();
        }

        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['nonce'], 'ddl_listing_show_posts_nonce')) {

            $data = json_decode(stripslashes($_POST['data']), true);

            $layout = (object)$data['layout'];

            $post_types = isset($data['post_types']) ? $data['post_types'] : array();

            $posts = $this->get_posts_where_used((object)$layout, $post_types);

            $send = wp_json_encode(array('Data' => array('posts' => $posts)));

        } else {
            $send = WPDD_Utils::ajax_nonce_fail( __METHOD__ );
        }
        die($send);
    }


    private function set_layout_posts_and_return_them($posts_ids, $args = array())
    {
        $posts = array();
        foreach ($posts_ids as $post_id) {
            $post = get_post($post_id);
            $do_edit = isset($args['do_edit_link']) && $args['do_edit_link'];
            $post = self::_filter_post( $post, self::$blacklist, $do_edit);
            if( isset( $args['link'] ) && $args['link'] === true ){
                $post->link = get_permalink( $post->ID );
            }
            $posts[] = $post;
        }
        return $posts;
    }

    private function get_batched_post_types_array($layout_id, $post_type_object)
    {
        if( is_array($post_type_object) === false ) return array();

        global $wpddlayout;

        $ret = array();

        $layout_batched_types = $wpddlayout->post_types_manager->get_layout_batched_post_types($layout_id);

        if (count($layout_batched_types) === 0) return $ret;

        $layout_batched_types = $layout_batched_types[0];

        foreach ($post_type_object as $post_type) {
            if (in_array($post_type['post_type'], $layout_batched_types)) {
                $ret[] = $post_type;
            }
        }

        return $ret;
    }

    private function get_post_types_to_query($layout_id, $layout_post_types)
    {
        global $wpddlayout;
        $all_types = array_map(array(&$this, 'map_wp_post_types_name'), $wpddlayout->post_types_manager->get_post_types_from_wp());
        $batched = $this->get_batched_post_types_array($layout_id, $layout_post_types);

        if (!$batched || count($batched) === 0) return $all_types;

        $batched = array_map(array(&$this, 'map_layout_post_types_name'), $batched);

        return array_diff($all_types, $batched);
    }

    function map_layout_post_types_name($m)
    {
        return $m['post_type'];
    }

    function map_wp_post_types_name($m)
    {
        return $m->name;
    }

    public static function get_children($layout, $layouts_list, $previous_slug = null)
    {
        $ret = array();

        if (isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {

            $layout_slug = $previous_slug === null ? $layout->slug : $previous_slug;

            foreach ($layouts_list as $post) {
                $child = null;
                /**
                 * if searched by settings child is child
                 */
                if( property_exists($post, 'ID') === false && property_exists($post, 'id') === true ){
                    $post_id = $post->id;
                    $child = $post;
                /**
                 * if searched by post fetch settings
                 */
                } else {
                    $post_id = $post->ID;
                    $child = WPDD_Layouts::get_layout_settings($post_id, true);
                }

                if ($child) {
                    if (property_exists($child, 'parent') && $child->parent == $layout_slug && $layout->id != $post_id) {
                        $ret[] = $post_id;
                    }
                }
            }
            return $ret;
        }
        return $ret;
    }

}