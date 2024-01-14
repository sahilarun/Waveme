<?php
/**
 * Template Name: Loop Inline
 * 
 * Template part for displaying loop inline
 */

?>

<article data-id="post-<?php the_ID(); ?>" data-play-id="<?php the_ID(); ?>" <?php post_class('block-loop-item block-loop-inline'); ?>>
	<?php do_action( 'the_play_button', get_the_ID() ); ?>
	<header class="entry-header">
		<div class="entry-header-inner">
			<?php do_action( 'before_loop_header', get_the_ID() ); ?>
			<?php
				the_title( sprintf( '<h3 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' );
			?>
			<div class="entry-meta">
				<?php do_action( 'the_loop_author', get_the_ID() ); ?>
			</div>
			<?php do_action( 'after_loop_header', get_the_ID() ); ?>
		</div>
		<?php do_action( 'the_loop_waveform', get_the_ID() ); ?>
		<div class="entry-footer">
			<?php do_action( 'before_loop_footer', get_the_ID(), $attributes ); ?>
			<?php do_action( 'the_purchase_button', get_the_ID() ); ?>
			<?php do_action( 'the_download_button', get_the_ID() ); ?>
			<?php do_action( 'the_like_button', get_the_ID() ); ?>
			<?php do_action( 'loop_footer', get_the_ID() ); ?>
			<?php do_action( 'the_more_button', get_the_ID() ); ?>
			<?php do_action( 'after_loop_footer', get_the_ID() ); ?>
		</div>
	</header>
</article>
