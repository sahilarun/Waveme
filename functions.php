<?php
update_option('envato_purchase_code','xxxxxxx');
if ( ! function_exists( 'ffl_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function ffl_setup() {
	/*
	 * Make theme available for translation.
	 */
	load_theme_textdomain( 'waveme', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary'    	 => __( 'Primary Menu', 'waveme' ),
		'secondary'		 => __( 'Secondary Menu', 'waveme' ),
		'user'   	     => __( 'User Menu', 'waveme' ),
		'before_login'   => __( 'Before Login Menu', 'waveme' ),
		'after_login'    => __( 'After Login Menu', 'waveme' )
	) );
	
	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'script', 
		'style'
	) );

	add_theme_support(
		'custom-logo'
	);

	// Add support for Block Styles.
	add_theme_support( 'wp-block-styles' );

	// Add support for full and wide align images.
	add_theme_support( 'align-wide' );

	// Add support for editor styles.
	add_theme_support( 'editor-styles' );

	// Add support for responsive embedded content.
	add_theme_support( 'responsive-embeds' );

	// Indicate widget sidebars can use selective refresh in the Customizer.
	add_theme_support( 'customize-selective-refresh-widgets' );

	if ( ! isset( $content_width ) ) { $content_width = 600; }
	
}
endif;
add_action( 'after_setup_theme', 'ffl_setup' );

function ffl_styles() {
	wp_enqueue_style( 'ffl-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'ffl_styles' );

get_template_part( 'theme/template-hooks' );
get_template_part( 'theme/template-plugins' );

function ffl_scripts() {
	$suffix = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
	wp_enqueue_script( 'ffl-pjax', get_template_directory_uri().'/assets/js/pjax.min.js', array('jquery'), wp_get_theme()->get( 'Version' ), true );
	wp_enqueue_script( 'ffl-js', get_template_directory_uri().'/assets/js/site'.$suffix.'.js', array('jquery'), wp_get_theme()->get( 'Version' ), true );
	// enqueue WordPress comment reply js.
	wp_enqueue_script( 'comment-reply' );
}
add_action( 'wp_enqueue_scripts', 'ffl_scripts' );

require_once get_template_directory() . '/includes/template-tags.php';
require_once get_template_directory() . '/includes/template-functions.php';
require_once get_template_directory() . '/includes/template-customize.php';
require_once get_template_directory() . '/includes/template-icons.php';

if( class_exists( 'WooCommerce' ) ){
	require_once get_template_directory() . '/woocommerce/woo.php';
}

if( class_exists( 'Easy_Digital_Downloads' ) ){
	require_once get_template_directory() . '/easy-digital-downloads/edd.php';
}
