<div>
    <div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p>
                <?php _e( "When loading default layouts for current theme integration a bunch of layouts will be loaded for the major resources of your theme and assigned to them automatically.", 'ddl-layouts' ); ?>
            </p>

            <p>
                <?php if( !get_option( $this->message->get_integration_option_string() ) || WPDD_Utils::at_least_one_layout_exists() === false ):?>
                    <a href="#" class="button button-primary button-primary-toolset js-ddl-layouts-loader-button" target="_blank" data-settings="yes"><?php _e('Create Layouts', 'ddl-layouts');?></a>
                <?php else:?>
                    <button href="#" class="button button-secondary" disabled="disabled"><?php _e('Create Layouts', 'ddl-layouts');?></button>
                <?php endif;?>
                <span class="js-wpv-messages js-upload-layouts-message"></span>
            </p>

        </div>
    </div>
</div>
