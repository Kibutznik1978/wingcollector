<?php

require WP_Installer()->plugin_path() . '/' . basename(dirname(__FILE__)) . '/include/misc-functions.php';
require WP_Installer()->plugin_path() . '/' . basename(dirname(__FILE__)) . '/include/toolset-plugins-updater.class.php';


class Installer_Embedded_Plugins{

    const PLUGINS_PACKAGE       = 'Toolset Embedded';
    const SETTINGS_KEY          = 'embedded_toolset_settings';
    const PLUGINS_REPOSITORIES  = 'toolset';

    private $required_plugins = null;
    private $missing_plugins = null;
    private $inactive_plugins = null;

    private $config_files_array = array();

    private $version = 1.4;

    private $url = '';
    private $path = '';

    private $installer_instance_key;

    function __construct(){

        if(!class_exists('WP_Installer') || !is_admin()) return;

        $this->installer_instance_key = dirname( dirname( __FILE__ ) ) . '/installer.php';

        $this->load_locale();

        $this->settings = get_option(self::SETTINGS_KEY);

        $this->url      = WP_Installer()->plugin_url() . '/' . basename(dirname(__FILE__));
        $this->path    = WP_Installer()->plugin_path() . '/' . basename(dirname(__FILE__));

        $this->read_config_files_array();

        if($this->is_theme_update() && !$this->is_add_missing_plugins()){

            define('TOOLSET_UPDATER_URI', $this->url . '/res/');

            $updater_instance = new ToolsetPluginsFactory();

            if( $updater_instance->toolset_elements_need_update() ){
                $updater_instance->render_updater_gui();
            }

        }

        if($this->is_setup_complete() && !$this->is_add_missing_plugins()){
            return;
        }

        add_action('admin_init', array($this, 'init'), 30);

        add_filter('installer_deps_missing', array($this, 'get_missing_plugins'));

        //_disable_wp_redirects
        if(isset($_POST['action']) && $_POST['action'] == 'installer_ep_run'){
            add_filter('wp_redirect', '__return_false', 10000);
        }

    }

    function init(){

        $this->settings['completed_items']['install'] = count($this->get_missing_plugins()) == 0;
        $this->settings['completed_items']['activate'] = count($this->get_inactive_plugins()) == 0;

        add_action('wp_ajax_installer_ep_run', array($this, 'run'));
        add_action('wp_ajax_installer_ep_postpone_setup', array($this, 'postpone_setup'));
        add_action('wp_ajax_installer_ep_resume_setup', array($this, 'resume_setup'));

        add_action('admin_notices', array($this, 'setup_notice'));

        wp_enqueue_script( 'installer-embedded-plugins', $this->url . '/res/js/scripts.js', array('jquery'), $this->version, true );
        wp_enqueue_style( 'installer-embedded-style', $this->url . '/res/css/style.css', array(), $this->version );


    }

    public function load_locale(){
        $locale = get_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'installer' );

        $mo_file = WP_Installer()->plugin_path() . '/' . basename(dirname(__FILE__)) . '/locale/installer-embedded-' . $locale . '.mo';

        if(file_exists($mo_file)){
            load_textdomain( 'installer', $mo_file  );
        }
    }

    public function save_settings(){
        update_option(self::SETTINGS_KEY, $this->settings);
    }

    public function read_config_files_array(){
        global $wp_installer_instances;

        $config_files = array();

        foreach($wp_installer_instances as $instance) {
            $config_file = dirname($instance['bootfile']) . '/deps.xml';

            if (file_exists($config_file) && is_readable($config_file)) {
                $config_files[] = $config_file;
            }
        }

        $config_file = get_template_directory() . '/deps.xml';
        if (file_exists($config_file) && is_readable($config_file)) {
            $config_files[] = $config_file;
        }

        if(get_stylesheet_directory() != get_template_directory()){
            $config_file = get_stylesheet_directory() . '/deps.xml';
            if (file_exists($config_file) && is_readable($config_file)) {
                $config_files[] = $config_file;
            }
        }

        $this->config_files_array = apply_filters( 'installer_ep_config_files', $config_files );

    }

    public function get_required_plugins(){

        if(is_null($this->required_plugins)) {

            $this->required_plugins = array();

            foreach ($this->config_files_array as $config_file) {

                $config = $this->read_config($config_file);
                $config_arr_key = md5($config_file) . '|' . $config['name'];

                foreach ($config['repositories'] as $repository_id => $repository) {

                    foreach ($repository['plugins'] as $plugin) {

                        $plugin_full_name = $this->get_plugin_full_name($repository_id, $plugin['name'], $config);
                        if (!$plugin_full_name) continue;

                        $this->required_plugins[$config_arr_key][] = array(
                            'slug'          => $plugin['name'],
                            'name'          => $plugin_full_name,
                            'format'        => $plugin['format'],
                            'url'           => $this->get_plugin_download_url($repository_id, $plugin['name'], $config),
                            'repository_id' => $repository_id
                        );

                    }

                }

            }

        }

        return $this->required_plugins;
    }

    public function get_missing_plugins(){

        if(is_null($this->missing_plugins)) {

            $this->missing_plugins = array();

            foreach ($this->config_files_array as $config_file) {

                $config = $this->read_config($config_file);
                $config_arr_key = md5($config_file) . '|' . $config['name'];

                foreach ($config['repositories'] as $repository_id => $repository) {

                    foreach ($repository['plugins'] as $plugin) {

                        if (empty($plugin['version']) || $plugin['version'] == 'latest') {
                            $plugin['version'] = WP_Installer()->get_plugin_repository_version($repository_id, $plugin['name']);
                        }

                        $plugin_full_name = $this->get_plugin_full_name($repository_id, $plugin['name'], $config);
                        if (!$plugin_full_name) continue;

                        $real_slug = $plugin['name'];
                        $plugin_real_full_name = $plugin_full_name;
                        if (isset($plugin['format'])) {  //embedded
                            $real_slug .= '-' . $plugin['format'];
                            $plugin_real_full_name .=  ' Embedded';
                        }

                        $missing    = false;
                        $outdated   = false;

                        if (!$this->is_plugin_installed($real_slug, $plugin_real_full_name) && !$this->is_plugin_installed($plugin['name'], $plugin_full_name)) {

                            $missing = true;

                        }elseif(
                                $this->is_plugin_installed($plugin['name'], $plugin_full_name) && $this->is_plugin_installed($plugin['name'], $plugin_full_name, $plugin['version'], '<') ||
                                $this->is_plugin_installed($real_slug, $plugin_real_full_name) && $this->is_plugin_installed($real_slug, $plugin_real_full_name, $plugin['version'], '<')
                        ){

                            $missing    = true;
                            $outdated   = true;

                        }

                        if($missing){

                            $this->missing_plugins[$config_arr_key][] = array(
                                'slug'          => $plugin['name'],
                                'name'          => $plugin_full_name,
                                'format'        => $plugin['format'],
                                'url'           => $this->get_plugin_download_url($repository_id, $plugin['name'], $config),
                                'repository_id' => $repository_id,
                                'outdated'      => $outdated
                            );


                        }

                    }

                }

            }

        }

        return $this->missing_plugins;
    }

    public function get_inactive_plugins(){

        if(is_null($this->required_plugins)) {

            $this->inactive_plugins = array();
            $required_plugins = $this->get_required_plugins();

            foreach($required_plugins as $repo_key => $plugins){

                foreach($plugins as $plugin){

                    $active = false;

                    if ($this->is_plugin_active($plugin['slug'], $plugin['name'])){
                        $active = true;
                    }elseif($plugin['format'] == 'embedded'){
                        $active = $this->is_plugin_active($plugin['slug'] . '-embedded', $plugin['name'] . ' Embedded');
                    }

                    if(!$active){

                       if($plugin['format'] == 'embedded' && $this->is_plugin_installed($plugin['slug'] . '-embedded', $plugin['name'] . ' Embedded')){
                           $plugin['real_slug'] = $plugin['slug'] . '-embedded';
                       }else{
                           $plugin['real_slug'] = $plugin['slug'];
                       }

                        $this->inactive_plugins[] = $plugin;
                    }

                }

            }


        }

        return $this->inactive_plugins;

    }

    public function run(){

        if(wp_create_nonce('installer_ep_form') != $_POST['nonce']) die('Invalid nonce');

        $import_class_file = WP_Installer()->plugin_path() . '/' . basename(dirname(__FILE__)) . '/import/' . self::PLUGINS_REPOSITORIES . '/toolset_import.php';
        if(file_exists($import_class_file)){
            require_once $import_class_file;
            new WP_Toolset_Import();
        }

        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : false;

        $return = array('continue' => 1);

        if(empty($_POST['repeat'])){ //run just once for each step
            do_action('installer_ep_before_' . $step);
        }

        switch($step){

            case 'install':
                $missing_plugins = $this->get_missing_plugins();

                $result = $this->install_plugins($missing_plugins);
                if(is_wp_error($result)){
                    $return['continue'] = 0;
                    $return['error'] = $result->get_error_message();
                }else{
                    if(is_numeric($result) && $result > 0){
                        $return['repeat'] = $step;
                    }
                }
                break;

            case 'activate':
                $inactive_plugins = $this->get_inactive_plugins();
                $result = $this->activate_plugins($inactive_plugins);
                if(is_wp_error($result)){
                    $return['continue'] = 0;
                    $return['error'] = $result->get_error_message();
                }else{
                    if(is_numeric($result) && $result > 0){
                        $return['repeat'] = $step;
                    }
                }
                break;

            case 'configure':

                //$this->settings['completed_items']['configure'] = 1;
                //$this->save_settings();

                break;

            case 'sample_content':

                //$this->settings['completed_items']['sample_content'] = 1;
                //$this->save_settings();

                break;

            case 'default_settings':

                //$this->settings['completed_items']['default_settings'] = 1;
                //$this->save_settings();

                break;

            case 'layout_content':

                //$this->settings['completed_items']['layout_content'] = 1;
                //$this->save_settings();

                break;

            case '__finalize__':

                $return['continue'] = 0;

                if(!$this->is_theme_update()){
                    $this->settings['setup_complete'] = wp_get_theme()->get( 'Version' );
                    $this->save_settings();
                }

                do_action('installer_ep_plugins_import_complete');
                break;

            default:

                die('Unknown step');


        }

        if(empty($return['repeat'])){ //run just once for each step
            do_action('installer_ep_after_' . $step);
        }

        echo json_encode($return);
        exit;
    }

    public function postpone_setup(){
        if(wp_create_nonce('installer_ep_form') != $_POST['nonce']) die('Invalid nonce');

        $this->settings['postponed'] = 1;
        $this->save_settings();

        echo json_encode(array());
        exit;

    }

    public function resume_setup(){
        if(wp_create_nonce('installer_ep_form') != $_POST['nonce']) die('Invalid nonce');

        $this->settings['postponed'] = 0;
        $this->save_settings();

        echo json_encode(array());
        exit;

    }

    public function is_setup_complete(){

        $setup_complete =!empty($this->settings['setup_complete']);

        return $setup_complete;
    }

    public function is_add_missing_plugins(){
        return $this->is_theme_update() && $this->get_missing_plugins() || (isset($_POST['action']) && $_POST['action'] == 'installer_ep_run');
    }

    public function is_theme_update(){
        $is_update = false;

        if($this->is_setup_complete()){
            $current_theme_version  = wp_get_theme()->get( 'Version' );
            $imported_theme_version = $this->settings['setup_complete'];

            $is_update = version_compare($current_theme_version, $imported_theme_version, '>');
        }

        return $is_update;
    }

    public function save_current_version(){

        $current_theme_version  = wp_get_theme()->get( 'Version' );
        $this->settings['setup_complete'] = $current_theme_version;
        $this->save_settings();
        return $current_theme_version;
    }

    private function install_plugins($plugins_repos_list){

        $plugins_left = 0;
        foreach($plugins_repos_list as $key => $plugins) {
            $plugins_left += count($plugins);
        }

        foreach($plugins_repos_list as $key => $plugins){
            foreach($plugins as $plugin){
                if($plugin['outdated']){ //upgrade routine

                    $real_slug = $plugin['slug'];
                    if(isset($plugin['format']) && $plugin['format'] != 'standard'){
                        $real_slug .= '-' . $plugin['format'];
                    }

                    $plugin_wp_id = $this->get_plugin_id($real_slug, $plugin['name']);

                    delete_plugins(array($plugin_wp_id));
                    $ret = WP_Installer()->download_plugin($plugin['slug'], $plugin['url']);

                    if (!$ret || is_wp_error($ret)) {

                        if (!$ret) {
                            $error_message = sprintf(__('Failed to upgrade %s.', 'installer'), $plugin['name']);
                            $error_message .= '&nbsp;&nbsp;' . sprintf(__('Please %sreload the page%s and try again.', 'installer'), '<a href="#" onclick="location.reload();return false;">', '</a>');
                            $result = new WP_Error('500', $error_message, $ret);
                        } else {
                            $result = $ret;
                        }
                        return $result;

                    } else {

                        // one at the time
                        return $plugins_left - 1;

                    }


                }else { //install routine

                    $ret = WP_Installer()->download_plugin($plugin['slug'], $plugin['url']);
                    if (!$ret || is_wp_error($ret)) {
                        if (!$ret) {
                            $error_message = sprintf(__('Failed to download %s.', 'installer'), $plugin['name']);
                            $error_message .= '&nbsp;&nbsp;' . sprintf(__('Please %sreload the page%s and try again.', 'installer'), '<a href="#" onclick="location.reload();return false;">', '</a>');
                            $result = new WP_Error('500', $error_message, $ret);
                        } else {
                            $result = $ret;
                        }
                        return $result;

                    } else {

                        // one at the time
                        return $plugins_left - 1;

                    }

                }

            }

        }


    }

    private function activate_plugins($plugins_list){

        $plugins_left = count($plugins_list);

        foreach($plugins_list as $plugin){

            //prevent redirects
            add_filter('wp_redirect', '__return_false', 10000);

            $plugin_wp_id = $this->get_plugin_id($plugin['real_slug'], $plugin['name']);

            $ret = activate_plugin($plugin_wp_id);

            if(!is_null($ret) && (!$ret || is_wp_error($ret))){
                if(!$ret){
                    $error_message = sprintf(__('Failed to download %s', 'installer'), $plugin['name']);
                    $error_message .= '&nbsp;&nbsp;' . sprintf(__('Please %sreload the page%s and try again.', 'installer'), '<a href="#" onclick="location.reload();return false;">', '</a>');
                    $result = new WP_Error('500', $error_message, $ret);
                }else{
                    $result = $ret;
                }
                return $ret;

            }else{
                // one at the time
                return $plugins_left - 1;

            }
        }


    }

    public function read_config($config_file){

        $transient_key = 'epconfigxml_' . md5($config_file . filemtime($config_file));
        $config = get_transient( $transient_key );

        if($config === false) {

            $repositories = array();

            $repositories_xml = simplexml_load_file($config_file);

            $array = json_decode(json_encode($repositories_xml), true);

            $repositories_arr = isset($array['repositories']['repository'][0]) ? $array['repositories']['repository'] : array($array['repositories']['repository']);

            foreach ($repositories_arr as $r) {
                $r['plugins'] = isset($r['plugins']['plugin'][0]) ? $r['plugins']['plugin'] : array($r['plugins']['plugin']);

                $repositories[$r['id']] = $r;
            }

            $config['repositories'] = $repositories;
            $config['name'] = $array['name'];

            set_transient( $transient_key, $config, 86400 );
        }

        return $config;

    }

    public function get_missing_deps(){
        return $this->missing;
    }

    // get configuration for specific theme
    public function get_config($theme_name){
        $config = false;

        foreach ($this->config_files_array as $config_file){

            $config = $this->read_config($config_file);
            if($config['name'] == $theme_name){
                break;
            }

        }

        return $config;
    }

    public function setup_notice(){
    	include dirname(__FILE__) . '/setup-menu.php';
    }

    public function is_plugin_installed($slug, $name, $version = false, $compare = '='){

        $is = false;
        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){
            $wp_plugin_slug = dirname($plugin_id);
            if($wp_plugin_slug == $slug || $name == $plugin_data['Name'] || $name == $plugin_data['Title']){
                if($version !== false ){
                    if(version_compare($plugin_data['Version'], $version, $compare)){
                        $is = true;
                    }
                }else{
                    $is = true;

                }
                break;

            }
        }

        return $is;

    }

    public function is_plugin_active($slug, $name){

        $is = false;
        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){
            $wp_plugin_slug = dirname($plugin_id);

            if(($wp_plugin_slug == $slug || $plugin_data['Name'] == $name || $plugin_data['Title'] == $name) && is_plugin_active($plugin_id)){
                $is = true;
                break;

            }
        }

        return $is;

    }

    public function get_plugin_id($slug, $name){

        $plugin_wp_id = false;

        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){
            $wp_plugin_slug = dirname($plugin_id);
            if($wp_plugin_slug == $slug || $plugin_data['Name'] == $name || $plugin_data['Title'] == $name ){
                $plugin_wp_id = $plugin_id;
                break;
            }
        }

        return $plugin_wp_id;

    }

    public function plugins_upgrade_check($update_plugins){

        $config_files = $this->get_config_files_array();

        foreach($config_files as $config_file) {

            $config = $this->read_config($config_file);
            $config_arr_key = md5($config_file) . '|' . $config['name'];

            foreach($config['repositories'] as $repository_id => $repository){

                $downloads = $this->get_repository_downloads($repository_id, $config);

                foreach($repository['plugins'] as $plugin){

                    if(!isset($downloads[$plugin['name']])) continue;

                    $real_slug = $plugin['name'];
                    if(isset($plugin['format']) && $plugin['format'] != 'standard'){
                        $real_slug .= '-' . $plugin['format'];
                    }

                    $plugin_full_name = $this->get_plugin_full_name($repository_id, $plugin['name'], $config);

                    $plugin_wp_id = $this->get_plugin_id($real_slug, $plugin_full_name);
                    if($plugin_wp_id){

                        $latest_version = WP_Installer()->get_plugin_repository_version($repository_id, $plugin['name']);    

                        $response = new stdClass();
                        $response->id = 0;
                        $response->slug = $real_slug;
                        $response->plugin = $plugin_wp_id;
                        $response->new_version = $latest_version;
                        $response->upgrade_notice = '';
                        $response->url = $this->get_plugin_download_url($repository_id, $plugin['name'], $config);
                        $response->package = $this->get_plugin_download_url($repository_id, $plugin['name'], $config);
                        $update_plugins->checked[$plugin_wp_id]  = $latest_version;
                        $update_plugins->response[$plugin_wp_id] = $response;


                    }

                }

            }

        }
        
        return $update_plugins;
    }

    public function get_repository_downloads($repository_id, $config){
    
    	if(!isset($this->repository_downloads[md5(serialize($config))][$repository_id])) {
    
    		$downloads = array();
    		$installer_settings = WP_Installer()->get_settings();
    
    		if (isset($installer_settings['repositories'][$repository_id])) {
    
    			foreach ($installer_settings['repositories'][$repository_id]['data']['packages'] as $package) {
    
    				foreach ($package['products'] as $product) {
    
    					$available_in_installer = WP_Installer()->is_product_available_for_download($product['name'], $repository_id);    
    					
    					/** Installer 1.7-New format for the products data file. */
    					/** Add backward compatibility to users with Installer version before 1.7 */
    					
    					if (empty($product['downloads'])) {
    						//Using new Installer data format
    						$product['downloads']=$installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'];
    					}
    					foreach ($product['downloads'] as $download) {
    						if (!isset($downloads[$download['slug']]) || (empty($d['_installer_download_url']) && $available_in_installer)) {
    
    							$d['name']              = $download['name'];
    							$d['slug']              = $download['slug'];
    							$d['version']           = $download['version'];
    							$d['date']              = $download['date'];
    							$d['_installer_url']    = $available_in_installer;
    
    							/*
    							 $format = 'standard';
    							 foreach($config['repositories'][$repository_id]['plugins'] as $p){
    							 if($p['name'] == $d['slug']){
    							 if(isset($p['format'])){
    							 $format = $p['format'];
    							 }
    							 break;
    							 }
    							 }
    							 */
    
    							//case of valid subscription
    							if($available_in_installer){
    								$d['url'] = WP_Installer()->append_site_key_to_download_url($download['url'], WP_Installer()->get_site_key($repository_id), $repository_id);
    
    							}else{
    
    								$query_args = array(
    										'theme_key'  => $config['repositories'][$repository_id]['key'],
    										'theme_name' => urlencode($config['name'])
    								);
    								$d['url'] = add_query_arg( $query_args, $download['url']);
    							}
    
    
    							$downloads[$d['slug']] = $d;
    						}
    
    					}
    
    				}
    
    			}
    
    		}
    
    		$this->repository_downloads[md5(serialize($config))][$repository_id] = $downloads;
    
    	}
    
    	return $this->repository_downloads[md5(serialize($config))][$repository_id];
    
    }

    public function get_plugin_download_url($repository_id, $slug, $config){

        $downloads = $this->get_repository_downloads($repository_id, $config);

        return isset($downloads[$slug]) ? $downloads[$slug]['url'] : false;

    }

    public function get_plugin_full_name($repository_id, $slug, $config){

        $downloads = $this->get_repository_downloads($repository_id, $config);

        return isset($downloads[$slug]) ? $downloads[$slug]['name'] : false;

    }

}