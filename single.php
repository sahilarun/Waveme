<?php
/**
 * The template for displaying all single posts
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php

			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				get_template_part( 'templates/content/content', 'single' );
				ffl_related_posts();
				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
		<?php
			get_sidebar( 'page' );
		?>
	</div><!-- #primary -->

<?php
get_footer();
