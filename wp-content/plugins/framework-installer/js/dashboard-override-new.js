jQuery(document).ready(function($){

        //Don't allow to dismiss. Instead, users can minimize it, but the message should stay there and they can expand again.
	$('#welcomepanelnonce').remove();
	$('a.welcome-panel-close').remove();
	var robot_icon_exported= fi_new_welcome_panel.robot_icon_exported;
	if ('no' == robot_icon_exported) {
		//Hide the rest of the dashboard.
		//Dashboard is too big for this
		$('#dashboard-widgets-wrap').remove(); 
	}        
        //Toggle mechanism
		$( ".wpvlive-toggle" ).click(function() {


			if($(this).hasClass('expanded')) {
				$(".wpvlive-content").slideUp('slow');
				$(".wpvlive-container").animate({
					"margin-left" : "84px"
				});
				$(".wpvlive-image").animate({
					"width" : "54px"
				});
				$(this).html("Expand<span class='dashicons dashicons-arrow-down'></span>");
			} else {
				$(".wpvlive-content").slideDown('slow');

				$(".wpvlive-robot .wpvlive-container").animate({
					"margin-left" : "230px"
				});
				$(".wpvlive-robot .wpvlive-image").animate({
					"width" : "200px"
				});

				$(".wpvlive-norobot .wpvlive-container").animate({
					"margin-left" : "0px"
				});
				$(".wpvlive-norobot .wpvlive-image").animate({
					"width" : "0px"
				});
				$(this).html("Minimize<span class='dashicons dashicons-arrow-up'></span>")
			}


			$(this).toggleClass('expanded');
		});

		var we_are_on_standalone= fi_new_welcome_panel.we_are_standalone;
		
		//Load JS below if we are on standalone mode
		if (('yes' ==we_are_on_standalone)) {
			//Disable the Framework installer deactivate button when user does not yet agree
			$('input[type="submit"][id="wpvdemo_read_understand_button"]').prop('disabled', true);
			
			//The button should only be active when the checkbox is selected. 
			//This ensures us that the user pays (some) attention to our message.
			$('#wpvdemo_read_understand_checkbox').click(function(){
				    if ($(this).is(':checked')) {		        	
			        	$('input[type="submit"][id="wpvdemo_read_understand_button"]').prop('disabled', false);		        
			        } else {		        	
			        	$('input[type="submit"][id="wpvdemo_read_understand_button"]').prop('disabled', true);
			        }
			});
			
			//Confirmation dialog
			$("#wpvdemo_client_fi_confirmation_form").submit(function(e){
				var are_you_sure_js= fi_new_welcome_panel.are_you_sure_msg;
			    if (!confirm(are_you_sure_js))
			    {
			        e.preventDefault();
			        return;
			    } 
			});
			
			if ($(".wpvdemo_framework_installer_deactivated")[0]){
				location.reload();
			}			
		}	
		
    });

