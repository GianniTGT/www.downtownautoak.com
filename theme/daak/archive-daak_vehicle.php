<?php
/**
 * The fleet.
 *
 * Filters are built from the vehicles actually in the fleet — a filter that
 * cannot narrow anything is not drawn. With dates in the box, only what is free
 * is listed, and the count says so plainly.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$from   = isset( $_GET['from'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : '';
$to     = isset( $_GET['to'] ) ? daak_date( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) : '';
$class  = isset( $_GET['class'] ) ? sanitize_key( wp_unslash( $_GET['class'] ) ) : '';
$drive  = isset( $_GET['drive'] ) ? sanitize_key( wp_unslash( $_GET['drive'] ) ) : '';
$seats  = isset( $_GET['seats'] ) ? absint( $_GET['seats'] ) : 0;
$gravel = isset( $_GET['gravel'] ) && '' !== $_GET['gravel'] ? absint( $_GET['gravel'] ) : '';

$vehicles = daak_search_vehicles( compact( 'from', 'to', 'class', 'drive', 'seats', 'gravel' ) );
$filters  = daak_fleet_filters();
$total    = count( daak_all_vehicles() );
?>

<div class="pagehead">
	<div class="wrap">
		<h1>The fleet</h1>
		<p class="lede">Every vehicle here is serviced in our own workshop. The gravel line on each card is the one that matters.</p>
		<?php daak_search_form( array( 'compact' => true, 'action' => daak_fleet_url() ) ); ?>
	</div>
</div>

<div class="wrap section">
	<?php if ( $filters ) : ?>
	<form class="filters" method="get" action="<?php echo esc_url( daak_fleet_url() ); ?>">
		<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>">
		<input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>">
		<?php foreach ( $filters as $key => $f ) : ?>
			<?php $name = 'dv_class' === $key ? 'class' : ( 'dv_drive' === $key ? 'drive' : $key ); ?>
			<label class="filter">
				<span><?php echo esc_html( $f['label'] ); ?></span>
				<select name="<?php echo esc_attr( $name ); ?>">
					<option value="">Any</option>
					<?php
					$current = 'class' === $name ? $class : ( 'drive' === $name ? $drive : ( 'seats' === $name ? (string) $seats : (string) $gravel ) );
					foreach ( $f['options'] as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( (string) $current, (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endforeach; ?>
		<button class="btn btn-outline" type="submit">Apply</button>
		<?php if ( $class || $drive || $seats || '' !== $gravel ) : ?>
			<a class="link" href="<?php echo esc_url( daak_fleet_url( array_filter( array( 'from' => $from, 'to' => $to ) ) ) ); ?>">Clear</a>
		<?php endif; ?>
	</form>
	<?php endif; ?>

	<p class="resultcount">
		<?php if ( $from && $to ) : ?>
			<strong><?php echo count( $vehicles ); ?></strong> of <?php echo (int) $total; ?> free <?php echo esc_html( daak_pretty_date( $from ) ); ?> &ndash; <?php echo esc_html( daak_pretty_date( $to ) ); ?>
			<?php if ( count( $vehicles ) < $total ) : ?><span class="dim">— the rest are already out on those dates</span><?php endif; ?>
		<?php else : ?>
			<strong><?php echo count( $vehicles ); ?></strong> vehicles. Add your dates to see what is free.
		<?php endif; ?>
	</p>

	<?php if ( $vehicles ) : ?>
		<div class="cards">
			<?php foreach ( $vehicles as $v ) { daak_vehicle_card( $v->ID, array( 'from' => $from, 'to' => $to ) ); } ?>
		</div>
		<p class="taxnote"><?php echo esc_html( daak_tax_line() ); ?></p>
	<?php else : ?>
		<div class="empty">
			<h2>Nothing free on those dates</h2>
			<p>It is a small fleet, which is why the site tells you the truth instead of taking the booking anyway. Telephone us and we will tell you what is coming back and when.</p>
			<?php if ( daak_profile( 'phone1' ) ) : ?>
				<a class="btn btn-accent" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>"><?php echo esc_html( daak_profile( 'phone1' ) ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
