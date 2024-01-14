<?php

function ffl_woo_theme_setup() {
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'ffl_woo_theme_setup' );

function ffl_woo_theme_wrapper_start() {
    echo '<div class="woocommerce container">';
}
function ffl_woo_theme_wrapper_end() {
    echo '</div>';
}
add_action('woocommerce_before_main_content', 'ffl_woo_theme_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'ffl_woo_theme_wrapper_end', 10);

// enqueue the woocommerce js/css for ajax
function ffl_woo_styles() {
	wp_enqueue_style( 'ffl-woocommerce-css', get_template_directory_uri().'/woocommerce/woo.css', array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'ffl_woo_styles');

// disable the lightbox
function ffl_woo_scripts(){
	wp_enqueue_script( 'wc-single-product' );
	wp_enqueue_script( 'wc-add-to-cart-variation' );
	wp_enqueue_script( 'zoom' );
	wp_enqueue_script( 'flexslider' );
	wp_enqueue_script( 'ffl-woocommerce', get_template_directory_uri().'/woocommerce/woo.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'wp_enqueue_scripts', 'ffl_woo_scripts' );

function ffl_woo_support(){
	remove_theme_support( 'wc-product-gallery-lightbox' );
	//remove_theme_support( 'wc-product-gallery-zoom' );
	//remove_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'template_redirect', 'ffl_woo_support', 100 );

// set the gallery thunbnail size
add_filter( 'woocommerce_gallery_thumbnail_size', function( $size ) {
	return array( '600', '600' );
}, 100 );

// remove sidebar for woocommerce pages 
add_action( 'get_header', 'ffl_woo_remove_storefront_sidebar' );
function ffl_woo_remove_storefront_sidebar() {
    if ( is_shop() ) {
        remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
    }
}

// add gutenberg to woocommerce
function ffl_woo_product_taxonomy_show_in_rest( $args ) {
	$args['show_in_rest'] = true;
	return $args;
}
add_filter( 'woocommerce_taxonomy_args_product_cat', 'ffl_woo_product_taxonomy_show_in_rest');
add_filter( 'woocommerce_taxonomy_args_product_tag', 'ffl_woo_product_taxonomy_show_in_rest');

call_user_func_array(
    sprintf('remove%sfilter', '_'),
    array( 'gutenberg_can_edit_post_type', 'WC_Post_Types::gutenberg_can_edit_post_type', 10 )
);

call_user_func_array(
    sprintf('remove%sfilter', '_'),
    array( 'use_block_editor_for_post_type', 'WC_Post_Types::gutenberg_can_edit_post_type', 10 )
);

function ffl_woo_admin_scripts(){
	wp_enqueue_script( 'ffl-woocommerce-admin', get_template_directory_uri().'/woocommerce/woo.admin.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'admin_enqueue_scripts', 'ffl_woo_admin_scripts' );

// notice
add_action( 'play_single_header_end', 'woocommerce_output_all_notices', 10 );

remove_action('template_redirect', 'wc_disable_author_archives_for_customers');