<?php
/**
 * Plugin Name: Loop Block
 * Plugin URI: https://flatfull.com/
 * Description: Loop post/user data in the Gutenberg editor.
 * Author: Flatfull
 * Version: 11.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$dir = plugin_dir_path( __FILE__ ) . 'inc/';

require_once $dir . 'class-loop-api.php';
require_once $dir . 'class-loop.php';
