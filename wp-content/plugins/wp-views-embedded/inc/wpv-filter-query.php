<?php

/**
* WPV_View_Post_Query
*
* Views Post Query class
*
* @since 2.1
*/

class WPV_View_Post_Query {
	
	/**
	* @var WPV_View_Post_Query Instance of WPV_View_Post_Query.
	*/
	
	private static $instance = null;

	/**
	* @return WPV_View_Post_Query The instance of WPV_View_Post_Query.
	*/
	public static function get_instance() {
		if ( null == WPV_View_Post_Query::$instance ) {
			WPV_View_Post_Query::$instance = new WPV_View_Post_Query();
		}
		return WPV_View_Post_Query::$instance;
	}
	
	public static function clear_instance() {
		if ( WPV_View_Post_Query::$instance ) {
			WPV_View_Post_Query::$instance = null;
		}
	}
	
	function __construct() {
		
		/**
		* WordPress fixes
		*/
		
		add_filter( 'wpv_filter_query',					array( $this, 'wpv_filter_query_post_in_and_not_in_fix' ), 999, 3 );
		
		/**
		* Extensibility
		*/
		
		add_filter( 'wpv_filter_query_post_process',						array( $this, 'wpv_filter_extend_query_for_parametric_and_counters' ), 999, 3 );
		add_action( 'wpv_action_extend_query_for_parametric_and_counters',	array( $this, 'wpv_filter_extend_query_for_parametric_and_counters' ), 10, 3 );
		
		add_filter( 'wpv_filter_wpv_get_dependant_extended_query_args',		array( $this, 'wpv_get_dependant_view_query_args' ), 10, 2 );
		
		/**
		* Compatibility
		*/
		
		add_filter( 'wpv_filter_query',					array( $this, 'wpv_filter_query_compatibility' ), 99, 3 );
		add_filter( 'wpv_filter_query_post_process',	array( $this, 'wpv_filter_query_post_proccess_compatibility' ), 99, 3 );
		
		/**
		* AJAX pagination
		*/
		
		add_action( 'wp_ajax_wpv_get_view_query_results',					array( $this, 'wpv_get_view_query_results' ) );
		add_action( 'wp_ajax_nopriv_wpv_get_view_query_results',			array( $this, 'wpv_get_view_query_results' ) );
		
	}
	
	/**
	* wpv_filter_query_post_in_and_not_in_fix
	*
	* WP_Query can not manage post__in and post__not_in args at the same time.
	*
	* @since unknown
	* @since 2.1		Moved to a method
	*/

	function wpv_filter_query_post_in_and_not_in_fix( $query, $view_settings, $view_id ) {

		if ( 
			isset( $query['post__in'] ) 
			&& isset( $query['post__not_in'] ) 
		) {
			$query['post__in'] = array_diff( (array) $query['post__in'], (array) $query['post__not_in'] );
			$query['post__in'] = array_values( $query['post__in'] );
			unset( $query['post__not_in'] );
			if ( empty( $query['post__in'] ) ) {
				$query['post__in'] = array( '0' );
			}
		}

		return $query;
	}
	
	/**
	* wpv_filter_extend_query_for_parametric_and_counters
	*
	* Creates the additional cached data for parametric search dependency and counters.
	* This is also used by the wpv_action_extend_query_for_parametric_and_counters action.
	*
	* @uses WP_Query
	* @uses WPV_Cache::generate_cache
	*
	* @since 1.6
	* @since 2.1	Moved to a method
	*/

	function wpv_filter_extend_query_for_parametric_and_counters( $post_query, $view_settings, $id ) {
		$dps_enabled = false;
		$counters_enabled = false;
		if ( 
			! isset( $view_settings['dps'] ) 
			|| ! is_array( $view_settings['dps'] ) 
		) {
			$view_settings['dps'] = array();
		}
		if ( 
			isset( $view_settings['dps']['enable_dependency'] ) 
			&& $view_settings['dps']['enable_dependency'] == 'enable' 
		) {
			$dps_enabled = true;
			$controls_per_kind = wpv_count_filter_controls( $view_settings );
			$controls_count = 0;
			$no_intersection = array();
			if ( ! isset( $controls_per_kind['error'] ) ) {
				$controls_count = $controls_per_kind['cf'] + $controls_per_kind['tax'] + $controls_per_kind['pr'] + $controls_per_kind['search'];
				if ( 
					$controls_per_kind['cf'] > 1 
					&& (
						! isset( $view_settings['custom_fields_relationship'] ) 
						|| $view_settings['custom_fields_relationship'] != 'AND' 
					) 
				) {
					$no_intersection[] = __( 'custom field', 'wpv-views' );
				}
				if ( 
					$controls_per_kind['tax'] > 1 
					&& (
						! isset( $view_settings['taxonomy_relationship'] ) 
						|| $view_settings['taxonomy_relationship'] != 'AND' 
					) 
				) {
					$no_intersection[] = __( 'taxonomy', 'wpv-views' );
				}
			} else {
				$dps_enabled = false;
			}
			if ( $controls_count > 0 ) {
				if ( count( $no_intersection ) > 0 ) {
					$dps_enabled = false;
				}
			} else {
				$dps_enabled = false;
			}
		}
		if ( ! isset( $view_settings['filter_meta_html'] ) ) {
			$view_settings['filter_meta_html'] = '';
		}
		if ( strpos( $view_settings['filter_meta_html'], '%%COUNT%%' ) !== false ) {
			$counters_enabled = true;
		}
		
		global $WP_Views;
		if ( 
			! $dps_enabled 
			&& ! $counters_enabled 
		) {
			// Set the force value
			$WP_Views->set_force_disable_dependant_parametric_search( true );
			return $post_query;
		}
		
		// In any case, we need to mimic the process that we used to generate the $query
		$view_settings_defaults = array(
			'post_type'         => 'any',
			'orderby'           => 'post-date',
			'order'             => 'DESC',
			'paged'             => '1',
			'posts_per_page'    =>  -1
		);
		extract( $view_settings_defaults );
		
		$view_settings['view_id'] = $id;
		extract( $view_settings, EXTR_OVERWRITE );
		
		$query = array(
			'posts_per_page'    	=> $posts_per_page,
			'paged'             	=> $paged,
			'post_type'         	=> $post_type,
			'order'             	=> $order,
			'suppress_filters'  	=> false,
			'ignore_sticky_posts' 	=> true
		);
		// Add special check for media (attachments) as their default status in not usually published
		if (
			sizeof( $post_type ) == 1 
			&& $post_type[0] == 'attachment'
		) {
			$query['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
		}
			
		$query = apply_filters( 'wpv_filter_query', $query, $view_settings, $id );
		
		// Now we have the $query as in the original one
		// We now need to overwrite the limit, offset, paged and pagination options
		// Also, we set it to just return the IDs
		$query['posts_per_page'] 	= -1;
		$query['limit'] 			= -1;
		$query['paged'] 			= 1;
		$query['offset'] 			= 0;
		$query['fields'] 			= 'ids';
		
		$already = array();
		if ( 
			isset( $post_query->posts ) 
			&& ! empty( $post_query->posts ) 
		) {
			foreach ( (array) $post_query->posts as $post_object ) {
				$already[] = $post_object->ID;
			}
		}
		$WP_Views->returned_ids_for_parametric_search = $already;
		
		$parametric_search_data_to_cache = WPV_Cache::get_parametric_search_data_to_cache( $view_settings );
		
		WPV_Cache::generate_native_cache( $already, $parametric_search_data_to_cache );
		
		// Adjust $query to avoid already queried posts
		if ( isset ( $query['pr_filter_post__in'] ) ) {
			$query['post__in'] = $query['pr_filter_post__in'];
		} else {
			// If just for the missing ones, generate the post__not_in argument
			if ( isset( $query['post__not_in'] ) ) {
				$query['post__not_in'] = array_merge( (array) $query['post__not_in'], (array) $already );
			} else {
				$query['post__not_in'] = (array) $already;
			}
			// And adjust on the post__in argument
			if ( isset( $query['post__in'] ) ) {
				$query['post__in'] = array_diff( (array) $query['post__in'], (array) $query['post__not_in'] );
			}
		}
		
		// Perform the query
		$aux_cache_query = new WP_Query( $query );

		// Add the auxiliar query results to the list of returned IDs
		// Generate the "extra" cache
		if ( 
			is_array( $aux_cache_query->posts ) 
			&& ! empty( $aux_cache_query->posts ) 
		) {
			$WP_Views->returned_ids_for_parametric_search = array_merge( $WP_Views->returned_ids_for_parametric_search, $aux_cache_query->posts );
			$WP_Views->returned_ids_for_parametric_search = array_unique( $WP_Views->returned_ids_for_parametric_search );
			WPV_Cache::generate_cache( $aux_cache_query->posts, $parametric_search_data_to_cache );
		}

		return $post_query;
	}
	
	/**
	* -------------------------
	* Compatibility
	* -------------------------
	*/

	function wpv_filter_query_compatibility( $query, $view_settings, $view_id ) {

		// Relevanssi compatibility
		if ( 
			isset( $view_settings['search_mode'] ) 
			&& function_exists( 'relevanssi_prevent_default_request' ) 
		) {
			remove_filter('posts_request', 'relevanssi_prevent_default_request', 10, 3 );
		}

		return $query;
	}
	
	function wpv_filter_query_post_proccess_compatibility( $post_query, $view_settings, $view_id ) {

	// Relevanssi compatibility - restore
		if ( 
			isset( $view_settings['search_mode'] ) 
			&& function_exists( 'relevanssi_prevent_default_request' ) 
		) {
			add_filter('posts_request', 'relevanssi_prevent_default_request', 10, 3 );
		}

		return $post_query;
	}
	
	/**
	* -------------------------
	* AJAX pagination
	* -------------------------
	*/
	
	function wpv_get_view_query_results() {
		global $post;
		
		$view_id		= ( $_POST['wpv_view_widget_id'] == 0 ) ? (int) $_POST['id'] : (int) $_POST['wpv_view_widget_id'];
		$page			= (int) $_POST['page'];
		$sort			= isset( $_POST['sort'] ) ? $_POST['sort'] : array();
		$environment	= isset( $_POST['environment'] ) ? $_POST['environment'] : array();
		$search			= isset( $_POST['search'] ) ? $_POST['search'] : array();
		$search_keys	= array();
		$extra			= isset( $_POST['extra'] ) ? $_POST['extra'] : array();
		$attributes		= isset( $_POST['attributes'] ) ? $_POST['attributes'] : array();
		
		$_GET['wpv_view_count']	= esc_attr( $_POST['view_number'] );
		$_GET['wpv_paged']		= $page;
		
		foreach ( $sort as $sort_key => $sort_value ) {
			if ( in_array( $sort_key, array( 'wpv_sort_orderby', 'wpv_sort_order', 'wpv_sort_orderby_as' ) ) ) {
				$_GET[ $sort_key ] = esc_attr( $sort_value );
			}
		}
		
		foreach ( $environment as $environment_key => $environment_value ) {
			if ( 
				in_array( $environment_key, array( 'wpv_aux_current_post_id', 'wpv_aux_parent_post_id', 'wpv_aux_parent_term_id', 'wpv_aux_parent_user_id' ) ) 
				&& (int) $environment_value > 0
			) {
				$search_keys[] = $environment_key;
				$_GET[ $environment_key ] = (int) $environment_value;
				switch ( $environment_key ) {
					case 'wpv_aux_current_post_id':
						$top_post_id = (int) $environment_value;
						$top_post = get_post( $top_post_id );
						do_action( 'wpv_action_wpv_set_top_current_post', $top_post );
						break;
					case 'wpv_aux_parent_post_id':
						global $authordata, $id;
						$post_id = (int) $environment_value;
						$post = get_post( $post_id );
						$authordata = new WP_User( $post->post_author );
						$id = $post->ID;
						do_action( 'wpv_action_wpv_set_current_post', $post );
						break;
					case 'wpv_aux_parent_term_id':
						do_action( 'wpv_action_wpv_set_parent_view_taxonomy', (int) $environment_value );
						break;
					case 'wpv_aux_parent_user_id':
						do_action( 'wpv_action_wpv_set_parent_view_user', (int) $environment_value );
						break;
				}
			}
		}
		
		if ( isset( $search['dps_general'] ) ) {
			$corrected_item = array();
			foreach ( $search['dps_general'] as $dps_pr_item ) {
				if ( 
					is_array( $dps_pr_item ) 
					&& isset( $dps_pr_item['name'] ) 
					&& isset( $dps_pr_item['value'] ) 
				) {
					if ( strlen( $dps_pr_item['name'] ) < 2 ) {
						$search_keys[] = $dps_pr_item['name'];
						$_GET[ $dps_pr_item['name'] ] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
					} else {
						if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
							$name = str_replace( '[]', '', $dps_pr_item['name'] );
							$search_keys[] = $name;
							if ( ! in_array( $name, $corrected_item ) ) {
								$corrected_item[] = $name;
								if ( isset( $_GET[ $name ] ) ) {
									unset( $_GET[ $name ] );
								}
							}
							if ( ! isset( $_GET[ $name ] ) ) {
								$_GET[ $name ] = array( esc_attr( wp_unslash( $dps_pr_item['value'] ) ) );
							} else if ( is_array( $_GET[ $name ] ) ) {
								$_GET[ $name ][] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
							}
						} else {
							$search_keys[] = $dps_pr_item['name'];
							$_GET[ $dps_pr_item['name'] ] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
						}
					}
				}
			}
		}
		
		if ( isset( $search['dps_pr'] ) ) {
			foreach ( $search['dps_pr'] as $dps_pr_item ) {
				if ( 
					is_array( $dps_pr_item ) 
					&& isset( $dps_pr_item['name'] ) 
					&& isset( $dps_pr_item['value'] ) 
				) {
					if ( strlen( $dps_pr_item['name'] ) < 2 ) {
						if ( ! isset( $_GET[ $dps_pr_item['name'] ] ) ) {
							$search_keys[] = $dps_pr_item['name'];
							$_GET[ $dps_pr_item['name'] ] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
						}
					} else {
						if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
							$name = str_replace( '[]', '', $dps_pr_item['name'] );
							$search_keys[] = $name;
							if ( ! isset( $_GET[ $name ] ) ) {
								$_GET[ $name ] = array( esc_attr( wp_unslash( $dps_pr_item['value'] ) ) );
							} else if ( is_array( $_GET[$name] ) ) {
								$_GET[ $name ][] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
							}
						} else {
							if ( ! isset( $_GET[ $dps_pr_item['name'] ] ) ) {
								$search_keys[] = $dps_pr_item['name'];
								$_GET[ $dps_pr_item['name'] ] = esc_attr( wp_unslash( $dps_pr_item['value'] ) );
							}
						}
					}
				}
			}
		}
		

		foreach ( $extra as $extra_key => $extra_value ) {
			if ( ! in_array( $extra_key, $search_keys ) ) {
				if ( ! isset( $_GET[ $extra_key ] ) ) { // Might be redundant with the check on $search_keys
					$extra_value = esc_attr( $extra_value );
					// @hack alert!! We can not avoid this :-(
					if ( strpos( $extra_value, '##URLARRAYVALHACK##' ) !== false ) {
						$_GET[ $extra_key ] = explode( '##URLARRAYVALHACK##', $extra_value );
					} else {
						$_GET[ $extra_key ] = $extra_value;
					}
				}
			}
		}
		
		if ( isset( $_POST['lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( $_POST['lang'] ) );
		}
		
		// Not sure if we need to fake a $post if not set let's see
		
		$view_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );
		
		if ( ! isset( $post ) ) {
			// Sometimes, $post is not set. We need to dummy it
			// Otherwise, Content Templates used on Views loops for taxonomies or users will not work
			$registered_post_types = get_post_types( array(), 'names' );
			$dummy_post_type_counter = 0;
			$dummy_post_type_base = 'view-dummy';
			$dummy_post_type = 'view-dummy';
			
			while ( in_array( $dummy_post_type, $registered_post_types ) ) {
				$dummy_post_type_counter = $dummy_post_type_counter + 1;
				$dummy_post_type = $dummy_post_type_base . '-' . $dummy_post_type_counter;
			}
			
			$post = get_post( $view_id );
			$post->post_type = $dummy_post_type;
			
		}
		
		if ( esc_attr( $_POST['wpv_view_widget_id'] ) == 0 ) {
			// set the view count so we return the right view number after rendering.
			/*
			$WP_Views->set_view_count( intval( esc_attr( $post_data['view_number'] ) ), $view_id );
			echo $WP_Views->short_tag_wpv_view( $view_data );
			*/
			
			$args = array(
				'id' => $view_id
			);
			
			if ( isset( $_POST['target_id'] ) ) {
				$args['target_id'] = $_POST['target_id'];
			}
			
			$args = array_merge( $args, $attributes );
			
			$expect = isset( $_POST['expect'] ) ? $_POST['expect'] : 'full';
			
			$data = array(
				'id'	=> $view_id
			);
			
			if ( $expect == 'form' ) {
				if ( isset( $args['target_id'] ) ) {
					$data['form'] = render_view( $args );
				}
				$data['full'] = '';
			} else if ( $expect == 'full' ) {
				$data['form'] = '';
				if ( isset( $args['target_id'] ) ) {
				}
				$data['full'] = render_view( $args );
			} else if ( $expect == 'both' ) {
				if ( isset( $args['target_id'] ) ) {
					$data['form'] = render_view( $args );
					unset( $args['target_id'] );
				}
				$data['full'] = render_view( $args );
			} else {
				$data['form'] = '';
				$data['full'] = '';
			}
		} else {
			// set the view count so we return the right view number after rendering.
			//$WP_Views->set_view_count( (int) esc_attr( $post_data['view_number'] ), $view_id );
			$data = array(
				'id'	=> $view_id,
				'form'	=> '',
			);
			
			$widget = new WPV_Widget();
			$args = array(
				'before_widget' => '',
				'before_title' => '',
				'after_title' => '',
				'after_widget' => ''
			);
			ob_start();
			$widget->widget(
				$args, 
				array(
					'title' => '',
					'view' => $view_id
				)
			);
			$data['full'] = ob_get_clean();
		}
		
		/**
		* A little hacky
		*
		* To calculate the View hash we need to set the View shortcode attributes, but we remove them when closing render_view(),
		* so we rstore them here
		*/
		
		do_action( 'wpv_filter_wpv_set_view_shortcodes_attributes', $attributes );
		
		$pagination_permalinks	= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
		if ( $page == 1 ) {
			$pagination_permalink = $pagination_permalinks['first'];
		} else {
			$pagination_permalink = str_replace( 'WPV_PAGE_NUM', $page, $pagination_permalinks['other'] );
		}
		
		// For parametric search URL history management
		$data['permalink']				= $pagination_permalink;
		// In theory, this is only used by parametric search, so we should always use the 'first' one above.
		$data['parametric_permalink']	= $pagination_permalink;
		
		if ( ! wpv_parametric_search_triggers_history( $view_id) ) {
			// When parametric search does not manage history, we need to clean the URL.
			$view_url_data					= get_view_allowed_url_parameters( $view_id );
			$query_args_remove				= wp_list_pluck( $view_url_data, 'attribute' );
			foreach ( $query_args_remove as $query_args_remove_string ) {
				$query_args_remove[] = $query_args_remove_string . '[]';
			}
			$query_args_remove[]			= 'lang';
			$query_args_remove[]			= 'wpv_sort_orderby';
			$query_args_remove[]			= 'wpv_sort_order';
			$query_args_remove[]			= 'wpv_aux_current_post_id';
			$query_args_remove[]			= 'wpv_aux_parent_post_id';
			$query_args_remove[]			= 'wpv_aux_parent_term_id';
			$query_args_remove[]			= 'wpv_aux_parent_user_id';
			$data['parametric_permalink']	= remove_query_arg(
				$query_args_remove,
				$pagination_permalink
			);
		}
		
		wp_send_json_success( $data );
		
	}
	
	function wpv_get_dependant_view_query_args( $args = array(), $view_settings = array() ) {
		if ( 
			isset( $view_settings['view-query-mode'] ) 
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			// In any case, we need to mimic the process that we used to generate the $args
			$view_settings_defaults = array(
				'post_type'         => 'any',
				'orderby'           => 'post-date',
				'order'             => 'DESC',
				'paged'             => '1',
				'posts_per_page'    =>  -1
			);
			extract( $view_settings_defaults );
			
			$id				= apply_filters( 'wpv_filter_wpv_get_current_view', null );
			$view_settings	= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
			
			$view_settings['view_id'] = $id;
			extract( $view_settings, EXTR_OVERWRITE );
			
			$args = array(
				'posts_per_page'  	 	=> $posts_per_page,
				'paged'           	 	=> $paged,
				'post_type'     	    => $post_type,
				'order'         	    => $order,
				'suppress_filters'		=> false,
				'ignore_sticky_posts'	=> true
			);
			// Add special check for media (attachments) as their default status in not usually published
			if (
				sizeof( $post_type ) == 1 
				&& $post_type[0] == 'attachment'
			) {
				$args['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
			}
			
			// !IMPORTANT override the sorting options (not important here), sorting by a custom field breaks everything, so revert to post_date
			$view_settings['orderby'] = 'ID';
			
			$args = apply_filters( 'wpv_filter_query', $args, $view_settings, $id );
			
			// Now we have the $args as in the original one
			// We now need to overwrite the limit, offset, paged and pagination options
			//Also,we set it to just return the IDs
			$args['posts_per_page'] 	= -1;
			$args['limit'] 				= -1;
			$args['paged'] 				= 1;
			$args['offset'] 			= 0;
			$args['fields'] 			= 'ids';
		}
		return $args;
	}
	
}

$WPV_View_Post_Query = WPV_View_Post_Query::get_instance();

/**
* wpv_filter_get_posts
*
* Create the query to return the posts based on the View settings
*
* @param $id (integer) The View ID
*
* @return $post_query (object) WP_Query instance
*
* @since unknown
*
* @todo remove extract() calls
*/

function wpv_filter_get_posts( $id ) {
    global $post, $WPVDebug;
	
	$view_settings_defaults = array(
		'post_type'         => 'any',
		'paged'             => '1',
		'posts_per_page'    =>  -1
	);
	extract( $view_settings_defaults );
	
	$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $id );
	$view_settings['view_id'] = $id;
	
	extract( $view_settings, EXTR_OVERWRITE );

	// Let URL pagination parameters set the page
	if (
		isset( $_GET['wpv_paged'] ) 
		&& isset( $_GET['wpv_view_count'] ) 
		&& esc_attr( $_GET['wpv_view_count'] ) == apply_filters( 'wpv_filter_wpv_get_view_unique_hash', '' )
	) {
		$paged = intval( esc_attr( $_GET['wpv_paged'] ) );
	}
	
    $query = array(
		'post_type'				=> $post_type,
		'paged'					=> $paged,
		'posts_per_page'		=> $posts_per_page,
		'suppress_filters'		=> false,
		'ignore_sticky_posts'	=> true
    );

    if (
		isset( $view_settings['pagination'][0] ) 
		&& $view_settings['pagination'][0] == 'disable'
    // && isset($view_settings['pagination']['mode']) && $view_settings['pagination']['mode'] == 'paged'
    ) {
        // Show all the posts if pagination is disabled.
        $query['posts_per_page'] = -1;
    } else if (
		isset( $view_settings['pagination']['mode'] ) 
		&& $view_settings['pagination']['mode'] == 'rollover'
	) {
        $query['posts_per_page'] = $view_settings['rollover']['posts_per_page'];
    }

	// Add special check for media (attachments) as their default status in not usually published
	if ( 
		sizeof( $post_type ) == 1 
		&& $post_type[0] == 'attachment'
	) {
		$query['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
	}

	$WPVDebug->add_log( 'info', apply_filters( 'wpv-view-get-content-summary', '', $id, $view_settings ), 'short_query' );

	$WPVDebug->add_log( 'info', "Basic query arguments\n". print_r( $query, true ), 'query_args' );

	/**
	* Filter wpv_filter_query
	*
	* This is where all the filters coming from the View settings to modify the query are hooked
	*
	* @param $query the Query arguments as in WP_Query
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $query
	*
	* @since unknown
	*/

    $query = apply_filters( 'wpv_filter_query', $query, $view_settings, $id );

    $WPVDebug->add_log( 'filters', "wpv_filter_query\n" . print_r( $query, true ), 'filters', 'Filter arguments before the query using <strong>wpv_filter_query</strong>' );

    $post_query = new WP_Query( $query );

	$WPVDebug->add_log( 'mysql_query', $post_query->request , 'posts', '', true );

	$WPVDebug->add_log( 'info', print_r( $post_query, true ), 'query_results', '', true );

	toolset_wplog( $post_query->query, 'debug', __FILE__, 'wpv_filter_get_posts', 98 );
	toolset_wplog( $post_query->request, 'debug', __FILE__, 'wpv_filter_get_posts', 99 );

	/**
	* Filter wpv_filter_query_post_process
	*
	* This is applied to the results of the main query.
	*
	* @param $post_query the queried object returned by the WordPress WP_Query()
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $post_query
	*
	* @since unknown
	*/

    $post_query = apply_filters( 'wpv_filter_query_post_process', $post_query, $view_settings, $id );

    $WPVDebug->add_log( 'filters', "wpv_filter_query_post_process\n" . print_r( $post_query, true ), 'filters', 'Filter the returned query using <strong>wpv_filter_query_post_process</strong>' );

    return $post_query;
}