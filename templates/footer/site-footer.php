<?php
/**
 * Displays the footer 
 */

defined( 'ABSPATH' ) || exit;

$id = get_option( 'page_footer' );

if( is_singular() ){
	$_id = get_post_meta( get_the_ID(), 'footer', true );
	if($_id) $id = $_id;
}

if( !$id ){ 
	get_template_part( 'templates/footer/site-footer', 'default' );
	return;
}

ffl_the_content($id);