<?php
/** 
 * 
 * Alias Functions for easy [wpv-if] implementation 
 * 
 * 
 */

/** Check if product is on sale */
/** Example usage:
 * 
             [wpv-if evaluate="woo_product_on_sale() = 1"]
                This product is on sale
              [/wpv-if]
               [wpv-if evaluate="woo_product_on_sale() = 0"]
                This product is not on sale
              [/wpv-if]
*
* @package WooCommerce Views
*
* @since 2.4
*/              


function woo_product_on_sale() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->woo_product_on_sale();
}

/** Check if stock is available */
/** Example usage:
              [wpv-if evaluate="woo_product_in_stock() = 1"]
                Stock is available for this product.
              [/wpv-if]
              [wpv-if evaluate="woo_product_in_stock() = 0"]
                Stock is not available for this product.
              [/wpv-if]
 */

function woo_product_in_stock() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->woo_product_in_stock();
}

/** Check if a rating is available for this product, only works in single product pages. */
/** Example usage:
              [wpv-if evaluate="wpv_woo_single_products_rating_func() != ''"]
                 A rating is available for this product.       
              [/wpv-if]
               [wpv-if evaluate="wpv_woo_single_products_rating_func() = ''"]
                 A rating is not available for this product.       
              [/wpv-if]             
 */

function wpv_woo_single_products_rating_func() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->wpv_woo_single_products_rating_func();

}

/** Check if this product has attributes set */
/** Example usage:
              [wpv-if evaluate="wpv_woo_list_attributes_func() != ''"]
                 This product has attributes set.  
              [/wpv-if]
              [wpv-if evaluate="wpv_woo_list_attributes_func() = ''"]
                 This product still does not have have attributes set.  
              [/wpv-if]               
 */

function wpv_woo_list_attributes_func() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->wpv_woo_list_attributes_func();

}

/** Check if this item has an upsell product assigned, works only on single product pages */
/** Example usage:
              [wpv-if evaluate="wpv_woo_show_upsell_func() != ''"]
              	This item has an associated upsell product assigned.
              [/wpv-if]
              [wpv-if evaluate="wpv_woo_show_upsell_func() = ''"]
                This item does not have any associated upsell item.
              [/wpv-if]
 */

function wpv_woo_show_upsell_func() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->wpv_woo_show_upsell_func();

}

/** Check if a rating is available for this product, only works in product listing pages. */
/** Example usage:
                  [wpv-if evaluate="wpv_woo_products_rating_on_listing_func() != ''"]
					This item has a rating, let's show in this product listing.       
                  [/wpv-if]
                  [wpv-if evaluate="wpv_woo_products_rating_on_listing_func() = ''"]
              		Product is not yet rated  
              	  [/wpv-if]
 */

function wpv_woo_products_rating_on_listing_func() {	
	
	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->wpv_woo_products_rating_on_listing_func();
	
}

/** Check if a product category being loaded has a subcategory */
/** Example usage:
              [wpv-if evaluate="woo_has_product_subcategory() = 1"]
                This product category has a subcategory.
              [/wpv-if]
              [wpv-if evaluate="woo_has_product_subcategory() = 0"]
                This product category has does not have a subcategory.
              [/wpv-if]
 */

function woo_has_product_subcategory() {
	
	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->woo_is_product_subcategory_func();
	
}

	/** NEW: WooCommerce Views 2.5.5 */
	/** Check if the WooCommerce Shop Page display is set to 'Show categories and subcategories */
	/** This setting is found in WooCommerce -> Settings -> Products -> Display -> Shop page Display */
	/** This is mainly used in the Views -> WordPress archive for Shop page customization mainly to check if this setting is enabled.  */
	/** To use this conditional function, copy woocommerce-views/archive-product.php to your theme/woocommerce/archive-product.php */
	/** Then delete this line in the theme/woocommerce/archive-product.php: */
	/** woocommerce_product_subcategories() 
	/** The purpose is for you to override the display with Toolset Views */
	/** To actvate this custom archive template, go to your WooCommerce Views settings in the backend and select this archive template that is now copied to your theme. */
	/** You should be able to see this copied template under 'Product Archive Template File' */
 
	/** In your Views -> WordPress archive designed to customize WooCommerce shop page, you can then use this conditional function. */
	/** This conditional is designed to work only on WooCommerce shop page, not anywhere else */

	/** Example usage inside WordPress archive Loop output
	 [wpv-if evaluate="woo_shop_display_is_categories() = 1"]
	 	[wpv-view name="Your View to customize categories display in SHOP page"]
	 [/wpv-if]
	 [wpv-if evaluate="woo_shop_display_is_categories() = 0"]
	 	Shop page is set to display product items.
	 [/wpv-if]
	 */

function woo_shop_display_is_categories() {

	global $Class_WooCommerce_Views; return $Class_WooCommerce_Views->woo_shop_display_is_categories_func();

}