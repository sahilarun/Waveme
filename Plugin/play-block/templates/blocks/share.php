<?php
/**
 * Share
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="modal-header">
	<h4 class="modal-title"><?php play_get_text('embed', true); ?></h4>
	<button class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
	<div class="share-embed">
		<iframe width="100%" height="220" scrolling="no" frameborder="no" src=""></iframe>
	</div>
	<input type="text" id="embed-code" class="input" value=""/>
	<h5><?php play_get_text('share', true); ?></h5>
	<div class="share-list">
		<a href="#" data-url="https://www.facebook.com/sharer.php?u=" target="_blank" title="Facebook">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
		</a>
		<a href="#" data-url="https://twitter.com/intent/tweet?url=" target="_blank" title="Twitter">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-twitter"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
		</a>
	</div>
	<form><input type="text" id="share-url" class="input" value=""/></form>
</div>
