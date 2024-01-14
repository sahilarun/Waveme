<?php
/**
 * Display station header
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="entry-header-container header-station">

	<?php do_action( 'play_before_single_header'); ?>

	<header class="entry-header">
		<?php do_action( 'play_single_header_start'); ?>

		<?php do_action( 'play_before_single_title'); ?>
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<?php do_action( 'play_after_single_title'); ?>

		<?php do_action( 'play_single_header_end'); ?>
	</header>

	<?php do_action( 'play_after_single_header'); ?>

</div>
