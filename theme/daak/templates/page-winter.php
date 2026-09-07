<?php
/**
 * Template Name: Winter driving in Alaska
 *
 * This is what people search for in October, and it is the page no national
 * rental site writes. Two rules hold it together: the studded-tire window is
 * printed only once somebody has verified it against the current statute, and
 * the daylight table is calculated from the latitude rather than remembered.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
$studs = daak_policy( 'studs_window' );
?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<div class="notecards">
		<article>
			<h3>Studded tires</h3>
			<?php if ( $studs ) : ?>
				<p><?php echo esc_html( $studs ); ?></p>
				<?php if ( daak_policy( 'studs_verified_on' ) ) : ?>
					<p class="microcopy">Checked against the statute on <?php echo esc_html( daak_policy( 'studs_verified_on' ) ); ?>.</p>
				<?php endif; ?>
			<?php else : ?>
				<p>Studded tires are seasonal in Alaska and the permitted window differs by latitude — Anchorage sits north of 60°N, which is the longer one. Telephone us for this winter's dates rather than trusting a date printed from memory.</p>
			<?php endif; ?>
			<p>Which vehicles carry studs or winter tires is on each vehicle's page.</p>
		</article>

		<article>
			<h3>The block heater and the plug-in</h3>
			<?php if ( daak_policy( 'block_heater' ) ) : ?>
				<p><?php echo esc_html( daak_policy( 'block_heater' ) ); ?></p>
			<?php else : ?>
				<p>Most vehicles here have an engine block heater with a cord behind the grille. On a cold night you plug it into an ordinary outlet — hotels, houses and many car parks in Anchorage have them — and the engine starts in the morning instead of turning over slowly. We show you where the cord is when you collect the vehicle.</p>
			<?php endif; ?>
		</article>

		<?php if ( daak_policy( 'moose_note' ) ) : ?>
		<article>
			<h3>Moose</h3>
			<p><?php echo esc_html( daak_policy( 'moose_note' ) ); ?></p>
		</article>
		<?php else : ?>
		<article>
			<h3>Moose</h3>
			<p>The most common serious accident here is not ice, it is a moose. They move at dusk and dawn, they stand taller than your headlights, and they step out of the trees without warning. Slow down at first and last light, scan the verges rather than the road, and if one is in the carriageway, stop and wait — they cross when they are ready.</p>
		</article>
		<?php endif; ?>

		<article>
			<h3>What to carry</h3>
			<p>A charged telephone and a charger, warm layers and boots you can walk in, water, a shovel and a torch. There is no mobile signal on most of the gravel, which is why we rent satellite messengers.</p>
		</article>
	</div>

	<h2>Daylight in Anchorage</h2>
	<p>How much daylight there is decides how far you can sensibly drive in a day. These are calculated for this latitude, not copied from anywhere.</p>
	<div class="tablewrap">
		<table class="rates daylight">
			<thead><tr><th scope="col">Month</th><th scope="col">Daylight, mid-month</th><th scope="col"></th></tr></thead>
			<tbody>
			<?php for ( $m = 1; $m <= 12; $m++ ) :
				$hours = daak_daylight_hours( $m );
				$pct   = max( 2, min( 100, $hours / 24 * 100 ) );
				?>
				<tr>
					<th scope="row"><?php echo esc_html( wp_date( 'F', mktime( 0, 0, 0, $m, 15 ) ) ); ?></th>
					<td><?php echo esc_html( sprintf( '%dh %02dm', floor( $hours ), round( ( $hours - floor( $hours ) ) * 60 ) ) ); ?></td>
					<td class="barcell"><span class="bar" style="width:<?php echo esc_attr( (string) round( $pct ) ); ?>%"></span></td>
				</tr>
			<?php endfor; ?>
			</tbody>
		</table>
	</div>
	<p class="microcopy">Calculated for <?php echo esc_html( number_format( daak_latitude(), 4 ) ); ?>°N on the 15th of each month, sun centre at the horizon and refraction ignored. Real twilight here is generous: usable light lasts well past sunset.</p>

	<div class="ctastrip">
		<p>Winter-equipped vehicles, all winter. Not a summer fleet with the tires swapped in November.</p>
		<a class="btn btn-accent" href="<?php echo esc_url( daak_fleet_url() ); ?>">See the fleet</a>
	</div>
</div>

<?php endwhile; get_footer(); ?>
