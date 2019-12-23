<?php
/**
 * Describe child theme functions
 *
 * @package News Portal
 * @subpackage News Portal Lite
 * 
 */

/*-------------------------------------------------------------------------------------------------------------------------------*/
if ( ! function_exists( 'news_portal_lite_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function news_portal_lite_setup() {
    
    //Added image size
    add_image_size( 'news-portal-lite-horizontal-thumb', 580, 195, true );

    $news_portal_lite_theme_info = wp_get_theme();
    $GLOBALS['news_portal_lite_version'] = $news_portal_lite_theme_info->get( 'Version' );
}
endif;

add_action( 'after_setup_theme', 'news_portal_lite_setup' );

/*-------------------------------------------------------------------------------------------------------------------------------*/
/**
 * Register Google fonts for News Portal Lite.
 *
 * @return string Google fonts URL for the theme.
 * @since 1.0.0
 */
if ( ! function_exists( 'news_portal_lite_fonts_url' ) ) :
    function news_portal_lite_fonts_url() {

        $fonts_url = '';
        $font_families = array();

        /*
         * Translators: If there are characters in your language that are not supported
         * by Montserrat, translate this to 'off'. Do not translate into your own language.
         */
        if ( 'off' !== _x( 'on', 'Montserrat font: on or off', 'news-portal-lite' ) ) {
            $font_families[] = 'Montserrat:300,400,400i,500,700';
        }

        if( $font_families ) {
            $query_args = array(
                'family' => urlencode( implode( '|', $font_families ) ),
                'subset' => urlencode( 'latin,latin-ext' ),
            );

            $fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
        }

        return $fonts_url;
    }
endif;

/*-------------------------------------------------------------------------------------------------------------------------------*/
/**
 * Managed the theme default color
 */
function news_portal_lite_customize_register( $wp_customize ) {
		global $wp_customize;

		$wp_customize->get_setting( 'news_portal_theme_color' )->default = '#0D88D2';

	}

add_action( 'customize_register', 'news_portal_lite_customize_register', 20 );

/*-------------------------------------------------------------------------------------------------------------------------------*/
/**
 * Enqueue child theme styles and scripts
 */
add_action( 'wp_enqueue_scripts', 'news_portal_lite_scripts', 20 );

function news_portal_lite_scripts() {
    
    global $news_portal_lite_version;
    
    wp_enqueue_style( 'news-portal-lite-google-font', news_portal_lite_fonts_url(), array(), null );
    
    wp_dequeue_style( 'news-portal-style' );
    
    wp_dequeue_style( 'news-portal-responsive-style' );
    
	wp_enqueue_style( 'news-portal-parent-style', get_template_directory_uri() . '/style.css', array(), esc_attr( $news_portal_lite_version ) );
    
    wp_enqueue_style( 'news-portal-parent-responsive', get_template_directory_uri() . '/assets/css/np-responsive.css', array(), esc_attr( $news_portal_lite_version ) );
    
    wp_enqueue_style( 'news-portal-lite-style', get_stylesheet_uri(), array(), esc_attr( $news_portal_lite_version ) );
    
    $get_categories = get_categories( array( 'hide_empty' => 1 ) );
    
    $news_portal_lite_theme_color = get_theme_mod( 'news_portal_theme_color', '#0D88D2' );
    
    $news_portal_lite_theme_hover_color = news_portal_hover_color( $news_portal_lite_theme_color, '-50' );
    
    $news_portal_site_title_option = get_theme_mod( 'news_portal_site_title_option', 'true' );        
    $news_portal_site_title_color = get_theme_mod( 'news_portal_site_title_color', '#0D88D2' );
    
    $output_css = '';
    
    foreach( $get_categories as $category ){

        $cat_color = get_theme_mod( 'news_portal_category_color_'.strtolower( $category->name ), '#00a9e0' );

        $cat_hover_color = news_portal_hover_color( $cat_color, '-50' );
        $cat_id = $category->term_id;
        
        if( !empty( $cat_color ) ) {
            $output_css .= ".category-button.np-cat-". esc_attr( $cat_id ) ." a { background: ". esc_attr( $cat_color ) ."}\n";

            $output_css .= ".category-button.np-cat-". esc_attr( $cat_id ) ." a:hover { background: ". esc_attr( $cat_hover_color ) ."}\n";

            $output_css .= ".np-block-title .np-cat-". esc_attr( $cat_id ) ." { color: ". esc_attr( $cat_color ) ."}\n";
        }
    }
    
    if ( $news_portal_site_title_option == 'false' ) {
        $output_css .=".site-title, .site-description {
                    position: absolute;
                    clip: rect(1px, 1px, 1px, 1px);
                }\n";
    } else {
        $output_css .=".site-title a, .site-description {
                    color:". esc_attr( $news_portal_site_title_color ) .";
                }\n";
    }
    
    $output_css .= ".navigation .nav-links a,.bttn,button,input[type='button'],input[type='reset'],input[type='submit'],.navigation .nav-links a:hover,.bttn:hover,button,input[type='button']:hover,input[type='reset']:hover,input[type='submit']:hover,.widget_search .search-submit,.edit-link .post-edit-link,.reply .comment-reply-link,.np-top-header-wrap,.np-header-menu-wrapper,#site-navigation ul.sub-menu, #site-navigation ul.children,.np-header-menu-wrapper::before, .np-header-menu-wrapper::after,.np-header-search-wrapper .search-form-main .search-submit,.news_portal_slider .lSAction > a:hover,.news_portal_default_tabbed ul.widget-tabs li,.np-full-width-title-nav-wrap .carousel-nav-action .carousel-controls:hover,.news_portal_social_media .social-link a,.np-archive-more .np-button:hover,.error404 .page-title,#np-scrollup,.news_portal_featured_slider .slider-posts .lSAction > a:hover,.home #masthead .np-home-icon a,#masthead .np-home-icon a:hover,#masthead #site-navigation ul li:hover > a, #masthead #site-navigation ul li.current-menu-item > a, #masthead #site-navigation ul li.current_page_item > a, #masthead #site-navigation ul li.current-menu-ancestor > a,.news_portal_lite_featured_slider .slider-posts .lSAction > a:hover{ background: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";

    $output_css .= ".home .np-home-icon a, .np-home-icon a:hover,#site-navigation ul li:hover > a, #site-navigation ul li.current-menu-item > a,#site-navigation ul li.current_page_item > a,#site-navigation ul li.current-menu-ancestor > a,.news_portal_default_tabbed ul.widget-tabs li.ui-tabs-active, .news_portal_default_tabbed ul.widget-tabs li:hover { background: ". esc_attr( $news_portal_lite_theme_hover_color ) ."}\n";

    $output_css .= ".np-header-menu-block-wrap::before, .np-header-menu-block-wrap::after { border-right-color: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";

    $output_css .= "a,a:hover,a:focus,a:active,.widget a:hover,.widget a:hover::before,.widget li:hover::before,.entry-footer a:hover,.comment-author .fn .url:hover,#cancel-comment-reply-link,#cancel-comment-reply-link:before,.logged-in-as a,.np-slide-content-wrap .post-title a:hover,#top-footer .widget a:hover,#top-footer .widget a:hover:before,#top-footer .widget li:hover:before,.news_portal_featured_posts .np-single-post .np-post-content .np-post-title a:hover,.news_portal_fullwidth_posts .np-single-post .np-post-title a:hover,.news_portal_block_posts .layout3 .np-primary-block-wrap .np-single-post .np-post-title a:hover,.news_portal_featured_posts .layout2 .np-single-post-wrap .np-post-content .np-post-title a:hover,.np-block-title,.widget-title,.page-header .page-title,.np-related-title,.np-post-meta span:hover,.np-post-meta span a:hover,.news_portal_featured_posts .layout2 .np-single-post-wrap .np-post-content .np-post-meta span:hover,.news_portal_featured_posts .layout2 .np-single-post-wrap .np-post-content .np-post-meta span a:hover,.np-post-title.small-size a:hover,#footer-navigation ul li a:hover,.entry-title a:hover,.entry-meta span a:hover,.entry-meta span:hover,.np-post-meta span:hover, .np-post-meta span a:hover, .news_portal_featured_posts .np-single-post-wrap .np-post-content .np-post-meta span:hover, .news_portal_featured_posts .np-single-post-wrap .np-post-content .np-post-meta span a:hover,.news_portal_featured_slider .featured-posts .np-single-post .np-post-content .np-post-title a:hover,.news_portal_lite_featured_slider .featured-posts .np-single-post .np-post-content .np-post-title a:hover,.np-slide-content-wrap .post-title a:hover, .news_portal_featured_posts .np-single-post .np-post-content .np-post-title a:hover, .news_portal_carousel .np-single-post .np-post-title a:hover, .news_portal_block_posts .layout3 .np-primary-block-wrap .np-single-post .np-post-title a:hover { color: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";

    $output_css .= ".navigation .nav-links a,.bttn,button,input[type='button'],input[type='reset'],input[type='submit'],.widget_search .search-submit,.np-archive-more .np-button:hover { border-color: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";

    $output_css .= ".comment-list .comment-body,.np-header-search-wrapper .search-form-main { border-top-color: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";
    
    $output_css .= ".np-header-search-wrapper .search-form-main:before { border-bottom-color: ". esc_attr( $news_portal_lite_theme_color ) ."}\n";

    $output_css .= "@media (max-width: 768px) { #site-navigation,.main-small-navigation li.current-menu-item > .sub-toggle i { background: ". esc_attr( $news_portal_lite_theme_color ) ." !important } }\n";
        
    $refine_output_css = news_portal_css_strip_whitespace( $output_css );

    wp_add_inline_style( 'news-portal-lite-style', $refine_output_css );
    
}

/*-------------------------------------------------------------------------------------------------------------------------------*/
/**
 * Managed the widget sections
 *
 * @since 1.0.0
 */
add_action( 'widgets_init', 'news_portal_lite_register_widgets', 11 );

function news_portal_lite_register_widgets() {
    
    unregister_widget( 'News_Portal_Featured_Slider' );
    
    register_widget( 'News_Portal_Lite_Featured_Slider' );
    
}

/**
 * Load required files for widgets
 */
require get_stylesheet_directory() . '/inc/widgets/npl-featured-slider.php';