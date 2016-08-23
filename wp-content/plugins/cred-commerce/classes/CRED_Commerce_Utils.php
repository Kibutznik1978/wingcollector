<?php

/**
 *
 *   Glue plugin for woocommerce, should work for both 1.x and 2.0.x versions of woocommerce
 *
 * */
final class CRED_Commerce_Utils {

    public static function is_user_form($form_id) {
        $type = get_post_type($form_id);
        return ($type == CRED_USER_FORMS_CUSTOM_POST_NAME);
    }

}
