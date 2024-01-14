<?php
/**
 * More
 */

defined( 'ABSPATH' ) || exit;

?>

<span class="dropdown-item btn-playlist"><?php play_get_text('add-to-playlist', true); ?></span>
<span class="dropdown-item btn-next-play" data-index="1"><?php play_get_text('next-to-play', true); ?></span>
<span class="dropdown-item btn-queue" data-index="-1"><?php play_get_text('add-to-queue', true); ?></span>
<span class="dropdown-item btn-share"><?php play_get_text('share', true); ?></span>
<div class="dropdown-divider"></div>
<span class="dropdown-item btn-play-now" data-index="0"><?php play_get_text('play', true); ?></span>
<a class="dropdown-item btn-edit" data-action="edit" href="#"><?php play_get_text('edit', true); ?></a>
<a class="dropdown-item btn-remove" data-action="remove" href="#"><?php play_get_text('remove', true); ?></a>
