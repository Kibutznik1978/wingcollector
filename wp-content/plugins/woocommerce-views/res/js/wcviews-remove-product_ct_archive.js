jQuery( document ).ready( function( $ ) {
	
	jQuery(document).ajaxSuccess(function(event,xhr,options){	
		
        var responseText_string= xhr.responseText;  
        var only_product_is_wparchive_ajax=wc_views_remove_product_ct_archive_localize.wcviews_product_only_archive;        
        if (responseText_string.indexOf("views_template_archive_for_product") > -1) {  
        	if ('yes' == only_product_is_wparchive_ajax) {        		
        		jQuery('#views_template_archive_for_product').closest("div").prev("p").remove();        		
        	} else {
        		jQuery('#views_template_archive_for_product').closest('li').remove();
        	}        	 
		}		         
	});
	var only_product_is_wparchive=wc_views_remove_product_ct_archive_localize.wcviews_product_only_archive;
	$('input[value="product"][data-bind="checked: assignedPostArchivesAccepted"]').closest('li').remove();
	var are_we_on_ptarchive_usage=wc_views_remove_product_ct_archive_localize.wcviews_pt_archive_usage;	
	
	if ('yes' == only_product_is_wparchive) {
		$('div.wpv-views-listing-page ul a[href$="usage=post-archives"]').closest('li').remove();
	}
	
	if ('yes' == are_we_on_ptarchive_usage) {
		$('tr#wpv_ct_list_row_product').remove();
	}

	$('ul.wpv-mightlong-list').not(':has(li)').closest('div').remove();

});