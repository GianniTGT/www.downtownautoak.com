<?php
/**
 * The privacy boundary.
 *
 * This site collects a name, a telephone number, an email, dates, a vehicle and
 * extras. It never collects a Social Security number, a date of birth, a
 * driver's licence number, or bank or card details. The licence is examined in
 * person, at the counter, where it can be looked at.
 *
 * That is the single most reassuring sentence the site can print, and it is also
 * what keeps the sensitive half off this server. A sentence is a promise though,
 * and a promise with nothing enforcing it is how these things drift. So: any
 * field posted to a request that looks like one of those is dropped before it
 * can be stored, and anything already stored under such a key is refused.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function daak_sensitive_key_patterns() {
	// \b is no use here: an underscore counts as a word character, so \bdob\b
	// never matches dr_dob — which is exactly the field name a form would use.
	// The lookarounds below treat _ and - as separators, as a field name does.
	return array(
		'/(?<![a-z])ssn(?![a-z])|social.?security/i',
		'/(?<![a-z])dob(?![a-z])|date.?of.?birth|birth.?date|birthday/i',
		'/driver.{0,2}licen[cs]e|licen[cs]e.{0,2}(no|number|num)|(?<![a-z])dl.{0,2}(no|num|number)(?![a-z])/i',
		'/credit.?card|card.{0,2}(number|num|cvv|cvc)|(?<![a-z])cvv(?![a-z])|(?<![a-z])cvc(?![a-z])|(?<![a-z])iban(?![a-z])|routing.?number|account.?number/i',
		'/passport/i',
	);
}

function daak_is_sensitive_key( $key ) {
	foreach ( daak_sensitive_key_patterns() as $pattern ) {
		if ( preg_match( $pattern, (string) $key ) ) { return true; }
	}
	return false;
}

/** Nothing sensitive is ever written onto a request, whoever asks. */
add_filter( 'update_post_metadata', function ( $check, $object_id, $meta_key ) {
	if ( 'daak_request' === get_post_type( $object_id ) && daak_is_sensitive_key( $meta_key ) ) {
		return false;   // refuse the write, quietly and completely
	}
	return $check;
}, 10, 3 );

add_filter( 'add_post_metadata', function ( $check, $object_id, $meta_key ) {
	if ( 'daak_request' === get_post_type( $object_id ) && daak_is_sensitive_key( $meta_key ) ) {
		return false;
	}
	return $check;
}, 10, 3 );

/**
 * A form on this site must not grow a sensitive field by accident — through a
 * plugin, a paste from another site's markup, or a helpful edit. If one appears,
 * it is stripped from the rendered page and the administrator is told.
 */
add_filter( 'daak_form_field_allowed', function ( $allowed, $name ) {
	return $allowed && ! daak_is_sensitive_key( $name );
}, 10, 2 );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$hits = get_transient( 'daak_sensitive_hit' );
	if ( ! $hits ) { return; }
	printf(
		'<div class="notice notice-error"><p>A form field named <code>%s</code> was refused: this site does not collect licence, birth-date, card or Social Security details. They are checked at the counter.</p></div>',
		esc_html( (string) $hits )
	);
	delete_transient( 'daak_sensitive_hit' );
} );
