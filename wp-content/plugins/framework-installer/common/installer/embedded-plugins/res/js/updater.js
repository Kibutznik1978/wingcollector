var Toolset_Updater = Toolset_Updater || {};

Toolset_Updater.Main = function ($) {
    "use strict"

    var self = this,
        $form = $('#toolset-updater-form'),
        $resume = $('#toolset_updater_postponed_resume'),
        $inner_wrap = $('.updater-nag-wrap'),
        $postpone = $('.postpone-updater');

    self.SETTINGS = UPDATER_SETTINGS;
    self.PLUGINS_DATA = self.SETTINGS.plugins_data;
    self.NONCE = self.SETTINGS.get_updater_nonce;

    self.init = function () {
        $postpone.on( 'click', self.postpone_setup );
        self.handle_submit();
        $resume.on( 'click', self.resume_setup );
    };

    self.handle_submit = function () {
        $form.on('submit', function (event) {

            var send = {
                action: 'toolset_get_updater',
                get_updater_nonce: self.NONCE,
                data: JSON.stringify( self.process_form_data( $(this).serializeArray() ) )
            };

            self.do_ajax(send, {
                success:function(data){
                    //console.log( data );
                    //TODO: message to the user and hide the form
                    //jQuery('.installer-ep-update-wrap').slideUp(400).remove();
                    if( data.message ){
                        $inner_wrap.empty().append('<h3>'+data.message+'</h3>');
                        window.scrollTo( 0, 0 );
                    }
                }
            });

            event.preventDefault();
        });
    };

    self.process_form_data = function (data) {
        var temp = {};

        _.each(data, function (item) {
            var value = JSON.parse(item['value']),
                plugin = value['plugin'];

            if (typeof temp[plugin] === 'undefined') {
                temp[plugin] = [];
            }

            temp[plugin].push(value);
        });
        return temp;
    };

    self.postpone_setup = function(event){
        event.preventDefault();

        var nonce = self.NONCE;

        self.do_ajax( {action: 'toolset_updater_postpone_setup', nonce: nonce}, {
            success:function(response) {

                jQuery('.installer-ep-update-wrap').slideUp();
                jQuery('#installer_ep_postponed_wrap').slideDown();

            }
        } );
    };

    self.resume_setup = function(event){
        event.preventDefault();
        var nonce = self.NONCE;

        self.do_ajax( {action: 'toolset_updater_resume_setup', nonce: nonce}, {
            success: function(response) {

                jQuery('.installer-ep-update-wrap').slideDown();
                jQuery('#installer_ep_postponed_wrap').slideUp();

            }
        } );
    };

    self.do_ajax = function( params, callback_object ){
        $.post(ajaxurl, jQuery.param( params ), function (response) {
            if ((typeof(response) !== 'undefined') && response !== null && ( response.message || response.Data )) {
                if( callback_object && callback_object.success && typeof callback_object.success == 'function'  )
                    callback_object.success.call( this, response, params );
            }
            else if ((typeof(response) !== 'undefined') && response !== null && response.error) {
                console.error(response.error);

            }
        }, 'json')
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Ajax call failed', textStatus, errorThrown)
            })
            .always(function () {
                //console.log(arguments);
            });
    };

    self.init();
};


;(function ($) {
    Toolset_Updater.Main.call( {}, $ );
}(jQuery));