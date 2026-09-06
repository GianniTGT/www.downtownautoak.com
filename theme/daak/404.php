<?php
/** A wrong turn, with the two links that fix it. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="wrap section prose">
	<h1>That page is not here</h1>
	<p>It may have been a vehicle that has since gone out of the fleet.</p>
	<p class="btnrow">
		<a class="btn btn-accent" href="<?php echo esc_url( daak_fleet_url() ); ?>">See what is available</a>
		<?php if ( daak_profile( 'phone1' ) ) : ?>
			<a class="btn btn-outline" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>"><?php echo esc_html( daak_profile( 'phone1' ) ); ?></a>
		<?php endif; ?>
	</p>
</div>
<?php get_footer(); ?>
