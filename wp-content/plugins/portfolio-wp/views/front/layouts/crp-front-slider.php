<?php
    $iconStyle = 'fa-angle-';
    $leftArrClass = substr($iconStyle, -1) == '-' ? $iconStyle.'left' : $iconStyle;
    $rightArrClass = substr($iconStyle, -1) == '-' ? $iconStyle.'right' : $iconStyle;
?>

<style>

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .owl-carousel {
        padding-left: 0;
        padding-right: 0
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-image-wrapper {
        height: 400px
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-image {
        background-size: cover;
        background-position: center
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next {
        top: 200px
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next {
        padding: 34px;
        margin-left: 20px;
        margin-right: 20px
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev i, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next i {
        color: #e2e2e2;
        font-size: 60px
    }

    #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev:hover i, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next:hover i, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev:active i, #gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next:active i {
        color: #fff
    }

</style>

<div id="gkit-slider-<?php echo $crp_portfolio->id; ?>" class="gkit-slider-layout">
    <a class="gkit-slider-ctrl gkit-slider-ctrl-prev"><i class="fa <?php echo $leftArrClass; ?>"></i></a>
    <a class="gkit-slider-ctrl gkit-slider-ctrl-next"><i class="fa <?php echo $rightArrClass; ?>"></i></a>
    <div class="owl-carousel">
        <?php
            foreach ($crp_portfolio->projects as $crp_project) {
                $coverInfo = CRPHelper::decode2Obj(CRPHelper::decode2Str($crp_project->cover));
                if (empty($coverInfo)) {
                    continue;
                }
                $url = isset($crp_project->url) ? $crp_project->url : "";
                $title = isset($crp_project->title) ? CRPHelper::decode2Str($crp_project->title) : "";

                $coverInfo = CRPHelper::decode2Obj(CRPHelper::decode2Str($crp_project->cover));
                $coverType = !isset($coverInfo->type) ? CRPAttachmentType::PICTURE : $coverInfo->type;
                $meta = CRPHelper::getAttachementMeta($coverInfo->id, $crp_portfolio->options[CRPOption::kThumbnailQuality]);
                $metaOriginal = CRPHelper::getAttachementMeta($coverInfo->id);
            ?>

                    <div class="gkit-slider-cell">
                        <div class="gkit-slider-image-wrapper">
                            <?php
                                $imgHtml = '<div class="gkit-slider-image" style="background-image: url('.$meta['src'].'"></div>';
                                $blank = ($crp_portfolio->options[CRPOption::kLoadUrlBlank]) ? ' target="blank" ' : '';
                                echo !empty($url) ? '<a href="' . $url . '" '.$blank.'>'.$imgHtml.'</a>' : $imgHtml;
                            ?>
                        </div>
                    </div>
            <?php

            }
        ?>
    </div>
</div>


<script>
    jQuery(document).ready(function(){

        jQuery('#gkit-slider-<?php echo $crp_portfolio->id; ?> .owl-carousel').owlCarousel({
            lazyLoad: false,
            items: 1,
            margin: 10,
            center: false,
            loop: true,
            autoplay: false,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            autoHeight: false,
            mouseDrag: true,
            touchDrag: true,
            nav: false,
            slideBy: 1,
            dots: false,
            dotsEach: false,
            animateOut: '',
            animateIn: ''
        });

        jQuery('#gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-prev').click(function() {
            jQuery(this).closest('.gkit-slider-layout').find('.owl-carousel').trigger('prev.owl.carousel');
        });

        jQuery('#gkit-slider-<?php echo $crp_portfolio->id; ?> .gkit-slider-ctrl-next').click(function() {
            jQuery(this).closest('.gkit-slider-layout').find('.owl-carousel').trigger('next.owl.carousel');
        });

        jQuery(window).resize(function(){
            gkit_AdjustSlider(jQuery("#gkit-slider-<?php echo $crp_portfolio->id; ?>"));
        });

        function gkit_AdjustSlider(slider) {
            if (slider.width() <= 600) {
                slider.addClass('gkit-slider-mobile');
            } else {
                slider.removeClass('gkit-slider-mobile');
            }
        }
        gkit_AdjustSlider(jQuery("#gkit-slider-<?php echo $crp_portfolio->id; ?>"));

    });
</script>
