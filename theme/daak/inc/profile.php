<?php
/**
 * The profile record.
 *
 * The dealer's name, address, telephone numbers, email and logo are not
 * literals anywhere in this theme. They live here, in options, and every page
 * reads them from here. That is what lets the same theme run for the next
 * dealership without a code edit — and it is checkable: a grep for a telephone
 * number over the theme directory must come back empty.
 *
 * Seeding is deliberately outside the theme too: on activation the theme looks
 * for deploy/daak-seed.json in the uploads directory and imports it if it is
 * there. Nothing is invented if it is not.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once DAAK_DIR . '/inc/admin-settings.php';

const DAAK_OPT_PROFILE = 'daak_profile';
const DAAK_OPT_POLICY  = 'daak_policy';
const DAAK_OPT_EXTRAS  = 'daak_extras';
const DAAK_OPT_SEASONS = 'daak_seasons';
const DAAK_OPT_TAX     = 'daak_tax';
const DAAK_OPT_ROADS   = 'daak_roads';

/** Field keys, and what each is for. Empty is a valid value: an unanswered field prints nothing. */
function daak_profile_fields() {
	return array(
		'name'        => 'Public name',
		'legal'       => 'Legal name',
		'street'      => 'Street',
		'city'        => 'City',
		'state'       => 'State',
		'zip'         => 'ZIP',
		'phone1'      => 'Telephone 1',
		'phone2'      => 'Telephone 2',
		'email'       => 'Email',
		'hours'       => 'Opening hours',
		'founded'     => 'Founded',
		'entity'      => 'State entity number',
		'sales_site'  => 'Sales site URL',
		'sales_name'  => 'Sales site name',
		'map_url'     => 'Map embed URL',
		'reviews_url' => 'Google listing URL',
		'rating'      => 'Google rating',
		'rating_count'=> 'Google review count',
		'logo_id'     => 'Logo attachment ID',
	);
}

/**
 * Read one field, or the whole record.
 *
 * tel1/tel2 (dialable) and addr (one line) are derived rather than stored, so
 * there is one place a telephone number is written down and one shape it has.
 */
function daak_profile( $key = null, $default = '' ) {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( DAAK_OPT_PROFILE, array() );
		$p     = array();
		foreach ( array_keys( daak_profile_fields() ) as $k ) {
			$p[ $k ] = isset( $saved[ $k ] ) ? $saved[ $k ] : '';
		}
		foreach ( array( 'phone1', 'phone2' ) as $i => $k ) {
			$digits = preg_replace( '/\D/', '', (string) $p[ $k ] );
			$p[ 'tel' . ( $i + 1 ) ] = $digits ? ( strlen( $digits ) === 10 ? '+1' : '+' ) . $digits : '';
		}
		$city_line = trim( $p['city'] . ( $p['state'] ? ', ' . $p['state'] : '' ) . ' ' . $p['zip'] );
		$p['addr'] = trim( trim( $p['street'] . ', ' . $city_line ), ', ' );
		$cache     = $p;
	}
	if ( null === $key ) { return $cache; }
	$v = $cache[ $key ] ?? '';
	return '' !== $v ? $v : $default;
}

/** True once the record carries enough to render a contact block. */
function daak_profile_ready() {
	return '' !== daak_profile( 'name' ) && '' !== daak_profile( 'phone1' );
}

/** Policy answers the owner gives once and the whole site then repeats verbatim. */
function daak_policy( $key = null ) {
	$d = array(
		'gravel_headline'  => 'Gravel-road approved. Unlimited miles. Winter-equipped.',
		'gravel_sentence'  => '',   // the exact words that also stand in the rental agreement
		'response_promise' => '',   // e.g. "We confirm within the hour, 8am-8pm"
		'min_age'          => '',
		'young_driver'     => '',
		'deposit'          => '',
		'fuel'             => '',
		'mileage'          => '',
		'pets'             => '',
		'smoking'          => '',
		'second_driver'    => '',
		'one_way'          => '',
		'airport'          => '',
		'ferry'            => '',
		'canada'           => '',
		'rto_credit'       => '',   // how much of the rental credits against a purchase
		'insurance_note'   => '',
		'recall_note'      => 'Every vehicle is checked against open NHTSA safety recalls before it goes out.',
		'studs_window'     => '',   // the statutory studded-tire window, verified against the current statute
		'studs_verified_on'=> '',
		'moose_note'       => '',
		'block_heater'     => '',
	);
	$saved = wp_parse_args( get_option( DAAK_OPT_POLICY, array() ), $d );
	return null === $key ? $saved : ( $saved[ $key ] ?? '' );
}

/**
 * Add-ons. These are the margin, not the decoration.
 * unit: 'day' bills per rental day, 'rental' bills once.
 */
function daak_extras() {
	$rows = get_option( DAAK_OPT_EXTRAS, null );
	if ( null === $rows ) {
		$rows = array(
			array( 'slug' => 'roof-box',   'label' => 'Roof box or rack',      'price' => '', 'unit' => 'day',    'note' => '' ),
			array( 'slug' => 'hitch',      'label' => 'Tow hitch',             'price' => '', 'unit' => 'rental', 'note' => '' ),
			array( 'slug' => 'camping',    'label' => 'Camping kit',           'price' => '', 'unit' => 'day',    'note' => 'Tent, bags, stove, cooler, chairs' ),
			array( 'slug' => 'bear-spray', 'label' => 'Bear spray',            'price' => '', 'unit' => 'rental', 'note' => '' ),
			array( 'slug' => 'child-seat', 'label' => 'Child seat',            'price' => '', 'unit' => 'day',    'note' => '' ),
			array( 'slug' => 'driver-2',   'label' => 'Second driver',         'price' => '', 'unit' => 'rental', 'note' => '' ),
			array( 'slug' => 'inreach',    'label' => 'Satellite messenger',   'price' => '', 'unit' => 'day',    'note' => 'No mobile signal on most of the gravel' ),
		);
	}
	$out = array();
	foreach ( (array) $rows as $r ) {
		if ( empty( $r['label'] ) ) { continue; }
		$out[] = array(
			'slug'  => sanitize_key( $r['slug'] ?? sanitize_title( $r['label'] ) ),
			'label' => (string) $r['label'],
			'price' => '' === ( $r['price'] ?? '' ) ? '' : (float) $r['price'],
			'unit'  => 'rental' === ( $r['unit'] ?? 'day' ) ? 'rental' : 'day',
			'note'  => (string) ( $r['note'] ?? '' ),
		);
	}
	return $out;
}

function daak_extra_by_slug( $slug ) {
	foreach ( daak_extras() as $e ) {
		if ( $e['slug'] === $slug ) { return $e; }
	}
	return null;
}

/** Seasons carry a rate multiplier; May-September is not June anywhere in Alaska. */
function daak_seasons() {
	$rows = get_option( DAAK_OPT_SEASONS, null );
	if ( null === $rows ) {
		$rows = array(
			array( 'label' => 'Summer', 'from' => '05-15', 'to' => '09-15', 'multiplier' => '1' ),
			array( 'label' => 'Winter', 'from' => '09-16', 'to' => '05-14', 'multiplier' => '1' ),
		);
	}
	$out = array();
	foreach ( (array) $rows as $r ) {
		if ( empty( $r['label'] ) ) { continue; }
		$out[] = array(
			'label'      => (string) $r['label'],
			'from'       => preg_match( '/^\d{2}-\d{2}$/', (string) ( $r['from'] ?? '' ) ) ? $r['from'] : '',
			'to'         => preg_match( '/^\d{2}-\d{2}$/', (string) ( $r['to'] ?? '' ) ) ? $r['to'] : '',
			'multiplier' => (float) ( $r['multiplier'] ?? 1 ) ?: 1,
		);
	}
	return $out;
}

/** Which season a date falls in. Ranges may wrap the new year. */
function daak_season_for( $date ) {
	$md = wp_date( 'm-d', strtotime( $date ) );
	foreach ( daak_seasons() as $s ) {
		if ( ! $s['from'] || ! $s['to'] ) { continue; }
		$wraps = $s['from'] > $s['to'];
		$in    = $wraps ? ( $md >= $s['from'] || $md <= $s['to'] ) : ( $md >= $s['from'] && $md <= $s['to'] );
		if ( $in ) { return $s; }
	}
	return null;
}

/**
 * Tax.
 *
 * Alaska levies a rental tax on passenger vehicles under 90 days (AS 43.52) and
 * the Municipality of Anchorage levies its own on top (AMC 12.50). The rates are
 * not written into this theme from memory — they are entered once, against the
 * current published schedule, and every price on the site then says the same
 * thing. Until they are entered, the site says tax is added rather than pretending
 * to know the number: a quoted price that grows by a fifth at the counter is this
 * industry's most common complaint, and in Alaska it is also AS 45.50.471.
 */
function daak_tax_settings() {
	$d = array(
		'state_pct'   => '',
		'muni_pct'    => '',
		'mode'        => 'add',   // 'add' shows tax on top, 'included' shows one gross price
		'note'        => '',
		'verified_on' => '',
	);
	$t = wp_parse_args( get_option( DAAK_OPT_TAX, array() ), $d );
	$t['total_pct'] = ( '' === $t['state_pct'] && '' === $t['muni_pct'] )
		? null
		: round( (float) $t['state_pct'] + (float) $t['muni_pct'], 3 );
	return $t;
}

/** One sentence about tax, for every page that shows a price. */
function daak_tax_line() {
	$t = daak_tax_settings();
	if ( null === $t['total_pct'] ) {
		return 'Alaska and Municipality of Anchorage vehicle rental tax is added to these rates.';
	}
	$pct = rtrim( rtrim( number_format( $t['total_pct'], 2 ), '0' ), '.' );
	return 'included' === $t['mode']
		? sprintf( 'Rates shown include %s%% Alaska and Anchorage vehicle rental tax.', $pct )
		: sprintf( '%s%% Alaska and Anchorage vehicle rental tax is added at the counter.', $pct );
}

/**
 * The road table. One record, printed on the drive page, on every vehicle and in
 * the policies page — so the website and the rental agreement cannot drift apart.
 * verdict: allowed | forbidden | ask | conditional
 */
function daak_roads() {
	$rows = get_option( DAAK_OPT_ROADS, null );
	if ( null === $rows ) {
		$rows = array(
			array( 'road' => 'Dalton Highway (Coldfoot, Prudhoe Bay)', 'majors' => 'Forbidden', 'verdict' => 'ask', 'note' => '' ),
			array( 'road' => 'Denali Highway',                          'majors' => 'Forbidden', 'verdict' => 'ask', 'note' => '' ),
			array( 'road' => 'McCarthy Road',                           'majors' => 'Forbidden', 'verdict' => 'ask', 'note' => '' ),
			array( 'road' => 'Steese, Taylor and Elliott Highways',     'majors' => 'Forbidden', 'verdict' => 'ask', 'note' => '' ),
			array( 'road' => 'Every paved road in Alaska',              'majors' => 'Allowed',   'verdict' => 'allowed', 'note' => '' ),
		);
	}
	$out = array();
	foreach ( (array) $rows as $r ) {
		if ( empty( $r['road'] ) ) { continue; }
		$v = in_array( $r['verdict'] ?? '', array( 'allowed', 'forbidden', 'ask', 'conditional' ), true ) ? $r['verdict'] : 'ask';
		$out[] = array(
			'road'    => (string) $r['road'],
			'majors'  => (string) ( $r['majors'] ?? '' ),
			'verdict' => $v,
			'note'    => (string) ( $r['note'] ?? '' ),
		);
	}
	return $out;
}

function daak_road_verdict_label( $verdict ) {
	return array(
		'allowed'     => 'Allowed',
		'forbidden'   => 'Not permitted',
		'ask'         => 'Ask us',
		'conditional' => 'With permission',
	)[ $verdict ] ?? 'Ask us';
}

/**
 * Import a seed file if the site has one.
 *
 * The seed lives in wp-content/uploads/daak-seed.json, never in the theme, for
 * the same reason the profile does: the theme must not carry one dealer's
 * details. Existing values are kept — a seed never overwrites what somebody typed.
 */
function daak_import_seed() {
	$dir  = wp_get_upload_dir();
	$file = trailingslashit( $dir['basedir'] ) . 'daak-seed.json';
	if ( ! file_exists( $file ) ) { return false; }
	$data = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) { return false; }

	$map = array(
		'profile' => DAAK_OPT_PROFILE,
		'policy'  => DAAK_OPT_POLICY,
		'extras'  => DAAK_OPT_EXTRAS,
		'seasons' => DAAK_OPT_SEASONS,
		'tax'     => DAAK_OPT_TAX,
		'roads'   => DAAK_OPT_ROADS,
	);
	foreach ( $map as $key => $option ) {
		if ( empty( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) { continue; }
		$current = get_option( $option, array() );
		if ( in_array( $key, array( 'extras', 'seasons', 'roads' ), true ) ) {
			if ( empty( $current ) ) { update_option( $option, $data[ $key ] ); }
			continue;
		}
		$merged = is_array( $current ) ? $current : array();
		foreach ( $data[ $key ] as $k => $v ) {
			if ( ! isset( $merged[ $k ] ) || '' === $merged[ $k ] ) { $merged[ $k ] = $v; }
		}
		update_option( $option, $merged );
	}
	return true;
}
