<?php
/**
 *
 * Template part for displaying ad.
 */

$order = (int)$data['adOrder'];
if($order ===-1){
	$order = rand(0, 4);
}
$file = $data['adImage'];
$check = wp_check_filetype( $file );
$ext = $check['ext'];
$content = '';
if( in_array( $ext, wp_get_video_extensions(), true ) ){
	$content = '';
	$atts = array( 'src' => $file, 'autoplay' => true, 'muted' => true );
    $content = wp_video_shortcode( $atts );
    $content =  preg_replace('/<!--(.|\n)*?-->/', '', $content);
    $content =  str_replace( 'controls="controls"', '', $content );
}
if( in_array( $ext, array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'webp' ), true) ){
	$content = sprintf('<img src="%s">', $file);
}
?>

<article <?php post_class('block-loop-item block-loop-ad'); ?> style="order:<?php echo esc_attr($order); ?>">
	
	<figure class="post-thumbnail">
		<?php echo $content; ?>
		<div class="entry-action">
			<a class="entry-action-link" href="<?php echo esc_url($data['adLink']); ?>" target="_blank"></a>
		</div>
	</figure>

	<header class="entry-header">
		<div class="entry-header-inner">
			<h3 class="entry-title">
				<a href="<?php echo esc_url($data['adLink']); ?>" target="_blank"><?php
				echo esc_html($data['adTitle']);
				?></a>
			</h3>
			<div class="entry-subtitle">
				<a href="<?php echo esc_url($data['adLink']); ?>" target="_blank"><?php
					echo esc_html($data['adSubtitle']);
				?></a>
			</div>
			<div class="entry-meta">
				<a href="<?php echo esc_url($data['adLink']); ?>" target="_blank"><?php
					echo esc_html($data['adSponsor']);
				?></a>
			</div>
		</div>
	</header>
</article>
