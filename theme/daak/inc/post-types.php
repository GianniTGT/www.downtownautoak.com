<?php
/**
 * The two things this site stores: a vehicle and a request.
 *
 * Deliberately not stored: anything the dealership's desktop application would
 * want. A rental vehicle has a different life and different money from a car on
 * the sale lot, and mixing them would put rental revenue into reports built to
 * measure sale margin. The VIN is the field that will join them if that day comes.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {

	register_post_type( 'daak_vehicle', array(
		'labels' => array(
			'name'               => daak_menu_label( 'Fleet' ),
			'singular_name'      => 'Vehicle',
			'add_new_item'       => 'Add vehicle',
			'edit_item'          => 'Edit vehicle',
			'search_items'       => 'Search fleet',
			'not_found'          => 'No vehicles yet',
			'menu_name'          => daak_menu_label( 'Fleet' ),
		),
		'public'        => true,
		'has_archive'   => 'fleet',
		'rewrite'       => array( 'slug' => 'fleet', 'with_front' => false ),
		'menu_icon'     => 'dashicons-car',
		'menu_position' => 25,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		'show_in_rest'  => true,
		'rest_base'     => 'vehicles',
	) );

	register_post_type( 'daak_request', array(
		'labels' => array(
			'name'          => daak_menu_label( 'Requests' ),
			'singular_name' => 'Request',
			'edit_item'     => 'Rental request',
			'not_found'     => 'No requests yet',
			'menu_name'     => daak_menu_label( 'Requests' ),
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-calendar-alt',
		'menu_position'       => 26,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'supports'            => array( 'title' ),
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_in_rest'        => false,   // requests carry a customer's telephone number
	) );
} );

/** Vehicle fields, and the type each is stored as. */
function daak_vehicle_meta_keys() {
	return array(
		'dv_vin'            => 'string',
		'dv_year'           => 'integer',
		'dv_make'           => 'string',
		'dv_model'          => 'string',
		'dv_class'          => 'string',
		'dv_seats'          => 'integer',
		'dv_doors'          => 'integer',
		'dv_drive'          => 'string',
		'dv_transmission'   => 'string',
		'dv_fuel'           => 'string',
		'dv_mileage_policy' => 'string',
		'dv_gravel'         => 'integer',
		'dv_winter'         => 'string',
		'dv_rate_day'       => 'number',
		'dv_rate_week'      => 'number',
		'dv_rate_month'     => 'number',
		'dv_min_days'       => 'integer',
		'dv_deposit'        => 'number',
		'dv_status'         => 'string',
		'dv_recall_checked' => 'string',
	);
}

/** The vocabularies the vehicle form offers. A value the form never offered is dropped rather than stored. */
function daak_vehicle_vocab( $field ) {
	$v = array(
		'dv_class'        => array( 'suv' => 'SUV', 'pickup' => 'Pickup truck', 'van' => 'Van', 'sedan' => 'Sedan', 'wagon' => 'Wagon', 'crossover' => 'Crossover' ),
		'dv_drive'        => array( '4wd' => '4WD', 'awd' => 'AWD', 'fwd' => 'FWD', 'rwd' => 'RWD' ),
		'dv_transmission' => array( 'automatic' => 'Automatic', 'manual' => 'Manual' ),
		'dv_fuel'         => array( 'petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid' ),
		'dv_status'       => array( 'active' => 'In the fleet', 'maintenance' => 'In the workshop', 'retired' => 'Out of the fleet' ),
		'dv_winter'       => array( 'studs' => 'Studded tires', 'winter_tires' => 'Winter tires', 'block_heater' => 'Engine block heater', 'remote_start' => 'Remote start' ),
	);
	return $v[ $field ] ?? array();
}

add_action( 'init', function () {
	foreach ( daak_vehicle_meta_keys() as $key => $type ) {
		register_post_meta( 'daak_vehicle', $key, array(
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}
}, 20 );

/** A vehicle's display name, falling back to what is actually filled in. */
function daak_vehicle_title( $post_id ) {
	$parts = array_filter( array(
		get_post_meta( $post_id, 'dv_year', true ),
		get_post_meta( $post_id, 'dv_make', true ),
		get_post_meta( $post_id, 'dv_model', true ),
	) );
	$built = trim( implode( ' ', $parts ) );
	return $built ?: get_the_title( $post_id );
}
