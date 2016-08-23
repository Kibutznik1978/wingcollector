var WPV_Toolset = WPV_Toolset  || {};

if ( typeof WPV_Toolset.CodeMirror_instance === "undefined" ) {
	WPV_Toolset.CodeMirror_instance = {};
}
if ( typeof WPV_Toolset.CodeMirror_instance_value === "undefined" ) {
	WPV_Toolset.CodeMirror_instance_value = {};
}
if ( typeof WPV_Toolset.CodeMirror_instance_qt === "undefined" ) {
	WPV_Toolset.CodeMirror_instance_qt = {};
}

// Instances definition


// Instances definition

WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content']			= icl_editor.codemirror( 'wpv_filter_meta_html_content', true );
WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_content']	= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].getValue();
WPV_Toolset.CodeMirror_instance_qt['wpv_filter_meta_html_content']		= quicktags( { id: 'wpv_filter_meta_html_content', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );


WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css']				= icl_editor.codemirror( 'wpv_filter_meta_html_css', true, 'css' );
WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_css']		= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].getValue();

WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js']				= icl_editor.codemirror( 'wpv_filter_meta_html_js', true, 'javascript' );
WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_js']		= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].getValue();

WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content']			= icl_editor.codemirror( 'wpv_layout_meta_html_content', true );
WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_content']	= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getValue();
WPV_Toolset.CodeMirror_instance_qt['wpv_layout_meta_html_content']		= quicktags( { id: 'wpv_layout_meta_html_content', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );

WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css']				= icl_editor.codemirror( 'wpv_layout_meta_html_css', true, 'css' );
WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_css']		= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].getValue();

WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js']				= icl_editor.codemirror( 'wpv_layout_meta_html_js', true, 'javascript' );
WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_js']		= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].getValue();

WPV_Toolset.CodeMirror_instance['wpv_content']							= icl_editor.codemirror( 'wpv_content', true );
WPV_Toolset.CodeMirror_instance_value['wpv_content']					= WPV_Toolset.CodeMirror_instance['wpv_content'].getValue();
WPV_Toolset.CodeMirror_instance_qt['wpv_content']						= quicktags( { id: 'wpv_content', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );

var WPViews = WPViews || {};

WPViews.EditScreenOptions = function( $ ) {
	
	var self = this;
	
	// ---------------------------------
	// Model
	// ---------------------------------
	
	self.view_id				= $('.js-post_ID').val();
	self.model					= {};
	self.purpose_to_sections	= {
		all:		{
			visible:	[ 'content-filter' ],
			hidden:		[ 'filter-extra-parametric', 'filter-extra', 'content' ]
		},
		parametric:	{
			visible:	[ 'content-filter', 'filter-extra-parametric', 'filter-extra' ],
			hidden:		[ 'content' ]
		}
	};
	
	/**
	* init_model
	*
	* Init track of sections that need to be manually updated.
	* Does not include editors
	*
	* @since 2.1
	*/
	
	self.init_model = function() {
		self.model['purpose']					= $( '.js-wpv-purpose' ).val();
		self.model['visible']					= $( '.js-wpv-screen-options:checked' ).map( function() {
														return $( this ).val();
													}).get();
		self.model['hidden']					= $( '.js-wpv-screen-options:not(:checked)' ).map( function() {
														return $( this ).val();
													}).get();
		return self;
	};
	
	// ---------------------------------
	// Save
	// ---------------------------------
	
	self.save_screen_options = function() {
		var container = $( '.js-wpv-screen-options-wrapper' ),
		options_visible = $( '.js-wpv-screen-options:checked' ).map( function() {
				return $( this ).val();
			}).get(),
		options_hidden = $( '.js-wpv-screen-options:not(:checked)' ).map( function() {
				return $( this ).val();
			}).get(),
		purpose = container.find('.js-wpv-purpose').val();
		container.find('.toolset-alert').remove();
		if ( 
			self.model['purpose'] != purpose 
			|| self.model['visible'] != options_visible
			|| self.model['hidden'] != options_hidden
		) {
			var data = {
				action:		'wpv_save_screen_options',
				id:			self.view_id,
				purpose:	purpose,
				visible:	options_visible,
				hidden:		options_hidden,
				wpnonce:	wpv_editor_strings.screen_options.nonce
			};
			$.ajax({
				type:		"POST",
				dataType: 	"json",
				url:		ajaxurl,
				data:		data,
				success:	function( response ) {
					if ( response.success ) {
						self.model['purpose']	= purpose;
						self.model['visible']	= options_visible;
						self.model['hidden']	= options_hidden;
						$( document ).trigger( 'js_event_wpv_screen_options_saved' );
					} else {
						//self.manage_action_bar_error( response.data );
					}
				},
				error:		function( ajaxContext ) {
					
				},
				complete:	function() {
					
				}
			});
		}
		return self;
	};
	
	self.screen_options_debounce_update = _.debounce( self.save_screen_options, 1000 );
	
	// ---------------------------------
	// Screen options
	// ---------------------------------
	
	self.init_screen_options = function() {
		var views_screen_options_container = $( '#js-screen-meta-dup > div#js-screen-options-wrap-dup' );
		$( '#screen-options-wrap' )
			.addClass( 'wpv-screen-options-wrapper js-wpv-screen-options-wrapper' )
			.html( views_screen_options_container.html() );
		views_screen_options_container.remove();
		return self;
	};
	
	self.validate_screen_options = function( changed ) {
		// First, validate against unsaved sections
		$( '.js-wpv-screen-options:not(:checked)' ).each( function() {
			var thiz_inner = $( this );
			if ( $('.js-wpv-settings-' + thiz_inner.val() ).find('.js-wpv-section-unsaved').length > 0 ) {
				thiz_inner.prop( 'checked', true );
				$('.js-wpv-screen-options-wrapper .js-wpv-toolset-messages')
					.wpvToolsetMessage({
						text:	wpv_editor_strings.screen_options.can_not_hide,
						type:	'error',
						inline:	true,
						stay:	true
					});
			}
		});
		// Now, specific dependencies
		if ( 
			(
				changed.val() == 'filter-extra-parametric' 
				&& changed.prop( 'checked' ) 
				&& ! $( '.js-wpv-show-hide-filter-extra' ).prop( 'checked' ) 
			) || (
				changed.val() == 'filter-extra' 
				&& ! changed.prop( 'checked' ) 
				&& $( '.js-wpv-show-hide-filter-extra-parametric' ).prop( 'checked' ) 
			)
		) {
			$( '.js-wpv-show-hide-filter-extra' ).prop( 'checked', true );
			$('.js-wpv-screen-options-wrapper .js-wpv-toolset-messages')
				.wpvToolsetMessage({
					text:	wpv_editor_strings.screen_options.parametric_search_needs_filter,
					type:	'info',
					inline:	true,
					stay:	true
				});
		}
		return self;
	};
	
	self.apply_screen_options = function() {
		$( '.js-wpv-screen-options:checked' ).each( function() {
			var thiz_inner_val = $( this ).val();
			$( '.js-wpv-settings-' + thiz_inner_val ).fadeIn( 'fast', function() {
				if ( thiz_inner_val == 'filter-extra' ) {
					WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].refresh();
					WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].refresh();
					WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].refresh();
				} else if ( thiz_inner_val == 'layout-extra' ) {
					WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].refresh();
					WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].refresh();
					WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].refresh();
				} else if ( thiz_inner_val == 'content' ) {
					WPV_Toolset.CodeMirror_instance['wpv_content'].refresh();
				}
			});
		});
		$( '.js-wpv-screen-options:not(:checked)' ).each( function() {
			var thiz_inner_val = $( this ).val();
			$( '.js-wpv-settings-' + thiz_inner_val ).hide();
		});
		return self;
	};
	
	self.manage_metasections = function() {
		$( '.js-wpv-screen-options-metasection' ).each( function() {
			var thiz_metasection_container = $( this ),
			thiz_metasection = thiz_metasection_container.data( 'metasection' );
			if ( thiz_metasection_container.find( '.js-wpv-screen-options:checked' ).length > 0 ) {
				$( '.' + thiz_metasection ).show();
			} else {
				$( '.' + thiz_metasection ).fadeOut( 'fast' );
			}
		});
		return self;
	};
	
	$( document ).on( 'change', '.js-wpv-screen-options', function() {
		var thiz = $( this );
		self
			.validate_screen_options( thiz )
			.apply_screen_options()
			.manage_metasections()
			.screen_options_debounce_update();
	});
	
	// ---------------------------------
	// Purpose management
	// ---------------------------------
	
	self.set_purpose_sections = function( purpose ) {
		_.each( self.purpose_to_sections[ purpose ].visible, function( element, index, list ) {
			$( '.js-wpv-show-hide-' + element + ':not(:checked)' )
				.prop( 'checked', true )
				.addClass( 'wpv-screen-options-changing' );
		});
		_.each( self.purpose_to_sections[ purpose ].hidden, function( element, index, list ) {
			$( '.js-wpv-show-hide-' + element + ':checked' )
				.prop( 'checked', false )
				.addClass( 'wpv-screen-options-changing' );
		});
		setTimeout( function () {
			$( '.js-wpv-screen-options' ).removeClass( 'wpv-screen-options-changing' );
		}, 1000 );
		return self;
	};
	
	$( document ).on( 'change', '.js-wpv-purpose', function() {
		self
			.set_purpose_sections( $( this ).val() )
			.apply_screen_options()
			.manage_metasections()
			.screen_options_debounce_update();
	});
	
	self.get_view_purpose = function( purpose ) {
		return self.model['purpose'];
	};
	
	// ---------------------------------
	// Init hooks
	// ---------------------------------
	
	self.init_hooks = function() {
		// Filter to get current View purpose
		Toolset.hooks.addFilter( 'wpv-filter-wpv-edit-screen-get-purpose', self.get_view_purpose );
		
		return self;
	};
	
	// ---------------------------------
	// Pointer
	// ---------------------------------
	
	self.init_pointer = function() {
		if ( $( '.js-wpv-set-wpa-purpose-pointer' ).length > 0 ) {
			self.screen_options_pointer = $('#screen-options-link-wrap').pointer({
				pointerClass:	'wp-toolset-pointer wp-toolset-views-pointer js-wp-toolset-views-set-wpa-purpose-pointer',
				pointerWidth:	400,
				content:		$( '.js-wpv-set-wpa-purpose-pointer' ).html(),
				position:		{
					edge:	'top',
					align:	'right'
				},
				buttons:		function( event, t ) {
					var button_close = $('<button class="button button-primary-toolset alignright js-wpv-close-this">' + wpv_editor_strings.pointer.close + '</button>');
					button_close.bind( 'click.pointer', function( e ) {
						e.preventDefault();
						if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
							var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
							$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
						}
						t.element.pointer( 'close' );
					});
					return button_close;
				}
			});
			// Now, let's move the arrow to the right of the top side
			$( '.js-wp-toolset-views-set-wpa-purpose-pointer .wp-pointer-arrow' ).css({
				'right':	'50px',
				'left':		'auto'
			});
			if ( ! $( '.js-wpv-set-wpa-purpose-pointer' ).hasClass( 'js-wpv-pointer-dismissed' ) ) {
				self.screen_options_pointer.pointer('open');
				$( document ).on( 'click', '#screen-options-link-wrap', function() {
					self.screen_options_pointer.pointer( 'close' );
				});
			}
		}
		return self;
	};
	
	// ---------------------------------
	// Init
	// ---------------------------------
	
	self.init = function() {
		self
			.init_screen_options()
			.init_model()
			.init_hooks()
			.manage_metasections()
			.init_pointer();
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.edit_screen_options = new WPViews.EditScreenOptions( $ );
});

WPViews.WPAEditScreen = function( $ ) {
	
	var self = this;
	
	self.get_view_query_mode = function() {
		return 'archive';
	};
	
	self.get_post_relationship_tree_reference = function() {
		if ( typeof WPViews.wpv_post_relationship_tree_reference === "undefined" ) {
			return {};
		}
		return WPViews.wpv_post_relationship_tree_reference;
	};
	
	// ---------------------------------
	// Model
	// ---------------------------------
	
	self.view_id			= $('.js-post_ID').val();
	self.model				= {};
	
	self.get_view_id = function() {
		return self.view_id;
	};
	
	/**
	* init_model
	*
	* Init track of sections that need to be manually updated.
	* Does not include editors
	*
	* @since 2.1
	*/
	
	self.init_model = function() {
		self.model['.js-wpv-title']						= $( '.js-title' ).val();
		self.model['.js-wpv-description']				= $( '.js-wpv-description' ).val();
		self.model['.js-wpv-layout-settings-extra-js']	= $( '.js-wpv-layout-settings-extra-js' ).val();
		return self;
	};
	
	// ---------------------------------
	// Frontend events
	// ---------------------------------
	
	self.frontend_events_comments = {
		js_event_wpv_pagination_completed: "\n\t/**" 
				+ "\n\t* data.view_unique_id " + wpv_editor_strings.event_trigger_callback_comments.view_unique_id
				+ "\n\t* data.effect " + wpv_editor_strings.event_trigger_callback_comments.effect
				+ "\n\t* data.speed " + wpv_editor_strings.event_trigger_callback_comments.speed
				+ "\n\t* data.layout " + wpv_editor_strings.event_trigger_callback_comments.layout
				+ "\n\t*/",
		js_event_wpv_parametric_search_triggered: "\n\t/**" 
				+ "\n\t* data.view_unique_id " + wpv_editor_strings.event_trigger_callback_comments.view_unique_id
				+ "\n\t* data.form " + wpv_editor_strings.event_trigger_callback_comments.form
				+ "\n\t* data.update_form " + wpv_editor_strings.event_trigger_callback_comments.update_form
				+ "\n\t* data.update_results " + wpv_editor_strings.event_trigger_callback_comments.update_results
				+ "\n\t*/",
		js_event_wpv_parametric_search_started: "\n\t/**" 
				+ "\n\t* data.view_unique_id " + wpv_editor_strings.event_trigger_callback_comments.view_unique_id
				+ "\n\t*/",
		js_event_wpv_parametric_search_form_updated: "\n\t/**" 
				+ "\n\t* data.view_unique_id " + wpv_editor_strings.event_trigger_callback_comments.view_unique_id
				+ "\n\t* data.form " + wpv_editor_strings.event_trigger_callback_comments.form
				+ "\n\t* data.view_changed_form_additional_forms_only " + wpv_editor_strings.event_trigger_callback_comments.view_changed_form_additional_forms_only
				+ "\n\t* data.view_changed_form_additional_forms_full " + wpv_editor_strings.event_trigger_callback_comments.view_changed_form_additional_forms_full
				+ "\n\t*/",
		js_event_wpv_parametric_search_results_updated: "\n\t/**" 
				+ "\n\t* data.view_unique_id " + wpv_editor_strings.event_trigger_callback_comments.view_unique_id
				+ "\n\t* data.layout " + wpv_editor_strings.event_trigger_callback_comments.layout
				+ "\n\t*/"
	};
	
	// ---------------------------------
	// Helpers
	// ---------------------------------
	
	self.overlay_container = $("<div class='wpv-setting-overlay js-wpv-setting-overlay'><div class='wpv-transparency'></div><i class='icon-lock fa fa-lock'></i></div>");
	
	self.dialog_minWidth = 870;
	
	self.calculate_dialog_maxWidth = function() {
		return ( $( window ).width() - 100 );
	};
	
	self.calculate_dialog_maxHeight = function() {
		return ( $( window ).height() - 100 );
	};
	
	$( document ).on( 'click', '.js-wpv-disable-events', function( e ) {
		e.preventDefault();
		return false;
	});
	
	self.apply_post_types_to_loop_data = {
		type:			'',
		name:			'',
		'default_pt':	[],
		'selected_pt':	[]
	};
	
	// ---------------------------------
	// Save queue
	// ---------------------------------
	
	/**
	* Store sections to be manually updatd on a queue.
	*
	* @since 2.1
	*/
	
	self.save_queue					= [];
	self.save_fail_queue			= [];
	self.save_callbacks				= {};
	self.save_section_defaults		= {
		callback:	self.save_section_defaults_callback,
		event:		'js_event_wpv_save_section_defaults_completed'
	};
	self.save_all_queue_step		= 0;
	self.save_all_queue_completed	= 100;
	self.save_all_progress_bar		= $( '#js-wpv-general-actions-bar .js-wpv-view-save-all-progress' );
	
	/**
	* save_section_defaults_callback
	*
	* Default callback and event to fire when saving a section.
	* This is usually overriden by each section data.
	*
	* @since 2.1
	*/
	
	self.save_section_defaults_callback = function( event, propagate ) {
		$( document ).trigger( event );
		if ( propagate ) {
			$( document ).trigger( 'js_wpv_save_section_queue' );
		} else {
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	/**
	* manage_save_queue
	*
	* Add or remove a section from the save queue.
	*
	* @since 2.1
	*/
	
	self.manage_save_queue = function( section, action, args ) {
		if ( typeof args === 'undefined' ) {
			args = {};
		}
		var target = { 
			section:	section, 
			args:		args 
		};
		switch ( action ) {
			case 'add':
				self.save_queue.push( target );
				self.save_queue = _.uniq( self.save_queue, function( item, key, a ) { 
					return item.section + '#' + _.keys( item.args ).join( '#' ) + '#' + _.values( item.args ).join( '#' );
				});
				break;
			case 'remove':
				self.save_queue = _.filter( self.save_queue, function( item ) {
					return ! _.isEqual( item, target );
				});
				break;
		}
		self.save_fail_queue = _.without( self.save_fail_queue, section );
	};
	
	/**
	* modify_save_queue
	*
	* Add or remove a section from the save queue, using a Toolset.hook.
	*
	* @since 2.1
	*/
	
	self.modify_save_queue = function( data ) {
		var safe_data = $.extend( {}, { section: '', action: '', args: {} }, data );
		if (
			'' != safe_data.section 
			&& '' != safe_data.action
		) {
			self.manage_save_queue( safe_data.section, safe_data.action, safe_data.args );
		}
	};
	
	/**
	* define_save_callbacks
	*
	* Add or remove a callback to be used by the save queue, using a Toolset.hook.
	*
	* @since 2.1
	*/
	
	self.define_save_callbacks = function( data ) {
		self.save_callbacks[ data.handle ] = {
			callback:	data.callback,
			event:		data.event
		};
	};
	
	/**
	* modify_save_fail_queue
	*
	* Add a section from the save fail queue, using a Toolset.hook.
	*
	* @since 2.1
	*/
	
	self.modify_save_fail_queue = function( section ) {
		self.save_fail_queue.push( section );
	};
	
	/**
	* Process save queue, one item at a time.
	*
	* @since 2.1
	*/
		
	$( document ).on( 'js_wpv_save_section_queue', function( event ) {
		if ( _.size( self.save_queue ) > 0 ) {
			
			if ( self.save_all_queue_step > 0 ) {
				self.save_all_progress_bar.addClass( 'wpv-view-save-all-progress-working' );
				if ( self.save_all_queue_completed >= 100 ) {
					self.save_all_queue_completed = 0;
				} else {
					self.save_all_queue_completed += self.save_all_queue_step;
					
				}
				self.save_all_progress_bar.css({
					'width': self.save_all_queue_completed + '%'
				});
			}
			
			var save_section_to_fire = _.first( self.save_queue ),
			save_section_to_fire_data = _.has( self.save_callbacks, save_section_to_fire.section ) ? self.save_callbacks[ save_section_to_fire.section ]: self.save_section_defaults;
			if ( typeof save_section_to_fire_data.callback == "function" ) {
				save_section_to_fire_data.callback( save_section_to_fire_data.event, true, save_section_to_fire.args );
			}
		} else {
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
			$( document ).trigger( 'js_wpv_save_section_queue_completed' );
		}
	});
	
	// ---------------------------------
	// Action Bar
	// ---------------------------------
	
	self.action_bar						= $( '#js-wpv-general-actions-bar' );
	self.action_bar_message_container	= $( '#js-wpv-general-actions-bar .js-wpv-message-container' );
	self.html							= $( 'html' );
	
	if ( self.action_bar && self.action_bar.offset() ) {
		var toolbarPos = self.action_bar.offset().top,
		adminBarHeight = 0,
		adminBarWidth = $( '.wpv-title-section .wpv-setting-container' ).width();
		if ( $('#wpadminbar').length !== 0 ) {
			adminBarHeight = $('#wpadminbar').height();
			self.action_bar.width( adminBarWidth );
		}
		self.set_toolbar_pos = function() {
			if ( toolbarPos <= $(window).scrollTop() + adminBarHeight + 15) {
				self.html.addClass('wpv-general-actions-bar-fixed');
			}
			else {
				self.html.removeClass('wpv-general-actions-bar-fixed');
			}
		};

		$( window ).on( 'scroll', function() {
			self.set_toolbar_pos();
		});
		
		$( window ).on( 'resize', function() {
			var adminBarWidth = $( '.wpv-title-section .wpv-setting-container' ).width();
			self.action_bar.width( adminBarWidth );
		});

		self.set_toolbar_pos();
	}
	
	// ---------------------------------
	// Init hooks
	// ---------------------------------
	
	self.init_hooks = function() {
		
		/**
		* Filters
		*/
		
		// Filter to get current View 
		Toolset.hooks.addFilter( 'wpv-filter-wpv-edit-screen-get-query-mode', self.get_view_query_mode );
		
		// Filter to get current View ID
		Toolset.hooks.addFilter( 'wpv-filter-wpv-edit-screen-get-id', self.get_view_id );
		
		// Filter to get the Types post relationship tree reference, stored in WPViews.wpv_post_relationship_tree_reference
		Toolset.hooks.addFilter( 'wpv-filter-wpv-edit-screen-get-post-relationship-tree-reference', self.get_post_relationship_tree_reference );
		
		/**
		* Actions
		*/
		
		// Action to execute self.trigger_ajax_fail, a wrapper for self.manage_ajax_fail
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', self.trigger_ajax_fail );
		
		// Action to execute self.set_confirm_unload
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', self.set_confirm_unload );
		
		// Action to add or remove from the save queue
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-manage-save-queue', self.modify_save_queue );
		
		// Action to add or remove from the save callbacks
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', self.define_save_callbacks );
		
		// Action to add or remove from the save fail queue
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', self.modify_save_fail_queue );
		
		// Action to refresh a CodeMirror instance
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-refresh-codemirror-instances', self.refresh_codemirror_instances );
		
	};
	
	// ---------------------------------
	// CodeMirror settings
	// ---------------------------------
	
	self.init_codemirror = function() {
		CodeMirror.commands.save = function(cm) {
			// Prevent Firefox trigger Save Dialog
			var keypress_handler = function (cm, event) {
				if (event.which == 115 && (event.ctrlKey || event.metaKey) || (event.which == 19)) {
					event.preventDefault();
					return false;
				}
				return true;
			};
			CodeMirror.off(cm.getWrapperElement(), 'keypress', keypress_handler);
			cm.on('keypress', keypress_handler);
			
			var textarea_id = cm.getTextArea().id;
			if (
					textarea_id === 'wpv_filter_meta_html_content' ||
					textarea_id === 'wpv_filter_meta_html_css' ||
					textarea_id === 'wpv_filter_meta_html_js'
					) {
				/* Filter */
				$('.js-wpv-filter-extra-update').click();
			} else if (
					textarea_id === 'wpv_layout_meta_html_content' ||
					textarea_id === 'wpv_layout_meta_html_css' ||
					textarea_id === 'wpv_layout_meta_html_js'
					) {
				/* Loop Output */
				$('.js-wpv-layout-extra-update').click();
			} else if (
					textarea_id === 'wpv_content'
					) {
				/* Filter and Loop Output Integration */
				$('.js-wpv-content-update').click();
			}
		};
		// Autoresize setting
		if ( 
			wpv_editor_strings.codemirror_autoresize == 'true' 
			|| wpv_editor_strings.codemirror_autoresize == '1' 
		) {
			$( '.CodeMirror' ).css( 'height', 'auto' );
			$( '.CodeMirror-scroll' ).css( {'overflow-y':'hidden', 'overflow-x':'auto', 'min-height':'15em'} );
		}
		// Refresh CodeMirror instances
		Toolset.hooks.doAction( 
			'wpv-action-wpv-edit-screen-refresh-codemirror-instances', 
			[ 
				'wpv_filter_meta_html_content', 'wpv_filter_meta_html_css', 'wpv_filter_meta_html_js', 
				'wpv_layout_meta_html_content', 'wpv_layout_meta_html_css', 'wpv_layout_meta_html_js', 
				'wpv_content'
			] 
		);
	};
	
	self.refresh_codemirror_instances = function( instances ) {
		_.each( instances, function( element, index, list ) {
			WPV_Toolset.CodeMirror_instance[ element ].refresh();
		});
	};
	
	self.refresh_codemirror = function( instance ) {
		if ( instance === 'all' ) {
			WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].refresh();
			WPV_Toolset.CodeMirror_instance['wpv_content'].refresh();
		} else {
			if ( instance == 'filter-css-editor' ) {
				WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].refresh();
			} else if ( instance == 'filter-js-editor' ) {
				WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].refresh();
			} else if ( instance == 'layout-css-editor' ) {
				WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].refresh();
			} else if ( instance == 'layout-js-editor' ) {
				WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].refresh();
			}
		}
	};
	
	// ---------------------------------
	// Save actions: error and success
	// ---------------------------------
	
	self.trigger_ajax_fail = function( args ) {
		self.manage_ajax_fail( args.data, args.container );
	}
	
	self.manage_ajax_fail = function( data, message_container ) {
		if ( data.type ) {
			switch ( data.type ) {
				case 'nonce':
				case 'id':
				case 'capability':
					self.manage_action_bar_error( data );
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', false );
					$( '.wpv-setting-container:not(.js-wpv-general-actions-bar)' ).prepend( self.overlay_container );
					break;
				default:
					if ( data.message ) {
						message_container
							.wpvToolsetMessage({
								text: data.message,
								type: 'error',
								inline: true,
								stay: true
							});
					}
					break;
			}
		} else {
			if ( data.message ) {
				message_container
					.wpvToolsetMessage({
						text: data.message,
						type: 'error',
						inline: true,
						stay: true
					});
			}
		}
	};
	
	self.manage_ajax_success = function( data, message_container ) {
		if ( data.message ) {
			message_container
				.wpvToolsetMessage({
					text: data.message,
					type: 'success',
					inline: true,
					stay: false
				});
		}
	};

    self.manage_action_bar_success = function( data ) {
        if ( data.message ) {
            self.action_bar_message_container
                .wpvToolsetMessage({
                    text: data.message,
                    type: 'success',
                    inline: false,
                    stay: false
                });
        }
    };
	
	self.manage_action_bar_error = function( data ) {
		if ( data.message ) {
            var stay = (typeof(data.stay) != 'undefined') ? data.stay : true;
			self.action_bar_message_container
				.wpvToolsetMessage({
					text: data.message,
					type: 'error',
					inline: false,
					stay: stay
				});
		}
	};
	
	// ---------------------------------
	// Title and description
	// ---------------------------------
	
	// Title placeholder
	
	self.title_placeholder = function() {
		$( '.js-title' ).each( function() {
			var thiz = $( this );
			if ( '' == thiz.val() ) {
				thiz
					.parents( '.js-wpv-titlewrap' )
						.find( '.js-title-reader' )
							.removeClass( 'screen-reader-text' );
			}
			thiz.focus( function() {
				thiz
					.parents( '.js-wpv-titlewrap' )
						.find( '.js-title-reader' )
							.addClass( 'screen-reader-text' );
			});
			thiz.blur( function() {
				if ( '' == thiz.val() ) {
					thiz
						.parents( '.js-wpv-titlewrap' )
							.find( '.js-title-reader' )
								.removeClass( 'screen-reader-text' );
				}
			thiz.focus( function() {
				thiz
					.parents( '.js-wpv-titlewrap' )
						.find( '.js-title-reader' )
							.addClass( 'screen-reader-text' );
			});
			thiz.blur( function() {
				if ( '' == thiz.val() ) {
					thiz
						.parents( '.js-wpv-titlewrap' )
							.find( '.js-title-reader' )
								.removeClass( 'screen-reader-text' );
				}
			});
			});
		});
	};
	
	// Title: track and save
	
	self.title_track_callback = function() {
		$( '#titlediv' ).find( '.toolset-alert' ).remove();
		$( '.js-wpv-title-update' ).parent().find( '.toolset-alert-error' ).remove();
		if ( self.model['.js-wpv-title'] != $( '.js-title' ).val() ) {
			self.manage_save_queue( 'save_section_title', 'add' );
			$( '.js-wpv-title-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_title', 'remove' );
			$( '.js-wpv-title-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	self.title_track = _.debounce( self.title_track_callback, 100 );
	
	$( document ).on( 'keyup input cut paste', '.js-title', function() {
		self.title_track();
	});
	
	self.save_section_title = function( event, propagate ) {
		var thiz = $( '.js-wpv-title-update' ),
		thiz_container = thiz.parents( '.js-wpv-settings-title-and-desc' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_title', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
		
		var titleOriginal = $('.js-title').val(),
		titleEscaped = titleOriginal.replace( /\'/gi, '' ),
		isTitleEscaped = true;
		
		titleEscaped = WPV_Toolset.Utils._strip_tags_and_preserve_text( _.unescape( titleEscaped ) );
		isTitleEscaped = ( titleOriginal != titleEscaped );

		var data = {
			action: 'wpv_update_title',
			id: self.view_id,
			title: titleEscaped,
			is_title_escaped: isTitleEscaped ? 1 : 0,
			wpnonce: nonce
		};

		$.ajax({
			type:		"POST",
			dataType: 	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass( 'js-wpv-section-unsaved' );
					self.model['.js-wpv-title'] = titleEscaped;
					$( '.js-title' ).val(titleEscaped);
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_title' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
					thiz
						.prop( 'disabled', false )
						.removeClass( 'button-secondary' )
						.addClass( 'button-primary' );
				}
			},
			error:		function( ajaxContext ) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_title' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
				thiz
					.prop( 'disabled', false )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_title'] = {
		callback:	self.save_section_title,
		event:		'js_event_wpv_save_section_title_completed'
	};
	
	$( document ).on( 'click', '.js-wpv-title-update', function( e ) {
		e.preventDefault();
		self.save_section_title( 'js_event_wpv_save_section_title_completed', false );
	});
	
	// Description: track and save
	
	$( '.js-wpv-description-toggle' ).on( 'click', function() {
		$( this ).hide();
		$( '.js-wpv-description-container' ).fadeIn( 'fast' );
		$( '#wpv-description' ).focus();
	});
	
	self.description_track_callback = function() {
		$( '.js-wpv-title-update' ).parent().find( '.toolset-alert-error' ).remove();
		if ( self.model['.js-wpv-description'] != $( '.js-wpv-description' ).val() ) {
			self.manage_save_queue( 'save_section_description', 'add' );
			$( '.js-wpv-description-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_description', 'remove' );
			$( '.js-wpv-description-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	self.description_track = _.debounce( self.description_track_callback, 100 );
	
	$( document ).on( 'keyup input cut paste', '.js-wpv-description', function() {
		self.description_track();
	});
	
	self.save_section_description = function( event, propagate ) {
		var thiz = $( '.js-wpv-description-update' ),
		thiz_container = thiz.parents( '.js-wpv-settings-title-and-desc' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		//update_message = thiz.data('success'),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_description', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );

		var data = {
			action: 'wpv_update_description',
			id: self.view_id,
			description: $('.js-wpv-description').val(),
			wpnonce: nonce
		};


		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass( 'js-wpv-section-unsaved' );
					self.model['.js-wpv-description'] = $( '.js-wpv-description' ).val();
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_description' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
					thiz
						.prop( 'disabled', false )
						.removeClass( 'button-secondary' )
						.addClass( 'button-primary' );
				}
			},
			error:		function( ajaxContext ) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_description' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
				thiz
					.prop( 'disabled', false )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_description'] = {
		callback:	self.save_section_description,
		event:		'js_event_wpv_save_section_description_completed'
	};
	
	$( document ).on( 'click', '.js-wpv-description-update', function( e ) {
		e.preventDefault();
		self.save_section_description( 'js_event_wpv_save_section_description_completed', false );
	});
	
	// Slug: edit and update
	
	$( document ).on( 'click', '.js-wpv-edit-slug, #editable-post-name', function( e ) {
		$('.js-wpv-edit-slug-toggle').toggle();
		$('#wpv-slug').val($('#editable-post-name').html());
    });
	
	$( document ).on( 'click', '.js-wpv-edit-slug-cancel', function( e ) {
		$('.js-wpv-edit-slug-toggle').toggle();
		$('.js-wpv-slug-container .js-wpv-message-container-slug' ).html('');
		$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
	});
	
	$( document ).on( 'click', '.js-wpv-edit-slug-update', function( e ) {
		e.preventDefault();
		var thiz = $( this );
		var message_where = $( '.js-wpv-slug-container .js-wpv-message-container-slug' );
		var update_message = thiz.data( 'success' );
		var error_message = thiz.data( 'unsaved' );
		var data = {
			action:		'wpv_view_change_post_name',
			id:			self.view_id,
			post_name:	$('#wpv-slug').val(),
			wpnonce :	thiz.data( 'nonce' )
		};
		$.ajax({
			type:		"POST",
			url:		ajaxurl,
			data:		data,
			dataType:	"json",
			success: function( response ) {
				if (
					typeof( response ) !== 'undefined'
					&& typeof( response.success ) !== 'undefined'
					&& response.success
				) {
					$('.js-wpv-edit-slug-toggle').toggle();
					$('#editable-post-name').html( response.data.slug );
				} else {
					message_where.wpvToolsetMessage({
						text: 'undefined' == typeof( response.data.message)? error_message:response.data.message,
						type:'error',
						inline:true,
						stay:true
					});
				}
			},
			error: function( ajaxContext ) {
				message_where.wpvToolsetMessage({
					text:error_message,
					type:'error',
					inline:true,
					stay:true
				});
			},
			complete: function() {
			}
		});
	});
	
	// Status: change

	$( document ).on( 'click', '.js-wpv-change-view-status', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		newstatus = thiz.data( 'statusto' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
		update_message = thiz.data( 'success' ),
		error_message = thiz.data( 'unsaved' ),
		redirect_url = thiz.data( 'redirect' ),
		message_where = $( '.js-wpv-settings-title-and-desc .js-wpv-message-container' );
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
		var data = {
			action:			'wpv_view_change_status',
			id:				self.view_id,
			newstatus:		newstatus,
			cleararchives:	( newstatus == 'trash' ) ? 1 : 0,
			wpnonce :		thiz.data( 'nonce' )
		};
		$.ajax({
			type:		"POST",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( ( typeof( response ) !== 'undefined' ) && ( response == data.id ) ) {
					if ( newstatus == 'trash' ) {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', false );
						//WPViews.wpa_edit_screen.set_confirm_unload( false );
						$( location ).attr( 'href', redirect_url );
					}
				} else {
					message_where.wpvToolsetMessage({
						text:error_message,
						type:'error',
						inline:true,
						stay:true
					});
				}
			},
			error:		function( ajaxContext ) {
				thiz.prop( 'disabled', false );
				spinnerContainer.remove();
				message_where.wpvToolsetMessage({
					text:error_message,
					type:'error',
					inline:true,
					stay:true
				});
			},
			complete:	function() {
			  
			}
		});
	});
	
	// ---------------------------------
	// Loop selection
	// ---------------------------------
	
	self.init_loop_selection_help = function() {
		if ( $( '.js-loop-selection-form input:checked' ).length > 1 ) {
			$( '.js-wpv-multiple-archive-loops-selected' ).fadeIn( 'fast' );
		} else {
			$( '.js-wpv-multiple-archive-loops-selected' ).hide();
		}
	}
	
	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function() {
		self.init_loop_selection_help();
	});
		
	self.save_wpa_loop_selection_options = function() {
		var dataholder = $( '.js-wpv-loop-selection-update' ),
		messages_container = dataholder.parents( '.js-wpv-update-action-wrap' ).find( '.js-wpv-message-container' ),
		section_container = $( '.js-wpv-settings-archive-loop' ),
		unsaved_message = dataholder.data('unsaved'),
		nonce = dataholder.data('nonce'),
		spinnerContainer;
		section_container
			.addClass( 'wpv-setting-replacing' )
			.find( '.wpv-spinner.ajax-loader' )
				.remove();
		messages_container.find('.toolset-alert-error').remove();
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( dataholder ).show();
		var data = {
			action:		'wpv_update_loop_selection',
			id:			self.view_id,
			form:		$('.js-loop-selection-form').serialize(),
			wpnonce:	nonce
		};
		$('.js-loop-selection-form input').prop( 'disabled', true );
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					$( '.js-loop-selection-form' ).html( response.data.updated_archive_loops );
					$( document ).trigger( 'js_event_wpv_save_section_loop_selection_completed' );
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				}
			},
			error:		function (ajaxContext) {
				messages_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
			},
			complete:	function() {
				spinnerContainer.remove();
				section_container.removeClass( 'wpv-setting-replacing' );
				$('.js-loop-selection-form input').prop( 'disabled', false );
			}
		});
	};
	
	self.loop_selection_debounce_update = _.debounce( self.save_wpa_loop_selection_options, 1000 );
		
	$( document ).on( 'change', '.js-loop-selection-form input', function() {
		self.loop_selection_debounce_update();
	});
	
	$( document ).on( 'click', '.js-wpv-apply-post-types-to-archive-loop-dialog', function( e ) {
		e.preventDefault();
		var thiz = $( this );
		
		self.apply_post_types_to_loop_data = {
			type:			thiz.data( 'type' ),
			name:			thiz.data( 'name' ),
			'default_pt':	thiz.data( 'default' ),
			'selected_pt':	thiz.data( 'selected' )
		};
		
		self.post_types_for_archive_loops_dialog.dialog( "open" ).dialog({
			maxHeight:	self.calculate_dialog_maxHeight(),
			maxWidth:	self.calculate_dialog_maxWidth(),
			position:	{
				my:			"center top+50",
				at:			"center top",
				of:			window,
				collision:	"none"
			}
		});
		$( '.js-wpv-post-types-for-archive-loop' ).prop( 'checked', false );
		$( '.js-wpv-archive-loop-for-post-type-assignment' ).html( thiz.data( 'display' ) );
		$( '.js-wpv-apply-post-types-to-archive-loop' )
			.addClass( 'button-primary' )
			.removeClass( 'button-secondary' )
			.prop( 'disabled', false );
		_.each( thiz.data( 'selected' ), function( element, index, list ) {
			$( '.js-wpv-post-types-for-archive-loop-' + element ).prop( 'checked', true );
		});
	});
	
	self.restore_post_types_to_archive_loop = function() {
		$( '.js-wpv-post-types-for-archive-loop' ).prop( 'checked', false );
		_.each( self.apply_post_types_to_loop_data[ 'default_pt' ], function( element, index, list ) {
			$( '.js-wpv-post-types-for-archive-loop-' + element ).prop( 'checked', true );
		});
	}
	
	self.apply_post_types_to_archive_loop = function() {
		var post_types = $( '.js-wpv-post-types-for-archive-loop:checked' ).map( function() {
			return $( this ).val();
		}).get();
		if ( post_types.length == 0 ) {
			$( '.js-wpv-dialog-assign-post-type-to-archive-loop-dialog-content .js-wpv-message-container' ).wpvToolsetMessage({
				text:	"Select at least one post type",
				type:	'error',
				inline:	true,
				stay:	false
			});
		} else {
			var thiz = $( '.js-wpv-apply-post-types-to-archive-loop' ),
			spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
			data = {
				action:		'wpv_update_post_types_for_archive_loop',
				id:			self.view_id,
				post_types:	post_types,
				type:		self.apply_post_types_to_loop_data.type,
				name:		self.apply_post_types_to_loop_data.name,
				wpnonce:	wpv_editor_strings.editor_nonce
			};
			
			thiz
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
			
			$.ajax({
				type:		"POST",
				dataType:	"json",
				url:		ajaxurl,
				data:		data,
				success:	function( response ) {
					if ( response.success ) {
						$( '.js-loop-selection-form' ).html( response.data.updated_archive_loops );
						self.post_types_for_archive_loops_dialog.dialog( "close" );
					} else {
						$( '.js-wpv-dialog-assign-post-type-to-archive-loop-dialog-content .js-wpv-message-container' ).wpvToolsetMessage({
							text:	"Error",
							type:	'error',
							inline:	true,
							stay:	false
						});
					}
				},
				error:		function (ajaxContext) {
					$( '.js-wpv-dialog-assign-post-type-to-archive-loop-dialog-content .js-wpv-message-container' ).wpvToolsetMessage({
						text:	"Error",
						type:	'error',
						inline:	true,
						stay:	false
					});
				},
				complete:	function() {
					spinnerContainer.remove();
				}
			});
		}
	};
		
	// ---------------------------------
	// Sorting
	// ---------------------------------
	
	/**
	* save_view_sorting_options
	*
	* Save the sorting settings.
	*
	* @since 2.1
	*/
		
	self.save_view_sorting_options = function() {
		var dataholder = $( '.js-wpv-ordering-update' ),
		messages_container = dataholder.parents( '.js-wpv-update-action-wrap' ).find( '.js-wpv-message-container' ),
		section_container = $( '.js-wpv-settings-ordering' ),
		unsaved_message = dataholder.data( 'unsaved' ),
		nonce = dataholder.data( 'nonce' ),
		spinnerContainer,
		view_id = self.view_id;
		section_container.find( '.wpv-spinner.ajax-loader' ).remove();
		messages_container.find('.toolset-alert-error').remove();
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( dataholder ).show();
		var data = {
			action:		'wpv_update_sorting',
			id:			view_id,
			orderby:	$( 'select.js-wpv-posts-orderby' ).val(),
			order:		$( 'select.js-wpv-posts-order' ).val(),
			orderby_as:	$( 'select.js-wpv-posts-orderby-as' ).val(),
			wpnonce:	nonce
		};
		$.ajax({
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					$('.js-screen-options').find('.toolset-alert').remove();
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				}
			},
			error: function (ajaxContext) {
				messages_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.sorting_debounce_update = _.debounce( self.save_view_sorting_options, 2000 );
	
	// Sorting - rand and pagination do not work well together
	/*
	self.sorting_random_and_pagination = function() {
		$('.js-wpv-settings-ordering .js-wpv-toolset-messages .toolset-alert, .js-wpv-settings-pagination .js-wpv-toolset-messages .toolset-alert').remove();
		if ( $( 'select.js-wpv-posts-orderby' ).val() == 'rand' && $( '.js-wpv-pagination-mode:checked' ).val() != 'none' ) {
			$('.js-wpv-settings-ordering .js-wpv-toolset-messages, .js-wpv-settings-pagination .js-wpv-toolset-messages' )
				.wpvToolsetMessage({
					text: $( 'select.js-wpv-posts-orderby' ).data( 'rand' ),
					stay: true,
					close: false,
					type: ''
				});
		}
	};
	*/

    /**
	* Sorting - Control the availability of the "order" select box.
	*
	* Disable "order" select box if random order has been selected. Enable it otherwise.
	*
	* @since 2.1
	*/
	
    self.sorting_update_order_availability = function() {
        if ( $( 'select.js-wpv-posts-orderby' ).val() == 'rand' ) {
            $( 'select.js-wpv-posts-order' ).attr( 'disabled', true );
        } else {
            $( 'select.js-wpv-posts-order' ).attr( 'disabled', false );
        }
		return self;
    };
	
	/**
	* sorting_manage_orderby_as - Control the visibility of orderby_as, based on selected field.
	*
	* Hides the orderby_as selection, if it's not ordering by a field.
	* Shows the orderby_as selection, if it's ordering by a field:
	* 	Sets the orderby_as to 'NUMERIC' if the field is a Types numeric or a date field.
	* 	Sets the orderby_as to 'STRING' if the field is another Types field type.
	* 	Sets the orderby_as to '' if the field is not a Types one.
	*
	* @todo AVoid depending on a shared and duplicated (!) ID attribute
	*
	* @since 2.1
	*/
	
	self.sorting_manage_orderby_as = function( parent_selector ) {
		var selected = $( 'select.js-wpv-posts-orderby' ).val();
		if ( 
			( 
				'' !== selected 
				|| typeof selected !== undefined 
			) && 'field-' == selected.substr( 0, 6 ) 
		) {
			var field_type = $( 'select.js-wpv-posts-orderby' ).find( ":selected" ).data( "field-type" );
			if ( field_type !== undefined ) {
				switch ( field_type ) {
					case 'date':
					case 'numeric':
						$( 'select.js-wpv-posts-orderby-as' )
							.val( 'NUMERIC' )
							.prop( 'disabled', true );
						$( '.js-wpv-settings-posts-orderby-as' ).show();
						break;
					default:
						$( 'select.js-wpv-posts-orderby-as' )
							.val( 'STRING' )
							.prop( 'disabled', false );
						$( '.js-wpv-settings-posts-orderby-as' ).show();
						break;
				}
			} else {
				$( 'select.js-wpv-posts-orderby-as' )
					.val( '' )
					.prop( 'disabled', false );
				$( '.js-wpv-settings-posts-orderby-as' ).show();
			}
		} else {
			// @todo: reconsider this.
			// Reset to default before hiding.
			// So we don't need to keep track of the value when sending AJAX request.
			$( 'select.js-wpv-posts-orderby-as' )
				.val( '' )
				.prop( 'disabled', false );
			$( '.js-wpv-settings-posts-orderby-as' ).hide();
		}

		return self;
	}
	
	$( document ).on( 'change', 'select.js-wpv-posts-orderby', function() {
		//self.sorting_random_and_pagination();
        self
			.sorting_update_order_availability()
			.sorting_manage_orderby_as()
			.sorting_debounce_update();
	});
	
	$( document ).on( 'change', 'select.js-wpv-posts-order, select.js-wpv-posts-orderby-as', function() {
		//self.sorting_random_and_pagination();
        self.sorting_debounce_update();
	});
	
	// ---------------------------------
	// Pagination
	// ---------------------------------
	
	/**
	* save_view_pagination_options
	*
	* Save the pagination settings.
	*
	* @since 2.1
	*/
	
	self.save_view_pagination_options = function() {
		var dataholder		= $( '.js-wpv-pagination-update' ),
		section_container	= $( '.js-wpv-settings-pagination' ),
		messages_container	= section_container.find( '.js-wpv-message-container' ),
		unsaved_message		= dataholder.data( 'unsaved' ),
		nonce				= dataholder.data( 'nonce' ),
		spinnerContainer,
		settings			= {
			type:				$( '.js-wpv-archive-pagination-type:checked' ).val(),
			'posts_per_page':	$( '.js-wpv-archive-pagination-posts-per-page' ).val(),
			effect:				$( '.js-wpv-archive-ajax-pagination-effect' ).val(),
			duration:			$( '.js-wpv-archive-ajax-pagination-duration' ).val(),
			'manage_history':	( $( '.js-wpv-archive-ajax-pagination-manage-history' ).length > 0 ) ? $( '.js-wpv-archive-ajax-pagination-manage-history' ).prop( 'checked' ) : false,
			tolerance:			$( '.js-wpv-archive-ajax-pagination-tolerance' ).val(),
			'preload_images':	$( '.js-wpv-archive-ajax-pagination-preload-images' ).prop( 'checked' ),
			'cache_pages':		$( '.js-wpv-archive-ajax-pagination-cache-pages' ).prop( 'checked' ),
			'preload_pages':	$( '.js-wpv-archive-ajax-pagination-preload-pages' ).prop( 'checked' ),
			'pre_reach':		$( '.js-wpv-archive-ajax-pagination-preload-reach' ).val(),
			spinner:			$( '.js-wpv-archive-pagination-spinner:checked' ).val(),
			'spinner_image':	$( '.js-wpv-archive-pagination-builtin-spinner-image:checked' ).val(),
			'spinner_image_uploaded':	$( '.js-wpv-archive-pagination-uploaded-spinner-image' ).val(),
		};
		
		section_container.find( '.wpv-spinner.ajax-loader' ).remove();
		messages_container.find('.toolset-alert-error').remove();
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( dataholder ).show();
		
		var data = {
			action:		'wpv_update_archive_pagination',
			id:			self.view_id,
			settings:	settings,
			wpnonce:	nonce
		};
		$.ajax({
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					$('.js-screen-options').find('.toolset-alert').remove();
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				}
			},
			error: function (ajaxContext) {
				messages_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.pagination_debounce_update = _.debounce( self.save_view_pagination_options, 1000 );
	
	self.manage_editor_pagination_controls_button = function() {
		var type	= $( '.js-wpv-archive-pagination-type:checked' ).val(),
		effect		= $( '.js-wpv-archive-ajax-pagination-effect' ).val(),
		button		= $( '.js-wpv-archive-editor-pagination-button-wrapper' );
		switch ( type ) {
			case 'disabled':
				button.hide();
				break;
			case 'paged':
				button.fadeIn( 'fast' );
				break;
			case 'ajaxed':
				if ( effect == 'infinite' ) {
					button.hide();
				} else {
					button.fadeIn( 'fast' );
				}
				break;
		}
		return self;
	};
	
	$( document ).on( 'change', '.js-wpv-archive-pagination-type', function() {
		var type = $( '.js-wpv-archive-pagination-type:checked' ).val();
		switch ( type ) {
			case 'disabled':
				$( '.js-wpv-archive-pagination-advanced-settings' ).hide();
				break;
			case 'paged':
				$( '.js-wpv-archive-ajax-pagination-settings-extra' ).hide();
				$( '.js-wpv-archive-pagination-advanced-settings' ).fadeIn( 'fast' );
				break;
			case 'ajaxed':
				$( '.js-wpv-archive-ajax-pagination-settings-extra' ).fadeIn( 'fast' );
				$( '.js-wpv-archive-pagination-advanced-settings' ).fadeIn( 'fast' );
				break;
		}
		self
			.manage_editor_pagination_controls_button()
			.pagination_debounce_update();
	});
	
	$( document ).on( 'change', '.js-wpv-archive-pagination-posts-per-page', function() {
		if ( $( this ).val() == 'default' ) {
			$( '.js-wpv-archive-pagination-posts-per-page-default' ).fadeIn( 'fast' );
		} else {
			$( '.js-wpv-archive-pagination-posts-per-page-default' ).hide();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-archive-ajax-pagination-effect', function() {
		if ( $( this ).val() == 'infinite' ) {
			$( '.js-wpv-archive-pagination-advanced-infinite-tolerance' ).fadeIn( 'fast' );
			$( '.js-wpv-archive-pagination-advanced-history-management' ).hide();
		} else {
			$( '.js-wpv-archive-pagination-advanced-infinite-tolerance' ).hide();
			$( '.js-wpv-archive-pagination-advanced-history-management' ).fadeIn( 'fast' );
		}
		self.manage_editor_pagination_controls_button();
	});
	
	$( document ).on( 'click', '.js-wpv-archive-pagination-advanced', function( e ) {
		e.preventDefault();
		$( this )
			.find( 'i' )
				.toggleClass( 'fa-caret-down fa-caret-up' );
		$( '.js-wpv-archive-pagination-advanced-container' ).fadeToggle( 'fast' );
	});
	
	$( document ).on( 'change', '.js-wpv-archive-pagination-spinner', function() {
		var thiz_value = $( '.js-wpv-archive-pagination-spinner:checked' ).val();
		switch ( thiz_value ) {
			case 'builtin':
				$( '.js-wpv-archive-pagination-spinner-builtin' ).fadeIn( 'fast' );
				$( '.js-wpv-archive-pagination-spinner-uploaded' ).hide();
				break;
			case 'uploaded':
				$( '.js-wpv-archive-pagination-spinner-builtin' ).hide();
				$( '.js-wpv-archive-pagination-spinner-uploaded' ).fadeIn( 'fast' );
				break;
			default:
				$( '.js-wpv-archive-pagination-spinner-builtin, .js-wpv-archive-pagination-spinner-uploaded' ).hide();
				break;
		}
	});
	
	$( document ).on( 'change', '.js-wpv-archive-pagination-advanced-settings input, .js-wpv-archive-pagination-advanced-settings select', function() {
		self.pagination_debounce_update();
	});
	
	// ---------------------------------
	// Pagination controls
	// ---------------------------------
	
	/**
	* This happens when user clicks on the "Pagination controls" button in the Layout HTML/CSS/JS section.
	*
	* A dialog for selecting controls to insert ("js-wpv-archive-pagination-dialog") is displayed. The process then
	* continues with clicking on a button with class "js-wpv-insert-archive-pagination".
	*
	* @since 1.7
	*/
	
	$( document ).on( 'click', '.js-wpv-archive-pagination-popup', function( e ) {
		e.preventDefault();
		self.archive_pagination_dialog.dialog( 'open' ).dialog({
			maxHeight:	self.calculate_dialog_maxHeight(),
			maxWidth:	self.calculate_dialog_maxWidth(),
			position:	{
				my:			"center top+50",
				at:			"center top",
				of:			window,
				collision:	"none"
			}
		});
	});
	
	self.init_pagination_dialog_options = function() {
		$( '.js-wpv-archive-pagination-control' ).prop( 'checked', false );
		$( '.js-wpv-dialog-pagination-wizard-item-extra' ).hide();
		$( '.js-wpv-dialog-pagination-wizard-preview' ).addClass( 'disabled' );
		$( '.js-wpv-insert-archive-pagination' )
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
	};
	
	self.get_pagination_shortcode = function() {
		var output = '';
		$.each( $( 'input.js-wpv-archive-pagination-control:checked' ), function() {
			var thiz = $( this ),
			value = thiz.val(),
			container = thiz.closest( '.js-wpv-archive-pagination-shortcode' );
			switch ( value ) {
				case 'wpv-pager-archive-prev-page':
					output += '[' + value;
					container
						.find( '.js-wpv-archive-pagination-shortcode-attribute' )
							.each( function() {
								var thiz_attr = $( this );
								switch ( thiz_attr.attr( 'type' ) ) {
									case 'checkbox':
										if ( thiz_attr.prop( 'checked' ) ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
									case 'text':
										if ( thiz_attr.val() != '' ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
								}
							});
					output += '][wpml-string context="wpv-views"]Previous[/wpml-string][/wpv-pager-archive-prev-page]';
					break;
				case 'wpv-pager-archive-next-page':
					output += '[' + value;
					container
						.find( '.js-wpv-archive-pagination-shortcode-attribute' )
							.each( function() {
								var thiz_attr = $( this );
								switch ( thiz_attr.attr( 'type' ) ) {
									case 'checkbox':
										if ( thiz_attr.prop( 'checked' ) ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
									case 'text':
										if ( thiz_attr.val() != '' ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
								}
							});
					output += '][wpml-string context="wpv-views"]Next[/wpml-string][/wpv-pager-archive-next-page]';
					break;
				case 'wpv-pager-archive-nav-links':
					output += '[' + value;
					container
						.find( '.js-wpv-archive-pagination-shortcode-attribute' )
							.each( function() {
								var thiz_attr = $( this );
								switch ( thiz_attr.attr( 'type' ) ) {
									case 'checkbox':
										if ( thiz_attr.prop( 'checked' ) ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
									case 'text':
										if ( thiz_attr.val() != '' ) {
											output += ' ' + thiz_attr.data( 'attribute' ) + '="' + thiz_attr.val() + '"';
										}
										break;
								}
							});
					output += ']';
					break;
				case 'wpv-pager-archive-current-page':
				case 'wpv-pager-archive-total-pages':
					output += '[' + value + ']';
					break;
			}
		});
		return output;
	};


	/**
	* Insert archive pagination controls on cursor position into layout editor.
	*
	* This happens when user clicks on the submit button in "js-wpv-archive-pagination-dialog" dialog.
	*
	* @since 1.7
	*/
	
	$( document ).on( 'click', '.js-wpv-insert-archive-pagination', function( e ) {
		
		var pagination_shortcodes = self.get_pagination_shortcode();
		
		// Insert pagination shortcodes at cursor position in the Layout editor
		var current_cursor = WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getCursor( true );
		WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].replaceRange( pagination_shortcodes, current_cursor, current_cursor );
		self.archive_pagination_dialog.dialog( 'close' );
		WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].refresh();
		WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].focus();
	});


	/**
	* Enable or disable the submit button in "js-wpv-archive-pagination-dialog" dialog depending on the input validity.
	* Manage extra attributes for a given shortcode.
	*
	* @since 1.7
	*/
	
	$( document ).on( 'change', '.js-wpv-archive-pagination-control', function( e ) {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpv-archive-pagination-shortcode' ),
		selected_options = $( '.js-wpv-archive-pagination-control:checked' ),
		submit_button = $('.js-wpv-insert-archive-pagination');
		
		if ( thiz.prop( 'checked' ) ) {
			thiz_container
				.find( '.js-wpv-archive-pagination-shortcode-attribute-container' )
					.slideDown();
			thiz_container
				.find( '.js-wpv-dialog-pagination-wizard-preview' )
					.removeClass( 'disabled' );
		} else {
			thiz_container
				.find( '.js-wpv-archive-pagination-shortcode-attribute-container' )
					.slideUp();
			thiz_container
				.find( '.js-wpv-dialog-pagination-wizard-preview' )
					.addClass( 'disabled' );
		}
		
		if ( selected_options.length > 0 ) {
			submit_button
				.prop( 'disabled', false )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' );
		} else {
			submit_button
				.prop( 'disabled', true )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' );
		}
		
		if ( selected_options.length > 0 ) {
			submit_button
				.prop( 'disabled', false )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' );
		} else {
			submit_button
				.prop( 'disabled', true )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' );
		}
		
	});
	
	// ---------------------------------
	// Parametric search
	// ---------------------------------
	
	// Parametric search - update automatically
	
	self.save_view_parametric_search_options = function() {
		var dataholder = $( '.js-wpv-filter-dps-update' ),
		messages_container = dataholder.parents( '.js-wpv-update-action-wrap' ).find( '.js-wpv-message-container' ),
		section_container = $( '.js-wpv-settings-filter-extra-parametric' ),
		nonce = dataholder.data('nonce'),
		spinnerContainer,
		unsaved_message = dataholder.data('unsaved'),
		dps_data = $('.js-wpv-dps-settings input, .js-wpv-dps-settings select').serialize();
		section_container.find( '.wpv-spinner.ajax-loader' ).remove();
		messages_container.find('.toolset-alert-error').remove();
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( dataholder ).show();
		var params = {
			action: 'wpv_filter_update_dps_settings',
			id: self.view_id,
			dpsdata: dps_data,
			wpnonce: nonce
		}
		$.ajax({
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: params,
			success: function( response ) {
				if ( response.success ) {
					
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				}
			},
			error:function(ajaxContext){
				messages_container
					.wpvToolsetMessage({
						 text:unsaved_message,
						 type:'error',
						 inline:true,
						 stay:true
					});
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete:function(){
				spinnerContainer.remove();
			}
		});
	};
	
	self.parametric_search_debounce_update = _.debounce( self.save_view_parametric_search_options, 1000 );
	
	// Parametric search - events
	
	$( document ).on( 'change keypress keyup input cut paste', '.js-wpv-dps-settings input', function() {
		self.parametric_search_debounce_update();
	});
	
	// ---------------------------------
	// Filter editor
	// ---------------------------------
	
	self.codemirror_filter_editors_track = function() {
		$( '.js-wpv-filter-extra-update' ).parent().find( '.toolset-alert-error' ).remove();
		if (
			WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_content'] != WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].getValue()
			|| WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_css'] != WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css']	.getValue()
			|| WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_js'] != WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].getValue()
		) {
			self.manage_save_queue( 'save_section_filter', 'add' );
			$( '.js-wpv-filter-extra-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_filter', 'remove' );
			$( '.js-wpv-filter-extra-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].on('change', function(){
		self.codemirror_filter_editors_track();
	});
	
	WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].on('change', function(){
		self.codemirror_filter_editors_track();
	});
	
	WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].on('change', function(){
		self.codemirror_filter_editors_track();
	});
	
	
	self.save_section_filter = function( event, propagate ) {
		var thiz		= $( '.js-wpv-filter-extra-update' ),
		query_val		= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_content'].getValue(),
		query_css_val	= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].getValue(),
		query_js_val	= WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].getValue(),
		
		thiz_container = thiz.parents( '.js-wpv-settings-filter-extra' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		//update_message = thiz.data('success'),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_filter', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
			
		var data = {
			action:			'wpv_update_filter_extra',
			id:				self.view_id,
			query_val:		query_val,
			query_css_val:	query_css_val,
			query_js_val:	query_js_val,
			wpnonce:		nonce
		};
		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass('js-wpv-section-unsaved');
					WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_content']	= query_val;
					WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_css']		= query_css_val;
					WPV_Toolset.CodeMirror_instance_value['wpv_filter_meta_html_js']		= query_js_val;
					
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
					
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_filter' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			},
			error:		function (ajaxContext) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_filter' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_filter'] = {
		callback:	self.save_section_filter,
		event:		'js_event_wpv_save_section_filter_completed'
	};

	$( '.js-wpv-filter-extra-update' ).on( 'click', function( e ) {
		e.preventDefault();
		self.save_section_filter( 'js_event_wpv_save_section_filter_completed', false );
	});
	
	// ---------------------------------
	// Frontend Events
	// ---------------------------------
	
	$( document ).on( 'change', '.js-wpv-frontend-event-gui', function() {
		self.manage_frontend_events_dialog_button();
	});
	
	self.manage_frontend_events_dialog_button = function() {
		if ( $( 'input.js-wpv-frontend-event-gui:checked', '#js-wpv-dialog-views-frontend-events' ).length > 0 ) {
			$( '.js-wpv-frontend-events-insert' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			$( '.js-wpv-frontend-events-insert' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		}
	};
	
	self.insert_frontend_event_handler = function() {
		var thiz_insert_array = [],
		thiz_insert = '';;
		$( '#js-wpv-dialog-views-frontend-events .js-wpv-frontend-event-gui:checked' ).each( function() {
			var thiz_event = $( this ).data( 'event' );
			thiz_insert = "jQuery( document ).on( '" + thiz_event + "', function( event, data ) {";
			if ( thiz_event in self.frontend_events_comments ) {
				thiz_insert += self.frontend_events_comments[thiz_event];
			}
			thiz_insert += "\n\t\n";
			thiz_insert += "});";
			thiz_insert_array.push( thiz_insert );
			$( this ).prop( 'checked', false );
		});
		self.frontend_events_dialog.dialog( "close" );
		window.icl_editor.insert( thiz_insert_array.join( "\n") );
	};
	
	$( document ).on( 'click', '.js-wpv-views-frontend-events-popup', function() {
		window.wpcfActiveEditor = $( this ).data( 'content' );
		var dialog_height = $(window).height() - 100;
		self.frontend_events_dialog.dialog('open').dialog({
            maxHeight:	self.calculate_dialog_maxHeight(),
			maxWidth:	self.calculate_dialog_maxWidth(),
			position:	{ 
				my:			"center top+50", 
				at:			"center top", 
				of:			window, 
				collision:	"none"
			}
        });
	});
	
	// ---------------------------------
	// Loop Output
	// ---------------------------------	
	
	self.codemirror_layout_editors_track = function() {
		$( '.js-wpv-layout-extra-update' ).parent().find( '.toolset-alert-error' ).remove();
		if (
			WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_content'] != WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getValue()
			|| WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_css'] != WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css']	.getValue()
			|| WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_js'] != WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].getValue()
		) {
			self.manage_save_queue( 'save_section_loop_output', 'add' );
			$( '.js-wpv-layout-extra-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_loop_output', 'remove' );
			$( '.js-wpv-layout-extra-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].on( 'change', function() {
		self.codemirror_layout_editors_track();
	});
	
	WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].on( 'change', function() {
		self.codemirror_layout_editors_track();
	});
	
	WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].on( 'change', function() {
		self.codemirror_layout_editors_track();
	});
	
	self.save_section_loop_output = function( event, propagate ) {
		var thiz		= $( '.js-wpv-layout-extra-update' ),
		layout_val		= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getValue(),
		layout_css_val	= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].getValue(),
		layout_js_val	= WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].getValue(),
		
		thiz_container = thiz.closest( '.js-wpv-settings-layout-extra' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		//update_message = thiz.data('success'),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_loop_output', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
			
		var data = {
			action:			'wpv_update_layout_extra',
			id:				self.view_id,
			layout_val:		layout_val,
			layout_css_val:	layout_css_val,
			layout_js_val:	layout_js_val,
			wpnonce:		nonce
		};

		// Include the wizard settings
		if ( WPViews.layout_wizard.settings_from_wizard ) {
			data.include_wizard_data = 'true';
			for ( var attr_name in WPViews.layout_wizard.settings_from_wizard ) {
				data[ attr_name ] = WPViews.layout_wizard.settings_from_wizard[ attr_name ];
			}
			if ( ! WPViews.layout_wizard.use_loop_template ) {
				if ( WPViews.layout_wizard.use_loop_template_id != '' ) {
					data['delete_view_loop_template'] =  WPViews.layout_wizard.use_loop_template_id;
					WPViews.view_edit_screen_inline_content_templates.remove_inline_content_template( WPViews.layout_wizard.use_loop_template_id, $( '.js-wpv-ct-listing-' + WPViews.layout_wizard.use_loop_template_id ) );
				}
				WPViews.layout_wizard.use_loop_template_id = '';
				WPViews.layout_wizard.use_loop_template_title = '';
			}
		}
		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass('js-wpv-section-unsaved');
					WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_content']	= layout_val;
					WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_css']		= layout_css_val;
					WPV_Toolset.CodeMirror_instance_value['wpv_layout_meta_html_js']		= layout_js_val;
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_loop_output' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			},
			error:		function (ajaxContext) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_loop_output' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_loop_output'] = {
		callback:	self.save_section_loop_output,
		event:		'js_event_wpv_save_section_loop_output_completed'
	};
	
	$( document ).on( 'click', '.js-wpv-layout-extra-update', function( e ) {
		e.preventDefault();
		self.save_section_loop_output( 'js_event_wpv_save_section_loop_output_completed', false );
	});
	
	// ---------------------------------
	// Content
	// ---------------------------------
	
	self.codemirror_content_track = function() {
		$('.js-wpv-content-update').parent().find('.toolset-alert-error').remove();
		if ( WPV_Toolset.CodeMirror_instance_value['wpv_content'] != WPV_Toolset.CodeMirror_instance['wpv_content'].getValue() ) {
			self.manage_save_queue( 'save_section_content', 'add' );
			$( '.js-wpv-content-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_content', 'remove' );
			$( '.js-wpv-content-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	WPV_Toolset.CodeMirror_instance['wpv_content'].on( 'change', function() {
		self.codemirror_content_track();
	});
	
	self.save_section_content = function( event, propagate ) {
		var thiz = $( '.js-wpv-content-update' ),
		content_val = WPV_Toolset.CodeMirror_instance['wpv_content'].getValue(),
		
		thiz_container = thiz.parents( '.js-wpv-settings-content' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_content', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
		
		var data = {
			action:		'wpv_update_content',
			id:			self.view_id,
			content:	content_val,
			wpnonce:	nonce
		};
		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass('js-wpv-section-unsaved');
					WPV_Toolset.CodeMirror_instance_value['wpv_content'] = content_val;
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_content' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			},
			error:		function( ajaxContext ) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_content' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_content'] = {
		callback:	self.save_section_content,
		event:		'js_event_wpv_save_section_content_completed'
	};
	
	$( document ).on( 'click', '.js-wpv-content-update', function( e ) {
		e.preventDefault();
		self.save_section_content( 'js_event_wpv_save_section_content_completed', false );
	});
	
	// Warning when the Content does not have a [wpv-filter-meta-html] shortcode
	
	self.fix_content_missing_filter_editor = function() {
		if ( WPV_Toolset.CodeMirror_instance_value['wpv_content'].search( 'wpv-filter-meta-html' ) == -1 ) {
			if ( ! $( '.js-wpv-screen-options-wrapper .js-wpv-show-hide-content' ).prop( 'checked' ) ) {
				$( '.js-wpv-screen-options-wrapper .js-wpv-show-hide-content' )
					.prop( 'checked', true )
					.trigger( 'change' );
			}
			var wpv_alert_content_missing_filter_editor = $( '.js-wpv-metasection-message-container.js-wpv-metasection-message-container-filter' ).wpvToolsetMessage({
				text:	wpv_editor_strings.toolset_alert.content_missing_filter_editor,
				type:	'error',
				inline:	false,
				stay:	true
			}),
			wpv_alert_content_missing_filter_editor_for_pagination = $( '.js-wpv-metasection-message-container.js-wpv-metasection-message-container-layout' ).wpvToolsetMessage({
				text:	wpv_editor_strings.toolset_alert.content_missing_filter_editor_for_pagination,
				type:	'error',
				inline:	false,
				stay:	true
			});
			$( document ).on( 'js_event_wpv_save_section_content_completed', function( event ) {
				if ( WPV_Toolset.CodeMirror_instance_value['wpv_content'].search( 'wpv-filter-meta-html' ) != -1 ) {
					wpv_alert_content_missing_filter_editor.remove();
					wpv_alert_content_missing_filter_editor_for_pagination.remove();
				}
			});
		}
		return self;
	};
	
	// ---------------------------------
	// Legacy - Layouts extra JavaScript files - track and update
	//
	// This is only available to users who already used it
	// ---------------------------------
	
	self.layout_extra_js_track = function() {
		$( '.js-wpv-layout-settings-extra-js-update' ).parent().find('.toolset-alert-error').remove();
		if ( self.model['.js-wpv-layout-settings-extra-js'] != $('.js-wpv-layout-settings-extra-js').val() ) {
			self.manage_save_queue( 'save_section_layout_extra_js', 'add' );
			$( '.js-wpv-layout-settings-extra-js-update' )
				.prop('disabled', false)
				.removeClass('button-secondary')
				.addClass('button-primary js-wpv-section-unsaved');
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			self.manage_save_queue( 'save_section_layout_extra_js', 'remove' );
			$( '.js-wpv-layout-settings-extra-js-update' )
				.prop('disabled', true)
				.removeClass('button-primary js-wpv-section-unsaved')
				.addClass('button-secondary');
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};
	
	$( document ).on( 'keyup input cut paste', '.js-wpv-layout-settings-extra-js', function() {
		self.layout_extra_js_track();
	});
	
	self.save_section_layout_extra_js = function( event, propagate ) {
		var thiz = $( '.js-wpv-layout-settings-extra-js-update' ),
		extra_js = $('.js-wpv-layout-settings-extra-js').val(),
		
		thiz_container = thiz.parents( '.js-wpv-settings-layout-settings-extra-js' ),
		thiz_message_container = thiz_container.find( '.js-wpv-message-container' ),
		//update_message = thiz.data('success'),
		unsaved_message = thiz.data('unsaved'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		
		self.manage_save_queue( 'save_section_layout_extra_js', 'remove' );
		
		thiz_container.find('.toolset-alert-error').remove();
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
		
		var data = {
			action:		'wpv_update_layout_extra_js',
			id:			self.view_id,
			value:		extra_js,
			wpnonce:	nonce
		};


		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz.removeClass('js-wpv-section-unsaved');
					self.model['.js-wpv-layout-settings-extra-js'] = $('.js-wpv-layout-settings-extra-js').val();
					self.manage_ajax_success( response.data, thiz_message_container );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: thiz_message_container} );
					self.save_fail_queue.push( 'save_section_layout_extra_js' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			},
			error:		function ( ajaxContext ) {
				thiz_message_container
					.wpvToolsetMessage({
						text:unsaved_message,
						type:'error',
						inline:true,
						stay:true
					});
				self.save_fail_queue.push( 'save_section_layout_extra_js' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	};
	
	self.save_callbacks['save_section_layout_extra_js'] = {
		callback:	self.save_section_layout_extra_js,
		event:		'js_event_wpv_save_section_layout_extra_js_completed'
	};
	
	$( document ).on( 'click', '.js-wpv-layout-settings-extra-js-update', function( e ) {
		e.preventDefault();
		self.save_section_layout_extra_js( 'js_event_wpv_save_section_layout_extra_js_completed', false );
	});
	
	// ---------------------------------
	// Quicktags
	// ---------------------------------
	
	// Add quicktags to the default editors
	
	self.add_quicktags = function() {
		WPV_Toolset.add_qt_editor_buttons( WPV_Toolset.CodeMirror_instance_qt['wpv_layout_meta_html_content'], WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'] );
		WPV_Toolset.add_qt_editor_buttons( WPV_Toolset.CodeMirror_instance_qt['wpv_content'], WPV_Toolset.CodeMirror_instance['wpv_content'] );
	};
	
	// ---------------------------------
	// Toggle boxes
	// ---------------------------------
	
	self.show_hide_toggle = function( thiz ) {
		$( '.' + thiz.data( 'target' ) ).slideToggle( 400, function() {
			thiz
				.find( '.js-wpv-toggle-toggler-icon i' )
					.toggleClass( 'icon-caret-down icon-caret-up fa-caret-down fa-caret-up' );
			$( document ).trigger( 'js_event_wpv_editor_metadata_toggle_toggled', [ thiz ] );
		});
	};
	
	$( document ).on( 'js_event_wpv_editor_metadata_toggle_toggled', function( event, toggler ) {
		var thiz_instance = toggler.data( 'instance' ),
		thiz_flag = toggler.find( '.js-wpv-textarea-full' ),
		this_toggler_icon = toggler.find( '.js-wpv-toggle-toggler-icon i' );
		thiz_flag.hide();
		if ( toggler.hasClass( 'js-wpv-assets-editor-toggle' ) ) {
			if ( ! toggler.hasClass( 'js-wpv-assets-editor-toggle-refreshed' ) ) {
				self.refresh_codemirror( thiz_instance );
				toggler.addClass( 'js-wpv-assets-editor-toggle-refreshed' );
			}
			if ( 
				self.asset_needs_flag( thiz_instance ) 
				&& (
					this_toggler_icon.hasClass( 'icon-caret-down' ) 
					|| this_toggler_icon.hasClass( 'fa-caret-down' ) 
				)
			) {
				thiz_flag.animate( {width: 'toggle'}, 200 );
			}
		}
	});
	
	this.asset_needs_flag = function( instance ) {
		if ( instance == 'filter-css-editor' ) {
			return ( WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_css'].getValue() != '' );
		} else if ( instance == 'filter-js-editor' ) {
			return ( WPV_Toolset.CodeMirror_instance['wpv_filter_meta_html_js'].getValue() != '' );
		} else if ( instance == 'layout-css-editor' ) {
			return ( WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_css'].getValue() != '' );
		} else if ( instance == 'layout-js-editor' ) {
			return ( WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_js'].getValue() != '' );
		}
	};
	
	$( document ).on( 'click', '.js-wpv-editor-instructions-toggle, .js-wpv-editor-metadata-toggle', function() {
		var thiz = $( this );
		self.show_hide_toggle( thiz );
	});
	
	// ---------------------------------
	// Pointers
	// ---------------------------------
	
	$( '.js-display-tooltip' ).click( function() {
		var thiz = $( this ),
		edge = ( $( 'html[dir="rtl"]' ).length > 0 ) ? 'right' : 'left';
		// hide this pointer if other pointer is opened.
		$( '.wp-toolset-pointer' ).fadeOut( 100 );
		thiz.pointer({
			pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
			pointerWidth: 400,
			content: '<h3>'+thiz.data('header')+'</h3><p>'+thiz.data('content')+'</p>',
			position: {
				edge: edge,
				align: 'center',
				offset: '15 0'
			},
			buttons: function( event, t ) {
				var button_close = $('<button class="button button-primary-toolset alignright js-wpv-close-this">Close</button>');
				button_close.bind( 'click.pointer', function( e ) {
					e.preventDefault();
					t.element.pointer('close');
				});
				return button_close;
			}
		}).pointer('open');
	});
	
	$( document ).on( 'js_event_wpv_dismiss_pointer', function( event, pointer_name ) {
		var data = {
			action: 'wpv_dismiss_pointer',
			name: pointer_name
			//wpnonce : $(this).data('nonce')
		};
		$.ajax({
			type : "POST",
			url : ajaxurl,
			data : data,
			success : function( response ) {
				
			},
			error: function ( ajaxContext ) {
				
			},
			complete: function() {
				$( '.js-wpv-' + pointer_name + '-pointer' ).addClass( 'js-wpv-pointer-dismissed' );
			}
		});
	});
	
	// ---------------------------------
	// Toolset compatibility
	// ---------------------------------
	
	self.toolset_compatibility = function() {
		// CRED plugin
		if ( typeof cred_cred != 'undefined' ) {
			cred_cred.posts();
		}
		// Layouts plugin
		if ( $( '.js-wpv-display-in-iframe' ).length == 1 ) {
			if ( $( '.js-wpv-display-in-iframe' ).val() == 'yes' ) {
				$( '.toolset-help a, .wpv-setting a' ).attr( "target", "_blank" );
			}
		}
		//Legacy WordPress Archives without a Filter shortcode in its content
		self.fix_content_missing_filter_editor();
	};
	
	/**
	* init_third_party
	*
	* Init third party callbacks.
	*
	* @since 2.1
	*/
	
	self.init_third_party = function() {
		// select2 in orderby dropdowns
		$( 'select.js-wpv-posts-orderby' ).select2(
			{ 
				width:				'resolve',
				dropdownAutoWidth:	true 
			}
		);
		// Admin menu link target
		$( '#adminmenu li.current a' ).attr( 'href', $( '#adminmenu li.current a' ).attr( 'href' ) + '&view_id=' + self.view_id );
	};
	
	// ---------------------------------
	// Dialogs
	// ---------------------------------
	
	self.init_dialogs = function() {
		self.post_types_for_archive_loops_dialog = $( "#js-wpv-wpa-hidden-dialogs-container #js-wpv-dialog-assign-post-type-to-archive-loop-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_editor_strings.dialog.post_types_for_archive_loop.title,
			minWidth:	self.dialog_minWidth,
			draggable:	false,
			resizable:	false,
			show: { 
				effect:		"blind", 
				duration:	800 
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close:		function( event, ui ) {
				self.apply_post_types_to_loop_data = {
					type:			'',
					name:			'',
					'default_pt':	[],
					'selected_pt':	[]
				};
				$( '.js-wpv-post-types-for-archive-loop' ).prop( 'checked', false );
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class:	'button-secondary',
					text:	wpv_editor_strings.dialog.cancel,
					click:	function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class:	'button-secondary',
					text:	wpv_editor_strings.dialog.restore,
					click:	function() {
						self.restore_post_types_to_archive_loop();
					}
				},
				{
					class:	'button-primary js-wpv-apply-post-types-to-archive-loop',
					text:	wpv_editor_strings.dialog.apply,
					click:	function() {
						self.apply_post_types_to_archive_loop();
					}
				}
			]
		});
		self.archive_pagination_dialog = $( "#js-wpv-wpa-hidden-dialogs-container #js-wpv-archive-pagination-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_editor_strings.add_archive_pagination_dialog_title,
			minWidth:	self.dialog_minWidth,
			draggable:	false,
			resizable:	false,
			show: { 
				effect:		"blind", 
				duration:	800 
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.init_pagination_dialog_options();
			},
			close:		function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: wpv_editor_strings.add_archive_pagination_dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-primary js-wpv-insert-archive-pagination',
					text: wpv_editor_strings.add_archive_pagination_dialog_insert,
					click: function() {

					}
				}
			]
		});
		self.frontend_events_dialog = $( "#js-wpv-dialog-views-frontend-events" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_editor_strings.frontend_events_dialog_title,
			minWidth:	self.dialog_minWidth,
			draggable:	false,
			resizable:	false,
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.manage_frontend_events_dialog_button();
				$('.js-wpv-shortcode-gui-tabs')
                    .tabs()
                    .addClass('ui-tabs-vertical ui-helper-clearfix')
                    .removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$('#js-wpv-shortcode-gui-dialog-tabs ul, #js-wpv-shortcode-gui-dialog-tabs li').removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary js-wpv-frontend-events-close',
					text: wpv_editor_strings.dialog_close,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-primary js-wpv-frontend-events-insert',
					text: wpv_editor_strings.add_event_trigger_callback_dialog_insert,
					click: function() {
						self.insert_frontend_event_handler();
					}
				}
			]
		});
	};
	
	// ---------------------------------
	// Save all
	// ---------------------------------
	
	$( document ).on( 'js_wpv_save_section_queue_completed', function( event ) {
		
		$( '.js-wpv-view-save-all-spinner' ).remove();
		self.save_all_queue_step		= 0;
		self.save_all_queue_completed	= 100;
		self.save_all_progress_bar
			.removeClass( 'wpv-view-save-all-progress-working' )
			.css({
				'width': '100%'
			});
		// Determine the overall result.
		var is_queue_successfull = ( self.save_fail_queue.length == 0 ),
		action_bar_class = ( is_queue_successfull ? 'wpv-action-success' : 'wpv-action-failure' );
		
		self.save_fail_queue = [];
		
		// Display success/failure message.
		if ( is_queue_successfull ) {
			//noinspection JSUnresolvedVariable
			self.manage_action_bar_success({message: wpv_editor_strings.sections_saved});
		} else {
			//noinspection JSUnresolvedVariable
			self.manage_action_bar_error( { message: wpv_editor_strings.some_section_unsaved, stay: false } );
		}

		// Highlight the action bar
		$( '.js-wpv-general-actions-bar' ).addClass( action_bar_class );
		setTimeout( function () {
			$( '.js-wpv-general-actions-bar' ).removeClass( action_bar_class );
			self.save_all_progress_bar.css({
				'width': '0'
			});
		}, 1000 );
		
	});
	
	$( document ).on( 'click', '.js-wpv-view-save-all', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainerAll = $('<div class="wpv-spinner ajax-loader js-wpv-view-save-all-spinner">').insertBefore( thiz ).show();
		
		thiz
			.prop('disabled', true).
			removeClass('button-primary')
			.addClass('button-secondary');
		
		if ( _.size( self.save_queue ) > 0 ) {
			self.save_all_queue_step = Math.round( 100 / _.size( self.save_queue ) );
		}
		
		$( document ).trigger( 'js_wpv_save_section_queue' );
	});
	
	// ---------------------------------
	// Warning when clicking away on unsaved changes
	// ---------------------------------
	
	$( document ).on( 'js_event_wpv_set_confirmation_unload_check', function( event ) {
		if ( $( '.js-wpv-section-unsaved' ).length < 1 ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', false );
		}	
	});
	
	self.set_confirm_unload = function( on ) {
		if (
			on 
			&& $( '.js-wpv-section-unsaved' ).length > 0
		) {
			window.onbeforeunload = function( e ) {
				$( '.js-wpv-section-unsaved' ).each( function() {
					var unsaved_message = $(this).data('unsaved');
					if ($(this).parents('.js-wpv-update-button-wrap').find('.toolset-alert-error').length < 1) {
						// @todo review this message, it needs to be attached to a dedicated empty container
						$(this)
							.parents('.js-wpv-update-button-wrap')
								.find('.js-wpv-message-container')
									.wpvToolsetMessage({
										text:unsaved_message,
										type:'error',
										inline:true,
										stay:true
									});
					}
				});
				var message = 'You have entered new data on this page.';
				// For IE and Firefox prior to version 4
				if (e) {
					e.returnValue = message;
				}
				// For Safari
				//	var e = event || window.event;
				return message;
			}
			$('.js-wpv-view-save-all').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
			$(document).trigger( 'js_event_wpv_set_confirmation_unload_done', [ true ] );
		} else {
			window.onbeforeunload = null;
			$('.js-wpv-view-save-all, .js-wpv-section-unsaved').prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
			$(document).trigger( 'js_event_wpv_set_confirmation_unload_done', [ false ] );
		}
	};

	// ---------------------------------
	// Init
	// ---------------------------------
	
	self.init = function() {
		// Init the model
		self.init_model();
		// Init hooks
		self.init_hooks();
		// Init help
		self.init_loop_selection_help();
		// Init CodeMirror editors
		self.init_codemirror();
		// Title placeholder
		self.title_placeholder();
		// Add quicktags to the right textareas
		self.add_quicktags();
		// Toolset compatibility
		self.toolset_compatibility();
		// Init third-party
		self.init_third_party();
		// Init dialogs
		self.init_dialogs();
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.wpa_edit_screen = new WPViews.WPAEditScreen( $ );
});

/**
* Quicktags custom implementation fallback
*/

if ( typeof WPV_Toolset.add_qt_editor_buttons !== 'function' ) {
    WPV_Toolset.add_qt_editor_buttons = function( qt_instance, editor_instance ) {
		var activeUrlEditor;
        QTags._buttonsInit();
		if ( typeof WPV_Toolset.CodeMirror_instance[qt_instance.id] === "undefined" ) {
			WPV_Toolset.CodeMirror_instance[qt_instance.id] = editor_instance;
		}
        for ( var button_name in qt_instance.theButtons ) {
			if ( qt_instance.theButtons.hasOwnProperty( button_name ) ) {
				qt_instance.theButtons[button_name].old_callback = qt_instance.theButtons[button_name].callback;
                if ( qt_instance.theButtons[button_name].id == 'img' ){
                    qt_instance.theButtons[button_name].callback = function( element, canvas, ed ) {
						var t = this,
						id = jQuery( canvas ).attr( 'id' ),
						selection = WPV_Toolset.CodeMirror_instance[id].getSelection(),
						e = "http://",
						g = prompt( quicktagsL10n.enterImageURL, e ),
						f = prompt( quicktagsL10n.enterImageDescription, "" );
						t.tagStart = '<img src="' + g + '" alt="' + f + '" />';
						selection = t.tagStart;
						t.closeTag( element, ed );
						WPV_Toolset.CodeMirror_instance[id].replaceSelection( selection, 'end' );
						WPV_Toolset.CodeMirror_instance[id].focus();
                    }
                } else if ( qt_instance.theButtons[button_name].id == 'wpv_conditional' ) {
                    qt_instance.theButtons[button_name].callback = function ( e, c, ed ) {                     
                        WPV_Toolset.activeUrlEditor = ed;                        
						var id = jQuery( c ).attr( 'id' ),
                        t = this;
                        window.wpcfActiveEditor = id;
                        WPV_Toolset.CodeMirror_instance[id].focus();
                        selection = WPV_Toolset.CodeMirror_instance[id].getSelection();
						var current_editor_object = {};
						if ( selection ) {
						   //When texty selected
						   current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : true, 'codemirror' : id};
						   WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', wpv_shortcodes_gui_texts.wpv_insert_conditional_shortcode, {}, wpv_shortcodes_gui_texts.wpv_editor_callback_nonce, current_editor_object );
						} else if ( ed.openTags ) {
							// if we have an open tag, see if it's ours
							var ret = false, i = 0, t = this;
							while ( i < ed.openTags.length ) {
								ret = ed.openTags[i] == t.id ? i : false;
								i ++;
							}
							if ( ret === false ) {
								t.tagStart = '';
								t.tagEnd = false;                
								if ( ! ed.openTags ) {
									ed.openTags = [];
								}
								ed.openTags.push(t.id);
								e.value = '/' + e.value;
								current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : false, 'codemirror' : id};
								WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', wpv_shortcodes_gui_texts.wpv_insert_conditional_shortcode, {}, wpv_shortcodes_gui_texts.wpv_editor_callback_nonce, current_editor_object );
							} else {
								// close tag
								ed.openTags.splice(ret, 1);
								t.tagStart = '[/wpv-conditional]';
								e.value = t.display;
								window.icl_editor.insert( t.tagStart );
							}
						} else {
							// last resort, no selection and no open tags
							// so prompt for input and just open the tag           
							t.tagStart = '';
							t.tagEnd = false;
							if ( ! ed.openTags ) {
								ed.openTags = [];
							}
							ed.openTags.push(t.id);
							e.value = '/' + e.value;
							current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : false, 'codemirror' : id};
							WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', wpv_shortcodes_gui_texts.wpv_insert_conditional_shortcode, {}, wpv_shortcodes_gui_texts.wpv_editor_callback_nonce, current_editor_object );
						}
					}
                } else if ( qt_instance.theButtons[button_name].id == 'close' ) {
                    
                } else if ( qt_instance.theButtons[button_name].id == 'link' ) {
					var t = this;
					qt_instance.theButtons[button_name].callback = function ( b, c, d, e ) {
						activeUrlEditor = c;var f,g=this;return"undefined"!=typeof wpLink?void wpLink.open(d.id):(e||(e="http://"),void(g.isOpen(d)===!1?(f=prompt(quicktagsL10n.enterURL,e),f&&(g.tagStart='<a href="'+f+'">',a.TagButton.prototype.callback.call(g,b,c,d))):a.TagButton.prototype.callback.call(g,b,c,d)))
					};
					jQuery( '#wp-link-submit' ).off();
					jQuery( '#wp-link-submit' ).on( 'click', function( event ) {
						event.preventDefault();
						var id = jQuery( activeUrlEditor ).attr('id'),
						selection = WPV_Toolset.CodeMirror_instance[id].getSelection(),
						inputs = {},
						attrs, text, title, html;
						inputs.wrap = jQuery('#wp-link-wrap');
						inputs.backdrop = jQuery( '#wp-link-backdrop' );
						if ( jQuery( '#link-target-checkbox' ).length > 0 ) {
							// Backwards compatibility - before WordPress 4.2
							inputs.text = jQuery( '#link-title-field' );
							attrs = wpLink.getAttrs();
							text = inputs.text.val();
							if ( ! attrs.href ) {
								return;
							}
							// Build HTML
							html = '<a href="' + attrs.href + '"';
							if ( attrs.target ) {
								html += ' target="' + attrs.target + '"';
							}
							if ( text ) {
								title = text.replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
								html += ' title="' + title + '"';
							}
							html += '>';
							html += text || selection;
							html += '</a>';
							t.tagStart = html;
							selection = t.tagStart;
						} else {
							// WordPress 4.2+
							inputs.text = jQuery( '#wp-link-text' );
							attrs = wpLink.getAttrs();
							text = inputs.text.val();
							if ( ! attrs.href ) {
								return;
							}
							// Build HTML
							html = '<a href="' + attrs.href + '"';
							if ( attrs.target ) {
								html += ' target="' + attrs.target + '"';
							}
							html += '>';
							html += text || selection;
							html += '</a>';
							selection = html;
						}
						jQuery( document.body ).removeClass( 'modal-open' );
						inputs.backdrop.hide();
						inputs.wrap.hide();
						jQuery( document ).trigger( 'wplink-close', inputs.wrap );
						WPV_Toolset.CodeMirror_instance[id].replaceSelection( selection, 'end' );
						WPV_Toolset.CodeMirror_instance[id].focus();
						return false;
                    });
                } else {
                    qt_instance.theButtons[button_name].callback = function( element, canvas, ed ) {                    
                        var id = jQuery( canvas ).attr( 'id' ),
                        t = this,
                        selection = WPV_Toolset.CodeMirror_instance[id].getSelection();
						if ( selection.length > 0 ) { 
							if ( !t.tagEnd ) {
								selection = selection + t.tagStart;
							} else {
								selection = t.tagStart + selection + t.tagEnd;
							}
						} else {
							if ( !t.tagEnd ) {
								selection = t.tagStart;
							} else if ( t.isOpen( ed ) === false ) {
								selection = t.tagStart;
								t.openTag( element, ed );
							} else {
								selection = t.tagEnd;
								t.closeTag( element, ed );
							}
						}
                        WPV_Toolset.CodeMirror_instance[id].replaceSelection(selection, 'end');
                        WPV_Toolset.CodeMirror_instance[id].focus();
                    }
                }
			}
		}
    }
}
