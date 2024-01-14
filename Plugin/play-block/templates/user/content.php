<?php
/**
 * Display user content
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="entry-content sub-content" id="sub-ajax-content">

	<?php 
		if(is_page()){
			the_content();
		}else{
			do_action( 'play_user_content');
		}
	?>
	
</div>
