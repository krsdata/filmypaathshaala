<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\Plugin;
use WpAssetCleanUp\Preloads;
use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\CleanUp;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\MetaBoxes;
use WpAssetCleanUp\Misc;

/**
 * Class OptimizeCss
 * @package WpAssetCleanUp
 */
class OptimizeCss
{
	/**
	 *
	 */
	const MOVE_NOSCRIPT_TO_BODY_FOR_ASYNC_PRELOADS = '<span style="display: none;" data-name=wpacu-delimiter data-content="ASSET CLEANUP NOSCRIPT FOR ASYNC PRELOADS"></span>';

	/**
	 * @var float|int
	 */
	public static $cachedCssAssetsFileExpiresIn = 28800; // 8 hours in seconds (60 * 60 * 8)

	/**
	 *
	 */
	public function init()
	{
		add_action('init', array($this, 'triggersAfterInit'));
		add_action('wp_footer', array($this, 'prepareOptimizeList'), PHP_INT_MAX);

		add_action('wp_footer', static function() {
			if ( Plugin::preventAnyChanges() || Main::isTestModeActive() ) {
				return;
			}

			echo self::MOVE_NOSCRIPT_TO_BODY_FOR_ASYNC_PRELOADS;
		}, PHP_INT_MAX);

		add_filter('wpacu_add_async_preloads_noscript', array($this, 'appendNoScriptAsyncPreloads'));
	}

	/**
	 *
	 */
	public function triggersAfterInit()
	{
		if (self::isInlineCssEnabled()) {
			$allPatterns = self::getAllInlineChosenPatterns();

			if (! empty($allPatterns)) {
				// Make "Inline CSS Files" compatible with "Optimize CSS Delivery" from WP Rocket
				add_filter('rocket_async_css_regex_pattern', static function($regex) {
					return '/(?=<link(?!.*wpacu-to-be-inlined.*)[^>]*\s(rel\s*=\s*[\'"]stylesheet["\']))<link(?!.*wpacu-to-be-inlined.*)[^>]*\shref\s*=\s*[\'"]([^\'"]+)[\'"](.*)>/iU';
				});

				add_filter('style_loader_tag', static function($styleTag) use ($allPatterns) {
					preg_match_all('#<link[^>]*stylesheet[^>]*(' . implode('|', $allPatterns) . ').*(>)#Usmi',
						$styleTag, $matchesSourcesFromTags, PREG_SET_ORDER);

					if (! empty($matchesSourcesFromTags)) {
						return str_replace('<link ', '<link wpacu-to-be-inlined=\'1\' ', $styleTag);
					}

					return $styleTag;
				}, 10, 1);
			}
		}
	}

	/**
	 * @return array
	 */
	public static function getAllInlineChosenPatterns()
	{
		$inlineCssFilesPatterns = trim(Main::instance()->settings['inline_css_files_list']);

		$allPatterns = array();

		if (strpos($inlineCssFilesPatterns, "\n")) {
			// Multiple values (one per line)
			foreach (explode("\n", $inlineCssFilesPatterns) as $inlinePattern) {
				$allPatterns[] = preg_quote(trim($inlinePattern), '/');
			}
		} else {
			// Only one value?
			$allPatterns[] = preg_quote(trim($inlineCssFilesPatterns), '/');
		}

		// Strip any empty values
		$allPatterns = array_filter($allPatterns);

		return $allPatterns;
	}

	/**
	 *
	 */
	public function prepareOptimizeList()
	{
		if ( ! self::isWorthCheckingForOptimization() || Plugin::preventAnyChanges() ) {
			return;
		}

		global $wp_styles;

		$allStylesHandles = wp_cache_get('wpacu_all_styles_handles');
		if (empty($allStylesHandles)) {
			return;
		}

		// [Start] Collect for caching
		$wpStylesDone = $wp_styles->done;
		$wpStylesRegistered = $wp_styles->registered;

		// Collect all enqueued clean (no query strings) HREFs to later compare them against any hardcoded CSS
		$allEnqueuedCleanLinkHrefs = array();

		foreach ($wpStylesDone as $styleHandle) {
			if (isset(Main::instance()->wpAllStyles['registered'][$styleHandle]->src) && ($src = Main::instance()->wpAllStyles['registered'][$styleHandle]->src)) {
				$localAssetPath = OptimizeCommon::getLocalAssetPath($src, 'css');

				if (! $localAssetPath || ! is_file($localAssetPath)) {
					continue; // not a local file
				}

				ob_start();
				$wp_styles->do_item($styleHandle);
				$linkSourceTag = trim(ob_get_clean());

				$cleanLinkHrefFromTagArray = OptimizeCommon::getLocalCleanSourceFromTag($linkSourceTag, 'href');

				if (isset($cleanLinkHrefFromTagArray['source']) && $cleanLinkHrefFromTagArray['source']) {
					$allEnqueuedCleanLinkHrefs[] = $cleanLinkHrefFromTagArray['source'];
				}
			}
		}

		$cssOptimizeList = array();

		foreach ($wpStylesDone as $handle) {
			if (! isset($wpStylesRegistered[$handle])) {
				continue;
			}

			$value = $wpStylesRegistered[$handle];

			$localAssetPath = OptimizeCommon::getLocalAssetPath($value->src, 'css');
			if (! $localAssetPath || ! is_file($localAssetPath)) {
				continue; // not a local file
			}

			$optimizeValues = self::maybeOptimizeIt($value);

			if (! empty($optimizeValues)) {
				$cssOptimizeList[] = $optimizeValues;
			}
		}

		if (empty($cssOptimizeList)) {
			return;
		}

		wp_cache_add('wpacu_css_enqueued_hrefs', $allEnqueuedCleanLinkHrefs);
		wp_cache_add('wpacu_css_optimize_list', $cssOptimizeList);
		// [End] Collect for caching
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function maybeOptimizeIt($value)
	{
		global $wp_version;

		$src = isset($value->src) ? $value->src : false;

		if (! $src) {
			return array();
		}

		$doFileMinify = true;

		if (! MinifyCss::isMinifyCssEnabled()) {
			$doFileMinify = false;
		} elseif (MinifyCss::skipMinify($src, $value->handle)) {
			$doFileMinify = false;
		}

		$fileVer = $dbVer = (isset($value->ver) && $value->ver) ? $value->ver : $wp_version;

		$handleDbStr = md5($value->handle);

		$transientName = 'wpacu_css_optimize_'.$handleDbStr;

		if (Main::instance()->settings['fetch_cached_files_details_from'] === 'db_disk') {
				    if ( ! isset( $GLOBALS['wpacu_from_location_inc'] ) ) {
					    $GLOBALS['wpacu_from_location_inc'] = 1;
				    }
				    $fromLocation = ( $GLOBALS['wpacu_from_location_inc'] % 2 ) ? 'db' : 'disk';
			    } else {
				    $fromLocation = Main::instance()->settings['fetch_cached_files_details_from'];
			    }

			$savedValues = OptimizeCommon::getTransient($transientName, $fromLocation);

			if ( $savedValues ) {
				$savedValuesArray = json_decode( $savedValues, ARRAY_A );

				if ( $savedValuesArray['ver'] !== $dbVer ) {
					// New File Version? Delete transient as it will be re-added to the database with the new version
					OptimizeCommon::deleteTransient($transientName);
				} else {
					$localPathToCssOptimized = str_replace( '//', '/', ABSPATH . $savedValuesArray['optimize_uri'] );

					// Read the file from its caching (that makes the processing faster)
					if ( isset( $savedValuesArray['source_uri'] ) && is_file( $localPathToCssOptimized ) ) {
						if (Main::instance()->settings['fetch_cached_files_details_from'] === 'db_disk') {
							$GLOBALS['wpacu_from_location_inc']++;
						}
						return array(
							$savedValuesArray['source_uri'],
							$savedValuesArray['optimize_uri'],
							$value->src
						);
					}

					// If nothing valid gets returned above, make sure the transient gets deleted as it's re-added later on
					OptimizeCommon::deleteTransient($transientName);
				}
			}
		// Check if it starts without "/" or a protocol; e.g. "wp-content/theme/style.css"
		if (strpos($src, '/') !== 0 &&
		    strpos($src, '//') !== 0 &&
		    stripos($src, 'http://') !== 0 &&
		    stripos($src, 'https://') !== 0
		) {
			$src = '/'.$src; // append the forward slash to be processed as relative later on
		}

		// Starts with '/', but not with '//'
		if (strpos($src, '/') === 0 && strpos($src, '//') !== 0) {
			$src = site_url() . $src;
		}

		$isCssFile = false;

		if (Main::instance()->settings['cache_dynamic_loaded_css'] &&
		    $value->handle === 'sccss_style' &&
		    in_array('simple-custom-css/simple-custom-css.php', apply_filters('active_plugins', get_option('active_plugins')))
		) {
			$pathToAssetDir = '';
			$sourceBeforeOptimization = $value->src;

			if (! ($cssContent = DynamicLoadedAssets::getAssetContentFrom('simple-custom-css', $value))) {
				return array();
			}
		} elseif (Main::instance()->settings['cache_dynamic_loaded_css'] &&
		          ((strpos($src, '/?') !== false) || (strpos($src, rtrim(site_url(),'/').'?') !== false) || (strpos($src, '.php?') !== false) || Misc::endsWith($src, '.php')) &&
		          (strpos($src, rtrim(site_url(), '/')) !== false)
		) {
			$pathToAssetDir = '';
			$sourceBeforeOptimization = str_replace('&#038;', '&', $value->src);

			if (! ($cssContent = DynamicLoadedAssets::getAssetContentFrom('dynamic', $value))) {
				return array();
			}
		} else {
			/*
			 * All the CSS that exists as a .css file within the plugins/theme
			 */
			$localAssetPath = OptimizeCommon::getLocalAssetPath($src, 'css');

			if (! is_file($localAssetPath)) {
				return array();
			}

			$isCssFile = true;

			$pathToAssetDir = OptimizeCommon::getPathToAssetDir($src);

			$cssContent = FileSystem::file_get_contents($localAssetPath, 'combine_css_imports');

			$sourceBeforeOptimization = str_replace(ABSPATH, '/', $localAssetPath);
		}

		/*
		 * [START] CSS Content Optimization
		*/
			// If there are no changes from this point, do not optimize (keep the file where it is)
			$cssContentBefore = $cssContent;

			if (Main::instance()->settings['google_fonts_display']) {
				// Any "font-display" enabled in "Settings" - "Google Fonts"?
				$cssContent = FontsGoogle::alterGoogleFontUrlFromCssContent($cssContent);
			}

			// Move any @imports to top; This also strips any @imports to Google Fonts if the option is chosen
			$cssContent = self::importsUpdate($cssContent);

			if ($doFileMinify) {
				// Minify this file?
				$cssContent = MinifyCss::applyMinification($cssContent) ?: $cssContent;
			}

			if (Main::instance()->settings['google_fonts_remove']) {
				$cssContent = FontsGoogleRemove::cleanFontFaceReferences($cssContent);
			}

			// No changes were made, thus, there's no point in changing the original file location
			if ($isCssFile && trim($cssContentBefore) === trim($cssContent)) {
				// There's no point in changing the original CSS (static) file location
				return false;
			}

			$cssContent = self::maybeFixCssContent($cssContent, $pathToAssetDir . '/'); // Path
		/*
         * [END] CSS Content Optimization
		*/

		// Relative path to the new file
		// Save it to /wp-content/cache/css/{OptimizeCommon::$optimizedSingleFilesDir}/
		if ($fileVer !== $wp_version) {
			if (is_array($fileVer)) {
				// Convert to string if it's an array (rare cases)
				$fileVer = implode('-', $fileVer);
			}
			$fileVer = trim(str_replace(' ', '_', preg_replace('/\s+/', ' ', $fileVer)));
			$fileVer = (strlen($fileVer) > 50) ? substr(md5($fileVer), 0, 20) : $fileVer; // don't end up with too long filenames
		}

		$newFilePathUri  = self::getRelPathCssCacheDir() . OptimizeCommon::$optimizedSingleFilesDir . '/' . $value->handle . '-v' . $fileVer;

		if (isset($localAssetPath)) { // could be from "/?custom-css=" so a check is needed
			$sha1File = @sha1_file($localAssetPath);

			if ($sha1File) {
				$newFilePathUri .= '-' . $sha1File;
			}
		}

		$newFilePathUri .= '.css';

		$newLocalPath    = WP_CONTENT_DIR . $newFilePathUri; // Ful Local path
		$newLocalPathUrl = WP_CONTENT_URL . $newFilePathUri; // Full URL path

		if ($cssContent) {
			$cssContent = '/*! ' . $sourceBeforeOptimization . ' */' . $cssContent;
		}

		$saveFile = FileSystem::file_put_contents($newLocalPath, $cssContent);

		if (! $saveFile && ! $cssContent) {
			// Fallback to the original CSS if the optimized version can't be created or updated
			return array();
		}

		$saveValues = array(
			'source_uri'   => OptimizeCommon::getSourceRelPath($src),
			'optimize_uri' => OptimizeCommon::getSourceRelPath($newLocalPathUrl),
			'ver'          => $dbVer
		);

		// Re-add transient
		OptimizeCommon::setTransient($transientName, json_encode($saveValues));

		return array(
			OptimizeCommon::getSourceRelPath($src), // Original SRC (Relative path)
			OptimizeCommon::getSourceRelPath($newLocalPathUrl), // New SRC (Relative path)
			$value->src // SRC (as it is)
		);
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed|void
	 */
	public static function alterHtmlSource($htmlSource)
	{
		// There has to be at least one "<link" or "<style", otherwise, it could be a feed request or something similar (not page, post, homepage etc.)
		if (stripos($htmlSource, '<link') === false && stripos($htmlSource, '<style') === false) {
			return $htmlSource;
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer('alter_html_source_for_optimize_css'); /* [/wpacu_timing] */

		// Are there any assets unloaded where their "children" are ignored?
		// Since they weren't dequeued the WP way (to avoid unloading the "children"), they will be stripped here
		if (! Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_unload_ignore_deps_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$htmlSource = self::ignoreDependencyRuleAndKeepChildrenLoaded($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		if (self::isInlineCssEnabled()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_inline_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$htmlSource = self::doInline($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		if (self::isWorthCheckingForOptimization()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_original_to_optimized_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			// 'wpacu_css_optimize_list' caching list is also checked; if it's empty, no optimization is made
			$htmlSource = self::updateHtmlSourceOriginalToOptimizedCss($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */


			// Are there any dynamic loaded CSS that were optimized? Check them too
			if (self::isInlineCssEnabled() && Main::instance()->settings['cache_dynamic_loaded_css']) {
				/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_dynamic_loaded_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
				$htmlSource = self::doInline($htmlSource, 'from_cache_for_dynamic_loaded_css');
				/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
			}
		}

		if (! Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_preload_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			/* [wpacu_pro] */ $htmlSource = apply_filters('wpacu_optimize_css_html_source', $htmlSource); /* [/wpacu_pro] */
			$htmlSource = Preloads::instance()->doChanges($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		$proceedWithCombineOnThisPage = true;

		// If "Do not combine CSS on this page" is checked in "Asset CleanUp: Options" side meta box
		// Works for posts, pages and custom post types
		if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0) {
			$pageOptions = MetaBoxes::getPageOptions(WPACU_CURRENT_PAGE_ID);

			// 'no_css_optimize' refers to avoid the combination of CSS files
			if ( isset( $pageOptions['no_css_optimize'] ) && $pageOptions['no_css_optimize'] ) {
				$proceedWithCombineOnThisPage = false;
			}
		}

		if ($proceedWithCombineOnThisPage) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_combine_css'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$htmlSource = CombineCss::doCombine($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		if (! Main::instance()->preventAssetsSettings() && Main::instance()->settings['minify_loaded_css'] && Main::instance()->settings['minify_loaded_css_inline']) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_minify_inline_style_tags'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$htmlSource = MinifyCss::minifyInlineStyleTags($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		// Final cleanups
		$htmlSource = preg_replace('#<link(\s+|)data-wpacu-link-rel-href-before=(["\'])' . '(.*)' . '(\1)#Usmi', '<link ', $htmlSource);
		$htmlSource = preg_replace('#<link(.*)data-wpacu-style-handle=\'(.*)\'#Umi', '<link \\1', $htmlSource);

		/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_google_fonts_optimization_removal'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
		// Alter HTML Source for Google Fonts Optimization / Removal
		$htmlSource = FontsGoogle::alterHtmlSource($htmlSource);
		/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */

		// NOSCRIPT fallbacks: Applies for Google Fonts (async) (Lite and Pro) and Preloads (Async in Pro version)
		/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_add_async_preloads_noscript'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
		$htmlSource = apply_filters('wpacu_add_async_preloads_noscript', $htmlSource);
		/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */

		// Final timing (for the whole HTML source)
		/* [wpacu_timing] */ Misc::scriptExecTimer('alter_html_source_for_optimize_css', 'end'); /* [/wpacu_timing] */

		return $htmlSource;
	}

	/**
	 * @return string
	 */
	public static function getRelPathCssCacheDir()
	{
		return OptimizeCommon::getRelPathPluginCacheDir().'css/'; // keep trailing slash at the end
	}

	/**
	 * @param $firstLinkHref
	 * @param $htmlSource
	 *
	 * @return string
	 */
	public static function getFirstLinkTag($firstLinkHref, $htmlSource)
	{
		preg_match_all('#<link[^>]*stylesheet[^>]*(>)#Usmi', $htmlSource, $matches);
		foreach ($matches[0] as $matchTag) {
			if (strpos($matchTag, $firstLinkHref) !== false) {
				return trim($matchTag);
			}
		}

		return '';
	}

	/**
	 *
	 * @param $cssContent
	 * @param $appendBefore
	 * @param $fix
	 *
	 * @return mixed
	 */
	public static function maybeFixCssContent($cssContent, $appendBefore, $fix = 'path')
	{
		// Updates (background | font etc.) URLs to the right path and others
		if ($fix === 'path') {
			// Clear any extra spaces between @import and the single/double quotes
			$cssContent = preg_replace('/@import(\s+|)([\'"])/i', '@import \\2', $cssContent);

			$cssContentPathReps = array(
				// @import with url(), background-image etc.
				'url("../' => 'url("'.$appendBefore.'../',
				"url('../" => "url('".$appendBefore.'../',
				'url(../'  => 'url('.$appendBefore.'../',

				'url("./'  => 'url("'.$appendBefore.'./',
				"url('./"  => "url('".$appendBefore.'./',
				'url(./'   => 'url('.$appendBefore.'./',

				// @import without URL
				'@import "../' => '@import "'.$appendBefore.'../',
				"@import '../" => "@import '".$appendBefore.'../',

				'@import "./'  => '@import "'.$appendBefore.'./',
				"@import './"  => "@import '".$appendBefore.'./'
			);

			$cssContent = str_replace(array_keys($cssContentPathReps), array_values($cssContentPathReps), $cssContent);

			// Rare cases
			$cssContent = preg_replace('/url\((\s+)http/i', 'url(http', $cssContent);

			// Avoid Background URLs starting with "data", "http" or "https" as they do not need to have a path updated
			preg_match_all('/url\((?![\'"]?(?:data|http|https):)[\'"]?([^\'")]*)[\'"]?\)/i', $cssContent, $matches);

			// If it start with forward slash (/), it doesn't need fix, just skip it
			// Also skip ../ types as they were already processed
			$toSkipList = array("url('/", 'url("/', 'url(/');

			foreach ($matches[0] as $match) {
				$fullUrlMatch = trim($match);

				foreach ($toSkipList as $toSkip) {
					if (substr($fullUrlMatch, 0, strlen($toSkip)) === $toSkip) {
						continue 2; // doesn't need any fix, go to the next match
					}
				}

				// Go through all situations: with and without quotes, with traversal directory (e.g. ../../)
				$alteredMatch = str_replace(
					array('url("', "url('"),
					array('url("' . $appendBefore, "url('" . $appendBefore),
					$fullUrlMatch
				);

				$alteredMatch = trim($alteredMatch);

				if (! in_array($fullUrlMatch[4], array("'", '"', '/', '.'))) {
					$alteredMatch = str_replace('url(', 'url(' . $appendBefore, $alteredMatch);
					$alteredMatch = str_replace(array('")', '\')'), ')', $alteredMatch);
				}

				// Finally, apply the changes
				$cssContent = str_replace($fullUrlMatch, $alteredMatch, $cssContent);

				// Bug fix
				$cssContent = str_replace(
					array($appendBefore . '"' . $appendBefore, $appendBefore . "'" . $appendBefore),
					$appendBefore,
					$cssContent
				);

				// Bug Fix 2
				$cssContent = str_replace($appendBefore . 'http', 'http', $cssContent);
				$cssContent = str_replace($appendBefore . '//', '//', $cssContent);
			}
		}

		return $cssContent;
	}

	/**
	 * Next: Alter the HTML source by updating the original link URLs with the just cached ones
	 *
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function updateHtmlSourceOriginalToOptimizedCss($htmlSource)
	{
		$cssOptimizeList = wp_cache_get('wpacu_css_optimize_list') ?: array();
		$allEnqueuedCleanLinkHrefs = wp_cache_get('wpacu_css_enqueued_hrefs') ?: array();

		$cdnUrls = OptimizeCommon::getAnyCdnUrls();
		$cdnUrlForCss = isset($cdnUrls['css']) ? $cdnUrls['css'] : false;

		preg_match_all('#<link[^>]*(stylesheet|(as(\s+|)=(\s+|)(|"|\')style(|"|\')))[^>]*(>)#Umi', OptimizeCommon::cleanerHtmlSource($htmlSource), $matchesSourcesFromTags, PREG_SET_ORDER);

		if (empty($matchesSourcesFromTags)) {
			return $htmlSource;
		}

		foreach ($matchesSourcesFromTags as $matches) {
			$linkSourceTag = $matches[0];

			if (strip_tags($linkSourceTag) !== '') {
				// Hmm? Not a valid tag... Skip it...
				continue;
			}

			// Is it a local CSS? Check if it's hardcoded (not enqueued the WordPress way)
			if ($cleanLinkHrefFromTagArray = OptimizeCommon::getLocalCleanSourceFromTag($linkSourceTag, 'href')) {
				$cleanLinkHrefFromTag = $cleanLinkHrefFromTagArray['source'];
				$afterQuestionMark = $cleanLinkHrefFromTagArray['after_question_mark'];

				if (! in_array($cleanLinkHrefFromTag, $allEnqueuedCleanLinkHrefs)) {
					// Not in the final enqueued list? Most likely hardcoded (not added via wp_enqueue_scripts())
					// Emulate the object value (as the enqueued styles)
					$value = (object)array(
						'handle' => md5($cleanLinkHrefFromTag),
						'src'    => $cleanLinkHrefFromTag,
						'ver'    => md5($afterQuestionMark)
					);

					$optimizeValues = self::maybeOptimizeIt($value);

					if (! empty($optimizeValues)) {
						$cssOptimizeList[] = $optimizeValues;
					}
				}
			}

			if (empty($cssOptimizeList)) {
				continue;
			}

			foreach ($cssOptimizeList as $listValues) {
				// Index 0: Source URL (relative)
				// Index 1: New Optimized URL (relative)
				// Index 2: Source URL (as it is)

				// The contents of the CSS file has been changed and thus, we will replace the source path from LINK with the cached (e.g. minified) one

				// If the minified files are deleted (e.g. /wp-content/cache/ is cleared)
				// do not replace the CSS file path to avoid breaking the website
				if (! is_file(rtrim(ABSPATH, '/') . $listValues[1])) {
					continue;
				}

				// Make sure the source URL gets updated even if it starts with // (some plugins/theme strip the protocol when enqueuing CSS files)
				$siteUrlNoProtocol = str_replace(array('http://', 'https://'), '//', site_url());

				// If the first value fails to be replaced, the next one will be attempted for replacement
				// the order of the elements in the array is very important
				$sourceUrlList = array(
					site_url() . $listValues[0], // with protocol
					$siteUrlNoProtocol . $listValues[0] // without protocol
				);

				if ($cdnUrlForCss) {
					// Does it have a CDN?
					$sourceUrlList[] = OptimizeCommon::cdnToUrlFormat($cdnUrlForCss, 'rel') . $listValues[0];
				}

				// Any rel tag? You never know
				// e.g. <link src="/wp-content/themes/my-theme/style.css"></script>
				if ( (strpos($listValues[2], '/') === 0 && strpos($listValues[2], '//') !== 0)
					|| (strpos($listValues[2], '/') !== 0 &&
					    strpos($listValues[2], '//') !== 0 &&
					    stripos($listValues[2], 'http://') !== 0 &&
					    stripos($listValues[2], 'https://') !== 0) ) {
					$sourceUrlList[] = $listValues[2];
				}

				// If no CDN is set, it will return site_url() as a prefix
				$optimizeUrl = OptimizeCommon::cdnToUrlFormat($cdnUrlForCss, 'raw') . $listValues[1]; // string

				if ($linkSourceTag !== str_ireplace($sourceUrlList, $optimizeUrl, $linkSourceTag)) {
					$newLinkSourceTag = self::updateOriginalToOptimizedTag($linkSourceTag, $sourceUrlList, $optimizeUrl);
					$htmlSource       = str_replace($linkSourceTag, $newLinkSourceTag, $htmlSource);
					break;
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $linkSourceTag string
	 * @param $sourceUrlList array
	 * @param $optimizeUrl string
	 *
	 * @return mixed
	 */
	public static function updateOriginalToOptimizedTag($linkSourceTag, $sourceUrlList, $optimizeUrl)
	{
		$newLinkSourceTag = str_replace($sourceUrlList, $optimizeUrl, $linkSourceTag);

		// Needed in case it's added to the Combine CSS exceptions list
		if (CombineCss::proceedWithCssCombine()) {
			$sourceUrlRel = is_array($sourceUrlList) ? OptimizeCommon::getSourceRelPath($sourceUrlList[0]) : OptimizeCommon::getSourceRelPath($sourceUrlList);
			$newLinkSourceTag = str_ireplace('<link ', '<link data-wpacu-link-rel-href-before="'.$sourceUrlRel.'" ', $newLinkSourceTag);
		}

		// Strip ?ver=
		$newLinkSourceTag = str_replace('.css&#038;ver=', '.css?ver=', $newLinkSourceTag);
		$toStrip = Misc::extractBetween($newLinkSourceTag, '?ver=', ' ');

		if (in_array(substr($toStrip, -1), array('"', "'"))) {
			$toStrip = '?ver='. trim(trim($toStrip, '"'), "'");
			$newLinkSourceTag = str_replace($toStrip, '', $newLinkSourceTag);
		}

		return $newLinkSourceTag;
	}

	/**
	 * @return bool
	 */
	public static function isInlineCssEnabled()
	{
		$isEnabledInSettingsWithListOrAuto = (Main::instance()->settings['inline_css_files'] &&
            (trim(Main::instance()->settings['inline_css_files_list']) !== '' || self::isAutoInlineEnabled()));

		if (! $isEnabledInSettingsWithListOrAuto) {
			return false;
		}

		// Finally, return true
		return true;
	}

	/**
	 * @param $htmlSource
	 * @param $fetch - 'all', 'from_cache_for_dynamic_loaded_css' (if "Cache Dynamic Loaded CSS" is enabled in "Settings" -> "Optimize CSS")
	 *
	 * @return mixed
	 */
	public static function doInline($htmlSource, $fetch = 'all')
	{
		$minifyInlineTags = (! Main::instance()->preventAssetsSettings() && Main::instance()->settings['minify_loaded_css'] && Main::instance()->settings['minify_loaded_css_inline']);
		$allPatterns = self::getAllInlineChosenPatterns();

		// Skip any LINK tags within conditional comments (e.g. Internet Explorer ones)
		if ($fetch === 'all') {
			preg_match_all('#<link[^>]*stylesheet[^>]*(>)#Umi', OptimizeCommon::cleanerHtmlSource($htmlSource, array('strip_content_between_conditional_comments')), $matchesSourcesFromTags, PREG_SET_ORDER);
		} elseif ($fetch === 'from_cache_for_dynamic_loaded_css') {
			preg_match_all('#<link[^>]*stylesheet[^>]*('.OptimizeCommon::getRelPathPluginCacheDir().').*(>)#Usmi', OptimizeCommon::cleanerHtmlSource($htmlSource, array('strip_content_between_conditional_comments')), $matchesSourcesFromTags, PREG_SET_ORDER);
		}

		// In case automatic inlining is used
		$belowSizeInput = (int)Main::instance()->settings['inline_css_files_below_size_input'];

		if ($belowSizeInput === 0) {
			$belowSizeInput = 1; // needs to have a minimum value
		}

		if (! empty($matchesSourcesFromTags)) {
			$cdnUrls = OptimizeCommon::getAnyCdnUrls();
			$cdnUrlForCss = isset($cdnUrls['css']) ? trim($cdnUrls['css']) : false;

			foreach ($matchesSourcesFromTags as $matchList) {
				$matchedTag = $matchList[0];

				if (strip_tags($matchedTag) !== '') {
					continue; // something is funny, don't mess with the HTML alteration, leave it as it was
				}

				// Condition #1: Only chosen (via textarea) CSS get inlined
				$chosenInlineCssMatches = (! empty($allPatterns) &&
				                 preg_match('/(' . implode('|', $allPatterns) . ')/i', $matchedTag));

				// Is auto inline disabled and the chosen CSS does not match? Continue to the next LINK tag
				if (! $chosenInlineCssMatches && ! self::isAutoInlineEnabled()) {
					continue;
				}

				preg_match_all('#href=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesHref);
				$linkHrefOriginal = trim($outputMatchesHref[2][0], '"\'');
				$localAssetPath = OptimizeCommon::getLocalAssetPath($linkHrefOriginal, 'css');

				if (! $localAssetPath) {
					continue; // Not on the same domain
				}

				// Condition #2: Auto inline is enabled and there's no match for any entry in the textarea
				if (! $chosenInlineCssMatches && self::isAutoInlineEnabled()) {
					$fileSizeKb = number_format(filesize($localAssetPath) / 1024, 2);

					// If it's not smaller than the value from the input, do not continue with the inlining
					if ($fileSizeKb >= $belowSizeInput) {
						continue;
					}
				}

				// Is there a media attribute? Make sure to add it to the STYLE tag
				preg_match_all('#media=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
				$mediaAttrValue = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';
				$mediaAttr = ($mediaAttrValue && $mediaAttrValue !== 'all') ? 'media=\''.$mediaAttrValue.'\'' : '';

				$appendBeforeAnyRelPath = $cdnUrlForCss ? OptimizeCommon::cdnToUrlFormat($cdnUrlForCss, 'raw') : '';

				$cssContent = self::maybeFixCssContent(
					FileSystem::file_get_contents($localAssetPath, 'combine_css_imports'), // CSS content
					$appendBeforeAnyRelPath . OptimizeCommon::getPathToAssetDir($linkHrefOriginal) . '/'
				);

				$cssContent = self::maybeAlterCssContent($cssContent, $minifyInlineTags, true);

				if ($cssContent && $cssContent !== '/**/') {
					$htmlSource = str_replace(
						$matchedTag,
						'<style type=\'text/css\' '.$mediaAttr.' data-wpacu-inline-css-file=\'1\'>'."\n".$cssContent."\n".'</style>',
						$htmlSource
					);
				} else {
					// After CSS alteration (e.g. minify), there's no content left, most likely the CSS file contained only comments, elements without any syntax or empty spaces
					// Strip the tag completely as there's no reason to print an empty SCRIPT tag to further add to the total DOM elements
					$htmlSource = str_replace($matchedTag, '', $htmlSource);
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * This applies to both inline and static JS files contents
	 *
	 * @param $cssContent
	 * @param bool $doCssMinify (false by default as it could be already minified or non-minify type)
	 * @param bool $useCache - (false by default - this param is relevant only for inline tags that are altered on the fly as existing CSS static enqueued files already have their own caching)
	 * @param array $extraParams
	 * @return mixed|string|string[]|null
	 */
	public static function maybeAlterCssContent($cssContent, $doCssMinify = false, $useCache = false, $extraParams = array())
	{
		if (! trim($cssContent)) {
			return $cssContent;
		}

		if ($useCache) {
			// Anything in the cache? Take it from there and don't spend resources with the minification
			// (which in some environments uses the CPU, depending on the complexity of the JavaScript code) and any other alteration
			$cssContentBeforeHash = sha1( $cssContent );

			$pathToInlineCssOptimizedItem = WP_CONTENT_DIR . self::getRelPathCssCacheDir() . '/item/inline/' . $cssContentBeforeHash . '.css';

			// Check if the file exists before moving forward
			if ( is_file( $pathToInlineCssOptimizedItem ) ) {
				$cachedCssFileExpiresIn = self::$cachedCssAssetsFileExpiresIn;

				if ( filemtime( $pathToInlineCssOptimizedItem ) < ( time() - 1 * $cachedCssFileExpiresIn ) ) {
					// Has the caching period expired? Remove the file as a new one has to be generated
					@unlink( $pathToInlineCssOptimizedItem );
				} else {
					// Not expired / Return its content from the cache in a faster way
					$inlineCssStorageItemJsonContent = trim( FileSystem::file_get_contents( $pathToInlineCssOptimizedItem ) );

					if ( $inlineCssStorageItemJsonContent !== '' ) {
						return $inlineCssStorageItemJsonContent;
					}
				}
			}
		}

		if ( $doCssMinify && in_array('just_minify', $extraParams) ) {
			$cssContent = MinifyCss::applyMinification( $cssContent, $useCache );
		} else {
			// Move any @imports to top; This also strips any @imports to Google Fonts if the option is chosen
			$cssContent = self::importsUpdate( $cssContent );

			if ( $doCssMinify ) {
				$cssContent = MinifyCss::applyMinification( $cssContent, $useCache );
			}

			if ( Main::instance()->settings['google_fonts_remove'] ) {
				$cssContent = FontsGoogleRemove::cleanFontFaceReferences( $cssContent );
			}
		}

		if ($useCache && isset($pathToInlineCssOptimizedItem)) {
			// Store the optimized content to the cached CSS file which would be read quicker
			if ($cssContent === '') {
				$cssContent = '/**/'; // dummy content to make it not empty (will checked and stripped later on)
			}

			FileSystem::file_put_contents( $pathToInlineCssOptimizedItem, $cssContent );
		}

		return $cssContent;
	}

	/**
	 * @return bool
	 */
	public static function isAutoInlineEnabled()
	{
		return Main::instance()->settings['inline_css_files'] &&
		       Main::instance()->settings['inline_css_files_below_size'] &&
		       (int)Main::instance()->settings['inline_css_files_below_size_input'] > 0;
	}

	/**
	 * Source: https://www.minifier.org/ | https://github.com/matthiasmullie/minify
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public static function importsUpdate($content)
	{
		if (preg_match_all('/(;?)(@import (?<url>url\()?(?P<quotes>["\']?).+?(?P=quotes)(?(url)\)));?/', $content, $matches)) {
			// Remove from content (they will be appended to the top if they qualify)
			foreach ($matches[0] as $import) {
				$content = str_replace($import, '', $content);
			}

			// Strip any @imports to Google Fonts if it's the case
			$importsAddToTop = Main::instance()->settings['google_fonts_remove'] ? FontsGoogleRemove::stripGoogleApisImport($matches[2]) : $matches[2];

			// Add to top if there are any imports left
			if (! empty($importsAddToTop)) {
				$content = implode(';', $importsAddToTop) . ';' . trim($content, ';');
			}
		}

		return $content;
	}

	/**
	 * @param string $returnType
	 *
	 * @return array|bool
	 */
	public static function isOptimizeCssEnabledByOtherParty($returnType = 'list')
	{
		$pluginsToCheck = array(
			'autoptimize/autoptimize.php'            => 'Autoptimize',
			'wp-rocket/wp-rocket.php'                => 'WP Rocket',
			'wp-fastest-cache/wpFastestCache.php'    => 'WP Fastest Cache',
			'w3-total-cache/w3-total-cache.php'      => 'W3 Total Cache',
			'sg-cachepress/sg-cachepress.php'        => 'SG Optimizer',
			'fast-velocity-minify/fvm.php'           => 'Fast Velocity Minify',
			'litespeed-cache/litespeed-cache.php'    => 'LiteSpeed Cache',
			'swift-performance-lite/performance.php' => 'Swift Performance Lite',
			'breeze/breeze.php'                      => 'Breeze – WordPress Cache Plugin'
		);

		$cssOptimizeEnabledIn = array();

		foreach ($pluginsToCheck as $plugin => $pluginTitle) {
			// "Autoptimize" check
			if ($plugin === 'autoptimize/autoptimize.php' && Misc::isPluginActive($plugin) && get_option('autoptimize_css')) {
				$cssOptimizeEnabledIn[] = $pluginTitle;

				if ($returnType === 'if_enabled') { return true; }
			}

			// "WP Rocket" check
			if ($plugin === 'wp-rocket/wp-rocket.php' && Misc::isPluginActive($plugin)) {
				if (function_exists('get_rocket_option')) {
					$wpRocketMinifyCss = trim(get_rocket_option('minify_css')) ?: false;
					$wpRocketMinifyConcatenateCss = trim(get_rocket_option('minify_concatenate_css')) ?: false;
				} else {
					$wpRocketSettings  = get_option('wp_rocket_settings');
					$wpRocketMinifyCss = isset($wpRocketSettings['minify_css']) && trim($wpRocketSettings['minify_css']);
					$wpRocketMinifyConcatenateCss = isset($wpRocketSettings['minify_concatenate_css']) && trim($wpRocketSettings['minify_concatenate_css']);
				}

				if ($wpRocketMinifyCss || $wpRocketMinifyConcatenateCss) {
					$cssOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "WP Fastest Cache" check
			if ($plugin === 'wp-fastest-cache/wpFastestCache.php' && Misc::isPluginActive($plugin)) {
				$wpfcOptionsJson = get_option('WpFastestCache');
				$wpfcOptions = @json_decode($wpfcOptionsJson, ARRAY_A);

				if (isset($wpfcOptions['wpFastestCacheMinifyCss']) || isset($wpfcOptions['wpFastestCacheCombineCss'])) {
					$cssOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "W3 Total Cache" check
			if ($plugin === 'w3-total-cache/w3-total-cache.php' && Misc::isPluginActive($plugin)) {
				$w3tcConfigMaster = Misc::getW3tcMasterConfig();
				$w3tcEnableCss = (int)trim(Misc::extractBetween($w3tcConfigMaster, '"minify.css.enable":', ','), '" ');

				if ($w3tcEnableCss === 1) {
					$cssOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "SG Optimizer" check
			if ($plugin === 'sg-cachepress/sg-cachepress.php' && Misc::isPluginActive($plugin)) {
				if (class_exists('\SiteGround_Optimizer\Options\Options')
				    && method_exists('\SiteGround_Optimizer\Options\Options', 'is_enabled')
				    && @\SiteGround_Optimizer\Options\Options::is_enabled('siteground_optimizer_combine_css')) {
					$cssOptimizeEnabledIn[] = $pluginTitle;
					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "Fast Velocity Minify" check
			if ($plugin === 'fast-velocity-minify/fvm.php' && Misc::isPluginActive($plugin)) {
				// It's enough if it's active due to its configuration
				$cssOptimizeEnabledIn[] = $pluginTitle;

				if ($returnType === 'if_enabled') { return true; }
			}

			// "LiteSpeed Cache" check
			if ($plugin === 'litespeed-cache/litespeed-cache.php' && Misc::isPluginActive($plugin) && ($liteSpeedCacheConf = apply_filters('litespeed_cache_get_options', get_option('litespeed-cache-conf')))) {
				if ( (isset($liteSpeedCacheConf['css_minify']) && $liteSpeedCacheConf['css_minify'])
				     || (isset($liteSpeedCacheConf['css_combine']) && $liteSpeedCacheConf['css_combine']) ) {
					$cssOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "Swift Performance Lite" check
			if ($plugin === 'swift-performance-lite/performance.php' && Misc::isPluginActive($plugin)
			    && class_exists('Swift_Performance_Lite') && method_exists('Swift_Performance_Lite', 'check_option')) {
				if ( @\Swift_Performance_Lite::check_option('merge-styles', 1) ) {
					$cssOptimizeEnabledIn[] = $pluginTitle;
				}

				if ($returnType === 'if_enabled') { return true; }
			}

			// "Breeze – WordPress Cache Plugin"
			if ($plugin === 'breeze/breeze.php' && Misc::isPluginActive($plugin)) {
				$breezeBasicSettings    = get_option('breeze_basic_settings');
				$breezeAdvancedSettings = get_option('breeze_advanced_settings');

				if (isset($breezeBasicSettings['breeze-minify-css'], $breezeAdvancedSettings['breeze-group-css'])
				    && $breezeBasicSettings['breeze-minify-css'] && $breezeAdvancedSettings['breeze-group-css']) {
					$cssOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}
		}

		if ($returnType === 'if_enabled') { return false; }

		return $cssOptimizeEnabledIn;
	}

	/**
	 * @return bool
	 */
	public static function isWpRocketOptimizeCssDeliveryEnabled()
	{
		if (Misc::isPluginActive('wp-rocket/wp-rocket.php')) {
			if (function_exists('get_rocket_option')) {
				$wpRocketAsyncCss = trim(get_rocket_option('async_css')) ?: false;
			} else {
				$wpRocketSettings  = get_option('wp_rocket_settings');
				$wpRocketAsyncCss = isset($wpRocketSettings['async_css']) && trim($wpRocketSettings['async_css']);
			}

			return $wpRocketAsyncCss;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function wpfcMinifyCssEnabledOnly()
	{
		if (Misc::isPluginActive('wp-fastest-cache/wpFastestCache.php')) {
			$wpfcOptionsJson = get_option('WpFastestCache');
			$wpfcOptions     = @json_decode($wpfcOptionsJson, ARRAY_A);

			// "Minify CSS" is enabled, "Combine CSS" is disabled
			return isset($wpfcOptions['wpFastestCacheMinifyCss']) && ! isset($wpfcOptions['wpFastestCacheCombineCss']);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isWorthCheckingForOptimization()
	{
		// At least one of these options have to be enabled
		// Otherwise, we will not perform specific useless actions and save resources
		return MinifyCss::isMinifyCssEnabled() ||
		       Main::instance()->settings['google_fonts_display'] ||
		       Main::instance()->settings['google_fonts_remove'];
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function ignoreDependencyRuleAndKeepChildrenLoaded($htmlSource)
	{
		$ignoreChild = Main::instance()->getIgnoreChildren();

		if (isset($ignoreChild['styles']) && ! empty($ignoreChild['styles'])) {
			foreach (array_keys($ignoreChild['styles']) as $styleHandle) {
				if (isset(Main::instance()->wpAllStyles['registered'][$styleHandle]->src, Main::instance()->ignoreChildren['styles'][$styleHandle.'_has_unload_rule']) && Main::instance()->wpAllStyles['registered'][$styleHandle]->src && Main::instance()->ignoreChildren['styles'][$styleHandle.'_has_unload_rule']) {
					$inlineStyleAssociatedWithLinkTag = self::getInlineAssociatedWithLinkHandle($styleHandle, Main::instance()->wpAllStyles['registered'], 'handle');

					if (isset($inlineStyleAssociatedWithLinkTag['after']) && $inlineStyleAssociatedWithLinkTag['after']) {
						$htmlSource = str_replace($inlineStyleAssociatedWithLinkTag['after'], '', $htmlSource);
					}

					$listWithMatches   = array();
					$listWithMatches[] = 'data-wpacu-style-handle=[\'"]'.$styleHandle.'[\'"]';

					if ($styleSrc = Main::instance()->wpAllStyles['registered'][$styleHandle]->src) {
						$listWithMatches[] = preg_quote(OptimizeCommon::getSourceRelPath($styleSrc), '/');
					}

					$htmlSource = CleanUp::cleanLinkTagFromHtmlSource($listWithMatches, $htmlSource);
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $styleTagOrHandle
	 * @param $wpacuRegisteredStyles
	 * @param $from
	 *
	 * @return array
	 */
	public static function getInlineAssociatedWithLinkHandle($styleTagOrHandle, $wpacuRegisteredStyles, $from = 'tag')
	{
		$styleExtraAfter = '';

		if ($from === 'tag') {
			preg_match_all('#data-wpacu-style-handle=([\'])' . '(.*)' . '(\1)#Usmi', $styleTagOrHandle, $outputMatches);
			$styleHandle = (isset($outputMatches[2][0]) && $outputMatches[2][0]) ? trim($outputMatches[2][0], '"\'') : '';
		} else {
			$styleHandle = $styleTagOrHandle;
		}

		if ($styleHandle && isset($wpacuRegisteredStyles[$styleHandle]->extra)) {
			$styleExtraArray = $wpacuRegisteredStyles[$styleHandle]->extra;

			if (isset($styleExtraArray['after']) && ! empty($styleExtraArray['after'])) {
				$styleExtraAfter .= "<style id='".$styleHandle."-inline-css' type='text/css'>\n";

				foreach ($styleExtraArray['after'] as $afterData) {
					if (! is_bool($afterData)) {
						$styleExtraAfter .= $afterData."\n";
					}
				}

				$styleExtraAfter .= '</style>';
			}
		}

		return array('after' => $styleExtraAfter);
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public function appendNoScriptAsyncPreloads($htmlSource)
	{
		preg_match_all('#<link[^>]*(data-wpacu-preload-it-async)[^>]*(>)#Umi', $htmlSource, $matchesSourcesFromTags, PREG_SET_ORDER);

		$noScripts = '';

		if (! empty($matchesSourcesFromTags)) {
			foreach ($matchesSourcesFromTags as $matchedValues) {
				$matchedTag = $matchedValues[0];

				preg_match_all('#media=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
				$mediaAttrValue = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

				preg_match_all('#href=(["\'])' . '(.*)' . '(["\'])#Usmi', $matchedTag, $outputMatchesMedia);
				$hrefAttrValue = isset($outputMatchesMedia[2][0]) ? trim($outputMatchesMedia[2][0], '"\'') : '';

				$noScripts .= '<noscript><link rel="stylesheet" href="'.$hrefAttrValue.'" media="'.$mediaAttrValue.'" /></noscript>'."\n";
			}
		}

		$htmlSource = str_replace(self::MOVE_NOSCRIPT_TO_BODY_FOR_ASYNC_PRELOADS, $noScripts, $htmlSource);

		return $htmlSource;
	}

	}
