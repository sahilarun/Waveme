<?php
/**
 * Play Notification Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a 'notification'.
 *
 * @param array $data
 *
 * @return int|false ID of newly created 'notification', false on error.
 */
function play_add_notification( $data = array() ) {

	if ( empty( $data[ 'user_id' ] ) || empty( $data[ 'notifier_id' ] ) ) {
		return false;
	}

	do_action( 'play_pre_add_notification', $data );

	// Instantiate a query object
	$notification_query = new Play_Block_Notification_Query();

	$data['date_notified'] = current_time( 'mysql', true );

	$data = apply_filters( 'play_add_notification', $data );

	$retval = $notification_query->add_item( $data );

	do_action( 'play_post_add_notification', $retval, $data );

	return $retval;
}

/**
 * Delete a 'notification'.
 *
 * @param int $object_id notification ID.
 *
 * @return int|false `1` if the 'notification' was deleted successfully, false on error.
 */
function play_delete_notification( $object_id = 0 ) {
	$notification_query = new Play_Block_Notification_Query();

	do_action( 'play_pre_delete_notification', $object_id );

	$retval = $notification_query->delete_item( $object_id );

	do_action( 'play_post_delete_notification', $retval, $object_id );

	return $retval;
}

/**
 * Update a 'notification' row.
 *
 * @param int   $object_id notification ID.
 * @param array $data
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function play_update_notification( $object_id = 0, $data = array() ) {

	do_action( 'play_pre_update_notification', $object_id, $data );

	$notification_query = new Play_Block_Notification_Query();

	$data = apply_filters( 'play_update_notification', $data, $object_id );

	$retval = $notification_query->update_item( $object_id, $data );

	do_action( 'play_post_update_notification', $retval, $object_id, $data );

	return $retval;
}

/**
 * Get a 'notification' by ID.
 *
 * @param int $object_id notification ID.
 *
 * @return object notification object if successful, false otherwise.
 */
function play_get_notification( $object_id = 0 ) {
	$notification_query = new Play_Block_Notification_Query();

	// Return note
	return $notification_query->get_item( $object_id );
}

/**
 * List for 'notifications'.
 *
 * @param array $args Arguments.
 *
 * @return notification Array of `notification` objects.
 */
function play_get_notifications( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$notification_query = new Play_Block_Notification_Query();
	
	return $notification_query->query( $r );
}

/**
 * Count 'notifications'.
 *
 * @param array $args Arguments.
 *
 * @return int Count of `notification` objects.
 */
function play_count_notifications( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$notification_query = new Play_Block_Notification_Query( $r );

	// Return count(s)
	return absint( $notification_query->found_items );
}
