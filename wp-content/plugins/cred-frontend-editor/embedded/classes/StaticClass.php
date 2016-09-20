<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StaticToGlobal
 *
 * @author Franko
 */
class StaticClass {
    
    public static $_current_post_title;
    public static $_current_prefix;
    public static $_current_form_id;
    
    public static $_reset_file_values = false;
    public static $_cred_container_id;
    public static $_____friendsStatic_____ = array(/* Friend Class Hashes as keys Here.. */);

    const METHOD = 'POST';                                         // form method POST
    const PREFIX = '_cred_cred_prefix_';
    // prefix for various hidden auxiliary fields
    const NONCE = '_cred_cred_wpnonce';                            // nonce field name
    const POST_CONTENT_TAG = '%__CRED__CRED__POST__CONTENT__%';    // placeholder for post content
    const FORM_TAG = '%__CRED__CRED__FORM___FORM__%';              // 
    const DELAY = 0;

    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
    public static $_allowed_mime_types;
    public static $_mail_error = "";
    // STATIC Properties
    public static $_staticGlobal = array(
        'ASSETS_PATH' => null, // physical path to files needed for Zebra form
        'ASSETS_URL' => null, // url for this physical path
        'MIMES' => array(), // WP allowed mime types (for file uploads)
        'LOCALES' => null, // global strings localization
        'RECAPTCHA' => false, // settings for recaptcha API
        'RECAPTCHA_LOADED' => false, // flag indicating whether recaptcha API has been loaded
        'COUNT' => 0, // number of forms rendered on same page
        'CACHE' => array(), // cache rendered forms here for future reference (eg by shortcodes)
        'CSS_LOADED' => array(), // references to CSS files that have been loaded
        'CURRENT_USER' => null                                    // info about current user using the forms
    );
    public static $_username_generated = null;
    public static $_password_generated = null;
    public static $_nickname_generated = null;

    /**
     * fix single quote in value in order to be replace in cred_field shortcode
     * @param type $content
     */
    public static function fix_cred_field_shortcode_value_attribute_by_single_quote(&$content) {
        $what = array();
        $to = array();
        preg_match_all("/\[cred_field(.*?)\]/is", $content, $matches, PREG_PATTERN_ORDER);
        for ($i = 0; $i < count($matches[1]); $i++) {
            preg_match("/value\=[\'|\"](.*?)[\'|\"][ a-z]{1,}\=| \]/is", $matches[1][$i], $submatches);
            if (isset($submatches[1]) && !empty($submatches[1])) {
                $tmp = str_replace("'", "@_cred_rsq_@", $submatches[1]);
                $what[] = "value='" . $submatches[1] . "'";
                $to[] = "value='" . $tmp . "'";
            }
        }
        $content = str_replace($what, $to, $content);
    }
    
    public static function cred_empty_array() {
        return array();
    }

    public static function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // to check ip is pass from proxy, also could be used ['HTTP_X_REAL_IP ']
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function get_draft_users() {
        $_cred_user_orders = get_option("_cred_user_orders", "");
        if (!isset($_cred_user_orders) || empty($_cred_user_orders))
            $_cred_user_orders = array();

        if (!empty($_cred_user_orders))
            $_cred_user_orders = unserialize(self::decrypt($_cred_user_orders));

        return $_cred_user_orders;
    }

    public static function create_temporary_user_from_draft($post_id, $order_id = null) {
        cred_log("create_temporary_user_from_draft");
        cred_log($post_id);
        global $wpdb;

//        $_cred_user_orders = get_option("_cred_user_orders", "");
//        if (!isset($_cred_user_orders) || empty($_cred_user_orders))
//            $_cred_user_orders = array();
//
//        if (!empty($_cred_user_orders))
//            $_cred_user_orders = unserialize(StaticClass::decrypt($_cred_user_orders));

        $cred_user_orders = self::get_draft_users();

        if (isset($_cred_user_orders[$post_id])) {

            $data = $_cred_user_orders[$post_id];
            cred_log($data);
            $userdata = $data['userdata'];
            $user_role = is_array($userdata['user_role']) ? $userdata['user_role'] : json_decode($userdata['user_role'], true);
            $user_role = $user_role[0];

            unset($userdata['user_role']);
            unset($userdata['ID']);

            $model = CRED_Loader::get('MODEL/UserForms');
            $real_post_id = $model->addUser($data['userdata'], $data['usermeta'], $data['fieldsInfo'], $data['removed_fields']);
            cred_log($real_post_id);
            if ($order_id != null) {
                $sql = 'SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_value="' . $post_id . '" and post_id = ' . $order_id;
                cred_log($sql);
                $metas = $wpdb->get_results($sql);
                foreach ($metas as $meta) {
                    cred_log($meta);
                    //$mkey = substr($meta->meta_key, 1, strlen($meta->meta_key));
                    //update_user_meta($meta->post_id, $meta->meta_key, $real_post_id);
                    update_post_meta($meta->post_id, $meta->meta_key, $real_post_id);
                }
            }

            return $real_post_id;
        }
        return -1;
    }

    public static function delete_temporary_user($user_id) {
        cred_log("delete_temporary_user " . $user_id);
        global $wpdb;
        $ret1 = $wpdb->query(
                $wpdb->prepare(
                        "
                DELETE FROM $wpdb->usermeta
		 WHERE user_id = %d		
		", $user_id
                )
        );
        $ret2 = $wpdb->query(
                $wpdb->prepare(
                        "
                DELETE FROM $wpdb->users
		 WHERE ID = %d		
		", $user_id
                )
        );
    }

    public static function delete_all_draft_users() {
        update_option("_cred_user_orders", "");
        $cred_user_orders = self::get_draft_users();
        return empty($cred_user_orders);
    }

    public static function unesc_meta_data($data) {
        //reverse special escape for meta data to prevent serialize eliminate CRLF (\r\n)
        if (is_array($data) || is_object($data)) {
            foreach ($data as $ii => $data_val) {
                if (is_object($data))
                    $data->$ii = self::unesc_meta_data($data_val);
                elseif (is_array($data))
                    $data[$ii] = self::unesc_meta_data($data_val);
            }
        } else
            $data = preg_replace('/%%CRED_NL%%/', "\r\n", $data);
        return $data;
    }

    public static function get_current_user_role() {
        global $current_user;
        $user_roles = $current_user->roles;
        $user_role = array_shift($user_roles);
        return $user_role;
    }

    public static function my_cred_exclude($post_types) {
        $post_types[] = CRED_FORMS_CUSTOM_POST_NAME;
        $post_types[] = CRED_USER_FORMS_CUSTOM_POST_NAME;
        return $post_types;
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateUsername($email) {
        $username = sanitize_user(current(explode('@', $email)), true);

        // Ensure username is unique
        $append = 1;
        $o_username = $username;

        while (username_exists($username)) {
            $username = $o_username . $append;
            $append ++;
        }
        return $username;
    }

    public static function cf_sanitize_values_on_save($value) {
        if (current_user_can('unfiltered_html')) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $val = self::cf_sanitize_values_on_save($val);
                }
            } else {
                $value = wp_filter_post_kses($value);
            }
        } else {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $val = self::cf_sanitize_values_on_save($val);
                }
            } else {
                $value = wp_filter_kses($value);
            }
        }
        return $value;
    }

    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
    public static function cred__add_custom_mime_types($mimes) {
        return StaticClass::$_allowed_mime_types = array_merge($mimes, StaticClass::$_allowed_mime_types);
    }

    public static function _pre($v) {
        echo "<pre>";
        print_r($v);
        echo "</pre>";
    }

    public static function parseFriendCallStatic($the) {
        $what = explode('_1_1_1_', $the);
        if (isset($what[0]) && isset($what[1])) {
            $hash = $what[0];
            $whatExactly = $what[1];
            $ref = false;
            if ($whatExactly && '&' == $whatExactly[0]) {
                $ref = true;
                $whatExactly = substr($whatExactly, 1);
            }
            return array($hash, $whatExactly, $ref);
        }
        return array(false, false, false);
    }

    public static function __getPrivStatic($prop) {
        return self::$$prop;
    }

    public static function &__getPrivStaticRef($prop) {
        return self::$$prop;
    }

    public static function encrypt($string) {
        return self::crypt('encrypt', $string);
    }

    public static function decrypt($string) {
        return self::crypt('decrypt', $string);
    }

    private static function crypt($action, $string) {
        if (!isset($string) || empty($string))
            return $string;
        $output = false;

        $encrypt_method = "AES-256-CBC";
        $secret_key = 'sdfasdfasdfsdfwewr22r2r2323342342323234';
        $secret_iv = 'asdccasdefw3434r34r335f345524r';

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            if (function_exists("openssl_encrypt")) {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                $output = base64_encode($output);
            } else {
                $output = base64_encode($string);
            }
        } else if ($action == 'decrypt') {
            if (function_exists("openssl_decrypt"))
                $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            else
                $output = base64_decode($string);
        }

        return $output;
    }

}

?>
