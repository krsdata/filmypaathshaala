<?php

function crp_infoBox($crp_project){
    $output = "";

    if( (isset($crp_project->title) && $crp_project->title !== '' ) || (isset($crp_project->description) && $crp_project->description !== '' )){
        $output .= "<div class='lg-info'>";

        if(isset($crp_project->title) && $crp_project->title !== '' ){
            $title = CRPHelper::decode2Str($crp_project->title);
            $output .= "<h4>".$title."</h4>";
        }

        if(isset($crp_project->description) && $crp_project->description !== '' ){
            $desc = CRPHelper::decode2Str($crp_project->description);
            $output .= "<p>".$desc."</p>";
        }
        $output .= "</div>";
    }

    $output = htmlentities($output);
    $output = str_replace("\n",'</br>',$output);

    return $output;
}

$gridType =  isset($crp_portfolio->extoptions['type']) ? $crp_portfolio->extoptions['type'] : CRPGridType::ALBUM;
$showTitle = ($gridType == CRPGridType::PORTFOLIO || $gridType == CRPGridType::ALBUM || $gridType == CRPGridType::GALLERY || $gridType == CRPGridType::TEAM);
$showDesc = false;


?>

<style>
    /* Portfolio Options Configuration Goes Here*/
    #gallery .tile:hover{
        cursor: <?php echo $crp_portfolio->options[CRPOption::kMouseType]; ?> !important;
    }

    /* - - - - - - - - - - - - - - -*/
    /* Tile Hover Customizations */

    /* Customize overlay background */
    #gallery .crp-tile-inner .overlay,
    #gallery .tile .caption {
        background-color: <?php echo CRPHelper::hex2rgba($crp_portfolio->options[CRPOption::kTileOverlayColor].$crp_portfolio->options[CRPOption::kTileOverlayOpacity]) ?> !important;
    }

    #gallery .crp-tile-inner.crp-details-bg .details {
        background-color: <?php echo CRPHelper::hex2rgba($crp_portfolio->options[CRPOption::kTileOverlayColor].$crp_portfolio->options[CRPOption::kTileOverlayOpacity]) ?> !important;
    }

    #gallery .crp-tile-inner .details h3 {
        color: <?php echo $crp_portfolio->options[CRPOption::kTileTitleColor] ?>;
        text-align: center;
        font-size: 18px;
    }

    #gallery .crp-tile-inner .details p {
        color: <?php echo $crp_portfolio->options[CRPOption::kTileDescColor] ?>;
        text-align: center;
        font-size: 11px;
    }

    <?php if(!$showDesc): ?>
    #gallery .crp-tile-inner .details h3 {
        margin-bottom: 0px;
    }
    <?php endif; ?>

</style>
<?php $isCatalog = (!empty($crp_portfolio->extoptions) && !empty($crp_portfolio->extoptions['type']) && $crp_portfolio->extoptions['type'] == CRPGridType::CATALOG); ?>

<!--Here Goes HTML-->
<div class="crp-wrapper">
    <div id="gallery">
        <div id="ftg-items" class="ftg-items">
            <?php foreach($crp_portfolio->projects as $crp_project): ?>
                <div id="crp-tile-<?php echo $crp_project->id?>" class="tile" data-url="<?php echo isset($crp_project->url) ? $crp_project->url : ""?>">
                    <?php if ($gridType == CRPGridType::CLIENT_LOGOS) { ?>
                    <div class="crp-tile-inner details27 image01">
                    <?php } else { ?>
                    <div class="crp-tile-inner details33 crp-details-bg image01">
                    <?php } ?>

                    <?php if($isCatalog) { ?>
                    <div class="crp-additional-block1">
                        <?php
                        $title = isset($crp_project->title) ? CRPHelper::decode2Str($crp_project->title) : "";
                        if (!empty($title)) {
                            echo '<h3 class="crp-catalog-title">'.$title.'</h3>';
                        }
                        ?>
                    </div>
                    <?php } ?>

                    <?php
                        $coverInfo = CRPHelper::decode2Str($crp_project->cover);
                        $coverInfo = CRPHelper::decode2Obj($coverInfo);
                        $meta = CRPHelper::getAttachementMeta($coverInfo->id, $crp_portfolio->options[CRPOption::kThumbnailQuality]);

                        if (isset($crp_project->details)) {
                            $crp_project->details = json_decode($crp_project->details);
                            $catalogPrice = (isset($crp_project->details) && isset($crp_project->details->price)) ? $crp_project->details->price : "";
                            $catalogSale = (isset($crp_project->details) && isset($crp_project->details->sale)) ? $crp_project->details->sale : "";
                        }
                    ?>

                    <a id="<?php echo $crp_project->id ?>" class="tile-inner">
                        <?php if ($isCatalog && !empty($catalogSale)) { ?>
                            <div class='crp-badge-box crp-badge-pos-RT'><div class="crp-badge"><span><?php echo '-'.$catalogSale.'%'; ?></span></div></div>
                        <?php } ?>
                        <img class="crp-item crp-tile-img" src="<?php echo $meta['src'] ?>" data-width="<?php echo $meta['width']; ?>" data-height="<?php echo $meta['height']; ?>" />
                        <?php
                        $html = '';
                        if ($showTitle || $showDesc) {
                            $html .= "<div class='overlay'></div>";
                            $title = isset($crp_project->title) ? CRPHelper::decode2Str($crp_project->title) : "";
                            $desc = isset($crp_project->description) ? CRPHelper::decode2Str($crp_project->description) : "";
                            $desc = CRPHelper::truncWithEllipsis($desc, 15);

                            if ($title != '' || $desc != '') {
                                $html .= "<div class='details'>";
                                if ($showTitle) {
                                    $html .= "<h3>{$title}</h3>";
                                }
                                if ($showDesc) {
                                    $html .= "<p>{$desc}</p>";
                                }
                                $html .= "</div>";
                            }
                        } else {
                            if ($gridType != CRPGridType::CLIENT_LOGOS && $gridType != CRPGridType::CATALOG) {
                                $html .= '<div class="caption"></div>';
                            }
                        }
                        echo $html;
                        ?>
                    </a>
                    <?php if ($isCatalog) { ?>
                        <div class="crp-additional-block2">
                            <?php
                            if (isset($crp_project->details)) {
                                $sale = '';
                                $overline = '';
                                if (!empty($catalogSale) && !empty($catalogPrice)) {
                                    $sale = "$" . number_format((float)($catalogPrice - $catalogPrice * $catalogSale / 100), 2, '.', '');
                                    $overline = 'style="text-decoration: line-through;"';
                                    echo "<p><span {$overline}> "."$"."{$catalogPrice} </span> &nbsp;<span>{$sale}</span></p>";
                                } elseif (!empty($catalogPrice)) {
                                    echo "<p><span>"."$"."{$catalogPrice}</span></p>";
                                }
                            }
                            ?>
                            <?php if (!empty($crp_project->url)) { ?><p><button class="crp-product-buy-button" onclick="crp_loadHref('<?php echo (!empty($crp_project->url) ? $crp_project->url : '#'); ?>', true)">BUY NOW</button></p><?php } ?>
                        </div>
                    <?php } ?>
                    </div>

                    <?php if(($gridType == CRPGridType::ALBUM || $gridType == CRPGridType::PORTFOLIO) && !$crp_portfolio->options[CRPOption::kDirectLinking]) : ?>

                    <ul id="crp-light-gallery-<?php echo $crp_project->id; ?>" class="crp-light-gallery" style="display: none;" data-sub-html="<?php echo crp_infoBox( $crp_project)?>" data-url="<?php echo isset($crp_project->url) ? $crp_project->url : ''; ?>">
                        <?php
                            $meta = CRPHelper::getAttachementMeta($coverInfo->id);
                            $metaThumb = CRPHelper::getAttachementMeta($coverInfo->id, "medium");
                        ?>

                        <li data-src="<?php echo $meta['src']; ?>" >
                            <a href="#">
                                <img src="<?php echo $metaThumb['src']; ?>" />
                            </a>
                        </li>

                        <?php foreach($crp_project->pics as $pic): ?>
                            <?php if(!empty($pic)): ?>
                                <?php
                                    $picInfo = CRPHelper::decode2Str($pic);
                                    $picInfo = CRPHelper::decode2Obj($picInfo);

                                    $meta = CRPHelper::getAttachementMeta($picInfo->id);
                                    $metaThumb = CRPHelper::getAttachementMeta($picInfo->id, "medium");
                                ?>

                                <li data-src="<?php echo $meta['src']; ?>">
                                    <a href="#">
                                        <img src="<?php echo $metaThumb['src']; ?>" />
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if($gridType != CRPGridType::ALBUM && $gridType != CRPGridType::PORTFOLIO && !$crp_portfolio->options[CRPOption::kDirectLinking]) : ?>
                <ul id="crp-light-gallery" class="crp-light-gallery" style="display: none;" >
                <?php foreach($crp_portfolio->projects as $crp_project): ?>
                    <?php
                        $coverInfo = CRPHelper::decode2Str($crp_project->cover);
                        $coverInfo = CRPHelper::decode2Obj($coverInfo);
                        $meta = CRPHelper::getAttachementMeta($coverInfo->id, $crp_portfolio->options[CRPOption::kThumbnailQuality]);
                        $meta = CRPHelper::getAttachementMeta($coverInfo->id);
                        $metaThumb = CRPHelper::getAttachementMeta($coverInfo->id, "medium");
                    ?>

                    <li id="crp-light-gallery-item-<?php echo $crp_project->id; ?>" data-src="<?php echo $meta['src']; ?>" data-sub-html="<?php echo crp_infoBox( $crp_project)?>" data-url="<?php echo isset($crp_project->url) ? $crp_project->url : ''; ?>">
                        <a href="#">
                            <img src="<?php echo $metaThumb['src']; ?>" />
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
    $approxTileWidth = ( isset($crp_portfolio->options[CRPOption::kTileApproxWidth]) && !empty($crp_portfolio->options[CRPOption::kTileApproxWidth]) ) ? $crp_portfolio->options[CRPOption::kTileApproxWidth] : 220;
    $approxTileHeight = ( isset($crp_portfolio->options[CRPOption::kTileApproxHeight]) &&  !empty($crp_portfolio->options[CRPOption::kTileApproxHeight]) ) ? $crp_portfolio->options[CRPOption::kTileApproxHeight] : 220;
    $minTileWidth = ( isset($crp_portfolio->options[CRPOption::kTileMinWidth]) && !empty($crp_portfolio->options[CRPOption::kTileMinWidth]) ) ? $crp_portfolio->options[CRPOption::kTileMinWidth] : 200;
?>

<!--Here Goes JS-->
<script>
    (function($) {
        $(document).ready(function(){

            var tileParams = {};

            if(<?php echo ($gridType == CRPGridType::CLIENT_LOGOS || $gridType == CRPGridType::TEAM) ? 1 : 0 ?>) {
                tileParams.approxTileWidth = <?php echo $approxTileWidth; ?>;
                tileParams.approxTileHeight = <?php echo $approxTileHeight; ?>;
                tileParams.minTileWidth = <?php echo $minTileWidth; ?>;
            }

            if(<?php echo ($gridType == CRPGridType::CATALOG) ? 1 : 0 ?>) {
                tileParams.addBlock1Height = 40;
                tileParams.addBlock2Height = 100;
            }
            jQuery('#gallery').crpTiledLayer(tileParams);

            $( ".crp-light-gallery" ).each(function() {
              var id = $( this ).attr("id");
              $("#" + id).lightGallery({
                mode: 'slide',
                useCSS: true,
                cssEasing: 'ease', //'cubic-bezier(0.25, 0, 0.25, 1)',//
                easing: 'linear', //'for jquery animation',//
                speed: 600,
                addClass: '',

                closable: true,
                loop: true,
                auto: false,
                pause: 6000,
                escKey: true,
                controls: true,
                hideControlOnEnd: false,

                preload: 1, //number of preload slides. will exicute only after the current slide is fully loaded. ex:// you clicked on 4th image and if preload = 1 then 3rd slide and 5th slide will be loaded in the background after the 4th slide is fully loaded.. if preload is 2 then 2nd 3rd 5th 6th slides will be preloaded.. ... ...
                showAfterLoad: true,
                selector: null,
                index: false,

                lang: {
                    allPhotos: 'All photos'
                },
                counter: false,

                exThumbImage: false,
                thumbnail: true,
                showThumbByDefault:false,
                animateThumb: true,
                currentPagerPosition: 'middle',
                thumbWidth: 150,
                thumbMargin: 10,


                mobileSrc: false,
                mobileSrcMaxWidth: 640,
                swipeThreshold: 50,
                enableTouch: true,
                enableDrag: true,

                vimeoColor: 'CCCCCC',
                youtubePlayerParams: false, // See: https://developers.google.com/youtube/player_parameters,
                videoAutoplay: true,
                videoMaxWidth: '855px',

                dynamic: false,
                dynamicEl: [],

                // Callbacks el = current plugin
                onOpen        : function(el) {}, // Executes immediately after the gallery is loaded.
                onSlideBefore : function(el) {}, // Executes immediately before each transition.
                onSlideAfter  : function(el) {}, // Executes immediately after each transition.
                onSlideNext   : function(el) {}, // Executes immediately before each "Next" transition.
                onSlidePrev   : function(el) {}, // Executes immediately before each "Prev" transition.
                onBeforeClose : function(el) {}, // Executes immediately before the start of the close process.
                onCloseAfter  : function(el) {}, // Executes immediately once lightGallery is closed.
                onOpenExternal  : function(el, index) {
                    if($(el).attr('data-url')) {
                        var href = $(el).attr("data-url");
                    } else {
                        var href = $("#crp-light-gallery li").eq(index).attr('data-url');
                    }
                    if(href) {
                        crp_loadHref(href,true);
                    }else {
                        return false;
                    }

                }, // Executes immediately before each "open external" transition.
                onToggleInfo  : function(el) {
                  var $info = $(".lg-info");
                  if($info.css("opacity") == 1){
                    $info.fadeTo("slow",0);
                  }else{
                    $info.fadeTo("slow",1);
                  }
                } // Executes immediately before each "toggle info" transition.
              });
            });

            jQuery(".tile").on('click', function (event){
                if(jQuery(event.target).hasClass('crp-product-buy-button')) {
                    return false;
                }
                <?php if($crp_portfolio->options[CRPOption::kDirectLinking]){ ?>
                event.preventDefault();
                var url = jQuery(this).attr("data-url");
                if(url != '') {
                    var blank = (<?php echo $gridType == CRPGridType::CLIENT_LOGOS ? 1 : 0; ?>) ? true : false;
                    crp_loadHref(url, blank);
                } else {
                    return false;
                }
                <?php } ?>

                event.preventDefault();
                if(jQuery(event.target).hasClass("fa") && !jQuery(event.target).hasClass("zoom")) return;

                <?php if($gridType == CRPGridType::ALBUM || $gridType == CRPGridType::PORTFOLIO) { ?>
                var tileId = jQuery(this).attr("id");
                var target = jQuery("#" + tileId + " .crp-light-gallery li:first");
                <?php } else { ?>
                var tileId = jQuery(".tile-inner", jQuery(this)).attr("id");
                var target = jQuery("#crp-light-gallery-item-"+tileId);
                <?php } ?>
                target.trigger( "click" );
            });

        });
    })( jQuery );

</script>
