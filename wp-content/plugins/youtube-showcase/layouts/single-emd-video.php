<?php $real_post = $post;
$ent_attrs = get_option('youtube_showcase_attr_list');
?>
<div id="single-emd-video-<?php echo get_the_ID(); ?>" class="emd-container emd-video-wrap single-wrap">
<?php $is_editable = 0; ?>
<div class="video-title"><h3><?php echo get_the_title(); ?></h3></div>
<div class="emd-embed-responsive">
	<iframe src="https://www.youtube.com/embed/<?php echo esc_html(emd_mb_meta('emd_video_key')); ?>
?html5=1&autoplay=<?php echo esc_html(emd_mb_meta('emd_video_autoplay')); ?>
" frameborder="0" allowfullscreen></iframe>
</div>
<div class="video-summary"><?php echo $post->post_content; ?></div>
</div><!--container-end-->