<div class="ddl-dialogs-container">

	<div class="ddl-dialog" id="ddl-row-edit">

		<div class="ddl-dialog-header">
			<h2 class="js-dialog-edit-title"><?php _e('Edit Row', 'ddl-layouts'); ?></h2>
			<h2 class="js-dialog-add-title"><?php _e('Add Row', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>

		<div class="ddl-dialog-content">

			<?php $unique_id = uniqid(); ?>
			<div class="js-popup-tabs">


				<div class="ddl-dialog-content-main ddl-popup-tab" id="js-row-basic-settings-<?php echo $unique_id; ?>">
					<input type="hidden" name="ddl-row-edit-row-name" id="ddl-row-edit-row-name">

					<ul class="ddl-form js-ddl-form-row">
						<!--	<li>
							<label for="ddl-row-edit-row-name"><?php _e('Row name:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
							<input type="text" name="ddl-row-edit-row-name" id="ddl-row-edit-row-name">
						</li>
						<li>
							<label for="ddl-row-edit-layout-type" for="ddl-row-edit-layout-type"><?php _e('Row layout type:', 'ddl-layouts'); ?></label>
							<select id="ddl-row-edit-layout-type" name="ddl-row-edit-layout-type">
								<option value="fixed"><?php _e('Fixed', 'ddl-layouts'); ?></option>
								<option value="fluid"><?php _e('Fluid', 'ddl-layouts'); ?></option>
							</select>

						</li> -->

						<li><input type="hidden" name="ddl-row-edit-row-name" id="ddl-row-edit-row-name"></li>

						<!--<li class="toolset-alert toolset-alert-info js-only-fluid-message">
							<?php //_e('Only fluid rows are allowed here because the parent row or layout are fluid.', 'ddl-layouts'); ?>
						</li>-->

						<li class="js-preset-layouts-rows row-not-render-message" id="js-row-not-render-message">
						        <p class="toolset-alert toolset-alert-info">
                                    <?php _e('This cell in itself will not have a typical row structure on the front-end. It will directly output content of the child layout which is why you can not add classes and IDs to it. To add custom styling, edit the child layout instead and add custom classes and IDs there. For more information, see <a href="http://wp-types.com/documentation/user-guides/hierarchical-layouts?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=child-layout-cell&utm_term=help-link" target="_blank">Using layout hierarchy for quick development</a>.', 'ddl-layouts');?>
                                </p>
						</li>

						<li class="js-preset-layouts-rows" id="js-row-edit-mode">
							<label for="ddl-row-edit-mode"><?php _e('Row type:', 'ddl-layouts'); ?></label>

							<?php // previews for row types ?>
							<ul class="presets-list row-types fields-group">
									<?php do_action('wpddl_render-row-modes-in-dialog'); ?>
							</ul>

							<p class="desc">
								<a class="fieldset-inputs" href="<?php echo WPDLL_LEARN_ABOUT_ROW_MODES; ?>" target="_blank">
									<?php _e('Learn about how rows can be displayed in different ways', 'ddl-layouts'); ?> &raquo;
								</a>
							</p>

						</li>
					</ul>



				</div> <!-- .ddl-popup-tab -->
				<?php do_action('ddl-before_row_markup_controls'); ?>
				<div class="ddl-popup-tab ddl-markup-controls"" id="js-row-design-<?php echo $unique_id; ?>">
					<?php
						$dialog_type = 'row';
						do_action('ddl-before_row_default_edit_fields');
						include 'cell_display_settings_tab.tpl.php';
						do_action('ddl-after_row_default_edit_fields');
					?>
				</div><!-- .ddl-popup-tab -->

			</div> <!-- .js-popup-tabs -->

		</div> <!-- .ddl-dialog-content -->

		<div class="ddl-dialog-footer">
			<?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
			<button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
		<!--	<button data-close="no" class="button button-primary js-row-dialog-edit-save js-save-dialog-settings"><?php _e('Save','ddl-layouts') ?></button> -->
			<button data-close="yes" class="button button-primary js-row-dialog-edit-save js-save-dialog-settings"><?php _e('Save and close','ddl-layouts') ?></button>
		</div>

	</div>

</div>