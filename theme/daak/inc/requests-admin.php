<?php
/**
 * The requests screen — where somebody actually answers the telephone.
 *
 * Confirming a request is the moment a vehicle stops being offered. That is done
 * here, with one select, and it writes the block on the vehicle so the two can
 * never disagree.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function daak_request_statuses() {
	return array(
		'new'         => 'New',
		'confirmed'   => 'Confirmed — vehicle held',
		'declined'    => 'Declined',
		'unavailable' => 'Dates were taken',
		'done'        => 'Completed',
	);
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'daak-request', 'Request', 'daak_box_request', 'daak_request', 'normal', 'high' );
} );

function daak_box_request( $post ) {
	wp_nonce_field( 'daak_request_save', 'daak_request_nonce' );
	$m = function ( $k ) use ( $post ) { return get_post_meta( $post->ID, $k, true ); };

	$vehicle_id = (int) $m( 'dr_vehicle' );
	$extras     = (array) json_decode( (string) $m( 'dr_extras' ), true );
	$labels     = array();
	foreach ( $extras as $slug ) {
		$e = daak_extra_by_slug( $slug );
		if ( $e ) { $labels[] = $e['label']; }
	}

	$rows = array(
		'Name'       => $m( 'dr_name' ),
		'Telephone'  => sprintf( '<a href="tel:%s">%s</a>', esc_attr( preg_replace( '/\D/', '', $m( 'dr_phone' ) ) ), esc_html( $m( 'dr_phone' ) ) ),
		'Email'      => $m( 'dr_email' ) ? sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $m( 'dr_email' ) ) ) : '—',
		'Pick-up'    => trim( $m( 'dr_pickup' ) . ' ' . $m( 'dr_pickup_time' ) ),
		'Return'     => trim( $m( 'dr_return' ) . ' ' . $m( 'dr_return_time' ) ),
		'Days'       => daak_days_between( $m( 'dr_pickup' ), $m( 'dr_return' ) ),
		'Vehicle'    => $vehicle_id ? sprintf( '<a href="%s">%s</a>', esc_url( (string) get_edit_post_link( $vehicle_id ) ), esc_html( daak_vehicle_title( $vehicle_id ) ) ) : 'Not chosen',
		'Add-ons'    => $labels ? implode( ', ', $labels ) : '—',
		'Message'    => nl2br( esc_html( $m( 'dr_message' ) ) ),
		'Source'     => $m( 'dr_source' ),
		'Notification' => 'sent' === $m( 'dr_mail' ) ? 'Sent' : '<strong style="color:#B3261E">Not sent — the request is still safe here</strong>',
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		printf( '<tr><th style="width:160px">%s</th><td>%s</td></tr>', esc_html( $label ), wp_kses_post( (string) $value ) );
	}
	echo '</tbody></table>';

	$quote = json_decode( (string) $m( 'dr_quote' ), true );
	if ( is_array( $quote ) && ! empty( $quote['lines'] ) ) {
		echo '<p style="margin-top:14px"><strong>Quote when the request came in</strong></p><table class="widefat striped"><tbody>';
		foreach ( $quote['lines'] as $l ) {
			printf( '<tr><td>%s</td><td style="text-align:right">%s</td></tr>', esc_html( $l['label'] ), null === $l['amount'] ? '—' : esc_html( daak_money( $l['amount'] ) ) );
		}
		printf( '<tr><th>Total</th><th style="text-align:right">%s</th></tr>', esc_html( daak_money( $quote['total'] ) ) );
		echo '</tbody></table>';
		printf( '<p class="description">%s</p>', esc_html( $quote['tax_line'] ?? '' ) );
	}

	$status = $m( 'dr_status' ) ?: 'new';
	echo '<p style="margin-top:16px"><label for="dr_status"><strong>Status</strong></label><br><select name="dr_status" id="dr_status">';
	foreach ( daak_request_statuses() as $slug => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $slug ), selected( $status, $slug, false ), esc_html( $label ) );
	}
	echo '</select></p>';

	if ( $vehicle_id ) {
		$free = daak_vehicle_is_free( $vehicle_id, $m( 'dr_pickup' ), $m( 'dr_return' ), $post->ID );
		printf(
			'<p class="description">%s</p>',
			$free
				? 'Those dates are free on that vehicle. Confirming holds them.'
				: '<strong style="color:#B3261E">Those dates are already blocked on that vehicle by something else.</strong> Confirming anyway would double-book it.'
		);
	}
	echo '<p class="description">Confirming writes the dates onto the vehicle so the site stops offering it. Declining takes them off again.</p>';
}

add_action( 'save_post_daak_request', function ( $post_id ) {
	if ( ! isset( $_POST['daak_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['daak_request_nonce'] ) ), 'daak_request_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	$status = sanitize_key( $_POST['dr_status'] ?? 'new' );
	if ( ! isset( daak_request_statuses()[ $status ] ) ) { $status = 'new'; }
	update_post_meta( $post_id, 'dr_status', $status );
	daak_sync_request_block( $post_id );
} );

/**
 * Keep the vehicle's blocks in step with this request.
 * A confirmed request owns exactly one line on its vehicle; anything else owns none.
 */
function daak_sync_request_block( $request_id ) {
	$vehicle_id = (int) get_post_meta( $request_id, 'dr_vehicle', true );
	if ( ! $vehicle_id ) { return; }

	$blocks = array();
	foreach ( daak_vehicle_blocks( $vehicle_id ) as $b ) {
		if ( $b['request'] !== (int) $request_id ) { $blocks[] = $b; }
	}
	if ( 'confirmed' === get_post_meta( $request_id, 'dr_status', true ) ) {
		$blocks[] = array(
			'from'    => daak_date( get_post_meta( $request_id, 'dr_pickup', true ) ),
			'to'      => daak_date( get_post_meta( $request_id, 'dr_return', true ) ),
			'reason'  => 'booked #' . $request_id,
			'request' => (int) $request_id,
		);
	}
	daak_save_blocks( $vehicle_id, $blocks );
}

/** A deleted request must not keep holding a vehicle. */
add_action( 'before_delete_post', function ( $post_id ) {
	if ( 'daak_request' !== get_post_type( $post_id ) ) { return; }
	update_post_meta( $post_id, 'dr_status', 'declined' );
	daak_sync_request_block( $post_id );
} );

add_filter( 'manage_daak_request_posts_columns', function () {
	return array(
		'cb'          => '<input type="checkbox">',
		'title'       => 'Request',
		'daak_dates'  => 'Dates',
		'daak_phone'  => 'Telephone',
		'daak_status' => 'Status',
		'daak_mail'   => 'Notified',
		'date'        => 'Received',
	);
} );

add_action( 'manage_daak_request_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'daak_dates':
			printf(
				'%s → %s (%d days)',
				esc_html( get_post_meta( $post_id, 'dr_pickup', true ) ),
				esc_html( get_post_meta( $post_id, 'dr_return', true ) ),
				daak_days_between( get_post_meta( $post_id, 'dr_pickup', true ), get_post_meta( $post_id, 'dr_return', true ) )
			);
			break;
		case 'daak_phone':
			$p = get_post_meta( $post_id, 'dr_phone', true );
			printf( '<a href="tel:%s">%s</a>', esc_attr( preg_replace( '/\D/', '', $p ) ), esc_html( $p ) );
			break;
		case 'daak_status':
			$s = get_post_meta( $post_id, 'dr_status', true ) ?: 'new';
			echo esc_html( daak_request_statuses()[ $s ] ?? $s );
			break;
		case 'daak_mail':
			echo 'sent' === get_post_meta( $post_id, 'dr_mail', true ) ? 'Yes' : '<span style="color:#B3261E">No</span>';
			break;
	}
}, 10, 2 );

/** New requests are the whole point of the site, so the menu says how many are waiting. */
add_filter( 'add_menu_classes', function ( $menu ) {
	$new = get_posts( array(
		'post_type'      => 'daak_request',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => 'dr_status', 'value' => 'new' ) ),
	) );
	if ( ! $new ) { return $menu; }
	foreach ( $menu as $i => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=daak_request' === $item[2] ) {
			$menu[ $i ][0] .= sprintf( ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>', count( $new ) );
		}
	}
	return $menu;
} );
