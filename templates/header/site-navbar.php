<?php
/**
 * Displays header site navbar
 */
?>
<!-- <div class="site-headbar">
	<p>Mock header, user can add custom html here</p>
</div> -->
<div class="header-container">
	<div class="site-navbar">
		<?php get_template_part( 'templates/header/site', 'brand' ); ?>
		<div class="flex"></div>
		<form class="search-form" method="get" action="<?php echo esc_url(home_url()); ?>">
			<input type="search" placeholder="<?php esc_attr_e('Search...','waveme'); ?>" value="" name="s" data-toggle="dropdown" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
			<label for="search-state" id="icon-search">
				<i class="icon-search"><i></i></i>
			</label>
			<div class="dropdown-menu"></div>
		</form>
		<div class="flex"></div>
		<?php if ( has_nav_menu( 'secondary' ) ) : ?>
			<nav id="secondary-menu" class="secondary-menu">
				<label id="icon-nav"> ⋯ </label>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'secondary',
						'menu_class'     => 'nav',
						'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( !is_user_logged_in() && has_nav_menu( 'before_login' ) ) : ?>
			<nav class="menu-before-login">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'before_login',
						'menu_class'     => 'nav',
						'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					)
				);
				?>
			</nav>
		<?php endif; ?>
		<?php if ( is_user_logged_in() && has_nav_menu( 'after_login' ) ) : ?>
			<nav class="menu-after-login">
				<?php
				do_action('menu_after_login_before');
				wp_nav_menu(
					array(
						'theme_location' => 'after_login',
						'menu_class'     => 'nav',
						'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					)
				);
				do_action('menu_after_login_after');
				?>
			</nav>
		<?php endif; ?>
	</div>
</div>
