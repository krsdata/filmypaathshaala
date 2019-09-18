<?php global $video_grid_count, $video_grid_filter, $video_grid_set_list;
$real_post = $post;
$ent_attrs = get_option('youtube_showcase_attr_list');
?>
<a href="<?php echo get_permalink(); ?>" title="<?php echo get_the_title(); ?>">
<div class="col-lg-3 col-sm-4 col-xs-6">
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
</a>