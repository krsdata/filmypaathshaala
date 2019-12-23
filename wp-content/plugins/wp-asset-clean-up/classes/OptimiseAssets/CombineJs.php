<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\Misc;

/**
 * Class CombineJs
 * @package WpAssetCleanUp\OptimiseAssets
 */
class CombineJs
{
	/**
	 * @var string
	 */
	public static $jsonStorageFile = 'js-combined{maybe-extra-info}.json';

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function doCombine($htmlSource)
	{
		if (! (function_exists('libxml_use_internal_errors') && function_exists('libxml_clear_errors') && class_exists('DOMDocument'))) {
			return $htmlSource;
		}

		if ( ! self::proceedWithJsCombine() ) {
			return $htmlSource;
		}

		$combineLevel = 2;

		$isDeferAppliedOnBodyCombineGroupNo = 0;

		// Speed up processing by getting the already existing final CSS file URI
		// This will avoid parsing the HTML DOM and determine the combined URI paths for all the CSS files
		$finalCacheList = OptimizeCommon::getAssetCachedData(self::$jsonStorageFile, OptimizeJs::getRelPathJsCacheDir(), 'js');

		// $uriToFinalJsFile will always be relative ONLY within WP_CONTENT_DIR . self::getRelPathJsCacheDir()
		// which is usually "wp-content/cache/asset-cleanup/js/"

		// "false" would make it avoid checking the cache and always use the DOM Parser / RegExp
		// for DEV purposes ONLY as it uses more resources
		if (empty($finalCacheList)) {
			/*
			 * NO CACHING TRANSIENT; Parse the DOM
			*/
			// Nothing in the database records or the retrieved cached file does not exist?
			OptimizeCommon::clearAssetCachedData(self::$jsonStorageFile);

			// Fetch the DOM
			$documentForJS = new \DOMDocument();
			libxml_use_internal_errors(true);

			$combinableList = array();

			$jQueryMigrateInBody = false;
			$jQueryLibInBodyCount = 0;

			// Strip NOSCRIPT tags
			$htmlSourceAlt = preg_replace('@<(noscript)[^>]*?>.*?</\\1>@si', '', $htmlSource);
			$documentForJS->loadHTML($htmlSourceAlt);

			// Only keep combinable JS files
			foreach ( array( 'head', 'body' ) as $docLocationScript ) {
				$groupIndex = 1;

				$docLocationElements = $documentForJS->getElementsByTagName($docLocationScript)->item(0);
				if ($docLocationElements === null) { continue; }

				$scriptTags = $docLocationElements->getElementsByTagName('script');
				if ($scriptTags === null) { continue; }

				foreach ($scriptTags as $scriptTagIndex => $tagObject) {
					if (! $tagObject->hasAttributes()) { continue; }

					$scriptAttributes = array();

					foreach ($tagObject->attributes as $attrObj) {
						$scriptAttributes[$attrObj->nodeName] = trim($attrObj->nodeValue);
					}

					$scriptNotCombinable = false;

					$hasSrc = isset($scriptAttributes['src']) && trim($scriptAttributes['src']); // No valid SRC attribute? It's not combinable (e.g. an inline tag)
					$isPluginScript = isset($scriptAttributes['data-wpacu-plugin-script']); // Only of the user is logged-in (skip it as it belongs to the Asset CleanUp (Pro) plugin)

					if (! $hasSrc || $isPluginScript) {
						// Inline tag? Skip it in the BODY
						if ($docLocationScript === 'body') {
							continue;
						}

						// Because of jQuery, we will not have the list of all inline scripts and then the combined files as it is in BODY
						// Once an inline SCRIPT is stumbled upon, a new combined group in the HEAD tag will be formed
						if ($docLocationScript === 'head') {
							$scriptNotCombinable = true;
						}
					}

					$isInGroupType = 'standard';
					$isJQueryLib = $isJQueryMigrate = false;

					if (! $scriptNotCombinable) { // Has SRC and $isPluginScript is set to false? Check the script
						$src = (string)$scriptAttributes['src'];

						if (self::skipCombine($src)) {
							$scriptNotCombinable = true;
						}

						if (isset($scriptAttributes['data-wpacu-to-be-preloaded-basic']) && $scriptAttributes['data-wpacu-to-be-preloaded-basic']) {
							$scriptNotCombinable = true;
						}

						// Was it optimized and has the URL updated? Check the Source URL
						if (! $scriptNotCombinable && isset($scriptAttributes['data-wpacu-script-rel-src-before']) && $scriptAttributes['data-wpacu-script-rel-src-before'] && self::skipCombine($scriptAttributes['data-wpacu-script-rel-src-before'])) {
							$scriptNotCombinable = true;
						}

						$isJQueryLib     = isset($scriptAttributes['data-wpacu-jquery-core-handle']);
						$isJQueryMigrate = isset($scriptAttributes['data-wpacu-jquery-migrate-handle']);

						if (isset($scriptAttributes['async'], $scriptAttributes['defer'])) { // Has both "async" and "defer"
							$isInGroupType = 'async_defer';
						} elseif (isset($scriptAttributes['async'])) { // Has only "async"
							$isInGroupType = 'async';
						} elseif (isset($scriptAttributes['defer'])) { // Has only "defer"
							// Does it have "defer" attribute, it's combinable (all checks were already done), loads in the BODY tag and "combine_loaded_js_defer_body" is ON? Keep it to the combination list
							$isCombinableWithBodyDefer = (! $scriptNotCombinable && $docLocationScript === 'body' && Main::instance()->settings['combine_loaded_js_defer_body']);

							if (! $isCombinableWithBodyDefer) {
								$isInGroupType = 'defer'; // Otherwise, add it to the "defer" group type
							}
						}
					}

					if ( ! $scriptNotCombinable ) {
						// It also checks the domain name to make sure no external scripts would be added to the list
						if ( $localAssetPath = OptimizeCommon::getLocalAssetPath( $src, 'js' ) ) {
							// Standard (could be multiple groups per $docLocationScript), Async & Defer, Async, Defer
							$groupByType = ($isInGroupType === 'standard') ? $groupIndex : $isInGroupType;

							if ($docLocationScript === 'body') {
								if ($isJQueryLib || strpos($localAssetPath, '/wp-includes/js/jquery/jquery.js') !== false) {
									$jQueryLibInBodyCount++;
								}

								if ($isJQueryMigrate || strpos($localAssetPath, '/wp-includes/js/jquery/jquery-migrate') !== false) {
									$jQueryLibInBodyCount++;
									$jQueryMigrateInBody = true;
								}
							}

							$combinableList[$docLocationScript][$groupByType][] = array(
								'src'   => $src,
								'local' => $localAssetPath,
								'info'  => array(
									'is_jquery'         => $isJQueryLib,
									'is_jquery_migrate' => $isJQueryMigrate
								)
							);

							if (($docLocationScript === 'body') && $jQueryLibInBodyCount === 2) {
								$jQueryLibInBodyCount = 0; // reset it
								$groupIndex ++; // a new JS group will be created if jQuery & jQuery Migrate are combined in the BODY
								continue;
							}
						}
					} else {
						$groupIndex ++; // a new JS group will be created (applies to "standard" ones only)
					}
				}
			}

			// Could be pages such as maintenance mode with no external JavaScript files
			if (empty($combinableList)) {
				return $htmlSource;
			}

			$finalCacheList = array();

			foreach ($combinableList as $docLocationScript => $combinableListGroups) {
				$groupNo = 1;

				foreach ($combinableListGroups as $groupType => $groupFiles) {
					// Any groups having one file? Then it's not really a group and the file should load on its own
					// Could be one extra file besides the jQuery & jQuery Migrate group or the only JS file called within the HEAD
					if (count($groupFiles) < 2) {
						continue;
					}

					$combinedUriPaths = $localAssetsPaths = $groupScriptSrcs = array();
					$jQueryIsIncludedInGroup = false;

					foreach ($groupFiles as $groupFileData) {
						if ($groupFileData['info']['is_jquery'] || strpos($groupFileData['local'], '/wp-includes/js/jquery/jquery.js') !== false) {
							$jQueryIsIncludedInGroup = true;

							// Is jQuery in the BODY without jQuery Migrate loaded?
							// Isolate it as it needs to be the first to load in case there are inline scripts calling it before the combined group(s)
							if ($docLocationScript === 'body' && ! $jQueryMigrateInBody) {
								continue;
							}
						}

						$src                    = $groupFileData['src'];
						$groupScriptSrcs[]      = $src;
						$combinedUriPaths[]     = OptimizeCommon::getSourceRelPath($src);
						$localAssetsPaths[$src] = $groupFileData['local'];
					}

					$maybeDoJsCombine = self::maybeDoJsCombine(
						sha1(implode('', $combinedUriPaths)) . '-' . $groupNo,
						$localAssetsPaths,
						$docLocationScript
					);

					// Local path to combined CSS file
					$localFinalJsFile = $maybeDoJsCombine['local_final_js_file'];

					// URI (e.g. /wp-content/cache/asset-cleanup/[file-name-here.js]) to the combined JS file
					$uriToFinalJsFile = $maybeDoJsCombine['uri_final_js_file'];

					if (! file_exists($localFinalJsFile)) {
						return $htmlSource; // something is not right as the file wasn't created, we will return the original HTML source
					}

					$groupScriptSrcsFilter = array_map(static function($src) {
						return str_replace(site_url(), '{site_url}', $src);
					}, $groupScriptSrcs);

					$finalCacheList[$docLocationScript][$groupNo] = array(
						'uri_to_final_js_file' => $uriToFinalJsFile,
						'script_srcs'          => $groupScriptSrcsFilter
					);

					if (in_array($groupType, array('async_defer', 'async', 'defer'))) {
						if ($groupType === 'async_defer') {
							$finalCacheList[$docLocationScript][$groupNo]['extra_attributes'][] = 'async';
							$finalCacheList[$docLocationScript][$groupNo]['extra_attributes'][] = 'defer';
						} else {
							$finalCacheList[$docLocationScript][$groupNo]['extra_attributes'][] = $groupType;
						}
					}

					// Apply defer="defer" to combined JS files from the BODY tag (if enabled), except the combined jQuery & jQuery Migrate Group
					if ($docLocationScript === 'body' && ! $jQueryIsIncludedInGroup && Main::instance()->settings['combine_loaded_js_defer_body']) {
						if ($isDeferAppliedOnBodyCombineGroupNo === 0) {
							// Only record the first one
							$isDeferAppliedOnBodyCombineGroupNo = $groupNo;
						}

						$finalCacheList[$docLocationScript][$groupNo]['extra_attributes'][] = 'defer';
					}

					$groupNo ++;
				}
			}

			OptimizeCommon::setAssetCachedData(self::$jsonStorageFile, OptimizeJs::getRelPathJsCacheDir(), json_encode($finalCacheList));
		}

		if (! empty($finalCacheList)) {
			$cdnUrls = OptimizeCommon::getAnyCdnUrls();
			$cdnUrlForJs = isset($cdnUrls['js']) ? $cdnUrls['js'] : false;

			foreach ( $finalCacheList as $docLocationScript => $cachedGroupsList ) {
				foreach ($cachedGroupsList as $groupNo => $cachedValues) {
					$htmlSourceBeforeGroupReplacement = $htmlSource;

					$uriToFinalJsFile = $cachedValues['uri_to_final_js_file'];

					// Basic Combining (1) -> replace "first" tag with the final combination tag (there would be most likely multiple groups)
					// Enhanced Combining (2) -> replace "last" tag with the final combination tag (most likely one group)
					$indexReplacement = ($combineLevel === 2) ? (count($cachedValues['script_srcs']) - 1) : 0;

					$finalTagUrl = OptimizeCommon::filterWpContentUrl($cdnUrlForJs) . OptimizeJs::getRelPathJsCacheDir() . $uriToFinalJsFile;

					$deferAttr = '';

					if (isset($cachedValues['extra_attributes']) && ! empty($cachedValues['extra_attributes'])) {
						if (in_array('async', $cachedValues['extra_attributes']) && in_array('defer', $cachedValues['extra_attributes'])) {
							$deferAttr = 'async=\'async\' defer=\'defer\'';
						} elseif (in_array('async', $cachedValues['extra_attributes'])) {
							$deferAttr = 'async=\'async\'';
						} elseif (in_array('defer', $cachedValues['extra_attributes'])) {
							$deferAttr = 'defer=\'defer\'';
						}
					}

					$finalJsTag = <<<HTML
<script {$deferAttr} id='wpacu-combined-js-{$docLocationScript}-group-{$groupNo}' type='text/javascript' src='{$finalTagUrl}'></script>
HTML;
					$tagsStripped = 0;

					$scriptTags = OptimizeJs::getScriptTagsFromSrcs($cachedValues['script_srcs'], $htmlSource);

					foreach ($scriptTags as $groupScriptTagIndex => $scriptTag) {
						$replaceWith = ($groupScriptTagIndex === $indexReplacement) ? $finalJsTag : '';
						$htmlSourceBeforeTagReplacement = $htmlSource;

						$htmlSource = OptimizeJs::strReplaceOnce($scriptTag, $replaceWith, $htmlSource);

						if ($htmlSource !== $htmlSourceBeforeTagReplacement) {
							$tagsStripped ++;
						}
					}

					// At least two tags has have be stripped from the group to consider doing the group replacement
					// If the tags weren't replaced it's likely there were changes to their structure after they were cached for the group merging
					if ($tagsStripped < 2) {
						$htmlSource = $htmlSourceBeforeGroupReplacement;
					}
				}
			}
		}

		// Only relevant if "Defer loading JavaScript combined files from <body>"" in "Settings" - "Combine CSS & JS Files" - "Combine loaded JS (JavaScript) into fewer files"
		// and there is at least one combined deferred tag
		if ($isDeferAppliedOnBodyCombineGroupNo > 0) {
			$strPart = "id='wpacu-combined-js-body-group-".$isDeferAppliedOnBodyCombineGroupNo."' type='text/javascript' ";
			list(,$htmlAfterFirstCombinedDeferScript) = explode($strPart, $htmlSource);
			$htmlAfterFirstCombinedDeferScriptMaybeChanged = $htmlAfterFirstCombinedDeferScript;

			$documentForPartBodyHtml = new \DOMDocument();
			libxml_use_internal_errors(true);

			$documentForPartBodyHtml->loadHTML($htmlSource);

			$scriptTags = $documentForPartBodyHtml->getElementsByTagName('script');

			// No other SCRIPT tags found after the first (maybe last) deferred combined tag? Just return the HTML source
			if ($scriptTags === null) {
				libxml_clear_errors();
				return $htmlSource;
			}

			foreach ($scriptTags as $scriptTagIndex => $tagObject) {
				if (! $tagObject->hasAttributes()) {
					continue;
				}

				$scriptAttributes = array();

				foreach ($tagObject->attributes as $attrObj) {
					$scriptAttributes[$attrObj->nodeName] = trim($attrObj->nodeValue);
				}

				// No "src" attribute? Skip it (most likely an inline script tag)
				if (! (isset($scriptAttributes['src']) && $scriptAttributes['src'])) {
					continue;
				}

				// Skip it as "defer" is already set
				if (isset($scriptAttributes['defer'])) {
					continue;
				}

				// Has "src" attribute and "defer" is not applied? Add it
				$htmlAfterFirstCombinedDeferScriptMaybeChanged = trim(preg_replace(
					'#src(\s+|)=(\s+|)(|"|\'|\s+)('.preg_quote($scriptAttributes['src'], '/').')(\3)#si',
					'src=\3\4\3 defer=\'defer\'',
					$htmlAfterFirstCombinedDeferScriptMaybeChanged
				));
			}

			if ($htmlAfterFirstCombinedDeferScriptMaybeChanged && $htmlAfterFirstCombinedDeferScriptMaybeChanged !== $htmlAfterFirstCombinedDeferScript) {
				$htmlSource = str_replace($htmlAfterFirstCombinedDeferScript, $htmlAfterFirstCombinedDeferScriptMaybeChanged, $htmlSource);
			}
		}

		libxml_clear_errors();

		// Finally, return the HTML source
		return $htmlSource;
	}

	/**
	 * @param $src
	 *
	 * @return bool
	 */
	public static function skipCombine($src)
	{
		$regExps = array();

		if (Main::instance()->settings['combine_loaded_js_exceptions'] !== '') {
			$loadedCssExceptionsPatterns = trim(Main::instance()->settings['combine_loaded_js_exceptions']);

			if (strpos($loadedCssExceptionsPatterns, "\n")) {
				// Multiple values (one per line)
				foreach (explode("\n", $loadedCssExceptionsPatterns) as $loadedCssExceptionPattern) {
					$regExps[] = '#'.trim($loadedCssExceptionPattern).'#';
				}
			} else {
				// Only one value?
				$regExps[] = '#'.trim($loadedCssExceptionsPatterns).'#';
			}
		}

		// No exceptions set? Do not skip combination
		if (empty($regExps)) {
			return false;
		}

		foreach ($regExps as $regExp) {
			if (preg_match($regExp, $src)) {
				// Skip combination
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $shaOneCombinedUriPaths
	 * @param $localAssetsPaths
	 * @param $docLocationScript
	 *
	 * @return array
	 */
	public static function maybeDoJsCombine($shaOneCombinedUriPaths, $localAssetsPaths, $docLocationScript)
	{
		$current_user = wp_get_current_user();
		$dirToUserCachedFile = ((isset($current_user->ID) && $current_user->ID > 0) ? 'logged-in/' : '');

		$uriToFinalJsFile = $dirToUserCachedFile . $docLocationScript . '-' . $shaOneCombinedUriPaths . '.js';

		$localFinalJsFile = WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir() . $uriToFinalJsFile;
		$localDirForJsFile = WP_CONTENT_DIR . OptimizeJs::getRelPathJsCacheDir() . $dirToUserCachedFile;

		// Only combine if $shaOneCombinedUriPaths.js does not exist
		// If "?ver" value changes on any of the assets or the asset list changes in any way
		// then $shaOneCombinedUriPaths will change too and a new JS file will be generated and loaded

		$skipIfFileExists = true;

		if ($skipIfFileExists || ! file_exists($localFinalJsFile)) {
			// Change $assetsContents as paths to fonts and images that are relative (e.g. ../, ../../) have to be updated
			$finalJsContents = '';

			foreach ($localAssetsPaths as $assetHref => $localAssetsPath) {
				if ($jsContent = trim(FileSystem::file_get_contents($localAssetsPath))) {
					if ($jsContent === '') {
						continue;
					}

					// Does it have a source map? Strip it
					if (strpos($jsContent, 'sourceMappingURL') !== false) {
						$jsContent = OptimizeCommon::stripSourceMap($jsContent);
					}

					$pathToAssetDir = OptimizeCommon::getPathToAssetDir($assetHref);

					$contentToAddToCombinedFile = '/*! '.str_replace(ABSPATH, '/', $localAssetsPath)." */\n";
					$contentToAddToCombinedFile .= OptimizeJs::maybeDoJsFixes($jsContent, $pathToAssetDir . '/') . "\n";

					$finalJsContents .= $contentToAddToCombinedFile;
				}
			}

			if ($finalJsContents !== '') {
				if ($dirToUserCachedFile !== '' && isset($current_user->ID) && $current_user->ID > 0 && ! is_dir($localDirForJsFile)) {
					$makeLocalDirForJs = @mkdir($localDirForJsFile);

					if (! $makeLocalDirForJs) {
						return array('uri_final_js_file' => '', 'local_final_js_file' => '');
					}
				}

				FileSystem::file_put_contents($localFinalJsFile, $finalJsContents);
			}
		}

		return array(
			'uri_final_js_file'   => $uriToFinalJsFile,
			'local_final_js_file' => $localFinalJsFile
		);
	}

	/**
	 * @return bool
	 */
	public static function proceedWithJsCombine()
	{
		// not on query string request (debugging purposes)
		if (array_key_exists('wpacu_no_js_combine', $_GET)) {
			return false;
		}

		// No JS files are combined in the Dashboard
		// Always in the front-end view
		// Do not combine if there's a POST request as there could be assets loading conditionally
		// that might not be needed when the page is accessed without POST, making the final JS file larger
		if (! empty($_POST) || is_admin()) {
			return false; // Do not combine
		}

		// Only clean request URIs allowed (with few exceptions)
		if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
			// Exceptions
			if (! OptimizeCommon::loadOptimizedAssetsIfQueryStrings()) {
				return false;
			}
		}

		if (! OptimizeCommon::doCombineIsRegularPage()) {
			return false;
		}

		$pluginSettings = Main::instance()->settings;

		if ($pluginSettings['test_mode'] && ! Menu::userCanManageAssets()) {
			return false; // Do not combine anything if "Test Mode" is ON
		}

		if ($pluginSettings['combine_loaded_js'] === '') {
			return false; // Do not combine
		}

		if (OptimizeJs::isOptimizeJsEnabledByOtherParty('if_enabled')) {
			return false; // Do not combine (it's already enabled in other plugin)
		}

		// "Minify HTML" from WP Rocket is sometimes stripping combined SCRIPT tags
		// Better uncombined then missing essential SCRIPT files
		if (Misc::isWpRocketMinifyHtmlEnabled()) {
			return false;
		}

		if ( ($pluginSettings['combine_loaded_js'] === 'for_admin'
		      || $pluginSettings['combine_loaded_js_for_admin_only'] == 1)
		     && Menu::userCanManageAssets() ) {
			return true; // Do combine
		}

		if ( $pluginSettings['combine_loaded_js_for_admin_only'] === ''
		     && in_array($pluginSettings['combine_loaded_js'], array('for_all', 1)) ) {
			return true; // Do combine
		}

		// Finally, return false as none of the checks above matched
		return false;
	}
}
