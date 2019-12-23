<div class="gkit-admin-header-banner">
<?php
    $gkitBanner = CRPHelper::getBanner('header');
    if (!empty($gkitBanner)) {
        echo $gkitBanner['content'];
    } else {
?>
        <style>
            .gkit-default-banner-box {
                width: 100%;
                background-color: #a0ce4e;
                height: 100px;
                margin-bottom: 20px;
                color: white;
            }
            .gkit-default-banner-box--bg-image {
                background-size: contain;
                background-position: center;
                background-repeat: no-repeat;
            }
            .gkit-default-banner-box--logo-block {
                padding:7px 10px 0 20px;
                float: left;
            }
            .gkit-default-banner-box--logo {
                background-image: url('<?php echo CRP_IMAGES_URL.'/admin/banner/logo.png'; ?>');
                width: 70px;
                height: 70px;
            }
            .gkit-default-banner-box--logo-title {
                text-align: center;
                margin-top: -5px;
            }
            .gkit-default-banner-box--title-block {
                padding-top: 20px;
            }
            .gkit-default-banner-box--title-block-icon {
                background-image: url('<?php echo CRP_IMAGES_URL.'/admin/banner/gridkit.png'; ?>');
                width: 220px;
                height: 70px;
                margin: 0 auto;
            }
            .gkit-default-banner-box--menu-block {
                float: right;
            }
            .gkit-default-banner-box--menu-block-help {
                background-image: url('<?php echo CRP_IMAGES_URL.'/admin/banner/support.png'; ?>');
                margin-top: -60px;
                width: 45px;
                height: 45px;
                margin-right: 20px;
                display: block;
            }
            .gkit-default-banner-box--menu-block-help:hover {
                opacity: 0.8;
            }
            .gkit-default-banner-box--menu-block-help:active,
            .gkit-default-banner-box--menu-block-help:focus {
                -webkit-box-shadow: none;
                -moz-box-shadow: none;
                box-shadow: none;
            }
        </style>
        <div class="gkit-default-banner-box">
            <div class="gkit-default-banner-box--logo-block">
                <div class="gkit-default-banner-box--logo gkit-default-banner-box--bg-image"></div>
                <div class="gkit-default-banner-box--logo-title">FREE</div>
            </div>
            <div class="gkit-default-banner-box--title-block">
                <div class="gkit-default-banner-box--title-block-icon gkit-default-banner-box--bg-image"></div>
            </div>
            <div class="gkit-default-banner-box--menu-block">
                <a href="https://wordpress.org/support/plugin/portfolio-wp/" target="_blank" class="gkit-default-banner-box--menu-block-help gkit-default-banner-box--bg-image"></a>
            </div>
        </div>
<?php
    }
?>
</div>
