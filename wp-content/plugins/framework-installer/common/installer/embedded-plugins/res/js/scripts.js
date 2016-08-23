var Installer_Embedded_Plugins = {

    submit_button: jQuery('form[name=installer_ep_form] :submit'),
    postpone_button: jQuery('#installer_ep_postpone'),

    init: function(){

        jQuery('form[name=installer_ep_form]').submit(Installer_Embedded_Plugins.run);

        jQuery('form[name=installer_ep_form] .installer_ep_step:checkbox').change(Installer_Embedded_Plugins.validate);

        jQuery('#installer_ep_postpone').click(Installer_Embedded_Plugins.postpone_setup);
        jQuery('#installer_ep_postponed_resume').click(Installer_Embedded_Plugins.resume_setup);


    },

    run: function(){

        var submit_button = Installer_Embedded_Plugins.submit_button;
        var postpone_button = Installer_Embedded_Plugins.postpone_button;
        var default_submit_label = submit_button.val();

        submit_button.val(submit_button.data('alt-name'));
        submit_button.data('alt-name', default_submit_label);

        submit_button.attr('disabled', 'disabled');
        postpone_button.attr('disabled', 'disabled');

        jQuery('form[name=installer_ep_form] .installer_ep_step:checkbox').attr('disabled', 'disabled');


        Installer_Embedded_Plugins.run_action()

        return false;

    },

    run_action: function(repeat){

        if(typeof repeat == 'undefined'){
            repeat = 0;
        }

        var checkbox = jQuery('form[name=installer_ep_form] .installer_ep_step:checked').eq(0);


        if(!checkbox.length){
            var step = '__finalize__';
        }else{
            var step = checkbox.attr('name');
            var list_item = checkbox.closest('li');
            list_item.addClass('installer_ep_progress_li');
            checkbox.hide();
        }
        var nonce = jQuery('form[name=installer_ep_form] input[name=nonce]').val();

        var installer_instance = jQuery('#installer_ep_instance').length ?  jQuery('#installer_ep_instance').val() : '';

        jQuery.ajax({
            url:        ajaxurl,
            type:       'post',
            dataType:   'json',
            data:       {action: 'installer_ep_run', nonce: nonce, step: step, repeat: repeat, installer_instance: installer_instance},
            success: function(ret) {
                if(ret.continue){
                    if(typeof ret.repeat == 'undefined'){
                        list_item.addClass('installer_ep_check_li');
                        checkbox.remove();
                        repeat = 0;
                    }else{
                        repeat = ret.repeat;
                    }
                    Installer_Embedded_Plugins.run_action(repeat);
                }else{
                    if(ret.error){
                        if(list_item.length){
                            list_item.addClass('installer_ep_error_li');
                            list_item.append('<p class="installer-status-error">'+ ret.error + '</p>');
                        }

                        Installer_Embedded_Plugins.postpone_button.removeAttr('disabled');

                    }else{

                        jQuery('#installer_ep_setup_actions').hide();
                        jQuery('#installer_ep_setup_complete').fadeIn();


                    }

                }


            }

        });

    },

    validate: function(){

        var checkbox = jQuery(this);
        var name = checkbox.attr('name');
        var checked = jQuery(this).attr('checked') == 'checked';

        var checkboxes_states = {}
        var checkboxes = {}
        jQuery('form[name=installer_ep_form] .installer_ep_step:checkbox').each(function(){
            checkboxes_states[jQuery(this).attr('name')] = jQuery(this).attr('checked') == 'checked';
            checkboxes[jQuery(this).attr('name')] = jQuery(this);
        });

        if(name == 'install'){

            if(!checked){
                checkboxes.activate.removeAttr('checked');
                checkboxes.configure.removeAttr('checked');
                checkboxes.sample_content.removeAttr('checked');
                checkboxes.default_settings.removeAttr('checked');
                checkboxes.layout_content.removeAttr('checked');
            }

        }else if(name == 'activate'){

            if(!checked){
                checkboxes.configure.removeAttr('checked');
                checkboxes.sample_content.removeAttr('checked');
                checkboxes.default_settings.removeAttr('checked');
                checkboxes.layout_content.removeAttr('checked');
            }else{
                if(!checkboxes_states.install){ checkboxes.install.attr('checked', 'checked'); }
            }

        }else if(name == 'configure'){

            if(!checked){
                checkboxes.sample_content.removeAttr('checked');
                checkboxes.default_settings.removeAttr('checked');
                checkboxes.layout_content.removeAttr('checked');
            }else{
                if(!checkboxes_states.install){ checkboxes.install.attr('checked', 'checked'); }
                if(!checkboxes_states.activate){ checkboxes.activate.attr('checked', 'checked'); }
            }

        }


    },

    postpone_setup: function(){

        var nonce = jQuery('form[name=installer_ep_form] input[name=nonce]').val();

        jQuery.ajax({
            url:        ajaxurl,
            type:       'post',
            dataType:   'json',
            data:       {action: 'installer_ep_postpone_setup', nonce: nonce},
            success: function(ret) {

                jQuery('#installer_ep_form_wrap').hide();
                jQuery('#installer_ep_postponed_wrap1').show();


            }

        });

        return false;
    },

    resume_setup: function(){

        var nonce = jQuery('form[name=installer_ep_form] input[name=nonce]').val();

        jQuery.ajax({
            url:        ajaxurl,
            type:       'post',
            dataType:   'json',
            data:       {action: 'installer_ep_resume_setup', nonce: nonce},
            success: function(ret) {

                jQuery('#installer_ep_form_wrap').show();
                jQuery('#installer_ep_postponed_wrap1').hide();

            }

        });

        return false;
    }




}

Installer_Embedded_Plugins.init();

















