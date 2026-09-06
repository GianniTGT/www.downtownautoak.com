<?php
/** Footer: contact block, the vendor credit, the legal line. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
</main>

<?php if ( ! is_singular( 'daak_vehicle' ) && daak_profile( 'phone1' ) ) : ?>
<section class="band">
	<div class="wrap">
		<div>
			<h2>Not sure which one fits your trip?</h2>
			<p>Tell us where you are going. We have driven all of it.</p>
		</div>
		<a class="btn btn-light" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-phone"/></svg>
			<?php echo esc_html( daak_profile( 'phone1' ) ); ?>
		</a>
	</div>
</section>
<?php endif; ?>

<footer class="site-footer">
	<div class="wrap">
		<div class="foot-grid">
			<div>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php daak_logo( 'brand-badge on-dark' ); ?>
					<?php if ( daak_profile( 'name' ) ) : ?><span class="brand-words"><b><?php echo esc_html( daak_profile( 'name' ) ); ?></b></span><?php endif; ?>
				</a>
				<p class="foot-line"><?php echo esc_html( daak_policy( 'gravel_headline' ) ); ?></p>
				<?php daak_reviews_line(); ?>
			</div>

			<div>
				<h4>The site</h4>
				<ul>
					<li><a href="<?php echo esc_url( daak_fleet_url() ); ?>">Fleet</a></li>
					<li><a href="<?php echo esc_url( daak_page_url( 'where-you-can-drive' ) ); ?>">Where you can drive</a></li>
					<li><a href="<?php echo esc_url( daak_page_url( 'rates' ) ); ?>">Rates</a></li>
					<li><a href="<?php echo esc_url( daak_page_url( 'winter-driving' ) ); ?>">Winter driving</a></li>
					<li><a href="<?php echo esc_url( daak_page_url( 'rent-to-own' ) ); ?>">Rent to own</a></li>
				</ul>
			</div>

			<?php if ( daak_profile( 'street' ) || daak_profile( 'phone1' ) ) : ?>
			<div>
				<h4>Find us</h4>
				<ul>
					<?php if ( daak_profile( 'street' ) ) : ?>
						<li><?php echo esc_html( daak_profile( 'street' ) ); ?><br><?php echo esc_html( trim( daak_profile( 'city' ) . ', ' . daak_profile( 'state' ) . ' ' . daak_profile( 'zip' ) ) ); ?></li>
					<?php endif; ?>
					<?php if ( daak_profile( 'phone1' ) ) : ?><li><a href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>"><?php echo esc_html( daak_profile( 'phone1' ) ); ?></a></li><?php endif; ?>
					<?php if ( daak_profile( 'phone2' ) ) : ?><li><a href="tel:<?php echo esc_attr( daak_profile( 'tel2' ) ); ?>"><?php echo esc_html( daak_profile( 'phone2' ) ); ?></a></li><?php endif; ?>
					<?php if ( daak_profile( 'email' ) ) : ?><li><a href="mailto:<?php echo esc_attr( daak_profile( 'email' ) ); ?>"><?php echo esc_html( daak_profile( 'email' ) ); ?></a></li><?php endif; ?>
					<?php if ( daak_profile( 'hours' ) ) : ?><li class="dim"><?php echo esc_html( daak_profile( 'hours' ) ); ?></li><?php endif; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if ( daak_profile( 'sales_site' ) ) : ?>
			<div>
				<h4>The other half</h4>
				<ul>
					<li><a href="<?php echo esc_url( daak_profile( 'sales_site' ) ); ?>" rel="noopener"><?php echo esc_html( daak_profile( 'sales_name', 'Our sales lot' ) ); ?></a></li>
					<li class="dim">Same lot, same telephone, same mechanic.</li>
				</ul>
			</div>
			<?php endif; ?>
		</div>

		<div class="foot-bottom">
			<span>
				<?php if ( daak_profile( 'founded' ) ) : ?>&copy; <?php echo esc_html( daak_profile( 'founded' ) ); ?>&ndash;<?php endif; ?><?php echo esc_html( wp_date( 'Y' ) ); ?>
				<?php echo esc_html( daak_profile( 'legal', daak_profile( 'name' ) ) ); ?>
			</span>
			<span><a href="<?php echo esc_url( daak_page_url( 'policies' ) ); ?>">Rental agreement &amp; policies</a></span>
			<?php if ( get_privacy_policy_url() ) : ?><span><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy</a></span><?php endif; ?>
			<span class="foot-credit"><?php daak_vendor_credit(); ?></span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
