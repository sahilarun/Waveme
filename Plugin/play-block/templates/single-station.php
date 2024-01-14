<?php
/**
 * The template for displaying all single posts
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<article id="post-<?php the_ID(); ?>" data-play-id="<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php
			while ( have_posts() ) :
				the_post();

				play_get_template( 'single-station/content-single.php' );
				
			endwhile;
			?>
			</article>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
