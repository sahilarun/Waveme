<?php
/**
 * The main template file
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			
			<?php
			if ( have_posts() ) {
				?>
				
				<div class="archive-content archive-content-column">

				<?php
				// Load posts loop.
				while ( have_posts() ) {
					the_post();
					get_template_part( 'templates/content/content-excerpt' );
				}
				?>

				</div>

				<?php

				ffl_the_posts_navigation();

			} else {

				// If no content, include the "No posts found" template.
				get_template_part( 'templates/content/content', 'none' );

			}
			?>
		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php
get_footer();
