<?php
/**
 * Remove
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="modal-header">
	<h4 class="modal-title"><?php play_get_text('remove', true); ?></h4>
</div>
<div class="modal-body">
	<p><?php play_get_text('remove-alert', true); ?></p>
</div>
<div class="modal-footer text-right">
	<button class="button-link" data-dismiss="modal"><?php play_get_text('cancel', true); ?></button>
	<a href="#" class="button no-ajax" data-remove><?php play_get_text('remove', true); ?></a>
</div>
