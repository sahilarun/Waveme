<?php

// process title
function ffl_title($string){
    $arr = preg_split('/ +/', $string);
    return implode(' ', array_slice($arr, 0, ceil( count($arr)/2 ))).' <span>'.implode(' ', array_slice($arr, ceil( count($arr)/2 ), count($arr)) ).'</span>';
}
// add_filter( 'the_title', 'ffl_title' );

function ffl_excerpt_length( $length ) {
    return 20;
}
add_filter( 'excerpt_length', 'ffl_excerpt_length', 999 );

function ffl_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'ffl_excerpt_more' );

// get post thumbnail
if ( ! function_exists( 'ffl_get_post_thumbnail' ) ) :
function ffl_get_post_thumbnail($post_id, $size = 'thumbnail', $src = true){
    $img = '';
    if(!$src){
        return the_post_thumbnail();
    }
    $thumbnail_id = get_post_thumbnail_id( $post_id );
    if( !empty( $thumbnail_id ) ){
        $img = wp_get_attachment_image_url( $thumbnail_id, $size );
    }else{
        $img = get_post_meta($post_id, 'cover', true);
    }
    return apply_filters( 'ffl_get_post_thumbnail', $img, $post_id, $size, $src );
}
endif;

// add icon/theme/color to menu
function ffl_nav_menu_items( $items ) {
    foreach ( $items as $key => $item ) {
        if( strpos($item->attr_title, '?') !== false ){
            $item->url .= $item->attr_title;
            $item->attr_title = '';
        }
        foreach ($item->classes as $key => $value) {
          if(strpos($value, 'icon-') !== false){
            $item->classes[] = 'menu-has-icon';
            $icon = str_replace('icon-', '', $value);
            $item->title = ffl_get_icon_svg($icon).'<span>'.$item->title.'</span>';
            break;
          }
        }
        if ( strpos( $item->url, '%theme-switch%' ) !== false ) {
            $item->classes[] = 'menu-has-icon';
            $item->url   = '#theme';
            $item->title = '<div class="theme-switch"><i></i><span>' . $item->title . '</span></div>';
        }
        if ( strpos( $item->url, '%theme-color%' ) !== false ) {
            $item->classes[] = 'menu-has-icon';
            $item->url   = '#color';
            $item->title = '<div class="theme-color"><i></i><span>' . $item->title . '</span></div>';
        }
    }
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'ffl_nav_menu_items' );

function ffl_contains($str, array $arr) {
    foreach($arr as $a) {
        if (stripos($str, $a) !== false) return true;
    }
    return false;
}

function ffl_get_archive_title( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tax() ) {
        $title = single_term_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title =  get_the_author();
    } elseif ( is_archive() ) {
        $title = post_type_archive_title( '', false );
    }
    return $title;
}
add_filter( 'get_the_archive_title', 'ffl_get_archive_title');

// can show post thumbnail
function ffl_can_show_post_thumbnail() {
    return apply_filters( 'ffl_can_show_post_thumbnail', ! post_password_required() && ! is_attachment() && has_post_thumbnail() );
}

// related posts
if ( ! function_exists( 'ffl_related_posts' ) ) :
function ffl_related_posts(){
    global $post;
    $categories = get_the_category($post->ID);
    if ($categories) {
        $category_ids = array();
        foreach($categories as $cat) $category_ids[] = $cat->term_id;
        $args = apply_filters( 'ffl_related_posts_args', array(
            'category__in' => $category_ids,
            'post__not_in' => array($post->ID),
            'posts_per_page' =>2,
            'ignore_sticky_posts'=>1
        ) );
        $my_query = new wp_query( $args );
        if( $my_query->have_posts() ) {
            echo '<div class="archive-content archive-content-column" id="related-posts"> <h3>'.esc_html__('Related Posts','waveme').'</h3>';
            while( $my_query->have_posts() ) {
                $my_query->the_post();
                get_template_part( 'templates/content/content', 'excerpt' );
            }
            echo '</div>';
        }
    }
    wp_reset_query();
}
endif;
