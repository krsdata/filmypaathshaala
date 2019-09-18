<?php global $video_indicators_count, $video_indicators_filter, $video_indicators_set_list;
$real_post = $post;
$ent_attrs = get_option('youtube_showcase_attr_list');
?>
<div style="cursor: pointer;" class="col-lg-3 col-sm-4 col-xs-6 <?php echo (($video_indicators_count == 0) ? 'active' : ''); ?>" data-slide-to="<?php echo $video_indicators_count; ?>" data-target="#emdvideos" onclick="[removed]='#emdvideos'">
<div class="panel panel-info item-video">
  <div class="panel-body emd-vid">
            <div class="thumbnail" style="padding:0;border: 0;border-bottom-left-radius: 0;border-bottom-right-radius: 0;">
                <img style="width:100%;height:auto;" src="https://img.youtube.com/vi/<?php echo esc_html(emd_mb_meta('emd_video_key')); ?>
/<?php echo ((emd_get_attr_val('youtube_showcase', $post->ID, 'emd_video', 'emd_video_thumbnail_resolution', 'key')) ? "" . emd_get_attr_val('youtube_showcase', $post->ID, 'emd_video', 'emd_video_thumbnail_resolution', 'key') . "" : "mq"); ?>default.jpg" alt="<?php echo get_the_title(); ?>" />
            </div>
  </div>
  <div class="panel-footer" style="background-color: rgba(0, 0, 0, 0);"><?php echo get_the_title(); ?></div>
</div>
 </div>


