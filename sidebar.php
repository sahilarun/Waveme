<?php
/**
 * Sidebar
 */

defined( 'ABSPATH' ) || exit;

$id = ffl_sidebar();

if( !$id ){
	return;
}

if( get_post_field('post_content', $id) === '' ){
	return;
}

?>

<div class="sidebar">
	<div class="sidebar-inner">
	<?php
		do_action( 'play_before_sidebar');
	?>
	<?php
		ffl_the_content($id);
	?>
	<?php
		do_action( 'play_after_sidebar');
	?>
	</div>
</div>
