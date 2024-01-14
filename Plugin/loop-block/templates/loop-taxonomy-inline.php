<?php
/**
 * Template Name: Loop Taxonomy Inline
 *
 * Template part for displaying loop taxonomy objects.
 */

?>

<div id="term-<?php echo esc_attr($term->term_id); ?>" class="block-loop-item">
	<?php echo sprintf('<a href="%s" class="block-loop-item-link">%s</a>', esc_url( get_term_link($term) ), esc_html( $term->name ) ); ?>
</div>
