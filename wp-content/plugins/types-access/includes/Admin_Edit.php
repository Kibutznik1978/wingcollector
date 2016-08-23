<?php
final class Access_Admin_Edit
{
/*
 * Edit access page.
 */


/**
 * Admin page form.
 *
 * We are doing lots of things here we do not need at all
 */
public static function wpcf_access_admin_edit_access( $enabled = true ) {
	$output = '';
	
	$tabs = array(
		'post-type'		=> __( 'Post Types', 'wpcf-access' ),
		'taxonomy'		=> __( 'Taxonomies', 'wpcf-access' ),
		'custom-group'	=> __( 'Posts Groups', 'wpcf-access' ),
	);
	
	$extra_tabs = apply_filters( 'types-access-tab', array() );
	
	foreach ( $extra_tabs as $tab_slug => $tab_name ) {
		$tabs[ $tab_slug ] = $tab_name;
	}
	
	$custom_areas = apply_filters( 'types-access-area', array() );
	if ( count( $custom_areas ) > 0 ) {
		$tabs['third-party'] = __( 'Custom Areas', 'wpcf-access' );
	}
		
	if ( apply_filters( 'otg_access_filter_is_wpml_installed', false ) ) {
		$tabs['wpml-group'] = __( 'WPML Groups', 'wpcf-access' );
	}
	
	$tabs['custom-roles'] = __( 'Custom Roles', 'wpcf-access' );
	
	$current_tab = 'post-type';
	if ( isset( $_GET['tab'] ) ) {
		$current_tab_candidate = sanitize_text_field( $_GET['tab'] );
		if ( isset( $tabs[ $current_tab_candidate ] ) ) {
			$current_tab = $current_tab_candidate;
		}
	}
	
	$output .= wp_nonce_field( 'otg-access-edit-sections', 'otg-access-edit-sections', true, false );

	$output .= wp_nonce_field( 'wpcf-access-error-pages', 'wpcf-access-error-pages', true, false );
	
	$output .= '<p class="otg-access-new-nav">';
	foreach ( $tabs as $tab_section => $tab_title ) {
		$section_classname = array( 'nav-tab' );
		if ( $current_tab == $tab_section ) {
			$section_classname[] = 'nav-tab-active';
		}
		$section_classname[] = 'js-wpcf-access-shortcuts js-otg-access-nav-tab';
		$output .= '<a href="' . admin_url( 'admin.php?page=types_access&tab=' . $tab_section ) . '" data-target="' . esc_attr( $tab_section ) . '" class="' .  implode( ' ', $section_classname ) . '">' . esc_html( $tab_title ) . '</a>';
	}
	$output .= '</p>';
	
	$output .= '<div class="otg-access-settings-container">';
	$output .= '<form id="wpcf_access_admin_form" method="post" action="">';
    $output .= '<div class="js-otg-access-content">';
	
	$output .= '<div class="otg-access-settings-section-loading js-otg-access-settings-section-loading" style="display:none;">'
		. '<i class="fa fa-refresh fa-spin"></i>  '
		. __( 'Loading...', 'wpcf-access' )
		. '</div>';
	
	switch ( $current_tab ) {
		case 'post-type';
			$output .= self::otg_access_get_permission_table_for_posts();
			break;
		case 'taxonomy';
			$output .= self::otg_access_get_permission_table_for_taxonomies();
			break;
		case 'third-party';
			$output .= self::otg_access_get_permission_table_for_third_party();
			break;
		case 'custom-group';
			$output .= self::otg_access_get_permission_table_for_custom_groups();
			break;
		case 'wpml-group';
			$output .= self::otg_access_get_permission_table_for_wpml();
			break;
		case 'custom-roles';
			$output .= self::otg_access_get_permission_table_for_custom_roles();
			break;
		default;
			if ( isset( $extra_tabs[ $current_tab ] ) ) {
				$output .= self::otg_access_get_permission_table_for_third_party( $current_tab );
			}
			break;
	}
		
	$output .= '</div>';

    $output .= '</form></div>';

    // Link to wp-types.com Access home URL
	/*
    $link_to_manual = '<a href="http://wp-types.com/documentation/user-guides/?utm_source=accessplugin&utm_campaign=access&utm_medium=access-edit&utm_term=Access manuals#Access" title="'
            . __('Access Manuals &raquo;', 'wpcf-access') . '" target="_blank" '
			. 'class="wpcf-access-link-to-manual" style="display:block;font-weight:bold;background-image: url(\''
			. TACCESS_ASSETS_URL . '/images/question.png\');background-repeat: no-repeat;text-indent: 18px;">'
            . __('Access Manuals &raquo;', 'wpcf-access') . '</a>';
	*/

    echo $output;

}

public static function otg_access_get_permission_table_for_posts() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-post-type" data-tab="post-type">';
	
	$model					= TAccess_Loader::get('MODEL/Access');
	$post_types_settings	= $model->getAccessTypes();
	$roles					= Access_Helper::wpcf_get_editable_roles();
	$post_types_available	= $model->getPostTypes();
	$post_types_available 	= Access_Helper::wpcf_object_to_array( $post_types_available );
	
	$container_class = 'is-enabled';
	$enabled = true;
	
	$access_bypass_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses the same word for singular name and plural name. Access can\'t control access to this object. Please use a different word for the singular and plural names.', 'wpcf-access') . '</p>'
		. '</div>';
    $access_conflict_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default WordPress capabilities. Access can not manage this entity, try changing its name and / or slug', 'wpcf-access') . '</p>'
		. '</div>';
    $access_notices='';
	
	
	
	foreach ( $post_types_available as $type_slug => $type_data ) {
		// filter types, excluding types that do not have different plural and singular names
		if ( 
			isset( $type_data['__accessIsNameValid'] ) 
			&& ! $type_data['__accessIsNameValid']
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}
		if ( 
			isset( $type_data['__accessIsCapValid'] ) 
			&& ! $type_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}

		if ( isset( $post_types_settings[ $type_slug ] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_capabilities'] = $post_types_settings[ $type_slug ];
		}

		if ( ! empty( $type_data['_wpcf_access_inherits_post_cap'] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_inherits_post_cap'] = 1;
		}
	}

	// Put Posts and Pages in front
	// @todo do this on a proper way
	$native_post_types = array( 'page', 'post' );
	foreach ( $native_post_types as $npt ) {
		if ( isset( $post_types_available[ $npt ] ) ) {
			$clone = array( $npt => $post_types_available[ $npt ] );
			unset( $post_types_available[ $npt ] );
			$post_types_available = $clone + $post_types_available;
		}
	}
	
	$output .= '<p class="otg-access-settings-section-description">' . __( 'Post Types not Managed by Access will inherit the same access rights as the standard WordPress Post', 'wpcf-access' ) . '</p>';
	
	if ( ! empty( $post_types_available ) ) {
		$permission_array = Access_Helper::wpcf_access_types_caps_predefined();
		$permissions_array_default = $permission_array;
		
		$post_types_with_custom_group = Access_Helper::otg_access_get_post_types_with_custom_groups();
		
		foreach ( $post_types_available as $type_slug => $type_data ) {
			if ( $type_data['public'] === 'hidden' ) {
				continue;
			}
			if (
				$type_slug == 'view-template' 
				|| $type_slug == 'view' 
				|| $type_slug == 'cred-form' 
				|| $type_slug == 'cred-user-form'
			) {
				continue;
			}
			// Set data
			$mode = isset( $type_data['_wpcf_access_capabilities']['mode'] ) ? $type_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			$is_managed = ( $mode != 'not_managed' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}

			$output .= '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item '. $container_class .' wpcf-access-post-type-name-' . $type_slug . ' js-wpcf-access-type-item">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $type_slug ) . '">' 
				. $type_data['labels']['name'] 
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' ) : __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';
			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $type_slug ) . '" style="display:none">';
			
			if ( $type_slug == 'attachment' ) {
				$output .= '<p class="otg-access-settings-section-description">' .
				__( 'This section controls access to media-element pages and not to media that is included in posts and pages.', 'wpcf-access' )
				. '</p>';
			}
			
			$output .= '<p class="wpcf-access-mode-control">
							<label>
							<input type="checkbox" class="js-wpcf-enable-access" value="permissions" ';
			$output .= $is_managed ? 'checked="checked" />' : ' />';
			$output .= '<input type="hidden" class="js-wpcf-enable-set" '
					. 'name="types_access[types]['
					. $type_slug . '][mode]" value="'
					. $mode . '" />';
			$output .= '' . __('Managed by Access', 'wpcf-access') . '</label>
					</p>';

			$permissions = ! empty( $type_data['_wpcf_access_capabilities']['permissions'] ) ? $type_data['_wpcf_access_capabilities']['permissions'] : array();

			$output .= self::wpcf_access_permissions_table(
						$roles, 
						$permissions,
						$permission_array, 
						'types', 
						$type_slug,
						$enabled, 
						$mode != 'not_managed', 
						$post_types_settings, 
						$type_data
					);

			$output .= '<p class="wpcf-access-buttons-wrap">';
				$output .= self::wpcf_access_reset_button( $type_slug, 'type', $enabled, $is_managed );
				$output .= self::wpcf_access_submit_button( $enabled, $is_managed, $type_data['labels']['name'] );
			$output .= '</p>';
			
			if ( in_array( $type_slug, $post_types_with_custom_group ) ) {
				$message = sprintf(
					__( 'Some %1$s may have different read settings because they belong to a Custom Group. %2$sEdit Custom Groups%3$s', 'wpcf-access' ),
						$type_data['labels']['name'], 
						'<a class="js-otg-access-manual-tab" data-target="custom-group" href="' . admin_url( 'admin.php?page=types_access&tab=custom-group' ) . '">',
						'</a>'
				);
				$output .= '<div class="toolset-alert toolset-alert-info js-toolset-alert">' . $message . '</div>';
			}
			
			$output .= '</div><!-- wpcf-access-mode -->';
			$output .= '</div><!-- wpcf-access-type-item -->';

		}
	} else {
		$output .= '<p>'
			. __( 'There are no post types registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_taxonomies() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-taxonomy" data-tab="taxonomy">';
	
	$model					= TAccess_Loader::get('MODEL/Access');
	$roles					= Access_Helper::wpcf_get_editable_roles();
	$post_types_settings	= $model->getAccessTypes();
	$post_types_available	= $model->getPostTypes();
	$post_types_available	= Access_Helper::wpcf_object_to_array( $post_types_available );
	$taxonomies_settings	= $model->getAccessTaxonomies();
	$taxonomies_available	= $model->getTaxonomies();
	$taxonomies_available	= Access_Helper::wpcf_object_to_array( $taxonomies_available );
	
	
	$container_class = 'is-enabled';
	$enabled = true;
	
	$access_bypass_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses the same word for singular name and plural name. Access can\'t control access to this object. Please use a different word for the singular and plural names.', 'wpcf-access') . '</p>'
		. '</div>';
    $access_conflict_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default WordPress capabilities. Access can not manage this entity, try changing its name and / or slug', 'wpcf-access') . '</p>'
		. '</div>';
    $access_notices='';
	
	$supports_check = array();
	
	foreach ( $post_types_available as $type_slug => $type_data ) {
		// filter types, excluding types that do not have different plural and singular names
		if ( 
			isset( $type_data['__accessIsNameValid'] ) 
			&& ! $type_data['__accessIsNameValid']
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}
		if ( 
			isset( $type_data['__accessIsCapValid'] ) 
			&& ! $type_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}

		if ( isset( $post_types_settings[ $type_slug ] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_capabilities'] = $post_types_settings[ $type_slug ];
		}

		if ( ! empty( $type_data['_wpcf_access_inherits_post_cap'] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_inherits_post_cap'] = 1;
		}
	}

	// Put Posts and Pages in front
	$native_post_types = array( 'page', 'post' );
	foreach ( $native_post_types as $npt ) {
		if ( isset( $post_types_available[ $npt ] ) ) {
			$clone = array( $npt => $post_types_available[ $npt ] );
			unset( $post_types_available[ $npt ] );
			$post_types_available = $clone + $post_types_available;
		}
	}

	foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
		// filter taxonomies, excluding tax that do not have different plural and singular names
		if (
			isset( $tax_data['__accessIsNameValid'] ) 
			&& ! $tax_data['__accessIsNameValid'] 
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Taxonomy','wpcf-access'), $tax_data['labels']['singular_name'] );
			unset( $taxonomies_available[ $tax_slug ] );
			continue;
		}
		if ( 
			isset( $tax_data['__accessIsCapValid'] ) 
			&& ! $tax_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Taxonomy','wpcf-access'), $tax_data['labels']['singular_name'] );
			unset( $taxonomies_available[ $tax_slug ] );
			continue;
		}

		$taxonomies_available[ $tax_slug ]['supports'] = array_flip( $tax_data['object_type'] );
		if ( isset( $taxonomies_settings[ $tax_slug ] ) ) {
			$taxonomies_available[ $tax_slug ]['_wpcf_access_capabilities'] = $taxonomies_settings[ $tax_slug ];
		}

		if ( $enabled ) {
			$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ? $tax_data['_wpcf_access_capabilities']['mode'] : 'follow';
			if ( empty( $tax_data['supports'] ) ) {
				continue;
			}

			foreach ( $tax_data['supports'] as $supports_type => $true ) {
				if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'] ) ) {
					continue;
				}

				$mode = $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'];

				if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) {
					continue;
				}

				$supports_check[ $tax_slug ][ md5( $mode . serialize( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) ][] = $post_types_available[ $supports_type ]['labels']['name'];
			}
		}
	}

	// Put Categories and Tags in front
	$native_taxonomies = array( 'post_tag', 'category' );
	foreach ( $native_taxonomies as $nt ){
		if ( isset( $taxonomies_available[ $nt ] ) ) {
			$clone = array( $nt => $taxonomies_available[ $nt ] );
			unset( $taxonomies_available[ $nt ] );
			$taxonomies_available = $clone + $taxonomies_available;
		}
	}
	
	$custom_data = Access_Helper::wpcf_access_tax_caps();

	if ( ! empty( $taxonomies_available ) ) {
		foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
			$mode = 'not_managed';
			if ( $tax_data['public'] === 'hidden' ) {
				continue;
			}
			// Set data
			if ( isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ) {
				$mode = $tax_data['_wpcf_access_capabilities']['mode'];
			} elseif ( $enabled ) {
				$mode = Access_Helper::wpcf_access_get_taxonomy_mode( $tax_slug, $mode );
			} else {
				$mode = 'not_managed';
			}

			// For built-in set default to 'not_managed'
			if ( in_array( $tax_slug, $native_taxonomies ) ) {
				$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ? $tax_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			}
			
			if ( isset( $tax_data['_wpcf_access_capabilities']['permissions'] ) ) {
				foreach ( $tax_data['_wpcf_access_capabilities']['permissions'] as $cap_slug => $cap_data ) {
					$custom_data[$cap_slug]['role'] = $cap_data['role'];
					$custom_data[$cap_slug]['users'] = isset( $cap_data['users'] ) ? $cap_data['users'] : array();
				}
			}
			
			$is_managed = ( $mode != 'not_managed' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}

			$output .= '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item ' . $container_class . '">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $tax_slug ) .'">' 
				. $tax_data['labels']['name'] 
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' ) : __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';

			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $tax_slug ) . '" style="display:none">';
			
			// Add warning if shared and settings are different
			$disable_same_as_parent = false;
			if (
				$enabled 
				&& isset( $supports_check[ $tax_slug ] ) 
				&& count( $supports_check[ $tax_slug ] ) > 1
			) {
				$txt = array();
				foreach ($supports_check[$tax_slug] as $sc_tax_md5 => $sc_tax_md5_data){
					$txt = array_merge($txt, $sc_tax_md5_data);
				}
				$last_element = array_pop($txt);
				$warning = '<br /><img src="' . TACCESS_ASSETS_URL . '/images/warning.png" style="position:relative;top:2px;" />' . sprintf(__('You need to manually set the access rules for taxonomy %s. That taxonomy is shared between several post types that have different access rules.'),
								$tax_data['labels']['name'],
								implode(', ', $txt), $last_element);
				$output .= $warning;
				$disable_same_as_parent = true;
			}
			
			// Managed checkbox - Custom taxonomies section
			$output .= '<p>';
			$output .= '<label><input type="checkbox" class="not-managed js-wpcf-enable-access" name="types_access[tax][' . $tax_slug . '][not_managed]" value="1"';
			if ( ! $enabled ) {
				$output .= ' disabled="disabled" readonly="readonly"';
			}
			$output .= $is_managed ? ' checked="checked"' : '';
			$output .= '/>' . __('Managed by Access', 'wpcf-access') . '</label>';
			$output .= '</p>';

			// 'Same as parent' checkbox
			$output .= '<p>';
			$output .= '<label><input type="checkbox" class="follow js-wpcf-follow-parent" name="types_access[tax][' . $tax_slug . '][mode]" value="follow"';
			if ( ! $enabled ) {
				$output .= ' disabled="disabled" readonly="readonly" checked="checked"';
			} else if ( $disable_same_as_parent ) {
				$output .= ' disabled="disabled" readonly="readonly"';
			} else {
				$output .= $mode == 'follow' ? ' checked="checked"' : '';
			}
			$output .= ' />' . __('Same as Parent', 'wpcf-access') . '</label>';
			$output .= '</p>';

			$output .= '<div class="wpcf-access-mode-custom">';
			$output .= self::wpcf_access_permissions_table(
				$roles, 
				$custom_data,
				$custom_data, 
				'tax', 
				$tax_slug, 
				$enabled,
				$is_managed, 
				$taxonomies_settings
			);
			$output .= '</div>	<!-- .wpcf-access-mode-custom -->';
			
			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= self::wpcf_access_reset_button( $tax_slug, 'tax', $enabled );
			$output .= self::wpcf_access_submit_button( $enabled, $is_managed, $tax_data['labels']['name'] );
			$output .= '</p>';
			
			$output .= '</div>	<!-- wpcf-access-mode -->';
			$output .= '</div>	<!-- wpcf-access-type-item -->';
		}
	} else {
		$output .= '<p>'
			. __( 'There are no taxonomies registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_third_party( $current_tab = 'third-party' ) {
	$output = '';
	
	$model				= TAccess_Loader::get('MODEL/Access');
	$roles				= Access_Helper::wpcf_get_editable_roles();
	$settings_access	= $model->getAccessTypes();
	$third_party		= $model->getAccessThirdParty();
	
	$enabled = true;
	$current_tab = esc_attr( $current_tab );
	
	if ( $current_tab == 'third-party' ) {
		$areas			= apply_filters( 'types-access-area', array() );
	} else {
		$areas			= apply_filters( 'types-access-area-for-' . $current_tab, array() );
	}
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-' . $current_tab . '" data-tab="' . $current_tab . '">';
	$has_output = false;
	
	foreach ( $areas as $area ) {
		// Do not allow Types IDs for post types or taxonomies
		if ( in_array( $area['id'], array( 'types', 'tax' ) ) )
			continue;

		// make all groups of same area appear on same line in shortcuts
		$groups = apply_filters( 'types-access-group', array(), $area['id'] );

		if ( 
			! is_array( $groups ) 
			|| empty( $groups ) 
		) {
			continue;
		}
		
		$has_output = true;
		
		foreach ( $groups as $group ) {

			$output .= '<div class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group['id'] ) .'">' . $group['name'] . '</h4>';
			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group['id'] ) . '" style="display:none">';

			$caps = array();
			$caps_filter = apply_filters( 'types-access-cap', array(), $area['id'], $group['id'] );
			$saved_data = array();
			foreach ( $caps_filter as $cap_slug => $cap ) {
				$caps[ $cap['cap_id'] ] = $cap;
				if ( isset( $cap['default_role'] ) ) {
					$caps[ $cap['cap_id'] ]['role'] = $cap['role'] = $cap['default_role'];
				}
				$saved_data[ $cap['cap_id'] ] =
						isset( $third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ] ) ?
						$third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ] : array( 'role' => $cap['role'] );
			}
			// Add registered via other hook
			if ( ! empty( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] ) ) {
				foreach ( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] as $cap_slug => $cap ) {
					// Don't allow duplicates
					if ( isset( $caps[ $cap['cap_id'] ] ) ) {
						unset( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap_slug ] );
						continue;
					}
					$saved_data[ $cap['cap_id'] ] = $cap['saved_data'];
					$caps[ $cap['cap_id'] ] = $cap;
				}
			}
			if ( 
				isset( $cap['style'] ) 
				&& $cap['style'] == 'dropdown'
			) {

			} else {
				$output .= self::wpcf_access_permissions_table(
					$roles, 
					$saved_data,
					$caps, 
					$area['id'], 
					$group['id'],
					true, 
					$settings_access
				);
			}


			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= self::wpcf_access_submit_button( $enabled, true, $group['name'] );
			$output .= '</p>';

			$output .= '</div>	<!-- .wpcf-access-mode -->';
			$output .= '</div>	<!-- .wpcf-access-type-item -->';
		}
	}
	
	if ( ! $has_output ) {
		$output .= '<p>'
			. __( 'There are no third party areas registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_custom_groups() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-custom-group" data-tab="custom-group">';
	
	$model = TAccess_Loader::get('MODEL/Access');
	$roles = Access_Helper::wpcf_get_editable_roles();
	$enabled = true;
	$group_output = '';
	
	$settings_access = $model->getAccessTypes();
	$show_section_header = true;
	$group_count = 0;
	if ( is_array($settings_access) && !empty($settings_access) ){
		foreach ( $settings_access as $group_slug => $group_data) {
			if ( strpos($group_slug, 'wpcf-custom-group-') !== 0 ) {
				continue;
			}
			if ( !isset($group_data['title']) ){
					$new_settings_access = $model->getAccessTypes();
					unset($new_settings_access[$group_slug]);
					$model->updateAccessTypes($new_settings_access);
					continue;
			}
			if ( $show_section_header ){
					$show_section_header = false;
			}
			$group_count++;
			$group_div_id = str_replace('%','',$group_slug);
			$group['id'] = $group_slug;
			$group['name']= $group_data['title'];
			
			$group_output .= '<div id="js-box-' . $group_div_id . '" class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
			$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group_div_id ) .'">' . $group['name'] . '</h4>';
			$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group_div_id ) . '" style="display:none">';
			
			$caps = array();
			$saved_data = array();

			// Add registered via other hook
			if ( !empty($group_data['permissions']) ) {
				  $saved_data['read'] = $group_data['permissions']['read'];
			}

			$def = array(
				'read' => array(
					'title' => __('Read', 'wpcf-access'),
					'role' => 'guest',
					'predefined' => 'read',
					'cap_id' => 'group')
				);

			$group_output .= self::wpcf_access_permissions_table(
					$roles, $saved_data,
					$def, 'types', $group['id'],
					$enabled, 'permissions',
					$settings_access );

			$group_output .= '<p class="wpcf-access-buttons-wrap">';
			$group_output .= '<span class="ajax-loading spinner"></span>';
			$group_output .= '<input data-group="' . $group_slug . '" data-groupdiv="' . $group_div_id . '" type="button" value="' . __('Modify Group', 'wpcf-access') . '"  class="js-wpcf-modify-group button-secondary" /> ';
			$group_output .= '<input data-group="' . $group_slug . '" data-target="custom-group" data-section="'. base64_encode('custom-group') .'" data-groupdiv="' . $group_div_id . '"  type="button" value="' . __('Remove Group', 'wpcf-access') . '"  class="js-wpcf-remove-group button-secondary" /> ';
			$group_output .= self::wpcf_access_submit_button($enabled, true, $group['name']);
			$group_output .= '</p>';
			$group_output .= '<input type="hidden" name="groupvalue-' . $group_slug . '" value="' . $group_data['title'] .'">';
			$group_output .= '</div>	<!-- .wpcf-access-mode  -->';
			$group_output .= '</div>	<!-- .wpcf-access-custom-group -->';
		}
	}

	if ( $group_count > 0 ){
		$output .= '<p>'
			. '<button data-label="' . esc_attr( __('Add Group','wpcf-access') ) . '" value="' . esc_attr( __('Add custom group', 'wpcf-access') ) .'" class="button button-large button-secondary wpcf-add-new-access-group js-wpcf-add-new-access-group">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. esc_html( __('Add custom group', 'wpcf-access') ) 
			. '</button>'
			. '</p>';
		$output .= $group_output;
	} else {
		  $output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups"><p>'. __('No custom groups found.', 'wpcf-access') .'</p><p>'
			.'<a href="" data-label="'.__('Add Group','wpcf-access').'"
			class="button button-secondary js-wpcf-add-new-access-group">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. __('Create your first custom group', 'wpcf-access') .'</a></p></div>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_wpml() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-wpml-group" data-tab="wpml-group">';
	
	$model = TAccess_Loader::get('MODEL/Access');
	if ( apply_filters( 'otg_access_filter_is_wpml_installed', false ) ) {
		$group_count = 0;
		$group_output = '<p><button " style="background-image: url('.ICL_PLUGIN_URL . '/res/img/icon.png'.')" class="button button-large button-secondary wpcf-add-new-access-group wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
			. __('Create permission for languages', 'wpcf-access') .'</button></p>';
		//WPML groups
		$settings_access = $model->getAccessTypes();
		$roles = Access_Helper::wpcf_get_editable_roles();
		$show_section_header = true;
		$enabled = true;
		if ( 
			is_array( $settings_access ) 
			&& ! empty( $settings_access )
		) {
			$_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
			foreach ( $settings_access as $group_slug => $group_data) {
				if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
					continue;
				}
				if ( ! isset( $_post_types[ $group_data['post_type'] ] ) ) {
					continue;
				}

				if ( ! apply_filters( 'wpml_is_translated_post_type', null, $group_data['post_type'] ) ) {
					self::otg_access_remove_wrong_wpml_group( $group_slug );
					continue;
				}

				if ( $show_section_header ) {
					$show_section_header = false;
				}
				$group_count++;
				$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );

				$languages = array();
				if ( isset( $group_data['languages'] ) ) {
					foreach( $group_data['languages'] as $lang => $lang_data ) {
						if ( isset( $wpml_active_languages[ $lang ] ) ) {
							$languages[] = $wpml_active_languages[ $lang ]['native_name'];
						} else {
							$group_data['title'] = self::otg_access_rename_wpml_group( $group_slug );
						}
					}
				}

				if ( count( $languages ) == 0 ) {
					self::otg_access_remove_wrong_wpml_group( $group_slug );
					continue;
				}

				$group_div_id = str_replace('%','',$group_slug);
				$group['id'] = $group_slug;
				$group['name']= $group_data['title'];
					
				$group_output .= '<div id="js-box-' . $group_div_id . '" class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
				$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group_div_id ) .'">' . $group['name'] . '</h4>';
				$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group_div_id ) . '" style="display:none">';
							
				$caps = array();
				$saved_data = array();

				if ( ! empty( $group_data['permissions'] ) ) {
					  $saved_data = array(
						'read'			=> $group_data['permissions']['read'],
						'edit_own'		=> $group_data['permissions']['edit_own'],
						'delete_own'	=> $group_data['permissions']['delete_own'],
						'edit_any'		=> $group_data['permissions']['edit_any'],
						'delete_any'	=> $group_data['permissions']['delete_any'],
					 );
				}

				$def = array(
					'read' => array(
						'title'			=> __('Read', 'wpcf-access'),
						'role'			=> $group_data['permissions']['read']['role'],
						'predefined'	=> 'read',
						'cap_id'		=> 'group'
					),
					'edit_own' => array(
						'title'			=> __('Edit and translate own', 'wpcf-access'),
						'role'			=> $group_data['permissions']['edit_own']['role'],
						'predefined'	=> 'edit_own',
						'cap_id'		=> 'group'
					),
					'delete_own' => array(
						'title'			=> __('Delete own', 'wpcf-access'),
						'role'			=> $group_data['permissions']['delete_own']['role'],
						'predefined'	=> 'delete_own',
						'cap_id'		=> 'group'
					),
					'edit_any' => array(
						'title'			=> __('Edit and translate any', 'wpcf-access'),
						'role'			=> $group_data['permissions']['edit_any']['role'],
						'predefined'	=> 'edit_any',
						'cap_id'		=> 'group'
					),
					'delete_any' => array(
						'title'			=> __('Delete any', 'wpcf-access'),
						'role'			=> $group_data['permissions']['delete_any']['role'],
						'predefined'	=> 'delete_any',
						'cap_id'		=> 'group'
					),
					'publish' => array(
						'title'			=> __('Publish', 'wpcf-access'),
						'role'			=> $group_data['permissions']['publish']['role'],
						'predefined'	=> 'publish',
						'cap_id'		=> 'group'
					)
				);

				$group_output .= self::wpcf_access_permissions_table(
					$roles, 
					$saved_data,
					$def, 
					'types', 
					$group['id'],
					$enabled, 
					'permissions',
					$settings_access 
				);

				$group_output .= '<p class="wpcf-access-buttons-wrap">';
				$group_output .= '<span class="ajax-loading spinner"></span>';
				$group_output .= '<input data-group="' . $group_slug . '" data-groupdiv="' . $group_div_id . '" type="button" value="' . __('Modify languages', 'wpcf-access') . '"  class="js-wpcf-add-new-wpml-group button-secondary" /> ';
				$group_output .= '<input data-group="' . $group_slug . '" data-target="wpml-group" data-section="'. base64_encode('wpml-group') .'" data-groupdiv="' . $group_div_id . '"  type="button" value="' . __('Remove language permission', 'wpcf-access') . '"  class="js-wpcf-remove-group button-secondary" /> ';
				$group_output .= self::wpcf_access_submit_button($enabled, true, '');
				$group_output .= '</p>';
				$group_output .= '</div>	<!-- .wpcf-access-mode  -->';
				$group_output .= '</div>	<!-- .wpcf-access-wpml-group -->';
			}
		}
		if ( $group_count > 0 ) {
			$output .= $group_output;
		} else {
			  $output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups"><p>'. __('No permission for languages found.', 'wpcf-access')
			.'</p><p><a href="#" data-label="'.__('Add Group','wpcf-access').'"
			class="button button-secondary js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. __('Create your first permission for languages', 'wpcf-access') .'</a></p></div>';
		}

	} else {
		$output .= '<p>'
			. __( 'WPML is not installed.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_custom_roles() {
	$roles = Access_Helper::wpcf_get_editable_roles();
	$enabled = true; // WTF
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-custom-roles" data-tab="custom-roles">';
	
    $output .= self::wpcf_access_admin_set_custom_roles_level_form( $roles, $enabled );
    $output .= wp_nonce_field('wpcf-access-edit', '_wpnonce', true, false);
	
	$output .= self::wpcf_access_new_role_form($enabled);
	
	$output .= '</div>';
	
	return $output;
}


/*
 * Rename WPML group when one of languages was deactivated
 * @paran $group_slug
 */
public static function otg_access_rename_wpml_group( $group_slug ){
    $model = TAccess_Loader::get('MODEL/Access');
    $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
    $languages = array();
    $title_languages_array = array();
    $wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
    $settings_access = $model->getAccessTypes();


    if ( isset($settings_access[$group_slug]['languages']) ) {
        //for ($i=0, $count_lang = count($settings_access[$group_slug]['languages']); $i<$count_lang; $i++){
        foreach( $settings_access[$group_slug]['languages'] as $lang_name => $lang_status){
            if ( isset($wpml_active_languages[$lang_name]) ){
                $languages[$lang_name] = 1;
                $title_languages_array[] = $wpml_active_languages[$lang_name]['translated_name'];
            }else{
                unset($settings_access[$group_slug]['languages'][$lang_name]);
            }
        }
    }
    if(count($title_languages_array)>1)
    {
        $title_languages = implode(', ' , array_slice($title_languages_array,0,count($title_languages_array)-1)) . ' and ' . end($title_languages_array);
    }
    else
    {
            $title_languages = implode(', ' , $title_languages_array);
    }
    $group_name = $title_languages .' '. $_post_types[$settings_access[$group_slug]['post_type']]['labels']['name'];
    $settings_access[$group_slug]['title'] = $group_name;
    $model->updateAccessTypes( $settings_access );

    return $group_name;
}

/*
 * Remove WPML group
 * @param $group_slug
 */
public static function otg_access_remove_wrong_wpml_group( $group_slug ){
    $model = TAccess_Loader::get('MODEL/Access');
	$settings_access = $model->getAccessTypes();

	if ( isset($settings_access[$group_slug]) ) {
			unset($settings_access[$group_slug]);
    }
    if ( isset($settings_access['_custom_read_errors'][$group_slug]) ) {
		unset($settings_access['_custom_read_errors'][$group_slug]);
    }
    if ( isset($settings_access['_custom_read_errors_value'][$group_slug]) ) {
		unset($settings_access['_custom_read_errors_value'][$group_slug]);
    }

    $model->updateAccessTypes( $settings_access );
}
/**
 * Renders dropdown with editable roles.
 *
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_roles_dropdown( $roles, $name, $data = array(), $dummy = false, $enabled = true, $exclude = array() ) {
    $default_roles = Access_Helper::wpcf_get_default_roles();
    $output = '';
    $output .= '<select name="' . $name . '"';
    $output .= isset($data['predefined']) ? 'class="js-wpcf-reassign-role wpcf-access-predefied-' . $data['predefined'] . '">' : '>';

	if ($dummy) {
        $output .= "\n\t<option";
        if (empty($data)) {
            $output .= ' selected="selected" disabled="disabled"';
        }
        $output .= ' value="0">' . $dummy . '</option>';
    }
    foreach ($roles as $role => $details)
    {
        if (in_array($role, $exclude)) {
            continue;
        }
        if (in_array($role, $default_roles))
            $title = translate_user_role($details['name']);
        else
            $title = taccess_t($details['name'], $details['name']);

        $output .= "\n\t<option";
        if (isset($data['role']) && $data['role'] == $role) {
            $output .= ' selected="selected"';
        }
        if (!$enabled) {
            $output .= ' disabled="disabled"';
        }
        $output .= ' value="' . esc_attr($role) . "\">$title</option>";
    }

    // For now, let's add Guest only for read-only
    if (isset($data['predefined']) && $data['predefined'] == 'read-only')
    {
        $output .= "\n\t<option";
        if (isset($data['role']) && $data['role'] == 'guest') {
            $output .= ' selected="selected"';
        }
        if (!$enabled) {
            $output .= ' disabled="disabled"';
        }
        $output .= ' value="guest">' . __('Guest', 'wpcf-access') . '</option>';
    }
    $output .= '</select>';
    return $output;
}

/**
 * Auto-suggest users search.
 *
 * @param type $data
 * @param type $name
 * @return string
 */
public static function wpcf_access_admin_users_form( $data, $name, $enabled = true, $managed = true ) {
    $output = ''; 
    $output .= self::wpcf_access_suggest_user($enabled, $managed, $name);
    $output .= '<div class="wpcf-access-user-list">';
		if ( $enabled && isset($data['users']) && is_array($data['users']) )
		{
			foreach ($data['users'] as $user_id)
			{
				$user = get_userdata($user_id);
				if ( !empty($user) )
				{
					$output .= '
							<div class="wpcf-access-remove-user-wrapper">
								<a href="javascript:;" class="wpcf-access-remove-user"></a>
								<input type="hidden"
										name="' . $name . '[users][]"
										value="' . $user -> ID . '" />'
								. $user->display_name . ' (' . $user->user_login . ')
							</div>';
				}
			}
		}
    $output .= '</div>	<!-- .wpcf-access-user-list -->';
    return $output;
}

/**
 * Renders pre-defined table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_predefined($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= '<table class="wpcf-access-predefined-table">';
    foreach ($data as $mode => $mode_data)
    {
        if (!isset($mode_data['title']) || !isset($mode_data['role']))
            continue;

        $output .= '<tr><td >' . $mode_data['title'] . '</td><td>';
        $output .= '<input
						type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $mode . '"
						value="' . $name . '[' . $mode . ']" />';
        $output .= self::wpcf_access_admin_roles_dropdown($roles, $name . '[' . $mode . '][role]', $mode_data, false, $enabled);
        $output .= '</td><td>';
        $output .= self::wpcf_access_admin_users_form($mode_data, $name . '[' . $mode . ']', $enabled);
        $output .= '</td></tr>';
    }
    $output .= '</table>	<!-- .wpcf-access-predefined-table -->';
    return $output;
}

/**
 * Renders custom caps types table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_edit_access_types_item($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= __('Set all capabilities to users of type:') . ''
            . self::wpcf_access_admin_roles_dropdown($roles,
                    'wpcf_access_bulk_set[' . $type_slug . ']', array(),
                    '-- ' . __('Choose user type', 'wpcf-access') . ' --', $enabled);
    $output .= self::wpcf_access_reset_button($type_slug, 'type', $enabled);
    $output .= '<table class="wpcf-access-caps-wrapper">';
    foreach ($data as $cap_slug => $cap_data)
    {
        $output .= '<tr>
						<td style="text-align:right;">';
        $output .= $cap_data['title'] . '<td/>
						<td>';
        $output .= self::wpcf_access_admin_roles_dropdown($roles,
                $name . '[' . $cap_slug . '][role]',
				$cap_data, false, $enabled);
        $output .= '<input
						type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $cap_slug . '"
						data-wpcfaccesscap="' . $cap_slug . '"
						data-wpcfaccessname="' . $name . '[' . $cap_slug . ']"
						value="' . $name . '[' . $cap_slug . ']" />';
        $output .= '</td>
					<td>';
        $output .= self::wpcf_access_admin_users_form(
				$cap_data,
                $name . '[' . $cap_slug . ']',
				$enabled);
        $output .= '</td>
				</tr>';
    }
    $output .= '</td></tr></table>';
    return $output;
}

/**
 * Renders custom caps tax table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_edit_access_tax_item($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= '<table class="wpcf-access-caps-wrapper">';
    foreach ($data as $cap_slug => $cap_data)
    {
        $output .= '<tr><td>';
        $output .= $cap_data['title'] . '<td/><td>';
        $output .= self::wpcf_access_admin_roles_dropdown($roles,
                $name . '[' . $cap_slug . '][role]', $cap_data, false, $enabled);
        $output .= '<input type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $cap_slug . '"
						value="' . $name . '[' . $cap_slug . ']" />';
        $output .= '</td><td>';
        $output .= self::wpcf_access_admin_users_form($cap_data, $name . '[' . $cap_slug . ']', $enabled);
        $output .= '</td></tr>';
    }
    $output .= '</table>';
    return $output;
}

/**
 * Reset caps button.
 *
 * @param type $type_slug
 * @param type $type
 * @return string
 */
public static function wpcf_access_reset_button( $type_slug, $type = 'type', $enabled = true, $managed = true ) {
    $output = '';
    $output .= '<input type="submit" id="wpcf-access-reset-' . md5($type_slug . $type)
			. '" class="button button-secondary wpcf-access-reset js-wpcf-access-reset"';
	$output .= ' href="'
					. admin_url('admin-ajax.php?action=wpcf_access_ajax_reset_to_default&amp;_wpnonce='
					. wp_create_nonce('wpcf_access_ajax_reset_to_default') . '&amp;type='
					. $type . '&amp;type_slug=' . $type_slug . '')
			. '" onclick="if (confirm(\''
			. addslashes(__('Are you sure? All permission settings for this type will change to their default values.', 'wpcf-access'))
			. '\')) { wpcfAccess.Reset(jQuery(this)); } return false;"';
    $output .= ' value="' . __('Reset to defaults', 'wpcf-access') . '" />';
    return $output;
}

/**
 * Submit button.
 *
 * @param type $enabled
 * @param type $managed
 * @return type
 */
public static function wpcf_access_submit_button( $enabled = true, $managed = true, $id = '' ) {
	ob_start();
	?>
	<button class="wpcf-access-submit-section otg-access-settings-section-save button-primary js-wpcf-access-submit-section js-otg-access-settings-section-save">
	<?php echo esc_html( __( 'Save ', 'wpcf-access' ) ); ?>
	</button>
	<?php
    return ob_get_clean();

}

/**
 * Custom roles form.
 *
 * @param type $roles
 * @return string
 */
public static function wpcf_access_admin_set_custom_roles_level_form($roles, $enabled = true)
{
    $levels = Access_Helper::wpcf_access_role_to_level_map();
    $builtin_roles = array();
    $default_roles = Access_Helper::wpcf_get_default_roles();
    $custom_roles = array();
    $output = '';

    foreach ($roles as $role => $details)
    {
        if (!in_array($role, $default_roles))
        {
            $compare = 'init';
            foreach ($details['capabilities'] as $capability => $true) {
                if (strpos($capability, 'level_') !== false && $true) {
                    $current_level = intval(substr($capability, 6));
                    if ($compare === 'init' || $current_level > intval($compare)) {
                        $compare = $current_level;
                    }
                }
            }
            $level = $compare !== 'init' ? $compare : 'not_set';
            $custom_roles[$level][$role] = $details;
            //$custom_roles[$level][$role]['name'] = taccess_t($details['name'], $details['name']);
            $custom_roles[$level][$role]['level'] = $compare !== 'init' ? $compare : 'not_set';
        }
        else if (isset($levels[$role]))
        {
            $level = intval(substr($levels[$role], 6));
            $builtin_roles[$level][$role] = $details;
            $builtin_roles[$level][$role]['name'] = translate_user_role($details['name']);
            $builtin_roles[$level][$role]['level'] = $level;
        }
    }

    $advanced_mode = get_option('otg_access_advaced_mode', 'false');

    if ( $advanced_mode == 'true' ){
        $advanced_mode_text = __('Enabled', 'wpcf-access');
    }else{
        $advanced_mode_text = __('Disabled', 'wpcf-access');
    }
       
    $output .= '<div id="wpcf-access-custom-roles-wrapper">';
	
	if ( empty( $custom_roles ) ) {
        $output .= '<p class="toolset-alert toolset-alert-info">'
                . __('No custom roles defined.', 'wpcf-access') .
				'</p>';
    }
	
	$output .= '<p>' . __('The user level determines which actions can be performed by each user in the WordPress admin.', 'wpcf-access') . '</p>';
    
    $output .= '<p>'. __('Advanced mode', 'wpcf-access') .': <button data-status="'. ( $advanced_mode == 'true' ? 'true':'false') .'" value="'. $advanced_mode_text .
    '" class="button button-large button-secondary js-otg_access_enable_advanced_mode"><i class="fa icon-'. ( $advanced_mode != 'true' ? 'lock fa-lock':'unlock fa-unlock') .'"></i>'. $advanced_mode_text .'</button></p>';
    $output .= '<div id="wpcf-access-custom-roles-table-wrapper">';
    $output .= '<table class="wpcf-access-custom-roles-table"><tbody>';
    for ($index = 10; $index >= 0; $index--)
    {
        $level_empty = true;
        $row = '<tr>
					<td>
						<div class="wpcf-access-roles-level">'
						    . sprintf(__('Level %d', 'wpcf-access'), $index)
					 . '</div>
					</td>
					<td>';
						if (isset($builtin_roles[$index])) {
							$level_empty = false;
							foreach ($builtin_roles[$index] as $role => $details) {
								$row .= '<div class="wpcf-access-roles wpcf-access-roles-builtin">';
									$row .= '<span>' . $details['name'] . '</span>';
									$row .= '<span>: : <a href="#" class="wpcf-access-view-caps" data-role="' . sanitize_title($role) . '">' . __('View permissions', 'wpcf-access') . '</a></span>';
								$row .= '</div>';
							}
						}
        if (isset($custom_roles[$index]))
        {
            $level_empty = false;
            foreach ($custom_roles[$index] as $role => $details) {
                $dropdown = '<div class="wpcf-access-custom-roles-select-wrapper js-access-custom-roles-selection">'
						      . '<select name="roles[' . $role . ']" class="wpcf-access-custom-roles-select">';
                for ($index2 = 10; $index2 > -1; $index2--)
                {
                    $dropdown .= '<option value="' . $index2 . '"';
                    if ($index == $index2) {
                        $dropdown .= ' selected="selected"';
                    }
                    if (!$enabled) {
                        $dropdown .= ' disabled="disabled"';
                    }
                    $dropdown .= '>' . sprintf(__('Level %d', 'wpcf-access'), $index2);
                    $dropdown .= '</option>';
                }
                $dropdown .= '</select>';
                $dropdown .= '<div class="button-group">
								<button ' . 'class="wpcf-access-change-level-cancel button button-secondary">' . __('Cancel') . '</button>
								<button ' . 'class="wpcf-access-change-level-apply button button-primary" data-message="' . __('By changing the level, you will reset the capabilities. Are you sure you want to do this?', 'wpcf-access') . '">' . __('Apply', 'wpcf-access') . '</button>
							</div>'
						. '</div>';
                $row .= '<div class="wpcf-access-roles wpcf-access-roles-custom">
							<span>' . taccess_t($details['name'], $details['name']) . '</span> ';
                if ( isset($details['capabilities']['wpcf_access_role']) || $advanced_mode == 'true' ) {
	                $row .= '<span>: : <a href="javascript:;" class="wpcf-access-change-level">' . __('Change level', 'wpcf-access') . '</a></span>';
	                $row .= $dropdown;

					//Change Caps link
					$row .= ' <span>: : <a href="#" class="wpcf-access-change-caps" data-role="' . sanitize_title($role) . '">' . __('Change permissions', 'wpcf-access') . '</a></span> ';
	                $row .= ' <span>: : <a ';
	                if ($enabled) {
						$row .= 'href="#" data-role="' . sanitize_title($role) . '" class="wpcf-access-delete-role js-wpcf-access-delete-role"';
	                } else {

	                    $row .= 'href="javascript:;"';
	                }
	                $row .= '>' . __('Delete role', 'wpcf-access') . '</a></span> ';
	                
                } else {

                	$row .= ' <span>: : <a href="#" class="wpcf-access-view-caps" data-role="' . sanitize_title($role) . '">' . __('View permissions', 'wpcf-access') . '</a></span> ';
				}
                $row .= '</div>';
            }
        }
        $row .= '</td></tr>';
        if (!$level_empty) {
            $output .= $row;
        }
    }

    if (isset($custom_roles['not_set']))
    {
        $output .= '<tr><td><div class="wpcf-access-roles-level">'
                . __('Undefined', 'wpcf-access') . '</div></td><td>';
        foreach ($custom_roles['not_set'] as $role => $details)
        {
            $dropdown = '<div class="wpcf-access-custom-roles-select-wrapper js-access-custom-roles-selection">'
						. '<select name="roles[' . $role . ']" class="wpcf-access-custom-roles-select">';
						for ($index2 = 10; $index2 >= 0; $index2--) {
							$dropdown .= '<option value="' . $index2 . '"';
							if ($index2 == 1) {
								$dropdown .= ' selected="selected"';
							}
							if (!$enabled) {
								$dropdown .= ' disabled="disabled"';
							}
							$dropdown .= '>'
									. sprintf(__('Level %d', 'wpcf-access'), $index2)
									. '</option>';
						}
            $dropdown .= '</select>
							<button class="wpcf-access-change-level-apply button-primary">' . __('Apply', 'wpcf-access') . '</button>
							<button class="wpcf-access-change-level-cancel button-secondary">' . __('Cancel') . '</button>'
					. '</div>';
            $output .= '<div class="wpcf-access-roles wpcf-access-roles-custom"><span>'
                    . $details['name'] . '</span>';
            if ( isset($details['capabilities']['wpcf_access_role']) || $advanced_mode == 'true' ) {
	            $output .= '<span>: : <a href="javascript:;" class="wpcf-access-change-level">' . __('Change level', 'wpcf-access') . '</a></span>';
	            $output .= $dropdown;

				//Change Caps link
				$output .= ' <span>: : <a href="#" class="wpcf-access-change-caps" data-role="' . sanitize_title($role) . '">' . __('Change permissions', 'wpcf-access') . '</a></span> ';
	            $output .= ' <span>: : <a ';
	            if ($enabled) {
					$output .= 'href="#" data-role="' . sanitize_title($role) . '" class="wpcf-access-delete-role js-wpcf-access-delete-role"';
	            } else {
	                $output .= 'href="javascript:;"';
	            }
	            $output .= '>' . __('Delete role', 'wpcf-access') . '</a></span> ';	                
            } else {
                $output .= ' <span>: : <a href="#" class="wpcf-access-view-caps" data-role="' . sanitize_title($role) . '">' . __('View permissions', 'wpcf-access') . '</a></span> ';
			}
                    
            
            
        }
        $output .= '</div></td></tr>';
    }
    $output .= '</tbody></table>';
    $output .= '</div>';
    $output .= '</div>';
    return $output;
}

/**
 * Get Content Template title
 * @param $id
 *
 */
public static function get_content_template_name( $id ) {
	global $wpdb;
	$template_list = wp_cache_get( 'access_templates_list' );
	if ( false === $template_list ) {
		$template_list = $wpdb->get_results( "SELECT ID,post_title FROM  `$wpdb->posts` WHERE post_type =  'view-template'" );
		$new_template_new_list = array();
		for ($i=0, $templates_count=count($template_list);$i<$templates_count;$i++) {
			$new_template_new_list[$template_list[$i]->ID] = $template_list[$i]->post_title;
		}
		wp_cache_add( 'access_templates_list', $new_template_new_list);
		if ( isset($new_template_new_list[$id]) ){
			return 	$new_template_new_list[$id];
		}
	}else{
		if ( isset($template_list[$id]) ){
			return $template_list[$id];
		}
	}
	return '';
}

/**
 * Get Content Template title
 * @param $id
 *
 */
public static function get_view_name( $id ) {
	$view = get_post($id);
	if ( is_object($view) ){
		return $view->post_title;
	}
}

/**
 * HTML formatted permissions table.
 *
 * @param type $roles
 * @param type $permissions
 * @param type $name
 * @return string
 */
public static function wpcf_access_permissions_table($roles, $permissions, $settings,
        $group_id, $id, $enabled = true, $managed = true, $custom_errors = array(), $type_data = array()) {

    $ordered_roles = Access_Helper::wpcf_access_order_roles_by_level($roles);
    $default_roles = Access_Helper::wpcf_get_default_roles();
    $output = '';
    $output .= '<table class="wpcf-access-table js-access-table">';
		$output .= '<tr>';
			$output .= '<th>' . __('Action', 'wpcf-access') . '</th>';
			foreach ($ordered_roles as $levels => $roles_data)
			{
				if (empty($roles_data))
					continue;

				$title = '';
				foreach ($roles_data as $role => $details)
				{
					if (in_array($role, $default_roles))
						$title .= '<p class="access-role-name-wrap js-tooltip"><span class="access-role-name">' . translate_user_role($details['name']) . '</span></p>';
					else
						$title .= '<p class="access-role-name-wrap js-tooltip"><span class="access-role-name">' . taccess_t($details['name'], $details['name']) . '</span></p>';
				}
				$output .= '<th>' . $title . '</th>';
			}
			// Add Guest
			$output .= '<th>' . __('Guest', 'wpcf-access') . '</th>';
			$output .= '<th>' . __('Specific user', 'wpcf-access') . '</th>';

			if ( $group_id == 'types' && $id != 'attachment') {
				$output .= '<th>' . __('When disabled', 'wpcf-access') . '</th>';
			}
		$output .= '</tr>';
    $output .= '<tbody>';
    foreach ($settings as $permission_slug => $data)
    {
        // Change slug for 3rd party
        if (!in_array($group_id, array('types', 'tax'))) {
            $permission_slug = $data['cap_id'];
			$managed = true;
        }
        $check = true;
        $output .= '<tr>';
		$output .= '<td class="wpcf-access-table-action-title">' . $data['title'] . '</td>';
        $name = 'types_access[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][role]';
		
        // If no settings saved use default setting [role]
        $role_check = !empty($permissions[$permission_slug]['role']) ? $permissions[$permission_slug]['role'] : $data['role'];
		$template_link = '';
		
		if ( isset($custom_errors['_custom_read_errors'][$id]) && $permission_slug == 'read' ) {
			$current_custom_errors = $custom_errors['_custom_read_errors'][$id]['permissions']['read'];
			$current_custom_errors_value = $custom_errors['_custom_read_errors_value'][$id]['permissions']['read'];
		}
		if ( isset($custom_errors['_archive_custom_read_errors'][$id]) && $permission_slug == 'read' ) {
		
			$current_archive_custom_errors = $custom_errors['_archive_custom_read_errors'][$id]['permissions']['read'];
			$current_archive_custom_errors_value = $custom_errors['_archive_custom_read_errors_value'][$id]['permissions']['read'];
		}

        foreach ($ordered_roles as $levels => $roles_data)
        {
            if (empty($roles_data))
                continue;
            $addon = '';
			
            // Render only first (built-in)
            $role = key($roles_data);
            $details = array_shift($roles_data);

			if ( $permission_slug == 'read'  && $role != 'administrator' && $id != 'attachment' ) {
				$addon_id = $group_id . '_' . $id . '_error_page_' . $permission_slug . '_' . $role . '_role';
				$error_value_value = $error_type_value = $archive_error_value_value = $archive_error_type_value = $text = $archive_text =  '';

				$link_title = '';
				if ( isset($current_custom_errors[$role]) && !empty($current_custom_errors[$role]) ) {
					$error_type_value = $current_custom_errors[$role];
					$error_value_value = $current_custom_errors_value[$role];
					if ( $error_type_value == 'error_404' ) {
						$text = '404';
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$text = __('Template', 'wpcf-access').': '. self::get_content_template_name($error_value_value);
						$link_title = __('Show Content Template','wpcf-access').' - '.self::get_content_template_name($error_value_value);
					} else {
						$text = __('PHP Template', 'wpcf-access').': '. $error_value_value;
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}
				elseif ( isset($current_custom_errors['everyone']) && !empty($current_custom_errors['everyone']) ) {
					if ( $error_type_value == 'error_404' ) {
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$link_title = __('Show Content Template','wpcf-access').' - '.self::get_content_template_name($error_value_value);
					} else {
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}
				
				//Set Archive Errors
				if ( isset($current_archive_custom_errors[$role]) && !empty($current_archive_custom_errors[$role]) && isset($type_data['has_archive']) && $type_data['has_archive'] == 1  ) {
					$archive_error_type_value = $current_archive_custom_errors[$role];
					$archive_error_value_value = $current_archive_custom_errors_value[$role];
					if ( $archive_error_type_value == 'default_error' ) {
						$archive_text = __('Display: \'No posts found\'','wpcf-access');
					} elseif ( $archive_error_type_value == 'error_ct' ) {
						$archive_text = __('View Archive', 'wpcf-access').': '. self::get_view_name($archive_error_value_value);
					} elseif ( $archive_error_type_value == 'error_php' ) {
						$archive_text = __('PHP Archive', 'wpcf-access').': '. preg_replace("/.*(\/.*\/)/","$1",$archive_error_value_value);
					}else{
						$archive_text = '';	
					}
					
				}
				
				
				$is_archive = '';
				$archive_vars = '';
				if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
					$is_archive = 1;
					$link_title = ' title="'.__('Set errors','wpcf-access').'" ';
				}else{
					if ( !empty($link_title) ){
					$link_title = ' title="'.__('Set single page error','wpcf-access').'. ('.$link_title.')" ';
					}else{
						$link_title = ' title="'.__('Set single page error','wpcf-access').'" ';
					}	
				}
				$error_type = 'types_access_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$error_value = 'types_access_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$archive_error_type = 'types_access_archive_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$archive_error_value = 'types_access_archive_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
//				$hide_link = $check ? ' style="display:none;" ' : '';
		        $addon = '<a '.$link_title.'class="wpcf-add-error-page js-wpcf-add-error-page"'
		        	.' data-typename="' . $error_type . '" data-valuename="' . $error_value . '"  data-curtype="'. $error_type_value . '" data-curvalue="'. $error_value_value . '"'
		        	.' data-archivetypename="' . $archive_error_type . '" data-archivevaluename="' . $archive_error_value . '"  data-archivecurtype="'. $archive_error_type_value . '" data-archivecurvalue="'. $archive_error_value_value . '"'
					. ' data-posttype="'.$id.'" data-archive="'.$is_archive.'" data-forall="0" href=""><i class="icon-edit fa fa-pencil-square-o"></i></a>';
						
						//Labels
						$addon .= '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">' . $text . '</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">' . $archive_text . '</span></p>'
						//Errors inputs
						. '<input type="hidden" name="' . $error_type . '" value="' . $error_type_value.'">
						<input type="hidden" name="' . $error_value . '" value="' . $error_value_value .'">';
						if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
							$addon .= '<input type="hidden" name="' . $archive_error_type . '" value="' . $archive_error_type_value.'">
							<input type="hidden" name="' . $archive_error_value . '" value="' . $archive_error_value_value .'">';
						}	
			}

            $att_id = $group_id . '_' . $id . '_permissions_' . $permission_slug . '_' . $role . '_role';
            $attributes = $check ? ' checked="checked" ' : '';
            $attributes .=!$managed ? ' readonly="readonly" disabled="disabled" ' : '';
            $output .= '<td><div class="error-page-set-wrap"><input type="checkbox" name="';
            $output .= $role_check == $role ? $name : 'dummy';
            $output .= '" id="' . $att_id . '" value="' . $role . '"'
                    . $attributes . ' class="wpcf-access-check-left wpcf-access-'
                    . $permission_slug . '" data-wpcfaccesscap="'
                    . $permission_slug . '" data-wpcfaccessname="'
                    . $name . '" ' . 'onclick="wpcfAccess.AutoThick(jQuery(this), \''
                    . $permission_slug . '\', \''
                    . $name . '\');"';
            if (!$enabled) {
                $output .= ' disabled="disabled" readonly="readonly"';
            }
            $output .= '/>'. $addon .'</div></td>';
            // Turn off onwards checking
            if ($role_check == $role) {
                $check = false;
            }
        }
        // Add Guest
        $name = 'types_access[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][role]';
        $attributes = $check ? ' checked="checked"' : '';
        $attributes .=!$managed ? ' readonly="readonly" disabled="disabled"' : '';
		$addon = '';
		if ( $permission_slug == 'read' && $id != 'attachment'  ) {
				$addon_id = $group_id . '_' . $id . '_error_page_' . $permission_slug . '_' . $role . '_role';
				$error_value_value = $error_type_value = $archive_error_value_value = $archive_error_type_value = $text = $archive_text =  '';
				$link_title = '';
				if ( isset($current_custom_errors['guest']) && !empty($current_custom_errors['guest']) ) {
					$error_type_value = $current_custom_errors['guest'];
					$error_value_value = $current_custom_errors_value['guest'];
						if ( $error_type_value == 'error_404' ) {
							$text = '404';
							$link_title = __('Show 404 - page not found','wpcf-access');
						} elseif ( $error_type_value == 'error_ct' ) {
							$text = __('Template', 'wpcf-access').': '.self::get_content_template_name($error_value_value);
							$link_title = __('Show Content Template','wpcf-access').' - '.self::get_content_template_name($error_value_value);
						} else {
							$text = __('PHP Template', 'wpcf-access').': '. $error_value_value;
							$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
						}
				}
				elseif ( isset($current_custom_errors['everyone']) && !empty($current_custom_errors['everyone']) ) {
					
					if ( $error_type_value == 'error_404' ) {
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$link_title = __('Show Content Template','wpcf-access').' - '.self::get_content_template_name($error_value_value);
					} else {
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}
				//Set Archive Errors
				if ( isset($current_archive_custom_errors['guest']) && !empty($current_archive_custom_errors['guest']) && isset($type_data['has_archive']) && $type_data['has_archive'] == 1  ) {
					$archive_error_type_value = $current_archive_custom_errors['guest'];
					$archive_error_value_value = $current_archive_custom_errors_value['guest'];
					if ( $archive_error_type_value == 'default_error' ) {
						$archive_text = __('Display: \'No posts found\'','wpcf-access');
					} elseif ( $archive_error_type_value == 'error_ct' ) {
						$archive_text = __('View Archive', 'wpcf-access').': '. self::get_view_name($archive_error_value_value);
					} elseif ( $archive_error_type_value == 'error_php' ) {
						$archive_text = __('PHP Archive', 'wpcf-access').': '. preg_replace("/.*(\/.*\/)/","$1",$archive_error_value_value);
					}else{
						$archive_text = '';	
					}
					
				}
				
				
				$is_archive = '';
				$archive_vars = '';
				if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
					$is_archive = 1;
					$link_title = ' title="'.__('Set errors','wpcf-access').'" ';
				}else{
					if ( !empty($link_title) ){
					$link_title = ' title="'.__('Set single page error','wpcf-access').'. ('.$link_title.')" ';
					}else{
						$link_title = ' title="'.__('Set single page error','wpcf-access').'" ';
					}	
				}
				$error_type = 'types_access_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][guest]';
				$error_value = 'types_access_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][guest]';
				$archive_error_type = 'types_access_archive_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][guest]';
				$archive_error_value = 'types_access_archive_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][guest]';
				$hide_link = $check ? ' style="display:none;" ' : '';
		        $addon = '<a '.$link_title.'class="wpcf-add-error-page js-wpcf-add-error-page"'
		        	.' data-typename="' . $error_type . '" data-valuename="' . $error_value . '"  data-curtype="'. $error_type_value . '" data-curvalue="'. $error_value_value . '"'
		        	.' data-archivetypename="' . $archive_error_type . '" data-archivevaluename="' . $archive_error_value . '"  data-archivecurtype="'. $archive_error_type_value . '" data-archivecurvalue="'. $archive_error_value_value . '"'
					. ' data-posttype="'.$id.'" data-archive="'.$is_archive.'" data-forall="0" href=""><i class="icon-edit fa fa-pencil-square-o"></i></a>';
						
						//Labels
						$addon .= '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">' . $text . '</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">' . $archive_text . '</span></p>'
						//Errors inputs
						. '<input type="hidden" name="' . $error_type . '" value="' . $error_type_value.'">
						<input type="hidden" name="' . $error_value . '" value="' . $error_value_value .'">';
						if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
							$addon .= '<input type="hidden" name="' . $archive_error_type . '" value="' . $archive_error_type_value.'">
							<input type="hidden" name="' . $archive_error_value . '" value="' . $archive_error_value_value .'">';
						}	
		}
        $output .= '<td><div class="error-page-set-wrap"><input type="checkbox" name="';
        $output .= $role_check == 'guest' ? $name : 'dummy';
        $output .= '" id="' . $group_id . '_' . $id . '_permissions_'
                . $permission_slug . '_guest_role" value="guest"'
                . $attributes . ' class="wpcf-access-check-left wpcf-access-'
                . $permission_slug . '" data-wpcfaccesscap="'
                . $permission_slug . '" data-wpcfaccessname="'
                . $name . '" ' . 'onclick="wpcfAccess.AutoThick(jQuery(this), \''
                . $permission_slug . '\', \''
                . $name . '\');"';
        if (!$enabled) {
            $output .= ' disabled="disabled" readonly="readonly"';
        }
        $output .= ' />'.$addon;

        // Add admin if all disabled
        $output .= '<input type="hidden" name="types_access[' . $group_id . '][' . $id . '][__permissions]' . '[' . $permission_slug . '][role]" value="administrator" />';
        $output .= '</div></td>';

        $data['users'] = !empty($permissions[$permission_slug]['users']) ? $permissions[$permission_slug]['users'] : array();
        $output .= '<td>'
                . '<input type="hidden" class="wpcf-access-name-holder" name="wpcf_access_'
                . $id . '_' . $permission_slug . '" data-wpcfaccesscap="'
                . $permission_slug . '" data-wpcfaccessname="'
                . 'types_access[' . $group_id . ']['
                . $id . ']'
                . '[permissions][' . $permission_slug . ']" value="types_access[' . $group_id . ']['
                . $id . ']'
                . '[permissions][' . $permission_slug . ']" />'
                . self::wpcf_access_admin_users_form($data,
                        'types_access[' . $group_id . '][' . $id . '][permissions]'
                        . '[' . $permission_slug . ']', $enabled, $managed)
                . '</td>';

		if ( $permission_slug == 'read'  && $id != 'attachment'  ) {
    		$addon_id = $group_id . '_' . $id . '_error_page_' . $permission_slug . '_' . $role . '_role';
				$link_title = '';
				$error_value_value = $error_type_value = $archive_error_value_value = $archive_error_type_value = $text = $archive_text =  '';
				if ( isset($current_custom_errors['everyone']) && !empty($current_custom_errors['everyone']) ) {
					$error_type_value = $current_custom_errors['everyone'];
					$error_value_value = $current_custom_errors_value['everyone'];
					if ( $error_type_value == 'error_404' ) {
						$text = '404';
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$text = __('Template', 'wpcf-access').': '.self::get_content_template_name($error_value_value);
						$link_title = __('Show Content Template','wpcf-access').' - '.self::get_content_template_name($error_value_value);
					} else {
						$text = __('PHP Template', 'wpcf-access').': '. $error_value_value;
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}

				//Set Archive Errors
				if ( isset($current_archive_custom_errors['everyone']) && !empty($current_archive_custom_errors['everyone']) && isset($type_data['has_archive']) && $type_data['has_archive'] == 1  ) {
					$archive_error_type_value = $current_archive_custom_errors['everyone'];
					$archive_error_value_value = $current_archive_custom_errors_value['everyone'];
					if ( $archive_error_type_value == 'default_error' ) {
						$archive_text = __('Display: \'No posts found\'','wpcf-access');
					} elseif ( $archive_error_type_value == 'error_ct' ) {
						$archive_text = __('View Archive', 'wpcf-access').': '. self::get_view_name($archive_error_value_value);
					} elseif ( $archive_error_type_value == 'error_php' ) {
						$archive_text = __('PHP Archive', 'wpcf-access').': '. preg_replace("/.*(\/.*\/)/","$1",$archive_error_value_value);
					}else{
						$archive_text = '';	
					}
					
				}
				
				
				$is_archive = '';
				$archive_vars = '';
				if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
					$is_archive = 1;
					$link_title = ' title="'.__('Set errors','wpcf-access').'" ';
				}else{
					if ( !empty($link_title) ){
					$link_title = ' title="'.__('Set single page error','wpcf-access').'. ('.$link_title.')" ';
					}else{
						$link_title = ' title="'.__('Set single page error','wpcf-access').'" ';
					}	
				}
				$error_type = 'types_access_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$error_value = 'types_access_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$archive_error_type = 'types_access_archive_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$archive_error_value = 'types_access_archive_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
		        $addon = '<a '.$link_title.'class="wpcf-add-error-page js-wpcf-add-error-page"'
		        	.' data-typename="' . $error_type . '" data-valuename="' . $error_value . '"  data-curtype="'. $error_type_value . '" data-curvalue="'. $error_value_value . '"'
		        	.' data-archivetypename="' . $archive_error_type . '" data-archivevaluename="' . $archive_error_value . '"  data-archivecurtype="'. $archive_error_type_value . '" data-archivecurvalue="'. $archive_error_value_value . '"'
					. ' data-posttype="'.$id.'" data-archive="'.$is_archive.'" data-forall="1" href=""><i class="icon-edit fa fa-pencil-square-o"></i></a>';
						
						//Labels
						$addon .= '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">' . $text . '</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">' . $archive_text . '</span></p>'
						//Errors inputs
						. '<input type="hidden" name="' . $error_type . '" value="' . $error_type_value.'">
						<input type="hidden" name="' . $error_value . '" value="' . $error_value_value .'">';
						if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
							$addon .= '<input type="hidden" name="' . $archive_error_type . '" value="' . $archive_error_type_value.'">
							<input type="hidden" name="' . $archive_error_value . '" value="' . $archive_error_value_value .'">';
						}	
				$output .= '<td>' . $addon . '</td>';
		}
		$output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';
    return $output;
}

/**
 * Suggest user form.
 *
 * @global type $wpdb
 * @return string
 */
public static function wpcf_access_suggest_user( $enabled = true, $managed = false, $name = '' )
{
    static $_id=0;
    global $wpdb;

    // Select first 5 users
    $users = $wpdb -> get_results("SELECT ID, user_login, display_name FROM $wpdb->users LIMIT 5");
    $output = '';
    $_id++;
	$first_class = ' dropdown_bottom';	
	//if ( strpos($name, '[read]') > 0 || strpos($name, '[view_fields_in_edit_page') > 0 || strpos($name, '[__CRED_CRED]') > 0 ){
	//	$first_class = ' dropdown_bottom';	
	//}
    $output = '<div class="types-suggest-user types-suggest" id="types-suggest-user-' . $_id . '">';
		$output .= '<input type="text" class="input" placeholder="' . esc_attr__('search', 'wpcf-access') . '"';
		if (!$enabled || !$managed) {
			$output .= ' readonly="readonly" disabled="disabled"';
		}
		$output .= ' />';
		$output .= '<img src="' . esc_url(admin_url('images/wpspin_light.gif')) . '" class="img-waiting" alt="" />';
		$output .= '<div class="button-group js-suggest-user-controls"><button class="cancel toggle button button-small button-secondary">' . __('Cancel', 'wpcf-access') . '</button> ';
		$output .= '<button class="confirm toggle button button-small button-primary">' . __('OK', 'wpcf-access') . '</button></div>';
		$output .= '<select size="' . count($users) . '" class="dropdown'.$first_class.'">';
		foreach ($users as $u) {
			$output .= '<option value="' . $u->ID . '">' . $u->display_name . ' (' . $u->user_login . ')' . '</option>';
		}
		$output .= '</select>';
    $output .= '</div>';
    return $output;
}

/**
 * New role form.
 *
 * @return string
 */
public static function wpcf_access_new_role_form( $enabled ) {
    $output = '';
    $output .= '<div id="wpcf-access-new-role" class="wpcf-access-new-role-wrap js-otg-access-new-role-wrap">';
		$output .= '<button class="button button-large button-secondary js-otg-access-add-new-role"';
		if ( ! $enabled ) {
			$output .= ' disabled="disabled" readonly="readonly"';
		}
		$output .= '><i class="icon-plus fa fa-plus"></i>' . __('Add a new role', 'wpcf-access') . '</button>';

		$output .= '<div class="otg-access-new-role-extra js-otg-access-new-role-extra" style="display:none">';
			$output .= '<input type="text" name="types_access[new_role]" class="js-otg-access-new-role-name" value="" /> ';
			$output .= '<button class="button-secondary js-otg-access-new-role-cancel">' . __('Cancel', 'wpcf-access') . '</button> ';
			$output .= '<button class="button-secondary js-otg-access-new-role-apply" disabled="disabled">' . __('Create role', 'wpcf-access') . '</button> ';
			$output .= '<p>'
				. __( 'Give the new role a name (5 characters minimum)', 'wpcf-access' )
				. '</p>';
		$output .= '</div>';
		$output .= '<div class="ajax-response js-otg-access-message-container"></div>';
    $output .= '</div>	<!-- #wpcf-access-new-role -->';
    return $output;
}

}
