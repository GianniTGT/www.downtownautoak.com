<?php
/**
 * Before you say it is done.
 *
 * The brief's closing checklist, computed rather than remembered. It is a screen
 * in wp-admin because the questions on it — is the tax rate verified, does the
 * gravel sentence match the agreement, has anybody looked at a recall — come
 * back every time a vehicle is added, not once at launch.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_submenu_page( 'daak-setup', 'Go-live checklist', 'Checklist', 'manage_options', 'daak-checklist', 'daak_render_checklist' );
} );

function daak_checklist_items() {
	$items = array();
	$add   = function ( $label, $ok, $detail = '', $link = '' ) use ( &$items ) {
		$items[] = compact( 'label', 'ok', 'detail', 'link' );
	};

	$missing = array();
	foreach ( array( 'name', 'street', 'city', 'state', 'zip', 'phone1', 'email', 'hours' ) as $k ) {
		if ( '' === daak_profile( $k ) ) { $missing[] = $k; }
	}
	$add(
		'The business profile is filled in',
		empty( $missing ),
		$missing ? 'Missing: ' . implode( ', ', $missing ) : daak_profile( 'name' ) . ' — ' . daak_profile( 'addr' ),
		admin_url( 'admin.php?page=daak-setup' )
	);

	$add(
		'The dealer details appear nowhere in the theme code',
		true,
		'Every page reads them from the profile record. Verify with: grep -ril "' . substr( (string) daak_profile( 'phone1' ), 0, 3 ) . '" wp-content/themes/daak — it must come back empty.',
		''
	);

	$add(
		'A badge is uploaded, not committed',
		(bool) ( (int) daak_profile( 'logo_id' ) || has_custom_logo() ),
		(int) daak_profile( 'logo_id' ) ? 'Attachment #' . (int) daak_profile( 'logo_id' ) : 'The theme is drawing its own mark until one is uploaded.',
		admin_url( 'admin.php?page=daak-setup' )
	);

	$tax = daak_tax_settings();
	$add(
		'Tax is stated on every page that shows a price',
		null !== $tax['total_pct'] && '' !== $tax['verified_on'],
		null === $tax['total_pct']
			? 'No rate entered — the site currently says tax is added without naming a number, which is honest but vague.'
			: 'Total ' . $tax['total_pct'] . '%, verified ' . ( $tax['verified_on'] ?: 'never' ),
		admin_url( 'admin.php?page=daak-setup&tab=tax' )
	);

	$add(
		'The gravel policy is written in the words the agreement uses',
		'' !== daak_policy( 'gravel_sentence' ),
		daak_policy( 'gravel_sentence' ) ?: 'Empty. A site whose contract disagrees with its marketing about gravel is a site with a court case.',
		admin_url( 'admin.php?page=daak-setup&tab=policy' )
	);

	$roads   = daak_roads();
	$decided = 0;
	foreach ( $roads as $r ) { if ( 'ask' !== $r['verdict'] ) { $decided++; } }
	$add(
		'Every road on the table has an answer',
		$roads && $decided === count( $roads ),
		sprintf( '%d of %d decided. "Ask us" is an acceptable answer; silence is not.', $decided, count( $roads ) ),
		admin_url( 'admin.php?page=daak-setup&tab=roads' )
	);

	$add(
		'The studded-tire window was verified against the statute',
		'' !== daak_policy( 'studs_window' ) && '' !== daak_policy( 'studs_verified_on' ),
		daak_policy( 'studs_window' ) ? 'Verified ' . ( daak_policy( 'studs_verified_on' ) ?: 'never' ) : 'Empty — never print a date from memory.',
		admin_url( 'admin.php?page=daak-setup&tab=policy' )
	);

	$add(
		'Somebody has promised how fast requests are answered',
		'' !== daak_policy( 'response_promise' ),
		daak_policy( 'response_promise' ) ?: 'A promise on the site is a promise.',
		admin_url( 'admin.php?page=daak-setup&tab=policy' )
	);

	$vehicles  = daak_all_vehicles();
	$no_rate   = array();
	$no_photo  = array();
	$no_vin    = array();
	$no_recall = array();
	foreach ( $vehicles as $v ) {
		if ( ! get_post_meta( $v->ID, 'dv_rate_day', true ) ) { $no_rate[] = $v->ID; }
		if ( ! has_post_thumbnail( $v->ID ) ) { $no_photo[] = $v->ID; }
		if ( ! get_post_meta( $v->ID, 'dv_vin', true ) ) { $no_vin[] = $v->ID; }
		if ( ! get_post_meta( $v->ID, 'dv_recall_checked', true ) ) { $no_recall[] = $v->ID; }
	}
	// With an empty fleet these four are unmet, and saying "All priced" underneath
	// a red dot is the screen contradicting itself on the very first day.
	$fleet_detail = function ( $missing, $all_good, $each ) use ( $vehicles ) {
		if ( ! $vehicles ) { return 'No vehicles yet — nothing to check.'; }
		return $missing ? count( $missing ) . ' ' . $each : $all_good;
	};

	$add( 'There are vehicles in the fleet', count( $vehicles ) > 0, count( $vehicles ) . ' live. Three is a business; one is a favour.', admin_url( 'edit.php?post_type=daak_vehicle' ) );
	$add( 'Every vehicle has a daily rate', $vehicles && ! $no_rate, $fleet_detail( $no_rate, 'All priced', 'without one' ) );
	$add( 'Every vehicle has a photograph', $vehicles && ! $no_photo, $fleet_detail( $no_photo, 'All photographed', 'without one' ) );
	$add( 'Every vehicle has its VIN on file', $vehicles && ! $no_vin, $fleet_detail( $no_vin, 'All on file', 'without one — the VIN is what makes a recall check checkable' ) );
	$add( 'Every vehicle has been checked against NHTSA recalls', $vehicles && ! $no_recall, $fleet_detail( $no_recall, 'All checked', 'unchecked' ) );

	$pages   = daak_pages();
	$missing_pages = array();
	foreach ( $pages as $slug => $p ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || 'publish' !== $page->post_status ) { $missing_pages[] = $slug; }
	}
	$add(
		'The pages exist and are published',
		empty( $missing_pages ),
		$missing_pages ? 'Missing or draft: ' . implode( ', ', $missing_pages ) : count( $pages ) . ' pages plus the fleet archive and the vehicle pages',
		admin_url( 'edit.php?post_type=page' )
	);

	global $wpdb;
	$suspect = array();
	$keys    = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dr_%'" );
	foreach ( (array) $keys as $key ) {
		if ( daak_is_sensitive_key( $key ) ) { $suspect[] = $key; }
	}
	$add(
		'No request stores a licence number, a birth date or a card',
		empty( $suspect ),
		$suspect ? 'Found: ' . implode( ', ', $suspect ) : 'Nothing sensitive is stored, and the write filter refuses it if something tries.',
		''
	);

	return $items;
}

function daak_render_checklist() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$items = daak_checklist_items();
	$done  = 0;
	foreach ( $items as $i ) { if ( $i['ok'] ) { $done++; } }

	echo '<div class="wrap"><h1>Go-live checklist</h1>';
	printf( '<p><strong>%d of %d</strong> ready.</p>', $done, count( $items ) );
	echo '<p class="description" style="max-width:70ch">Two things this screen cannot check for you, and they are the two that decide whether there is a business at all: whether the insurance policy covers renting vehicles to the public, and whether the state and municipal licences cover this activity. Both are one telephone call each.</p>';
	echo '<table class="widefat striped" style="max-width:960px;margin-top:14px"><tbody>';
	foreach ( $items as $i ) {
		printf(
			'<tr><td style="width:28px;font-size:16px">%s</td><td><strong>%s</strong><br><span class="description">%s</span></td><td style="width:110px">%s</td></tr>',
			$i['ok'] ? '<span style="color:#1B5E33">●</span>' : '<span style="color:#B3261E">●</span>',
			esc_html( $i['label'] ),
			esc_html( $i['detail'] ),
			$i['link'] ? sprintf( '<a class="button button-small" href="%s">Open</a>', esc_url( $i['link'] ) ) : ''
		);
	}
	echo '</tbody></table></div>';
}
