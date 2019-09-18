jQuery(document).ready(function($){
	if(log_reg_show == 'register'){
		$("#emd-register-container").show();
		$("#emd-login-container").hide();
	}
	else if(log_reg_show == 'both'){
		$('p.emd-register-link').show();
	}
	else {
		$('p.emd-register-link').hide();
	}
	$("p.emd-register-link a").click(function(e){
		e.preventDefault();
		$("#emd-register-container").fadeIn(1000);
		$("#emd-login-container").fadeOut(1000);
	});
	$("p.emd-login-link a").click(function(e){
		e.preventDefault();
		$("#emd-register-container").fadeOut(1000);
		$("#emd-login-container").fadeIn(1000);
	});
});
