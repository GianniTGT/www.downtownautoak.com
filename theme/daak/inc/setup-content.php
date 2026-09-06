<?php
/**
 * What the theme builds for itself the first time it is switched on.
 *
 * Nine pages, a menu, and an import of the seed file if the site has one. The
 * pages are created with their content in the database, not in the templates, so
 * the owner can edit every word without a deploy — and so the theme carries no
 * dealer's prose either.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function daak_pages() {
	return array(
		'home' => array(
			'title'    => 'Home',
			'template' => '',
			'content'  => '',
		),
		'where-you-can-drive' => array(
			'title'    => 'Where you can drive',
			'template' => 'templates/page-drive.php',
			'content'  => "<p>The national rental chains forbid driving on unpaved roads. That rules out the Dalton, the Denali Highway, the McCarthy Road, the Steese, the Taylor and the Elliott — which is to say most of the reason people come to Alaska. Visitors usually discover this after they land.</p>\n<p>This page says exactly where our vehicles may go. Whatever it says, the rental agreement says in the same words.</p>",
		),
		'rates' => array(
			'title'    => 'Rates and what is included',
			'template' => 'templates/page-rates.php',
			'content'  => "<p>The rate you see is the rate we charge. What is added, and what is not, is set out below.</p>",
		),
		'winter-driving' => array(
			'title'    => 'Winter driving in Alaska',
			'template' => 'templates/page-winter.php',
			'content'  => "<p>Winter here is a set of practical questions rather than a warning label: what is on the tires, where the vehicle plugs in overnight, how much daylight there is, and what to carry.</p>",
		),
		'rent-to-own' => array(
			'title'    => 'Rent to own',
			'template' => 'templates/page-rent-to-own.php',
			'content'  => "<p>We sell cars as well as rent them. If you rent one and decide to buy it, part of what you paid to rent comes off the price. It is a test drive that lasts a week, and no rental company can offer it.</p>",
		),
		'about' => array(
			'title'    => 'About and contact',
			'template' => 'templates/page-contact.php',
			'content'  => "<p>Same lot, same people, same mechanic as the sales side of the business. The cars we rent are the cars we service.</p>",
		),
		'policies' => array(
			'title'    => 'Rental agreement and policies',
			'template' => 'templates/page-policies.php',
			'content'  => "<p>The rental agreement is the document you sign at the counter. The terms that matter before you get there are on this page, in the same words.</p>",
		),
		'privacy' => array(
			'title'    => 'Privacy',
			'template' => 'templates/page-privacy.php',
			'content'  => "<p>What this website collects, why, and what it deliberately does not collect.</p>",
		),
	);
}

add_action( 'after_switch_theme', 'daak_first_run' );

function daak_first_run() {
	daak_import_seed();

	$ids = array();
	foreach ( daak_pages() as $slug => $page ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $page['title'],
			'post_content' => $page['content'],
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			if ( $page['template'] ) { update_post_meta( $id, '_wp_page_template', $page['template'] ); }
			$ids[ $slug ] = $id;
		}
	}

	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( ! empty( $ids['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy'] );
	}

	daak_build_menu( $ids );
	flush_rewrite_rules();
}

/** A menu in the order a visitor needs it, built once and then theirs to edit. */
function daak_build_menu( $ids ) {
	if ( has_nav_menu( 'primary' ) ) { return; }
	$menu_id = wp_create_nav_menu( 'Primary' );
	if ( is_wp_error( $menu_id ) ) { return; }

	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'  => 'Fleet',
		'menu-item-url'    => daak_fleet_url(),
		'menu-item-status' => 'publish',
	) );
	foreach ( array( 'where-you-can-drive', 'rates', 'winter-driving', 'rent-to-own', 'about' ) as $slug ) {
		if ( empty( $ids[ $slug ] ) ) { continue; }
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-object-id' => $ids[ $slug ],
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		) );
	}
	set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id ) );
}

/** The menu a visitor gets before anybody has built one. */
function daak_fallback_menu() {
	$items = array(
		daak_fleet_url()                        => 'Fleet',
		daak_page_url( 'where-you-can-drive' )   => 'Where you can drive',
		daak_page_url( 'rates' )                 => 'Rates',
		daak_page_url( 'winter-driving' )        => 'Winter driving',
		daak_page_url( 'rent-to-own' )           => 'Rent to own',
		daak_page_url( 'about' )                 => 'Contact',
	);
	echo '<ul>';
	foreach ( $items as $url => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}
