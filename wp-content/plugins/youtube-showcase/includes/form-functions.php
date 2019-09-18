<?php
/**
 * Form Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
/**
 * Check min max value for int/decimal fields
 *
 * @since WPAS 4.0
 * @param int $value
 * @param int $minvalue
 * @param int $maxvalue
 * @param bool
 *
 * @return string $operator
 */
if (!function_exists('emd_check_min_max_value')) {
	function emd_check_min_max_value($value, $minvalue, $maxvalue, $required) {
		if ($required == 0 && $value == '') return true;
		if ($value < $minvalue) return false;
		if ($maxvalue != 0 && $value > $maxvalue) return false;
		return true;
	}
}
/**
 * Check min max word count for textarea fields
 *
 * @since WPAS 4.0
 * @param string $value
 * @param int $minwords
 * @param int $maxwords
 * @param bool $required
 *
 * @return bool
 */
if (!function_exists('emd_check_min_max_words')) {
	function emd_check_min_max_words($value, $minwords, $maxwords, $required) {
		if ($required == 0 && str_word_count($value) == 0) return true;
		if ($maxwords != 0 && str_word_count($value) > $maxwords) return false;
		if (str_word_count($value) < $minwords) return false;
		return true;
	}
}
/**
 * Unlink uploads
 *
 * @since WPAS 4.0
 * @param array $uploads
 *
 */
if (!function_exists('emd_delete_uploads')) {
	function emd_delete_uploads($uploads) {
		if(!empty($uploads)){
			foreach ($uploads as $myupload) {
				foreach ($myupload as $upfile) {
					if (isset($upfile['path']) && file_exists($upfile['path'])) {
						unlink($upfile['path']);
					}
				}
			}
		}
	}
}
/**
 * Process search form and return layout
 *
 * @since WPAS 4.0
 * @param string $myapp
 * @param string $myentity
 * @param string $myform
 * @param string $myview
 * @param string $noresult_msg
 * @param string $path
 *
 * @return string $res_layout html
 */
if (!function_exists('emd_search_form')) {
	function emd_search_form($myapp, $myentity, $myform, $myview, $noresult_msg, $path) {
		$args = Array();
		$search_fields = Array();
		$attrs = Array();
		$txns = Array();
		$rels = Array();
		$oprs = Array();
		$blts = Array();
		$rel_count = 0;
		$res_layout = $noresult_msg;
		$myattr_list = Array();
		$mytxn_list = Array();
		$myrel_list = Array();
		$attr_list = get_option($myapp . '_attr_list', Array());
		$txn_list = get_option($myapp . '_tax_list', Array());
		$rel_list = get_option($myapp . '_rel_list', Array());
		$cust_fields = Array();
		if(post_type_supports($myentity, 'custom-fields') == 1){
			$cust_fields = apply_filters('emd_get_cust_fields', $cust_fields, $myentity);
		}
		if (isset($attr_list[$myentity])) {
			$myattr_list = array_keys($attr_list[$myentity]);
		}
		if (isset($txn_list[$myentity])) {
			$mytxn_list = array_keys($txn_list[$myentity]);
		}
		if (isset($rel_list)) {
			$myrel_list = array_keys($rel_list);
		}
		if(!empty($_POST)){
			foreach ($_POST as $postkey => $postval) {
				if (!empty($postkey) && !is_array($postval)) {
					$postval = sanitize_text_field(urldecode($postval));
					$postval = html_entity_decode($postval);
				}
				if (!empty($postval)) {
					if (in_array($postkey, $mytxn_list)) {
						$txns[$postkey] = $postval;
					} elseif (in_array($postkey, $myrel_list)) {
						$rel_key = preg_replace("/rel_/", "", $postkey, 1);
						$rels[$rel_count] = Array(
							'key' => $rel_key,
							'val' => $postval
						);
						$rel_count++;
					} elseif (in_array($postkey, $myattr_list)) {
						$attrs[$postkey] = $postval;
					} elseif (preg_match('/^opr__/', $postkey)) {
						$opr_key = preg_replace("/opr__/", "", $postkey, 1);
						$oprs[$opr_key] = $postval;
					} elseif (in_array($postkey, Array(
						'blt_title',
						'blt_content',
						'blt_excerpt'
					))) {
						$blts[$postkey] = $postval;
					} elseif (!empty($cust_fields) && in_array($postkey, array_keys($cust_fields))) {
						$attrs[$cust_fields[$postkey]] = $postval;
					}
				}
			}
		}
		if (!empty($blts)) {
			foreach ($blts as $bltkey => $bltval) {
				$args[$bltkey] = $bltval;
				if (!empty($oprs) && isset($oprs[$myform . '_' . $bltkey])) {
					$args['opr__' . $bltkey] = emd_get_meta_operator($oprs[$myform . '_' . $bltkey]);
					$blts[$bltkey] = $oprs[$myform . '_' . $bltkey];			
				} else {
					//Change default to like
					//$args['opr__' . $bltkey] = "=";
					$args['opr__' . $bltkey] = "LIKE";
				}
			}
			$args['emd_blts'] = $blts;
		}
		$filter = "";
		if (!empty($attrs)) {
			foreach ($attrs as $key => $myattr) {
				if(!empty($myattr)){
					if(is_array($myattr)){
						foreach($myattr as $karr => $varr){
							if($varr == ''){
								unset($myattr[$karr]);
							}
						}
					}
					if(!empty($myattr)){
						if(!empty($cust_fields) && in_array($key,$cust_fields)){
							$filter.= "cattr::" . $key . "::";
							$opr_key = array_search($key,$cust_fields);
							if (!empty($oprs) && isset($oprs[$myform . '_' . $opr_key])) {
								$filter.= $oprs[$myform . '_'  . $opr_key];
							} else {
								$filter.= "is";
							}
						}
						else {	
							$filter.= "attr::" . $key . "::";
							if (!empty($oprs) && isset($oprs[$myform . '_' . $key])) {
								$filter.= $oprs[$myform . '_'  . $key];
							} else {
								$filter.= "is";
							}
						}
						if (is_array($myattr) && !empty($myattr)) {
							$filter.= "::" . implode(',', $myattr) . ";";
						} else {
							$filter.= "::" . $myattr . ";";
						}
					}
				}
			}
		}
		if (!empty($txns)) {
			foreach ($txns as $keytxn => $mytxn) {
				if (is_array($mytxn) && !empty($mytxn)) {
					$filter.= "tax::" . $keytxn . "::is::" . implode(",", $mytxn) . ";";
				} elseif (!empty($mytxn)) {
					$filter.= "tax::" . $keytxn . "::is::" . $mytxn . ";";
				}
			}
		}
		if (!empty($rels)) {
			foreach ($rels as $vrel) {
				if (is_array($vrel['val']) && !empty($vrel['val'])) {
					$filter.= "rel::" . $vrel['key'] . "::is::" . implode(',', $vrel['val']) . ";";
				} elseif (!empty($vrel['val'])) {
					$filter.= "rel::" . $vrel['key'] . "::is::" . $vrel['val'] . ";";
				}
			}
		}
		$emd_query = new Emd_Query($myentity, $myapp);
		$emd_query->args_filter($filter);
		$args = array_merge($args,$emd_query->args);
		$args['post_type'] = $myentity;
		if (!empty($attrs) || !empty($txns) || !empty($rels) || !empty($blts)) {
			$func_layout = $myapp . "_" . $myview . "_set_shc";
			$list_layout = $func_layout('', $args, $myform);
			if ($list_layout != '') {
				$res_layout = "<div id='" . $myview . $myentity . "-results'>";
				$res_layout.= $list_layout;
			}
		}
		$emd_query->remove_filters();
		return $res_layout;
	}
}
/**
 * Process submit form and redirect to given url or show errors if form doesn't validate
 *
 * @since WPAS 4.0
 * @param string $form_name
 * @param string $myapp
 * @param string $myentity
 * @param string $post_status
 * @param string $redirect_url
 * @param string $error_msg
 * @param bool $file_attr_exists
 *
 */
if (!function_exists('emd_submit_redirect_form')) {
	function emd_submit_redirect_form($form_name, $myapp, $myentity, $post_status, $visitor_post_status, $redirect_url, $error_msg, $file_attr_exists) {
		check_admin_referer($form_name, $form_name . '_nonce');
		$set_fname = $myapp . "_set_" . $form_name;
		$form = $set_fname();
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		if ($file_attr_exists) {
			$sess_uploads = $session_class->session->get('uploads');
			emd_set_file_upload($sess_uploads);
		}
		if ($form->validate()) {
			$result = emd_submit_form($myapp, $myentity, $post_status, $visitor_post_status, $form);
			if ($result !== false) {
				$rel_uniqs = $result['rel_uniqs'];
				if(!empty($rel_uniqs)){
					foreach($rel_uniqs as $kconn => $rel_conn){
						if(is_array($rel_conn)){        
							foreach($rel_conn as $rpid){
								do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rpid));
							}
						}
						else{
							do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rel_conn));
						}
					}
				}
				do_action('emd_notify', $myapp, $result['id'], 'entity', 'front_add', $rel_uniqs);
				wp_redirect($redirect_url);
				exit;
			} else {
				$session_class->session->set('form_errors', Array(
					'error' => Array(
						'0' => $error_msg
					)
				));
			}
		} else {
			if ($file_attr_exists) {
				emd_unset_file_upload($session_class);
			}
			$session_class->session->set('form_errors',$form->errors);
		}
	}
}
/**
 * Set files variable from session uploads
 *
 * @since WPAS 4.0
 *
 */
if (!function_exists('emd_set_file_upload')) {
	function emd_set_file_upload($sess_uploads) {
		if (!empty($_FILES)) {
			foreach ($_FILES as $key_attr => $myfile) {
				if (!empty($sess_uploads) && !empty($sess_uploads[$key_attr])) {
					$_FILES[$key_attr] = $sess_uploads[$key_attr];
				} else {
					unset($_FILES[$key_attr]);
					$_FILES[$key_attr][0] = $myfile;
				}
			}
		}
		if (!empty($sess_uploads) && empty($_FILES)) {
			foreach ($sess_uploads as $key_attr => $files) {
				$_FILES[$key_attr] = $files;
			}
		}
	}
}
/**
 * Unsets session uploads
 *
 * @since WPAS 4.0
 *
 */
if (!function_exists('emd_unset_file_upload')) {
	function emd_unset_file_upload($session_class) {
		$sess_uploads = $session_class->session->get('uploads');
		if (!empty($sess_uploads)) {
			$session_class->session->set('uploads','');	
		}
	}
}
/**
 * Process Search form and return layout and form
 *
 * @since WPAS 4.0
 * @param string $form_name
 * @param string $myapp
 * @param string $myentity
 * @param string $noresult_msg
 * @param string $view_name
 *
 * @return string $layout
 */
if (!function_exists('emd_search_php_form')) {
	function emd_search_php_form($form_name, $myapp, $myentity, $noresult_msg, $view_name, $atts) {
		$set_fname = $myapp . "_set_" . $form_name;
		$form = $set_fname($atts);
		$paged = (get_query_var('pageno')) ? get_query_var('pageno') : 0;
		if($paged == '0'){
			$paged = (get_query_var('paged')) ? get_query_var('paged') : 0;
		}
		$path = constant(strtoupper($myapp) . "_PLUGIN_DIR");
		$form_file = str_replace("_", "-", $form_name);
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		$sess_form_args = $session_class->session->get($form_name . '_args');
		if (empty($_POST) && $paged != 0 && !empty($sess_form_args)) {
			$layout = "<div style='position:relative;' id='" . $form_file . "-search' class='emd-container'>";
			$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
			$set_layout_func = $myapp . "_" . $view_name . "_set_shc";
			$list_layout = $set_layout_func('', $sess_form_args, $form_name, $paged);
			if ($list_layout != '') {
				$layout.= "<div id='" . $view_name . $myentity . "-results'>";
				$layout.= $list_layout;
			}
			$layout.= "</div>";
			return $layout;
		}
		if (!empty($_POST) && !empty($_POST['form_name']) && $_POST['form_name'] == $form_name) {
			check_admin_referer($form_name, $form_name . '_nonce');
			if ($form->validate()) {
				$layout = "<div style='position:relative;' id='" . $form_file . "-search' class='emd-container'>";
				$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
				$layout.= emd_search_form($myapp, $myentity, $form_name, $view_name, $noresult_msg, $path);
				$layout.= "</div>";
				return $layout;
			} else {
				$sess_form_errors = $session_class->session->get('form_errors');
				if (!empty($sess_form_errors)) {
					$form->errors = $sess_form_errors;
				}
				$layout = "<div style='position:relative;' id='" . $form_file . "-search' class='emd-container'>";
				$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
				$layout.= "</div><!--container-end-->";
				return $layout;
			}
		} else {
			$layout = "<div style='position:relative;' id='" . $form_file . "-search' class='emd-container'>";
			$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
			$layout.= "</div><!--container-end-->";
			return $layout;
		}
	}
}
/**
 * Process Submit form and return layout and form
 *
 * @since WPAS 4.0
 * @param string $form_name
 * @param string $myapp
 * @param string $myentity
 * @param string $post_status
 * @param string $success_msg
 * @param string $error_msg
 * @param bool $clear_hide_form
 * @param bool $file_attr_exists
 *
 * @return string $layout
 */
if (!function_exists('emd_submit_php_form')) {
	function emd_submit_php_form($form_name, $myapp, $myentity, $post_status, $visitor_post_status, $success_msg, $error_msg, $clear_hide_form, $file_attr_exists, $atts) {
		$set_fname = $myapp . "_set_" . $form_name;
		$form = $set_fname($atts);
		$form_file = str_replace("_", "-", $form_name);
		$path = constant(strtoupper($myapp) . "_PLUGIN_DIR");
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		if (!empty($_POST) && $_POST['form_name'] == $form_name) {
			check_admin_referer($form_name, $form_name . '_nonce');
			if ($file_attr_exists) {
				$sess_uploads = $session_class->session->get('uploads');
				emd_set_file_upload($sess_uploads);
			}
			if ($form->validate()) {
				$result = emd_submit_form($myapp, $myentity, $post_status, $visitor_post_status, $form);
				if ($file_attr_exists) {
					emd_unset_file_upload($session_class);
				}
				if ($result !== false) {
					$rel_uniqs = $result['rel_uniqs'];
					if(!empty($rel_uniqs)){
						foreach($rel_uniqs as $kconn => $rel_conn){
							if(is_array($rel_conn)){	
								foreach($rel_conn as $rpid){
									do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rpid));
								}
							}
							else{
								do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rel_conn));
							}
						}
					}
					do_action('emd_notify', $myapp, $result['id'], 'entity', 'front_add', $rel_uniqs);
					$layout = "<div style='position:relative;' id='" . $form_file . "-submit' class='emd-container'>";
					$layout.= $form->render($path . 'forms/' . $form_file . '.php', true, '', $success_msg, '', $clear_hide_form);
					$layout.= "</div>";
					return $layout;
				} else {
					$layout = "<div style='position:relative;' id='" . $form_file . "-submit' class='emd-container'>";
					$layout.= $form->render($path . 'forms/' . $form_file . '.php', true, '', '', $error_msg);
					$layout.= "</div>";
					return $layout;
				}
			} else {
				if ($file_attr_exists) {
					emd_unset_file_upload($session_class);
				}
				$sess_form_errors = $session_class->session->get('form_errors');
				if (!empty($sess_form_errors)) {
					$form->errors = $sess_form_errors;
				}
				$layout = "<div style='position:relative;' id='" . $form_file . "-submit' class='emd-container'>";
				$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
				$layout.= "</div><!--container-end-->";
				return $layout;
			}
		} else {
			if ($file_attr_exists) {
				emd_unset_file_upload($session_class);
			}
			$layout = "<div style='position:relative;' id='" . $form_file . "-submit' class='emd-container'>";
			$layout.= $form->render($path . 'forms/' . $form_file . '.php', true);
			$layout.= "</div><!--container-end-->";
			return $layout;
		}
	}
}
/**
 * Process Submit form, insert ent, attribute and tax and rel values
 *
 * @since WPAS 4.0
 * @param string $myapp
 * @param string $myentity
 * @param string $post_status
 * @param string $form
 *
 * @return array $ret
 */
if (!function_exists('emd_submit_form')) {
	function emd_submit_form($myapp, $myentity, $post_status, $visitor_post_status, $form) {
		$user_conf = Array();
		$entity_post = Array();
		$entity_fields = Array();
		$txn_fields = Array();
		$rel_fields = Array();
		$user_email_val = "";
		$myattr_list = Array();
		$mytxn_list = Array();
		$myrel_list = Array();
		$default_list = Array();
		$default_txns = Array();
		$blts = Array(
			'blt_title' => '',
			'blt_content' => '',
			'blt_excerpt' => ''
		);
		$attr_list = get_option($myapp . '_attr_list', Array());
		$txn_list = get_option($myapp . '_tax_list', Array());
		$rel_list = get_option($myapp . '_rel_list', Array());
		$cust_fields = Array();
		if(post_type_supports($myentity, 'custom-fields') == 1){
			$cust_fields = apply_filters('emd_get_cust_fields', $cust_fields, $myentity);
		}
		if(!empty($txn_list[$myentity])){
			foreach ($txn_list[$myentity] as $mykey => $mytxn) {
				if (!empty($mytxn['default'])) {
					$default_txns[$mykey] = $mytxn['default'];
				}
			}
		}
		if(!empty($attr_list[$myentity])){
			$current_user = wp_get_current_user();
			foreach ($attr_list[$myentity] as $mykey => $myattr) {
				if (isset($myattr['std'])) {
					$default_list[$mykey] = $myattr['std'];
				}
				if(!empty($current_user) && !empty($myattr['user_map'])){
					$umap = $myattr['user_map'];
					$default_list[$mykey] = (string)$current_user->$umap;	
				}
				if(!empty($current_user) && !empty($myattr['email'])){
					$default_list[$mykey] =  (string)$current_user->user_email;
				}	
			}
		}
		if (isset($attr_list[$myentity])) {
			$myattr_list = array_keys($attr_list[$myentity]);
		}
		if (isset($txn_list[$myentity])) {
			$mytxn_list = array_keys($txn_list[$myentity]);
		}
		if (isset($rel_list)) {
			$myrel_list = array_keys($rel_list);
		}
		if(!empty($_POST)){
			foreach ($_POST as $postkey => $mypost) {
				if (!empty($mypost) && !is_array($mypost)) {
					$mypost = sanitize_text_field(urldecode($mypost));
					$mypost = html_entity_decode($mypost);
				}
				if (!empty($mypost)) {
					if(!empty($cust_fields) && in_array($postkey,array_keys($cust_fields))){
						$entity_fields[$cust_fields[$postkey]] = $mypost;
					} elseif (in_array($postkey, $mytxn_list)) {
						$txn_fields[$postkey] = $mypost;
					} elseif (in_array($postkey, $myrel_list)) {
						$postkey = preg_replace("/rel_/", "", $postkey, 1);
						$rel_fields[$postkey] = $mypost;
						if (isset($user_conf['email_type']) && $user_conf['email_type'] == 'rel' && $user_conf['email_field'] != '') {
							$email_arr = explode("__rel__", $user_conf['email_field']);
							if ($email_arr[0] == $postkey) {
								if (is_array($mypost)) {
									foreach ($mypost as $mypostid) {
										$user_email_val[$mypostid] = get_post_meta($mypostid, $email_arr[1], true);
									}
								} else {
									$user_email_val[$mypost] = get_post_meta($mypost, $email_arr[1], true);
								}
							}
						}
					} elseif (in_array($postkey, $myattr_list)) {
						$entity_fields[$postkey] = $mypost;
						if (isset($user_conf['email_type']) && $user_conf['email_type'] == 'ent' && $user_conf['email_field'] != '' && $postkey == $user_conf['email_field']) {
							$user_email_val[$postkey] = $mypost;
						}
					} elseif (in_array($postkey, Array(
						'blt_title',
						'blt_content',
						'blt_excerpt'
					))) {
						$blts[$postkey] = $mypost;
					}
				}
			}
		}
		$entity_post['post_type'] = $myentity;
		$published_cap = get_post_type_object($myentity)->cap->edit_published_posts;
		$current_user_id = get_current_user_id();

		if (current_user_can($published_cap)) {
			$entity_post['post_status'] = $post_status;
			$entity_post['post_author'] = $current_user_id;
		}
		else {
			$entity_post['post_status'] = $visitor_post_status;
			$entity_post['post_author'] = $current_user_id;
		}
		if (!empty($blts)) {
			foreach ($blts as $blt_key => $blt_val) {
				$key = str_replace("blt_", "post_", $blt_key);
				$entity_post[$key] = $blt_val;
			}
		}
		if (empty($entity_post['post_title'])) {
			$wpas_ent_list = get_option($myapp . '_ent_list');
			if (!empty($wpas_ent_list[$myentity]['class_title'])) {
				$class_title = $wpas_ent_list[$myentity]['class_title'];
				$new_title = '';
				$class_delimiter = apply_filters('emd_get_title_delimiter', " ", $myapp, $myentity);
				foreach ($class_title as $mykey) {
					if(isset($entity_fields[$mykey])){
						$new_title.= $entity_fields[$mykey] . $class_delimiter;
					}
				}
				$entity_post['post_title'] = rtrim($new_title,$class_delimiter);
				$blts['blt_title'] = $entity_post['post_title'];
			}
		}
		$id = wp_insert_post($entity_post);
		if(empty($entity_post['post_title'])){
			wp_update_post(Array('ID' => $id,'post_title'=>$id));
		}
		if (!empty($id)) {
			if (!empty($default_list)) {
				foreach ($default_list as $def_key => $def_value) {
					if (!in_array($def_key, array_keys($entity_fields))) {
						$def_value_arr = explode(",", $def_value);
						if (count($def_value_arr) > 1) {
							foreach ($def_value_arr as $dvalue) {
								$dvalue = rtrim($dvalue, "'");
								$dvalue = ltrim($dvalue, "'");
								add_post_meta($id, $def_key, $dvalue);
							}
						} else {
							$def_value = rtrim($def_value, "'");
							$def_value = ltrim($def_value, "'");
							add_post_meta($id, $def_key, $def_value);
						}
					}
				}
			}
			$concat_arr = Array();
			foreach ($entity_fields as $meta_key => $meta_value) {
				if ($meta_value == 'emd_uid') {
					$meta_value = uniqid($id, false);
					if($entity_post['post_title'] == 'emd_uid'){
						wp_update_post(Array('ID' => $id,'post_title'=>$meta_value,'post_name' => $meta_value));
					}
				}
				elseif($meta_value == 'emd_autoinc'){
					$autoinc_start = $attr_list[$myentity][$meta_key]['autoinc_start'];
					$autoinc_incr = $attr_list[$myentity][$meta_key]['autoinc_incr'];
					$meta_value = get_option($meta_key . "_autoinc",$autoinc_start);
					if($meta_value < $autoinc_start){
						$meta_value = $autoinc_start;
					}
					else {
						$meta_value = $meta_value + $autoinc_incr;
					}
					if(preg_match('/emd_autoinc/',$entity_post['post_title'])){
						$entity_post['post_title'] = preg_replace('/emd_autoinc/',$meta_value,$entity_post['post_title']);
						wp_update_post(Array('ID' => $id,'post_title'=>$entity_post['post_title']));
					}
					update_option($meta_key . "_autoinc", $meta_value);
				}
				elseif($meta_value == 'emd_concat'){
					$concat_arr['key'] = $meta_key;
					$concat_arr['concat'] = $attr_list[$myentity][$meta_key]['concat_string'];
				}
				if (is_array($meta_value)) {
					foreach ($meta_value as $mvalue) {
						add_post_meta($id, $meta_key, $mvalue);
					}
				} else {
					if(!empty($attr_list[$myentity][$meta_key])){
						$meta_value = emd_translate_date_format($attr_list[$myentity][$meta_key], $meta_value);
					}
					add_post_meta($id, $meta_key, $meta_value);
				}
			}
			if(!empty($concat_arr)){
				$meta_value = emd_get_hidden_func('concat',$myapp,$concat_arr['concat'],$id);
				update_post_meta($id, $concat_arr['key'], $meta_value);	
				if($entity_post['post_title'] == 'emd_concat'){
					wp_update_post(Array('ID' => $id,'post_title'=>$meta_value,'post_name' => $meta_value));
				}
			}
			if (!empty($default_txns)) {
				foreach ($default_txns as $def_key => $def_value) {
					if (!in_array($def_key, array_keys($txn_fields))) {
						foreach ($def_value as $dvalue) {
							$def = get_term_by('name', $dvalue, $def_key);
							if (!empty($def)) {
								$new_def_value[] = $def->term_id;
							}
						}
						if(!empty($new_def_value)){
							wp_set_object_terms($id, $new_def_value, $def_key);
						}
					}
				}
			}
			if(!empty($txn_fields)){
				foreach ($txn_fields as $txn_key => $txn_value) {
					wp_set_object_terms($id, $txn_value, $txn_key);
				}
			}
			if(!empty($rel_fields)){
				foreach ($rel_fields as $rel_key => $rel_value) {
					if (!empty($rel_value)) {
						if (is_array($rel_value)) {
							foreach ($rel_value as $rvalue) {
								if(!empty($rvalue)){
									p2p_type($rel_key)->connect($rvalue, $id);
								}
							}
						} else {
							p2p_type($rel_key)->connect($rel_value, $id);
						}
					}
				}
			}
			if (!empty($form->file_upload)) {
				$emd_file_upload = wp_upload_dir();
				foreach ($form->file_upload as $key_upload => $uploads) {
					if (is_array($uploads)) {
						foreach ($uploads as $myfileupload) {
							if (isset($myfileupload['path']) && $myfileupload['error'] == 0) {
								if(!$myfileupload['type']){
									$filetype = wp_check_filetype(basename($myfileupload['name']));
									$pmtype = $filetype['type'];
								}
								else {
									$pmtype = $myfileupload['type'];
								}
								$guid = $emd_file_upload['url'] . '/' . $myfileupload['name'];
								$attachment = array(
									'post_mime_type' => $pmtype,
									'guid' => $guid,
									'post_title' => basename($myfileupload['name']) ,
									'post_content' => '',
									'post_status' => 'inherit',
								);
								require_once (ABSPATH . 'wp-admin/includes/image.php');
								$insert_id = wp_insert_attachment($attachment, $myfileupload['path'], $id);
								if (!is_wp_error($insert_id)) {
									wp_update_attachment_metadata($insert_id, wp_generate_attachment_metadata($insert_id, $myfileupload['path']));
									// Save file ID in meta field
									add_post_meta($id, $key_upload, $insert_id, false);
								}
							}
						}
					}
				}
			}
			$ret['id'] = $id;
			$ret['user_send_to'] = $user_email_val;
			$ret['rel_uniqs'] = $rel_fields;
			if (!empty($blts)) {
				$ret['blts'] = $blts;
			}
			$ret['blts']['post_author'] = $entity_post['post_author'];
			do_action('emd_after_form_submit',$myapp, $myentity, $form, $id);
			return $ret;
		}
		return false;
	}
}
/**
 * Create where clause for builtins
 *
 * @since WPAS 4.0
 * @param string $where
 * @param object $wp_query
 *
 * @return string $where
 */
if (!function_exists('emd_builtin_posts_where')) {
	function emd_builtin_posts_where($where, $wp_query) {
		if(!empty($wp_query->query['emd_blts'])){
			$blts = $wp_query->query['emd_blts'];
			global $wpdb;
			foreach ($blts as $bltkey => $bltval) {
				$key = str_replace('blt_', '', $bltkey);
				$value = esc_sql($wp_query->get($bltkey));	
				$where.= ' AND ' . $wpdb->posts . '.post_' . $key . ' ' . $wp_query->get('opr__' . $bltkey) . ' \'';
				if ($wp_query->get('opr__' . $bltkey) == 'LIKE' || $wp_query->get('opr__' . $bltkey) == 'NOT LIKE') {
					$where.= '%' . $value . '%';
				}
				elseif($wp_query->get('opr__' . $bltkey) == 'REGEXP'){
					switch($bltval){
						case 'begins':
							$value = '^' . $value;
							break;
						case 'ends':
							$value = $value . '$';
							break;
						case 'word':
							$value = '[[:<:]]' . $value . '[[:>:]]';
							break;
					}	
					$where .= $value;
				}
				$where.= '\'';
			}
		}
		return $where;
	}
}
if (!function_exists('emd_get_form_req_hide_vars')) {
	function emd_get_form_req_hide_vars($app,$fname){
		$shc_list = get_option($app . '_shc_list');
		$post_type = $shc_list['forms'][$fname]['ent'];
		$attr_list = get_option($app . '_attr_list');
		$glob_forms_list = get_option($app . '_glob_forms_list');
		$req_arr= Array();
		$hide_arr= Array();
		if(empty($glob_forms_list[$fname])){
			$glob_forms_list_init = get_option($app . '_glob_forms_init_list');
			$glob_forms_list[$fname] = $glob_forms_list_init[$fname];
		}
		if(!empty($glob_forms_list[$fname])){
			foreach($glob_forms_list[$fname] as $fkey => $fval){
				if(!empty($fval['req']) && $fval['req'] == 1){
					if(!empty($attr_list[$post_type][$fkey]) && $attr_list[$post_type][$fkey]['display_type'] == 'checkbox'){
						$req_arr[] = $fkey . "_1";
					}
					elseif($fkey != 'btn') {
						$req_arr[] = $fkey;
					}
				}
				if(isset($fval['show']) && $fval['show'] == 0){
					$hide_arr[] = $fkey;
				}
			}
		}
		$ret['req'] = $req_arr;
		$ret['hide'] = $hide_arr;
		return $ret;
	}
}
/**
 * Process registration form  which displays on form pages
 *
 * @since WPAS 5.3
 * @param string $app
 * @param array $data
 *
 */
function emd_process_register($data,$app){
	if( is_user_logged_in() ) {
		return;
	}
	$fname = strtoupper($app);
	$session_class = $fname();
	if( empty( $data['emd_user_login'] ) ) {
		$error = __('Invalid username', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
		return;
	}
	if( username_exists( $data['emd_user_login'] ) ) {
		$error = __('Username already taken', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}

	if( ! validate_username( $data['emd_user_login'] ) ) {
		$error = __('Invalid username', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}

	if( email_exists( $data['emd_user_email'] ) ) {
		$error = __('Email address already taken', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}

	if( empty( $data['emd_user_email'] ) || ! is_email( $data['emd_user_email'] ) ) {
		$error = __('Invalid Email', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}
	if( empty( $data['emd_user_pass'] ) ) {
		$error = __('Please enter a password', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}
	if( ( ! empty( $data['emd_user_pass'] ) && empty( $data['emd_user_pass2'] ) ) || ( $data['emd_user_pass'] !== $data['emd_user_pass2'] ) ) {
		$error = __('Passwords do not match', 'emd-plugins');
		$session_class->session->set('login_reg_errors',$error);
	}
	if(empty($error)){
		$user_args = apply_filters( 'emd_insert_user_args', array(
			'user_login'      => isset( $data['emd_user_login'] ) ? $data['emd_user_login'] : '',
			'user_pass'       => isset( $data['emd_user_pass'] )  ? $data['emd_user_pass']  : '',
			'user_email'      => isset( $data['emd_user_email'] ) ? $data['emd_user_email'] : '',
			'first_name'      => isset( $data['emd_user_first'] ) ? sanitize_text_field($data['emd_user_first']) : '',
			'last_name'       => isset( $data['emd_user_last'] )  ? sanitize_text_field($data['emd_user_last'])  : '',
			'user_registered' => date( 'Y-m-d H:i:s' ),
			'role'            => get_option( 'default_role' )
		), $data );

		// Insert new user
		$user_id = wp_insert_user($user_args);

		// Validate inserted user
		if ( is_wp_error( $user_id ) ) return -1;
		if($user_id < 1) return;
		wp_set_auth_cookie($user_id, false);
		wp_set_current_user($user_id, $user_login);
		do_action('wp_login', $user_login, get_userdata($user_id));
		wp_redirect($data['emd_redirect']);
		exit;
	}
}
/**
 * Process login form  which displays on form pages
 *
 * @since WPAS 5.3
 * @param string $app
 * @param array $data
 *
 */
function emd_process_login($data,$app){
	if( is_user_logged_in() ) {
		return;
	}
	$error = "";
	$user_data = get_user_by('login', $data['emd_user_login']);
	if(!$user_data){
		$user_data = get_user_by( 'email', $data['emd_user_login'] );
	}
	$fname = strtoupper($app);
	$session_class = $fname();
	if($user_data) {
		$user_id = $user_data->ID;
		$user_email = $user_data->user_email;

		if(wp_check_password($data['emd_user_pass'], $user_data->user_pass, $user_data->ID)) {
			if(isset($data['rememberme'])) {
				$data['rememberme'] = true;
			} else {
				$data['rememberme'] = false;
			}
			if($user_id < 1) return;
			wp_set_auth_cookie($user_id, $data['rememberme']);
			wp_set_current_user($user_id, $user_login);
			do_action('wp_login', $user_login, get_userdata($user_id));
		} else {
			$error = __( 'The password you entered is incorrect', 'emd-plugins');
			$session_class->session->set( 'login_reg_errors',$error);
		}
	} else {
		$error = __('The username you entered does not exist', 'emd-plugins');
		$session_class->session->set( 'login_reg_errors',$error);
	}
	// Check for errors and redirect if none present
	if(!$error){
		wp_redirect($data['emd_redirect']);
		exit;
	}
}
