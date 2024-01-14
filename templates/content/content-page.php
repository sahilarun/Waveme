<?php
/**
 * Template part for displaying page content in page.php
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php 
		$hide_title = get_post_meta( get_the_ID(), 'hide_title', true );
    ?>
	<header class="entry-header <?php echo esc_attr( $hide_title ? 'hide-title' : '' ); ?>">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header>

	<?php ffl_post_thumbnail(); ?>
	
	<?php ffl_the_page_navigation(); ?>
	
	<div class="entry-content">
		<?php
		the_content();

		ffl_link_pages();
		?>
	</div>
</article>
