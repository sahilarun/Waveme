<?php
/**
 * User bio
 */

defined( 'ABSPATH' ) || exit;
$user_id = get_the_author_meta( 'ID' );
$user = get_userdata($user_id);
$url = get_author_posts_url( $user_id );
?>
<div class="user-bio">
	<a class="user-avatar" href="<?php echo esc_url( $url ); ?>" rel="author">
		<?php
			echo get_avatar($user_id);
		?>
		<?php do_action('ffl_author_avatar', $user_id); ?>
	</a>
	<div class="user-info">
		<div class="user-info-col">
			<div class="user-title">
				<h3>
					<a href="<?php echo esc_url( $url ); ?>" rel="author">
					<?php esc_html_e( get_the_author() ); ?>
					</a>
					<?php do_action('the_verified_button', $user_id); ?>
				</h3>
				<?php do_action('play_after_author_link', $user_id); ?>
			</div>
			<div class="user-follower">
				<span><?php play_get_text('followers', true); ?>:</span>
				<span class="follow-count count">
					<?php $count = apply_filters('user_follow', $user_id, true, true ); 
					echo esc_html($count);
					?>
				</span>
			</div>
		</div>
		<span class="flex"></span>
		<div>
		<?php do_action('the_follow_button', $user_id); ?>
		</div>
	</div>
</div><!-- .author-bio -->
