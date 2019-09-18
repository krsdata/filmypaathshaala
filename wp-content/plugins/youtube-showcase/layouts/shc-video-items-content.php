<?php global $video_items_count, $video_items_filter, $video_items_set_list;
$real_post = $post;
$ent_attrs = get_option('youtube_showcase_attr_list');
?>
<div id="emd-videos" class="item <?php echo (($video_items_count == 0) ? 'active' : ''); ?>">
	<div class="emd-embed-responsive">
	<iframe src="https://www.youtube.com/embed/<?php echo esc_html(emd_mb_meta('emd_video_key')); ?>
?html5=1" frameborder="0" allowfullscreen></iframe>
	</div>	
<div class="panel panel-default">
  <div class="video-summary">
<p><a title="<?php echo get_the_title(); ?>" href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></p>
 <div class="video-excerpt"> <?php echo $post->post_excerpt; ?></div>
  </div>
      <!-- Controls -->
      <div class="controls visible-xs-block">
      <a data-slide="prev" role="button" href="#emdvideos" class="left carousel-control">
        <span class="icon-prev"></span>
        <span class="sr-only">Previous</span>
      </a>
      <a data-slide="next" role="button" href="#emdvideos" class="right carousel-control">
        <span class="icon-next"></span>
        <span class="sr-only">Next</span>
      </a>
      </div>
</div>
 </div>
