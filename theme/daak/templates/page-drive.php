<?php
/**
 * Template Name: Where you can drive
 *
 * This page is why the site exists. It is a table, not fine print.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<div class="pagehead">
	<div class="wrap">
		<h1><?php the_title(); ?></h1>
		<p class="lede"><?php echo esc_html( daak_policy( 'gravel_headline' ) ); ?></p>
	</div>
</div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<h2>Road by road</h2>
	<?php daak_road_table(); ?>

	<?php if ( daak_profile( 'map_url' ) ) : ?>
		<figure class="mapbox">
			<iframe src="<?php echo esc_url( daak_profile( 'map_url' ) ); ?>" width="100%" height="380" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
		</figure>
	<?php endif; ?>

	<h2>Getting out of town</h2>
	<div class="notecards">
		<article>
			<h3>The Whittier tunnel</h3>
			<p>One lane, shared with the railway, tolled and timed. Check the schedule before you drive out there — you can wait half an hour for your direction.</p>
		</article>
		<?php if ( daak_policy( 'ferry' ) ) : ?>
		<article>
			<h3>Alaska Marine Highway ferries</h3>
			<p><?php echo esc_html( daak_policy( 'ferry' ) ); ?></p>
		</article>
		<?php endif; ?>
		<?php if ( daak_policy( 'canada' ) ) : ?>
		<article>
			<h3>Driving into Canada</h3>
			<p><?php echo esc_html( daak_policy( 'canada' ) ); ?></p>
		</article>
		<?php endif; ?>
		<?php if ( daak_policy( 'one_way' ) ) : ?>
		<article>
			<h3>One-way rentals</h3>
			<p><?php echo esc_html( daak_policy( 'one_way' ) ); ?></p>
		</article>
		<?php endif; ?>
	</div>

	<?php if ( daak_policy( 'moose_note' ) ) : ?>
		<h2>Moose</h2>
		<p class="prose"><?php echo esc_html( daak_policy( 'moose_note' ) ); ?></p>
	<?php endif; ?>

	<div class="ctastrip">
		<p>Tell us where you are going and we will tell you which vehicle is cleared for it.</p>
		<a class="btn btn-accent" href="<?php echo esc_url( daak_fleet_url() ); ?>">See the fleet</a>
	</div>
</div>

<?php endwhile; get_footer(); ?>
