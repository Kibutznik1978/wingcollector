<?php

class WPDDL_Settings {

    private static $instance;
    const MAX_POSTS_OPTION_NAME = WPDDL_MAX_POSTS_OPTION_NAME;
    const MAX_POSTS_OPTION_DEFAULT = WPDDL_MAX_POSTS_OPTION_DEFAULT;
    private $parent_default;
    public static $max_posts_num_option = self::MAX_POSTS_OPTION_DEFAULT;

    public function __construct() {
        $this->parent_default = apply_filters('ddl-get-default-'.WPDDL_Options::PARENTS_OPTIONS, WPDDL_Options::PARENTS_OPTIONS );
        self::set_max_num_posts( self::get_option_max_num_posts() );
        add_action( 'wp_ajax_ddl_update_toolset_admin_bar_menu_status', array( $this, 'ddl_update_toolset_admin_bar_menu_status' ) );
        add_action( 'wp_ajax_ddl_set_max_posts_amount', array( __CLASS__, 'ddl_set_max_posts_amount' ) );
        add_action('wp_ajax_'.WPDDL_Options::PARENTS_OPTIONS, array(&$this, 'parent_default_ajax_callback'));
	
	    add_filter( 'toolset_filter_toolset_admin_bar_menu_insert', array( $this, 'extend_toolset_admin_bar_menu' ), 11, 3 );
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Settings();
        }

        return self::$instance;
    }

    public function init(){
        $this->init_gui();
    }

    public function get_default_parent(){
        return $this->parent_default;
    }

    /**
     * Layouts Settings page set up
     */
    function init_gui() {

        $settings_script_texts = array(
            'setting_saved' => __( 'Settings saved', 'ddl-layouts' ),
            'parent_default' => $this->parent_default,
            'parent_option_name' => WPDDL_Options::PARENTS_OPTIONS,
            'parent_settings_nonce' => wp_create_nonce( WPDDL_Options::PARENTS_OPTIONS.'_nonce', WPDDL_Options::PARENTS_OPTIONS.'nonce' )
        );

        if ( is_admin() && isset( $_GET['page'] ) && ($_GET['page'] === 'toolset-settings' || $_GET['page'] === 'dd_layouts_edit') ) {
            do_action( 'ddl-enqueue_styles', 'layouts-settings-admin-css' );
            do_action( 'ddl-enqueue_scripts', 'layouts-settings-admin-js' );
            do_action( 'ddl-localize_script', 'layouts-settings-admin-js', 'DDL_Settings_JS', $settings_script_texts );
        }
        
    }

    function default_parent_gui(){
        require_once WPDDL_GUI_ABSPATH . 'templates/layouts-parent-settings-gui.tpl.php';
    }

    function ddl_update_toolset_admin_bar_menu_status() {
        
        if ( ! current_user_can( 'manage_options' ) ) {
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        if (
                ! isset( $_POST["wpnonce"] ) || ! wp_verify_nonce( $_POST["wpnonce"], 'ddl_toolset_admin_bar_menu_nonce' )
        ) {
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        
        $status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( $_POST['status'] ) : 'true';
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_options['show_admin_bar_shortcut'] = ( $status == 'true' ) ? 'on' : 'off';
        update_option( 'toolset_options', $toolset_options );
        wp_send_json_success();
        
    }

    ////////////////////////////////////////////////////////////////////////////
    //
    // Layouts Settings Page - GUI Code
    //
    ////////////////////////////////////////////////////////////////////////////


    public function ddl_show_hidden_toolset_admin_bar_menu(  ) {
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_admin_bar_menu_show = ( isset( $toolset_options['show_admin_bar_shortcut'] ) && $toolset_options['show_admin_bar_shortcut'] == 'off' ) ? false : true;
        ob_start();
        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-admin_bar.tpl.php';
        echo ob_get_clean();
    }
    
    public function extend_toolset_admin_bar_menu( $menu_item_definitions, $context, $post_id ){
        if( !is_array( $menu_item_definitions ) ) {
            $menu_item_definitions = array();
        }

        $menu_item_definitions[] = array(
	    'title' => __( 'Layouts CSS Editor', 'wpv-views' ),
	    'menu_id' => 'toolset_layouts_edit_css',
	    'href' => admin_url().'admin.php?page=dd_layout_CSS'
	);

	return $menu_item_definitions;
    }

    function ddl_set_max_query_size(  ){
        self::$max_posts_num_option = self::get_option_max_num_posts();
        ob_start();

        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-wp_query.tpl.php';

        echo ob_get_clean();
    }

    public static function get_option_max_num_posts(){
            return get_option( self::MAX_POSTS_OPTION_NAME, self::MAX_POSTS_OPTION_DEFAULT );
    }

    public static function set_option_max_num_posts( $num ){
        return update_option( self::MAX_POSTS_OPTION_NAME, $num );
    }

    public static function get_max_posts_num( ){
        return self::$max_posts_num_option;
    }

    public static function set_max_num_posts( $num ){
        return self::$max_posts_num_option = $num;
    }

    public static function ddl_set_max_posts_amount( ){
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['ddl_max-posts-num_nonce'], 'ddl_max-posts-num_nonce' ) )
        {
            $update = false;
            $amount = isset( $_POST['amount_posts'] ) ? $_POST['amount_posts'] : self::$max_posts_num_option;

            if( $amount !==  self::$max_posts_num_option ){
                self::$max_posts_num_option = $amount;
                $update = self::set_option_max_num_posts( $amount );
            }


            if( $update )
            {
                $send = wp_json_encode( array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'amount' => $amount  ) )  );

            } else {
                $send = wp_json_encode( array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts'), 'amount' => $amount  ) ) );

            }
        }
        else
        {
            $send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
        }

        die($send);
    }

    /**
     * @deprecated
     */
    private function parents_options(){
        $default_parent = $this->parent_default;
        $parents = WPDD_Layouts::get_available_parents();?>
        <option value=""><?php _e("None", 'ddl-layouts'); ?></option>
        <?php
        for ( $i=0,$total_parents=count($parents); $i<$total_parents; $i++){
            $selected = '';
            if ( $parents[$i]->ID == $default_parent ){
                $selected = ' selected';
            }
            echo '<option value="'.$parents[$i]->ID.'"'.$selected.'>'.$parents[$i]->post_title.'</option>';
        }
    }

    public function parent_default_ajax_callback(){

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['parents_options_nonce'], 'parents_options_nonce' ) && isset( $_POST['action'] ) && $_POST['action'] === 'parents_options' )
        {

            if( isset( $_POST[WPDDL_Options::PARENTS_OPTIONS] ) ){

                $update = apply_filters('ddl-set-default-'.WPDDL_Options::PARENTS_OPTIONS, WPDDL_Options::PARENTS_OPTIONS, $_POST[WPDDL_Options::PARENTS_OPTIONS] );
            }

            if( $update )
            {
                $send =  array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'value' => $_POST[WPDDL_Options::PARENTS_OPTIONS]  ) );

            } else {
                $send =  array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts') ) );

            }
        }
        else
        {
            $send = array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') );
        }

        wp_send_json($send);
    }
}

add_action( 'init', array('WPDDL_Settings', 'getInstance') );