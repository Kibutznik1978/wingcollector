<?php

/**
*  WooCommerce Views Shortcode GUI callback
*/

if(is_admin()){ 
	add_action('init', 'wcviews_shortcodes_gui_init');
}

function wcviews_shortcodes_gui_init() {
	add_action('admin_head', 'wcviews_shortcodes_gui_js_init');

}

// TODO  can we please turn this into a JS file with dependencies (jquery-ui-dialog) and URLs, nonces and other
// TODO  information passed through wp_localize_script??
function wcviews_shortcodes_gui_js_init()
{
    $editor_callback_nonce = wp_create_nonce( 'wcviews_editor_callback' );
    ?>
    <script type="text/javascript">
        //<![CDATA[

        /**
         * Obtain dialog contents and display it.
         *
         * @param {string} action Name of the AJAX action that should be used to obtain the dialog content. It is
         *     supposed to return default wp response with these data fields: body, title, button.close and button.insert.
         * @param {function} insert_callback Function to be called when the Insert shortcode button is clicked. It needs
         *     only to handle the processing of the user input, the usual manipulation with the dialog is already
         *     handled here.
         * @since 2.5.5
         */
        function wcviews_show_insert_shortcode_dialog(action, insert_callback) {
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    _wpnonce: '<?php echo $editor_callback_nonce; ?>',
                    action: action
                },
                success: function (response) {
                    if (response.success) {
                        jQuery('<div></div>').html(response.data.body).dialog({
                            autoOpen: true,
                            modal: true,
                            minWidth: 600,
                            show: {
                                effect: "blind",
                                duration: 800
                            },
                            title: response.data.title,
                            buttons: [
                                {
                                    'class': 'button-secondary',
                                    text: response.data.button.close,
                                    click: function () {
                                        jQuery(this).dialog("close");
                                    }
                                },
                                {
                                    'class': 'button-primary',
                                    text: response.data.button.insert,
                                    click: function() {
                                        var insertButton = jQuery(this).closest(".ui-dialog").find(".ui-button:last");
                                        insertButton.removeClass('button-primary').addClass('button-secondary').prop('disabled', true);
                                        jQuery('<div class="spinner ajax-loader"></div>').insertBefore(insertButton).show();

                                        insert_callback();

                                        jQuery(this).dialog("close");
                                    }
                                }
                            ]
                        });
                    }
                    // todo handle errors
                }
            });
        }		

 		/**
 		* version 2.6.5: Revised to use single quotes
 		*/
        function wcviews_insert_wpv_woo_buy_or_select() {

            wcviews_show_insert_shortcode_dialog('wcviewsgui_wpv_woo_buy_or_select', function() {
                var addtocarttext = jQuery('#add_to_cart_text_wc_views_shortcodegui').val();   
                var group_addtocarttext = jQuery('#group_add_to_cart_text_wc_views_shortcodegui').val(); 
                var external_addtocarttext = jQuery('#external_add_to_cart_text_wc_views_shortcodegui').val();
                var listing_add_to_cart_text=  jQuery('#linktoproduct_text_wc_views_shortcodegui').val();            
                var showquantity_field = jQuery('#show_quantityfield_product_listing_button_id').val();                
                var show_variation_options = jQuery('#show_variation_options_product_listing_button_id').val();

                var active_area=jQuery('#'+window.wpcfActiveEditor); 
                var wpv_woo_buy_or_select_syntax="[wpv-woo-buy-or-select add_to_cart_text='" + addtocarttext + "' link_to_product_text='" + listing_add_to_cart_text + "' group_add_to_cart_text='" + group_addtocarttext + "' external_add_to_cart_text='" + external_addtocarttext + "' show_quantity_in_button='" + showquantity_field + "' show_variation_options='" + show_variation_options + "']"; 
                icl_editor.InsertAtCursor(active_area, wpv_woo_buy_or_select_syntax); 

            });

        }

 		/**
 		* version 2.6.5: Revised to use single quotes
 		*/
        function wcviews_insert_wpv_woo_buy_options() {			
            wcviews_show_insert_shortcode_dialog('wcviewsgui_wpv_woo_buy_options', function() {
                var productaddtocarttext= jQuery('#add_to_cart_textproductpage_wc_views_shortcodegui').val();                
                
                var active_area=jQuery('#'+window.wpcfActiveEditor); 
                var wpv_woo_buy_options_syntax="[wpv-woo-buy-options add_to_cart_text='" + productaddtocarttext + "']";
                icl_editor.InsertAtCursor(active_area, wpv_woo_buy_options_syntax);                  
            });
        }

 		/**
 		* version 2.6.5: Revised to use single quotes
 		*/ 		
        function wcviews_insert_wpv_woo_display_tabs() {			
            wcviews_show_insert_shortcode_dialog('wcviewsgui_wpv_woo_display_tabs', function() {
            	var disable_wcviews_reviews_data = jQuery('#disable_wcviews_reviews_button_id').val();               
                
                var active_area=jQuery('#'+window.wpcfActiveEditor); 
                var wpv_woo_display_tabs_syntax = "[wpv-woo-display-tabs disable_reviews_tab='" + disable_wcviews_reviews_data + "']";
                icl_editor.InsertAtCursor(active_area, wpv_woo_display_tabs_syntax);                  
            });
        }

 		/**
 		* version 2.6.5: Revised to use single quotes
 		*/      
        function wcviews_insert_wpv_woo_product_image() {  	
            				
            wcviews_show_insert_shortcode_dialog('wcviewsgui_wpv_woo_product_image', function() {
                var user_image_size_selected= jQuery('#wcviews_available_image_sizes').val();                            
                var user_image_format_selected= jQuery('#wcviews_available_output_format').val();
                var showgallery_field = jQuery('#show_image_gallery_thumbnails_listing_id').val(); 
                var enable_third_party_plugins_field = jQuery('#enable_third_party_filters_id').val();                         

                var active_area=jQuery('#'+window.wpcfActiveEditor); 
                var wpv_woo_product_image_syntax="[wpv-woo-product-image size='" + user_image_size_selected + "' output='" + user_image_format_selected + "' enable_third_party_filters='" + enable_third_party_plugins_field +"' gallery_on_listings='" + showgallery_field+ "']"; 
                icl_editor.InsertAtCursor(active_area, wpv_woo_product_image_syntax); 
                
				//Reset to default				
                jQuery('#wcviews_available_image_sizes').val('shop_single');                
                jQuery('#wcviews_available_output_format').val(''); 
                jQuery('#show_image_gallery_thumbnails_listing_id').val('no');
                jQuery('#enable_third_party_filters_id').val('no');  
            });           
        	
        }

 		/**
 		* version 2.6.5: Revised to use single quotes
 		*/
        function wcviews_insert_wpv_woo_productcategory_images() {

            wcviews_show_insert_shortcode_dialog('wcviewsgui_wpv_woo_productcategory_images', function() {
                var user_image_size_selected= jQuery('#wcviews_available_catimage_sizes').val();
                var user_image_format_selected= jQuery('#wcviews_available_output_formatcat').val();

                var active_area=jQuery('#'+window.wpcfActiveEditor); 
                var wpv_woo_productcategory_images_syntax="[wpv-woo-productcategory-images size='" + user_image_size_selected + "' output='" + user_image_format_selected +"']"; 
                icl_editor.InsertAtCursor(active_area, wpv_woo_productcategory_images_syntax); 
                
				//Reset to default
				jQuery('#wcviews_available_output_formatcat').val('raw');	
				jQuery('#wcviews_available_catimage_sizes').val('shop_single'); 
            });
        }

        /**
         * Legacy code to manage the opening/closing/workflow of the Types fields dialogs inside the Fields and Views popup.
         *
         * Juan's remark: We have that crazy idea of revamping those dialogs at some point move them from Colorbox to
         * jQueryUI and it all. I would not touch that at the moment.
         */
         
        var wpcfFieldsEditorCallback_redirect = null;

        function wpcfFieldsEditorCallback_set_redirect(function_name, params) {
            wpcfFieldsEditorCallback_redirect = {'function': function_name, 'params': params};
        }

        //]]>
    </script>
    <?php
}


function wcviewsgui_wpv_woo_buy_or_select_func() {

	if( !wp_verify_nonce( $_GET['_wpnonce'], 'wcviews_editor_callback' ) ) {
        wp_send_json_error();
    }

    $response = array(
        'title' => __('Add to cart button - product listing pages','woocommerce_views'),
        'button' => array(
            'close' => __( 'Cancel', 'woocommerce_views' ),
            'insert' => __('Insert shortcode', 'woocommerce_views')
        )
    );

    ob_start();
    ?>
    <p id="wc_viewsshortcode_gui_description"><span id="descriptionheader_gui_wcviews"><?php _e("Description:","woocommerce_views");?></span> <span id="descriptiontext_gui_wcviews"><?php _e("Displays 'Add to cart' or 'Select options' button in product listing pages.","woocommerce_views");?></span></p>
    <p id="addtocarttext_wcviews_gui"><?php _e('Add to Cart Text:','woocommerce_views');?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "simple" products','woocommerce_views')?></em>)</p>
    <p id="add_to_cart_text_wcviewsenclosure"><input type="text" name="add_to_cart_text_wc_views_shortcodegui" id="add_to_cart_text_wc_views_shortcodegui" value=""></p>
    <p class="defaulttext_wcviews_gui"><?php _e('Optional. Defaults to "Add to cart"','woocommerce_views');?></p>
    <p id="linktoproducttext_wcviews_gui"><?php _e('Link to Product Text:','woocommerce_views');?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "variation" products','woocommerce_views')?></em>)</p>
    <p id="linktoproduct_text_wcviewsenclosure"><input type="text" name="linktoproduct_text_wc_views_shortcodegui" id="linktoproduct_text_wc_views_shortcodegui" value=""></p>
    <p class="defaulttext_wcviews_gui"><?php _e('Optional. Defaults to "Select options"','woocommerce_views');?></p>  
    <p id="show_quantity_field_wcviews_gui"><?php _e('Show quantities next to add to cart buttons?','woocommerce_views')?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "simple" products','woocommerce_views')?></em>)</p>
    <input id="show_quantityfield_product_listing_button_id" type="checkbox" name="show_quantityfield_product_listing_button" value=""> <?php _e('Yes','woocommerce_views');?>&nbsp;&nbsp;(<?php _e('Optional, defaults to "No"','woocommerce_views');?>)<br>
    <p id="show_variation_options_field_wcviews_gui"><?php _e('Display product variation options in product listing page?','woocommerce_views')?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "variation" products','woocommerce_views')?></em>)</p>
    <input id="show_variation_options_product_listing_button_id" type="checkbox" name="show_variation_options_product_listing_button" value=""> <?php _e('Yes','woocommerce_views');?>&nbsp;&nbsp;(<?php _e('Optional, defaults to "No"','woocommerce_views');?>)<br>
     <p><label id="wcviews_customize_group_external_text"><span id="unexpanded_arrow_wcviews">&#9658;&nbsp;&nbsp;</span><span id="expanded_arrow_wcviews">&#9660;&nbsp;&nbsp;</span><?php _e('Customize Add to Cart text for WooCommerce Grouped or External products.')?></label></p> 
    <div id="group_external_wcviews_div" style="display: none">
    <p id="group_addtocarttext_wcviews_gui"><?php _e('Group Add to Cart Text:','woocommerce_views');?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "grouped" products','woocommerce_views')?></em>)</p>
    <p id="group_add_to_cart_text_wcviewsenclosure"><input type="text" name="group_add_to_cart_text_wc_views_shortcodegui" id="group_add_to_cart_text_wc_views_shortcodegui" value=""></p>
    <p class="defaulttext_wcviews_gui"><?php _e('Optional. Defaults to "View products"','woocommerce_views');?></p>    
    <p id="external_addtocarttext_wcviews_gui"><?php _e('External Add to Cart Text:','woocommerce_views');?>&nbsp;&nbsp;(<em><?php _e('Only applicable for WooCommerce "external" products','woocommerce_views')?></em>)</p>
    <p id="external_add_to_cart_text_wcviewsenclosure"><input type="text" name="external_add_to_cart_text_wc_views_shortcodegui" id="external_add_to_cart_text_wc_views_shortcodegui" value=""></p>
    <p class="defaulttext_wcviews_gui"><?php _e('Optional. Defaults to "Buy product"','woocommerce_views');?></p> 
    </div>    
    <script type="text/javascript">
        //<![CDATA[        
        jQuery(function(){	  	
        	jQuery('#add_to_cart_text_wc_views_shortcodegui').val('');
		    jQuery(document).on('change','#add_to_cart_text_wc_views_shortcodegui', function() {
		    	var add_to_cart_text_listing_ui =jQuery(this).val();	               
	            jQuery('#add_to_cart_text_wc_views_shortcodegui').val(add_to_cart_text_listing_ui);                     
	  	 	});
        	jQuery('#group_add_to_cart_text_wc_views_shortcodegui').val('');
		    jQuery(document).on('change','#group_add_to_cart_text_wc_views_shortcodegui', function() {
		    	var group_add_to_cart_text_listing_ui =jQuery(this).val();	               
	            jQuery('#group_add_to_cart_text_wc_views_shortcodegui').val(group_add_to_cart_text_listing_ui);                     
	  	 	});	

        	jQuery('#external_add_to_cart_text_wc_views_shortcodegui').val('');
		    jQuery(document).on('change','#external_add_to_cart_text_wc_views_shortcodegui', function() {
		    	var external_add_to_cart_text_listing_ui =jQuery(this).val();	               
	            jQuery('#external_add_to_cart_text_wc_views_shortcodegui').val(external_add_to_cart_text_listing_ui);                     
	  	 	});
	  	 		  	 	  	 		
        	jQuery('#linktoproduct_text_wc_views_shortcodegui').val('');
		    jQuery(document).on('change','#linktoproduct_text_wc_views_shortcodegui', function() {
		    	var link_to_product_text_ui =jQuery(this).val();	               
	            jQuery('#linktoproduct_text_wc_views_shortcodegui').val(link_to_product_text_ui);                     
	  	 	});	
		    jQuery('#show_quantityfield_product_listing_button_id').val('no');
		    jQuery(document).on('change','#show_quantityfield_product_listing_button_id', function() {		    
		    	    if(this.checked) {			    	    
		    	    	jQuery('#show_quantityfield_product_listing_button_id').val('yes');
		    	    } else {		    	    	
		    	    	jQuery('#show_quantityfield_product_listing_button_id').val('no'); 
		    	    }                    
	  	 	});
		    jQuery('#show_variation_options_product_listing_button_id').val('no');
		    jQuery(document).on('change','#show_variation_options_product_listing_button_id', function() {		    
		    	    if(this.checked) {		    	    	
		    	    	jQuery('#show_variation_options_product_listing_button_id').val('yes');
		    	    } else {		    	    	
		    	    	jQuery('#show_variation_options_product_listing_button_id').val('no'); 
		    	    }                    
	  	 	});
	  	 	//External and Group products text box handler	  	 	
	  	 	jQuery('.ui-dialog #expanded_arrow_wcviews').hide();	  	 	
	  	 	jQuery('.ui-dialog #unexpanded_arrow_wcviews').show();	  	 	
		    jQuery('.ui-dialog #wcviews_customize_group_external_text').toggle(
		            function(){			            		            
		            	jQuery('.ui-dialog #expanded_arrow_wcviews').show();
		                jQuery('.ui-dialog #group_external_wcviews_div').show();	
		                jQuery('.ui-dialog #unexpanded_arrow_wcviews').hide();               
		            },
		            function(){				            		            
		            	jQuery('.ui-dialog #expanded_arrow_wcviews').hide();
		            	jQuery('.ui-dialog #group_external_wcviews_div').hide();	
		            	jQuery('.ui-dialog #unexpanded_arrow_wcviews').show();	            	
		            });	  	 		  	 	 	
       }); 
        //]]>
    </script>
    <?php

    $response['body'] = ob_get_contents();
    ob_end_clean();

    wp_send_json_success( $response );
}


function wcviewsgui_wpv_woo_buy_options_func()
{

    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcviews_editor_callback' ) ) {
        wp_send_json_error();
    }

    $response = array(
        'title' => __( 'Add to cart button - single product page', 'woocommerce_views' ),
        'button' => array(
            'close' => __( 'Cancel', 'woocommerce_views' ),
            'insert' => __( 'Insert shortcode', 'woocommerce_views' )
        )
    );

    ob_start();
    ?>
    <p id="wc_viewsshortcode_gui_description"><span
            id="descriptionheader_gui_wcviews"><?php _e( "Description:", "woocommerce_views" ); ?></span> <span
            id="descriptiontext_gui_wcviews"><?php _e( "Displays 'add to cart' (for simple products) or 'select options' box (for variation products) in single product pages.", "woocommerce_views" ); ?></span>
    </p>
    <p id="addtocarttext_wcviews_gui"><?php _e( 'Add to Cart Text:', 'woocommerce_views' ); ?></p>
    <p id="add_to_cart_text_wcviewsenclosure"><input type="text"
                                                     name="add_to_cart_textproductpage_wc_views_shortcodegui"
                                                     id="add_to_cart_textproductpage_wc_views_shortcodegui" value="">
    </p>
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to "Add to cart"', 'woocommerce_views' ); ?></p>
    <script type="text/javascript">
        //<![CDATA[        
        jQuery(function(){	  	
        	jQuery('#add_to_cart_textproductpage_wc_views_shortcodegui').val('');
		    jQuery(document).on('change','#add_to_cart_textproductpage_wc_views_shortcodegui', function() {			    
		    	var add_to_cart_text_ui =jQuery(this).val();	               
	            jQuery('#add_to_cart_textproductpage_wc_views_shortcodegui').val(add_to_cart_text_ui);                     
	  	 	});	 	
       }); 
        //]]>
    </script>    
    <?php

    $response['body'] = ob_get_contents();
    ob_end_clean();

    wp_send_json_success( $response );
}

function wcviewsgui_wpv_woo_display_tabs_func()
{

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcviews_editor_callback' ) ) {
		wp_send_json_error();
	}

	$response = array(
			'title' => __( 'Product tabs - single product page', 'woocommerce_views' ),
			'button' => array(
					'close' => __( 'Cancel', 'woocommerce_views' ),
					'insert' => __( 'Insert shortcode', 'woocommerce_views' )
			)
	);

	ob_start();
	?>
    <p id="wc_viewsshortcode_gui_description"><span
            id="descriptionheader_gui_wcviews"><?php _e( "Description:", "woocommerce_views" ); ?></span> <span
            id="descriptiontext_gui_wcviews"><?php _e( "This shortcode displays WooCommerce product tabs. By default this will display product reviews and product attributes.", "woocommerce_views" ); ?></span>
    </p>  
    <p id="disable_reviews_wcviews_gui"><?php _e('Do you want to disable "Reviews" tab inside WooCommerce Product data tab?','woocommerce_views')?>&nbsp;&nbsp;(<em><?php _e('This is useful if you want reviews to be outputted using wpv-woo-reviews shortcode.','woocommerce_views')?></em>)</p>
    <input id="disable_wcviews_reviews_button_id" type="checkbox" name="disable_wcviews_reviews_button" value=""> <?php _e('Yes','woocommerce_views');?>&nbsp;&nbsp;(<?php _e('Optional, defaults to "No"','woocommerce_views');?>)<br>   
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to having reviews tab enabled.', 'woocommerce_views' ); ?></p>
    <script type="text/javascript">
        //<![CDATA[        
        jQuery(function(){        	
		    jQuery('#disable_wcviews_reviews_button_id').val('no');
		    jQuery(document).on('change','#disable_wcviews_reviews_button_id', function() {		    
		    	    if(this.checked) {			    	    
		    	    	jQuery('#disable_wcviews_reviews_button_id').val('yes');
		    	    } else {		    	    	
		    	    	jQuery('#disable_wcviews_reviews_button_id').val('no'); 
		    	    }                    
	  	 	});	
       }); 
        //]]>
    </script>    
    <?php

    $response['body'] = ob_get_contents();
    ob_end_clean();

    wp_send_json_success( $response );
}

function wcviewsgui_wpv_woo_product_image_func()
{

    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcviews_editor_callback' ) ) {
        wp_send_json_error();
    }

    $response = array(
        'title' => __( 'Product image', 'woocommerce_views' ),
        'button' => array(
            'close' => __( 'Cancel', 'woocommerce_views' ),
            'insert' => __( 'Insert shortcode', 'woocommerce_views' )
        )
    );

    ob_start();
    ?>
    <p id="wc_viewsshortcode_gui_description"><span
            id="descriptionheader_gui_wcviews"><?php _e( "Description:", "woocommerce_views" ); ?></span> <span
            id="descriptiontext_gui_wcviews"><?php _e( "Display the product image on single product and product listing pages. It will use the product featured image if set or output a placeholder if empty. This will also display variation images.", "woocommerce_views" ); ?></span>
    </p>
    <p id="imagesetting_wcviews_gui">A.)&nbsp;&nbsp;<?php _e( 'Select image size:', 'woocommerce_views' ); ?></p>
    <?php
    global $Class_WooCommerce_Views;

    //Retrieve available image sizes usable for WooCommerce Product Images
    $available_images_for_wcviews = $Class_WooCommerce_Views->wc_views_list_image_sizes();

    //Loop through the sizes and display as options
    if ( ( is_array( $available_images_for_wcviews ) ) && ( ! ( empty( $available_images_for_wcviews ) ) ) ) {
        ?>
        <!--suppress HtmlFormInputWithoutLabel -->
        <select id="wcviews_available_image_sizes" name="wcviews_available_image_sizes">
            <?php
            //Set the clean name array
            $clean_image_name_array = array(
                'thumbnail' => __( 'WordPress thumbnail size', 'woocommerce_views' ),
                'medium' => __( 'WordPress medium image size', 'woocommerce_views' ),
                'large' => __( 'WordPress large image size', 'woocommerce_views' ),
                'shop_thumbnail' => __( 'WooCommerce product thumbnail size', 'woocommerce_views' ),
                'shop_catalog' => __( 'WooCommerce shop catalog image size', 'woocommerce_views' ),
                'shop_single' => __( 'WooCommerce single product image size', 'woocommerce_views' ) );

            foreach ( $available_images_for_wcviews as $key => $value ) {
                if ( isset( $clean_image_name_array[ $key ] ) ) {
                    $image_name_set = $clean_image_name_array[ $key ];
                } else {
                    $image_name_set = '[' . __( 'Custom size', 'woocommerce_views' ) . ']-' . $key;
                }

                printf(
                    '<option value="%s" %s>%s</option>',
                    $key,
                    ( $key == 'shop_single' ) ? 'SELECTED' : '',
                    $image_name_set
                );

            }
            ?>
        </select>
        <?php
        //Retrieve default image size
        if ( isset( $available_images_for_wcviews['shop_single'] ) ) {
            if ( ( is_array( $available_images_for_wcviews['shop_single'] ) ) && ( ! ( empty( $available_images_for_wcviews['shop_single'] ) ) ) ) {
                $default_image_size_php = $available_images_for_wcviews['shop_single'];
                $default_imagewidth_size_set = $default_image_size_php[0];
                $default_imageheight_size_set = $default_image_size_php[1];
                ?>
                <span id="imagesizes_outputtext_wcviews"><?php echo $default_imagewidth_size_set; ?>
                    x <?php echo $default_imageheight_size_set; ?> ( <?php _e( 'in pixels', 'woocommerce_views' ); ?>
                    )</span>
                <?php
            }
        } else {
            ?>
            <span id="imagesizes_outputtext_wcviews"></span>
            <?php
        }

    }
    ?>
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to "Single Product Image"', 'woocommerce_views' ); ?></p>
    
    <p id="imagesetting_wcviews_gui">B.)&nbsp;&nbsp;<?php _e( 'Select output format:', 'woocommerce_views' ); ?></p>
    <!--suppress HtmlFormInputWithoutLabel -->
    <select id="wcviews_available_output_format" name="wcviews_available_output_format">
        <option value="" SELECTED><?php _e( 'WooCommerce default', 'woocommerce_views' ); ?></option>
        <option value="img_tag"><?php _e( 'Output image tag only', 'woocommerce_views' ); ?></option>
        <option value="raw"><?php _e( 'Output image URL only', 'woocommerce_views' ); ?></option>
    </select>
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to WooCommerce image format which is an image link and popup when clicked.', 'woocommerce_views' ); ?></p>
    
    <p id="show_imagegallery_listings_wcviews_gui">C.)&nbsp;&nbsp;<?php _e( 'Show image gallery thumbnails in product listings', 'woocommerce_views' ) ?>
        ?&nbsp;&nbsp;(<em><?php _e( 'Only affects product listings', 'woocommerce_views' ) ?></em>)</p>
    <!--suppress HtmlFormInputWithoutLabel -->
    <label><input id="show_image_gallery_thumbnails_listing_id" type="checkbox" name="show_image_gallery_thumbnails_listing"
           value=""> <?php _e( 'Yes', 'woocommerce_views' ); ?>&nbsp;&nbsp;(<?php _e( 'Optional, defaults to "No".', 'woocommerce_views' ); ?>)</label>
    
    <p id="enable_thid_party_filters_gui">D.)&nbsp;&nbsp;<?php _e( 'Enable third party plugin filters on WooCommerce Image', 'woocommerce_views' ) ?></p>
    <p><em><?php _e( 'Third party plugins/themes hooking to "woocommerce_before_single_product_summary" and "woocommerce_before_shop_loop_item"', 'woocommerce_views' ) ?></em></p>
    <label><input id="enable_third_party_filters_id" type="checkbox" name="enable_third_party_filters_wcviews_image"
           value=""> <?php _e( 'Yes', 'woocommerce_views' ); ?>&nbsp;&nbsp;(<?php _e( 'Optional, defaults to "No".', 'woocommerce_views' ); ?>)</label>
    <br />    
    <script type="text/javascript">
        //<![CDATA[        
        jQuery(function(){	  	
		    jQuery(document).on('change','#wcviews_available_image_sizes', function() {                   
		            <?php if (is_array($available_images_for_wcviews) && (!(empty($available_images_for_wcviews)))) { ?>		
		            var available_sizes_array_canonical =<?php echo json_encode($available_images_for_wcviews);?>;                    
		            var setting_used_sizes =jQuery(this).val();		            	            
		            var image_sizes_unprocessed = available_sizes_array_canonical[setting_used_sizes];		            
		            var image_height_set = image_sizes_unprocessed[1];
		            var image_width_set = image_sizes_unprocessed[0];		            
		            var output_text_image_note = image_width_set + '  x  ' + image_height_set + ' ( <?php echo esc_js(__('in pixels','woocommerce_views'));?> )';		            
		            jQuery('.ui-dialog-content #imagesizes_outputtext_wcviews').text(output_text_image_note);	
		            jQuery('#wcviews_available_image_sizes').val(setting_used_sizes); 	            
		            <?php } ?>
		   });
		    jQuery(document).on('change','#wcviews_available_output_format', function() {
		    	var imageformat_selected_ui =jQuery(this).val();	               
	            jQuery('#wcviews_available_output_format').val(imageformat_selected_ui);                     
	  	 	});
		    jQuery('#show_image_gallery_thumbnails_listing_id').val('no');
		    jQuery(document).on('change','#show_image_gallery_thumbnails_listing_id', function() {		    
		    	    if(this.checked) {
		    	    	jQuery('#show_image_gallery_thumbnails_listing_id').val('yes');
		    	    } else {
		    	    	jQuery('#show_image_gallery_thumbnails_listing_id').val('no'); 
		    	    }                    
	  	 	}); 
	  	 	/** Third party plugin filters */
		    jQuery('#enable_third_party_filters_id').val('no');
		    jQuery(document).on('change','#enable_third_party_filters_id', function() {		    
		    	    if(this.checked) {
		    	    	jQuery('#enable_third_party_filters_id').val('yes');
		    	    } else {
		    	    	jQuery('#enable_third_party_filters_id').val('no'); 
		    	    }                    
	  	 	}); 	  	 		 	
       }); 
        //]]>
    </script>
    <?php

    $response['body'] = ob_get_contents();
    ob_end_clean();

    wp_send_json_success( $response );

}


function wcviewsgui_wpv_woo_productcategory_images_func()
{

    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcviews_editor_callback' ) ) {
        wp_send_json_error();
    }

    $response = array(
        'title' => __( 'WooCommerce Product Category Image', 'woocommerce_views' ),
        'button' => array(
            'close' => __( 'Cancel', 'woocommerce_views' ),
            'insert' => __( 'Insert shortcode', 'woocommerce_views' )
        )
    );

    ob_start();
    ?>
    <p id="wc_viewsshortcode_gui_description">
        <span id="descriptionheader_gui_wcviews"><?php _e( "Description:", "woocommerce_views" ); ?></span>
        <span id="descriptiontext_gui_wcviews">
            <?php
            _e( "Display the product category image on product listing pages. It will use the product category image set on the backend. If it is not set, it will show no image.", "woocommerce_views" );
            ?>
        </span>
    </p>
    <p id="imagesetting_wcviews_gui"><?php _e( 'Select image size:', 'woocommerce_views' ); ?></p>
    <?php
    global $Class_WooCommerce_Views;

    //Retrieve available image sizes usable for WooCommerce Product Images
    $available_images_for_wcviews = $Class_WooCommerce_Views->wc_views_list_image_sizes();

    //Loop through the sizes and display as options
    if ( ( is_array( $available_images_for_wcviews ) ) && ( ! ( empty( $available_images_for_wcviews ) ) ) ) {
        ?>
        <select id="wcviews_available_catimage_sizes" name="wcviews_available_image_sizes">
            <?php
            //Set the clean name array
            $clean_image_name_array = array(
                'thumbnail' => __( 'WordPress thumbnail size', 'woocommerce_views' ),
                'medium' => __( 'WordPress medium image size', 'woocommerce_views' ),
                'large' => __( 'WordPress large image size', 'woocommerce_views' ),
                'shop_thumbnail' => __( 'WooCommerce product thumbnail size', 'woocommerce_views' ),
                'shop_catalog' => __( 'WooCommerce shop catalog image size', 'woocommerce_views' ),
                'shop_single' => __( 'WooCommerce single product image size', 'woocommerce_views' ) );

            foreach ( $available_images_for_wcviews as $key => $value ) {
                if ( isset( $clean_image_name_array[ $key ] ) ) {
                    $image_name_set = $clean_image_name_array[ $key ];
                } else {
                    $image_name_set = '[' . __( 'Custom size', 'woocommerce_views' ) . ']-' . $key;
                }

                ?>
                <option value="<?php echo $key; ?>" <?php if ( $key == 'shop_single' ) {
                    echo "SELECTED";
                } ?>><?php echo $image_name_set; ?></option>
                <?php
            }
            ?>
        </select>
        <?php
        //Retrieve default image size
        if ( isset( $available_images_for_wcviews['shop_single'] ) ) {
            if ( ( is_array( $available_images_for_wcviews['shop_single'] ) ) && ( ! ( empty( $available_images_for_wcviews['shop_single'] ) ) ) ) {
                $default_image_size_php = $available_images_for_wcviews['shop_single'];
                $default_imagewidth_size_set = $default_image_size_php[0];
                $default_imageheight_size_set = $default_image_size_php[1];
                ?>
                <span id="catimagesizes_outputtext_wcviews"><?php echo $default_imagewidth_size_set; ?>
                    x <?php echo $default_imageheight_size_set; ?> ( <?php _e( 'in pixels', 'woocommerce_views' ); ?>
                    )</span>
                <?php
            }
        } else {
            ?>
            <span id="catimagesizes_outputtext_wcviews"></span>
            <?php
        }

    }
    ?>
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to "Single Product Image"', 'woocommerce_views' ); ?></p>
    <p id="imagesetting_wcviews_gui"><?php _e( 'Select output format:', 'woocommerce_views' ); ?></p>
    <select id="wcviews_available_output_formatcat" name="wcviews_available_output_format">
        <option value="img_tag"><?php _e( 'Output image tag only', 'woocommerce_views' ); ?></option>
        <option value="raw" SELECTED><?php _e( 'Output image URL only', 'woocommerce_views' ); ?></option>
    </select>
    <p class="defaulttext_wcviews_gui"><?php _e( 'Optional. Defaults to raw URL of the resized image.', 'woocommerce_views' ); ?></p>
     <script type="text/javascript">
        //<![CDATA[        
        jQuery(function(){        	
		    jQuery(document).on('change','#wcviews_available_catimage_sizes', function() {                   
		            <?php if (is_array($available_images_for_wcviews) && (!(empty($available_images_for_wcviews)))) { ?>		
		            var available_sizes_array_canonical =<?php echo json_encode($available_images_for_wcviews);?>;                    
		            var setting_used_sizes =jQuery(this).val();		            	            
		            var image_sizes_unprocessed = available_sizes_array_canonical[setting_used_sizes];		            
		            var image_height_set = image_sizes_unprocessed[1];
		            var image_width_set = image_sizes_unprocessed[0];		            
		            var output_text_image_note = image_width_set + '  x  ' + image_height_set + ' ( <?php echo esc_js(__('in pixels','woocommerce_views'));?> )';		            
		            jQuery('.ui-dialog-content #catimagesizes_outputtext_wcviews').text(output_text_image_note);	
		            jQuery('#wcviews_available_catimage_sizes').val(setting_used_sizes); 	            
		            <?php } ?>
		   });		    
		    jQuery(document).on('change','#wcviews_available_output_formatcat', function() {
		    	var imageformat_selected_ui =jQuery(this).val();		    	               
	            jQuery('#wcviews_available_output_formatcat').val(imageformat_selected_ui);                     
	  	 	}); 	 	
       }); 
        //]]>
    </script>   

    <?php

    $response['body'] = ob_get_contents();
    ob_end_clean();

    wp_send_json_success( $response );
}