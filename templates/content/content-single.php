<?php
/**
 * Template part for displaying posts
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php 
		ffl_posted_sticky();
		ffl_categories_list();
		
		if ( is_page() ) :
			the_title( '<h2 class="entry-title h1">', '</h2>' );
		else :
			the_title( '<h1 class="entry-title">', '</h1>' );
		endif;
		ffl_entry_meta();
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
		<?php get_template_part( 'templates/post/author-bio' ); ?>
	</footer>
</article>
