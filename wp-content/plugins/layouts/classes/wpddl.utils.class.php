<?php

define('Rows', 'Rows');
define('Cells', 'Cells');
define('Cell', 'Cell');

/**
 * Utils API init
 */
add_action('ddl-before_init_layouts_plugin', array('WPDD_Utils', 'init_layouts_utils'), 1 );

final class WPDD_Utils{

    public static $default_page_template;

    public static final function init_layouts_utils(){

        self::$default_page_template = apply_filters( 'ddl-wp-default-page-template', self::buildPageTemplateFileName() );

        /*
         * @param $bool, boolean default to false
         * @param $cell, object
         * @param $property, string
         */
        add_filter( 'ddl-is_cell_and_of_type', array(__CLASS__, 'isCellAndOfType' ), 99, 2 );

        add_filter( 'ddl-toolset_cell_types', array(__CLASS__, 'toolsetCellTypes' ), 99, 1 );
        /*
         * @param $layout, mixed, false or layout slug
         * @param $post_id, integer
         */
        add_filter( 'ddl-page_has_layout', array(__CLASS__, 'page_has_layout'), 99, 1 );

        add_filter( 'ddl-template_have_layout', array(__CLASS__, 'template_have_layout'), 99, 2 );

        add_filter( 'ddl-this_page_template_have_layout', array(__CLASS__, 'this_page_template_have_layout'), 99, 1);
        /*
         * @param $value, array default to null
         */
        add_filter( 'ddl-get_all_layouts_settings', array(__CLASS__, 'get_all_settings'), 99, 1);

        /*
         * @param $bool, boolean default to false
         */
        add_action( 'ddl-check-page_templates_have_layout', array(__CLASS__, 'page_templates_have_layout'), 10, 1);

        add_filter('ddl_get_page_template', array(__CLASS__, 'get_page_template'), 10, 1);

        add_filter('ddl-layout_is_parent', array(__CLASS__, 'layout_is_parent'), 10, 1 );

        add_filter('ddl-get_current_integration_template_path', array(__CLASS__, 'get_current_integration_template_path'), 10, 1);

        add_filter('assign_layout_to_post_object', array(__CLASS__, 'clear_cache'), 999, 5 );

        add_filter('remove_layout_assignment_to_post_object', array(__CLASS__, 'clear_cache'), 999, 5 );


        add_filter( 'ddl-is_layout_assigned', array(__CLASS__, 'is_layout_assigned'), 10, 2);

        add_filter( 'ddl-get_post_type_items_assigned_count', array(__CLASS__, 'get_post_type_items_assigned_count'), 10, 1 );
    }

    public static function get_post_type_items_assigned_count( $post_type ){
        global $wpdb;
        $query = $wpdb->prepare("SELECT COUNT(wposts.ID) FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '%s' AND wposts.post_type = '%s' ORDER BY wpostmeta.meta_value DESC", WPDDL_LAYOUTS_META_KEY, $post_type );
        return $wpdb->get_var( $query );
    }

    public static final function clear_cache( $ret, $post_id, $layout_slug, $template = null, $meta = '' ){

        if( $ret === false ) return $ret;

        clean_post_cache( $post_id );

        return $ret;
    }

    public static function content_template_cell_has_body_tag( $cells ){

        if( !is_array($cells) || count($cells) === 0 ) return '';

        $ret = '';

        foreach( $cells as $cell ){
            if( method_exists($cell, 'check_if_cell_renders_post_content') && $cell->check_if_cell_renders_post_content( ) ){
                $ret = 'cell-content-template';
                break;
            } else {
                $ret = '';
            }

        }

        return $ret;
    }

    public static function layout_assigned_count( $layout_id ){
        global $wpdb;
        $layout_name = WPDD_Layouts_Cache_Singleton::get_name_by_id( $layout_id );
        $count =  $wpdb->get_var( $wpdb->prepare("SELECT COUNT(meta_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", WPDDL_LAYOUTS_META_KEY, $layout_name) );
        return $count && $count > 0;
    }
    
    public static function is_layout_assigned( $bool, $layout_id ){
        
        if( !$layout_id ) return $bool;
        
        $archives = apply_filters('ddl-get_layout_loops', $layout_id );
        $single = self::layout_assigned_count( $layout_id );
        $types = apply_filters( 'ddl-get_layout_post_types', $layout_id);

        return $single || count( $archives ) > 0 || count($types) > 0;
    }

    public static function visual_editor_cell_has_wpvbody_tag( $cells ){
        if( !is_array($cells) || count($cells) === 0 ) return '';

        $ret = '';

        foreach( $cells as $cell ){
            $content = $cell->get_content();

            if( !$content ) {
                $ret = '';
            } else {
                $content = (object) $content;
                if( self::content_content_has_views_tag( $content ) ){
                    $ret = 'cell-content-template';
                    break;
                } else {
                    $ret = '';
                }
            }
        }

        return $ret;

    }

    public static function content_content_has_views_tag( $content ){

        if(  property_exists(  $content, 'content' ) === false ) return false;

        $checks = apply_filters('ddl-do-not-apply-overlay-for-post-editor', array('wpv-post-body') );

        $bool = false;

        foreach( $checks as $check ){
            if( strpos(  $content->content, $check ) !== false ){
                $bool = true;
                break;
            }
        }

        return apply_filters( 'ddl-show_post_edit_page_editor_overlay', $bool, __CLASS__ );
    }
    
    

    public static function get_post_property_from_ID( $id, $property = 'post_name' )
    {
        if( is_nan($id) || !$id ) return null;

        $post = get_post($id);

        if( is_object($post) === false ) return null;

        if( get_class( $post ) !== 'WP_Post' ) return null;

        return $post->{$property};
    }

    public static function string_contanins_strings( $string = '', $strings = array() ){
        
        if( $string === '' ) return false;

        if( count( $strings ) === 0 ) return false;

        $bool = false;

        foreach( $strings as $check ){
            if( strpos($string, $check) !== false ){
                $bool = true;
                break;
            }
        }

        return $bool;
    }

    public static function get_layout_id_from_post_name($layout_name)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s", WPDDL_LAYOUTS_POST_TYPE, $layout_name));
    }

    public static function invoke_child_method($class, $method = 'get_instance'){

        $sub = self::introspect( $class );

        if( $sub ){
            $r = new ReflectionClass( $sub );
            $instance = $r->getMethod($method)->invoke(null);
            return $instance;
        }

        return null;
    }

    public static function introspect($class)
    {
        $sub = self::getSubclassesOf($class);
        if (count($sub) > 0) {
            return $sub[0];
        } else {
            return null;
        }
        return null;
    }

    public static function getSubclassesOf($parent) {
        $result = array();
        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            if ( is_subclass_of($class, $parent) && $reflection->isAbstract() === false )
                $result[] = $class;
        }
        return $result;
    }

    public static function get_current_integration_template_path( $tpl ){

        $router = self::invoke_child_method( 'WPDDL_Integration_Theme_Template_Router_Abstract', 'get_instance');

        if( $router ){
            $path = $router->locate_template( array( $tpl ), false, false );
        } else {
            $path = locate_template( array( $tpl ), false, false );
        }

        return is_file( $path ) ? $path :  null ;
    }

    public static function getPageDefaultTemplate(){
        return self::$default_page_template;
    }

    public static function buildPageTemplateFileName(){
        $files = wp_get_theme()->get_files( 'php', 1, true );

        if( array_key_exists( 'page.php', $files ) ){
            return 'page.php';
        }

        return 'index.php';
    }

    public static final function isCellAndOfType( $cell = null, $type = 'menu-cell' ){

        return is_object( $cell ) && $cell instanceof WPDD_layout_cell && $cell->get_cell_type() === $type;
    }

    public static final function toolsetCellTypes( $cell_types = array() ) {

        $cell_types =  apply_filters('ddl-toolset-types', array(
            "cell-content-template" => array( "type" => "view-template", "property" => "ddl_view_template_id", "label" => "Content template"),
            "post-loop-views-cell" => array("type" => "view", "property" => "ddl_layout_view_id", "label" => "Archive view"),
            "views-content-grid-cell" => array("type" => "view", "property" => "ddl_layout_view_id", "label" => "View"),
            "cred-cell" => array("type" => "cred-form", "property" => "ddl_layout_cred_id", "label" => "CRED Post Form"),
            "cred-user-cell" => array("type" => "cred-user-form", "property" => "ddl_layout_cred_user_id", "label" => "CRED User form")
        ) );

        return $cell_types;
    }

    public static final function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        return array_reverse($arr, true);
    }

    public static function get_property_from_cell_type( $type, $property ){
        $infos = self::toolsetCellTypes();

        if( !isset($infos[$type]) ) return null;

        if( !isset( $infos[$type][$property] ) ) return null;

        return $infos[$type][$property];
    }

    public static final function assign_layout_to_post_object( $post_id, $layout_slug, $template = null, $meta = '' ){
        $ret = update_post_meta($post_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta);
        if( $ret && $template !== null ){
            update_post_meta($post_id, '_wp_page_template', $template);
        }
        return apply_filters('assign_layout_to_post_object', $ret, $post_id, $layout_slug, $template, $meta);
    }

    public static final function remove_layout_assignment_to_post_object( $post_id, $meta = '', $and_template = true ){
        $ret = delete_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, $meta );
        if( $ret && $and_template ){
            delete_post_meta($post_id, '_wp_page_template');
        }
        return apply_filters('remove_layout_assignment_to_post_object', $ret, $post_id, $meta, $and_template, $meta);
    }

    public static final function get_all_settings( $ret = null ){
        global $wpdb;

        $query = $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s", WPDDL_LAYOUTS_SETTINGS);

        return $wpdb->get_col( $query );
    }

    public static final function get_all_layouts_json_by_status( $status = 'publish' )
    {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare("SELECT wpostmeta.meta_value
                                              FROM $wpdb->postmeta wpostmeta, $wpdb->posts wposts
                                              WHERE wpostmeta.post_id = wposts.ID
                                              AND wpostmeta.meta_key = %s
                                              AND wposts.post_type = %s
                                              AND wposts.post_status = %s
                                              ORDER BY wpostmeta.meta_value ASC", WPDDL_LAYOUTS_SETTINGS, WPDDL_LAYOUTS_POST_TYPE, $status) );
    }

    public static final function get_all_published_settings_as_array(){
            return array_map('json_decode', self::get_all_layouts_json_by_status() );
    }

    public static function get_page_template( $id ){
        return get_post_meta($id, '_wp_page_template', true);
    }

    public static final function layout_has_one_of_type( $layout_json, $additional_types = array(), $only_extra = false ){

        $types = $only_extra ? array() : array_keys( self::toolsetCellTypes() );
        $builder = new WPDD_json2layout();
        $layout = $builder->json_decode( $layout_json );
        $bool = false;

        $types = wp_parse_args( $additional_types , $types );

        foreach( $types as $type ){
            if( $layout->has_cell_of_type($type, true) ){
                $bool = true;
                break;
            }
        }
        return $bool;
    }

    public static function layout_is_parent( $layout_id ){
        $layout = WPDD_Layouts::get_layout_settings($layout_id, true);
        return is_object($layout) && property_exists($layout, 'has_child') && $layout->has_child === true;
    }

    public static function at_least_one_layout_exists(){
        $args = array( "status" => array('publish', 'trash', 'draft', 'private'),
            "order_by" => "date",
            "fields" => "ids",
            "return_query" => false,
            "no_found_rows" => false,
            "update_post_term_cache" => false,
            "update_post_meta_cache" => false,
            "cache_results" => false,
            "order" => "DESC",
            "post_type" => WPDDL_LAYOUTS_POST_TYPE );

        return count( DDL_GroupedLayouts::get_all_layouts_as_posts( $args ) ) > 0;
    }

    public static final function page_has_layout( $post_id )
    {
        $meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

        if( $meta === '' ) {
            $ret = false;
        }
        elseif( $meta == '0' ){
            $ret = false;
        }
        else{
            $ret = $meta;
        }
        return $ret;
    }

    public static function page_templates_have_layout( $ret = null ){
        $bool = false;
        if( !function_exists('get_page_templates') ){
            include_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        $tpls = apply_filters( 'ddl-theme_page_templates', get_page_templates() );

        foreach( $tpls as $tpl ){
            $check = WPDD_Utils::template_have_layout( $tpl );
            if( $check ){
                $bool = true;
                break;
            }
        }

        return apply_filters( 'ddl-page_templates_have_layout', $bool, $tpls );
    }

    public static function this_page_template_have_layout( $post_id ){
        $current_template = get_post_meta( $post_id, '_wp_page_template', true );
        return apply_filters( 'ddl-current_page_templates_have_layout', WPDD_Utils::template_have_layout( $current_template ), $post_id );
    }

    public static function templates_have_layout( $templates )
    {

        $layout_templates = array();
        $file_data = false;
        foreach ($templates as $file => $name) {

            if (!in_array($file, $layout_templates)) {
                if( file_exists( get_template_directory() . '/' . $file ) ){
                    $file_data = file_get_contents(get_template_directory() . '/' . $file);
                }

                if ( self::is_child_theme() ) {
                    // try child theme.
                    if( file_exists( get_stylesheet_directory() . '/' . $file ) ){
                        $file_data = file_get_contents(get_stylesheet_directory() . '/' . $file);
                    }
                }
                if ($file_data !== false) {
                    if (strpos($file_data, 'the_ddlayout') !== false) {
                        $layout_templates[] = $file;
                    }
                }
            }
        }

        return apply_filters('ddl_templates_have_layout', $layout_templates, $templates);
    }

    public static function is_child_theme(){
        return get_stylesheet_directory() !== get_template_directory();
    }

    public static final function template_have_layout( $file, $dir = '' )
    {

        if( $file === null ){
            return false;
        }

        $bool = false;
        $file_data = false;

        $directory = $dir ? $dir : get_template_directory();

        $file_abs = $directory . '/' . $file;

        if ( file_exists( $file_abs ) ) {
            $file_data = @file_get_contents( $file_abs );

        } else {
            if( file_exists( get_stylesheet_directory() . '/' . $file )  ){
                $file_data = @file_get_contents(get_stylesheet_directory() . '/' . $file);
            }
        }

        if ($file_data !== false) {
            if (strpos($file_data, 'the_ddlayout') !== false) {
                $bool = true;
            }
        }

        return apply_filters('ddl_template_have_layout', $bool, $file);
    }

    public static final function page_template_has_layout( $post_id )
    {
        $template = get_post_meta($post_id, '_wp_page_template', true);
        return self::template_have_layout( $template );
    }

    public static function get_single_template( $post_type )
    {
        $templates = array();

        if( $post_type === 'page' )
        {
            /** Thanks to http://wordpress.stackexchange.com/questions/83180/get-page-templates
             **  get_page_templates function is not defined in FE so we need to load it in order
             **  for this one to work
             **/
            if( !function_exists('get_page_templates') ) include_once ABSPATH . 'wp-admin/includes/theme.php';
            $templates[$post_type] = "{$post_type}.php";
            $templates += apply_filters( 'ddl-theme_page_templates', get_page_templates() );
        }
        else if( $post_type === 'post' )
        {
            $templates['single'] = "single.php";
        }
        else{
            $templates["single-{$post_type}"] = "single-{$post_type}.php";
            $templates['single'] = "single.php";
        }

        $templates['index'] = 'index.php';

        return apply_filters('ddl-get_single_templates', $templates, $post_type );
    }

    public static function post_type_template_have_layout( $post_type ){

        $bool = false;
        $tpls = self::get_single_template( $post_type );

        foreach( $tpls as $tpl ){
            $check = self::template_have_layout( $tpl );
            if( $check ){
                $bool = true;
                break;
            }
        }

        return apply_filters( 'ddl_check_layout_template_page_exists', $bool, $post_type );
    }

    public static function ajax_nonce_fail( $method ){
        return wp_json_encode( array('Data' => array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', $method ), 'ddl-layouts') ) ) );
    }

    public static function ajax_caps_fail( $method ){
        return wp_json_encode( array( 'Data' => array( 'error' =>  __( sprintf('I am sorry but you don\'t have the necessary privileges to perform this action. %s', $method ), 'ddl-layouts') ) ) );
    }

    public static function user_not_admin(){
        return !current_user_can( DDL_CREATE );
    }

    public static function get_image_sizes( $size = '' ) {

        global $_wp_additional_image_sizes;

        $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach( $get_intermediate_image_sizes as $_size ) {

            if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

                $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
                $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
                $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

            } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

                $sizes[ $_size ] = array(
                    'width' => $_wp_additional_image_sizes[ $_size ]['width'],
                    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                    'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
                );

            }

        }

        // Get only 1 size if found
        if ( $size ) {

            if( isset( $sizes[ $size ] ) ) {
                return $sizes[ $size ];
            } else {
                return false;
            }

        }

        return $sizes;
    }

    public static function create_cell($name, $divider = 1, $cell_type = 'spacer', $options = array() )
    {
        // create most complex id possible
        $id = (string)uniqid('s', true);
        // het only the latest numeric only part
        $id = explode('.', $id);
        $id = "s" . $id[1];
        // keep only 5 chars to help base64_encode slowness
        $id = substr($id, 0, 5);
        // build a spacer and return it

        return (object) wp_parse_args( $options, array(
            'name' => $name,
            'cell_type' => $cell_type,
            'row_divider' => $divider,
            'content' => '',
            'cssClass' => '',
            'cssId' => 'span1',
            'tag' => 'div',
            'width' => 1,
            'additionalCssClasses' => '',
            'editorVisualTemplateID' => '',
            'id' => $id,
            'kind' => 'Cell'
        ) );
    }


    public static function create_cells($amount, $divider = 1, $cell_type = 'spacer')
    {
        $spacers = array();
        for ($i = 0; $i < $amount; $i++) {
            $spacers[] = self::create_cell($i + 1, $divider, $cell_type);
        }
        return $spacers;
    }

    public static function is_post_published( $id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE ID = '%s' AND post_status = 'publish'", $id) ) > 0;
    }

    public static function where( $array, $property, $value ){
        return array_filter($array, array( new Toolset_ArrayUtils($property, $value ), 'filter_array'));
    }

    public static final function property_exists( $object, $property){
        return is_object( $object ) ? property_exists($object, $property) : false;
    }

    public static final function str_replace_once($str_pattern, $str_replacement, $string){

        if (strpos($string, $str_pattern) !== false){
            $occurrence = strpos($string, $str_pattern);
            return substr_replace($string, $str_replacement, $occurrence, strlen($str_pattern));
        }

        return $string;
    }
}


class WPDDL_LayoutsCleaner
{
    private $layout_id;
    private $layout;
    private $cell_type;
    private $to_remove;
    private $removed;
    private $remapped = false;

    public function __construct($layout_id)
    {
        $this->remapped = false;
        $this->removed = array();
        $this->layout_id = $layout_id;
        $this->layout = WPDD_Layouts::get_layout_settings($this->layout_id, true);
    }

    public function remove_cells_of_type_by_property($cell_type, $property, $callable = array( 'WPDD_Utils', "is_post_published" ) )
    {
        $this->remapped = false;
        $this->cell_type = $cell_type;
        $this->property = $property;
        $rows = $this->get_rows();
        $rows = $this->remap_rows($rows, $callable);

        if( null !== $rows ){
            $this->layout->Rows = $rows;
            WPDD_Layouts::save_layout_settings( $this->layout_id, $this->layout );
        }

        return $this->removed;
    }


    function remove_unwanted($row, $remove)
    {
        $this->to_remove = $remove;

        if (in_array($remove, $row->Cells)) {

            $width = $remove->width;
            $divider = $remove->row_divider;
            $index = array_search($remove, $row->Cells);
            $spacers = WPDD_Utils::create_cells($width, $divider);
            array_splice($row->Cells, $index, 1, $spacers);

        }

        return $row;
    }


    public function remap_rows( $rows, $callable = array( 'WPDD_Utils', "is_post_published" ) )
    {
        foreach ($rows as $key => $row) {
            //$filtered = array_filter($row->Cells, array(&$this, 'filter_orphaned_cells_of_type'));
            if( !is_object($row) || property_exists($row, 'Cells') === false ){
                return null;
            }
            $filtered = $this->filtered_cells_recurse( $row->Cells, $callable );
            if (empty($filtered) === false) {
                foreach ($filtered as $ret) {
                    $this->remapped = true;
                    $this->removed[] = $ret->name;
                    $rows[$key] = $this->remove_unwanted($row, $ret);
                }
            }
        }

        if ($this->remapped === true) {
            return $rows;
        }
        return null;
    }

    function filtered_cells_recurse( $cells, $callable = array( 'WPDD_Utils', "is_post_published" ) ){
            $array = array();
            foreach( $cells as $key => $cell ){
                if( is_object($cell) && $cell->kind === 'Container' ){
                    $container_rows = $this->remap_rows( $cell->Rows );
                    if( null !== $container_rows ){
                        $cell->Rows = $container_rows;
                    }
                } else if(
                    is_object($cell) &&
                    property_exists($cell, 'cell_type') &&
                    $cell->cell_type === $this->cell_type &&
                    $cell->content &&
                    property_exists($cell->content, $this->property) &&
                    $cell->content->{$this->property} &&
                    call_user_func( $callable, $cell->content->{$this->property} ) === false
                ){
                    $array[] = $cell;
                }
            }

            return $array;
    }

    private function get_rows()
    {
        if( $this->layout && $this->layout->Rows ){
            return $this->layout->Rows;
        } else {
            return array();
        }
    }

    function filter_cells_of_type($cell, $callable = array( 'WPDD_Utils', "is_post_published" ) )
    {
        if (is_object($cell) && property_exists($cell, 'cell_type') && $cell->cell_type === $this->cell_type && $cell->content && $cell->content->{$this->property}) {
            return call_user_func( $callable, $cell->content->{$this->property} ) === false;
        }
    }
}

class WPDDL_RemapLayouts{
    protected $layout;
    protected $poperty;
    protected $new_value;
    protected $cell_id;
    protected $old_value;
    protected $type;
    protected $remapped = false;
    protected $results = array();
    protected $old_name;
    protected $new_name;

    public function __construct( $args = array() ){

            $this->layout = $args['layout'];
            $this->property = $args['property'];
            $this->cell_id = $args['cell_id'];
            $this->new_value = $args['new_value'];
            $this->old_value = $args['old_value'];
            $this->type = $args['cell_type'];
            $this->old_name = $args['old_name'];
            $this->new_name = $args['new_name'];
    }

    function get_layout(){
        return $this->layout;
    }

    function get_process_results(){
        return $this->results;
    }

    private function get_rows()
    {
        if( $this->layout && $this->layout[Rows] ){
            return $this->layout[Rows];
        } else {
            return array();
        }
    }

    public function process_layouts_properties( )
    {
        $this->remapped = false;
        $rows = $this->get_rows();
        $rows = $this->remap_rows($rows);

        if( null !== $rows ){
            $this->layout[Rows] = $rows;
        }

        return $this->layout;
    }

    private function remap_rows( $rows ){
        foreach ($rows as $key => $row) {

            if( !is_array($row) || isset( $row['Cells'] ) === false ){
                return null;
            }
            $filtered = $this->filtered_cells_recurse( $row[Cells] );
            if (empty($filtered) === false) {
                foreach ($filtered as $ret) {
                    $this->remapped = true;
                    $rows[$key] = $this->replace_cell($row, $ret);
                }
            }
        }

        if ($this->remapped === true) {
            return $rows;
        }
        return null;
    }

    private function filtered_cells_recurse( $cells ){
        $array = array();
        foreach( $cells as $key => $cell ){
            if( is_array( $cell ) && $cell['kind'] === 'Container' ){
                $container_rows = $this->remap_rows( $cell[Rows] );
                if( null !== $container_rows ){
                    $cell[Rows] = $container_rows;
                }
            } else if(
                is_array($cell) &&
                isset( $cell['cell_type'] ) &&
                $cell['cell_type'] === $this->type &&
                isset( $cell['content'] ) &&
                isset( $cell['content'][$this->property] ) &&
                $cell['content'][$this->property] == $this->old_value
            ){
                $cell['content'][$this->property] = $this->new_value;
                $array[] = array('cell' => $cell, 'key' => $key, 'new_name' => $this->new_name);
                $this->results[] = (object) array(
                    'old_value' => $this->old_value,
                    'new_value' => $this->new_value,
                    'property' => $this->property,
                    'cell_type' => $this->type,
                    'id' => $cell['id']
                );
            }
        }

        return $array;
    }

    function replace_cell($row, $cell_data)
    {
        $index = $cell_data['key'];
        $cell = $cell_data['cell'];
        $cell['name'] = $cell_data['new_name'];
        $row[Cells][$index] = $cell;
        return $row;
    }

}

class WPDDL_Layouts_WPML{

    private static $instance = null;
    static $languages = null;
    static $current_language = 'en';
    static $default_language = 'en';

    private function __construct(){

        self::$current_language = apply_filters( 'wpml_current_language', NULL );
        self::$default_language = apply_filters('wpml_default_language', NULL );

        add_filter('assign_layout_to_post_object', array(&$this, 'handle_save_update_assignment'), 99, 5 );

        add_filter('remove_layout_assignment_to_post_object', array(&$this, 'handle_remove_assignment'), 99, 4 );

        add_action('ddl-add-wpml-custom-switcher', array(&$this, 'print_wpml_custom_switcher') );

        add_action('ddl-wpml-switch-language', array(&$this, 'ddl_wpml_switch_language'), 10, 1 );

        add_action( 'ddl-wpml-switcher-scripts', array(&$this, 'enqueue_language_switcher_script') );

        add_action('admin_init', array(&$this, 'get_active_languages') );
    }

    /**
     * Make sure did_action('wp') returns true even if the action was not added yet or ever
     * source: https://core.trac.wordpress.org/browser/tags/4.4.2/src/wp-includes/plugin.php#L485
     * @return bool
     * @deprecated
     */
    private function cheat_wpml(){
        global $wp_actions;

        if( !isset( $wp_actions['wp'] ) ){
            $wp_actions['wp'] = 1;
            return true;
        }

        return false;
    }

    function get_active_languages(){
        self::$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=name&order=asc&skip_missing=0' );
        return self::$languages;
    }

    public function ddl_wpml_switch_language( $lang ){
        self::$current_language = isset( $lang ) && $lang ? $lang :self::$default_language;
        do_action( 'wpml_switch_language', self::$current_language );
    }

    public function enqueue_language_switcher_script(){
        add_action('admin_print_scripts', array(&$this, 'enqueue_wpml_selector_script'));
    }

    function enqueue_wpml_selector_script(){

        if( null === self::wpml_languages() ) return;

        global $wpddlayout;

        $wpddlayout->enqueue_scripts('ddl-wpml-switcher');
        $wpddlayout->localize_script('ddl-wpml-switcher', 'DDLayout_LangSwitch_Settings', apply_filters( 'ddl-wpml-localize-switcher', array(
            'default_language' => self::$default_language,
	    'current_language' => self::$current_language,
        ) ) );
    }

    public function print_wpml_custom_switcher(){
        $languages = self::wpml_languages();
        if( null === $languages ) return;

        ob_start();
        include_once WPDDL_GUI_ABSPATH . 'templates/layout-language-switcher.tpl.php';
        echo ob_get_clean();
    }

    public static function wpml_languages(){

        if( count(self::$languages) === 0 ) return null;

        return self::$languages;
    }

    public function handle_save_update_assignment(  $ret, $post_id, $layout_slug, $template, $meta ){
        if( $ret === false ) return $ret;

        $post_type = get_post_type( $post_id );
        $is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
        if( $is_translated_post_type === false ){
            return $ret;
        }

        $translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

        if( !$translations ){
            return $ret;
        }

        foreach( $translations as $translation){
            if( $translation->element_id !== $post_id ){
                $up = update_post_meta($translation->element_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta);
                if( $up && $template !== null ){
                    update_post_meta($translation->element_id, '_wp_page_template', $template);
                }
            }
        }

        return $ret;
    }

    public function handle_remove_assignment( $ret, $post_id, $meta, $and_template ){
        if( $ret === false ) return $ret;

        $post_type = get_post_type( $post_id );
        $is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
        if( $is_translated_post_type === false ){
            return $ret;
        }
        $translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

        if( !$translations ){
            return $ret;
        }

        foreach( $translations as $translation){

            if( $translation->element_id !== $post_id ){
                $up = delete_post_meta( $translation->element_id, WPDDL_LAYOUTS_META_KEY, $meta );
                if( $up && $and_template ){
                    delete_post_meta($translation->element_id, '_wp_page_template');
                }
            }
        }

        return $ret;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Layouts_WPML();
        }

        return self::$instance;
    }
}
//add_action( 'admin_init', array('WPDDL_Layouts_WPML', 'getInstance') );

/**
 * Class WPDDL_Framework
 *
 * Framework API Basic elements
 */
final class WPDDL_Framework{
    static function get_container_class($mode){
        return apply_filters('ddl-get_container_class', 'container', $mode);
    }

    static function get_container_fluid_class($mode){
        return apply_filters('ddl-get_container_fluid_class', 'container-fluid', $mode);
    }

    static function get_row_class($mode){
        return apply_filters('ddl-get_row_class', 'row', $mode);
    }

    static function get_offset_prefix(){
        return apply_filters('ddl-get_offset_prefix', 'offset-');
    }

    static function get_image_responsive_class(){
        return apply_filters('ddl-get_image_responsive_class', 'img-responsive');
    }

    static function get_column_prefix(){
        return apply_filters('ddl-get-column-prefix', 'col-sm-');
    }

    static function get_additional_column_class(){
        return apply_filters( 'ddl-get_additional_column_class', '' );
    }

    static function get_thumbnail_class(){
        return apply_filters('ddl-get_thumbnail_class', 'thumbnail');
    }

    static function framework_supports_responsive_images(){
        return apply_filters( 'ddl-framework_supports_responsive_images', true );
    }
}