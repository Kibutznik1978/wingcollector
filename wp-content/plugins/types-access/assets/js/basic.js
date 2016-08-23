var wpcfAccess = wpcfAccess || {};
var OTGAccess = OTGAccess || {};

/**
* OTGAccess.AccessSettings
*
* @todo Decouple this script in two: one for the Access Control page, other for the items in the post edit page
* @todo AJAX messages management, including single messages container and tabs locking when needed
*
* @since 2.0
*/

OTGAccess.AccessSettings = function( $ ) {
	
	// @todo add proper mesage management
	
	var self = this;
	
	self.spinner = '<span class="wpcf-loading ajax-loader js-otg-access-spinner"></span>';
	
	self.spinner_placeholder = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="otg-access-spinner"><i class="fa fa-refresh fa-spin"></i></div>' +
		'</div>' +
		'</div>'
	);
	
	self.glow_container = function( container, reason ) {
		$( container ).addClass( reason );
		setTimeout( function () {
			$( container ).removeClass( reason );
		}, 500 );
	};
	
	/**
	* Tab management
	*
	* @since 2.0
	*/
		
	$( document ).on( 'click', '.js-otg-access-nav-tab', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		target = thiz.data( 'target' ),
		current = $( '.js-otg-access-nav-tab.nav-tab-active' ).data( 'target' );
		if ( ! thiz.hasClass( 'nav-tab-active' ) ) {
			$( '.js-otg-access-nav-tab.nav-tab-active' ).removeClass( 'nav-tab-active' );
			if ( $( '.js-otg-access-content .js-otg-access-settings-section-for-' + target ).length > 0 ) {
				$( '.js-otg-access-content .js-otg-access-settings-section-for-' + current ).fadeOut( 'fast', function() {
					thiz.addClass( 'nav-tab-active' );
					$( '.js-otg-access-content .js-otg-access-settings-section-for-' + target ).fadeIn( 'fast' );
				});
			} else {
				if ( ! thiz.hasClass( 'js-otg-access-nav-tab-loading' ) ) {
					$( '.js-otg-access-content .js-otg-access-settings-section-for-' + current ).fadeOut( 'fast' );
					$( '.js-otg-access-content .js-otg-access-settings-section-loading' ).fadeIn( 'fast' );
					$( '.js-otg-access-nav-tab' ).addClass( 'js-otg-access-nav-tab-loading' );
					var data = {
						action : 'wpcf_access_load_permission_table',
						section : target,
						wpnonce : jQuery('#wpcf-access-error-pages').attr('value')
					},
					data_for_events = {
						section: target
					};
					jQuery.ajax({
						url:		ajaxurl,
						type:		'POST',
						dataType:	"json",
						data:		data,
						success: 	function( response ) {
							if ( response.success ) {
								thiz.addClass( 'nav-tab-active' );
								$( '.js-otg-access-content .js-otg-access-settings-section-loading' ).fadeOut( 'fast', function() {
									jQuery( '.js-otg-access-content' ).append( response.data.output );
									jQuery( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
								});
							}
						},
						complete:	function( object, status ) {
							$( '.js-otg-access-nav-tab' ).removeClass( 'js-otg-access-nav-tab-loading' );
						}
					});
				}
			}
		}
	});
	
	$( document ).on( 'click', '.js-otg-access-manual-tab', function( e ) {
		e.preventDefault();
		var target = $( this ).data( 'target' ),
		target_tab = $( '.js-otg-access-nav-tab[data-target=' + target + ']' );
		target_tab.trigger( 'click' );
	});
	
	/**
	* load_permission_tables
	*
	* Load a tab content, mainly used when reloading a tab after some create/modify event.
	*
	* @param string		section
	*
	* @since unknown
	* @since 2.1		Renamed from otg_access_load_permission_tables and moved to a module method
	*/
	
	self.load_permission_tables = function( section ) {
		var data = {
			action: 'wpcf_access_load_permission_table',
			section: section,
			wpnonce: $('#wpcf-access-error-pages').attr('value')
		},
		data_for_events = {
			section: section
		};
		$.ajax({
			url:		ajaxurl,
			type:		'POST',
			dataType:	"json",
			data:		data,
			success: 	function( response ) {
				if ( response.success ) {
					$('.js-otg-access-content .js-otg-access-settings-section-for-' + section).replaceWith( response.data.output );
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
				}
			}
		});
	}
	
	/**
	* Invalidate tabs
	*
	* @since 2.1
	*/
		
	self.available_tabs = $( '.js-otg-access-nav-tab' ).map( function() {
		return $( this ).data( 'target' );
	}).get();
	
	$( document ).on( 'js_event_otg_access_settings_section_saved', function( event, section, tab ) {
		var tabs_to_invalidate = [];
		switch ( tab ) {
			case 'custom-roles':
				// This never happens as roles are saved in a different way, but leave for consistency
				tabs_to_invalidate = _.without( self.available_tabs, 'custom-roles' );
				break;
			case 'custom-group':
				tabs_to_invalidate.push( 'post-type' );
				break;
		}
		self.invalidate_tabs( tabs_to_invalidate );
	});
	
	$( document ).on( 'js_event_types_access_custom_group_updated js_event_types_access_wpml_group_updated', function() {
		var tabs_to_invalidate = [];
		tabs_to_invalidate.push( 'post-type' );
		self.invalidate_tabs( tabs_to_invalidate );
	});
	
	$( document ).on( 'js_event_types_access_custom_roles_updated', function() {
		var tabs_to_invalidate = _.without( self.available_tabs, 'custom-roles' );
		self.invalidate_tabs( tabs_to_invalidate );
	});
	
	self.invalidate_tabs = function( tabs ) {
		$.each( tabs, function( index, value ) {
			$( '.js-otg-access-content .js-otg-access-settings-section-for-' + value ).remove();
		});
	};
	
	/**
	* Sections toggle
	*
	* @since 2.0
	*/
	
	$( document ).on( 'click', '.js-otg-access-settings-section-item-toggle', function() {
		var thiz = $( this ),
		target = thiz.data( 'target' );
		thiz.find( '.js-otg-access-settings-section-item-managed' ).toggle();
		$( '.js-otg-access-settings-section-item-toggle-target-' + target ).slideToggle();
	});
	
	/**
	* Save settings section 
	*
	* @since 2.0
	*/
	
	$( document ).on( 'click', '.js-otg-access-settings-section-save', function( e ) {
        e.preventDefault();
		var thiz			= $( this );
		thiz_section		= thiz.closest( '.js-otg-access-settings-section-item' ),
		thiz_tab			= thiz.closest( '.js-otg-access-settings-tab-section' ).data( 'tab' ),
		spinnerContainer	= $( self.spinner ).insertBefore( thiz ).show();
        $( '#wpcf_access_admin_form' )
			.find('.dep-message')
			.hide();
        $.ajax({
            url:		ajaxurl,
            type:		'POST',
            dataType:	'json',
            data:		thiz_section.find('input').serialize()
						+ '&wpnonce=' + $('#otg-access-edit-sections').val()
						+ '&_wp_http_referer=' + $('input[name=_wp_http_referer]').val()
						+ '&action=wpcf_access_save_settings_section',
            success:	function( response ) {
				if ( response.success ) {
					if ( '' != response.data.message ) {
						$( '#wpcf_access_notices' )
							.empty()
							.html( response.data.message );
					}
					$( document ).trigger( 'js_event_otg_access_settings_section_saved', [ thiz_section, thiz_tab ] );
				}
            },
			complete: function() {
				spinnerContainer.remove();
			}
        });
        return false;
    });
	
	$( document ).on( 'js_event_otg_access_settings_section_saved', function( event, section, tab ) {
		self.glow_container( section, 'otg-access-settings-section-item-saved' );
		var has_enable = section.find( '.js-wpcf-enable-access' );
		if ( has_enable.length > 0 ) {
			var is_enabled = has_enable.prop( 'checked' );
			if ( is_enabled ) {
				section
					.removeClass( 'otg-access-settings-section-item-not-managed' )
					.find( '.js-otg-access-settings-section-item-managed' )
						.text( wpcf_access_dialog_texts.otg_access_managed );
			} else {
				section
					.addClass( 'otg-access-settings-section-item-not-managed' )
					.find( '.js-otg-access-settings-section-item-managed' )
						.text( wpcf_access_dialog_texts.otg_access_not_managed );
			}
		}
	});
	
	/**
	* Custom Roles management
	*
	* @since 2.0
	*/
	
	$( document ).on( 'click', '.js-otg-access-add-new-role', function( e ) {
		e.preventDefault();
        $( '.js-otg-access-new-role-wrap .js-otg-access-new-role-extra' )
			.fadeIn( 'fast' )
			.find( '.js-otg-access-new-role-name' )
				.val('')
				.focus();
        $( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( '' );
    });

	$( document ).on( 'click', '.js-otg-access-new-role-wrap .js-otg-access-new-role-cancel', function( e ) {
		e.preventDefault();
        $( '.js-otg-access-new-role-wrap .js-otg-access-new-role-apply' ).prop( 'disabled', true );
        $( '.js-otg-access-new-role-wrap .js-otg-access-new-role-extra' )
			.hide()
			.find( '.js-otg-access-new-role-name' )
				.val('');
        $( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( '' );
    });

    $( document ).on( 'click', '.js-otg-access-new-role-wrap .js-otg-access-new-role-apply', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		data = {
			action:		'wpcf_access_add_role',
			role:		$( '.js-otg-access-new-role-wrap .js-otg-access-new-role-name' ).val(),
			wpnonce:	wpcf_access_dialog_texts.otg_access_general_nonce,
		},
		data_for_events = {
			section: 'custom-roles'
		};
		
		spinnerContainer = $( self.spinner ).insertAfter( thiz ).show();
        thiz
			.prop( 'disabled', true )
			.addClass( 'button-secondary' )
			.removeClass( 'button-primary' );
		
        $( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( '' );

		$.ajax({
            url:		ajaxurl,
            type:		'POST',
            dataType:	'json',
            data:		data,
            success:	function( response ) {
				if ( response.success ) {
					$( '.js-otg-access-new-role-wrap .js-otg-access-new-role-name' ).val('');
					$( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
					$( document ).trigger( 'js_event_types_access_custom_roles_updated' );
				} else {
					$( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( response.data.message );
				}
            },
			complete: function() {
				spinnerContainer.remove();
			}
        });
    });

    $( document ).on( 'keyup', '.js-otg-access-new-role-wrap .js-otg-access-new-role-name', function() {
        $( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( '' );
        if ( $(this).val().length > 4 ) {
            $( '.js-otg-access-new-role-wrap .js-otg-access-new-role-apply' )
				.prop( 'disabled', false )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' );
        } else {
            $( '.js-otg-access-new-role-wrap .js-otg-access-new-role-apply' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
        }
    });

    // DELETE ROLE - NOT SURE WHERE THIS IS USED ???
    $( document ).on( 'click', '#wpcf-access-delete-role', function() {
        $(this).next().show();
    });
	
	/**
	* Initialize some data on tab load, like administrators checkboxes and taxonomies special inputs.
	*
	* @since 2.0
	*/
	
	self.init_inputs = function( container ) {
		// ADD DEPENDENCY MESSAGE - to review
		$( '.wpcf-access-type-item', container )
				.find('.wpcf-access-mode')
				.prepend('<div class="dep-message toolset-alert toolset-alert-info hidden"></div>');

		// Disable admin checkboxes
		$( ':checkbox[value="administrator"]', container )
				.prop('disabled', true)
				.prop('readonly', true)
				.prop('checked', true);


		// Initialize  "same as parent" checkboxes properties
		$.each( $( '.js-wpcf-follow-parent', container ), function() {
			var $manageByAccessCheckbox = $(this)
						.closest('.js-wpcf-access-type-item')
						.find('.js-wpcf-enable-access');
			
			if ( ! $manageByAccessCheckbox.is(':checked') ) {
				$(this)
					.prop('disabled', true)
					.prop('readonly', true);
			}
			
			
			var $container = $(this).closest('.js-wpcf-access-type-item');
			var checked = $(this).is(':checked');
			var $tableInputs = $container.find('table :checkbox, table input[type=text]');
		
			$tableInputs = $tableInputs.filter(function() { // All elements except 'administrator' role checkboxes
				return ( $(this).val() !== 'administrator' );
			});
			if ( checked) {
				wpcfAccess.DisableTableInputs($tableInputs, $container);
				$container.find('.js-wpcf-access-reset').prop('disabled', true);
			} 
		});
	};
	
	$( document ).on( 'js_event_types_access_permission_table_loaded', function( event, data ) {
		self.init_inputs( $( '.js-otg-access-settings-section-for-' + data.section ) );
		if ( self.access_control_dialog.dialog( 'isOpen' ) === true ) {
			self.access_control_dialog.dialog('close');
		}
	});
	
	/**
	* init_dialogs
	*
	* Init the Access Control page dialogs.
	*
	* @since 2.1
	*/
	
	self.init_dialogs = function() {
		$('body').append('<div id="js-wpcf-access-dialog-container" class="toolset-shortcode-gui-dialog-container wpcf-access-dialog-container js-wpcf-access-dialog-container"></div>');
		self.dialog_callback = '';
		self.dialog_callback_params = [];
		self.access_control_dialog = $("#js-wpcf-access-dialog-container").dialog({
			autoOpen:	false,
			modal:		true,
			minWidth:	450,
			show: {
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$('body').addClass('modal-open');
				$('.js-wpcf-access-process-button ')
						.addClass('button-secondary')
						.removeClass('button-primary ui-button-disabled ui-state-disabled')
						.prop('disabled', true)
						.css({'marginLeft': '15px', 'display': 'inline'});
				$('.js-wpcf-access-gui-close').css('display', 'inline');
			},
			close:		function( event, ui ) {
				$('body').removeClass('modal-open');
				$( '.js-otg-access-spinner' ).remove();
			},
			buttons: [
				{
					class: 'button-secondary js-wpcf-access-gui-close',
					text: wpcf_access_dialog_texts.wpcf_close,
					click: function () {
						$(this).dialog("close");
					}
				},
				{
					class: 'button-primary js-wpcf-access-process-button',
					text: '',
					click: function () {
						if ( self.dialog_callback != '' ) {
							self.dialog_callback.call( null, self.dialog_callback_params );
							$( self.spinner ).insertBefore( $( '.js-wpcf-access-process-button' ) ).show();
						}
					}
				}
			]
		});
	};
	
	self.init = function() {
		self.init_inputs( $( '.js-otg-access-content' ) );
		self.init_dialogs();
    };
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
    OTGAccess.access_settings = new OTGAccess.AccessSettings( $ );
});


(function (window, $, undefined) {


    $(document).ready(function () {
        
        var access_dialog_insert_shortcode = $("#wpcf-access-shortcodes-dialog-tpl").dialog({
            autoOpen: false,
            title: wpcf_access_dialog_texts.wpcf_shortcodes_dialog_title,
            modal: true,
            minWidth: 550,
            show: {
                effect: "blind",
                duration: 800
            },
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( ".js-wpcf-access-list-roles" ).prop("checked", false);
				$( ".js-wpcf-access-shortcode-operator" ).prop('checked', false);
				$( ".js-wpcf-access-conditional-message" ).val('');
				$( '.js-wpcf-access-add-shortcode' )
					.prop('disabled', true)
					.addClass('button-secondary')
					.removeClass('button-primary ui-button-disabled ui-state-disabled');
			},
            close: function (event, ui) {
				$( 'body' ).removeClass( 'modal-open' );
            },
            buttons: [
                {
                    class: 'button js-dialog-close',
                    text: wpcf_access_dialog_texts.wpcf_cancel,
                    click: function () {
                        $(this).dialog("close");
                    }
                },
                {
                    class: 'button js-wpcf-access-add-shortcode',
                    text: wpcf_access_dialog_texts.wpcf_insert,
					disabled: 'disabled',
                    click: function () {
                        insert_shortcode_to_editor( $( this ) );
                        $(this).dialog("close");
                    },
                }
            ]
        });

        $(document).on('mouseover', '.otg-access-nav-caret', function (e) {
            $(this).parent().find('.otg-access-nav-submenu').show();
        });
        $(document).on('mouseout', '.otg-access-nav-caret', function (e) {
            $(this).parent().find('.otg-access-nav-submenu').hide();
        });

		// We do not use colorbox here, we need to review, deprecate, remove dependency and call it a day.
        $(document).on('click', '.js-dialog-close', function (e) {
            e.preventDefault();
            $.colorbox.close();
        });

        // Show tooltips
        $('.js-tooltip').hover(function () {
            var $this = $(this);
            var $tooltip = $('<div class="tooltip">' + $this.text() + '</div>');

            if ($this.children().outerWidth() < $this.children()[0].scrollWidth) {
                $tooltip
                        .appendTo($this)
                        .css({
                            'visibility': 'visible',
                            'left': -1 * ($tooltip.outerWidth() / 2) + $this.width() / 2
                        })
                        .hide()
                        .fadeIn('600');
            }
            ;
        }, function () {
            $(this)
                    .find('.tooltip')
                    .remove();
        });

        // Count table columns
        $.each($('.js-access-table'), function () {
            var columns = $(this).find('th').length;
            $(this).addClass('columns-' + columns);
        });

        //Enabled advanced mode
        $(document).on('click', '.js-otg_access_enable_advanced_mode', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_close);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_ok);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_advanced_mode);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            OTGAccess.access_settings.dialog_callback = $confirm_advaced_mode;
            OTGAccess.access_settings.dialog_callback_params[''] = '';
            if ($(this).data('status') === false) {
                OTGAccess.access_settings.access_control_dialog.html(wpcf_access_dialog_texts.wpcf_advanced_mode1 + '<p>' + wpcf_access_dialog_texts.wpcf_advanced_mode2 + '</p>');
            } else {
                OTGAccess.access_settings.access_control_dialog.html(wpcf_access_dialog_texts.wpcf_advanced_mode3 + '<p>' + wpcf_access_dialog_texts.wpcf_advanced_mode2 + '</p>');
            }
            $('.js-wpcf-access-process-button')
                    .addClass('button-primary')
                    .removeClass('button-secondary')
                    .prop('disabled', false);
        });

        $confirm_advaced_mode = function (params) {

            var data = {
			action:		'wpcf_access_change_advanced_mode',
			wpnonce:	wpcf_access_dialog_texts.otg_access_general_nonce
		},
		data_for_events = {
			section: 'custom-roles'
		};
		$.ajax({
            url:		ajaxurl,
            type:		'POST',
			dataType:	'json',
            data:		data,
            success:	function( response ) {
				OTGAccess.access_settings.access_control_dialog.dialog('close');
                $( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
				$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
                }
            });
        };

        $(document).on('click', '.js-wpcf-access-delete-role', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_close);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_delete_role);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_delete_role);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_delete_role_form',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $confirm_remove_role;
            OTGAccess.access_settings.dialog_callback_params['role'] = $(this).data('role');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                }
            });
        });

    $confirm_remove_role =  function( params ) {
        var role = params['role'],
        data = {
			action:						'wpcf_access_delete_role',
			wpcf_access_delete_role:	role,                
			wpcf_reassign:				$('[name="wpcf_reassign"]').val(),
			wpnonce:					wpcf_access_dialog_texts.otg_access_general_nonce
		},
		data_for_events = {
			section: 'custom-roles'
		};
        $.ajax({
            url:		ajaxurl,
            type:		'POST',
			dataType:	'json',
            data:		data,
            success:	function( response ) {
				if ( response.success ) {
					$( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
					OTGAccess.access_settings.access_control_dialog.dialog('close');
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
					$( document ).trigger( 'js_event_types_access_custom_roles_updated' );
				}            	
                }
            });
        };

        jQuery(document).on('click', '.js-dialog-close', function (e) {
            jQuery('.editor_addon_dropdown').css({
                'visibility': 'hidden'
                        //'display' : 'inline'
            });
        });





        // Show editor dropdown
        $(document).on('click', '.js-wpcf-access-editor-button', function (e) {
            e.preventDefault();
            window.wpcfActiveEditor = jQuery(this).data('editor');
			var dialog_height = $(window).height() - 100;
            access_dialog_insert_shortcode.dialog('open').dialog({
				width: 770,
				maxHeight: dialog_height,
				draggable: false,
				resizable: false,
				position: {
					my: "center top+50",
					at: "center top",
					of: window,
					collision: "none"
				}
			});
        });

        access_dialog_callback = function () {
            return true;
        };

        $(document).on('click', '.js-wpcf-access-import-button', function (e) {
            $('.toolset-alert').remove();
            if ($('.js-wpcf-access-import-file').val() === '') {
                $('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + $(this).data('error') + '</p>').insertAfter(".js-wpcf-access-import-button")
                return false;
            } else {
                return true;
            }
        });
        $(document).on('change', '.js-wpcf-access-import-file', function (e) {
            $('.toolset-alert').remove();
        });

        //Enable insert shortocde button when one or more roles selected
        $(document).on('change', '.js-wpcf-access-list-roles', function () {
            $('.js-wpcf-access-add-shortcode').prop('disabled', true).addClass('button-secondary').removeClass('button-primary');
            if ($('.js-wpcf-access-list-roles:checked').length > 0) {
                $('.js-wpcf-access-add-shortcode').prop('disabled', false).addClass('button-primary').removeClass('button-secondary');
            }
        });

        //Insert shortocde to editor
        insert_shortcode_to_editor = function( dialog ){
            shortcode = '[toolset_access role="';
            shortcode += $( '.js-wpcf-access-list-roles:checked', dialog ).map(function () {
                return $(this).val();
            }).get().join(",");
            shortcode += ( $( 'input[name="wpcf-access-shortcode-operator"]:checked', dialog ).length > 0 ) ? '" operator="' + $( 'input[name="wpcf-access-shortcode-operator"]:checked', dialog ).val() + '"' : '';
			shortcode += ']' + $( '.js-wpcf-access-conditional-message', dialog ).val() + '[/toolset_access]';
            //window.wpcfActiveEditor = jQuery(this).data('editor');
            icl_editor.insert( shortcode );
            jQuery('.editor_addon_dropdown').css({'visibility': 'hidden'});
            return false;
        };

        //Show Role caps (read only)
        $(document).on('click', '.wpcf-access-view-caps', function (e) {
            e.preventDefault();

            $access_dialog_open(400);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_close);
            $('.js-wpcf-access-process-button').css('display', 'none');
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_role_permissions);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_show_role_caps',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = '';
            OTGAccess.access_settings.dialog_callback_params = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);

                }
            });
        });

        $access_dialog_open = function (width) {
            var dialog_height = $(window).height() - 100;
            OTGAccess.access_settings.access_control_dialog.dialog('open').dialog({
                title: wpcf_access_dialog_texts.wpcf_change_perms,
                width: width,
                maxHeight: dialog_height,
                draggable: false,
                resizable: false,
                position: {my: "center top+50", at: "center top", of: window}
            });
        }

        //Show popup: change custom role permissions
        $(document).on('click', '.wpcf-access-change-caps', function (e) {
            e.preventDefault();

            $access_dialog_open(800);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_close);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_change_perms);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_change_perms);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_change_role_caps',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $role_caps_process;
            OTGAccess.access_settings.dialog_callback_params = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
				$('.js-otg-access-change-role-caps-tabs')
					.tabs({
						active: 0
					})
					.addClass('ui-tabs-vertical ui-helper-clearfix')
					.removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                }
            });
        });


        //Process: change custom role permissions
        $role_caps_process = function () {
            var caps = [];
            if (typeof $('input[name="assigned-posts"]') !== 'undefined') {
                $('input[name="current_role_caps[]"]:checked').each(function () {
                    caps.push($(this).val());
                });
            }
            var data = {
                action: 'wpcf_process_change_role_caps',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                role: $('.js-wpcf-current-edit-role').val(),
                caps: caps
            };
            $('.js-wpcf-access-role-caps-process').prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.dialog('close');
                }
            });

            return false;
        };


        //Open for for new custom cap
        $(document).on('click', '.js-wpcf-access-add-custom-cap', function () {
            $(this).hide();
            $('.js-wpcf-create-new-cap-form').show();
            $('#js-wpcf-new-cap-slug').focus();

            return false;
        });

        $(document).on('input', '#js-wpcf-new-cap-slug', function () {
            $('.js-wpcf-new-cap-add').prop('disabled', true).removeClass('button-primary');
            $('.toolset-alert').remove();
            if ($(this).val() !== '') {
                $('.js-wpcf-new-cap-add').prop('disabled', false).addClass('button-primary');
            }
        });

        $(document).on('click', '.js-wpcf-new-cap-cancel', function () {
            $('.js-wpcf-access-add-custom-cap').show();
            $('.js-wpcf-create-new-cap-form').hide();
            return false;
        });

        $(document).on('click', '.js-wpcf-remove-custom-cap a, .js-wpcf-remove-cap-anyway', function () {
            var div = $(this).data('object');
            var cap = $(this).data('cap');
            var remove = $(this).data('remove');
            var $thiz = $(this);
            var ajaxSpinner = $(this).parent().find('.spinner');
            ajaxSpinner.css('visibility', 'visible');
            var data = {
                action: 'wpcf_delete_cap',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                cap_name: cap,
                remove_div: div,
                remove: remove,
                edit_role: $('.js-wpcf-current-edit-role').val()
            };
            $thiz.hide();
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    ajaxSpinner.css('visibility', 'hidden');
                    if (data == 1) {
                        $('#wpcf-custom-cap-' + cap).remove();
                        if ($('.js-wpcf-remove-custom-cap').length == 0) {
                            $('.js-wpcf-no-custom-caps').show();
                        }
                    } else {
                        $(data).insertAfter($thiz);
                    }

                }
            });
            return false;
        });

        $(document).on('click', '.js-wpcf-remove-cap-cancel', function () {
            $('.js-wpcf-remove-custom-cap_' + $(this).data('cap')).find('a').show();
            $('.js-removediv_' + $(this).data('cap')).remove();
            return false;
        });



        $(document).on('click', '.js-wpcf-new-cap-add', function (e) {
            var test_cap_name = /^[a-z0-9_]*$/.test($('#js-wpcf-new-cap-slug').val());
            $('.js-wpcf-create-new-cap-form').find('.toolset-alert').remove();
            if (test_cap_name === false) {
                $('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + $(this).data('error') + '</p>');
                return false;
            }

            var ajaxSpinner = $('.js-new-cap-spinner');
            ajaxSpinner.css('visibility', 'visible');
            var data = {
                action: 'wpcf_create_new_cap',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                cap_name: $('#js-wpcf-new-cap-slug').val(),
                cap_description: $('#js-wpcf-new-cap-description').val()
            };
            $('.js-wpcf-new-cap-add').prop('disabled', true).removeClass('button-primary');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                dataType: 'json',
                success: function (data) {
                    ajaxSpinner.css('visibility', 'hidden');

                    if (data[0] == 1) {
                        $('.js-wpcf-list-custom-caps').append(data[1]);
                        $('#js-wpcf-new-cap-slug,#js-wpcf-new-cap-description').val('');
                        $('.js-wpcf-access-add-custom-cap').show();
                        $('.js-wpcf-create-new-cap-form, .js-wpcf-no-custom-caps').hide();

                    } else {
                        $('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + data[1] + '</p>');
                    }
                }
            });
            return false;
        });

        //Show popup from edit post page (assign post to group)
        $(document).on('click', '.js-wpcf-access-assign-post-to-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_assign_group);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_access_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_select_access_group_for_post',
                id: $(this).data('id'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_access_assign_post_to_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('id');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    if ($('input[name="wpcf-access-group-method"]:checked').val() == 'existing_group') {
                        $('.js-wpcf-access-process-button')
                                .addClass('button-primary')
                                .removeClass('button-secondary')
                                .prop('disabled', false);
                        $('select[name="wpcf-access-existing-groups"]').show();
                    }
                }
            });

        });

        $process_access_assign_post_to_group = function (params) {
            id = params['id'];
            var data = {
                action: 'wpcf_process_select_access_group_for_post',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                id: id,
                methodtype: $('input[name="wpcf-access-group-method"]:checked').val(),
                group: $('select[name="wpcf-access-existing-groups"]').val(),
                new_group: $('input[name="wpcf-access-new-group"]').val()
            };
            $('.js-wpcf-access-process-button ')
                    .addClass('button-secondary')
                    .removeClass('button-primary ui-button-disabled ui-state-disabled')
                    .prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if (data != 'error') {
                        $('.js-wpcf-access-post-group').html(data);
                        OTGAccess.access_settings.access_control_dialog.dialog('close');
						$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        $('.js-wpcf-access-process-button ')
                                .addClass('button-secondary')
                                .removeClass('button-primary ui-button-disabled ui-state-disabled')
                                .prop('disabled', true);
                    }
                }
            });

            return false;
        };

        $(document).on('change', 'input[name="wpcf-access-group-method"]', function () {
            $('select[name="wpcf-access-existing-groups"],input[name="wpcf-access-new-group"]').hide();
            $('.js-wpcf-access-process-button ')
                    .addClass('button-secondary')
                    .removeClass('button-primary ui-button-disabled ui-state-disabled')
                    .prop('disabled', true);
            if ($(this).val() == 'existing_group') {
                $('select[name="wpcf-access-existing-groups"]').show();
                if ($('select[name="wpcf-access-existing-groups"]').val() != '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                }
            } else {
                $('input[name="wpcf-access-new-group"]').show();
                $('input[name="wpcf-access-new-group"]').focus();
                if ($('input[name="wpcf-access-new-group"]').val() !== '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                }
            }
        });

        $(document).on('change', 'select[name="wpcf-access-existing-groups"]', function () {
            $('.js-wpcf-access-process-button')
                    .addClass('button-primary')
                    .removeClass('button-secondary')
                    .prop('disabled', false);
        });

        $(document).on('input', 'input[name="wpcf-access-new-group"]', function () {
            $('.js-wpcf-access-process-button ')
                    .addClass('button-secondary')
                    .removeClass('button-primary ui-button-disabled ui-state-disabled')
                    .prop('disabled', true);
            $('.js-error-container').html('');
            if ($(this).val() != '') {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            }
        });

        $disable_languages = function () {
            var post_type = $('#wpcf-wpml-group-post-type').val();
            languages = jQuery.parseJSON($('#wpcf-wpml-group-disabled-languages').val());
            $('input[name="group_language_list"]').prop('disabled', false);
            if (typeof languages[post_type] !== 'undefined') {
                $('input[name="group_language_list"]').each(function () {
                    if (languages[post_type][$(this).val()] == 1) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
            }
        }

        $(document).on('change', '#wpcf-wpml-group-post-type', function (e) {
            $disable_languages();
        });

        //Create WPML group
        $(document).on('click', '.js-wpcf-add-new-wpml-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_create_group);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_set_wpml_settings);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );
            var group_id = '',
                    group_div_id = '';
            if (typeof $(this).data('group') !== 'undefined') {
                group_id = $(this).data('group');
                group_div_id = $(this).data('groupdiv');
                $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_modify_group);
            }
            var data = {
                action: 'wpcf_access_create_wpml_group_dialog',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                group_id: group_id,
                group_div_id: group_div_id
            };

            OTGAccess.access_settings.dialog_callback = $save_wpml_group;
            OTGAccess.access_settings.dialog_callback_params['divid'] = group_div_id;

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (res) {
                    OTGAccess.access_settings.access_control_dialog.html(res);
                    check_errors_form();
                    $disable_languages();
                    if (data.group_id === '') {
                        $('.js-wpcf-access-process-button')
                                .removeClass('button-primary')
                                .addClass('button-secondary')
                                .prop('disabled', true);
                    }
                }
            });

        });



        $save_wpml_group = function (params) {

            var data = {
                action: 'wpcf_access_wpml_group_save',
                //group_name : $('#wpcf-access-new-wpml-group-title').val(),
                group_nice: $('#wpcf-access-wpml-group-nice').val(),
                group_id: $('#wpcf-access-group-id').val(),
                languages: $('input[name="group_language_list"]').serializeArray(),
                form_action: $('#wpcf-access-group-action').val(),
                post_type: $('#wpcf-wpml-group-post-type').val(),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if (data != 'error') {
                        if ($('#wpcf-access-group-action').val() == 'add') {
							OTGAccess.access_settings.load_permission_tables( 'wpml-group' );
							$( document ).trigger( 'js_event_types_access_wpml_group_updated' );
                        } else {
                            $('#js-box-' + params['divid'])
                                .find('h4')
                                    .html(data);
							OTGAccess.access_settings.access_control_dialog.dialog('close');
                        }
                        wpcfAccess.addSuggestedUser();
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        $('.js-wpcf-access-process-button')
                                .addClass('button-primary')
                                .removeClass('button-secondary')
                                .prop('disabled', false);
                    }
                }
            });


        };


        $(document).on('change', 'input[name="group_language_list"]', function () {
            if (jQuery('input[name="group_language_list"]:checked').length > 0) {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            } else {
                $('.js-wpcf-access-process-button')
                        .removeClass('button-primary')
                        .addClass('button-secondary')
                        .prop('disabled', true);
            }
        });

        $(document).on('change', '#wpcf-wpml-group-post-type', function () {
            jQuery('input[name="group_language_list"]').prop('checked', false);
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);
        });

        $(document).on('click', '.js-wpcf-add-error-page', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_set_errors);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_set_errors);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_show_error_list',
                access_type: $(this).data('typename'),
                access_value: $(this).data('valuename'),
                cur_type: $(this).data('curtype'),
                cur_value: $(this).data('curvalue'),
                access_archivetype: $(this).data('archivetypename'),
                access_archivevalue: $(this).data('archivevaluename'),
                cur_archivetype: $(this).data('archivecurtype'),
                cur_archivevalue: encodeURIComponent($(this).data('archivecurvalue')),
                posttype: $(this).data('posttype'),
                is_archive: $(this).data('archive'),
                forall: $(this).data('forall'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $set_error_page;
            OTGAccess.access_settings.dialog_callback_params['id'] = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    check_errors_form();
                }
            });

        });

        // 'Set error page' popup
        $set_error_page = function () {
            var text = valname = typename = archivevalname = archivetypename = '';

            typename = $('input[name="error_type"]:checked').val();
            archivetypename = $('input[name="archive_error_type"]:checked').val();

            if ($('input[name="error_type"]:checked').val() === 'error_php') {
                text = wpcf_access_dialog_texts.wpcf_info2 + ': ' + $('select[name="wpcf-access-php"] option:selected').text();
                valname = $('select[name="wpcf-access-php"]').val();
                link_error = wpcf_access_dialog_texts.wpcf_error3 + valname;

            } else if ($('input[name="error_type"]:checked').val() === 'error_ct') {
                text = wpcf_access_dialog_texts.wpcf_info1 + ': ' + $('select[name="wpcf-access-ct"] option:selected').text();
                valname = $('select[name="wpcf-access-ct"]').val();
                link_error = wpcf_access_dialog_texts.wpcf_error2 + $('select[name="wpcf-access-ct"] option:selected').text();
            } else if ($('input[name="error_type"]:checked').val() === 'error_404') {
                text = '404';
                link_error = wpcf_access_dialog_texts.wpcf_error1;
                archivetypename = '';
            } else {
                text = '';
                typename = '';
                link_error = '';
            }


            if ($('input[name="archive_error_type"]').val() !== "undefined") {
                if ($('input[name="archive_error_type"]:checked').val() === 'error_php') {
                    archivetext = wpcf_access_dialog_texts.wpcf_info3 + ': ' + $('select[name="wpcf-access-archive-php"] option:selected').text();
                    archivevalname = $('select[name="wpcf-access-archive-php"]').val();
                    archivetypename = $('input[name="archive_error_type"]:checked').val();

                } else if ($('input[name="archive_error_type"]:checked').val() === 'error_ct') {
                    archivetext = wpcf_access_dialog_texts.wpcf_info4 + ': ' + $('select[name="wpcf-access-archive-ct"] option:selected').text();
                    archivevalname = $('select[name="wpcf-access-archive-ct"]').val();
                    archivetypename = $('input[name="archive_error_type"]:checked').val();
                } else if ($('input[name="archive_error_type"]:checked').val() === 'default_error') {
                    archivetext = wpcf_access_dialog_texts.wpcf_info5;
                    archivevalname = '';
                    archivetypename = '';
                } else {
                    archivetext = '';
                    archivetypename = '';
                }
            }

            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-error-page-name').html(text);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('curtype', typename);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('curvalue', valname);
            $('input[name="' + $('input[name="valuename"]').val() + '"]').val(valname);
            $('input[name="' + $('input[name="typename"]').val() + '"]').val(typename);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-wpcf-add-error-page').attr("title", link_error);
            if ($('input[name="archive_error_type"]').val() !== "undefined") {
                $('input[name="' + $('input[name="archivetypename"]').val() + '"]').parent().find('.js-archive_error-page-name').html(archivetext);
                $('input[name="' + $('input[name="archivevaluename"]').val() + '"]').val(archivevalname);
                $('input[name="' + $('input[name="archivetypename"]').val() + '"]').val(archivetypename);
                $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('archivecurtype', archivetypename);
                $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('archivecurvalue', archivevalname);
            }

            OTGAccess.access_settings.access_control_dialog.dialog('close');
        };

        function check_errors_form() {

            $('select[name="wpcf-access-ct"], select[name="wpcf-access-php"]').hide();
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            if ($('input[name="error_type"]:checked').val() == 'error_php') {
                $('select[name="wpcf-access-php"]').show();
                if ($('select[name="wpcf-access-php"]').val() !== '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                } else {
                    return;
                }
            } else if ($('input[name="error_type"]:checked').val() == 'error_ct') {
                $('select[name="wpcf-access-ct"]').show();
                if ($('select[name="wpcf-access-ct"]').val() !== '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                } else {
                    return;
                }
            } else {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            }


            $('select[name="wpcf-access-archive-ct"], select[name="wpcf-access-archive-php"],.js-wpcf-error-php-value-info,.js-wpcf-error-ct-value-info').hide();
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            if ($('input[name="archive_error_type"]:checked').val() == 'error_php') {
                $('select[name="wpcf-access-archive-php"], .js-wpcf-error-php-value-info').show();
                if ($('select[name="wpcf-access-archive-php"]').val() !== '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                } else {
                    return;
                }
            } else if ($('input[name="archive_error_type"]:checked').val() == 'error_ct') {
                $('select[name="wpcf-access-archive-ct"], .js-wpcf-error-ct-value-info').show();
                if ($('select[name="wpcf-access-archive-ct"]').val() !== '') {
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);
                } else {
                    return;
                }
            } else {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            }
        }

        $(document).on('change', '.js-wpcf-access-type-archive', function () {
            check_errors_form();
        });

        $(document).on('change', '.js-wpcf-access-type', function () {
            check_errors_form();
        });

        $(document).on('change', 'select[name="wpcf-access-php"], select[name="wpcf-access-ct"], select[name="wpcf-access-archive-php"], select[name="wpcf-access-archive-ct"]', function () {
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            if ($(this).val() !== '') {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            }
        });

        $(document).on('click', '.js-wpcf-search-posts', function () {

            $('.js-wpcf-search-posts').prop('disabled', true);
            var data = {
                action: 'wpcf_search_posts_for_groups',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-suggest-posts').val()
            };
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    $('.js-use-search').hide();
                    $('.js-wpcf-suggested-posts ul').html(data);
                    $('.js-wpcf-search-posts').prop('disabled', false);
                }
            });

            return false;
        });

        $(document).on('click', '.js-wpcf-search-posts-clear', function () {
            $('#wpcf-access-suggest-posts').val('');
            $('.js-wpcf-suggested-posts ul li').remove();

            return false;
        });

        // Add posts
        $(document).on('click', '.js-wpcf-add-post-to-group', function () {
            var li = '.js-assigned-access-post-' + $(this).data('id');

            if (typeof $(li).html() === 'undefined') {
                $('.js-no-posts-assigned').hide();
                $(".js-wpcf-assigned-posts ul").append('<li class="js-assigned-access-post-' + $(this).data('id') + '">' +
                        $(this).data('title') + ' <a href="" class="js-wpcf-unassign-access-post" data-id="' + $(this).data('id') + '">Remove</a>' +
                        '<input type="hidden" value="' + $(this).data('id') + '" name="assigned-posts[]"></li>');

                $(this)
                        .parent()
                        .remove();
            }

            if ($('.js-wpcf-suggested-posts ul').is(':empty')) {
                $('.js-use-search').fadeIn('fast');
            }

            return false;
        });

        // Remove posts
        $(document).on('click', '.js-wpcf-unassign-access-post', function () {
            var li = '.js-assigned-access-post-' + $(this).data('id');
            $(li).remove();
            var data = {
                action: 'wpcf_remove_postmeta_group',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                id: $(this).data('id')
            };
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if ($('.js-wpcf-assigned-posts ul').is(':empty')) {
                        $('.js-no-posts-assigned').fadeIn('fast');
                    }
                }
            });
            return false;
        });


        $(document).on('click', '.js-wpcf-remove-group', function (e) {
            e.preventDefault();

            $access_dialog_open(400);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_remove_group);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_delete_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_remove_group',
                group_id: $(this).data('group'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $delete_group_process;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('group');
            OTGAccess.access_settings.dialog_callback_params['divid'] = $(this).data('groupdiv');
			//OTGAccess.access_settings.dialog_callback_params['section'] = $(this).data('section');
			OTGAccess.access_settings.dialog_callback_params['target'] = $(this).data('target');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    if ($('.js-wpcf-assigned-posts ul').is(':empty')) {
                        $('.js-no-posts-assigned').show();
                    }
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);

                }
            });

        });

        $delete_group_process = function (params) {
            group_id = params['id'];
            var data = {
                action: 'wpcf_remove_group_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                group_id: group_id
            };

            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
					OTGAccess.access_settings.load_permission_tables( params['target'] );
					$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                }
            });

        };



        $(document).on('click', '.js-wpcf-add-new-access-group', function (e) {

            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_add_group);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_custom_access_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_add_new_group_form',
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_new_access_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    $('.js-wpcf-access-process-button')
                            .removeClass('button-primary')
                            .addClass('button-secondary')
                            .prop('disabled', true);

                }
            });

        });

        $process_new_access_group = function () {
            var posts = [];

            if (typeof $('input[name="assigned-posts"]') !== 'undefined') {
                $('input[name="assigned-posts[]"]').each(function () {
                    posts.push($(this).val());
                });
            }

            var data = {
                action: 'wpcf_process_new_access_group',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-new-group-title').val(),
                add: $('#wpcf-access-new-group-action').val(),
                posts: posts
            };

            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if (data != 'error') {
						OTGAccess.access_settings.load_permission_tables( 'custom-group' );
						$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        $('.js-wpcf-access-process-button')
                                .addClass('button-primary')
                                .removeClass('button-secondary')
                                .prop('disabled', false);
                    }
                }
            });
        };

        $(document).on('input', '#wpcf-access-new-group-title', function () {
            $('.js-error-container').html('');

            if ($(this).val() !== '') {
                $('.js-wpcf-access-process-button')
                        .addClass('button-primary')
                        .removeClass('button-secondary')
                        .prop('disabled', false);
            } else {
                $('.js-wpcf-access-process-button')
                        .removeClass('button-primary')
                        .addClass('button-secondary')
                        .prop('disabled', true);
            }
        });

        $(document).on('click', '.js-wpcf-modify-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

            $('.js-wpcf-access-gui-close .ui-button-text').html(wpcf_access_dialog_texts.wpcf_cancel);
            $('.js-wpcf-access-process-button .ui-button-text').html(wpcf_access_dialog_texts.wpcf_modify_group);
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_custom_access_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'wpcf_access_add_new_group_form',
                modify: $(this).data('group'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_modify_access_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('group');
            OTGAccess.access_settings.dialog_callback_params['divid'] = $(this).data('groupdiv');

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.html(data);
                    if ($('.js-wpcf-assigned-posts ul').is(':empty')) {
                        $('.js-no-posts-assigned').show();
                    }
                    $('.js-wpcf-access-process-button')
                            .addClass('button-primary')
                            .removeClass('button-secondary')
                            .prop('disabled', false);

                }
            });
        });

        $process_modify_access_group = function (params) {
            var posts = [];
            if (typeof $('input[name="assigned-posts"]') !== 'undefined') {
                $('input[name="assigned-posts[]"]').each(function () {
                    posts.push($(this).val());
                });
            }

            id = params['id'];
            var data = {
                action: 'wpcf_process_modify_access_group',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-new-group-title').val(),
                add: $('#wpcf-access-new-group-action').val(),
                id: id,
                posts: posts
            };
            $('.js-wpcf-access-process-button')
                    .removeClass('button-primary')
                    .addClass('button-secondary')
                    .prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if (data != 'error') {
                        $('#js-box-' + params['divid'])
                                .find('h4')
                                .eq(0)
                                .html($('#wpcf-access-new-group-title').val());
                        OTGAccess.access_settings.access_control_dialog.dialog('close');
						$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {

                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error js-toolset-alert" style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-wpcf-access-process-button')
                                .addClass('button-primary')
                                .removeClass('button-secondary')
                                .prop('disabled', false);
                    }
                }
            });

            return false;
        };

        $(document).on('submit', '.wpcf-access-set_error_page', function () {
            return false;
        });

    $( document ).on( 'change', 'select[name^="wpcf_access_bulk_set"]', function() {
            var value = $(this).val();
            if (value != '0') {

                $(this).parent().find('select').each(function () {
                    $(this).val(value);
                });
            }
        });

        // ASSIGN LEVELS
    $( document ).on( 'click', '#wpcf_access_admin_form .wpcf-access-change-level', function() {
            $(this).hide();
            $(this)
                    .closest('.wpcf-access-roles')
                    .find('.wpcf-access-custom-roles-select-wrapper')
                    .slideDown();
        });

    $( document ).on( 'click', '#wpcf_access_admin_form .wpcf-access-change-level-cancel', function(e) {
            e.preventDefault();

            $(this)
                    .closest('.js-access-custom-roles-selection')
                    .hide()
                    .parent()
                    .find('.wpcf-access-change-level')
                    .show();
        });

    $( document ).on( 'click', '#wpcf_access_admin_form .wpcf-access-change-level-apply', function(e) {
            e.preventDefault();

            if ($(this).data('message') !== "undefined" && confirm($(this).data('message'))) {
                wpcfAccess.ApplyLevels($(this));
            } else {
                return false;
            }
        });

    $( document ).on( 'change', '.wpcf-access-reassign-role select', function() {
            $(this)
                    .parents('.wpcf-access-reassign-role')
                    .find('.confirm')
                    .removeAttr('disabled');
        });

    });

    wpcfAccess.Reset = function (object) {
        $('#wpcf_access_admin_form')
                .find('.dep-message')
                .fadeOut('fast');

        $.ajax({
            url: object.attr('href') + '&button_id=' + object.attr('id'),
            type: 'get',
            dataType: 'json',
            //            data: ,
            cache: false,
            beforeSend: function () {},
            success: function (data) {
                if (data !== null) {
                    if (typeof data.output !== 'undefined' && typeof data.button_id !== 'undefined') {

                        var parent = $('#' + data.button_id).closest('.js-wpcf-access-type-item');

                        $.each(data.output, function (index, value) {
                            object = parent.find('input[id*="_permissions_' + index + '_' + value + '_role"]');
                            object
                                    .trigger('click')
                                    .prop('checked', true);
                        });
                    }
                }
            }
        });
        return false;
    };

    wpcfAccess.ApplyLevels = function (object) {
	var data_for_events = {
		section: 'custom-roles'
	};
    $.ajax({
        url:		ajaxurl,
        type:		'POST',
        dataType:	'json',
        data:		object.closest('.js-access-custom-roles-selection').find('.wpcf-access-custom-roles-select').serialize() +
        '&wpnonce=' + wpcf_access_dialog_texts.otg_access_general_nonce + '&action=wpcf_access_ajax_set_level',
        beforeSend:	function() {
                $('#wpcf-access-custom-roles-table-wrapper').css('opacity', 0.5);
            },
        success:	function( response ) {
			if ( response.success ) {
				$( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
				$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
				$( document ).trigger( 'js_event_types_access_custom_roles_updated' );
			} 
            }
        });
        return false;
    };


    wpcfAccess.enableElement = function ($obj) {
        if ($obj.data('isPrimary')) {
            $obj.addClass('button-primary');
        }
        if ($obj.data('isSecondary')) {
            $obj.addClass('button-secondary');
        }
        $obj
                .prop('disabled', false)
                .prop('readonly', false);
    };

    wpcfAccess.disableElement = function ($obj) {
        if ($obj.data('isPrimary')) {
            $obj
                    .removeClass('button-primary')
                    .addClass('button-secondary');
        }
        $obj.prop('disabled', true);
    };

    wpcfAccess.EnableTableInputs = function ($inputs, $container) {
        $container.addClass('is-enabled');
        $.each($inputs, function () {
            wpcfAccess.enableElement($(this));
        });

    };

    wpcfAccess.DisableTableInputs = function ($inputs, $container) {
        $container.removeClass('is-enabled');
        $.each($inputs, function () {
            wpcfAccess.disableElement($(this));
        });
    };



// Enable/Disable inputs
    $(document).on('change', '.js-wpcf-enable-access, .js-wpcf-follow-parent', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $tableInputs = $container.find('table :checkbox, table input[type=text]');

        $tableInputs = $tableInputs.filter(function () { // All elements except 'administrator' role checkboxes
            return ($(this).val() !== 'administrator');
        });

        if ($(this).is('.js-wpcf-enable-access')) {
            if (checked) {

                wpcfAccess.EnableTableInputs($tableInputs, $container);
                wpcfAccess.enableElement($container.find('.js-wpcf-follow-parent'));
                $container.find('.js-wpcf-access-reset').prop('disabled', false);
            } else {
                $container.find('.js-wpcf-access-reset').prop('disabled', true);
                wpcfAccess.DisableTableInputs($tableInputs, $container);
                wpcfAccess.disableElement($container.find('.js-wpcf-follow-parent'));

            }
        } else if ($(this).is('.js-wpcf-follow-parent')) {
            if (checked) {
                $container.find('.js-wpcf-access-reset').prop('disabled', true);
                wpcfAccess.DisableTableInputs($tableInputs, $container);
            } else {
                $container.find('.js-wpcf-access-reset').prop('disabled', false);
                wpcfAccess.EnableTableInputs($tableInputs, $container);
            }
        }
    });

// Set hidden input val and show/hide messages
    $(document).on('change', '.js-wpcf-enable-access', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $hiddenInput = $container.find('.js-wpcf-enable-set');
        var $message = $container.find('.js-warning-fallback');
        var $depMessage = $container.find('.dep-message');

        if (checked) {

            $hiddenInput.val($(this).val());
            $message.hide();
        } else {

            $hiddenInput.val('not_managed');
            $message.fadeIn('fast');
            $depMessage.hide();
        }
    });

    $(document).on('change', '.js-wpcf-enable-languageaccess', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $hiddenInput = $container.find('.js-wpcf-enable-wpml-language-permissions');

        if (checked) {
            $hiddenInput.val($(this).val());
        } else {

            $hiddenInput.val('disabled');
        }
    });


// Auto check/uncheck checkboxes
    wpcfAccess.AutoThick = function (object, cap, name) {
        var thick = new Array();
        var thickOff = new Array();
        var active = object.is(':checked');
        var role = object.val();
        var cap_active = 'wpcf_access_dep_true_' + cap;
        var cap_inactive = 'wpcf_access_dep_false_' + cap;
        var message = new Array();

        if (active) {
            if (typeof window[cap_active] != 'undefined') {
                thick = thick.concat(window[cap_active]);
            }
        } else {
            if (typeof window[cap_inactive] != 'undefined') {
                thickOff = thickOff.concat(window[cap_inactive]);
            }
        }

        // FIND DEPENDABLES
        //
        // Check ONs
        $.each(thick, function (index, value) {
            object.parents('table').find(':checkbox').each(function () {

                if ($(this).attr('id') != object.attr('id')) {

                    if ($(this).val() == role && $(this).hasClass('wpcf-access-' + value)) {
                        // Mark for message
                        if ($(this).is(':checked') == false) {
                            message.push($(this).data('wpcfaccesscap'));
                        }
                        // Set element form name
                        $(this).attr('checked', 'checked').attr('name', $(this).data('wpcfaccessname'));
                        wpcfAccess.ThickTd($(this), 'prev', true);
                    }
                }
            });
        });

        // Check OFFs
        $.each(thickOff, function (index, value) {
            object.parents('table').find(':checkbox').each(function () {

                if ($(this).attr('id') != object.attr('id')) {

                    if ($(this).val() == role && $(this).hasClass('wpcf-access-' + value)) {

                        // Mark for message
                        if ($(this).is(':checked')) {
                            message.push($(this).data('wpcfaccesscap'));
                        }
                        $(this).removeAttr('checked').attr('name', 'dummy');

                        // Set element form name
//                    var prevSet = $(this).parent().prev().find(':checkbox');
                        var prevSet = $(this).closest('td').prev().find(':checkbox');

                        if (prevSet.is(':checked')) {
                            prevSet.attr('checked', 'checked').attr('name', prevSet.data('wpcfaccessname'));
                        }
                        wpcfAccess.ThickTd($(this), 'next', false);
                    }
                }
            });
        });

        // Thick all checkboxes
        wpcfAccess.ThickTd(object, 'next', false);
        wpcfAccess.ThickTd(object, 'prev', true);

        // SET NAME
        //
        // Find previous if switched off
        if (object.is(':checked')) {
            object.attr('name', name);

        } else {
            object.attr('name', 'dummy');
            object
                    .closest('td')
                    .prev()
                    .find(':checkbox')
                    .attr('checked', 'checked')
                    .attr('name', name);
        }
        // Set true if admnistrator
        if (object.val() == 'administrator') {
            object
                    .attr('name', name)
                    .attr('checked', 'checked');
        }

        // Alert
        wpcfAccess.DependencyMessageShow(object, cap, message, active);
    }

    wpcfAccess.ThickTd = function (object, direction, checked) {
        if (direction == 'next') {
            var cbs = object
                    .closest('td')
                    .nextAll('td')
                    .find(':checkbox');
        } else {
            var cbs = object
                    .closest('td')
                    .prevAll('td')
                    .find(':checkbox');
        }
        if (checked) {
            cbs.each(function () {
                $(this)
                        .prop('checked', true)
                        .prop('name', 'dummy');
//			$(this).parent().find('.wpcf-add-error-page,.error-page-name-wrap').hide();
            });
        } else {
            cbs.each(function () {
                $(this)
                        .prop('checked', false)
                        .prop('name', 'dummy');
//            $(this).parent().find('.wpcf-add-error-page,.error-page-name-wrap').attr('style','');
            });
        }
    };

    wpcfAccess.DependencyMessageShow = function (object, cap, caps, active) {
        var update_message = wpcfAccess.DependencyMessage(cap, caps, active);
        var update = object.parents('.wpcf-access-type-item').find('.dep-message');

        update.hide().html('');
        if (update_message != false) {
            update.html(update_message).show();
        }
    }

    wpcfAccess.DependencyMessage = function (cap, caps, active) {
        var active_pattern_singular = window['wpcf_access_dep_active_messages_pattern_singular'];
        var active_pattern_plural = window['wpcf_access_dep_active_messages_pattern_plural'];
        var inactive_pattern_singular = window['wpcf_access_dep_inactive_messages_pattern_singular'];
        var inactive_pattern_plural = window['wpcf_access_dep_inactive_messages_pattern_singular'];
        /*var no_edit_comments = window['wpcf_access_edit_comments_inactive'];*/
        var caps_titles = new Array();
        var update_message = false;

        $.each(caps, function (index, value) {
            if (active) {

                var key = window['wpcf_access_dep_true_' + cap].indexOf(value);
                caps_titles.push(window['wpcf_access_dep_true_' + cap + '_message'][key]);
            } else {

                var key = window['wpcf_access_dep_false_' + cap].indexOf(value);
                caps_titles.push(window['wpcf_access_dep_false_' + cap + '_message'][key]);
            }
        });

        if (caps.length > 0) {
            if (active) {
                if (caps.length < 2) {

                    var update_message = active_pattern_singular.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                } else {

                    var update_message = active_pattern_plural.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                }
            } else {
                if (caps.length < 2) {

                    var update_message = inactive_pattern_singular.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                } else {

                    var update_message = inactive_pattern_plural.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                }
            }
            update_message = update_message.replace('%dcaps', caps_titles.join('\', \''));
        }
        return update_message;
    }

// export it
    window.wpcfAccess = window.wpcfAccess || {};
    $.extend(window.wpcfAccess, wpcfAccess);
})(window, jQuery);