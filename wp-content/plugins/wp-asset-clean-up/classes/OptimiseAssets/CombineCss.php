<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\Misc;

/**
 * Class CombineCss
 * @package WpAssetCleanUp\OptimiseAssets
 */
class CombineCss
{
	/**
	 * @var string
	 */
	public static $jsonStorageFile = 'css-combined{maybe-extra-info}.json';

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function doCombine($htmlSource)
	{
		if (! (function_exists('libxml_use_internal_errors') && function_exists('libxml_clear_errors') && class_exists('DOMDocument')) && class_exists('DOMXpath')) {
			return $htmlSource;
		}

		if ( ! self::proceedWithCssCombine() ) {
			return $htmlSource;
		}

		// Speed up processing by getting the already existing final CSS file URI
		// This will avoid parsing the HTML DOM and determine the combined URI paths for all the CSS files
		$storageJsonContents = OptimizeCommon::getAssetCachedData(self::$jsonStorageFile, OptimizeCss::getRelPathCssCacheDir(), 'css');

		// $uriToFinalCssFile will always be relative ONLY within WP_CONTENT_DIR . self::getRelPathCssCacheDir()
		// which is usually "wp-content/cache/asset-cleanup/css/"

		if (empty($storageJsonContents)) {
			$storageJsonContentsToSave = array();

			/*
			 * NO CACHING? Parse the DOM
			*/
			// Nothing in the database records or the retrieved cached file does not exist?
			OptimizeCommon::clearAssetCachedData(self::$jsonStorageFile);

			// Fetch the DOM
			$documentForCSS = new \DOMDocument();

			libxml_use_internal_errors(true);

			$storageJsonContents = array();

			// Strip NOSCRIPT tags
			$htmlSourceAlt = preg_replace('@<(noscript)[^>]*?>.*?</\\1>@si', '', $htmlSource);
			$documentForCSS->loadHTML($htmlSourceAlt);

			foreach (array('head', 'body') as $docLocationTag) {
				$combinedUriPathsGroup = $localAssetsPathsGroup = $linkHrefsGroup = array();

				$docLocationElements = $documentForCSS->getElementsByTagName($docLocationTag)->item(0);
				if ($docLocationElements === null) { continue; }

				$xpath = new \DOMXpath($documentForCSS);
				$linkStylesheetTags = $xpath->query('/html/'.$docLocationTag.'/link[@rel="stylesheet"]');
				if ($linkStylesheetTags === null) { continue; }

				foreach ($linkStylesheetTags as $tagObject) {
					$linkAttributes = array();
					foreach ($tagObject->attributes as $attrObj) { $linkAttributes[$attrObj->nodeName] = trim($attrObj->nodeValue); }

					// Only rel="stylesheet" (with no rel="preload" associated with it) gets prepared for combining as links with rel="preload" (if any) are never combined into a standard render-blocking CSS file
					// rel="preload" is there for a reason to make sure the CSS code is made available earlier prior to the one from rel="stylesheet" which is render-blocking
					if (isset($linkAttributes['rel'], $linkAttributes['href']) && $linkAttributes['href']) {
						$href = (string) $linkAttributes['href'];

						// 1) Check if there is any rel="preload" connected to the rel="stylesheet"
						//    making sure the file is not added to the final CSS combined file

						// 2) Only combine media "all" and the ones with no media
						//    Do not combine media='only screen and (max-width: 768px)', media='print' etc.
						if (isset($linkAttributes['data-wpacu-to-be-preloaded-basic']) && $linkAttributes['data-wpacu-to-be-preloaded-basic']) {
							continue;
						}

						// Separate each combined group by the "media" attribute; e.g. we don't want "all" and "print" mixed
						$mediaValue = (array_key_exists('media', $linkAttributes) && $linkAttributes['media']) ? $linkAttributes['media'] : 'all';

						if (self::skipCombine($linkAttributes['href'])) {
							continue;
						}

						// Was it optimized and has the URL updated? Check the Source URL
						if (isset($linkAttributes['data-wpacu-link-rel-href-before']) && $linkAttributes['data-wpacu-link-rel-href-before'] && self::skipCombine($linkAttributes['data-wpacu-link-rel-href-before'])) {
							continue;
						}

						$localAssetPath = OptimizeCommon::getLocalAssetPath($href, 'css');

						// It will skip external stylesheets (from a different domain)
						if ( $localAssetPath ) {
							$combinedUriPathsGroup[$mediaValue][]      = OptimizeCommon::getSourceRelPath($href);
							$localAssetsPathsGroup[$mediaValue][$href] = $localAssetPath;
							$linkHrefsGroup[$mediaValue][]             = $href;
						}
					}
				}

				// No Link Tags or only one tag in the combined group? Do not proceed with any combining
				if ( empty( $combinedUriPathsGroup ) ) {
					continue;
				}

				foreach ($combinedUriPathsGroup as $mediaValue => $combinedUriPaths) {
					$localAssetsPaths = $localAssetsPathsGroup[$mediaValue];
					$linkHrefs = $linkHrefsGroup[$mediaValue];

					$maybeDoCssCombine = self::maybeDoCssCombine(sha1(implode('', $combinedUriPaths)),
						$localAssetsPaths, $linkHrefs,
						$docLocationTag);

					// Local path to combined CSS file
					$localFinalCssFile = $maybeDoCssCombine['local_final_css_file'];

					// URI (e.g. /wp-content/cache/asset-cleanup/[file-name-here.css]) to the combined CSS file
					$uriToFinalCssFile = $maybeDoCssCombine['uri_final_css_file'];

					// Any link hrefs removed perhaps if the file wasn't combined?
					$linkHrefs = $maybeDoCssCombine['link_hrefs'];

					if (file_exists($localFinalCssFile)) {
						$storageJsonContents[$docLocationTag][$mediaValue] = array(
							'uri_to_final_css_file' => $uriToFinalCssFile,
							'link_hrefs'            => array_map(static function($href) {
								return str_replace('{site_url}', '', OptimizeCommon::getSourceRelPath($href));
							}, $linkHrefs)
						);

						$storageJsonContentsToSave[$docLocationTag][$mediaValue] = array(
							'uri_to_final_css_file' => $uriToFinalCssFile,
							'link_hrefs'            => array_map(static function($href) {
								return OptimizeCommon::getSourceRelPath($href);
							}, $linkHrefs)
						);
					}
				}
			}

			libxml_clear_errors();

			OptimizeCommon::setAssetCachedData(
				self::$jsonStorageFile,
				OptimizeCss::getRelPathCssCacheDir(),
				json_encode($storageJsonContentsToSave)
			);
		}

		$cdnUrls = OptimizeCommon::getAnyCdnUrls();
		$cdnUrlForCss = isset($cdnUrls['css']) ? $cdnUrls['css'] : false;

		if ( ! empty($storageJsonContents) ) {
			foreach ($storageJsonContents as $docLocationTag => $mediaValues) {
				$groupLocation = 1;

				foreach ($mediaValues as $mediaValue => $storageJsonContentLocation) {
					if (! isset($storageJsonContentLocation['link_hrefs'][0])) {
						continue;
					}

					$storageJsonContentLocation['link_hrefs'] = array_map(static function($href) {
						return str_replace('{site_url}', '', $href);
					}, $storageJsonContentLocation['link_hrefs']);

					$finalTagUrl = OptimizeCommon::filterWpContentUrl($cdnUrlForCss) . OptimizeCss::getRelPathCssCacheDir() . $storageJsonContentLocation['uri_to_final_css_file'];

					$finalCssTag = <<<HTML
<link rel='stylesheet' id='wpacu-combined-css-{$docLocationTag}-{$groupLocation}' href='{$finalTagUrl}' type='text/css' media='{$mediaValue}' />
HTML;
					$htmlSourceBeforeAnyLinkTagReplacement = $htmlSource;

					// Detect first LINK tag from the <$locationTag> and replace it with the final combined LINK tag
					$firstLinkTag = OptimizeCss::getFirstLinkTag($storageJsonContentLocation['link_hrefs'][0], $htmlSource);

					if ($firstLinkTag) {
						$htmlSource = str_replace($firstLinkTag, $finalCssTag, $htmlSource);
					}

					if ($htmlSource !== $htmlSourceBeforeAnyLinkTagReplacement) {
						$htmlSource = self::stripJustCombinedLinkTags(
							$storageJsonContentLocation['link_hrefs'],
							$htmlSource
						); // Strip the combined files to avoid duplicate code

						// There should be at least two replacements made
						if ($htmlSource === 'do_not_combine') {
							$htmlSource = $htmlSourceBeforeAnyLinkTagReplacement;
						} else {
							$groupLocation++;
						}
					}
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $filesSources
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function stripJustCombinedLinkTags($filesSources, $htmlSource)
	{
		preg_match_all('#<link[^>]*stylesheet[^>]*(>)#Usmi', $htmlSource, $matchesSourcesFromTags, PREG_SET_ORDER);

		$linkTagsStripped = 0;

		foreach ($matchesSourcesFromTags as $matchSourceFromTag) {
			$matchedSourceFromTag = (isset($matchSourceFromTag[0]) && strip_tags($matchSourceFromTag[0]) === '') ? trim($matchSourceFromTag[0]) : '';

			if (! $matchSourceFromTag) {
				continue;
			}

			$domTag = new \DOMDocument();

			libxml_use_internal_errors(true);
			$domTag->loadHTML($matchedSourceFromTag);

			foreach ($domTag->getElementsByTagName('link') as $tagObject) {
				if (! $tagObject->hasAttributes()) { continue; }

				foreach ($tagObject->attributes as $tagAttrs) {
					if ($tagAttrs->nodeName === 'href') {
						$relNodeValue = trim(OptimizeCommon::getSourceRelPath($tagAttrs->nodeValue));

						if (in_array($relNodeValue, $filesSources)) {
							$htmlSourceBeforeLinkTagReplacement = $htmlSource;

							$htmlSource = str_replace(array($matchedSourceFromTag."\n", $matchedSourceFromTag), '', $htmlSource);

							if ($htmlSource !== $htmlSourceBeforeLinkTagReplacement) {
								$linkTagsStripped++;
							}

							continue;
						}
					}
				}
			}

			libxml_clear_errors();
		}

		if ($linkTagsStripped < 1) {
			return 'do_not_combine';
		}

		return $htmlSource;
	}

	/**
	 * @param $href
	 *
	 * @return bool
	 */
	public static function skipCombine($href)
	{
		$regExps = array();

		if (Main::instance()->settings['combine_loaded_css_exceptions'] !== '') {
			$loadedCssExceptionsPatterns = trim(Main::instance()->settings['combine_loaded_css_exceptions']);

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
			if ( preg_match( $regExp, $href ) ) {
				// Skip combination
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $shaOneCombinedUriPaths
	 * @param $localAssetsPaths
	 * @param $linkHrefs
	 * @param $docLocationTag
	 *
	 * @return array
	 */
	public static function maybeDoCssCombine($shaOneCombinedUriPaths, $localAssetsPaths, $linkHrefs, $docLocationTag)
	{
		$current_user = wp_get_current_user();
		$dirToUserCachedFile = ((isset($current_user->ID) && $current_user->ID > 0) ? 'logged-in/' : '');

		$uriToFinalCssFile = $dirToUserCachedFile . $docLocationTag . '-' .$shaOneCombinedUriPaths . '.css';
		$localFinalCssFile = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . $uriToFinalCssFile;

		$localDirForCssFile = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . $dirToUserCachedFile;

		// Only combine if $shaOneCombinedUriPaths.css does not exist
		// If "?ver" value changes on any of the assets or the asset list changes in any way
		// then $shaOneCombinedUriPaths will change too and a new CSS file will be generated and loaded

		$skipIfFileExists = true;

		if ($skipIfFileExists || ! file_exists($localFinalCssFile)) {
			// Change $finalCombinedCssContent as paths to fonts and images that are relative (e.g. ../, ../../) have to be updated + other optimization changes
			$finalCombinedCssContent = '';

			foreach ($localAssetsPaths as $assetHref => $localAssetsPath) {
				if ($cssContent = trim(FileSystem::file_get_contents($localAssetsPath, 'combine_css_imports'))) {
					$pathToAssetDir = OptimizeCommon::getPathToAssetDir($assetHref);

					// Does it have a source map? Strip it
					if (strpos($cssContent, 'sourceMappingURL') !== false) {
						$cssContent = OptimizeCommon::stripSourceMap($cssContent);
					}

					$finalCombinedCssContent .= '/*! '.str_replace(ABSPATH, '/', $localAssetsPath)." */\n";
					$finalCombinedCssContent .= OptimizeCss::maybeFixCssContent($cssContent, $pathToAssetDir . '/') . "\n";
				}
			}

			// Move any @imports to top; This also strips any @imports to Google Fonts if the option is chosen
			$finalCombinedCssContent = trim(OptimizeCss::importsUpdate($finalCombinedCssContent));

			if (Main::instance()->settings['google_fonts_remove']) {
				$finalCombinedCssContent = FontsGoogleRemove::cleanFontFaceReferences($finalCombinedCssContent);
			}

			if ($finalCombinedCssContent) {
				if ($dirToUserCachedFile !== '' && isset($current_user->ID) && $current_user->ID > 0 && ! is_dir($localDirForCssFile)) {
					$makeLocalDirForCss = @mkdir($localDirForCssFile);

					if (! $makeLocalDirForCss) {
						return array('uri_final_css_file' => '', 'local_final_css_file' => '');
					}
				}

				FileSystem::file_put_contents($localFinalCssFile, $finalCombinedCssContent);
			}
		}

		return array(
			'uri_final_css_file'   => $uriToFinalCssFile,
			'local_final_css_file' => $localFinalCssFile,
			'link_hrefs'           => $linkHrefs
		);
	}

	/**
	 * @return bool
	 */
	public static function proceedWithCssCombine()
	{
		// Not on query string request (debugging purposes)
		if (array_key_exists('wpacu_no_css_combine', $_GET)) {
			return false;
		}

		// No CSS files are combined in the Dashboard
		// Always in the front-end view
		// Do not combine if there's a POST request as there could be assets loading conditionally
		// that might not be needed when the page is accessed without POST, making the final CSS file larger
		if (! empty($_POST) || is_admin()) {
			return false; // Do not combine
		}

		// Only clean request URIs allowed (with Exceptions)
		// Exceptions
		if ((strpos($_SERVER['REQUEST_URI'], '?') !== false) && ! OptimizeCommon::loadOptimizedAssetsIfQueryStrings()) {
			return false;
		}

		if (! OptimizeCommon::doCombineIsRegularPage()) {
			return false;
		}

		$pluginSettings = Main::instance()->settings;

		if ($pluginSettings['test_mode'] && ! Menu::userCanManageAssets()) {
			return false; // Do not combine anything if "Test Mode" is ON and the user is in guest mode (not logged-in)
		}

		if ($pluginSettings['combine_loaded_css'] === '') {
			return false; // Do not combine
		}

		if (OptimizeCss::isOptimizeCssEnabledByOtherParty('if_enabled')) {
			return false; // Do not combine (it's already enabled in other plugin)
		}

		// "Minify HTML" from WP Rocket is sometimes stripping combined LINK tags
		// Better uncombined then missing essential CSS files
		if (Misc::isWpRocketMinifyHtmlEnabled()) {
			return false;
		}

		if ( ($pluginSettings['combine_loaded_css'] === 'for_admin'
		      || $pluginSettings['combine_loaded_css_for_admin_only'] == 1)
		     && Menu::userCanManageAssets()) {
			return true; // Do combine
		}

		if ( $pluginSettings['combine_loaded_css_for_admin_only'] === ''
		     && in_array($pluginSettings['combine_loaded_css'], array('for_all', 1)) ) {
			return true; // Do combine
		}

		// Finally, return false as none of the checks above matched
		return false;
	}
}
