<?php
/**
 * Template part for displaying a message that posts cannot be found
 */

?>

<article class="no-results not-found">
	<header class="page-header">
		<h1><?php echo esc_html__('Nothing Found','waveme'); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content">
		<?php
		if ( is_search() ) :
			?>

			<p>
				<?php echo esc_html__('Sorry, but nothing matched your search terms. Please try again with some different keywords.','waveme'); ?>
			</p>
			
			<?php
			get_search_form();

		else :
			?>

			<p>
				<?php echo esc_html__('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.','waveme'); ?>
			</p>

			<?php
			get_search_form();

			?>

			<p>
				<a class="button button-rounded button-primary" href="<?php echo esc_url( home_url() ); ?>"><?php echo esc_html__('Back to homepage','waveme'); ?></a>
			</p>
			
			<?php
		endif;
		?>
		<p></p>
	</div>
</article>
