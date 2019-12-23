<div class="crp-background">
</div>
<div id="crp-wrap" class="crp-wrap crp-glazzed-wrap">

<?php include_once( CRP_ADMIN_VIEWS_DIR_PATH.'/crp-header-banner.php'); ?>

<div class="crp-wrap-main">

    <script>
        CRP_AJAX_URL = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>';
        CRP_IMAGES_URL = '<?php echo CRP_IMAGES_URL ?>';
    </script>

    <?php

    abstract class CRPTabType{
        const Dashboard = 'dashboard';
        const Settings = 'settings';
        const Help = 'help';
        const Terms = 'terms';
    }

    $crp_tabs = array(
        CRPTabType::Dashboard => 'All Portfolios',
        CRPTabType::Settings => 'General Settings',
        CRPTabType::Help => 'User Manual',
    );

    $crp_adminPage = isset( $_REQUEST['page']) ? filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING) : null;
    $crp_currentTab = isset ( $_GET['tab'] ) ? filter_var($_GET['tab'], FILTER_SANITIZE_STRING) : CRPTabType::Dashboard;
    $crp_action = isset ( $_GET['action'] ) ? filter_var($_GET['action'], FILTER_SANITIZE_STRING) : null;
    $crp_gridType = isset ( $_GET['type'] ) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : null;

    include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-modal-spinner.php");
    include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-header.php");

    if($crp_action == 'create' || $crp_action == 'edit'){
        if($crp_gridType == CRPGridType::GALLERY) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-gallery.php");
        } elseif($crp_gridType == CRPGridType::TEAM) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-team.php");
        } elseif($crp_gridType == CRPGridType::CLIENT_LOGOS) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-client_logos.php");
        } elseif($crp_gridType == CRPGridType::CATALOG) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-catalog.php");
        } elseif($crp_gridType == CRPGridType::PORTFOLIO) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-portfolio.php");
        } else if($crp_gridType == CRPGridType::SLIDER) {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-slider.php");
        } else {
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-album.php");
        }
    }else if ($crp_action == 'options'){
        include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-portfolio-options.php");
    }else{
        //Tabs are not fully developed yet, that's why we have disabled them in this version
        //crp_renderAdminTabs($crp_currentTab, $crp_adminPage, $crp_tabs);

        if($crp_currentTab == CRPTabType::Dashboard){
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-dashboard.php");
        }else if($crp_currentTab == CRPTabType::Settings){
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-settings.php");
        }else if($crp_currentTab == CRPTabType::Help){
            include_once(CRP_ADMIN_VIEWS_DIR_PATH."/crp-admin-help.php");
        }
    }

    function crp_renderAdminTabs( $current, $page, $tabs = array()){
        //Hardcoded style for removing dynamically added bottom-border
        echo '<h2 class="nav-tab-wrapper crp-admin-nav-tab-wrapper" style="border: 0px">';

        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current) ? 'nav-tab-active' : '';
            echo "<a class='nav-tab $class' href='?page=$page&tab=$tab'>$name</a>";
        }
        echo '</h2>';
    }

    ?>
    <div style="clear:both;"></div>
</div>
</div>
