<?php

/**
 *   CRED Commerce Form Handler
 *
 * */
final class CRED_Commerce_Form_Handler {

    private $plugin = null;
    private $form = null;
    private $model;
    private $_data = false;

    public function __construct() {
        
    }

    // dependency injection
    public function init($plugin, $model) {
        $this->model = $model;

        $this->plugin = $plugin;

        // add necessary hooks to manage the form submission
        //add_action('cred_save_data', array(&$this, 'onSaveData'), 10, 2);
        add_action('cred_submit_complete', array(&$this, 'onSubmitComplete'), 1, 2);
        add_action('cred_custom_success_action', array(&$this, 'onFormSuccessAction'), 1, 3);
        //add_action('cred_commerce_payment_complete', array(&$this, 'onPaymentComplete'), 1, 1 );
        $this->plugin->attach('_cred_commerce_order_received', array(&$this, 'onOrderReceived'));
        $this->plugin->attach('_cred_commerce_payment_failed', array(&$this, 'onPaymentFailed'));
        $this->plugin->attach('_cred_commerce_payment_completed', array(&$this, 'onPaymentComplete'));
        $this->plugin->attach('_cred_order_status_changed', array(&$this, 'onOrderChange'));
        $this->plugin->attach('_cred_commerce_order_on_hold', array(&$this, 'onHold'));
        $this->plugin->attach('_cred_commerce_payment_refunded', array(&$this, 'onRefund'));
        $this->plugin->attach('_cred_commerce_payment_cancelled', array(&$this, 'onCancel'));
        $this->plugin->attach('_cred_order_created', array(&$this, 'onOrderCreated'));
        $this->plugin->attach('_cred_commerce_order_completed', array(&$this, 'onOrderComplete'));
    }

    public function getProducts() {
        return $this->plugin->getProducts();
    }

    public function getProduct($id) {
        return $this->plugin->getProduct($id);
    }

    public function getRelativeProduct($id) {
        return $this->plugin->getRelativeProduct($id);
    }

    public function getAbsoluteProduct($id2) {
        return $this->plugin->getAbsoluteProduct($id2);
    }

    public function getCredData() {
        return $this->plugin->getCredData();
    }

    public function getNewProductLink() {
        return $this->plugin->getNewProductLink();
    }

    public function onSubmitComplete($post_id, $form_data) {
        cred_log("onSubmitComplete");
        cred_log(array($post_id, $form_data));
        // get form meta data related to cred commerce
        $this->form = $this->model->getForm($form_data['id'], false);

        if ($this->form->isCommerce) {
            // HOOKS API
            do_action('cred_commerce_before_add_to_cart', $this->form->ID, $post_id);

            // clear cart if needed
            if ($this->form->clearCart)
                $this->plugin->clearCart();

            // add product to cart
            if ('post' == $this->form->associateProduct) {
                $product = $this->model->getPostMeta($post_id, $this->form->productField);
            } else {
                if (isset($this->form->product)) {
                    $product = $this->form->product;
                } else {
                    // No product so return.
                    return;
                }
            }

            // HOOKS API allow plugins to filter the product
            $product = apply_filters('cred_commerce_add_product_to_cart', $product, $this->form->ID, $post_id);

            //if (!$product || empty($product)) return;

            $this->plugin->addTocart($product, array('cred_product_id' => $product, 'cred_form_id' => $this->form->ID, 'cred_post_id' => $post_id));

            // HOOKS API
            do_action('cred_commerce_after_add_to_cart', $this->form->ID, $post_id);
        }
    }

    public function onFormSuccessAction($action, $post_id, $form_data) {
        cred_log("onFormSuccessAction");
        cred_log(array($action, $post_id, $form_data));
        if ($this->form->ID == $form_data['id'] && $this->form->isCommerce) {
            // HOOKS API
            do_action('cred_commerce_form_action', $action, $this->form->ID, $post_id, $form_data);

            switch ($action) {
                case 'cart':
                    wp_redirect($this->plugin->getPageUri('cart'));
                    exit;
                    break;
                case 'checkout':
                    wp_redirect($this->plugin->getPageUri('checkout'));
                    exit;
                    break;
            }
        }
    }

    public function getCustomer($post_id, $form_id) {
        return $this->plugin->getCustomer($post_id, $form_id);
    }

    // trigger notifications on order created (on checkout)
    public function onOrderCreated($data) {
        $this->plugin->detach('_cred_order_created', array(&$this, 'onOrderCreated'));
        cred_log("onOrderCreated");
        cred_log($data);
        // HOOKS API
        //do_action('cred_commerce_before_send_notifications', $data);
        //cred_log($data);
        if (isset($data['cred_meta']) && $data['cred_meta']) {
            $model = CREDC_Loader::get('MODEL/Main');
            CRED_Loader::load('CLASS/Notification_Manager');
            foreach ($data['cred_meta'] as $ii => $meta) {
                if (!isset($meta['cred_form_id']))
                    continue;
                $form_id = $meta['cred_form_id'];
                $form_slug = '';
                $cred_form_post = get_post($form_id);
                if ($cred_form_post) {
                    $form_slug = $cred_form_post->post_name;
                    $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                }
                $post_id = $meta['cred_post_id'];
                $form = $model->getForm($form_id, true);

                if ($form->isCommerce && isset($form->fields['notification'])) {

                    $can = false;
                    $to_delete = -1;
                    if ($is_user_form) {
                        if (isset($form->fields['notification']->notifications)) {
                            foreach ($form->fields['notification']->notifications as $n => $notif) {
                                if ($notif['event']['type'] == 'order_created') {
                                    $can = true;
                                    break;
                                }
                            }
                        }
                        if ($can) {
                            $new_user_id = StaticClass::create_temporary_user_from_draft($post_id, $data['order_id']);

                            if ($new_user_id != -1) {
                                $to_delete = $post_id;
                                cred_log("to_delete " . $to_delete);
                                $post_id = $new_user_id;
                                $meta['cred_post_id'] = $post_id;
                                if (!function_exists("delete_temporary_user")) {

                                    function delete_temporary_user($post_id) {
                                        cred_log("cred_after_send_notifications.delete_temporary_user");
                                        StaticClass::delete_temporary_user($post_id);
                                    }

                                }
                                add_action('cred_after_send_notifications', 'delete_temporary_user', 10, 1);
                            }
                        }
                    }

                    $this->_data = array(
                        'order_id' => $data['order_id'],
                        'cred_meta' => $meta
                    );

                    add_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCreatedEvent'), 1, 4);
                    CRED_Notification_Manager::triggerNotifications($post_id, array(
                        'event' => 'order_created',
                        'form_id' => $form_id,
                        'notification' => $form->fields['notification'],
                        'customer' => $this->getCustomer($post_id, $form_id)
                    ));
                    remove_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCreatedEvent'), 1, 4);

                    if ($can && $to_delete != -1)
                        StaticClass::delete_temporary_user($post_id);

                    $this->_data = false;
                }
            }
        }

        // HOOKS API
        //do_action('cred_commerce_after_send_notifications', $data);
    }

    // trigger notifications on order status change
    public function onOrderChange($data) {
        $this->plugin->detach('_cred_order_status_changed', array(&$this, 'onOrderChange'));
        cred_log("onOrderChange");
        cred_log($data);
        // HOOKS API
        //do_action('cred_commerce_before_send_notifications', $data);
        // send notifications
        if (!isset($data['new_status']) || !in_array($data['new_status'], array('pending', 'failed', 'processing', 'completed', 'on-hold', 'cancelled', 'refunded')))
            return; // not spam with useless notifications ;)

        if (isset($data['cred_meta']) && $data['cred_meta']) {
            $model = CREDC_Loader::get('MODEL/Main');
            CRED_Loader::load('CLASS/Notification_Manager');
            foreach ($data['cred_meta'] as $ii => $meta) {
                if (!isset($meta['cred_form_id']))
                    continue;
                $form_id = $meta['cred_form_id'];
                $form_slug = '';
                $cred_form_post = get_post($form_id);
                if ($cred_form_post) {
                    $form_slug = $cred_form_post->post_name;
                    $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                }
                $post_id = $meta['cred_post_id'];
                $form = $model->getForm($form_id, true);

                if ($form->isCommerce && isset($form->fields['notification'])) {

                    $can = false;
                    $to_delete = -1;
                    if ($is_user_form &&
                            $data['previous_status'] != $data['new_status']) {

                        if (isset($form->fields['notification']->notifications)) {
                            foreach ($form->fields['notification']->notifications as $n => $notif) {
                                if ($notif['event']['type'] == 'order_modified') {
                                    cred_log($notif['event']['order_status']);
                                    cred_log($data['new_status']);
                                    $can = ($notif['event']['order_status'] == $data['new_status']);
                                    cred_log($can);
                                    if ($can)
                                        break;
                                }
                            }
                        }
                        if ($can) {
                            if ($data['new_status'] == 'completed') {
                                $order_id = isset($data['order_id']) ? $data['order_id'] : $data['transaction_id'];
                                cred_log("order $order_id");
                                cred_log("order $post_id");

                                $model = CRED_Loader::get('MODEL/UserForms');
                                $new_user_id = $model->publishTemporaryUser($post_id, $order_id);
                                $post_id = $new_user_id;
                                $meta['cred_post_id'] = $post_id;
                                cred_log("new_user_id $new_user_id");
                            } else {
                                $to_delete = StaticClass::create_temporary_user_from_draft($post_id, $data['order_id']);
                                cred_log($to_delete);
                                if ($to_delete != -1) {
                                    $post_id = $to_delete;
                                    $meta['cred_post_id'] = $post_id;
                                    if (!function_exists("delete_temporary_user")) {

                                        function delete_temporary_user($post_id) {
                                            cred_log("cred_after_send_notifications.delete_temporary_user");
                                            StaticClass::delete_temporary_user($post_id);
                                        }

                                    }
                                    add_action('cred_after_send_notifications', 'delete_temporary_user', 10, 1);
                                }
                            }
                        }

                        if ($data['previous_status'] != $data['new_status'] &&
                                $data['new_status'] == 'cancelled' &&
                                $this->form->commerce['order_cancelled']['post_status'] == 'delete') {
                            $model = CRED_Loader::get('MODEL/UserForms');
                            $new_user_id = $model->deleteTemporaryUser($post_id);
                        }
                    }

                    $this->_data = array(
                        'order_id' => $data['order_id'],
                        'previous_status' => $data['previous_status'],
                        'new_status' => $data['new_status'],
                        'cred_meta' => $meta
                    );
                    cred_log($this->_data);
                    add_filter('cred_custom_notification_event', array(&$this, 'notificationOrderEvent'), 1, 4);
                    CRED_Notification_Manager::triggerNotifications($post_id, array(
                        'event' => 'order_modified',
                        'form_id' => $form_id,
                        'notification' => $form->fields['notification']
                    ));
                    remove_filter('cred_custom_notification_event', array(&$this, 'notificationOrderEvent'), 1, 4);

                    if ($can && $to_delete != -1 && $data['new_status'] != 'completed')
                        StaticClass::delete_temporary_user($post_id);

                    $this->_data = false;
                }
            }
        }

        // HOOKS API
        do_action('cred_commerce_after_send_notifications_form_' . $form_slug, $data);
        do_action('cred_commerce_after_send_notifications', $data);
    }

    public function onOrderComplete($data) {
        $this->plugin->detach('_cred_commerce_order_completed', array(&$this, 'onOrderComplete'));
        cred_log("onOrderComplete");
        cred_log($data);

        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                $model = CREDC_Loader::get('MODEL/Main');
                CRED_Loader::load('CLASS/Notification_Manager');
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                $form_slug = '';
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);

                    if ($cred_form_post) {
                        $form_slug = $cred_form_post->post_name;
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $form_id = $cred_data['cred_form_id'];
                $form = $model->getForm($form_id, true);
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {

                    if ($is_user_form) {

                        //Move user from wp_options to wp_user $post_id is the counter
                        $order_id = isset($data['order_id']) ? $data['order_id'] : $data['transaction_id'];
                        cred_log("order $order_id");
                        cred_log("order $post_id");

                        $model = CRED_Loader::get('MODEL/UserForms');
                        $new_user_id = $model->publishTemporaryUser($post_id, $order_id);
                        $post_id = $new_user_id;

                        cred_log("new_user_id $new_user_id");

                        $this->_data = array(
                            'order_id' => $order_id,
                            'cred_meta' => $cred_data
                        );

                        add_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCompleteEvent'), 1, 4);
                        CRED_Notification_Manager::triggerNotifications($post_id, array(
                            'event' => 'order_completed',
                            'form_id' => $form_id,
                            'notification' => $form->fields['notification'],
                            'customer' => $this->getCustomer($post_id, $form_id)
                        ));
                        remove_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCompleteEvent'), 1, 4);
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if ($this->form->fixAuthor && $user_id) {
                                    $postdata['post_author'] = $user_id;
                                }
                                if (
                                        isset($this->form->commerce['order_completed']) &&
                                        isset($this->form->commerce['order_completed']['post_status']) &&
                                        in_array($this->form->commerce['order_completed']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_completed']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }
                            }
                        }
                    }

                    // HOOKS API
                    do_action('cred_commerce_after_order_completed_form_' . $form_slug, $data);
                    do_action('cred_commerce_after_order_completed', $data);
                }
            }
        }
    }

    public function onOrderReceived($data) {
        cred_log("onOrderReceived");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        //Todo: user forms
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if ($this->form->fixAuthor && $user_id) {
                                    $postdata['post_author'] = $user_id;
                                }
                                if (
                                        isset($this->form->commerce['order_pending']) &&
                                        isset($this->form->commerce['order_pending']['post_status']) &&
                                        in_array($this->form->commerce['order_pending']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_pending']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }
                            }
                        }
                    }

                    // HOOKS API
                    do_action('cred_commerce_after_order_received', $data);
                }
            }
        }
    }

    public function onPaymentFailed($data) {
        cred_log("onPaymentFailed");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        //Todo: user forms
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if ($this->form->fixAuthor && $user_id) {
                                    $postdata['post_author'] = $user_id;
                                }
                                if (
                                        isset($this->form->commerce['order_failed']) &&
                                        isset($this->form->commerce['order_failed']['post_status']) &&
                                        in_array($this->form->commerce['order_failed']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_failed']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }
                            }
                        }
                    }
                    // HOOKS API
                    do_action('cred_commerce_after_payment_failed', $data);
                }
            }
        }
    }

    public function onPaymentComplete($data) {
        cred_log("onPaymentComplete");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if ($this->form->fixAuthor && $user_id) {
                                    $postdata['post_author'] = $user_id;
                                }
                                if (
                                        isset($this->form->commerce['order_processing']) &&
                                        isset($this->form->commerce['order_processing']['post_status']) &&
                                        in_array($this->form->commerce['order_processing']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_processing']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }
                            }
                        }
                    }

                    // HOOKS API
                    do_action('cred_commerce_after_payment_completed', $data);
                }
            }
        }
    }

    public function onHold($data) {
        cred_log("onHold");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        //Todo: users form
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if ($this->form->fixAuthor && $user_id) {
                                    $postdata['post_author'] = $user_id;
                                }
                                if (
                                        isset($this->form->commerce['order_on_hold']) &&
                                        isset($this->form->commerce['order_on_hold']['post_status']) &&
                                        in_array($this->form->commerce['order_on_hold']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_on_hold']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }
                            }
                        }
                    }

                    // HOOKS API
                    do_action('cred_commerce_after_order_on_hold', $data);
                }
            }
        }
    }

    public function onRefund($data) {
        cred_log("onRefund");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        //Todo: user forms
                        if ($this->form->commerce['order_refunded']['post_status'] == 'delete') {
                            $model = CRED_Loader::get('MODEL/UserForms');
                            $new_user_id = $model->deleteTemporaryUser($post_id);
                        }
//                        if ($this->form->commerce['order_complete']['post_status'] == 'publish') {
//                            //Move user from wp_options to wp_user $post_id is the counter
//                            $model = CRED_Loader::get('MODEL/UserForms');
//                            $new_user_id = $model->publishTemporaryUser($post_id);
//                        }
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if (
                                        isset($this->form->commerce['order_refunded']) &&
                                        isset($this->form->commerce['order_refunded']['post_status']) &&
                                        in_array($this->form->commerce['order_refunded']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_refunded']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }

                                if (
                                        isset($this->form->commerce['order_refunded']) &&
                                        isset($this->form->commerce['order_refunded']['post_status']) &&
                                        'trash' == $this->form->commerce['order_refunded']['post_status']
                                ) {
                                    // move to trash
                                    wp_delete_post($post_id, false);
                                } elseif (
                                        isset($this->form->commerce['order_refunded']) &&
                                        isset($this->form->commerce['order_refunded']['post_status']) &&
                                        'delete' == $this->form->commerce['order_refunded']['post_status']
                                ) {
                                    // delete
                                    wp_delete_post($post_id, true);
                                }
                            }
                        }
                    }
                    // HOOKS API
                    do_action('cred_commerce_after_payment_refunded', $data);
                }
            }
        }
    }

    public function onCancel($data) {
        cred_log("onCancel");
        cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data'])) {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data) {
                // get form meta data related to cred commerce
                $this->form = isset($cred_data['cred_form_id']) ? $this->model->getForm($cred_data['cred_form_id'], false) : false;
                if (isset($cred_data['cred_form_id'])) {
                    $cred_form_post = get_post($cred_data['cred_form_id']);
                    if ($cred_form_post) {
                        $is_user_form = ($cred_form_post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME);
                    }
                }
                $post_id = isset($cred_data['cred_post_id']) ? $cred_data['cred_post_id'] : false;
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : false;
                if ($this->form && $this->form->isCommerce) {
                    if ($is_user_form) {
                        if ($this->form->commerce['order_cancelled']['post_status'] == 'delete') {
                            $model = CRED_Loader::get('MODEL/UserForms');
                            $new_user_id = $model->deleteTemporaryUser($post_id);
                        }
                    } else {
                        if ($post_id) {
                            // check if post actually exists !!
                            $_post = get_post($post_id);
                            //if (!$_post) return;

                            if ($_post) {
                                $postdata = array();
                                if (
                                        isset($this->form->commerce['order_cancelled']) &&
                                        isset($this->form->commerce['order_cancelled']['post_status']) &&
                                        in_array($this->form->commerce['order_cancelled']['post_status'], array('draft', 'pending', 'private', 'publish'))
                                ) {
                                    $postdata['post_status'] = $this->form->commerce['order_cancelled']['post_status'];
                                }
                                if (!empty($postdata)) {
                                    $postdata['ID'] = $post_id;
                                    wp_update_post($postdata);
                                }

                                if (
                                        isset($this->form->commerce['order_cancelled']) &&
                                        isset($this->form->commerce['order_cancelled']['post_status']) &&
                                        'trash' == $this->form->commerce['order_cancelled']['post_status']
                                ) {
                                    // move to trash
                                    wp_delete_post($post_id, false);
                                } elseif (
                                        isset($this->form->commerce['order_cancelled']) &&
                                        isset($this->form->commerce['order_cancelled']['post_status']) &&
                                        'delete' == $this->form->commerce['order_cancelled']['post_status']
                                ) {
                                    // delete
                                    wp_delete_post($post_id, true);
                                }
                            }
                        }
                    }
                    // HOOKS API
                    do_action('cred_commerce_after_payment_cancelled', $data);
                }
            }
        }
    }

    public function notificationOrderCreatedEvent($result, $notification, $form_id, $post_id) {
        cred_log('notificationOrderCreatedEvent');
        cred_log(array($notification, $form_id, $post_id, $this->_data));
        if ($this->_data) {
            if (
                    'order_created' == $notification['event']['type'] &&
                    $form_id == $this->_data['cred_meta']['cred_form_id'] &&
                    $post_id == $this->_data['cred_meta']['cred_post_id']
            )
                $result = true;
        }
        //cred_log($result);
        return $result;
    }

    public function notificationOrderEvent($result, $notification, $form_id, $post_id) {
        cred_log("notificationOrderEvent");
        cred_log($this->_data);
        if ($this->_data) {
            if (
                    'order_modified' == $notification['event']['type'] &&
                    $form_id == $this->_data['cred_meta']['cred_form_id'] &&
                    $post_id == $this->_data['cred_meta']['cred_post_id'] &&
                    isset($notification['event']['order_status']) &&
                    isset($this->_data['new_status']) &&
                    $this->_data['new_status'] == $notification['event']['order_status']
            )
                $result = true;
        }
        return $result;
    }

    public function notificationOrderCompleteEvent($result, $notification, $form_id, $post_id) {
        cred_log("notificationOrderCompleteEvent");
        cred_log($this->_data);
        if ($this->_data) {
            if (
                    'order_completed' == $notification['event']['type'] &&
                    $form_id == $this->_data['cred_meta']['cred_form_id'] &&
                    $post_id == $this->_data['cred_meta']['cred_post_id']
            )
                $result = true;
        }
        return $result;
    }

}
