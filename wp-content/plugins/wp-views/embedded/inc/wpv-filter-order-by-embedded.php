<?php

/**
* wpv-filter-order-by-embedded.php
*
* @package Views
*
* @since unknown
*/

/**
* WPV_Sorting_Embedded
*
* @since 1.11
*/

class WPV_Sorting_Embedded {
	
	public function __construct() {
		add_action( 'init',											array( $this, 'init' ) );	
    }
	
	function init() {
		$this->register_shortcodes();
		
		// Legacy
		add_action( 'wpv_action_wpv_pagination_map_legacy_order',	array( $this, 'map_legacy_order' ) );
		
		add_filter( 'wpv_view_settings',							array( $this, 'sorting_defaults' ) );
		add_filter( 'wpv_filter_wpv_get_sorting_defaults',			array( $this, 'sorting_defaults' ) );
		
		add_filter( 'wpv_filter_query',								array( $this, 'set_post_view_sorting' ), 100, 2 );
		add_filter( 'wpv_filter_wpv_apply_post_view_sorting',		array( $this, 'set_post_view_sorting' ), 10, 2 );
		
		add_filter( 'wpv_filter_taxonomy_query',					array( $this, 'set_taxonomy_view_sorting' ), 10, 3 );
		add_filter( 'wpv_filter_wpv_apply_taxonomy_view_sorting',	array( $this, 'set_taxonomy_view_sorting' ), 10, 3 );
		add_filter( 'wpv_filter_taxonomy_post_query',				array( $this, 'set_taxonomy_view_sorting_post_query' ), 30, 4 );
		
		add_filter( 'wpv_filter_user_query',						array( $this, 'set_user_view_sorting' ), 40, 2 );
		add_filter( 'wpv_filter_wpv_apply_user_view_sorting',		array( $this, 'set_user_view_sorting' ), 10, 2 );
		
	}
	
	function register_shortcodes() {
		
	}
	
	/**
	* @since 2.0
	*/
	
	function map_legacy_order() {
		if ( isset( $_GET['wpv_column_sort_id'] ) ) {
			$_GET['wpv_sort_orderby'] = $_GET['wpv_column_sort_id'];
			unset( $_GET['wpv_column_sort_id'] );
		}
		if ( isset( $_GET['wpv_column_sort_dir'] ) ) {
			$_GET['wpv_sort_order'] = $_GET['wpv_column_sort_dir'];
			unset( $_GET['wpv_column_sort_dir'] );
		}
	}
	
	function sorting_defaults( $view_settings ) {
		if ( ! isset( $view_settings['orderby'] ) ) {
			$view_settings['orderby'] = 'post_date';
		}
		if ( ! isset( $view_settings['order'] ) ) {
			$view_settings['order'] = 'DESC';
		}
		if ( ! isset( $view_settings['taxonomy_orderby'] ) ) {
			$view_settings['taxonomy_orderby'] = 'name';
		}
		if ( ! isset( $view_settings['taxonomy_order'] ) ) {
			$view_settings['taxonomy_order'] = 'DESC';
		}
		if ( ! isset( $view_settings['users_orderby'] ) ) {
			$view_settings['users_orderby'] = 'user_login';
		}
		if ( ! isset( $view_settings['users_order'] ) ) {
			$view_settings['users_order'] = 'DESC';
		}
		if( ! isset( $view_settings['orderby_as'] ) ) {
			$view_settings['orderby_as'] = '';
		}
		if( ! isset( $view_settings['taxonomy_orderby_as'] ) ) {
			$view_settings['taxonomy_orderby_as'] = '';
		}
		return $view_settings;
	}
	
	function set_post_view_sorting( $query, $view_settings ) {
		
		$is_view_posted = false;
		if ( isset( $_GET['wpv_view_count'] ) ) {
			$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
			if ( esc_attr( $_GET['wpv_view_count'] ) == $view_unique_hash ) {
				$is_view_posted = true;
				// Map old URL parameters to new ones
				do_action( 'wpv_action_wpv_pagination_map_legacy_order' );
			}
		}
		
		$orderby	= $view_settings['orderby'];
		$order		= $view_settings['order'];
		$orderby_as	= $view_settings['orderby_as'];
		// Override with attributes
		$override_allowed = array(
			'orderby'		=> array(),
			'order'			=> array( 'asc', 'ASC', 'desc', 'DESC' ),
			'orderby_as'	=> array( '', 'string', 'STRING', 'numeric', 'NUMERIC' )
		);
		$override_values = wpv_override_view_orderby_order( $override_allowed );
		if ( isset( $override_values['orderby'] ) ) {
			$orderby = $override_values['orderby'];
		}
		if ( isset( $override_values['order'] ) ) {
			$order = strtoupper( $override_values['order'] );
		}
		if ( isset( $override_values['orderby_as'] ) ) {
			$orderby_as = strtoupper( $override_values['orderby_as'] );
		}
		
		// Override with URL parameters
		
		// Legacy order URL override
		if ( 
			$is_view_posted
			&& isset( $_GET['wpv_order'] ) 
			&& isset( $_GET['wpv_order'][0] )
			&& in_array( $_GET['wpv_order'][0], array( 'ASC', 'DESC' ) )
		) {
			$order = esc_attr( $_GET['wpv_order'][0] );
		}
		// Modern order URL override
		if ( $is_view_posted ) {
			if (
				isset( $_GET['wpv_sort_order'] ) 
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$order = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
			}
			if (
				isset( $_GET['wpv_sort_orderby'] ) 
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != 'undefined' 
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != '' 
			) {
				$orderby = esc_attr( $_GET['wpv_sort_orderby'] );
			}

			if (
				isset( $_GET['wpv_sort_orderby_as'] )
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( 'STRING', 'NUMERIC' ) )
			) {
				$orderby_as = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
			}
		}
		
		// Adjust values for custom field sorting
		
		if ( strpos( $orderby, 'field-' ) === 0 ) {
			// Natural Views sorting by custom field
			$query['meta_key'] = substr( $orderby, 6 );
			$orderby = 'meta_value';
		} else if ( strpos( $orderby, 'post-field' ) === 0 ) {
			// Table sorting for custom field
			$query['meta_key'] = substr( $orderby, 11 );
			$orderby = 'meta_value';
		} else if ( strpos( $orderby, 'types-field' ) === 0 ) {
			// Table sorting for Types custom field
			$query['meta_key'] = strtolower( substr( $orderby, 12 ) );
			$orderby = 'meta_value';
		} else {
			$orderby = str_replace( '-', '_', $orderby );
		}
		
		if ( 
			'meta_value' == $orderby 
			&& isset( $query['meta_key'] )
		) {
			$is_types_field_data = wpv_is_types_custom_field ( $query['meta_key'] );

			if (
				$is_types_field_data 
				&& isset( $is_types_field_data['meta_key'] ) 
				&& isset( $is_types_field_data['type'] )
			) {
				$query['meta_key'] = $is_types_field_data['meta_key'];

				// User preference overrides the auto-discover
				if (
						isset( $orderby_as )
						&& in_array( $orderby_as, array( 'STRING', 'NUMERIC' ) )
				) {
					switch ( $orderby_as ) {
						case "STRING":
							$orderby = 'meta_value';
							break;
						case "NUMERIC":
							$orderby = 'meta_value_num';
							break;
					}
				} elseif ( in_array( $is_types_field_data['type'], array( 'numeric', 'date' ) ) ) {	// Auto-Discover
					$orderby = 'meta_value_num';
				}
			}
		}
		
		// Correct orderby options
		switch ( $orderby ) {
			case 'post_link':
				$orderby = 'post_title';
				break;
			case 'post_body':
				$orderby = 'post_content';
				break;
			case 'post_slug':
				$orderby = 'name';
				break;
			case 'post_id':
			case 'id':
				$orderby = 'ID';
				break;
			default:
				if ( strpos( $orderby, 'post_' ) === 0 ) {
					$orderby = substr( $orderby, 5 );
				}
				break;
		}
		
		$query['orderby']	= $orderby;
		$query['order']		= $order;
		
		// See if filtering by custom fields and sorting by custom field too
		if (
			isset( $query['meta_key'] ) 
			&& isset( $query['meta_query'] )
		) {
			// We only need to do something if the relation is OR
			// When the relation is AND it does not matter if we sort by one of the filtering fields, because the filter will add an existence clause anyway
			// When the relation is OR, the natural query will generate an OR clause on the sorting field existence:
			// - if it is one of the filtering fields, it will make its clause useless because just existence will make it pass
			// - if it is not one of the filtering fields it will add an OR clause on this field existence that might pass for results that do not match any of the other requirements
			// See also: https://core.trac.wordpress.org/ticket/25538
			// Since WordPress 4.1 this is indeed not needed, thanks to nested meta_query entries
			if ( 
				isset( $query['meta_query']['relation'] ) 
				&& $query['meta_query']['relation'] == 'OR' 
			) {
				global $wp_version;
				if ( version_compare( $wp_version, '4.1', '<' ) ) {
					$refinedquery = $query;
					unset( $refinedquery['orderby'] );
					unset( $refinedquery['meta_key'] );
					$refinedquery['posts_per_page'] = -1; // remove the limit in the main query to get all the relevant IDs
					$refinedquery['fields'] = 'ids';
					// first query only for filtering
					$filtered_query = new WP_Query( $refinedquery );
					$filtered_ids = array();
					if ( 
						is_array( $filtered_query->posts ) 
						&& !empty( $filtered_query->posts ) 
					) {
						$filtered_ids = $filtered_query->posts;
					}
					// remove the fields filter from the original query and add the filtered IDs
					unset( $query['meta_query'] );
					// we can replace the $query['post__in'] argument because it was applied on the auxiliar query before
					if ( count( $filtered_ids ) ) {
						$query['post__in'] = $filtered_ids;
					} else {
						$query['post__in'] = array('0');
					}
				}
			}
			
		}
		
		return $query;
	}
	
	function set_taxonomy_view_sorting( $tax_query_settings, $view_settings, $view_id ) {
		$is_view_posted = false;
		if ( isset( $_GET['wpv_view_count'] ) ) {
			$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
			if ( esc_attr( $_GET['wpv_view_count'] ) == $view_unique_hash ) {
				$is_view_posted = true;
				// Map old URL parameters to new ones
				do_action( 'wpv_action_wpv_pagination_map_legacy_order' );
			}
		}

		$orderby =	$view_settings['taxonomy_orderby'];
		$order =	$view_settings['taxonomy_order'];
		$orderby_as = $view_settings['taxonomy_orderby_as'];
		// Override with attributes
		$override_allowed = array(
			'orderby'		=> array(),
			'order'			=> array( 'asc', 'ASC', 'desc', 'DESC' ),
			'orderby_as'	=> array( '', 'string', 'STRING', 'numeric', 'NUMERIC' )
		);
		$override_values = wpv_override_view_orderby_order( $override_allowed );
		if ( 
			isset( $override_values['orderby'] ) 
			&& in_array( $override_values['orderby'], $override_allowed['orderby'] )
		) {
			$orderby = $override_values['orderby'];
		}
		if ( isset( $override_values['order'] ) ) {
			$order = strtoupper( $override_values['order'] );
		}
		if ( isset( $override_values['orderby_as'] ) ) {
			$orderby_as = strtoupper( $override_values['orderby_as'] );
		}
		
		// Override with URL parameters
		if ( $is_view_posted ) {
			// Orderby
			if (
				isset( $_GET['wpv_sort_orderby'] ) 
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != 'undefined' 
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != '' 
			) {
				$orderby = esc_attr( $_GET['wpv_sort_orderby'] );
			}
			// Order
			if (
				isset( $_GET['wpv_sort_order'] ) 
				&& esc_attr( $_GET['wpv_sort_order'] ) != '' 
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$order = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
			}

			if (
					isset( $_GET['wpv_sort_orderby_as'] )
					&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( 'STRING', 'NUMERIC' ) )
			) {
				$orderby_as = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
			}
			
		}
		
		// Correct orderby options
		switch ( $orderby ) {
			case 'taxonomy-link':
			case 'taxonomy-title':
				$orderby = 'name';
				break;
			case 'taxonomy-post_count':
				$orderby = 'count';
				break;
			case 'taxonomy-id':
				$orderby = 'id';
				break;
			case 'taxonomy-slug':
				$orderby = 'slug';
				break;
			default:
				if ( strpos( $orderby, 'taxonomy-field-' ) === 0 ) {
					global $wp_version;
					if ( version_compare( $wp_version, '4.5', '<' ) ) {
						$orderby = 'name';
					} else {
						$tax_query_settings['meta_key'] = substr( $orderby, 15 );
						$orderby = 'meta_value';
					}
				}
				break;
		}
		
		if ( 
			'meta_value' == $orderby 
			&& isset( $tax_query_settings['meta_key'] )
		) {
			$is_types_field_data = wpv_is_types_custom_field( $tax_query_settings['meta_key'], 'tf' );

			if (
					$is_types_field_data
					&& isset( $is_types_field_data['meta_key'] )
					&& isset( $is_types_field_data['type'] )
			) {
				$tax_query_settings['meta_key'] = $is_types_field_data['meta_key'];

				// User preference overrides the auto-discover
				if (
						isset( $orderby_as )
						&& in_array( $orderby_as, array( 'STRING', 'NUMERIC' ) )
				) {
					switch ( $orderby_as ) {
						case "STRING":
							$orderby = 'meta_value';
							break;
						case "NUMERIC":
							$orderby = 'meta_value_num';
							break;
					}
				} elseif ( in_array( $is_types_field_data['type'], array( 'numeric', 'date' ) ) ) {	// Auto-Discover
					$orderby = 'meta_value_num';
				}
			}
		}
		
		$tax_query_settings['orderby']	= $orderby;
		$tax_query_settings['order']	= $order;

		return $tax_query_settings;
	}
	
	function set_taxonomy_view_sorting_post_query( $items, $tax_query_settings, $view_settings, $view_id ) {
		if ( 
			$tax_query_settings['orderby'] == 'count' 
			&& $tax_query_settings['pad_counts']
		) {
			if ( $tax_query_settings['order'] == 'ASC' ) {
				usort( $items, '_wpv_taxonomy_sort_asc' );
			} else {
				usort( $items, '_wpv_taxonomy_sort_dec' );
			}
		}
		return $items;
	}
	
	function set_user_view_sorting( $args, $view_settings ) {
		
		$is_view_posted = false;
		if ( isset( $_GET['wpv_view_count'] ) ) {
			$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
			if ( esc_attr( $_GET['wpv_view_count'] ) == $view_unique_hash ) {
				$is_view_posted = true;
				// Map old URL parameters to new ones
				do_action( 'wpv_action_wpv_pagination_map_legacy_order' );
			}
		}
		
		$orderby = '';
		$order = '';
		// @todo check this is most likely set!! No need to pretend it might not be
		if ( isset( $view_settings['users_orderby'] ) ) {
			$orderby = $view_settings['users_orderby'];
		}
		if ( isset( $view_settings['users_order'] ) ) {
			$order = $view_settings['users_order'];
		}
		// Override with attributes
		$override_allowed = array(
			'orderby'	=> array( 'user_email', 'user_login', 'display_name', 'user_url', 'user_registered', 'include' ),
			'order'		=> array( 'asc', 'ASC', 'desc', 'DESC' )
		);
		$override_values = wpv_override_view_orderby_order( $override_allowed );
		if ( 
			isset( $override_values['orderby'] ) 
			&& in_array( $override_values['orderby'], $override_allowed['orderby'] )
		) {
			$orderby = $override_values['orderby'];
		}
		if ( isset( $override_values['order'] ) ) {
			$order = strtoupper( $override_values['order'] );
		}
		// Override with URL parameters
		if ( $is_view_posted ) {
			$column_sort_id = wpv_getget( 'wpv_sort_orderby', '', $override_allowed['orderby'] );
			if ( ! empty( $column_sort_id ) ) {
				$orderby = $column_sort_id;
			}
			if (
				isset( $_GET['wpv_sort_order'] ) 
				&& esc_attr( $_GET['wpv_sort_order'] ) != '' 
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$order = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
			}
		}
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}
		if ( ! empty( $order ) ) {
			$args['order'] = $order;
		}
		return $args;
	}
	
}

global $WPV_Sorting_Embedded;
$WPV_Sorting_Embedded = new WPV_Sorting_Embedded();

/**
* -------------------------------------------------
* Shared functions
* -------------------------------------------------
*/

/**
 * Auxiliary function that will provide limit and offset settings coming from the Views shortcode atributes, if possible
 *
 * @param array $allowed (array) Valid values that can be used to override
 *
 * @return array $return (array)
 *
 * @since 1.10
 */
function wpv_override_view_orderby_order( $allowed = array() ) {
	$defaults = array(
		'orderby'		=> array(),
		'order'			=> array(),
		'orderby_as'	=> array()
	);
	$allowed = wp_parse_args( $allowed, $defaults );
	$return = array();
	$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );;
	if ( isset( $view_attrs['orderby'] ) ) {
		if ( count( $allowed['orderby'] ) > 0 ) {
			if ( in_array( $view_attrs['orderby'], $allowed['orderby'] ) ) {
				$return['orderby'] = $view_attrs['orderby'];
			}
		} else {
			$return['orderby'] = $view_attrs['orderby'];
		}
	}
	if ( isset( $view_attrs['order'] ) ) {
		if ( count( $allowed['order'] ) > 0 ) {
			if ( in_array( $view_attrs['order'], $allowed['order'] ) ) {
				$return['order'] = $view_attrs['order'];
			}
		} else {
			$return['order'] = $view_attrs['order'];
		}
	}
	if ( isset( $view_attrs['orderby_as'] ) ) {
		if ( count( $allowed['orderby_as'] ) > 0 ) {
			if ( in_array( $view_attrs['orderby_as'], $allowed['orderby_as'] ) ) {
				$return['orderby_as'] = $view_attrs['orderby_as'];
			}
		} else {
			$return['orderby_as'] = $view_attrs['orderby_as'];
		}
	}
	return $return;
}

// @todo temporary shortcodes

add_shortcode( 'wpv-orderby', 'wpv_shortcode_wpv_orderby' );

function wpv_shortcode_wpv_orderby( $atts ) {
	extract(
		shortcode_atts( array(
			'values'			=> '',
			'display_values'	=> '',
			'default'			=> '',
			'empty'				=> ''
		), $atts )
	);
	$return = '';
	$values = explode( ',', $values );
	$values = array_map( 'trim', $values );
	$values = array_map( 'sanitize_text_field', $values );
	$display_values = explode( ',', $display_values ) ;
	$display_values = array_map( 'trim', $display_values );
	$display_values = array_map( 'sanitize_text_field', $display_values );
	if ( 
		count( $values ) != count( $display_values ) 
		|| empty( $values )
	) {
		return $return;
	}
	$selected = isset( $_GET['wpv_sort_orderby'] ) ? esc_attr( $_GET['wpv_sort_orderby'] ) : esc_attr( $default );
	$return .= '<select name="wpv_sort_orderby" class="js-wpv-filter-trigger">';
		if ( ! empty( $empty ) ) {
			// Empty
			$return .= '<option value="">';
			$return .= $empty;
			$return .= '</option>';
		}
	foreach ( $values as $key => $val ) {
		$return .= '<option value="' . esc_attr( $val ) . '" ' . selected( $val, $selected, false ) . '>';
		$return .= $display_values[$key];
		$return .= '</option>';
	}
	$return .= '</select>';
	return $return;
}

add_shortcode( 'wpv-order', 'wpv_shortcode_wpv_order' );

function wpv_shortcode_wpv_order( $atts ) {
	extract(
		shortcode_atts( array(
			'asc'		=> 'ASC',
			'desc'		=> 'DESC',
			'default'	=> '',
			'empty'		=> ''
		), $atts )
	);
	$return = '';
	$selected = isset( $_GET['wpv_sort_order'] ) ? esc_attr( $_GET['wpv_sort_order'] ) : esc_attr( $default );
	$selected = strtolower( $selected );
	$return .= '<select name="wpv_sort_order" class="js-wpv-filter-trigger">';
		if ( ! empty( $empty ) ) {
			// Empty
			$return .= '<option value="">';
			$return .= $empty;
			$return .= '</option>';
		}
		// ASC
		$return .= '<option value="asc" ' . selected( 'asc', $selected, false ) . '>';
		$return .= $asc;
		$return .= '</option>';
		// ASC
		$return .= '<option value="desc" ' . selected( 'desc', $selected, false ) . '>';
		$return .= $desc;
		$return .= '</option>';
	$return .= '</select>';
	return $return;
}
