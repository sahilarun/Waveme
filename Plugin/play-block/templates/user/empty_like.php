<?php
/**
 * Empty
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="user-placeholder">
	<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
<?php 
$user_id = get_queried_object_id();

if( get_current_user_id() == $user_id ){
?>
		<p><?php play_get_text('no-like', true); ?></p>
<?php
}else{
?>
		<p><?php play_get_text('no-like-alt', true); ?></p>
<?php
}
?>
</div>
