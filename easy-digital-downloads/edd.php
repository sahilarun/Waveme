<?php

// Disable scheme markup
add_filter( 'edd_add_schema_microdata', '__return_false' );

// Remove the automatic purchase link ouput from the download content
remove_action( 'edd_after_download_content', 'edd_append_purchase_link' );

// Replace button color (unused) with 'no-ajax' class
function ffl_edd_filter_checkout_button_color( $value, $key, $default ) {
	return 'no-ajax';
}
add_filter( 'edd_get_option_checkout_color', 'ffl_edd_filter_checkout_button_color', 10, 3 );

// add gutenberg to easy digital downloads
function ffl_edd_product_taxonomy_show_in_rest( $args ) {
	$args['show_in_rest'] = true;
	return $args;
}

add_filter( 'edd_download_category_args', 'ffl_edd_product_taxonomy_show_in_rest');
add_filter( 'edd_download_tag_args', 'ffl_edd_product_taxonomy_show_in_rest');


function ffl_edd_styles() {
	wp_enqueue_style( 'ffl-edd-css', get_template_directory_uri().'/easy-digital-downloads/edd.css', array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'ffl_edd_styles' );

// enqueue our easy digital downloads JS file
function ffl_edd_scripts(){
	wp_enqueue_script( 'ffl-edd', get_template_directory_uri().'/easy-digital-downloads/edd.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'wp_enqueue_scripts', 'ffl_edd_scripts' );

/**
 * Filters the registration arguments when registering meta to add an auth_callback fix.
 *
 * @param array  $args        Array of meta registration arguments.
 * @param array  $defaults    Array of default arguments.
 * @param string $object_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                            or any other object type with an associated meta table.
 * @param string $meta_key    Meta key.
 */
function ffl_apply_edd_auth_callbacks( $args, $defaults, $object_type, $meta_key ) {

	$meta_keys = apply_filters( 'ffl_edd_auth_callback_keys', array(
		'_edd_download_earnings',
		'_edd_download_sales',
		'edd_price',
		'edd_variable_prices',
		'_edd_bundled_products',
		'_edd_button_behavior',
		'_edd_default_price_id',
		'edd_download_files'
	) );

	if ( in_array( $meta_key, $meta_keys ) ) {
		$args['auth_callback'] = function () { return current_user_can( 'edit_posts' ); };
	}

	return $args;
}
add_filter( 'register_meta_args', 'ffl_apply_edd_auth_callbacks', 10, 4 );


// Add the billing address fields to the profile form
function ffl_edd_profile_fields() {
	edd_default_cc_address_fields();
	echo '<input type="hidden" name="edd_profile_editor_submit">';
}
add_action( 'profile_form_middle', 'ffl_edd_profile_fields' );

// Save the billing address fields on update
function ffl_edd_update_billing_address( $user_id ) {
	$user = array(
		'user_id' => $user_id
	);
	$valid_data = array(
		'logged_in_user' => $user
	);
	$user = edd_get_purchase_form_user( $valid_data );

	// edd 3.0
	if( function_exists('edd_process_profile_editor_updates') ){
		$address = $user['address'];
		$data = array(
			'edd_address_line1'     => $address['line1'],
			'edd_address_line2'     => $address['line2'],
			'edd_address_city'      => $address['city'],
			'edd_address_state'     => $address['state'],
			'edd_address_country'   => $address['country'],
			'edd_address_zip' 		=> $address['zip'],
			'edd_redirect'			=> '',
		);
		$data['edd_profile_editor_nonce'] = wp_create_nonce( 'edd-profile-editor-nonce' );
		// disable redirect
		define( 'WP_TESTS_DIR', true );
		edd_process_profile_editor_updates($data);
	}
}
add_action( 'profile_form_update', 'ffl_edd_update_billing_address', 10, 1 );
