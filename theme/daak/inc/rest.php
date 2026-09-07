<?php
/**
 * Two read-only endpoints: what is free, and what it costs.
 *
 * The fleet page and the booking form use them so a date change does not reload
 * the page on an airport wifi. Neither returns anything about a person.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function () {

	register_rest_route( 'daak/v1', '/availability', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'from'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'to'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'class'  => array( 'sanitize_callback' => 'sanitize_key' ),
			'drive'  => array( 'sanitize_callback' => 'sanitize_key' ),
			'seats'  => array( 'sanitize_callback' => 'absint' ),
			'gravel' => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'max'    => array( 'sanitize_callback' => 'absint' ),
		),
		'callback' => function ( WP_REST_Request $r ) {
			$from = daak_date( $r->get_param( 'from' ) );
			$to   = daak_date( $r->get_param( 'to' ) );
			$list = daak_search_vehicles( array(
				'from'   => $from,
				'to'     => $to,
				'class'  => $r->get_param( 'class' ),
				'drive'  => $r->get_param( 'drive' ),
				'seats'  => (int) $r->get_param( 'seats' ),
				'gravel' => '' === (string) $r->get_param( 'gravel' ) ? '' : (int) $r->get_param( 'gravel' ),
				'max'    => (int) $r->get_param( 'max' ),
			) );

			$out = array();
			foreach ( $list as $v ) {
				$quote = ( $from && $to ) ? daak_quote( $v->ID, $from, $to ) : null;
				$out[] = array(
					'id'     => $v->ID,
					'title'  => daak_vehicle_title( $v->ID ),
					'url'    => get_permalink( $v ),
					'image'  => get_the_post_thumbnail_url( $v, 'daak_card' ) ?: '',
					'class'  => daak_vehicle_vocab( 'dv_class' )[ get_post_meta( $v->ID, 'dv_class', true ) ] ?? '',
					'drive'  => daak_vehicle_vocab( 'dv_drive' )[ get_post_meta( $v->ID, 'dv_drive', true ) ] ?? '',
					'seats'  => (int) get_post_meta( $v->ID, 'dv_seats', true ),
					'gravel' => (bool) get_post_meta( $v->ID, 'dv_gravel', true ),
					'rate'   => (float) get_post_meta( $v->ID, 'dv_rate_day', true ),
					'total'  => $quote ? $quote['total'] : null,
					'days'   => $quote ? $quote['days'] : null,
				);
			}
			return rest_ensure_response( array(
				'from'     => $from,
				'to'       => $to,
				'count'    => count( $out ),
				'vehicles' => $out,
				'taxLine'  => daak_tax_line(),
			) );
		},
	) );

	register_rest_route( 'daak/v1', '/quote', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'vehicle' => array( 'sanitize_callback' => 'absint', 'required' => true ),
			'from'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'to'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'extras'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
		),
		'callback' => function ( WP_REST_Request $r ) {
			$id = (int) $r->get_param( 'vehicle' );
			if ( 'daak_vehicle' !== get_post_type( $id ) ) {
				return new WP_Error( 'daak_no_vehicle', 'No such vehicle', array( 'status' => 404 ) );
			}
			$from   = daak_date( $r->get_param( 'from' ) );
			$to     = daak_date( $r->get_param( 'to' ) );
			$extras = array_filter( array_map( 'sanitize_key', explode( ',', (string) $r->get_param( 'extras' ) ) ) );
			$quote  = daak_quote( $id, $from, $to, $extras );
			$quote['free'] = ( $from && $to ) ? daak_vehicle_is_free( $id, $from, $to ) : null;
			return rest_ensure_response( $quote );
		},
	) );
} );
