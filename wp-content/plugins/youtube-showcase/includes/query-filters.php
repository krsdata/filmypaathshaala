<?php
/**
 * Query Filter Functions
 *
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
/**
 * Change query parameters before wp_query is processed
 *
 * @since WPAS 4.0
 * @param object $query
 *
 * @return object $query
 */
function youtube_showcase_query_filters($query) {
	if (!is_admin() && $query->is_main_query()) {
		if ($query->is_category && empty($query->query_vars['post_type'])) {
			$query->query_vars['post_type'] = Array(
				"post",
				"emd_video"
			);
		}
		if ($query->is_tag && empty($query->query_vars['post_type'])) {
			$query->query_vars['post_type'] = Array(
				"post",
				"emd_video"
			);
		}
	}
	return $query;
}
add_action('pre_get_posts', 'youtube_showcase_query_filters');
