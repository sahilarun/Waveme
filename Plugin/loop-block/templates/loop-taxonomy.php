<?php
/**
 * Template Name: Loop Taxonomy
 *
 * Template part for displaying loop taxonomy objects.
 */

?>

<div id="term-<?php echo esc_attr($term->term_id); ?>" class="block-loop-item">
	<figure class="post-thumbnail">
	<?php do_action( 'before_loop_thumbnail', get_the_ID() ); ?>
	<?php
		$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		if($thumbnail_id){
            $image = wp_get_attachment_image( $thumbnail_id, '' );
			echo sprintf('<a class="post-thumbnail-inner" href="%s">%s</a>', esc_url( get_term_link($term) ), $image);
		}
	?>
	<?php do_action( 'after_loop_thumbnail', get_the_ID() ); ?>
	</figure>
	<header class="entry-header">
		<?php echo sprintf('<a href="%s">%s</a>', esc_url( get_term_link($term) ), esc_html( $term->name ) ); ?>
	</header>

</div>
