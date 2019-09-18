<?php
/**
 * Settings Functions Advanced
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 5.0
 */
if (!defined('ABSPATH')) exit;

add_action('emd_ext_register','emd_form_register_settings');
add_filter('emd_add_settings_tab','emd_form_settings_tab',9,2);
add_action('emd_show_settings_tab','emd_show_form_settings_tab',10,2);

if (!function_exists('emd_form_settings_tab')) {
	function emd_form_settings_tab($tabs,$app){
		$form_init_variables = get_option($app . '_glob_forms_init_list');
		$form_init_variables = apply_filters('emd_ext_form_var_init', $form_init_variables, $app, '');
		if(!empty($form_init_variables)){
			$tabs['forms'] = __('Forms', 'emd_plugins');
			echo '<p>' . settings_errors($app . '_glob_forms_list') . '</p>';
		}
		return $tabs;
	}
}
if (!function_exists('emd_show_form_settings_tab')) {
	function emd_show_form_settings_tab($app,$active_tab){
		$variables = get_option($app . '_glob_list',Array());
		$form_init_variables = get_option($app . '_glob_forms_init_list');
		$form_init_variables = apply_filters('emd_ext_form_var_init', $form_init_variables, $app, '');
		$form_variables = get_option($app . '_glob_forms_list');
		$form_variables = apply_filters('emd_ext_form_var_init', $form_variables, $app, '');
		if(!empty($form_init_variables)){
			emd_forms_tab($app,$active_tab,$form_init_variables,$form_variables,$variables);
		}
	}
}
if (!function_exists('emd_form_register_settings')) {
	function emd_form_register_settings($app){
		register_setting($app . '_glob_forms_list', $app . '_glob_forms_list', 'emd_forms_sanitize');
	}
}
if (!function_exists('emd_forms_sanitize')) {
	function emd_forms_sanitize($input){
		if(empty($input['app'])){
			return $input;
		}
		$form_variables = get_option($input['app'] . '_glob_forms_init_list');
		$form_variables = apply_filters('emd_ext_form_var_init', $form_variables, $input['app'], '');
		$attr_list = get_option($input['app'] . '_attr_list');
		$shc_list = get_option($input['app'] . '_shc_list');
		$msgs = Array('error_msg','success_msg','noaccess_msg','noresult_msg');
		foreach($form_variables as $kv => $val){
			foreach($val as $kfield => $vfield){
				$change_show = 0;
				if(!in_array($kfield,Array('captcha','csrf','login_reg')) && !in_array($kfield,$msgs)){
					if(isset($input[$kv][$kfield]['req']) && $input[$kv][$kfield]['req'] == 1){
						if(!isset($input[$kv][$kfield]['show'])){
							$form_variables[$kv][$kfield]['show'] = 1;
							$change_show = 1;
						}
						$form_variables[$kv][$kfield]['req'] = 1;
					}
					elseif(!isset($input[$kv][$kfield]['req'])){
						$form_variables[$kv][$kfield]['req'] = 0;
					}
					if($kfield == 'btn'){
						$form_variables[$kv][$kfield]['show'] = 1;
						$form_variables[$kv][$kfield]['req'] = 1;
					}
					elseif(!empty($attr_list[$shc_list['forms'][$kv]['ent']][$kfield]) && $shc_list['forms'][$kv]['type'] == 'submit' && isset($attr_list[$shc_list['forms'][$kv]['ent']][$kfield]['uniqueAttr']) && $attr_list[$shc_list['forms'][$kv]['ent']][$kfield]['uniqueAttr'] == true){
						$form_variables[$kv][$kfield]['show'] = 1;
						if($shc_list['forms'][$kv]['type'] == 'submit'){
							$form_variables[$kv][$kfield]['req'] = 1;
						}
					}
					elseif(isset($input[$kv][$kfield]['show']) && $input[$kv][$kfield]['show'] == 1){
						$form_variables[$kv][$kfield]['show'] = 1;
					}
					elseif($change_show != 1) {
						$form_variables[$kv][$kfield]['show'] = 0;
					}
					if(isset($input[$kv][$kfield]['size'])){
						$form_variables[$kv][$kfield]['size'] = $input[$kv][$kfield]['size'];
					}
					else {
						$form_variables[$kv][$kfield]['size'] = '';
					}
				}
				else {
					if($kfield == 'csrf' && !isset($input[$kv][$kfield])){
						$form_variables[$kv][$kfield] = 0;
					}
					elseif($kfield == 'login_reg' && !isset($input[$kv][$kfield])){
						$form_variables[$kv][$kfield] = 0;
					}
					else {
						$form_variables[$kv][$kfield] = $input[$kv][$kfield];
					}
				}
			}
		}
		return $form_variables;
	}
}
if (!function_exists('emd_forms_tab')) {
	function emd_forms_tab($app,$active_tab,$form_init_variables,$form_variables,$glb_list){
		$attr_list = get_option($app . '_attr_list');
		$tax_list = get_option($app . '_tax_list');
		$rel_list = get_option($app . '_rel_list');
		$shc_list = get_option($app . '_shc_list');
		$ent_map_list = get_option($app .'_ent_map_list');
		$tax_settings = get_option($app .'_tax_settings');
		echo '<div class="tab-content" id="tab-forms"';
		if ( 'forms' != $active_tab ) { 
			echo 'style="display:none;"'; 
		} 
		echo '>';
		echo '<form method="post" action="options.php">';
		settings_fields($app .'_glob_forms_list'); 
		$form_html = '<h4>';
		$form_header = __('Use the section below to show or hide corresponding form elements. Size column refers to the form elements length relative to the other elements in the same row. Total element size in each row can not exceed 12 units. When you hide or show an element you may adjust sizes of the other elements in the same row. The form elements which are in the same row are color coded.','emd-plugins'); 
		$form_content = '</h4>';
		$form_content .= '<div id="forms-list" class="accordion-container"><ul class="outer-border">';
		$unique_text = 0;
		$msgs = Array('error_msg' => __('Error Message','emd-plugins'),'success_msg' => __('Success Mesage','emd-plugins'),'noaccess_msg'=>__('No Access Message'),'noresult_msg' => __('No Result Message'));
		if(!empty($form_init_variables)){
			foreach($form_init_variables as $key => $val){
				$form_label = isset($shc_list['forms'][$key]['page_title']) ? $shc_list['forms'][$key]['page_title'] : ucwords(str_replace("_"," ",$shc_list['forms'][$key]['name']));
				$form_content .= '<li id="' . esc_attr($key) . '" class="control-section accordion-section ';
				$form_content .= (count($form_init_variables) == 1) ? 'open' : '';
				$form_content .= '">';
				$form_content .= '<h3 class="accordion-section-title hndle" tabindex="0">' . $form_label . '</h3>';
				$form_content .= '<div class="accordion-section-content"><div class="inside">';
				$form_content .= '<input type="hidden" name="' . esc_attr($app) . '_glob_forms_list[app]" id="' . esc_attr($app) . '_glob_forms_list_app" value="' . $app . '">';
				$form_content .= '<table class="form-table">';
				$row = 1;
				$max_row = 1;
                                foreach($val as $fval){
                                        if(!empty($fval['row']) && $fval['row'] > $max_row){
                                                $max_row = $fval['row'];
                                        }
                                }
				for($i=1;$i<=$max_row;$i++){
					foreach($val as $elm_key => $elm_val){
						if(!in_array($elm_key,Array('captcha','csrf','login_reg')) && !in_array($elm_key, array_keys($msgs)) && $i == $elm_val['row'] 
					&& ((!empty($ent_map_list[$shc_list['forms'][$key]['ent']]['attrs'][$elm_key]) && $ent_map_list[$shc_list['forms'][$key]['ent']]['attrs'][$elm_key] != 'hide') || empty($ent_map_list[$shc_list['forms'][$key]['ent']]['attrs'][$elm_key]))
					&& ((!empty($ent_map_list[$shc_list['forms'][$key]['ent']]['hide_rels'][$elm_key]) && $ent_map_list[$shc_list['forms'][$key]['ent']]['hide_rels'][$elm_key] != 'hide') || empty($ent_map_list[$shc_list['forms'][$key]['ent']]['hide_rels'][$elm_key]))
					&& ((!empty($tax_settings[$elm_key]['hide']) && $tax_settings[$elm_key]['hide'] != 'hide') || empty($tax_settings[$elm_key]['hide']))
						){
							if(!empty($form_variables) && !empty($form_variables[$key][$elm_key])){
								$elm_val = $form_variables[$key][$elm_key];
							}
							$label = "";
							$req = 0;
							$unique = false;
							$req_disable = 0;
							$form_content .= '<tr'; 
							if($row == $elm_val['row']){
								$form_content .= ' style="background-color: rgb(245, 245, 255);"';
							}
							else {
								$row = $elm_val['row'] + 1;
							}
							if(isset($elm_val['req'])){
								$req = $elm_val['req'];
							}
							$form_content .= '>
								<th scope="row">
								<label for="' . $elm_key . '">';
							if(!empty($attr_list[$shc_list['forms'][$key]['ent']][$elm_key])){
								$label = $attr_list[$shc_list['forms'][$key]['ent']][$elm_key]['label'];
								if($attr_list[$shc_list['forms'][$key]['ent']][$elm_key]['display_type'] == 'file'){
									$req_disable = 1;
								}
								if(isset($attr_list[$shc_list['forms'][$key]['ent']][$elm_key]['uniqueAttr']) && $shc_list['forms'][$key]['type'] == 'submit'){
									$unique_text = 1;
									$unique = $attr_list[$shc_list['forms'][$key]['ent']][$elm_key]['uniqueAttr'];
								}
							}
							elseif(!empty($tax_list[$shc_list['forms'][$key]['ent']][$elm_key])){
								$label = $tax_list[$shc_list['forms'][$key]['ent']][$elm_key]['label'];
							}
							elseif(!empty($rel_list[$elm_key])){
								if($shc_list['forms'][$key]['ent'] == $rel_list[$elm_key]['from']){
									$label = $rel_list[$elm_key]['from_title'];
								}
								else {
									$label = $rel_list[$elm_key]['to_title'];
								}
							}
							elseif($elm_val['label']) {
								$label = $elm_val['label'];
								if(!empty($glb_list[$elm_key])){
									$req_disable = 1;
								}
							}
							$form_content .= sprintf(__('Show %s','emd-plugins'),$label); 
							$form_content .= '</label>
								</th>
								<td>';
							$form_content .= '<input id="' . esc_attr($app) . '_glob_forms_list_' . $elm_key . '_show" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][' . $elm_key . '][show]" type="checkbox" value=1';
							if($elm_val['show'] == 1){
								$form_content .= " checked";
							}
							if($unique == true || $elm_key == 'btn'){
								$form_content .= " disabled";
							}
							if(!$elm_val['size']){
								$elm_val['size'] = "";
							}
							$form_content .= '></input></td><th>Required:</th><td>
							<input type="checkbox" value="1" id="' . esc_attr($app) . '_glob_forms_list_' . $elm_key . '" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][' . $elm_key . '][req]"';
							if($req == 1){
								$form_content .= ' checked';
							}
							if($req_disable == 1 || $unique == true || $elm_key == 'btn'){
								$form_content .= ' disabled';
							}
							$form_content .= '></td>';
							$form_content .= '<th scope="row">Size:</th><td>
							<input type="text" class="small-text" id="' . esc_attr($app) . '_glob_forms_list_' . $elm_key . '" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][' . $elm_key . '][size]" value="' . $elm_val['size'] . '">
							</td></tr>';
						}
					}
				}
				if($val['captcha']){
					$elm_val = $val['captcha'];
					if(!empty($form_variables) && !empty($form_variables[$key]['captcha'])){
						$elm_val = $form_variables[$key]['captcha'];
					}
					$captcha_str = '<tr><th scope="row"><label for="captcha">' . __('Show Captcha','emd-plugins') . '</label></th>';
					$captcha_str .= '<td colspan=5><select id="' . esc_attr($app) . '_glob_forms_list_captcha" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][captcha]">';
					$captcha_vals = Array('never-show' => __('Never Show','emd-plugins'),
							'show-always' => __('Always Show','emd-plugins'),
							'show-to-visitors' => __('Visitors Only','emd-plugins')
						);
					foreach($captcha_vals as $ckey => $cval){
						$captcha_str .= '<option value="' . $ckey . '"';
						if($elm_val == $ckey){
							$captcha_str .= ' selected';
						}		
						$captcha_str .= '>' . $cval . '</option>';
					}
					$captcha_str .= '</select></td></tr>';
					$form_content .= $captcha_str;
				}
				if(isset($val['csrf'])){
					$elm_val = $val['csrf'];
					if(!empty($form_variables) && isset($form_variables[$key]['csrf'])){
						$elm_val = $form_variables[$key]['csrf'];
					}
					$csrf_str = '<tr><th scope="row"><label for="csrf">' . __('CSRF Check','emd-plugins') . '</label></th>';
					$csrf_str .= '<td colspan=5><input id="' . esc_attr($app) . '_glob_forms_list_csrf" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][csrf]" type="checkbox" value=1';
					if($elm_val == 1){
						$csrf_str .= ' checked';
					}
					$csrf_str .= '><p class="description">' . __('Enables Cross-site request forgery protection when selected.','emd-plugins') . '</p></td></tr>';
					$form_content .= $csrf_str;
				}
				if(isset($val['login_reg'])){
					$elm_val = $val['login_reg'];
					if(!empty($form_variables) && isset($form_variables[$key]['login_reg'])){
						$elm_val = $form_variables[$key]['login_reg'];
					}
					$login_reg_str = '<tr><th scope="row"><label for="login_reg">' . __('Show Register / Login Form','emd-plugins') . '</label></th>';
					$login_reg_str .= '<td colspan=5><select id="' . esc_attr($app) . '_glob_forms_list_login_reg" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][login_reg]">';
					$login_reg_options = Array('none' => __('None','emd-plugins'),
									'both' => __('Registration and Login Forms','emd-plugins'),
									'login' => __('Login Form only','emd-plugins'));
					foreach($login_reg_options as $kopt => $vopt){
						$login_reg_str .= '<option value="' . $kopt . '"';
						if($elm_val == $kopt){
							$login_reg_str .= ' selected';
						}
						$login_reg_str .= '>' . $vopt . '</option>';
					}
					$login_reg_str .= '</select>';
					$login_reg_str .= '<p class="description">' . __('Displays or hides registration and login forms on this form for non-logged-in users.','emd-plugins') . '</p></td></tr>';
					$form_content .= $login_reg_str;
				}
				foreach($msgs as $fmsg => $lmsg){
					if(isset($val[$fmsg])){
						$elm_val = $val[$fmsg];
						if(!empty($form_variables) && isset($form_variables[$key][$fmsg])){
							$elm_val = $form_variables[$key][$fmsg];
						}
						$form_content .= '<tr><th scope="row"><label for="' . $fmsg . '">' . $lmsg . '</label></th>';
						$form_content .= '<td colspan=5><textarea class="large-text code" cols=50 rows=5 id="' . esc_attr($app) . '_glob_forms_list_' . $fmsg . '" name="' . esc_attr($app) . '_glob_forms_list[' . $key . '][' . $fmsg . ']">' . $elm_val . '</textarea>';
						$form_content .= '</td></tr>';
					}
				}
				$form_content .= '</table>';
				$form_content .= '</div></div></li>';
			}
		}
		if($unique_text == 1){
			$form_header .= __('The unique form elements are disabled however you can change their sizes.','emd-plugins');
		}
		$form_html .= $form_header . $form_content . '</div>';
		echo $form_html;
		submit_button(); 
		echo '</form></div>';
	}
}
