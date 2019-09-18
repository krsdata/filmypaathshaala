<?php
/**
 * Integration Shortcode Functions
 *
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
add_shortcode('video_gallery', 'youtube_showcase_get_integ_video_gallery');
/**
 * Display integration shortcode or no access msg
 * @since WPAS 4.0
 *
 * @return string $layout or $no_access_msg
 */
function youtube_showcase_get_integ_video_gallery($atts) {
	$no_access_msg = __('You are not allowed to access to this area. Please contact the site administrator.', 'youtube-showcase');
	$access_views = get_option('youtube_showcase_access_views', Array());
	if (!current_user_can('view_video_gallery') && !empty($access_views['integration']) && in_array('video_gallery', $access_views['integration'])) {
		return $no_access_msg;
	} else {
		wp_enqueue_script('jquery');
		youtube_showcase_enq_bootstrap();
		wp_enqueue_script('video-gallery-js');
		add_action('wp_footer', 'youtube_showcase_enq_allview');
		if (!empty($atts) && !empty($atts['filter'])) {
			$shc_filter = "video_gallery_filter";
			global $ {
				$shc_filter
			};
			$ {
				$shc_filter
			} = $atts['filter'];
		}
		ob_start();
		emd_get_template_part('youtube-showcase', 'integration', 'video-gallery');
		$layout = ob_get_clean();
		return $layout;
	}
}
