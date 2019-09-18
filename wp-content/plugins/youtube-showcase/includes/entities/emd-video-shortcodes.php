<?php
/**
 * Entity Related Shortcode Functions
 *
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
/**
 * Shortcode function
 *
 * @since WPAS 4.0
 * @param array $atts
 * @param array $args
 * @param string $form_name
 * @param int $pageno
 *
 * @return html
 */
function youtube_showcase_video_grid_set_shc($atts, $args = Array() , $form_name = '', $pageno = 1, $shc_page_count = 0) {
	global $shc_count;
	if ($shc_page_count != 0) {
		$shc_count = $shc_page_count;
	} else {
		if (empty($shc_count)) {
			$shc_count = 1;
		} else {
			$shc_count++;
		}
	}
	$fields = Array(
		'app' => 'youtube_showcase',
		'class' => 'emd_video',
		'shc' => 'video_grid',
		'shc_count' => $shc_count,
		'form' => $form_name,
		'has_pages' => true,
		'pageno' => $pageno,
		'pgn_class' => '',
		'theme' => 'bs',
		'hier' => 0,
		'hier_type' => 'ul',
		'hier_depth' => - 1,
		'hier_class' => '',
		'has_json' => 0,
	);
	$args_default = array(
		'posts_per_page' => '16',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'filter' => ''
	);
	return emd_shc_get_layout_list($atts, $args, $args_default, $fields);
}
add_shortcode('video_grid', 'video_grid_list');
function video_grid_list($atts) {
	$show_shc = 1;
	if ($show_shc == 1) {
		youtube_showcase_enq_bootstrap();
		wp_enqueue_script('ytscjs');
		wp_enqueue_script('ytsc-js');
		add_action('wp_footer', 'youtube_showcase_enq_allview');
		wp_enqueue_style('emd-pagination');
		youtube_showcase_enq_custom_css_js();
		$list = youtube_showcase_video_grid_set_shc($atts);
	} else {
		$list = '<div class="alert alert-info not-authorized">You are not authorized to access this content.</div>';
	}
	return $list;
}
/**
 * Shortcode function
 *
 * @since WPAS 4.0
 * @param array $atts
 * @param array $args
 * @param string $form_name
 * @param int $pageno
 *
 * @return html
 */
function youtube_showcase_video_indicators_set_shc($atts, $args = Array() , $form_name = '', $pageno = 1, $shc_page_count = 0) {
	global $shc_count;
	if ($shc_page_count != 0) {
		$shc_count = $shc_page_count;
	} else {
		if (empty($shc_count)) {
			$shc_count = 1;
		} else {
			$shc_count++;
		}
	}
	$fields = Array(
		'app' => 'youtube_showcase',
		'class' => 'emd_video',
		'shc' => 'video_indicators',
		'shc_count' => $shc_count,
		'form' => $form_name,
		'has_pages' => true,
		'pageno' => $pageno,
		'pgn_class' => 'visible-lg visible-md',
		'theme' => 'bs',
		'hier' => 0,
		'hier_type' => 'ul',
		'hier_depth' => - 1,
		'hier_class' => '',
		'has_json' => 0,
	);
	$args_default = array(
		'posts_per_page' => '8',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'filter' => ''
	);
	return emd_shc_get_layout_list($atts, $args, $args_default, $fields);
}
add_shortcode('video_indicators', 'video_indicators_list');
function video_indicators_list($atts) {
	$show_shc = 1;
	if ($show_shc == 1) {
		youtube_showcase_enq_bootstrap();
		wp_enqueue_script('ytscjs');
		wp_enqueue_script('ytsc-js');
		add_action('wp_footer', 'youtube_showcase_enq_allview');
		wp_enqueue_style('emd-pagination');
		youtube_showcase_enq_custom_css_js();
		$list = youtube_showcase_video_indicators_set_shc($atts);
	} else {
		$list = '<div class="alert alert-info not-authorized">You are not authorized to access this content.</div>';
	}
	return $list;
}
/**
 * Shortcode function
 *
 * @since WPAS 4.0
 * @param array $atts
 * @param array $args
 * @param string $form_name
 * @param int $pageno
 *
 * @return html
 */
function youtube_showcase_video_items_set_shc($atts, $args = Array() , $form_name = '', $pageno = 1, $shc_page_count = 0) {
	global $shc_count;
	if ($shc_page_count != 0) {
		$shc_count = $shc_page_count;
	} else {
		if (empty($shc_count)) {
			$shc_count = 1;
		} else {
			$shc_count++;
		}
	}
	$fields = Array(
		'app' => 'youtube_showcase',
		'class' => 'emd_video',
		'shc' => 'video_items',
		'shc_count' => $shc_count,
		'form' => $form_name,
		'has_pages' => true,
		'pageno' => $pageno,
		'pgn_class' => 'hidden',
		'theme' => 'bs',
		'hier' => 0,
		'hier_type' => 'ul',
		'hier_depth' => - 1,
		'hier_class' => '',
		'has_json' => 0,
	);
	$args_default = array(
		'posts_per_page' => '8',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'filter' => ''
	);
	return emd_shc_get_layout_list($atts, $args, $args_default, $fields);
}
add_shortcode('video_items', 'video_items_list');
function video_items_list($atts) {
	$show_shc = 1;
	if ($show_shc == 1) {
		youtube_showcase_enq_bootstrap();
		add_action('wp_footer', 'youtube_showcase_enq_allview');
		wp_enqueue_style('emd-pagination');
		youtube_showcase_enq_custom_css_js();
		$list = youtube_showcase_video_items_set_shc($atts);
	} else {
		$list = '<div class="alert alert-info not-authorized">You are not authorized to access this content.</div>';
	}
	return $list;
}
/**
 * Shortcode function
 *
 * @since WPAS 4.0
 * @param array $atts
 * @param array $args
 * @param string $form_name
 * @param int $pageno
 *
 * @return html
 */
function youtube_showcase_video_search_set_shc($atts, $args = Array() , $form_name = '', $pageno = 1, $shc_page_count = 0) {
	global $shc_count;
	if ($shc_page_count != 0) {
		$shc_count = $shc_page_count;
	} else {
		if (empty($shc_count)) {
			$shc_count = 1;
		} else {
			$shc_count++;
		}
	}
	$fields = Array(
		'app' => 'youtube_showcase',
		'class' => 'emd_video',
		'shc' => 'video_search',
		'shc_count' => $shc_count,
		'form' => $form_name,
		'has_pages' => false,
		'pageno' => $pageno,
		'pgn_class' => '',
		'theme' => 'bs',
		'hier' => 0,
		'hier_type' => 'ul',
		'hier_depth' => - 1,
		'hier_class' => '',
		'has_json' => 0,
	);
	$args_default = array(
		'posts_per_page' => '10',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'filter' => ''
	);
	return emd_shc_get_layout_list($atts, $args, $args_default, $fields);
}
add_filter('widget_text', 'shortcode_unautop');
add_filter('widget_text', 'do_shortcode', 11);
