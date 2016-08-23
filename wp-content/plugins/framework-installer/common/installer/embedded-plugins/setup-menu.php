<div class="updated" id="installer_ep_setup">

<?php foreach($this->get_required_plugins() as $key => $plugins): ?>
    <?php list($null, $name) = explode('|', $key); ?>
    <?php
    $plugin_names = array();
    foreach($plugins as $p){
        $plugin_names[] = $p['name'];
    }

    $m_plugin_names = array();
    $outdated_plugins = 0;
    $missing_plugins  = 0;

    if(empty($this->settings['completed_items']['install'])){
        $missing_plugins_array = $this->get_missing_plugins();
        foreach($missing_plugins_array as $t_key => $m_plugins){
            foreach($m_plugins as $m_plugin){
                $m_plugin_names[] = $m_plugin['name'];

                $missing_plugins++;
                if($m_plugin['outdated']){
                    $outdated_plugins++;
                }

            }
        }
    }

    if(empty($this->settings['completed_items']['activate   '])){
        $inactive_plugins = $this->get_inactive_plugins();
        $ua_plugin_names = array();
        foreach($inactive_plugins as $ua_plugin){
            $ua_plugin_names[] = $ua_plugin['name'];
        }
        $ua_plugin_names = array_diff($ua_plugin_names, $m_plugin_names);
    }

    $download_impossible = empty($this->settings['completed_items']['install']) && !WP_Installer()->is_uploading_allowed();

    ?>

    <p id="installer_ep_postponed_wrap1" <?php if(empty($this->settings['postponed'])): ?>style="display:none"<?php endif; ?>>
            <?php printf(__('%s is almost ready to be used.', 'installer'), $name); ?>
            <a href="#" id="installer_ep_postponed_resume"><?php _e('Resume setup', 'installer') ?>&nbsp;&raquo;</a>
    </p>

    <div id="installer_ep_form_wrap" <?php if(!empty($this->settings['postponed'])): ?>style="display:none"<?php endif; ?>>

        <?php if($this->is_add_missing_plugins()): ?>
            <h3><strong><?php printf(__('%s %s requires extra plugins', 'installer'), wp_get_theme( )->Name, wp_get_theme()->get( 'Version' ) ); ?></strong></h3>
        <?php else: ?>
            <h3><strong><?php printf(__('Complete %s setup', 'installer'), $name); ?></strong></h3>
        <?php endif; ?>

        <?php printf(__('%s theme uses %s technology. To complete its setup, %s needs to load several items.', 'installer'), $name, ucfirst($plugins[0]['repository_id']), $name); ?>

        <form name="installer_ep_form" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('installer_ep_form') ?>" />
            <ul>
                <li <?php if(!empty($this->settings['completed_items']['install'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['install'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="install" value="1" checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php if($this->is_add_missing_plugins()): ?>
                        <?php printf(__('Install missing %s plugins required by %s %s', 'installer'), self::PLUGINS_PACKAGE, wp_get_theme( )->Name, wp_get_theme()->get( 'Version' )); ?>
                        <?php elseif(empty($outdated_plugins)): ?>
                        <?php printf(__('Install %s plugins', 'installer'), self::PLUGINS_PACKAGE); ?>
                        <?php elseif($outdated_plugins == $missing_plugins): ?>
                        <?php printf(__('Update %s plugins', 'installer'), self::PLUGINS_PACKAGE);  ?>
                        <?php else: ?>
                        <?php printf(__('Install and update %s plugins', 'installer'), self::PLUGINS_PACKAGE);  ?>
                        <?php endif; ?>

                        <?php if($m_plugin_names): ?> (<?php echo join(', ', $m_plugin_names) ?>)<?php endif; ?>
                    </label>

                    <?php if($download_impossible): ?>
                        <p class="installer-status-error"><?php printf(__('Automatic downloading is not possible because WordPress cannot write into the plugins folder. %sHelp%s', 'installer'), '<a href="http://codex.wordpress.org/Changing_File_Permissions">', '</a>'); ?></p>
                    <?php endif; ?>

                </li>
                <li <?php if(!empty($this->settings['completed_items']['activate'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['activate'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="activate" value="1" checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php if($this->is_add_missing_plugins()): ?>
                        <?php printf(__('Activate new %s plugins', 'installer'), self::PLUGINS_PACKAGE)  ?>
                        <?php else: ?>
                        <?php printf(__('Activate %s', 'installer'), self::PLUGINS_PACKAGE)  ?>
                        <?php endif; ?>
                        <?php if($ua_plugin_names): ?> (<?php echo join(', ', $ua_plugin_names) ?>)<?php endif; ?>
                    </label>
                </li>
                <li <?php if(!empty($this->settings['completed_items']['configure'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['configure'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="configure" value="1"  checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php printf(__('Apply configuration for %s', 'installer'), self::PLUGINS_PACKAGE)  ?>
                    </label>
                </li>
                <?php if(installer_ep_is_fresh_wp_install()): ?>
                <li <?php if(!empty($this->settings['completed_items']['sample_content'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['sample_content'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="sample_content" value="1" checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php printf(__('Import sample %s content and media', 'installer'), $name)  ?>
                    </label>
                </li>
                <li <?php if(!empty($this->settings['completed_items']['default_settings'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['default_settings'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="default_settings" value="1" checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php _e('Update WordPress settings (homepage, etc.)', 'installer')  ?>
                    </label>
                </li>
                <li <?php if(!empty($this->settings['completed_items']['layout_content'])): ?>class="installer_ep_check_li"<?php endif; ?>>
                    <label>
                        <?php if(empty($this->settings['completed_items']['layout_content'])): ?>
                        <input type="checkbox" class="installer_ep_step"  name="layout_content" value="1" checked="checked" />&nbsp;
                        <?php endif; ?>
                        <?php printf(__('Import Layout ', 'installer'), $name)  ?>
                    </label>
                </li>
                <?php else: if(!$this->is_add_missing_plugins()): ?>
                <li>
                    <p class="installer-status-error"><?php printf(__("It's easiest to use %s with sample content. To receive sample content, please install %s on a fresh WordPress site, without existing content.", 'installer'), $name, $name); ?></p>
                </li>
                <?php endif; endif; ?>

            </ul>

            <p id="installer_ep_setup_actions">
                <input type="submit" class="button-primary" value="<?php esc_attr_e('Start', 'installer')  ?>&nbsp;&raquo;" data-alt-name="<?php esc_attr_e('Running', 'installer')  ?>&nbsp;&raquo;" <?php if($download_impossible): ?>disabled="disabled"<?php endif; ?> />
                &nbsp;
                <input type="button" id="installer_ep_postpone" class="button-secondary" value="<?php esc_attr_e('Postpone', 'installer') ?>" />
                <input type="hidden" id="installer_ep_instance" value="<?php echo esc_attr($this->installer_instance_key) ?>" />
            </p>

            <div id="installer_ep_setup_complete" style="display: none">
                <?php if(!$this->is_theme_update()): ?>
                    <p>
                        <?php printf(__('%s is ready for you to use.', 'installer'), $name); ?>
                        <a href="<?php echo apply_filters('installer_ep_installer_complete_link_url', admin_url('index.php')) ?>" class="button-primary"><?php
                            echo apply_filters('installer_ep_installer_complete_link_name', __('Enjoy!', 'installer') . ' &raquo;') ?></a>
                    </p>
                    <?php if(!installer_ep_is_fresh_wp_install()): ?>
                        <p class="installer-status-note"><?php printf(__("Please note that because this site included some content, %s did not install its own sample content. To receive %s with complete sample content and media, please start with a fresh WordPress site.", 'installer'), $name, $name); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>
                        <?php _e('The new plugins are now installed.', 'installer'); ?>
                        <a href="<?php echo apply_filters('installer_ep_installer_complete_link_url', admin_url('index.php')) ?>" class="button-primary"><?php
                            echo apply_filters('installer_ep_installer_complete_link_name', __('Continue!', 'installer') . ' &raquo;') ?></a>
                    </p>
                <?php endif; ?>

            </div>


        </form>
        <?php if(!WP_Installer()->is_uploading_allowed()): ?>
        <?php endif; ?>


    </div>


<?php endforeach; ?>


</div>

