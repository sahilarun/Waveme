<?php
/**
 * Remove
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="modal-header">
	<h4 class="modal-title"><?php play_get_text('delete-account', true); ?></h4>
</div>
<div class="modal-body">
	<p><?php play_get_text('delete-account-alert', true); ?></p>
</div>
<div class="modal-footer text-right">
	<button class="button-link" data-dismiss="modal"><?php play_get_text('cancel', true); ?></button>
	<?php
		$url = apply_filters('get_endpoint_url', 'delete-my-account', '', get_author_posts_url( get_current_user_id() ) );
	?>
	<a href="<?php echo esc_url( $url ); ?>" class="button no-ajax" data-remove><?php play_get_text('delete-my-account', true); ?></a>
</div>
