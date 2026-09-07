<?php
/**
 * Template Name: Rates and what is included
 *
 * Daily, weekly, monthly, straight from the fleet — a rate typed onto a page is
 * a rate that disagrees with the vehicle by Christmas.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
$vehicles = daak_all_vehicles();
?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<?php if ( $vehicles ) : ?>
	<h2>Rates</h2>
	<div class="tablewrap">
		<table class="rates">
			<thead><tr><th scope="col">Vehicle</th><th scope="col">Day</th><th scope="col">Week</th><th scope="col">Month</th><th scope="col">Gravel</th></tr></thead>
			<tbody>
			<?php foreach ( $vehicles as $v ) :
				$day   = (float) get_post_meta( $v->ID, 'dv_rate_day', true );
				$week  = (float) get_post_meta( $v->ID, 'dv_rate_week', true );
				$month = (float) get_post_meta( $v->ID, 'dv_rate_month', true );
				?>
				<tr>
					<th scope="row"><a href="<?php echo esc_url( get_permalink( $v ) ); ?>"><?php echo esc_html( daak_vehicle_title( $v->ID ) ); ?></a></th>
					<td><?php echo $day ? esc_html( daak_rate( $day ) ) : 'Ask us'; ?></td>
					<td><?php echo $week ? esc_html( daak_rate( $week ) ) : ( $day ? '<span class="dim">' . esc_html( daak_rate( $day * 7 ) ) . '</span>' : 'Ask us' ); ?></td>
					<td><?php echo $month ? esc_html( daak_rate( $month ) ) : ( $day ? '<span class="dim">' . esc_html( daak_rate( $day * 30 ) ) . '</span>' : 'Ask us' ); ?></td>
					<td><?php echo get_post_meta( $v->ID, 'dv_gravel', true ) ? '<span class="verdict v-allowed">Yes</span>' : '<span class="verdict v-forbidden">No</span>'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="taxnote"><?php echo esc_html( daak_tax_line() ); ?></p>
	<p class="microcopy">Greyed weekly and monthly figures are seven and thirty days at the daily rate — ask us, they are usually better than that.</p>
	<?php endif; ?>

	<?php $seasons = daak_seasons(); if ( $seasons ) : ?>
	<h2>Seasons</h2>
	<div class="tablewrap">
		<table class="rates">
			<thead><tr><th scope="col">Season</th><th scope="col">From</th><th scope="col">To</th><th scope="col">Rate</th></tr></thead>
			<tbody>
			<?php foreach ( $seasons as $s ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $s['label'] ); ?></th>
					<td><?php echo esc_html( $s['from'] ); ?></td>
					<td><?php echo esc_html( $s['to'] ); ?></td>
					<td><?php echo 1.0 === (float) $s['multiplier'] ? 'Base rate' : esc_html( rtrim( rtrim( number_format( (float) $s['multiplier'], 2 ), '0' ), '.' ) . ' × base rate' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<?php $extras = daak_extras(); if ( $extras ) : ?>
	<h2>Add-ons</h2>
	<div class="tablewrap">
		<table class="rates">
			<thead><tr><th scope="col">Add-on</th><th scope="col">Price</th><th scope="col"></th></tr></thead>
			<tbody>
			<?php foreach ( $extras as $e ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $e['label'] ); ?></th>
					<td><?php echo '' === $e['price'] ? 'Ask us' : esc_html( daak_rate( $e['price'] ) . ( 'day' === $e['unit'] ? ' / day' : ' / rental' ) ); ?></td>
					<td class="dim"><?php echo esc_html( $e['note'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<h2>What is included, and what is not</h2>
	<dl class="policies">
		<?php
		daak_policy_row( 'mileage', 'Mileage' );
		daak_policy_row( 'fuel', 'Fuel' );
		daak_policy_row( 'deposit', 'Deposit' );
		daak_policy_row( 'min_age', 'Minimum age' );
		daak_policy_row( 'young_driver', 'Drivers under 25' );
		daak_policy_row( 'second_driver', 'Additional drivers' );
		daak_policy_row( 'pets', 'Pets' );
		daak_policy_row( 'smoking', 'Smoking' );
		daak_policy_row( 'airport', 'Airport pick-up' );
		daak_policy_row( 'one_way', 'One-way' );
		?>
	</dl>

	<div class="panel panel-quiet">
		<h3>Protection at the counter, not online</h3>
		<p>We quote the rate and handle damage protection in person. Selling a waiver online is a regulated product in several states, and until an attorney has answered that question for Alaska we are not going to pretend otherwise.</p>
	</div>
</div>

<?php endwhile; get_footer(); ?>
