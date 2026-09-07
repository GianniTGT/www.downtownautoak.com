<?php
/** Header: badge, wordmark, menu, one telephone number. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>

<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>
	<symbol id="i-phone" viewBox="0 0 24 24"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.2.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1l-2.3 2.2z"/></symbol>
	<symbol id="i-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></symbol>
	<symbol id="i-road" viewBox="0 0 24 24"><path d="M5 21 8.5 3h7L19 21" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 5.5v3M12 11v3M12 16.5v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
	<symbol id="i-snow" viewBox="0 0 24 24"><path d="M12 2.5v19M4 7.2l16 9.6M20 7.2 4 16.8M12 6.6 9.4 5M12 6.6 14.6 5M12 17.4 9.4 19M12 17.4l2.6 1.6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></symbol>
	<symbol id="i-key" viewBox="0 0 24 24"><path d="M14 7a4 4 0 1 1-3.5 5.9L4 19l-2-2 1.5-1.5L5 17l1.5-1.5L5 14l4.1-4.1A4 4 0 0 1 14 7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
</defs></svg>

<header class="site">
	<div class="wrap nav">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php daak_logo(); ?>
			<?php if ( daak_profile( 'name' ) ) : ?>
				<span class="brand-words">
					<b><?php echo esc_html( daak_profile( 'name' ) ); ?></b>
					<em>Vehicle rental &middot; Anchorage</em>
				</span>
			<?php endif; ?>
		</a>

		<button class="hamb" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
		</button>

		<nav class="menu" id="menu" aria-label="Primary">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1, 'items_wrap' => '<ul>%3$s</ul>' ) );
			} else {
				daak_fallback_menu();
			}
			?>
			<?php if ( daak_profile( 'phone1' ) ) : ?>
			<a class="btn btn-brand" href="tel:<?php echo esc_attr( daak_profile( 'tel1' ) ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-phone"/></svg>
				<?php echo esc_html( daak_profile( 'phone1' ) ); ?>
			</a>
			<?php endif; ?>
		</nav>
	</div>
</header>
<main id="main">
