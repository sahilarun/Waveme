<?php
/**
 * The template for displaying 404 pages
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="error-404 not-found">
				<header class="page-header">
					<h2><?php echo esc_html__('404','waveme'); ?></h2>
					<h1 class="page-title"><?php echo esc_html__('Oops! That page can&rsquo;t be found.','waveme'); ?></h1>
				</header><!-- .page-header -->
				
				<div class="page-content">
					<p>
						<?php echo esc_html__('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.','waveme'); ?>
					</p>
					<?php get_search_form(); ?>
					<p>
						<a class="button button-rounded button-primary" href="<?php echo esc_url( home_url() ); ?>"><?php echo esc_html__('Back to homepage','waveme'); ?></a>
					</p>
				</div><!-- .page-content -->
			</div><!-- .error-404 -->
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
