<?php
// display author bio

$user_id = get_the_author_meta( 'ID' );
$desc = get_the_author_meta( 'description',  $user_id);
if(empty($desc)) return;
?>
<div class="user-bio">
	<a class="user-avatar" href="<?php echo esc_url( get_author_posts_url( $user_id ) ); ?>" rel="author">
		<?php
			echo get_avatar($user_id, 64);
		?>
		<?php do_action('ffl_author_avatar', $user_id); ?>
	</a>
	<div class="user-info">
		<div class="user-meta">
			<div>
				<span class="user-heading">
					<?php echo esc_html__('Published by','waveme'); ?>
				</span>
				<div class="user-title">
					<h3>
						<a class="user-link" href="<?php echo esc_url( get_author_posts_url( $user_id ) ); ?>" rel="author">
						<?php echo esc_html( get_the_author() ); ?>
						</a>
					</h3>
				</div>
			</div>
			<span class="flex"></span>
			<div>
				<?php do_action('ffl_after_author_link', $user_id); ?>
			</div>
		</div>
		<div class="user-description">
			<?php echo get_the_author_meta('description')?>
		</div>
	</div>
</div><!-- .author-bio -->
