<?php
/**
 * What the page tells a search engine and a shared link.
 *
 * Structured data is filled from the profile record and the fleet, never from
 * literals — a review count typed into a template is a review count that goes
 * stale silently.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', function () {
	$name = daak_profile( 'name' );
	if ( ! $name ) { return; }

	$graph = array(
		'@context' => 'https://schema.org',
		'@type'    => 'AutoRental',
		'name'     => $name,
		'url'      => home_url( '/' ),
	);
	if ( daak_profile( 'phone1' ) )  { $graph['telephone'] = daak_profile( 'phone1' ); }
	if ( daak_profile( 'email' ) )   { $graph['email'] = daak_profile( 'email' ); }
	if ( daak_profile( 'street' ) ) {
		$graph['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => daak_profile( 'street' ),
			'addressLocality' => daak_profile( 'city' ),
			'addressRegion'   => daak_profile( 'state' ),
			'postalCode'      => daak_profile( 'zip' ),
			'addressCountry'  => 'US',
		);
	}
	if ( daak_profile( 'hours' ) )   { $graph['openingHours'] = daak_profile( 'hours' ); }
	if ( daak_profile( 'founded' ) ) { $graph['foundingDate'] = daak_profile( 'founded' ); }
	if ( daak_profile( 'rating' ) && daak_profile( 'rating_count' ) ) {
		$graph['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => daak_profile( 'rating' ),
			'reviewCount' => daak_profile( 'rating_count' ),
		);
	}

	if ( is_singular( 'daak_vehicle' ) ) {
		$id    = get_the_ID();
		$rate  = (float) get_post_meta( $id, 'dv_rate_day', true );
		$car   = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Car',
			'name'          => daak_vehicle_title( $id ),
			'url'           => get_permalink( $id ),
			'vehicleTransmission' => daak_vehicle_vocab( 'dv_transmission' )[ get_post_meta( $id, 'dv_transmission', true ) ] ?? '',
			'seatingCapacity'     => (int) get_post_meta( $id, 'dv_seats', true ),
			'driveWheelConfiguration' => daak_vehicle_vocab( 'dv_drive' )[ get_post_meta( $id, 'dv_drive', true ) ] ?? '',
		);
		if ( get_the_post_thumbnail_url( $id, 'daak_card' ) ) { $car['image'] = get_the_post_thumbnail_url( $id, 'daak_card' ); }
		if ( $rate ) {
			$car['offers'] = array(
				'@type'         => 'Offer',
				'price'         => $rate,
				'priceCurrency' => 'USD',
				'availability'  => 'https://schema.org/InStock',
				'description'   => daak_tax_line(),
			);
		}
		printf( '<script type="application/ld+json">%s</script>' . "\n", wp_json_encode( array_filter( $car ) ) );
	}

	printf( '<script type="application/ld+json">%s</script>' . "\n", wp_json_encode( $graph ) );
}, 20 );

/** Open Graph, so a link shared to a group chat shows the vehicle and not the theme name. */
add_action( 'wp_head', function () {
	$title = wp_get_document_title();
	$desc  = is_singular() ? wp_strip_all_tags( get_the_excerpt() ) : daak_policy( 'gravel_headline' );
	$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'daak_hero' ) : DAAK_URI . '/assets/img/daak-badge-512.png';

	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_trim_words( (string) $desc, 30 ) ) );
	printf( '<meta property="og:type" content="website">' . "\n" );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( home_url( add_query_arg( array() ) ) ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( (string) $image ) );
	printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( is_singular() ? get_permalink() : home_url( add_query_arg( array() ) ) ) );
}, 5 );
