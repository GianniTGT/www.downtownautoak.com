<?php
/**
 * Template Name: Rent to own
 *
 * The offer no rental company can make and no other dealer in town is making.
 * With honest arithmetic: the calculator shows what the rental actually costs
 * as well as what it takes off the price.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
$credit = daak_policy( 'rto_credit' );
?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1><p class="lede">A test drive that lasts a week.</p></div></div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<div class="split">
		<div>
			<h2>How it works</h2>
			<ol class="steps">
				<li><b>Rent it.</b> Pick the vehicle you are curious about and rent it like any other.</li>
				<li><b>Drive it properly.</b> A week on real roads tells you what twenty minutes around the block never will — the seat, the noise, the heater, the fuel it actually drinks.</li>
				<li><b>Decide.</b> If you buy it, part of what you paid to rent comes off the purchase price.<?php if ( $credit ) : ?> <?php echo esc_html( $credit ); ?><?php endif; ?></li>
			</ol>
			<?php if ( daak_profile( 'sales_site' ) ) : ?>
				<p><a class="btn btn-outline" href="<?php echo esc_url( daak_profile( 'sales_site' ) ); ?>" rel="noopener">See the cars for sale &rarr;</a></p>
			<?php endif; ?>
		</div>

		<div class="panel">
			<h3>The arithmetic</h3>
			<p class="microcopy">Type your own numbers. Nothing here is sent anywhere.</p>
			<div class="rto" data-rto>
				<p class="field"><label for="rto-rate">Daily rate</label><input type="number" id="rto-rate" value="" min="0" step="1" placeholder="e.g. 120"></p>
				<p class="field"><label for="rto-days">Days you rent</label><input type="number" id="rto-days" value="7" min="1" step="1"></p>
				<p class="field"><label for="rto-credit">Percentage that credits</label><input type="number" id="rto-credit" value="50" min="0" max="100" step="5"></p>
				<p class="field"><label for="rto-price">Price of the car</label><input type="number" id="rto-price" value="" min="0" step="100" placeholder="e.g. 14500"></p>
				<dl class="rtoout">
					<div><dt>The rental costs</dt><dd data-rto-rent>—</dd></div>
					<div><dt>Credited if you buy</dt><dd data-rto-credit>—</dd></div>
					<div><dt>You pay for the car</dt><dd data-rto-final>—</dd></div>
					<div><dt>Net cost of trying it</dt><dd data-rto-net>—</dd></div>
				</dl>
			</div>
			<p class="microcopy">Before tax, and before anything agreed in writing. <?php echo esc_html( daak_tax_line() ); ?></p>
		</div>
	</div>

	<div class="ctastrip">
		<p>Tell us which one you are thinking about.</p>
		<a class="btn btn-accent" href="<?php echo esc_url( daak_fleet_url() ); ?>">Pick a vehicle</a>
	</div>
</div>

<?php endwhile; get_footer(); ?>
