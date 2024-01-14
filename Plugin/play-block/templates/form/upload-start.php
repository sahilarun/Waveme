<?php
/**
 * Start Upload Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/upload-start.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'before_upload_start' );

?>

<?php if(!$post->ID){ ?>
<div class="upload-start">
	<?php if( $user_can_upload_stream ){ ?>
	<div class="dragdrop-upload">
		<h3><?php play_get_text('dragdrop-upload-title', true); ?></h3>
		<p class="file-upload">
			<input type="file" name="stream_file" multiple accept="video/mp4,video/x-m4v,video/*,audio/mp3,audio/x-m4a,audio/*," />
			<button class="button-primary"><?php play_get_text('dragdrop-upload-button', true); ?></button>
		</p>
		<p><?php play_get_text('dragdrop-playlist', true); ?></p>
		<p><small><?php play_get_text('dragdrop-copyright', true); ?></small></p>
	</div>
	<?php } ?>
	<?php if( $user_can_upload_online ){ ?>
	<div class="online-upload">
		<h3><?php play_get_text('online-upload-title', true); ?></h3>
		<p class="url-upload"><input placeholder="http://" type="url" name="stream_url" /></p>
		<p><small><?php play_get_text('online-upload-tip', true); ?></small></p>
	</div>
	<?php } ?>
</div>
<?php } ?>

<?php do_action( 'after_upload_start' ); ?>
