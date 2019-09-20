<?php
/**
 * Frontend Form Functions
 *
 */
if (!defined('ABSPATH')) exit;

if (is_admin()) {
	add_action('wp_ajax_emd_formb_submit_ajax_form', 'emd_formb_submit_ajax_form');
	add_action('wp_ajax_nopriv_emd_formb_submit_ajax_form', 'emd_formb_submit_ajax_form');
	add_action('wp_ajax_nopriv_emd_check_userEmail', 'emd_check_userEmail');
}

function emd_formb_submit_ajax_form(){
	$form_data = isset($_POST['form_data']) ? $_POST['form_data'] : '';
	if (!empty($form_data)) {
		//parse_str(stripslashes($form_data) , $post_array);
		$post_array = explode("&",stripslashes($form_data));
		foreach ($post_array as $key => $pvalue){
			$mpost = explode("=",urldecode($pvalue));
			if(!empty($mpost[0]) && !empty($mpost[1])){
				if(preg_match('/\[\]$/',$mpost[0])){
					$mpost[0] = preg_replace('/\[\]$/','',$mpost[0]);
					if(empty($_POST[$mpost[0]])){
						$_POST[$mpost[0]] = Array();
						$_REQUEST[$mpost[0]] = Array();
					}
					$_POST[$mpost[0]][] = $mpost[1];
					$_REQUEST[$mpost[0]][] = $mpost[1];
				}
				else {
					$_POST[$mpost[0]] = $mpost[1];
					$_REQUEST[$mpost[0]] = $mpost[1];
				}
			}
		}
		emd_form_builder_process();
	}
	die();
}

add_action('init', 'emd_form_builder_process');

function emd_form_builder_process(){
	if (!empty($_POST) && !empty($_POST['emd_form_id'])) {
		$myform = get_post($_POST['emd_form_id']);
		$fcontent = json_decode($myform->post_content,true);
		if(empty($_POST['emd_step'])){
			$_POST['emd_step'] = 0;
		}
		if (!empty($myform) && $_POST['form_name'] == $fcontent['name']) {
			if(!empty($fcontent['settings']['enable_ajax']) || !empty($_POST['save_step']) || !empty($_POST['save_end'])){
				$ret = check_ajax_referer($fcontent['name'], $fcontent['name'] . '_' . $_POST['emd_step'] . '_nonce',false);
				if ($ret === false) {
					$ret = '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please refresh the page and try again.', 'emd-plugins') . '</a></div>';
					wp_send_json_error(array('status' => 'error', 'msg' => $ret));
					die();
				}
				if(!empty($fcontent['settings']['captcha']) && !empty($fcontent['settings']['captcha_site_key']) && 
					(($fcontent['settings']['captcha'] == 'show_always') || ($fcontent['settings']['captcha'] == 'show_to_visitors' && !is_user_logged_in()))){
					$cap_id = $fcontent['name'] . $_POST['emd_step'] . "_capt";
					if(!empty($_POST[$cap_id]) && !empty($fcontent['settings']['captcha_secret_key'])){
						$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
						$recaptcha_secret = $fcontent['settings']['captcha_secret_key'];
						$recaptcha_response = $_POST[$cap_id];

						$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
						$recaptcha = json_decode($recaptcha);
						// Take action based on the score returned
						if (!$recaptcha->success || $recaptcha->score <= 0.5) {
							$ret = '<div class="text-danger">' . __('Captcha error. Please refresh the page and try again.', 'emd-plugins') . '</div>';
							wp_send_json_error(array('status' => 'error', 'msg' => $ret));
							die();
						}
					}
				}
			}
			else {
				check_admin_referer($fcontent['name'], $fcontent['name'] . '_' . $_POST['emd_step'] . '_nonce');
			}
			$app = $_POST['emd_app'];
			$new_user_id = 0;
			if(!empty($_POST['emd_reg_user'])){
				//register user
				//first check if username and password are valid
				if(!empty($_POST['login_box_reg_username']) && !empty(trim($_POST['login_box_reg_password'])) && !empty(trim($_POST['login_box_reg_confirm_password']))){
					if(trim($_POST['login_box_reg_password']) == trim($_POST['login_box_reg_confirm_password'])){
						if(!validate_username($_POST['login_box_reg_username'])){	
							wp_send_json_error(array('msg' => __('Invalid username','emd-plugins')));
							die();
						}
						elseif(username_exists($_POST['login_box_reg_username'])){
							wp_send_json_error(array('msg' => __('Username already taken','emd-plugins')));
							die();
						}
						else {
							$ent_list = get_option($app . '_ent_list');
							$attr_list = get_option($app . '_attr_list');
							$user_args = Array('user_login' => trim($_POST['login_box_reg_username']),
									'user_pass' => trim($_POST['login_box_reg_password']),
									'user_registered' => date('Y-m-d H:i:s'),
							);
							if(!empty($ent_list[$fcontent['entity']]['user_email_key'])){
								$user_args['user_email'] = trim($_POST[$ent_list[$fcontent['entity']]['user_email_key']]);
							}
							if(!empty($ent_list[$fcontent['entity']]['limit_user_roles'])){
								$user_args['role'] = $ent_list[$fcontent['entity']]['limit_user_roles'][0];
							}
							else {
								$user_args['role'] = get_option('default_role');
							}
							$user_fname_key = '';
							$user_lname_key = '';
							if(!empty($attr_list[$fcontent['entity']])){
								foreach($attr_list[$fcontent['entity']] as $kattr => $vattr){
									if(!empty($vattr['user_map']) && $vattr['user_map'] == 'user_firstname'){
											$user_fname_key = $kattr;
									}
									elseif(!empty($vattr['user_map']) && $vattr['user_map'] == 'user_lastname'){
											$user_lname_key = $kattr;
									}
								}
							}
							if(!empty($user_fname_key) && !empty($_POST[$user_fname_key])){
								$user_args['first_name'] = $_POST[$user_fname_key];
							}	
							if(!empty($user_lname_key) && !empty($_POST[$user_lname_key])){
								$user_args['last_name'] = $_POST[$user_lname_key];
							}
							// Insert new user
							$new_user_id = wp_insert_user($user_args);
							// Validate inserted user
							if(is_wp_error($new_user_id)){
								wp_send_json_error(array('msg' => __('Please try again','emd-plugins')));
								die();
							}
							add_user_meta($new_user_id, 'emd_status', 'draft');
						}
					}
					else {
						$ret = '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please enter same password.', 'emd-plugins') . '</a></div>';
						wp_send_json_error(array('status' => 'error', 'msg' => $ret));
						die();
					}
				}
				else {
					if(empty($_POST['login_box_reg_username'])){
						$ret = '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please enter username.', 'emd-plugins') . '</a></div>';
					}
					elseif(empty($_POST['login_box_reg_password'])){
						$ret = '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please enter password.', 'emd-plugins') . '</a></div>';
					}
					elseif(empty($_POST['login_box_reg_confirm_password'])){
						$ret = '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please enter confirm password.', 'emd-plugins') . '</a></div>';
					}
					wp_send_json_error(array('status' => 'error', 'msg' => $ret));
					die();
				}
			}
			if(!empty($_POST['save_step']) || !empty($_POST['save_end'])){
				$result = emd_form_builder_submit_form($app, $fcontent);
				if ($result === false) {
					$ret = "<div class='well text-danger'>";
					$ret .= '<div class="text-danger">' . $fcontent['settings']['error_msg'] . '</div>';
					$ret .= "</div>";
					wp_send_json_error(array('status' => 'error', 'msg' => $ret));
					die();
				}
				else {
					if(!empty($_POST['save_end'])){
						do_action('emd_form_after_save_end',$app,$fcontent['name'],$result['id']);
					}
					else if(!empty($_POST['save_step'])){
						do_action('emd_form_after_save_step',$app,$fcontent['name'],$result['id']);
					}
					if(!empty($new_user_id)){
						$new_user = get_user_by('id', $new_user_id);
						update_post_meta($result['id'],'wpas_form_submitted_by',$new_user->user_login);
						wp_update_post(Array('ID' => $result['id'],'post_author'=>$new_user_id));
						$rel_list = get_option($app . '_rel_list', Array());
						if(!empty($_POST['emd_hidden_rel']) && !empty($_POST['emd_hidden_rel_val'])){
							update_post_meta($_POST['emd_hidden_rel_val'],'wpas_form_submitted_by',$new_user->user_login);
							wp_update_post(Array('ID' => $_POST['emd_hidden_rel_val'],'post_author'=>$new_user_id));
						}
						//link user_id with entity id
						if(!empty($ent_list[$fcontent['entity']]['user_key'])){
							$user_key = $ent_list[$fcontent['entity']]['user_key'];
							add_post_meta($result['id'], $user_key, $new_user_id);
						}	
					}
					if(!empty($_POST['save_end'])){
						$rel_uniqs = $result['rel_uniqs'];
						if(!empty($rel_uniqs)){
							foreach($rel_uniqs as $kconn => $rel_conn){
								if(is_array($rel_conn)){
									foreach($rel_conn as $rpid){
										do_action('emd_notify', $app, $result['id'], 'rel', 'front_add', Array($kconn => $rpid));
									}
								}
								else{
									do_action('emd_notify', $app, $result['id'], 'rel', 'front_add', Array($kconn => $rel_conn));
								}
							}
						}
						do_action('emd_notify', $app, $result['id'], 'entity', 'front_add', $rel_uniqs);
						if(!empty($_POST['emd_step']) && !empty($_POST['emd_next_step_login_check']) && is_user_logged_in()){
							//check if user logged in	
							if(preg_match('/emd_/',$_POST['emd_next_step_login_check'])){
								$next_ent = $_POST['emd_next_step_login_check'];
								$user_id = get_current_user_id();
								$ent_list = get_option($app . '_ent_list');
								if(!empty($ent_list[$next_ent]['user_key']) && !empty($user_id)){
									//update the previous entity authors for limitby
									$this_user = get_user_by('id', $user_id);
									update_post_meta($result['id'],'wpas_form_submitted_by',$this_user->user_login);
									wp_update_post(Array('ID' => $result['id'],'post_author'=>$user_id));
									//add the relationship between forms
									$user_attr = $ent_list[$next_ent]['user_key'];
									$args = Array('posts_per_page' => 1, 'post_type' => $next_ent, 
											'meta_key' => $user_attr, 'meta_value' => $user_id,'fields'=>'ids');
									$posts = get_posts($args);
									if(!empty($posts)){
										$link = get_permalink($posts[0]);
										//add hidden rel before redirecting
										if(!empty($result['hidden_rel'])){
											p2p_type($result['hidden_rel'])->connect($posts[0],$result['id']);	
											do_action('emd_form_after_login',$result['hidden_rel'],$result['id']);
										}	
									}
								}
							}
							else {
								$link = $_POST['emd_next_step_login_check'];
							}
							if(!empty($link)){
								wp_send_json_success(array('status' => 'redirect', 'link' => $link));
								die();
							}	
							else {
								wp_send_json_success(array('status' => 'success','rel_id'=>'rel_' . $result['hidden_rel'],'rel_val'=>$result['id']));
							}
						}
						elseif(!empty($_POST['end_form'])){
							if(!empty($_POST['emd_hidden_rel']) && !empty($_POST['emd_hidden_rel_val'])){
								$rel = preg_replace('/rel_/','',$_POST['emd_hidden_rel']);
								p2p_type($rel)->connect($result['id'],$_POST['emd_hidden_rel_val']);	
							}
							$ret = "<div class='well text-success'>";
							$ret .= '<div class="text-success">' . $fcontent['settings']['success_msg'] . '</div>';
							$ret .= "</div>";
							wp_send_json_success(array('status' => 'success', 'msg' => $ret));
							die();
						}
						else {
							if(!empty($_POST['emd_hidden_rel']) && !empty($_POST['emd_hidden_rel_val'])){
								$rel = preg_replace('/rel_/','',$_POST['emd_hidden_rel']);
								p2p_type($rel)->connect($result['id'],$_POST['emd_hidden_rel_val']);	
							}
							wp_send_json_success(array('status' => 'success', 'rel_val' => $result['id']));
							die();
						}
					}
					else {
						if(!empty($_POST['emd_step']) && !empty($_POST['emd_next_step_login_check']) && is_user_logged_in()){
							//check if user logged in	
							if(preg_match('/emd_/',$_POST['emd_next_step_login_check'])){
								$next_ent = $_POST['emd_next_step_login_check'];
								$user_id = get_current_user_id();
								if(!empty($ent_list[$next_ent]['user_key']) && !empty($user_id)){
									//update the previous entity authors for limitby
									$this_user = get_user_by('id', $user_id);
									update_post_meta($result['id'],'wpas_form_submitted_by',$this_user->user_login);
									wp_update_post(Array('ID' => $result['id'],'post_author'=>$user_id));
									//add the relationship between forms
									$user_attr = $ent_list[$next_ent]['user_key'];
									$args = Array('posts_per_page' => 1, 'post_type' => $next_ent, 
											'meta_key' => $user_attr, 'meta_value' => $user_id,'fields'=>'ids');
									$posts = get_posts($args);
									if(!empty($posts)){
										$link = get_permalink($posts[0]);
									}
								}
							}
							else {
								$link = $_POST['emd_next_step_login_check'];
							}
							if(!empty($link)){
								wp_send_json_success(array('status' => 'redirect', 'link' => $link));
								die();
							}	
							else {
								wp_send_json_success(array('status' => 'success'));
							}
						}
						else {
							$uniq_keys = emd_form_builder_get_uniq_attrs($result['id'],$app);
							wp_send_json_success(array('status' => 'success','uniq_keys'=> $uniq_keys));
						}
						die();
					}
				}	
				die();
			}
			else {
				if($fcontent['type'] == 'submit'){
					$atts_set = '';
					if(!empty($_POST['emd_form_set'])){
						$atts_set = $_POST['emd_form_set'];
					}
				}
				$form_validate = emd_form_builder_validate($app,$fcontent);
				if($fcontent['type'] == 'search' || $form_validate['success']){
					if($fcontent['type'] == 'submit'){
						//process the form
						$result = emd_form_builder_submit_form($app, $fcontent);
						do_action('emd_form_after_save_end',$app,$fcontent['name'],$result['id']);
						if ($result !== false) {
							$rel_uniqs = $result['rel_uniqs'];
							if(!empty($rel_uniqs)){
								foreach($rel_uniqs as $kconn => $rel_conn){
									if(is_array($rel_conn)){
										foreach($rel_conn as $rpid){
											do_action('emd_notify', $app, $result['id'], 'rel', 'front_add', Array($kconn => $rpid));
										}
									}
									else{
										do_action('emd_notify', $app, $result['id'], 'rel', 'front_add', Array($kconn => $rel_conn));
									}
								}
							}
							do_action('emd_notify', $app, $result['id'], 'entity', 'front_add', $rel_uniqs);
							//lets take a look at confirm_method
							if(!empty($fcontent['settings']['confirm_method']) && $fcontent['settings']['confirm_method'] == 'redirect' && !empty($fcontent['settings']['confirm_url'])){
								$confirm_url = $fcontent['settings']['confirm_url'];
								if(preg_match('/\?(.+)$/',$confirm_url,$matches)){
									if(!empty($matches[1])){
										$params = emd_parse_template_tags($app, $matches[1], $result['id']);
										$confirm_url = preg_replace('/' . $matches[1] . '/',$params,$fcontent['settings']['confirm_url']);
									}
								}	
								header('Location: '. $confirm_url );
								exit;
							}
							else {	
								if(!empty($fcontent['settings']['enable_ajax'])){
									$ret = "<div class='well text-success'>";
									$ret .= '<div class="text-success">' . $fcontent['settings']['success_msg'] . '</div>';
									$ret .= "</div>";
									wp_send_json_success(array('status' => 'success', 'msg' => $ret));
									die();
								}
								else {
									wp_safe_redirect(esc_url_raw(add_query_arg('status','success')));
									exit;
								}
							}
						} else {
							if(!empty($fcontent['settings']['enable_ajax'])){
								$ret = "<div class='well text-danger'>";
								$ret .= '<div class="text-danger">' . $fcontent['settings']['error_msg'] . '</div>';
								$ret .= "</div>";
								wp_send_json_error(array('status' => 'error', 'msg' => $ret));
								die();
							}
							else {
								wp_safe_redirect(esc_url_raw(add_query_arg('status','error')));
								exit;
							}
						}
					}
					elseif($fcontent['type'] == 'search' && !empty($fcontent['settings']['ajax_search'])){
						$ret = emd_form_builder_search_form($app, $fcontent);
						wp_send_json_success(array('status' => 'success', 'msg' => $ret));
						die();
					}
				}
				else {
					//didn't validate, lets show form with error msg
					if(!empty($fcontent['settings']['enable_ajax'])){
						$ret = "<div class='well text-danger'>";
						foreach($form_validate['error'] as $err_msg){
							$ret .= '<div class="text-danger">' . $err_msg . '</div>';
						}
						$ret .= "</div>";
						wp_send_json_error(array('status' => 'error', 'msg' => $ret));
						die();
					}
					else {
						$url = add_query_arg('status','error');
						$url = add_query_arg('resp',json_encode($form_validate['error']),$url);
						wp_safe_redirect(esc_url_raw($url));
						exit;
					}
				}		
			}
		}
	}
}

add_shortcode('emd_form','emd_form_builder_show_form');
function emd_form_builder_show_form($atts){
	if(!empty($atts['id'])){
		$myform = get_post($atts['id']);
		if(empty($myform)){
			return;
		}
		$fcontent = json_decode($myform->post_content,true);
		$app = $fcontent['app'];
		$fentity = $fcontent['entity'];
		$atts_set = '';
		if (!empty($atts['set'])) {
			$atts_set = $atts['set'];
		}
		$show_form = 1;
		$caps = get_option($app . '_add_caps',Array());
		if(!empty($caps['view_' . $fcontent['name']])){
			$show_form = 0;
			if(current_user_can('view_' . $fcontent['name'])){
				$show_form = 1;
			}
		}
		else {
			$show_form = 1;
		}
		//check submit count
		if(!empty($fcontent['settings']['disable_after'])){
			$submits = get_posts(array('post_type' => $fcontent['entity'],'meta_key'=>'wpas_form_name','meta_value'=>$fcontent['name'],'posts_per_page'=>-1, 'fields' => 'ids'));
			$count_submits = count($submits);
			if($count_submits > $fcontent['settings']['disable_after']){
				$show_form = 0;
			}
		}
		//form schedule
		if($show_form == 1 && !empty($fcontent['settings']['schedule_start'])){
			$now = date("Y-m-d H:i:s");
			if($now < $fcontent['settings']['schedule_start']){
				$show_form = 0;
			}
		}
		if($show_form == 1 && !empty($fcontent['settings']['schedule_end'])){
			$now = date("Y-m-d H:i:s");
			if($now > $fcontent['settings']['schedule_end']){
				$show_form = 0;
			}
		}
		$access_views = get_option($app . '_access_views', Array());
		if (!current_user_can('view_' . $fcontent['name']) && !empty($access_views['forms']) && in_array($fcontent['name'], $access_views['forms'])) {
			$show_form = 0;
		}
		if ($show_form == 1) {
			if (!empty($fcontent['settings']['login_reg'])) {
				$show_login_register = $fcontent['settings']['login_reg'];
				if (!is_user_logged_in() && $show_login_register != 'none') {
					do_action('emd_show_login_register_forms', $app, $fcontent['name'], $show_login_register);
					return;
				}
			}
			$status = '';
			$error = '';
			if(!empty($_GET['status'])){
				$status = $_GET['status'];
			}	
			if($status == 'error' && !empty($_GET['resp'])){
				$error = json_decode($_GET['resp']);
			}
			return emd_form_builder_render_form($myform->ID,$app,$fcontent,$error,$status,$atts_set);	
		} else {
			$noaccess_msg = $fcontent['settings']['noaccess_msg'];
			return "<div class='alert alert-info not-authorized'>" . $noaccess_msg . "</div>";
		}
	}
}
function emd_form_builder_display_top($kfield,$cfield,$extra_class=''){
	$top_layout = '';
	if(empty($cfield['display_type']) || (!empty($cfield['display_type']) && $cfield['display_type'] != 'checkbox')){
		$top_layout .= '<div class="form-group';
		if(!empty($cfield['search_opr'])){
			//add if search enable operator
			$top_layout .= ' input-group';
		}
		if(!empty($cfield['display_type']) && $cfield['display_type'] == 'radio'){
			$top_layout .= ' radio';
		}
		if(!empty($cfield['display_type']) && $cfield['display_type'] == 'checkbox_list'){
			$top_layout .= ' checkboxlist';
		}
		if(!empty($extra_class)){
			$top_layout .= ' ' . $extra_class;
		}
		$top_layout .= '">';

	}
	if(!empty($cfield['display_type']) && $cfield['display_type'] == 'checkbox'){
		$top_layout .= '<label class="form-check-label ' . $kfield . '" for="' . $kfield . '">';
		$top_layout .= $cfield['label'];
	}
	elseif($cfield['label_position'] == 'top' || $cfield['label_position'] == 'left'){
		$top_layout .= '<label class="control-label ' . $kfield . '" for="' . $kfield . '"';
		if(!empty($cfield['search_opr'])){
			$top_layout .= ' style="display:table-caption;"';
		}
		$top_layout .= '>';
		$top_layout .= '<span id="label_' . $kfield . '">';
		$top_layout .= $cfield['label'];
		$top_layout .= '</span>';
	}
	else {
		$top_layout .= '<label class="nolabel ' . $kfield . '" for="' . $kfield . '">';
	}
	$top_layout .= '<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
		<a data-html="true" href="#" tabindex=-1 data-toggle="tooltip"';
	if(empty($cfield['desc'])){
		$top_layout .= ' style="display:none;"';
	}
	else {
		$top_layout .= ' title="' . $cfield['desc'] . '"';
	}
	$top_layout .= ' id="info_' . $kfield . '" class="helptip">
		<span class="field-icons info"></span></a>';
	$top_layout .= '<a href="#" data-html="true" tabindex=-1 data-toggle="tooltip" title="' . $cfield['label'] . ' field is required" id="req_' . $kfield . '" class="helptip"';
	if (empty($cfield['req'])) { 
		$top_layout .= ' style="display:none;"';
	}
	$top_layout .= '>
		<span class="field-icons required"></span>
		</a>
		</span>
		</label>';
	return $top_layout;
}
function emd_form_builder_blt_display($kfield,$cfield,$set_arrs){
	if($kfield == 'blt_title'){
		$blt_lay = '<input type="text" name="' . $kfield . '" id="' . $kfield . '" class="text ' . $cfield['element_size'] . ' form-control';
		if(!empty($cfield['req'])){
			$blt_lay .= ' required ';
		}
		if(!empty($cfield['css_class'])){
			$blt_lay .= ' ' . $cfield['css_class'];
		}
		if(!empty($cfield['uniqueAttr'])){
			$blt_lay .= ' uniqueattr ';
		}
		$blt_lay .= '" placeholder="' . $cfield['placeholder'] . '"';
		if(!empty($_POST[$kfield])){
			$blt_lay .= ' value="' . $_POST[$kfield] . '"';
		}
		elseif(!empty($_GET[$kfield])){
			$blt_lay .= ' value="' . sanitize_text_field($_GET[$kfield]) . '"';
		}
		elseif(!empty($set_arrs['attr'][$kfield])) {
			$blt_lay .= ' value="' . $set_arrs['attr'][$kfield] . '"';
		}
		$blt_lay .= '/>';
	}
	else {
		$blt_lay = '<textarea name="' . $kfield . '" id="' . $kfield . '" class="form-control emd-sumnote" placeholder="' . $cfield['placeholder'] . '">';
		$blt_lay .= '</textarea>';
	}
	return $blt_lay;
}
function emd_form_builder_attr_display($kfield,$cfield,$set_arrs){
	$attr_lay = '';
	switch($cfield['display_type']){
		case 'hidden':
			if(!empty($set_arrs['attr'][$kfield])) {
				$hidden_val = $set_arrs['attr'][$kfield];
			}
			elseif(!empty($cfield['hidden_func'])){
				$hidden_val = emd_get_hidden_func($cfield['hidden_func']);
			}
			$attr_lay .= "<input type='hidden' value='" . $hidden_val  . "' name='" . $kfield . "'>";
			break;		
		case 'select':
		case 'select_advanced':
			if(!empty($cfield['select_list']) && $cfield['select_list'] == 'country'){
				$options = emd_get_country_list();
				$def = 'US';
				$ent_map_list = get_option($cfield['app'] . '_ent_map_list');
				if(!empty($ent_map_list[$cfield['entity']]['default_country'][$kfield])) {
					$def = $ent_map_list[$cfield['entity']]['default_country'][$kfield];
				}
				//dependent_state
				$dep_state = $cfield['dependent_state'];
			}	
			elseif(!empty($cfield['select_list']) && $cfield['select_list'] == 'state'){
				$def_country = 'US';
				$ent_map_list = get_option($cfield['app'] . '_ent_map_list');
				if(!empty($ent_map_list[$cfield['entity']]['default_country'][$cfield['dependent_country']])) {
					$def_country = $ent_map_list[$cfield['entity']]['default_country'][$cfield['dependent_country']];
				}
				$options = emd_get_country_states($def_country);	
				$def = '';
				if(!empty($ent_map_list[$cfield['entity']]['default_state'][$kfield])){
					$def = $ent_map_list[$cfield['entity']]['default_state'][$kfield];
				}
			}
			else {
				$def = $cfield['std'];
				$options = $cfield['options'];
			}
			$attr_lay .= '<select name="' . $kfield . '" id="' . $kfield . '" class="' . $cfield['element_size'] . ' form-control emd-select';
			if(!empty($cfield['req'])){
				$attr_lay .= ' required ';
			}
			if(!empty($cfield['uniqueAttr'])){
				$attr_lay .= ' uniqueattr ';
			}
			if(!empty($dep_state)){
				$attr_lay .= ' emd-country';
			}
			if(!empty($cfield['css_class'])){
				$attr_lay .= ' ' . $cfield['css_class'];
			}
			$attr_lay .= '"';
			if(!empty($dep_state)){
				$attr_lay .= ' data-dep-state="' . $dep_state . '"';
			}
			$attr_lay .= ' placeholder="' . $cfield['placeholder'] . '" data-options="{\"allowClear\":true,\"placeholder\":\"Please Select\"}">';
			if(!empty($options)){
				foreach($options as $kopt => $vopt){
					$attr_lay .= '<option value="' . $kopt . '"';
					if($def == $kopt){
						$attr_lay .= ' selected';
					}
					$attr_lay .= '>' . $vopt . '</option>';
				}
			}
			$attr_lay .= '</select>';
			break;
		case 'wysiwyg':
			$attr_lay .= '<textarea name="' . $kfield . '" id="' . $kfield . '" class="form-control emd-sumnote';
			if(!empty($cfield['req'])){
				$attr_lay .= ' required ';
			}
			if(!empty($cfield['css_class'])){
				$attr_lay .= ' ' . $cfield['css_class'];
			}
			$attr_lay .= '" placeholder="' . $cfield['placeholder'] . '">';
			if(!empty($_POST[$kfield])){
				$attr_lay .= $_POST[$kfield];
			}
			$attr_lay .= '</textarea>';
			break;
		case 'checkbox':
			$attr_lay .= '<input type="checkbox" name="' . $kfield . '[]" id="' . $kfield . '" class="';
			if(!empty($cfield['req'])){
				$attr_lay .= 'required';
			}
			if(!empty($cfield['css_class'])){
				$attr_lay .= ' ' . $cfield['css_class'];
			}
			$attr_lay .= ' emd-input-md form-check-input"';
			$attr_lay .= '/>';
			break;
		case 'radio':
			if(!empty($cfield['options'])){
				foreach($cfield['options'] as $kopt => $vopt){
					$attr_lay .= '<div class="form-check';
					if(!empty($cfield['display_inline'])){
						$attr_lay .= ' form-check-inline';
					}
					$attr_lay .= ' radio">';
					$attr_lay .= '<input type="radio" name="' . $kfield . '" id="' . $kfield . '_' . $kopt . '" value="' . $kopt . '" class="radio emd-input-md form-check-input';
					if(!empty($cfield['css_class'])){
						$attr_lay .= ' ' . $cfield['css_class'];
					}
					$attr_lay .= '">';
					$attr_lay .= '<label class="form-check-label" for="' . $kfield . '_' . $kopt . '">' . $vopt  . '</label>';
					$attr_lay .= '</div>';
				}
			}
			break;
		case 'checkbox_list':
			if(!empty($cfield['options'])){
				foreach($cfield['options'] as $kopt => $vopt){
					$attr_lay .= '<div class="form-check';
					if(!empty($cfield['display_inline'])){
						$attr_lay .= ' form-check-inline';
					}
					$attr_lay .= ' checkbox_list">';
					$attr_lay .= '<input type="checkbox" name="' . $kfield . '[]" id="' . $kfield . '_' . $kopt . '" value="' . $kopt . '" class="checkbox_list emd-input-md form-check-input';
					if(!empty($cfield['css_class'])){
						$attr_lay .= ' ' . $cfield['css_class'];
					}
					$attr_lay .= '">';
					$attr_lay .= '<label class="form-check-label" for="' . $kfield . '_' . $kopt . '">' . $vopt  . '</label>';
					$attr_lay .= '</div>';
				}
			}
			break;
		case 'datetime':
			$attr_lay .= '<input type="text" name="' . $kfield . '" id="' . $kfield . '" class="emd-datetime text';
			if(!empty($cfield['req'])){
				$attr_lay .= ' required';
			}
			if(!empty($cfield['validate'])){
				$attr_lay .= ' ' . $cfield['validate'];
			}
			if(!empty($cfield['css_class'])){
				$attr_lay .= ' ' . $cfield['css_class'];
			}
			if(!empty($cfield['dformat'])){
				$dformat = str_replace('dd','DD',$cfield['dformat']);
				$dformat = str_replace('HH:mm','HH:ss',$dformat);
				$dformat = str_replace('mm','MM',$dformat);
				$dformat = str_replace('yy','YYYY',$dformat);
			}
			$attr_lay .= ' '  . $cfield['element_size'] . ' form-control" placeholder="' . $cfield['placeholder'] . '"';
			if(!empty($cfield['dformat'])){
				$dformat = str_replace('dd','DD',$cfield['dformat']);
				$dformat = str_replace('HH:mm','HH:ss',$dformat);
				$dformat = str_replace('mm','MM',$dformat);
				$dformat = str_replace('yy','YYYY',$dformat);
				$attr_lay .= ' data-format="' . $dformat . '"';
			}
			$attr_lay .= '/>';
			break;
		case 'file':
			$attr_lay .= '<div class="small text-muted" style="margin:0.75rem 0 0.50rem;">';
			if (!empty($cfield['max_files'])) {
				$attr_lay .= sprintf(__('Max number of files: %s', 'emd-plugins') , $cfield['max_files']);
			}
			if (!empty($cfield['max_file_size'])) {
				$attr_lay .= '<br> ' . sprintf(__('Max file size: %s', 'emd-plugins') , $cfield['max_file_size']) . ' KB';
			}
			if (!empty($cfield['file_exts'])) {
				$attr_lay .= '<br> ' . sprintf(__('File extensions allowed: %s', 'emd-plugins') , $cfield['file_exts']);
			}
			$attr_lay .= '</div>';
			$attr_lay .= '<div class="form-group">';
			$attr_lay .= '<input type="file" name="' . $kfield . '" id="' . $kfield . '" class="emd-file">';
			$attr_lay .= '</div>';
			break;
		case 'text':
		default:
			$attr_lay .= '<input type="text" name="' . $kfield . '" id="' . $kfield . '" class="text';
			if(!empty($cfield['req'])){
				$attr_lay .= ' required';
			}
			if(!empty($cfield['uniqueAttr'])){
				$attr_lay .= ' uniqueattr';
			}
			if(!empty($cfield['user_email_key'])){
				$attr_lay .= ' user_email_key ';
			}
			if(!empty($cfield['validate'])){
				$attr_lay .= ' ' . $cfield['validate'];
			}
			$data_fields = '';
			if(!empty($cfield['validate_with_vals'])){
				foreach($cfield['validate_with_vals'] as $vkey => $vval){
					$attr_lay .= ' ' . $vkey;
					$data_fields .= ' data-' . $vkey . '="' . $vval . '"';
				}
			}	
			if(!empty($cfield['css_class'])){
				$attr_lay .= ' ' . $cfield['css_class'];
			}
			$attr_lay .= ' '  . $cfield['element_size'] . ' form-control" placeholder="' . $cfield['placeholder'] . '"';
			$attr_lay .= $data_fields;
			if(!empty($cfield['form_type']) && $cfield['form_type'] == 'submit'){
				$current_user = wp_get_current_user();
				if (!empty($current_user) && !empty($current_user->user_email) && !empty($cfield['email'])) {
					$attr_lay .= ' value="' . (string)$current_user->user_email . '"';
				}
				elseif(!empty($_POST[$kfield])){
					$attr_lay .= ' value="' . $_POST[$kfield] . '"';
				}
				elseif(!empty($_GET[$kfield])) {
					$attr_lay .= ' value="' . sanitize_text_field($_GET[$kfield]) . '"';
				}
				elseif(!empty($set_arrs['attr'][$kfield])) {
					$attr_lay .= ' value="' . $set_arrs['attr'][$kfield] . '"';
				}
				elseif (!empty($current_user) && !empty($current_user->$umap) && !empty($cfield['user_map'])) {
					$umap = $cfield['user_map'];
					$attr_lay .= ' value="' . (string)$current_user->$umap . '"';
				}
			}
			elseif(!empty($cfield['form_type']) && $cfield['form_type'] == 'search'){
				$attr_lay .= ' autocomplete="off"';
			}
				
			if(!empty($cfield['autocomplete'])){
				$attr_lay .= ' autocomplete="' . $cfield['autocomplete'] . '"';
			}
			$attr_lay .= '/>';
			break;
	}
	if(in_array($cfield['display_type'],Array('wysiwyg','text')) && !empty($cfield['search_opr'])){
		$attr_lay .= '<input type="hidden" name="opr__' . $cfield['form_name'] . '_' . $kfield . '" id="opr__' . $cfield['form_name'] . '_' . $kfield . '" value="like">';
		$attr_lay .= '<div class="input-group-btn opr" id="opr__quote_search_' . $kfield . '_div">';
		$attr_lay .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">&#8776;&nbsp;<span class="caret"></span></button>';
		$attr_lay .= '<ul class="dropdown-menu dropdown-menu-right" role="menu" style="min-width:0;">';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="like">&#8776;</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="not_like">!&#8776;</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="is">=</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="is_not">&#8800;</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="begins">' . __('Begins', 'empd-pro') . '</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="ends">' . __('Ends', 'empd-pro'). '</a></li>';
		$attr_lay .= '<li><a href="#" onclick="return false;" val="word">' . __('Word', 'empd-pro'). '</a></li>';
		$attr_lay .= '</ul></div>';
	}
	return $attr_lay;
}
function emd_form_builder_txn_display($kfield,$cfield){
	$def = '';
	if (!empty($_GET[$kfield])) {
		$def = sanitize_text_field($_GET[$kfield]);
	} elseif (!empty($set_arrs['tax'][$kfield])) {
		$def = $set_arrs['tax'][$kfield];
	}
	$options = Array();
	$txn_obj = get_terms($kfield, array(
				'hide_empty' => 0
				));
	foreach ($txn_obj as $txn) {
		$options[$txn->slug] = $txn->name;
	}
	$txn_lay = '<select name="' . $kfield;
	if($cfield['type'] == 'multi'){
		$txn_lay .=  '[]';
	}
	$txn_lay .= '" id="' . $kfield . '" class="' . $cfield['element_size'] . ' form-control emd-select';
	if(!empty($cfield['req'])){
		$txn_lay .= ' required ';
	}	
	if(!empty($cfield['css_class'])){
		$txn_lay .= ' ' . $cfield['css_class'];
	}
	$txn_lay .= '" ';
	if($cfield['type'] == 'multi'){
		$txn_lay .= ' multiple';
	}
	$txn_lay .= ' data-options="{\"allowClear\":true,\"placeholder\":\"Please Select\"}">';
	if(!empty($options)){
		foreach($options as $kopt => $vopt){
			$txn_lay .= '<option value="' . $kopt . '"';
			if($def == $kopt){
				$txn_lay .= ' selected';
			}
			$txn_lay .= '>' . $vopt . '</option>';
		}
	}
	$txn_lay .= '</select>';
	return $txn_lay;
}
function emd_form_builder_rel_display($kfield,$cfield,$rel_conf,$set_arrs){
	$def = '';
	$hide = 0;
	if($rel_conf['from'] == $cfield['entity']){
		$other_ent = $rel_conf['to'];
	}
	else {
		$other_ent = $rel_conf['from'];
	}
	$rel_key = preg_replace('/rel_/','',$kfield);
	if (!empty($_GET[$kfield])) {
		$def = sanitize_text_field($_GET[$kfield]);
	} elseif (!empty($set_arrs['rel'][$rel_key])) {
		$def = $set_arrs['rel'][$rel_key];
		$hide = 1;
	}
	if($hide == 1){
		$rel_lay .= '<input type="hidden" name="' . $kfield . '" id="' . $kfield . '" value="' . $def . '">';
	}
	else {
		//get entity values
		$options = Array();
		$rel_ent_args = Array(
				'post_type' => $other_ent,
				'numberposts' => - 1,
				'orderby' => 'title',
				'order' => 'ASC'
				);
		
		$rel_ent_args = apply_filters('emd_form_builder_rel_args', $rel_ent_args, $cfield['app'], $other_ent, $cfield['entity']);
		$front_ents = emd_find_limitby('frontend', $cfield['app']);
		if (!empty($front_ents) && in_array($other_ent, $front_ents)) {
			$pids = emd_get_form_pids($cfield['app'], $other_ent);
			$rel_ent_args['post__in'] = $pids;
		}
		$rel_ent_pids = get_posts($rel_ent_args);
		if (!empty($rel_ent_pids)) {
			foreach ($rel_ent_pids as $my_ent_pid) {
				$options[$my_ent_pid->ID] = get_the_title($my_ent_pid->ID);
			}
		}
		if(empty($options) && !empty($def)){
			$options[$def] = get_the_title($def->ID);
		}
		$rel_lay = '<select name="' . $kfield;
		if($rel_conf['type'] == 'many-to-many' || $cfield['type'] == 'multi'){
			$rel_lay .=  '[]';
		}
		$rel_lay .= '" id="' . $kfield . '" class="' . $cfield['element_size'] . ' form-control emd-select';
		if(!empty($cfield['req'])){
			$rel_lay .= ' required ';
		}
		if(!empty($cfield['css_class'])){
			$rel_lay .= ' ' . $cfield['css_class'];
		}
		$rel_lay .= '" ';
		if($rel_conf['type'] == 'many-to-many' || $cfield['type'] == 'multi'){
			$rel_lay .= ' multiple';
		}
		$rel_lay .= ' data-options="{\"allowClear\":true,\"placeholder\":\"Please Select\"}">';
		if(!empty($options)){
			foreach($options as $kopt => $vopt){
				$rel_lay .= '<option value="' . $kopt . '"';
				if($def == $kopt){
					$rel_lay .= ' selected';
				}
				$rel_lay .= '>' . $vopt . '</option>';
			}
		}
		$rel_lay .= '</select>';
	}
	return $rel_lay;
}
function emd_form_builder_render_form($form_id,$app,$fcontent,$error='',$submit_result,$atts_set=''){
	$fentity = $fcontent['entity'];
	$attr_list = get_option($app . '_attr_list',Array());
	$ent_list = get_option($app . '_ent_list',Array());
	$txn_list = get_option($app . '_tax_list', Array());
	$rel_list = get_option($app . '_rel_list', Array());

	$local_vars['ajax_url'] = admin_url('admin-ajax.php');
	$local_vars['validate_msg']['required'] = __('This field is required.', 'emd-plugins');
	$local_vars['validate_msg']['passw'] = __('Please enter same password.', 'emd-plugins');
	$local_vars['validate_msg']['remote'] = __('Please fix this field.', 'emd-plugins');
	$local_vars['validate_msg']['email'] = __('Please enter a valid email address.', 'emd-plugins');
	$local_vars['validate_msg']['url'] = __('Please enter a valid URL.', 'emd-plugins');
	$local_vars['validate_msg']['date'] = __('Please enter a valid date.', 'emd-plugins');
	$local_vars['validate_msg']['dateISO'] = __('Please enter a valid date ( ISO )', 'emd-plugins');
	$local_vars['validate_msg']['number'] = __('Please enter a valid number.', 'emd-plugins');
	$local_vars['validate_msg']['digits'] = __('Please enter only digits.', 'emd-plugins');
	$local_vars['validate_msg']['creditcard'] = __('Please enter a valid credit card number.', 'emd-plugins');
	$local_vars['validate_msg']['equalTo'] = __('Please enter the same value again.', 'emd-plugins');
	$local_vars['validate_msg']['maxlength'] = __('Please enter no more than {0} characters.', 'emd-plugins');
	$local_vars['validate_msg']['minlength'] = __('Please enter at least {0} characters.', 'emd-plugins');
	$local_vars['validate_msg']['rangelength'] = __('Please enter a value between {0} and {1} characters long.', 'emd-plugins');
	$local_vars['validate_msg']['range'] = __('Please enter a value between {0} and {1}.', 'emd-plugins');
	$local_vars['validate_msg']['max'] = __('Please enter a value less than or equal to {0}.', 'emd-plugins');
	$local_vars['validate_msg']['min'] = __('Please enter a value greater than or equal to {0}.', 'emd-plugins');
	$local_vars['unique_msg'] = __('Please enter a unique value.', 'emd-plugins');
	$local_vars['user_email_msg'] = __('This email has been already registered.', 'emd-plugins');
	if(!empty($fcontent['settings']['enable_ajax'])){
		$local_vars['enable_ajax'] = 1;
	}
	if(!empty($fcontent['settings']['ajax_search'])){
		$local_vars['enable_ajax'] = 1;
	}
	if(!empty($fcontent['settings']['after_submit'])){
		$local_vars['after_submit'] = $fcontent['settings']['after_submit'];
	}
	if(!empty($fcontent['settings']['disable_submit'])){
		$local_vars['disable_submit'] = $fcontent['settings']['disable_submit'];
	}
	if($fcontent['type'] == 'search' && !empty($fcontent['settings']['result_templ'])){
		$local_vars['result_templ'] = $fcontent['settings']['result_templ'];
	}
	$local_vars['element_size'] = 'emd-input-md';
	if(!empty($fcontent['settings']['element_size'])){
		switch($fcontent['settings']['element_size']){
			case 'small':
				$local_vars['element_size'] = 'emd-input-sm';
				break;
			case 'large':
				$local_vars['element_size'] = 'emd-input-lg';
				break;
			case 'medium':
			default:
				$local_vars['element_size'] = 'emd-input-md';
				break;
		}
	}
	//AYSEN
	$form_steps = Array();
	$next_page_count = 0;
	$page_count = count($fcontent['layout']);
	$nsettings = $fcontent['settings'];
	$form_steps[$fcontent['name']]['beg'] = 1;
	$form_steps[$fcontent['name']]['end'] = $page_count;
	while(emd_form_builder_get_next_form($nsettings)){
		$next_form = get_post($nsettings['confirm_form']);
		$next_content = json_decode($next_form->post_content,true);
		$next_page_count = count($next_content['layout']);
		$form_steps[$next_content['name']]['beg'] = $page_count + 1;
		$page_count += $next_page_count;	
		$form_steps[$next_content['name']]['end'] = $page_count;
		$next_fcontent[$nsettings['confirm_form']] = $next_content;
		$nsettings = $next_content['settings'];
	}
	$local_vars['form_steps'] = $form_steps;
	$local_vars['laststep'] = $page_count - 1;
	if($page_count > 1){
		$local_vars['has_paging'] = true;
		$local_vars['button_size'] = 'emd-btn-std';
		$finish_btn_class = 'emd-form-submit emd-btn';
		if(!empty($next_page_count) && !empty($next_content)){
			$pick_fcontent = $next_content;
		}
		else {
			$pick_fcontent = $fcontent;
		}	
		if(!empty($pick_fcontent['settings']['submit_button_type'])){
			$finish_btn_class .= ' emd-' . $pick_fcontent['settings']['submit_button_type'];
		}
		if(!empty($pick_fcontent['settings']['submit_button_class']) && $pick_fcontent['settings']['submit_button_class'] != 'btn-custom'){
			$finish_btn_class .= ' ' . $pick_fcontent['settings']['submit_button_class'];
		}
		if(!empty($pick_fcontent['settings']['submit_button_size'])){
			$finish_btn_class .= ' emd-' . $pick_fcontent['settings']['submit_button_size'];
			$local_vars['button_size'] = ' emd-' . $pick_fcontent['settings']['submit_button_size'];
		}
		if(!empty($pick_fcontent['settings']['submit_button_block'])){
			$finish_btn_class .= ' emd-btn-block';
		}
		$local_vars['finish_class'] = $finish_btn_class;
		$local_vars['finish_name'] = 'submit_' . $pick_fcontent['name'];
		$local_vars['finish_label'] = $pick_fcontent['settings']['submit_button_label'];
		if(!empty($pick_fcontent['settings']['submit_button_fa']) && !empty($pick_fcontent['settings']['submit_button_fa_pos']) && $pick_fcontent['settings']['submit_button_fa_pos'] == 'left'){
			$local_vars['finish_fa_pos'] = 'left';
			$local_vars['finish_fa_class'] = $pick_fcontent['settings']['submit_button_fa'];
			if(!empty($pick_fcontent['settings']['submit_button_fa_size'])){
				$local_vars['finish_fa_size'] = $pick_fcontent['settings']['submit_button_fa_size'];
			}
			//$layout .= "<i class='fa fa-fw fas " . $fcontent['settings']['submit_button_fa'] . "' aria-hidden='true'></i>";
		}
		if(!empty($pick_fcontent['settings']['submit_button_fa']) && !empty($pick_fcontent['settings']['submit_button_fa_pos']) && $pick_fcontent['settings']['submit_button_fa_pos'] == 'right'){
			$local_vars['finish_fa_pos'] = 'right';
			$local_vars['finish_fa_class'] = $pick_fcontent['settings']['submit_button_fa'];
			if(!empty($pick_fcontent['settings']['submit_button_fa_size'])){
				$local_vars['finish_fa_size'] = $pick_fcontent['settings']['submit_button_fa_size'];
			}
		}
		if(!empty($pick_fcontent['settings']['wizard_style'])){
			$local_vars['wizard'] = $pick_fcontent['settings']['wizard_style'];
		}
		if(!empty($pick_fcontent['settings']['wizard_toolbar'])){
			$local_vars['wizard_toolbar'] = $pick_fcontent['settings']['wizard_toolbar'];
		}
		$local_vars['wizard_effect'] = 'none';
		if(!empty($pick_fcontent['settings']['wizard_trans_effect'])){
			$local_vars['wizard_effect'] = $pick_fcontent['settings']['wizard_trans_effect'];
		}
		$local_vars['wizard_speed'] = 400;
		if(!empty($pick_fcontent['settings']['wizard_trans_speed'])){
			$local_vars['wizard_speed'] = $pick_fcontent['settings']['wizard_trans_speed'];
		}
		if(!empty($pick_fcontent['settings']['wizard_cancel_url'])){
			$local_vars['wizard_cancel'] = $pick_fcontent['settings']['wizard_cancel_url'];
		}
		if(!empty($pick_fcontent['settings']['wizard_save_step'])){
			$local_vars['wizard_save_step'] = $pick_fcontent['settings']['wizard_save_step'];
		}	
	}
	$local_vars['conditional_rules'] = emd_form_builder_cond_vars($fcontent['layout'],$fentity,$attr_list,$txn_list);
	$req_hide_vars = emd_form_builder_req_hide_vars($fcontent['layout']);
	$local_vars['req'] = $req_hide_vars['req'];
	if(!defined(strtoupper($app) . "_PLUGIN_URL")){
		return '';
	}	
	$dir_url = constant(strtoupper($app) . "_PLUGIN_URL");
	//Enqueue	
	wp_enqueue_script('jquery');
	if($fcontent['type'] == 'submit'){
		wp_enqueue_script('wpas-jvalidate', $dir_url . 'assets/ext/jvalidate/wpas.validate.min.js', array('jquery'),'',true);
		$local_vars['locale'] = get_locale();
		$local_vars['months'] = __('January_February_March_April_May_June_July_August_September_October_November_December', 'emd-plugins');
		$local_vars['weekdays'] = __('Su_Mo_Tu_We_Th_Fr_Sa', 'emd-plugins');
	}

	//file begin
	$ret_attrs = emd_form_builder_check_attr($fcontent['layout'],$fentity,$ent_list,$attr_list,$txn_list,$rel_list);
	if(!empty($ret_attrs['select_attrs'])){
		wp_enqueue_style('wpas-select2', $dir_url . 'assets/ext/bselect24/select2.min.css');
		wp_enqueue_script('wpas-select2-js', $dir_url . 'assets/ext/bselect24/select2.full.min.js');
	}
	if(!empty($ret_attrs['date_attrs'])){
		wp_enqueue_style('wpas-bdatetime', $dir_url . '/assets/ext/bdatetime/bootstrap-datetimepicker.min.css');
		wp_enqueue_script('wpas-bdatetime', $dir_url . '/assets/ext/bdatetime/bootstrap-datetimepicker.min.js',array('wpas-boot-js'),'',true);
	}
	if($fcontent['type'] == 'submit' && !empty($ret_attrs['wysiwyg_attrs'])){
		wp_enqueue_style('wpas-summer-lite', $dir_url . '/includes/emd-form-builder-lite/css/summernote-lite.css');
		wp_enqueue_script('wpas-summer-lite-js', $dir_url . '/includes/emd-form-builder-lite/js/summernote-lite.js',array(),'',true);
	}
	if($fcontent['type'] == 'submit' && !empty($ret_attrs['file_attrs'])){
		wp_enqueue_script('wpas-filepicker-js');
		foreach($ret_attrs['file_attrs'] as $myfattr){
			$local_vars[$myfattr]['theme'] = 'Bootstrap';
			//$local_vars['emd_qr_file_upload']['theme'] = 'custom'; //Pure
			$local_vars[$myfattr]['btnText'] = __('File Upload', 'emd-plugins');
			$local_vars[$myfattr]['url'] = admin_url('admin-ajax.php');
			$local_vars[$myfattr]['path'] = $dir_url;
			$local_vars[$myfattr]['nonce'] = wp_create_nonce('emd_load_file');
			$local_vars[$myfattr]['del_nonce'] = wp_create_nonce('emd_delete_file');
			$local_vars[$myfattr]['errorMsg'] = __('ERROR:', 'emd-plugins');
			$local_vars[$myfattr]['invalidExtError'] = __('Invalid file type.', 'emd-plugins');
			$local_vars[$myfattr]['sizeError'] = __('File size is greater than allowed limit.', 'emd-plugins');
			$local_vars[$myfattr]['maxUploadError'] = __('Maximum number of allowable file uploads has been exceeded.', 'emd-plugins');
			$ent_map_list = get_option($app . '_ent_map_list', Array());
			if (!empty($ent_map_list[$fentity]['max_files'][$myfattr])) {
				$local_vars[$myfattr]['maxFileCount'] = $ent_map_list[$fentity]['max_files'][$myfattr];
			}
			if (!empty($ent_map_list[$fentity]['max_file_size'][$myfattr])) {
				$local_vars[$myfattr]['maxSize'] = $ent_map_list[$fentity]['max_file_size'][$myfattr];
			} else {
				$server_size = ini_get('upload_max_filesize');
				if (preg_match('/M$/', $server_size)) {
					$server_size = preg_replace('/M$/', '', $server_size);
					$server_size = $server_size * 1000;
				}
				$local_vars[$myfattr]['maxSize'] = $server_size;
			}
			if (!empty($ent_map_list[$fentity]['file_exts'][$myfattr])) {
				$local_vars[$myfattr]['allowedExtensions'] = $ent_map_list[$fentity]['file_exts'][$myfattr];
			}
		}
	}
	//file end


	if($fcontent['type'] == 'submit' && !empty($local_vars['conditional_rules'])){
		wp_enqueue_script('cond-js', $dir_url . '/assets/js/cond-forms.js',array(),'',true);
	}
	$func_name = $app . "_enq_bootstrap";
	$func_name('css');
	if($fcontent['type'] == 'search' && !empty($fcontent['settings']['enable_operators'])){
		$func_name('js');
	}
	if($fcontent['type'] == 'search' && !empty($fcontent['settings']['result_templ']) && $fcontent['settings']['result_templ'] == 'adv_table'){
		$func_name('js');
		wp_enqueue_script('bootjs-cdn', $dir_url . 'includes/emd-form-builder-lite/js/advtable.js',array(),'',true);
		wp_enqueue_style('bootcss-cdn', $dir_url . 'includes/emd-form-builder-lite/css/advtable.css');
	}
	if(!empty($fcontent['settings']['css_enq'])){
		$css_enqs = explode(';',$fcontent['settings']['css_enq']);
		if(!empty($css_enqs)){
			$count_css = 1;
			foreach($css_enqs as $mycss){
				wp_enqueue_style('emd-form-css-' . $count_css, $mycss);
				$count_css++;
			}
		}
	}
	if(!empty($fcontent['settings']['js_enq'])){
		$js_enqs = explode(';',$fcontent['settings']['js_enq']);
		if(!empty($js_enqs)){
			$count_js = 1;
			foreach($js_enqs as $myjs){
				wp_enqueue_script('emd-form-js-' . $count_js, $myjs,array(),'',true);
				$count_js++;
			}
		}
	}
	//captcha	
	if(!empty($fcontent['settings']['captcha']) && !empty($fcontent['settings']['captcha_site_key']) && 
		(($fcontent['settings']['captcha'] == 'show_always') || ($fcontent['settings']['captcha'] == 'show_to_visitors' && !is_user_logged_in()))){
		if(!empty($fcontent['settings']['captcha_version']) && $fcontent['settings']['captcha_version'] == 'recapt_v3'){
			wp_enqueue_script('gcaptc', 'https://www.google.com/recaptcha/api.js?render=' . $fcontent['settings']['captcha_site_key']);
			$local_vars['recapt_skey'] = $fcontent['settings']['captcha_site_key'];
			$local_vars['recapt_action'] = 'myform'; 
			$local_vars['show_captcha'] = true;
		}
	}
	if(!empty($fcontent['settings']['display_records'])){
		$local_vars['display_records'] = 1;
	}
			
	if($fcontent['type'] == 'submit' && !empty($local_vars['wizard'])){
		wp_enqueue_style('emd-wizard', $dir_url . '/includes/emd-form-builder-lite/css/emd-wizard.min.css');
		wp_enqueue_script('emd-wizard-js', $dir_url . '/includes/emd-form-builder-lite/js/emd-wizard.js',array(),'',true);
	}
	if($fcontent['type'] == 'submit'){
		wp_enqueue_style('form-frontend-css', $dir_url . '/includes/emd-form-builder-lite/css/emd-form-frontend.css');
		wp_enqueue_script('form-frontend-js', $dir_url . '/includes/emd-form-builder-lite/js/emd-form-frontend.js',array(),'',true);
		wp_localize_script('form-frontend-js', 'emd_form_vars', $local_vars);
	}
	elseif($fcontent['type'] == 'search'){
		wp_enqueue_style('form-frontend-css', $dir_url . '/includes/emd-form-builder-lite/css/emd-form-frontend.css');
		wp_enqueue_script('form-frontend-js', $dir_url . '/includes/emd-form-builder-lite/js/emd-form-frontend-search.js',array(),'',true);
		wp_localize_script('form-frontend-js', 'emd_form_vars', $local_vars);
		wp_enqueue_style('emd-simple-table-css', $dir_url . '/includes/emd-form-builder-lite/css/emd-simple-table.css');
		wp_enqueue_style('emd-pagination');
	}
	$func_name_custom = $app . "_enq_custom_css_js";
	$func_name_custom();

	do_action('emd_ext_form_enq', $app, $fcontent['name']);
	//Enqueue	

	$layout = "<div style='position:relative;' id='" . $fcontent['name'] . "-" . $fcontent['type'] . "' class='emd-form emd-container'>";
	if(!empty($error)){
		$layout .= "<div class='form-alerts'><div class='well text-danger'>";
		foreach($error as $err_msg){
			$layout .= '<div class="text-danger">' . $err_msg . '</div>';
		}
		$layout .= "</div></div>";
	}
	elseif($submit_result == 'success'){
		$layout .= "<div class='form-alerts'><div class='well text-success'>";
		$layout .= '<div class="text-success">' . $fcontent['settings']['success_msg'] . '</div>';
		$layout .= "</div></div>";
		if(!empty($fcontent['settings']['after_submit']) && $fcontent['settings']['after_submit'] == 'hide'){	
			$layout .= "</div><!--container-end-->";
			return $layout;
		}
	}
	elseif($submit_result == 'error'){
		$layout .= "<div class='form-alerts'><div class='well text-danger'>";
		$layout .= '<div class="text-danger">' . $fcontent['settings']['error_msg'] . '</div>';
		$layout .= "</div></div>";
	}
	elseif(!empty($fcontent['settings']['enable_ajax']) || !empty($next_content)){
		$layout .= "<div class='form-alerts emd-form-success-error'></div>";
	} 

	if($page_count == 1){
		$layout .= emd_form_builder_get_form_hidden($fcontent,$form_id,$app,$atts_set);
	}
	elseif($page_count > 1){
		$layout .= '<div id="emd-wizard"'; 
		if(!empty($next_content['settings']['wizard_vertical'])){
			$layout .= ' class="vertical"';
		}
		$layout .= '>
			<ul>';
		foreach($fcontent['layout'] as $p => $cont_page){
			$step_title = 'Step 1';
			if(!empty($cont_page['step_title'])){
				$step_title = $cont_page['step_title'];
			}
			$step_desc = '';
			if(!empty($cont_page['step_desc'])){
				$step_desc = $cont_page['step_desc'];
			}
			$layout .= '<li><a href="#step-' . $p . '"><div class="emd-step-title">' . $step_title . '</div><div class="emd-step-desc">' . $step_desc . '</div>';
			if(!empty($fcontent['settings']['wizard_style']) && $fcontent['settings']['wizard_style'] == 'circles'){
				//$layout .= "<div class='emd-step-icon'><i class='fa fa-fw fas fa-dot-circle-o' aria-hidden='true'></i></div>";
			}
			$layout .= '</a></li>';
			$pcounter = $p;
		}
		if(!empty($next_page_count) && !empty($next_fcontent)){
			foreach($next_fcontent as $fid => $ncontent){
				foreach($ncontent['layout'] as $p => $cont_page){
					$pcounter ++;
					$step_title = 'Step 1';
					if(!empty($cont_page['step_title'])){
						$step_title = $cont_page['step_title'];
					}
					$step_desc = '';
					if(!empty($cont_page['step_desc'])){
						$step_desc = $cont_page['step_desc'];
					}
					$layout .= '<li><a href="#step-' . $pcounter . '"><div class="emd-step-title">' . $step_title . '</div><div class="emd-step-desc">' . $step_desc . '</div>';
					if(!empty($fcontent['settings']['wizard_style']) && $fcontent['settings']['wizard_style'] == 'circles'){
						//$layout .= "<div class='emd-step-icon'><i class='fa fa-fw fas fa-dot-circle-o' aria-hidden='true'></i></div>";
					}
					$layout .= '</a></li>';
				}
			}
		}
		$layout .= '</ul>';
		$layout .= '<div>';
	}
	$dcounter = 1;	
	$has_login_reg_box = Array();
	foreach($fcontent['layout'] as $kpage => $cpage){
		if($page_count > 1){
			$layout .= '<div id="step-' . $kpage . '" class="">';
			if(!empty($next_page_count) && !empty($next_fcontent)){
				if($kpage < count($fcontent['layout'])){
					$has_login_reg_box = emd_form_builder_check_loginbox($fcontent['layout'][$kpage + 1]['rows'],$fcontent['entity']);
				}
				else {
					$has_login_reg_box = emd_form_builder_check_loginbox($next_content['layout'][1]['rows'],$next_content['entity']);
				}
				$layout .= emd_form_builder_get_form_hidden($fcontent,$form_id,$app,$atts_set,$has_login_reg_box,$kpage);
			}
		}
		if(!empty($cpage['rows'])){
			$layout .= emd_form_builder_show_rows($cpage,$fcontent,$attr_list,$app,$atts_set,$txn_list,$rel_list,$ent_list);
		}
		if($page_count > 1){
			if(!empty($next_page_count) && !empty($next_fcontent)){
				$layout .= "</form>";
			}
			$layout .= '</div>';
		}
		$dcounter ++;
	}
	if(!empty($next_page_count) && !empty($next_fcontent)){
		$form_rels = Array();
		if(!empty($rel_list)){
			foreach($rel_list as $rkey => $rval){
				if($rval['from'] == $fcontent['entity']){
					$form_rels[$rval['to']] = $rkey;
				}
				elseif($rval['to'] == $fcontent['entity']){
					$form_rels[$rval['from']] = $rkey;
				}
			}
		}
		foreach($next_fcontent as $fid => $ncontent){
			if(!empty($form_rels[$ncontent['entity']])){
				$hidden_rel = $form_rels[$ncontent['entity']];
			}
			$form_rels = Array();
			foreach($rel_list as $rkey => $rval){
				if($rval['from'] == $ncontent['entity']){
					$form_rels[$rval['to']] = $rkey;
				}
				elseif($rval['to'] == $ncontent['entity']){
					$form_rels[$rval['from']] = $rkey;
				}
			}
			foreach($ncontent['layout'] as $kpage => $cpage){
				$layout .= '<div id="step-' . $dcounter . '" class="">';
				if($kpage < count($ncontent['layout'])){
					$has_login_reg_box = emd_form_builder_check_loginbox($ncontent['layout'][$kpage + 1]['rows'],$ncontent['entity']);
				}
				$layout .= emd_form_builder_get_form_hidden($ncontent,$fid,$app,$atts_set,$has_login_reg_box,$dcounter,$hidden_rel);
				if(!empty($cpage['rows'])){
					$layout .= emd_form_builder_show_rows($cpage,$ncontent,$attr_list,$app,$atts_set,$txn_list,$rel_list,$ent_list,$hidden_rel);
				}
				$layout .= "</form>";
				$layout .= '</div>';
				$dcounter ++;
			}
		}
	}
	//check cust fields ???
	$cust_fields = Array();
	$cust_fields = apply_filters('emd_get_cust_fields', $cust_fields, $fentity);
	if($page_count == 1){
		$layout .= "<button class='emd-form-submit emd-btn";
		if(!empty($fcontent['settings']['submit_button_type'])){
			$layout .= ' emd-' . $fcontent['settings']['submit_button_type'];
		}
		if(!empty($fcontent['settings']['submit_button_class']) && $fcontent['settings']['submit_button_class'] != 'btn-custom'){
			$layout .= ' ' . $fcontent['settings']['submit_button_class'];
		}
		if(!empty($fcontent['settings']['submit_button_size'])){
			$layout .= ' emd-' . $fcontent['settings']['submit_button_size'];
		}
		if(!empty($fcontent['settings']['submit_button_block'])){
			$layout .= ' emd-btn-block';
		}
		$layout .= "' type='submit' value='submit' name='submit_" . $fcontent['name'] . "'>";
		if(!empty($fcontent['settings']['submit_button_fa']) && !empty($fcontent['settings']['submit_button_fa_pos']) && $fcontent['settings']['submit_button_fa_pos'] == 'left'){
			$layout .= "<i class='fa fa-fw fas " . $fcontent['settings']['submit_button_fa'] . "' aria-hidden='true'></i>";
		}
		if(!empty($fcontent['settings']['submit_button_label'])){
			$layout .= $fcontent['settings']['submit_button_label'];
		}
		else {
			$layout .= __('Submit','emd-plugins');
		}
		if(!empty($fcontent['settings']['submit_button_fa']) && !empty($fcontent['settings']['submit_button_fa_pos']) && $fcontent['settings']['submit_button_fa_pos'] == 'right'){
			$layout .= "<i class='fa fa-fw fas " . $fcontent['settings']['submit_button_fa'];
			if(!empty($fcontent['settings']['submit_button_fa_size'])){
				$layout .= " " . $fcontent['settings']['submit_button_fa_size'];
			}
			$layout .=  "' aria-hidden='true'></i>";
		}
		$layout .= "</button>";
		$layout .= "</form>";
	}
	else {
		$layout .= '</div></div>';
	}
	if($fcontent['type'] == 'search'){
		if(!empty($fcontent['settings']['ajax_search'])){
			if(!empty($fcontent['settings']['display_records'])){
				$layout .= emd_form_builder_search_form($app, $fcontent);
			}
			else {
				$layout .= '<div class="emd-form-search-results"></div>';
			}
		}
		else {
			$layout .= emd_form_builder_search_form($app, $fcontent);
		}
	}
	$layout .= "</div><!--container-end-->";
	return $layout;
}
function emd_form_builder_validate($app,$fcontent){
	$ret['success'] = 1;
	return $ret;
	$ret['error'] = Array();
	$attr_list = get_option($app . '_attr_list',Array());
	//check each layout field for validation
	foreach($fcontent['layout'] as $kpage => $cpage){
		foreach($cpage['rows'] as $krow => $crow){
			foreach($crow as $fcount => $field){
				foreach($field as $kfield => $cfield){
					if(!empty($cfield['show'])){
						if(!empty($cfield['req']) && empty($_POST[$kfield])){
							$ret['error'][] = $cfield['label'] . ' is required';
						}
						elseif(!empty($_POST[$kfield]) && !empty($attr_list[$fcontent['entity']][$kfield]['email']) && !is_email($_POST[$kfield])){
							$ret['error'][] = $cfield['label'] . ': Please enter a valid email address';
						}
					}
				}
			}
		}
	}
	if(!empty($ret['error'])){
		$ret['success'] = 0;
	}
	return $ret;
}
function emd_form_builder_show_rows($cpage,$fcontent,$attr_list,$app,$atts_set,$txn_list,$rel_list,$ent_list,$hidden_rel=''){
	$set_arrs = Array();
	if(!empty($atts_set)){
		$set_arrs = emd_parse_set_filter($atts_set);
	}
	$layout = '';

	$fentity = $fcontent['entity'];
	if($fcontent['type'] == 'submit' && !empty($attr_list[$fcontent['entity']])){
		foreach($attr_list[$fcontent['entity']] as $kattr => $vattr){
			if(!empty($vattr['uniqueAttr'])){
				if(!empty($_GET[$kattr])){
					$val_hidden = $_GET[$kattr];
				}
				elseif(!empty($set_arrs['attr'][$kattr])){

					$val_hidden = $set_arrs['attr'][$kattr];
				}
				elseif(!empty($vattr['hidden_func'])) {
					$val_hidden = emd_get_hidden_func($vattr['hidden_func']);
				}
				if(!empty($val_hidden)){
					$layout .= "<input type='hidden' name='" . $kattr . "' value='" . $val_hidden . "'>";
				}
			}
		}
	}
	foreach($cpage['rows'] as $krow => $crow){
		$layout .= '<div class="emd-form-row emd-row">';	
		foreach($crow as $fcount => $field){
			foreach($field as $kfield => $cfield){
				if(!empty($cfield['show'])){
					if($kfield == 'login_box_username'){
						$layout .= emd_form_builder_display_login_box($cfield);
					}
					elseif($kfield == 'login_box_reg_username'){
						$layout .= '<div class="emd-form-row emd-row">';
					}
					//if this field is an html field
					if(!empty($cfield['value'])){
						$cfield['size'] = 12;
					}
					$layout .= '<div class="emd-form-field emd-col'; 
					switch($fcontent['settings']['targeted_device']){
						case 'desktops':
							$layout .= ' emd-md-' . $cfield['size'] . ' emd-sm-12 emd-xs-12';
							break;
						case 'phones':
							$layout .= ' emd-xs-' . $cfield['size'];
							break;
						case 'large_desktops':
							$layout .= ' emd-lg-' . $cfield['size'] . ' emd-md-12 emd-sm-12 emd-xs-12';
							break;
						case 'tablets':
						default:
							$layout .= ' emd-sm-' . $cfield['size']. ' emd-xs-12';
							break;
					}
					if(preg_match('/^login_box/',$kfield)){
						$cfield['req'] = 1;
						if(in_array($kfield,Array('login_box_username','login_box_password'))){
							$layout .= ' emd-login';
						}
						else {
							$layout .= ' emd-reg';
						}
					}
					$layout .= '" data-field="' . $kfield . '"';
					if(in_array($kfield,Array('login_box_username','login_box_password'))){
						$layout .= ' style="display:none;"';	
					}
					$layout .= '>';
					$cfield['label_position'] = 'top';
					if(!empty($fcontent['settings']['label_position'])){
						$cfield['label_position'] = $fcontent['settings']['label_position'];
					}
					$cfield['element_size'] = 'emd-input-md';
					if(!empty($fcontent['settings']['element_size'])){
						switch($fcontent['settings']['element_size']){
							case 'small':
								$cfield['element_size'] = 'emd-input-sm';
								break;
							case 'large':
								$cfield['element_size'] = 'emd-input-lg';
								break;
							case 'medium':
							default:
								$cfield['element_size'] = 'emd-input-md';
								break;
						}
					}
					if(!empty($fcontent['settings']['display_inline'])){
						$cfield['display_inline'] = $fcontent['settings']['display_inline'];
					}
					if(preg_match('/^login_box/',$kfield)){
						$layout .= emd_form_builder_display_top($kfield,$cfield);
						$layout .= emd_form_builder_login_fields_display($kfield,$cfield);
						$layout .= '</div>';	
					}
					if(in_array($kfield,Array('blt_title','blt_content','blt_excerpt'))){
						if(!empty($ent_list[$fentity]['unique_keys']) && in_array($kfield,$ent_list[$fentity]['unique_keys'])){
							$cfield['uniqueAttr'] = true;
						}
						$layout .= emd_form_builder_display_top($kfield,$cfield);
						$layout .= emd_form_builder_blt_display($kfield,$cfield,$set_arrs);
						$layout .= '</div>';	
					}
					elseif(!empty($attr_list[$fentity]) && in_array($kfield,array_keys($attr_list[$fentity]))){
						$cfield['display_type'] = $attr_list[$fentity][$kfield]['display_type'];
						if($fcontent['type'] == 'search' && !empty($fcontent['settings']['enable_operators'])){
							$cfield['search_opr'] = 1;
							$cfield['form_name'] = $fcontent['name'];
						}
						if($cfield['display_type'] != 'hidden' && $cfield['display_type'] != 'checkbox'){
							$layout .= emd_form_builder_display_top($kfield,$cfield);
						}
						$cfield['user_map'] = '';
						if(!empty($attr_list[$fentity][$kfield]['user_map'])){
							$cfield['user_map'] = $attr_list[$fentity][$kfield]['user_map'];
						}
						if(!empty($attr_list[$fentity][$kfield]['select_list'])){
							$cfield['select_list'] = $attr_list[$fentity][$kfield]['select_list'];
						}
						if(!empty($attr_list[$fentity][$kfield]['options'])){
							$cfield['options'] = $attr_list[$fentity][$kfield]['options'];
						}
						if(!empty($attr_list[$fentity][$kfield]['dependent_country'])){
							$cfield['dependent_country'] = $attr_list[$fentity][$kfield]['dependent_country'];
						}
						if(!empty($attr_list[$fentity][$kfield]['dependent_state'])){
							$cfield['dependent_state'] = $attr_list[$fentity][$kfield]['dependent_state'];
						}
						if(!empty($attr_list[$fentity][$kfield]['email'])){
							$cfield['email'] = $attr_list[$fentity][$kfield]['email'];
						}
						if(!empty($attr_list[$fentity][$kfield]['std'])){
							$cfield['std'] = $attr_list[$fentity][$kfield]['std'];
						}
						if(!empty($attr_list[$fentity][$kfield]['uniqueAttr'])){
							$cfield['uniqueAttr'] = $attr_list[$fentity][$kfield]['uniqueAttr'];
						}
						if(!empty($ent_list[$fentity]['user_email_key']) && $kfield == $ent_list[$fentity]['user_email_key']){
							$cfield['user_email_key'] = true;
						}
						if(!empty($attr_list[$fentity][$kfield]['hidden_func'])){
							$cfield['hidden_func'] = $attr_list[$fentity][$kfield]['hidden_func'];
						}
						if(!empty($attr_list[$fentity][$kfield]['options'])){
							$cfield['options'] = $attr_list[$fentity][$kfield]['options'];
						}
						$cfield['entity'] = $fentity;
						$cfield['app'] = $app;
						$cfield['form_type'] = $fcontent['type'];
						if($cfield['display_type'] == 'checkbox'){
							$layout .= '<div class="form-group ' . $cfield['display_type'] . '">';;
							$layout .= '<div class="form-check ';
							if(!empty($fcontent['settings']['display_inline'])){
								$layout .= 'form-check-inline ';
							}
							$layout .= $cfield['display_type'] . '">';
						}
						$validation_fields = Array('postalCodeCA','mobileUK','ipv6','ipv4','vinUS','integer','postcodeUK','zipcodeUS','nowhitespace',
							'lettersonly','alphanumeric','letterswithbasicpunc','phoneUK','phoneUS','creditcard','digits','number','url','email');
						foreach($validation_fields as $validate){
							if(!empty($attr_list[$fentity][$kfield][$validate])){
								$cfield['validate'] = $validate;
							}
						}
						$validation_val_fields = Array('minlength','maxlength');
						foreach($validation_val_fields as $validate_val){
							if(!empty($attr_list[$fentity][$kfield][$validate_val])){
								$cfield['validate_with_vals'][$validate_val] = $attr_list[$fentity][$kfield][$validate_val];
							}
						}
						if($cfield['display_type'] == 'file'){
							$ent_map_list = get_option($app . '_ent_map_list', Array());
							if (!empty($ent_map_list[$fentity]['max_files'][$kfield])) {
								$cfield['max_files'] = $ent_map_list[$fentity]['max_files'][$kfield];
							}
							if (!empty($ent_map_list[$fentity]['max_file_size'][$kfield])) {
								$cfield['max_file_size'] = $ent_map_list[$fentity]['max_file_size'][$kfield];
							} else {
								$server_size = ini_get('upload_max_filesize');
								if (preg_match('/M$/', $server_size)) {
									$server_size = preg_replace('/M$/', '', $server_size);
									$server_size = $server_size * 1000;
								}
								$cfield['max_file_size'] = $server_size;
							}
							if (!empty($ent_map_list[$fentity]['file_exts'][$kfield])) {
								$cfield['file_exts'] = $ent_map_list[$fentity]['file_exts'][$kfield];
							}
						}
						if($cfield['display_type'] == 'datetime' && !empty($attr_list[$fentity][$kfield]['dformat'])){
							$cfield['dformat'] = $attr_list[$fentity][$kfield]['dformat']['dateFormat'];
							if(!empty($attr_list[$fentity][$kfield]['dformat']['timeFormat'])){
								$cfield['dformat'] .= ' ' . $attr_list[$fentity][$kfield]['dformat']['timeFormat'];
							}
						}

						$layout .= emd_form_builder_attr_display($kfield,$cfield,$set_arrs);
						if($cfield['display_type'] != 'hidden' && $cfield['display_type'] != 'checkbox'){
							$layout .= '</div>';	
						}
						elseif($cfield['display_type'] == 'checkbox'){
							$layout .= emd_form_builder_display_top($kfield,$cfield);
							$layout .= '</div>';	
							$layout .= '</div>';	
						}
					}
					elseif(!empty($txn_list[$fentity]) && in_array($kfield,array_keys($txn_list[$fentity]))){
						$cfield['type'] = $txn_list[$fentity][$kfield]['type'];
						if($fcontent['type'] == 'search'){
							$cfield['type'] = 'multi';
						}
						$layout .= emd_form_builder_display_top($kfield,$cfield);
						$layout .= emd_form_builder_txn_display($kfield,$cfield,$set_arrs);
						$layout .= '</div>';	
					}
					elseif(!empty($rel_list) && array_key_exists($kfield,$rel_list)){
						$relf = preg_replace('/^rel_/','',$kfield);
						$extra_class = '';
						if(!empty($set_arrs['rel'][$relf])){
							$extra_class = 'emd-hide-form-rel';
						}
						if($fcontent['type'] == 'search'){
							$cfield['type'] = 'multi';
						}
						$layout .= emd_form_builder_display_top($kfield,$cfield,$extra_class);
						$cfield['entity'] = $fentity;
						$cfield['app'] = $app;
						$layout .= emd_form_builder_rel_display($kfield,$cfield,$rel_list[$kfield],$set_arrs);
						$layout .= '</div>';	
					}
					elseif(!empty($cfield['value'])){
						//if this field is an html field
						$layout .= $cfield['value'];
					}	
					$layout .= '</div>';	
					if($kfield == 'login_box_password'){
						$layout .= emd_form_builder_display_login_button($fcontent,$ent_list,$cfield,$hidden_rel);
						$layout .= '</div>';
					}
				}
			}
		}
		$layout .= '</div>';	
	}
	return $layout;
}
function emd_form_builder_get_next_form($nsettings){
	if(!empty($nsettings['confirm_method']) && $nsettings['confirm_method'] == 'form' && !empty($nsettings['confirm_form'])){
		return true;
	}
	return false;
}
function emd_form_builder_get_form_hidden($fcontent,$form_id,$app,$atts_set,$has_login_reg_box=Array(),$stepnum=0,$hidden_rel=''){
	$layout = "<form id='" . $fcontent['name'];
	if(!empty($stepnum)){
		$layout .= "_" . $stepnum;
	}
	$layout .= "' action='" . esc_url_raw(remove_query_arg('status')) . "' method='post' class='form-container";
	if(!empty($fcontent['settings']['label_position']) && $fcontent['settings']['label_position'] == 'left'){
		$layout .= " form-inline";
	}
	if(!empty($fcontent['settings']['form_class'])){
		$layout .= " " . $fcontent['settings']['form_class'];
	}
	$layout .= "'>";
	$layout_hidden = "<input type='hidden' value='" . $fcontent['name']  . "' name='form_name' id='form_name_" . $stepnum . "'>";
	$layout_hidden .= "<input type='hidden' value='" . $form_id  . "' name='emd_form_id' id='emd_form_id_" . $stepnum . "'>";
	$layout_hidden .= "<input type='hidden' value='" . $app  . "' name='emd_app' id='emd_app_" . $stepnum . "'>";
	$layout_hidden .= "<input type='hidden' value='" . $fcontent['entity']  . "' name='emd_ent' id='emd_ent_" . $stepnum . "'>";
	$layout_hidden .= "<input type='hidden' value='" . $stepnum  . "' name='emd_step' id='emd_step_" . $stepnum . "'>";
	$layout_hidden .= wp_nonce_field($fcontent['name'], $fcontent['name'] . '_' . $stepnum . '_nonce',true,false);
	if(!empty($has_login_reg_box['login'])){
		$layout_hidden .= "<input type='hidden' value='" . $has_login_reg_box['login'] . "' name='emd_next_step_login_check' id='emd_next_step_login_check_" . $stepnum . "'>";
	}	
	if(!empty($hidden_rel)){
		$layout_hidden .= "<input type='hidden' name='" . $hidden_rel . "' id='" . $hidden_rel . "'>";
	}	
	$set_arrs = Array();
	if (!empty($atts_set)) {
		$layout_hidden .= "<input type='hidden' value='" . $atts_set . "' name='emd_form_set'>";
	}
	if($fcontent['type'] == 'submit'){
		//hidden
		$layout_hidden .= "<input type='hidden' value='" . $fcontent['name'] . "' name='wpas_form_name'>";
		//hidden_funcs
		$wpas_form_submitted_by = emd_get_hidden_func('user_login');
		$layout_hidden .= "<input type='hidden' value='" . $wpas_form_submitted_by . "' name='wpas_form_submitted_by'>";
		$wpas_form_submitted_ip = emd_get_hidden_func('user_ip');
		$layout_hidden .= "<input type='hidden' value='" . $wpas_form_submitted_ip . "' name='wpas_form_submitted_ip'>";
	}
	/*if(!empty($fcontent['settings']['captcha']) && !empty($fcontent['settings']['captcha_site_key']) && 
		(($fcontent['settings']['captcha'] == 'show_always') || ($fcontent['settings']['captcha'] == 'show_to_visitors' && !is_user_logged_in()))){
		$layout_hidden .= "<input type='hidden' id='". $fcontent['name'] . $stepnum . "_capt' name='" . $fcontent['name'] . $stepnum . "_capt'>";
	}*/
	//if honeypot is enabled
	if(!empty($fcontent['settings']['honeypot'])){	
		$honeys = Array('web_site','url','email','company','name','phone','twitter');
		$honey_key = $honeys[rand(0, count($honeys) - 1)];
		$honeypot = Array('label' => ucwords(str_replace('_', ' ',$honey_key)), 'size' => 12, 'css_class' => 'emd-ahp', 
				'label_position' => 'top', 'element_size' => 'emd-input-md','display_type'=>'text', 'autocomplete' => 'off');
		$layout_hidden .= '<div class="emd-form-row emd-row">';	
		$layout_hidden .= '<div class="emd-form-field emd-col'; 
		switch($fcontent['settings']['targeted_device']){
			case 'desktops':
				$layout_hidden .= ' emd-md-' . $honeypot['size'] . ' emd-sm-12 emd-xs-12';
				break;
			case 'phones':
				$layout_hidden .= ' emd-xs-' . $honeypot['size'];
				break;
			case 'large_desktops':
				$layout_hidden .= ' emd-lg-' . $honeypot['size'] . ' emd-md-12 emd-sm-12 emd-xs-12';
				break;
			case 'tablets':
			default:
				$layout_hidden .= ' emd-sm-' . $honeypot['size']. ' emd-xs-12';
				break;
		}
		$layout_hidden .= '" data-field="' . $honey_key . '">';
		$layout_hidden .= emd_form_builder_display_top($honey_key,$honeypot);
		$layout_hidden .= emd_form_builder_attr_display($fcontent['name'] . $stepnum . "_" . $honey_key,$honeypot,Array());
		$layout_hidden .= '</div></div></div>';
	}
	$layout .= $layout_hidden;
	return $layout;
}
function emd_form_builder_display_login_box($cfield){
	$layout = '<div class="emd-login-label"><a href="#" class="emd-login-box">';
	$layout .= $cfield['login_label'] . '</a></div>';
	$layout .= '<div class="emd-reg-label" style="display:none;"><a href="#" class="emd-register-login">'; 
	$layout .= $cfield['reg_label'] . '</a></div>';
	$layout .= '<div class="emd-reg-error" style="display:none;"></div>'; 
	return $layout;
}
function emd_form_builder_display_login_button($fcontent,$ent_list,$cfield,$hidden_rel=''){
	$layout = '<div class="emd-login-button" style="display:none;">';
	$layout .= "<button class='emd-login-submit emd-btn emd-btn-primary";
	if(!empty($fcontent['settings']['submit_button_type'])){
		$layout .= ' emd-' . $fcontent['settings']['submit_button_type'];
	}
	if(!empty($fcontent['settings']['submit_button_class']) && $fcontent['settings']['submit_button_class'] != 'btn-custom'){
		$layout .= ' ' . $fcontent['settings']['submit_button_class'];
	}
	if(!empty($fcontent['settings']['submit_button_size'])){
		$layout .= ' emd-' . $fcontent['settings']['submit_button_size'];
	}
	if(!empty($fcontent['settings']['submit_button_block'])){
		$layout .= ' emd-btn-block';
	}
	$layout .= "' type='submit' value='submit' name='submit_" . $fcontent['name'] . "'>";
	$layout .= __('Login','emd-plugins');
	$layout .= '</button>';
	$layout .= wp_nonce_field('emd_login_form', 'emd_login_nonce', false, true);
	$layout .= '<input type="hidden" id="emd_login_entity" name="emd_login_entity" value="' . $fcontent['entity'] . '">';
	if(!empty($ent_list[$fcontent['entity']]['user_key'])){
		$user_attr = $ent_list[$fcontent['entity']]['user_key'];
		$layout .= '<input type="hidden" id="emd_login_user_attr" name="emd_login_user_attr" value="' . $user_attr . '">';
	}
	if(!empty($cfield['redirect_link'])){
		$layout .= '<input type="hidden" id="emd_login_redirect" name="emd_login_redirect" value="' . $cfield['redirect_link'] . '">';
	}
	if(!empty($hidden_rel)){
		$layout .= "<input type='hidden' value='" . $hidden_rel . "' id='emd_hidden_rel' name='emd_hidden_rel'>";
		$layout .= "<input type='hidden' id='emd_hidden_rel_val' name='emd_hidden_rel_val'>";
	}	
	$layout .= '</div>';
	return $layout;
}
function emd_form_builder_login_fields_display($kfield,$cfield){
	$login_lay = '<input type="';
	if(in_array($kfield, Array('login_box_password','login_box_reg_password','login_box_reg_confirm_password'))){
		$login_lay .= 'password';
	}
	else {
		$login_lay .= 'text';
	}	
	$login_lay .= '" name="' . $kfield . '" id="' . $kfield . '" class="text required';
	if(!empty($cfield['css_class'])){
		$login_lay .= ' ' . $cfield['css_class'];
	}
	$login_lay .= ' '  . $cfield['element_size'] . ' form-control" placeholder="' . $cfield['placeholder'] . '"';
	$login_lay .= '/>';
	if($kfield == 'login_box_reg_username' && !empty($cfield['enable_registration'])){
		$login_lay .= "<input type='hidden' value='1' name='emd_reg_user' id='emd_reg_user'>";
	}	
	return $login_lay;
}
add_action('wp_ajax_nopriv_emd_process_login', 'emd_form_builder_process_login');
add_action('wp_ajax_emd_process_login', 'emd_form_builder_process_login');

function emd_form_builder_process_login(){
	$nonce  = sanitize_text_field($_POST['nonce']);
	$nonce_verified = wp_verify_nonce($nonce, 'emd_login_form');
        if(false === $nonce_verified){
		//error
		$error = __('Please refresh the page and try again.','emd-plugins');
	}
	else {
		$error = "";
		$user_data = get_user_by('login', $_POST['emd_user_login']);
		if(!$user_data){
			$user_data = get_user_by('email', $_POST['emd_user_login']);
		}
		if($user_data) {
			$user_id = $user_data->ID;
			$user_email = $user_data->user_email;
			if(wp_check_password($_POST['emd_user_pass'], $user_data->user_pass, $user_data->ID)) {
				if($user_id < 1) return;
				wp_set_auth_cookie($user_id);
				wp_set_current_user($user_id, $_POST['emd_user_login']);
				do_action('wp_login', $_POST['emd_user_login'], get_userdata($user_id));
			} else {
				$error = __( 'The password or username you entered is incorrect.', 'emd-plugins');
			}
		} else {
			$error = __('The password or username you entered is incorrect.', 'emd-plugins');
		}
	}
	// Check for errors and redirect if none present
	if(!empty($error)){
		wp_send_json_error(array('error' => $error));
	}
	else {
		if(!empty($_POST['emd_hidden_rel']) && !empty($_POST['emd_hidden_rel_val'])){
			//update the previous entity authors for limitby
			update_post_meta($_POST['emd_hidden_rel_val'],'wpas_form_submitted_by',$user_data->user_login);
			wp_update_post(Array('ID' => $_POST['emd_hidden_rel_val'],'post_author'=>$user_id));
			do_action('emd_form_after_login',$_POST['emd_hidden_rel'],$_POST['emd_hidden_rel_val']);
		}
		$emd_login_ent = $_POST['emd_login_entity'];
		$emd_user_attr = $_POST['emd_login_user_attr'];
		$args = Array('posts_per_page' => 1, 'post_type' => $emd_login_ent, 'meta_key' => $emd_user_attr, 'meta_value' => $user_id,'fields'=>'ids');
		$posts = get_posts($args);
		if(empty($_POST['emd_login_redirect'])){
			$redirect = get_permalink($posts[0]);
			if(!empty($_POST['emd_hidden_rel']) && !empty($_POST['emd_hidden_rel_val'])){
				$rel = preg_replace('/rel_/','',$_POST['emd_hidden_rel']);
				p2p_type($rel)->connect($posts[0],$_POST['emd_hidden_rel_val']);	
			}
		}
		else {
			$redirect = $_POST['emd_login_redirect'];
		}
		wp_send_json_success(array('redirect' => $redirect));
        }
	die();
}
function emd_form_builder_check_loginbox($layout_rows,$entity){
	$has_login_reg_box = Array();
	if(!empty($layout_rows)){
		foreach($layout_rows as $myrow){
			foreach($myrow as $fcount => $field){
				foreach($field as $kfield => $cfield){
					if($kfield == 'login_box_username'){
						if(!empty($cfield['redirect_link'])){
							$has_login_reg_box['login'] = $cfield['redirect_link'];
						}
						else {
							$has_login_reg_box['login'] = $entity;
						}
					}
					elseif($kfield == 'login_box_reg_username'){
						if(!empty($cfield['enable_registration'])){
							$has_login_reg_box['reg'] = 1;
						}
					}
				}
			}
		}
	}
	return $has_login_reg_box;
}
add_action('wp_ajax_nopriv_emd_verify_registration', 'emd_verify_registration');
add_action('wp_ajax_emd_verify_registration', 'emd_verify_registration');

function emd_verify_registration(){
	if(!empty($_POST['reg_username'])){
		if(username_exists($_POST['reg_username'])){
			wp_send_json_error(array('msg' => __('Username already taken','emd-plugins')));
		}
		elseif(!validate_username($_POST['reg_username'])){	
			wp_send_json_error(array('msg' => __('Invalid username','emd-plugins')));
		}
		else {
			wp_send_json_success(array('status' => 'success'));
		}
	}
	die();
}
function emd_form_builder_get_uniq_attrs($pid,$app){
	$uniq_attrs = Array();
	if(!empty($pid)){
		$ptype = get_post_type($pid);
		$attr_list = get_option($app . '_attr_list');
		if(!empty($attr_list[$ptype])){
			foreach($attr_list[$ptype] as $kattr => $vattr){
				$val = '';
				if(!empty($vattr['uniqueAttr'])){
					$val = get_post_meta($pid,$kattr,true);
				}
				if(!empty($val)){
					$uniq_attrs[$kattr] = $val;
				}
			}
		}
	}
	return $uniq_attrs;
}
