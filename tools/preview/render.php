<?php
/**
 * Renders every front-end template to static HTML with fixture data.
 *
 *   php tools/preview/render.php [output-dir]
 *
 * The fixtures are a plausible small Alaskan fleet and the seed profile. They
 * are fixtures: nothing here is written into the theme, and the theme reads all
 * of it through the same profile record a live site uses.
 */

$root = dirname( __DIR__, 2 );
$out  = $argv[1] ?? __DIR__ . '/out';
@mkdir( $out, 0777, true );
@mkdir( $out . '/img', 0777, true );

require __DIR__ . '/wp-shim.php';

define( 'DAAK_DIR', $root . '/theme/daak' );
define( 'DAAK_URI', '../../../theme/daak' );
define( 'DAAK_VERSION', 'preview' );

$GLOBALS['daak_css'] = DAAK_URI . '/style.css';
$GLOBALS['daak_js']  = DAAK_URI . '/assets/js/site.js';

foreach ( array( 'profile', 'post-types', 'availability', 'booking', 'template-tags', 'setup-content', 'schema' ) as $file ) {
	require_once DAAK_DIR . '/inc/' . $file . '.php';
}

/* ------------------------------------------------------------- fixtures */

$seed = json_decode( (string) file_get_contents( $root . '/deploy/daak-seed.json' ), true );
daak_fixture_option( 'daak_profile', $seed['profile'] );

// The owner's answers, as they would look on a site that has been through the
// checklist. The seed ships them empty on purpose; a preview with every policy
// blank shows nothing about how the pages read when they are filled in.
daak_fixture_option( 'daak_policy', array_merge( $seed['policy'], array(
	'gravel_sentence'   => 'Our vehicles may be driven on the Denali Highway and the McCarthy Road. The Dalton north of Coldfoot needs our written permission before you leave the lot.',
	'response_promise'  => 'A person answers every request between 8am and 8pm, usually within the hour.',
	'min_age'           => '21',
	'young_driver'      => 'Drivers aged 21 to 24 pay a young-driver surcharge.',
	'deposit'           => 'A refundable deposit is taken at the counter, on a card, when you collect the vehicle.',
	'fuel'              => 'Collect it full, bring it back full.',
	'mileage'           => 'Unlimited miles on every vehicle. No zone limits inside Alaska.',
	'pets'              => 'Dogs are welcome. A cleaning fee applies if the vehicle needs more than a vacuum.',
	'smoking'           => 'No smoking or vaping in any vehicle.',
	'second_driver'     => 'A second driver may be added at the counter with a valid licence.',
	'one_way'           => 'One-way to Fairbanks, Seward, Homer and Whittier: ask us. We do it, and the fee depends on getting the vehicle back.',
	'airport'           => 'We are off-airport. Telephone us when you land and we collect you.',
	'ferry'             => 'Vehicles may travel on Alaska Marine Highway ferries with our permission, arranged before you book the sailing.',
	'canada'            => 'Driving the Alcan into Canada is allowed with notice. We issue the Canadian non-resident inter-province insurance card, and it must stay in the vehicle.',
	'rto_credit'        => 'Half of what you pay to rent comes off the purchase price if you buy the vehicle within thirty days.',
	'insurance_note'    => 'Your own policy or a card benefit may already cover a rental. Bring the details and we will look at them with you.',
	'studs_window'      => 'Studded tires are permitted north of 60°N from mid-September to the end of April. Ask us for this winter\'s exact dates.',
	'studs_verified_on' => '',
	'moose_note'        => 'The most common serious accident here is a moose, not ice. They move at dusk and dawn, they stand taller than your headlights, and they step out of the trees without warning. Slow down at first and last light, watch the verges rather than the road, and if one is standing in the carriageway, stop and wait.',
	'block_heater'      => 'Most of our vehicles have an engine block heater with a cord behind the grille. On a cold night you plug it into an ordinary outlet — hotels, houses and many car parks in Anchorage have them — and it starts properly in the morning.',
) ) );

daak_fixture_option( 'daak_tax', array( 'state_pct' => '', 'muni_pct' => '', 'mode' => 'add', 'note' => '', 'verified_on' => '' ) );

daak_fixture_option( 'daak_roads', array(
	array( 'road' => 'Dalton Highway (Coldfoot, Prudhoe Bay)', 'majors' => 'Forbidden', 'verdict' => 'conditional', 'note' => '400 miles of gravel and two spare tires. Ask before you leave the lot.' ),
	array( 'road' => 'Denali Highway', 'majors' => 'Forbidden', 'verdict' => 'allowed', 'note' => '' ),
	array( 'road' => 'McCarthy Road', 'majors' => 'Forbidden', 'verdict' => 'allowed', 'note' => 'Railway spikes still surface. Check the spare before you go.' ),
	array( 'road' => 'Steese, Taylor and Elliott Highways', 'majors' => 'Forbidden', 'verdict' => 'ask', 'note' => '' ),
	array( 'road' => 'Every paved road in Alaska', 'majors' => 'Allowed', 'verdict' => 'allowed', 'note' => '' ),
) );

daak_fixture_option( 'daak_extras', array(
	array( 'slug' => 'roof-box', 'label' => 'Roof box or rack', 'price' => '12', 'unit' => 'day', 'note' => '' ),
	array( 'slug' => 'camping', 'label' => 'Camping kit', 'price' => '25', 'unit' => 'day', 'note' => 'Tent, bags, stove, cooler, chairs' ),
	array( 'slug' => 'bear-spray', 'label' => 'Bear spray', 'price' => '15', 'unit' => 'rental', 'note' => 'Cannot fly home with you' ),
	array( 'slug' => 'child-seat', 'label' => 'Child seat', 'price' => '8', 'unit' => 'day', 'note' => '' ),
	array( 'slug' => 'driver-2', 'label' => 'Second driver', 'price' => '', 'unit' => 'rental', 'note' => 'Free — bring the licence' ),
	array( 'slug' => 'inreach', 'label' => 'Satellite messenger', 'price' => '10', 'unit' => 'day', 'note' => 'No mobile signal on most of the gravel' ),
) );

daak_fixture_option( 'daak_seasons', array(
	array( 'label' => 'Summer', 'from' => '05-15', 'to' => '09-15', 'multiplier' => '1' ),
	array( 'label' => 'Winter', 'from' => '09-16', 'to' => '05-14', 'multiplier' => '0.8' ),
) );

/* ------------------------------------------------------- placeholder art */

function daak_placeholder( $file, $label, $hueShift = 0 ) {
	$tones = array(
		array( '#103071', '#1A4EA8' ),
		array( '#15418D', '#3568C4' ),
		array( '#0C2A63', '#2A5FB8' ),
		array( '#1A4EA8', '#5B8AD6' ),
	);
	list( $a, $b ) = $tones[ $hueShift % count( $tones ) ];
	$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 533" width="800" height="533">
  <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="$a"/><stop offset="1" stop-color="$b"/></linearGradient></defs>
  <rect width="800" height="533" fill="url(#g)"/>
  <path d="M0 380 L150 250 L250 330 L380 200 L520 340 L640 260 L800 390 L800 533 L0 533 Z" fill="#0B2454" opacity=".55"/>
  <path d="M0 430 L180 330 L320 420 L470 320 L620 420 L800 340 L800 533 L0 533 Z" fill="#081C42" opacity=".6"/>
  <g fill="#E9EEFA" opacity=".92" transform="translate(250 300) scale(1.5)">
    <path d="M12 44 L22 22 C25 16 30 14 36 14 h48 c6 0 11 2 14 8 l10 22" fill="none" stroke="#E9EEFA" stroke-width="5" stroke-linecap="round"/>
    <rect x="6" y="44" width="120" height="20" rx="6" fill="none" stroke="#E9EEFA" stroke-width="5"/>
    <circle cx="34" cy="66" r="10" fill="none" stroke="#E9EEFA" stroke-width="5"/>
    <circle cx="98" cy="66" r="10" fill="none" stroke="#E9EEFA" stroke-width="5"/>
  </g>
  <text x="400" y="500" text-anchor="middle" font-family="Barlow, sans-serif" font-size="22" fill="#C7D4EE">$label</text>
</svg>
SVG;
	file_put_contents( $file, $svg );
}

/* ---------------------------------------------------------- the fleet */

$fleet = array(
	array( 'y' => 2019, 'make' => 'Toyota', 'model' => '4Runner SR5', 'class' => 'suv', 'drive' => '4wd', 'seats' => 5, 'doors' => 4, 'day' => 149, 'week' => 899, 'month' => 3200, 'gravel' => 1, 'winter' => array( 'studs', 'block_heater' ), 'min' => 3 ),
	array( 'y' => 2021, 'make' => 'Ford', 'model' => 'F-150 XLT', 'class' => 'pickup', 'drive' => '4wd', 'seats' => 5, 'doors' => 4, 'day' => 169, 'week' => 1020, 'month' => 3600, 'gravel' => 1, 'winter' => array( 'winter_tires', 'block_heater', 'remote_start' ), 'min' => 3 ),
	array( 'y' => 2020, 'make' => 'Subaru', 'model' => 'Outback', 'class' => 'wagon', 'drive' => 'awd', 'seats' => 5, 'doors' => 5, 'day' => 119, 'week' => 719, 'month' => 2500, 'gravel' => 1, 'winter' => array( 'studs', 'block_heater' ), 'min' => 2 ),
	array( 'y' => 2018, 'make' => 'Chevrolet', 'model' => 'Suburban', 'class' => 'suv', 'drive' => '4wd', 'seats' => 8, 'doors' => 5, 'day' => 189, 'week' => 1140, 'month' => 3900, 'gravel' => 1, 'winter' => array( 'winter_tires', 'block_heater' ), 'min' => 3 ),
	array( 'y' => 2022, 'make' => 'Toyota', 'model' => 'Camry', 'class' => 'sedan', 'drive' => 'fwd', 'seats' => 5, 'doors' => 4, 'day' => 79, 'week' => 469, 'month' => 1600, 'gravel' => 0, 'winter' => array( 'winter_tires', 'block_heater' ), 'min' => 1 ),
	array( 'y' => 2019, 'make' => 'Dodge', 'model' => 'Grand Caravan', 'class' => 'van', 'drive' => 'fwd', 'seats' => 7, 'doors' => 5, 'day' => 109, 'week' => 659, 'month' => 2300, 'gravel' => 0, 'winter' => array( 'winter_tires' ), 'min' => 2 ),
);

$vehicle_ids = array();
foreach ( $fleet as $i => $v ) {
	$slug = 'v' . ( $i + 1 );
	daak_placeholder( $out . '/img/' . $slug . '.svg', $v['y'] . ' ' . $v['make'] . ' ' . $v['model'], $i );
	$vehicle_ids[] = daak_fixture_post( array(
		'post_type'    => 'daak_vehicle',
		'post_title'   => $v['y'] . ' ' . $v['make'] . ' ' . $v['model'],
		'post_name'    => strtolower( $v['make'] . '-' . str_replace( ' ', '-', $v['model'] ) ),
		'post_content' => '<p>Serviced in our own workshop and checked against open safety recalls before it goes out. Ask us what it is like on the Denali Highway — we have driven this one out there.</p>',
		'menu_order'   => $i,
		'thumb'        => 'img/' . $slug . '.svg',
		'meta'         => array(
			'dv_year' => $v['y'], 'dv_make' => $v['make'], 'dv_model' => $v['model'],
			'dv_vin' => 'PREVIEWVIN' . ( 1000 + $i ), 'dv_class' => $v['class'], 'dv_drive' => $v['drive'],
			'dv_transmission' => 'automatic', 'dv_fuel' => 'petrol', 'dv_seats' => $v['seats'], 'dv_doors' => $v['doors'],
			'dv_mileage_policy' => 'Unlimited miles', 'dv_gravel' => $v['gravel'],
			'dv_winter' => json_encode( $v['winter'] ),
			'dv_rate_day' => $v['day'], 'dv_rate_week' => $v['week'], 'dv_rate_month' => $v['month'],
			'dv_min_days' => $v['min'], 'dv_status' => 'active',
			'dv_recall_checked' => gmdate( 'Y-m-d', strtotime( '-3 weeks' ) ),
			'dv_gallery' => '[]',
			// One vehicle is out, so the fleet page has something true to say.
			'dv_blocks' => 3 === $i ? json_encode( array( array(
				'from' => gmdate( 'Y-m-d', strtotime( '+2 days' ) ),
				'to'   => gmdate( 'Y-m-d', strtotime( '+9 days' ) ),
				'reason' => 'booked #1041', 'request' => 1041,
			) ) ) : '[]',
		),
	) );
}

/* ---------------------------------------------------------- the pages */

$pages = array();
foreach ( daak_pages() as $slug => $page ) {
	$pages[ $slug ] = daak_fixture_post( array(
		'post_type'    => 'page',
		'post_name'    => $slug,
		'post_title'   => $page['title'],
		'post_content' => $page['content'],
	) );
}

/* -------------------------------------------------------------- render */

function daak_render( $template, $file, $loop, $ctx, $title, $query = array() ) {
	global $out;
	$_GET = $query;
	daak_set_loop( $loop, $ctx );
	$GLOBALS['daak_title'] = $title;
	ob_start();
	include DAAK_DIR . '/' . $template;
	$html = ob_get_clean();
	file_put_contents( $out . '/' . $file, $html );
	printf( "%-22s %-34s %6d bytes\n", $file, $template, strlen( $html ) );
	return $html;
}

$all_vehicles = array_map( function ( $id ) { return $GLOBALS['daak_posts'][ $id ]; }, $vehicle_ids );
$page_post    = function ( $slug ) use ( $pages ) { return array( $GLOBALS['daak_posts'][ $pages[ $slug ] ] ); };

$from = gmdate( 'Y-m-d', strtotime( '+4 days' ) );
$to   = gmdate( 'Y-m-d', strtotime( '+11 days' ) );

daak_render( 'front-page.php', 'home.html', $page_post( 'home' ), array( 'singular' => 'page', 'front' => true ), 'Gravel-road approved vehicle rental in Anchorage' );
daak_render( 'archive-daak_vehicle.php', 'fleet.html', $all_vehicles, array( 'archive' => true ), 'The fleet' );
daak_render( 'archive-daak_vehicle.php', 'fleet-dates.html', $all_vehicles, array( 'archive' => true ), 'The fleet — with dates', array( 'from' => $from, 'to' => $to ) );
daak_render( 'single-daak_vehicle.php', 'vehicle-1.html', array( $all_vehicles[0] ), array( 'singular' => 'daak_vehicle' ), '2019 Toyota 4Runner SR5', array( 'from' => $from, 'to' => $to ) );
daak_render( 'single-daak_vehicle.php', 'vehicle-4.html', array( $all_vehicles[3] ), array( 'singular' => 'daak_vehicle' ), '2018 Chevrolet Suburban', array( 'from' => gmdate( 'Y-m-d', strtotime( '+3 days' ) ), 'to' => gmdate( 'Y-m-d', strtotime( '+6 days' ) ) ) );
daak_render( 'templates/page-drive.php', 'drive.html', $page_post( 'where-you-can-drive' ), array( 'singular' => 'page' ), 'Where you can drive' );
daak_render( 'templates/page-rates.php', 'rates.html', $page_post( 'rates' ), array( 'singular' => 'page' ), 'Rates and what is included' );
daak_render( 'templates/page-winter.php', 'winter.html', $page_post( 'winter-driving' ), array( 'singular' => 'page' ), 'Winter driving in Alaska' );
daak_render( 'templates/page-rent-to-own.php', 'rent-to-own.html', $page_post( 'rent-to-own' ), array( 'singular' => 'page' ), 'Rent to own' );
daak_render( 'templates/page-contact.php', 'contact.html', $page_post( 'about' ), array( 'singular' => 'page' ), 'About and contact' );
daak_render( 'templates/page-policies.php', 'policies.html', $page_post( 'policies' ), array( 'singular' => 'page' ), 'Rental agreement and policies' );
daak_render( 'templates/page-privacy.php', 'privacy.html', $page_post( 'privacy' ), array( 'singular' => 'page' ), 'Privacy' );
daak_render( '404.php', '404.html', array(), array(), 'Not found' );

echo "\nRendered into $out\n";
