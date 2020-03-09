<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-script-single-row.php
*/

if ( ! isset($data, $ver) ) {
	exit; // no direct access
}

if (isset($data['row']['obj']->src, $data['row']['obj']->srcHref) && $data['row']['obj']->src !== '' && $data['row']['obj']->srcHref) {
	$isExternalSrc = true;

	if (\WpAssetCleanUp\Misc::getLocalSrc($data['row']['obj']->src)
	    || strpos($data['row']['obj']->src, '/?') !== false // Dynamic Local URL
	    || strpos(str_replace(site_url(), '', $data['row']['obj']->src), '?') === 0 // Starts with ? right after the site url (it's a local URL)
	) {
		$isExternalSrc = false;
	}

	$relSrc = str_replace(site_url(), '', $data['row']['obj']->src);

	if (isset($data['row']['obj']->baseUrl)) {
		$relSrc = str_replace($data['row']['obj']->baseUrl, '/', $relSrc);
	}

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

	$isJsPreload = (isset($data['preloads']['scripts'][$data['row']['obj']->handle]) && $data['preloads']['scripts'][$data['row']['obj']->handle])
		? $data['preloads']['scripts'][$data['row']['obj']->handle]
		: false;

	if ($isJsPreload) {
		$data['row']['obj']->preload_status = 'preloaded';
	}
	?>
	<div class="wpacu-source-row" style="margin-top: 12px;">
		<?php _e('Source:', 'wp-asset-clean-up'); ?> <a target="_blank" style="color: green;" <?php if ($isExternalSrc) { ?> data-wpacu-external-source="<?php echo $data['row']['obj']->srcHref . $verToAppend; ?>" <?php } ?> href="<?php echo $data['row']['obj']->srcHref . $verToAppend; ?>"><?php echo $relSrc; ?></a> <?php if ($isExternalSrc) { ?><span data-wpacu-external-source-status></span><?php } ?>
		&nbsp;&#10230;&nbsp;
		Preload (if kept loaded)?
		&nbsp;<select style="display: inline-block; width: auto;"
		              name="wpacu_preloads[scripts][<?php echo $data['row']['obj']->handle; ?>]">
			<option value="">No (default)</option>
			<option <?php if ($isJsPreload) { ?>selected="selected"<?php } ?> value="basic">Yes, basic</option>
		</select>
		<small>* applies site-wide</small> <small><a style="text-decoration: none; color: inherit;" target="_blank" href="https://assetcleanup.com/docs/?p=202"><span class="dashicons dashicons-editor-help"></span></a></small>
	</div>
	<?php
} else {
    ?>
    <input type="hidden" name="wpacu_preloads[scripts][<?php echo $data['row']['obj']->handle; ?>]" value="" />
    <?php
}
