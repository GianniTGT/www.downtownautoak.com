<?php
/** A plain page: the hero band, the editor's content, and nothing invented. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="pagehead">
		<div class="wrap"><h1><?php the_title(); ?></h1></div>
	</div>
	<div class="wrap section">
		<div class="prose"><?php the_content(); ?></div>
	</div>
	<?php
endwhile;
get_footer();
