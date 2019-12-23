<?php
/*
 * Plugin Name: video carousel slider with lightbox
 * Plugin URI:http://www.i13websolution.com/wordpress-responsive-video-gallery-with-lightbox-pro.html 
 * Author URI:http://www.i13websolution.com/wordpress-responsive-video-gallery-with-lightbox-pro.html 
 * Description:This is beautiful responsive carousel slider with responsive lightbox.Add any number of video from admin panel. 
 * Author:I Thirteen Web Solution 
 * Version:1.0.14
 * Text Domain:wp-responsive-video-gallery-with-lightbox
 * Domain Path: /languages
 */
//error_reporting ( 0 );
add_filter ( 'widget_text', 'do_shortcode' );
add_action ( 'admin_menu', 'responsive_video_gallery_plus_lightbox_add_admin_menu' );
//add_action ( 'admin_init', 'responsive_video_gallery_plus_lightbox_add_admin_init' );
register_activation_hook ( __FILE__, 'install_responsive_video_gallery_plus_lightbox' );
register_deactivation_hook(__FILE__,'rvg_responsive_video_gallery_remove_access_capabilities');
add_action ( 'wp_enqueue_scripts', 'responsive_video_gallery_plus_lightbox_load_styles_and_js' );
add_shortcode ( 'print_responsive_video_gallery_plus_lightbox', 'print_responsive_video_gallery_plus_lightbox_func' );
add_action ( 'admin_notices', 'responsive_video_gallery_plus_lightbox_admin_notices' );

add_action( 'wp_ajax_check_file_exist', 'check_file_exist_callback' );
add_action( 'wp_ajax_get_youtube_info', 'get_youtube_info_callback' );
add_action('plugins_loaded', 'wrvgwl_load_lang_for_responsive_video_gallery_plus_lightbox');
add_filter( 'user_has_cap', 'rvg_responsive_video_gallery_admin_cap_list' , 10, 4 );

function wrvgwl_load_lang_for_responsive_video_gallery_plus_lightbox() {
            
            load_plugin_textdomain( 'wp-responsive-video-gallery-with-lightbox', false, basename( dirname( __FILE__ ) ) . '/languages/' );
            add_filter( 'map_meta_cap',  'map_rvg_responsive_video_gallery_meta_caps', 10, 4 );
    }

    
function map_rvg_responsive_video_gallery_meta_caps( array $caps, $cap, $user_id, array $args  ) {
        
       
        if ( ! in_array( $cap, array(
                                      'rvg_responsive_video_gallery_settings',
                                      'rvg_responsive_video_gallery_view_video',
                                      'rvg_responsive_video_gallery_add_video',
                                      'rvg_responsive_video_gallery_edit_video',
                                      'rvg_responsive_video_gallery_delete_video',
                                      'rvg_responsive_video_gallery_preview',
                                      
                                    ), true ) ) {
            
			return $caps;
         }

       
         
   
        $caps = array();

        switch ( $cap ) {
            
                 case 'rvg_responsive_video_gallery_settings':
                        $caps[] = 'rvg_responsive_video_gallery_settings';
                        break;
              
                case 'rvg_responsive_video_gallery_view_video':
                        $caps[] = 'rvg_responsive_video_gallery_view_video';
                        break;
              
                case 'rvg_responsive_video_gallery_add_video':
                        $caps[] = 'rvg_responsive_video_gallery_add_video';
                        break;
              
                case 'rvg_responsive_video_gallery_edit_video':
                        $caps[] = 'rvg_responsive_video_gallery_edit_video';
                        break;
              
                case 'rvg_responsive_video_gallery_delete_video':
                        $caps[] = 'rvg_responsive_video_gallery_delete_video';
                        break;
                    
                case 'rvg_responsive_video_gallery_preview':
                        $caps[] = 'rvg_responsive_video_gallery_preview';
                        break;
                  
              
                default:
                        
                        $caps[] = 'do_not_allow';
                        break;
        }

      
     return apply_filters( 'rvg_responsive_video_gallery_meta_caps', $caps, $cap, $user_id, $args );
}


 function rvg_responsive_video_gallery_admin_cap_list($allcaps, $caps, $args, $user){
        
        
        if ( ! in_array( 'administrator', $user->roles ) ) {
            
            return $allcaps;
        }
        else{
            
            if(!isset($allcaps['rvg_responsive_video_gallery_settings'])){
                
                $allcaps['rvg_responsive_video_gallery_settings']=true;
            }
            
            if(!isset($allcaps['rvg_responsive_video_gallery_view_video'])){
                
                $allcaps['rvg_responsive_video_gallery_view_video']=true;
            }
            
            if(!isset($allcaps['rvg_responsive_video_gallery_add_video'])){
                
                $allcaps['rvg_responsive_video_gallery_add_video']=true;
            }
            if(!isset($allcaps['rvg_responsive_video_gallery_edit_video'])){
                
                $allcaps['rvg_responsive_video_gallery_edit_video']=true;
            }
            if(!isset($allcaps['rvg_responsive_video_gallery_delete_video'])){
                
                $allcaps['rvg_responsive_video_gallery_delete_video']=true;
            }
            if(!isset($allcaps['rvg_responsive_video_gallery_preview'])){
                
                $allcaps['rvg_responsive_video_gallery_preview']=true;
            }
         
        }
        
        return $allcaps;
        
    }

function  rvg_responsive_video_gallery_add_access_capabilities() {
     
    // Capabilities for all roles.
    $roles = array( 'administrator' );
    foreach ( $roles as $role ) {
        
            $role = get_role( $role );
            if ( empty( $role ) ) {
                    continue;
            }
         
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_settings' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_settings' );
            }
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_view_video' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_view_video' );
            }
         
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_add_video' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_add_video' );
            }
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_edit_video' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_edit_video' );
            }
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_delete_video' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_delete_video' );
            }
            
            if(!$role->has_cap( 'rvg_responsive_video_gallery_preview' ) ){
            
                    $role->add_cap( 'rvg_responsive_video_gallery_preview' );
            }
            
         
    }
    
    $user = wp_get_current_user();
    $user->get_role_caps();
    
}

function rvg_responsive_video_gallery_remove_access_capabilities(){
    
    global $wp_roles;

    if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
    }

    foreach ( $wp_roles->roles as $role => $details ) {
            $role = $wp_roles->get_role( $role );
            if ( empty( $role ) ) {
                    continue;
            }

            $role->remove_cap( 'rvg_responsive_video_gallery_settings' );
            $role->remove_cap( 'rvg_responsive_video_gallery_view_video' );
            $role->remove_cap( 'rvg_responsive_video_gallery_add_video' );
            $role->remove_cap( 'rvg_responsive_video_gallery_edit_video' );
            $role->remove_cap( 'rvg_responsive_video_gallery_delete_video' );
            $role->remove_cap( 'rvg_responsive_video_gallery_preview' );
       

    }

    // Refresh current set of capabilities of the user, to be able to directly use the new caps.
    $user = wp_get_current_user();
    $user->get_role_caps();
    
}

function vgallery_save_image_curl($url,$saveto){
    
    $raw = wp_remote_retrieve_body( wp_remote_get( $url ) );
    
    if(file_exists($saveto)){
        @unlink($saveto);
    }
    $fp = @fopen($saveto,'x');
    @fwrite($fp, $raw);
    @fclose($fp);
    
}

function get_youtube_info_callback(){
  
    if(isset($_POST) and is_array($_POST) and  isset($_POST['url'])){
        
        
               $retrieved_nonce = '';

                if (isset($_POST['vNonce']) and $_POST['vNonce'] != '') {

                    $retrieved_nonce = $_POST['vNonce'];
                }
                if (!wp_verify_nonce($retrieved_nonce, 'vNonce')) {


                    wp_die('Security check fail');
                }

                $vid=htmlentities(strip_tags($_POST['vid']),ENT_QUOTES);
                $url=esc_url_raw($_POST['url']); 
                $output=  wp_remote_retrieve_body( wp_remote_get( $url ) ); 

                $output=json_decode($output);
                
                
                $videoInfo=  file_get_contents("https://www.youtube.com/watch?v=$vid");

                $doc = new DomDocument;

                $doc->validateOnParse = false;
                
                @$doc->loadHTML($videoInfo);

                $node= $doc->getElementById('watch-description-text');
                
                $description='';
                if($node!=null and $node!=false){
                
                  $description = $node->ownerDocument->saveHTML( $node );

                  $description=  strip_tags($description,'<br>');
                  
                  $breaks = array("<br />","<br>","<br/>");  
                  $description = str_ireplace($breaks, "\r\n", $description);  
                }
 
                $return=array();
                if(is_object($output)){
                   
                 $return['title']=$output->title;
                 $return['thumbnail_url']=$output->thumbnail_url;
                 $return['description']=$description;
                 
               }
                
          echo json_encode($return);
          exit;
        
    }
    
}

function check_file_exist_callback() {
	
	if(isset($_POST) and is_array($_POST) and  isset($_POST['url'])){

                 $retrieved_nonce = '';

                if (isset($_POST['vNonce']) and $_POST['vNonce'] != '') {

                    $retrieved_nonce = $_POST['vNonce'];
                }
                if (!wp_verify_nonce($retrieved_nonce, 'vNonce')) {


                    wp_die('Security check fail');
                }

                 $response = wp_remote_get(sanitize_text_field($_POST['url']));
                $httpCode = wp_remote_retrieve_response_code( $response );
		
		echo trim((string)$httpCode);die;
                
		
	}
	//echo die;
	
}


function responsive_video_gallery_plus_lightbox_admin_notices() {
	if (is_plugin_active ( 'wp-responsive-video-gallery-with-lightbox/wp-responsive-video-gallery-with-lightbox.php' )) {
		
		$uploads = wp_upload_dir ();
		$baseDir = $uploads ['basedir'];
		$baseDir = str_replace ( "\\", "/", $baseDir );
		$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
		
		if (file_exists ( $pathToImagesFolder ) and is_dir ( $pathToImagesFolder )) {
			
			if (! is_writable ( $pathToImagesFolder )) {
                            
                                 echo "<div class='updated'><p>".__( 'Video Carousel Slider is active but does not have write permission on','wp-responsive-video-gallery-with-lightbox')."</p><p><b>" . $pathToImagesFolder . "</b>".__( ' directory.Please allow write permission.','wp-responsive-video-gallery-with-lightbox')."</p></div> ";
				
			}
		} else {
			
			wp_mkdir_p ( $pathToImagesFolder );
			if (! file_exists ( $pathToImagesFolder ) and ! is_dir ( $pathToImagesFolder )) {
                            
                                echo "<div class='updated'><p>".__( 'Video Carousel Slider is active but plugin does not have permission to create directory','wp-responsive-video-gallery-with-lightbox')."</p><p><b>" . $pathToImagesFolder . "</b>".__( ' Please create wp-responsive-video-gallery-with-lightbox directory inside upload directory and allow write permission.','wp-responsive-video-gallery-with-lightbox')."</p></div> ";
				
			}
		}
	}
}
function responsive_video_gallery_plus_lightbox_load_styles_and_js() {
	if (! is_admin ()) {
		
		wp_register_style ( 'wp-video-gallery-lighbox-style', plugins_url ( '/css/wp-video-gallery-lighbox-style.css', __FILE__ ) );
		wp_register_style ( 'vl-box-css', plugins_url ( '/css/vl-box-css.css', __FILE__ ) );
		wp_register_script ( 'video-gallery-jc', plugins_url ( '/js/video-gallery-jc.js', __FILE__ ),array('jquery'),'1.0.14' );
		wp_register_script ( 'vl-box-js', plugins_url ( '/js/vl-box-js.js', __FILE__ ),array('jquery'),'1.0.14' );
	}
}
function install_responsive_video_gallery_plus_lightbox() {
	global $wpdb;
	$table_name = $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox";
	$charset_collate = $wpdb->get_charset_collate();
        
	$sql = "CREATE TABLE " . $table_name . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `vtype` varchar(50)  NOT NULL,
        `vid` varchar(500)  NOT NULL,
        `video_url` varchar(1000)  DEFAULT NULL,
        `embed_url` varchar(300) NOT NULL,
        `HdnMediaSelection` varchar(500)  NOT NULL,
        `image_name` varchar(500)  NOT NULL,
        `videotitle` varchar(1000)  NOT NULL,
        `videotitleurl` varchar(1000)  DEFAULT NULL,
         `video_description` text  DEFAULT NULL,
        `video_order` int(11) NOT NULL DEFAULT '0',
        `open_link_in` tinyint(1) NOT NULL DEFAULT '1',
        `enable_light_box_video_desc` tinyint(1) NOT NULL DEFAULT '1',
        `createdon` datetime NOT NULL,
        `slider_id` int(10) unsigned NOT NULL DEFAULT '1',
         PRIMARY KEY (`id`)
        ) $charset_collate;";
        
         $responsive_video_gallery_slider_settings=array(
                                                    'pauseonmouseover' => '1',
                                                    'auto' =>'',
                                                    'speed' => '1000',
                                                    'pause'=>1000,
                                                    'circular' => '1',
                                                    'imageheight' => '120',
                                                    'imagewidth' => '120',
                                                    'imageMargin'=>'15',
                                                    'visible'=> '3',
                                                    'min_visible'=> '1',
                                                    'scroll' => '1',
                                                    'resizeImages'=>'1',
                                                    'scollerBackground'=>'#FFFFFF',
                                                    'show_caption'=>'0',
                                                    'show_pager'=>'0'
                                                    
                                                );
               
         
         
          $existingopt=get_option('responsive_video_gallery_slider_settings');
          if(!is_array($existingopt)){

               update_option('responsive_video_gallery_slider_settings',$responsive_video_gallery_slider_settings);

           }
           else{

               $flag=false;
               if(!isset($existingopt['show_caption'])){

                  $flag=true; 
                  $existingopt['show_caption']='0'; 

               }
               if(!isset($existingopt['show_pager'])){

                   $flag=true; 
                   $existingopt['show_pager']='0'; 

                }

               if($flag==true){

                  update_option('responsive_video_gallery_slider_settings', $existingopt); 

                 }
           }
         
         
                
	require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta ( $sql );
	
	$uploads = wp_upload_dir ();
	$baseDir = $uploads ['basedir'];
	$baseDir = str_replace ( "\\", "/", $baseDir );
	$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
	wp_mkdir_p ( $pathToImagesFolder );
        rvg_responsive_video_gallery_add_access_capabilities();
        
        
}
function responsive_video_gallery_plus_lightbox_add_admin_menu() {
	$hook_suffix=add_menu_page ( __ ( 'Responsive Video Carousel','wp-responsive-video-gallery-with-lightbox' ), __ ( 'Video Carousel with Lightbox','wp-responsive-video-gallery-with-lightbox' ), 'rvg_responsive_video_gallery_settings', 'responsive_video_gallery_with_lightbox', 'responsive_video_gallery_with_lightbox_admin_options_func' );
	$hook_suffix=add_submenu_page ( 'responsive_video_gallery_with_lightbox', __ ( 'Carousel Settings','wp-responsive-video-gallery-with-lightbox' ), __ ( 'Carousel Settings', 'wp-responsive-video-gallery-with-lightbox'), 'rvg_responsive_video_gallery_settings', 'responsive_video_gallery_with_lightbox', 'responsive_video_gallery_with_lightbox_admin_options_func' );
	$hook_suffix_image=add_submenu_page ( 'responsive_video_gallery_with_lightbox', __ ( 'Manage Videos','wp-responsive-video-gallery-with-lightbox' ), __ ( 'Manage Videos','wp-responsive-video-gallery-with-lightbox' ), 'rvg_responsive_video_gallery_view_video', 'responsive_video_gallery_with_lightbox_video_management', 'responsive_video_gallery_with_lightbox_video_management_func' );
	$hook_suffix_prev=add_submenu_page ( 'responsive_video_gallery_with_lightbox', __ ( 'Preview Carousel','wp-responsive-video-gallery-with-lightbox' ), __ ( 'Preview Gallery','wp-responsive-video-gallery-with-lightbox' ), 'rvg_responsive_video_gallery_preview', 'responsive_video_gallery_with_lightbox_video_preview', 'responsive_video_gallery_with_lightbox_video_preview_func' );
	
	add_action( 'load-' . $hook_suffix , 'responsive_video_gallery_plus_lightbox_add_admin_init' );
	add_action( 'load-' . $hook_suffix_image , 'responsive_video_gallery_plus_lightbox_add_admin_init' );
	add_action( 'load-' . $hook_suffix_prev , 'responsive_video_gallery_plus_lightbox_add_admin_init' );
	
}
function responsive_video_gallery_plus_lightbox_add_admin_init() {
	$url = plugin_dir_url ( __FILE__ );
	
	wp_enqueue_style ( 'wp-video-gallery-lighbox-style', plugins_url ( '/css/wp-video-gallery-lighbox-style.css', __FILE__ ) );
	wp_enqueue_style ( 'vl-box-css', plugins_url ( '/css/vl-box-css.css', __FILE__ ) );
        wp_enqueue_style( 'admin-css-resp-video-gallery', plugins_url('/css/admin-css.css', __FILE__) );
	wp_enqueue_script ( 'jquery' );
	wp_enqueue_script ( 'jquery.validate', $url . 'js/jquery.validate.js' );
	wp_enqueue_script ( 'video-gallery-jc', plugins_url ( '/js/video-gallery-jc.js', __FILE__ ) );
	wp_enqueue_script ( 'vl-box-js', plugins_url ( '/js/vl-box-js.js', __FILE__ ) );
	
	responsive_video_gallery_plus_lightbox_admin_scripts_init ();
}


   function responsive_video_gallery_with_lightbox_admin_options_func(){
       
     if ( ! current_user_can( 'rvg_responsive_video_gallery_settings' ) ) {

           wp_die( __( "Access Denied", "wp-responsive-video-gallery-with-lightbox" ) );

      } 
      
     if(isset($_POST['btnsave'])){
         
         if (!check_admin_referer('action_image_add_edit', 'add_edit_image_nonce')) {

                wp_die('Security check fail','wp-responsive-video-gallery-with-lightbox');
            }


         $auto=trim(htmlentities(sanitize_text_field($_POST['isauto']),ENT_QUOTES));
         
        if($auto=='auto')
           $auto=true;
         else if($auto=='manuall')
           $auto=false; 
         else
           $auto=2;  
            
         $speed=(int)trim(htmlentities(sanitize_text_field($_POST['speed']),ENT_QUOTES));
         $pause=(int)trim(htmlentities(sanitize_text_field($_POST['pause']),ENT_QUOTES));
         
         if(isset($_POST['circular']))
           $circular=true;  
        else
           $circular=false;  

         //$scrollerwidth=$_POST['scrollerwidth'];
         
         $visible=intval(htmlentities(sanitize_text_field($_POST['visible']),ENT_QUOTES));
         
         $min_visible=intval(htmlentities(sanitize_text_field($_POST['min_visible']),ENT_QUOTES));

         $show_caption=intval(htmlentities(sanitize_text_field($_POST['show_caption'],ENT_QUOTES)));  

         $show_pager=intval(htmlentities(sanitize_text_field($_POST['show_pager'],ENT_QUOTES)));  

            
         if(isset($_POST['pauseonmouseover']))
           $pauseonmouseover=true;  
         else 
          $pauseonmouseover=false;
         
         if(isset($_POST['linkimage']))
           $linkimage=true;  
         else 
          $linkimage=false;
         
         $scroll=intval(htmlentities(sanitize_text_field($_POST['scroll']),ENT_QUOTES));
         
         if($scroll=="")
          $scroll=1;
         
         $imageMargin=(int) trim(htmlentities(sanitize_text_field($_POST['imageMargin']),ENT_QUOTES));
         $imageheight=(int) trim(htmlentities(sanitize_text_field($_POST['imageheight']),ENT_QUOTES));
         $imagewidth=(int)  trim(htmlentities(sanitize_text_field($_POST['imagewidth']),ENT_QUOTES));
         
         $scollerBackground=trim(htmlentities(sanitize_text_field($_POST['scollerBackground']),ENT_QUOTES));
         
         $options=array();
         $options['pauseonmouseover']=$pauseonmouseover;  
         $options['auto']=$auto;  
         $options['speed']=$speed;  
         $options['pause']=$pause;  
         $options['circular']=$circular;  
         //$options['scrollerwidth']=$scrollerwidth;  
         $options['imageMargin']=$imageMargin;  
         $options['imageheight']=$imageheight;  
         $options['imagewidth']=$imagewidth;  
         $options['visible']=$visible;  
         $options['min_visible']=$min_visible;  
         $options['scroll']=$scroll;  
         $options['resizeImages']=1;  
         $options['scollerBackground']=$scollerBackground;  
         $options['show_caption']=$show_caption;  
         $options['show_pager']=$show_pager;  
        
         
         $settings=update_option('responsive_video_gallery_slider_settings',$options); 
         $responsive_video_gallery_plus_lightbox_messages=array();
         $responsive_video_gallery_plus_lightbox_messages['type']='succ';
         $responsive_video_gallery_plus_lightbox_messages['message']=__('Settings saved successfully.','wp-responsive-video-gallery-with-lightbox');
         update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);

        
         
     }  
      $settings=get_option('responsive_video_gallery_slider_settings');
      
?>      
<div id="poststuff" > 
   <div id="post-body" class="metabox-holder columns-2" >  
      <div id="post-body-content">
          <div class="wrap">
              <table><tr>
                      <td>
                          <div class="fb-like" data-href="https://www.facebook.com/i13websolution" data-layout="button" data-action="like" data-size="large" data-show-faces="false" data-share="false"></div>
                          <div id="fb-root"></div>
                            <script>(function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0];
                              if (d.getElementById(id)) return;
                              js = d.createElement(s); js.id = id;
                              js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.2&appId=158817690866061&autoLogAppEvents=1';
                              fjs.parentNode.insertBefore(js, fjs);
                            }(document, 'script', 'facebook-jssdk'));</script>
                      </td>
                      <td>
                          <a target="_blank" title="Donate" href="http://www.i13websolution.com/donate-wordpress_image_thumbnail.php">
                              <img id="help us for free plugin" height="30" width="90" src="<?php echo plugins_url( 'images/paypaldonate.jpg', __FILE__ ) ;?>" border="0" alt="help us for free plugin" title="help us for free plugin">
                          </a>
                      </td>
                  </tr>
              </table>
               <span><h3 style="color: blue;"><a target="_blank" href="https://www.i13websolution.com/wordpress-responsive-video-gallery-with-lightbox-pro.html"><?php echo __('UPGRADE TO PRO VERSION','wp-responsive-video-gallery-with-lightbox');?></a></h3></span>
              <?php
                  $messages=get_option('responsive_video_gallery_plus_lightbox_messages'); 
                  $type='';
                  $message='';
                  if(isset($messages['type']) and $messages['type']!=""){

                      $type=$messages['type'];
                      $message=$messages['message'];

                  }  


                 if(trim($type)=='err'){ echo "<div class='notice notice-error is-dismissible'><p>"; echo $message; echo "</p></div>";}
                 else if(trim($type)=='succ'){ echo "<div class='notice notice-success is-dismissible'><p>"; echo $message; echo "</p></div>";}
       
                  update_option('responsive_video_gallery_plus_lightbox_messages', array());     
              ?>      
              
              <h2><?php echo __('Gallery Slider Settings','wp-responsive-video-gallery-with-lightbox');?></h2>
              <div id="poststuff">
                  <div id="post-body" class="metabox-holder columns-2">
                      <div id="post-body-content">
                          <form method="post" action="" id="scrollersettiings" name="scrollersettiings" >

                             
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Auto Scroll ?','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <?php $settings['auto']=(int)$settings['auto'];?>
                                                  <input style="width:20px;" type='radio' <?php if($settings['auto']==1){echo "checked='checked'";}?>  name='isauto' value='auto' ><?php echo __('Auto','wp-responsive-video-gallery-with-lightbox');?> &nbsp;<input style="width:20px;" type='radio' name='isauto' <?php if($settings['auto']==0){echo "checked='checked'";} ?> value='manuall' ><?php echo __('Scroll By Left & Right Arrow','wp-responsive-video-gallery-with-lightbox');?> &nbsp; &nbsp;<input style="width:20px;" type='radio' name='isauto' <?php if($settings['auto']==2){echo "checked='checked'";} ?> value='both' ><?php echo __('Scroll Auto With Arrow','wp-responsive-video-gallery-with-lightbox');?>
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label ><?php echo __('Speed','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="speed" size="30" name="speed" value="<?php echo $settings['speed']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <div style="clear:both"></div>

                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label ><?php echo __('Pause','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="pause" size="30" name="pause" value="<?php echo $settings['pause']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <div style="clear:both"><?php echo __('The amount of time (in ms) between each auto transition','wp-responsive-video-gallery-with-lightbox');?></div>

                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label ><?php echo __('Circular Slider ?','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="checkbox" id="circular" size="30" name="circular" value="" <?php if($settings['circular']==true){echo "checked='checked'";} ?> style="width:20px;">&nbsp;<?php echo __('Circular Slider ?','wp-responsive-video-gallery-with-lightbox');?>
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <div style="clear:both"></div>

                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Slider Background color','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="scollerBackground" size="30" name="scollerBackground" value="<?php echo $settings['scollerBackground']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>

                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Max Visible','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="visible" size="30" name="visible" value="<?php echo $settings['visible']; ?>" style="width:100px;">
                                                  <div style="clear:both"><?php echo __('This will decide your slider width automatically','wp-responsive-video-gallery-with-lightbox');?></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <?php echo __('Specify the number of items visible at all times within the slider.','wp-responsive-video-gallery-with-lightbox');?>
                                      <div style="clear:both"></div>

                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                 <h3><label><?php echo __('Min Visible','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                <div class="inside">
                                     <table>
                                       <tr>
                                         <td>
                                           <input type="text" id="min_visible" size="30" name="min_visible" value="<?php echo $settings['min_visible']; ?>" style="width:100px;">
                                           <div style="clear:both"><?php echo __('This will decide your slider width in responsive layout','wp-responsive-video-gallery-with-lightbox');?></div>
                                           <div></div>
                                         </td>
                                       </tr>
                                     </table>
                                     <?php echo __('The responsive layout decide by slider itself using min visible.','wp-responsive-video-gallery-with-lightbox');?>
                                     <div style="clear:both"></div>
                                   
                                 </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Scroll','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="scroll" size="30" name="scroll" value="<?php echo $settings['scroll']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <?php echo __('You can specify the number of items to scroll when you click the next or prev buttons.','wp-responsive-video-gallery-with-lightbox');?>
                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Pause On Mouse Over ?','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="checkbox" id="pauseonmouseover" size="30" name="pauseonmouseover" value="" <?php if($settings['pauseonmouseover']==true){echo "checked='checked'";} ?> style="width:20px;">&nbsp;Pause On Mouse Over ? 
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>
                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                            
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Image Height','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="imageheight" size="30" name="imageheight" value="<?php echo $settings['imageheight']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>

                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Image Width','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="imagewidth" size="30" name="imagewidth" value="<?php echo $settings['imagewidth']; ?>" style="width:100px;">
                                                  <div style="clear:both"></div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>

                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                              <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __('Image Margin','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                  <div class="inside">
                                      <table>
                                          <tr>
                                              <td>
                                                  <input type="text" id="imageMargin" size="30" name="imageMargin" value="<?php echo $settings['imageMargin']; ?>" style="width:100px;">
                                                  <div style="clear:both;padding-top:5px"><?php echo __('Gap between two images','wp-responsive-video-gallery-with-lightbox');?> </div>
                                                  <div></div>
                                              </td>
                                          </tr>
                                      </table>

                                      <div style="clear:both"></div>
                                  </div>
                              </div>
                             <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __( 'Show Caption?','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                 <div class="inside">
                                      <table>
                                        <tr>
                                          <td>
                                            <input style="width:20px;" type='radio' <?php if($settings['show_caption']==true){echo "checked='checked'";}?>  name='show_caption' value='1' ><?php echo __( 'Yes','wp-responsive-video-gallery-with-lightbox');?> &nbsp;<input style="width:20px;" type='radio' name='show_caption' <?php if($settings['show_caption']==false){echo "checked='checked'";} ?> value='0' ><?php echo __( 'No','wp-responsive-video-gallery-with-lightbox');?>
                                            <div style="clear:both"></div>
                                            <div></div>
                                          </td>
                                        </tr>
                                      </table>
                                      <div style="clear:both"></div>
                                  </div>
                               </div>
                                <div class="stuffbox" id="namediv" style="width:100%;">
                                  <h3><label><?php echo __( 'Show Pager?','wp-responsive-video-gallery-with-lightbox');?></label></h3>
                                 <div class="inside">
                                      <table>
                                        <tr>
                                          <td>
                                            <input style="width:20px;" type='radio' <?php if($settings['show_pager']==true){echo "checked='checked'";}?>  name='show_pager' value='1' ><?php echo __( 'Yes','wp-responsive-video-gallery-with-lightbox');?> &nbsp;<input style="width:20px;" type='radio' name='show_pager' <?php if($settings['show_pager']==false){echo "checked='checked'";} ?> value='0' ><?php echo __( 'No','wp-responsive-video-gallery-with-lightbox');?>
                                            <div style="clear:both"></div>
                                            <div></div>
                                          </td>
                                        </tr>
                                      </table>
                                      <div style="clear:both"></div>
                                  </div>
                               </div>
                              <?php wp_nonce_field('action_image_add_edit', 'add_edit_image_nonce'); ?> 
                              <input type="submit"  name="btnsave" id="btnsave" value="<?php echo __('Sage Changes','wp-responsive-video-gallery-with-lightbox');?>" class="button-primary">&nbsp;&nbsp;<input type="button" name="cancle" id="cancle" value="<?php echo __('Cancel','wp-responsive-video-gallery-with-lightbox');?>" class="button-primary" onclick="location.href='admin.php?page=responsive_video_gallery_with_lightbox_video_management'">

                          </form> 
                          <script type="text/javascript">

                              var $n = jQuery.noConflict();  
                              $n(document).ready(function() {

                                      $n("#scrollersettiings").validate({
                                              rules: {
                                                  isauto: {
                                                      required:true
                                                  },speed: {
                                                      required:true, 
                                                      number:true, 
                                                      maxlength:15
                                                  },pause: {
                                                      required:true, 
                                                      number:true, 
                                                      maxlength:15
                                                  },
                                                  visible:{
                                                      required:true,  
                                                      number:true,
                                                      maxlength:15

                                                  },
                                                  min_visible:{
                                                      required:true,  
                                                      number:true,
                                                      maxlength:15

                                                  },
                                                  scroll:{
                                                      required:true,
                                                      number:true,
                                                      maxlength:15  
                                                  },
                                                  scollerBackground:{
                                                      required:true,
                                                      maxlength:7  
                                                  },
                                                  /*scrollerwidth:{
                                                  required:true,
                                                  number:true,
                                                  maxlength:15    
                                                  },*/imageheight:{
                                                      required:true,
                                                      number:true,
                                                      maxlength:15    
                                                  },
                                                  imagewidth:{
                                                      required:true,
                                                      number:true,
                                                      maxlength:15    
                                                  },imageMargin:{
                                                      required:true,
                                                      number:true,
                                                      maxlength:15    
                                                  }

                                              },
                                              errorClass: "image_error",
                                              errorPlacement: function(error, element) {
                                                  error.appendTo( element.next().next());
                                              } 


                                      })
                                      
                                     $n('#scollerBackground').wpColorPicker();
                                           
                              });

                          </script> 

                      </div>
                  </div>
              </div>  
          </div>      
      </div>
 <div id="postbox-container-1" class="postbox-container" > 

          <div class="postbox"> 
              <h3 class="hndle"><span></span><?php echo __('Access All Themes In One Price','wp-responsive-video-gallery-with-lightbox');?></h3> 
              <div class="inside">
                  <center><a href="http://www.elegantthemes.com/affiliates/idevaffiliate.php?id=11715_0_1_10" target="_blank"><img border="0" src="<?php echo plugins_url( 'images/300x250.gif', __FILE__ ) ;?>" width="250" height="250"></a></center>

                  <div style="margin:10px 5px">

                  </div>
              </div></div>
              <div class="postbox"> 
                <h3 class="hndle"><span></span><?php echo __('Google For Business Coupon','wp-responsive-video-gallery-with-lightbox');?></h3> 
                    <div class="inside">
                        <center><a href="https://goo.gl/OJBuHT" target="_blank">
                                <img src="<?php echo plugins_url( 'images/g-suite-promo-code-4.png', __FILE__ );?>" width="250" height="250" border="0">
                            </a></center>
                        <div style="margin:10px 5px">
                        </div>
                    </div>
                    
                </div>

      </div>      
     
     <div class="clear"></div>
  </div>  
 </div> 
<?php
   } 
   
function responsive_video_gallery_with_lightbox_video_management_func() {
    
	$action = 'gridview';
	global $wpdb;
	
	
	
	if (isset ( $_GET ['action'] ) and $_GET ['action'] != '') {
		
		$action = trim ( $_GET ['action'] );
	}
	?>

        <?php
	if (strtolower ( $action ) == strtolower ( 'gridview' )) {
		
            
              if ( ! current_user_can( 'rvg_responsive_video_gallery_view_video' ) ) {

                    wp_die( __( "Access Denied", "wp-responsive-video-gallery-with-lightbox" ) );

               }

		$wpcurrentdir = dirname ( __FILE__ );
		$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
		
		$uploads = wp_upload_dir ();
		$baseurl = $uploads ['baseurl'];
		$baseurl .= '/wp-responsive-video-gallery-with-lightbox/';
		?> 
          <div class="wrap">
	   <?php
		$messages = get_option ( 'responsive_video_gallery_plus_lightbox_messages' );
		$type = '';
		$message = '';
		if (isset ( $messages ['type'] ) and $messages ['type'] != "") {
			
			$type = $messages ['type'];
			$message = $messages ['message'];
		}
		
		 if(trim($type)=='err'){ echo "<div class='notice notice-error is-dismissible'><p>"; echo $message; echo "</p></div>";}
                 else if(trim($type)=='succ'){ echo "<div class='notice notice-success is-dismissible'><p>"; echo $message; echo "</p></div>";}
       
		
		update_option ( 'responsive_video_gallery_plus_lightbox_messages', array () );
		?>
                <div id="poststuff" > 
                    <div id="post-body" class="metabox-holder columns-2" >  
                     <div id="post-body-content">
                     <div class="wrap">
                           <span><h3 style="color: blue;"><a target="_blank" href="https://www.i13websolution.com/wordpress-responsive-video-gallery-with-lightbox-pro.html"><?php echo __('UPGRADE TO PRO VERSION','wp-responsive-video-gallery-with-lightbox');?></a></h3></span>
                           <div style="width: 100%;">
			<div style="float: left; width: 100%;">
				<div class="icon32 icon32-posts-post" id="icon-edit">
					<br>
				</div>
				<h2>
					<?php echo __('Videos','wp-responsive-video-gallery-with-lightbox');?><a class="button add-new-h2" href="admin.php?page=responsive_video_gallery_with_lightbox_video_management&action=addedit"><?php echo __('Add New','wp-responsive-video-gallery-with-lightbox');?></a>
				</h2>
				<br />

				<form method="POST"
					action="admin.php?page=responsive_video_gallery_with_lightbox_video_management&action=deleteselected"
					id="posts-filter" onkeypress="return event.keyCode != 13;">
					<div class="alignleft actions">
						<select name="action_upper" id="action_upper">
							<option selected="selected" value="-1"><?php echo __('Bulk Actions','wp-responsive-video-gallery-with-lightbox');?></option>
							<option value="delete"><?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?></option>
						</select> <input type="submit" value="<?php echo __('Apply','wp-responsive-video-gallery-with-lightbox');?>"
							class="button-secondary action" id="deleteselected"
							name="deleteselected" onclick="return confirmDelete_bulk();">
					</div>
					<br class="clear">
                                        
                                                <?php
                                                    $setacrionpage='admin.php?page=responsive_video_gallery_with_lightbox_video_management';

                                                    if(isset($_GET['order_by']) and $_GET['order_by']!=""){
                                                      $setacrionpage.='&order_by='.sanitize_text_field($_GET['order_by']);   
                                                    }

                                                    if(isset($_GET['order_pos']) and $_GET['order_pos']!=""){
                                                     $setacrionpage.='&order_pos='.sanitize_text_field($_GET['order_pos']);   
                                                    }

                                                    $seval="";
                                                    if(isset($_GET['search_term']) and $_GET['search_term']!=""){
                                                     $seval=trim(sanitize_text_field($_GET['search_term']));   
                                                    }

                                                ?>
                                                <?php
							global $wpdb;
                                                        
							$settings=get_option('responsive_video_gallery_slider_settings');
							
							$visibleImages = $settings ['visible'];
                                                        
                                                         $order_by='id';
                                                        $order_pos="asc";

                                                        if(isset($_GET['order_by']) and sanitize_sql_orderby($_GET['order_by'])!==false){

                                                           $order_by=trim($_GET['order_by']); 
                                                        }

                                                        if(isset($_GET['order_pos'])){

                                                           $order_pos=trim(sanitize_text_field($_GET['order_pos'])); 
                                                        }
                                                        $search_term_='';
                                                        if(isset($_GET['search_term'])){

                                                           $search_term_='&search_term='.urlencode(sanitize_text_field($_GET['search_term']));
                                                        }
                                                         $search_term='';
                                                        if(isset($_GET['search_term'])){

                                                           $search_term= sanitize_text_field(esc_sql($_GET['search_term']));
                                                        }
                                                        
							$query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox ";
							$querycount = "SELECT count(*) FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox ";
                                                        if($search_term!=''){
                                                            $query.=" where id like '%$search_term%' or videotitle like '%$search_term%' "; 
                                                            $querycount.=" where id like '%$search_term%' or videotitle like '%$search_term%' "; 
                                                          }

                                                         $order_by=sanitize_text_field(sanitize_sql_orderby($order_by));
                                                         $order_pos=sanitize_text_field(sanitize_sql_orderby($order_pos));

                                                         $query.=" order by $order_by $order_pos";
                                                         $rowCount=$wpdb->get_var($querycount);
                                                         
                                                         
                                                          
							$query1 = "SELECT count(*) FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox ";
							$rowsCount=$wpdb->get_var($query1);
							
							?>
					                            <?php if ($rowsCount < $visibleImages) { ?>
                                                                                <h4 style="color: green"> <?php echo __('Current slider setting','wp-responsive-video-gallery-with-lightbox');?> - <?php echo __('Total visible Videos','wp-responsive-video-gallery-with-lightbox');?> <?php echo $visibleImages; ?></h4>
										<h4 style="color: green"><?php echo __('Please add atleast','wp-responsive-video-gallery-with-lightbox');?> <?php echo $visibleImages; ?> <?php echo __('Videos','wp-responsive-video-gallery-with-lightbox');?></h4>
					                                <?php
							} else {
								echo "<br/>";
							}
							?>
                                                                                
                                            <div style="padding-top:5px;padding-bottom:5px">
                                                <b><?php echo __( 'Search','full-width-responsive-slider-wp');?> : </b>
                                                  <input type="text" value="<?php echo $seval;?>" id="search_term" name="search_term">&nbsp;
                                                  <input type='button'  value='<?php echo __( 'Search','wp-responsive-video-gallery-with-lightbox');?>' name='searchusrsubmit' class='button-primary' id='searchusrsubmit' onclick="SearchredirectTO();" >&nbsp;
                                                  <input type='button'  value='<?php echo __( 'Reset Search','wp-responsive-video-gallery-with-lightbox');?>' name='searchreset' class='button-primary' id='searchreset' onclick="ResetSearch();" >
                                            </div>  
                                            <script type="text/javascript" >
                                               var $n = jQuery.noConflict();   
                                                $n('#search_term').on("keyup", function(e) {
                                                       if (e.which == 13) {

                                                           SearchredirectTO();
                                                       }
                                                  });   
                                             function SearchredirectTO(){
                                               var redirectto='<?php echo $setacrionpage; ?>';
                                               var searchval=jQuery('#search_term').val();
                                               redirectto=redirectto+'&search_term='+jQuery.trim(encodeURIComponent(searchval));  
                                               window.location.href=redirectto;
                                             }
                                            function ResetSearch(){

                                                 var redirectto='<?php echo $setacrionpage; ?>';
                                                 window.location.href=redirectto;
                                                 exit;
                                            }
                                            </script>      
                                            <div id="no-more-tables">
						<table cellspacing="0" id="gridTbl"
							class="table-bordered table-striped table-condensed cf">
							<thead>
								<tr>
									<th class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
									<?php if($order_by=="id" and $order_pos=="asc"):?>
                                                                            <th><a href="<?php echo $setacrionpage;?>&order_by=id&order_pos=desc<?php echo $search_term_;?>"><?php echo __('Id','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/desc.png', __FILE__); ?>"/></a></th>
                                                                       <?php else:?>
                                                                           <?php if($order_by=="id"):?>
                                                                       <th><a href="<?php echo $setacrionpage;?>&order_by=id&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Id','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/asc.png', __FILE__); ?>"/></a></th>
                                                                           <?php else:?>
                                                                               <th><a href="<?php echo $setacrionpage;?>&order_by=id&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Id','wp-responsive-video-gallery-with-lightbox');?></a></th>
                                                                           <?php endif;?>    
                                                                       <?php endif;?>  
									<?php if($order_by=="vtype" and $order_pos=="asc"):?>
                                                                            <th><a href="<?php echo $setacrionpage;?>&order_by=vtype&order_pos=desc<?php echo $search_term_;?>"><?php echo __('Video Type','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/desc.png', __FILE__); ?>"/></a></th>
                                                                       <?php else:?>
                                                                           <?php if($order_by=="vtype"):?>
                                                                       <th><a href="<?php echo $setacrionpage;?>&order_by=vtype&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Video Type','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/asc.png', __FILE__); ?>"/></a></th>
                                                                           <?php else:?>
                                                                               <th><a href="<?php echo $setacrionpage;?>&order_by=vtype&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Video Type','wp-responsive-video-gallery-with-lightbox');?></a></th>
                                                                           <?php endif;?>    
                                                                       <?php endif;?> 
                                                                               
									<?php if($order_by=="videotitle" and $order_pos=="asc"):?>
                                                                            <th><a href="<?php echo $setacrionpage;?>&order_by=videotitle&order_pos=desc<?php echo $search_term_;?>"><?php echo __('Title','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/desc.png', __FILE__); ?>"/></a></th>
                                                                       <?php else:?>
                                                                           <?php if($order_by=="videotitle"):?>
                                                                       <th><a href="<?php echo $setacrionpage;?>&order_by=videotitle&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Title','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/asc.png', __FILE__); ?>"/></a></th>
                                                                           <?php else:?>
                                                                               <th><a href="<?php echo $setacrionpage;?>&order_by=videotitle&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Title','wp-responsive-video-gallery-with-lightbox');?></a></th>
                                                                           <?php endif;?>    
                                                                       <?php endif;?>  
									<th><span></span></th>
                                                                        <?php if($order_by=="createdon" and $order_pos=="asc"):?>
                                                                            <th><a href="<?php echo $setacrionpage;?>&order_by=createdon&order_pos=desc<?php echo $search_term_;?>"><?php echo __('Published On','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/desc.png', __FILE__); ?>"/></a></th>
                                                                        <?php else:?>
                                                                            <?php if($order_by=="createdon"):?>
                                                                        <th><a href="<?php echo $setacrionpage;?>&order_by=createdon&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Published On','wp-responsive-video-gallery-with-lightbox');?><img style="vertical-align:middle" src="<?php echo plugins_url('/images/asc.png', __FILE__); ?>"/></a></th>
                                                                            <?php else:?>
                                                                                <th><a href="<?php echo $setacrionpage;?>&order_by=createdon&order_pos=asc<?php echo $search_term_;?>"><?php echo __('Published On','wp-responsive-video-gallery-with-lightbox');?></a></th>
                                                                            <?php endif;?>    
                                                                        <?php endif;?> 
									<th><span><?php echo __('Edit','wp-responsive-video-gallery-with-lightbox');?></span></th>
									<th><span><?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?></span></th>
								</tr>
							</thead>

							<tbody id="the-list">
                                                        <?php
								if ($rowCount > 0) {
									
									global $wp_rewrite;
									$rows_per_page = 10;
									
									$current = (isset ( $_GET ['paged'] )) ? ((int) htmlentities(strip_tags($_GET ['paged']),ENT_QUOTES)) : 1;
									$pagination_args = array (
											'base' => @add_query_arg ( 'paged', '%#%' ),
											'format' => '',
											'total' => ceil ( $rowCount / $rows_per_page ),
											'current' => $current,
											'show_all' => false,
											'type' => 'plain' 
									);
									
									$delRecNonce = wp_create_nonce('delete_image');
                                                                        $offset = ($current - 1) * $rows_per_page;
                                                                        $query.=" limit $offset, $rows_per_page";
                                                                        
                                                                        $rows = $wpdb->get_results ( $query ,'ARRAY_A' );
									foreach($rows as $row) {
										
										
										$id = $row ['id'];
										$editlink = "admin.php?page=responsive_video_gallery_with_lightbox_video_management&action=addedit&id=$id";
										$deletelink = "admin.php?page=responsive_video_gallery_with_lightbox_video_management&action=delete&id=$id&nonce=$delRecNonce";
										
										$outputimgmain = $baseurl . $row ['image_name'].'?rand='.  rand(0, 5000);
										?>
                                                                    <tr valign="top">
									<td class="alignCenter check-column" data-title="<?php echo __('Select Record','wp-responsive-video-gallery-with-lightbox');?>">
                                                                            <input type="checkbox" value="<?php echo $row['id'] ?>" name="thumbnails[]">
                                                                        </td>
									<td data-title="<?php echo __('Id','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter"><?php echo $row['id']; ?></td>
									<td data-title="<?php echo __('Video Type','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter">
                                                                            <div>
											<strong><?php echo $row['vtype']; ?></strong>
										</div>
                                                                        </td>
									   <td data-title="<?php echo __('Title','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter">
									   <div>
											<strong><?php echo $row['videotitle']; ?></strong>
										</div>
                                                                           </td>
									<td class="alignCenter">
                                                                            <img src="<?php echo $outputimgmain; ?>" style="width: 50px" height="50px" />
                                                                        </td>
									<td data-title="<?php echo __('Published On','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter"><?php echo $row['createdon'] ?></td>
									<td data-title="<?php echo __('Edit','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter">
                                                                            <strong><a href='<?php echo $editlink; ?>' title="<?php echo __('Edit','wp-responsive-video-gallery-with-lightbox');?>"><?php echo __('Edit','wp-responsive-video-gallery-with-lightbox');?></a></strong>
                                                                        </td>
									<td data-title="<?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?>" class="alignCenter">
                                                                            <strong>
                                                                                <a href='<?php echo $deletelink; ?>' onclick="return confirmDelete();" title="<?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?>"><?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?></a> 
                                                                            </strong>
                                                                        </td>
								</tr>
                                                            <?php
									}
								} else {
									?>
								<tr valign="top" class=""
									id="">
									<td colspan="8" data-title="<?php echo __('No Records','wp-responsive-video-gallery-with-lightbox');?>" align="center"><strong><?php echo __('No Videos Found','wp-responsive-video-gallery-with-lightbox');?></strong></td>
								</tr>
                                                                <?php
								}
								?>      
                                                     </tbody>
                                                    </table>
                                                    </div>
                                                 <?php
							if ($rowCount > 0) {
								echo "<div class='pagination' style='padding-top:10px'>";
								echo paginate_links ( $pagination_args );
								echo "</div>";
							}
							?>
                                                 <br />
					<div class="alignleft actions">
						<select name="action" id="action_bottom">
							<option selected="selected" value="-1"><?php echo __('Bulk Actions','wp-responsive-video-gallery-with-lightbox');?></option>
							<option value="delete"><?php echo __('Delete','wp-responsive-video-gallery-with-lightbox');?></option>
						</select>
                                            <?php wp_nonce_field('action_settings_mass_delete', 'mass_delete_nonce'); ?>
                                            <input type="submit" value="<?php echo __('Apply','wp-responsive-video-gallery-with-lightbox');?>"
							class="button-secondary action" id="deleteselected"
							name="deleteselected" onclick="return confirmDelete_bulk();">
					</div>

				</form>
				<script type="text/JavaScript">

                                 function  confirmDelete_bulk(){
                                     var topval=document.getElementById("action_bottom").value;
                                     var bottomVal=document.getElementById("action_upper").value;

                                     if(topval=='delete' || bottomVal=='delete'){


                                         var agree=confirm("<?php echo __('Are you sure you want to delete selected videos?','wp-responsive-video-gallery-with-lightbox');?>");
                                         if (agree)
                                             return true ;
                                         else
                                             return false;
                                     }
                              }
                                function  confirmDelete(){

                                    var agree=confirm("<?php echo __('Are you sure you want to delete this video ?','wp-responsive-video-gallery-with-lightbox');?>");
                                    if (agree)
                                        return true ;
                                   else
                                      return false;
                                }
                        </script>

				<br class="clear">
			</div>
			<div style="clear: both;"></div>
                    <?php $url = plugin_dir_url(__FILE__); ?>


                </div>
		<h3><?php echo __('To print this video carousel into WordPress Post/Page use below code','wp-responsive-video-gallery-with-lightbox');?></h3>
		<input type="text"
			value='[print_responsive_video_gallery_plus_lightbox] '
			style="width: 400px; height: 30px"
			onclick="this.focus(); this.select()" />
		<div class="clear"></div>
		<h3><?php echo __('To print this video carousel into WordPress theme/template PHP files use below code','wp-responsive-video-gallery-with-lightbox');?></h3>
                <?php
		$shortcode = '[print_responsive_video_gallery_plus_lightbox]';
		?>
                <input type="text"
			value="&lt;?php echo do_shortcode('<?php echo htmlentities($shortcode, ENT_QUOTES); ?>'); ?&gt;"
			style="width: 400px; height: 30px"
			onclick="this.focus(); this.select()" />
		<div class="clear"></div>
          </div>
          </div>
             <div id="postbox-container-1" class="postbox-container" > 

          <div class="postbox"> 
              <h3 class="hndle"><span></span><?php echo __('Access All Themes In One Price','wp-responsive-video-gallery-with-lightbox');?></h3> 
              <div class="inside">
                  <center><a href="http://www.elegantthemes.com/affiliates/idevaffiliate.php?id=11715_0_1_10" target="_blank"><img border="0" src="<?php echo plugins_url( 'images/300x250.gif', __FILE__ ) ;?>" width="250" height="250"></a></center>

                  <div style="margin:10px 5px">

                  </div>
              </div></div>
             <div class="postbox"> 
                <h3 class="hndle"><span></span><?php echo __('Google For Business Coupon','wp-responsive-video-gallery-with-lightbox');?></h3> 
                    <div class="inside">
                        <center><a href="https://goo.gl/OJBuHT" target="_blank">
                                <img src="<?php echo plugins_url( 'images/g-suite-promo-code-4.png', __FILE__ );?>" width="250" height="250" border="0">
                            </a></center>
                        <div style="margin:10px 5px">
                        </div>
                    </div>
                    
                </div>

      </div>
    </div>
   </div>
</div>
       <?php
	} else if (strtolower ( $action ) == strtolower ( 'addedit' )) {
		$url = plugin_dir_url ( __FILE__ );
                $vNonce = wp_create_nonce('vNonce');
		?><?php
		if (isset ( $_POST ['btnsave'] )) {
			
                        if (!check_admin_referer('action_image_add_edit', 'add_edit_image_nonce')) {

                            wp_die('Security check fail');
                        }

			$uploads = wp_upload_dir ();
			$baseDir = $uploads ['basedir'];
			$baseDir = str_replace ( "\\", "/", $baseDir );
			$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
			
			$vtype = trim ( htmlentities(sanitize_text_field( $_POST ['vtype'] ),ENT_QUOTES) );
			$videourl = trim ( htmlentities(esc_url_raw($_POST ['videourl'] ),ENT_QUOTES));
			// echo $videourl;die;
			$vid = uniqid ( 'vid_' );
			$embed_url='';
			if ($vtype == 'youtube') {
				// parse
				
				$parseUrl = @parse_url ( $videourl );
				if (is_array ( $parseUrl )) {
					
					$queryStr = $parseUrl ['query'];
					parse_str ( $queryStr, $array );
					if (is_array ( $array ) and isset ( $array ['v'] )) {
						
						$vid = $array ['v'];
					}
				}
				
			    $embed_url="//www.youtube.com/embed/$vid";	
			}
			else if($vtype=='dailymotion'){
				
                                $url_arr = parse_url($videourl);
                                if(is_array($url_arr) and isset($url_arr['query'])){
                                    
                                    $query = $url_arr['query'];
                                    $videourl = str_replace(array($query,'?'), '', $videourl);
                                }
                               $pos = strpos($videourl, '/video/');
                                $vid=0;
                                if ($pos !== false){

                                    $vid=substr($videourl, $pos+strlen('/video/'));

                                }

                               $embed_url="//www.dailymotion.com/embed/video/$vid";
				
			}
			
			
			$HdnMediaSelection = trim ( htmlentities(esc_url_raw($_POST ['HdnMediaSelection'] ),ENT_QUOTES));
			$videotitle = trim ( htmlentities(sanitize_text_field($_POST ['videotitle'] ),ENT_QUOTES)) ;
			$videotitleurl = trim ( htmlentities(esc_url_raw($_POST ['videotitleurl'] ),ENT_QUOTES));
			$video_order = 0;
			
			$video_description = '';
			
			$videotitle = str_replace("'","’",$videotitle);
			$videotitle = str_replace('"', '&quot;', $videotitle);
			
			$open_link_in = 1;
			
			$enable_light_box_video_desc = 0;

			$location = "admin.php?page=responsive_video_gallery_with_lightbox_video_management";
				// edit save
			if (isset ( $_POST ['videoid'] )) {
				
                                 if ( ! current_user_can( 'rvg_responsive_video_gallery_edit_video' ) ) {

                                        $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                                        $responsive_video_gallery_plus_lightbox_messages=array();
                                        $responsive_video_gallery_plus_lightbox_messages['type']='err';
                                        $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                                        update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                                        echo "<script type='text/javascript'> location.href='$location';</script>";     
                                        exit;   

                                  }
                                
				try {
						
						$videoidEdit=intval(htmlentities(strip_tags($_POST ['videoid']),ENT_QUOTES));
						if (trim ( $_POST ['HdnMediaSelection'] ) != '') {
                                                    
							$pInfo = pathinfo ( $HdnMediaSelection );
							 $ext = @$pInfo ['extension'];
                                                        if($ext==''){
                                                           if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_PNG) {

                                                              $ext='png'; 
                                                           } 
                                                           else if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_JPEG) {

                                                              $ext='jpeg'; 
                                                           } 
                                                           else if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_GIF) {

                                                              $ext='gif'; 
                                                           } 

                                                        }
							$imagename = $vid . '_big.' . $ext;
							$imageUploadTo = $pathToImagesFolder . '/' . $imagename;
							@copy ( $HdnMediaSelection, $imageUploadTo );
                                                        if(!file_exists($imageUploadTo)){
                                                            vgallery_save_image_curl($HdnMediaSelection,$imageUploadTo);
                                                        }
                                                        $settings=get_option('responsive_video_gallery_slider_settings');
                                                        $imageheight = $settings ['imageheight'];
                                                        $imagewidth = $settings ['imagewidth'];
                                                        @unlink($pathToImagesFolder.'/'.$vid . '_big_'.$imageheight.'_'.$imagewidth.'.'.$ext);
						}
							
						$query = "update " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox
						set vtype='$vtype',vid='$vid',video_url='$videourl',embed_url='$embed_url',image_name='$imagename',HdnMediaSelection='$HdnMediaSelection',
						videotitle='$videotitle',videotitleurl='$videotitleurl',video_description='$video_description',video_order=$video_order,
						open_link_in=$open_link_in,enable_light_box_video_desc=$enable_light_box_video_desc where id=$videoidEdit";
							
						
						$wpdb->query ( $query );
							
						$responsive_video_gallery_plus_lightbox_messages = array ();
						$responsive_video_gallery_plus_lightbox_messages ['type'] = 'succ';
						$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Video updated successfully.','wp-responsive-video-gallery-with-lightbox');
						update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
					} 
                                        catch ( Exception $e ) {
							
						$responsive_video_gallery_plus_lightbox_messages = array ();
                                                $responsive_video_gallery_plus_lightbox_messages ['type'] = 'err';
                                                $responsive_video_gallery_plus_lightbox_messages ['message'] = __('Error while adding video.','wp-responsive-video-gallery-with-lightbox');
						update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
				         }

				
				
				echo "<script type='text/javascript'> location.href='$location';</script>";
				exit ();
			} 
                        else {
				
				// add new
				
                                if ( ! current_user_can( 'rvg_responsive_video_gallery_add_video' ) ) {

                                        $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                                        $responsive_video_gallery_plus_lightbox_messages=array();
                                        $responsive_video_gallery_plus_lightbox_messages['type']='err';
                                        $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                                        update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                                        echo "<script type='text/javascript'> location.href='$location';</script>";     
                                        exit;   

                                  }
                                  
				$createdOn = current_time ( 'Y-m-d h:i:s' );
				
				try {
					
					if (trim ( $_POST ['HdnMediaSelection'] ) != '') {
						$pInfo = pathinfo ( $HdnMediaSelection );
						 $ext = @$pInfo ['extension'];
                                                if($ext==''){
                                                   if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_PNG) {

                                                      $ext='png'; 
                                                   } 
                                                   else if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_JPEG) {

                                                      $ext='jpeg'; 
                                                   } 
                                                   else if (exif_imagetype($HdnMediaSelection) == IMAGETYPE_GIF) {

                                                      $ext='gif'; 
                                                   } 

                                                }
						$imagename = $vid . '_big.' . $ext;
						$imageUploadTo = $pathToImagesFolder . '/' . $imagename;
						@copy ( $HdnMediaSelection, $imageUploadTo );
                                                if(!file_exists($imageUploadTo)){
                                                    vgallery_save_image_curl($HdnMediaSelection,$imageUploadTo);
                                                }

					}
					
					$query = "INSERT INTO " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox 
                                		(vtype, vid,video_url,embed_url,image_name,HdnMediaSelection,videotitle,videotitleurl,video_description,video_order,open_link_in,
                            			enable_light_box_video_desc,createdon) 
                           				 VALUES ('$vtype','$vid','$videourl','$embed_url','$imagename','$HdnMediaSelection','$videotitle','$videotitleurl','$video_description',
                                		$video_order,$open_link_in,$enable_light_box_video_desc,'$createdOn')";
					
					//echo $query;die;
					$wpdb->query ( $query );
					
					$responsive_video_gallery_plus_lightbox_messages = array ();
					$responsive_video_gallery_plus_lightbox_messages ['type'] = 'succ';
					$responsive_video_gallery_plus_lightbox_messages ['message'] = __('New video added successfully.','wp-responsive-video-gallery-with-lightbox');
					update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
				} catch ( Exception $e ) {
					
					$responsive_video_gallery_plus_lightbox_messages = array ();
					$responsive_video_gallery_plus_lightbox_messages ['type'] = 'err';
					$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Error while adding video','wp-responsive-video-gallery-with-lightbox');
					update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
				}
				
				echo "<script type='text/javascript'> location.href='$location';</script>";
				exit ();
			}
		} else {
			
			$uploads = wp_upload_dir ();
			$baseurl = $uploads ['baseurl'];
			$baseurl .= '/wp-responsive-video-gallery-with-lightbox/';
			?>
                 <div id="poststuff" > 
                  <div id="post-body" class="metabox-holder columns-2" >  
                   <div id="post-body-content">
                    <div class="wrap">
                       <div style="float: left; width: 100%;">
                          <div class="wrap">
                          <?php
		    	  if (isset ( $_GET ['id'] ) and intval($_GET ['id']) > 0) {
				
                                if ( ! current_user_can( 'rvg_responsive_video_gallery_edit_video' ) ) {

                                        $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                                        $responsive_video_gallery_plus_lightbox_messages=array();
                                        $responsive_video_gallery_plus_lightbox_messages['type']='err';
                                        $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                                        update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                                        echo "<script type='text/javascript'> location.href='$location';</script>";     
                                        exit;   

                                  }
                                  
				$id = intval($_GET ['id']);
				$query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox WHERE id=$id";
				
				$myrow = $wpdb->get_row ( $query );
				
				if (is_object ( $myrow )) {
					
                                    
                                    if ( ! current_user_can( 'rvg_responsive_video_gallery_edit_video' ) ) {

                                        $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                                        $responsive_video_gallery_plus_lightbox_messages=array();
                                        $responsive_video_gallery_plus_lightbox_messages['type']='err';
                                        $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                                        update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                                        echo "<script type='text/javascript'> location.href='$location';</script>";     
                                        exit;   

                                     }
                                  
					$vtype = $myrow->vtype ;
					$title = $myrow->videotitle ;
					$image_name = $myrow->image_name;
					$video_url =  $myrow->video_url ;
					$HdnMediaSelection = $myrow->HdnMediaSelection;
					$videotitle = $myrow->videotitle;
					$videotitleurl=$myrow->videotitleurl;
					$video_order = $myrow->video_order;
					$video_description = $myrow->video_description;
					$open_link_in = $myrow->open_link_in ;
					$enable_light_box_video_desc = $myrow->enable_light_box_video_desc ;
				}
				?>
                            <h2><?php echo __('Update Video','wp-responsive-video-gallery-with-lightbox');?></h2>
                                <?php
                                   } else {

                                       
                                          if ( ! current_user_can( 'rvg_responsive_video_gallery_add_video' ) ) {

                                                $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                                                $responsive_video_gallery_plus_lightbox_messages=array();
                                                $responsive_video_gallery_plus_lightbox_messages['type']='err';
                                                $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                                                update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                                                echo "<script type='text/javascript'> location.href='$location';</script>";     
                                                exit;   

                                             } 
                                           $vtype='';
                                           $title = '';
                                           $videotitle='';
                                           $videotitleurl='';
                                           $HdnMediaSelection='';
                                           $video_url = '';
                                           $image_link = '';
                                           $image_name = '';
                                           $video_order = '';
                                           $video_description = '';
                                           $open_link_in = true;
                                           $enable_light_box_video_desc = true;
                                           ?>

                             <div style="clear:both">
                                       <span><h3 style="color: blue;"><a target="_blank" href="https://www.i13websolution.com/wordpress-responsive-video-gallery-with-lightbox-pro.html"><?php echo __('UPGRADE TO PRO VERSION','wp-responsive-video-gallery-with-lightbox');?></a></h3></span>
                                   </div>  
                            <h2><?php echo __('Add Video','wp-responsive-video-gallery-with-lightbox');?></h2>
                              <?php } ?>
                              <br />
					<div id="poststuff">
						<div id="post-body" class="metabox-holder columns-2">
							<div id="post-body-content">
								<form method="post" action="" id="addimage" name="addimage"
									enctype="multipart/form-data">
									<div class="stuffbox" id="namediv" style="width: 100%">
										<h3>
											<label for="link_name"><?php echo __('Video Information','wp-responsive-video-gallery-with-lightbox');?> (<span
												style="font-size: 11px; font-weight: normal"><?php _e('Choose Video Site','wp-responsive-video-gallery-with-lightbox'); ?></span>)
											</label>
										</h3>
										<div class="inside">
											<div>
												<input type="radio" value="youtube" name="vtype"
													<?php if($vtype=='youtube'): ?> checked='checked' <?php endif;?> style="width: 15px" id="type_youtube" /><?php echo __('Youtube','wp-responsive-video-gallery-with-lightbox');?>&nbsp;&nbsp;
												<input <?php if($vtype=='dailymotion'): ?> checked='checked' <?php endif;?> type="radio" value="dailymotion" name="vtype"
													style="width: 15px" id="type_DailyMotion" /><?php echo __('DailyMotion','wp-responsive-video-gallery-with-lightbox');?>&nbsp;&nbsp;
											</div>
											<div style="clear: both"></div>
											<div></div>
											<div style="clear: both"></div>
											<br />
											<div>
												<b><?php echo __('Video Url','wp-responsive-video-gallery-with-lightbox');?></b> <input type="text" id="videourl"
													class="url" tabindex="1" size="30" name="videourl"
													value="<?php echo $video_url; ?>">
											</div>
											<div style="clear: both"></div>
											<div></div>
											<div style="clear: both"></div>
										</div>
									</div>
									<div class="stuffbox" id="namediv" style="width: 100%">
										<h3>
											<label for="link_name"><?php echo __('Video Information','wp-responsive-video-gallery-with-lightbox');?></label>
										</h3>
										<div class="inside" id="fileuploaddiv">
                                                                                 <?php if ($image_name != "") { ?>
                                                                                        <div>
												<b><?php echo __('Current Image : ','wp-responsive-video-gallery-with-lightbox');?></b>
												<br/>
												<img id="img_disp" name="img_disp"
													src="<?php echo $baseurl . $image_name; ?>" />
											</div>
                                                                                        <?php }else{ ?>      
                                                                                            <img
												src="<?php echo plugins_url('/images/no-img.jpeg', __FILE__); ?>"
												id="img_disp" name="img_disp" />
                                                           
                                                                                        <?php } ?>
                                                                                        <br /> <a
												href="javascript:;" class="niks_media"
												id="videoFromExternalSite"  ><b><?php echo __('Click Here to get video information and thumbnail','wp-responsive-video-gallery-with-lightbox');?><span id='fromval'> From <?php echo $vtype;?></span>
											</b></a>&nbsp;<img
												src="<?php echo plugins_url('/images/ajax-loader.gif', __FILE__); ?>"
												style="display: none" id="loading_img" name="loading_img" />
											<div style="clear: both"></div>
											<div></div>
											<div class="uploader">
												<br /> <b style="margin-left: 50px;">OR</b>
												<div style="clear: both; margin-top: 15px;"></div>
                                                                                                <?php if (responsive_video_gallery_plus_responsive_lightbox_get_wp_version() >= 3.5) { ?>
                                                                                                    <a href="javascript:;" class="niks_media" id="myMediaUploader"><b><?php echo __('Click Here to upload custom video thumbnail','wp-responsive-video-gallery-with-lightbox');?></b></a>
                                                                                                <?php } ?>  
                                                                                                 <br /> <br />
												<div>
													<input id="HdnMediaSelection" name="HdnMediaSelection" type="hidden" value="<?php echo $HdnMediaSelection;?>" />
												</div>
												<div style="clear: both"></div>
												<div></div>
												<div style="clear: both"></div>

												<br />
											</div>
											<script>

                                                                                                function GetParameterValues(param,str) {
                                                                                                  var return_p='';  
                                                                                                  var url = str.slice(str.indexOf('?') + 1).split('&');
                                                                                                  for (var i = 0; i < url.length; i++) {
                                                                                                        var urlparam = url[i].split('=');
                                                                                                        if (urlparam[0] == param) {
                                                                                                         return_p= urlparam[1];
                                                                                                        }
                                                                                                    }
                                                                                                    return return_p;
                                                                                                }

                                                                                                var $n = jQuery.noConflict();

                                                                                                function UrlExists(url, cb){
                                                                                                    $n.ajax({
                                                                                                        url:      url,
                                                                                                        dataType: 'text',
                                                                                                        type:     'GET',
                                                                                                        complete:  function(xhr){
                                                                                                            if(typeof cb === 'function')
                                                                                                               cb.apply(this, [xhr.status]);
                                                                                                        }
                                                                                                    });
                                                                                                }

                                                                                                function getDailyMotionId(url) {
                                                                                                    
                                                                                                    if (url.indexOf("?") > 0) {
                                                                                                        url = url.substring(0, url.indexOf("?"));
                                                                                                   }
                                                                                                    var m = url.match(/^.+dailymotion.com\/(video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/);
                                                                                                    if (m !== null) {
                                                                                                        if(m[4] !== undefined) {
                                                                                                            return m[4];
                                                                                                        }
                                                                                                        return m[2];
                                                                                                    }
                                                                                                    return null;
                                                                                                }


                                                                                                $n(document).ready(function() {


                                                                                                $n("input:radio[name=vtype]").click(function() {


                                                                                                var value = $n(this).val();
                                                                                                        $n("#fromval").html(" from " + value);
                                                                                                });

                                                                                                $n("#videoFromExternalSite").click(function() {


                                                                                                var videoService = $n('input[name="vtype"]:checked').length;
                                                                                                        var videourlVal = $n.trim($n("#videourl").val());
                                                                                                        var flag = true;
                                                                                                        if (videourlVal == '' && videoService == 0){

                                                                                                alert('Please select video site.\nPlease enter video url.');
                                                                                                        $n("input:radio[name=vtype]").focus();
                                                                                                        flag = false;

                                                                                                }
                                                                                                else if (videoService == 0){

                                                                                                alert('Please select video site.');
                                                                                                        $n("input:radio[name=vtype]").focus();
                                                                                                        flag = false;
                                                                                                }
                                                                                                else if (videourlVal == ''){

                                                                                                alert('Please enter video url.');
                                                                                                        $n("#videourl").focus();
                                                                                                        flag = false;
                                                                                                }

                                                                                                if (flag){

                                                                                                    setTimeout(function() {
                                                                                                             $n("#loading_img").show();   
                                                                                                            }, 100);

                                                                                                var selectedRadio = $n('input[name=vtype]');
                                                                                                var checkedValueRadio = selectedRadio.filter(':checked').val();
                                                                                                if (checkedValueRadio == 'youtube') {
                                                                                                var vId = GetParameterValues('v', videourlVal);
                                                                                                if(vId!=''){


                                                                                                 var tumbnailImg='http://img.youtube.com/vi/'+vId+'/maxresdefault.jpg';

                                                                                                 var data = {
                                                                                                                    'action': 'check_file_exist',
                                                                                                                    'url': tumbnailImg,
                                                                                                                    'vNonce':'<?php echo $vNonce; ?>'
                                                                                                            };

                                                                                                            $n.post(ajaxurl, data, function(response) {



                                                                                                          var youtubeJsonUri='http://www.youtube.com/oembed?url=https://www.youtube.com/watch%3Fv='+vId+'&format=json';
                                                                                                           var data_youtube = {
                                                                                                                    'action': 'get_youtube_info',
                                                                                                                    'url': youtubeJsonUri,
                                                                                                                    'vid':vId,
                                                                                                                    'vNonce':'<?php echo $vNonce; ?>'
                                                                                                            };

                                                                                                          $n.post(ajaxurl, data_youtube, function(data) {

                                                                                                           data = $n.parseJSON(data);
                                                                                                           
                                                                                                            if(typeof data =='object'){    
                                                                                                                    if(typeof data =='object'){ 
                                                                                                                            
                                                                                                                         if(data.title!='' && data.title!=''){
                                                                                                                             $n("#videotitle").val(data.title); 
                                                                                                                         }
                                                                                                                         $n("#videotitleurl").val(videourlVal);
                                                                                                                         if(data.description!='' && data.description!=''){
                                                                                                                             $n("#video_description").val(data.description); 
                                                                                                                         }
                                                                                                                         if(response=='404' && data.thumbnail_url!=''){
                                                                                                                              tumbnailImg=data.thumbnail_url;
                                                                                                                         }
                                                                                                                         else{
                                                                                                                              tumbnailImg='http://img.youtube.com/vi/'+vId+'/0.jpg';
                                                                                                                          }

                                                                                                                         $n("#img_disp").attr('src', tumbnailImg);
                                                                                                                         $n("#HdnMediaSelection").val(tumbnailImg);
                                                                                                                         $n("#loading_img").hide();

                                                                                                                    }

                                                                                                                 }
                                                                                                                $n("#loading_img").hide();
                                                                                                            })  


                                                                                                             });
                                                                   
                                                                                                        }
                                                                                                        else{
                                                                                                            alert('Could not found such video');
                                                                                                            $n("#loading_img").hide();
                                                                                                        }
                                                                                                    }
                                                                                                    else if(checkedValueRadio == 'dailymotion'){

                                                                                                            var vid=getDailyMotionId(videourlVal);	
                                                                                                            var apiUrl='https://api.dailymotion.com/video/'+vid+'?fields=description,id,thumbnail_720_url,title';
                                                                                                            $n.getJSON( apiUrl, function( data ) {
                                                                                                                     if(typeof data =='object'){    


                                                                                                                             $n("#HdnMediaSelection").val(data.thumbnail_720_url);	
                                                                                                                             $n("#videotitle").val($n.trim(data.title));
                                                                                                                             $n("#videotitleurl").val(videourlVal);
                                                                                                                             $n("#img_disp").attr('src', data.thumbnail_720_url);
                                                                                                                             $n("#loading_img").hide();
                                                                                                                     }	 
                                                                                                                     $n("#loading_img").hide(); 
                                                                                                            })	


                                                                                                             $n("#loading_img").hide();
                                                                                                    }          

                                                                                                    $n("#loading_img").hide();
                                                                                                }

                                                                                                 setTimeout(function() {
                                                                                                             $n("#loading_img").hide();   
                                                                                                     }, 2000);    

                                                                                                });
                                                                                                        //uploading files variable
                                                                                                   var custom_file_frame;
                                                                                              $n("#myMediaUploader").click(function(event) {
                                                                                                event.preventDefault();
                                                                                                        //If the frame already exists, reopen it
                                                                                                        if (typeof (custom_file_frame) !== "undefined") {
                                                                                                custom_file_frame.close();
                                                                                                }

                                                                                                //Create WP media frame.
                                                                                                custom_file_frame = wp.media.frames.customHeader = wp.media({
                                                                                                //Title of media manager frame
                                                                                                title: "WP Media Uploader",
                                                                                                        library: {
                                                                                                        type: 'image'
                                                                                                        },
                                                                                                        button: {
                                                                                                        //Button text
                                                                                                        text: "Set Image"
                                                                                                        },
                                                                                                        //Do not allow multiple files, if you want multiple, set true
                                                                                                        multiple: false
                                                                                                });
                                                                                                        //callback for selected image
                                                                                                        custom_file_frame.on('select', function() {

                                                                                                    var attachment = custom_file_frame.state().get('selection').first().toJSON();
                                                                                                    var validExtensions = new Array();
                                                                                                    validExtensions[0] = 'jpg';
                                                                                                    validExtensions[1] = 'jpeg';
                                                                                                    validExtensions[2] = 'png';
                                                                                                    validExtensions[3] = 'gif';

                                                                                                    var inarr = parseInt($n.inArray(attachment.subtype, validExtensions));
                                                                                                      if (inarr > 0 && attachment.type.toLowerCase() == 'image'){

                                                                                                        var titleTouse = "";
                                                                                                        var imageDescriptionTouse = "";
                                                                                                         if ($n.trim(attachment.title) != ''){

                                                                                                             titleTouse = $n.trim(attachment.title);
                                                                                                        }
                                                                                                        else if ($n.trim(attachment.caption) != ''){

                                                                                                            titleTouse = $n.trim(attachment.caption);
                                                                                                        }

                                                                                                        if ($n.trim(attachment.description) != ''){

                                                                                                           imageDescriptionTouse = $n.trim(attachment.description);
                                                                                                        }
                                                                                                        else if ($n.trim(attachment.caption) != ''){

                                                                                                        imageDescriptionTouse = $n.trim(attachment.caption);
                                                                                                        }

                                                                                                       // $n("#videotitle").val(titleTouse);
                                                                                                      //  $n("#video_description").val(imageDescriptionTouse);

                                                                                                        if (attachment.id != ''){

                                                                                                                  $n("#HdnMediaSelection").val(attachment.url);
                                                                                                                  $n("#img_disp").attr('src', attachment.url);

                                                                                                            }

                                                                                                        }
                                                                                                        else{

                                                                                                          alert('Invalid image selection.');
                                                                                                        }
                                                                                                        //do something with attachment variable, for example attachment.filename
                                                                                                        //Object:
                                                                                                        //attachment.alt - image alt
                                                                                                        //attachment.author - author id
                                                                                                        //attachment.caption
                                                                                                        //attachment.dateFormatted - date of image uploaded
                                                                                                        //attachment.description
                                                                                                        //attachment.editLink - edit link of media
                                                                                                        //attachment.filename
                                                                                                        //attachment.height
                                                                                                        //attachment.icon - don't know WTF?))
                                                                                                        //attachment.id - id of attachment
                                                                                                        //attachment.link - public link of attachment, for example ""http://site.com/?attachment_id=115""
                                                                                                        //attachment.menuOrder
                                                                                                        //attachment.mime - mime type, for example image/jpeg"
                                                                                                        //attachment.name - name of attachment file, for example "my-image"
                                                                                                        //attachment.status - usual is "inherit"
                                                                                                        //attachment.subtype - "jpeg" if is "jpg"
                                                                                                        //attachment.title
                                                                                                        //attachment.type - "image"
                                                                                                        //attachment.uploadedTo
                                                                                                        //attachment.url - http url of image, for example "http://site.com/wp-content/uploads/2012/12/my-image.jpg"
                                                                                                        //attachment.width
                                                                                                        });
                                                                                                        //Open modal
                                                                                                        custom_file_frame.open();
                                                                                                });
                                                                                                })
                                                                                            </script>
										</div>
									</div>

									<div class="stuffbox" id="namediv" style="width: 100%">
										<h3>
											<label for="link_name"><?php echo __('Video Title','wp-responsive-video-gallery-with-lightbox');?> (<span
												style="font-size: 11px; font-weight: normal"><?php _e('Used into lightbox','wp-responsive-video-gallery-with-lightbox'); ?></span>)
											</label>
										</h3>
										<div class="inside">
											<div>
												<input type="text" id="videotitle" tabindex="1" size="30"
													name="videotitle" value="<?php echo $videotitle; ?>">
											</div>
											<div style="clear: both"></div>
											<div></div>
											<div style="clear: both"></div>
										</div>
									</div>
									<div class="stuffbox" id="namediv" style="width: 100%">
										<h3>
											<label for="link_name"><?php echo __('Video Title Url','wp-responsive-video-gallery-with-lightbox');?> (<span
												style="font-size: 11px; font-weight: normal"><?php _e(' click on title redirect to this url.Used in lightbox for video title','wp-responsive-video-gallery-with-lightbox'); ?></span>)
											</label>
										</h3>
										<div class="inside">
											<div>
												<input type="text" id="videotitleurl" class="url"
													tabindex="1" size="30" name="videotitleurl"
													value="<?php echo $videotitleurl; ?>">
											</div>
											<div style="clear: both"></div>
											<div></div>
											<div style="clear: both"></div>

										</div>
									</div>
							
									
                                                                          <?php if (isset($_GET['id']) and intval($_GET['id']) > 0) { ?> 
										 <input type="hidden" name="videoid" id="videoid" value="<?php echo (int) htmlentities(strip_tags($_GET['id']),ENT_QUOTES); ?>">
                                                                            <?php
										}
										?>
                                                                              <?php wp_nonce_field('action_image_add_edit','add_edit_image_nonce'); ?>    
                                                                             <input type="submit"
										onclick="" name="btnsave" id="btnsave" value="<?php echo __('Save Changes','wp-responsive-video-gallery-with-lightbox');?>"
										class="button-primary">&nbsp;&nbsp;<input type="button"
										name="cancle" id="cancle" value="<?php echo __('Cancel','wp-responsive-video-gallery-with-lightbox');?>"
										class="button-primary" onclick="location.href = 'admin.php?page=responsive_video_gallery_with_lightbox_video_management'">

								</form>
								<script type="text/javascript">

                                                                    var $n = jQuery.noConflict();
                                                                    $n(document).ready(function() {

                                                                     $n.validator.setDefaults({ 
                                                                         ignore: [],
                                                                         // any other default options and/or rules
                                                                     });

                                                                     $n("#addimage").validate({
                                                                     rules: {
                                                                     videotitle: {
                                                                     required:true,
                                                                             maxlength: 200
                                                                     },
                                                                             vtype: {
                                                                             required:true

                                                                             },
                                                                             videourl: {
                                                                             required:true,
                                                                                     url:true,
                                                                                     maxlength: 500
                                                                             },
                                                                             HdnMediaSelection:{
                                                                               required:true  
                                                                             },
                                                                             videotitleurl: {

                                                                             url:true,
                                                                              maxlength: 500
                                                                             }

                                                                     },
                                                                             errorClass: "image_error",
                                                                             errorPlacement: function(error, element) {
                                                                             error.appendTo(element.parent().next().next());
                                                                             }, messages: {
                                                                                 HdnMediaSelection: "Please select video thumbnail or Upload by wordpress media uploader.",

                                                                             }

                                                                         })
                                                                     });
                                                                             function validateFile(){

                                                                             var $n = jQuery.noConflict();
                                                                                     if ($n('#currImg').length > 0 || $n.trim($n("#HdnMediaSelection").val()) != ""){
                                                                             return true;
                                                                             }
                                                                             var fragment = $n("#image_name").val();
                                                                                     var filename = $n("#image_name").val().replace(/.+[\\\/]/, "");
                                                                                     var videoid = $n("#image_name").val();
                                                                                     if (videoid == ""){

                                                                             if (filename != "")
                                                                                     return true;
                                                                                     else
                                                                             {
                                                                             $n("#err_daynamic").remove();
                                                                                     $n("#image_name").after('<label class="image_error" id="err_daynamic">Please select file or use media manager to select file.</label>');
                                                                                     return false;
                                                                             }
                                                                             }
                                                                             else{
                                                                             return true;
                                                                             }
                                                                             }
                                                                     function reloadfileupload(){

                                                                     var $n = jQuery.noConflict();
                                                                             var fragment = $n("#image_name").val();
                                                                             var filename = $n("#image_name").val().replace(/.+[\\\/]/, "");
                                                                             var validExtensions = new Array();
                                                                             validExtensions[0] = 'jpg';
                                                                             validExtensions[1] = 'jpeg';
                                                                             validExtensions[2] = 'png';
                                                                             validExtensions[3] = 'gif';
                                                                             validExtensions[4] = 'bmp';
                                                                             validExtensions[5] = 'tif';
                                                                             var extension = filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase();
                                                                             var inarr = parseInt($n.inArray(extension, validExtensions));
                                                                             if (inarr < 0){

                                                                     $n("#err_daynamic").remove();
                                                                             $n('#fileuploaddiv').html($n('#fileuploaddiv').html());
                                                                             $n("#image_name").after('<label class="image_error" id="err_daynamic">Invalid file extension</label>');
                                                                     }
                                                                     else{
                                                                     $n("#err_daynamic").remove();
                                                                     }


                                                                     }
                                                                 </script>

							</div>
						</div>
					</div>
                   
				</div>
			</div>
          </div>
          </div>     
          <div id="postbox-container-1" class="postbox-container" > 

          <div class="postbox"> 
              <h3 class="hndle"><span></span><?php echo __('Access All Themes In One Price','wp-responsive-video-gallery-with-lightbox');?></h3> 
              <div class="inside">
                  <center><a href="http://www.elegantthemes.com/affiliates/idevaffiliate.php?id=11715_0_1_10" target="_blank"><img border="0" src="<?php echo plugins_url( 'images/300x250.gif', __FILE__ ) ;?>" width="250" height="250"></a></center>

                  <div style="margin:10px 5px">

                  </div>
              </div></div>
              <div class="postbox"> 
                <h3 class="hndle"><span></span><?php echo __('Google For Business Coupon','wp-responsive-video-gallery-with-lightbox');?></h3> 
                    <div class="inside">
                        <center><a href="https://goo.gl/OJBuHT" target="_blank">
                                <img src="<?php echo plugins_url( 'images/g-suite-promo-code-4.png', __FILE__ );?>" width="250" height="250" border="0">
                            </a></center>
                        <div style="margin:10px 5px">
                        </div>
                    </div>
                    
                </div>

      </div>   
         </div>
     </div>                 
<?php
          }
	} else if (strtolower ( $action ) == strtolower ( 'delete' )) {
		
            
               $retrieved_nonce = '';

                if(isset($_GET['nonce']) and $_GET['nonce']!=''){

                    $retrieved_nonce=$_GET['nonce'];

                }
                if (!wp_verify_nonce($retrieved_nonce, 'delete_image' ) ){


                    wp_die('Security check fail'); 
                }
                
                if ( ! current_user_can( 'rvg_responsive_video_gallery_delete_video' ) ) {

                    $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                    $responsive_video_gallery_plus_lightbox_messages=array();
                    $responsive_video_gallery_plus_lightbox_messages['type']='err';
                    $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                    update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                    echo "<script type='text/javascript'> location.href='$location';</script>";     
                    exit;   

                 }
                
		$uploads = wp_upload_dir ();
		$baseDir = $uploads ['basedir'];
		$baseDir = str_replace ( "\\", "/", $baseDir );
		$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
		
		
		$location = "admin.php?page=responsive_video_gallery_with_lightbox_video_management";
		$deleteId = ( int )  htmlentities(strip_tags($_GET ['id']),ENT_QUOTES);
		
		try {
			
			$query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox WHERE id=$deleteId";
			$myrow = $wpdb->get_row ( $query );
			
			if (is_object ( $myrow )) {
				
				$image_name = $myrow->image_name;
				$wpcurrentdir = dirname ( __FILE__ );
				$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
				$imagetoDel = $pathToImagesFolder . '/' . $image_name;
                                $settings=get_option('responsive_video_gallery_slider_settings');
                                $imageheight = $settings ['imageheight'];
                                $imagewidth = $settings ['imagewidth'];
				
                                $pInfo = pathinfo ( $myrow->HdnMediaSelection );
                                $ext = $pInfo ['extension'];

                                @unlink ( $imagetoDel );
                                @unlink($pathToImagesFolder.'/'.$myrow->vid . '_big_'.$imageheight.'_'.$imagewidth.'.'.$ext);

				
				$query = "delete from  " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox where id=$deleteId";
				$wpdb->query ( $query );
				
				$responsive_video_gallery_plus_lightbox_messages = array ();
				$responsive_video_gallery_plus_lightbox_messages ['type'] = 'succ';
				$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Video deleted successfully.','wp-responsive-video-gallery-with-lightbox');
				update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
			}
		} catch ( Exception $e ) {
			
			$responsive_video_gallery_plus_lightbox_messages = array ();
			$responsive_video_gallery_plus_lightbox_messages ['type'] = 'err';
			$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Error while deleting video.','wp-responsive-video-gallery-with-lightbox');
			update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
		}
		
		echo "<script type='text/javascript'> location.href='$location';</script>";
		exit ();
                
	} else if (strtolower ( $action ) == strtolower ( 'deleteselected' )) {
		
		  if(!check_admin_referer('action_settings_mass_delete','mass_delete_nonce')){

                        wp_die('Security check fail'); 
                  }
                    
                  if ( ! current_user_can( 'rvg_responsive_video_gallery_delete_video' ) ) {

                    $location='admin.php?page=responsive_video_gallery_with_lightbox_video_management';
                    $responsive_video_gallery_plus_lightbox_messages=array();
                    $responsive_video_gallery_plus_lightbox_messages['type']='err';
                    $responsive_video_gallery_plus_lightbox_messages['message']=__('Access Denied. Please contact your administrator.','wp-responsive-video-gallery-with-lightbox');
                    update_option('responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages);
                    echo "<script type='text/javascript'> location.href='$location';</script>";     
                    exit;   

                 }
                    
		$location = "admin.php?page=responsive_video_gallery_with_lightbox_video_management";
		
		if (isset ( $_POST ) and isset ( $_POST ['deleteselected'] ) and ($_POST ['action'] == 'delete' or $_POST ['action_upper'] == 'delete')) {
			
			$uploads = wp_upload_dir ();
			$baseDir = $uploads ['basedir'];
			$baseDir = str_replace ( "\\", "/", $baseDir );
			$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
			
			if (sizeof ( $_POST ['thumbnails'] ) > 0) {
				
				$deleteto = $_POST ['thumbnails'];
				$implode = implode ( ',', $deleteto );
				
				try {
					
                                       $settings=get_option('responsive_video_gallery_slider_settings');
                                       $imageheight = $settings ['imageheight'];
                                       $imagewidth = $settings ['imagewidth'];

					foreach ( $deleteto as $img ) {
						
                                                $img=intval($img);
						$query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox WHERE id=$img";
						$myrow = $wpdb->get_row ( $query );
						
						if (is_object ( $myrow )) {
							
							$image_name = $myrow->image_name;
							$wpcurrentdir = dirname ( __FILE__ );
							$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
                                                        $imagetoDel = $pathToImagesFolder . '/' . $image_name;
				
                                                        $pInfo = pathinfo ( $myrow->HdnMediaSelection );
                                                        $ext = $pInfo ['extension'];

                                                        @unlink ( $imagetoDel );
                                                        @unlink($pathToImagesFolder.'/'.$myrow->vid . '_big_'.$imageheight.'_'.$imagewidth.'.'.$ext);

				
										
							$query = "delete from  " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox where id=$img";
							$wpdb->query ( $query );
							
							$responsive_video_gallery_plus_lightbox_messages = array ();
							$responsive_video_gallery_plus_lightbox_messages ['type'] = 'succ';
							$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Selected videos deleted successfully.','wp-responsive-video-gallery-with-lightbox');
							update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
						}
					}
				} catch ( Exception $e ) {
					
					$responsive_video_gallery_plus_lightbox_messages = array ();
					$responsive_video_gallery_plus_lightbox_messages ['type'] = 'err';
					$responsive_video_gallery_plus_lightbox_messages ['message'] = __('Error while deleting videos.','wp-responsive-video-gallery-with-lightbox');
					update_option ( 'responsive_video_gallery_plus_lightbox_messages', $responsive_video_gallery_plus_lightbox_messages );
				}
				
				echo "<script type='text/javascript'> location.href='$location';</script>";
				exit ();
			} else {
				
				echo "<script type='text/javascript'> location.href='$location';</script>";
				exit ();
			}
		} else {
			
			echo "<script type='text/javascript'> location.href='$location';</script>";
			exit ();
		}
	}
}
function responsive_video_gallery_with_lightbox_video_preview_func() {
    
       if ( ! current_user_can( 'rvg_responsive_video_gallery_preview' ) ) {

            wp_die( __( "Access Denied", "wp-responsive-video-gallery-with-lightbox" ) );

       } 

	global $wpdb;
	$settings=get_option('responsive_video_gallery_slider_settings');
	
	$rand_Numb = uniqid ( 'thumnail_slider' );
	$rand_Num_td = uniqid ( 'divSliderMain' );
	$rand_var_name = uniqid ( 'rand_' );
	
	
	$wpcurrentdir = dirname ( __FILE__ );
	$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
	// $settings=get_option('thumbnail_slider_settings');
	
	$uploads = wp_upload_dir ();
	$baseDir = $uploads ['basedir'];
	$baseDir = str_replace ( "\\", "/", $baseDir );
	$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
	$baseurl = $uploads ['baseurl'];
	$baseurl .= '/wp-responsive-video-gallery-with-lightbox/';
	?>      
       <style type='text/css'>
        #<?php echo $rand_Num_td;?> .bx-wrapper .bx-viewport {background: none repeat scroll 0 0<?php echo $settings ['scollerBackground'];?> ! important;
                border: 0px none !important;
                box-shadow: 0 0 0 0 !important;
                /*padding:<?php echo $settings['imageMargin']; ?>px !important;*/
        }
        #poststuff #post-body.columns-2{margin-right: 0px}
        </style>
<?php
	$wpcurrentdir = dirname ( __FILE__ );
	$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
	$randOmeAlbName = uniqid ( 'alb_' );
	$randOmeRel = uniqid ( 'rel_' );
        
	?>
                <div style="width: 100%;">
				<div style="float: left; width: 100%;">
					<div class="wrap">
						<h2>Slider Preview</h2>
						
                            <?php if (is_array($settings)) { ?>
                                <div id="poststuff">
				 <div id="post-body" class="metabox-holder columns-2">
				   <div id="post-body-content">
					<div style="clear: both;"></div>
                                            <?php $url = plugin_dir_url(__FILE__); ?>           

                                            <div style="width: auto; postion: relative" id="<?php echo $rand_Num_td; ?>">
						 <div id="<?php echo $rand_Numb; ?>" class="responsiveSlider" style="margin-top: 2px !important; visibility: hidden;">
						<?php
                                                            global $wpdb;
                                                            $imageheight = $settings ['imageheight'];
                                                            $imagewidth = $settings ['imagewidth'];
                                                            $query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox  order by createdon desc";
                                                            $rows = $wpdb->get_results ( $query, 'ARRAY_A' );

                                                            if (count ( $rows ) > 0) {
                                                                 
                                                                foreach ( $rows as $row ) {

                                                                            $imagename = $row ['image_name'];
                                                                            $video_url = $row ['video_url'];
                                                                            $imageUploadTo = $pathToImagesFolder . '/' . $imagename;
                                                                            $imageUploadTo = str_replace ( "\\", "/", $imageUploadTo );
                                                                            $pathinfo = pathinfo ( $imageUploadTo );
                                                                            $filenamewithoutextension = $pathinfo ['filename'];
                                                                            $outputimg = "";

                                                                            $outputimgmain = $baseurl . $row ['image_name'];
                                                                            if ($settings ['resizeImages'] == 0) {

                                                                                    $outputimg = $baseurl . $row ['image_name'];
                                                                            } else {
                                                                                    $imagetoCheck = $pathToImagesFolder . '/' . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];

                                                                                    if (file_exists ( $imagetoCheck )) {
                                                                                            $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                                    } else {

                                                                                            if (function_exists ( 'wp_get_image_editor' )) {

                                                                                                    $image = wp_get_image_editor ( $pathToImagesFolder . "/" . $row ['image_name'] );

                                                                                                    if (! is_wp_error ( $image )) {
                                                                                                            $image->resize ( $imagewidth, $imageheight, true );
                                                                                                            $image->save ( $imagetoCheck );
                                                                                                            $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                                                    } else {
                                                                                                            $outputimg = $baseurl . $row ['image_name'];
                                                                                                    }
                                                                                            } else if (function_exists ( 'image_resize' )) {

                                                                                                    $return = image_resize ( $pathToImagesFolder . "/" . $row ['image_name'], $imagewidth, $imageheight );
                                                                                                    if (! is_wp_error ( $return )) {

                                                                                                            $isrenamed = rename ( $return, $imagetoCheck );
                                                                                                            if ($isrenamed) {
                                                                                                                    $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                                                            } else {
                                                                                                                    $outputimg = $baseurl . $row ['image_name'];
                                                                                                            }
                                                                                                    } else {
                                                                                                            $outputimg = $baseurl . $row ['image_name'];
                                                                                                    }
                                                                                            } else {

                                                                                                    $outputimg = $baseurl . $row ['image_name'];
                                                                                            }

                                                                                            // $url = plugin_dir_url(__FILE__)."imagestoscroll/".$filenamewithoutextension.'_'.$imageheight.'_'.$imagewidth.'.'.$pathinfo['extension'];
                                                                                    }
                                                                            }
                                                                              $embed_url=$row['embed_url'];

                                                                      $title="";
			                                              $rowTitle=$row['videotitle'];
			                                              $rowTitle=str_replace("'","’",$rowTitle); 
			                                              $rowTitle=str_replace('"','”',$rowTitle); 
			                                              
			                                              $rowDescrption=$row['video_description'];
			                                              $rowDescrption=str_replace("'","’",$rowDescrption); 
			                                              $rowDescrption=str_replace('"','”',$rowDescrption); 
                                                                      $rowDescrption=strip_tags($rowDescrption); 
			                                             
			                                              if(strlen($rowDescrption)>300){
                                                                          
                                                                        $rowDescrption=substr($rowDescrption,0,300)."..."; 
                                                                      }
			                                              //$openImageInNewTab='_blank';
			                                              $open_link_in=$row['open_link_in'];
			                                             // if($open_link_in==0){
			                                                $openImageInNewTab='_self';  
			                                               //}
			                                             
			                                              if(trim($row['videotitle'])!='' and trim($row['videotitleurl'])!=''){
			                                                  
			                                                   $title="<a class='Imglink' target='$openImageInNewTab' href='{$row['videotitleurl']}'>{$rowTitle}</a>";
			                                                   if($row['video_description']!=''){
			                                                    $title.="<div class='clear_description_'>{$rowDescrption}</div>";
			                                                   }
			                                              }
			                                              else if(trim($row['videotitle'])!='' and trim($row['videotitleurl'])==''){
			                                                  
			                                                 $title="<a class='Imglink' href='#'>{$rowTitle}</a>"; 
			                                                 if($row['video_description']!=''){
			                                                    $title.="<div class='clear_description_'>{$rowDescrption}</div>";
			                                                  }
			                                              }
			                                              else{
			                                                  
			                                                  if($row['video_description']!='')
			                                                     $title="<div class='clear_description_'>{$row['video_description']}</div>"; 
			                                              }
                                                                     
                                                                      
			                                           ?>
                                                                        <div>
                                                                                <a rel="<?php echo $randOmeRel;?>" data-overlay="1" data-title="<?php echo $title;?>" class="video_lbox" href="<?php echo $embed_url;?>">
                                                                              <img    src="<?php echo $outputimg; ?>" alt="<?php echo $rowTitle; ?>" title="<?php if(trim($rowDescrption)!=''){ echo $rowDescrption;} else{echo $rowTitle;}; ?>" />
                                                                             <span class="playbtnCss"></span>   
                                                                            </a> 
					                                             
                                                                        </div>
                                                                    
                                                        <?php } ?>   
                                                    <?php } ?>   
                                                </div>
			                  </div>
					  <script>
                                                        var $n = jQuery.noConflict();
                                                        var uniqObj=$n("a[rel^='<?php echo $randOmeRel;?>']");
                                                           
                                                       $n(document).ready(function(){
                                                           var <?php echo $rand_var_name; ?> = $n('#<?php echo $rand_Num_td; ?>').html();
                                                        $n('#<?php echo $rand_Numb; ?>').bxSlider({
                                                        <?php if($settings['visible']==1 ):?>
                                                             mode:'fade',
                                                         <?php endif;?>
                                                                slideWidth: <?php echo $settings['imagewidth']; ?>,
                                                        minSlides: <?php echo $settings['min_visible']; ?>,
                                                        maxSlides: <?php echo $settings['visible']; ?>,
                                                        moveSlides: <?php echo $settings['scroll']; ?>,
                                                        slideMargin:<?php echo $settings['imageMargin']; ?>,
                                                        speed:<?php echo $settings['speed']; ?>,
                                                        pause:<?php echo $settings['pause']; ?>,
                                                        <?php if($settings['pauseonmouseover'] and ($settings['auto']==1 or $settings['auto']==2) ){ ?>
                                                              autoHover: true,
                                                            <?php
                                                                } else {
                                                                        if ($settings['auto']==1 or $settings['auto']==2) {
                                                             ?>
                                                           autoHover:false,
                                                           <?php
                                                                }
                                                            }
                                                         ?>
                                                        <?php if ($settings['auto']==1): ?>
                                                            controls:false,
                                                        <?php else: ?>
                                                            controls:true,
                                                        <?php endif; ?>
                                                            useCSS:false,
                                                   <?php if($settings['auto']==1 or $settings['auto']==2):?>
                                                            autoStart:true,
                                                            autoDelay:200,
                                                            auto:true,
                                                         <?php endif; ?>
                                                  <?php if ($settings['circular']): ?>
                                                            infiniteLoop: true,
                                                  <?php else: ?>
                                                            infiniteLoop: false,
                                                  <?php endif; ?>
                                                  <?php if($settings['show_pager']):?>
                                                     pager:true, 
                                                   <?php else:?>
                                                     pager:false,
                                                   <?php endif;?>
                                                   <?php if($settings['show_caption']):?>
                                                     captions:true, 
                                                   <?php else:?>
                                                     captions:false,
                                                   <?php endif;?>    
                                                    onSliderLoad: function(){
                                   
                                                       $n("#<?php echo $rand_Numb; ?>").css("visibility", "visible");
                                                        $n(".video_lbox").fancybox_vgl({
                                                        'type'    : "iframe",
                                                        'overlayColor':'#000000',
                                                         'padding': 10,
                                                         'autoScale': true,
                                                         'autoDimensions':true,
                                                         'transitionIn': 'none',
                                                         'uniqObj':uniqObj,
                                                         'transitionOut': 'none',
                                                         'titlePosition': 'outside',
                                                         <?php if ($settings['circular']): ?>
                                                         'cyclic':true,
                                                        <?php else: ?>
                                                         'cyclic':false,
                                                        <?php endif; ?>
                                                         'hideOnContentClick':false,
                                                         'width' : 650,
                                                         'height' : 400,
                                                         'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                                             var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                                             var isoverlay = $n(currtElem).attr('data-overlay')

                                                            if(isoverlay=="1" && $n.trim(title)!=""){
                                                             return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                                            }
                                                            else{
                                                                return '';
                                                            }

                                                            },

                                                       });
                                                     
                                     
                                  
                                     
                                                      }               

                                                });
                                                 $n("#<?php echo $rand_Numb; ?>").show();
                                                   <?php if ($settings['auto']) { ?>
            						 <?php $newrand = rand(0, 1111111111); ?>
                                                           var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') > - 1;
                                                            var is_android = navigator.userAgent.toLowerCase().indexOf('android') > - 1;
                                                            var is_iphone = navigator.userAgent.toLowerCase().indexOf('iphone') > - 1;
                                                            var width = $n(window).width();
                                                          if (is_firefox && (is_android || is_iphone)){

		                                                    } else{
		                                                    var timer;
		                                                            $n(window).bind('resize', function(){
		                                                    if ($n(window).width() != width){
		
		                                                    width = $n(window).width();
		                                                            timer && clearTimeout(timer);
		                                                            timer = setTimeout(onResize<?php echo $newrand; ?>, 600);
		                                                    }
		                                                    });
		                                            }

                                                    function onResize<?php echo $newrand; ?>(){
                                                    $n('#<?php echo $rand_Num_td; ?>').html('');
                                                            $n('#<?php echo $rand_Num_td; ?>').html(<?php echo $rand_var_name; ?>);
                                                            $n('#<?php echo $rand_Numb; ?>').bxSlider({

                                                            	<?php if($settings['visible']==1 ):?>
                                                                mode:'fade',
                                                               <?php endif;?>
                                                   		    	 slideWidth: <?php echo $settings['imagewidth']; ?>,
                                                            	 minSlides: <?php echo $settings['min_visible']; ?>,
                                                            	 maxSlides: <?php echo $settings['visible']; ?>,
                                                            	 moveSlides: <?php echo $settings['scroll']; ?>,
                                                            	 slideMargin:<?php echo $settings['imageMargin']; ?>,
                                                             	 speed:<?php echo $settings['speed']; ?>,
                                                            	 pause:<?php echo $settings['pause']; ?>,
                                                                <?php if($settings['pauseonmouseover'] and ($settings['auto']==1 or $settings['auto']==2) ){ ?>
                                                        	  autoHover: true,
                						<?php
								  } 
                                                                  else {
									if ($settings['auto']==1 or $settings['auto']==2) {
								   ?>
                                                                	autoHover:false,
                    						   <?php
									}
								   }
							          ?>
            							  <?php if ($settings['auto']==1): ?>
                                                        	    controls:false,
            							 <?php else: ?>
                                                        	    controls:true,
            							 <?php endif; ?>
                                                    		   pager:false,
                                                            	   useCSS:false,
            							 <?php if ($settings['auto']==1 or $settings['auto']==2): ?>
                                                        	   autoStart:true,
                                                                   autoDelay:200,
                                                                   auto:true,
            							 <?php endif; ?>
            							 <?php if ($settings['circular']): ?>
                                                        	   infiniteLoop: true,
            							 <?php else: ?>
                                                       		   infiniteLoop: false,
            						         <?php endif; ?>
            							 <?php if($settings['show_pager']):?>
                                                                    pager:true, 
                                                                  <?php else:?>
                                                                    pager:false,
                                                                  <?php endif;?>
                                                                  <?php if($settings['show_caption']):?>
                                                                    captions:true, 
                                                                  <?php else:?>
                                                                    captions:false,
                                                                  <?php endif;?> 
            							   onSliderLoad: function(){
                                   
                                                                        $n("#<?php echo $rand_Numb; ?>").css("visibility", "visible");
                                                                         $n(".video_lbox").fancybox_vgl({
                                                                         'type'    : "iframe",
                                                                         'overlayColor':'#000000',
                                                                          'padding': 10,
                                                                          'autoScale': true,
                                                                          'autoDimensions':true,
                                                                          'transitionIn': 'none',
                                                                          'uniqObj':uniqObj,
                                                                          'transitionOut': 'none',
                                                                          'titlePosition': 'outside',
                                                                          <?php if ($settings['circular']): ?>
                                                                          'cyclic':true,
                                                                         <?php else: ?>
                                                                          'cyclic':false,
                                                                         <?php endif; ?>
                                                                          'hideOnContentClick':false,
                                                                          'width' : 650,
                                                                          'height' : 400,
                                                                          'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                                                              var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                                                              var isoverlay = $n(currtElem).attr('data-overlay')

                                                                             if(isoverlay=="1" && $n.trim(title)!=""){
                                                                              return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                                                             }
                                                                             else{
                                                                                 return '';
                                                                             }

                                                                             },

                                                                        });




                                                                       } 

                                                    	});
                                                            
                                                    }

                                                    <?php } ?>

			        		 
                                                            window.rebind<?php echo $rand_Numb;?> = function() {

                                                                    $n(".video_lbox").fancybox_vgl({
                                                                   'type'    : "iframe",
                                                                   'overlayColor':'#000000',
                                                                    'padding': 10,
                                                                    'autoScale': true,
                                                                    'autoDimensions':true,
                                                                    'transitionIn': 'none',
                                                                    'uniqObj':uniqObj,
                                                                    'transitionOut': 'none',
                                                                    'titlePosition': 'outside',
                                                                    <?php if ($settings['circular']): ?>
                                                                    'cyclic':true,
                                                                   <?php else: ?>
                                                                    'cyclic':false,
                                                                   <?php endif; ?>
                                                                    'hideOnContentClick':false,
                                                                    'width' : 650,
                                                                    'height' : 400,
                                                                    'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                                                        var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                                                        var isoverlay = $n(currtElem).attr('data-overlay')

                                                                       if(isoverlay=="1" && $n.trim(title)!=""){
                                                                        return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                                                       }
                                                                       else{
                                                                           return '';
                                                                       }

                                                                       },

                                                                  });

                                                            }
                                                 
		
		                                    });
                                             </script>
		
					</div>
				   </div>
				</div>  
                            <?php } ?>
                        </div>
				</div>
				<div class="clear"></div>
			</div>
                <?php if (is_array($settings)) { ?>

                    <h3><?php echo __('To print this video carousel into WordPress Post/Page use below code','wp-responsive-video-gallery-with-lightbox');?></h3>
                            <input type="text" value='[print_responsive_video_gallery_plus_lightbox] '
                                    style="width: 400px; height: 30px"
                                    onclick="this.focus(); this.select()" />
                            <div class="clear"></div>
                            <h3><?php echo __('To print this video carousel into WordPress theme/template PHP files use below code','wp-responsive-video-gallery-with-lightbox');?></h3>
                    <?php
			$shortcode = '[print_responsive_video_gallery_plus_lightbox]';
		    ?>
                    <input type="text" value="&lt;?php echo do_shortcode('<?php echo htmlentities($shortcode, ENT_QUOTES); ?>'); ?&gt;" style="width: 400px; height: 30px" onclick="this.focus(); this.select()" />
                <?php } ?>
                <div class="clear"></div>
 <?php
   }
function print_responsive_video_gallery_plus_lightbox_func($atts) {
    
    
        wp_enqueue_style('wp-video-gallery-lighbox-style');
        wp_enqueue_style('vl-box-css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('video-gallery-jc');
        wp_enqueue_script('effects-video-plus-light-box');
        wp_enqueue_script('vl-box-js');

            
        ob_start();
        global $wpdb;

        $settings=get_option('responsive_video_gallery_slider_settings');
	$rand_Numb = uniqid ( 'thumnail_slider' );
	$rand_Num_td = uniqid ( 'divSliderMain' );
	$rand_var_name = uniqid ( 'rand_' );
	
	
	$wpcurrentdir = dirname ( __FILE__ );
	$wpcurrentdir = str_replace ( "\\", "/", $wpcurrentdir );
	// $settings=get_option('thumbnail_slider_settings');
	
	$uploads = wp_upload_dir ();
	$baseDir = $uploads ['basedir'];
	$baseDir = str_replace ( "\\", "/", $baseDir );
	$pathToImagesFolder = $baseDir . '/wp-responsive-video-gallery-with-lightbox';
	$baseurl = $uploads ['baseurl'];
	$baseurl .= '/wp-responsive-video-gallery-with-lightbox/';
        $randOmeRel = uniqid ( 'rel_' );
        $randOmVlBox=  uniqid('video_lbox_');
	?><!-- print_responsive_video_gallery_plus_lightbox_func --><style type='text/css'>
        #<?php echo $rand_Num_td;?> .bx-wrapper .bx-viewport {
                background: none repeat scroll 0 0<?php echo $settings ['scollerBackground'];?> ! important;
                border: 0px none !important;
                box-shadow: 0 0 0 0 !important;
                /*padding:<?php echo $settings['imageMargin']; ?>px !important;*/
        }
        </style>	
      <?php
      
          if (is_array($settings)) 
              
              { ?>
                             <div style="clear: both;"></div>
                                 <?php $url = plugin_dir_url(__FILE__); ?>           

                                 <div style="width: auto; postion: relative" id="<?php echo $rand_Num_td; ?>">
                                      <div id="<?php echo $rand_Numb; ?>" class="responsiveSlider" style="margin-top: 2px !important; visibility: hidden;">
                                     <?php
                                                 global $wpdb;
                                                 $imageheight = $settings ['imageheight'];
                                                 $imagewidth = $settings ['imagewidth'];
                                                 $query = "SELECT * FROM " . $wpdb->prefix . "responsive_video_gallery_plus_responsive_lightbox order by createdon desc";
                                                 $rows = $wpdb->get_results ( $query, 'ARRAY_A' );

                                                 if (count ( $rows ) > 0) {
                                                         foreach ( $rows as $row ) {

                                                                 $imagename = $row ['image_name'];
                                                                 $video_url = $row ['video_url'];
                                                                 $imageUploadTo = $pathToImagesFolder . '/' . $imagename;
                                                                 $imageUploadTo = str_replace ( "\\", "/", $imageUploadTo );
                                                                 $pathinfo = pathinfo ( $imageUploadTo );
                                                                 $filenamewithoutextension = $pathinfo ['filename'];
                                                                 $outputimg = "";

                                                                 $outputimgmain = $baseurl . $row ['image_name'];
                                                                 if ($settings ['resizeImages'] == 0) {

                                                                         $outputimg = $baseurl . $row ['image_name'];
                                                                 } else {
                                                                         $imagetoCheck = $pathToImagesFolder . '/' . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];

                                                                         if (file_exists ( $imagetoCheck )) {
                                                                                 $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                         } else {

                                                                                 if (function_exists ( 'wp_get_image_editor' )) {

                                                                                         $image = wp_get_image_editor ( $pathToImagesFolder . "/" . $row ['image_name'] );

                                                                                         if (! is_wp_error ( $image )) {
                                                                                                 $image->resize ( $imagewidth, $imageheight, true );
                                                                                                 $image->save ( $imagetoCheck );
                                                                                                 $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                                         } else {
                                                                                                 $outputimg = $baseurl . $row ['image_name'];
                                                                                         }
                                                                                 } else if (function_exists ( 'image_resize' )) {

                                                                                         $return = image_resize ( $pathToImagesFolder . "/" . $row ['image_name'], $imagewidth, $imageheight );
                                                                                         if (! is_wp_error ( $return )) {

                                                                                                 $isrenamed = rename ( $return, $imagetoCheck );
                                                                                                 if ($isrenamed) {
                                                                                                         $outputimg = $baseurl . $filenamewithoutextension . '_' . $imageheight . '_' . $imagewidth . '.' . $pathinfo ['extension'];
                                                                                                 } else {
                                                                                                         $outputimg = $baseurl . $row ['image_name'];
                                                                                                 }
                                                                                         } else {
                                                                                                 $outputimg = $baseurl . $row ['image_name'];
                                                                                         }
                                                                                 } else {

                                                                                         $outputimg = $baseurl . $row ['image_name'];
                                                                                 }

                                                                                 // $url = plugin_dir_url(__FILE__)."imagestoscroll/".$filenamewithoutextension.'_'.$imageheight.'_'.$imagewidth.'.'.$pathinfo['extension'];
                                                                         }
                                                                 }
                                                                   $embed_url=$row['embed_url'];

                                                           $title="";
                                                           $rowTitle=$row['videotitle'];
                                                           $rowTitle=str_replace("'","’",$rowTitle); 
                                                           $rowTitle=str_replace('"','”',$rowTitle); 

                                                           $rowDescrption=$row['video_description'];
                                                           $rowDescrption=str_replace("'","’",$rowDescrption); 
                                                           $rowDescrption=str_replace('"','”',$rowDescrption); 
                                                           $rowDescrption=strip_tags($rowDescrption); 

                                                           if(strlen($rowDescrption)>300){

                                                             $rowDescrption=substr($rowDescrption,0,300)."..."; 
                                                           }
                                                          // $openImageInNewTab='_blank';
                                                          $open_link_in=$row['open_link_in'];
                                                           //if($open_link_in==0){
                                                             $openImageInNewTab='_self';  
                                                            //}

                                                           if(trim($row['videotitle'])!='' and trim($row['videotitleurl'])!=''){

                                                                $title="<a class='Imglink' target='$openImageInNewTab' href='{$row['videotitleurl']}'>{$rowTitle}</a>";
                                                                if($row['video_description']!=''){
                                                                 $title.="<div class='clear_description_'>{$rowDescrption}</div>";
                                                                }
                                                           }
                                                           else if(trim($row['videotitle'])!='' and trim($row['videotitleurl'])==''){

                                                              $title="<a class='Imglink' href='#'>{$rowTitle}</a>"; 
                                                              if($row['video_description']!=''){
                                                                 $title.="<div class='clear_description_'>{$rowDescrption}</div>";
                                                               }
                                                           }
                                                           else{

                                                               if($row['video_description']!='')
                                                                  $title="<div class='clear_description_'>{$row['video_description']}</div>"; 
                                                           }


                                                        ?>
                                                         <div>
                                                            <a rel="<?php echo $randOmeRel;?>" data-overlay="1" data-title="<?php echo htmlentities($title);?>" class="<?php echo $randOmVlBox;?>" href="<?php echo $embed_url;?>">
                                                                <img    src="<?php echo $outputimg; ?>" alt="<?php echo $rowTitle; ?>" title="<?php if(trim($rowDescrption)!=''){ echo $rowDescrption;} else{echo $rowTitle;}; ?>" />
                                                                <span class="playbtnCss">
                                                                 </span> 
                                                                 
                                                            </a> 

                                                        </div>
                                                    
                                             <?php } ?>   
                                         <?php } ?>   
                                     </div>
                               </div>
                            <script>
                            
                          
                                    <?php $intval= uniqid('interval_');?>

                                   var <?php echo $intval;?> = setInterval(function() {

                                   if(document.readyState === 'complete') {

                                         clearInterval(<?php echo $intval;?>);
                                      
                                          var $n = jQuery.noConflict();
                                          <?php $uniqId=uniqid();?>
                                          var uniqObj<?php echo $uniqId?>=$n("a[rel^='<?php echo $randOmeRel;?>']");
                                          var <?php echo $rand_var_name; ?> = $n('#<?php echo $rand_Num_td; ?>').html();
                                          $n('#<?php echo $rand_Numb; ?>').bxSlider({
                                          <?php if($settings['visible']==1 ):?>
                                               mode:'fade',
                                           <?php endif;?>
                                                  slideWidth: <?php echo $settings['imagewidth']; ?>,
                                          minSlides: <?php echo $settings['min_visible']; ?>,
                                          maxSlides: <?php echo $settings['visible']; ?>,
                                          moveSlides: <?php echo $settings['scroll']; ?>,
                                          slideMargin:<?php echo $settings['imageMargin']; ?>,
                                          speed:<?php echo $settings['speed']; ?>,
                                          pause:<?php echo $settings['pause']; ?>,
                                         <?php if($settings['pauseonmouseover'] and ($settings['auto']==1 or $settings['auto']==2) ){ ?>
                                                autoHover: true,
                                              <?php
                                                  } else {
                                                          if ($settings['auto']==1 or $settings['auto']==2) {
                                               ?>
                                             autoHover:false,
                                             <?php
                                                  }
                                              }
                                           ?>
                                          <?php if ($settings['auto']==1): ?>
                                              controls:false,
                                          <?php else: ?>
                                              controls:true,
                                          <?php endif; ?>
                                              pager:false,
                                              useCSS:false,
                                     <?php if($settings['auto']==1 or $settings['auto']==2):?>
                                              autoStart:true,
                                              autoDelay:200,
                                              auto:true,
                                           <?php endif; ?>
                                    <?php if ($settings['circular']): ?>
                                              infiniteLoop: true,
                                    <?php else: ?>
                                              infiniteLoop: false,
                                    <?php endif; ?>
                                    <?php if($settings['show_pager']):?>
                                    pager:true, 
                                  <?php else:?>
                                    pager:false,
                                  <?php endif;?>
                                  <?php if($settings['show_caption']):?>
                                    captions:true, 
                                  <?php else:?>
                                    captions:false,
                                  <?php endif;?>     
                                  onSliderLoad: function(){
                                   
                                     $n("#<?php echo $rand_Numb; ?>").css("visibility", "visible");
                                     
                                     $n(".<?php echo $randOmVlBox;?>").fancybox_vgl({
                                            'type'    : "iframe",
                                            'overlayColor':'#000000',
                                             'padding': 10,
                                             'autoScale': true,
                                             'autoDimensions':true,
                                             'uniqObj':uniqObj<?php echo $uniqId;?>,
                                             'transitionIn': 'none',
                                             'transitionOut': 'none',
                                             'titlePosition': 'outside',
                                             <?php if ($settings['circular']): ?>
                                                   'cyclic':true,
                                                  <?php else: ?>
                                                   'cyclic':false,
                                                  <?php endif; ?>
                                             'hideOnContentClick':false,
                                             'width' : 650,
                                              'height' : 400,
                                             'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                               var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                               var isoverlay = $n(currtElem).attr('data-overlay')

                                              if(isoverlay=="1" && $n.trim(title)!=""){
                                               return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                              }
                                              else{
                                                  return '';
                                              }

                                           },

                                        }); 
                                     
                                  }                                                         
                                  
                                 
                                  });
                                 
                                     <?php if ($settings['auto']) { ?>
                                           <?php $newrand = rand(0, 1111111111); ?>
                                             var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') > - 1;
                                              var is_android = navigator.userAgent.toLowerCase().indexOf('android') > - 1;
                                              var is_iphone = navigator.userAgent.toLowerCase().indexOf('iphone') > - 1;
                                              var width = $n(window).width();
                                            if (is_firefox && (is_android || is_iphone)){

                                                      } else{
                                                      var timer;
                                                              $n(window).bind('resize', function(){
                                                      if ($n(window).width() != width){

                                                      width = $n(window).width();
                                                              timer && clearTimeout(timer);
                                                              timer = setTimeout(onResize<?php echo $newrand; ?>, 600);
                                                      }
                                                      });
                                              }

                                      function onResize<?php echo $newrand; ?>(){
                                      $n('#<?php echo $rand_Num_td; ?>').html('');
                                              $n('#<?php echo $rand_Num_td; ?>').html(<?php echo $rand_var_name; ?>);
                                              $n('#<?php echo $rand_Numb; ?>').bxSlider({

                                                  <?php if($settings['visible']==1 ):?>
                                                  mode:'fade',
                                                 <?php endif;?>
                                                   slideWidth: <?php echo $settings['imagewidth']; ?>,
                                                   minSlides: <?php echo $settings['min_visible']; ?>,
                                                   maxSlides: <?php echo $settings['visible']; ?>,
                                                   moveSlides: <?php echo $settings['scroll']; ?>,
                                                   slideMargin:<?php echo $settings['imageMargin']; ?>,
                                                   speed:<?php echo $settings['speed']; ?>,
                                                   pause:<?php echo $settings['pause']; ?>,
                                                  <?php if($settings['pauseonmouseover'] and ($settings['auto']==1 or $settings['auto']==2) ){ ?>
                                                    autoHover: true,
                                                  <?php
                                                    } 
                                                    else {
                                                          if ($settings['auto']==1 or $settings['auto']==2) {
                                                     ?>
                                                          autoHover:false,
                                                     <?php
                                                          }
                                                     }
                                                    ?>
                                                    <?php if ($settings['auto']==1): ?>
                                                      controls:false,
                                                   <?php else: ?>
                                                      controls:true,
                                                   <?php endif; ?>
                                                     pager:false,
                                                     useCSS:false,
                                                   <?php if($settings['auto']==1 or $settings['auto']==2):?>
                                                     autoStart:true,
                                                     autoDelay:200,
                                                     auto:true,
                                                   <?php endif; ?>
                                                   <?php if ($settings['circular']): ?>
                                                     infiniteLoop: true,
                                                   <?php else: ?>
                                                     infiniteLoop: false,
                                                   <?php endif; ?>
                                                   <?php if($settings['show_pager']):?>
                                                     pager:true, 
                                                   <?php else:?>
                                                     pager:false,
                                                   <?php endif;?>
                                                   <?php if($settings['show_caption']):?>
                                                     captions:true, 
                                                   <?php else:?>
                                                     captions:false,
                                                   <?php endif;?> 
                                                     onSliderLoad: function(){
                                   
                                                                $n("#<?php echo $rand_Numb; ?>").css("visibility", "visible");

                                                                $n(".<?php echo $randOmVlBox;?>").fancybox_vgl({
                                                                       'type'    : "iframe",
                                                                       'overlayColor':'#000000',
                                                                        'padding': 10,
                                                                        'autoScale': true,
                                                                        'autoDimensions':true,
                                                                        'uniqObj':uniqObj<?php echo $uniqId;?>,
                                                                        'transitionIn': 'none',
                                                                        'transitionOut': 'none',
                                                                        'titlePosition': 'outside',
                                                                        <?php if ($settings['circular']): ?>
                                                                              'cyclic':true,
                                                                             <?php else: ?>
                                                                              'cyclic':false,
                                                                             <?php endif; ?>
                                                                        'hideOnContentClick':false,
                                                                        'width' : 650,
                                                                         'height' : 400,
                                                                        'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                                                          var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                                                          var isoverlay = $n(currtElem).attr('data-overlay')

                                                                         if(isoverlay=="1" && $n.trim(title)!=""){
                                                                          return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                                                         }
                                                                         else{
                                                                             return '';
                                                                         }

                                                                      },

                                                                   }); 

                                                             }        

                                          });
                                              $n("#<?php echo $rand_Numb; ?>").css("visibility", "visible");
                                      }

                                <?php } ?>

                                  
                                   window.rebind<?php echo $rand_Numb;?> = function() {

                                          $n(".<?php echo $randOmVlBox;?>").fancybox_vgl({
                                                   'type'    : "iframe",
                                                   'overlayColor':'#000000',
                                                    'padding': 10,
                                                    'autoScale': true,
                                                    'autoDimensions':true,
                                                    'uniqObj':uniqObj<?php echo $uniqId;?>,
                                                    'transitionIn': 'none',
                                                    'transitionOut': 'none',
                                                    'titlePosition': 'outside',
                                                    <?php if ($settings['circular']): ?>
                                                          'cyclic':true,
                                                         <?php else: ?>
                                                          'cyclic':false,
                                                         <?php endif; ?>
                                                    'hideOnContentClick':false,
                                                    'width' : 650,
                                                     'height' : 400,
                                                    'titleFormat': function(title, currentArray, currentIndex, currentOpts) {

                                                      var currtElem = $n('#<?php echo $rand_Numb; ?> a[href="'+currentOpts.href+'"]');

                                                      var isoverlay = $n(currtElem).attr('data-overlay')

                                                     if(isoverlay=="1" && $n.trim(title)!=""){
                                                      return '<span id="fancybox_vgl-title-over">' + title  + '</span>';
                                                     }
                                                     else{
                                                         return '';
                                                     }

                                                  },

                                               }); 

                                      }

                                 }    
                            }, 100);

                              
                                      
                                      
                           </script><!-- end print_responsive_video_gallery_plus_lightbox_func --><?php } 
	$output = ob_get_clean ();
	return $output;
}
function responsive_video_gallery_plus_responsive_lightbox_get_wp_version() {
	global $wp_version;
	return $wp_version;
}

// also we will add an option function that will check for plugin admin page or not
function responsive_video_gallery_plus_lightbox_is_plugin_page() {
	$server_uri = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	
	foreach ( array (
			'responsive_video_gallery_with_lightbox' 
	) as $allowURI ) {
		if (stristr ( $server_uri, $allowURI ))
			return true;
	}
	return false;
}

// add media WP scripts
function responsive_video_gallery_plus_lightbox_admin_scripts_init() {
	if (responsive_video_gallery_plus_lightbox_is_plugin_page ()) {
		// double check for WordPress version and function exists
		if (function_exists ( 'wp_enqueue_media' ) && version_compare ( responsive_video_gallery_plus_responsive_lightbox_get_wp_version (), '3.5', '>=' )) {
			// call for new media manager
			wp_enqueue_media ();
		}
		wp_enqueue_style ( 'media' );
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_script( 'wp-color-picker' );

	}
}


function wrvgwl_remove_extra_p_tags($content){

        if(strpos($content, 'print_responsive_video_gallery_plus_lightbox_func')!==false){
        
            
            $pattern = "/<!-- print_responsive_video_gallery_plus_lightbox_func -->(.*)<!-- end print_responsive_video_gallery_plus_lightbox_func -->/Uis"; 
            $content = preg_replace_callback($pattern, function($matches) {


               $altered = str_replace("<p>","",$matches[1]);
               $altered = str_replace("</p>","",$altered);
              
                $altered=str_replace("&#038;","&",$altered);
                $altered=str_replace("&#8221;",'"',$altered);
              

              return @str_replace($matches[1], $altered, $matches[0]);
            }, $content);

              
            
        }
        
        $content = str_replace("<p><!-- print_responsive_video_gallery_plus_lightbox_func -->","<!-- print_responsive_video_gallery_plus_lightbox_func -->",$content);
        $content = str_replace("<!-- end print_responsive_video_gallery_plus_lightbox_func --></p>","<!-- end print_responsive_video_gallery_plus_lightbox_func -->",$content);
        
        
        return $content;
  }

  add_filter('widget_text_content', 'wrvgwl_remove_extra_p_tags', 999);
  add_filter('the_content', 'wrvgwl_remove_extra_p_tags', 999);


?>