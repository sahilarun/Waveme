<?php
/**
 * Display user header
 */
defined( 'ABSPATH' ) || exit;

if( apply_filters('play_user_header_before', true) === false ){
	return;
}

$user_id = get_queried_object_id();

// user template
if(!is_author()){
    if(is_user_logged_in()){
        $user_id = get_current_user_id();
    }else{
    	return;
    }
}

$user = get_userdata($user_id);
// add css class on own page
$class= '';
if( get_current_user_id() == $user_id ){
	$class= 'current-user';
}
// add css on thumbnail
$thumbnail_pos_y = get_user_meta($user_id, 'thumbnail_pos_y', true);
$size = (int) get_option( 'large_size_w' ) / 2;
$image = get_avatar($user_id, $size ? $size : 1, '', '', 
	array(
		'attr' => array(
			'style' => 'object-position: 50% '.$thumbnail_pos_y.'%', 
			'data-pos-y' => $thumbnail_pos_y
		)
	)
);
?>

<div class="entry-header-container header-user <?php echo esc_attr($class);?>">
	<?php do_action( 'play_before_user_header_thumbnail', $user_id); ?>
	<figure class="post-thumbnail">
		<?php echo wp_kses_post($image); ?>
	</figure>
	<?php do_action( 'play_after_user_header_thumbnail', $user_id); ?>
	<?php do_action( 'play_before_user_header', $user_id); ?>
	<header class="entry-header">
		<?php do_action( 'play_user_header_start', $user_id); ?>

		<h1 class="entry-title"><a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>"><?php echo esc_html( $user->display_name ); ?></a> <?php do_action('the_verified_button', $user_id); ?></h1>

		<?php do_action( 'play_before_user_header_desc', $user_id); ?>

		<?php
			$wrap = '<div class="entry-description" %s><p>%s</p></div>';
			$attr = '';
			if( apply_filters('play_use_desc_moreless', false) ){
				$attr = sprintf('data-plugin="moreless" more="%s" less="%s" type="%s" title="%s"',
					esc_attr(play_get_text( 'show-more' )),
					esc_attr(play_get_text( 'show-less' )),
					apply_filters('play_user_content_moreless_type', 'modal'),
					esc_attr($user->display_name)
				);
			}

			echo sprintf($wrap, $attr, wp_kses_post($user->description));
		?>

		<?php do_action( 'play_after_user_header_desc', $user_id); ?>

		<div class="entry-meta">
		<?php
			do_action( 'the_play_button', $user_id, 'user', '', true);
			do_action( 'the_follow_button', $user_id);
			do_action( 'the_user_links', $user);
			do_action( 'play_after_user_meta', $user_id);
		?>
		</div>

		<?php do_action( 'play_user_header_end', $user_id); ?>
	</header>
	<?php do_action( 'play_after_user_header', $user_id); ?>
</div>
