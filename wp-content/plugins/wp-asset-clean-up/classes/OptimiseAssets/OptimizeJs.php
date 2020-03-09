<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\CleanUp;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\MetaBoxes;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\Preloads;

/**
 * Class OptimizeJs
 * @package WpAssetCleanUp
 */
class OptimizeJs
{
	/**
	 * @var float|int
	 */
	public static $cachedJsAssetsFileExpiresIn = 28800; // 8 hours in seconds (60 * 60 * 8)

	/**
	 *
	 */
	public function init()
	{
		add_action('wp_print_footer_scripts', array($this, 'prepareOptimizeList'), PHP_INT_MAX);
	}

	/**
	 *
	 */
	public function prepareOptimizeList()
	{
		// Are both Minify and Cache Dynamic JS disabled? No point in continuing and using extra resources as there is nothing to change
		if (! self::isWorthCheckingForOptimization()) {
			return;
		}

		global $wp_scripts;

		$jsOptimizeList = array();

		$wpScriptsList = array_unique(array_merge($wp_scripts->done, $wp_scripts->queue));

		// Collect all enqueued clean (no query strings) HREFs to later compare them against any hardcoded JS
		$allEnqueuedCleanScriptSrcs = array();

		foreach ($wpScriptsList as $scriptHandle) {
			if (isset(Main::instance()->wpAllScripts['registered'][$scriptHandle]->src) && ($src = Main::instance()->wpAllScripts['registered'][$scriptHandle]->src)) {
				$localAssetPath = OptimizeCommon::getLocalAssetPath($src, 'js');

				if (! $localAssetPath || ! is_file($localAssetPath)) {
					continue; // not a local file
				}

				ob_start();
				$wp_scripts->do_item($scriptHandle);
				$scriptSourceTag = trim(ob_get_clean());

				$cleanScriptSrcFromTagArray = OptimizeCommon::getLocalCleanSourceFromTag($scriptSourceTag, 'src');

				if (isset($cleanScriptSrcFromTagArray['source']) && $cleanScriptSrcFromTagArray['source']) {
					$allEnqueuedCleanScriptSrcs[] = $cleanScriptSrcFromTagArray['source'];
				}
			}
		}

		// [Start] Collect for caching
		foreach ($wpScriptsList as $handle) {
			if (! isset($wp_scripts->registered[$handle])) { continue; }

			$value = $wp_scripts->registered[$handle];

			$localAssetPath = OptimizeCommon::getLocalAssetPath($value->src, 'js');
			if (! $localAssetPath || ! is_file($localAssetPath)) {
				continue; // not a local file
			}

			$optimizeValues = self::maybeOptimizeIt($value);

			if ( ! empty( $optimizeValues ) ) {
				$jsOptimizeList[] = $optimizeValues;
			}
		}

		wp_cache_add('wpacu_js_enqueued_srcs', $allEnqueuedCleanScriptSrcs);
		wp_cache_add('wpacu_js_optimize_list', $jsOptimizeList);
		// [End] Collect for caching
	}

	/**
	 * @param $value
	 *
	 * @return array
	 */
	public static function maybeOptimizeIt($value)
	{
		global $wp_version;

		$src = isset($value->src) ? $value->src : false;

		if (! $src) {
			return array();
		}

		$doFileMinify = true;

		if (! MinifyJs::isMinifyJsEnabled()) {
			$doFileMinify = false;
		} elseif (MinifyJs::skipMinify($src, $value->handle)) {
			$doFileMinify = false;
		}

		$fileVer = $dbVer = (isset($value->ver) && $value->ver) ? $value->ver : $wp_version;

		$handleDbStr = md5($value->handle);

		$transientName = 'wpacu_js_optimize_'.$handleDbStr;

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
				$savedValuesArray = json_decode($savedValues, ARRAY_A);

				if ( $savedValuesArray['ver'] !== $dbVer ) {
					// New File Version? Delete transient as it will be re-added to the database with the new version
					OptimizeCommon::deleteTransient($transientName);
				} else {
					$localPathToJsOptimized = str_replace( '//', '/', ABSPATH . $savedValuesArray['optimize_uri'] );

					// Do not load any minified JS file (from the database transient cache) if it doesn't exist
					// It will fallback to the original JS file
					if ( isset( $savedValuesArray['source_uri'] ) && is_file( $localPathToJsOptimized ) ) {
						if (Main::instance()->settings['fetch_cached_files_details_from'] === 'db_disk') {
							$GLOBALS['wpacu_from_location_inc']++;
						}

						return array(
							$savedValuesArray['source_uri'],
							$savedValuesArray['optimize_uri'],
							$value->src
						);
					}
				}
			}

		// Check if it starts without "/" or a protocol; e.g. "wp-content/theme/script.js"
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

		$isJsFile = $jsContentBefore = false;

		if (Main::instance()->settings['cache_dynamic_loaded_js'] &&
			((strpos($src, '/?') !== false) || strpos($src, '.php?') !== false || Misc::endsWith($src, '.php')) &&
		    (strpos($src, site_url()) !== false)
		) {
			$pathToAssetDir = '';
			$sourceBeforeOptimization = $value->src;

			if (! ($jsContent = DynamicLoadedAssets::getAssetContentFrom('dynamic', $value))) {
				return array();
			}
		} else {
			$localAssetPath = OptimizeCommon::getLocalAssetPath($src, 'js');

			if (! is_file($localAssetPath)) {
				return array();
			}

			$isJsFile = true;

			$pathToAssetDir = OptimizeCommon::getPathToAssetDir($value->src);
			$sourceBeforeOptimization = str_replace(ABSPATH, '/', $localAssetPath);

			$jsContent = $jsContentBefore = FileSystem::file_get_contents($localAssetPath);
		}

		$jsContent = self::maybeAlterJsContent($jsContent, $doFileMinify);

		if ($isJsFile && trim($jsContent, '; ') === trim($jsContentBefore, '; ')) {
			// The (static) JS file is already minified / No need to copy it in to the cache (save disk space)
			return array();
		}

		$jsContent = self::maybeDoJsFixes($jsContent, $pathToAssetDir . '/'); // Minify it and save it to /wp-content/cache/js/min/

		// Relative path to the new file
		// Save it to /wp-content/cache/js/{OptimizeCommon::$optimizedSingleFilesDir}/
		if ($fileVer !== $wp_version) {
			if (is_array($fileVer)) {
				// Convert to string if it's an array (rare cases)
				$fileVer = implode('-', $fileVer);
			}
			$fileVer = trim(str_replace(' ', '_', preg_replace('/\s+/', ' ', $fileVer)));
			$fileVer = (strlen($fileVer) > 50) ? substr(md5($fileVer), 0, 20) : $fileVer; // don't end up with too long filenames
		}

		$newFilePathUri  = self::getRelPathJsCacheDir() . OptimizeCommon::$optimizedSingleFilesDir . '/' . $value->handle . '-v' . $fileVer;

		if (isset($localAssetPath)) { // For static files only
			$sha1File = @sha1_file($localAssetPath);

			if ($sha1File) {
				$newFilePathUri .= '-' . $sha1File;
			}
		}

		$newFilePathUri .= '.js';

		$newLocalPath    = WP_CONTENT_DIR . $newFilePathUri; // Ful Local path
		$newLocalPathUrl = WP_CONTENT_URL . $newFilePathUri; // Full URL path

		if ($jsContent) {
			$jsContent = '/*! ' . $sourceBeforeOptimization . ' */' . "\n" . $jsContent;
		}

		$saveFile = FileSystem::file_put_contents($newLocalPath, $jsContent);

		if (! $saveFile || ! $jsContent) {
			// Fallback to the original JS if the optimized version can't be created or updated
			return array();
		}

		$saveValues = array(
			'source_uri'   => OptimizeCommon::getSourceRelPath($value->src),
			'optimize_uri' => OptimizeCommon::getSourceRelPath($newLocalPathUrl),
			'ver'          => $dbVer
		);

		// Add / Re-add (with new version) transient
		OptimizeCommon::setTransient($transientName, json_encode($saveValues));

		return array(
			OptimizeCommon::getSourceRelPath($value->src), // Original SRC (Relative path)
			OptimizeCommon::getSourceRelPath($newLocalPathUrl), // New SRC (Relative path)
			$value->src // SRC (as it is)
		);
	}

	/**
	 * This applies to both inline and static JS files contents
	 *
	 * @param $jsContent
	 * @param bool $doJsMinify (false by default as it could be already minified or non-minify type)
	 *
	 * @return mixed|string|string[]|null
	 */
	public static function maybeAlterJsContent($jsContent, $doJsMinify = false)
	{
		if (! trim($jsContent)) {
			return $jsContent;
		}

		if ($doJsMinify) {
			$jsContent = MinifyJs::applyMinification($jsContent);
		}

		if (Main::instance()->settings['google_fonts_remove']) {
			$jsContent = FontsGoogleRemove::stripReferencesFromJsCode($jsContent);
		} elseif (Main::instance()->settings['google_fonts_display']) {
			// Perhaps "display" parameter has to be applied to Google Font Links if they are active
			$jsContent = FontsGoogle::alterGoogleFontUrlFromJsContent($jsContent);
		}

		return $jsContent;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function updateHtmlSourceOriginalToOptimizedJs($htmlSource)
	{
		$jsOptimizeList = wp_cache_get('wpacu_js_optimize_list') ?: array();
		$allEnqueuedCleanScriptSrcs = wp_cache_get('wpacu_js_enqueued_srcs') ?: array();

		$cdnUrls = OptimizeCommon::getAnyCdnUrls();
		$cdnUrlForJs = isset($cdnUrls['js']) ? $cdnUrls['js'] : false;

		preg_match_all('#(<script[^>]*src(|\s+)=(|\s+)[^>]*(>))|(<link[^>]*(as(\s+|)=(\s+|)(|"|\')script(|"|\'))[^>]*(>))#Umi', OptimizeCommon::cleanerHtmlSource($htmlSource), $matchesSourcesFromTags, PREG_SET_ORDER);

		foreach ($matchesSourcesFromTags as $matches) {
			$scriptSourceTag = $matches[0];

			if (strip_tags($scriptSourceTag) !== '') {
				// Hmm? Not a valid tag... Skip it...
				continue;
			}

			$forAttr = 'src';

			// Any preloads for the optimized script?
			// e.g. <link rel='preload' as='script' href='...' />
			if (strpos($scriptSourceTag, '<link') !== false) {
				$forAttr = 'href';
			}

			// Is it a local JS? Check if it's hardcoded (not enqueued the WordPress way)
			if ($cleanScriptSrcFromTagArray = OptimizeCommon::getLocalCleanSourceFromTag($scriptSourceTag, $forAttr)) {
				$cleanScriptSrcFromTag      = $cleanScriptSrcFromTagArray['source'];
				$afterQuestionMark          = $cleanScriptSrcFromTagArray['after_question_mark'];

				if (! in_array($cleanScriptSrcFromTag, $allEnqueuedCleanScriptSrcs)) {
					// Not in the final enqueued list? Most likely hardcoded (not added via wp_enqueue_scripts())
					// Emulate the object value (as the enqueued styles)
					$value = (object)array(
						'handle' => md5($cleanScriptSrcFromTag),
						'src'    => $cleanScriptSrcFromTag,
						'ver'    => md5($afterQuestionMark)
					);

					$optimizeValues = self::maybeOptimizeIt($value);

					if (! empty($optimizeValues)) {
						$jsOptimizeList[] = $optimizeValues;
					}
				}
			}

			if (empty($jsOptimizeList)) {
				continue;
			}

			foreach ($jsOptimizeList as $listValues) {
				// Index 0: Source URL (relative)
				// Index 1: New Optimized URL (relative)
				// Index 2: Source URL (as it is)

				// If the minified files are deleted (e.g. /wp-content/cache/ is cleared)
				// do not replace the JS file path to avoid breaking the website
				if (! is_file(rtrim(ABSPATH, '/') . $listValues[1])) {
					continue;
				}

				// Make sure the source URL gets updated even if it starts with // (some plugins/theme strip the protocol when enqueuing JavaScript files)
				$siteUrlNoProtocol = str_replace(array('http://', 'https://'), '//', site_url());

				$sourceUrlList = array(
					site_url() . $listValues[0],
					$siteUrlNoProtocol . $listValues[0]
				); // array

				if ($cdnUrlForJs) {
					// Does it have a CDN?
					$sourceUrlList[] = OptimizeCommon::cdnToUrlFormat($cdnUrlForJs, 'rel') . $listValues[0];
				}

				// Any rel tag? You never know
				// e.g. <script src="/wp-content/themes/my-theme/script.js"></script>
				if ( (strpos($listValues[2], '/') === 0 && strpos($listValues[2], '//') !== 0)
				     || (strpos($listValues[2], '/') !== 0 &&
				         strpos($listValues[2], '//') !== 0 &&
				         stripos($listValues[2], 'http://') !== 0 &&
				         stripos($listValues[2], 'https://') !== 0) ) {
					$sourceUrlList[] = $listValues[2];
				}

				// If no CDN is set, it will return site_url() as a prefix
				$optimizeUrl = OptimizeCommon::cdnToUrlFormat($cdnUrlForJs, 'raw') . $listValues[1]; // string

				if ($scriptSourceTag !== str_ireplace($sourceUrlList, $optimizeUrl, $scriptSourceTag)) {
					$newLinkSourceTag = self::updateOriginalToOptimizedTag($scriptSourceTag, $sourceUrlList, $optimizeUrl);
					$htmlSource       = str_replace($scriptSourceTag, $newLinkSourceTag, $htmlSource);
					break;
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $scriptSourceTag string
	 * @param $sourceUrl array
	 * @param $optimizeUrl string
	 *
	 * @return mixed
	 */
	public static function updateOriginalToOptimizedTag($scriptSourceTag, $sourceUrl, $optimizeUrl)
	{
		$newScriptSourceTag = str_replace($sourceUrl, $optimizeUrl, $scriptSourceTag);

		// Needed in case it's added to the Combine JS exceptions list
		if (CombineJs::proceedWithJsCombine()) {
			$sourceUrlRel = is_array($sourceUrl) ? OptimizeCommon::getSourceRelPath($sourceUrl[0]) : OptimizeCommon::getSourceRelPath($sourceUrl);
			$newScriptSourceTag = str_ireplace('<script ', '<script data-wpacu-script-rel-src-before="'.$sourceUrlRel.'" ', $newScriptSourceTag);
		}

		// Strip ?ver=
		$toStrip = Misc::extractBetween($newScriptSourceTag, '?ver=', '>');

		if (in_array(substr($toStrip, -1), array('"', "'"))) {
			$toStrip = '?ver='. trim(trim($toStrip, '"'), "'");
			$newScriptSourceTag = str_replace($toStrip, '', $newScriptSourceTag);
		}

		global $wp_version;

		$newScriptSourceTag = str_replace('.js&#038;ver='.$wp_version, '.js', $newScriptSourceTag);
		$newScriptSourceTag = str_replace('.js&#038;ver=', '.js?ver=', $newScriptSourceTag);

		return $newScriptSourceTag;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed|void
	 */
	public static function alterHtmlSource($htmlSource)
	{
		// There has to be at least one "<script", otherwise, it could be a feed request or something similar (not page, post, homepage etc.)
		if (stripos($htmlSource, '<script') === false) {
			return $htmlSource;
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer( 'alter_html_source_for_optimize_js' ); /* [/wpacu_timing] */

		/* [wpacu_pro] */$htmlSource = apply_filters('wpacu_pro_maybe_move_jquery_after_body_tag', $htmlSource);/* [/wpacu_pro] */

		if (! Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_unload_ignore_deps_js'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			// Are there any assets unloaded where their "children" are ignored?
			// Since they weren't dequeued the WP way (to avoid unloading the "children"), they will be stripped here
			$htmlSource = self::ignoreDependencyRuleAndKeepChildrenLoaded($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */

			// Move any jQuery inline SCRIPT that is triggered before jQuery library is called through "jquery-core" handle
			if (Main::instance()->settings['move_inline_jquery_after_src_tag']) {
				/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_move_inline_jquery_after_src_tag'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
				$htmlSource = self::moveInlinejQueryAfterjQuerySrc($htmlSource);
				/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
			}
		}

		/*
		 * The JavaScript files only get cached if they are minified or are loaded like /?custom-js=version - /script.php?ver=1 etc.
		 * #optimizing
		 * STEP 2: Load optimize-able caching list and replace the original source URLs with the new cached ones
		 */

		// At least minify or cache dynamic loaded JS has to be enabled to proceed
		if (self::isWorthCheckingForOptimization()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_original_to_optimized_js'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			// 'wpacu_js_optimize_list' caching list is also checked; if it's empty, no optimization is made
			$htmlSource = self::updateHtmlSourceOriginalToOptimizedJs($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		if (! Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_preload_js'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$preloads = Preloads::instance()->getPreloads();

			if (isset($preloads['scripts']) && ! empty($preloads['scripts'])) {
				$htmlSource = Preloads::appendPreloadsForScriptsToHead($htmlSource);
			}

			$htmlSource = str_replace(Preloads::DEL_SCRIPTS_PRELOADS, '', $htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		$proceedWithCombineOnThisPage = true;

		// If "Do not combine JS on this page" is checked in "Asset CleanUp Options" side meta box
		// Works for posts, pages and custom post types
		if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0) {
			$pageOptions = MetaBoxes::getPageOptions( WPACU_CURRENT_PAGE_ID );

			// 'no_js_optimize' refers to avoid the combination of JS files
			if ( isset( $pageOptions['no_js_optimize'] ) && $pageOptions['no_js_optimize'] ) {
				$proceedWithCombineOnThisPage = false;
			}
		}

		if ($proceedWithCombineOnThisPage) {
			/* [wpacu_timing] */ // Note: Load timing is checked within the method /* [/wpacu_timing] */
			$htmlSource = CombineJs::doCombine($htmlSource);
		}

		if (self::isWorthCheckingForOptimization() && ! Main::instance()->preventAssetsSettings()) {
			/* [wpacu_timing] */ $wpacuTimingName = 'alter_html_source_for_minify_inline_script_tags'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */
			$htmlSource = self::optimizeInlineScriptTags($htmlSource);
			/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
		}

		// Final cleanups
		$htmlSource = preg_replace('#<script(\s+|)(data-wpacu-jquery-core-handle=1|data-wpacu-jquery-migrate-handle=1)#Umi', '<script ', $htmlSource);

		$htmlSource = preg_replace('#<script(\s+|)data-wpacu-script-rel-src-before=(["\'])' . '(.*)' . '(\1)#Usmi', '<script ', $htmlSource);
		$htmlSource = preg_replace('#<script(.*)data-wpacu-script-handle=\'(.*)\'#Umi', '<script \\1', $htmlSource);

		/* [wpacu_timing] */ Misc::scriptExecTimer('alter_html_source_for_optimize_js', 'end'); /* [/wpacu_timing] */

		return $htmlSource;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed|string
	 */
	public static function optimizeInlineScriptTags($htmlSource)
	{
		if (stripos($htmlSource, '<script') === false) {
			return $htmlSource; // no SCRIPT tags, hmm
		}

		$domTag = new \DOMDocument();
		libxml_use_internal_errors(true);
		$domTag->loadHTML($htmlSource);

		$scriptTagsObj = $domTag->getElementsByTagName( 'script' );

		if ($scriptTagsObj === null) {
			return $htmlSource;
		}

		$doJsMinify = MinifyJs::isMinifyJsEnabled() && Main::instance()->settings['minify_loaded_js_inline'];

		$skipTagsContaining = array_map( static function ( $toMatch ) {
			return preg_quote($toMatch, '/');
		}, array(
			'/* <![CDATA[ */', // added via wp_localize_script()
			'window._wpemojiSettings', // Emoji
			'wpacu-google-fonts-async-load',
			'wpacu-preload-async-css-fallback',
			/* [wpacu_pro] */'data-wpacu-inline-js-file',/* [/wpacu_pro] */
			'document.body.prepend(wpacuLinkTag',
			'var wc_product_block_data = JSON.parse( decodeURIComponent('
		));

		foreach ($scriptTagsObj as $scriptTagObj) {
			// Does it have the "src" attribute? Skip it as it's not an inline SCRIPT tag
			if (isset($scriptTagObj->attributes) && $scriptTagObj->attributes !== null) {
				foreach ($scriptTagObj->attributes as $attrObj) {
					if ($attrObj->nodeName === 'src') {
						continue 2;
					}

					if ($attrObj->nodeName === 'type' && $attrObj->nodeValue !== 'text/javascript') {
						// If a "type" parameter exists (otherwise it defaults to "text/javascript"
						// and the value of "type" is not "text/javascript", do not proceed with any optimization (including minification)
						continue 2;
					}
				}
			}

			$originalTag = CleanUp::getOuterHTML($scriptTagObj);

			// No need to use extra resources as the tag is already minified
			if (preg_match('/('.implode('|', $skipTagsContaining).')/', $originalTag)) {
				continue;
			}

			$originalTagContents = (isset($scriptTagObj->nodeValue) && trim($scriptTagObj->nodeValue) !== '') ? $scriptTagObj->nodeValue : false;

			if ($originalTagContents) {
				$newTagContents = self::maybeAlterJsContent($originalTagContents, $doJsMinify);

				if ($newTagContents !== $originalTagContents) {
					$htmlSource = str_ireplace(
						'>' . $originalTagContents . '</script',
						'>' . $newTagContents . '</script',
						$htmlSource
					);
				}

				libxml_clear_errors();
			}
		}

		return $htmlSource;
	}

	/**
	 * @return string
	 */
	public static function getRelPathJsCacheDir()
	{
		return OptimizeCommon::getRelPathPluginCacheDir().'js/'; // keep trailing slash at the end
	}

	/**
	 * @param $scriptSrcs
	 * @param $htmlSource
	 *
	 * @return array
	 */
	public static function getScriptTagsFromSrcs($scriptSrcs, $htmlSource)
	{
		$scriptTags = array();

		$cleanerHtmlSource = OptimizeCommon::cleanerHtmlSource($htmlSource);

		foreach ($scriptSrcs as $scriptSrc) {
			$scriptSrc = str_replace('{site_url}', '', $scriptSrc);

			preg_match_all('#<script[^>]*src(|\s+)=(|\s+)[^>]*'. preg_quote($scriptSrc, '/'). '.*(>)(.*|)</script>#Usmi', $cleanerHtmlSource, $matchesFromSrc, PREG_SET_ORDER);

			if (isset($matchesFromSrc[0][0]) && strip_tags($matchesFromSrc[0][0]) === '') {
				$scriptTags[] = trim($matchesFromSrc[0][0]);
			}
		}

		return $scriptTags;
	}

	/**
	 * @param $strFind
	 * @param $strReplaceWith
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function strReplaceOnce($strFind, $strReplaceWith, $string)
	{
		if ( strpos($string, $strFind) === false ) {
			return $string;
		}

		$occurrence = strpos($string, $strFind);
		return substr_replace($string, $strReplaceWith, $occurrence, strlen($strFind));
	}

	/**
	 * @param $jsContent
	 * @param $appendBefore
	 *
	 * @return mixed
	 */
	public static function maybeDoJsFixes($jsContent, $appendBefore)
	{
		// Relative URIs for CSS Paths
		// For code such as:
		// $(this).css("background", "url('../images/image-1.jpg')");

		$jsContentPathReps = array(
			'url("../' => 'url("'.$appendBefore.'../',
			"url('../" => "url('".$appendBefore.'../',
			'url(../'  => 'url('.$appendBefore.'../',

			'url("./'  => 'url("'.$appendBefore.'./',
			"url('./"  => "url('".$appendBefore.'./',
			'url(./'   => 'url('.$appendBefore.'./'
		);

		$jsContent = str_replace(array_keys($jsContentPathReps), array_values($jsContentPathReps), $jsContent);

		$jsContent = trim($jsContent);

		if (substr($jsContent, -1) !== ';') {
			$jsContent .= "\n" . ';'; // add semicolon as the last character
		}

		return $jsContent;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return false|mixed|string|void
	 */
	public static function moveInlinejQueryAfterjQuerySrc($htmlSource)
	{
		if (stripos($htmlSource, '<script') === false) {
			return $htmlSource; // no SCRIPT tags, hmm
		}

		$domTag = new \DOMDocument();
		libxml_use_internal_errors(true);
		$domTag->loadHTML($htmlSource);

		$scriptTagsObj = $domTag->getElementsByTagName( 'script' );

		if ($scriptTagsObj === null) {
			return $htmlSource;
		}

		// Does it have the "src" attribute? Skip it as it's not an inline SCRIPT tag
		$jQueryPatternsToMatch = array(
			'jQuery',
			'\$(\s+|)\((\s+|)document(\s+|)\)(\s+|).(\s+|)ready(\s+|)\('
		);

		$jQueryRegExp = '#' . implode('|', $jQueryPatternsToMatch) . '#si';

		$jQueryCoreDel    = 'data-wpacu-jquery-core-handle=';
		$jQueryMigrateDel = 'data-wpacu-jquery-migrate-handle=';

		if (strpos($htmlSource, $jQueryMigrateDel) !== false) {
			$collectUntil = $jQueryMigrateDel;
		} elseif (strpos($htmlSource, $jQueryCoreDel) !== false) {
			$collectUntil = $jQueryCoreDel;
		} else {
			return $htmlSource; // No jQuery or jQuery Migrate? Just return the HTML source
		}

		$inlineBeforejQuerySrc = array();

		foreach ($scriptTagsObj as $scriptTagObj) {
			$tagContents = $scriptTagObj->nodeValue;

			if (strpos(CleanUp::getOuterHTML($scriptTagObj), $collectUntil) !== false) {
				break;
			}

			if ($tagContents !== '' && preg_match($jQueryRegExp, $tagContents)) {
				preg_match('#<script[^>]*>'.preg_quote($tagContents, '/').'</script>#si', $htmlSource, $matchesExact);
				$exactMatchTag = isset($matchesExact[0]) ? $matchesExact[0] : '';

				// Replace the first match only in rare cases there are multiple SCRIPT tags with the same code
				if ($exactMatchTag && ($pos = strpos($htmlSource, $exactMatchTag)) !== false) {
					$inlineBeforejQuerySrc[] = $exactMatchTag;
					$htmlSource = substr_replace($htmlSource, '', $pos, strlen($exactMatchTag));
				}
			}
		}

		preg_match('#<script* '.$collectUntil.'*[^>]*>(.*?)</script>#si', $htmlSource, $matches);

		if (! empty($inlineBeforejQuerySrc) && $collectUntil && isset($matches[0])) {
			$htmlSource = preg_replace('#<script* '.$collectUntil.'*[^>]*>(.*?)</script>#si', $matches[0]."\n".implode("\n", $inlineBeforejQuerySrc), $htmlSource);
		}

		return $htmlSource;
	}

	/**
	 * @param string $returnType
	 * 'list' - will return the list of plugins that have JS optimization enabled
	 * 'if_enabled' - will stop when it finds the first one (any order) and return true
	 * @return array|bool
	 */
	public static function isOptimizeJsEnabledByOtherParty($returnType = 'list')
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

		$jsOptimizeEnabledIn = array();

		foreach ($pluginsToCheck as $plugin => $pluginTitle) {
			// "Autoptimize" check
			if ($plugin === 'autoptimize/autoptimize.php' && Misc::isPluginActive($plugin) && get_option('autoptimize_js')) {
				$jsOptimizeEnabledIn[] = $pluginTitle;

				if ($returnType === 'if_enabled') { return true; }
			}

			// "WP Rocket" check
			if ($plugin === 'wp-rocket/wp-rocket.php' && Misc::isPluginActive($plugin)) {
				if (function_exists('get_rocket_option')) {
					$wpRocketMinifyJs = get_rocket_option('minify_js');
					$wpRocketMinifyConcatenateJs = get_rocket_option('minify_concatenate_js');
				} else {
					$wpRocketSettings  = get_option('wp_rocket_settings');
					$wpRocketMinifyJs = isset($wpRocketSettings['minify_js']) ? $wpRocketSettings['minify_js'] : false;
					$wpRocketMinifyConcatenateJs = isset($wpRocketSettings['minify_concatenate_js']) ? $wpRocketSettings['minify_concatenate_js'] : false;
				}

				if ($wpRocketMinifyJs || $wpRocketMinifyConcatenateJs) {
					$jsOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "WP Fastest Cache" check
			if ($plugin === 'wp-fastest-cache/wpFastestCache.php' && Misc::isPluginActive($plugin)) {
				$wpfcOptionsJson = get_option('WpFastestCache');
				$wpfcOptions = @json_decode($wpfcOptionsJson, ARRAY_A);

				if (isset($wpfcOptions['wpFastestCacheMinifyJs']) || isset($wpfcOptions['wpFastestCacheCombineJs'])) {
					$jsOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "W3 Total Cache" check
			if ($plugin === 'w3-total-cache/w3-total-cache.php' && Misc::isPluginActive($plugin)) {
				$w3tcConfigMaster = Misc::getW3tcMasterConfig();
				$w3tcEnableJs = (int)trim(Misc::extractBetween($w3tcConfigMaster, '"minify.js.enable":', ','), '" ');

				if ($w3tcEnableJs === 1) {
					$jsOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "SG Optimizer" check
			if ($plugin === 'sg-cachepress/sg-cachepress.php' && Misc::isPluginActive($plugin)) {
				if (class_exists('\SiteGround_Optimizer\Options\Options') && method_exists('\SiteGround_Optimizer\Options\Options', 'is_enabled')) {
					if (@\SiteGround_Optimizer\Options\Options::is_enabled( 'siteground_optimizer_optimize_javascript')) {
						$jsOptimizeEnabledIn[] = $pluginTitle;

						if ($returnType === 'if_enabled') { return true; }
					}
				}
			}

			// "Fast Velocity Minify" check
			if ($plugin === 'fast-velocity-minify/fvm.php' && Misc::isPluginActive($plugin)) {
				// It's enough if it's active due to its configuration
				$jsOptimizeEnabledIn[] = $pluginTitle;

				if ($returnType === 'if_enabled') { return true; }
			}

			// "LiteSpeed Cache" check
			if ($plugin === 'litespeed-cache/litespeed-cache.php' && Misc::isPluginActive($plugin) && ($liteSpeedCacheConf = apply_filters('litespeed_cache_get_options', get_option('litespeed-cache-conf')))) {
				if ( (isset($liteSpeedCacheConf['js_minify']) && $liteSpeedCacheConf['js_minify'])
				     || (isset($liteSpeedCacheConf['js_combine']) && $liteSpeedCacheConf['js_combine']) ) {
					$jsOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}

			// "Swift Performance Lite" check
			if ($plugin === 'swift-performance-lite/performance.php' && Misc::isPluginActive($plugin)
			    && class_exists('Swift_Performance_Lite') && method_exists('Swift_Performance_Lite', 'check_option')) {
				if ( @\Swift_Performance_Lite::check_option('merge-scripts', 1) ) {
					$jsOptimizeEnabledIn[] = $pluginTitle;
				}

				if ($returnType === 'if_enabled') { return true; }
			}

			// "Breeze – WordPress Cache Plugin"
			if ($plugin === 'breeze/breeze.php' && Misc::isPluginActive($plugin)) {
				$breezeBasicSettings    = get_option('breeze_basic_settings');
				$breezeAdvancedSettings = get_option('breeze_advanced_settings');

				if (isset($breezeBasicSettings['breeze-minify-js'], $breezeAdvancedSettings['breeze-group-js'])
				    && $breezeBasicSettings['breeze-minify-js'] && $breezeAdvancedSettings['breeze-group-js']) {
					$jsOptimizeEnabledIn[] = $pluginTitle;

					if ($returnType === 'if_enabled') { return true; }
				}
			}
		}

		if ($returnType === 'if_enabled') { return false; }

		return $jsOptimizeEnabledIn;
	}

	/**
	 * @return bool
	 */
	public static function isWorthCheckingForOptimization()
	{
		// At least one of these options have to be enabled
		// Otherwise, we will not perform specific useless actions and save resources
		return MinifyJs::isMinifyJsEnabled() ||
		       Main::instance()->settings['cache_dynamic_loaded_js'] ||
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

		if (isset($ignoreChild['scripts']) && ! empty($ignoreChild['scripts'])) {
			foreach (array_keys($ignoreChild['scripts']) as $scriptHandle) {
				if (isset(Main::instance()->wpAllScripts['registered'][$scriptHandle]->src, Main::instance()->ignoreChildren['scripts'][$scriptHandle.'_has_unload_rule']) && ($scriptSrc = Main::instance()->wpAllScripts['registered'][$scriptHandle]->src) && Main::instance()->ignoreChildren['scripts'][$scriptHandle.'_has_unload_rule']) {
					$inlineAssociatedWithHandle = self::getInlineAssociatedWithScriptHandle($scriptHandle, Main::instance()->wpAllScripts['registered'], 'handle');

					$toReplaceTagList = '';

					if ($inlineAssociatedWithHandle['cdata']) {
						$toReplaceTagList .= $inlineAssociatedWithHandle['cdata'] . "\n";
					}

					if ($inlineAssociatedWithHandle['before']) {
						$toReplaceTagList .= $inlineAssociatedWithHandle['before'] . "\n";
					}

					$toReplaceTagList .= self::getScriptTagFromHandle(array('data-wpacu-script-handle=[\'"]' . $scriptHandle . '[\'"]'), $htmlSource);

					if ($inlineAssociatedWithHandle['after']) {
						$toReplaceTagList .= "\n" . $inlineAssociatedWithHandle['after'];
					}

					$htmlSource = str_replace($toReplaceTagList, '', $htmlSource);

					// Extra, in case the previous replace didn't go through
					$listWithMatches   = array();
					$listWithMatches[] = 'data-wpacu-script-handle=[\'"]'.$scriptHandle.'[\'"]';
					$listWithMatches[] = preg_quote(OptimizeCommon::getSourceRelPath($scriptSrc), '/');

					$htmlSource = CleanUp::cleanScriptTagFromHtmlSource($listWithMatches, $htmlSource);
				}
			}
		}

		return $htmlSource;
	}


	/**
	 * @param $scriptTagOrHandle
	 * @param $wpacuRegisteredScripts
	 * @param string $from
	 * @param bool $withOpenCloseTags
	 *
	 * @return array
	 */
	public static function getInlineAssociatedWithScriptHandle($scriptTagOrHandle, $wpacuRegisteredScripts, $from = 'tag', $withOpenCloseTags = true)
	{
		$scriptExtraCdata = $scriptExtraBefore = $scriptExtraAfter = '';

		if ($from === 'tag') {
			preg_match_all('#data-wpacu-script-handle=([\'])' . '(.*)' . '(\1)#Usmi', $scriptTagOrHandle, $outputMatches);
			$scriptHandle = (isset($outputMatches[2][0]) && $outputMatches[2][0]) ? trim($outputMatches[2][0], '"\'') : '';
		} else { // 'handle'
			$scriptHandle = $scriptTagOrHandle;
		}

		if ($scriptHandle && isset($wpacuRegisteredScripts[$scriptHandle]->extra)) {
			$scriptExtraArray = $wpacuRegisteredScripts[$scriptHandle]->extra;

			if (isset($scriptExtraArray['data']) && $scriptExtraArray['data']) {
				$scriptExtraCdata .= $withOpenCloseTags ? '<script type=\'text/javascript\'>'."\n" : '';
				$scriptExtraCdata .= '/* <![CDATA[ */'."\n";
				$scriptExtraCdata .= $scriptExtraArray['data']."\n";
				$scriptExtraCdata .= '/* ]]> */'."\n";
				$scriptExtraCdata .= $withOpenCloseTags ? '</script>'."\n" : '';
			}

			if (isset($scriptExtraArray['before']) && ! empty($scriptExtraArray['before'])) {
				$scriptExtraBefore .= $withOpenCloseTags ? "<script data-wpacu-script-handle='".$scriptHandle."' type='text/javascript'>\n" : '';

				foreach ($scriptExtraArray['before'] as $beforeData) {
					if (! is_bool($beforeData)) {
						$scriptExtraBefore .= $beforeData."\n";
					}
				}

				$scriptExtraBefore .= $withOpenCloseTags ? '</script>' : '';
			}

			if (isset($scriptExtraArray['after']) && ! empty($scriptExtraArray['after'])) {
				$scriptExtraAfter .= $withOpenCloseTags ? "<script data-wpacu-script-handle='".$scriptHandle."' type='text/javascript'>\n" : '';

				foreach ($scriptExtraArray['after'] as $afterData) {
					if (! is_bool($afterData)) {
						$scriptExtraAfter .= $afterData."\n";
					}
				}

				$scriptExtraAfter .= $withOpenCloseTags ? '</script>' : '';
			}
		}

		return array('cdata' => trim($scriptExtraCdata), 'before' => trim($scriptExtraBefore), 'after' => trim($scriptExtraAfter));
	}

	/**
	 * @param $listWithPatterns
	 * @param $htmlSource
	 *
	 * @return string
	 */
	public static function getScriptTagFromHandle($listWithPatterns, $htmlSource)
	{
		if (empty($listWithPatterns)) {
			return '';
		}

		if (! is_array($listWithPatterns)) {
			$listWithPatterns = array($listWithPatterns);
		}

		preg_match_all(
			'#<script[^>]*('.implode('|', $listWithPatterns).')[^>].*(>)#Usmi',
			$htmlSource,
			$matchesSourcesFromTags
		);

		if (empty($matchesSourcesFromTags)) {
			return '';
		}

		if (isset($matchesSourcesFromTags[0]) && ! empty($matchesSourcesFromTags[0])) {
			foreach ($matchesSourcesFromTags[0] as $matchesFromTag) {
				if (stripos($matchesFromTag, ' src=') !== false && strip_tags($matchesFromTag) === '') {
					return $matchesFromTag.'</script>';
				}
			}
		}

		return '';
	}

	}
