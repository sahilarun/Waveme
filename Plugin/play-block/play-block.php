<?php
/**
 * Plugin Name: Play Block
 * Plugin URI: http://flatfull.com/
 * Description: Play the post
 * Author: Flatfull
 * Version: 11.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
define( 'PLAYBLOCK_PLUGIN_URL',  plugins_url('', __FILE__ ) );
define( 'PLAYBLOCK_PLUGIN_PATH', plugin_dir_path(__FILE__ ) );

$dir = plugin_dir_path( __FILE__ ) . 'inc/';

require_once $dir . 'class-play-db.php';
require_once $dir . 'class-play-utils.php';
require_once $dir . 'class-play-stream.php';
require_once $dir . 'class-play-block.php';
require_once $dir . 'class-play-api.php';
require_once $dir . 'class-play-like.php';
require_once $dir . 'class-play-count.php';
require_once $dir . 'class-play-playlist.php';
require_once $dir . 'class-play-follow.php';
require_once $dir . 'class-play-download.php';
require_once $dir . 'class-play-post-type.php';
require_once $dir . 'class-play-upload.php';
require_once $dir . 'class-play-comment.php';
require_once $dir . 'class-play-notification.php';
require_once $dir . 'class-play-import.php';
//require_once $dir . 'class-play-extension.php';

require_once $dir . 'class-play-user.php';
require_once $dir . 'class-play-user-avatar.php';
require_once $dir . 'class-play-user-profile.php';

require_once $dir . 'play-functions.php';
require_once $dir . 'play-texts.php';
