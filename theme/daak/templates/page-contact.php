<?php
/**
 * Template Name: About and contact
 *
 * Same dealership, same people, same lot. Every line on this page is read from
 * the profile record.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>

<div class="wrap section split">
	<div>
		<div class="prose"><?php the_content(); ?></div>

		<?php if ( daak_profile( 'founded' ) ) : ?>
			<p class="microcopy">Trading since <?php echo esc_html( daak_profile( 'founded' ) ); ?><?php if ( daak_profile( 'entity' ) ) : ?>, Alaska entity <?php echo esc_html( daak_profile( 'entity' ) ); ?><?php endif; ?>.</p>
		<?php endif; ?>

		<?php daak_reviews_line(); ?>

		<?php if ( daak_profile( 'sales_site' ) ) : ?>
			<div class="panel panel-quiet">
				<h3>The sales side</h3>
				<p>We sell cars as well as renting them, from the same lot. <a href="<?php echo esc_url( daak_profile( 'sales_site' ) ); ?>" rel="noopener"><?php echo esc_html( daak_profile( 'sales_name', 'Our sales site' ) ); ?></a></p>
			</div>
		<?php endif; ?>
	</div>

	<aside class="contactcard">
		<h2>Find us</h2>
		<?php if ( daak_profile( 'street' ) ) : ?>
			<p class="addr"><?php echo esc_html( daak_profile( 'street' ) ); ?><br><?php echo esc_html( trim( daak_profile( 'city' ) . ', ' . daak_profile( 'state' ) . ' ' . daak_profile( 'zip' ) ) ); ?></p>
		<?php endif; ?>
		<ul class="contactlist">
			<?php if ( daak_profile( 'phone1' ) ) : ?><li><a href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>"><?php echo esc_html( daak_profile( 'phone1' ) ); ?></a></li><?php endif; ?>
			<?php if ( daak_profile( 'phone2' ) ) : ?><li><a href="tel:<?php echo esc_attr( daak_profile( 'tel2' ) ); ?>"><?php echo esc_html( daak_profile( 'phone2' ) ); ?></a></li><?php endif; ?>
			<?php if ( daak_profile( 'email' ) ) : ?><li><a href="mailto:<?php echo esc_attr( daak_profile( 'email' ) ); ?>"><?php echo esc_html( daak_profile( 'email' ) ); ?></a></li><?php endif; ?>
			<?php if ( daak_profile( 'hours' ) ) : ?><li class="dim"><?php echo esc_html( daak_profile( 'hours' ) ); ?></li><?php endif; ?>
		</ul>
		<?php if ( daak_policy( 'response_promise' ) ) : ?><p class="microcopy"><?php echo esc_html( daak_policy( 'response_promise' ) ); ?></p><?php endif; ?>
	</aside>
</div>

<?php if ( daak_profile( 'map_url' ) ) : ?>
<div class="wrap section">
	<figure class="mapbox">
		<iframe src="<?php echo esc_url( daak_profile( 'map_url' ) ); ?>" width="100%" height="420" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
	</figure>
</div>
<?php endif; ?>

<div class="wrap section">
	<?php daak_booking_form( 0, array( 'title' => 'Or leave your dates here', 'source' => 'contact' ) ); ?>
</div>

<?php endwhile; get_footer(); ?>
