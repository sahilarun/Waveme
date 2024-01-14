<?php
/**
 * Template Name: Loop Post Excerpt
 * 
 * Template part for displaying post excerpt
 */

?>

<article data-id="post-<?php the_ID(); ?>" <?php post_class('block-loop-item'); ?>>
	<?php if(has_post_thumbnail()) { ?>
	<figure class="post-thumbnail">
		<?php do_action( 'before_loop_thumbnail', get_the_ID() ); ?>
		<a class="post-thumbnail-inner" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'medium' ); ?>
		</a>
		<?php do_action( 'after_loop_thumbnail', get_the_ID() ); ?>
	</figure>
	<?php } ?>
	<header class="entry-header">
		<div class="entry-header-inner">
			<?php
				the_title( sprintf( '<h3 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' );
			?>
			<div class="entry-excerpt">
				<?php the_excerpt(); ?>
			</div>
		</div>
	</header>
</article>
