<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<?php do_action('play_block_embed_before_head'); ?>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<link rel="stylesheet" type="text/css" href="<?php echo esc_url(PLAYBLOCK_PLUGIN_URL.'/build/style.min.css'); ?>">
	<style type="text/css">
		:root{
			--color-primary: <?php echo get_option( 'primary_color' ); ?>;
		}
		.btn-play,
		.waveform .waveform-container{
		    border-color: var(--color-primary);
		    color: var(--color-primary);
		    background-color: transparent;
		}
		<?php if(isset($_REQUEST['theme']) ){
			echo 'body{color: #fff}';
		} ?>
	</style>
	<?php do_action('play_block_embed_after_head'); ?>
</head>
<body class="play-embed-body no-player">
<?php do_action('play_block_embed_before_body'); ?>
<?php
	global $wp;
	$id = (int) $wp->query_vars['embed'];
	$type = isset($_GET['u']) ? 'user' : 'post';
	$GLOBALS[ 'post' ] = get_post( $id );
	setup_postdata( $GLOBALS[ 'post' ] );

	$url = $img = '';
	if($type == 'post'){
		$url = get_the_permalink($id);
		$img = get_the_post_thumbnail($id,'post-thumbnail');
	}else{
		$url = get_author_posts_url($id);
		$img = get_avatar($id, 120);
	}
?>
<div class="play-embed <?php echo esc_attr($type); ?>">
	<figure class="post-thumbnail">
		<a class="post-thumbnail-inner" href="<?php echo esc_url($url); ?>" target="_blank">
			<?php echo $img ?>
		</a>
	</figure>
	<div class="play-embed-content">
		<div class="play-embed-header">
			<?php if($type == 'post'){ ?>
				<button class="btn-play" data-play-id="<?php echo esc_attr($id); ?>"></button>
				<div class="sep-1"></div>
				<div class="flex">
					<div class="entry-meta">
						<?php do_action( 'the_loop_author', $id ); ?>
					</div>
					<?php
						the_title( sprintf( '<h3 class="entry-title"><a href="%s" target="_blank">', esc_url( get_permalink() ) ), '</a></h3>' );
					?>
				</div>
			<?php } ?>
			<?php if($type == 'user'){ 
				$user = get_userdata($id);
			?>
				<div class="flex">
					<h3 class="entry-title"><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html($user->display_name); ?></a><?php do_action('the_verified_button', $id); ?></h3>
					<div class="entry-meta">
						<span><?php play_get_text('followers', true); ?>:</span>
						<span class="follow-count count">
							<?php $count = apply_filters('user_follow', $id, true, true ); 
							echo esc_html($count);
							?>
						</span>
					</div>
				</div>
			<?php } ?>
		</div>
		<?php do_action( 'the_loop_waveform', $id ); ?>
		<div class="play-embed-footer">
			<?php if($type == 'post'){ ?>
				<span class="play-embed-cat"><?php echo get_the_term_list($id, 'genre'); ?></span>
			<?php } ?>
			<div class="flex"></div>
			<div class="site-brand">
				<?php if ( has_custom_logo() ) : ?>
					<div class="site-logo"><?php the_custom_logo(); ?></div>
				<?php endif; ?>
				<?php $site_title = get_bloginfo( 'name' ); ?>
				<?php if ( ! empty( $site_title ) ) : ?>
					<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php echo esc_html($site_title); ?></a></p>
				<?php endif; ?>
			</div>
			<?php do_action( 'play-embed-footer', $id ); ?>
		</div>
	</div>
</div>
<div class="play-embed-list">
	<?php if($type == 'user'){
		$types   = play_get_option( 'play_types' );
		$arg = array(
            'type'      => $types,
            'pages'     => 20,
            'pager'	    => '',
            'query'     => array( 'author' => $id ),
            'className' => 'block-loop-row block-loop-index station-tracklist',
            'debug'     => false
        );
        do_action( 'the_loop_block', apply_filters( 'play_album_tracks', $arg ) );
	} ?>
	<?php do_action( 'play_after_embed', $id ); ?>
</div>
<script type="text/javascript">
	<?php 
		echo sprintf('var play = {
				url: "%s",
				nonce: "%s",
				rest: {
					endpoints:{ play: "%s"},
				}
			}',
			esc_url(PLAYBLOCK_PLUGIN_URL.'/build/'),
			wp_create_nonce( 'wp_rest' ),
			get_rest_url(null, 'play/play')
		);
	?>
</script>
<script type="text/javascript" src="<?php echo esc_url(site_url().'/wp-includes/js/jquery/jquery.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo esc_url(PLAYBLOCK_PLUGIN_URL.'/build/play.min.js'); ?>"></script>
<script type="text/javascript">
	var dark = jQuery('html, iframe', parent.document).hasClass('dark');
	dark ? jQuery('html').addClass('dark') : jQuery('html').removeClass('dark');

</script>

<?php
do_action('play_block_embed_after_body'); 
wp_reset_postdata();
?>
</body>
</html>