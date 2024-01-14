<?php
/**
 * Playlist
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="modal-header">
	<h4 class="modal-title"><?php play_get_text('playlist', true); ?></h4>
	<button class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
	<div class="wp-block-loop block-loop-row block-loop-xs">
		<div class="block-loop-items">
			<div class="spinner"></div>
		</div>
	</div>
	<article class="block-loop-item is-album" id="tpl-item" style="display: none">
		<figure class="post-thumbnail">
			<a href="#"><img></a>
		</figure>
		<header class="entry-header">
			<div class="entry-header-inner">
				<a href="#" class="entry-title"></a>
				<div class="entry-meta">
					<div><i class="icon-playlist"></i><span class="count"></span></div>
				</div>
			</div>
			<footer class="entry-footer">
				<button class="btn-add button-xs"><?php play_get_text('add', true); ?></button>
				<button class="btn-added button-xs"><?php play_get_text('added-to-playlist', true); ?></button> 
				<!-- <button class="btn-remove button-xs button-light">&times;<span class="screen-reader-text"><?php play_get_text('remove', true); ?></span></button> -->
			</footer>
		</header>
	</article>
</div>
<div class="modal-footer">
	<form>
		<label><?php play_get_text('new', true); ?></label>
		<input type="text" placeholder="<?php play_get_text('playlist-placeholder', true); ?>">
		<button type="button" class="btn-new"><?php play_get_text('save', true); ?></button>
	</form>
</div>
