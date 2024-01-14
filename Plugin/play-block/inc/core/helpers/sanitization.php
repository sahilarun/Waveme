<?php
/**
 * Play Sanitization Functions
 *
 * @package   play-block
 * @copyright Copyright (c) 2022, Flatfull
 * @license   GPL2+
 * @since     9.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize play block action.
 *
 * @param string $value
 *
 * @return string
 */
function play_sanitize_action( $value = '' ) {

  $object_types = apply_filters( 'play_sanitize_actions', array(
    'like',
    'dislike'
  ) );

  $value = sanitize_text_field( strtolower( $value ) );

  if ( ! in_array( $value, $object_types ) ) {
    $value = 'like';
  }

  return $value;
}

/**
 * Sanitize play block object type.
 *
 * @param string $value
 *
 * @return string
 */
function play_sanitize_object_type( $value = '' ) {

  $object_types = apply_filters( 'play_sanitize_object_types', array(
    'post',
    'comment'
  ) );

  $value = sanitize_text_field( strtolower( $value ) );

  if ( ! in_array( $value, $object_types ) ) {
    $value = 'post';
  }

  return $value;
}

/**
 * Sanitize play block custom data.
 *
 * @param string $value
 *
 * @return mixed
 */
function play_sanitize_custom_data( $value = '' ) {
  return apply_filters( 'play_sanitize_custom_data', $value );
}
