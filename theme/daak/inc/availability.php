<?php
/**
 * Availability — the one piece of real machinery version one needs.
 *
 * A site that books one truck twice on a Saturday has done worse than one that
 * books nothing, so the rules here are deliberately conservative:
 *
 *  - A block covers both its dates inclusively. A vehicle that comes back on the
 *    10th is not offered for a pick-up on the 10th, because nobody has looked at
 *    it yet. A lot that turns cars round the same day can switch that off with
 *    the daak_same_day_turnaround filter.
 *  - Confirming a request writes its dates in as a block, tagged with the
 *    request number; changing that request rewrites its own line and no other.
 *  - Search never offers a vehicle whose dates overlap a block, and the booking
 *    handler checks again at submission, because a form can sit open for an hour.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** All blocks on a vehicle, normalised and sorted. */
function daak_vehicle_blocks( $vehicle_id ) {
	$raw = json_decode( (string) get_post_meta( $vehicle_id, 'dv_blocks', true ), true );
	$out = array();
	foreach ( (array) $raw as $b ) {
		$from = daak_date( $b['from'] ?? '' );
		$to   = daak_date( $b['to'] ?? '' );
		if ( ! $from || ! $to ) { continue; }
		if ( $to < $from ) { list( $from, $to ) = array( $to, $from ); }
		$out[] = array(
			'from'    => $from,
			'to'      => $to,
			'reason'  => sanitize_text_field( (string) ( $b['reason'] ?? 'blocked' ) ),
			'request' => absint( $b['request'] ?? 0 ),
		);
	}
	usort( $out, function ( $a, $b ) { return strcmp( $a['from'], $b['from'] ); } );
	return $out;
}

function daak_save_blocks( $vehicle_id, $blocks ) {
	update_post_meta( $vehicle_id, 'dv_blocks', wp_json_encode( array_values( $blocks ) ) );
}

/**
 * Parse the textarea in the vehicle editor.
 * Lines that came from a booking keep their request number so the two stay tied.
 */
function daak_save_blocks_from_text( $vehicle_id, $text ) {
	$existing = array();
	foreach ( daak_vehicle_blocks( $vehicle_id ) as $b ) {
		$existing[ $b['from'] . '|' . $b['to'] ] = $b['request'];
	}
	$blocks = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		if ( '' === trim( $line ) ) { continue; }
		$p    = array_map( 'trim', explode( '|', $line ) );
		$from = daak_date( $p[0] ?? '' );
		$to   = daak_date( $p[1] ?? ( $p[0] ?? '' ) );
		if ( ! $from || ! $to ) { continue; }
		$blocks[] = array(
			'from'    => $from,
			'to'      => $to,
			'reason'  => sanitize_text_field( $p[2] ?? 'blocked' ),
			'request' => (int) ( $existing[ $from . '|' . $to ] ?? 0 ),
		);
	}
	daak_save_blocks( $vehicle_id, $blocks );
}

/** A date, or '' if it is not one. Everything internal is Y-m-d, which sorts as a string. */
function daak_date( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) { return ''; }
	$ts = strtotime( $value );
	return $ts ? gmdate( 'Y-m-d', $ts ) : '';
}

/** Whole days between two dates. A one-night rental is one day. */
function daak_days_between( $from, $to ) {
	$from = daak_date( $from );
	$to   = daak_date( $to );
	if ( ! $from || ! $to ) { return 0; }
	$diff = (int) round( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS );
	return max( 1, $diff );
}

/**
 * Is this vehicle free for these dates?
 *
 * @param int    $vehicle_id Vehicle.
 * @param string $from       Pick-up date.
 * @param string $to         Return date.
 * @param int    $ignore_req Request whose own block should not count against it.
 */
function daak_vehicle_is_free( $vehicle_id, $from, $to, $ignore_req = 0 ) {
	$from = daak_date( $from );
	$to   = daak_date( $to );
	if ( ! $from || ! $to ) { return false; }
	if ( 'retired' === get_post_meta( $vehicle_id, 'dv_status', true ) ) { return false; }

	$same_day = (bool) apply_filters( 'daak_same_day_turnaround', false, $vehicle_id );
	foreach ( daak_vehicle_blocks( $vehicle_id ) as $b ) {
		if ( $ignore_req && $b['request'] === $ignore_req ) { continue; }
		$overlaps = $same_day
			? ( $from < $b['to'] && $to > $b['from'] )
			: ( $from <= $b['to'] && $to >= $b['from'] );
		if ( $overlaps ) { return false; }
	}
	return true;
}

/** The next block starting from today, for the fleet list column. */
function daak_next_block( $vehicle_id ) {
	$today = wp_date( 'Y-m-d' );
	foreach ( daak_vehicle_blocks( $vehicle_id ) as $b ) {
		if ( $b['to'] >= $today ) { return $b; }
	}
	return null;
}

/** Every live vehicle, ordered as the fleet screen orders them. */
function daak_all_vehicles() {
	return get_posts( array(
		'post_type'      => 'daak_vehicle',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'dv_status', 'value' => 'retired', 'compare' => '!=' ),
			array( 'key' => 'dv_status', 'compare' => 'NOT EXISTS' ),
		),
	) );
}

/**
 * The search behind every date box on the site.
 *
 * With no dates it returns the whole fleet, because a fleet page with a filter
 * on it is still useful to somebody who has not decided when they are coming.
 */
function daak_search_vehicles( $args = array() ) {
	$a = wp_parse_args( $args, array(
		'from'   => '',
		'to'     => '',
		'class'  => '',
		'drive'  => '',
		'seats'  => 0,
		'gravel' => '',
		'max'    => 0,
	) );

	$out = array();
	foreach ( daak_all_vehicles() as $v ) {
		if ( $a['from'] && $a['to'] && ! daak_vehicle_is_free( $v->ID, $a['from'], $a['to'] ) ) { continue; }
		if ( $a['class'] && get_post_meta( $v->ID, 'dv_class', true ) !== $a['class'] ) { continue; }
		if ( $a['drive'] && get_post_meta( $v->ID, 'dv_drive', true ) !== $a['drive'] ) { continue; }
		if ( $a['seats'] && (int) get_post_meta( $v->ID, 'dv_seats', true ) < (int) $a['seats'] ) { continue; }
		if ( '' !== $a['gravel'] && (int) get_post_meta( $v->ID, 'dv_gravel', true ) !== (int) $a['gravel'] ) { continue; }
		if ( $a['max'] ) {
			$rate = (float) get_post_meta( $v->ID, 'dv_rate_day', true );
			if ( $rate && $rate > (float) $a['max'] ) { continue; }
		}
		if ( $a['from'] && $a['to'] ) {
			$min = (int) get_post_meta( $v->ID, 'dv_min_days', true );
			if ( $min && daak_days_between( $a['from'], $a['to'] ) < $min ) { continue; }
		}
		$out[] = $v;
	}
	return $out;
}

/**
 * The filters a fleet page may draw.
 *
 * Built from the vehicles actually in the fleet: a filter that cannot narrow
 * anything is not drawn, because a Drive filter offering only 4WD on a fleet of
 * four 4WDs is furniture.
 */
function daak_fleet_filters() {
	$vehicles = daak_all_vehicles();
	$seen     = array( 'dv_class' => array(), 'dv_drive' => array(), 'seats' => array(), 'gravel' => array() );
	foreach ( $vehicles as $v ) {
		foreach ( array( 'dv_class', 'dv_drive' ) as $k ) {
			$val = get_post_meta( $v->ID, $k, true );
			if ( $val ) { $seen[ $k ][ $val ] = ( $seen[ $k ][ $val ] ?? 0 ) + 1; }
		}
		$s = (int) get_post_meta( $v->ID, 'dv_seats', true );
		if ( $s ) { $seen['seats'][ $s ] = ( $seen['seats'][ $s ] ?? 0 ) + 1; }
		$g = (int) get_post_meta( $v->ID, 'dv_gravel', true );
		$seen['gravel'][ $g ] = ( $seen['gravel'][ $g ] ?? 0 ) + 1;
	}

	$filters = array();
	foreach ( array( 'dv_class' => 'Class', 'dv_drive' => 'Drive' ) as $k => $label ) {
		if ( count( $seen[ $k ] ) < 2 ) { continue; }
		$opts = array();
		foreach ( daak_vehicle_vocab( $k ) as $slug => $text ) {
			if ( isset( $seen[ $k ][ $slug ] ) ) { $opts[ $slug ] = $text . ' (' . $seen[ $k ][ $slug ] . ')'; }
		}
		$filters[ $k ] = array( 'label' => $label, 'options' => $opts );
	}
	if ( count( $seen['seats'] ) > 1 ) {
		$sizes = array_keys( $seen['seats'] );
		sort( $sizes );
		$opts = array();
		foreach ( $sizes as $s ) { $opts[ $s ] = $s . ' seats or more'; }
		$filters['seats'] = array( 'label' => 'Seats', 'options' => $opts );
	}
	if ( count( $seen['gravel'] ) > 1 ) {
		$filters['gravel'] = array( 'label' => 'Gravel', 'options' => array( '1' => 'Gravel-approved only' ) );
	}
	return $filters;
}

/** Requests attached to a vehicle, newest first. */
function daak_vehicle_requests( $vehicle_id ) {
	return get_posts( array(
		'post_type'      => 'daak_request',
		'posts_per_page' => 20,
		'post_status'    => 'any',
		'meta_key'       => 'dr_vehicle',
		'meta_value'     => (string) $vehicle_id,
	) );
}

/* ------------------------------------------------- the admin availability grid */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=daak_vehicle',
		'Availability',
		'Availability',
		'edit_posts',
		'daak-availability',
		'daak_render_availability_grid'
	);
} );

function daak_render_availability_grid() {
	$days  = isset( $_GET['days'] ) ? max( 14, min( 90, (int) $_GET['days'] ) ) : 45;
	$start = isset( $_GET['start'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['start'] ) ) ) : '';
	$start = $start ?: wp_date( 'Y-m-d' );

	echo '<div class="wrap"><h1>Availability</h1>';
	echo '<form method="get" style="margin:12px 0"><input type="hidden" name="post_type" value="daak_vehicle"><input type="hidden" name="page" value="daak-availability">';
	printf( '<input type="date" name="start" value="%s"> ', esc_attr( $start ) );
	printf( '<input type="number" name="days" value="%d" min="14" max="90" style="width:80px"> days ', $days );
	submit_button( 'Show', 'secondary', '', false );
	echo '</form>';

	$vehicles = daak_all_vehicles();
	if ( ! $vehicles ) {
		echo '<p>No vehicles yet.</p></div>';
		return;
	}

	echo '<style>
	.daak-grid{border-collapse:collapse;font-size:11px}
	.daak-grid th,.daak-grid td{border:1px solid #dcdcde;padding:0}
	.daak-grid th.v{text-align:left;padding:6px 10px;font-size:13px;white-space:nowrap;position:sticky;left:0;background:#fff}
	.daak-grid td.d{width:16px;height:26px;text-align:center}
	.daak-grid td.free{background:#EAF6EE}
	.daak-grid td.busy{background:#08318B}
	.daak-grid td.wknd{box-shadow:inset 0 -3px 0 #E4E9F4}
	.daak-grid th.d{font-weight:500;color:#5A6785;font-size:10px;padding:2px 0}
	</style>';

	echo '<table class="daak-grid"><thead><tr><th class="v">Vehicle</th>';
	for ( $i = 0; $i < $days; $i++ ) {
		$d = gmdate( 'Y-m-d', strtotime( $start . " +$i days" ) );
		printf( '<th class="d" title="%s">%s</th>', esc_attr( $d ), esc_html( gmdate( 'j', strtotime( $d ) ) ) );
	}
	echo '</tr></thead><tbody>';

	foreach ( $vehicles as $v ) {
		printf( '<tr><th class="v"><a href="%s">%s</a></th>', esc_url( (string) get_edit_post_link( $v->ID ) ), esc_html( daak_vehicle_title( $v->ID ) ) );
		for ( $i = 0; $i < $days; $i++ ) {
			$d    = gmdate( 'Y-m-d', strtotime( $start . " +$i days" ) );
			$free = daak_vehicle_is_free( $v->ID, $d, $d );
			$dow  = (int) gmdate( 'N', strtotime( $d ) );
			printf(
				'<td class="d %s%s" title="%s"></td>',
				$free ? 'free' : 'busy',
				$dow >= 6 ? ' wknd' : '',
				esc_attr( $d . ' — ' . ( $free ? 'free' : 'booked' ) )
			);
		}
		echo '</tr>';
	}
	echo '</tbody></table><p class="description" style="margin-top:10px">Dark is blocked. A block covers both its dates, so a vehicle that comes back on a date is not offered for a pick-up that same date.</p></div>';
}
