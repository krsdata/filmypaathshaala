<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeJs;

/**
 * Class Settings
 * @package WpAssetCleanUp
 */
class Settings
{
	/**
	 * @var array
	 */
	public $settingsKeys = array(
		// Stored in 'wpassetcleanup_settings'
		'wiki_read',

        // Dashboard Assets Management
        'dashboard_show',
        'dom_get_type',

		'hide_assets_meta_box',  // Asset CleanUp (Pro): CSS & JavaScript Manager
		'hide_options_meta_box', // Asset CleanUp (Pro): Options
        'hide_meta_boxes_for_post_types', // Hide all meta boxes for the chosen post types

		// Front-end View Assets Management
        'frontend_show',
        'frontend_show_exceptions',

		'hide_from_admin_bar',

		// The way the CSS/JS list is showing (various ways depending on the preference)
		'assets_list_layout',
		'assets_list_layout_areas_status',
		'assets_list_inline_code_status',

		// [wpacu_pro]
        'assets_list_layout_plugin_area_status',
		// [/wpacu_pro]

		// Fetch automatically, Fetch on click
        'assets_list_show_status',

		'input_style',

		'hide_core_files',
		'test_mode',

		// Combine loaded CSS (remaining ones after unloading the useless ones) into fewer files
		'combine_loaded_css',
		'combine_loaded_css_exceptions',
		'combine_loaded_css_for_admin_only', // Since v1.1.1.4 (Pro) & v1.3.1.1 (Lite)

        // [wpacu_pro]
        'combine_loaded_css_append_handle_extra', // Adds any associated inline STYLE tags content to the combined files
        'defer_css_loaded_body',
        // [/wpacu_pro]

        'cache_dynamic_loaded_css',
		'cache_dynamic_loaded_js',

		'inline_js_files',
		'inline_js_files_below_size', // Enable?
		'inline_js_files_below_size_input', // Actual size
        'inline_js_files_list',

        'move_inline_jquery_after_src_tag',

		// [wpacu_pro]
        'move_scripts_to_body',
        'move_scripts_to_body_exceptions',
        // [/wpacu_pro]

        'inline_css_files',
        'inline_css_files_below_size', // Enable?
        'inline_css_files_below_size_input', // Actual size
        'inline_css_files_list',

        // Combine loaded JS (remaining ones after unloading the useless ones) into fewer files
        'combine_loaded_js',
		'combine_loaded_js_exceptions',
        'combine_loaded_js_for_admin_only',
        'combine_loaded_js_defer_body', // Applies defer="defer" to the combined file(s) within BODY tag

        // [wpacu_pro]
        'combine_loaded_js_append_handle_extra', // Adds any CDATA and other associated inline SCRIPT tags content to the combined files
        // [/wpacu_pro]

		// Minify each loaded CSS (remaining ones after unloading the useless ones)
		'minify_loaded_css',
		'minify_loaded_css_inline',
		'minify_loaded_css_exceptions',

		// Minify each loaded JS (remaining ones after unloading the useless ones)
		'minify_loaded_js',
		'minify_loaded_js_inline',
		'minify_loaded_js_exceptions',

        'cdn_rewrite_enable',
		'cdn_rewrite_url_css',
		'cdn_rewrite_url_js',

        'disable_emojis',
		'disable_oembed',
		'disable_dashicons_for_guests',

		// Stored in 'wpassetcleanup_global_unload' option
		'disable_wp_block_library',
        'disable_jquery_migrate',
        'disable_comment_reply',

		// <head> CleanUp
		'remove_rsd_link',
		'remove_wlw_link',
		'remove_rest_api_link',
		'remove_shortlink',
		'remove_posts_rel_links',
		'remove_wp_version',

		// all "generator" meta tags including the WordPress version
		'remove_generator_tag',

		// RSS Feed Links
		'remove_main_feed_link',
		'remove_comment_feed_link',

		// Remove HTML comments
		'remove_html_comments',
		'remove_html_comments_exceptions',

		'disable_xmlrpc',

        // Allow Usage Tracking
        'allow_usage_tracking',

        // Serve cached CSS/JS details from: Database or Disk
        'fetch_cached_files_details_from',

        // Clear Cached CSS/JS files after (x) days
        'clear_cached_files_after',

		// Do not load Asset CleanUp (Pro) if the URI is matched by the specified patterns
		'do_not_load_plugin_patterns',

		// [wpacu_pro]
        // Local Fonts: "font-display" CSS property (Pro feature)
        'local_fonts_display',
        'local_fonts_display_overwrite',
		// [/wpacu_pro]

        // Local Fonts: Preload Files
        'local_fonts_preload_files',

        // Google Fonts: Combine Into One Request
        'google_fonts_combine',
        'google_fonts_combine_type',

        // Google Fonts: "font-display" CSS property: LINK & STYLE tags, @import in CSS files
        'google_fonts_display',

        // Google Fonts: preconnect hint
        'google_fonts_preconnect',

        // Google Fonts: Preload Files
        'google_fonts_preload_files',

        // Google Fonts: Remove all traces
        'google_fonts_remove',

        // [wpacu_lite]
        // Do not trigger Feedback Popup on Deactivation
        'disable_freemius'
		// [/wpacu_lite]
    );

    /**
     * @var array
     */
    public $currentSettings = array();

	/**
	 * @var array
	 */
	public $defaultSettings = array();

	/**
	 * Settings constructor.
	 */
	public function __construct()
    {
        $this->defaultSettings = array(
	        // Show the assets list within the Dashboard, while they are hidden in the front-end view
	        'dashboard_show' => '1',

	        // Direct AJAX call by default (not via WP Remote Post)
	        'dom_get_type'   => 'direct',

	        'hide_meta_boxes_for_post_types' => array(),

	        // Very good especially for page builders: Divi Visual Builder, Oxygen Builder, WPBakery, Beaver Builder etc.
	        // It is also hidden in preview mode (if query strings such as 'preview_nonce' are used)
	        'frontend_show_exceptions' =>  'et_fb=1'."\n"
	                                       .'ct_builder=true'."\n"
	                                       .'vc_editable=true'."\n"
	                                       .'preview_nonce='."\n",

	        // Since v1.2.9.3 (Lite) and version 1.1.0.8 (Pro), the default value is "by-location" (All Styles & All Scripts - By Location (Theme, Plugins, Custom & External))
	        // Prior to that it's "two-lists" (All Styles & All Scripts - 2 separate lists)
	        'assets_list_layout'              => 'by-location',
	        'assets_list_layout_areas_status' => 'expanded',

	        // [wpacu_pro]
	        'assets_list_layout_plugin_area_status' => 'expanded',
	        // [/wpacu_pro]

	        // "contracted" since 1.1.0.8 (Pro)
	        'assets_list_inline_code_status' => 'contracted', // takes less space overall

	        'minify_loaded_css_exceptions' => '(.*?)\.min.css'. "\n". '/plugins/wd-instagram-feed/(.*?).css',
	        'minify_loaded_js_exceptions'  => '(.*?)\.min.js' . "\n". '/plugins/wd-instagram-feed/(.*?).js',

	        'inline_css_files_below_size' => '1', // Enabled by default
	        'inline_css_files_below_size_input' => '3', // Size in KB

            // [wpacu_pro]
	        'inline_js_files_below_size_input' => '3', // Size in KB

            // Specific AMP scripts should always be in 'HEAD'
            'move_scripts_to_body_exceptions' => '//cdn.ampproject.org/',
            // [/wpacu_pro]

	        'combine_loaded_css_exceptions' => '/plugins/wd-instagram-feed/(.*?).css',
            'combine_loaded_css_append_handle_extra' => '1',

	        'combine_loaded_js_exceptions'  => '/plugins/wd-instagram-feed/(.*?).js',
	        // [wpacu_pro]
	        'combine_loaded_js_append_handle_extra' => '1',
	        // [/wpacu_pro]

	        // [wpacu_pro]
            'defer_css_loaded_body' => 'moved',
	        // [/wpacu_pro]

            'cache_dynamic_loaded_css' => 1,
	        'cache_dynamic_loaded_js'  => 1,

	        'input_style' => 'enhanced',

	        // Starting from v1.2.8.6 (lite), WordPress core files are hidden in the assets list as a default setting
	        'hide_core_files' => '1',

            'fetch_cached_files_details_from' => 'disk', // Do not add more rows to the database by default (options table can become quite large)

            'clear_cached_files_after' => '10'
        );
    }

	/**
     *
     */
    public function adminInit()
    {
        // This is triggered BEFORE "triggerAfterInit" from 'Main' class
        add_action('admin_init', array($this, 'saveSettings'), 9);

        if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_settings') {
	        add_action('wpacu_admin_notices', array($this, 'notices'));
        }
    }

	/**
	 *
	 */
	public function notices()
    {
    	$settings = $this->getAll();

    	// When all ways to manage the assets are not enabled
    	if ($settings['dashboard_show'] != 1 && $settings['frontend_show'] != 1) {
		    ?>
		    <div class="notice notice-warning">
				<p><span style="color: #ffb900;" class="dashicons dashicons-info"></span>&nbsp;<?php _e('It looks like you have both "Manage in the Dashboard?" and "Manage in the Front-end?" inactive. The plugin still works fine and any assets you have selected for unload are not loaded. However, if you want to manage the assets in any page, you need to have at least one of the view options enabled.', 'wp-asset-clean-up'); ?></p>
		    </div>
		    <?php
	    }

	    // After "Save changes" is clicked
        if (get_transient('wpacu_settings_updated')) {
            delete_transient('wpacu_settings_updated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> <?php _e('The settings were successfully updated.', 'wp-asset-clean-up'); ?></p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function saveSettings()
    {
	    if (! Misc::getVar('post', 'wpacu_settings_nonce')) {
		    return;
	    }

	    check_admin_referer('wpacu_settings_update', 'wpacu_settings_nonce');

        $savedSettings = Misc::getVar('post', WPACU_PLUGIN_ID . '_settings', array());
        $savedSettings = stripslashes_deep($savedSettings);

        // Hooks can be attached here
        // e.g. from PluginTracking.php (check if "Allow Usage Tracking" has been enabled)
        do_action('wpacu_before_save_settings', $savedSettings);

        $this->update($savedSettings);
    }

    /**
     *
     */
    public function settingsPage()
    {
        $data = $this->getAll();

        foreach ($this->settingsKeys as $settingKey) {
            // Special check for plugin versions < 1.2.4.4
            if ($settingKey === 'frontend_show') {
                $data['frontend_show'] = $this->showOnFrontEndLegacy();
            }
        }

        $globalUnloadList = Main::instance()->getGlobalUnload();

        if (in_array('wp-block-library', $globalUnloadList['styles'])) {
            $data['disable_wp_block_library'] = 1;
        }

        if (in_array('jquery-migrate', $globalUnloadList['scripts'])) {
            $data['disable_jquery_migrate'] = 1;
        }

	    if (in_array('comment-reply', $globalUnloadList['scripts'])) {
		    $data['disable_comment_reply'] = 1;
	    }

	    $data['is_optimize_css_enabled_by_other_party'] = OptimizeCss::isOptimizeCssEnabledByOtherParty();
	    $data['is_optimize_js_enabled_by_other_party']  = OptimizeJs::isOptimizeJsEnabledByOtherParty();

	    Main::instance()->parseTemplate('admin-page-settings-plugin', $data, true);
    }

    /**
     * @return bool
     */
    public function showOnFrontEndLegacy()
    {
        $settings = $this->getAll();

        if ($settings['frontend_show'] == 1) {
            return true;
        }

        // [wpacu_lite]
        // Prior to 1.2.4.4
        if (get_option( WPACU_PLUGIN_ID . '_frontend_show') == 1) {
            // Put it in the main settings option
            $settings = $this->getAll();
            $settings['frontend_show'] = 1;
            $this->update($settings);

            delete_option( WPACU_PLUGIN_ID . '_frontend_show');
            return true;
        }
	    // [/wpacu_lite]

        return false;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        if (! empty($this->currentSettings)) {
            return $this->currentSettings;
        }

        $settingsOption = get_option(WPACU_PLUGIN_ID . '_settings');

        // If there's already a record in the database
        if ($settingsOption !== '' && is_string($settingsOption)) {
            $settings = (array)json_decode($settingsOption);

            if (Misc::jsonLastError() === JSON_ERROR_NONE) {
                // Make sure all the keys are there even if no value is attached to them
                // To avoid writing extra checks in other parts of the code and prevent PHP notice errors
                foreach ($this->settingsKeys as $settingsKey) {
                    if (! array_key_exists($settingsKey, $settings)) {
                        $settings[$settingsKey] = '';

                        // If it doesn't exist, it was never saved
                        // Make sure the default value is added
	                    if (in_array($settingsKey, array('frontend_show_exceptions', 'minify_loaded_css_exceptions', 'inline_css_files_below_size_input', 'minify_loaded_js_exceptions', 'inline_js_files_below_size_input', 'clear_cached_files_after', 'hide_meta_boxes_for_post_types'))) {
	                        $settings[$settingsKey] = isset($this->defaultSettings[$settingsKey]) ? $this->defaultSettings[$settingsKey] : '';
                        }
                    }
                }

                $this->currentSettings = $this->filterSettings($settings);

                return $this->currentSettings;
            }
        }

	    // No record in the database? Set the default values
	    // That could be because no changes were done on the "Settings" page
	    // OR a full reset of the plugin (via "Tools") was performed
        $defaultSettings = $this->defaultSettings;

        foreach ($this->settingsKeys as $settingsKey) {
	        if (! array_key_exists($settingsKey, $defaultSettings)) {
		        // Keep the keys with empty values to avoid notice errors
		        $defaultSettings[$settingsKey] = '';
	        }
        }

	    return $this->filterSettings($defaultSettings);
    }

	/**
	 * @param $settingsKey
	 *
	 * @return mixed
	 */
	public function getOption($settingsKey)
    {
        $settings = $this->getAll();
        return $settings[$settingsKey];
    }

	/**
	 * @param $key
	 * @param $value
	 */
	public function updateOption($key, $value)
    {
	    $settings = $this->getAll();
	    $settings[$key] = $value;
	    $this->update($settings, false, false);
    }

	/**
	 * @param $key
	 */
	public function deleteOption($key)
	{
		$settings = $this->getAll();
		$settings[$key] = '';
		$this->update($settings, false, false);
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function filterSettings($settings)
	{
		// /?wpacu_test_mode (will load the page with "Test Mode" enabled disregarding the value from the plugin's "Settings")
		// For debugging purposes (e.g. to make sure the HTML source is the same when a guest user accesses it as the one that is generated when the plugin is deactivated)
		if (array_key_exists('wpacu_test_mode', $_GET)) {
			$settings['test_mode'] = true;
		}

		if (array_key_exists('wpacu_skip_test_mode', $_GET)) {
		    $settings['test_mode'] = false;
        }

		// /?wpacu_skip_inline_css
        if (array_key_exists('wpacu_skip_inline_css_files', $_GET)) {
	        $settings['inline_css_files'] = false;
        }

		// /?wpacu_skip_inline_js
		if (array_key_exists('wpacu_skip_inline_js_files', $_GET)) {
			$settings['inline_js_files'] = false;
		}

		if (isset($_GET['wpacu_settings']) && is_array($_GET['wpacu_settings']) && ! empty($_GET['wpacu_settings'])) {
            foreach ($_GET['wpacu_settings'] as $settingKey => $settingValue) {
                if ($settingValue === 'true') {
	                $settingValue = true;
                }
	            if ($settingValue === 'false') {
		            $settingValue = false;
	            }
                $settings[$settingKey] = $settingValue;
            }
		}

		return $settings;
	}

	/**
	 * @param $settings
	 * @param bool $redirectAfterUpdate
	 * @param bool $clearCache
	 */
	public function update($settings, $redirectAfterUpdate = true, $clearCache = true)
    {
	    $settingsNotNull = array();

	    foreach ($settings as $settingKey => $settingValue) {
	        if ($settingValue !== '') {
	            // Some validation
	            if ($settingKey === 'clear_cached_files_after') {
	                $settingValue = (int)$settingValue;
                }

		        $settingsNotNull[$settingKey] = $settingValue;
            }
        }

	    if (json_encode($this->defaultSettings) === json_encode($settingsNotNull)) {
	        // Do not keep a record in the database (no point of having an extra entry)
            // if the submitted values are the same as the default ones
	        delete_option(WPACU_PLUGIN_ID . '_settings');

	        if ($redirectAfterUpdate) {
		        $this->redirectAfterUpdate(); // script ends here
	        }
        }

	    // The following are only triggered IF the user submitted the form from "Settings" area
        if (Misc::getVar('post', 'wpacu_settings_nonce')) {
	        // "Site-Wide Common Unloads" tab
	        $disableGutenbergCssBlockLibrary = isset($_POST[WPACU_PLUGIN_ID . '_global_unloads']['disable_wp_block_library']);
	        $disableJQueryMigrate = isset($_POST[WPACU_PLUGIN_ID . '_global_unloads']['disable_jquery_migrate']);
	        $disableCommentReply  = isset($_POST[WPACU_PLUGIN_ID . '_global_unloads']['disable_comment_reply']);

	        $this->updateSiteWideRuleForCommonAssets(array(
                'wp_block_library' => $disableGutenbergCssBlockLibrary,
		        'jquery_migrate'   => $disableJQueryMigrate,
		        'comment_reply'    => $disableCommentReply
	        ));

	        // Some validation
	        $settings['local_fonts_preload_files'] = strip_tags($settings['local_fonts_preload_files']);
	        $settings['google_fonts_preload_files'] = strip_tags($settings['google_fonts_preload_files']);
        }

	    Misc::addUpdateOption(WPACU_PLUGIN_ID . '_settings', json_encode(Misc::filterList($settings)));

        if ($clearCache) {
	        // After settings are saved, clear all cache to re-built the CSS/JS based on the new settings
	        OptimizeCommon::clearAllCache();
        }

	    if ($redirectAfterUpdate) {
		    $this->redirectAfterUpdate();
	    }
    }

	/**
	 * @param $unloadsList
	 */
	public function updateSiteWideRuleForCommonAssets($unloadsList)
    {
	    $wpacuUpdate = new Update;

	    $disableGutenbergCssBlockLibrary = $unloadsList['wp_block_library'];
	    $disableJQueryMigrate = $unloadsList['jquery_migrate'];
	    $disableCommentReply  = $unloadsList['comment_reply'];

	    /*
	     * Add element(s) to the global unload rules
	     */
	    if ($disableGutenbergCssBlockLibrary) {
		    $wpacuUpdate->saveToEverywhereUnloads(array('wp-block-library'));
        }

	    if ($disableJQueryMigrate || $disableCommentReply) {
		    $unloadList = array();

		    // Add jQuery Migrate to the global unload rules
		    if ($disableJQueryMigrate) {
			    $unloadList[] = 'jquery-migrate';
		    }

		    // Add Comment Reply to the global unload rules
		    if ($disableCommentReply) {
			    $unloadList[] = 'comment-reply';
		    }

		    $wpacuUpdate->saveToEverywhereUnloads(array(), $unloadList);
	    }

	    /*
		 * Remove element(s) from the global unload rules
		 */
	    if (! $disableGutenbergCssBlockLibrary) {
		    $wpacuUpdate->removeEverywhereUnloads(array('wp-block-library' => 'remove'));
	    }

	    if (! $disableJQueryMigrate || ! $disableCommentReply) {
		    $removeFromUnloadList = array();

		    // Remove jQuery Migrate from global unload rules
		    if (! $disableJQueryMigrate) {
			    $removeFromUnloadList['jquery-migrate'] = 'remove';
		    }

		    // Remove Comment Reply from global unload rules
		    if (! $disableCommentReply) {
			    $removeFromUnloadList['comment-reply'] = 'remove';
		    }

		    $wpacuUpdate->removeEverywhereUnloads(array(), $removeFromUnloadList);
	    }
    }

	/**
	 *
	 */
	public function redirectAfterUpdate()
    {
	    $tabArea = Misc::getVar('post', 'wpacu_selected_tab_area', 'wpacu-setting-plugin-usage-settings');
	    $subTabArea = Misc::getVar('post', 'wpacu_selected_sub_tab_area', '');

	    set_transient('wpacu_settings_updated', 1, 30);

	    $wpacuQueryString = array(
            'page' => 'wpassetcleanup_settings',
            'wpacu_selected_tab_area' => $tabArea,
            'wpacu_time' => time()
        );

	    if ($subTabArea) {
		    $wpacuQueryString['wpacu_selected_sub_tab_area'] = $subTabArea;
        }

	    wp_redirect(add_query_arg($wpacuQueryString, admin_url('admin.php')));
	    exit();
    }
}
