<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Plugin_Name
 * @author    Your Name <email@example.com>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 */
//we don't have data at the moment.
$data = '';
?>   
<div class="wrap efbl" id="dashboard-widgets">
<h2 class="nav-tab-wrapper">
<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';?>
                 

                <a href="<?php echo admin_url('admin.php')?>?page=easy-facebook-likebox&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('Authentication', 'easy-facebook-likebox'); ?></a>

                <a href="<?php echo admin_url('admin.php')?>?page=easy-facebook-likebox&tab=auth" class="nav-tab <?php echo $active_tab == 'auth' ? 'nav-tab-active' : ''; ?>"><?php _e('How to use?', 'easy-facebook-likebox'); ?></a>

                <a href="<?php echo admin_url('admin.php')?>?page=easy-facebook-likebox&tab=autopopup" class="nav-tab <?php echo $active_tab == 'autopopup' ? 'nav-tab-active' : ''; ?>"><?php _e('Auto PopUp', 'easy-facebook-likebox'); ?></a>
               
              
                <a href="<?php echo admin_url('admin.php')?>?page=easy-facebook-likebox&tab=clear_cache" class="nav-tab <?php echo $active_tab == 'clear_cache' ? 'nav-tab-active' : ''; ?>"><?php _e('Clear Cache', 'easy-facebook-likebox'); ?></a>

                <a href="<?php echo admin_url('admin.php')?>?page=easy-facebook-likebox&tab=supportupdates" class="nav-tab <?php echo $active_tab == 'supportupdates' ? 'nav-tab-active' : ''; ?>"><?php _e('Support and Updates', 'easy-facebook-likebox'); ?></a>

              
                 
            </h2><br /><br />
            
             
            
<form method="post" action="<?php echo admin_url('options.php')?>">

  <?php if( $active_tab == 'auth' ) {?>
   
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
    
    <?php do_meta_boxes($this->plugin_screen_hook_suffix, 'normal', $data ); ?>
    </div>
    
  <?php }//End general tab?> 
 
 <?php if( $active_tab == 'general' ) {
    if(isset($_GET['access_token']))
    $access_token = $_GET['access_token'];
    if(isset($access_token ) && !empty($access_token)) update_option('efbl_access_token', $access_token);
    // echo '<pre>'; print_r(get_option('efbl_access_token', false));exit;
  ?>
   
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">

      <div id="easy-facebook-auth" class="postbox ">
<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php _e('Toggle panel: Configure', 'easy-facebook-likebox'); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle ui-sortable-handle"><span><?php _e('Configure', 'easy-facebook-likebox'); ?></span></h2>
<div class="inside">
  <p><?php _e('Log into your Facebook account using the button below and approve the plugin to connect your account.', 'easy-facebook-likebox'); ?></p>

  <?php 
  $app_ID = array('222116127877068','405460652816219','256146211143372', '406362656509338','395202813876688');
  $rand_app_ID = array_rand($app_ID, '1');
  
   $u_app_ID = $app_ID[$rand_app_ID]; 
  ?>
  <a class="efbl-auth-btn button" href="https://www.facebook.com/dialog/oauth?client_id=<?php echo $u_app_ID; ?>&redirect_uri=https://maltathemes.com/efbl/app-<?php echo $u_app_ID; ?>/index.php&scope=manage_pages&state=<?php echo admin_url('admin.php?page=easy-facebook-likebox'); ?>"><i class="dashicons dashicons-facebook-alt"></i><?php _e('Get My Access Token', 'easy-facebook-likebox'); ?></a>
 
  <table class="form-table">
    <tbody>
      <tr><th scope="row"><?php _e('Access Token', 'easy-facebook-likebox'); ?></th><td><textarea id="efbl_access_token" name="efbl_access_token" rows="5" cols="50" placeholder=""><?php echo get_option('efbl_access_token', false); ?></textarea></td>
      </tr>
    </tbody>
  </table>
<div class="button_container">
   <a class="efbl-save-access-token button button-primary" href="javascript:void(0);"><?php _e('Save Changes', 'easy-facebook-likebox'); ?></a> 
</div>

 <p><?php _e('Please note: This does not give us permission to manage your Facebook pages, it simply allows the plugin to see a list of the pages you manage and retrieve an Access Token.', 'easy-facebook-likebox'); ?></p>

      </div>
    </div>

    
    
    </div>
    
  <?php }//End general tab?> 
  
    <?php if( $active_tab == 'autopopup' ) { //Start Post Layout tab ?> 
 
 
  
  <div id="normal-sortables" class="meta-box-sortables ui-sortable">
  
   <?php do_meta_boxes($this->plugin_screen_hook_suffix, 'additional', $data); ?>
   </div>
   <div class="clearfix"></div>
   <?php } //End ?>
   
   <?php if( $active_tab == 'supportupdates' ) { //Start Post Layout tab ?> 
   <div id="normal-sortables" class="meta-box-sortables ui-sortable">
    <?php do_meta_boxes($this->plugin_screen_hook_suffix, 'side', $data);
 ?>
        
    </div>
    <?php }?>


     <?php if( $active_tab == 'clear_cache' ) { //Start Post Layout tab ?> 
   <div id="normal-sortables" class="meta-box-sortables ui-sortable">

      <?php 
       global $wpdb;
      $sql = "SELECT `option_name` AS `name`, `option_value` AS `value`
            FROM  $wpdb->options
            WHERE `option_name` LIKE '%transient_%'
            ORDER BY `option_name`";
    $results = $wpdb->get_results( $sql );
    $transients = array();
foreach ( $results as $result )
    {
        if ( 0 === strpos( $result->name, '_site_transient_' ) )
        {
            if ( 0 === strpos( $result->name, '_site_transient_timeout_') )
                $transients['site_transient_timeout'][ $result->name ] = $result->value;
            else
                $transients['site_transient'][ $result->name ] = maybe_unserialize( $result->value );
        }
        else
        {
            if ( 0 === strpos( $result->name, '_transient_timeout_') )
                $transients['transient_timeout'][ $result->name ] = $result->value;
            else
                $transients['transient'][ $result->name ] = maybe_unserialize( $result->value );
        }
    }
    // print '<pre>$transients = ' . esc_html( var_export( $transients, TRUE ) ) . '</pre>';
    $efbl_trans = array();
    if($transients['transient'])
      foreach ($transients['transient'] as $key => $value) {
        if (strpos($key, 'efbl') !== false) $efbl_trans[$key] =  $value;
      }
       // echo "<pre>"; print_r($efbl_trans);exit();
       ?> 

      
<div id="easy-facebook-likebox_popup" class="postbox ">
<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: <?php echo __('Cached Pages', 'easy-facebook-likebox'); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle ui-sortable-handle"><span><?php echo __('Cached Pages', 'easy-facebook-likebox'); ?></span></h2>
<div class="inside">
<table class="form-table">
  <tbody>
    <?php if($efbl_trans)
              foreach ($efbl_trans as $key => $value):
               $pieces = explode('_', $key);
               $page_name = array_pop($pieces); ?>
      <tr class="<?php echo $key; ?>">
        <th scope="row"> <?php echo $page_name; ?></th>
      <td><a href="javascript:void(0);" data-efbl_trans="<?php echo $key; ?>" class="button button-primary efbl_del_trans"><?php echo __('Delete', 'easy-facebook-likebox'); ?></a></td>
    </tr>

  <?php endforeach; ?>

    </tbody></table>

      </div>
    </div>
    <?php }?>
   
</form>  
</div>

<div class="mt-other-plugins-holder">
  <div id="mt-other-plugins" class="">
             <!-- Our Plugins  HTML-->
             <iframe src="https://maltathemes.com/our-plugins/" height="400" width="680"  style="border:0px;float:left;" id="mt-our-plugins" name="Our Plugins"></iframe>
      </div>
 </div> 

<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready( function($) {
      // close postboxes that should be closed
      $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
      // postboxes setup
      postboxes.add_postbox_toggles('<?php echo $this->plugin_screen_hook_suffix; ?>');
    });
    //]]>
  </script>
    
<style type="text/css">
#dashboard_right_now li{
  width:100%;
}
.hndle{
  padding: 10px;
  margin:0px;
}
</style>