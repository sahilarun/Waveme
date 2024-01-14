<?php
/**
 * Display station content
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="entry-content">

	<?php do_action( 'play_single_content_start'); ?>

	<div class="entry-content-wrap">
		<div class="entry-content-inner">
			<?php
			do_action( 'play_before_content');
			do_action( 'play_content');
			do_action( 'play_after_content');

			do_action( 'play_before_comment');
			do_action( 'play_comment');
			do_action( 'play_after_comment');
			?>
		</div>
		<?php
			get_sidebar( 'station' );
		?>
	</div>

	<?php do_action( 'play_single_content_end'); ?>
	
</div>
