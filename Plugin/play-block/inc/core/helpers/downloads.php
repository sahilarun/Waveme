<?php
/**
 * Play Download Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a 'download'.
 *
 * @param array $data
 *
 * @return int|false ID of newly created 'download', false on error.
 */
function play_add_download( $data = array() ) {

	if ( empty( $data[ 'object_id' ] ) || empty( $data[ 'object_type' ] ) ) {
		return false;
	}

	do_action( 'play_pre_add_download', $data );

	// Instantiate a query object
	$download_query = new Play_Block_Download_Query();

	$data['date_created'] = current_time( 'mysql', true );

	$data = apply_filters( 'play_add_download', $data );

	$retval = $download_query->add_item( $data );

	do_action( 'play_post_add_download', $retval, $data );

	return $retval;
}

/**
 * Delete a 'download'.
 *
 * @param int $object_id Like ID.
 *
 * @return int|false `1` if the 'download' was deleted successfully, false on error.
 */
function play_delete_download( $object_id = 0 ) {
	$download_query = new Play_Block_Download_Query();

	do_action( 'play_pre_delete_download', $object_id );

	$retval = $download_query->delete_item( $object_id );

	do_action( 'play_post_delete_download', $retval, $object_id );

	return $retval;
}

/**
 * Update a 'download' row.
 *
 * @param int   $object_id Like ID.
 * @param array $data
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function play_update_download( $object_id = 0, $data = array() ) {

	do_action( 'play_pre_update_download', $object_id, $data );

	$download_query = new Play_Block_Download_Query();

	$data = apply_filters( 'play_update_download', $data, $object_id );

	$retval = $download_query->update_item( $object_id, $data );

	do_action( 'play_post_update_download', $retval, $object_id, $data );

	return $retval;
}

/**
 * Get a 'download' by ID.
 *
 * @param int $object_id Like ID.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_download( $object_id = 0 ) {
	$download_query = new Play_Block_Download_Query();

	// Return note
	return $download_query->get_item( $object_id );
}

/**
 * Get a 'download' by a specific field value.
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_download_by( $field = '', $value = '' ) {
	$download_query = new Play_Block_Download_Query();

	// Return note
	return $download_query->get_item_by( $field, $value );
}

/**
 * Retrieve a 'download' field
 *
 * @param int    $object_id Like ID.
 * @param string $field   The field to retrieve the 'download' with.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_download_field( $object_id, $field = '' ) {
	$like = play_get_follow( $object_id );

	// Check that field exists
	return isset( $like->{$field} )
		? $like->{$field}
		: null;
}

/**
 * Query for 'downloads'.
 *
 * @param array $args Arguments.
 *
 * @return Like Array of `download` objects.
 */
function play_get_downloads( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$download_query = new Play_Block_Download_Query();

	// Return order coupons
	return $download_query->query( $r );
}

/**
 * Count 'downloads'.
 *
 * @param array $args Arguments.
 *
 * @return int Count of `download` objects.
 */
function play_count_downloads( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$download_query = new Play_Block_Download_Query( $r );

	// Return count(s)
	return absint( $download_query->found_items );
}

/**
 * Verify if 'downloads' exist.
 *
 * @param array $args Arguments.
 *
 * @return Bool
 */
function play_has_downloads( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number'      => 1,
		'count'				=> true
	) );

	// Query for count(s)
	$download_query = new Play_Block_Download_Query();

	return (bool) $download_query->query( $r );
}

/**
 * Get an download by a specific object ID and Type.
 *
 * @param int    $object_id   Object ID
 * @param string $object_type Object type
 *
 * @return Array
 */
function play_get_download_by_object( $object_id = 0, $object_type = 'post', $user_id = 0 ) {

	// Get events
	$downloads = play_get_downloads( array(
		'object_id'     => $object_id,
		'object_type'   => $object_type,
		'user_id'				=> $user_id,
		'number'        => 1,
		'no_found_rows' => true
	) );

	// Bail if no download
	if ( empty( $downloads ) ) {
		return false;
	}

	// Return the first download
	return reset( $downloads );
}
