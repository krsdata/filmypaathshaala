<?php
add_action('emd_show_shortcodes_page','emd_show_shortcodes_page',1);
/**
 * Show shortcodes builder page
 *
 * @param string $app
 * @since WPAS 4.4
 *
 * @return html page content
 */
if (!function_exists('emd_show_shortcodes_page')) {
	function emd_show_shortcodes_page($app){
		global $title;
		add_thickbox();
		?>
		<div class="wrap">
		<h2><?php 
		$has_bulk = 0;
		if(function_exists('emd_std_media_js') || function_exists('emd_analytics_media_js')){	
			echo '<span style="padding-right:10px;">' . __('Visual ShortCode Builder','emd-plugins') . '</span>'; 
			$has_bulk = 1;
			$create_url = admin_url('admin.php?page=' . $app . '_shortcodes#TB_inline?width=640&height=750&inlineId=wpas-component');
			echo '<a href="' . $create_url . '" class="thickbox button button-primary">' . esc_html('Create New', 'emd-plugins') . '</a>';
			echo '</h2>';
			echo '<p>' . __('The following shortcodes are provided by default. To use the shortcode, click copy button and paste it in a page.','emd-plugins');
			echo ' ' . __('To create advanced shortcodes click Create New button.','emd-plugins') . '</p>';
		}
		else {
			echo '<span style="padding-right:10px;">' . __('ShortCodes','emd-plugins') . '</span></h2>'; 
			echo '<p>' . __('The following shortcodes are provided by default. To use the shortcode, click copy button and paste it in a page.','emd-plugins');
			echo ' ' . sprintf(__('To learn more on how to create new shortcodes with filters go to the %s documentation.%s','emd-plugins'),'<a href="https://docs.emdplugins.com/docs/' . str_replace('_','-',$app) . '" target="_blank">','</a>') . '</p>';
			echo '<p>' . sprintf(__('Create advanced shortcodes easily with Visual Shortcode builder.%s Upgrade Now!%s','emd-plugins'),'<a href="https://emdplugins.com/plugin_tag/' . str_replace('_','-',$app) . '" target="_blank">','</a>') . '</p>';
		}
		$list_table = new Emd_List_Table($app,'shortcode',$has_bulk);
                $list_table->prepare_items();
?>
		<div class="emd-shortcode-list-admin-content">
		<form id="emd-shortcode-list-table" method="get" action="<?php echo admin_url( 'admin.php?page=' . $app . '_shortcodes'); ?>">
		<input type="hidden" name="page" value="<?php echo $app . '_shortcodes';?>"/>
		<?php $list_table->views(); ?>
		<?php $list_table->display(); ?>
		</form>
		</div>
		<script>
		var clipboard = new ClipboardJS('.emd-copy-clipb');
		clipboard.on('success', function(e) {
			var copies = document.getElementsByClassName("emd-copy-clipb");
			for(var i = 0; i < copies.length; i++)
			{
				copies.item(i).innerHTML = 'Copy';
			}
			e.trigger.innerHTML = 'Copied!';
		});
		</script>
<?php
	}
}
add_action('emd_create_shc_with_filters', 'emd_create_shc_with_filters');

function emd_create_shc_with_filters(){
?>
app = $('#add-wpas-component').data('app');
$.ajax({
type:'GET',
url : ajaxurl,
data: {action:'emd_insert_new_shc',app:app,shc:shc},
success : function(response){
	if(!response){
		alert('<?php echo __('Error: Please try again.','emd-plugins'); ?>');
	}
	redirect_link = '<?php echo admin_url('admin.php');?>?page='+app+'_shortcodes';
	window.location.href = redirect_link;
}
});
<?php
}
add_action('wp_ajax_emd_insert_new_shc','emd_insert_new_shc');
function emd_insert_new_shc(){
	if(!empty($_GET['app']) && !empty($_GET['shc'])){
		$user_shortcodes = get_option($_GET['app'] . '_user_shortcodes',Array());
		$shc_list = get_option($_GET['app'] . '_shc_list');
		preg_match('/\[(\w*)( filter="(.+)")?\]/',stripslashes($_GET['shc']),$matches);
		if(!empty($matches[1])){
			$myshc['name'] = $matches[1];
			$myshc['type'] = '';
			if(!empty($myshc['name']) && !empty($shc_list['forms'])){
				if(array_key_exists($myshc['name'],$shc_list['forms'])){
					$myshc['type'] = __('Form','emd-plugins') . ' - ' . ucfirst($shc_list['forms'][$myshc['name']]['type']);
				}
			}
			if(empty($myshc['type']) && !empty($myshc['name']) && !empty($shc_list['shcs'])){
				if(array_key_exists($myshc['name'],$shc_list['shcs'])){
					$myshc['type'] = __('View','emd-plugins');
				}
			}
			if(empty($myshc['type']) && !empty($myshc['name']) && !empty($shc_list['integrations'])){
				if(array_key_exists($myshc['name'],$shc_list['integrations'])){
					$myshc['type'] = __('View','emd-plugins');
				}
			}
			$myshc['shortcode'] = $_GET['shc'];	
			$myshc['created'] = current_time('timestamp',0);
			$user_shortcodes[] = $myshc;
			update_option($_GET['app'] . '_user_shortcodes',$user_shortcodes);
			echo true;
		}
		else {
			echo false;
		}
	}
	else {
		echo false;
		die();
	}
}
