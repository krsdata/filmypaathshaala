<?php

/*
* Plugin Name: Grid Kit
* Plugin URI: http://wpsofts.com
* Description: WordPress multipurpose plugin to showcase your portfolio, photo gallery, eCommerce product catalog, image slider, album, clients or team members on your site!
* Author: wpsofts
* Author URI: http://wpsofts.com
* License: GPLv2 or later
* Version: 1.8.20
*/


//Load configs
require_once( dirname(__FILE__).'/gkit-config.php');
require_once( CRP_CLASSES_DIR_PATH.'/crp-ajax.php');
require_once( CRP_CLASSES_DIR_PATH.'/CRPHelper.php');
require_once( CRP_CLASSES_DIR_PATH.'/DBInitializer.php');

//Register activation & deactivation hooks
register_activation_hook( __FILE__, 'crp_activation_hook');
register_uninstall_hook( __FILE__, 'crp_uninstall_hook');
register_deactivation_hook( __FILE__, 'crp_deactivation_hook');

//Register action hooks
add_action('init', 'crp_init_action');
add_action('admin_enqueue_scripts', 'crp_admin_enqueue_scripts_action' );
add_action('wp_enqueue_scripts', 'crp_wp_enqueue_scripts_action' );
add_action('admin_menu', 'crp_admin_menu_action');
add_action('admin_head', 'crp_admin_head_action');
add_action('admin_footer', 'crp_admin_footer_action');
add_action('upgrader_process_complete', 'gkit_update_complete_action', 10, 2);
add_action('plugins_loaded', 'grid_kit_plugins_loaded_action');

//Register filter hooks

//Register crp shortcode handlers
add_shortcode('crp_portfolio', 'crp_shortcode_handler');
add_shortcode('gkit', 'crp_shortcode_handler');

//Register Ajax actions
add_action( 'wp_ajax_crp_get_portfolio', 'wp_ajax_crp_get_portfolio');
add_action( 'wp_ajax_crp_save_portfolio', 'wp_ajax_crp_save_portfolio');
add_action( 'wp_ajax_crp_get_options', 'wp_ajax_crp_get_options');
add_action( 'wp_ajax_crp_save_options', 'wp_ajax_crp_save_options');


//Global vars
$crp_portfolio;

function gkit_update_complete_action( $upgrader_object, $options ) {
    $our_plugin = plugin_basename( __FILE__ );
    if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        foreach( $options['plugins'] as $plugin ) {
            if( $plugin == $our_plugin ) {
                set_transient( 'grid_kit_updated', 1 );
            }
        }
    }
}

function grid_kit_plugins_loaded_action()
{
    if (get_transient('grid_kit_updated')) {
        $dbInitializer = new DBInitializer();
        $dbInitializer->checkForChanges();

        delete_transient('grid_kit_updated');
    }
}

//Registered activation hook
function crp_activation_hook(){
    $dbInitializer = new DBInitializer();
    if($dbInitializer->needsConfiguration()){
        $dbInitializer->configure();
    }
    $dbInitializer->checkForChanges();
}

function crp_uninstall_hook(){
    delete_option(CRP_BANNERS_CONTENT);
    delete_option(CRP_BANNERS_LAST_LOADED_AT);
}

function crp_deactivation_hook(){
}

//Registered hook actions
function crp_init_action() {
    global $wp_version;
    if ( version_compare( $wp_version, '5.0.0', '>=' ) ) {
        wp_register_script(
            'gkit-shortcode-block-script',
            CRP_JS_URL . '/gkit-shortcode-block.js',
            array('wp-blocks', 'wp-element')
        );

        wp_register_style(
            'gkit-shortcode-block-style',
            CRP_CSS_URL . '/gkit-admin-editor-block.css',
            array('wp-edit-blocks'),
            filemtime(plugin_dir_path(__FILE__) . 'css/gkit-admin-editor-block.css')
        );

        register_block_type('grid-kit-premium/gkit-shortcode-block', array(
            'editor_script' => 'gkit-shortcode-block-script',
            'editor_style' => 'gkit-shortcode-block-style',
        ));
    }
    ob_start();
}

function crp_admin_enqueue_scripts_action($hook) {
    if (stripos($hook, CRP_PLUGIN_SLAG) !== false) {
        crp_enqueue_admin_scripts();
        crp_enqueue_admin_csss();
    }
}

function crp_wp_enqueue_scripts_action(){
    crp_enqueue_front_scripts();
    crp_enqueue_front_csss();
}

function crp_admin_menu_action() {
    crp_setup_admin_menu_buttons();
}

function crp_admin_head_action(){
    crp_include_inline_scripts();
    crp_setup_media_buttons();
}

function crp_admin_footer_action() {
    crp_include_inline_htmls();
}

//Registered hook filters
function crp_mce_external_plugins_filter($pluginsArray){
    return crp_register_tinymce_plugin($pluginsArray);
}

function crp_mce_buttons_filter($buttons){
    return crp_register_tc_buttons($buttons);
}

//Shortcode Hanlders
function crp_shortcode_handler($attributes){
	ob_start();

    //Prepare render data
    global $crp_portfolio;
    $crp_portfolio = CRPHelper::getPortfolioWithId($attributes['id']);
    require_once(CRP_FRONT_VIEWS_DIR_PATH."/crp-front.php");

    $result = ob_get_clean();
    return $result;
}

//Internal functionality
function crp_setup_admin_menu_buttons(){
    add_menu_page(CRP_PLUGIN_NAME, CRP_PLUGIN_NAME, 'edit_posts', CRP_PLUGIN_SLAG, "crp_admin_portfolios_page", 'dashicons-portfolio', 76);
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_PORTFOLIOS_TITLE, CRP_SUBMENU_PORTFOLIOS_TITLE, 'edit_posts', CRP_PLUGIN_SLAG, 'crp_admin_portfolios_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_ALBUMS_TITLE, CRP_SUBMENU_ALBUMS_TITLE, 'edit_posts', CRP_SUBMENU_ALBUMS_SLUG, 'crp_admin_albums_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_GALLERIES_TITLE, CRP_SUBMENU_GALLERIES_TITLE, 'edit_posts', CRP_SUBMENU_GALLERIES_SLUG, 'crp_admin_galleries_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_CLIENT_LOGOS_TITLE, CRP_SUBMENU_CLIENT_LOGOS_TITLE, 'edit_posts', CRP_SUBMENU_CLIENT_LOGOS_SLUG, 'crp_admin_client_logos_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_TEAMS_TITLE, CRP_SUBMENU_TEAMS_TITLE, 'edit_posts', CRP_SUBMENU_TEAMS_SLUG, 'crp_admin_teams_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_PRODUCT_CATALOGS_TITLE, CRP_SUBMENU_PRODUCT_CATALOGS_TITLE, 'edit_posts', CRP_SUBMENU_PRODUCT_CATALOGS_SLUG, 'crp_admin_catalogs_page');
    add_submenu_page(CRP_PLUGIN_SLAG, CRP_SUBMENU_SLIDER_TITLE, CRP_SUBMENU_SLIDER_TITLE, 'edit_posts', CRP_SUBMENU_SLIDER_SLUG, 'crp_admin_sliders_page');
}

function crp_admin_page() {
  require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}

function crp_admin_albums_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::ALBUM;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_portfolios_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::PORTFOLIO;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_galleries_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::GALLERY;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_client_logos_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::CLIENT_LOGOS;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_teams_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::TEAM;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_catalogs_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::CATALOG;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}
function crp_admin_sliders_page(){
    global $crp_adminPageType;
    $crp_adminPageType = CRPGridType::SLIDER;
    require_once(CRP_ADMIN_VIEWS_DIR_PATH.'/crp-admin.php');
}

function crp_setup_media_buttons(){
    global $typenow;
    // check user permissions
    if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
        return;
    }

    // verify the post type
    if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        return;

    // check if WYSIWYG is enabled
    if ( get_user_option('rich_editing') == 'true') {
        add_filter("mce_external_plugins", "crp_mce_external_plugins_filter");
        add_filter('mce_buttons', 'crp_mce_buttons_filter');
    }
}

function crp_register_tinymce_plugin($pluginsArray) {
    $pluginsArray['crp_tc_buttons'] = CRP_JS_URL."/crp-tc-buttons.js";
    return $pluginsArray;
}

function crp_register_tc_buttons($buttons) {
    array_push($buttons, "crp_insert_tc_button");
    return $buttons;
}

function crp_include_inline_scripts(){
?>
    <script type="text/javascript">

        <?php
             $dbInitializer = new DBInitializer();
            if ($dbInitializer->needsInitialization()) {
                $crp_shortcodes = array();
            } else {
                $crp_shortcodes = CRPHelper::tcButtonShortcodes();
            }
        ?>

        crp_shortcodes = [];
        <?php foreach($crp_shortcodes as $crp_shortcode): ?>
            crp_shortcodes.push({
                "id" : "<?php echo $crp_shortcode->id ?>",
                "title" : "<?php echo $crp_shortcode->title ?>",
                "shortcode" : "<?php echo $crp_shortcode->shortcode ?>"
            });
        <?php endforeach; ?>


        jQuery(document).ready(function() {
        });
    </script>
<?php
}

function crp_include_inline_htmls(){
?>

<?php
}

function crp_enqueue_admin_scripts(){
    wp_enqueue_script("jquery");
    wp_enqueue_script("jquery-ui-core");
    wp_enqueue_script("jquery-ui-sortable");
    wp_enqueue_script("jquery-ui-autocomplete");

    //Enqueue JS files
    wp_enqueue_script( 'crp-helper-js', CRP_JS_URL.'/crp-helper.js', array('jquery'), "", false );
    wp_enqueue_script( 'crp-main-admin-js', CRP_JS_URL.'/crp-main-admin.js', array('jquery'), "", true );
    wp_enqueue_script( 'crp-ajax-admin-js', CRP_JS_URL.'/crp-ajax-admin.js', array('jquery'), "", true );

    wp_register_script('crp-tooltipster', CRP_JS_URL."/jquery/jquery.tooltipster.js", array('jquery'), "", true );
    wp_enqueue_script('crp-tooltipster');

    wp_register_script('crp-caret', CRP_JS_URL."/jquery/jquery.caret.js", array('jquery'), "", true );
    wp_enqueue_script('crp-caret');

    wp_register_script('crp-tageditor', CRP_JS_URL."/jquery/jquery.tageditor.js", array('jquery'), "", true );
    wp_enqueue_script('crp-tageditor');

    wp_enqueue_media();
    wp_enqueue_script('wp-color-picker');
}

function crp_enqueue_admin_csss(){
    //Enqueue CSS files

    wp_register_style('crp-main-admin-style', CRP_CSS_URL.'/crp-main-admin.css');
    wp_enqueue_style('crp-main-admin-style');

    wp_register_style('crp-tc-buttons', CRP_CSS_URL.'/crp-tc-buttons.css');
    wp_enqueue_style('crp-tc-buttons');

    wp_register_style('crp-tooltipster', CRP_CSS_URL.'/tooltipster/tooltipster.css');
    wp_enqueue_style('crp-tooltipster');
    wp_register_style('crp-tooltipster-theme', CRP_CSS_URL.'/tooltipster/themes/tooltipster-shadow.css');
    wp_enqueue_style('crp-tooltipster-theme');

    wp_register_style('crp-accordion', CRP_CSS_URL.'/accordion/accordion.css');
    wp_enqueue_style('crp-accordion');

    wp_register_style('crp-tageditor', CRP_CSS_URL.'/tageditor/tageditor.css');
    wp_enqueue_style('crp-tageditor');

    wp_enqueue_style( 'wp-color-picker' );

    wp_register_style('crp-font-awesome', CRP_CSS_URL.'/fontawesome/font-awesome.css');
    wp_enqueue_style('crp-font-awesome');
}

function crp_enqueue_front_scripts(){
    //Enqueue JS files
    wp_enqueue_script( 'crp-main-front-js', CRP_JS_URL.'/crp-main-front.js', array('jquery') );
    wp_enqueue_script( 'crp-helper-js', CRP_JS_URL.'/crp-helper.js', array('jquery') );

    wp_enqueue_script( 'crp-modernizr', CRP_JS_URL."/jquery/jquery.modernizr.js", array('jquery') );
    wp_enqueue_script( 'crp-tiled-layer', CRP_JS_URL."/crp-tiled-layer.js", array('jquery') );
    wp_enqueue_script( 'crp-fs-viewer', CRP_JS_URL.'/crp-fs-viewer.js', array('jquery') );
    wp_enqueue_script( 'crp-lg-viewer', CRP_JS_URL.'/jquery/jquery.lightgallery.js', array('jquery') );
    wp_enqueue_script( 'crp-owl', CRP_JS_URL.'/owl-carousel/owl.carousel.js', array('jquery') );
}

function crp_enqueue_front_csss(){
    //Enqueue CSS files
    wp_register_style('crp-main-front-style', CRP_CSS_URL.'/crp-main-front.css');
    wp_enqueue_style('crp-main-front-style');

    wp_register_style('crp-tc-buttons', CRP_CSS_URL.'/crp-tc-buttons.css');
    wp_enqueue_style('crp-tc-buttons');

    wp_register_style('crp-tiled-layer', CRP_CSS_URL.'/crp-tiled-layer.css');
    wp_enqueue_style('crp-tiled-layer');

    wp_register_style('crp-fs-viewer', CRP_CSS_URL.'/fsviewer/crp-fs-viewer.css');
    wp_enqueue_style('crp-fs-vewer');

    wp_register_style('crp-font-awesome', CRP_CSS_URL.'/fontawesome/font-awesome.css');
    wp_enqueue_style('crp-font-awesome');

    wp_register_style('crp-lg-viewer', CRP_CSS_URL.'/lightgallery/lightgallery.css');
    wp_enqueue_style('crp-lg-viewer');

    wp_register_style('crp-captions', CRP_CSS_URL.'/crp-captions.css');
    wp_enqueue_style('crp-captions');

    wp_register_style('crp-captions', CRP_CSS_URL.'/crp-captions.css');
    wp_enqueue_style('crp-captions');

    wp_register_style('crp-owl', CRP_CSS_URL.'/owl-carousel/assets/owl.carousel.css');
    wp_enqueue_style('crp-owl');

    wp_register_style('crp-layout', CRP_CSS_URL.'/owl-carousel/layout.css');
    wp_enqueue_style('crp-layout');
}
