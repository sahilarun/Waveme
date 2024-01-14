<?php
/**
 * The header
 */
?><!doctype html>
<html <?php language_attributes(); ?> <?php ffl_html_class();?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<input type="checkbox" id="menu-state" <?php apply_filters('menu_state', ''); ?>>
	<input type="checkbox" id="search-state">
	<header id="header" class="site-header">
		<?php get_template_part( 'templates/header/site', 'navbar' ); ?>
	</header>
	<?php get_template_part( 'templates/header/site', 'sidebar' ); ?>
	<div class="backdrop"><i></i><i></i><i></i></div>
	<div id="content" <?php ffl_content_class('site-content');?>>
