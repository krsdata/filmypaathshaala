(function ( $ ) {
	$(function () {
		
		if ( $( '#efbl_enabe_if_login' ).is(":checked") ) {
			$('#efbl_enabe_if_not_login').removeAttr("checked"); 
			$('#efbl_enabe_if_not_login').attr("disabled", true);
		}else if ( $( '#efbl_enabe_if_login' ).is(":checked") ) {
			$('#efbl_enabe_if_login').removeAttr("checked"); 
			$('#efbl_enabe_if_login').attr("disabled", true);
		}
		
		$('#efbl_enabe_if_login').click(function (){
		 
			 if ( $( this ).is(":checked")) {
					$('#efbl_enabe_if_not_login').removeAttr("checked"); 
					$('#efbl_enabe_if_not_login').attr("disabled", true);
					
 			  } else {
 				   $('#efbl_enabe_if_not_login').removeAttr("disabled"); 
 					
			}
			 
		});
		
		$('#efbl_enabe_if_not_login').click(function (){
		 
			 if ( $( this ).is(":checked")) {
					$('#efbl_enabe_if_login').removeAttr("checked"); 
					$('#efbl_enabe_if_login').attr("disabled", true);
					
 			  } else {
 				   $('#efbl_enabe_if_login').removeAttr("disabled"); 
 					
			}
			 
		});

		$('.efbl_del_trans').click(function (){
		 
			/*
			* Getting clicked option value.
			*/	
			var efbl_option = jQuery(this).data('efbl_trans');
			

			var data = { action : 'efbl_del_trans',
				efbl_option : efbl_option
				}
				

			jQuery.ajax({
			url : efbl.ajax_url,
			type : 'POST',
			dataType: 'json',
			data : data,
			success : function( response ) {
			
					if(response.success){
						jQuery('.form-table .'+response.data).slideUp('slow');
						
						
					}
					
			}

			});/* Ajax func ends here. */			

			 
		});	

		$('#easy-facebook-auth .efbl-save-access-token').click(function (){
		 
			/*
			* Getting clicked option value.
			*/	
			var efbl_access_token = $.trim($("#efbl_access_token").val());


			var data = { action : 'efbl_save_access_token',
				efbl_access_token : efbl_access_token
				}
				

			jQuery.ajax({
			url : efbl.ajax_url,
			type : 'POST',
			data : data,
			success : function( response ) {
				// console.log(response); return;	 
					if(response.success){
						jQuery('#easy-facebook-auth .efbl-save-access-token').html(' ').html('Saved');
						
						
					}
					
			}

			});/* Ajax func ends here. */			

			 
		});	
		

		function getUrlVars()
			{
			    var vars = [], hash;
			    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
			    for(var i = 0; i < hashes.length; i++)
			    {
			        hash = hashes[i].split('=');
			        vars.push(hash[0]);
			        vars[hash[0]] = hash[1];
			    }
			    return vars;
			}

			if(getUrlVars().access_token){
				var access_token = getUrlVars().access_token;
				jQuery('#efbl_access_token').html(access_token);	
			
			}

	});

}(jQuery));	