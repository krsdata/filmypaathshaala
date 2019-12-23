<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package CoverNews
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">

<meta http-equiv="Content-Security-Policy" content="img-src https://*;">
<?php
if($_SERVER['REDIRECT_URL']==""){
?>
  <meta property='og:title' content="Entertainment, Television Bollywood, Memes, Webseries, Promotion and Branding for Actors, New Movies and TV Shows - FILMY PAATHSHAALA"/>
  <meta property='og:site_name' content='FILMY PAATHSHAALA'/>
  <meta name="description" content="Filmypaathshaala is an online information related to entertainment, memes, branding, promotion, bollywood, films, television programs, footage and news "/>
  <meta name="og:description" content="Filmypaathshaala is an online information related to entertainment, memes, branding, promotion, bollywood, films, television programs, footage and News"/>
<?php } else { ?>

  <meta property='og:title' content="<?php echo the_title(); ?>"/>
  <meta property='og:site_name' content='FILMY PAATHSHAALA'/>
  <meta name="description" content="<?php echo the_title(); ?>"/>
  <meta name="og:description" content="Filmypaathshaala is an online information related to entertainment, memes, branding, promotion, bollywood, films, television programs, footage and News"/>
 
<?php } ?>


  <meta name="keywords" content="entertainment, segment, promotion, branding, bollywood, movies, films, actors, actresses, hollywood, movies, television, rating & review, webseries, footage, News, filmypaathshaala"/>
  <meta name="author" content="Filmypaathshaala">

 <script type="application/ld+json">{
  "@context": "http://schema.org",
  "@type": "Organization",
  "name": "Filmypaathshaala",
  "url": "https://www.filmypaathshaala.com/",
  "sameAs": [
    "https://www.facebook.com/iSchoolLifeVsCollegeLife/",
    "https://twitter.com/search?q=iSchoollifevscollegelife",
    "https://www.instagram.com/ischoollifevscollegelife/",  
    "https://www.youtube.com/user/vishal00710/videos"
  ]
}</script>

    <?php wp_head(); ?>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-148489748-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-148489748-1');
</script>


</head>

<body <?php body_class(); ?>>


<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Skip to content', 'covermag'); ?></a>

<?php covernews_get_block('header-layout'); ?>

    <div id="content" class="container">
<?php
    do_action('covernews_action_get_breadcrumb');
