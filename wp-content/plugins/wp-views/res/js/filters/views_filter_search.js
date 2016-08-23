/**
* Views Search Filter GUI - script
*
* Adds basic interaction for the Search Filter
*
* @package Views
*
* @since 1.7.0
*/


var WPViews = WPViews || {};

WPViews.SearchFilterGUI = function( $ ) {
	
	var self = this;
	
	self.view_id = $('.js-post_ID').val();
	
	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';
	
	self.post_row = '.js-wpv-filter-row-post-search';
	self.post_options_container_selector = '.js-wpv-filter-post-search-options';
	self.post_summary_container_selector = '.js-wpv-filter-post-search-summary';
	self.post_edit_open_selector = '.js-wpv-filter-post-search-edit-open';
	self.post_close_save_selector = '.js-wpv-filter-post-search-edit-ok';
	
	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
	
	self.tax_row = '.js-wpv-filter-row-taxonomy-search';
	self.tax_options_container_selector = '.js-wpv-filter-taxonomy-search-options';
	self.tax_summary_container_selector = '.js-wpv-filter-taxonomy-search-summary';
	self.tax_edit_open_selector = '.js-wpv-filter-taxonomy-search-edit-open';
	self.tax_close_save_selector = '.js-wpv-filter-taxonomy-search-edit-ok';
	
	self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select').serialize();
	
	//--------------------
	// Events for search
	//--------------------
	
	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file
	
	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	});
	
	// Track changes in options
	
	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() { // watch on inputs change
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
			$( self.post_close_save_selector )
				.addClass('button-secondary')
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data('close')
				);
			$( self.post_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});
	
	// Save filter options
	
	self.save_filter_post_search = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );
		
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
		
		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var action = thiz.data( 'saveaction' ),
			nonce = thiz.data('nonce'),
			spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
			error_container = thiz
				.closest( '.js-filter-row' )
					.find( '.js-wpv-filter-toolset-messages' );
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
			var data = {
				action:			action,
				id:				self.view_id,
				filter_options:	self.post_current_options,
				wpnonce:		nonce
			};
			$.ajax( {
				type:		"POST",
				url:		ajaxurl,
				dataType:	"json",
				data:		data,
				success:	function( response ) {
					if ( response.success ) {
						$( self.post_close_save_selector )
							.addClass('button-secondary')
							.removeClass('button-primary js-wpv-section-unsaved')
							.html( 
								WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
							);
						$( self.post_summary_container_selector ).html( response.data.summary );
						WPViews.query_filters.close_and_glow_filter_row( self.post_row, 'wpv-filter-saved' );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags' );
						$( document ).trigger( event );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						} else {
							$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
						}
					} else {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					}
				},
				error:		function( ajaxContext ) {
					console.log( "Error: ", textStatus, errorThrown );
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_search' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				},
				complete:	function() {
					spinnerContainer.remove();
					thiz.hide();
				}
			});
		}
	};
	
	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_search( 'js_event_wpv_save_filter_post_search_completed', false );
	});
	
	// Remove filter from the save queue an clean cache
	
	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_search' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-get-parametric-search-hints' );
		}
	});
	
	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_search' ) {
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'add' } );
		}
	});
	
	//--------------------
	// Events for taxonomy search
	//--------------------
	
	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file
	
	$( document ).on( 'click', self.tax_edit_open_selector, function() {
		self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
		$( self.tax_close_save_selector ).show();
		$( self.tax_row ).addClass( 'wpv-filter-row-current' );
	});
	
	// Track changes
	
	$( document ).on( 'change keyup input cut paste', self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select', function() {
		WPViews.query_filters.clear_validate_messages( self.tax_row );
		if ( self.tax_current_options != $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'add' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.removeClass( 'button-secondary' )
				.html(
					WPViews.query_filters.icon_save + $( self.tax_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-secondary' )
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.tax_close_save_selector ).data('close')
				);
			$( self.tax_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});
	
	// Save options
	
	self.save_filter_taxonomy_search = function( event, propagate ) {
		var thiz = $( self.tax_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.tax_row );
		
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );
		
		if ( self.tax_current_options == $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.tax_row );
			thiz.hide();
		} else {
			var action = thiz.data( 'saveaction' ),
			nonce = thiz.data('nonce'),
			spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
			error_container = thiz
				.closest( '.js-filter-row' )
					.find( '.js-wpv-filter-toolset-messages' );
			self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
			var data = {
				action:			action,
				id:				self.view_id,
				filter_options:	self.tax_current_options,
				wpnonce:		nonce
			};
			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					$( self.tax_close_save_selector )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary js-wpv-section-unsaved' )
						.html(
							WPViews.query_filters.icon_edit + $( self.tax_close_save_selector ).data( 'close' )
						);
					$( self.tax_summary_container_selector ).html( response.data.summary );
					WPViews.query_filters.close_and_glow_filter_row( self.tax_row, 'wpv-filter-saved' );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			}, 'json' )
			.fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( "Error: ", textStatus, errorThrown );
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_search' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.hide();
			});
		}
	};
	
	$( document ).on( 'click', self.tax_close_save_selector, function() {
		self.save_filter_taxonomy_search( 'js_event_wpv_save_filter_taxonomy_search_completed', false );
	});
	
	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'taxonomy_search' == filter_type ) {
			self.tax_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );
		}
	});
	
	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'taxonomy_search' ) {
			self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'add' } );
		}
	});
	
	//--------------------
	// Init hooks
	//--------------------
	
	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_post_search',
			callback:	self.save_filter_post_search,
			event:		'js_event_wpv_save_filter_post_search_completed'
		});
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_taxonomy_search',
			callback:	self.save_filter_taxonomy_search,
			event:		'js_event_wpv_save_filter_taxonomy_search_completed'
		});
	};
	
	//--------------------
	// Init
	//--------------------
	
	self.init = function() {
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.search_filter_gui = new WPViews.SearchFilterGUI( $ );
});