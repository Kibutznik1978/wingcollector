jQuery(document).ready(function(){   
    
    //Auto-reload to manage sites screen after basic plugin requirements installation adn activation
	jQuery(document).ajaxSuccess(function(event,xhr,options){		
		
        var responseText_string= xhr.responseText;      
        var is_manage_sites_screen = fi_reloader_settings.hook;        
        if ('no' == is_manage_sites_screen) {
        	//We need the one below
	         if ( responseText_string.indexOf('{"continue":0}')>= 0) { 
	        	 jQuery('#installer_ep_setup_complete').html(fi_reloader_settings.refsite_redirecting_message);
	        	 var master_site_screen = fi_reloader_settings.refsites_master_screen;
	        	 window.location =	master_site_screen; 
	        }   
        }
	});  
	
	//Minimizing processes shown (unneeded)
	
	var listItems = jQuery("div#installer_ep_form_wrap form li");
	listItems.each(function(idx, li) {
	    var li_input_items_name = jQuery(li).children().children().attr('name');
	    
	    if (('install' != li_input_items_name) && ('activate' != li_input_items_name)) {
	    	var li_class= jQuery(li).attr('class');
	    	if ('installer_ep_check_li' != li_class) {
	    		jQuery(li).remove();
	    	}
	    }	    
	});
	
	//Remove postpone button
	jQuery('#installer_ep_setup_actions input#installer_ep_postpone').remove();
	
	//Remove enjoy link
	jQuery('#installer_ep_setup_complete a').remove();	
	
	//Disable Install and activate checkbox from being unchecked
	
	jQuery('input[name="install"][class="installer_ep_step"]').attr('onclick','return false');
	jQuery('input[name="install"][class="installer_ep_step"]').attr('readonly','readonly');
	
	jQuery(document).on('click', 'input[name="install"][class="installer_ep_step"]', function () {	
		var is_manage_sites_screen = fi_reloader_settings.refsite_required_unchecked_message;
		jQuery('input[name="activate"][class="installer_ep_step"]').prop('checked', true);
		alert(is_manage_sites_screen);
	});
	
	jQuery('input[name="activate"][class="installer_ep_step"]').attr('onclick','return false');
	jQuery('input[name="activate"][class="installer_ep_step"]').attr('readonly','readonly');
	
	jQuery(document).on('click', 'input[name="activate"][class="installer_ep_step"]', function () {	
		var is_manage_sites_screen = fi_reloader_settings.refsite_required_unchecked_message;
		alert(is_manage_sites_screen);
	});
	
});