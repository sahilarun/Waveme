<?php
/**
 * Empty
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="user-placeholder">
	<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
<?php 
$user_id = get_queried_object_id();

if( get_current_user_id() == $user_id ){
?>
		<p><?php play_get_text('no-follower', true); ?></p>
<?php
}else{
?>
		<p><?php play_get_text('no-follower-alt', true); ?></p>
<?php
}
?>
</div>
