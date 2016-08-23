<?php

/*
 
    Shortcode for sorting by the column heading in
    table layout mode.
    
*/

add_shortcode('wpv-heading', 'wpv_header_shortcode');
function wpv_header_shortcode( $atts, $value ) {
    extract(
        shortcode_atts( array(
            'name' => '',
            'style' => '',
            'class' => ''
            ), $atts )
    );

    if (isset($atts['name']) && strpos($atts['name'], 'types-field-')) {
        $atts['name'] = strtolower($atts['name']);
    }
    
    if ( ! empty( $style ) ) {
        $style = ' style="'. esc_attr( $style ).'"';
    }
    if ( ! empty( $class) ) {
        $class = ' ' . esc_attr( $class );
    }
        
    global $wp_version;
    $view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
    
    $order_class	= 'wpv-header-no-sort';
	$dir			= "asc";
	$orderby_as		= '';
	$can_order		= true;
	$default_order	= $view_settings['order'];
	
	if ( 
		$view_settings['view-query-mode'] == 'normal' 
		&& ! empty( $atts['name'] ) 
		&& isset( $view_settings['query_type'][0] )
	) {
		switch ( $view_settings['query_type'][0] ) {
			case 'posts':
				$default_order = $view_settings['order'];
				if ( in_array( $atts['name'], array( 'post-body', 'wpv-post-taxonomy' ) ) ) {
					$can_order = false;
				}
				if ( strpos( $atts['name'], 'types-field-') === 0 ) {
					$field_name = strtolower( substr( $atts['name'], 12 ) );
					$field_type = wpv_types_get_field_type( $field_name );
					if ( in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
						$can_order = false;
					} else if ( in_array( $field_type, array( 'numeric', 'date' ) ) ) {
						$orderby_as = 'numeric';
					}
				}
				break;
			case 'taxonomy':
				$default_order = $view_settings['taxonomy_order'];
				if ( strpos( $atts['name'], 'taxonomy-field-') === 0 ) {
					$field_name = strtolower( substr( $atts['name'], 15 ) );
					$field_type = wpv_types_get_field_type( $field_name, 'tf' );
					if ( in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
						$can_order = false;
					} else if ( in_array( $field_type, array( 'numeric', 'date' ) ) ) {
						$orderby_as = 'numeric';
					}
					if ( version_compare( $wp_version, '4.5', '<' ) ) {
						$can_order = false;
					}
				}
				break;
			case 'users':
				$default_order = $view_settings['users_order'];
				if ( ! in_array( $atts['name'], array( 'user_email', 'user_login', 'display_name', 'user_url', 'user_registered' ) ) ) {
					$can_order = false;
				}
				break;
		}
	} else {
		$can_order = false;
	}
	
	if ( $can_order ) {
		$view_number = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		if (
			isset( $_GET['wpv_sort_orderby'] ) 
			&& esc_attr( $_GET['wpv_sort_orderby'] ) == $atts['name'] 
			&& isset( $_GET['wpv_view_count'] ) 
			&& $view_number == esc_attr( $_GET['wpv_view_count'] ) 
		) {
			if ( isset( $_GET['wpv_sort_order'] ) ) {
				$passed_dort_dir = esc_attr( strtolower( $_GET['wpv_sort_order'] ) );
				switch ( $passed_dort_dir ) {
					case 'asc':
						$order_class = 'wpv-header-asc';
						$dir = "desc";
						break;
					case 'desc':
						$order_class = 'wpv-header-desc';
						$dir = "asc";
						break;
					default:
						if ( strtolower( $default_order ) == 'asc' ) {
							$order_class = 'wpv-header-asc';
							$dir = "desc";
						} else {
							$order_class = 'wpv-header-desc';
							$dir = "asc";
						}
						break;
				}
			}
		}
        $link = '<a href="#"'
			. ' class="' . $order_class . ' js-wpv-column-header-click'. $class .'"'
			. $style 
			. ' data-viewnumber="' 	. $view_number . '"'
			. ' data-name="' 		. $atts['name'] . '"'
			. ' data-direction="' 	. $dir . '"'
			. ' data-orderbyas="' 	. $orderby_as . '"'
			. '>' 
			. wpv_do_shortcode( $value ) 
			. '<span class="wpv-sorting-indicator"></span>'
			. '</a>';
        return $link;
	} else {
		return wpv_do_shortcode( $value );
	}
}

add_shortcode('wpv-layout-start', 'wpv_layout_start_shortcode');
function wpv_layout_start_shortcode($atts){
    
	$view_id				= apply_filters( 'wpv_filter_wpv_get_current_view', null );
    $view_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
	$view_number			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
	$pagination_data		= apply_filters( 'wpv_filter_wpv_get_pagination_settings', array(), $view_settings );
	$pagination_permalinks	= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
	if ( $pagination_data['page'] == 1 ) {
		$pagination_permalink = $pagination_permalinks['first'];
	} else {
		$pagination_permalink = str_replace( 'WPV_PAGE_NUM', $pagination_data['page'], $pagination_permalinks['other'] );
	}
	
	
    $class = array( 
		'js-wpv-view-layout', 
		'js-wpv-layout-responsive',
		'js-wpv-view-layout-' . esc_attr( $view_number )
	);
    $style = array();
	$add = '';
	
	if ( $pagination_data['type'] == 'ajaxed' ) {
		
		$class[] = 'wpv-pagination';
		$class[] = 'js-wpv-layout-has-pagination';
		
		if ( $pagination_data['effect'] == 'infinite' ) {
			$class[] = 'js-wpv-layout-infinite-scrolling';
		}
		
		if ( $pagination_data['preload_images'] == 'enabled'  ) {
            $class[] = 'wpv-pagination-preload-images';
			$class[] = 'js-wpv-layout-preload-images';
            $style[] = 'visibility:hidden;';
        }
		
		if ( $pagination_data['preload_pages'] == 'enabled'  ) {
			$class[] = 'wpv-pagination-preload-pages';
			$class[] = 'js-wpv-layout-preload-pages';
		}
		
	}
    
	if ( ! empty( $class ) ) {
		$add .= ' class="' . implode( ' ', $class ) . '"';
	}
	if ( ! empty( $style ) ) {
		$add .= ' style="' . implode( ' ', $style ) . '"';
	}
		
	$add .= ' data-viewnumber="' . esc_attr( $view_number ) . '"';
	
	$return = '<div'
		. ' id="wpv-view-layout-' . esc_attr( $view_number ) . '"'
		. $add
		. ' data-pagination="' . esc_js( wp_json_encode( $pagination_data ) ) . '"'
		. ' data-permalink="' . esc_url( $pagination_permalink ) . '"'
		. ">\n";
		
	return $return;
}

add_shortcode('wpv-layout-end', 'wpv_layout_end_shortcode');
function wpv_layout_end_shortcode($atts){
	return '</div>';
}

add_shortcode('wpv-layout-row', 'wpv_layout_row');
function wpv_layout_row( $atts, $value ){
	extract(
		shortcode_atts( array(
			'framework' => 'bootstrap',
			'cols' => 12,
			'col_options' => '',
		), $atts )
	);
	if ( 'bootstrap' == $framework ) {
		$elements = substr_count( $value, '[wpv-layout-cell-span]' );
		$counter = 1;
		$pattern = array();

		// if we have col_options
		preg_match_all('/\{([^}]*)\}/', $col_options, $pieces);
		foreach($pieces[1] as $match) {
			$piece = explode(',', $match);
			if ( ( count( $piece ) == $elements ) && ( array_sum( $piece ) == $cols ) ) {
				$pattern = $piece;
			}
		}
		while(preg_match('#\\[wpv-layout-cell-span]#', $value, $matches)) {
			$pos = strpos( $value, $matches[0] );
			$len = strlen( $matches[0] );
			if ( 0 < count( $pattern ) ) {
				$value = substr_replace( $value, 'span' . $pattern[$counter - 1], $pos, $len );
				$counter++;
			} elseif ( $counter < $elements ) {
				$counter++;
				$value = substr_replace( $value, 'span' . floor( $cols/$elements ), $pos, $len );
			} else {
				$value = substr_replace( $value, 'span' . ( $cols - ( ( $elements -1 ) * ( floor( $cols/$elements ) ) ) ), $pos, $len );
			}
		}
	}
	
	return wpv_do_shortcode( $value );
        
}

add_shortcode('wpv-layout-meta-html', 'wpv_layout_meta_html');
function wpv_layout_meta_html($atts) {
    extract(
        shortcode_atts( array(), $atts )
    );

    $view_layout_settings = apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array() );
    
    if ( isset( $view_layout_settings['layout_meta_html'] ) ) {
        $content = $view_layout_settings['layout_meta_html'];
        return wpv_do_shortcode($content);
    } else {
        return '';
    }
}