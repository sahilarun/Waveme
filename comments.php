<?php
/**
 * The template for displaying comments
 *
 * The area of the page that contains both current comments
 * and the comment form.
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">
	<h3 class="comments-title">
		<?php
			comments_popup_link( esc_html__('Comment','waveme'), esc_html__('1 Comment','waveme'), esc_html__('% Comments','waveme') );
		?>
	</h3>

	<?php
		comment_form( array(
			'title_reply_before' => sprintf('<input id="commentform-state" type="checkbox"></input><label for="commentform-state" class="comment-reply-header %s no-ajax">%s', ($user_identity ? 'is-logged-in' : '') , get_avatar(get_current_user_id())),
			'title_reply_after'  => '</label>',
			'comment_field'        => sprintf(
				'<p class="comment-form-comment">%s %s</p>',
				'<label for="commentform-state"></label>',
				'<textarea id="comment" name="comment" cols="45" rows="'.(is_user_logged_in() ? 1 : 5).'" required="required"></textarea>'
			),
			'must_log_in'          => sprintf(
				'<div class="must-log-in"><p class="input"><a href="%s" class="btn-ajax-login no-ajax">%s</a></p></div><button class="button" disabled>'.esc_html__('Comment','waveme').'</button>'
				, wp_login_url()
				, esc_html__('You must be logged in to post a comment.','waveme')
			),
			'title_reply' => '',
			'title_reply_to' => '',
			'logged_in_as' => '',
			'label_submit' => esc_html__('Comment','waveme'),
			'class_submit' => 'button-primary'
		) );
	?>

	<?php if ( have_comments() ) : ?>

		<ol class="comment-list" <?php apply_filters('comments_attr', ''); ?> >
			<?php
				$comments = wp_list_comments( array(
					'echo'			=> false,
					'style'         => 'ol',
					'short_ping'    => true,
					'avatar_size'   => 96
				) );
				echo apply_filters('comments', $comments );
			?>
		</ol><!-- .comment-list -->

		<?php
			$comment_pagination = paginate_comments_links(
				array(
					'echo'      => false,
					'mid_size'  => 2,
					'next_text' => ' ',
					'prev_text' => ' ',
				)
			);
			if ( $comment_pagination ) {
				echo apply_filters('comments_pagination', wp_kses_post('<nav class="comments-pagination nav-links">'.$comment_pagination.'</nav>') );
			}
		?>

	<?php endif; // Check for have_comments(). ?>

	<?php
		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
	?>
		<p class="no-comments"><?php esc_html__('Comments are closed.','waveme'); ?></p>
	<?php endif; ?>

</div><!-- .comments-area -->
