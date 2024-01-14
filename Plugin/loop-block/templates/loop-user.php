<?php
/**
 * Template Name: Loop User
 *
 * Template part for displaying loop users.
 */

?>

<article id="user-<?php echo esc_attr($user->ID); ?>" class="block-loop-item">
	<figure class="post-thumbnail">
		<?php do_action( 'before_loop_thumbnail', get_the_ID() ); ?>
		<a class="post-thumbnail-inner" href="<?php echo esc_url(get_author_posts_url($user->ID)); ?>">
			<?php
			$size = (int) get_option( 'thumbnail_size_w' ) / 2;
			echo get_avatar($user->ID, $size, '', $user->display_name);
			?>
		</a>
		<?php do_action( 'after_loop_thumbnail', get_the_ID() ); ?>
	</figure>

	<header class="entry-header">
		<h3 class="entry-title">
			<a href="<?php echo esc_url(get_author_posts_url($user->ID)); ?>" rel="bookmark"><?php echo esc_html($user->display_name); ?></a><?php do_action( 'the_verified_button', $user->ID ); ?>
		</h3>
		<?php do_action( 'the_follow_button', $user->ID ); ?>
	</header>
</article>
