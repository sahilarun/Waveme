<?php
/**
 * Template Name: Loop Tracks
 *
 * Template part for displaying loop tracks.
 */

?>

<article data-id="post-<?php the_ID(); ?>" data-play-id="<?php the_ID(); ?>" <?php post_class('block-loop-item album-track'); ?>>
	<span class="btn-play-now icon-play"  data-play-id="<?php the_ID(); ?>"></span>
	<button class="btn-playlist icon-plus"></button>
	<figure class="post-thumbnail">
		<?php do_action( 'before_loop_thumbnail', get_the_ID() ); ?>
		<a class="post-thumbnail-inner" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'thumbnail' ); ?>
		</a>
		<?php do_action( 'after_loop_thumbnail', get_the_ID() ); ?>
	</figure>
	<header class="entry-header">
		<div class="entry-header-inner btn-play-now">
			<?php do_action( 'before_loop_header', get_the_ID() ); ?>
			<?php
				the_title( sprintf( '<h3 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' );
			?>
			<div class="entry-meta">
				<?php do_action( 'the_loop_author', get_the_ID() ); ?>
			</div>
			<?php do_action( 'after_loop_header', get_the_ID() ); ?>
		</div>

		<div class="entry-footer">
			<?php do_action( 'before_loop_footer', get_the_ID() ); ?>
			<?php do_action( 'the_download_button', get_the_ID() ); ?>
			<?php do_action( 'the_like_button', get_the_ID() ); ?>
			<?php do_action( 'loop_footer', get_the_ID() ); ?>
			<?php do_action( 'the_more_button', get_the_ID() ); ?>
			<?php do_action( 'after_loop_footer', get_the_ID() ); ?>
		</div>
	</header>
</article>
