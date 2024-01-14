<?php
/**
 * Template part for displaying notification
 */

$user_id = $item->notifier_id;
$user = get_userdata($user_id);
if(!$user){
	return;
}
$url = get_author_posts_url( $user_id );
$n = '';
$n_related = '';
if($item->item_id !== 0){
	$link = get_permalink($item->item_id);
	$title = get_the_title($item->item_id);
	$n = sprintf( '<a href="%s">%s</a>', esc_url($link), esc_html($title) );
}
if($item->item_related_id !== 0){
	$link = get_permalink($item->item_related_id);
	$title = get_the_title($item->item_related_id);
	$n_related = sprintf( '<a href="%s">%s</a>', esc_url($link), esc_html($title) );
}

$notifier = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $user->display_name ) );
$actions = apply_filters( 'play_notification_actions', $user_id );

$is_new = false;

?>

<div class="play-notification-item <?php echo esc_attr( $item->status == 0 ? 'is-new' : '' ); ?>">
	<a class="user-avatar" href="<?php echo esc_url( $url ); ?>" rel="author">
		<?php
			echo get_avatar($user_id);
		?>
	</a>
	<div class="play-notification-content">
		<div>
			<?php 
			if( isset( $actions[ $item->action ] ) ) { 
				printf( play_get_text( $actions[ $item->action ] ), $notifier, $n, $n_related ); 
			}
			?>
		</div>
		<div class="play-notification-date">
			<?php 
			printf( play_get_text('time-ago'), human_time_diff( strtotime( $item->date_notified ), current_time( 'timestamp', 1 ) ) );
			?>
		</div>
	</div>
</div>