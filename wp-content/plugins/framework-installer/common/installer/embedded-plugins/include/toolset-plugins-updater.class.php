<?php
class ToolsetPluginsFactory
{
    const SETTINGS_KEY = "toolset_updater_settings";
    const PLUGINS_PACKAGE       = 'Toolset Embedded';
    const PLUGINS_REPOSITORIES  = 'toolset';
    const META_KEY = '_toolset_edit_last';

    protected $have_common_items = false;
    private $instances = array();
    private $settings;
    private $theme;
    private $current_version;
    private $update_version;
    private $data_root;

    private static $TOOLSET_TYPES = array(
        "wp-types-group",
        "dd_layouts",
        "view-template",
        "view",
        "cred-form",
        "wp-types-user-group"
    );

    public static $PLUGIN_ACCESS = array(
        /**
         * Types
         */
        "wp-types-group" => array(
            "label" => "Types",
            "dir" => "types"
        ),
        "wp-types-user-group" => array(
            "label" => "Types",
            "dir" => "types"
        ),
        "wpcf-custom-taxonomies" => array(
            "label" => "Types",
            "dir" => "types"
        ),
        "wpcf-custom-types" => array(
            "label" => "Types",
            "dir" => "types"
        ),
        /**
         * Layouts
         */
        "dd_layouts" => array(
            "label" => "Layouts",
            "dir" => "layouts"
        ),
        /**
         * Views
         */
        "view-template" => array(
            "label" => "Views",
            "dir" => "views"
        ),
        "view" => array(
            "label" => "Views",
            "dir" => "views"
        ),
        /**
         * CRED
         */
        "cred-form" => array(
            "label" => "CRED",
            "dir" => "cred"
        )
    );

    public static $ADMIN_PAGES = array(
        'views',
        'dd_layouts',
        'wpcf-cpt',
        'CRED_Forms'
    );

    function __construct()
    {
        $this->settings = get_option(self::SETTINGS_KEY);
        $this->data_root = get_stylesheet_directory() . DIRECTORY_SEPARATOR . "toolset_import";
        $this->theme = wp_get_theme( )->Name;
        $this->current_version = wp_get_theme()->get( 'Version' );

        // if update directory does not exist do nothing
        if (is_dir($this->data_root) === false) {
            return;
        }

        add_filter('posts_clauses_request', array(&$this, 'posts_clauses_request_filter'), 10, 2);
        add_filter('updater_get_new_items', array(&$this, 'updater_get_new_items_filter'), 10, 2);

        $this->factory($this->get_items());

        remove_filter('posts_clauses_request', array(&$this, 'posts_clauses_request_filter'), 10, 2);

        $this->have_common_items = $this->plugins_have_common_items();

        add_action('wp_ajax_toolset_get_updater', array(&$this, 'get_updater_data') );
        add_action('wp_ajax_toolset_updater_postpone_setup', array(&$this, 'postpone_setup') );
        add_action('wp_ajax_toolset_updater_resume_setup', array(&$this, 'resume_setup') );
    }

    public function toolset_elements_need_update(){
        return $this->have_common_items;
    }

    public function render_updater_gui(){
        // if there are not flagged items in common with the updates then don't do nothing
        if ( $this->toolset_elements_need_update() && self::is_edit_page() === false ) {

            add_action('admin_notices', array(&$this, 'admin_notice'));

            add_action('admin_enqueue_scripts', array(&$this, 'register_updater_scripts'));
        }
    }

    public function postpone_setup(){
        if(wp_create_nonce('get_updater_nonce') != $_POST['nonce']){

            die( wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'toolset-updater') ) ) );

        }

        $this->settings['postponed'] = 1;
        $this->save_settings();

        echo wp_json_encode( array("message" => sprintf("Postponed %d", $this->settings['postponed']) ) );
        exit;

    }

    public function resume_setup(){
        if(wp_create_nonce('get_updater_nonce') != $_POST['nonce']){

            die( wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'toolset-updater') ) ) );

        }

        $this->settings['postponed'] = 0;
        $this->save_settings();

        echo wp_json_encode( array("message" => sprintf("Resumed %d", $this->settings['postponed']) ) );
        exit;

    }

    public function save_settings(){
        update_option(self::SETTINGS_KEY, $this->settings);
    }

    function admin_notice()
    { ?>

        <div class="updated error" id="installer_ep_postponed_wrap" <?php if(empty($this->settings['postponed'])): ?>style="display:none"<?php endif; ?>>
            <p>
                <?php printf(__('%s Toolset assets are almost ready to be used.', 'installer'), $this->theme); ?>
                <a href="#" id="toolset_updater_postponed_resume"><?php _e('Resume setup', 'installer') ?>&nbsp;&raquo;</a>
            </p>
        </div>

        <div class="updated error installer-ep-update-wrap" <?php if(!empty($this->settings['postponed'])): ?>style="display:none"<?php endif; ?>>
            <div class="updater-nag-wrap">
                <div class="update-nag-header">
                    <h3><?php _e('There are updates to elements that you edited', 'toolset-updater'); ?></h3>
                    <p><?php _e( sprintf('%s %s updates Toolset elements, which make up your site and were edited locally. Please choose how to receive these updates:', $this->theme, $this->current_version), 'toolset-updater' ); ?></p>
                </div>
                <div class="toolset-updater-form-wrap">
                    <div class="toolset-updater-form-inner">
                <form id="toolset-updater-form" name="toolset-updater-form" method="post"
                      action="toolset-get-updater-items">
                    <?php
                    foreach ($this->instances as $instance) {
                        $instance->render_form_elements();
                    }
                    ?>
                    <div class="button-wrap">
                        <button class="postpone-updater button-secondary"><?php _e('Postpone', 'toolset-updater'); ?></button>
                        <input type="submit" class="toolset-updater-submit button-primary"
                               value="Continue with updates"/>

                    </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function register_updater_scripts()
    {
        wp_register_style('toolset-updater-style', TOOLSET_UPDATER_URI . 'css/updater.css', array(), '0.1', 'screen');
        wp_register_script('toolset-updater-script', TOOLSET_UPDATER_URI . 'js/updater.js', array('jquery', 'underscore'), '0.1', true);

        wp_enqueue_style('toolset-updater-style');
        wp_enqueue_script('toolset-updater-script');
        wp_localize_script('toolset-updater-script',
                            "UPDATER_SETTINGS",
                            array(
                                "plugins_data" => self::$PLUGIN_ACCESS,
                                "get_updater_nonce" => wp_create_nonce('get_updater_nonce'),
                            )
        );
    }

    public static function is_toolset_page()
    {
        global $pagenow;
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if (
            ($pagenow == 'admin.php' && (in_array($page, self::$ADMIN_PAGES) || $page == 'dd_layouts_edit')) ||
            ($pagenow == 'post.php' && $action === 'edit') || ($pagenow === 'themes.php')
        ) {
            return true;
        }
        return false;
    }

    public static function is_edit_page(){

        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ( $action === 'edit' ) {
            return true;
        }
        return false;
    }

    function factory($items)
    {
        $class_prefix = "Toolset";
        if($items){
            foreach ($items as $item) {
                $class = $class_prefix . $item['name'];
                $this->instances[$class] = new $class($item['name'], $item['items'], $item['dir']);
            }
        }
    }

    function get_data_root()
    {
        return $this->data_root;
    }

    function posts_clauses_request_filter($pieces, $query)
    {
        global $wpdb;
        // only return the fields required for the data object.
        $pieces['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_name, $wpdb->posts.post_type";

        return $pieces;
    }

    private function plugins_have_common_items()
    {

        if (count($this->instances) === 0) {
            return false;
        }

        $ret = false;

        foreach ($this->instances as $instance) {
            if ( $instance->has_common_items() ) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    function get_items()
    {
        $args = array(
            'posts_per_page' => -1,
            'post_type' => self::$TOOLSET_TYPES,
            // get only published posts
            'post_status' => array('publish','private'),
            //don't perform found posts query
            'no_found_rows' => false,
            // leave the terms alone we don't need them
            'update_post_term_cache' => false,
            // leave the meta alone we don't need them
            'update_post_meta_cache' => false,
            // don't cache results
            'cache_results' => false,
            'suppress_filters' => false,
            );

        $query = new WP_Query($args);

        if (count($query->posts) === 0) {
            return null;
        }

        $ret = $existing_items = array();

        foreach ($query->posts as $post) {
            $post = (array)$post;

            $blacklist = array('post_status', 'filter', 'post_date', 'post_parent', 'post_password', 'comment_count', 'comment_status', 'guid', 'menu_order', 'pinged', 'ping_status', 'post_author', 'post_content', 'post_content_filtered', 'post_date_gmt', 'post_excerpt', 'post_mime_type', 'post_modified', 'post_modified_gmt', 'to_ping');

            $keys = array_filter(array_keys($post), array( new Toolset_FilterByProperty($blacklist ), 'value_not_in_array') );

            $updated = get_post_meta($post['ID'], self::META_KEY, true);
            $ret = $this->set_meta($ret, $post['post_type']);
            if ( !empty($updated) ){
                $ret[$this->get_plugin_label($post['post_type'])]['items'][] = (object)array_intersect_key($post, array_flip($keys));
            }

            $existing_items[$this->get_plugin_label($post['post_type'])]['items'][] = (object)array_intersect_key($post, array_flip($keys));
        }

        $ret = $this->get_types_definitions( $ret, 'wpcf-custom-types');
        $ret = $this->get_types_definitions( $ret, 'wpcf-custom-taxonomies');
        
        $existing_items = $this->get_types_definitions( $ret, 'wpcf-custom-types', true, $existing_items);
        $existing_items = $this->get_types_definitions( $ret, 'wpcf-custom-taxonomies', true, $existing_items);
        
        $ret = apply_filters( 'updater_get_new_items', $ret, $existing_items );
       
        return $ret;
    }
    
    /**
     * set meta data
     */
    private function set_meta($ret, $post_type )
    {
        /**
         * check and if data exist, just return
         */
        if (isset($ret[$this->get_plugin_label($post_type)])) {
            return $ret;
        }
        /**
         * add meta data
         */
        $ret[$this->get_plugin_label($post_type)] = array(
            "items" => array(),
            "dir" => $this->get_plugin_data_dir($post_type),
            "name" => $this->get_plugin_label($post_type)
        );
        return $ret;
    }

    /**
     * get Types CPT and CT definition
     */
    private function get_types_definitions($ret, $type, $existing = false, $existing_items = '')
    {
        $custom = get_option( $type, array() );
        foreach( $custom as $one ) {
            if ( !isset($one[self::META_KEY]) && !$existing ) {
                continue;
            }
            $ret = $this->set_meta($ret, $type);
            $temp = array(
                'ID' => $one['slug'],
                'post_title' => $one['labels']['name'],
                'post_name' => $one['slug'],
                'post_type' => $type,
            );
            if ( $existing ){
                $existing_items[$this->get_plugin_label($type)]['items'][] = $temp;
            }
            $ret[$this->get_plugin_label($type)]['items'][] = (object)$temp;
        }
        if ( $existing ){
            return $existing_items;  
        }
        return $ret;
    }
    
    function updater_get_new_items_filter( $ret = array(), $existing_items ){
    
        $ret = $this->updater_get_new_types_items($ret, $existing_items);
        $ret = $this->updater_get_new_cred_items($ret, $existing_items);
        $ret = $this->updater_get_new_views_items($ret, $existing_items);
        $ret = $this->updater_get_new_layouts_items($ret, $existing_items);
        
        return $ret;
    }
    
    function updater_check_if_item_exists( $item, $existing_items, $plugin, $post_type ){
        $items = $existing_items[$plugin]['items'];
        for ($i=0,$count=count($items);$i<$count;$i++){           
            $temp_item = (object)$items[$i];
            if ( $temp_item->post_name == $item && $temp_item->post_type == $post_type ){
                return true;
            }
        }
        return false;
        
    }
    
    function updater_get_new_layouts_items( $ret, $existing_items ){
        $layouts_directory = get_stylesheet_directory() . '/toolset_import/layouts/';
        $reader = new DLL_Reader($layouts_directory);
        $reader->get_layouts_from_dir();
        $import_layouts = $reader->layouts;
        
        for ( $i=0, $count = count($import_layouts); $i<$count; $i++ ){
            $layout = $import_layouts[$i];
            if ( !$this->updater_check_if_item_exists($layout['name'], $existing_items, 'Layouts', 'dd_layouts') ){
                $ret['Layouts']['items'][] = (object)array(
                            'ID'            => $layout['id'],
                            'post_title'    => $layout['title'],
                            'post_name'     => $layout['name'],
                            'post_type'     => 'dd_layouts',
                            'is_new' => true
                );
            }
        }
        return $ret;
    }

    function updater_get_new_views_items( $ret, $existing_items ){
        $wpv_theme_import_xml = get_stylesheet_directory() . '/toolset_import/views/settings.xml';
        if ( file_exists($wpv_theme_import_xml) ){
            $ret = $this->set_meta($ret, 'view');
            $data = join('', file($wpv_theme_import_xml));
            $use_errors = libxml_use_internal_errors(true);

            $xml = (array)simplexml_load_string($data);
            $xml = $this->xml2array($xml);
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);

            if ( isset($xml['view-templates']['view-template']) ){
                $templates = $xml['view-templates']['view-template'];
                if ( !isset($templates[0]) ){
                    $templates[0] = $templates;
                }
                for ( $i=0, $count=count($templates); $i<$count; $i++ ){
                    $template = (array)$templates[$i];
                    if ( !$this->updater_check_if_item_exists($template['post_name'], $existing_items, 'Views', $template['post_type']) ){
                        $ret['Views']['items'][] = (object)array(
                            'ID'            => $template['ID'],
                            'post_title'    => $template['post_title'],
                            'post_name'     => $template['post_name'],
                            'post_type'     => $template['post_type'],
                            'is_new' => true
                        );
                    }
                }
            }

            if ( isset($xml['views']['view']) ){
                $views = $xml['views']['view'];
                if ( !isset($views[0]) ){
                    $views[0] = $views;
                }
                for ( $i=0, $count=count($views); $i<$count; $i++ ){
                    $view = (array)$views[$i];
                    if ( !$this->updater_check_if_item_exists($view['post_name'], $existing_items, 'Views', $view['post_type']) ){
                        $ret['Views']['items'][] = (object)array(
                            'ID'            => $view['ID'],
                            'post_title'    => $view['post_title'],
                            'post_name'     => $view['post_name'],
                            'post_type'     => $view['post_type'],
                            'is_new' => true
                        );
                    }
                }
            }
        }

        return $ret;
    }


    function xml2array( $xmlObject, $out = array () )
    {
        $out = json_decode(json_encode((array)$xmlObject), TRUE);
        return $out;
    }

    function updater_get_new_types_items( $ret, $existing_items ){
        $types_import_xml = $ret['Types']['dir'] . '/settings.xml';
        if ( file_exists($types_import_xml) && (function_exists('wpcf_embedded_load_or_deactivate') || defined('WPCF_VERSION')) ){
            $ret = $this->set_meta($ret, 'wp-types-group');
            $data = join('', file($types_import_xml));
            $use_errors = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($data);
            $xml = $this->xml2array($xml);
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);

            if ( isset($xml['taxonomies']['taxonomy']) ){
                $tax = $xml['taxonomies']['taxonomy'];
                if ( !isset($tax[0]) ){
                    $temp[0] = $tax;
                    $tax = $temp;
                }
                for ( $i=0,$count=count($tax); $i<$count; $i++){
                    $item = (array)$tax[$i];
                    if ( !$this->updater_check_if_item_exists($item['__types_id'], $existing_items, 'Types', 'wpcf-custom-taxonomies') ){
                        $ret['Types']['items'][] = (object)array(
                            'ID'            => $item['__types_id'],
                            'post_title'    => $item['__types_title'],
                            'post_name'     => $item['__types_id'],
                            'post_type'     => 'wpcf-custom-taxonomies',
                            'is_new' => true
                        );
                    }
                }
            }

            if ( isset($xml['user_groups']['group']) ){
                $groups = $xml['user_groups']['group'];
                if ( !isset($groups[0]) ){
                    $temp[0] = $groups;
                    $groups = $temp;
                }
                for ( $i=0,$count=count($groups); $i<$count; $i++){
                    $item = $groups[$i];
                    if ( !$this->updater_check_if_item_exists($item['__types_id'], $existing_items, 'Types', $item['post_type']) ){
                        $ret['Types']['items'][] = (object)array(
                            'ID'            => $item['ID'],
                            'post_title'    => $item['post_title'],
                            'post_name'     => $item['__types_id'],
                            'post_type'     => $item['post_type'],
                            'is_new' => true
                        );
                    }
                }
            }

            if ( isset($xml['groups']['group']) ){
                $groups = $xml['groups']['group'];
                if ( !isset($groups[0]) ){
                    $temp[0] = $groups;
                    $groups = $temp;
                }
                for ( $i=0,$count=count($groups); $i<$count; $i++){
                    $item = (array)$groups[$i];
                    if ( !$this->updater_check_if_item_exists($item['__types_id'], $existing_items, 'Types', $item['post_type']) ){
                        $ret['Types']['items'][] = (object)array(
                            'ID'            => $item['ID'],
                            'post_title'    => $item['post_title'],
                            'post_name'     => $item['__types_id'],
                            'post_type'     => $item['post_type'],
                            'is_new' => true
                        );
                    }
                }
            }

            if ( isset($xml['types']['type']) ){
                $types = $xml['types']['type'];
                if ( !isset($types[0]) ){
                    $temp[0] = $types;
                    $types = $temp;
                }
                for ( $i=0,$count=count($types); $i<$count; $i++){
                    $item = (array)$types[$i];
                    if ( !$this->updater_check_if_item_exists($item['__types_id'], $existing_items, 'Types', 'wpcf-custom-types') ){
                        $ret['Types']['items'][] = (object)array(
                            'ID'            => $item['__types_id'],
                            'post_title'    => $item['__types_title'],
                            'post_name'     => $item['__types_id'],
                            'post_type'     => 'wpcf-custom-types',
                            'is_new' => true
                        );
                    }
                }
            }
        }
        return $ret;
    }

    function updater_get_new_cred_items( $ret, $existing_items ){

        $cred_file = get_stylesheet_directory() . '/toolset_import/cred/settings.xml';
        if ( file_exists($cred_file) && class_exists('CRED_Loader') ){

            $data = join('', file($cred_file));
            $use_errors = libxml_use_internal_errors(true);
            $xml = (array)simplexml_load_string($data);
            $xml = $this->xml2array($xml);
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);

            if ( isset($xml['form']) && is_array($xml['form']) ){
                $forms = $xml['form'];
                if ( !isset($forms[0]) ){
                    $temp[0] = $forms;
                    $forms = $temp;
                }
                for ( $i=0, $count_forms=count($forms); $i<$count_forms; $i++ ){
                    $form = $forms[$i];
                    if ( !$this->updater_check_if_item_exists($form['post_name'], $existing_items, 'CRED', $form['post_type']) ){
                        $ret = $this->set_meta($ret, 'cred-form');
                        $ret['CRED']['items'][] = (object)array(
                            'ID'            => $form['ID'],
                            'post_title'    => $form['post_title'],
                            'post_name'     => $form['post_name'],
                            'post_type'     => $form['post_type'],
                            'is_new' => true
                        );
                    }
                }
            }

        }
        return $ret;
    }
    
   
    
    function check_item_updated($id){
        $updated = get_post_meta($id, self::META_KEY, true);
        if ( !empty($updated) ){
            return true;
        }
        else{
            return false;
        }
    }
    
    function get_plugin_label($type)
    {
        $plugin = (object)self::$PLUGIN_ACCESS[$type];
        return $plugin->label;
    }

    function get_plugin_from_type($post_type)
    {
        return self::$PLUGIN_ACCESS[$post_type];
    }

    function get_plugin_data_dir($post_type)
    {
        $plugin = (object)$this->get_plugin_from_type($post_type);
        return $this->get_data_root() . DIRECTORY_SEPARATOR . $plugin->dir;
    }

    public function get_updater_data(){
        if( $_POST ){
            if( !current_user_can( 'manage_options' ) ){
                die( wp_json_encode( array( 'error' =>  __( sprintf('I am sorry but you don\'t have the necessary privileges to perform this action. %s', __METHOD__ ), 'toolset-updater') ) ) );
            }
            if( !wp_verify_nonce($_POST['get_updater_nonce'], 'get_updater_nonce') ){
                die( wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'toolset-updater') ) ) );
            }

            if( $_POST['data'] ){

                $raw = stripslashes( $_POST['data'] );
                $json = json_decode( $raw, true );
                
                $import_class_file = WP_Installer()->plugin_path() . '/embedded-plugins/import/' . self::PLUGINS_REPOSITORIES . '/toolset_import.php';
                if(file_exists($import_class_file)){
                    require_once $import_class_file;
                    new WP_Toolset_Import();
                }
                



                do_action('installer_ep_update_data', $json);

                $version = WP_Installer()->installer_embedded_plugins->save_current_version();

                echo wp_json_encode( array( 'message' =>  __( sprintf('%s theme Toolset assets updated to version. %s', $this->theme, $version ), 'toolset-updater') ) );

            }
        }

        die();
    }

}

interface Toolset_Reader
{
    function get_items_from_files();
    function set_items_from_files();
}

class XML_Reader implements Toolset_Reader
{

    private $data_dir;
    private $file_name;
    private $file = null;
    protected $doc;
    protected $tags;
    protected $items = array();

    public function __construct($data_dir, $file_name, $tag)
    {
        $this->data_dir = $data_dir;
        $this->file_name = $file_name;
        $this->doc = new DOMDocument();
        $this->doc->load($this->get_file());
        $this->doc->preserveWhiteSpace = false;
        $this->tags = $this->getElementsByTagName($tag);
    }

    function get_file()
    {
        $this->file = $this->data_dir . DIRECTORY_SEPARATOR . $this->file_name;
        if (file_exists($this->file)) {
            return $this->file;
        } else {
            return null;
        }
    }

    function get_items_from_files()
    {
        return $this->items;
    }

    function set_items_from_files()
    {
        foreach ($this->tags as $tag) {
            $this->items[] = $tag->nodeValue;
        }
    }

    function getElementsByTagName($tag)
    {
        return $this->doc->getElementsByTagName($tag);
    }

    function getTags()
    {
        return $this->tags;
    }
}

class DLL_Reader implements Toolset_Reader
{
    private $data_dir;
    private $files = array();
    public $layouts = array();

    public function __construct($data_dir)
    {
        $this->data_dir = $data_dir;
    }

    public function set_items_from_files()
    {
        $iterator = new DirectoryIterator($this->data_dir);
        foreach ($iterator as $item) {
            if ($item->isDot() === false && $item->isFile()) {
                $name = explode('.' . $item->getExtension(), $item->getFilename());
                $this->files[] = $name[0];
            }
        }
    }
    
    public function get_layouts_from_dir(){
        $iterator = new DirectoryIterator($this->data_dir);
        foreach ($iterator as $item) {       
            if ($item->isDot() === false && $item->isFile()) {                
                $content = json_decode( join('', file($item->getPathname())) );
                if (isset($content->slug)){
                    $name = explode('.' . $item->getExtension(), $item->getFilename());
                    $this->layouts[] = array('id'=>$content->id, 'slug'=>$content->slug, 'name'=>$name[0], 'title'=>$content->name);
                }
            }
        }
    }

    public function get_items_from_files()
    {
        return $this->files;
    }
}

class Types_Reader extends XML_Reader
{
    private $xml_tag_to_post_type = array(
        'fields' => '',
        'groups' => 'wp-types-group',
        'taxonomies' => 'wpcf-custom-taxonomies',
        'types' => 'wpcf-custom-types',
        'user_fields' => '',
        'user_groups' => 'wp-types-user-group',
    );

    public function __construct($data_dir, $file_name, $tag)
    {
        parent::__construct($data_dir, $file_name, $tag);
        $this->tags = $this->getElementsByTagName($tag);
    }

    function getElementsByTagName($tag)
    {
        $elements = array();
        foreach( array('groups', 'user_groups', ) as $type ) {
            $elements[$this->xml_tag_to_post_type[$type]] = array();
            foreach( $this->doc->getElementsByTagName($type) as $item ) {
                $elements[$this->xml_tag_to_post_type[$type]] = $item->getElementsByTagName($tag);
            }
        }
        foreach( array('fields', 'user_fields','types', 'taxonomies', ) as $type ) {
            $elements[$this->xml_tag_to_post_type[$type]] = array();
            foreach( $this->doc->getElementsByTagName($type) as $item ) {
                $elements[$this->xml_tag_to_post_type[$type]] = $item->getElementsByTagName('__types_title');
            }
        }
        return $elements;
    }

    function set_items_from_files()
    {
        foreach(array_values($this->xml_tag_to_post_type) as $key ) {
            $this->itemsp[$key] = array();
        }
        foreach ($this->tags as $key => $group) {
            foreach( $group as $tag ) {
                $this->items[$key][] = $tag->nodeValue;
            }
        }
    }

    function get_items_from_files()
    {
        return $this->items;
    }

}

abstract class ToolsetPlugin
{
    protected $name;
    protected $dir;
    protected $items;
    protected $fileReader;
    private $human_post_type_names = array();

    public function __construct($name, $items, $dir)
    {
        $this->name = $name;
        $this->items = $items;
        $this->dir = $dir;
    }

    protected function getFlaggedItems()
    {
        return $this->items;
    }

    protected function get_human_post_type_names($key)
    {
        $human_post_type_names = array(
            'wp-types-group' => __('Custom field group', 'toolset-updater'),
            'wpcf-custom-taxonomies' => __('Custom taxonomy', 'toolset-updater'),
            'wpcf-custom-types' => __('Custom post type', 'toolset-updater'),
        );
        if (isset($human_post_type_names[$key])) {
            return $human_post_type_names[$key];
        }
        return $key;
    }

    function render_form_elements()
    {
        $elements = $this->get_common_items();
        if (!is_array($elements) || count($elements) == 0) {
            return;
        }
        ?>
        <h3><?php echo $this->name;?></h3>
        <div class="checkboxes-box">

        <?php ob_start();?>
            <table class="installer-ep-updater-table" cellspacing="0">

                <tr class="titles-row">
                    <th class="updater-titles"><?php echo trim($this->name, 's');?></th>

                    <th class="updater-titles"><?php printf( __('Keep using my edited %s and save the new version with a new name', 'toolset-updater'), trim($this->name, 's') );?></th>

                    <th class="updater-titles"><?php _e('Overwrite my edits with the new version', 'toolset-updater');?></th>
                    <th class="updater-titles"><?php  _e('Skip this update and don\'t add it to the site', 'toolset-updater') ;?></th>
                </tr>
                <tbody>
                <?php
                $count = 1;
                $new_count = 0;
                foreach ($this->get_common_items() as $item):
                    $item->plugin = $this->name;                   
                    $input_prefix = strtolower( trim($this->name, 's') );
                    if ( !isset($item->is_new ) ){
                    ?>
                    <tr class="asset-element-row">
                    <td class="updater-col updater-title-col"><?php echo $item->post_title; ?> <small>(<?php echo $this->get_human_post_type_names($item->post_type); ?>)</small></td>

                        <td class="updater-input-col">
                            
                            
                            <input checked class="updater_item_select" value="<?php $item->do = "duplicate";
                            echo htmlspecialchars(wp_json_encode($item)); ?>" type="radio"
                                   name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>"></td>

                        <td class="updater-input-col">

                           <input class="updater_item_select" value="<?php $item->do = "overwrite";
                            echo htmlspecialchars(wp_json_encode($item)); ?>" type="radio"
                                name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>"></td>
                        <td class="updater-input-col">
                            <input
                                class="updater_item_select" value="<?php $item->do = "skip";
                            echo htmlspecialchars(wp_json_encode($item)); ?>" type="radio"
                                name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>"></td>
                    </tr>
                    <?php
                    $count++;
                    }
                endforeach; ?>
                
              <!--  <input type="hidden" name="updater-plugin" value="<?php echo $this->name;?>"/> -->
                </tbody>
            </table>
        <?php $out = ob_get_clean();
        $and = '';
        if ( $count > 1 ){
            $and = __('and ', 'installer');
            echo $out;
        }
        foreach ($this->get_common_items() as $item):      
            if ( isset($item->is_new ) ){  
                ?>
                <input class="updater_item_select" value="<?php $item->do = "overwrite";
                            echo htmlspecialchars(wp_json_encode($item)); ?>" type="hidden"
                                name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>">
                <?php
                $new_count++;
                $count++;
            }
        endforeach;
        
        if ( $new_count > 0 ){?>
            <p><?php
            echo $and . sprintf( _n( '1 new item', '%s new items', $new_count , 'toolset-updater' ), $new_count );
            ?></p>
        <?php }?>        
        </div>
        <?php

    }

    function has_common_items()
    {
        $elements = $this->get_common_items();
        if (is_array($elements) && count($elements) > 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function get_common_items()
    {
        return array_filter($this->items, function ($item) {
            return in_array($item->{$this->field}, $this->reader->get_items_from_files());
        });
    }

    public function get_objects_to_render()
    {
        return array(
            $this->name => $this->get_common_items()
        );
    }
}

class ToolsetViews extends ToolsetPlugin
{

    protected $file = 'settings.xml';
    protected $field = 'post_name';

    public function __construct($name, $items, $dir)
    {
        parent::__construct($name, $items, $dir);
        $this->reader = new XML_Reader($this->dir, $this->file, $this->field);
        $this->reader->set_items_from_files();
    }
}

class ToolsetTypes extends ToolsetPlugin
{

    protected $file = 'settings.xml';
    protected $field = 'post_title';

    public function __construct($name, $items, $dir)
    {
        parent::__construct($name, $items, $dir);
        $this->reader = new Types_Reader($this->dir, $this->file, $this->field);
        $this->reader->set_items_from_files();
    }

    protected function get_common_items()
    {
        return array_filter($this->items, function ($item) {
            $items = $this->reader->get_items_from_files();
            return
                isset($items[$item->post_type])
                && in_array($item->{$this->field}, $items[$item->post_type]);
        });
    }

    function render_form_elements()
    {
        $elements = $this->get_common_items();
        if (!is_array($elements) || count($elements) == 0) {
            return;
        }
        ?>
        <h3><?php echo $this->name;?></h3>
        <div class="checkboxes-box">

            <?php ob_start();?>
            <table class="installer-ep-updater-table three-columns-table" cellspacing="0">

                <tr class="titles-row">
                    <th class="updater-titles first-one"><?php echo trim($this->name, 's');?></th>

                    <th class="updater-titles centered"><?php _e('Overwrite my edits with the new version', 'toolset-updater');?></th>
                    <th class="updater-titles centered"><?php  _e('Skip this update and don\'t add it to the site', 'toolset-updater') ;?></th>
                </tr>
                <tbody>
                <?php
                $count = 1;
                $new_count = 0;
                foreach ($this->get_common_items() as $item):
                    $item->plugin = $this->name;
                    $input_prefix = strtolower( trim($this->name, 's') );
                    if ( !isset($item->is_new ) ){
                        ?>
                        <tr class="asset-element-row">
                            <td class="updater-col updater-title-col first-one"><?php echo $item->post_title; ?> <small>(<?php echo $this->get_human_post_type_names($item->post_type); ?>)</small></td>

                            <td class="updater-input-col">

                                <input checked class="updater_item_select" value="<?php $item->do = "overwrite";
                                echo htmlspecialchars(wp_json_encode($item)); ?>" type="radio"
                                       name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>"></td>
                            <td class="updater-input-col">
                                <input
                                    class="updater_item_select" value="<?php $item->do = "skip";
                                echo htmlspecialchars(wp_json_encode($item)); ?>" type="radio"
                                    name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>"></td>
                        </tr>
                        <?php
                        $count++;
                    }
                endforeach; ?>

                <!--  <input type="hidden" name="updater-plugin" value="<?php echo $this->name;?>"/> -->
                </tbody>
            </table>
            <?php $out = ob_get_clean();
            $and = '';
            if ( $count > 1 ){
                $and = __('and ', 'installer');
                echo $out;
            }
            foreach ($this->get_common_items() as $item):
                if ( isset($item->is_new ) ){
                    ?>
                    <input class="updater_item_select" value="<?php $item->do = "overwrite";
                    echo htmlspecialchars(wp_json_encode($item)); ?>" type="hidden"
                           name="updater_item_select_<?php echo $input_prefix.'_'.$count;?>">
                    <?php
                    $new_count++;
                    $count++;
                }
            endforeach;

            if ( $new_count > 0 ){?>
                <p><?php
                    echo $and . sprintf( _n( '1 new item', '%s new items', $new_count , 'toolset-updater' ), $new_count );
                    ?></p>
            <?php }?>
        </div>
    <?php

    }
}

class ToolsetLayouts extends ToolsetPlugin
{
    protected $field = 'post_name';

    public function __construct($name, $items, $dir)
    {
        parent::__construct($name, $items, $dir);
        $this->reader = new DLL_Reader($dir);
        $this->reader->set_items_from_files();
    }

    function render_form_elements(){

        if( function_exists('ddl_update_theme_layouts') ) {

            parent::render_form_elements();

        } else {

            $elements = $this->get_common_items();
            if (!is_array($elements) || count($elements) == 0) {
                return;
            }
            ?>
            <h3><?php echo $this->name;?></h3>
            <div class="checkboxes-box">

            <?php
            ob_start();
            ?>
            <table class="installer-ep-updater-table" cellspacing="0">

                <tr class="titles-row">
                    <th class="updater-titles"><?php echo trim($this->name, 's'); ?></th>
                    <th class="updater-titles"><?php printf( __('Keep using my edited %s and save the new version with a new name', 'toolset-updater'), trim($this->name, 's') );?></th>
                    <th class="updater-titles"><?php _e('Overwrite my edits with the new version', 'toolset-updater'); ?></th>
                    <th class="updater-titles"><?php _e('Skip this update and don\'t add it to the site', 'toolset-updater'); ?></th>
                </tr>
                <tbody>
                <?php
                $count = 1;
                $new_count = 0;
                foreach ($this->get_common_items() as $item):
                    $item->plugin = $this->name;
                    $input_prefix = strtolower(trim($this->name, 's'));
                    if (!isset($item->is_new)) {
                        ?>
                        <tr class="asset-element-row">
                            <td class="updater-col updater-title-col"><?php echo $item->post_title; ?>
                                <small>(<?php echo $this->get_human_post_type_names($item->post_type); ?>)</small>
                            </td>

                            <td class="updater-input-col">

                                <input checked disabled class="updater_item_select" value="" type="radio"
                                       name=""></td>

                            <td class="updater-input-col">

                                <input disabled class="updater_item_select" value="" type="radio"
                                       name=""></td>
                            <td class="updater-input-col">
                                <input disabled
                                    class="updater_item_select" value="" type="radio"
                                    name=""></td>
                        </tr>
                        <?php
                        $count++;
                    }
                endforeach; ?>

                <!--  <input type="hidden" name="updater-plugin" value="<?php echo $this->name; ?>"/> -->
                </tbody>
            </table>
            <?php $out = ob_get_clean();
            $and = '';
            if ($count > 1) {
                $and = __('and ', 'installer');
                echo $out;
            }
            foreach ($this->get_common_items() as $item):
                if (isset($item->is_new)) {
                    ?>
                    <input class="updater_item_select" value="<?php $item->do = "overwrite";
                    echo htmlspecialchars(wp_json_encode($item)); ?>" type="hidden"
                           name="updater_item_select_<?php echo $input_prefix . '_' . $count;?>">
                    <?php
                    $new_count++;
                    $count++;
                }
            endforeach;

            if ($new_count > 0) {
                ?>
                <p><?php
                    echo $and . sprintf(_n('1 new item', '%s new items', $new_count, 'toolset-updater'), $new_count);
                    ?></p>
            <?php }
            ?>
                <div class="message warning updater-warning toolset-alert"><?php printf(__('Layouts %s version does not support automatic updates. Please upgrade at least to Layouts 1.1 to update your layouts with Installer.', 'toolset-updater'), WPDDL_VERSION)?></div>
            </div>
        <?php
        }
    }
}

class ToolsetCRED extends ToolsetViews
{
    public function __construct($name, $items, $dir)
    {
        parent::__construct($name, $items, $dir);
    }
}

Class Toolset_FilterByProperty{
   public $value = null;

    public function __construct( $value ){
        $this->value = $value;
    }

    public function value_not_in_array( $array ){
        return !in_array( $array, $this->value  );
    }
}
