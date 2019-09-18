<?php
/**
 * Entity Widget Classes
 *
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
/**
 * Entity widget class extends Emd_Widget class
 *
 * @since WPAS 4.0
 */
class youtube_showcase_recent_videos_widget extends Emd_Widget {
	public $title;
	public $text_domain = 'youtube-showcase';
	public $class_label;
	public $class = 'emd_video';
	public $type = 'entity';
	public $has_pages = false;
	public $css_label = 'recent-videos';
	public $id = 'youtube_showcase_recent_videos_widget';
	public $query_args = array(
		'post_type' => 'emd_video',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'context' => 'youtube_showcase_recent_videos_widget',
	);
	public $filter = '';
	public $header = '';
	public $footer = '';
	/**
	 * Instantiate entity widget class with params
	 *
	 * @since WPAS 4.0
	 */
	public function __construct() {
		parent::__construct($this->id, __('Recent Videos', 'youtube-showcase') , __('Videos', 'youtube-showcase') , __('The most recent videos', 'youtube-showcase'));
	}
	/**
	 * Get header and footer for layout
	 *
	 * @since WPAS 4.6
	 */
	protected function get_header_footer() {
	}
	/**
	 * Enqueue css and js for widget
	 *
	 * @since WPAS 4.5
	 */
	protected function enqueue_scripts() {
		youtube_showcase_enq_custom_css_js();
	}
	/**
	 * Returns widget layout
	 *
	 * @since WPAS 4.0
	 */
	public static function layout() {
		ob_start();
		emd_get_template_part('youtube_showcase', 'widget', 'recent-videos-content');
		$layout = ob_get_clean();
		return $layout;
	}
}
/**
 * Entity widget class extends Emd_Widget class
 *
 * @since WPAS 4.0
 */
class youtube_showcase_featured_videos_widget extends Emd_Widget {
	public $title;
	public $text_domain = 'youtube-showcase';
	public $class_label;
	public $class = 'emd_video';
	public $type = 'entity';
	public $has_pages = false;
	public $css_label = 'featured-videos';
	public $id = 'youtube_showcase_featured_videos_widget';
	public $query_args = array(
		'post_type' => 'emd_video',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'context' => 'youtube_showcase_featured_videos_widget',
	);
	public $filter = 'attr::emd_video_featured::is::1';
	public $header = '';
	public $footer = '';
	/**
	 * Instantiate entity widget class with params
	 *
	 * @since WPAS 4.0
	 */
	public function __construct() {
		parent::__construct($this->id, __('Featured Videos', 'youtube-showcase') , __('Videos', 'youtube-showcase') , __('The most recent videos', 'youtube-showcase'));
	}
	/**
	 * Get header and footer for layout
	 *
	 * @since WPAS 4.6
	 */
	protected function get_header_footer() {
	}
	/**
	 * Enqueue css and js for widget
	 *
	 * @since WPAS 4.5
	 */
	protected function enqueue_scripts() {
		youtube_showcase_enq_custom_css_js();
	}
	/**
	 * Returns widget layout
	 *
	 * @since WPAS 4.0
	 */
	public static function layout() {
		ob_start();
		emd_get_template_part('youtube_showcase', 'widget', 'featured-videos-content');
		$layout = ob_get_clean();
		return $layout;
	}
}
$access_views = get_option('youtube_showcase_access_views', Array());
if (empty($access_views['widgets']) || (!empty($access_views['widgets']) && in_array('recent_videos', $access_views['widgets']) && current_user_can('view_recent_videos'))) {
	register_widget('youtube_showcase_recent_videos_widget');
}
if (empty($access_views['widgets']) || (!empty($access_views['widgets']) && in_array('featured_videos', $access_views['widgets']) && current_user_can('view_featured_videos'))) {
	register_widget('youtube_showcase_featured_videos_widget');
}
