<?php
/**
 * Play Like Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a 'like'.
 *
 * @param array $data
 *
 * @return int|false ID of newly created 'like', false on error.
 */
function play_add_like( $data = array() ) {

	if ( empty( $data[ 'object_id' ] ) || empty( $data[ 'object_type' ] ) ) {
		return false;
	}

	do_action( 'play_pre_add_like', $data );

	// Instantiate a query object
	$like_query = new Play_Block_Like_Query();

	$data['date_created'] = current_time( 'mysql', true );

	$data = apply_filters( 'play_add_like', $data );

	$retval = $like_query->add_item( $data );

	do_action( 'play_post_add_like', $retval, $data );

	return $retval;
}

/**
 * Delete a 'like'.
 *
 * @param int $object_id Like ID.
 *
 * @return int|false `1` if the 'like' was deleted successfully, false on error.
 */
function play_delete_like( $object_id = 0 ) {
	$like_query = new Play_Block_Like_Query();

	do_action( 'play_pre_delete_like', $object_id );

	$retval = $like_query->delete_item( $object_id );

	do_action( 'play_post_delete_like', $retval, $object_id );

	return $retval;
}

/**
 * Update a 'like' row.
 *
 * @param int   $object_id Like ID.
 * @param array $data
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function play_update_like( $object_id = 0, $data = array() ) {

	do_action( 'play_pre_update_like', $object_id, $data );

	$like_query = new Play_Block_Like_Query();

	$data = apply_filters( 'play_update_like', $data, $object_id );

	$retval = $like_query->update_item( $object_id, $data );

	do_action( 'play_post_update_like', $retval, $object_id, $data );

	return $retval;
}

/**
 * Get a 'like' by ID.
 *
 * @param int $object_id Like ID.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_like( $object_id = 0 ) {
	$like_query = new Play_Block_Like_Query();

	// Return note
	return $like_query->get_item( $object_id );
}

/**
 * Get a 'like' by a specific field value.
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_like_by( $field = '', $value = '' ) {
	$like_query = new Play_Block_Like_Query();

	// Return note
	return $like_query->get_item_by( $field, $value );
}

/**
 * Retrieve a 'like' field
 *
 * @param int    $object_id Like ID.
 * @param string $field   The field to retrieve the 'like' with.
 *
 * @return object Like object if successful, false otherwise.
 */
function play_get_like_field( $object_id, $field = '' ) {
	$like = play_get_like( $object_id );

	// Check that field exists
	return isset( $like->{$field} )
		? $like->{$field}
		: null;
}

/**
 * Query for 'likes'.
 *
 * @param array $args Arguments.
 *
 * @return Like Array of `like` objects.
 */
function play_get_likes( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$like_query = new Play_Block_Like_Query();

	// Return order coupons
	return $like_query->query( $r );
}

/**
 * Count 'likes'.
 *
 * @param array $args Arguments.
 *
 * @return int Count of `like` objects.
 */
function play_count_likes( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$like_query = new Play_Block_Like_Query( $r );

	// Return count(s)
	return absint( $like_query->found_items );
}

/**
 * Verify if 'likes' exist.
 *
 * @param array $args Arguments.
 *
 * @return Bool
 */
function play_has_likes( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number'      => 1,
		'count'				=> true
	) );

	// Query for count(s)
	$like_query = new Play_Block_Like_Query();

	return (bool) $like_query->query( $r );
}

/**
 * Get an like by a specific object ID and Type.
 *
 * @param int    $object_id   Object ID
 * @param string $object_type Object type
 * @param string $action 			Action (like or dislike)
 *
 * @return Play_Block_Like
 */
function play_get_like_by_object( $object_id = 0, $object_type = 'post', $action = 'like', $user_id = 0 ) {

	// Get likes
	$likes = play_get_likes( array(
		'object_id'     => $object_id,
		'object_type'   => $object_type,
		'action'				=> $action,
		'user_id'				=> $user_id,
		'number'        => 1,
		'no_found_rows' => true
	) );

	// Bail if no likes
	if ( empty( $likes ) ) {
		return false;
	}

	// Return the first like
	return reset( $likes );
}
