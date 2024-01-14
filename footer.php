<?php
/**
 * The footer
 */
?>
	</div>
<footer id="footer" class="site-footer">
	<?php get_template_part( 'templates/footer/site', 'footer' ); ?>
</footer>
<?php 
	if ( has_nav_menu( 'mobile' ) ) : ?>
	<nav id="mobile-menu" class="mobile-menu">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'mobile',
				'menu_class'     => 'nav',
				'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
		?>
	</nav>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
