function check_current_cred_post_id() {
    if (jQuery("input[name='_cred_cred_prefix_post_id']") &&
            jQuery("input[name='_cred_cred_prefix_form_id']")) {
        var _form_id = jQuery("input[name='_cred_cred_prefix_form_id']").val();
        var _post_id = jQuery("input[name='_cred_cred_prefix_post_id']").val();
        if (_post_id && _post_id != '' &&
                _form_id && _form_id != '') {
            var data = {
                action: 'check_post_id',
                post_id: _post_id,
                form_id: _form_id,
            };

            if (jQuery(".wpt-form-submit")) {
                jQuery(".wpt-form-submit").attr("disabled", true);
            }

            jQuery.post(MyAjax.ajaxurl, data, function (pid) {
                if (pid)
                    jQuery("input[name='_cred_cred_prefix_post_id']").val(pid);

                if (jQuery(".wpt-form-submit")) {
                    jQuery(".wpt-form-submit").attr("disabled", false);
                }
            });
        }
    }
}

jQuery(document).ready(check_current_cred_post_id);