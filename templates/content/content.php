<?php
/**
 * Template part for displaying posts
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
		ffl_posted_sticky();
		
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
		endif;
		?>
	</header>

	<?php ffl_post_thumbnail(); ?>

	<div class="entry-content">
		<?php
		the_content();
		
		ffl_link_pages();
		?>
	</div>
	<footer class="entry-footer">
		<?php ffl_entry_footer(); ?>
	</footer>
</article>
