<?php

/**
* Search frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Search_Frontend_Filter::on_load();

/**
* WPV_Author_Filter
*
* Views Search Filter Frontend Class
*
* @since 2.1
*/

class WPV_Search_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post search
        add_filter( 'wpv_filter_query',										array( 'WPV_Search_Frontend_Filter', 'filter_post_search' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Search_Frontend_Filter', 'archive_filter_post_search' ), 40, 3 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Search_Frontend_Filter', 'url_parameters_for_posts' ), 10, 2 );
		// Apply frontend filter by taxonomy search
		add_filter( 'wpv_filter_taxonomy_query',							array( 'WPV_Search_Frontend_Filter', 'filter_taxonomy_search' ), 10, 3 );
		add_filter( 'wpv_filter_register_url_parameters_for_taxonomy',		array( 'WPV_Search_Frontend_Filter', 'url_parameters_for_taxonomy' ), 10, 2 );
    }
	
	/**
	* filter_post_search
	*
	* Apply the query filter by post search to Views.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_post_search and moved to a static method
	*/
	
	static function filter_post_search( $query, $view_settings, $view_id ) {
		if ( 
			isset( $view_settings['post_search_value'] ) 
			&& $view_settings['post_search_value'] != '' 
			&& isset( $view_settings['search_mode'] ) 
			&& $view_settings['search_mode'][0] == 'specific' 
		) {
			$query['s'] = $view_settings['post_search_value'];
		}
		if ( 
			isset( $view_settings['search_mode'] ) 
			&& isset( $_GET['wpv_post_search'] ) 
		) {
			$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_post_search'] ) );
			if ( ! empty( $search_term ) ) {
				$query['s'] = $search_term;
			}
		}
		if ( 
			isset( $view_settings['post_search_content'] ) 
			&& 'just_title' == $view_settings['post_search_content'] 
			&& isset( $query['s'] ) 
		) {
			add_filter( 'posts_search', array( 'WPV_Search_Frontend_Filter', 'search_by_title_only' ), 500, 2 );
		}
		
		return $query;
	}
	
	/**
	* archive_filter_post_search
	*
	* Apply the query filter by post search to WPAs.
	*
	* @since 2.1		Renamed from wpv_filter_post_search and moved to a static method
	*/
	
	static function archive_filter_post_search( $query, $archive_settings, $archive_id ) {
		$search_term = '';
		if ( 
			isset( $archive_settings['post_search_value'] ) 
			&& $archive_settings['post_search_value'] != '' 
			&& isset( $archive_settings['search_mode'] ) 
			&& $archive_settings['search_mode'][0] == 'specific' 
		) {
			$search_term = $archive_settings['post_search_value'];
		}
		if ( 
			isset( $archive_settings['search_mode'] ) 
			&& isset( $_GET['wpv_post_search'] ) 
		) {
			$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_post_search'] ) );
			
		}
		if ( ! empty( $search_term ) ) {
			$query->set( 's', $search_term );
		}
		if ( 
			isset( $archive_settings['post_search_content'] ) 
			&& 'just_title' == $archive_settings['post_search_content'] 
			&& ! empty( $search_term )
		) {
			add_filter( 'posts_search', array( 'WPV_Search_Frontend_Filter', 'search_by_title_only' ), 500, 2 );
		}
	}
	
	/**
	* search_by_title_only
	*
	* Auxiliar method to force searching just in post titles
	*
	* @since unknown
	* @since 2.1		Renamed and moved to a static method
	*/
	
	static function search_by_title_only( $search, &$wp_query ) {
		global $wpdb;
		if ( empty( $search ) )
			return $search; // skip processing - no search term in query
		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$search = '';
		$searchand = "";
		foreach ( (array) $q['search_terms'] as $term ) {
			$term = $n . wpv_esc_like( $term ) . $n;
			$search .= $wpdb->prepare( $searchand . "( $wpdb->posts.post_title LIKE %s )", $term );
			$searchand = " AND ";
		}
		if ( ! empty( $search ) ) {
			$search = " AND ( {$search} ) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ( $wpdb->posts.post_password = '' ) ";
		}
		return $search;
	}
	
	/**
	* filter_taxonomy_search
	*
	* Apply the query filter by taxonomy search to Views.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_taxonomy_search and moved to a static method
	*/
	
	static function filter_taxonomy_search( $tax_query_settings, $view_settings, $view_id ) {
		if ( isset( $view_settings['taxonomy_search_mode'] ) ) {
			if ( $view_settings['taxonomy_search_mode'][0] == 'specific' ) {
				if (
					isset( $view_settings['taxonomy_search_value'] ) 
					&& $view_settings['taxonomy_search_value'] != '' 
				) {
					$tax_query_settings['search'] = sanitize_text_field( $view_settings['taxonomy_search_value'] );
				}
			} else if ( isset( $_GET['wpv_taxonomy_search'] ) ) {
				$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_taxonomy_search'] ) );
				if ( ! empty( $search_term ) ) {
					$tax_query_settings['search'] = $search_term;
				}
			}
		}
		return $tax_query_settings;
	}
	
	static function url_parameters_for_posts( $attributes, $view_settings ) {
		if (
			isset( $view_settings['search_mode'][0] )
			&& $view_settings['search_mode'][0] != 'specific' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_search',
				'filter_label'	=> __( 'Post search', 'wpv-views' ),
				'value'			=> '',
				'attribute'		=> 'wpv_post_search',
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
	static function url_parameters_for_taxonomy( $attributes, $view_settings ) {
		if (
			isset( $view_settings['taxonomy_search_mode'][0] )
			&& $view_settings['taxonomy_search_mode'][0] != 'specific' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'taxonomy_search',
				'filter_label'	=> __( 'Taxonomy search', 'wpv-views' ),
				'value'			=> '',
				'attribute'		=> 'wpv_taxonomy_search',
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
}