<?php
/*
 * No direct access to this file
 */

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;

if (! isset($data)) {
    exit;
}

global $wp_version;

$tabIdArea = 'wpacu-setting-cdn-rewrite-urls';
$styleTabContent = ($selectedTabArea === $tabIdArea) ? 'style="display: table-cell;"' : '';
?>
<div id="<?php echo $tabIdArea; ?>" class="wpacu-settings-tab-content" <?php echo $styleTabContent; ?>>
    <h2 class="wpacu-settings-area-title"><?php _e('Rewrite cached static assets URLs with the CDN ones if necessary', 'wp-asset-clean-up'); ?></h2>

    <table class="wpacu-form-table">
        <tr valign="top">
            <th scope="row" class="setting_title">
                <label for="wpacu_cdn_rewrite_enable"><?php _e('Enable CDN URL rewrite?', 'wp-asset-clean-up'); ?></label>
                <p class="wpacu_subtitle"><small><em><?php echo sprintf(__('This applies to files saved in %s', 'wp-asset-clean-up'), '<code style="font-size: inherit;">'.str_replace(ABSPATH, '', WP_CONTENT_DIR . OptimizeCommon::getRelPathPluginCacheDir().'</code>')); ?></em></small></p>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_cdn_rewrite_enable"
                           data-target-opacity="wpacu_cdn_rewrite_enable_area"
                           type="checkbox"
                           <?php
                           echo (($data['cdn_rewrite_enable'] == 1) ? 'checked="checked"' : '');
                           ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[cdn_rewrite_enable]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>

                &nbsp;If you're using a CDN enabled through your hosting company or by another party plugin, the external URL is not always recognisable by <?php echo WPACU_PLUGIN_TITLE; ?> and it's considered an external URL unconnected to your website's CSS/JS files. To fix, this, please put the CDN's CNAME/URL in the inputs below to make sure the files are detected as local files and optimized accordingly.

                <?php
				$cdnRewriteAreaStyle = ($data['cdn_rewrite_enable'] == 1) ? 'opacity: 1;' : 'opacity: 0.4;';
				?>
                <div id="wpacu_cdn_rewrite_enable_area" style="<?php echo $cdnRewriteAreaStyle; ?>">
                    <div id="wpacu_minify_css_exceptions_area">
                        <div style="margin-top: 20px; margin-bottom: 0;"></div>
                        <table>
                            <tr>
                                <td style="vertical-align: top;" valign="top">For Stylesheet (.css) Files:&nbsp;&nbsp;</td>
                                <td style="padding-bottom: 10px;">
                                    <label for="wpacu_cdn_rewrite_url_css"><input id="wpacu_cdn_rewrite_url_css"
                                            name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[cdn_rewrite_url_css]"
                                            value="<?php echo $data['cdn_rewrite_url_css']; ?>"
                                            style="width: 300px;" /><br />
                                    </label>

                                    <ul style="font-style: italic; line-height: 13px; font-size: 12px; margin-top: 5px; margin-bottom: 0;">
                                        <li>e.g. //css-zone-name.kxcdn.com</li>
                                        <li>zone-name.kxcdn.com etc.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top;" valign="top">For JavaScript (.js) Files:&nbsp;&nbsp;</td>
                                <td style="padding-bottom: 3px;"><label for="wpacu_cdn_rewrite_url_js">
                                        <input id="wpacu_cdn_rewrite_url_js"
                                               name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[cdn_rewrite_url_js]"
                                               value="<?php echo $data['cdn_rewrite_url_js']; ?>"
                                               style="width: 300px;" /><br />
                                    </label>
                                    <ul style="font-style: italic; line-height: 13px; font-size: 12px; margin-top: 5px;">
                                        <li>e.g. //js-zone-name.kxcdn.com</li>
                                        <li>zone-name.kxcdn.com etc.</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <hr />
                    <p style="margin-top: 10px;"><strong>Note:</strong> Most of the time the CNAME / CDN URL is the same for both CSS &amp; JS files. You can use the same value in both fields.</p>

                    <p class="wpacu-warning" style="font-size: inherit;">
                        <span class="dashicons dashicons-warning"></span> If you're unsure if the <strong>C</strong>ontent <strong>D</strong>elivery <strong>N</strong>etwork's CNAME/URL is the right one, please enable "Test Mode" to test it out, thus making sure the layout won't be broken for your website visitors.
                    </p>
                </div>
			</td>
		</tr>
	</table>
</div>
