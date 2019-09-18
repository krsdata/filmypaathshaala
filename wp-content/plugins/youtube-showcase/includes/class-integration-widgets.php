<?php
/**
 * Integration Widget Classes
 *
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.6
 */
if (!defined('ABSPATH')) exit;
/**
 * Integration widget class extends Emd_Widget class
 *
 * @since WPAS 4.6
 */
class youtube_showcase_search_videos_widget extends Emd_Widget {
	public $title;
	public $text_domain = 'youtube-showcase';
	public $class_label;
	public $type = 'integration';
	public $id = 'youtube_showcase_search_videos_widget';
	public $header = '';
	public $footer = '';
	/**
	 * Instantiate integration widget class with params
	 *
	 * @since WPAS 4.6
	 */
	public function __construct() {
		parent::__construct($this->id, __('Search Videos', 'youtube-showcase') , '', __('Integration widget label', 'youtube-showcase'));
	}
	/**
	 * Enqueue css and js for widget
	 *
	 * @since WPAS 4.6
	 */
	protected function enqueue_scripts() {
		youtube_showcase_enq_custom_css_js();
	}
	/**
	 * Returns widget layout
	 *
	 * @since WPAS 4.6
	 */
	public static function layout() {
		ob_start();
		emd_get_template_part('youtube_showcase', 'widget', 'search-videos-content');
		$layout = ob_get_clean();
		return $layout;
	}
}
$access_views = get_option('youtube_showcase_access_views', Array());
if (empty($access_views['widgets']) || (!empty($access_views['widgets']) && in_array('search_videos', $access_views['widgets']) && current_user_can('view_search_videos'))) {
	register_widget('youtube_showcase_search_videos_widget');
}
