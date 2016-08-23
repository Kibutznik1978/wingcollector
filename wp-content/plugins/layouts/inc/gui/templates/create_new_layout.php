<script type="application/javascript">
	var ddl_create_layout_error = '<?php echo esc_js( __('Failed to create the layout.', 'ddl-layouts') ); ?>';
</script>
<?php wp_nonce_field('wp_nonce_create_layout', 'wp_nonce_create_layout'); ?>
<input class="js-layout-new-redirect" name="layout_creation_redirect" type="hidden" value="<?php echo admin_url( 'admin.php?page=dd_layouts_edit&amp;layout_id='); ?>" />


<?php if ( isset( $_GET['new_layout'] ) && $_GET['new_layout'] == 'true'): ?>

	<script type="application/javascript">
		var ddl_layouts_create_new_layout_trigger = true;
	</script>

<?php endif; ?>

<script type="text/html" id="js-ddl-create-layout-for-post-types-selection">
    <div class="ddl-dialog-header">
        <h2><?php _e('Create new Layout', 'ddl-layouts');?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content ddl-create-layout-for-post-types">
		<p><input type="radio" name="create_layout_for_post_type" id="create_layout_for_post_type_one" value="one" checked /><label for="create_layout_for_post_type_one"><?php printf(__('Only for %s%s%s', 'ddl-layouts'), '<span class="semi-bold">','{{{ post_title }}}', '</span>'); ?></label></p>
        <p><input type="radio" name="create_layout_for_post_type" id="create_layout_for_post_type_all" value="all" /><label for="create_layout_for_post_type_all"><?php printf(__('For all posts in Post Type %s%s%s', 'ddl-layouts'), '<span class="semi-bold">','{{{ post_type_label }}}', '</span>'); ?></label></p>
        <# if( post_count > 0 ) {#> 
        <div class="ddl-extra-controls js-ddl-extra-controls hidden">
            <p class="indent-20"><input checked type="radio" name="create_layout_for_new_posts" id="create_layout_for_new_posts_all" value="all" /><label for="create_layout_for_new_posts_all"><?php _e('For already existing and new posts', 'ddl-layouts'); ?></label>
                <# if(assigned_count === 1) { #>
                    <span class="ont-color-red"><i class="fa fa-exclamation-triangle padding-right-4" aria-hidden="true"></i><?php printf(__('%s post uses another Layout', 'ddl-layouts'), '{{{ assigned_count }}}'); ?></span>
                    <# } else if(assigned_count > 1) { #>
                        <span class="ont-color-red"><i class="fa fa-exclamation-triangle padding-right-4" aria-hidden="true"></i><?php printf(__('%s posts use another Layout', 'ddl-layouts'), '{{{ assigned_count }}}'); ?></span>
                        <# }  #>
            </p>
            <p class="indent-20"><input type="radio" name="create_layout_for_new_posts" id="create_layout_for_new_posts_new" value="new" /><label for="create_layout_for_new_posts_new"><?php _e('Only for new posts', 'ddl-layouts'); ?></label></p>
        </div>
            <# } #>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button button-primary js-ddl-continue-to-layout-creation ddl-continue-to-layout-creation">
				<?php _e('Create', 'ddl-layouts'); ?></button>
        <button class="button js-edit-dialog-close close-change-use"><?php _e('Cancel', 'ddl-layouts'); ?></button>

    </div>
</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-ddl-create-layout-for-post-types-selection-wrap"></div>
</div>