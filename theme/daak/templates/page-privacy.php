<?php
/**
 * Template Name: Privacy
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<div class="pagehead"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>

<div class="wrap section">
	<div class="prose"><?php the_content(); ?></div>

	<h2>What a rental request stores</h2>
	<ul class="ticks">
		<li>Your name</li>
		<li>Your telephone number</li>
		<li>Your email address, if you give one</li>
		<li>The dates you asked for</li>
		<li>The vehicle and any add-ons you chose</li>
		<li>Anything you typed in the message box</li>
	</ul>

	<h2>What it never stores</h2>
	<ul class="crosses">
		<li>A Social Security number</li>
		<li>A date of birth</li>
		<li>A driver's licence number</li>
		<li>Bank or card details</li>
	</ul>
	<p>Those are checked in person at the counter, where a licence can actually be looked at. There is no field for them on this site, and the software refuses to store one if a future change ever tries to add it.</p>

	<h2>Where it goes</h2>
	<p>Your request is saved on this website and sent by email to <?php echo daak_profile( 'email' ) ? '<a href="mailto:' . esc_attr( daak_profile( 'email' ) ) . '">' . esc_html( daak_profile( 'email' ) ) . '</a>' : 'the office'; ?>. It is not sold, and it is not passed to anybody who is not answering your request.</p>
	<p>Ask us to delete it and we will.</p>
</div>

<?php endwhile; get_footer(); ?>
