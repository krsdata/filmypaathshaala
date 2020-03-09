<?php

su_add_shortcode(
	array(
		'id'       => 'feed',
		'callback' => 'su_shortcode_feed',
		'image'    => su_get_plugin_url() . 'admin/images/shortcodes/feed.svg',
		'name'     => __( 'RSS feed', 'shortcodes-ultimate' ),
		'type'     => 'single',
		'group'    => 'content other',
		'atts'     => array(
			'url'   => array(
				'values'  => array(),
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate' ),
				'desc'    => __( 'RSS feed URL', 'shortcodes-ultimate' ),
			),
			'limit' => array(
				'type'    => 'slider',
				'min'     => 1,
				'max'     => 20,
				'step'    => 1,
				'default' => 3,
				'name'    => __( 'Limit', 'shortcodes-ultimate' ),
				'desc'    => __( 'Number of items to show', 'shortcodes-ultimate' ),
			),
			'class' => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate' ),
				'default' => '',
			),
		),
		'desc'     => __( 'Feed grabber', 'shortcodes-ultimate' ),
		'icon'     => 'rss',
	)
);

function su_shortcode_feed( $atts = null, $content = null ) {

	$atts   = su_parse_shortcode_atts( 'feed', $atts );
	$feed   = null;
	$items  = array();
	$output = '';

	if ( ! filter_var( $atts['url'], FILTER_VALIDATE_URL ) ) {
		return su_error_message( 'Feed', __( 'invalid feed URL', 'shortcodes-ultimate' ) );
	}

	$feed = fetch_feed( $atts['url'] );

	if ( is_wp_error( $feed ) ) {
		return su_error_message( 'Feed', $feed->get_error_message() );
	}

	$items = $feed->get_items( 0, (int) $atts['limit'] );

	if ( ! count( $items ) ) {
		return su_error_message( 'Feed', __( 'no items in the feed', 'shortcodes-ultimate' ) );
	}

	foreach ( $items as $item ) {

		$output .= sprintf(
			'<li><a href="%s">%s</a></li>',
			$item->get_permalink(),
			$item->get_title()
		);

	}

	return sprintf(
		'<ul class="su-feed%s">%s</ul>',
		esc_attr( su_get_css_class( $atts ) ),
		$output
	);

}
