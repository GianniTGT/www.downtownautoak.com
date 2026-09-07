<?php
/** The fallback loop. Rarely reached: this site is pages, a fleet and requests. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="wrap section">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="prose">
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<h1>Nothing here</h1>
		<p><a class="btn btn-accent" href="<?php echo esc_url( daak_fleet_url() ); ?>">See the fleet</a></p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
