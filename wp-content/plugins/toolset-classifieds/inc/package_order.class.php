<?php

if ( !class_exists( 'Toolset_Classifieds_PackageOrder' ) ) {

    require_once TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/toolset-classifieds.class.php';

    class Toolset_Classifieds_PackageOrder extends Toolset_Classifieds
    {

        function __construct() {
            /* ad package processing */
            add_shortcode('classifieds-return-available-ad-credits', array($this, 'classifieds_return_available_ad_credits'), 10, 1);
            add_shortcode('classifieds-return-active-package', array($this, 'classifieds_func_return_active_package_of_current_user'), 10, 1);

            /**Hook updated**/
            add_action('cred_save_data_form_add-new-ad-package', array($this, 'classifieds_cred_save_data_add_new_package_order_function'), 10, 2);

            /**Hook updated**/
            add_action('cred_commerce_after_order_completed_form_add-new-ad-package', array($this, 'classifieds_save_user_fields_package_order'), 10, 1);
            add_action('woocommerce_after_my_account', array($this, 'classifieds_show_ad_credits_on_account_page'), 10, 1);

            /**Hook OK**/
            add_action('cred_submit_complete_form_add-another-premium-ad', array($this, 'classifieds_cred_submit_complete_add_another_premium_ad'), 10, 2);

            /**Hook OK**/
            add_action('cred_commerce_after_send_notifications_form_add-another-premium-ad', array($this, 'classifieds_cred_commerce_after_send_notifications_add_another_premium_ad'), 10, 1);
        }

        /**
         * ad package processing specific functions
         *
         **/
        public function classifieds_cred_save_data_add_new_package_order_function($post_id, $form_data) {
            //create an automatic title and slug using a given phrase and the ID of the post we are creating
            $title = 'Package Order #' . $post_id;
            if (isset($slug)) {
                wp_update_post(array('ID' => $post_id, 'post_title' => $title, 'post_name' => $slug));
            } else {
                wp_update_post(array('ID' => $post_id, 'post_title' => $title));
            }
        }

        public function classifieds_cred_submit_complete_add_another_premium_ad($post_id, $form_data) {
            /* Update available credits for the Ad Package user when submitting a listing */
            //Get user ID
            global $current_user;
            $user_id = $current_user->ID;

            //Verify that this form submission is from an ad package client
            $ad_package_client_status = $this->classifieds_verify_if_user_is_ad_package_client($user_id);
            if ($ad_package_client_status == 'yes') {
                //Retrieve available ad credits
                $user_available_ad_credits = $this->classifieds_return_available_ad_credits($user_id);
                //Reduce credit by one count after posting one ad
                $updated_ad_credits = $user_available_ad_credits - 1;
                //Update back the credits
                $success_updating_credits = update_user_meta($user_id, 'wpcf-user-total-available-ad-credits', $updated_ad_credits);
                //Reduce credit of the package added
                $success_retrieving_existing_packages_for_this_user_array = get_user_meta($user_id, 'wpcf-customer-active-packages', FALSE);
                if ((is_array($success_retrieving_existing_packages_for_this_user_array)) && (!(empty($success_retrieving_existing_packages_for_this_user_array)))) {
                    $ad_package_id_for_processsing = reset($success_retrieving_existing_packages_for_this_user_array);
                    //Get ad credit for this package
                    $success_retrieving_credits = get_post_meta($ad_package_id_for_processsing, 'wpcf-package-number-of-ads-allowed', TRUE);
                    $updated_package_credit = $success_retrieving_credits - 1;
                    if ($updated_package_credit < 0) {
                        $updated_package_credit = 0;
                    }
                    //Update back
                    $success_updating_credits_back = update_post_meta($ad_package_id_for_processsing, 'wpcf-package-number-of-ads-allowed', $updated_package_credit);

                    /*Update in WPML 3.1.4+, ensure that field is duplicated to translated version*/
                    $this->_classifieds_copy_cf_values_to_translations($ad_package_id_for_processsing, $updated_package_credit, 'wpcf-package-number-of-ads-allowed', 'package');

                    if ($updated_package_credit == 0) {
                        //Remove this package since it run out of credits
                        $success_updating_package_to_user = delete_user_meta($user_id, 'wpcf-customer-active-packages', $ad_package_id_for_processsing);
                        //reorder Types custom field array
                        $active_packages_sort_order = get_user_meta($user_id, '_wpcf-customer-active-packages-sort-order', TRUE);
                        array_shift($active_packages_sort_order);
                        $success_updating_active_packages_sort_order = update_user_meta($user_id, '_wpcf-customer-active-packages-sort-order', $active_packages_sort_order);
                    }
                }
            }

            //duplicate post to WPML translation
            $this->_classifieds_duplicate_on_publish($post_id);

        }

        /*Save user fields data to database after successful order*/
        public function classifieds_save_user_fields_package_order($data) {
            /*Order completed*/
            if ((is_array($data)) && (!(empty($data)))) {
                //Get processed product ID from the order $data array
                if (isset($data['extra_data'][0]['cred_product_id'])) {
                    $processed_product_id = $data['extra_data'][0]['cred_product_id'];
                    $processed_package_post_id = $data['extra_data'][0]['cred_post_id'];
                    //Retrieve customer user ID
                    if (isset($data['user_id'])) {
                        $customer_user_id = $data['user_id'];
                        //Get the latest maximum ad credits assigned by administrator for an ad package woocommerce product
                        $max_ad_credits = get_post_meta($processed_product_id, 'wpcf-number-of-ads', TRUE);
                        //Associate this maximum ad credits to the completed order
                        $current_ad_credits_after_completed_order = $max_ad_credits;
                        //Store the ad credits in the purchased package.
                        $success_updating_credits = update_post_meta($processed_package_post_id, 'wpcf-package-number-of-ads-allowed', $current_ad_credits_after_completed_order);
                        //Mark status as ad package customer
                        $success_updating_credits = update_user_meta($customer_user_id, 'wpcf-ad-package-customer', 'yes');
                        //Retrieve any existing customer active packages if any
                        $success_retrieving_existing_packages_for_this_user_array = get_user_meta($customer_user_id, 'wpcf-customer-active-packages', FALSE);
                        if (empty($success_retrieving_existing_packages_for_this_user_array)) {
                            $success_retrieving_existing_packages_for_this_user_array = array();
                        }
                        $success_retrieving_existing_packages_for_this_user_array[] = (string)$processed_package_post_id;
                        //Delete all to add all
                        $success_delete_all_packages_to_user = delete_user_meta($customer_user_id, 'wpcf-customer-active-packages');
                        $active_packages_sort_order = array();
                        foreach ($success_retrieving_existing_packages_for_this_user_array as $key => $package_id) {
                            $success_add_package_mid_to_user = add_user_meta($customer_user_id, 'wpcf-customer-active-packages', $package_id, FALSE);
                            $active_packages_sort_order[$key] = $success_add_package_mid_to_user;
                        }
                        $success_updating_active_packages_sort_order = update_user_meta($customer_user_id, '_wpcf-customer-active-packages-sort-order', $active_packages_sort_order);
                        //Associate customer user_id to package
                        $success_inserting_custom_id_to_package = update_post_meta($processed_package_post_id, '_classifieds_ad_package_customer_id', $customer_user_id);
                        //Store original ad quantity after purchase and associate this with the package created
                        $this->classifieds_store_original_ad_quantity_to_package($processed_package_post_id, $max_ad_credits);
                        //Retrieve user existing total available ad credits
                        $success_retrieving_existing_user_total_credits = get_user_meta($customer_user_id, 'wpcf-user-total-available-ad-credits', TRUE);
                        if (empty($success_retrieving_existing_user_total_credits)) {
                            $success_retrieving_existing_user_total_credits = 0;
                        }
                        $updated_total_user_credits_after_purchase = $success_retrieving_existing_user_total_credits + $max_ad_credits;
                        //Update user total available number of ad credits
                        $success_updating_total_ad_credits = update_user_meta($customer_user_id, 'wpcf-user-total-available-ad-credits', $updated_total_user_credits_after_purchase);
                        //duplicate post to WPML translation
                        $this->_classifieds_duplicate_on_publish($processed_package_post_id);
                    }
                }
            }
        }

        public function classifieds_cred_commerce_after_send_notifications_add_another_premium_ad($data) {
            //check if the status of the WooCommerce order is completed
            if (isset($data['new_status']) && 'completed' == $data['new_status']) {
                //duplicate post to WPML translation
                $this->_classifieds_duplicate_on_publish($data['cred_meta'][0]['cred_post_id']);
            }
        }

        /*Function to compute available ad credits*/
        public function classifieds_return_available_ad_credits($user_id = '') {
            if (empty($user_id)) {
                global $current_user;
                //Get user_id
                $user_id = $current_user->ID;
            }
            //Get number of ad package credits for this user
            $ad_credits_available = get_user_meta($user_id, 'wpcf-user-total-available-ad-credits', TRUE);

            $out = '0';
            if ($ad_credits_available){
                $out = $ad_credits_available;
            }

            return $out;

        }

        //Store original ad quantity after purchase and associate this with the package created
        public function classifieds_store_original_ad_quantity_to_package($post_id, $max_ad_credits) {

            $success_updating_original_ad_qty = update_post_meta($post_id, 'wpcf-original-number-of-ads', $max_ad_credits);

        }

        /* Display ad credits information for ad package clients in WooCommerce My Account Settings */
        public function classifieds_show_ad_credits_on_account_page()
        {
            global $current_user;
            //Get user_id
            $user_id = $current_user->ID;
            //Check if this user is an ad package client
            $ad_package_client_status = $this->classifieds_verify_if_user_is_ad_package_client();
            //Only show ad credits section for Ad package Clients in WooCommerce My Account settings
            if ($ad_package_client_status == 'yes') {
                //Get number of ad package credits for this user
                $ad_credits_available = $this->classifieds_return_available_ad_credits($user_id);
                ?>
                <h2><?php _e('Ad Package Credits Available', 'toolset_classifieds'); ?></h2>
                <p><?php _e("Your total number of ad credits available is:", "toolset_classifieds"); ?></p>
                <p><strong><?php echo $ad_credits_available ?></strong></p>
            <?php
            }
        }

        //Return the active package title of the user by id
        public function classifieds_func_return_active_package_of_current_user()
        {

            global $current_user;

            if (isset($current_user->ID)) {
                //Get user_id
                $user_id = $current_user->ID;

                if ('yes' == classifieds_verify_if_user_is_ad_package_client($user_id)) {

                    $success_retrieving_existing_packages_for_this_user_array = get_user_meta($user_id, 'wpcf-customer-active-packages', FALSE);

                    if ((is_array($success_retrieving_existing_packages_for_this_user_array)) && (!(empty($success_retrieving_existing_packages_for_this_user_array)))) {

                        $ad_package_id_for_processsing = reset($success_retrieving_existing_packages_for_this_user_array);
                        $ad_package_id_for_processsing_title = get_the_title($ad_package_id_for_processsing);
                        return $ad_package_id_for_processsing_title;
                    }
                }
            }
            return __("none", "toolset_classifieds");
        }

        /**
         * API functions for Ad Package processing
         *
         **/

        /*Verify if user is ad package client */
        public function classifieds_verify_if_user_is_ad_package_client($user_id = '', $type = '', $object = NULL) {
            $user_id = intval($user_id);
            if (empty($user_id)) {
                global $current_user;
                //Get user_id
                $user_id = $current_user->ID;
            }
            //Check if this user is an ad package client
            $ad_package_client_status = get_user_meta($user_id, 'wpcf-ad-package-customer', TRUE);
            if ($ad_package_client_status == 'yes') {
                return 'yes';
            } else {
                return 'no';
            }
        }


        /*Function to check if ad package subscription is valid for user*/
        public function classifieds_check_if_subscription_is_still_valid($user_id = '', $type = '', $object = NULL) {
            $user_id = intval($user_id);
            if (empty($user_id)) {
                global $current_user;
                //Get user_id
                $user_id = $current_user->ID;
            }
            $credits_valid = 'yes';
            $available_credits = $this->classifieds_return_available_ad_credits($user_id);
            if (empty($available_credits)) {
                $credits_valid = 'no';
                return $credits_valid;
            }
            return $credits_valid;
        }

        /**
         * Return the active package title of the user
         * this functions has two default parameters has defined in the evaluation function for wpv-if
         **/
        public function classifieds_func_return_active_package_of_user($type = '', $object = NULL) {

            if ('posts' == $type) {
                //Retrieve post ID
                if (isset($object->ID)) {
                    $post_id_package = $object->ID;

                    //Get user id associated with this package
                    $user_id = get_post_meta($post_id_package, '_classifieds_ad_package_customer_id', TRUE);

                    if (empty($user_id)) {

                        global $current_user;

                        //Get user_id
                        $user_id = $current_user->ID;

                    }

                    $success_retrieving_existing_packages_for_this_user_array = get_user_meta($user_id, 'wpcf-customer-active-packages', FALSE);

                    if ((is_array($success_retrieving_existing_packages_for_this_user_array)) && (!(empty($success_retrieving_existing_packages_for_this_user_array)))) {

                        $ad_package_id_for_processsing = reset($success_retrieving_existing_packages_for_this_user_array);
                        $ad_package_id_for_processsing_title = get_the_title($ad_package_id_for_processsing);
                        return $ad_package_id_for_processsing_title;
                    }
                }
            }
            return __("none", "toolset_classifieds");
        }


        public function classifieds_func_check_if_ad_package_empty($type = '', $object = NULL) {

            if ('posts' == $type && isset($object->ID)) {

                //Retrieve post ID
                $post_id_package = $object->ID;

                $success_retrieving_credits = get_post_meta($post_id_package, 'wpcf-package-number-of-ads-allowed', TRUE);

                if ($success_retrieving_credits == 0) {

                    return "empty";

                }
            }
        }
    }

}