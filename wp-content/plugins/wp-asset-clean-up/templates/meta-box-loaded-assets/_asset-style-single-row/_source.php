<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-style-single-row.php
*/

if ( ! isset($data, $ver, $styleHandleHasSrc, $showGoogleFontRemoveNotice) ) {
	exit; // no direct access
}

// If there is a source (in rare cases there are handles such as "woocommerce-inline" that do not have a source)
if (isset($data['row']['obj']->src, $data['row']['obj']->srcHref) && $data['row']['obj']->src && $data['row']['obj']->srcHref) {
	$styleHandleHasSrc = $isExternalSrc = true; // default

	if (\WpAssetCleanUp\Misc::getLocalSrc($data['row']['obj']->src)
	    || strpos($data['row']['obj']->src, '/?') !== false // Dynamic Local URL
	    || strpos(str_replace(site_url(), '', $data['row']['obj']->src), '?') === 0 // Starts with ? right after the site url (it's a local URL)
	) {
		$isExternalSrc = false;
	}

	$isGoogleFontLink = stripos($data['row']['obj']->srcHref, '//fonts.googleapis.com/') !== false;

	// Formatting for Google Fonts
	if ($isGoogleFontLink) {
		$data['row']['obj']->src     = urldecode(\WpAssetCleanUp\OptimiseAssets\FontsGoogle::alterGoogleFontLink($data['row']['obj']->src));
		$data['row']['obj']->srcHref = urldecode(\WpAssetCleanUp\OptimiseAssets\FontsGoogle::alterGoogleFontLink($data['row']['obj']->srcHref));
	}

	$data['row']['obj']->src = str_replace(' ', '+', $data['row']['obj']->src);
	$data['row']['obj']->srcHref = str_replace(' ', '+', $data['row']['obj']->srcHref);

	$relSrc = str_replace(site_url(), '', $data['row']['obj']->src);

	if (isset($data['row']['obj']->baseUrl)) {
		$relSrc = str_replace($data['row']['obj']->baseUrl, '/', $data['row']['obj']->src);
	}

	// "font-display" CSS Property for Google Fonts - underline the URL parameter
	$toUnderline = 'display='.$data['plugin_settings']['google_fonts_display'];
	$relSrc = str_replace($toUnderline, '<u style="background: #f2faf2;">'.$toUnderline.'</u>', $relSrc);

	if ($isExternalSrc) {
		$verToAppend = ''; // no need for any "ver"
	} else {
		$appendAfterSrcHref = ( strpos( $data['row']['obj']->srcHref, '?' ) === false ) ? '?' : '&';

		if ( isset( $data['row']['obj']->ver ) && $data['row']['obj']->ver ) {
			$verToAppend = $appendAfterSrcHref .
			               (is_array( $data['row']['obj']->ver )
				               ? http_build_query( array( 'ver' => $data['row']['obj']->ver ) )
				               : 'ver=' . $ver);
		} else {
			global $wp_version;
			$verToAppend = $appendAfterSrcHref . 'ver=' . $wp_version;
		}
	}

	if ( $isGoogleFontLink && $data['plugin_settings']['google_fonts_remove'] ) {
		$showGoogleFontRemoveNotice = '<span style="color:#c00;">This resource is not loaded as "Remove Google Fonts" is enabled in "Settings" -&gt; "Google Fonts".</span>';
	}

	$isCssPreload = (isset($data['preloads']['styles'][$data['row']['obj']->handle]) && $data['preloads']['styles'][$data['row']['obj']->handle])
		? $data['preloads']['styles'][$data['row']['obj']->handle]
		: false;

	if ($isCssPreload) {
		$data['row']['obj']->preload_status = 'preloaded';
	}

	if ($showGoogleFontRemoveNotice) {
		echo $showGoogleFontRemoveNotice;
	}
	?>
	<div class="wpacu-source-row" style="margin-top: 12px;">
		<?php _e('Source:', 'wp-asset-clean-up'); ?> <a <?php if ($isExternalSrc) { ?>data-wpacu-external-source="<?php echo $data['row']['obj']->srcHref . $verToAppend; ?>" <?php } ?> target="_blank" style="color: green;" href="<?php echo $data['row']['obj']->srcHref . $verToAppend; ?>"><?php echo $relSrc; ?></a> <?php if ($isExternalSrc) { ?><span data-wpacu-external-source-status></span><?php } ?>
		&nbsp;&#10230;&nbsp;
		Preload (if kept loaded)?
		&nbsp;<select style="display: inline-block; width: auto;"
		              name="wpacu_preloads[styles][<?php echo $data['row']['obj']->handle; ?>]">
			<option value="">No (default)</option>
			<option <?php if ($isCssPreload) { ?>selected="selected"<?php } ?> value="basic">Yes, basic</option>
			<option disabled="disabled" value="async">Yes, async (Pro)</option>
		</select>
		<small>* applies site-wide</small> <small><a style="text-decoration: none; color: inherit;" target="_blank" href="https://assetcleanup.com/docs/?p=202"><span class="dashicons dashicons-editor-help"></span></a></small>
	</div>
	<?php
} else {
	?>
    <input type="hidden" name="wpacu_preloads[styles][<?php echo $data['row']['obj']->handle; ?>]" value="" />
	<?php
}
