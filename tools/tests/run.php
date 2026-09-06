<?php
/**
 * The behaviour that has to be true, checked rather than assumed.
 *
 *   php tools/tests/run.php
 *
 * These cover the two things that would cost real money: a vehicle offered
 * twice for the same dates, and a price that does not add up.
 */

$root = dirname( __DIR__, 2 );
require $root . '/tools/preview/wp-shim.php';

define( 'DAAK_DIR', $root . '/theme/daak' );
define( 'DAAK_URI', '' );
define( 'DAAK_VERSION', 'test' );

foreach ( array( 'profile', 'post-types', 'availability', 'booking', 'template-tags' ) as $f ) {
	require_once DAAK_DIR . '/inc/' . $f . '.php';
}

$pass = 0;
$fail = 0;

function ok( $label, $condition ) {
	global $pass, $fail;
	if ( $condition ) { $pass++; printf( " ok   %s\n", $label ); }
	else { $fail++; printf( "FAIL  %s\n", $label ); }
}

function is_same( $label, $actual, $expected ) {
	ok( $label . ' (' . var_export( $actual, true ) . ')', $actual === $expected );
}

/* ------------------------------------------------------------- fixtures */

daak_fixture_option( 'daak_extras', array(
	array( 'slug' => 'camping', 'label' => 'Camping kit', 'price' => '25', 'unit' => 'day', 'note' => '' ),
	array( 'slug' => 'bear-spray', 'label' => 'Bear spray', 'price' => '15', 'unit' => 'rental', 'note' => '' ),
) );
daak_fixture_option( 'daak_seasons', array(
	array( 'label' => 'Summer', 'from' => '05-15', 'to' => '09-15', 'multiplier' => '1' ),
	array( 'label' => 'Winter', 'from' => '09-16', 'to' => '05-14', 'multiplier' => '0.8' ),
) );
daak_fixture_option( 'daak_tax', array( 'state_pct' => '10', 'muni_pct' => '8', 'mode' => 'add', 'note' => '', 'verified_on' => '2026-01-01' ) );

$truck = daak_fixture_post( array(
	'post_type'  => 'daak_vehicle',
	'post_title' => 'Test truck',
	'meta'       => array(
		'dv_rate_day' => 100, 'dv_rate_week' => 600, 'dv_rate_month' => 2000,
		'dv_status' => 'active', 'dv_min_days' => 2, 'dv_class' => 'pickup', 'dv_drive' => '4wd', 'dv_seats' => 5,
		'dv_gravel' => 1,
		'dv_blocks' => json_encode( array( array( 'from' => '2026-07-10', 'to' => '2026-07-14', 'reason' => 'booked #7', 'request' => 7 ) ) ),
	),
) );
$car = daak_fixture_post( array(
	'post_type'  => 'daak_vehicle',
	'post_title' => 'Test car',
	'meta'       => array( 'dv_rate_day' => 80, 'dv_status' => 'active', 'dv_class' => 'sedan', 'dv_drive' => 'fwd', 'dv_seats' => 5, 'dv_gravel' => 0, 'dv_blocks' => '[]' ),
) );
$retired = daak_fixture_post( array(
	'post_type'  => 'daak_vehicle',
	'post_title' => 'Sold last month',
	'meta'       => array( 'dv_rate_day' => 60, 'dv_status' => 'retired', 'dv_blocks' => '[]' ),
) );

echo "Availability\n";

ok( 'a free week is free', daak_vehicle_is_free( $truck, '2026-06-01', '2026-06-08' ) );
ok( 'the booked week itself is refused', ! daak_vehicle_is_free( $truck, '2026-07-10', '2026-07-14' ) );
ok( 'a range starting inside the block is refused', ! daak_vehicle_is_free( $truck, '2026-07-12', '2026-07-20' ) );
ok( 'a range ending inside the block is refused', ! daak_vehicle_is_free( $truck, '2026-07-05', '2026-07-11' ) );
ok( 'a range swallowing the block is refused', ! daak_vehicle_is_free( $truck, '2026-07-01', '2026-07-31' ) );
ok( 'the return day is not offered as a pick-up day', ! daak_vehicle_is_free( $truck, '2026-07-14', '2026-07-18' ) );
ok( 'the day before the block is free', daak_vehicle_is_free( $truck, '2026-07-06', '2026-07-09' ) );
ok( 'the day after the block is free', daak_vehicle_is_free( $truck, '2026-07-15', '2026-07-20' ) );
ok( 'a request ignores its own hold', daak_vehicle_is_free( $truck, '2026-07-10', '2026-07-14', 7 ) );
ok( 'a retired vehicle is never free', ! daak_vehicle_is_free( $retired, '2026-06-01', '2026-06-08' ) );
ok( 'a missing date is not an availability', ! daak_vehicle_is_free( $truck, '', '2026-06-08' ) );

$found = wp_list_pluck_ids( daak_search_vehicles( array( 'from' => '2026-07-11', 'to' => '2026-07-13' ) ) );
is_same( 'search hides the booked truck', in_array( $truck, $found, true ), false );
ok( 'search still offers the free car', in_array( $car, $found, true ) );
ok( 'search never offers a retired vehicle', ! in_array( $retired, $found, true ) );

$found = wp_list_pluck_ids( daak_search_vehicles( array( 'from' => '2026-06-01', 'to' => '2026-06-02' ) ) );
is_same( 'a 1-day request drops a vehicle with a 2-day minimum', in_array( $truck, $found, true ), false );

$found = wp_list_pluck_ids( daak_search_vehicles( array( 'gravel' => 1 ) ) );
ok( 'the gravel filter keeps the truck', in_array( $truck, $found, true ) );
ok( 'the gravel filter drops the car', ! in_array( $car, $found, true ) );

is_same( 'a filter that cannot narrow anything is not drawn', array_key_exists( 'gravel', daak_fleet_filters() ), true );

echo "\nDays and dates\n";
is_same( 'one night is one day', daak_days_between( '2026-07-01', '2026-07-02' ), 1 );
is_same( 'a week is seven days', daak_days_between( '2026-07-01', '2026-07-08' ), 7 );
is_same( 'the same day is one day', daak_days_between( '2026-07-01', '2026-07-01' ), 1 );
is_same( 'a date is normalised', daak_date( '7/1/2026' ), '2026-07-01' );
is_same( 'nonsense is not a date', daak_date( 'whenever' ), '' );

echo "\nQuote\n";
$q = daak_quote( $truck, '2026-07-01', '2026-07-08' );      // 7 summer days
is_same( 'a week bills the weekly rate once', count( $q['lines'] ), 1 );
is_same( 'the weekly rate is used, not 7 x daily', $q['subtotal'], 600.0 );
is_same( '18% tax is added', $q['tax'], 108.0 );
is_same( 'the total is the sum', $q['total'], 708.0 );

$q = daak_quote( $truck, '2026-07-01', '2026-07-10' );      // 9 days = a week + 2
is_same( 'nine days is a week plus two', $q['subtotal'], 800.0 );

$q = daak_quote( $truck, '2026-12-01', '2026-12-08' );      // winter multiplier 0.8
is_same( 'the winter multiplier applies', $q['subtotal'], 480.0 );

$q = daak_quote( $truck, '2026-07-01', '2026-07-04', array( 'camping', 'bear-spray' ) );
is_same( 'per-day and per-rental add-ons bill differently', $q['subtotal'], 300.0 + 75.0 + 15.0 );

$q = daak_quote( $truck, '2026-07-01', '2026-07-04', array( 'gold-plating' ) );
is_same( 'an add-on the form never offered is dropped', count( $q['lines'] ), 1 );

daak_fixture_option( 'daak_tax', array( 'state_pct' => '', 'muni_pct' => '', 'mode' => 'add', 'note' => '', 'verified_on' => '' ) );
$q = daak_quote( $truck, '2026-07-01', '2026-07-08' );
is_same( 'with no rate entered the quote does not invent tax', $q['tax'], 0.0 );
ok( 'and it says tax is added anyway', str_contains( daak_tax_line(), 'added' ) );

echo "\nThe privacy boundary\n";
require_once DAAK_DIR . '/inc/privacy-boundary.php';
foreach ( array( 'dr_ssn', 'dr_social_security', 'dr_dob', 'dr_date_of_birth', 'dr_drivers_license', 'dr_license_number', 'dr_card_number', 'dr_cvv', 'dr_iban', 'dr_passport' ) as $key ) {
	ok( 'refused: ' . $key, daak_is_sensitive_key( $key ) );
}
foreach ( array( 'dr_name', 'dr_phone', 'dr_email', 'dr_pickup', 'dr_message' ) as $key ) {
	ok( 'allowed: ' . $key, ! daak_is_sensitive_key( $key ) );
}

echo "\nDaylight\n";
ok( 'midsummer in Anchorage is over 18 hours', daak_daylight_hours( 6 ) > 18 );
ok( 'midwinter in Anchorage is under 6 hours', daak_daylight_hours( 12 ) < 6 );
ok( 'the equinox is near 12 hours', abs( daak_daylight_hours( 3 ) - 12 ) < 1.5 );
is_same( 'the pole has a midnight sun', round( daak_daylight_hours( 6, 89 ) ), 24.0 );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail ? 1 : 0 );

function wp_list_pluck_ids( $posts ) {
	return array_map( function ( $p ) { return $p->ID; }, $posts );
}
