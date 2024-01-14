<?php
/**
 * The template for displaying user
 */

get_header();

?>
	
	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<article class="entry user single" id="user-<?php echo get_query_var( 'author' ); ?>">
				<?php
					play_get_template( 'user/header.php' );
					play_get_template( 'user/nav.php' );
					play_get_template( 'user/content.php' );
				?>
			</article>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
