
<div class="form-alerts">
<?php
echo (isset($zf_error) ? $zf_error : (isset($error) ? $error : ''));
$form_list = get_option('youtube_showcase_glob_forms_list');
$form_list_init = get_option('youtube_showcase_glob_forms_init_list');
if (!empty($form_list['video_search'])) {
	$form_variables = $form_list['video_search'];
}
$form_variables_init = $form_list_init['video_search'];
$max_row = count($form_variables_init);
foreach ($form_variables_init as $fkey => $fval) {
	if (empty($form_variables[$fkey])) {
		$form_variables[$fkey] = $form_variables_init[$fkey];
	}
}
$ext_inputs = Array();
$ext_inputs = apply_filters('emd_ext_form_inputs', $ext_inputs, 'youtube_showcase', 'video_search');
$form_variables = apply_filters('emd_ext_form_var_init', $form_variables, 'youtube_showcase', 'video_search');
$req_hide_vars = emd_get_form_req_hide_vars('youtube_showcase', 'video_search');
$glob_list = get_option('youtube_showcase_glob_list');
?>
</div>
<fieldset>
<?php wp_nonce_field('video_search', 'video_search_nonce'); ?>
<input type="hidden" name="form_name" id="form_name" value="video_search">
<div class="video_search-btn-fields container-fluid">
<!-- video_search Form Attributes -->
<div class="video_search_attributes">
<div id="row1" class="row ">
<!-- text input-->
<?php if ($form_variables['blt_title']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['blt_title']['size']; ?> woptdiv">
<div class="form-group">
<label id="label_blt_title" class="control-label" for="blt_title">
<?php _e('Title', 'youtube-showcase'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('blt_title', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Title field is required', 'youtube-showcase'); ?>" id="info_blt_title" class="helptip">
<i class="field-icons fa fa-star required"></i>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $blt_title; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row2" class="row ">
<!-- Taxonomy input-->
<?php if ($form_variables['category']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['category']['size']; ?>">
<div class="form-group">
<label id="label_category" class="control-label" for="category">
<?php _e('Category', 'youtube-showcase'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('category', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Category field is required', 'youtube-showcase'); ?>" id="info_category" class="helptip">
<i class="field-icons fa fa-star required"></i>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $category; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row3" class="row ">
<!-- Taxonomy input-->
<?php if ($form_variables['post_tag']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['post_tag']['size']; ?>">
<div class="form-group">
<label id="label_post_tag" class="control-label" for="post_tag">
<?php _e('Tag', 'youtube-showcase'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('post_tag', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Tag field is required', 'youtube-showcase'); ?>" id="info_post_tag" class="helptip">
<i class="field-icons fa fa-star required"></i>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $post_tag; ?>
</div>
</div>
<?php
} ?>
</div>
</div><!--form-attributes-->
<?php if ($show_captcha == 1) { ?>
<div class="row">
<div class="col-xs-12">
<div id="captcha-group" class="form-group">
<?php echo $captcha_image; ?>
<label style="padding:0px;" id="label_captcha_code" class="control-label" for="captcha_code">
<a id="info_captcha_code_help" class="helptip" data-html="true" data-toggle="tooltip" href="#" title="<?php _e('Please enter the characters with black color in the image above.', 'youtube-showcase'); ?>">
<i class="field-icons fa fa-info-circle"></i>
</a>
<a id="info_captcha_code_req" class="helptip" title="<?php _e('Security Code field is required', 'youtube-showcase'); ?>" data-toggle="tooltip" href="#">
<i class="field-icons fa fa-star required"></i>
</a>
</label>
<?php echo $captcha_code; ?>
</div>
</div>
</div>
<?php
} ?>
<!-- Button -->
<div class="row">
<div class="col-md-12">
<div class="wpas-form-actions">
<?php echo $singlebutton_video_search; ?>
</div>
</div>
</div>
</div><!--form-btn-fields-->
</fieldset>