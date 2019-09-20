jQuery(document).ready(function($){
	//$('.emd-form-submit').prop('disabled', true);
	form_disabled = 1;
	//fields
	if($('.emd-ahp').length > 0){
		$.each($('.emd-ahp'), function (ind, val){
			$(val).hide();	
			$(val).closest('.emd-form-row').hide();	
		});
	}
	if($('.emd-select').length > 0){
		$.each($('.emd-select'), function( ind, val ) {
			$(val).select2(); //$(val).data('options'));
			$(val).parent().find('.select2-selection').addClass(emd_form_vars.element_size);
		});
	}
	if($('.emd-datetime').length > 0){
		$.each($('.emd-datetime'), function( ind, val ) {
			$(val).datetimepicker({
				format: 'DD-MM-YYYY HH:mm',
				pickTime: true,
				pickDate: true,
				pickSeconds: false,
				language: emd_form_vars.locale,
			});
		});
	}
	$.fn.Paging = function (form_name,beg){
		var page =1;
		if(beg != 1){
			$('form[id="'+form_name+'"]').fadeOut(1000);
		}
		$('#'+form_name+'_show_link a').click(function(){
			$('form[id="'+form_name+'"]').fadeIn(1000);
			$('#'+form_name+'_hide_link').show();
			$('#'+form_name+'_show_link').hide();
		});
		$('#'+form_name+'_hide_link a').click(function(){
			$('form[id="'+form_name+'"]').fadeOut(1000);
			$('#'+form_name+'_show_link').show();
			$('#'+form_name+'_hide_link').hide();
		});
		$('.pagination-bar a').click(function(){
			if($(this).hasClass('prev')){
				page --;
			}  
			else if($(this).hasClass('next')){
				page ++;
			}  
			else{  
				page = $(this).text();
			}
			var div_id = $(this).closest('.emd-view-results').attr('id');
			var entity = $('#emd_entity').val();
			var view = $('#emd_view').val();
			var app = $('#emd_app').val();
			load_posts(div_id,entity,view,form_name,app);
			return false;
		}); 
		var load_posts = function(div_id,entity,view,form,app){
			$.ajax({
				type: 'GET',
				url: emd_form_vars.ajax_url,
				cache: false,
				async: false,
				data: {action:'emd_form_builder_pagenum',pageno: page,entity:entity,view:view,form:form,app:app},
				success: function(response)
				{
					$('#'+ div_id).html(response);
					if(emd_form_vars.result_templ == 'adv_table'){
						$.fn.showAdvTable();
					}
					$('.pagination-bar a').click(function(){
						if($(this).hasClass('prev')){
							page --;
						}
						else if($(this).hasClass('next')){
							page ++;
						}
						else{
							page = $(this).text();
						}
						var div_id = $(this).closest('.emd-view-results').attr('id');
						var entity = $('#emd_entity').val();
						var view = $('#emd_view').val();
						var app = $('#emd_app').val();
						load_posts(div_id,entity,view,form,app);
						return false;
					});
				},
			});
		}
	}

	$.fn.showLocalStor = function (){
		$.each($('.form-control,.form-check-input'), function() {
			input_name = $(this).attr('name');
			if (input_name && localStorage[input_name]) {
				if($(this).hasClass('emd-select')){
					$(this).val(localStorage[input_name]).trigger('change');
				}
				else if($(this).hasClass('radio')){
					//do nothing 
					//$("input[name="+input_name+"][value=" + localStorage[input_name] + "]").attr('checked', 'checked');
				}
				else {
					$(this).val(localStorage[input_name]);
				}
			}
		});
	}
	$.fn.showAdvTable = function (){
		if($('.emd-table') != 'undefined'){
			$('.emd-table').bootstrapTable();
		}
		$('.emd-table-toolbar').find('li').click(function (e) {
			e.preventDefault();
			$(this).find('i').addClass('fa-check');
			$(this).siblings().find('i').removeClass('fa-check');
			$(this).closest('.emd-table-toolbar').find('.emd-table-export').text($(this).find('a').text());
			var strSelector = $( this ).data('type') == 'selected' ? 'tr.selected' : 'tr';
			$(this).closest('.emd-table-container').find('.emd-table').bootstrapTable( 'refreshOptions', {
				exportDataType: $(this).data('type'),
				exportOptions: {
					tbodySelector: strSelector
				}
			});
		});
	}
	if(emd_form_vars.display_records){
		form_div = $('.emd-form');
		var form_name = form_div.attr('id').replace('-search','');
		$('#'+form_name+'_show_link').hide();
		$('#'+form_name+'_hide_link').show();
		$('#'+form_name+'_show_link a').click(function(){
			$('form[id="'+form_name+'"]').fadeIn(1000);
			$('#'+form_name+'_hide_link').show();
			$('#'+form_name+'_show_link').hide();
		});
		$('#'+form_name+'_hide_link a').click(function(){
			$('form[id="'+form_name+'"]').fadeOut(1000);
			$('#'+form_name+'_show_link').show();
			$('#'+form_name+'_hide_link').hide();
		});
		if(emd_form_vars.enable_ajax){
			$.fn.Paging(form_name,1);
		}
	}
	$('.emd-country').change(function(){
		dep_state = $(this).data('dep-state');
		  $.ajax({
		    type: 'GET',
		    url: emd_form_vars.ajax_url,
		    cache: false,
		    async: false,
		    data: {action:'emd_get_ajax_states',country:$(this).val()},
		    success: function(response)
		    {
			    if(response.length > 0){
				$('#'+dep_state).val("").trigger("change");
				$('#'+dep_state).html(response);
				$('#'+dep_state).closest('.emd-row').show();
			    }
			    else{
				$('#'+dep_state).val("").trigger("change");
				$('#'+dep_state).closest('.emd-row').hide();
			    }
		    },
		  });
	});
	$(".dropdown-menu li a").click(function(){
		var div = $(this).closest('.input-group-btn').attr('id');
		if(div){
			$("#"+div+" .btn").html($(this).text() + '&nbsp;<span class="caret"></span>');
			hidden_var = div.replace('_div','');
			$('#'+hidden_var).val($(this).attr('val'));
		}
	});
	$('.form-container :input').change(function () {
		if($(this).val()){
			$('.emd-form-submit').prop('disabled', false);
		}
		localStorage[$(this).attr('name')] = $(this).val();
	});


	$.fn.showLocalStor();

	if(emd_form_vars.enable_ajax){
		$(document).on('click','.emd-form-submit',function(event){
			sform =  $(this).closest('.form-container');	
			form_data = sform.find(':input').serialize();
			form_div = $(this).closest('.emd-form');
			event.preventDefault();
			if(emd_form_vars.show_captcha){
				grecaptcha.ready(function() {
					grecaptcha.execute(emd_form_vars.recapt_skey, {action: emd_form_vars.recapt_action}).then(function(token) {
						submitted_form = $('.form-container:last #form_name_0').val();
						form_data += '&'+submitted_form+'0_capt='+token;
						$.ajax({
							type: 'POST',
							url:emd_form_vars.ajax_url ,
							data: {action:'emd_formb_submit_ajax_form',form_data:form_data},
							success: function(resp) {
								if(resp.success){
									form_div.find('.emd-form-success-error').html(resp.data.msg);
									form_div.find('.emd-form-success-error').show();
									if(sform.closest('.modal').length > 0){
										mymodal = sform.closest('.modal');
										setTimeout(function() {
											mymodal.modal('hide');
										}, 2000);
										mymodal.parent().find('button').hide();
									}
									new_pos = form_div.find('.emd-form-success-error').parent().parent().offset();
									window.scrollTo(new_pos.left,new_pos.top);
									if(emd_form_vars.after_submit == 'hide'){
										sform.hide();
									}
									localStorage.clear();
								}
								else {
									form_div.find('.emd-form-success-error').html(resp.data.msg);
									form_div.find('.emd-form-success-error').show();
									new_pos = form_div.find('.emd-form-success-error').parent().parent().offset();
									window.scrollTo(new_pos.left,new_pos.top);
								}
							}
						});
					});
				});
			}
			else {
				$.ajax({
					type: 'POST',
					url:emd_form_vars.ajax_url ,
					data: {action:'emd_formb_submit_ajax_form',form_data:form_data},
					success: function(resp) {
						if(resp.success){
							form_div.find('.emd-form-search-results').html(resp.data.msg);
							form_div.find('.emd-form-search-results').show();
							var form = form_div.attr('id').replace('-search','');
							if(emd_form_vars.display_records){
								$.fn.Paging(form,1);
							}
							else {
								$.fn.Paging(form,0);
							}
							if(emd_form_vars.after_submit == 'hide'){
								sform.hide();
							}
							if(emd_form_vars.result_templ == 'adv_table'){
								$.fn.showAdvTable();
							}
						}
					}
				});
			}
		});
	}
	/*else {
		$(document).on('click','.emd-form-submit',function(event){
				return false;
			}
			var valid = $('.form-container:last').valid();
			if(!valid) {
				event.preventDefault();
				return false;
			}
			$('.form-container:last').submit();
			//localStorage.clear();
		});
	}*/
});
