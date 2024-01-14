<?php
/**
 * Displays header site brand
 */
?>
<div class="site-brand">
	<?php if ( has_nav_menu( 'primary' ) ) : ?>
	<label for="menu-state" class="menu-toggle"><i class="icon-nav"></i></label>
	<?php endif; ?>
	<?php if ( has_custom_logo() ) : ?>
		<div class="site-logo"><?php the_custom_logo(); ?></div>
	<?php endif; ?>
	<?php $site_title = get_bloginfo( 'name' ); ?>
	<?php if ( ! empty( $site_title ) ) : ?>
		<?php if ( is_front_page() && is_home() ) : ?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html($site_title); ?></a></h1>
		<?php else : ?>
			<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html($site_title); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
