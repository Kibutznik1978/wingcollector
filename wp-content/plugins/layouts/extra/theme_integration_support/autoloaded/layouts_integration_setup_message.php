<?php
/**
 * Created by PhpStorm.
 * User: Riccardo Strobbia
 * Date: 25/01/16
 * Time: 12:16
 */

class WPDDL_Layouts_Integration_Setup_Message{
    protected $dismiss_notice_string;
    protected $integration_option_name;
    protected $message_update;

    public function __construct()
    {
        $this->setup_strings();
        add_action('wp_ajax_'.$this->dismiss_notice_string, array($this, 'wpddl_dismissible_notice') );
        add_action('admin_print_scripts', array($this, 'admin_print_scripts') );
        add_action('ddl_dismiss_dismissabe_notice', array(&$this, 'dismiss_notice'));
    }



    protected function setup_strings(){
        $this->dismiss_notice_string = 'wpddl-integration-dismissible-notice';
        $this->integration_option_name = 'wpddl_layouts_integration_notice_shown';

        global $pagenow;

        if( $pagenow === 'admin.php' ){
            $this->message_update = '<div class="ddl-integration-default-layouts notice notice-success is-dismissible wpddl-dismissible-notice inline" data-option="%s"><p><i class="icon-layouts-logo ont-color-orange ont-icon-24"></i><span class="text-span">%s</span></p></div>';

        } else {
            $this->message_update = '<div class="ddl-integration-default-layouts notice notice-success is-dismissible wpddl-dismissible-notice" data-option="%s"><p><i class="icon-layouts-logo ont-color-orange ont-icon-24"></i><span class="text-span">%s</span></p></div>';

        }
    }

    public function dismiss_notices_script(){
        ob_start();?>
        <script type="text/javascript">
            jQuery(function ($) {
                var wpdd_dismissible_integration_notice_nonce = "<?php echo wp_create_nonce( $this->dismiss_notice_string ); ?>";

                _.defer(function ($) {
                    $('.wpddl-dismissible-notice').each(function () {
                        var $button = $('button.notice-dismiss', $(this)), option = $(this).data('option');
                        $button.on('click', function (event) {
                            var data = {
                                'wpddl-integration-dismissible-notice': wpdd_dismissible_integration_notice_nonce,
                                action: "<?php echo $this->dismiss_notice_string; ?>",
                                option: option,
                                option_value: 1
                            };
                            $.post(ajaxurl, data, function ( response ) {
                                    if( response && response.Data && response.Data.error ){
                                        console.info( 'Error', response.Data.error );
                                    }
                                }, 'json')
                                .fail(function(xhr, error){
                                    console.error( arguments );
                                });
                        })

                    });
                }, $);
            });
        </script>
        <?php
        echo ob_get_clean();
    }

    public function admin_print_scripts(){
        global $pagenow, $wpddlayout;
        $wpddlayout->enqueue_styles('toolset-notifications-css');
        ob_start();
        ?>
            <style type="text/css" media="screen">
                div.wpddl-dismissible-notice{
                    margin-left:0px;
                    margin-top:10px;
                }
                .wpddl-dismissible-notice .text-span{
                    vertical-align: -3px;
                    margin-left:5px;
                }
                .wpddl-dismissible-notice .button-primary-toolset{margin-left:5px;}
            </style>
        <?php
        echo ob_get_clean();
    }

    public function dismissible_notice($option, $message)
    {
        printf( $this->message_update, $option, $message );

        add_action('admin_footer', array($this, 'dismiss_notices_script') );
    }

    public function wpddl_dismissible_notice(){
        if( $_POST && wp_verify_nonce($_POST[$this->dismiss_notice_string], $this->dismiss_notice_string) ){

            $this->dismiss_notice();

            die( wp_json_encode( array( 'Data' => array('message' => $_POST['option_value'] ) ) ) );
        } else {
            die( wp_json_encode( array( 'Data' => array( 'error' => __("Nonce problem", 'ddl-layouts') ) ) ) );
        }
    }

    public function dismiss_notice(){
        update_option( $this->dismiss_notice_string, 'yes');
    }

    public function integration_message(){
        $theme = wp_get_theme();
        $this->dismissible_notice(
            $this->integration_option_name,
            sprintf( __('Do you want to create default layouts for <strong>%s</strong>? If you skip this action now you can do this later from Layouts settings page. <a href="#" class="button button-primary button-primary-toolset js-ddl-layouts-loader-button" target="_blank">Create Layouts</a>', 'ddl-layouts'), $theme->get('Name') )
        );
    }

    public function do_notice(){
        if( get_option( $this->get_integration_option_string() ) ) {
            return;
        }
        if( !get_option( $this->dismiss_notice_string )  ){
            add_action( 'admin_notices', array( $this, 'integration_message' ) );
        }
    }

    protected function get_current_theme_slug() {
        $theme = wp_get_theme();
        $name = strtolower( $theme->get( 'Name' ) );
        return str_replace(' ', '_', $name);
    }

    public function get_integration_option_string(){
        return $this->get_current_theme_slug() . '_' . $this->integration_option_name;
    }
}