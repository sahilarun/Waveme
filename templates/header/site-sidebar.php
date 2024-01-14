<?php
/**
 * Displays header site sidebar
 */
?>

<?php if ( has_nav_menu( 'primary' ) ) : ?>
	<aside id="aside" class="site-sidebar">
		<?php get_template_part( 'templates/header/site', 'brand' ); ?>
		<?php get_template_part( 'templates/header/site', 'sideheader' ); ?>
		<nav id="primary-menu" class="primary-menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'nav',
					'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					'header' => true
				)
			);
			?>
		</nav>
		<span class="flex"></span>
		<?php get_template_part( 'templates/header/site', 'sidefooter' ); ?>
	</aside>
<?php endif; ?>
