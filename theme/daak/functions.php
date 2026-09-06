<?php
/**
 * Downtown Auto AK — theme bootstrap.
 *
 * The rental half of a dealership that already sells cars on another domain.
 * Same badge family, same navy, one accent colour of its own, and a data model
 * that keeps rental money out of the sale-margin reports.
 *
 * Nothing in this theme knows the dealer's name, address, telephone, email or
 * logo. Every one of them is read from the profile record (inc/profile.php), so
 * the theme runs for the next dealership without a code edit. There is a test
 * for that: `grep -ril "907-" theme/` must come back empty.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DAAK_VERSION', '1.0.0' );
define( 'DAAK_DIR', get_template_directory() );
define( 'DAAK_URI', get_template_directory_uri() );

require_once DAAK_DIR . '/inc/profile.php';
require_once DAAK_DIR . '/inc/post-types.php';
require_once DAAK_DIR . '/inc/vehicle-meta.php';
require_once DAAK_DIR . '/inc/availability.php';
require_once DAAK_DIR . '/inc/booking.php';
require_once DAAK_DIR . '/inc/requests-admin.php';
require_once DAAK_DIR . '/inc/template-tags.php';
require_once DAAK_DIR . '/inc/rest.php';
require_once DAAK_DIR . '/inc/schema.php';
require_once DAAK_DIR . '/inc/privacy-boundary.php';
require_once DAAK_DIR . '/inc/setup-content.php';
require_once DAAK_DIR . '/inc/checklist.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 260, 'flex-height' => true, 'flex-width' => true ) );
	add_image_size( 'daak_card', 800, 533, true );
	add_image_size( 'daak_hero', 1600, 900, true );
	register_nav_menus( array(
		'primary' => 'Primary menu',
		'legal'   => 'Footer legal menu',
	) );
	$GLOBALS['content_width'] = 1180;
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'daak-fonts',
		'https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'daak', get_stylesheet_uri(), array( 'daak-fonts' ), DAAK_VERSION );
	wp_enqueue_script( 'daak', DAAK_URI . '/assets/js/site.js', array(), DAAK_VERSION, true );

	// Over 80% of this traffic is a phone, frequently on airport wifi. The script
	// gets the numbers it needs inline rather than making a second request for them.
	wp_localize_script( 'daak', 'DAAK', array(
		'rest'    => esc_url_raw( rest_url( 'daak/v1' ) ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
		'extras'  => daak_extras(),
		'tax'     => daak_tax_settings(),
		'fleetUrl'=> esc_url_raw( get_post_type_archive_link( 'daak_vehicle' ) ),
		'today'   => wp_date( 'Y-m-d' ),
	) );
} );

add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
} );

/** The fleet archive shows every live vehicle; a rental fleet is not a blog roll. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) { return; }
	if ( $q->is_post_type_archive( 'daak_vehicle' ) ) {
		$q->set( 'posts_per_page', 48 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$q->set( 'meta_query', array(
			'relation' => 'OR',
			array( 'key' => 'dv_status', 'value' => 'retired', 'compare' => '!=' ),
			array( 'key' => 'dv_status', 'compare' => 'NOT EXISTS' ),
		) );
	}
} );

/** The badge is the favicon, and the ICO carries the size-appropriate drawing. */
add_action( 'wp_head', function () {
	if ( has_site_icon() ) { return; } // an uploaded site icon wins, as an owner expects
	$ico = DAAK_URI . '/assets/img/favicon.ico';
	$svg = DAAK_URI . '/assets/img/daak-monogram.svg';
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( $ico ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $svg ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( DAAK_URI . '/assets/img/daak-mark-512.png' ) );
}, 2 );

/** Body classes the stylesheet leans on. */
add_filter( 'body_class', function ( $c ) {
	if ( is_singular( 'daak_vehicle' ) ) { $c[] = 'vehicle-page'; }
	return $c;
} );
