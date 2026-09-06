<?php
/**
 * The vehicle editor: one meta box for the specification, one for the gallery,
 * one for availability.
 *
 * Values are stored machine-readable and the label lives in the vocabulary, so
 * a filter can group on them and a rename never orphans a record.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'daak-spec', 'Vehicle', 'daak_box_spec', 'daak_vehicle', 'normal', 'high' );
	add_meta_box( 'daak-rates', 'Rates', 'daak_box_rates', 'daak_vehicle', 'side', 'default' );
	add_meta_box( 'daak-gallery', 'Photographs', 'daak_box_gallery', 'daak_vehicle', 'normal', 'default' );
	add_meta_box( 'daak-availability', 'Availability', 'daak_box_availability', 'daak_vehicle', 'normal', 'default' );
} );

function daak_meta_text( $post, $key, $label, $desc = '', $type = 'text' ) {
	$v = get_post_meta( $post->ID, $key, true );
	printf(
		'<p class="daak-f"><label for="%1$s"><strong>%2$s</strong></label><br><input type="%4$s" id="%1$s" name="%1$s" value="%3$s" class="widefat"></p>',
		esc_attr( $key ), esc_html( $label ), esc_attr( $v ), esc_attr( $type )
	);
	if ( $desc ) { printf( '<p class="description" style="margin-top:-8px">%s</p>', esc_html( $desc ) ); }
}

function daak_meta_select( $post, $key, $label ) {
	$v    = get_post_meta( $post->ID, $key, true );
	$opts = daak_vehicle_vocab( $key );
	printf( '<p class="daak-f"><label for="%1$s"><strong>%2$s</strong></label><br><select id="%1$s" name="%1$s" class="widefat"><option value="">—</option>', esc_attr( $key ), esc_html( $label ) );
	foreach ( $opts as $val => $text ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $v, $val, false ), esc_html( $text ) );
	}
	echo '</select></p>';
}

function daak_box_spec( $post ) {
	wp_nonce_field( 'daak_vehicle_save', 'daak_vehicle_nonce' );
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0 18px">';
	daak_meta_text( $post, 'dv_year', 'Year', '', 'number' );
	daak_meta_text( $post, 'dv_make', 'Make' );
	daak_meta_text( $post, 'dv_model', 'Model' );
	daak_meta_text( $post, 'dv_vin', 'VIN', 'On file so a recall check is checkable rather than a claim.' );
	daak_meta_select( $post, 'dv_class', 'Class' );
	daak_meta_select( $post, 'dv_drive', 'Drive' );
	daak_meta_select( $post, 'dv_transmission', 'Transmission' );
	daak_meta_select( $post, 'dv_fuel', 'Fuel' );
	daak_meta_text( $post, 'dv_seats', 'Seats', '', 'number' );
	daak_meta_text( $post, 'dv_doors', 'Doors', '', 'number' );
	daak_meta_text( $post, 'dv_mileage_policy', 'Mileage policy', 'e.g. Unlimited miles' );
	daak_meta_select( $post, 'dv_status', 'Status' );
	echo '</div>';

	$gravel = (int) get_post_meta( $post->ID, 'dv_gravel', true );
	printf(
		'<p style="margin-top:12px"><label><input type="checkbox" name="dv_gravel" value="1"%s> <strong>Gravel-road approved</strong></label><br><span class="description">This is the whole proposition. Whatever is ticked here must match the words in the rental agreement.</span></p>',
		checked( $gravel, 1, false )
	);

	$winter = (array) json_decode( (string) get_post_meta( $post->ID, 'dv_winter', true ), true );
	echo '<p><strong>Winter equipment</strong><br>';
	foreach ( daak_vehicle_vocab( 'dv_winter' ) as $slug => $label ) {
		printf(
			'<label style="margin-right:16px"><input type="checkbox" name="dv_winter[]" value="%s"%s> %s</label>',
			esc_attr( $slug ), checked( in_array( $slug, $winter, true ), true, false ), esc_html( $label )
		);
	}
	echo '</p>';
	daak_meta_text( $post, 'dv_recall_checked', 'Recall check date', 'Federal law bites at fleets of 35 or more; below that it is still right practice and worth saying on the site.', 'date' );
}

function daak_box_rates( $post ) {
	daak_meta_text( $post, 'dv_rate_day', 'Daily rate', '', 'number' );
	daak_meta_text( $post, 'dv_rate_week', 'Weekly rate', 'Leave empty for 7 x daily.', 'number' );
	daak_meta_text( $post, 'dv_rate_month', 'Monthly rate', 'Leave empty for 30 x daily.', 'number' );
	daak_meta_text( $post, 'dv_min_days', 'Minimum days', '', 'number' );
	daak_meta_text( $post, 'dv_deposit', 'Deposit', '', 'number' );
	echo '<p class="description">Seasonal multipliers are set once for the whole fleet under Rentals &rarr; Setup &rarr; Seasons.</p>';
}

function daak_box_gallery( $post ) {
	$ids = (array) json_decode( (string) get_post_meta( $post->ID, 'dv_gallery', true ), true );
	printf(
		'<p><label for="dv_gallery"><strong>Attachment IDs, comma separated</strong></label><br><input type="text" class="widefat" id="dv_gallery" name="dv_gallery" value="%s"></p>',
		esc_attr( implode( ',', array_map( 'absint', $ids ) ) )
	);
	echo '<p class="description">The featured image is the card photograph. These are the rest of the gallery.</p>';
	if ( $ids ) {
		echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">';
		foreach ( $ids as $id ) {
			$src = wp_get_attachment_image_url( (int) $id, 'thumbnail' );
			if ( $src ) { printf( '<img src="%s" width="80" height="80" style="object-fit:cover;border-radius:6px" alt="">', esc_url( $src ) ); }
		}
		echo '</div>';
	}
}

function daak_box_availability( $post ) {
	$blocks = daak_vehicle_blocks( $post->ID );
	$lines  = array();
	foreach ( $blocks as $b ) {
		$lines[] = sprintf( '%s | %s | %s', $b['from'], $b['to'], $b['reason'] );
	}
	printf(
		'<p><label for="dv_blocks"><strong>Dates this vehicle is not available</strong></label><br><textarea id="dv_blocks" name="dv_blocks" rows="6" class="widefat code">%s</textarea></p>',
		esc_textarea( implode( "\n", $lines ) )
	);
	echo '<p class="description">One per line: <code>YYYY-MM-DD | YYYY-MM-DD | reason</code>. Both dates are covered: a vehicle that comes back on the second date is not offered for a pick-up that same date, because nobody has looked at it yet. Confirming a request writes its dates in here automatically; a line added by a booking carries the request number and is rewritten when that request changes.</p>';

	$upcoming = daak_vehicle_requests( $post->ID );
	if ( $upcoming ) {
		echo '<p><strong>Requests for this vehicle</strong></p><ul style="margin:0">';
		foreach ( $upcoming as $r ) {
			printf(
				'<li><a href="%s">#%d</a> — %s to %s (%s)</li>',
				esc_url( get_edit_post_link( $r->ID ) ),
				(int) $r->ID,
				esc_html( get_post_meta( $r->ID, 'dr_pickup', true ) ),
				esc_html( get_post_meta( $r->ID, 'dr_return', true ) ),
				esc_html( get_post_meta( $r->ID, 'dr_status', true ) ?: 'new' )
			);
		}
		echo '</ul>';
	}
}

add_action( 'save_post_daak_vehicle', function ( $post_id ) {
	if ( ! isset( $_POST['daak_vehicle_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['daak_vehicle_nonce'] ) ), 'daak_vehicle_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	foreach ( daak_vehicle_meta_keys() as $key => $type ) {
		if ( 'dv_winter' === $key || 'dv_gravel' === $key ) { continue; }
		if ( ! isset( $_POST[ $key ] ) ) { continue; }
		$raw = wp_unslash( $_POST[ $key ] );
		if ( 'integer' === $type ) {
			$val = '' === trim( (string) $raw ) ? '' : (int) $raw;
		} elseif ( 'number' === $type ) {
			$val = '' === trim( (string) $raw ) ? '' : (float) $raw;
		} else {
			$val = sanitize_text_field( $raw );
			// A value the form never offered is dropped rather than stored.
			$vocab = daak_vehicle_vocab( $key );
			if ( $vocab && '' !== $val && ! isset( $vocab[ $val ] ) ) { $val = ''; }
		}
		update_post_meta( $post_id, $key, $val );
	}

	update_post_meta( $post_id, 'dv_gravel', isset( $_POST['dv_gravel'] ) ? 1 : 0 );

	$winter = array();
	foreach ( (array) ( $_POST['dv_winter'] ?? array() ) as $slug ) {
		$slug = sanitize_key( $slug );
		if ( isset( daak_vehicle_vocab( 'dv_winter' )[ $slug ] ) ) { $winter[] = $slug; }
	}
	update_post_meta( $post_id, 'dv_winter', wp_json_encode( $winter ) );

	$ids = array_filter( array_map( 'absint', explode( ',', (string) ( $_POST['dv_gallery'] ?? '' ) ) ) );
	update_post_meta( $post_id, 'dv_gallery', wp_json_encode( array_values( $ids ) ) );

	if ( isset( $_POST['dv_blocks'] ) ) {
		daak_save_blocks_from_text( $post_id, wp_unslash( $_POST['dv_blocks'] ) );
	}
} );

/** Fleet list columns worth having on the screen where a fleet is actually managed. */
add_filter( 'manage_daak_vehicle_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['daak_class']  = 'Class';
			$new['daak_drive']  = 'Drive';
			$new['daak_gravel'] = 'Gravel';
			$new['daak_rate']   = 'Daily';
			$new['daak_free']   = 'Next booked';
		}
	}
	return $new;
} );

add_action( 'manage_daak_vehicle_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'daak_class':
			echo esc_html( daak_vehicle_vocab( 'dv_class' )[ get_post_meta( $post_id, 'dv_class', true ) ] ?? '—' );
			break;
		case 'daak_drive':
			echo esc_html( daak_vehicle_vocab( 'dv_drive' )[ get_post_meta( $post_id, 'dv_drive', true ) ] ?? '—' );
			break;
		case 'daak_gravel':
			echo get_post_meta( $post_id, 'dv_gravel', true ) ? '<span style="color:#1B5E33">Yes</span>' : '—';
			break;
		case 'daak_rate':
			$r = get_post_meta( $post_id, 'dv_rate_day', true );
			echo $r ? esc_html( '$' . rtrim( rtrim( number_format( (float) $r, 2 ), '0' ), '.' ) ) : '—';
			break;
		case 'daak_free':
			$next = daak_next_block( $post_id );
			echo $next ? esc_html( $next['from'] . ' → ' . $next['to'] ) : '—';
			break;
	}
}, 10, 2 );
