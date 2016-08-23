<?php

/* Usage 
* toolset_import_init( array('types'=>true, 'views'=>true, 'cred'=>true, 'layouts'=>true, 'posts' => false) );
*
*/

function toolset_import_init( ){    
    
    $import = new WP_Toolset_Import();
    $import->start_import();
}
/*
 * Toolset Importer class
 * Usage
 * $import = new WP_Toolset_Import();
 * $import->set_import_plugins( ); //Default false
 * $import->start_import();
 */
class WP_Toolset_Import {
    public $import_types = false;
    public $import_views = false;
    public $import_cred = false;
    public $import_layouts = false;
    public $import_posts = false;
    public $import_settings = false;
    public $import_media = false;
    public $import_path = '';
    public $upload_status = true;
    
   
    
    function __construct(){        
        $this->import_path = get_stylesheet_directory() . '/toolset_import';
        $this->set_import_plugins();

        //installer_ep_update_data
        if( WP_Installer()->installer_embedded_plugins->is_theme_update() ){
            add_action( 'installer_ep_update_data', array($this, 'process_update_toolset_config'), 10, 1 );
        }else{        
            if ( get_option('toolset_theme_options_uploaded') == 'yes' ) {
                $this->upload_status = false;
            }
            add_action('installer_ep_plugins_import_complete', array($this, 'toolset_importer_save_status'));
            
            add_action('installer_ep_after_configure', array($this, 'process_import_toolset_config'));
            add_action('installer_ep_after_layout_content', array($this, 'process_import_layouts'));
            add_action('installer_ep_after_sample_content', array($this, 'process_import_posts'));
            add_action('installer_ep_after_default_settings', array($this, 'process_import_settings'));
        }
    }

    /**
     * Collect data and route them t single plugins methods
     * @param $args
     */
    public function process_update_toolset_config( $args ){
        $plugins = array( 'Types', 'Layouts', 'Views', 'CRED' );
        for ( $i = 0, $plugins_count = count($plugins); $i < $plugins_count; $i++ ){
            if ( isset( $args[$plugins[$i]] ) ){
                $current = $args[$plugins[$i]];
                $import_args = array();
                for ( $j = 0, $count_items = count($current); $j < $count_items; $j++ ){
                    if ( 'Types' == $plugins[$i]){
                        if ( !isset( $import_args[$current[$j]['do']][$current[$j]['post_type']] ) ) {
                            $import_args[$current[$j]['do']][$current[$j]['post_type']] = array();
                        }
                        $import_args[$current[$j]['do']][$current[$j]['post_type']][] = $current[$j]['post_name'];
                    } else {
                        $import_args[$current[$j]['do']][] = $current[$j]['post_name'];
                    }
                }

                if ( $plugins[$i] == 'Types' ){
                     $this->import_types( $import_args );
                }

                if ( $plugins[$i] == 'CRED' ){
                    $this->import_cred( $import_args );
                }
                
                if ( $plugins[$i] == 'Views' ){
                    $this->import_views( $import_args );
                }

                if ( $plugins[$i] == 'Layouts' ){
                    $this->import_layouts( $import_args );
                }
                
            }  
        }
    }

    public function start_import(){
        if ( get_option('toolset_theme_options_uploaded') ) {
            return;
        }
        
        if ( is_admin() ){
            //Save this for use outside Installer
            //add_action('init', array($this, 'process_import'), 99 );
            $this->process_import();
        }
    }
    
    public function toolset_importer_save_status(){
        update_option('toolset_theme_options_uploaded', 'yes');
    }
    
    public function toolset_importer_check_status(){
        if ( $this->upload_status ) {
            return true;
        }else{
            return false;
        }
    }
    
    public function process_import( $current_plugin = '' ){
         $out = array( 'status'=>false, 'message'=>'' );
         
         if ( $this->import_types && $current_plugin == 'types' ){
             $out = $this->import_types();
         }
         if ( $this->import_views && $current_plugin == 'views' ){
             $out = $this->import_views();
         }
         if ( $this->import_cred && $current_plugin == 'cred' ){
             $out = $this->import_cred();
         }         
         if ( $this->import_posts && $current_plugin == 'posts' ){
            $out = $this->import_posts();
         }
         if ( $this->import_settings && $current_plugin == 'settings' ){
             $out = $this->import_settings();
         }
         if ( $this->import_layouts && $current_plugin == 'layouts' ){
             $out = $this->import_layouts();
         }

         return $out;
    }
    
    // Import Toolset pllugins configuration
    public function process_import_toolset_config(){
         $out = true;
         if ( $this->import_types ){
             $out = $this->import_types();
         }
         if ( $this->import_views ){
             $out = $this->import_views();
         }
         if ( $this->import_cred ){
             $out = $this->import_cred();
         }
         return $out;
    }
    
    //Import Layouts
    public function process_import_layouts(){
         $out = true;
         if ( $this->import_layouts){
             $out = $this->import_layouts();
         }
         return $out;
    }
    
    //Import Posts
    public function process_import_posts(){
         $out = true;
         if ( $this->import_posts ){
            $out = $this->import_posts();
         }
         return $out;
    }
    
    //Import Settings
    public function process_import_settings(){
         $out = true;
         if ( $this->import_settings ){
             $out = $this->import_settings();
         }
         return $out;
    }
    
    /*
    * Check if we need import
    */
    public function set_import_plugins ( $plugins = array() ){
       
        
        if ( file_exists( $this->import_path . '/types/settings.xml' ) && (function_exists('wpcf_embedded_load_or_deactivate') || defined('WPCF_VERSION')) ){
            $this->import_types = true;
        }
        
        if ( file_exists( $this->import_path . '/views/settings.xml' ) && class_exists('WP_Views') && function_exists('wpv_admin_import_data') ){
            $this->import_views = true;
        }
        
        if ( file_exists( $this->import_path . '/cred/settings.xml' ) && class_exists('CRED_Loader') ){
            $this->import_cred = true;
        }
        
        if ( file_exists( $this->import_path . '/layouts' ) && class_exists( 'WPDD_Layouts' ) ){
            $this->import_layouts = true;
        }
        
        if ( file_exists( $this->import_path . '/posts/settings.xml' ) ){
            $this->import_posts = true;
            if ( file_exists( $this->import_path . '/media' ) ){
                 $this->import_media = true;
            }
        }
        
        if ( file_exists( $this->import_path . '/settings/settings.json' ) ){
            $this->import_settings = true;
        }
        
    }
    
    private function import_settings(){
        $settings_file = get_stylesheet_directory() . '/toolset_import/settings/settings.json';
        if ( file_exists($settings_file) ){
            $data = join('', file($settings_file));
            $data = json_decode($data);
            if ( isset($data->page_on_front) || isset($data->page_for_posts) ){
                update_option('show_on_front','page');
                if ( isset($data->page_on_front) ){;
                    $import_data = $data->page_on_front;
                    $import_page = get_page_by_path($import_data[1], OBJECT, 'page');
                    if ( isset($import_page->ID) ){
                        update_option('page_on_front', $import_page->ID);
                    }
                }
                if ( isset($data->page_for_posts) ){;
                    $import_data = $data->page_for_posts;
                    $import_page = get_page_by_path($import_data[1], OBJECT, 'page');
                    if ( isset($import_page->ID) ){
                        update_option('page_for_posts', $import_page->ID);
                    }
                }
            }
        }
        return true;
    }
    
    private function import_types( $import_args = array() ){
        add_filter('wpcf_admin_message_store', '__return_false');
        $_POST['overwrite-groups'] = 1;
        $_POST['overwrite-fields'] = 1;
        $_POST['overwrite-types'] = 1;
        $_POST['overwrite-tax'] = 1;
        $_POST['post_relationship'] = 1;
        $types_file = get_stylesheet_directory() . '/toolset_import/types/settings.xml';
        if ( file_exists($types_file) && defined('WPCF_EMBEDDED_INC_ABSPATH') ){
            $data = join('', file($types_file));
            require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
            require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
            
            $args = array();
            if ( isset($import_args['overwrite']) ){
                $args['force_import_post_name'] = $import_args['overwrite'];
            }
            if ( isset($import_args['skip']) ){
                $args['force_skip_post_name'] = $import_args['skip'];
            }
            if ( isset($import_args['duplicate']) ){
                $args['force_duplicate_post_name'] = $import_args['duplicate'];
            }
            
            $success = wpcf_admin_import_data($data, false, 'wpvdemo', $args );
            return true;
        }
        return false;
    }
    
    private function import_views( $import_args = array() ){
        global $WP_Views;
        $wpv_theme_import = get_stylesheet_directory() . '/toolset_import/views/settings.php';
        $wpv_theme_import_xml = get_stylesheet_directory() . '/toolset_import/views/settings.xml';
        if ( file_exists($wpv_theme_import_xml) ){
            $args = array(
                'import-file' => $wpv_theme_import_xml,
                'views-overwrite' => true,
                'view-templates-overwrite' => true,
                'view-settings-overwrite' => true
            );
            if ( isset($import_args['overwrite']) ){
                $args['force_import_post_name'] = $import_args['overwrite'];
            }
            if ( isset($import_args['skip']) ){
                $args['force_skip_post_name'] = $import_args['skip'];
            }
            if ( isset($import_args['duplicate']) ){
                $args['force_duplicate_post_name'] = $import_args['duplicate'];
            }
            $import_errors = wpv_admin_import_data( $args );
            return true;
        }
        return false;
    }
    
    private function import_cred( $import_args = array() ){
        $cred_file = get_stylesheet_directory() . '/toolset_import/cred/settings.xml';
        if ( file_exists($cred_file) ){
            $data = join('', file($cred_file));
            $args = array(
                'overwrite_forms' => 1,
                'verwrite_custom_fields' => 1
            );
            if ( isset($import_args['overwrite']) ){
                $args['force_overwrite_post_name'] = $import_args['overwrite'];
            }
            if ( isset($import_args['skip']) ){
                $args['force_skip_post_name'] = $import_args['skip'];
            }
            if ( isset($import_args['duplicate']) ){
                $args['force_duplicate_post_name'] = $import_args['duplicate'];
            }
            $result = cred_import_xml_from_string( $data, $args );
            return true;
        }
        return false;
    }

    /**
     * @param null $import_args
     * @return bool
     */
    private function import_layouts( $import_args = null ) {
        if( defined('WPDDL_ABSPATH') === false ){
            return false;
        }
        require_once WPDDL_ABSPATH . '/ddl-theme.php';
        $layouts_dir = get_stylesheet_directory() . '/toolset_import/layouts';
        if ( file_exists($layouts_dir) ){
            if( null === $import_args  ){
                ddl_import_layouts_from_theme_dir( $layouts_dir );
            } else {
                if( function_exists('ddl_update_theme_layouts') ){
                    ddl_update_theme_layouts( $layouts_dir, $import_args );
                } else{
                    throw new Exception( __( sprintf("Layout %s does not support this functionality. Please update Layouts to a newer version and try again.", WPDDL_VERSION ), 'ddl-layouts') );
                }
            }
            return true;
        }
        return false;
    }
    
    private function import_posts(){
        ob_start();  
        define('WP_LOAD_IMPORTERS', true);
        require_once ABSPATH . 'wp-admin/includes/post.php';
        require_once ABSPATH . 'wp-admin/includes/comment.php';
        require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        remove_action('admin_init', 'wordpress_importer_init', 10, 1);
        require_once dirname(__FILE__) . '/wordpress-importer/wordpress-importer.php';
        $import = new WP_Lions_Import();
        $import->fetch_attachments = true;
        $file = get_stylesheet_directory() . '/toolset_import/posts/settings.xml';
        if ( file_exists($file) ){
            $_GET['step'] = 2;
            $import->dispatch($file); 
            $data = ob_get_contents();
        }
        ob_end_clean();
        return true;
    }

}