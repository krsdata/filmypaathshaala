<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-style-single-row.php
*/

if ( ! isset($data, $isCoreFile, $hideCoreFiles, $childHandles) ) {
	exit; // no direct access
}
?>
<div class="wpacu_handle" style="margin: 0 0 -8px;">
	<label for="style_<?php echo $data['row']['obj']->handle; ?>"><?php _e('Handle:', 'wp-asset-clean-up'); ?> <strong><span style="color: green;"><?php echo $data['row']['obj']->handle; ?></span></strong></label>
	&nbsp;<em>* Stylesheet (.css)</em>
	<?php if ($isCoreFile && ! $hideCoreFiles) { ?>
		<span class="dashicons dashicons-wordpress-alt wordpress-core-file"><span class="wpacu-tooltip">WordPress Core File<br /><?php _e('Not sure if needed or not? In this case, it\'s better to leave it loaded to avoid breaking the website.', 'wp-asset-clean-up'); ?></span></span>
		<?php
	}

	// Any conditions set such as "IE" or "lt IE 8"?
	$dataRowExtra = (array)$data['row']['obj']->extra;
	if (isset($dataRowExtra['conditional']) && $dataRowExtra['conditional']) {
		// Notify the user the assets load only on Internet Explorer
		if (strpos($dataRowExtra['conditional'], 'IE') !== false) {
			echo '&nbsp;&nbsp;<span><img style="vertical-align: middle;" width="25" height="25" src="'.WPACU_PLUGIN_URL.'/assets/icons/icon-ie.svg" alt="" title="Microsoft / Public domain" />&nbsp;<span style="font-weight: 400; color: #1C87CF;">Loads only in Internet Explorer based on the following condition:</span> <em> if '.$dataRowExtra['conditional'].'</em></span>';
		}
	}
	?>
</div>
<?php
if (! empty($childHandles)) {
	$ignoreChild = (isset($data['ignore_child']['styles'][$data['row']['obj']->handle]) && $data['ignore_child']['styles'][$data['row']['obj']->handle]);
	?>
	<p>
		<em style="font-size: 85%;">
			<span style="color: #0073aa; width: 19px; height: 19px; vertical-align: middle;" class="dashicons dashicons-info"></span>
			This file has other CSS "children" files depending on it. By unloading this CSS, the following "children" files will be unloaded too:
			<span style="color: green; font-weight: 600;">
                        <?php echo implode(', ', $childHandles); ?>
                    </span>
		</em>
		<label for="style_<?php echo $data['row']['obj']->handle; ?>_ignore_children">
			<input type="hidden" name="wpacu_ignore_child[styles][<?php echo $data['row']['obj']->handle; ?>]" value="" />
			&#10230; <input id="style_<?php echo $data['row']['obj']->handle; ?>_ignore_children"
			                type="checkbox"
			                <?php if ($ignoreChild) { ?>checked="checked"<?php } ?>
			                name="wpacu_ignore_child[styles][<?php echo $data['row']['obj']->handle; ?>]"
			                value="1" /> <small><?php _e('Ignore dependency rule and keep the "children" loaded', 'wp-asset-clean-up'); ?></small>
		</label>
	</p>
	<?php
}
