<?php
/**
 * Play Stat Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a 'stat'.
 *
 * @param array $data
 *
 * @return int|false ID of newly created 'stat', false on error.
 */
function play_add_stat( $data = array() ) {

	if ( empty( $data[ 'object_id' ] ) || empty( $data[ 'object_type' ] ) ) {
		return false;
	}

	do_action( 'play_pre_add_stat', $data );

	// Instantiate a query object
	$stat_query = new Play_Block_Stat_Query();

	$data['date_created'] = current_time( 'mysql', true );

	$data = apply_filters( 'play_add_stat', $data );

	$retval = $stat_query->add_item( $data );

	do_action( 'play_post_add_stat', $retval, $data );

	return $retval;
}

/**
 * Delete a 'stat'.
 *
 * @param int $object_id stat ID.
 *
 * @return int|false `1` if the 'stat' was deleted successfully, false on error.
 */
function play_delete_stat( $object_id = 0 ) {
	$stat_query = new Play_Block_Stat_Query();

	do_action( 'play_pre_delete_stat', $object_id );

	$retval = $stat_query->delete_item( $object_id );

	do_action( 'play_post_delete_stat', $retval, $object_id );

	return $retval;
}

/**
 * Update a 'stat' row.
 *
 * @param int   $object_id stat ID.
 * @param array $data
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function play_update_stat( $object_id = 0, $data = array() ) {

	do_action( 'play_pre_update_stat', $object_id, $data );

	$stat_query = new Play_Block_Stat_Query();

	$data = apply_filters( 'play_update_stat', $data, $object_id );

	$retval = $stat_query->update_item( $object_id, $data );

	do_action( 'play_post_update_stat', $retval, $object_id, $data );

	return $retval;
}

/**
 * Get a 'stat' by ID.
 *
 * @param int $object_id stat ID.
 *
 * @return object stat object if successful, false otherwise.
 */
function play_get_stat( $object_id = 0 ) {
	$stat_query = new Play_Block_Stat_Query();

	// Return note
	return $stat_query->get_item( $object_id );
}

/**
 * Get a 'play' by a specific field value.
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return object stat object if successful, false otherwise.
 */
function play_get_play_by( $field = '', $value = '' ) {
	$stat_query = new Play_Block_Stat_Query();

	// Return note
	return $stat_query->get_item_by( $field, $value );
}

/**
 * Retrieve a 'stat' field
 *
 * @param int    $object_id stat ID.
 * @param string $field   The field to retrieve the 'stat' with.
 *
 * @return object stat object if successful, false otherwise.
 */
function play_get_stat_field( $object_id, $field = '' ) {
	$stat = play_get_stat( $object_id );

	// Check that field exists
	return isset( $stat->{$field} )
		? $stat->{$field}
		: null;
}

/**
 * Query for 'stats'.
 *
 * @param array $args Arguments.
 *
 * @return stat Array of `stat` objects.
 */
function play_get_stats( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$stat_query = new Play_Block_Stat_Query();

	// Return order coupons
	return $stat_query->query( $r );
}

/**
 * Count 'stats'.
 *
 * @param array $args Arguments.
 *
 * @return int Count of `stat` objects.
 */
function play_count_stats( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$stat_query = new Play_Block_Stat_Query( $r );

	// Return count(s)
	return absint( $stat_query->found_items );
}

/**
 * Verify if 'stats' exist.
 *
 * @param array $args Arguments.
 *
 * @return Bool
 */
function play_has_stats( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number'      => 1,
		'count'				=> true
	) );

	// Query for count(s)
	$stat_query = new Play_Block_Stat_Query();

	return (bool) $stat_query->query( $r );
}


/**
 * Get an stat by a specific object ID and Type.
 *
 * @param int    $object_id   Object ID
 * @param string $object_type Object type
 *
 * @return Play_Block_Like
 */
function play_get_stat_by_object( $object_id = 0, $object_type = 'post', $user_id = 0 ) {

	// Get stats
	$stats = play_get_stats( array(
		'object_id'     => $object_id,
		'object_type'   => $object_type,
		'user_id'				=> $user_id,
		'number'        => 1,
		'no_found_rows' => true
	) );

	// Bail if no stats
	if ( empty( $stats ) ) {
		return false;
	}

	// Return the first stat
	return reset( $stats );
}
