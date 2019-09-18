<?php
/**
 * Login and Register form functions/actions
 * @since WPAS 5.3
 */
if (!defined('ABSPATH')) exit;

add_filter('emd_get_login_register_option_for_views','emd_get_login_register_option_for_views',10,2);

function emd_get_login_register_option_for_views($show,$app){
	if(is_user_logged_in()){
		return 'none';
	}
	$misc_settings = get_option($app . '_misc_settings');
	if(!empty($misc_settings['login_reg'])){
		return $misc_settings['login_reg'];
	}
	return 'none';
}


add_action('emd_show_login_register_forms','emd_show_login_register_forms',10,3);

function emd_show_login_register_forms($app,$fname,$show){
	if($show != 'none'){
		$sess_name = strtoupper($app);
		$session_class = $sess_name();
		$error = $session_class->session->get('login_reg_errors');
		if (!empty($error) && $_POST['emd_action'] == $app . '_user_register') {
			$show = "register";
		}	
	}
	$dir_url = constant(strtoupper($app) . "_PLUGIN_URL");
	//check to show login and registration forms
	wp_enqueue_style('emd-login-register', $dir_url . 'assets/css/emd-login-register.css');
	wp_enqueue_script('emd-login-register', $dir_url . 'assets/js/emd-login-register.js',Array('jquery'));
	wp_localize_script("emd-login-register", 'log_reg_show', $show);
	if($show != 'none'){
		ob_start();
		echo "<div class='emd-container'>";
		if (!empty($error)) {
			echo "<div class='emd-alert-container'>";
			echo "<div class='emd-alert-error emd-alert'>" . $error . "</div>";
			echo "</div>";
		}
		elseif(!empty($fname)) {
			$form_init_variables = get_option($app . '_glob_forms_init_list');
			$form_variables = get_option($app . '_glob_forms_list');
			$noaccess_msg = (isset($form_variables[$fname]['noaccess_msg']) ? $form_variables[$fname]['noaccess_msg'] : $form_init_variables[$fname]['noaccess_msg']);
			if(!empty($noaccess_msg)){
				echo "<div class='emd-alert-container'>";
				echo "<div class='emd-alert-error emd-alert'>" . $noaccess_msg . "</div>";
				echo "</div>";
			}
		}
		emd_get_template_part(str_replace("_","-",$app), 'emd-login');
		if ($show == 'both' || (!empty($error) && $_POST['emd_action'] == $app . '_user_register')) {
			emd_get_template_part(str_replace("_","-",$app), 'emd-register');
		}
		echo "</div>";
		$layout = ob_get_clean();
		$session_class->session->set('login_reg_errors', null);
		echo $layout;
	}
	else {
		echo "<div class='noaccess-container'><div class='emd-ncc-msg'>";
		$misc_settings = get_option($app . '_misc_settings');
		if(!empty($misc_settings['no_access_msg'])){
			echo $misc_settings['no_access_msg'];
		}
		else {
			_e('You do not have sufficient permissions to access this page.', 'emd-plugins');
		}
		echo '</div></div>';
	}
}
function emd_show_login_register_options($app){
	$access_views = get_option($app . "_access_views");
	if(!empty($access_views['single']) || !empty($access_views['tax']) || !empty($access_views['archive'])){
		return true;
	}
	$front_ents = emd_find_limitby('frontend', $app);
	if(!empty($front_ents)){
		return true;
	}
	return false;
}
