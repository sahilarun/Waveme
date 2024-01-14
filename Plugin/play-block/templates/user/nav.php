<?php
/**
 * Display user nav
 */
defined( 'ABSPATH' ) || exit;

if( apply_filters('play_user_nav_before', true) === false ){
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

?>
<nav class="navigation user-navigation" id="sub-ajax-menu">
	<?php if ( has_nav_menu( 'user' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'user',
				'menu_class'     => 'nav',
				'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
	} else { ?>
		<ul class="nav">
			<?php
				global $wp;
				
				$endpoints = apply_filters('get_user_endpoints', '');
				foreach ( $endpoints as $endpoint => $label ){
					
					$pass = apply_filters('pass_user_endpoints', $endpoint, $user_id);
					if( $pass ){
						continue;
					}
					$count = apply_filters('nav_count', $user_id, $endpoint);
					if(isset($label['id'])){
						$endpoint = $label['id'];
					}
					$active = isset( $wp->query_vars[ $endpoint ] ) ? 'active' : '';
					$link = apply_filters('get_endpoint_url', $endpoint, '', get_author_posts_url($user_id) );
					$name = isset($label['name']) ? $label['name'] : $label;
					?>
					<li class="<?php echo esc_attr($active); ?>"  >
						<a class="sub-ajax" href="<?php echo esc_url($link); ?>"><?php echo esc_html( $name ).$count; ?></a>
					</li>
			<?php } ?>
		</ul>
	<?php } ?>
</nav>
