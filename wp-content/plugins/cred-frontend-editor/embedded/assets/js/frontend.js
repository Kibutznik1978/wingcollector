function check_current_cred_post_id() {
    if (jQuery(document).find("input[name='_cred_cred_prefix_post_id']").length &&
            jQuery(document).find("input[name='_cred_cred_prefix_form_id']").length) {
        
        var _document_forms = jQuery(document).find("input[name='_cred_cred_prefix_form_id']");
        var _document_prefix_post_id = jQuery(document).find("input[name='_cred_cred_prefix_post_id']");
        
        for(var _form in _document_forms){
            if(isNaN(_form))
                break
                
            var _form_id = jQuery(_document_forms[_form]).val();
            var _post_id = jQuery(_document_prefix_post_id[_form]).val();
            
            if (_post_id && _post_id != '' &&
                    _form_id && _form_id != '') {
                var data = {
                    action: 'check_post_id',
                    post_id: _post_id,
                    form_id: _form_id,
                    form_index: _form
                };
    
                if (jQuery(".wpt-form-submit")) {
                    jQuery(".wpt-form-submit").attr("disabled", true);
                }
    
                jQuery.post(MyAjax.ajaxurl, data, function (form_data) {
                    form_data = {};
                    var pid, form_index = null;
                    
                    if(form_data !== null && form_data !== undefined && form_data.length > 0)
                        form_data = JSON.parse(form_data);
                    
                    if(form_data.hasOwnProperty("pid"))
                        pid = form_data.pid;
                    
                    if(form_data.hasOwnProperty("form_index"))
                        form_index = form_data.form_index;
                    
                    if (pid !== null && form_index !== null)
                        jQuery(_document_prefix_post_id[form_index]).val(pid);
    
                    if (jQuery(".wpt-form-submit")) {
                        jQuery(".wpt-form-submit").attr("disabled", false);
                    }
                });
            }
        }
       
    }
}

jQuery(document).ready(function(){
    check_current_cred_post_id(); 
    jQuery(document).on('js_event_cred_ajax_form_response_completed', function(){
        if(jQuery.fn.iris){
            jQuery('input.js-wpt-colorpicker').iris();
        }
    });
});