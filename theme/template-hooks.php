<?php
/**
 * Hooks
 */

add_action( 'wp_enqueue_scripts', 'ffl_theme_scripts' );
if ( ! function_exists( 'ffl_theme_scripts' ) ) :
function ffl_theme_scripts() {
	wp_enqueue_style( 'ffl-custom-style', get_template_directory_uri().'/theme/theme.css', array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'ffl-theme-script', get_template_directory_uri().'/theme/theme.js', array('jquery'), wp_get_theme()->get( 'Version' ), true );
}
endif;

add_action('ffl_after_user_link', 'ffl_follow_btn');
add_action('ffl_after_author_link', 'ffl_follow_btn');
if ( ! function_exists( 'ffl_follow_btn' ) ) :
function ffl_follow_btn($user_id){
	do_action('the_follow_button', $user_id);
}
endif;

add_filter( 'loop_block_template', 'ffl_loop_block_template' );
if ( ! function_exists( 'ffl_loop_block_template' ) ) :
function ffl_loop_block_template(){
	return 'templates/loop-waveform.php';
}
endif;

add_action('ffl_after_user_link', 'ffl_edit_profile_link');
if ( ! function_exists( 'ffl_edit_profile_link' ) ) :
function ffl_edit_profile_link($user_id){
	do_action('the_edit_profile_button', $user_id);
}
endif;

add_action('ffl_after_user_description', 'ffl_follow_button');
if ( ! function_exists( 'ffl_follow_button' ) ) :
function ffl_follow_button($user_id){
	do_action('the_user_social_links', $user_id);
}
endif;

add_action('ffl_after_user_description', 'ffl_follow_links');
if ( ! function_exists( 'ffl_follow_links' ) ) :
function ffl_follow_links($user_id){
	do_action('the_user_links', $user_id);
}
endif;

if ( ! function_exists( 'ffl_release_date' ) ) :
function ffl_release_date(){
	echo sprintf('<p class="station-release">%s</p>', ffl_posted_on(false));
}
endif;

add_filter( 'more_button_svg', 'ffl_more_button_svg' );
if ( ! function_exists( 'ffl_more_button_svg' ) ) :
function ffl_more_button_svg(){
	return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"></circle><circle cx="12" cy="6" r="2"></circle><circle cx="12" cy="18" r="2"></circle></svg>';
}
endif;

add_filter( 'play_album_tracks', 'ffl_play_album_tracks' );
if ( ! function_exists( 'ffl_play_album_tracks' ) ) :
function ffl_play_album_tracks($arg){
	$arg['className'] = 'block-loop-row block-loop-index album-tracks';
	$arg['template'] = 'templates/loop-tracks.php';
	$arg['pages'] = 100;
	$arg['title'] = '';
	return $arg;
}
endif;

add_filter( 'play_user_pages', 'ffl_play_user_pages' );
if ( ! function_exists( 'ffl_play_user_pages' ) ) :
function ffl_play_user_pages(){
	return 10;
}
endif;

add_filter( 'play_archive_pages', 'ffl_play_archive_pages' );
if ( ! function_exists( 'ffl_play_archive_pages' ) ) :
function ffl_play_archive_pages(){
	return 20;
}
endif;

add_filter( 'player_theme', 'ffl_player_theme' );
if ( ! function_exists( 'ffl_player_theme' ) ) :
function ffl_player_theme(){
	return '1';
}
endif;

remove_action( 'play_single_meta', 'play_output_author', 70);
remove_action( 'play_content', 'play_output_content', 10);
remove_action( 'play_after_content', 'play_output_tracks', 10);
remove_action( 'play_after_content', 'play_output_similar', 30);
remove_action( 'play_single_meta', 'play_output_author_verified', 75);
remove_action( 'after_loop_header', 'play_output_purchase_btn', 50);
remove_action( 'play_after_single_title', 'play_output_term', 200);

add_action( 'play_after_single_header', 'play_output_waveform', 20);
add_action( 'play_after_single_header', 'play_output_tracks', 30);
add_action( 'play_after_single_title', 'play_output_info', 50);

add_action( 'play_content', 'play_output_author_bio', 40);
add_action( 'play_content', 'play_output_content', 50);

add_action( 'play_content', 'ffl_release_date', 60);
add_action( 'play_content', 'play_output_term', 70);
add_action( 'play_content', 'play_output_rank', 80);
