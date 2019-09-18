<?php global $video_gallery_filter; ?><div id="integration-<?php echo get_the_ID(); ?>" class="emd-container emd-integration-wrap">
<?php echo do_shortcode("[video_items filter=\"" . $video_gallery_filter . "\" int_from=\"video_gallery\"]"); ?>

<?php echo do_shortcode("[video_indicators filter=\"" . $video_gallery_filter . "\" int_from=\"video_gallery\"]"); ?>
</div><!--container-end-->