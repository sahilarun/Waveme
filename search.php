<?php
/**
 * The template for displaying search results pages
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title search-title"><?php printf( esc_html__('Search results for: %s','waveme'), '<strong>' . get_search_query() . '</strong>' ); ?></h1>
			</header>
			<div class="archive-content archive-content-column">
			<?php
			// Start the Loop.
			while ( have_posts() ) :
				the_post();

				/*
				 * Include the Post-Format-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
				 */
				$type = get_post_type();
				$types = apply_filters( 'ffl_play_types', ['station'] );
				if( in_array($type, $types) ){
					get_template_part( 'templates/content/content', 'play' );
				} else {
					get_template_part( 'templates/content/content', 'excerpt' );
				}

				// End the loop.
			endwhile;
			?>
			</div>
			<?php
			ffl_the_posts_navigation();
			
			// If no content, include the "No posts found" template.
			else :
				get_template_part( 'templates/content/content', 'none' );
			endif;
			?>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
