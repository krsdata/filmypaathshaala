<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
    exit;
}

if ($data['is_list_fetchable']) {
    ?>
    <input type="hidden" id="wpacu_ajax_fetch_assets_list_dashboard_view" value="1" />
<?php
}
?>
<div id="wpacu_meta_box_content">
    <?php
    if ($data['is_list_fetchable']) {
        if ($data['fetch_assets_on_click']) {
            ?>
            <a style="margin: 10px 0; padding: 0 26px;" href="#" class="button button-secondary button-hero" id="wpacu_ajax_fetch_on_click_btn"><span style="font-size: 24px; vertical-align: middle;" class="dashicons dashicons-download"></span>&nbsp; Fetch CSS &amp; JavaScript Management List</a>
            <?php
        }
        ?>
        <div id="wpacu_fetching_assets_list_wrap" <?php if ($data['fetch_assets_on_click']) { echo 'style="display: none;"'; } ?>>
            <img src="<?php echo admin_url('images/spinner.gif'); ?>" align="top" width="20" height="20" alt="" />&nbsp;
            <?php echo sprintf(__('Fetching the loaded scripts and styles for <strong>%s</strong> <br /><br /> Please wait... <br /><br /> In case the list does not show consider checking your internet connection and the actual page that is being fetched to see if it loads completely.', 'wp-asset-clean-up'), $data['fetch_url']); ?>
            <p style="margin-bottom: 0;"><?php echo sprintf(
                    __('If you believe fetching the page takes too long and the assets should have loaded by now, I suggest you go to "Settings", make sure "Manage in front-end" is checked and then %smanage the assets in the front-end%s.', 'wp-asset-clean-up'),
                    '<a href="'.$data['fetch_url'].'#wpacu_wrap_assets">',
                    '</a>'
                ); ?></p>
        </div>
        <?php
    } elseif ($data['status'] === 2) {
	    echo '<p>'.__('In order to manage the CSS/JS files here, you need to have "Manage in the Dashboard?" enabled within the plugin\'s settings ("Plugin Usage Preferences" tab).', 'wp-asset-clean-up').'</p>';
	    echo '<p style="margin-bottom: 0;">'.__('If you prefer to manage the assets within the front-end view and wish to hide this meta box, you can click on "Screen Options" at the top of this page and deselect "Asset CleanUp: CSS &amp; JavaScript Manager".').'</p>';
    } elseif ($data['status'] === 3) {
        _e('The styles and scripts will be available for unload once this post/page is <strong>public</strong> and <strong>publish</strong>ed as the whole page needs to be scanned for all the loaded assets.', 'wp-asset-clean-up');
        ?>
        <p class="wpacu-warning" style="margin: 15px 0 0; padding: 10px; font-size: inherit;"><span class="dashicons dashicons-image-rotate" style="-webkit-transform: rotateY(180deg); transform: rotateY(180deg);"></span> &nbsp;<?php _e('If this post/page was meanwhile published (after you saw the above notice), just reload this edit page and you should see the list of CSS/JS files loaded in the page.', 'wp-asset-clean-up'); ?></p>
    <?php
    } elseif ($data['status'] === 4) {
        ?>
            <p style="margin-bottom: 0;">
                <span class="dashicons dashicons-info"></span>
                <?php
                _e('There are no CSS/JS to manage as the permalink for this attachment redirects to the attachment itself because <em>"Redirect attachment URLs to the attachment itself?"</em> is set to <em>"Yes"</em> in <em>"Search Appearance - Yoast SEO" - "Media"</em> tab).', 'wp-asset-clean-up');

                echo ' '.sprintf(
                    __('As a result, the "%s" side meta box is not shown as it is irrelevant in this situation.', 'wp-asset-clean-up'),
                    WPACU_PLUGIN_TITLE.': '.__('Options', 'wp-asset-clean-up')
                );
                ?>
            </p>
        <?php
    }  elseif ($data['status'] === 5) {
        ?>
        <p class="wpacu_verified">
            <strong>Page URL:</strong> <a target="_blank" href="<?php echo $data['fetch_url']; ?>"><span><?php echo $data['fetch_url']; ?></span></a>
        </p>
        <?php
	    $msg =__('This page\'s URL is matched by one of the RegEx rules you have in <strong>"Settings"</strong> -&gt; <strong>"Plugin Usage Preferences"</strong> -&gt; <strong>"Do not load the plugin on certain pages"</strong>, thus Asset CleanUp is not loaded on that page and no CSS/JS are to be managed. If you wish to view the CSS/JS manager, please remove the matching RegEx rule and reload this page.', 'wp-asset-clean-up');
        ?>
	    <p class="wpacu-warning"
           style="margin: 15px 0 0; padding: 10px; font-size: inherit; width: 99%;">
            <span style="color: red;"
                  class="dashicons dashicons-info"></span> <?php echo $msg; ?>
        </p>
    <?php
    }
    ?>
</div>