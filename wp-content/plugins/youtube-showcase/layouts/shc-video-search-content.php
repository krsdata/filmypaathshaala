<?php global $video_search_count;
$ent_attrs = get_option('youtube_showcase_attr_list'); ?>
<div style="border:1px solid lightgrey;background: white;padding:4px;margin-bottom:-1px;">
<a title="<?php echo get_the_title(); ?>" href="<?php echo get_permalink(); ?>" class="video-item"><?php echo get_the_title(); ?></a>
</div>