<?php
/**
 * One vehicle: gallery, specification, what is included, where it may go, and
 * the booking form with the dates already filled in.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$from    = isset( $_GET['from'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : '';
	$to      = isset( $_GET['to'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) : '';
	$gravel  = (int) get_post_meta( $id, 'dv_gravel', true );
	$rate    = (float) get_post_meta( $id, 'dv_rate_day', true );
	$gallery = (array) json_decode( (string) get_post_meta( $id, 'dv_gallery', true ), true );
	$winter  = (array) json_decode( (string) get_post_meta( $id, 'dv_winter', true ), true );
	$free    = ( $from && $to ) ? daak_vehicle_is_free( $id, $from, $to ) : null;

	$specs = array(
		'Class'        => daak_vehicle_vocab( 'dv_class' )[ get_post_meta( $id, 'dv_class', true ) ] ?? '',
		'Drive'        => daak_vehicle_vocab( 'dv_drive' )[ get_post_meta( $id, 'dv_drive', true ) ] ?? '',
		'Transmission' => daak_vehicle_vocab( 'dv_transmission' )[ get_post_meta( $id, 'dv_transmission', true ) ] ?? '',
		'Fuel'         => daak_vehicle_vocab( 'dv_fuel' )[ get_post_meta( $id, 'dv_fuel', true ) ] ?? '',
		'Seats'        => get_post_meta( $id, 'dv_seats', true ),
		'Doors'        => get_post_meta( $id, 'dv_doors', true ),
		'Mileage'      => get_post_meta( $id, 'dv_mileage_policy', true ),
		'Minimum'      => get_post_meta( $id, 'dv_min_days', true ) ? get_post_meta( $id, 'dv_min_days', true ) . ' days' : '',
	);
	?>

	<div class="vhead">
		<div class="wrap">
			<p class="crumbs"><a href="<?php echo esc_url( daak_fleet_url() ); ?>">Fleet</a> <span>/</span> <?php echo esc_html( daak_vehicle_title( $id ) ); ?></p>
			<h1><?php echo esc_html( daak_vehicle_title( $id ) ); ?></h1>
			<p class="vtags">
				<?php if ( $gravel ) : ?><span class="tag tag-gravel">Gravel-road approved</span><?php endif; ?>
				<?php foreach ( $winter as $w ) : ?>
					<span class="tag"><?php echo esc_html( daak_vehicle_vocab( 'dv_winter' )[ $w ] ?? $w ); ?></span>
				<?php endforeach; ?>
			</p>
		</div>
	</div>

	<div class="wrap section vgrid">
		<div class="vmain">
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="vshot"><?php the_post_thumbnail( 'daak_hero', array( 'alt' => esc_attr( daak_vehicle_title( $id ) ) ) ); ?></figure>
			<?php endif; ?>

			<?php if ( $gallery ) : ?>
				<div class="vgallery">
					<?php foreach ( $gallery as $att ) : ?>
						<?php $img = wp_get_attachment_image( (int) $att, 'daak_card', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
						<?php if ( $img ) { echo $img; } ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="specs">
				<?php foreach ( $specs as $label => $value ) : ?>
					<?php if ( '' === (string) $value ) { continue; } ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
				<?php endforeach; ?>
			</div>

			<?php if ( trim( (string) get_the_content() ) ) : ?>
				<div class="prose"><?php the_content(); ?></div>
			<?php endif; ?>

			<h2>Where this one may go</h2>
			<?php daak_road_table( true ); ?>
			<?php if ( ! $gravel ) : ?>
				<p class="warn">This vehicle is not cleared for unpaved roads. If your trip includes one, ask us for a vehicle that is.</p>
			<?php endif; ?>

			<?php if ( daak_policy( 'rto_credit' ) ) : ?>
				<div class="panel panel-quiet">
					<h3>You can buy this one</h3>
					<p><?php echo esc_html( daak_policy( 'rto_credit' ) ); ?> <a class="link" href="<?php echo esc_url( daak_page_url( 'rent-to-own' ) ); ?>">How rent-to-own works &rarr;</a></p>
				</div>
			<?php endif; ?>
		</div>

		<aside class="vaside">
			<div class="ratebox">
				<?php if ( $rate ) : ?>
					<p class="price"><?php echo esc_html( daak_rate( $rate ) ); ?><small>/day</small></p>
				<?php else : ?>
					<p class="price price-ask">Rate on request</p>
				<?php endif; ?>
				<?php if ( null !== $free ) : ?>
					<p class="availability <?php echo $free ? 'is-free' : 'is-busy'; ?>">
						<?php echo $free ? 'Free ' . esc_html( daak_pretty_date( $from ) ) . ' &ndash; ' . esc_html( daak_pretty_date( $to ) ) : 'Already out on those dates'; ?>
					</p>
				<?php endif; ?>
				<p class="taxnote"><?php echo esc_html( daak_tax_line() ); ?></p>
				<?php if ( daak_profile( 'phone1' ) ) : ?>
					<a class="btn btn-brand btn-block" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-phone"/></svg>
						<?php echo esc_html( daak_profile( 'phone1' ) ); ?>
					</a>
				<?php endif; ?>
				<a class="btn btn-accent btn-block" href="#request">Request these dates</a>
			</div>
		</aside>
	</div>

	<div class="wrap section">
		<?php daak_booking_form( $id, array( 'title' => 'Request this vehicle', 'source' => 'vehicle' ) ); ?>
	</div>

	<?php
endwhile;
get_footer();
