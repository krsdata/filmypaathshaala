<?php
/*
 * The file is included from _asset-script-rows.php
*/
if (! isset($data)) {
	exit; // no direct access
}

$inlineCodeStatus = $data['plugin_settings']['assets_list_inline_code_status'];
$isCoreFile       = isset($data['row']['obj']->wp) && $data['row']['obj']->wp;
$hideCoreFiles    = $data['plugin_settings']['hide_core_files'];
$isGroupUnloaded  = $data['row']['is_group_unloaded'] || $data['row']['is_post_type_unloaded'];

// Does it have "children"? - other JS file(s) depending on it
$childHandles     = isset($data['all_deps']['scripts'][$data['row']['obj']->handle]) ? $data['all_deps']['scripts'][$data['row']['obj']->handle] : array();
sort($childHandles);

$jqueryIconHtmlHandle  = '<img src="'.WPACU_PLUGIN_URL.'/assets/icons/handles/icon-jquery.png" style="max-width: 22px; max-height: 22px; margin-bottom: 0;" width="18" height="18" title="" alt="" />';
$jqueryIconHtmlDepends = '<img src="'.WPACU_PLUGIN_URL.'/assets/icons/handles/icon-jquery.png" style="max-width: 22px; max-height: 22px; vertical-align: text-top; margin-bottom: 0;" width="16" height="16" alt="" />';

// Unloaded site-wide
if ($data['row']['global_unloaded']) {
	$data['row']['class'] .= ' wpacu_is_global_unloaded';
}

// Unloaded site-wide OR on all posts, pages etc.
if ($isGroupUnloaded) {
	$data['row']['class'] .= ' wpacu_is_bulk_unloaded';
}
?>
<tr data-script-handle-row="<?php echo $data['row']['obj']->handle; ?>"
    class="wpacu_asset_row <?php echo $data['row']['class']; ?>"
    style="<?php if ($isCoreFile && $hideCoreFiles) { echo 'display: none;'; } ?>">
	<td valign="top">
	    <?php
        include '_asset-script-single-row/_handle.php';

	    $ver = $data['wp_version']; // default
	    if (isset($data['row']['obj']->ver) && $data['row']['obj']->ver) {
		    $ver = is_array($data['row']['obj']->ver) ? implode(', ', $data['row']['obj']->ver) : $data['row']['obj']->ver;
	    }

	    $data['row']['obj']->preload_status = 'not_preloaded'; // default

        // Source, Preload area
        include '_asset-script-single-row/_source.php';

	    // Any tips?
	    if (isset($data['tips']['js'][$data['row']['obj']->handle]) && ($assetTip = $data['tips']['js'][$data['row']['obj']->handle])) {
            ?>
            <div class="tip"><strong>Tip:</strong> <?php echo $assetTip; ?></div>
		    <?php
	    }

        $extraInfo = array();

	    include '_asset-script-single-row/_handle_deps.php';

        $extraInfo[] = __('Version:', 'wp-asset-clean-up').' '.$ver;

        include '_asset-script-single-row/_position.php';

        // [wpacu_lite]
        if (isset($data['row']['obj']->src) && $data['row']['obj']->src) {
	        $extraInfo[] = __('File Size:', 'wp-asset-clean-up') . ' <a class="go-pro-link-no-style" href="' . WPACU_PLUGIN_GO_PRO_URL . '?utm_source=manage_asset&utm_medium=file_size"><span class="wpacu-tooltip">Upgrade to Pro and unlock all features</span><img width="20" height="20" src="' . WPACU_PLUGIN_URL . '/assets/icons/icon-lock.svg" valign="top" alt="" /> Pro Version</a>';
        }
        // [/wpacu_lite]

        if (! empty($extraInfo)) {
	        echo '<div style="margin: 0 0 10px;">'.implode(' &nbsp;/&nbsp; ', $extraInfo).'</div>';
        }
        ?>

        <div class="wrap_bulk_unload_options">
            <?php
            // Unload on this page
            include '_asset-script-single-row/_unload-per-page.php';

            // Unload site-wide (everywhere)
            include '_asset-script-single-row/_unload-site-wide.php';

            // Unload on all pages of [post] post type (if applicable)
            include '_asset-script-single-row/_unload-post-type.php';

            // Unload via RegEx (if site-wide is not already chosen)
            include '_asset-script-single-row/_unload-via-regex.php';

            // If any bulk unload rule is set, show the load exceptions
            include '_asset-script-single-row/_load-exceptions.php';
		    ?>
            <div class="wpacu-clearfix"></div>
        </div>

        <?php
        // Extra inline associated with the SCRIPT tag
        include '_asset-script-single-row/_extra_inline.php';

        // Async, Defer (only in Pro)
        include '_asset-script-single-row/_attrs.php';

        // Handle Note
        include '_asset-script-single-row/_notes.php';
        ?>
        <img style="display: none;" class="wpacu-ajax-loader" src="<?php echo WPACU_PLUGIN_URL; ?>/assets/icons/icon-ajax-loading-spinner.svg" alt="" />
	</td>
</tr>