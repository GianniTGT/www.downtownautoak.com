<?php
/**
 * Booking — a request, not a payment.
 *
 * Taking a card means a merchant account, PCI scope, refunds, no-shows,
 * chargebacks and a deposit-hold policy, none of which exists yet. A request
 * form and a telephone call converts perfectly well at this volume and ships in
 * days. The data model already has room for a deposit, so version two is a field
 * and not a rewrite.
 *
 * Two rules this file exists to keep:
 *   1. The request is written to the database before anything is sent, so a mail
 *      failure never loses a customer.
 *   2. Nothing sensitive is collected. No Social Security number, no date of
 *      birth, no driver's licence number, no card. The licence is examined in
 *      person at the counter, where it can be looked at.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** What a request is allowed to carry. Anything else the form posts is ignored. */
function daak_request_fields() {
	return array( 'dr_name', 'dr_phone', 'dr_email', 'dr_pickup', 'dr_return', 'dr_pickup_time', 'dr_return_time', 'dr_vehicle', 'dr_extras', 'dr_message', 'dr_source' );
}

/**
 * Price a rental.
 *
 * Weekly and monthly rates are breaks, not separate products: 9 days is a week
 * plus two days, priced as such, because that is what a customer expects when a
 * weekly rate is advertised.
 */
function daak_quote( $vehicle_id, $from, $to, $extras = array() ) {
	$days = daak_days_between( $from, $to );
	$q    = array(
		'days'      => $days,
		'lines'     => array(),
		'subtotal'  => 0.0,
		'tax'       => 0.0,
		'total'     => 0.0,
		'tax_line'  => daak_tax_line(),
		'complete'  => false,   // false when a rate is missing and the number would be a guess
		'season'    => null,
	);

	$day   = (float) get_post_meta( $vehicle_id, 'dv_rate_day', true );
	$week  = (float) get_post_meta( $vehicle_id, 'dv_rate_week', true );
	$month = (float) get_post_meta( $vehicle_id, 'dv_rate_month', true );
	if ( ! $day ) { return $q; }

	$season = $from ? daak_season_for( $from ) : null;
	$mult   = $season ? (float) $season['multiplier'] : 1.0;
	$q['season'] = $season;

	$day   = round( $day * $mult, 2 );
	$week  = $week ? round( $week * $mult, 2 ) : round( $day * 7, 2 );
	$month = $month ? round( $month * $mult, 2 ) : round( $day * 30, 2 );

	$left = $days;
	if ( $left >= 30 && $month ) {
		$n = intdiv( $left, 30 );
		$q['lines'][] = array( 'label' => $n . ' × monthly rate', 'amount' => $n * $month );
		$left -= $n * 30;
	}
	if ( $left >= 7 && $week ) {
		$n = intdiv( $left, 7 );
		$q['lines'][] = array( 'label' => $n . ' × weekly rate', 'amount' => $n * $week );
		$left -= $n * 7;
	}
	if ( $left > 0 ) {
		$q['lines'][] = array( 'label' => $left . ' × daily rate', 'amount' => $left * $day );
	}

	foreach ( (array) $extras as $slug ) {
		$e = daak_extra_by_slug( sanitize_key( $slug ) );
		if ( ! $e ) { continue; }
		if ( '' === $e['price'] ) {
			$q['lines'][] = array( 'label' => $e['label'] . ' (price on request)', 'amount' => null );
			continue;
		}
		$amount = 'day' === $e['unit'] ? $e['price'] * $days : $e['price'];
		$q['lines'][] = array( 'label' => $e['label'] . ( 'day' === $e['unit'] ? ' × ' . $days . ' days' : '' ), 'amount' => $amount );
	}

	foreach ( $q['lines'] as $l ) { $q['subtotal'] += (float) $l['amount']; }
	$q['subtotal'] = round( $q['subtotal'], 2 );

	$tax = daak_tax_settings();
	if ( null !== $tax['total_pct'] ) {
		if ( 'included' === $tax['mode'] ) {
			$q['total'] = $q['subtotal'];
			$q['tax']   = round( $q['subtotal'] - ( $q['subtotal'] / ( 1 + $tax['total_pct'] / 100 ) ), 2 );
		} else {
			$q['tax']   = round( $q['subtotal'] * $tax['total_pct'] / 100, 2 );
			$q['total'] = round( $q['subtotal'] + $q['tax'], 2 );
		}
		$q['complete'] = true;
	} else {
		$q['total'] = $q['subtotal'];
	}
	return $q;
}

function daak_money( $amount ) {
	return '$' . number_format( (float) $amount, 2 );
}

/* ------------------------------------------------------------ the handler */

add_action( 'admin_post_nopriv_daak_request', 'daak_handle_request' );
add_action( 'admin_post_daak_request', 'daak_handle_request' );

function daak_handle_request() {
	$back = wp_get_referer() ?: home_url( '/' );

	if ( ! isset( $_POST['daak_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['daak_nonce'] ) ), 'daak_request' ) ) {
		daak_redirect_back( $back, 'expired' );
	}

	// A form filled in under three seconds, or with the hidden field filled, is a robot.
	$elapsed = time() - (int) ( $_POST['daak_t'] ?? 0 );
	if ( ! empty( $_POST['daak_hp'] ) || $elapsed < 3 || $elapsed > DAY_IN_SECONDS ) {
		daak_redirect_back( $back, 'ok' );   // say nothing useful to a robot
	}

	// Six an hour from one address is generous for a fleet this size.
	$key   = 'daak_rl_' . substr( hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() ), 0, 20 );
	$count = (int) get_transient( $key );
	if ( $count >= 6 ) { daak_redirect_back( $back, 'slow' ); }
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$name   = sanitize_text_field( wp_unslash( $_POST['dr_name'] ?? '' ) );
	$phone  = sanitize_text_field( wp_unslash( $_POST['dr_phone'] ?? '' ) );
	$email  = sanitize_email( wp_unslash( $_POST['dr_email'] ?? '' ) );
	$pickup = daak_date( wp_unslash( $_POST['dr_pickup'] ?? '' ) );
	$return = daak_date( wp_unslash( $_POST['dr_return'] ?? '' ) );

	if ( '' === $name || '' === $phone || ! $pickup || ! $return ) { daak_redirect_back( $back, 'missing' ); }
	if ( $return < $pickup ) { daak_redirect_back( $back, 'dates' ); }

	$vehicle_id = absint( $_POST['dr_vehicle'] ?? 0 );
	if ( $vehicle_id && 'daak_vehicle' !== get_post_type( $vehicle_id ) ) { $vehicle_id = 0; }

	$extras = array();
	foreach ( (array) ( $_POST['dr_extras'] ?? array() ) as $slug ) {
		$slug = sanitize_key( $slug );
		if ( daak_extra_by_slug( $slug ) ) { $extras[] = $slug; }   // a value the form never offered is dropped
	}

	$free   = $vehicle_id ? daak_vehicle_is_free( $vehicle_id, $pickup, $return ) : true;
	$status = $free ? 'new' : 'unavailable';

	$title = sprintf(
		'%s — %s to %s%s',
		$name,
		$pickup,
		$return,
		$vehicle_id ? ' — ' . daak_vehicle_title( $vehicle_id ) : ''
	);

	// Written down first. Whatever happens to the mail after this, the customer is not lost.
	$request_id = wp_insert_post( array(
		'post_type'   => 'daak_request',
		'post_status' => 'publish',
		'post_title'  => $title,
	), true );

	if ( is_wp_error( $request_id ) ) { daak_redirect_back( $back, 'error' ); }

	$meta = array(
		'dr_name'        => $name,
		'dr_phone'       => $phone,
		'dr_email'       => $email,
		'dr_pickup'      => $pickup,
		'dr_return'      => $return,
		'dr_pickup_time' => sanitize_text_field( wp_unslash( $_POST['dr_pickup_time'] ?? '' ) ),
		'dr_return_time' => sanitize_text_field( wp_unslash( $_POST['dr_return_time'] ?? '' ) ),
		'dr_vehicle'     => (string) $vehicle_id,
		'dr_extras'      => wp_json_encode( $extras ),
		'dr_message'     => sanitize_textarea_field( wp_unslash( $_POST['dr_message'] ?? '' ) ),
		'dr_source'      => sanitize_text_field( wp_unslash( $_POST['dr_source'] ?? 'site' ) ),
		'dr_status'      => $status,
	);
	if ( $vehicle_id ) {
		$meta['dr_quote'] = wp_json_encode( daak_quote( $vehicle_id, $pickup, $return, $extras ) );
	}
	foreach ( $meta as $k => $v ) { update_post_meta( $request_id, $k, $v ); }

	$sent = daak_notify_request( $request_id );
	update_post_meta( $request_id, 'dr_mail', $sent ? 'sent' : 'failed' );

	daak_redirect_back( $back, $free ? 'ok' : 'taken', $request_id );
}

function daak_redirect_back( $url, $state, $request_id = 0 ) {
	$args = array( 'request' => $state );
	if ( $request_id ) { $args['ref'] = $request_id; }
	wp_safe_redirect( add_query_arg( $args, remove_query_arg( array( 'request', 'ref' ), $url ) ) . '#request' );
	exit;
}

/** The notification, and an acknowledgement to the customer when they left an address. */
function daak_notify_request( $request_id ) {
	$to = daak_profile( 'email' );
	if ( ! $to ) { return false; }

	$vehicle_id = (int) get_post_meta( $request_id, 'dr_vehicle', true );
	$extras     = (array) json_decode( (string) get_post_meta( $request_id, 'dr_extras', true ), true );
	$labels     = array();
	foreach ( $extras as $slug ) {
		$e = daak_extra_by_slug( $slug );
		if ( $e ) { $labels[] = $e['label']; }
	}

	$lines = array(
		'Name:       ' . get_post_meta( $request_id, 'dr_name', true ),
		'Telephone:  ' . get_post_meta( $request_id, 'dr_phone', true ),
		'Email:      ' . ( get_post_meta( $request_id, 'dr_email', true ) ?: '—' ),
		'Pick-up:    ' . get_post_meta( $request_id, 'dr_pickup', true ) . ' ' . get_post_meta( $request_id, 'dr_pickup_time', true ),
		'Return:     ' . get_post_meta( $request_id, 'dr_return', true ) . ' ' . get_post_meta( $request_id, 'dr_return_time', true ),
		'Vehicle:    ' . ( $vehicle_id ? daak_vehicle_title( $vehicle_id ) : 'Not chosen' ),
		'Add-ons:    ' . ( $labels ? implode( ', ', $labels ) : '—' ),
		'Message:    ' . ( get_post_meta( $request_id, 'dr_message', true ) ?: '—' ),
		'',
		'Status:     ' . ( 'unavailable' === get_post_meta( $request_id, 'dr_status', true )
			? 'THOSE DATES ARE ALREADY BLOCKED FOR THIS VEHICLE — call with an alternative'
			: 'Free when the request came in' ),
		'',
		'Open it: ' . admin_url( 'post.php?post=' . $request_id . '&action=edit' ),
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$email   = get_post_meta( $request_id, 'dr_email', true );
	if ( $email ) { $headers[] = 'Reply-To: ' . $email; }

	$sent = wp_mail(
		$to,
		sprintf( '[Rental request] %s', get_the_title( $request_id ) ),
		implode( "\n", $lines ),
		$headers
	);

	if ( $email ) {
		$promise = daak_policy( 'response_promise' );
		$ack = array(
			'Thank you — your request is with us.',
			'',
			'Pick-up: ' . get_post_meta( $request_id, 'dr_pickup', true ),
			'Return:  ' . get_post_meta( $request_id, 'dr_return', true ),
			$vehicle_id ? 'Vehicle: ' . daak_vehicle_title( $vehicle_id ) : '',
			'',
			$promise ?: 'We will call you to confirm.',
			'',
			'Nothing is booked until we confirm it, and we take no card details online.',
			daak_profile( 'name' ),
			daak_profile( 'phone1' ),
		);
		wp_mail( $email, 'We have your rental request', implode( "\n", array_filter( $ack ) ), array( 'Content-Type: text/plain; charset=UTF-8' ) );
	}

	return (bool) $sent;
}

/** What the page says after a submission. */
function daak_request_notice() {
	$state = isset( $_GET['request'] ) ? sanitize_key( wp_unslash( $_GET['request'] ) ) : '';
	if ( ! $state ) { return ''; }
	$promise = daak_policy( 'response_promise' );
	$map = array(
		'ok'      => array( 'ok', 'Request received. ' . ( $promise ?: 'We will call you to confirm.' ) . ' Nothing is booked until we confirm it.' ),
		'taken'   => array( 'warn', 'Request received — but that vehicle has just been taken for those dates. We will call you with what is free.' ),
		'missing' => array( 'warn', 'We need a name, a telephone number and both dates.' ),
		'dates'   => array( 'warn', 'The return date is before the pick-up date.' ),
		'expired' => array( 'warn', 'That form had been open a while. Please send it again.' ),
		'slow'    => array( 'warn', 'That is a lot of requests from one connection. Please telephone us instead.' ),
		'error'   => array( 'warn', 'Something went wrong saving that. Please telephone us.' ),
	);
	if ( ! isset( $map[ $state ] ) ) { return ''; }
	list( $kind, $text ) = $map[ $state ];
	return sprintf( '<div class="notice-%s" role="status">%s</div>', esc_attr( $kind ), esc_html( $text ) );
}
