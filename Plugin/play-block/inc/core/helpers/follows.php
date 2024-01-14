<?php
/**
 * Play Follow Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a 'follow'.
 *
 * @param array $data
 *
 * @return int|false ID of newly created 'follow', false on error.
 */
function play_add_follow( $data = array() ) {

	if ( empty( $data[ 'object_id' ] ) || empty( $data[ 'object_type' ] ) ) {
		return false;
	}

	do_action( 'play_pre_add_follow', $data );

	// Instantiate a query object
	$follow_query = new Play_Block_Follow_Query();

	$data['date_created'] = current_time( 'mysql', true );

	$data = apply_filters( 'play_add_follow', $data );

	$retval = $follow_query->add_item( $data );

	do_action( 'play_post_add_follow', $retval, $data );

	return $retval;
}

/**
 * Delete a 'follow'.
 *
 * @param int $object_id Like ID.
 *
 * @return int|false `1` if the 'follow' was deleted successfully, false on error.
 */
function play_delete_follow( $object_id = 0 ) {
	$follow_query = new Play_Block_Follow_Query();

	do_action( 'play_pre_delete_follow', $object_id );

	$retval = $follow_query->delete_item( $object_id );

	do_action( 'play_post_delete_follow', $retval, $object_id );

	return $retval;
}

/**
 * Update a 'follow' row.
 *
 * @param int   $object_id Like ID.
 * @param array $data
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function play_update_follow( $object_id = 0, $data = array() ) {

	do_action( 'play_pre_update_follow', $object_id, $data );

	$follow_query = new Play_Block_Follow_Query();

	$data = apply_filters( 'play_update_follow', $data, $object_id );

	$retval = $follow_query->update_item( $object_id, $data );

	do_action( 'play_post_update_follow', $retval, $object_id, $data );

	return $retval;
}

/**
 * Get a 'follow' by ID.
 *
 * @param int $object_id Like ID.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_follow( $object_id = 0 ) {
	$follow_query = new Play_Block_Follow_Query();

	// Return note
	return $follow_query->get_item( $object_id );
}

/**
 * Get a 'follow' by a specific field value.
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_follow_by( $field = '', $value = '' ) {
	$follow_query = new Play_Block_Follow_Query();

	// Return note
	return $follow_query->get_item_by( $field, $value );
}

/**
 * Retrieve a 'follow' field
 *
 * @param int    $object_id Like ID.
 * @param string $field   The field to retrieve the 'follow' with.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_follow_field( $object_id, $field = '' ) {
	$like = play_get_follow( $object_id );

	// Check that field exists
	return isset( $like->{$field} )
		? $like->{$field}
		: null;
}

/**
 * Query for 'follows'.
 *
 * @param array $args Arguments.
 *
 * @return array.
 */
function play_get_follows( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$follow_query = new Play_Block_Follow_Query();

	return $follow_query->query( $r );
}

/**
 * Count 'follows'.
 *
 * @param array $args Arguments.
 *
 * @return int Count of `follow` objects.
 */
function play_count_follows( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$follow_query = new Play_Block_Follow_Query( $r );

	// Return count(s)
	return absint( $follow_query->found_items );
}

/**
 * Verify if 'follows' exist.
 *
 * @param array $args Arguments.
 *
 * @return Bool
 */
function play_has_follows( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number'      => 1,
		'count'				=> true
	) );

	// Query for count(s)
	$follow_query = new Play_Block_Follow_Query();

	return (bool) $follow_query->query( $r );
}

/**
 * Get follow by a specific object ID and Type.
 *
 * @param int    $object_id   Object ID
 * @param string $object_type Object type
 *
 * @return object
 */
function play_get_follow_by_object( $object_id = 0, $object_type = 'user', $user_id = 0 ) {

	// Get follows
	$follows = play_get_follows( array(
		'object_id'     => $object_id,
		'object_type'   => $object_type,
		'user_id'				=> $user_id,
		'number'        => 1,
		'no_found_rows' => true
	) );

	// Bail if no follow
	if ( empty( $follows ) ) {
		return false;
	}

	// Return the first follow
	return reset( $follows );
}
