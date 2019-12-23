<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-script-single-row.php
*/

if (! isset($data, $inlineCodeStatus)) {
	exit; // no direct access
}

if ($data['row']['extra_data_js']) { ?>
	<div class="wpacu-assets-inline-code-wrap">
		<?php _e('Inline JavaScript code associated with the handle:', 'wp-asset-clean-up'); ?>
		<a class="wpacu-assets-inline-code-collapsible"
			<?php if ($inlineCodeStatus !== 'contracted') { echo 'wpacu-assets-inline-code-collapsible-active'; } ?>
           href="#"><?php _e('Show', 'wp-asset-clean-up'); ?> / <?php _e('Hide', 'wp-asset-clean-up'); ?></a>
		<div class="wpacu-assets-inline-code-collapsible-content <?php if ($inlineCodeStatus !== 'contracted') { echo 'wpacu-open'; } ?>">
			<div>
				<p style="margin-top: -7px !important; line-height: normal !important;">
					<em><?php echo strip_tags($data['row']['extra_data_js']); ?></em>
				</p>
			</div>
		</div>
	</div>
	<?php
}
