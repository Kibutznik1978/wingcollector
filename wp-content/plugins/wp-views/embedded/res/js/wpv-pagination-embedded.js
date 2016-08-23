var WPViews = WPViews || {};

var wpv_stop_rollover = {};
window.wpvPaginationAjaxLoaded = {};
window.wpvPaginationAnimationFinished = {};
window.wpvPaginationQueue = {};

// ------------------------------------
// Clone
// ------------------------------------

// Textarea and select clone() bug workaround | Spencer Tipping
// Licensed under the terms of the MIT source code license
// Motivation.
// jQuery's clone() method works in most cases, but it fails to copy the value of textareas and select elements. This patch replaces jQuery's clone() method with a wrapper that fills in the
// values after the fact.
// An interesting error case submitted by Piotr Przybyl: If two <select> options had the same value, the clone() method would select the wrong one in the cloned box. The fix, suggested by Piotr
// and implemented here, is to use the selectedIndex property on the <select> box itself rather than relying on jQuery's value-based val().
;( function() {
    jQuery.fn.wpv_clone = function() {
        var result = jQuery.fn.clone.apply( this, arguments ),
		my_textareas = this.find( 'textarea' ).add( this.filter( 'textarea' ) ),
		result_textareas = result.find( 'textarea' ).add( result.filter( 'textarea' ) ),
		my_selects = this.find( 'select' ).add( this.filter( 'select' ) ),
		result_selects = result.find( 'select' ).add( result.filter( 'select' ) );
		for ( var i = 0, l = my_textareas.length; i < l; ++i ) {
			jQuery( result_textareas[i] ).val( jQuery( my_textareas[i] ).val() );
		}
		for ( var i = 0, l = my_selects.length; i < l; ++i ) {
			for ( var j = 0, m = my_selects[i].options.length; j < m; ++j ) {
				if ( my_selects[i].options[j].selected === true ) {
					result_selects[i].options[j].selected = true;
				} else {
					result_selects[i].options[j].selected = false;
				}
			}
		}
        return result;
    };
})();

// ------------------------------------
// Rollover
// ------------------------------------

jQuery.fn.wpvRollover = function( data ) {
	var args = jQuery.extend( {}, {id: 1, effect: "fade", speed: 5, page: 1, count: 1}, data ),
	id = args.id,
	effect = args.effect,
	speed = args.speed*1000,
	page = args.page,
	count = args.count,
	wpvInfiniteLoop;
	if ( count > 1 ) {
		if ( 
			window.wpvPaginationAjaxLoaded.hasOwnProperty( id ) 
			&& window.wpvPaginationAjaxLoaded[id] === false 
		) {
			if ( jQuery( this ).length > 0 ) {
				setTimeout( function() {
						jQuery( this ).wpvRollover( {
							id:id,
							effect:effect,
							speed:speed/1000,
							page:page,
							count:count,
						} );
					}, 
					100 );
			}
			return false;
		}
		window.wpvPaginationAjaxLoaded[id] = false;
		wpvInfiniteLoop = setTimeout( function() {
			if ( effect === 'slideright' || effect === 'slidedown' ) {
				if ( page <= 1 ) {
					page = count;
				} else {
					page--;
				}
			} else {
				if ( page === count ) {
					page = 1;
				} else {
					page++;
				}
			}
			if (
				! wpv_stop_rollover.hasOwnProperty( id ) 
				&& jQuery( this ).length > 0
			) {
				WPViews.view_pagination.trigger_pagination( id, page );
				jQuery( this ).wpvRollover( {
					id:id,
					effect:effect,
					speed:speed/1000,
					page:page,
					count:count,
				} );
			}
		}, speed);
	}
};


////////////////////////////////////////////////////
// Table sorting head click
////////////////////////////////////////////////////

// TODO create a table sorting object to wrap all related code

jQuery( document ).on( 'click', '.js-wpv-column-header-click', function( e ) {
	e.preventDefault();
	var thiz		= jQuery( this ),
	view_number		= thiz.data( 'viewnumber' ),
	name			= thiz.data( 'name' ),
	direction		= thiz.data( 'direction' ),
	orderby_as		= thiz.data( 'orderbyas' ),
	innerthis;
	jQuery( 'form[name="wpv-filter-' + view_number + '"]' ).each( function() {
		innerthis = jQuery( this );
		WPViews.view_frontend_utils.set_extra_url_query_parameters_by_form( innerthis );
		if ( innerthis.find( '[name=wpv_sort_orderby]' ).length > 0 ) {
			innerthis.find( '[name=wpv_sort_orderby]' ).val( name );
		} else {
			jQuery( '<input>' )
				.attr({
					type:	'hidden',
					name:	'wpv_sort_orderby',
					value:	name
				})
				.appendTo( innerthis );
		}
		if ( innerthis.find( '[name=wpv_sort_order]' ).length > 0 ) {
			innerthis.find( '[name=wpv_sort_order]' ).val( direction );
		} else {
			jQuery( '<input>' )
				.attr({
					type:	'hidden',
					name:	'wpv_sort_order',
					value:	direction
				})
				.appendTo( innerthis );
		}
		if ( innerthis.find( '[name=wpv_sort_orderby_as]' ).length > 0 ) {
			innerthis.find( '[name=wpv_sort_orderby_as]' ).val( orderby_as );
		} else {
			jQuery( '<input>' )
				.attr({
					type:	'hidden',
					name:	'wpv_sort_orderby_as',
					value:	orderby_as
				})
				.appendTo( innerthis );
		}
	});
	jQuery( 'form[name="wpv-filter-' + view_number + '"]' ).submit();
});

WPViews.ViewFrontendUtils = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	self.just_return = function() {
		return;
	};
	
	/**
	* extract_url_query_parameters
	*
	* Extracts parameters from a query string, managing arrays, and returns an array of pairs key => value
	*
	* @param string query_string
	*
	* @return array
	*
	* @note ##URLARRAYVALHACK## is a hacky constant
	*
	* @uses decodeURIComponent
	*
	* @since 1.9
	*/
	
	self.extract_url_query_parameters = function( query_string ) {
		var query_string_pairs = {};
		if ( query_string == "" ) {
			return query_string_pairs;
		}
		var query_string_split = query_string.split( '&' ),
		query_string_split_length = query_string_split.length;
		for ( var i = 0; i < query_string_split_length; ++i ) {
			var qs_part = query_string_split[i].split( '=' );
			if ( qs_part.length != 2 ) {
				continue;
			};
			var thiz_key = qs_part[0],
			thiz_val = decodeURIComponent( qs_part[1].replace( /\+/g, " " ) );
			// Adjust thiz_key to work with POSTed arrays
			thiz_key = thiz_key.replace( /(\[)\d?(\])/, "" );
			thiz_key = thiz_key.replace( "[]", "" );// Just in case
			thiz_key = thiz_key.replace( /(%5B)\d?(%5D)/, "" );
			thiz_key = thiz_key.replace( "%5B%5D", "" );// Just in case
			thiz_key = thiz_key.replace( /(%255B)\d?(%255D)/, "" );
			thiz_key = thiz_key.replace( "%255B%255D", "" );// Just in case
			if ( query_string_pairs.hasOwnProperty( thiz_key ) ) {
				if ( query_string_pairs[thiz_key] != thiz_val ) {
					// @hack alert!! We can not avoid using this :-(
					query_string_pairs[thiz_key] += '##URLARRAYVALHACK##' + thiz_val;
				} else {
					query_string_pairs[thiz_key] = thiz_val;
				}
			} else {
				query_string_pairs[thiz_key] = thiz_val;
			}
		}
		return query_string_pairs;
	};
	
	/**
	* get_extra_url_query_parameters
	*
	* Gets the current URL query parameters, but only those that do not belong to the current form already.
	*
	* @note Arrays are returned on a single key with valus on a single string, separated by the ##URLARRAYVALHACK## placeholder. We might review that later
	*
	* @return array
	*
	* @uses self.extract_url_query_parameters
	*
	* @since 2.1
	*/

	self.get_extra_url_query_parameters_by_form = function( form ) {
		var query_string = self.extract_url_query_parameters( window.location.search.substr( 1 ) );
		data = {};
		for ( var prop in query_string ) {
			if ( 
				query_string.hasOwnProperty( prop ) 
				&& ! data.hasOwnProperty( prop )
				&& form.find( '[name=' + prop + '], [name=' + prop + '\\[\\]]' ).length === 0
			) {
				data[ prop ] = query_string[ prop ];
			}
		}
		return data;
	};
	
	/**
	* set_extra_url_query_parameters
	*
	* Forces the current URL query parameters in a form, but only those that do not belong to the form already.
	*
	* @uses self.extract_url_query_parameters
	*
	* @since 2.1
	*/

	self.set_extra_url_query_parameters_by_form = function( form ) {
		var extra = self.get_extra_url_query_parameters_by_form( form );
		$.each( extra, function( key, value ) {
			if ( form.find( '[name=' + key + '], [name=' + key + '\\[\\]]' ).length === 0 ) {
				// @hack alert!! WE can not avoid this :-(
				var pieces = value.split( '##URLARRAYVALHACK##' ),
				pieces_length = pieces.length;
				if ( pieces_length < 2 ) {
					$( '<input>' ).attr({
						type: 'hidden',
						name: key,
						value: value
					})
					.appendTo( form );
				} else {
					for ( var iter = 0; iter < pieces_length; iter++ ) {
						$( '<input>' ).attr({
							type: 'hidden',
							name: key + "[]",
							value: pieces[iter]
						})
						.appendTo( form );
					}
				}
			}
		});
	};
	
	/**
	* render_frontend_datepicker
	*
	* Adds a datepicker to a selector but only if it has not been added before.
	*
	* Fired on document.ready, after AJAX pagination and after AJAX parametric search events.
	*
	* @since 1.9
	*/
	
	self.render_frontend_datepicker = function() {
		$( '.js-wpv-frontend-datepicker:not(.js-wpv-frontend-datepicker-inited)' ).each( function() {
			var thiz = $( this );
			thiz
				.addClass( 'js-wpv-frontend-datepicker-inited' )
				.datepicker({
					onSelect: function( dateText, inst ) {
						var url_param = thiz.data( 'param' ),
						data = 'date=' + dateText,
						form = thiz.closest( 'form' );
						data += '&date-format=' + $( '.js-wpv-date-param-' + url_param + '-format' ).val();
						data += '&action=wpv_format_date';
						$.post( wpv_pagination_local.front_ajaxurl, data, function( response ) {
							response = $.parseJSON( response );
							form.find('.js-wpv-date-param-' + url_param ).html( response['display'] );
							form.find('.js-wpv-date-front-end-clear-' + url_param ).show();
							form.find('.js-wpv-date-param-' + url_param + '-value' ).val( response['timestamp'] ).trigger( 'change' );
						});
					},
					dateFormat: 'ddmmyy',
					minDate: wpv_pagination_local.datepicker_min_date,
					maxDate: wpv_pagination_local.datepicker_max_date,
					showOn: "button",
					buttonImage: wpv_pagination_local.calendar_image,
					buttonText: wpv_pagination_local.calendar_text,
					buttonImageOnly: true,
					changeMonth: true,
					changeYear: true
				});
		});
	};
	
	/**
	* clone_form
	*
	* Clones a form using the fixed clone() method that covers select and textarea elements
	*
	* @param object fil
	* @param array targets
	*
	* @since 1.9
	*/
	
	self.clone_form = function( fil, targets ) {
		var cloned = fil.wpv_clone();
		targets.each( function() {
			$( this ).replaceWith( cloned );
		});
	};
	
	/**
	* render_frontend_media_shortcodes
	*
	* Render the WordPress media players for items inside a container.
	*
	* @param object container
	*
	* @since 1.9
	*/
	
	self.render_frontend_media_shortcodes = function( container ) {
		container.find( '.wp-audio-shortcode, .wp-video-shortcode' ).each( function() {
			var thiz = $( this );
			thiz.mediaelementplayer();
		});
		container.find( '.wp-playlist' ).each( function() {
			var thiz = $( this );
			return new WPPlaylistView({ el: this });
		});
	};
	
	// ------------------------------------
	// Get updated results
	// ------------------------------------
	
	/**
	* get_updated_query_results
	*
	* Shared method for paginating Views and WordPress Archives, and also for parametric search on both.
	* Returns a promise so you can operate on the response at will.
	*
	* Note that it applis the current form regardless it being submitted or not.
	* @todo we might want to avoid adding that data when the form contains unsubmitted changes.
	*
	* @param view_number	The object hash
	* @param page			The page that we want to get
	* @param form			The form to track changes against
	* @param expect			'form'|'full'|'both' Whether to return the full View, just the form, or both
	*
	* @since 2.1
	*/
	
	self.get_updated_query_results = function( view_number, page, form, expect ) {
		var data				= {},
		sort					= {},
		environment				= {},
		search					= {},
		extra					= {}.
		attributes				= {},
		lang					= ( typeof icl_lang == 'undefined' ) ? false : icl_lang,
		parametric_data			= form.data( 'parametric' );
		
		sort['wpv_sort_orderby']	= parametric_data['sort']['orderby'];
		sort['wpv_sort_order']		= parametric_data['sort']['order'];
		sort['wpv_sort_orderby_as']	= parametric_data['sort']['orderbyas'];
		
		if ( parametric_data['environment'].current_post_id > 0 ) {
			environment['wpv_aux_current_post_id'] = parametric_data['environment'].current_post_id;
		}
		if ( parametric_data['environment'].parent_post_id > 0 ) {
			environment['wpv_aux_parent_post_id'] = parametric_data['environment'].parent_post_id;
		}
		if ( parametric_data['environment'].parent_term_id > 0 ) {
			environment['wpv_aux_parent_term_id'] = parametric_data['environment'].parent_term_id;
		}
		if ( parametric_data['environment'].parent_user_id > 0 ) {
			environment['wpv_aux_parent_user_id'] = parametric_data['environment'].parent_user_id;
		}
		
		if ( form.find( '.js-wpv-post-relationship-update' ).length ) {
			search['dps_pr'] = form.find( '.js-wpv-post-relationship-update' ).serializeArray();
		}
		if ( 
			form.hasClass( 'js-wpv-dps-enabled' ) 
			|| form.hasClass( 'js-wpv-ajax-results-enabled' ) 
		) {
			search['dps_general'] = form.find( '.js-wpv-filter-trigger, .js-wpv-filter-trigger-delayed' ).serializeArray();
		}
		
		attributes = parametric_data['attributes'];
		
		extra = self.get_extra_url_query_parameters_by_form( form );
		
		data = {
			'view_number':	view_number,
			page:			page,
			sort:			sort,
			attributes:		attributes,
			environment:	environment,
			search:			search,
			extra:			extra,
			expect:			expect
		};
		
		if ( lang ) {
			data['lang'] = lang;
		}
		
		switch ( parametric_data.query ) {
			case 'archive':
				data['action']	= 'wpv_get_archive_query_results';
				data['loop']	= parametric_data.loop;
				break;
			default:
				data['action']	= 'wpv_get_view_query_results';
				data['id']		= parametric_data.id;
				if ( form.attr( 'data-targetid' ) ) {
					data['target_id'] = form.data( 'targetid' );
				} else if ( $( '.js-wpv-form-only.js-wpv-filter-form-' + view_number ).length > 0 ) {
					data['target_id'] = $( '.js-wpv-form-only.js-wpv-filter-form-' + view_number ).data( 'targetid' );
				}
				data['wpv_view_widget_id']	= parametric_data['widget_id'];
				break;
		}
		return $.ajax({
			type:		"POST",
			dataType:	"json",
			url:		wpv_pagination_local.front_ajaxurl,
			data:		data
		});
	};
	
	// ------------------------------------
	// Events
	// ------------------------------------
	
	/**
	* Window resize event
	*
	* Make Views layouts responsive
	*
	* @since 1.9
	* @since 1.11 added debounce
	*/
	
	$( window ).on( 'resize', _.debounce(
		function() {
			$( '.js-wpv-layout-responsive' ).each( function() {
				$( this ).css( 'width', '' );
			})
			.promise()
			.done( function() {
				$( document ).trigger( 'js_event_wpv_layout_responsive_resize_completed' );
			});
		},
		wpv_pagination_local.resize_debounce_tolerance
	));
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	self.init = function() {
		self.render_frontend_datepicker();
	};
	
	self.init();

};

WPViews.ViewPagination = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	self.rollover_running = [];
	self.rollover_stopped = [];
	
	self.init_rollover_timing_fired = false;
	
	self.pagination_queue = {};
	
	self.pagination_effects				= {};
	self.pagination_effects_conditions	= {};
	self.pagination_effects_spinner		= {};
	self.paged_views					= {};
	self.paged_views_initial_page		= {};
	
	self.last_paginated_view				= [];
	self.paginated_history_reach			= 0;
	self.add_paginated_history				= true;
	self.pagination_effect_state_push		= [ 'fade', 'slidev', 'slideh' ];
	self.pagination_effect_state_replace	= [];
	self.pagination_effect_state_keep		= [ 'infinite' ];
	
	self.init_scrolling_event_fired = false;
		
	self.slide_data_defaults = { 
		view_number:		'',
		page:				0,
		max_pages:			0,
		speed:				500,
		next:				true,
		effect:				'fade',
		response:			null,
		wpvPaginatorFilter: null,
		wpvPaginatorLayout: null,
		responseFilter:		null,
		responseView:		null,
		callback_next_func:	WPViews.view_frontend_utils.just_return
	};
	
	self.pagination_doing_ajax = [];
		
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	/**
	* add_view_parameters
	*
	* Add several information to the data used to get pagination pages.
	* For example, add column sorting data, parametric search data and parent View data.
	*
	* @since 1.9
	* @since 2.1	Deprecated
	*/
	
	self.add_view_parameters = function( data, page, view_number ) {
		var this_form			= $( 'form.js-wpv-filter-form-' + view_number ),
		this_prelements			= this_form.find( '.js-wpv-post-relationship-update' ),
		this_form_environment	= this_form.data( 'environment' );
		
		data['action']				= 'wpv_get_page';
		data['page']				= page;
		data['view_number']			= view_number;
		data['wpv_sort_orderby']	= this_form.data( 'orderby' );
		data['wpv_sort_order']		= this_form.data( 'order' );
		data['wpv_sort_orderby_as']	= this_form.data( 'orderbyas' );
		data['wpv_view_widget_id']	= this_form.data( 'viewwidgetid' );
		data['view_hash']			= this_form.data( 'viewhash' );
		data['dps_pr']				= {};
		data['dps_general']			= {};
		
		if ( this_form_environment.current_post_id > 0 ) {
			data['wpv_aux_current_post_id'] = this_form_environment.current_post_id;
		}
		if ( this_form_environment.parent_post_id > 0 ) {
			data['wpv_aux_parent_post_id'] = this_form_environment.parent_post_id;
		}
		if ( this_form_environment.parent_term_id > 0 ) {
			data['wpv_aux_parent_term_id'] = this_form_environment.parent_term_id;
		}
		if ( this_form_environment.parent_user_id > 0 ) {
			data['wpv_aux_parent_user_id'] = this_form_environment.parent_user_id;
		}
		
		if ( this_prelements.length ) {
			data['dps_pr'] = this_prelements.serializeArray();
		}
		if ( this_form.hasClass( 'js-wpv-dps-enabled' ) || this_form.hasClass( 'js-wpv-ajax-results-enabled' ) ) {
			data['dps_general'] = this_form.find( '.js-wpv-filter-trigger, .js-wpv-filter-trigger-delayed' ).serializeArray();
		}
		
		return data;
	};
	
	/**
	* pagination_preload_pages
	*
	* Preload pages to a reach.
	*
	* @param object preload_data
	*
	* @since 1.9
	*/
	
	self.pagination_preload_pages = function( preload_data ) {
		var page = parseInt( preload_data.page, 10 ),
		max_pages = parseInt( preload_data.max_pages, 10 ),
		max_reach = parseInt( preload_data.max_reach, 10 );
		
		if ( max_reach > max_pages ) {
			max_reach = max_pages;
		}
		
		if ( preload_data.preload_pages == 'enabled' ) {
			var reach = 1;
			while ( reach < max_reach ) {
				self.pagination_preload_next_page( preload_data.view_number, page, max_pages, reach );
				self.pagination_preload_previous_page( preload_data.view_number, page, max_pages, reach );
				reach++;
			}
		}
		if ( preload_data.cache_pages == 'enabled' ) {
			self.pagination_cache_current_page( preload_data.view_number, page );
		}
	};
	
	/**
	* pagination_cache_current_page
	*
	* Cache current page.
	*
	* @param string	view_number
	* @param int	page
	*
	* @since 1.9
	*/
	
	self.pagination_cache_current_page = function( view_number, page ) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var current_page_permalink,
		content;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( page ) ) {
			WPViews.view_frontend_utils.get_updated_query_results( view_number, page, $( 'form.js-wpv-filter-form-' + view_number ), 'full' )
				.done( function( response ) {
					if ( response.success ) {
						window.wpvCachedPages[view_number][page] = response.data.full;
						content = $( response.data.full ).find( 'img' );
						content.each( function() {
							window.wpvCachedImages.push( this.src );
						});
					}
				});
		}
	};
	
	/**
	* pagination_preload_next_page
	*
	* Load the next page, or the next one counting "reach" pages.
	*
	* @param string	view_number
	* @param int	page
	* @param int	max_pages
	* @param int	reach
	*
	* @since 1.9
	*/
	
	self.pagination_preload_next_page = function( view_number, page, max_pages, reach ) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var next_page = page + reach,
		content,
		current_page_permalink;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( next_page ) ) {
			if ( ( next_page - 1 ) < max_pages ) {
				WPViews.view_frontend_utils.get_updated_query_results( view_number, next_page, $( 'form.js-wpv-filter-form-' + view_number ), 'full' )
					.done( function( response ) {
						if ( response.success ) {
							window.wpvCachedPages[view_number][next_page] = response.data.full;
							content = $( response.data.full ).find( 'img' );
							content.each( function() {
								window.wpvCachedImages.push( this.src );
							});
						}
					});
			}
		}
	};
	
	/**
	* pagination_preload_previous_page
	*
	* Load the previous page, or the previous one counting "reach" pages.
	*
	* @param string	view_number
	* @param int	page
	* @param int	max_pages
	* @param int	reach
	*
	* @since 1.9
	*/
	
	self.pagination_preload_previous_page = function(view_number, page, max_pages, reach) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var previous_page = page - reach,
		current_page_permalink,
		content;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( previous_page ) ) {
			// LOAD PREVIOUS
			if ( ( previous_page + 1 ) > 1 ) {
				WPViews.view_frontend_utils.get_updated_query_results( view_number, previous_page, $( 'form.js-wpv-filter-form-' + view_number ), 'full' )
					.done( function( response ) {
						if ( response.success ) {
							window.wpvCachedPages[view_number][previous_page] = response.data.full;
							content = $( response.data.full ).find( 'img' );
							content.each( function() {
								window.wpvCachedImages.push( this.src );
							});
						}
					});
			} else if ( (previous_page + 1 ) === 1 ) { // LOAD LAST PAGE IF ON FIRST PAGE
				WPViews.view_frontend_utils.get_updated_query_results( view_number, max_pages, $( 'form.js-wpv-filter-form-' + view_number ), 'full' )
					.done( function( response ) {
						if ( response.success ) {
							window.wpvCachedPages[view_number][max_pages] = response.data.full;
							window.wpvCachedPages[view_number][0] = response.data.full;
							content = $( response.data.full ).find( 'img' );
							content.each( function() {
								window.wpvCachedImages.push( this.src );
							});
						}
					});
			}
		}
	};
	
	/**
	* trigger_pagination
	*
	* Manage the View pagination after a control has been inited
	*
	* @param string	view_number
	* @param int	page
	*
	* @since 1.9
	*/
	
	self.trigger_pagination = function( view_number, page ) {
		if ( ! window.wpvPaginationAnimationFinished.hasOwnProperty( view_number ) ) {
			window.wpvPaginationAnimationFinished[ view_number ] = false;
		} else if ( window.wpvPaginationAnimationFinished[ view_number ] !== true ) {
			if ( ! window.wpvPaginationQueue.hasOwnProperty( view_number ) ) {
				window.wpvPaginationQueue[view_number] = [];
			}
			window.wpvPaginationQueue[ view_number ].push( arguments );
			return;
		}
		if ( ! view_number in self.paged_views ) {
			window.wpvPaginationAnimationFinished[ view_number ] = true;
			return;
		}
		window.wpvPaginationAnimationFinished[ view_number ] = false;
		
		if ( self.paged_views[ view_number ].effect in self.pagination_effects_conditions ) {
			if ( ! self.pagination_effects_conditions[ self.paged_views[ view_number ].effect ]( self.paged_views[ view_number ], page ) ) {
				window.wpvPaginationAnimationFinished[ view_number ] = true;
				return;
			}
		}
		
		var data = {},
		wpvPaginatorLayout = $( '#wpv-view-layout-' + view_number ),
		wpvPaginatorFilter = $( 'form[name=wpv-filter-' + view_number + ']' ),
		speed = 500,
		next = true,
		max_reach = parseInt( self.paged_views[ view_number ].preload_reach ) + 1,
		callback_next_func = WPViews.view_frontend_utils.just_return,
		data_for_get_page,
		img;
		
		// Not using AJAX pagination
		// For WPAs we just need to adjust the form target and we are done :-)
		if ( 
			self.paged_views[ view_number ].type == 'disabled' 
			|| self.paged_views[ view_number ].type == 'paged' 
		) {

			switch ( self.paged_views[ view_number ].query ) {
				case 'archive':
					current_page_permalink = self.paged_views[ view_number ][ 'base_permalink' ].replace( 'WPV_PAGE_NUM', page );
					window.location.replace( current_page_permalink );
					break;
				default:
					WPViews.view_frontend_utils.set_extra_url_query_parameters_by_form( wpvPaginatorFilter );
					// Adjust the wpv_paged hidden input to the page that we want to show
					if ( $( 'input[name=wpv_paged]' ).length > 0 ) {
						$( 'input[name=wpv_paged]' ).attr( 'value', page );
					} else {
						$( '<input>')
							.attr({
								type: 'hidden',
								name: 'wpv_paged',
								value: page
							})
							.appendTo( wpvPaginatorFilter );
					}
					wpvPaginatorFilter[0].submit();
					break;
			}
			return;
		}
		// Using AJAX pagination
		window.wpvPaginationAjaxLoaded[view_number] = false;
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[view_number] || [];
		
		if ( this.historyP.hasOwnProperty( view_number ) ) {
			next = ( this.historyP[ view_number ] < page ) ? true : false;
		}
		
		if ( self.paged_views[ view_number ].callback_next !== '' ) {
			callback_next_func = window[ self.paged_views[ view_number ].callback_next ];
			if ( typeof callback_next_func !== "function" ) {
				callback_next_func = WPViews.view_frontend_utils.just_return;
			}
		}
		
		data_for_get_page = { 
			view_number:		view_number,
			page:				page,
			max_pages:			parseInt( self.paged_views[ view_number ].max_pages, 10 ),
			speed:				parseFloat( self.paged_views[ view_number ].duration ),
			next:				next,
			effect:				self.paged_views[ view_number ].effect,
			wpvPaginatorFilter: wpvPaginatorFilter,
			wpvPaginatorLayout: wpvPaginatorLayout,
			responseView:		null,
			callback_next_func:	callback_next_func
		};
		
		if ( 
			window.wpvCachedPages[ view_number ].hasOwnProperty( page ) 
		) {
			data_for_get_page.response = window.wpvCachedPages[ view_number ][ page ];
			self.prepare_slide( data_for_get_page );
		} else {
			// Set loading class
			if ( self.paged_views[ view_number ].spinner !== 'disabled' ) {
				if ( self.paged_views[ view_number ].effect in self.pagination_effects_spinner ) {
					self.pagination_effects_spinner[ self.paged_views[ view_number ].effect ]( view_number, wpvPaginatorLayout );
				} else {
					self.pagination_effects_spinner[ 'fade' ]( view_number, wpvPaginatorLayout );
				}
			}
			WPViews.view_frontend_utils.get_updated_query_results( view_number, page, $( 'form.js-wpv-filter-form-' + view_number ), 'full' )
				.done( function( response ) {
					if ( response.success ) {
						data_for_get_page.response = response.data.full;
						self.prepare_slide( data_for_get_page );
					}
				});
		}
		self.pagination_preload_pages({
			view_number:	view_number, 
			cache_pages:	self.paged_views[ view_number ].cache_pages, 
			preload_pages:	self.paged_views[ view_number ].preload_pages, 
			page:			page, 
			max_reach:		max_reach, 
			max_pages:		self.paged_views[ view_number ].max_pages 
		});
		this.historyP[ view_number ] = page;
		return;
	};
	
	/**
	* prepare_slide
	*
	* Wrap the old layout into a div.wpv_slide_remove and change its ID to ~-response
	* Preload images on the new page if needed
	* Fire self.pagination_slide
	*
	* @param object data
	*
	* @since 1.9
	*/
	
	self.prepare_slide = function( data ) {
		var slide_data = $.extend( {}, self.slide_data_defaults, data ),
		width = slide_data.wpvPaginatorLayout.width(),
		outer_width = slide_data.wpvPaginatorLayout.outerWidth(),
		height = slide_data.wpvPaginatorLayout.height(),
		outer_height = slide_data.wpvPaginatorLayout.outerHeight(),
		responseObj = $( '<div></div>' ).append( slide_data.response ),
		preloadedImages,
		images;
		
		slide_data.responseView = responseObj.find( '.js-wpv-view-layout-' + slide_data.view_number );
		slide_data.responseFilter = responseObj.find( 'form.js-wpv-filter-form-' + slide_data.view_number ).html();
		slide_data.pagination_page_permalink = slide_data.responseView.data( 'permalink' );
		
		// Wrap old layout in a div.wpv_slide_remove nd change its ID to ~-response
		slide_data.wpvPaginatorLayout
			.attr( 'id', 'wpv-view-layout-' + slide_data.view_number + '-response' )
			.wrap( '<div class="wpv_slide_remove" style="width:' + outer_width + 'px;height:' + outer_height + 'px;overflow:hidden;" />' )
			.css( 'width', width );
		// Add an ID attribute to the upcoming new layout, and hide it
		slide_data.responseView
			.attr( 'id', 'wpv-view-layout-' + slide_data.view_number )
			.css( {
				'visibility': 'hidden',
				'width': width
			} );
		
		// Preload images if needed
		if ( slide_data.wpvPaginatorLayout.hasClass( 'js-wpv-layout-preload-images' ) ) {
			preloadedImages = [];
			images = slide_data.responseView.find( 'img' );
			if ( images.length < 1 ) {
				self.pagination_slide( slide_data );
			} else {
				images.one( 'load', function() {
					preloadedImages.push( $( this ).attr( 'src' ) );
					if ( preloadedImages.length === images.length ) {
						self.pagination_slide( slide_data );
					}
				}).each( function() {
					$( this ).load();
				});
			}
			// Fix inner nested Views with AJAX pagination: the inner View, when preloading mages, was rendered with visibility:hidden by default
			slide_data.responseView
				.find( '.js-wpv-layout-preload-images' )
					.css( 'visibility', 'visible' );
		} else {
			self.pagination_slide( slide_data );
		}
		WPViews.view_frontend_utils.render_frontend_datepicker();
	};
	
	/**
	* pagination_slide
	*
	* Routes to the right pagination effect callback
	*
	* @param object data
	*
	* @since 1.9
	*/
	
	self.pagination_slide = function( data ) {
		var slide_data = $.extend( {}, self.slide_data_defaults, data );
		
		switch ( slide_data.effect ) {
			case 'slideleft':
				slide_data.next = true;
				slide_data.effect = 'slideh';
				break;
			case 'slideright':
				slide_data.next = false;
				slide_data.effect = 'slideh';
				break;
			case 'slidedown':
				slide_data.next = false;
				slide_data.effect = 'slidev';
				break;
			case 'slideup':
				slide_data.next = true;
				slide_data.effect = 'slidev';
				break;
		}
		
		if ( ! slide_data.effect in self.pagination_effects ) {
			slide_data.effect = 'fade';
		}
		
		self.pagination_effects[ slide_data.effect ]( slide_data );
	};
	
	/**
	* pagination_queue_trigger
	*
	* Manage multiple and fast pagination requests.
	*
	* @param int		view_number
	* @param boolean	next
	* @param object		wpvPaginatorFilter
	*
	* @todo why do we pass next and wpvPaginatorFilter here?
	*
	* @since 1.9
	*/
	
	self.pagination_queue_trigger = function( view_number, next, wpvPaginatorFilter ) {
		var args,
		page,
		max_pages;
		if ( window.wpvPaginationQueue.hasOwnProperty( view_number ) && window.wpvPaginationQueue[view_number].length > 0 ) {
		// when double clicking,we have set window.wpvPaginationQueue[view_number][1] and maybe we could tweak it to change the page number. Maybe checkin historyP
			window.wpvPaginationQueue[view_number].sort();
			args = window.wpvPaginationQueue[view_number][0];
			window.wpvPaginationQueue[view_number].splice(0, 1);
			page = args[1];
			max_pages = args[4];
			if ( page > max_pages ) {
				page = 1;
			} else if ( page < 1 ) {
				page = max_pages;
			}
			self.trigger_pagination( view_number, page );
		}
	};
	
	// ------------------------------------
	// Events for Views pagination
	// ------------------------------------
	
	/**
	* Manage pagination triggered from prev/next links
	*
	* @since 1.9
	*/
	
	$( document ).on( 'click', '.js-wpv-pagination-next-link, .js-wpv-pagination-previous-link', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		view_number = thiz.data( 'viewnumber' ),
		page = thiz.data( 'page' );
		wpv_stop_rollover[ view_number ] = true;
		self.trigger_pagination( view_number, page );
	});
	
	/**
	* Manage pagination triggered by a change in the page selector dropdown
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', '.js-wpv-page-selector', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		view_number = thiz.data( 'viewnumber' ),
		page = thiz.val();
		wpv_stop_rollover[ view_number ] = true
		self.trigger_pagination( view_number, page );
	});
	
	/**
	* Manage pagination triggered by a click on a pagination link.
	*
	* @since 1.9
	*
	* @note Safari on iOS might need to also listen to the touchstart event. Investigate this!
	*/
	
	$( document ).on( 'click', '.js-wpv-pagination-link', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		data_collected = {};
		data_collected.view_number = thiz.data( 'viewnumber');
		data_collected.page = thiz.data( 'page' );
		var i;
		// TODO this can be improved: we should not need a loop here at all
		// @todo review this, data_collected.max_pages is UNDEFINED
		for ( i = 1; i <= data_collected.max_pages; i++ ) {
			if ( i === data_collected.page ) {
				$( '.js-wpv-page-link-' + data_collected.view_number + '-' + i ).addClass( 'wpv_page_current' );
			} else {
				$( '.js-wpv-page-link-' + data_collected.view_number + '-' + i ).removeClass( 'wpv_page_current' );
			}
			
		}
		wpv_stop_rollover[ data_collected.view_number ] = true
		self.trigger_pagination( data_collected.view_number, data_collected.page );
	});
	
	/*
	$( document ).on( 'click', '.js-wpv-pagination-pause-rollover', function( e ) {
		e.preventDefault();
		var view_num = $( this ).data( 'viewnumber' );
		wpv_stop_rollover[view_num] = true;
	});
	
	$( document ).on( 'click', '.js-wpv-pagination-resume-rollover', function( e ) {
		e.preventDefault();
		var view_num = $( this ).data( 'viewnumber' );
		delete wpv_stop_rollover[view_num];
	});
	*/
	
	// ------------------------------------
	// Events for WordPress Archives pagination
	// ------------------------------------
	
	/**
	* Manage pagination triggered from prev/next links, and numeric links, including their own prev/next ones
	*
	* @since 2.1
	*/
	
	$( document ).on( 'click', '.js-wpv-archive-pagination-link, .js-wpv-archive-pagination-next-link, .js-wpv-archive-pagination-prev-link, .js-wpv-archive-pagination-links-next-link, .js-wpv-archive-pagination-links-prev-link', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		view_number = thiz.data( 'viewnumber' ),
		page = thiz.data( 'page' );
		wpv_stop_rollover[ view_number ] = true;
		self.trigger_pagination( view_number, page );
	});
	
	// ------------------------------------
	// Custom events
	// ------------------------------------
	
	/**
	* js_event_wpv_pagination_completed
	*
	* Event fired after a pagination transition has been completed
	*
	* @param data
	* 	- view_unique_id
	* 	- effect
	* 	- speed
	* 	- layout
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_pagination_completed', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_media_shortcodes( data.layout );
		// Init pagination for any inner View that might be included inside the new page
		self.init_paged_views( data.layout );
		self.init_preload_images( data.layout );
		self.init_preload_pages( data.layout );
	});
	
	$( document ).on( 'js_event_wpv_parametric_search_results_updated', function( event, data ) {
		// Init the pagination settings for this View results being updated
		self.init_paged_view( data.view_unique_id );
		// Init pagination for any inner View that might be included inside the new page
		self.init_paged_views( data.layout );
		self.init_preload_images( data.layout );
		self.init_preload_pages( data.layout );
	});
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	/**
	* init_effects
	*
	* Define the default pagination effects, can be extended by third parties.
	* Each callback gets the foloring parameters:
	* @param object slide_data
	*
	* @since 1.11
	*/
	
	self.init_effects = function() {
		self.pagination_effects = {
			infinite: function( slide_data ) {
				
				if ( slide_data.page != ( self.paged_views[ slide_data.view_number ].page + 1 ) ) {
					// This should never happen! See self.pagination_effects_conditions
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					slide_data.wpvPaginatorLayout
						.animate( { opacity: 1 }, 300 )
						.unwrap()// If it got here, it has a -response suffix in the ID and is wrapped in an auxiliar div
						.attr( 'id', 'wpv-view-layout-' + slide_data.view_number  );
					window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
					window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
					
				} else {
				
					// content.match(/<!-- HOOK OPEN -->(W?)\<!-- HOOK CLOSE -->/);
					var data_for_events = {},
					data_for_history = {};
					
					data_for_events.view_unique_id	= slide_data.view_number;
					data_for_events.effect			= 'infinite';
					data_for_events.speed			= slide_data.speed;
					
					data_for_history.view_number				= slide_data.view_number;
					data_for_history.page						= slide_data.page;
					data_for_history.effect						= 'infinite';
					data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
					
					if (
						slide_data.wpvPaginatorLayout.find( '.js-wpv-loop' ).length > 0 
						&& slide_data.responseView.find( '.js-wpv-loop' ).length > 0 
					) {
						slide_data.responseView
							.find( '.js-wpv-loop' )
							.children()
								.addClass( 'wpv-loop-item-blink' )
								.css( { 'opacity': '0.3' } );
						slide_data.responseView
							.find( '.js-wpv-loop' )
							.prepend( 
								slide_data.wpvPaginatorLayout
									.find( '.js-wpv-loop' )
									.html() 
							);
						slide_data.wpvPaginatorLayout.html( slide_data.responseView.html() );
						slide_data.wpvPaginatorLayout
							.find( '.wpv-loop-item-blink' )
								.removeClass( 'wpv-loop-item-blink' )
								.animate( { opacity: 1 }, slide_data.speed );
								
					} else {
						var oldHTML = slide_data.wpvPaginatorLayout.html(),
						oldArray = oldHTML.split( '<!-- WPV_Infinite_Scroll -->', 3 ),
						oldReplace = ( oldArray.length > 2 ) ? oldArray[1] : '';
						slide_data.wpvPaginatorLayout.html(
							slide_data.responseView.html().replace( 
								'<!-- WPV_Infinite_Scroll_Insert -->',
								oldReplace
							)
						);
					}
					data_for_events.layout = slide_data.wpvPaginatorLayout;
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					slide_data.wpvPaginatorLayout
						.animate( { opacity: 1 }, 300 )
						.unwrap()
						.attr( 'id', 'wpv-view-layout-' + slide_data.view_number  );
										
					slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
					
					self.paged_views[ slide_data.view_number ].page = slide_data.page;
					
					window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
					window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
					slide_data.callback_next_func();
					self.manage_browser_history( data_for_history );
					$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
					self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
				}
			},
			slideh: function( slide_data ) {
				var height = slide_data.wpvPaginatorLayout.height(),
				old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};
				
				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'slideh';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'slideh';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				if ( slide_data.next === true ) {
					slide_data.wpvPaginatorLayout.css( 'float', 'left' );
					slide_data.responseView.css( {"float": "left", "visibility": "visible"} );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div style="width:5000px;" />' );
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut(function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				} else {
					slide_data.wpvPaginatorLayout.css( 'float', 'right' );
					slide_data.responseView.css( {'float': 'right', 'visibility': 'visible'} );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div style="height:' + height +  ';width:' + ( slide_data.responseView.outerWidth() + slide_data.wpvPaginatorLayout.outerWidth() ) + 'px; margin-left:-' + ( slide_data.wpvPaginatorLayout.outerWidth() ) + 'px;" />' );
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				}
			},
			slidev: function( slide_data ) {
				var old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};
				
				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'slidev';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'slidev';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				if ( slide_data.next === true ) {
					slide_data.responseView.css( 'visibility', 'visible' );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div />' );
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function(){
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '-' + slide_data.responseView.outerHeight()+'px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '-'+old_height+'px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginTop: '-'+old_height+'px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				} else {
					slide_data.responseView.css( 'visibility', 'visible' );
					slide_data.wpvPaginatorLayout
						.before( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div />' );
					$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					slide_data.wpvPaginatorLayout.parent().css( {'position': 'relative', 'margin-top': '-' + slide_data.responseView.outerHeight() + 'px'} );
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
						});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				}
			},
			fade: function( slide_data ) {
				var old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};

				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'fade';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'fade';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				$( '.js-wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
					$( this ).remove();
				});
				
				slide_data.wpvPaginatorLayout
					.css( {'position': 'absolute', 'z-index': '5'} )
					.after( slide_data.responseView )
						.next()
						.css( 'position', 'static' );
				// We need to set a zero timeout here since the above modifications introduce a race condition that produces a wrong new_height 
				setTimeout( function() {
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout.fadeOut( slide_data.speed, function() {
							slide_data.wpvPaginatorLayout.unwrap().remove();
							
							slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
							
							window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
							window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
							slide_data.callback_next_func();
							self.manage_browser_history( data_for_history );
							$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
							WPViews.view_pagination.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
						});
						slide_data.responseView
							.hide()
							.css( 'visibility', 'visible' )
							.fadeIn( slide_data.speed );
					} else {
						slide_data.wpvPaginatorLayout.fadeOut( slide_data.speed, function() {
							slide_data.wpvPaginatorLayout
								.parent()
									.animate( {height: new_height+'px'}, slide_data.speed, function() {
										slide_data.wpvPaginatorLayout.unwrap().remove();
										
										slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
										
										window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
										window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
										slide_data.callback_next_func();
										self.manage_browser_history( data_for_history );
										$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
										WPViews.view_pagination.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
										slide_data.responseView
											.hide()
											.css( 'visibility', 'visible' )
											.fadeIn( slide_data.speed );
									});
						});
					}
				}, 0 );
			}
		};
	
	};
	
	/**
	* manage_browser_history
	*
	* Makes the history in the browser work with AJAX pagination, except infinite scrolling and sliders
	*
	* @note Some View IDs are stored in WPViews.rollower_ids as numbers, so we need to check parseInt( data.view_number ) too
	*
	* @param object data
	*
	* @since 1.11
	*/
	
	self.manage_browser_history = function( data ) {
		var num_view_number = $.isNumeric( data.view_number ) ? parseInt( data.view_number ) : data.view_number;
		if ( 
			! _.has( WPViews, 'rollower_ids' )
			|| (
				! _.contains( WPViews.rollower_ids, data.view_number ) 
				&& ! _.contains( WPViews.rollower_ids, num_view_number ) 
			)
		) {
			if ( self.paged_views[ data.view_number ].manage_history == 'enabled' ) {
				if ( self.add_paginated_history == true ) {
					if ( ! _.contains( self.pagination_effect_state_keep, data.effect ) ) {
						if ( _.contains( self.pagination_effect_state_replace, data.effect ) ) {
							history.replaceState( null, '', data.pagination_page_permalink );
						} else {
							self.last_paginated_view.push( data.view_number );
							state_obj = { 
								view_number: data.view_number, 
								page: data.page
							};
							history.pushState( state_obj, '', data.pagination_page_permalink );
							// http://scrollsample.appspot.com/items
							// http://html5.gingerhost.com/
							self.paginated_history_reach = self.paginated_history_reach + 1;
						}
					}
				} else {
					self.add_paginated_history = true;
				}
			}
		}
	};
	
	/**
	* window.onpopstate
	*
	* Manages the browser back button click based on daya added by Views pagination
	*
	* @since 1.11
	*/
	
	window.onpopstate = function( event ) {
		if ( event.state == null ) {
			var last_paginated_view_number = self.last_paginated_view.pop();
			if ( last_paginated_view_number != undefined ) {
				self.add_paginated_history = false;
				self.paged_views_initial_page[ last_paginated_view_number ] = self.paged_views_initial_page[ last_paginated_view_number ] || 1;
				self.trigger_pagination( last_paginated_view_number, self.paged_views_initial_page[ last_paginated_view_number ] );
			}
		} else {
			if (
				_.has( event.state, 'view_number' )
				&& _.has( event.state, 'page' )
			) {
				self.add_paginated_history = false;
				self.trigger_pagination( event.state.view_number, event.state.page );
			}
		}
	};
	
	/**
	* When the parametric search with automatic results has been completed, reset the pagination history and add a state with the current URL
	*
	* @since 1.11
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_results_updated', function( event, data ) {
		window.wpvCachedPages[ data.view_unique_id ] = [];
		self.last_paginated_view = [];
		if ( self.paginated_history_reach > 0 ) {
			window.history.go( -Math.abs( self.paginated_history_reach ) );
			self.paginated_history_reach = 0;
		}
		// HACK! HACK! HACK!
		// Chrome and Safari execute history.replaceState before history.go so we end up with a mess
		// That is why we need to set a timeout here
		// See https://code.google.com/p/chromium/issues/detail?id=529810
		setTimeout( function() {
			history.replaceState( null, '', data.permalink );
		}, 100 );
	});
	
	/**
	* init_effects_conditions
	*
	* Lets you define a condition that an effect must meet to be applied, doing nothing otherwise.
	* Each callback gets the following parameters:
	* @param object	view_pagination_data
	* @param int	page
	*
	* @since 1.11
	*/
	
	self.init_effects_conditions = function() {
		self.pagination_effects_conditions = {
			infinite: function( view_pagination_data, page ) {
				if ( page != ( view_pagination_data.page + 1 ) ) {
					return false;
				}
				return true;				
			}
		};
	};
	
	/**
	* init_effects_conditions
	*
	* Lets you define a condition that an effect must meet to be applied, doing nothing otherwise.
	* Each callback gets the following parameters:
	* @param string	view_number
	* @param object	wpvPaginatorLayout
	*
	* @since 1.11
	*/
	
	self.init_effects_spinner = function() {
		self.pagination_effects_spinner['fade']			= 
		self.pagination_effects_spinner['slideleft']	= 
		self.pagination_effects_spinner['slideright']	= 
		self.pagination_effects_spinner['slideh']		= 
		self.pagination_effects_spinner['slideup']		=
		self.pagination_effects_spinner['slidedown']	= 
		self.pagination_effects_spinner['slidev']		= function( view_number, wpvPaginatorLayout ) {
			var img = new Image();
			img.src = self.paged_views[ view_number ].spinner_image;
			img.onload = function() {
				var wpvPaginatorLayoutOffset = wpvPaginatorLayout.position(),
				wpvPaginatorSpinner = '<div style="'
					+ 'width:' + img.width + 'px;'
					+ 'height:' + img.height + 'px;'
					+ 'border:none;'
					+ 'background:transparent 50% 50% no-repeat url(' + self.paged_views[ view_number ].spinner_image + ');'
					+ 'position:absolute;'
					+ 'z-index:99;'
					+ 'top:' + ( Math.round( wpvPaginatorLayoutOffset.top ) + ( Math.round( wpvPaginatorLayout.height()/2 ) ) - ( Math.round( img.height/2 ) ) ) + 'px;'
					+ 'left:' + ( Math.round( wpvPaginatorLayoutOffset.left ) + ( Math.round( wpvPaginatorLayout.width()/2 ) ) - ( Math.round( img.width/2 ) ) ) + 'px;'
					+ '" '
					+ 'class="wpv_slide_loading_img js-wpv_slide_loading_img_' + view_number + '"'
					+ '>'
					+ '</div>';
				wpvPaginatorLayout
					.before( wpvPaginatorSpinner )
						.animate( { opacity: 0.5 }, 300 );
			};
		};
		self.pagination_effects_spinner['infinite'] = function( view_number, wpvPaginatorLayout ) {
			var img = new Image();
			img.src = self.paged_views[ view_number ].spinner_image;
			img.onload = function() {
				var wpvPaginatorLayoutOffset = wpvPaginatorLayout.position(),
				wpvPaginatorSpinner = '<div style="'
					+ 'width:' + img.width + 'px;'
					+ 'height:' + img.height + 'px;'
					+ 'border:none;'
					+ 'background:transparent 50% 50% no-repeat url(' + self.paged_views[ view_number ].spinner_image + ');'
					+ 'position:absolute;'
					+ 'z-index:99;'
					+ 'top:' + ( Math.round( wpvPaginatorLayoutOffset.top ) + ( wpvPaginatorLayout.height() ) - ( Math.round( img.height/2 ) ) ) + 'px;'
					+ 'left:' + ( Math.round( wpvPaginatorLayoutOffset.left ) + ( Math.round( wpvPaginatorLayout.width()/2 ) ) - ( Math.round( img.width/2 ) ) ) + 'px;'
					+ '" '
					+ 'class="wpv_slide_loading_img js-wpv_slide_loading_img_' + view_number + '"'
					+ '>'
					+ '</div>';
				wpvPaginatorLayout
					.before( wpvPaginatorSpinner )
						.animate( { opacity: 0.5 }, 300 );
			};
		};
	};
	
	/**
	* init_paged_views
	*
	* Gather the data for paginating each of the Views rendered in a page
	*
	* @since 1.11
	*/
	
	self.init_paged_views = function( container ) {
		var init_scrolling_event = false,
		init_rollover_timing = false;
		this.historyP = this.historyP || [];
		window.wpvCachedPages = window.wpvCachedPages || [];
		window.wpvCachedImages = window.wpvCachedImages || [];
		$( '.js-wpv-view-layout', container ).each( function() {
			var thiz = $( this ),
			view_number = thiz.data( 'viewnumber' );
			self.init_paged_view( view_number );
			if (
				self.paged_views[ view_number ].effect == 'infinite' 
				&& self.paged_views[ view_number ].page == 1
			) {
				init_scrolling_event = true;
			}
			if ( self.paged_views[ view_number ].type == 'rollover' ) {
				init_rollover_timing = true;
				self.rollover_running.push( view_number );
			}
		});
		if ( 
			! self.init_scrolling_event_fired 
			&& init_scrolling_event 
		) {
			self.init_scrolling_event_callback();
		}
		
		if ( 
			! self.init_rollover_timing_fired
			&& init_rollover_timing
		) {
			self.init_rollover_timing_callback();
		}
		return self;
	};
	
	/**
	* init_paged_view
	*
	* Gather pagination info for a specific View rendered in a page.
	* Note that this is also used to init the View pagination data after a parametric search change.
	*
	* @since 1.11
	*/
	
	self.init_paged_view = function( view_number ) {
		this.historyP = this.historyP || [];
		self.paged_views[ view_number ] = $( '#wpv-view-layout-' + view_number ).data( 'pagination' );
		self.paged_views_initial_page[ view_number ] = self.paged_views[ view_number ].page;
		this.historyP[ view_number ] = self.paged_views[ view_number ].page;
		window.wpvCachedPages[ view_number ] = [];
		if ( 
			self.paged_views[ view_number ].type != 'disabled' 
			&& self.paged_views[ view_number ].type != 'paged' 
			&& self.paged_views[ view_number ].page > 1 
		) {
			// Infinite scrolling only can br triggered from the first page - individual URLs can not have that effect
			$( '#wpv-view-layout-' + view_number ).removeClass( 'js-wpv-layout-infinite-scrolling' );
		}
		if ( $( '#wpv-view-layout-' + view_number ).parents( '.js-wpv-view-layout' ).length > 0 ) {
			// Disable history management in inner Views in nested structures
			self.paged_views[ view_number ].manage_history = 'disabled';
		}
	};
	
	/**
	* is_infinite_triggable
	*
	* Auxiliar method to check whether the scroll got to a point where a pagination event should be triggered
	*
	* @param object	view_layout
	*
	* @since 1.11
	* @since 2.0	Add a tolerance setting
	* @since 2.0	Trigger the pagination when scrolling to the bottom of the page
	*/
	
	self.is_infinite_triggable = function( view_layout ) {
		var flag_element = view_layout,
		view_number = view_layout.data( 'viewnumber' );
		infinite_tolerance = self.paged_views[ view_number ].infinite_tolerance;
		infinite_tolerance = ( isNaN( infinite_tolerance ) ) ? 0 : + infinite_tolerance;
		if ( view_layout.find( '.js-wpv-loop' ).length > 0 ) {
			flag_element = view_layout.find( '.js-wpv-loop' );
		}
		return (
			( flag_element.offset().top + flag_element.outerHeight() ) <= ( $( window ).scrollTop() + $( window ).height() + infinite_tolerance )
			|| ( $( window ).scrollTop() + $(window).height() ) == $( document ).height()
		);
	};
	
	/**
	* init_scrolling_event_callback
	*
	* Init the scrolling event callback, only when there is a View with infinite scrlling in a page
	*
	* @since 1.11
	*/
	
	self.init_scrolling_event_callback = function() {
		$( window ).on( 'scroll', _.debounce( 
			_.throttle( 
				function() {
					$( '.js-wpv-layout-infinite-scrolling' ).each( function() {
						var thiz = $( this ),
						thiz_view_number = thiz.data( 'viewnumber' );
						if ( 
							self.paged_views[ thiz_view_number ].page < self.paged_views[ thiz_view_number ].max_pages 
							&& self.is_infinite_triggable( thiz )
						) {
							self.trigger_pagination( thiz_view_number, self.paged_views[ thiz_view_number ].page + 1 );
						}
					});
				},
				1000
			),
			1000
		));
		self.init_scrolling_event_fired = true;
	};
	
	self.init_rollover_timing_callback = function() {
		//alert(self.rollover_running.toSource());
	};
	
	/**
	* pagination_init_preload_images
	*
	* Init-preload images.
	*
	* @since 1.9
	*/
	
	self.init_preload_images = function( container ) {
		$( '.js-wpv-layout-preload-images', container ).css( 'visibility', 'hidden' ); // TODO move it to the CSS file and test
		$( '.js-wpv-layout-preload-images', container ).each( function() {
			var preloadedImages = [],
			element = $( this ),
			images = element.find( 'img' );
			if ( images.length < 1 ) {
				element.css( 'visibility', 'visible' );
			} else {
				images.one( 'load', function() {
					preloadedImages.push( $( this ).attr( 'src' ) );
					if ( preloadedImages.length === images.length ) {
						element.css( 'visibility', 'visible' );
					}
				}).each( function() {
					if( this.complete ) {
						$( this ).load();
					}
				});
				setTimeout( function() {
					element.css( 'visibility', 'visible' );
				}, 3000 );
			}
		});
	};
	
	self.init_preload_pages = function( container ) {
		$( '.js-wpv-layout-preload-pages', container ).each( function() {
			var thiz = $( this ),
			view_number = thiz.data( 'viewnumber' ),
			max_pages = parseInt( self.paged_views[ view_number ].max_pages, 10 ),
			max_reach = parseInt( self.paged_views[ view_number ].preload_reach, 10 ) + 1;
			
			self.pagination_preload_pages({
				view_number:	view_number, 
				cache_pages:	'disabled', 
				preload_pages:	'enabled', 
				page:			1, 
				max_reach:		max_reach,
				max_pages:		max_pages 
			});
		});
	};
	
	self.init = function() {
		self.init_effects();
		self.init_effects_conditions();
		self.init_effects_spinner();
		// Init Views inside the html
		var init_html = $( 'html' );
		self.init_paged_views( init_html );
		self.init_preload_images( init_html );
		self.init_preload_pages( init_html );
	}
	
	self.init();

};

WPViews.ViewParametricSearch = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	self.manage_update_results = function( data ) {
		if ( data.ajax_before !== '' ) {
			var ajax_before_func = window[data.ajax_before];
			if ( typeof ajax_before_func === "function" ) {
				ajax_before_func( data.view_num );
			}
		}
		var data_for_events = {};
		data_for_events.view_unique_id	= data.view_num;
		data_for_events.permalink	= data.permalink;
		data.layout.fadeOut( 200, function() {
			data.layout.html( data.new_layout.html() )
				.attr( 'data-pagination', data.new_layout.attr( 'data-pagination' ) )
				.data( 'pagination', data.new_layout.data( 'pagination' ) )
				.fadeIn( 'fast', function() {
					var ajax_after_func = window[data.ajax_after];
					if ( typeof ajax_after_func === "function" ) {
						ajax_after_func( data.view_num );
					}
					data_for_events.layout = data.layout;
					$( document ).trigger( 'js_event_wpv_parametric_search_results_updated', [ data_for_events ] );
				});		
		});
	};
	
	/**
	* manage_changed_form
	*
	* @param settings	object
	* 	{
	*		view_unique_id:			string			View unique ID
	*		form:					collection		Filter jQuery object
	* 		force_form_update:		boolean			
	* 		update_form				boolean			Whether the form will be updated, either because forced or native
	* 		force_results_update:	boolean			
	* 		update_results			boolean			Whether the results wil be updated, either because forced or native
	*	};
	*
	* @todo Switch .js-wpv-filter-data-for-this-form into a form data attribute that we can init on document.ready and refresh when neeeded
	*
	* @since 1.9
	* @since 2.1	Merge a group of parameters into an object
	*/
	
	self.manage_changed_form = function( settings ) {
		
		var fil				= settings.form,
		view_num			= settings.view_unique_id,
		lay					= $( '#wpv-view-layout-' + view_num ),
		full_data			= fil.find( '.js-wpv-filter-data-for-this-form' ),
		ajax_pre_before		= full_data.data( 'ajaxprebefore' ),
		view_type			= 'full',
		additional_forms	= $( '.js-wpv-filter-form-' + view_num ).not( fil ),
		additional_forms_only,
		additional_forms_full,
		ajax_get			= 'both',
		new_content_form,
		new_content_form_filter,
		new_content_full,
		new_content_full_filter,
		new_content_full_layout,
		spinnerContainer	= fil.find( '.js-wpv-dps-spinner' ).add( additional_forms.find( '.js-wpv-dps-spinner' ) ),
		spinnerItems		= spinnerContainer.length
		data_for_events		= {},
		data_for_manage_updated_results	= {};
		
		data_for_events.view_unique_id	= view_num;
		
		data_for_manage_updated_results.view_num		= view_num;
		data_for_manage_updated_results.ajax_before		= full_data.data( 'ajaxbefore' );
		data_for_manage_updated_results.ajax_after		= full_data.data( 'ajaxafter' );
		
		if ( fil.hasClass( 'js-wpv-form-only' ) ) {
			view_type = 'form';
		}
		if ( settings.update_form ) {
			if ( additional_forms.length > 0 ) {
				additional_forms_only = additional_forms.not( '.js-wpv-form-full' );
				additional_forms_full = additional_forms.not( '.js-wpv-form-only' );
				if ( view_type == 'form' ) {
					if ( additional_forms_full.length > 0 || settings.update_results ) {
						ajax_get = 'both';					
					} else {
						ajax_get = 'form';
					}
					if ( settings.update_results ) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					
					wpv_stop_rollover[ view_num ] = true;
					
					WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, ajax_get )
						.done( function( response ) {
							if ( response.success ) {
								new_content_form = $( '<div></div>' ).append( response.data.form );
								new_content_full = $( '<div></div>' ).append( response.data.full );
								new_content_form_filter = new_content_form.find( '.js-wpv-filter-form-' + view_num );
								new_content_full_filter = new_content_full.find( '.js-wpv-filter-form-' + view_num );
								new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
								
								fil.html( new_content_form_filter.html() );
								$( ".js-wpv-frontend-datepicker" )
									.removeClass( 'js-wpv-frontend-datepicker-inited' )
									.datepicker( "destroy" );
								WPViews.view_frontend_utils.clone_form( fil, additional_forms_only );
								additional_forms_full.each( function() {
									$( this ).html( new_content_full_filter.html() );
								});
								data_for_events.view_changed_form						= fil;
								data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
								data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
								$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
								if ( settings.update_results ) {
									data_for_manage_updated_results.layout			= lay;
									data_for_manage_updated_results.new_layout		= new_content_full_layout;
									data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
									self.manage_update_results( data_for_manage_updated_results );
								}
								spinnerContainer.hide();
							}
						});
				} else {
					if ( additional_forms_only.length > 0 ) {
						ajax_get = 'both';
					} else {
						ajax_get = 'full';
					}
					if ( settings.update_results ) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					
					wpv_stop_rollover[ view_num ] = true;
					
					WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, ajax_get )
						.done( function( response ) {
							if ( response.success ) {
								new_content_form = $( '<div></div>' ).append( response.data.form );
								new_content_full = $( '<div></div>' ).append( response.data.full );
								new_content_form_filter = new_content_form.find( '.js-wpv-filter-form-' + view_num );
								new_content_full_filter = new_content_full.find( '.js-wpv-filter-form-' + view_num );
								new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
								
								fil.html( new_content_full_filter.html() );
								$( ".js-wpv-frontend-datepicker" )
									.removeClass( 'js-wpv-frontend-datepicker-inited' )
									.datepicker( "destroy" );
								WPViews.view_frontend_utils.clone_form( fil, additional_forms_full );
								additional_forms_only.each( function() {
									$( this ).html( new_content_form_filter.html() );
								});
								data_for_events.view_changed_form						= fil;
								data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
								data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
								$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
								if ( settings.update_results ) {
									data_for_manage_updated_results.layout			= lay;
									data_for_manage_updated_results.new_layout		= new_content_full_layout;
									data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
									self.manage_update_results( data_for_manage_updated_results );
								}
								spinnerContainer.hide();
							}
						});
				}
			} else {
				if ( view_type == 'form' ) {
					if ( settings.update_results ) {
						ajax_get = 'both';
						// NOTE this should never happen:
						// If change is done on an only-form and there is no extra form, there is no full form thus there is no layout
						// WARNING this can be executed on an only-form form from a View with automatic results
						// I might want to avoid this branch completely
						// NOTE-2 might be a good idea to keep-on-clear// As we might be displaying the layout in non-standard ways
						// So keeping the check for lay.length should suffice
						
					} else {
						ajax_get = 'form';
					}
					
					if ( settings.update_results ) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					
					wpv_stop_rollover[ view_num ] = true;
					
					WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, ajax_get )
						.done( function( response ) {
							if ( response.success ) {
								new_content_form = $( '<div></div>' ).append( response.data.form );
								new_content_full = $( '<div></div>' ).append( response.data.full );
								new_content_form_filter = new_content_form.find( '.js-wpv-filter-form-' + view_num );
								//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
								new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
								fil.html( new_content_form_filter.html() );
								data_for_events.view_changed_form						= fil;
								data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
								data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
								$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
								if ( settings.update_results ) {
									data_for_manage_updated_results.layout			= lay;
									data_for_manage_updated_results.new_layout		= new_content_full_layout;
									data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
									self.manage_update_results( data_for_manage_updated_results );
								}
								spinnerContainer.hide();
							}
						});
				} else {
					if ( settings.update_results ) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					
					wpv_stop_rollover[ view_num ] = true;
					
					WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, 'full' )
						.done( function( response ) {
							if ( response.success ) {
								//new_content_form = $( '<div></div>' ).append( ajax_result.form );
								new_content_full = $( '<div></div>' ).append( response.data.full );
								//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
								new_content_full_filter = new_content_full.find( '.js-wpv-filter-form-' + view_num );
								new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
								fil.html( new_content_full_filter.html() );
								data_for_events.view_changed_form						= fil;
								data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
								data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
								$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
								if ( settings.update_results ) {
									data_for_manage_updated_results.layout			= lay;
									data_for_manage_updated_results.new_layout		= new_content_full_layout;
									data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
									self.manage_update_results( data_for_manage_updated_results );
								}
								spinnerContainer.hide();
							}
						});
				}
			}
		} else {
			if ( additional_forms.length > 0 ) {
				additional_forms_only = additional_forms.not( '.js-wpv-form-full' );
				additional_forms_full = additional_forms.not( '.js-wpv-form-only' );
				if ( view_type == 'form' ) {
					$( ".js-wpv-frontend-datepicker" )
						.removeClass( 'js-wpv-frontend-datepicker-inited' )
						.datepicker( "destroy" );
					WPViews.view_frontend_utils.clone_form( fil, additional_forms_only );
					if ( additional_forms_full.length > 0 || settings.update_results ) {
						if ( settings.update_results ) {
							if ( ajax_pre_before !== '' ) {
								var ajax_pre_before_func = window[ajax_pre_before];
								if ( typeof ajax_pre_before_func === "function" ) {
									ajax_pre_before_func( view_num );
								}
							}
							$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
						}
						if ( spinnerItems ) {// TODO maybe only when updating results
							$( spinnerContainer ).fadeIn( 'fast' );
						}
						
						wpv_stop_rollover[ view_num ] = true;
						
						WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, 'full' )
							.done( function( response ) {
								if ( response.success ) {
									//new_content_form = $( '<div></div>' ).append( response.data.form );
									new_content_full = $( '<div></div>' ).append( response.data.full );
									//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
									new_content_full_filter = new_content_full.find( '.js-wpv-filter-form-' + view_num );
									new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
									
									additional_forms_full.each( function() {
										$( this ).html( new_content_full_filter.html() );
									});
									data_for_events.view_changed_form						= fil;
									data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
									data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
									$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
									if ( settings.update_results ) {
										data_for_manage_updated_results.layout			= lay;
										data_for_manage_updated_results.new_layout		= new_content_full_layout;
										data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
										self.manage_update_results( data_for_manage_updated_results );
									}
									spinnerContainer.hide();
								}
							});
					} else {
						data_for_events.view_changed_form						= fil;
						data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
					}
				} else {
					$( ".js-wpv-frontend-datepicker" )
						.removeClass( 'js-wpv-frontend-datepicker-inited' )
						.datepicker( "destroy" );
					WPViews.view_frontend_utils.clone_form( fil, additional_forms_full );
					WPViews.view_frontend_utils.render_frontend_datepicker();
					if ( additional_forms_only.length > 0 || settings.update_results ) {
						if ( additional_forms_only.length > 0 ) {
							ajax_get = 'both';
						} else {
							ajax_get = 'full';
						}
						
						if ( settings.update_results ) {
							if ( ajax_pre_before !== '' ) {
								var ajax_pre_before_func = window[ajax_pre_before];
								if ( typeof ajax_pre_before_func === "function" ) {
									ajax_pre_before_func( view_num );
								}
							}
							$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
						}
						if ( spinnerItems ) {// TODO maybe only when updating results
							$( spinnerContainer ).fadeIn( 'fast' );
						}
						
						wpv_stop_rollover[ view_num ] = true;
						
						WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, ajax_get )
							.done( function( response ) {
								if ( response.success ) {
									new_content_form = $( '<div></div>' ).append( response.data.form );
									new_content_full = $( '<div></div>' ).append( response.data.full );
									new_content_form_filter = new_content_form.find( '.js-wpv-filter-form-' + view_num );
									//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
									new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
									additional_forms_only.each( function() {
										$( this ).html( new_content_form_filter.html() );
									});
									data_for_events.view_changed_form						= fil;
									data_for_events.view_changed_form_additional_forms_only	= additional_forms_only;
									data_for_events.view_changed_form_additional_forms_full	= additional_forms_full;
									$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
									if ( settings.update_results ) {
										data_for_manage_updated_results.layout			= lay;
										data_for_manage_updated_results.new_layout		= new_content_full_layout;
										data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
										self.manage_update_results( data_for_manage_updated_results );
									}
									spinnerContainer.hide();
								}
							});
					}
				}
			} else {
				if ( settings.update_results ) {
					if ( ajax_pre_before !== '' ) {
						var ajax_pre_before_func = window[ajax_pre_before];
						if ( typeof ajax_pre_before_func === "function" ) {
							ajax_pre_before_func( view_num );
						}
					}
					$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					
					wpv_stop_rollover[ view_num ] = true;
					
					WPViews.view_frontend_utils.get_updated_query_results( view_num, 1, fil, 'full' )
						.done( function( response ) {
							if ( response.success ) {
								//new_content_form = $( '<div></div>' ).append( response.data.form );
								new_content_full = $( '<div></div>' ).append( response.data.full );
								//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
								//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
								new_content_full_layout = new_content_full.find( '.js-wpv-view-layout-' + view_num );
								data_for_manage_updated_results.layout			= lay;
								data_for_manage_updated_results.new_layout		= new_content_full_layout;
								data_for_manage_updated_results.permalink		= response.data['parametric_permalink'];
								self.manage_update_results( data_for_manage_updated_results );
								spinnerContainer.hide();
							}
						});
				}
			}
		}
	};
	
	/**
	* Normalize the data passed to the js_event_wpv_parametric_search_triggered event.
	*
	* @para data	Object
	* 	{
	*		view_unique_id:			string			View unique ID
	*		form:					collection		Filter jQuery object
	* 		force_form_update:		boolean			Optional
	* 		force_results_update:	boolean			Optional
	*	};
	*
	* @return settings	Object
	* 	{
	*		view_unique_id:			string			View unique ID
	*		form:					collection		Filter jQuery object
	* 		force_form_update:		boolean			
	* 		update_form				boolean			Whether the form will be updated, either because forced or native
	* 		force_results_update:	boolean			
	* 		update_results			boolean			Whether the results wil be updated, either because forced or native
	*	};
	*
	* @since 2.1
	*/
	
	self.extend_wpv_parametric_search_triggered_data = function( data ) {
		var defaults	= { 
			force_form_update:		false, 
			force_results_update:	false
		},
		settings		= $.extend( {}, defaults, data ),
		view_loop		= $( '#wpv-view-layout-' + settings.view_unique_id );
		
		if (
			settings.force_form_update == false 
			&& (
				// The form contains a post relationship filter, hence it really needs to be updated
				settings.form.find( '.js-wpv-post-relationship-update' ).length > 0
				|| (
					// The form contains pagination settings and the results are set to be updated
					(
						settings.force_results_update == true 
						|| settings.form.hasClass( 'js-wpv-ajax-results-enabled' ) 
					) && _.has( WPViews.view_pagination.paged_views, settings.view_unique_id ) 
					&& _.has( WPViews.view_pagination.paged_views[ settings.view_unique_id ], 'has_controls_in_form' )
			&& WPViews.view_pagination.paged_views[ settings.view_unique_id ].has_controls_in_form == 'enabled'
				)
			)
		) {
			settings.force_form_update = true;
		}
		
		settings.update_form	= ( settings.force_form_update || settings.form.hasClass( 'js-wpv-dps-enabled' ) );
		settings.update_results	= ( view_loop.length > 0 && ( settings.force_results_update || settings.form.hasClass( 'js-wpv-ajax-results-enabled' ) ) );
		
		return settings;
	};
	
	// ------------------------------------
	// Events
	// ------------------------------------
	
	// Show datepicker on date string click
	$( document ).on( 'click', '.js-wpv-date-display', function() {
		var url_param = $( this ).data( 'param' );
		$( '.js-wpv-date-front-end-' + url_param ).datepicker( 'show' );
	});

	// Remove current selected date
	$( document ).on( 'click', '.js-wpv-date-front-end-clear', function(e) {
		e.preventDefault();
		var thiz = $( this ),
		url_param = thiz.data( 'param' ),
		form = thiz.closest( 'form' );
		form.find( '.js-wpv-date-param-' + url_param ).html( '' );
		form.find( '.js-wpv-date-front-end-' + url_param ).val( '' );
		thiz.hide();
		form.find('.js-wpv-date-param-' + url_param + '-value' )
			.val( '' )
			.trigger( 'change' );
	});
	
	$( document ).on( 'change', '.js-wpv-post-relationship-update', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' ),
		additional_forms = $( '.js-wpv-filter-form-' + view_number ).not( fil ),
		currentposttype = thiz.data( 'currentposttype' ),
		watchers = fil.find( '.js-wpv-' + currentposttype + '-watch' ).add( additional_forms.find( '.js-wpv-' + currentposttype + '-watch' ) ),
		watcherslength = watchers.length,
		i;
		if ( watcherslength ) {
			for( i = 0; i < watcherslength; i++ ) {
				$( watchers[i] )
					.attr( 'disabled', true )
					.removeAttr( 'checked' )
					.removeAttr( 'selected' )
					.not( ':button, :submit, :reset, :hidden, :radio, :checkbox' )
					.val( '0' );
			}
		}
		
		var data_for_events = {
			view_unique_id:		view_number,
			form:				fil,
			force_form_update:	true
		};
		
		data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
		
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
	});

	$( document ).on( 'change', '.js-wpv-filter-trigger', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' );
		
		var data_for_events = {
			view_unique_id:	view_number,
			form:			fil
		};
		
		data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
		
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
	});

	$( document ).on( 'click', '.js-wpv-ajax-results-enabled .js-wpv-submit-trigger, .js-wpv-ajax-results-submit-enabled .js-wpv-submit-trigger', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' );
		
		var data_for_events = {
			view_unique_id:			view_number,
			form:					fil,
			force_form_update:		false,
			force_results_update:	true
		};
		
		data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
		
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
	});

	$( document).on( 'keypress', '.js-wpv-ajax-results-enabled .js-wpv-filter-trigger-delayed, .js-wpv-ajax-results-submit-enabled .js-wpv-filter-trigger-delayed', function( e ) {
		// Enter pressed?
		if ( e.which == 13 ) {
			e.preventDefault();
			var thiz = $( this ),
			fil = thiz.closest( 'form' ),
			view_number = fil.data( 'viewnumber' );
			
			var data_for_events = {
				view_unique_id:			view_number,
				form:					fil,
				force_results_update:	true
			};
			
			data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
			
			$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
		}
	});

	$( document ).on( 'click', '.js-wpv-reset-trigger', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' ),
		additional_forms = $( '.js-wpv-filter-form-' + view_number ).not( fil ),
		watchers,
		watcherslength,
		i,
		target = fil.attr( 'action' ),
		form_only_force_update = false,
		form_only_results_force_update = false;
		
		if ( fil.hasClass( 'js-wpv-form-only' ) ) {
			watchers = fil.find( 'input, select' ).add( additional_forms.find( 'input, select' ) );
			watcherslength = watchers.length;
			form_only_force_update = ( fil.hasClass( 'js-wpv-dps-enabled' ) || ( fil.find( '.js-wpv-post-relationship-update' ).length > 0 ) );
			if (
				fil.hasClass( 'js-wpv-ajax-results-enabled' ) 
				|| fil.hasClass( 'js-wpv-ajax-results-submit-enabled' )
			) {
				form_only_results_force_update = true;
			}
			
			if ( watcherslength ) {
				for ( i = 0; i < watcherslength; i++ ) {
					if ( ! $( watchers[i] ).hasClass( 'js-wpv-keep-on-clear' ) ) {
						$( watchers[i] )
							.attr( 'disabled', form_only_force_update )
							.removeAttr( 'checked' )
							.removeAttr( 'selected' )
							.not( ':button, :submit, :reset, :hidden, :radio, :checkbox' )
							.val( '' );
					}
				}
			}
			
			var data_for_events = {
				view_unique_id:			view_number,
				form:					fil,
				force_form_update:		form_only_force_update,
				force_results_update:	form_only_results_force_update
			};
			
			data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
			
			$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
		} else if ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) || fil.hasClass( 'js-wpv-ajax-results-submit-enabled' ) ) {
			watchers = fil.find( 'input, select' ).add( additional_forms.find( 'input, select' ) );
			watcherslength = watchers.length;
			if ( watcherslength ) {
				for ( i = 0; i < watcherslength; i++ ) {
					if ( ! $( watchers[i] ).hasClass( 'js-wpv-keep-on-clear' ) ) {
						$( watchers[i] )
							.attr( 'disabled', true )
							.removeAttr( 'checked' )
							.removeAttr( 'selected' )
							.not( ':button, :submit, :reset, :hidden, :radio, :checkbox' )
							.val( '' );
					}
				}
			}
			
			var data_for_events = {
				view_unique_id:			view_number,
				form:					fil,
				force_form_update:		true,
				force_results_update:	true
			};
			
			data_for_events = self.extend_wpv_parametric_search_triggered_data( data_for_events );
			
			$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ data_for_events ] );
		} else {
			window.location.href = target;
		}
	});
	
	$( document ).on( 'js_event_wpv_parametric_search_triggered', function( event, settings ) {
		self.manage_changed_form( settings );
	});

	// Also, stop the rollover if we do any modification on the parametric search form

	$( document ).on( 'change', '.js-wpv-filter-trigger, .js-wpv-filter-trigger-delayed', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_num = fil.data( 'viewnumber' );
		wpv_stop_rollover[view_num] = true;
	});
	
	// ------------------------------------
	// Custom events
	// ------------------------------------
	
	/**
	* js_event_wpv_parametric_search_started
	*
	* Event fired before updating the parametric search forms and results.
	*
	* @param data
	* 	- view_unique_id
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_started', function( event, data ) {
		
	});
	
	
	/**
	* js_event_wpv_parametric_search_form_updated
	*
	* Event fired after updating the parametric search forms.
	*
	* @param data
	* 	- view_unique_id
	* 	- view_changed_form
	* 	- view_changed_form_additional_forms_only
	* 	- view_changed_form_additional_forms_full
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_form_updated', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_datepicker();
	});
	
	/**
	* js_event_wpv_parametric_search_results_updated
	*
	* Event fired after updating the parametric search results.
	*
	* @param data
	* 	- view_unique_id
	* 	- layout
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_results_updated', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_media_shortcodes( data.layout );
	});
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	self.init = function() {
		
	}
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
	WPViews.view_frontend_utils = new WPViews.ViewFrontendUtils( $ );
	WPViews.view_pagination = new WPViews.ViewPagination( $ );
    WPViews.view_parametric_search = new WPViews.ViewParametricSearch( $ );
});