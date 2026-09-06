<?php
/**
 * Template Name: Rental agreement and policies
 *
 * The terms in the same words the agreement uses. Linked from every booking step.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<h2>Where our vehicles may be driven</h2>
	<?php daak_road_table(); ?>

	<h2>The terms</h2>
	<dl class="policies">
		<?php
		daak_policy_row( 'gravel_sentence', 'Unpaved roads' );
		daak_policy_row( 'mileage', 'Mileage' );
		daak_policy_row( 'fuel', 'Fuel' );
		daak_policy_row( 'deposit', 'Deposit' );
		daak_policy_row( 'min_age', 'Minimum age' );
		daak_policy_row( 'young_driver', 'Drivers under 25' );
		daak_policy_row( 'second_driver', 'Additional drivers' );
		daak_policy_row( 'pets', 'Pets' );
		daak_policy_row( 'smoking', 'Smoking' );
		daak_policy_row( 'one_way', 'One-way rentals' );
		daak_policy_row( 'ferry', 'Ferries' );
		daak_policy_row( 'canada', 'Driving into Canada' );
		daak_policy_row( 'insurance_note', 'Insurance' );
		daak_policy_row( 'recall_note', 'Safety recalls' );
		?>
	</dl>

	<div class="panel panel-quiet">
		<h3>What this website collects</h3>
		<p>A name, a telephone number, an email address if you give one, your dates, a vehicle and any add-ons. That is the whole list.</p>
		<p><strong>It never collects a Social Security number, a date of birth, a driver's licence number, or bank or card details.</strong> Your licence is examined in person, at the counter, where it can be looked at.</p>
		<?php if ( get_privacy_policy_url() ) : ?><p><a class="link" href="<?php echo esc_url( get_privacy_policy_url() ); ?>">The full privacy page &rarr;</a></p><?php endif; ?>
	</div>

	<p class="microcopy">The rental agreement you sign at the counter is the binding document. If anything here and the agreement ever disagree, tell us — that is a mistake on our side, and we will fix it.</p>
</div>

<?php endwhile; get_footer(); ?>
