<?php
/**
 * Template Name: Loop Attachment
 *
 * Template part for displaying loop attachment objects.
 */

?>

<article data-id="post-<?php the_ID(); ?>" <?php post_class('block-loop-item'); ?>>
	<?php
		the_content();
	?>
</article>
