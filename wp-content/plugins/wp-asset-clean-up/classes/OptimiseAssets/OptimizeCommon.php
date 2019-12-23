<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\CleanUp;
use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\Plugin;
use WpAssetCleanUp\Tools;

/**
 * Class OptimizeCommon
 * @package WpAssetCleanUp
 */
class OptimizeCommon
{
	/**
	 * @var string
	 */
	public static $relPathPluginCacheDirDefault = '/cache/asset-cleanup/'; // keep forward slash at the end

	/**
	 * @var string
	 */
	public static $optimizedSingleFilesDir = 'item';

	/**
	 * @var array
	 */
	public static $wellKnownExternalHosts = array(
		'googleapis.com',
		'bootstrapcdn.com',
		'cloudflare.com',
		'jsdelivr.net'
	);

	/**
	 *
	 */
	public function init()
	{
		add_action('switch_theme', array($this, 'clearAllCache'));
		add_action('after_switch_theme', array($this, 'clearAllCache'));

		// Is WP Rocket's page cache cleared? Clear Asset CleanUp's CSS cache files too
		if (array_key_exists('action', $_GET) && $_GET['action'] === 'purge_cache') {
			// Leave its default parameters, no redirect needed
			add_action('init', static function() {
				OptimizeCommon::clearAllCache();
			}, PHP_INT_MAX);
		}

		add_action('admin_post_assetcleanup_clear_assets_cache', static function() {
			self::clearAllCache(true);
		});

		// Make sure HTML changes are applied to cached pages from "Cache Enabler" plugin
		add_filter('cache_enabler_before_store', static function($htmlSource) {
			return self::alterHtmlSource($htmlSource);
		}, 1, 1);

		// In case HTML Minify is enabled in W3 Total Cache, make sure any settings (e.g. JS combine) in Asset CleanUp will be applied
		add_filter('w3tc_minify_before', static function ($htmlSource) {
			return self::alterHtmlSource($htmlSource);
		}, 1, 1);

		// Is Smart Slider 3 used?
		add_action('init', static function() {
			if (defined('NEXTEND_SMARTSLIDER_3_URL_PATH') && class_exists('\N2WordpressAssetInjector') && method_exists('\N2WordpressAssetInjector', 'platformRenderEnd')) {
				add_filter('wpacu_html_source_before_optimization', '\N2WordpressAssetInjector::platformRenderEnd');
			}
		}, PHP_INT_MAX);

		add_action('wp_loaded', array($this, 'maybeAlterHtmlSource'), 1);
	}

	/**
	 *
	 */
	public function maybeAlterHtmlSource()
	{
		if (is_admin()) { // don't apply any changes if not in the front-end view (e.g. Dashboard view)
			return;
		}

		ob_start(static function($htmlSource) {
			// Do not do any optimization if "Test Mode" is Enabled
			if (! Menu::userCanManageAssets() && Main::instance()->settings['test_mode']) {
				return $htmlSource;
			}

			return self::alterHtmlSource($htmlSource);
		});
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed|string|string[]|void|null
	 */
	public static function alterHtmlSource($htmlSource)
	{
		if (Plugin::preventAnyChanges()) {
			return $htmlSource;
		}

		$htmlSource = apply_filters('wpacu_html_source_before_optimization', $htmlSource);

		$htmlSource = OptimizeCss::alterHtmlSource($htmlSource);
		$htmlSource = OptimizeJs::alterHtmlSource($htmlSource);

		$htmlSource = Main::instance()->settings['remove_generator_tag'] ? CleanUp::removeMetaGenerators($htmlSource) : $htmlSource;
		$htmlSource = Main::instance()->settings['remove_html_comments'] ? CleanUp::removeHtmlComments($htmlSource) : $htmlSource;

		if (in_array(Main::instance()->settings['disable_xmlrpc'], array('disable_all', 'disable_pingback'))) {
			// Also clean it up from the <head> in case it's hardcoded
			$htmlSource = CleanUp::cleanPingbackLinkRel($htmlSource);
		}

		$htmlSource = apply_filters('wpacu_html_source', $htmlSource); // legacy

		return apply_filters('wpacu_html_source_after_optimization', $htmlSource);
	}

	/**
	 * @return string
	 */
	public static function getRelPathPluginCacheDir()
	{
		// In some cases, hosting companies put restriction for writable folders
		// Pantheon, for instance, allows only /wp-content/uploads/ to be writable
		// For security reasons, do not allow ../
		return ((defined('WPACU_CACHE_DIR') && strpos(WPACU_CACHE_DIR, '../') === false)
			? WPACU_CACHE_DIR
			: self::$relPathPluginCacheDirDefault);
	}

	/**
	 * The following output is used only for fetching purposes
	 * It will not be part of the final output
	 *
	 * @param $htmlSource
	 *
	 * @return string|string[]|null
	 */
	public static function cleanerHtmlSource($htmlSource)
	{
		// Removes HTML comments including MSIE conditional ones as they are left intact
		// and not combined with other JavaScript files in case the method is called from CombineJs.php
		return preg_replace('/<!--(.|\s)*?-->/', '', $htmlSource);
	}

	/**
	 * Is this a regular WordPress page (not feed, REST API etc.)?
	 * If not, do not proceed with any CSS/JS combine
	 *
	 * @return bool
	 */
	public static function doCombineIsRegularPage()
	{
		// In particular situations, do not process this
		if (strpos($_SERVER['REQUEST_URI'], '/wp-content/plugins/') !== false
		    && strpos($_SERVER['REQUEST_URI'], '/wp-content/themes/') !== false) {
			return false;
		}

		if (Misc::endsWith($_SERVER['REQUEST_URI'], '/comments/feed/')) {
			return false;
		}

		if (str_replace('//', '/', site_url() . '/feed/') === $_SERVER['REQUEST_URI']) {
			return false;
		}

		if (is_feed()) { // any kind of feed page
			return false;
		}

		return true;
	}

	/**
	 * @param $href
	 * @param $assetType
	 *
	 * @return bool|string
	 */
	public static function getLocalAssetPath($href, $assetType)
	{
		// Check if it starts without "/" or a protocol; e.g. "wp-content/theme/style.css", "wp-content/theme/script.js"
		if (strpos($href, '/') !== 0 &&
		    strpos($href, '//') !== 0 &&
		    stripos($href, 'http://') !== 0 &&
			stripos($href, 'https://') !== 0
		) {
			$href = '/'.$href; // append the forward slash to be processed as relative later on
		}

		// starting with "/", but not with "//"
		$isRelHref = (strpos($href, '/') === 0 && strpos($href, '//') !== 0);

		if (! $isRelHref) {
			$href = self::isSourceFromSameHost($href);

			if (! $href) {
				return false;
			}
		}

		$hrefRelPath = self::getSourceRelPath($href);

		if (strpos($hrefRelPath, '/') === 0) {
			$hrefRelPath = substr($hrefRelPath, 1);
		}

		$localAssetPath = ABSPATH . $hrefRelPath;

		if (strpos($localAssetPath, '?ver=') !== false) {
			list($localAssetPathAlt,) = explode('?ver=', $localAssetPath);
			$localAssetPath = $localAssetPathAlt;
		}

		// Not using "?ver="
		if (strpos($localAssetPath, '.' . $assetType . '?') !== false) {
			list($localAssetPathAlt,) = explode('.' . $assetType . '?', $localAssetPath);
			$localAssetPath = $localAssetPathAlt . '.' . $assetType;
		}

		if (strrchr($localAssetPath, '.') === '.' . $assetType && file_exists($localAssetPath)) {
			return $localAssetPath;
		}

		return false;
	}

	/**
	 * @param $assetHref
	 *
	 * @return bool|mixed|string
	 */
	public static function getPathToAssetDir($assetHref)
	{
		$posLastSlash   = strrpos($assetHref, '/');
		$pathToAssetDir = substr($assetHref, 0, $posLastSlash);

		$parseUrl = parse_url($pathToAssetDir);

		if (isset($parseUrl['scheme']) && $parseUrl['scheme'] !== '') {
			$pathToAssetDir = str_replace(
				array('http://'.$parseUrl['host'], 'https://'.$parseUrl['host']),
				'',
				$pathToAssetDir
			);
		} elseif (strpos($pathToAssetDir, '//') === 0) {
			$pathToAssetDir = str_replace(
				array('//'.$parseUrl['host'], '//'.$parseUrl['host']),
				'',
				$pathToAssetDir
			);
		}

		return $pathToAssetDir;
	}

	/**
	 * @param $sourceTag
	 * @param string $forAttr
	 *
	 * @return array|bool
	 */
	public static function getLocalCleanSourceFromTag($sourceTag, $forAttr)
	{
		preg_match_all('#'.$forAttr.'=(["\'])' . '(.*)' . '(["\'])#Usmi', $sourceTag, $outputMatchesSource);

		$sourceFromTag = (isset($outputMatchesSource[2][0]) && $outputMatchesSource[2][0])
			? trim($outputMatchesSource[2][0], '"\'')
			: false;

		if (! $sourceFromTag) {
			return false;
		}

		$isRelPath = false;

		// Check if it starts without "/" or a protocol; e.g. "wp-content/theme/style.css", "wp-content/theme/script.js"
		if (strpos($sourceFromTag, '/')   !== 0 &&
		    strpos($sourceFromTag, '//')  !== 0 &&
		    stripos($sourceFromTag, 'http://')   !== 0 &&
		    stripos($sourceFromTag, 'https://')  !== 0
		) {
			$sourceFromTag = '/'.$sourceFromTag; // append the forward slash to be processed as relative later on
		}

		// Perhaps the URL starts with / (not //) and site_url() was not used
		if (strpos($sourceFromTag, '/') === 0 && strpos($sourceFromTag, '//') !== 0 && file_exists(ABSPATH . $sourceFromTag)) {
			$isRelPath = true;
		}

		if ($isRelPath || (stripos($sourceFromTag, site_url()) !== false)) {
			$cleanLinkHrefFromTag = trim($sourceFromTag, '?&');
			$afterQuestionMark = WPACU_PLUGIN_VERSION;

			// Is it a dynamic URL? Keep the full path
			if (strpos($cleanLinkHrefFromTag, '.php') !== false ||
			    strpos($cleanLinkHrefFromTag, '/?') !== false ||
			    strpos($cleanLinkHrefFromTag, rtrim(site_url(), '/').'?') !== false) {
				list(,$afterQuestionMark) = explode('?', $sourceFromTag);
			} elseif (strpos($sourceFromTag, '?') !== false) {
				list($cleanLinkHrefFromTag, $afterQuestionMark) = explode('?', $sourceFromTag);
			}

			if (! $afterQuestionMark) {
				return false;
			}

			return array('source' => $cleanLinkHrefFromTag, 'after_question_mark' => $afterQuestionMark);
		}

		return false;
	}

	/**
	 * @param $href
	 *
	 * @return bool
	 */
	public static function isSourceFromSameHost($href)
	{
		// Check the host name
		$siteDbUrl   = get_option('siteurl');
		$siteUrlHost = strtolower(parse_url($siteDbUrl, PHP_URL_HOST));

		$cdnUrls = self::getAnyCdnUrls();

		// Are there any CDN urls set? Check them out
		if (! empty($cdnUrls)) {
			$hrefAlt = $href;

			foreach ($cdnUrls as $cdnUrl) {
				$hrefCleanedArray = self::getCleanHrefAfterCdnStrip(trim($cdnUrl), $hrefAlt);
				$cdnNoPrefix = $hrefCleanedArray['cdn_no_prefix'];
				$hrefAlt = $hrefCleanedArray['rel_href'];

				if ($hrefAlt !== $href && stripos($href, '//'.$cdnNoPrefix) !== false) {
					return $href;
				}
			}
		}

		if (strpos($href, '//') === 0) {
			list ($urlPrefix) = explode('//', $siteDbUrl);
			$href = $urlPrefix . $href;
		}

		/*
		 * Validate it first
		 */
		$assetHost = strtolower(parse_url($href, PHP_URL_HOST));

		if (preg_match('#'.$assetHost.'#si', implode('', self::$wellKnownExternalHosts))) {
			return false;
		}

		// Different host name (most likely 3rd party one such as fonts.googleapis.com or an external CDN)
		// Do not add it to the combine list
		if ($assetHost !== $siteUrlHost) {
			return false;
		}

		return $href;
	}

	/**
	 * @param $href
	 *
	 * @return mixed
	 */
	public static function getSourceRelPath($href)
	{
		// Already starts with / but not with //
		// Path is relative, just return it
		if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
			return $href;
		}

		// Starts with // (protocol is missing)
		// Add a dummy one to validate the whole URL and get the host
		if (strpos($href, '//') === 0) {
			$href = (Misc::isHttpsSecure() ? 'https:' : 'http:') . $href;
		}

		$parseUrl = parse_url($href);
		$hrefHost = isset($parseUrl['host']) ? $parseUrl['host'] : false;

		if (! $hrefHost) {
			return $href;
		}

		// Sometimes host is different on Staging websites such as the ones from Siteground
		// e.g. staging1.domain.com and domain.com
		// We need to make sure that the URI path is fetched correctly based on the host value from the $href
		$siteDbUrl      = get_option('siteurl');
		$parseDbSiteUrl = parse_url($siteDbUrl);

		$dbSiteUrlHost = $parseDbSiteUrl['host'];

		$finalBaseUrl = str_replace($dbSiteUrlHost, $hrefHost, $siteDbUrl);

		$hrefAlt = $finalRelPath = $href;

		$cdnUrls = self::getAnyCdnUrls();

		// Are there any CDN urls set? Filter them out in order to retrieve the relative path
		if (! empty($cdnUrls)) {
			foreach ($cdnUrls as $cdnUrl) {
				$hrefCleanArray = self::getCleanHrefAfterCdnStrip(trim($cdnUrl), $hrefAlt);
				$cdnNoPrefix = $hrefCleanArray['cdn_no_prefix'];

				$finalRelPath = str_replace(
					array('http://'.$cdnNoPrefix, 'https://'.$cdnNoPrefix, '//'.$cdnNoPrefix),
					'',
					$finalRelPath
				);
			}
		}

		$finalRelPath = str_replace($finalBaseUrl, '', $finalRelPath);

		if (defined('WP_ROCKET_CACHE_BUSTING_URL') && function_exists('get_current_blog_id') && get_current_blog_id()) {
			$finalRelPath = str_replace(
				array(WP_ROCKET_CACHE_BUSTING_URL . get_current_blog_id(), WP_ROCKET_CACHE_BUSTING_URL),
				'',
				$finalRelPath
			);
		}

		return $finalRelPath;
	}

	/**
	 * @param $cdnUrl
	 * @param $hrefAlt
	 *
	 * @return mixed
	 */
	public static function getCleanHrefAfterCdnStrip($cdnUrl, $hrefAlt)
	{
		if (strpos($cdnUrl, '//') !== false) {
			$parseUrl = parse_url($cdnUrl);
			$cdnNoPrefix = $parseUrl['host'];

			if (isset($parseUrl['path']) && $parseUrl['path'] !== '') {
				$cdnNoPrefix .= $parseUrl['path'];
			}
		} else {
			$cdnNoPrefix = $cdnUrl; // CNAME
		}

		$hrefAlt = str_ireplace(array('http://' . $cdnNoPrefix, 'https://' . $cdnNoPrefix, '//'.$cdnNoPrefix), '', $hrefAlt);

		return array('cdn_no_prefix' => $cdnNoPrefix, 'rel_href' => $hrefAlt);
	}

	/**
	 * @param $jsonStorageFile
	 * @param $relPathAssetCacheDir
	 * @param $assetType
	 * @param $forType
	 *
	 * @return array|mixed|object
	 */
	public static function getAssetCachedData($jsonStorageFile, $relPathAssetCacheDir, $assetType, $forType = 'combine')
	{
		if ($forType === 'combine') {
			// Only clean request URIs allowed
			if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
				list($requestUri) = explode('?', $_SERVER['REQUEST_URI']);
			} else {
				$requestUri = $_SERVER['REQUEST_URI'];
			}

			$requestUriPart = $requestUri;

			// Same results for Homepage (any pagination), 404 Not Found & Date archive pages
			if ($requestUri === '/' || is_404() || is_date() || Misc::isHomePage()) {
				$requestUriPart = '';
			}

			// Treat the pagination pages the same as the main page (same it's done for the unloading rules)
			if (($currentPage = get_query_var('paged')) && (is_archive() || is_singular())) {
				$paginationBase = isset($GLOBALS['wp_rewrite']->pagination_base) ? $GLOBALS['wp_rewrite']->pagination_base : 'page';
				$requestUriPart = str_replace('/'.$paginationBase.'/'.$currentPage.'/', '', $requestUriPart);
			}

			$dirToFilename = WP_CONTENT_DIR . dirname($relPathAssetCacheDir) . '/_storage/'
			                 . parse_url(site_url(), PHP_URL_HOST) .
			                 $requestUriPart . '/';

			$dirToFilename = str_replace('//', '/', $dirToFilename);

			$assetsFile = $dirToFilename . self::filterStorageFileName($jsonStorageFile);
		} elseif ($forType === 'item') {
			$dirToFilename = WP_CONTENT_DIR . dirname($relPathAssetCacheDir) . '/_storage/'.self::$optimizedSingleFilesDir.'/';
			$assetsFile = $dirToFilename . $jsonStorageFile;
		}

		if (! file_exists($assetsFile)) {
			return array();
		}

		if ($assetType === 'css') {
			$cachedAssetsFileExpiresIn = OptimizeCss::$cachedCssAssetsFileExpiresIn;
		} elseif ($assetType === 'js') {
			$cachedAssetsFileExpiresIn = OptimizeJs::$cachedJsAssetsFileExpiresIn;
		} else {
			return array();
		}

		// Delete cached file after it expired as it will be regenerated
		if (filemtime($assetsFile) < (time() - 1 * $cachedAssetsFileExpiresIn)) {
			self::clearAssetCachedData($jsonStorageFile);
			return array();
		}

		$optionValue = FileSystem::file_get_contents($assetsFile);

		if ($optionValue) {
			$optionValueArray = @json_decode($optionValue, ARRAY_A);

			if ($forType === 'combine') {
				$isValidJsonCombinedData = false;

				if (! empty($optionValueArray)) {
					foreach ($optionValueArray as $assetsPosition => $assetsValues) {
						foreach ($assetsValues as $finalValues) {
							if ( isset( $finalValues['link_hrefs'] ) ) {
								$isValidJsonCombinedData = true;
								break 2;
							}
						}
					}
				}

				if ($assetType === 'css' && $isValidJsonCombinedData) {
					return $optionValueArray;
				}

				if ($assetType === 'js' && ! empty($optionValueArray)) {
					return $optionValueArray;
				}
			} elseif ($forType === 'item') {
				return $optionValueArray;
			}
		}

		// File exists, but it's invalid or outdated; Delete it as it has to be re-generated
		self::clearAssetCachedData($jsonStorageFile);

		return array();
	}

	/**
	 * @param $jsonStorageFile
	 * @param $relPathAssetCacheDir
	 * @param $list
	 * @param $forType
	 */
	public static function setAssetCachedData($jsonStorageFile, $relPathAssetCacheDir, $list, $forType = 'combine')
	{
		// Combine CSS/JS JSON Storage
		if ($forType === 'combine') {
			// Only clean request URIs allowed
			if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
				list($requestUri) = explode('?', $_SERVER['REQUEST_URI']);
			} else {
				$requestUri = $_SERVER['REQUEST_URI'];
			}

			$requestUriPart = $requestUri;

			// Same results for Homepage (any pagination), 404 Not Found & Date archive pages
			if ($requestUri === '/' || is_404() || is_date() || Misc::isHomePage()) {
				$requestUriPart = '';
			}

			// Treat the pagination pages the same as the main page (same it's done for the unloading rules)
			if (($currentPage = get_query_var('paged')) && (is_archive() || is_singular())) {
				$paginationBase = isset($GLOBALS['wp_rewrite']->pagination_base) ? $GLOBALS['wp_rewrite']->pagination_base : 'page';
				$requestUriPart = str_replace('/'.$paginationBase.'/'.$currentPage.'/', '', $requestUriPart);
			}

			$dirToFilename = WP_CONTENT_DIR . dirname($relPathAssetCacheDir) . '/_storage/'
			                 . parse_url(site_url(), PHP_URL_HOST) .
			                 $requestUriPart . '/';

			$dirToFilename = str_replace('//', '/', $dirToFilename);

			if (! is_dir($dirToFilename)) {
				$makeFileDir = @mkdir($dirToFilename, 0755, true);

				if (! $makeFileDir) {
					return;
				}
			}

			$assetsFile = $dirToFilename . self::filterStorageFileName($jsonStorageFile);

			// CSS/JS JSON FILE DATA
			$assetsValue = $list;
		}

		// Optimize single CSS/JS item JSON Storage
		if ($forType === 'item') {
			$dirToFilename = WP_CONTENT_DIR . dirname($relPathAssetCacheDir) . '/_storage/'.self::$optimizedSingleFilesDir.'/';

			$dirToFilename = str_replace('//', '/', $dirToFilename);

			if (! is_dir($dirToFilename)) {
				$makeFileDir = @mkdir($dirToFilename, 0755, true);

				if (! $makeFileDir) {
					return;
				}
			}

			$assetsFile = $dirToFilename . $jsonStorageFile;
			$assetsValue = $list;
		}

		FileSystem::file_put_contents($assetsFile, $assetsValue);
	}

	/**
	 * @param $jsonStorageFile
	 */
	public static function clearAssetCachedData($jsonStorageFile)
	{
		if (strpos($jsonStorageFile, '-combined') !== false) {
			/*
	        * #1: Combined CSS/JS JSON
	        */
			// Only clean request URIs allowed
			if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
				list($requestUri) = explode('?', $_SERVER['REQUEST_URI']);
			} else {
				$requestUri = $_SERVER['REQUEST_URI'];
			}

			$requestUriPart = $requestUri;

			// Same results for Homepage (any pagination), 404 Not Found & Date archive pages
			if ($requestUri === '/' || is_404() || is_date() || Misc::isHomePage()) {
				$requestUriPart = '';
			}

			// Treat the pagination pages the same as the main page (same it's done for the unloading rules)
			if (($currentPage = get_query_var('paged')) && (is_archive() || is_singular())) {
				$paginationBase = isset($GLOBALS['wp_rewrite']->pagination_base) ? $GLOBALS['wp_rewrite']->pagination_base : 'page';
				$requestUriPart = str_replace('/'.$paginationBase.'/'.$currentPage.'/', '', $requestUriPart);
			}

			$dirToFilename = WP_CONTENT_DIR . self::getRelPathPluginCacheDir() . '_storage/'
			                 . parse_url(site_url(), PHP_URL_HOST) .
			                 $requestUriPart;

			// If it doesn't have "/" at the end, append it (it will prevent double forward slashes)
			if (substr($dirToFilename, - 1) !== '/') {
				$dirToFilename .= '/';
			}

			$assetsFile = $dirToFilename . self::filterStorageFileName($jsonStorageFile);
		} elseif (strpos($jsonStorageFile, '_optimize_') !== false) {
			/*
			 * #2: Optimized CSS/JS JSON
			 */
			$dirToFilename = WP_CONTENT_DIR . self::getRelPathPluginCacheDir() . '_storage/'.self::$optimizedSingleFilesDir.'/';
			$assetsFile = $dirToFilename . $jsonStorageFile;
		}

		if (file_exists($assetsFile)) { // avoid E_WARNING errors | check if it exists first
			@unlink($assetsFile);
		}
	}

	/**
	 * Clears all CSS & JS cache
	 *
	 * @param bool $redirectAfter
	 */
	public static function clearAllCache($redirectAfter = false)
	{
		if (self::doNotClearAllCache()) {
			return;
		}

		/*
		 * STEP 1: Clear all .json, .css & .js files (older than $clearFilesOlderThan days) that are related to "Minify/Combine CSS/JS files" feature
		 */
		$skipFiles       = array('index.php', '.htaccess');
		$fileExtToRemove = array('.json', '.css', '.js');

		$clearFilesOlderThan = Main::instance()->settings['clear_cached_files_after']; // days

		$assetCleanUpCacheDir = WP_CONTENT_DIR . self::getRelPathPluginCacheDir();
		$storageDir           = $assetCleanUpCacheDir . '_storage';

		$userIdDirs = array();

		if (is_dir($assetCleanUpCacheDir)) {
			$storageEmptyDirs = $allJsons = $allAssets = $allAssetsToKeep = array();

			$dirItems = new \RecursiveDirectoryIterator($assetCleanUpCacheDir, \RecursiveDirectoryIterator::SKIP_DOTS);

			foreach (new \RecursiveIteratorIterator($dirItems, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD) as $item) {
				$fileBaseName = trim(strrchr($item, '/'), '/');
				$fileExt = strrchr($fileBaseName, '.');

				if (is_file($item) && in_array($fileExt, $fileExtToRemove) && (! in_array($fileBaseName, $skipFiles))) {
					$isJsonFile  = ($fileExt === '.json');
					$isAssetFile = in_array($fileExt, array('.css', '.js'));

					// Remove all JSONs and .css & .js ONLY if they are older than $clearFilesOlderThan
					if ($isJsonFile || ($isAssetFile && (strtotime('-' . $clearFilesOlderThan . ' days') > $item->getCTime()))) {
						if ($isJsonFile) {
							$allJsons[] = $item;
						}

						if ($isAssetFile) {
							$allAssets[] = $item;
						}
					}
				} elseif (is_dir($item) && (strpos($item, '/css/logged-in/') !== false || strpos($item, '/js/logged-in/') !== false)) {
					$userIdDirs[] = $item;
				} elseif ($item != $storageDir && strpos($item, $storageDir) !== false) {
					$storageEmptyDirs[] = $item;
				}
			}

			// Now go through the JSONs and collect the latest assets so they would be kept
			foreach ($allJsons as $jsonFile) {
				$jsonContents = FileSystem::file_get_contents($jsonFile);
				$jsonContentsArray = @json_decode($jsonContents, ARRAY_A);

				$uriToFinalCssFileIndexKey = 'uri_to_final_css_file';
				$uriToFinalJsFileIndexKey = 'uri_to_final_js_file';

				if (is_array($jsonContentsArray) && strpos($jsonContents, $uriToFinalCssFileIndexKey) !== false) {
					if (isset($jsonContentsArray['head'][$uriToFinalCssFileIndexKey])) {
						$allAssetsToKeep[] = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . $jsonContentsArray['head'][$uriToFinalCssFileIndexKey];
					}

					if (isset($jsonContentsArray['body'][$uriToFinalCssFileIndexKey])) {
						$allAssetsToKeep[] = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . $jsonContentsArray['body'][$uriToFinalCssFileIndexKey];
					}
				} elseif (is_array($jsonContentsArray) && strpos($jsonContents, $uriToFinalJsFileIndexKey) !== false) {
					foreach ($jsonContentsArray as $jsGroupVal) {
						if (isset($jsGroupVal[$uriToFinalJsFileIndexKey]) ) {
							$allAssetsToKeep[] = WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir() . $jsGroupVal[$uriToFinalJsFileIndexKey];
						}
					}
				}

				// Clear the JSON files as new ones will be generated
				@unlink($jsonFile);
			}

			// Finally, collect the rest of $allAssetsToKeep from the database transients
			// Do not check if they are expired or not as their assets could still be referenced
			// until those pages will be accessed in a non-cached way
			global $wpdb;

			$sqlGetCacheTransients = <<<SQL
SELECT option_value FROM `{$wpdb->options}` 
WHERE `option_name` LIKE '%transient_wpacu_css_optimize%' OR `option_name` LIKE '%transient_wpacu_js_optimize%'
SQL;
			$cacheTransients = $wpdb->get_col($sqlGetCacheTransients);

			if (! empty($cacheTransients)) {
				foreach ($cacheTransients as $optionValue) {
					$jsonValueArray = @json_decode($optionValue, ARRAY_A);

					if (isset($jsonValueArray['optimize_uri'])) {
						$allAssetsToKeep[] = rtrim(ABSPATH, '/') . $jsonValueArray['optimize_uri'];
					}
				}
			}

			// Finally clear the matched assets, except the active ones
			foreach ($allAssets as $assetFile) {
				if (in_array($assetFile, $allAssetsToKeep)) {
					continue;
				}
				@unlink($assetFile);
			}

			foreach (array_reverse($storageEmptyDirs) as $storageEmptyDir) {
				@rmdir($storageEmptyDir);
			}

			// Remove empty dirs from /css/logged-in/ and /js/logged-in/
			if (! empty($userIdDirs)) {
				foreach ($userIdDirs as $userIdDir) {
					@rmdir($userIdDir); // it needs to be empty, otherwise, it will not be removed
				}
			}
		}

		@rmdir(WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir().'min');
		@rmdir(WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir().'min');
		@rmdir(WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir().'one');
		@rmdir(WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir().'one');

		/*
		 * STEP 2: Remove all transients related to the Minify CSS/JS files feature
		 */
		$toolsClass = new Tools();
		$toolsClass->clearAllCacheTransients();

		// Make sure all the caching files/folders are there in case the plugin was upgraded
		Plugin::createCacheFoldersFiles(array('css', 'js'));

		if ($redirectAfter && wp_get_referer()) {
			wp_safe_redirect(wp_get_referer());
			exit;
		}
	}

	/**
	 * @return array
	 */
	public static function getStorageStats()
	{
		$assetCleanUpCacheDir = WP_CONTENT_DIR . self::getRelPathPluginCacheDir();

		if (is_dir($assetCleanUpCacheDir)) {
			$dirItems = new \RecursiveDirectoryIterator($assetCleanUpCacheDir, \RecursiveDirectoryIterator::SKIP_DOTS);

			$totalFiles = 0;
			$totalSize = 0;

			foreach (new \RecursiveIteratorIterator($dirItems, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD) as $item) {
				$fileBaseName = trim(strrchr($item, '/'), '/');
				$fileExt = strrchr($fileBaseName, '.');

				if ($item->isFile() && in_array($fileExt, array('.css', '.js'))) {
					$totalSize += $item->getSize();
					$totalFiles++;
				}
			}

			return array(
				'total_size'  => Misc::formatBytes($totalSize),
				'total_files' => $totalFiles
			);
		}

		return array();
	}

	/**
	 * Prevent clear cache function in the following situations
	 *
	 * @return bool
	 */
	public static function doNotClearAllCache()
	{
		// WooCommerce GET or AJAX call
		if (array_key_exists('wc-ajax', $_GET) && $_GET['wc-ajax']) {
			return true;
		}

		if (defined('WC_DOING_AJAX') && WC_DOING_AJAX === true) {
			return true;
		}

		return false;
	}

	/**
	 * @param $fileName
	 *
	 * @return mixed
	 */
	public static function filterStorageFileName($fileName)
	{
		$filterString = '';

		if (is_404()) {
			$filterString = '-404-not-found';
		} elseif (is_date()) {
			$filterString = '-date';
		} elseif (Misc::isHomePage()) {
			$filterString = '-homepage';
		}

		$current_user = wp_get_current_user();

		if (isset($current_user->ID) && $current_user->ID > 0) {
			$fileName = str_replace(
				'{maybe-extra-info}',
				$filterString.'-logged-in',
				$fileName
			);
		} else {
			// Just clear {maybe-extra-info}
			$fileName = str_replace('{maybe-extra-info}', $filterString, $fileName);
		}

		return $fileName;
	}

	/**
	 * @param $anyCdnUrl
	 *
	 * @return mixed|string
	 */
	public static function filterWpContentUrl($anyCdnUrl = '')
	{
		$wpContentUrl = WP_CONTENT_URL;

		// Is the page loaded via SSL, but the site url from the database starts with 'http://'
		// Then use '//' in front of CSS/JS generated via Asset CleanUp
		if (Misc::isHttpsSecure() && strpos($wpContentUrl, 'http://') !== false) {
			$wpContentUrl = str_replace('http://', '//', $wpContentUrl);
		}

		if ($anyCdnUrl) {
			$wpContentUrl = str_replace(site_url(), self::cdnToUrlFormat($anyCdnUrl, 'raw'), $wpContentUrl);
		}

		return $wpContentUrl;
	}

	/**
	 * @param $assetContent
	 *
	 * @return mixed
	 */
	public static function stripSourceMap($assetContent)
	{
		return str_replace('# sourceMappingURL=', '# From Source Map: ', $assetContent);
	}

	/**
	 * URLs with query strings are not loading Optimised Assets (e.g. combine CSS files into one file)
	 * However, there are exceptions such as the ones below (preview, debugging purposes)
	 *
	 * @return bool
	 */
	public static function loadOptimizedAssetsIfQueryStrings()
	{
		$isPreview = (isset($_GET['preview_id'], $_GET['preview_nonce'], $_GET['preview']) || isset($_GET['preview']));
		$isQueryStringDebug = isset($_GET['wpacu_no_css_minify']) || isset($_GET['wpacu_no_js_minify']) || isset($_GET['wpacu_no_css_combine']) || isset($_GET['wpacu_no_js_combine']);

		return ($isPreview || $isQueryStringDebug);
	}

	/**
	 * The following custom methods of transients work for both (MySQL) database and local storage
	 * The cached information is read from both locations to avoid having too much queries to the database
	 *
	 * @param $transient
	 * @param $fromLocation
	 *
	 * @return bool|mixed
	 */
	public static function getTransient($transient, $fromLocation = 'db')
	{
		$contents = '';

		// Local record
		if ($fromLocation === 'disk') {
			$dirToFilename = WP_CONTENT_DIR . self::getRelPathPluginCacheDir() . '_storage/'.self::$optimizedSingleFilesDir.'/';
			$assetsFile = $dirToFilename . $transient.'.json';

			if (file_exists($assetsFile)) {
				$contents = trim(FileSystem::file_get_contents($assetsFile));

				if (! $contents) {
					// Empty file? Something weird, use the MySQL record as a fallback (if any)
					return get_transient($transient);
				}
			}

			return $contents;
		}

		// MySQL record: $fromLocation default 'db'
		return get_transient($transient);
	}

	/**
	 * @param $transientName
	 */
	public static function deleteTransient($transientName)
	{
		// MySQL record
		delete_transient($transientName);

		// File record
		self::clearAssetCachedData($transientName.'.json');
	}

	/**
	 * @param $transient
	 * @param $value
	 * @param int $expiration
	 */
	public static function setTransient($transient, $value, $expiration = 0)
	{
		// MySQL record
		set_transient($transient, $value, $expiration);

		// File record
		self::setAssetCachedData(
			$transient.'.json',
			OptimizeCss::getRelPathCssCacheDir(),
			$value,
			'item'
		);
	}

	/**
	 * @return array
	 */
	public static function getAnyCdnUrls()
	{
		if (! Main::instance()->settings['cdn_rewrite_enable']) {
			return array();
		}

		$cdnUrls = array();

		$cdnCssUrl = trim(Main::instance()->settings['cdn_rewrite_url_css']) ?: '';
		$cdnJsUrl  = trim(Main::instance()->settings['cdn_rewrite_url_js'])  ?: '';

		if ($cdnCssUrl) {
			$cdnUrls['css'] = $cdnCssUrl;
		}

		if ($cdnJsUrl) {
			$cdnUrls['js'] = $cdnJsUrl;
		}

		return $cdnUrls;
	}

	/**
	 * @param $cdnUrl
	 * @param $getType
	 *
	 * @return string|void
	 */
	public static function cdnToUrlFormat($cdnUrl, $getType)
	{
		if (! $cdnUrl) {
			return site_url();
		}

		$cdnUrlFinal = $cdnUrl;

		// CNAME (not URL) was added
		if (strpos($cdnUrl, '//') === false) {
			$cdnUrlFinal = '//'.$cdnUrl;
		}

		// The URL will start with //
		if ($getType === 'rel') {
			$cdnUrlFinal = trim(str_ireplace(array('http://', 'https://'), '//', $cdnUrl));
		}

		$cdnUrlFinal = rtrim($cdnUrlFinal, '/'); // no trailing slash after the CDN URL

		return $cdnUrlFinal;
	}
}
