<?php
/**
 * Setup and Process submit and search forms
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
if (is_admin()) {
}
if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
	add_filter('posts_where', 'emd_builtin_posts_where', 10, 2);
}
add_action('init', 'youtube_showcase_form_shortcodes', -2);
/**
 * Start session and setup upload idr and current user id
 * @since WPAS 4.0
 *
 */
function youtube_showcase_form_shortcodes() {
	global $file_upload_dir;
	$upload_dir = wp_upload_dir();
	$file_upload_dir = $upload_dir['basedir'];
	if (!empty($_POST['emd_action'])) {
		if ($_POST['emd_action'] == 'youtube_showcase_user_login' && wp_verify_nonce($_POST['emd_login_nonce'], 'emd-login-nonce')) {
			emd_process_login($_POST, 'youtube_showcase');
		} elseif ($_POST['emd_action'] == 'youtube_showcase_user_register' && wp_verify_nonce($_POST['emd_register_nonce'], 'emd-register-nonce')) {
			emd_process_register($_POST, 'youtube_showcase');
		}
	}
}
add_shortcode('video_search', 'youtube_showcase_process_video_search');
/**
 * Set each form field(attr,tax and rels) and render form
 *
 * @since WPAS 4.0
 *
 * @return object $form
 */
function youtube_showcase_set_video_search($atts) {
	global $file_upload_dir;
	$show_captcha = 0;
	$form_variables = get_option('youtube_showcase_glob_forms_list');
	$form_init_variables = get_option('youtube_showcase_glob_forms_init_list');
	if (!empty($atts['set'])) {
		$set_arrs = emd_parse_set_filter($atts['set']);
	}
	if (!empty($form_variables['video_search']['captcha'])) {
		switch ($form_variables['video_search']['captcha']) {
			case 'never-show':
				$show_captcha = 0;
			break;
			case 'show-always':
				$show_captcha = 1;
			break;
			case 'show-to-visitors':
				if (is_user_logged_in()) {
					$show_captcha = 0;
				} else {
					$show_captcha = 1;
				}
			break;
		}
	}
	$req_hide_vars = emd_get_form_req_hide_vars('youtube_showcase', 'video_search');
	$ent_map_list = get_option('youtube_showcase_ent_map_list');
	$fname_id = 'video_search';
	if (!empty($atts['id'])) {
		$fname_id = 'video_search_' . $atts['id'];
	}
	$form = new Zebra_Form($fname_id, 0, 'POST', '', array(
		'class' => 'video_search form-container wpas-form wpas-form-stacked',
		'session_obj' => YOUTUBE_SHOWCASE()->session
	));
	$csrf_storage_method = (isset($form_variables['video_search']['csrf']) ? $form_variables['video_search']['csrf'] : $form_init_variables['video_search']['csrf']);
	if (isset($set_arrs['csrf'])) {
		$csrf_storage_method = $set_arrs['csrf'];
	}
	if ($csrf_storage_method == 0) {
		$form->form_properties['csrf_storage_method'] = false;
	}
	if (!empty($atts['set'])) {
		$form->add('hidden', 'emd_form_set', $atts['set']);
	}
	if (!in_array('blt_title', $req_hide_vars['hide'])) {
		//text
		$form->add('label', 'label_blt_title', 'blt_title', __('Title', 'youtube-showcase') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md form-control',
			'placeholder' => __('Title', 'youtube-showcase')
		);
		if (!empty($_GET['blt_title'])) {
			$attrs['value'] = sanitize_text_field($_GET['blt_title']);
		} elseif (!empty($set_arrs['attr']['blt_title'])) {
			$attrs['value'] = $set_arrs['attr']['blt_title'];
		}
		$obj = $form->add('text', 'blt_title', '', $attrs);
		$zrule = Array();
		if (in_array('blt_title', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Title is required', 'youtube-showcase')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('category', $req_hide_vars['hide'])) {
		$form->add('label', 'label_category', 'category', __('Category', 'youtube-showcase') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'multiple' => 'multiple',
			'class' => 'input-md'
		);
		if (!empty($_GET['category'])) {
			$attrs['value'] = sanitize_text_field($_GET['category']);
		} elseif (!empty($set_arrs['tax']['category'])) {
			$attrs['value'] = $set_arrs['tax']['category'];
		}
		$obj = $form->add('selectadv', 'category[]', '', $attrs, '', '{"allowClear":true,"placeholder":"' . __("Please Select", "youtube-showcase") . '","placeholderOption":"first"}');
		//get taxonomy values
		$txn_arr = Array();
		$txn_obj = get_terms('category', array(
			'hide_empty' => 0
		));
		foreach ($txn_obj as $txn) {
			$txn_arr[$txn->slug] = $txn->name;
		}
		$obj->add_options($txn_arr);
		$zrule = Array();
		if (in_array('category', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Category is required!', 'youtube-showcase')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('post_tag', $req_hide_vars['hide'])) {
		$form->add('label', 'label_post_tag', 'post_tag', __('Tag', 'youtube-showcase') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'multiple' => 'multiple',
			'class' => 'input-md'
		);
		if (!empty($_GET['post_tag'])) {
			$attrs['value'] = sanitize_text_field($_GET['post_tag']);
		} elseif (!empty($set_arrs['tax']['post_tag'])) {
			$attrs['value'] = $set_arrs['tax']['post_tag'];
		}
		$obj = $form->add('selectadv', 'post_tag[]', '', $attrs, '', '{"allowClear":true,"placeholder":"' . __("Please Select", "youtube-showcase") . '","placeholderOption":"first"}');
		//get taxonomy values
		$txn_arr = Array();
		$txn_obj = get_terms('post_tag', array(
			'hide_empty' => 0
		));
		foreach ($txn_obj as $txn) {
			$txn_arr[$txn->slug] = $txn->name;
		}
		$obj->add_options($txn_arr);
		$zrule = Array();
		if (in_array('post_tag', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Tag is required!', 'youtube-showcase')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	$form->assign('show_captcha', $show_captcha);
	if ($show_captcha == 1) {
		//Captcha
		$form->add('captcha', 'captcha_image', 'captcha_code', '', '<i class="fa fa-refresh"></i>', '');
		$form->add('label', 'label_captcha_code', 'captcha_code', __('Please enter the characters with black color.', 'youtube-showcase'));
		$obj = $form->add('text', 'captcha_code', '', array(
			'placeholder' => __('Code', 'youtube-showcase')
		));
		$obj->set_rule(array(
			'required' => array(
				'error',
				__('Captcha is required', 'youtube-showcase')
			) ,
			'captcha' => array(
				'error',
				__('Characters from captcha image entered incorrectly!', 'youtube-showcase')
			)
		));
	}
	$form->add('submit', 'singlebutton_video_search', '' . __('Search', 'youtube-showcase') . ' ', array(
		'class' => 'wpas-button wpas-juibutton-secondary   col-md-12 col-lg-12 col-xs-12 col-sm-12'
	));
	return $form;
}
/**
 * Process each form and show error or success
 *
 * @since WPAS 4.0
 *
 * @return html
 */
function youtube_showcase_process_video_search($atts) {
	$show_form = 1;
	$access_views = get_option('youtube_showcase_access_views', Array());
	if (!current_user_can('view_video_search') && !empty($access_views['forms']) && in_array('video_search', $access_views['forms'])) {
		$show_form = 0;
	}
	$form_init_variables = get_option('youtube_showcase_glob_forms_init_list');
	$form_variables = get_option('youtube_showcase_glob_forms_list');
	if ($show_form == 1) {
		if (!empty($form_init_variables['video_search']['login_reg'])) {
			$show_login_register = (isset($form_variables['video_search']['login_reg']) ? $form_variables['video_search']['login_reg'] : $form_init_variables['video_search']['login_reg']);
			if (!is_user_logged_in() && $show_login_register != 'none') {
				do_action('emd_show_login_register_forms', 'youtube_showcase', 'video_search', $show_login_register);
				return;
			}
		}
		wp_enqueue_script('wpas-jvalidate-js');
		wp_enqueue_style('wpasui');
		wp_enqueue_style('font-awesome');
		wp_enqueue_style('video-search-forms');
		wp_enqueue_script('video-search-forms-js');
		wp_enqueue_style('youtube-showcase-allview-css');
		wp_enqueue_style('emd-pagination');
		youtube_showcase_enq_custom_css_js();
		do_action('emd_ext_form_enq', 'youtube_showcase', 'video_search');
		$noresult_msg = (isset($form_variables['video_search']['noresult_msg']) ? $form_variables['video_search']['noresult_msg'] : $form_init_variables['video_search']['noresult_msg']);
		return emd_search_php_form('video_search', 'youtube_showcase', 'emd_video', $noresult_msg, 'video_search', $atts);
	} else {
		$noaccess_msg = (isset($form_variables['video_search']['noaccess_msg']) ? $form_variables['video_search']['noaccess_msg'] : $form_init_variables['video_search']['noaccess_msg']);
		return "<div class='alert alert-info not-authorized'>" . $noaccess_msg . "</div>";
	}
}
