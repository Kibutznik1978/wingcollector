<?php

WPV_Editor_Ordering::on_load();

class WPV_Editor_Ordering {
	
	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_query',		array( 'WPV_Editor_Ordering', 'wpv_screen_options_ordering' ), 30 );
		add_filter( 'wpv_screen_options_wpa_editor_section_query',	array( 'WPV_Editor_Ordering', 'wpv_screen_options_ordering' ), 30 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_query',			array( 'WPV_Editor_Ordering', 'wpv_editor_section_ordering' ), 30, 2 );
		add_action( 'wpv_action_wpa_editor_section_query',			array( 'WPV_Editor_Ordering', 'wpv_wpa_editor_section_ordering' ), 30, 3 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_sorting',					array( 'WPV_Editor_Ordering', 'wpv_update_sorting_callback' ) );
	}
	
	static function wpv_screen_options_ordering( $sections ) {
		$sections['ordering'] = array(
			'name'		=> __( 'Ordering', 'wpv-views' ),
			'disabled'	=> true,
		);
		return $sections;
	}
	
	static function wpv_editor_section_ordering( $view_settings, $view_id ) {
		$hide = '';
		if (
			isset( $view_settings['sections-show-hide'] ) 
			&& isset( $view_settings['sections-show-hide']['ordering'] ) 
			&& 'off' == $view_settings['sections-show-hide']['ordering']
		) {
			$hide = ' hidden';
		}
		$section_help_pointer	= WPV_Admin_Messages::edit_section_help_pointer( 'ordering' );
		$view_settings			= apply_filters( 'wpv_filter_wpv_get_sorting_defaults', $view_settings );
		?>
		<div class="wpv-setting-container wpv-settings-ordering js-wpv-settings-ordering<?php echo $hide; ?>">
			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Ordering', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip" 
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>" 
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<p class="wpv-settings-query-type-posts js-wpv-settings-posts-order"<?php echo ( $view_settings['query_type'][0] != 'posts' ) ? ' style="display: none;"' : ''; ?>>
					<label for="wpv-settings-orderby"><?php _e( 'Order by ', 'wpv-views' ) ?></label>
					<select id="wpv-settings-orderby" class="js-wpv-posts-orderby" name="_wpv_settings[orderby]" autocomplete="off" data-rand="<?php echo esc_attr( __('Pagination and random ordering do not work together and would produce unexpected results. Please disable pagination or random ordering.', 'wpv-views') ); ?>">
						<option value="post_date" <?php selected( $view_settings['orderby'], 'post_date' ); ?>><?php _e('Post date', 'wpv-views'); ?></option>
						<option value="post_title" <?php selected( $view_settings['orderby'], 'post_title' ); ?>><?php _e('Post title', 'wpv-views'); ?></option>
						<option value="ID" <?php selected( $view_settings['orderby'], 'ID' ); ?>><?php _e('Post ID', 'wpv-views'); ?></option>
						<option value="modified" <?php selected( $view_settings['orderby'], 'modified' ); ?>><?php _e('Last modified', 'wpv-views'); ?></option>
						<option value="menu_order" <?php selected( $view_settings['orderby'], 'menu_order' ); ?>><?php _e('Menu order', 'wpv-views'); ?></option>
						<option value="rand" <?php selected( $view_settings['orderby'], 'rand' ); ?>><?php _e('Random order', 'wpv-views'); ?></option>
						<?php
							$all_types_fields				= get_option( 'wpcf-fields', array() );
							$cf_keys						= apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
							$show_orderby_as				= false;
							$selected_orderby_field_type	= '';

							foreach ( $cf_keys as $key ) {
								$selected = ( $view_settings['orderby'] == "field-" . $key ) ? ' selected="selected"' : '';
								$show_orderby_as = ( ! empty( $selected ) ) ? true : $show_orderby_as;
								$option_text = "";
								$field_type = '';
								$data_field_type = "";

								if ( stripos( $key, 'wpcf-' ) === 0 )  {
									if (
										isset( $all_types_fields[substr( $key, 5 )] )
										&& isset( $all_types_fields[substr( $key, 5 )]['name'] )
									) {
										$option_text = sprintf(__('Field - %s', 'wpv-views'), $all_types_fields[substr( $key, 5 )]['name']);
										$field_type = $all_types_fields[substr( $key, 5 )]['type'];
										$selected_orderby_field_type = ( ! empty( $selected ) ) ? $field_type : $selected_orderby_field_type;
										$data_field_type = ' data-field-type="' . esc_attr( $field_type ) . '"';
									} else {
										$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
									}
								} else {
									$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
								}
								
								if ( ! in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
									$option = '<option value="field-' . esc_attr( $key ) . '"' . $data_field_type . $selected . '>';
									$option .= $option_text;
									$option .= '</option>';
									echo $option;
								}
							}
						?>
					</select>
					<select name="_wpv_settings[order]" class="js-wpv-posts-order" autocomplete="off" <?php disabled( $view_settings['orderby'], 'rand' ); ?>>
						<option value="DESC" <?php selected( $view_settings['order'], 'DESC' ); ?>><?php _e( 'Descending', 'wpv-views' ) ?></option>
						<option value="ASC" <?php selected( $view_settings['order'], 'ASC' ); ?>><?php _e( 'Ascending', 'wpv-views' ) ?></option>
					</select>
					<span class="js-wpv-settings-posts-orderby-as"<?php echo ( $view_settings['query_type'][0] != 'posts' || !$show_orderby_as ) ? ' style="display: none;"' : ''; ?>>
						<?php
						$disable_orderby_as = false;
						if ( in_array( $selected_orderby_field_type, array( 'numeric', 'date' ) ) ) {
							$view_settings['orderby_as'] = 'NUMERIC';
							$disable_orderby_as = true;
						}
						?>
						<select id="wpv-settings-orderby-as" name="_wpv_settings[orderby_as]" class="js-wpv-posts-orderby-as" autocomplete="off" <?php disabled( $disable_orderby_as ); ?>>
							<option value=""><?php _e( 'As a native custom field', 'wpv-views' ) ?></option>
							<option value="STRING" <?php selected( $view_settings['orderby_as'], 'STRING' ); ?>><?php _e( 'As a string', 'wpv-views' ) ?></option>
							<option value="NUMERIC" <?php selected( $view_settings['orderby_as'], 'NUMERIC' ); ?>><?php _e( 'As a number', 'wpv-views' ) ?></option>
						</select>
					</span>
				</p>

				<p class="wpv-settings-query-type-taxonomy"<?php echo ( $view_settings['query_type'][0] != 'taxonomy' ) ? ' style="display: none;"' : ''; ?>>
					<?php
					$taxonomy_order_by = array(
						'id'			=> __( 'Term ID', 'wpv-views' ),
						'count'			=> __( 'Post count', 'wpv-views' ),
						'name'			=> __( 'Term name', 'wpv-views' ),
						'slug'			=> __( 'Term slug', 'wpv-views' ),
						'term_group'	=> __( 'Term group', 'wpv-views' ),
						'none'			=> __( 'No order', 'wpv-views' )
					);
					?>
					<label for="wpv-settings-orderby"><?php _e( 'Order by ', 'wpv-views' ) ?></label>
					<select id="wpv-settings-orderby" class="js-wpv-taxonomy-orderby" name="_wpv_settings[taxonomy_orderby]" autocomplete="off">
						<?php
							foreach ( $taxonomy_order_by as $id => $text ) {
							?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $view_settings['taxonomy_orderby'], $id ); ?>><?php echo $text; ?></option>
							<?php
							}
						?>
						<?php
							global $wp_version;
							if ( ! version_compare( $wp_version, '4.5', '<' ) ) {
								$all_types_termmeta_fields			= get_option( 'wpcf-termmeta', array() );
								$termmeta_keys = apply_filters( 'wpv_filter_wpv_get_termmeta_keys', array() );
								$show_tax_orderby_as				= false;
								$selected_tax_orderby_field_type	= '';
								
								foreach ( $termmeta_keys as $key ) {
									$selected = ( $view_settings['taxonomy_orderby'] == "taxonomy-field-" . $key ) ? ' selected="selected"' : '';
									$show_tax_orderby_as = ( !empty( $selected ) ) ? true : $show_tax_orderby_as;
									$option_text = "";
									$field_type = '';
									$data_field_type = "";

									if ( stripos( $key, 'wpcf-' ) === 0 )  {
										if (
											isset( $all_types_termmeta_fields[substr( $key, 5 )] )
											&& isset( $all_types_termmeta_fields[substr( $key, 5 )]['name'] )
										) {
											$option_text = sprintf(__('Term Field - %s', 'wpv-views'), $all_types_termmeta_fields[substr( $key, 5 )]['name']);
											$field_type = $all_types_termmeta_fields[substr( $key, 5 )]['type'];
											$selected_tax_orderby_field_type = ( ! empty( $selected ) ) ? $field_type : $selected_tax_orderby_field_type;
											$data_field_type = ' data-field-type="' . esc_attr( $field_type ) . '"';
										} else {
											$option_text = sprintf(__('Term Field - %s', 'wpv-views'), $key);
										}
									} else {
										$option_text = sprintf(__('Term Field - %s', 'wpv-views'), $key);
									}
									
									if ( ! in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
										$option = '<option value="taxonomy-field-' . esc_attr( $key ) . '"' . $data_field_type . $selected . '>';
										$option .= $option_text;
										$option .= '</option>';
										echo $option;
									}
								}
							}
						?>
					</select>
					<select name="_wpv_settings[taxonomy_order]" class="js-wpv-taxonomy-order" autocomplete="off">
						<option value="DESC" <?php selected( $view_settings['taxonomy_order'], 'DESC' ); ?>><?php _e( 'Descending', 'wpv-views' ) ?></option>
						<option value="ASC" <?php selected( $view_settings['taxonomy_order'], 'ASC' ); ?>><?php _e( 'Ascending', 'wpv-views' ) ?></option>
					</select>
					<span class="js-wpv-settings-taxonomy-orderby-as"<?php echo ( $view_settings['query_type'][0] != 'taxonomy' || !$show_tax_orderby_as) ? ' style="display: none;"' : ''; ?>>
						<?php
						$disable_tax_orderby_as = false;
						if ( in_array( $selected_tax_orderby_field_type, array( 'numeric', 'date' ) ) ) {
							$view_settings['taxonomy_orderby_as'] = 'NUMERIC';
							$disable_tax_orderby_as = true;
						}
						?>
						<select id="wpv-settings-taxonomy-orderby-as" name="_wpv_settings[taxonomy_orderby_as]" class="js-wpv-taxonomy-orderby-as" autocomplete="off" <?php disabled( $disable_tax_orderby_as ); ?>>
							<option value=""><?php _e( 'As a native custom field', 'wpv-views' ) ?></option>
							<option value="STRING" <?php selected( $view_settings['taxonomy_orderby_as'], 'STRING' ); ?>><?php _e( 'As a string', 'wpv-views' ) ?></option>
							<option value="NUMERIC" <?php selected( $view_settings['taxonomy_orderby_as'], 'NUMERIC' ); ?>><?php _e( 'As a number', 'wpv-views' ) ?></option>
						</select>
					</span>
				</p>

				<p class="wpv-settings-query-type-users"<?php echo ( $view_settings['query_type'][0] != 'users' ) ? ' style="display: none;"' : ''; ?>>
					<?php 
					$users_order_by = array(
						'user_login'		=> __( 'User login', 'wpv-views' ),
						'ID'				=> __( 'User ID', 'wpv-views' ),
						'user_name'			=> __( 'User name', 'wpv-views' ),
						'display_name'		=> __( 'User display name', 'wpv-views' ),
						'user_nicename'		=> __( 'User nicename', 'wpv-views' ),
						'user_email'		=> __( 'User email', 'wpv-views' ),
						'user_url'			=> __( 'User URL', 'wpv-views' ),
						'user_registered'	=> __( 'User registered date', 'wpv-views' ),
						'include'			=> __( 'User order on a filter', 'wpv-views' ),
						'post_count'		=> __( 'User post count', 'wpv-views' )
					);
					if ( ! isset( $view_settings['users_orderby'] ) ) {
						$view_settings['users_orderby'] = 'user_login';
					}
					if ( ! isset( $view_settings['users_order'] ) ) {
						$view_settings['users_order'] = 'DESC';
					}
					?>
					<label for="wpv-settings-orderby"><?php _e( 'Order by ', 'wpv-views' ) ?></label>
					<select id="wpv-settings-orderby" class="js-wpv-users-orderby" name="_wpv_settings[users_orderby]" autocomplete="off">
						<?php
							foreach ( $users_order_by as $id => $text ) {
							?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $view_settings['users_orderby'], $id ); ?>><?php echo $text; ?></option>
							<?php

							}
						?>
					</select>
					<select name="_wpv_settings[users_order]" class="js-wpv-users-order" autocomplete="off">
						<option value="DESC" <?php selected( $view_settings['users_order'], 'DESC' ); ?>><?php _e( 'Descending', 'wpv-views' ) ?></option>
						<option value="ASC" <?php selected( $view_settings['users_order'], 'ASC' ); ?>><?php _e( 'Ascending', 'wpv-views' ) ?></option>
					</select>
				</p>
				<div class="js-wpv-toolset-messages"></div>
			</div>
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<span type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_ordering_nonce' ); ?>" class="js-wpv-ordering-update" />
			</span>
		</div>
	<?php 
	}

	static function wpv_wpa_editor_section_ordering( $view_settings, $view_id, $user_id ) {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'archive_ordering' );
		$view_settings = apply_filters( 'wpv_filter_wpv_get_sorting_defaults', $view_settings );
		?>
		<div class="wpv-setting-container wpv-settings-ordering js-wpv-settings-ordering">
			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Ordering', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip" 
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>" 
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<p class="wpv-settings-query-type-posts js-wpv-settings-posts-order">
					<label for="wpv-settings-orderby"><?php _e( 'Order by: ', 'wpv-views' ) ?></label>
					<select id="wpv-settings-orderby" class="js-wpv-posts-orderby" name="_wpv_settings[orderby]" autocomplete="off" data-rand="<?php echo esc_attr( __('Pagination and random ordering do not work together and would produce unexpected results. Please disable pagination or random ordering.', 'wpv-views') ); ?>">
						<option value="post_date" <?php selected( $view_settings['orderby'], 'post_date' ); ?>><?php _e('Post date', 'wpv-views'); ?></option>
						<option value="post_title" <?php selected( $view_settings['orderby'], 'post_title' ); ?>><?php _e('Post title', 'wpv-views'); ?></option>
						<option value="ID" <?php selected( $view_settings['orderby'], 'ID' ); ?>><?php _e('Post ID', 'wpv-views'); ?></option>
						<option value="modified" <?php selected( $view_settings['orderby'], 'modified' ); ?>><?php _e('Last modified', 'wpv-views'); ?></option>
						<option value="menu_order" <?php selected( $view_settings['orderby'], 'menu_order' ); ?>><?php _e('Menu order', 'wpv-views'); ?></option>
						<option value="rand" <?php selected( $view_settings['orderby'], 'rand' ); ?>><?php _e('Random order', 'wpv-views'); ?></option>
						<?php
							$all_types_fields				= get_option( 'wpcf-fields', array() );
							$cf_keys						= apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
							$show_orderby_as				= false;
							$selected_orderby_field_type	= '';
							
							foreach ( $cf_keys as $key ) {
								$selected = ( $view_settings['orderby'] == "field-" . $key ) ? ' selected="selected"' : '';
								$show_orderby_as = ( ! empty( $selected ) ) ? true : $show_orderby_as;
								$option_text = "";
								$field_type = '';
								$data_field_type = "";

								if ( stripos( $key, 'wpcf-' ) === 0 )  {
									if (
										isset( $all_types_fields[substr( $key, 5 )] )
										&& isset( $all_types_fields[substr( $key, 5 )]['name'] )
									) {
										$option_text = sprintf(__('Field - %s', 'wpv-views'), $all_types_fields[substr( $key, 5 )]['name']);
										$field_type = $all_types_fields[substr( $key, 5 )]['type'];
										$selected_orderby_field_type = ( ! empty( $selected ) ) ? $field_type : $selected_orderby_field_type;
										$data_field_type = ' data-field-type="' . esc_attr( $field_type ) . '"';
									} else {
										$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
									}
								} else {
									$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
								}
								
								if ( ! in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
									$option = '<option value="field-' . esc_attr( $key ) . '"' . $data_field_type . $selected . '>';
									$option .= $option_text;
									$option .= '</option>';
									echo $option;
								}
							}
						?>
					</select>
					<select name="_wpv_settings[order]" class="js-wpv-posts-order" autocomplete="off" <?php disabled( $view_settings['orderby'], 'rand' ); ?>>
						<option value="DESC" <?php selected( $view_settings['order'], 'DESC' ); ?>><?php _e( 'Descending', 'wpv-views' ) ?></option>
						<option value="ASC" <?php selected( $view_settings['order'], 'ASC' ); ?>><?php _e( 'Ascending', 'wpv-views' ) ?></option>
					</select>
					<span class="js-wpv-settings-posts-orderby-as"<?php echo ( $view_settings['query_type'][0] != 'posts' || !$show_orderby_as ) ? ' style="display: none;"' : ''; ?>>
						<?php
						$disable_orderby_as = false;
						if ( in_array( $selected_orderby_field_type, array( 'numeric', 'date' ) ) ) {
							$view_settings['orderby_as'] = 'NUMERIC';
							$disable_orderby_as = true;
						}
						?>
						<select id="wpv-settings-orderby-as" name="_wpv_settings[orderby_as]" class="js-wpv-posts-orderby-as" autocomplete="off" <?php disabled( $disable_orderby_as ); ?>>
							<option value=""><?php _e( 'As a native custom field', 'wpv-views' ) ?></option>
							<option value="STRING" <?php selected( $view_settings['orderby_as'], 'STRING' ); ?>><?php _e( 'As a string', 'wpv-views' ) ?></option>
							<option value="NUMERIC" <?php selected( $view_settings['orderby_as'], 'NUMERIC' ); ?>><?php _e( 'As a number', 'wpv-views' ) ?></option>
						</select>
					</span>
				</p>
				<div class="js-wpv-toolset-messages"></div>
			</div>
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<span type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_ordering_nonce' ); ?>" class="js-wpv-ordering-update" />
			</span>
		</div>
		<?php
	}
	
	static function wpv_update_sorting_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if ( 
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_ordering_nonce' ) 
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1 
		) {
			$data = array(
				'type'		=> 'id',
				'message'	=> __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$changed = false;
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$sorting_options = array(
			'orderby', 'order', 'orderby_as',
			'taxonomy_orderby', 'taxonomy_order', 'taxonomy_orderby_as',
			'users_orderby', 'users_order'
		);
		foreach ( $sorting_options as $sorting_opt) {
			if (
				isset( $_POST[$sorting_opt] )
				&& (
					! isset( $view_array[ $sorting_opt ] )
					|| $_POST[ $sorting_opt ] != $view_array[ $sorting_opt ]
				)
			) {
				if ( is_array( $_POST[ $sorting_opt ] ) ) {
					$_POST[ $sorting_opt ] = array_map( 'sanitize_text_field', $_POST[ $sorting_opt ] );
				} else {
					$_POST[ $sorting_opt ] = sanitize_text_field( $_POST[ $sorting_opt ] );
				}
				$view_array[ $sorting_opt ] = $_POST[ $sorting_opt ];
				$changed = true;
			}
		}
		if ( $changed ) {
			update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		}
		$data = array(
			'id'		=> $_POST["id"],
			'message'	=> __( 'Ordering saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}