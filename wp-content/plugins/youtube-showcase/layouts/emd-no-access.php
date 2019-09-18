<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
}

get_header('emdplugins'); 
emd_get_template_part('youtube-showcase', 'no-access');
get_footer('emdplugins');
