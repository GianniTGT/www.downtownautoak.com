<?php
/**
 * The home page.
 *
 * The first sentence is the whole business: the national chains forbid unpaved
 * roads, and the roads to everything a visitor came to Alaska to see are unpaved.
 * Everything below that sentence supports it.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$from = isset( $_GET['from'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : '';
$to   = isset( $_GET['to'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) : '';
$fleet = daak_search_vehicles( array( 'from' => $from, 'to' => $to ) );
$hero  = daak_hero_style();
?>

<section class="hero<?php echo $hero ? ' has-photo' : ''; ?>"<?php echo $hero; ?>>
	<div class="wrap">
		<p class="eyebrow"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-road"/></svg> Anchorage, Alaska</p>
		<h1><?php echo esc_html( daak_policy( 'gravel_headline' ) ); ?></h1>
		<p class="lede">The national chains forbid unpaved roads &mdash; which rules out the Dalton, the Denali Highway, the McCarthy Road and most of the reason you came. We do not.</p>

		<div class="herosearch">
			<?php daak_search_form(); ?>
			<p class="microcopy">Pick your dates and we show only what is actually free.</p>
		</div>

		<ul class="herofacts">
			<li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg> Gravel-road approved</li>
			<li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg> Unlimited miles</li>
			<li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg> Winter-equipped</li>
		</ul>
	</div>
</section>

<?php if ( $fleet ) : ?>
<section class="section">
	<div class="wrap">
		<header class="sechead">
			<h2><?php echo $from && $to ? 'Free on your dates' : 'The fleet'; ?></h2>
			<a class="link" href="<?php echo esc_url( daak_fleet_url( array_filter( array( 'from' => $from, 'to' => $to ) ) ) ); ?>">See all <?php echo count( $fleet ); ?> &rarr;</a>
		</header>
		<div class="cards">
			<?php foreach ( array_slice( $fleet, 0, 4 ) as $v ) { daak_vehicle_card( $v->ID, array( 'from' => $from, 'to' => $to ) ); } ?>
		</div>
		<p class="taxnote"><?php echo esc_html( daak_tax_line() ); ?></p>
	</div>
</section>
<?php endif; ?>

<section class="section section-tint">
	<div class="wrap">
		<h2 class="center">Three reasons this is not a chain</h2>
		<div class="reasons">
			<article>
				<span class="disc"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-road"/></svg></span>
				<h3>The gravel roads are the point</h3>
				<p>The Dalton, the Denali Highway, the McCarthy Road, the Steese. Where our vehicles may go is written down, road by road, and the rental agreement uses the same words.</p>
				<a class="link" href="<?php echo esc_url( daak_page_url( 'where-you-can-drive' ) ); ?>">Where you can drive &rarr;</a>
			</article>
			<article>
				<span class="disc"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-snow"/></svg></span>
				<h3>We repair what we rent</h3>
				<p>This is a dealership with its own workshop. The car you drive away was serviced by the people handing you the keys, and checked against open safety recalls before it went out.</p>
				<a class="link" href="<?php echo esc_url( daak_page_url( 'about' ) ); ?>">About us &rarr;</a>
			</article>
			<article>
				<span class="disc"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-key"/></svg></span>
				<h3>Rent it, then buy it</h3>
				<p>We sell cars too. Rent one, and part of what you paid comes off the price if you buy it. A test drive that lasts a week.</p>
				<a class="link" href="<?php echo esc_url( daak_page_url( 'rent-to-own' ) ); ?>">How that works &rarr;</a>
			</article>
		</div>
	</div>
</section>

<section class="section">
	<div class="wrap split">
		<div>
			<h2>Where you can drive</h2>
			<p>Ask any national counter about the Denali Highway and the answer is no. Here is ours, in full.</p>
			<?php daak_road_table( true ); ?>
			<p><a class="btn btn-outline" href="<?php echo esc_url( daak_page_url( 'where-you-can-drive' ) ); ?>">The whole table, with the map</a></p>
		</div>
		<div class="panel">
			<h3>Car in the workshop?</h3>
			<p>We keep vehicles for people whose own car is being repaired &mdash; ours or anybody's. Insurance replacement is a telephone call, all year round, not just in summer.</p>
			<?php if ( daak_profile( 'phone1' ) ) : ?>
				<a class="btn btn-brand" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-phone"/></svg>
					<?php echo esc_html( daak_profile( 'phone1' ) ); ?>
				</a>
			<?php endif; ?>
			<?php if ( daak_policy( 'response_promise' ) ) : ?><p class="microcopy"><?php echo esc_html( daak_policy( 'response_promise' ) ); ?></p><?php endif; ?>
		</div>
	</div>
</section>

<?php if ( daak_profile( 'rating' ) ) : ?>
<section class="section section-tint">
	<div class="wrap center">
		<h2>What our customers say</h2>
		<?php daak_reviews_line(); ?>
		<p class="microcopy">We link to the reviews rather than retyping them. They are our customers' words, and they should stay that way.</p>
	</div>
</section>
<?php endif; ?>

<?php daak_the_content_if_any(); ?>

<section class="section">
	<div class="wrap">
		<?php daak_booking_form( 0, array( 'title' => 'Tell us your dates', 'source' => 'home' ) ); ?>
	</div>
</section>

<?php get_footer(); ?>
