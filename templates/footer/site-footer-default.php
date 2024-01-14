<?php
/**
 * Displays the default footer 
 */

?>
<div class="container">
	<div class="site-info">
		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<div class="site-logo"><?php the_custom_logo(); ?></div>
			<?php endif; ?>
			<?php $site_title = get_bloginfo( 'name' ); ?>
			<?php if ( ! empty( $site_title ) ) : ?>
				<div>
					<?php if ( is_front_page() && is_home() ) : ?>
						<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html($site_title); ?></a></h1>
					<?php else : ?>
						<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html($site_title); ?></a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div><!-- .site-brand -->
		<div class="site-copyright">
			<?php esc_html_e('&copy; Copyright','waveme'); ?>
			<?php bloginfo( 'name' ); ?>
			<?php echo date('Y'); ?>
		</div><!-- .site-copyright -->
	</div>
</div>