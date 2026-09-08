<?php
/**
 * The admin screens behind the profile record.
 *
 * One menu, six tabs, one POST handler. Every screen writes an option that a
 * template reads — there is no second place where a price, a road verdict or a
 * telephone number can be typed.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The menu suffix says whose content this is.
 *
 * Derived from the profile name rather than typed, because the theme is meant
 * to run for the next dealership too. Note that deriving initials from
 * "Downtown Auto AK" gives DAA and not DAAK — it takes first letters of words.
 * That is a menu label, not the badge; the badge is drawn art and unaffected.
 */
function daak_initials( $fallback = 'AK' ) {
	$name = trim( (string) daak_profile( 'name' ) );
	if ( '' === $name ) { return $fallback; }
	$skip = array( 'and', 'of', 'the', 'llc', 'inc' );
	$out  = '';
	foreach ( preg_split( '/[\s\-]+/', $name ) as $word ) {
		$word = preg_replace( '/[^A-Za-z0-9]/', '', $word );
		if ( '' === $word || in_array( strtolower( $word ), $skip, true ) ) { continue; }
		$out .= strtoupper( $word[0] );
		if ( strlen( $out ) >= 4 ) { break; }
	}
	return '' !== $out ? $out : $fallback;
}

function daak_menu_label( $label ) {
	return $label . '-' . daak_initials();
}

add_action( 'admin_menu', function () {
	add_menu_page(
		'Rental setup',
		daak_menu_label( 'Rentals' ),
		'manage_options',
		'daak-setup',
		'daak_render_settings',
		'dashicons-admin-site-alt3',
		26
	);
	add_submenu_page( 'daak-setup', 'Rental setup', 'Setup', 'manage_options', 'daak-setup', 'daak_render_settings' );
} );

function daak_settings_tabs() {
	return array(
		'profile' => 'Business profile',
		'policy'  => 'Policies',
		'roads'   => 'Roads',
		'extras'  => 'Add-ons',
		'seasons' => 'Seasons',
		'tax'     => 'Tax',
	);
}

function daak_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$tabs = daak_settings_tabs();
	$tab  = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'profile';

	if ( isset( $_POST['daak_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['daak_settings_nonce'] ) ), 'daak_settings' ) ) {
		daak_save_settings( $tab );
		echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
	}

	echo '<div class="wrap"><h1>Rental setup</h1><h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $slug => $label ) {
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=daak-setup&tab=' . $slug ) ),
			$slug === $tab ? ' nav-tab-active' : '',
			esc_html( $label )
		);
	}
	echo '</h2><form method="post"><table class="form-table" role="presentation">';
	call_user_func( 'daak_settings_tab_' . $tab );
	echo '</table>';
	wp_nonce_field( 'daak_settings', 'daak_settings_nonce' );
	submit_button();
	echo '</form></div>';
}

/* ---------------------------------------------------------------- helpers */

function daak_field( $label, $name, $value, $desc = '', $type = 'text' ) {
	printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $name ), esc_html( $label ) );
	if ( 'textarea' === $type ) {
		printf( '<textarea class="large-text" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $name ), esc_attr( $name ), esc_textarea( $value ) );
	} else {
		printf( '<input class="regular-text" type="%s" id="%s" name="%s" value="%s">', esc_attr( $type ), esc_attr( $name ), esc_attr( $name ), esc_attr( $value ) );
	}
	if ( $desc ) { printf( '<p class="description">%s</p>', wp_kses_post( $desc ) ); }
	echo '</td></tr>';
}

/**
 * A repeatable table, serialised as one textarea of pipe-separated rows.
 *
 * Deliberately not a drag-and-drop repeater: this is edited a handful of times
 * a year by one person, and a textarea has no JavaScript to break on the day the
 * rates change.
 */
function daak_rows_field( $label, $name, $rows, $cols, $desc ) {
	$lines = array();
	foreach ( $rows as $r ) {
		$vals = array();
		foreach ( $cols as $c ) { $vals[] = (string) ( $r[ $c ] ?? '' ); }
		$lines[] = implode( ' | ', $vals );
	}
	printf( '<tr><th scope="row"><label for="%s">%s</label></th><td>', esc_attr( $name ), esc_html( $label ) );
	printf( '<textarea class="large-text code" rows="8" id="%s" name="%s">%s</textarea>', esc_attr( $name ), esc_attr( $name ), esc_textarea( implode( "\n", $lines ) ) );
	printf( '<p class="description">One per line: <code>%s</code><br>%s</p></td></tr>', esc_html( implode( ' | ', $cols ) ), wp_kses_post( $desc ) );
}

function daak_parse_rows( $raw, $cols ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		if ( '' === trim( $line ) ) { continue; }
		$parts = array_map( 'trim', explode( '|', $line ) );
		$row   = array();
		foreach ( $cols as $i => $c ) { $row[ $c ] = sanitize_text_field( $parts[ $i ] ?? '' ); }
		$out[] = $row;
	}
	return $out;
}

function daak_post( $key ) {
	return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

/* ------------------------------------------------------------------ tabs */

function daak_settings_tab_profile() {
	$p = daak_profile();
	echo '<tr><td colspan="2"><p class="description">Everything on this tab is read by the site at render time. Nothing here is written into the theme, which is what lets the theme run for another dealership unchanged.</p></td></tr>';
	foreach ( daak_profile_fields() as $key => $label ) {
		$desc = '';
		if ( 'logo_id' === $key )      { $desc = 'Attachment ID of the badge in the media library. Upload it once; never commit it into the theme.'; }
		if ( 'hero_image_id' === $key ) { $desc = 'Attachment ID of the home-page photograph. Landscape, and busy on one side only — the headline sits over the left of it. Leave empty for the plain navy hero.'; }
		if ( 'rating' === $key )       { $desc = 'If reviews are shown they are quoted verbatim or linked. Never paraphrased.'; }
		if ( 'founded' === $key )      { $desc = 'The founding year, not the year of an administrative dissolution.'; }
		daak_field( $label, 'p_' . $key, $p[ $key ] ?? '', $desc );
	}
}

function daak_settings_tab_policy() {
	$fields = array(
		'gravel_headline'  => array( 'Headline', 'The first sentence on the home page.' ),
		'gravel_sentence'  => array( 'Gravel policy, in words', 'These exact words must also stand in the rental agreement. A site whose contract disagrees with its marketing about gravel is a site with a court case.' ),
		'response_promise' => array( 'Response promise', 'A promise on the site is a promise. Say who answers and how fast.' ),
		'min_age'          => array( 'Minimum age', '' ),
		'young_driver'     => array( 'Young driver terms', '' ),
		'deposit'          => array( 'Deposit', '' ),
		'fuel'             => array( 'Fuel policy', '' ),
		'mileage'          => array( 'Mileage', '' ),
		'pets'             => array( 'Pets', 'Alaska renters travel with dogs. Yes with a cleaning fee, or no — but say which.' ),
		'smoking'          => array( 'Smoking', '' ),
		'second_driver'    => array( 'Additional drivers', '' ),
		'one_way'          => array( 'One-way rentals', 'Fairbanks, Seward, Homer, Whittier — offered or not, and at what fee. "Ask us" is an answer; silence is not.' ),
		'airport'          => array( 'Airport pick-up', '' ),
		'ferry'            => array( 'Alaska Marine Highway ferries', '' ),
		'canada'           => array( 'Driving into Canada', 'Needs a Canadian non-resident inter-province insurance card carried in the vehicle.' ),
		'rto_credit'       => array( 'Rent-to-own credit', 'How much of the rental credits against the purchase price.' ),
		'insurance_note'   => array( 'Insurance note', '' ),
		'recall_note'      => array( 'Recall note', '' ),
		'studs_window'     => array( 'Studded tire window', 'Seasonal in Alaska and the window differs by latitude — Anchorage is north of 60°N, so it is the longer one. Verify the current statutory dates before publishing them; never print a date from memory.' ),
		'studs_verified_on'=> array( 'Studded window verified on', 'The date somebody last checked that against the statute.' ),
		'moose_note'       => array( 'Moose', 'One honest paragraph — dusk and dawn, and what to do — beats a disclaimer.' ),
		'block_heater'     => array( 'Block heater and plug-in', 'Every visitor asks and no national site explains it.' ),
	);
	$v = daak_policy();
	foreach ( $fields as $key => $meta ) {
		daak_field( $meta[0], 'pol_' . $key, $v[ $key ] ?? '', $meta[1], in_array( $key, array( 'gravel_sentence', 'canada', 'ferry', 'one_way', 'insurance_note', 'recall_note', 'moose_note', 'block_heater' ), true ) ? 'textarea' : 'text' );
	}
}

function daak_settings_tab_roads() {
	daak_rows_field(
		'Road policy',
		'roads',
		daak_roads(),
		array( 'road', 'majors', 'verdict', 'note' ),
		'verdict is one of <code>allowed</code>, <code>forbidden</code>, <code>ask</code>, <code>conditional</code>. This table is printed on the drive page, on every vehicle and in the policies page, so there is only one version of it.'
	);
}

function daak_settings_tab_extras() {
	daak_rows_field(
		'Add-ons',
		'extras',
		daak_extras(),
		array( 'slug', 'label', 'price', 'unit', 'note' ),
		'unit is <code>day</code> or <code>rental</code>. Leave price empty to show the add-on without a price ("ask us"). Add-ons are the margin, not the decoration.'
	);
}

function daak_settings_tab_seasons() {
	daak_rows_field(
		'Seasons',
		'seasons',
		daak_seasons(),
		array( 'label', 'from', 'to', 'multiplier' ),
		'from and to are <code>MM-DD</code> and may wrap the new year. multiplier applies to the daily rate; 1 means the base rate.'
	);
}

function daak_settings_tab_tax() {
	$t = daak_tax_settings();
	echo '<tr><td colspan="2"><p class="description">Look the current rates and filing schedule up at the Alaska Department of Revenue before typing them here. Do not trust a figure written from memory — including one in a build brief. Until both fields are filled the site says tax is added rather than naming a number.</p></td></tr>';
	daak_field( 'Alaska state rate (%)', 'tax_state_pct', $t['state_pct'], 'AS 43.52, passenger vehicle rentals of 90 days or less.' );
	daak_field( 'Anchorage municipal rate (%)', 'tax_muni_pct', $t['muni_pct'], 'AMC 12.50, levied on top of the state rate.' );
	printf(
		'<tr><th scope="row">How prices are shown</th><td><label><input type="radio" name="tax_mode" value="add"%s> Rates shown are net; tax is added</label><br><label><input type="radio" name="tax_mode" value="included"%s> Rates shown include tax</label></td></tr>',
		'included' === $t['mode'] ? '' : ' checked',
		'included' === $t['mode'] ? ' checked' : ''
	);
	daak_field( 'Verified on', 'tax_verified_on', $t['verified_on'], 'The date somebody last checked these against the published schedule.' );
	daak_field( 'Extra note', 'tax_note', $t['note'], '', 'textarea' );
	printf( '<tr><th scope="row">What the site says now</th><td><code>%s</code></td></tr>', esc_html( daak_tax_line() ) );
}

/* ------------------------------------------------------------------ save */

function daak_save_settings( $tab ) {
	switch ( $tab ) {
		case 'profile':
			$p = array();
			foreach ( array_keys( daak_profile_fields() ) as $k ) {
				$raw = daak_post( 'p_' . $k );
				if ( 'email' === $k ) {
					$p[ $k ] = sanitize_email( $raw );
				} elseif ( in_array( $k, array( 'sales_site', 'map_url', 'reviews_url' ), true ) ) {
					$p[ $k ] = esc_url_raw( $raw );
				} elseif ( 'logo_id' === $k || 'hero_image_id' === $k ) {
					$p[ $k ] = (string) absint( $raw );
				} else {
					$p[ $k ] = sanitize_text_field( $raw );
				}
			}
			update_option( DAAK_OPT_PROFILE, $p );
			break;

		case 'policy':
			$v = array();
			foreach ( array_keys( daak_policy() ) as $k ) {
				$v[ $k ] = sanitize_textarea_field( daak_post( 'pol_' . $k ) );
			}
			update_option( DAAK_OPT_POLICY, $v );
			break;

		case 'roads':
			update_option( DAAK_OPT_ROADS, daak_parse_rows( daak_post( 'roads' ), array( 'road', 'majors', 'verdict', 'note' ) ) );
			break;

		case 'extras':
			update_option( DAAK_OPT_EXTRAS, daak_parse_rows( daak_post( 'extras' ), array( 'slug', 'label', 'price', 'unit', 'note' ) ) );
			break;

		case 'seasons':
			update_option( DAAK_OPT_SEASONS, daak_parse_rows( daak_post( 'seasons' ), array( 'label', 'from', 'to', 'multiplier' ) ) );
			break;

		case 'tax':
			update_option( DAAK_OPT_TAX, array(
				'state_pct'   => '' === trim( (string) daak_post( 'tax_state_pct' ) ) ? '' : (string) (float) daak_post( 'tax_state_pct' ),
				'muni_pct'    => '' === trim( (string) daak_post( 'tax_muni_pct' ) ) ? '' : (string) (float) daak_post( 'tax_muni_pct' ),
				'mode'        => 'included' === daak_post( 'tax_mode' ) ? 'included' : 'add',
				'note'        => sanitize_textarea_field( daak_post( 'tax_note' ) ),
				'verified_on' => sanitize_text_field( daak_post( 'tax_verified_on' ) ),
			) );
			break;
	}
	wp_cache_flush();
}

/** An empty profile is a broken site, so say so where it will be seen. */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || daak_profile_ready() ) { return; }
	printf(
		'<div class="notice notice-warning"><p>The rental site has no business profile yet — the header, footer and contact page have nothing to print. <a href="%s">Fill it in</a>.</p></div>',
		esc_url( admin_url( 'admin.php?page=daak-setup' ) )
	);
} );
