<?php
/**
 * Display station header
 */
defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	echo '<div class="container">'.get_the_password_form().'</div>';
	return;
}

do_action( 'play_before_single_station');

play_get_template( 'single-station/content.php' );

do_action( 'play_after_single_station');
