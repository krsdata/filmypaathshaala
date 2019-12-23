<?php
namespace WpAssetCleanUp;

/**
 * Class MetaBoxes
 * @package WpAssetCleanUp
 */
class MetaBoxes
{
	/**
	 * @var array
	 */
	public static $noMetaBoxesForPostTypes = array(
		// Oxygen Page Builder
		'ct_template',

		// Themify Page Builder (Layout & Layout Part)
		'tbuilder_layout',
		'tbuilder_layout_part',

		// "Popup Maker" plugin
		'popup',
		'popup_theme',

		// "Popup Builder" plugin
		'popupbuilder'
	);

	/**
	 *
	 */
	public function initManagerMetaBox()
	{
		add_action( 'add_meta_boxes', array( $this, 'addAssetManagerMetaBox' ) );
	}

	/**
	 *
	 */
	public function initCustomOptionsMetaBox()
	{
		add_action( 'add_meta_boxes', array( $this, 'addPageOptionsMetaBox' ) );
	}

	/**
	 * @param $postType
	 */
	public function addAssetManagerMetaBox($postType)
	{
		$obj = get_post_type_object($postType);

		// These are not public pages that are loading CSS/JS
		// e.g. URI request ending in '/ct_template/inner-content/'
		if (isset($obj->name) && in_array($obj->name, self::hideMetaBoxesForPostTypes())) {
			return;
		}

		if (isset($obj->public) && $obj->public > 0) {
			add_meta_box(
				WPACU_PLUGIN_ID . '_asset_list',
				 WPACU_PLUGIN_TITLE.': '.__('CSS &amp; JavaScript Manager', 'wp-asset-clean-up'),
				array($this, 'renderAssetManagerMetaBoxContent'),
				$postType,
				apply_filters('wpacu_asset_list_meta_box_context',  'normal'),
				apply_filters('wpacu_asset_list_meta_box_priority', 'high')
			);
		}
	}

	/**
	 * This is triggered only in the Edit Mode Dashboard View
	 */
	public function renderAssetManagerMetaBoxContent()
	{
		global $post;

		if ($post->ID === null) {
			return;
		}

		$data = array('status' => 1);

		$postId = (isset($post->ID) && $post->ID > 0) ? $post->ID : 0;

		$isListFetchable = true;

		if (! Main::instance()->settings['dashboard_show']) {
			$isListFetchable = false;
			$data['status'] = 2; // "Manage within Dashboard" is disabled in plugin's settings
		} elseif ($postId < 1 || get_post_status($postId) !== 'publish') {
			$data['status'] = 3; // "draft", "auto-draft" post (it has to be published)
			$isListFetchable = false;
		}

		if (class_exists('WPSEO_Options') && 'attachment' === get_post_type($post->ID)) {
			try {
				if (\WPSEO_Options::get( 'disable-attachment' ) === true) {
					$isListFetchable = false;
					$data['status'] = 4; // "Redirect attachment URLs to the attachment itself?" is enabled in "Yoast SEO" -> "Media"
				}
			} catch (\Exception $e) {}
		}

		$data['is_list_fetchable'] = $isListFetchable;
		$data['fetch_assets_on_click'] = false;

		if ($isListFetchable) {
			if (Main::instance()->settings['assets_list_show_status'] === 'fetch_on_click') {
				$data['fetch_assets_on_click'] = true;
			}

			$data['fetch_url'] = Misc::getPageUrl($postId);
		}

		Main::instance()->parseTemplate('meta-box', $data, true);
	}

	/**
	 * @param $postType
	 */
	public function addPageOptionsMetaBox($postType)
	{
		$obj = get_post_type_object($postType);

		// These are not public pages that are loading CSS/JS
		// e.g. URI request ending in '/ct_template/inner-content/'
		if (isset($obj->name) && in_array($obj->name, self::hideMetaBoxesForPostTypes())) {
			return;
		}

		if (isset($obj->public) && $obj->public > 0) {
			add_meta_box(
				WPACU_PLUGIN_ID . '_page_options',
				WPACU_PLUGIN_TITLE.': '.__('Options', 'wp-asset-clean-up'),
				array($this, 'renderPageOptionsMetaBoxContent'),
				$postType,
				apply_filters('wpacu_page_options_meta_box_context',  'side'),
				apply_filters('wpacu_page_options_meta_box_priority', 'high')
			);
		}
	}

	/**
	 *
	 */
	public function renderPageOptionsMetaBoxContent()
	{
		$data = array('page_options' => self::getPageOptions());

		Main::instance()->parseTemplate('meta-box-side-page-options', $data, true);
	}

	/**
	 * @param int $postId
	 *
	 * @return array|mixed|object
	 */
	public static function getPageOptions($postId = 0)
	{
		if ($postId < 1) {
			global $post;
			$postId = (int)$post->ID;
		}

		if ($postId > 1) {
			$metaPageOptionsJson = get_post_meta($postId, '_'.WPACU_PLUGIN_ID.'_page_options', true);

			return @json_decode( $metaPageOptionsJson, ARRAY_A );
		}

		return array();
	}

	/**
	 * @return mixed|void
	 */
	public static function hideMetaBoxesForPostTypes()
	{
		$allValues = self::$noMetaBoxesForPostTypes;

		$hideForChosenPostTypes = Main::instance()->settings['hide_meta_boxes_for_post_types'];

		if (! empty($hideForChosenPostTypes)) {
			foreach ($hideForChosenPostTypes as $chosenPostType) {
				$allValues[] = trim($chosenPostType);
			}
		}

		return $allValues;
	}
}
