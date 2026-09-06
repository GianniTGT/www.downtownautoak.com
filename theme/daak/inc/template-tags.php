<?php
/**
 * The pieces every template draws with.
 *
 * Everything here reads the profile record and the fleet. None of it knows a
 * telephone number, and none of it invents a fact — an unanswered setting prints
 * nothing rather than a plausible default.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The badge.
 *
 * An uploaded logo always wins — the dealer's mark belongs in the media library,
 * not in the theme, which is what lets this theme run for the next dealership.
 * The drawn badge in assets/img is only what stands there until one is uploaded.
 *
 * @param string $class   CSS class.
 * @param string $variant 'reverse' for navy grounds — the footer, a photograph.
 */
function daak_logo( $class = 'brand-badge', $variant = 'default' ) {
	$id = (int) daak_profile( 'logo_id' );
	if ( $id && wp_get_attachment_image( $id, 'medium' ) ) {
		echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => $class, 'alt' => esc_attr( daak_profile( 'name' ) ) ) );
		return;
	}
	if ( has_custom_logo() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => $class, 'alt' => esc_attr( daak_profile( 'name' ) ) ) );
		return;
	}
	// The brand's own rule: the full badge holds four things — frame, name, wings,
	// letters — and below about 40px they collide. In the header and the footer the
	// wordmark stands beside the mark anyway, so the small-size artwork (AK alone)
	// is the right one there. The full badge is what og:image and print use.
	$file = 'reverse' === $variant ? 'daak-badge-reverse.svg' : 'daak-monogram.svg';
	printf(
		'<img class="%s" src="%s" width="52" height="52" alt="%s">',
		esc_attr( $class ),
		esc_url( DAAK_URI . '/assets/img/' . $file ),
		esc_attr( daak_profile( 'name', 'Home' ) )
	);
}

function daak_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

function daak_fleet_url( $args = array() ) {
	$url = get_post_type_archive_link( 'daak_vehicle' ) ?: home_url( '/fleet/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/** Money without cents when there are none — a rate card is not a ledger. */
function daak_rate( $amount ) {
	$amount = (float) $amount;
	return '$' . ( floor( $amount ) == $amount ? number_format( $amount ) : number_format( $amount, 2 ) );
}

/** The date-range search. The same markup on the home page, the fleet and a vehicle. */
function daak_search_form( $args = array() ) {
	$a = wp_parse_args( $args, array( 'compact' => false, 'action' => daak_fleet_url() ) );
	$from = isset( $_GET['from'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : '';
	$to   = isset( $_GET['to'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) : '';
	?>
	<form class="datesearch<?php echo $a['compact'] ? ' is-compact' : ''; ?>" method="get" action="<?php echo esc_url( $a['action'] ); ?>">
		<div class="field">
			<label for="from<?php echo $a['compact'] ? '-c' : ''; ?>">Pick-up</label>
			<input type="date" id="from<?php echo $a['compact'] ? '-c' : ''; ?>" name="from" value="<?php echo esc_attr( $from ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
		</div>
		<div class="field">
			<label for="to<?php echo $a['compact'] ? '-c' : ''; ?>">Return</label>
			<input type="date" id="to<?php echo $a['compact'] ? '-c' : ''; ?>" name="to" value="<?php echo esc_attr( $to ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
		</div>
		<button class="btn btn-accent" type="submit">See what is free</button>
	</form>
	<?php
}

/** One vehicle card. Photograph, class, seats, drive, transmission, rate, gravel. */
function daak_vehicle_card( $post_id, $dates = array() ) {
	$class  = daak_vehicle_vocab( 'dv_class' )[ get_post_meta( $post_id, 'dv_class', true ) ] ?? '';
	$drive  = daak_vehicle_vocab( 'dv_drive' )[ get_post_meta( $post_id, 'dv_drive', true ) ] ?? '';
	$trans  = daak_vehicle_vocab( 'dv_transmission' )[ get_post_meta( $post_id, 'dv_transmission', true ) ] ?? '';
	$seats  = (int) get_post_meta( $post_id, 'dv_seats', true );
	$rate   = (float) get_post_meta( $post_id, 'dv_rate_day', true );
	$gravel = (int) get_post_meta( $post_id, 'dv_gravel', true );
	$url    = get_permalink( $post_id );
	if ( ! empty( $dates['from'] ) && ! empty( $dates['to'] ) ) {
		$url = add_query_arg( array( 'from' => $dates['from'], 'to' => $dates['to'] ), $url );
	}
	?>
	<article class="card vcard">
		<a class="vcard-shot" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<?php echo get_the_post_thumbnail( $post_id, 'daak_card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
			<?php else : ?>
				<span class="vcard-noshot"></span>
			<?php endif; ?>
			<?php if ( $gravel ) : ?><span class="tag tag-gravel">Gravel approved</span><?php endif; ?>
		</a>
		<div class="vcard-body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( daak_vehicle_title( $post_id ) ); ?></a></h3>
			<ul class="specline">
				<?php if ( $class ) : ?><li><?php echo esc_html( $class ); ?></li><?php endif; ?>
				<?php if ( $seats ) : ?><li><?php echo (int) $seats; ?> seats</li><?php endif; ?>
				<?php if ( $drive ) : ?><li><?php echo esc_html( $drive ); ?></li><?php endif; ?>
				<?php if ( $trans ) : ?><li class="hide-narrow"><?php echo esc_html( $trans ); ?></li><?php endif; ?>
			</ul>
			<p class="vcard-foot">
				<?php if ( $rate ) : ?>
					<span class="price"><?php echo esc_html( daak_rate( $rate ) ); ?><small>/day</small></span>
				<?php else : ?>
					<span class="price price-ask">Rate on request</span>
				<?php endif; ?>
				<a class="btn btn-outline" href="<?php echo esc_url( $url ); ?>">Details</a>
			</p>
		</div>
	</article>
	<?php
}

/** The road table. One record, printed wherever the question comes up. */
function daak_road_table( $compact = false ) {
	$roads = daak_roads();
	if ( ! $roads ) { return; }
	?>
	<div class="tablewrap">
	<table class="roads">
		<thead>
			<tr><th scope="col">Road</th><?php if ( ! $compact ) : ?><th scope="col">The national chains</th><?php endif; ?><th scope="col">Us</th></tr>
		</thead>
		<tbody>
		<?php foreach ( $roads as $r ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $r['road'] ); ?><?php if ( $r['note'] ) : ?><span class="roadnote"><?php echo esc_html( $r['note'] ); ?></span><?php endif; ?></th>
				<?php if ( ! $compact ) : ?><td class="dim"><?php echo esc_html( $r['majors'] ?: '—' ); ?></td><?php endif; ?>
				<td><span class="verdict v-<?php echo esc_attr( $r['verdict'] ); ?>"><?php echo esc_html( daak_road_verdict_label( $r['verdict'] ) ); ?></span></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<?php
	$sentence = daak_policy( 'gravel_sentence' );
	if ( $sentence ) {
		printf( '<p class="roads-words">%s</p>', esc_html( $sentence ) );
	}
}

/** Add-ons as checkboxes with prices, which is where the margin is. */
function daak_extras_fields( $checked = array() ) {
	$extras = daak_extras();
	if ( ! $extras ) { return; }
	echo '<fieldset class="extras"><legend>Add-ons</legend><div class="extras-grid">';
	foreach ( $extras as $e ) {
		printf(
			'<label class="extra"><input type="checkbox" name="dr_extras[]" value="%s" data-price="%s" data-unit="%s"%s><span class="extra-body"><b>%s</b>%s<em>%s</em></span></label>',
			esc_attr( $e['slug'] ),
			esc_attr( (string) $e['price'] ),
			esc_attr( $e['unit'] ),
			checked( in_array( $e['slug'], (array) $checked, true ), true, false ),
			esc_html( $e['label'] ),
			$e['note'] ? '<small>' . esc_html( $e['note'] ) . '</small>' : '',
			'' === $e['price'] ? 'Ask us' : esc_html( daak_rate( $e['price'] ) . ( 'day' === $e['unit'] ? ' / day' : ' / rental' ) )
		);
	}
	echo '</div></fieldset>';
}

/**
 * The booking form.
 *
 * Name, telephone, email, dates, vehicle, extras, message. Nothing else — and
 * the sentence that says so is part of the form, not a footnote, because it is
 * the most reassuring thing on the page.
 */
function daak_booking_form( $vehicle_id = 0, $args = array() ) {
	$a = wp_parse_args( $args, array( 'title' => 'Request these dates', 'source' => 'site' ) );
	$from = isset( $_GET['from'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : '';
	$to   = isset( $_GET['to'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) : '';
	?>
	<section class="bookbox" id="request">
		<h2><?php echo esc_html( $a['title'] ); ?></h2>
		<?php echo wp_kses_post( daak_request_notice() ); ?>
		<form class="bookform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-vehicle="<?php echo (int) $vehicle_id; ?>">
			<input type="hidden" name="action" value="daak_request">
			<input type="hidden" name="dr_vehicle" value="<?php echo (int) $vehicle_id; ?>">
			<input type="hidden" name="dr_source" value="<?php echo esc_attr( $a['source'] ); ?>">
			<input type="hidden" name="daak_t" value="<?php echo esc_attr( (string) time() ); ?>">
			<?php wp_nonce_field( 'daak_request', 'daak_nonce' ); ?>
			<p class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="daak_hp" value="" tabindex="-1" autocomplete="off"></label></p>

			<div class="grid2">
				<p class="field"><label for="dr_pickup">Pick-up date</label><input type="date" id="dr_pickup" name="dr_pickup" required value="<?php echo esc_attr( $from ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
				<p class="field"><label for="dr_return">Return date</label><input type="date" id="dr_return" name="dr_return" required value="<?php echo esc_attr( $to ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
				<p class="field"><label for="dr_pickup_time">Pick-up time</label><input type="time" id="dr_pickup_time" name="dr_pickup_time"></p>
				<p class="field"><label for="dr_return_time">Return time</label><input type="time" id="dr_return_time" name="dr_return_time"></p>
			</div>

			<?php daak_extras_fields(); ?>

			<div class="quote" data-quote hidden>
				<h3>Your estimate</h3>
				<ul data-quote-lines></ul>
				<p class="quote-total"><span>Total</span><b data-quote-total></b></p>
				<p class="quote-tax"><?php echo esc_html( daak_tax_line() ); ?></p>
			</div>

			<div class="grid2">
				<p class="field"><label for="dr_name">Your name</label><input type="text" id="dr_name" name="dr_name" required autocomplete="name"></p>
				<p class="field"><label for="dr_phone">Telephone</label><input type="tel" id="dr_phone" name="dr_phone" required autocomplete="tel"></p>
			</div>
			<p class="field"><label for="dr_email">Email <span class="opt">(optional)</span></label><input type="email" id="dr_email" name="dr_email" autocomplete="email"></p>
			<p class="field"><label for="dr_message">Anything we should know <span class="opt">(optional)</span></label><textarea id="dr_message" name="dr_message" rows="3"></textarea></p>

			<button class="btn btn-accent btn-lg" type="submit">Send request</button>
			<p class="formnote">
				We ask for a name, a number and your dates — nothing else. No licence number, no date of birth, no card details.
				Your licence is looked at in person, at the counter.
				<?php if ( daak_policy( 'response_promise' ) ) : ?><br><?php echo esc_html( daak_policy( 'response_promise' ) ); ?><?php endif; ?>
			</p>
		</form>
	</section>
	<?php
}

/**
 * The vendor credit. The dealer's mark is uploaded; this one ships with the
 * theme, because the vendor is the same whoever installs it.
 *
 * The build brief wrote the vendor URL as hoponeurope.com. It is not: the
 * vendor's own site is tiff-software-solutions.com, which is also what the
 * dealership's theme links to. Corrected on the vendor's word, September 2026.
 */
function daak_vendor_credit() {
	printf(
		'<a class="tiff-credit" href="%s" rel="noopener"><img src="%s" alt="" width="14" height="15" loading="lazy"><span>Site by <b>TIFF</b> Software Solutions</span></a>',
		esc_url( apply_filters( 'daak_vendor_url', 'https://tiff-software-solutions.com/' ) ),
		esc_url( DAAK_URI . '/assets/img/tiff-mark-footer.svg' )
	);
}

/** Reviews: the real rating and a link to the listing. Never a paraphrase, never an invention. */
function daak_reviews_line() {
	$rating = daak_profile( 'rating' );
	$count  = daak_profile( 'rating_count' );
	$url    = daak_profile( 'reviews_url' );
	if ( ! $rating || ! $count ) { return; }
	$stars = str_repeat( '★', (int) round( (float) $rating ) ) . str_repeat( '☆', max( 0, 5 - (int) round( (float) $rating ) ) );
	printf(
		'<p class="reviews"><span class="stars" aria-hidden="true">%s</span> <b>%s</b> from %s Google reviews%s</p>',
		esc_html( $stars ),
		esc_html( $rating ),
		esc_html( $count ),
		$url ? sprintf( ' — <a href="%s" rel="noopener nofollow" target="_blank">read them</a>', esc_url( $url ) ) : ''
	);
}

/** A policy line, printed only when somebody has answered it. */
function daak_policy_row( $key, $label ) {
	$value = daak_policy( $key );
	if ( ! $value ) { return; }
	printf( '<div class="polrow"><dt>%s</dt><dd>%s</dd></div>', esc_html( $label ), esc_html( $value ) );
}

/** Whatever the owner has typed into the home page, if anything. */
function daak_the_content_if_any() {
	if ( ! have_posts() ) { return; }
	the_post();
	$content = trim( (string) get_the_content() );
	if ( '' === $content ) { rewind_posts(); return; }
	echo '<section class="section"><div class="wrap prose">';
	the_content();
	echo '</div></section>';
	rewind_posts();
}

/** The latitude the daylight table is calculated for. Anchorage unless a site says otherwise. */
function daak_latitude() {
	return (float) apply_filters( 'daak_latitude', 61.2181 );
}

/**
 * Hours of daylight on the 15th of a month, from the latitude.
 *
 * Standard solar-declination geometry: sun centre at the horizon, refraction
 * ignored. Printed rather than remembered, because a daylight table copied from
 * somewhere else is a table nobody can check.
 */
function daak_daylight_hours( $month, $lat = null ) {
	$lat = null === $lat ? daak_latitude() : (float) $lat;
	$n   = (int) gmdate( 'z', gmmktime( 0, 0, 0, (int) $month, 15, (int) gmdate( 'Y' ) ) ) + 1;
	$dec = 23.44 * sin( deg2rad( 360 / 365 * ( 284 + $n ) ) );
	$cos = -tan( deg2rad( $lat ) ) * tan( deg2rad( $dec ) );
	if ( $cos <= -1 ) { return 24.0; }   // midnight sun
	if ( $cos >= 1 ) { return 0.0; }     // polar night
	return 2 * rad2deg( acos( $cos ) ) / 15;
}

/**
 * A date as a person reads it.
 *
 * Everything is stored and compared as Y-m-d because that sorts as a string,
 * but "2026-09-10" on a page about a holiday is a database talking.
 */
function daak_pretty_date( $date, $with_year = false ) {
	$ts = strtotime( (string) $date );
	if ( ! $ts ) { return ''; }
	$same_year = gmdate( 'Y', $ts ) === wp_date( 'Y' );
	return wp_date( $with_year || ! $same_year ? 'D j M Y' : 'D j M', $ts );
}
