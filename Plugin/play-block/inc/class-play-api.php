<?php

defined( 'ABSPATH' ) || exit;

class Play_API {

    protected static $_instance = null;
    public $namespace = 'play';
    private $endpoint = 'stream';

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        $types = play_get_option( 'play_types' );
        if ( ! empty( $types ) ) {
            foreach ( $types as $type ) {
                add_filter( 'rest_prepare_' . $type, array( $this, 'rest_prepare_post' ), 10, 3 );
            }
        }

        if(play_get_option('php_stream')){
            add_filter('play_stream_url', array( $this, 'play_stream' ), 10, 2);
        }

        add_action( 'rest_api_init', array( $this, 'set_rest' ) );
        add_action( 'init', array( $this, 'add_rewrite' ) );
        add_action( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'parse_request', array( $this, 'embed' ) );
        add_action( 'template_redirect', array( $this, 'stream' ) );

        add_filter( 'posts_search', array( $this, 'filter_search' ), 10, 2 );

        do_action( 'play_block_api_init', $this );

        function play_get_data($id){
            return Play_API::instance()->get_data($id);
        }
    }
    
    public function get_play_api_url($path = ''){
        $path = '/' . ltrim( $path, '/' );
        $url = get_rest_url( null, $this->namespace.$path );
        return apply_filters( 'play_rest_url', $url, $path );
    }

    public function play_stream($url, $id) {
        if ( strpos( $url, 'icecast' ) !== false || strpos( $url, 'shoutcast' ) !== false || strpos( $url, 'azuracast' ) !== false || strpos( $url, 'liveradio') !== false ) {
            return $url;
        }
        $uri = Play_Utils::instance()->fixURL( $url );
        if($uri){
            $permalink = get_permalink($id);
            if ( get_option( 'permalink_structure' ) ) {
                $url = trailingslashit( $permalink ) . $this->endpoint;
            } else {
                $url = add_query_arg( $this->endpoint, '', $permalink );
            }
        }
        return $url;
    }

    public function stream() {
        global $wp_query;
        if ( ! isset( $wp_query->query_vars[ $this->endpoint ] ) || isset( $_REQUEST[$this->endpoint] ) || ! is_singular() ) {
            return;
        }
        $post_id = get_the_ID();
        $url     = get_post_meta( $post_id, 'stream', true );
        $_url    = get_post_meta( $post_id, 'stream_url', true );
        if ( ! empty( $_url ) ) {
            $url = $_url;
        }

        $path = Play_Utils::instance()->getPath( $url );
        $path = apply_filters('play_block_stream_file_path', $path);

        if(!file_exists($path)){
            wp_redirect($url);
            exit;
        }

        $preview = false;
        $preview_length = play_get_option('preview_length');
        if($preview_length){
            $preview = true;
            $role = play_get_option( 'preview_free_role' );
            $role = is_array($role) ? array_filter( $role ) : $role;
            if( is_user_logged_in() && $role ){
                $user = wp_get_current_user();
                if( count( array_intersect($role, $user->roles) ) > 0 ){
                    $preview = false;
                }
            }
        }

        $preview = apply_filters('play_preview', $preview );
        $preview_length = apply_filters('play_preview_length', $preview_length );

        $stream = new Play_Stream($path, ($preview ? $preview_length : false) );
        $stream->start();
    }

    public function set_rest() {
        register_rest_route( $this->namespace, '/play/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'play' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric( $param );
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/play/items', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_items' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/proxy', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'proxy' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/search', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'search' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/follow', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'follow' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/like', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'like' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/dislike', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'dislike' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/notification', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'notification' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/comments', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'comments' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/modal', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'modal' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/playlist', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'playlist' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/auth', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'auth' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/profile', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'profile' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/upload', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'upload' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/upload/stream', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'upload_stream' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/adtag', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'adtag' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/generatepwd', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'generatepwd' ),
            'permission_callback' => '__return_true',
        ) );

    }

    public function add_rewrite() {
        add_rewrite_rule('^embed/([a-z0-9]+)/?', 'index.php?embed=$matches[1]', 'top');
        add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'embed';
        return $vars;
    }

    public function embed() {
        global $wp;
        if ( !empty( $wp->query_vars['embed'] ) ) {
            play_get_template( 'blocks/embed.php' );
            exit();
        }
    }

    public function search( $request ) {
        $search = sanitize_text_field( $request[ 'search' ] );
        $data = [];
        $posts = [];
        $users = [];

        // post search

        $_posts = get_posts( apply_filters( 'play_block_post_search_args', array(
            'post_type'        => apply_filters( 'search_post', array( 'post', 'station', 'product', 'download' ) ),
            's'                => $search,
            'numberposts'      => 10,
            'suppress_filters' => false,
            'orderby'          => 'relevance'
        )) );

        if ( ! empty( $_posts ) ) {
            foreach ( $_posts as $post ) {
                $p            = array(
                    'title'     => $post->post_title,
                    'thumbnail' => '',
                    'author'    => get_the_author_meta( 'display_name', $post->post_author ),
                    'url'       => get_permalink( $post->ID ),
                    'type'      => 'post'
                );
                if (taxonomy_exists('artist') && get_the_term_list( $post->ID, 'artist' ) ) {
                    $artist_sep = ', ';
                    $artist_link = ' & ';
                    $terms = get_the_terms( $post->ID, 'artist' );
                    $str = join($artist_sep, wp_list_pluck($terms, 'name'));
                    if( strpos($str, $artist_sep) ){
                        $str = substr_replace($str, $artist_link, strrpos($str, $artist_sep), strlen($artist_sep) );
                    }
                    $p['author'] = $str;
                }
                $thumbnail_id = get_post_thumbnail_id( $post->ID );
                if ( $thumbnail_id ) {
                    $img              = wp_get_attachment_image( $thumbnail_id );
                    $p[ 'thumbnail' ] = $img;
                }
                $posts[] = apply_filters( 'play_block_get_post_search_item', $p, $post, $request );
            }
        }

        $posts = apply_filters( 'play_block_post_search', $posts, $search, $request );

        // user search
        $_users = get_users( apply_filters( 'play_block_user_search_args', array(
            'search' => '*' . apply_filters( 'search_user', $search ) . '*'
        ) ) );

        if ( ! empty( $_users ) ) {
            foreach ( $_users as $user ) {
                $p   = array(
                    'title'     => $user->display_name,
                    'thumbnail' => '',
                    'author'    => '',
                    'url'       => get_author_posts_url( $user->ID ),
                    'type'      => 'user'
                );
                $img = get_avatar( $user->ID );
                if ( $img ) {
                    $p[ 'thumbnail' ] = $img;
                }
                $users[] = apply_filters( 'play_block_get_user_search_item', $p, $user, $request );
            }
        }

        $users = apply_filters( 'play_block_user_search', $users, $search, $request );

        $data = array_merge($posts, $users);

        return new WP_REST_Response( $data );
    }

    public function play( $request ) {
        $data  = [];
        $id    = (int) $request[ 'id' ];
        $_type = isset($request[ 'type' ]) ? sanitize_text_field( $request[ 'type' ] ) : 'post';
        if ( $_type == 'played' ){
            if ( Play_Utils::instance()->validate_nonce( $request['nonce'] ) ) {
                do_action( 'save_play_count', $id );
            }
            return new WP_REST_Response( ['msg' => 'played'] );
        }
        if ( $_type == 'user' ) {
            $args  = array(
                'post_type'  => play_get_option( 'post_type' ),
                'author'     => (int) $id,
                'fields'     => 'ids',
                'meta_query' => array(
                    array(
                        'key'     => 'type',
                        'value'   => array( 'album', 'playlist' ),
                        'compare' => 'NOT IN'
                    )
                )
            );
            $posts = get_posts( apply_filters('play_player_user_args', $args ) );
            foreach ( $posts as $key => $post_id ) {
                $data[] = $this->get_play_item( $post_id );
            }
            return new WP_REST_Response( $data );
        }
        if ( $_type == 'next' && isset( $request[ 'ids' ] ) ) {
            if( !apply_filters('play_player_auto_next', true) ){
                return;
            }
            $ids = array_filter( $request[ 'ids' ], 'intval' );

            $args = array(
                'post_type'      => play_get_option( 'play_types' ),
                'posts_per_page' => 1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'exclude'        => $ids,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'meta_key'       => 'type',
                'meta_value'     => 'single'
            );

            $tag  = apply_filters( 'play_next_tag', 'artist' );
            $tags = wp_get_post_terms( $id, $tag );

            if (!is_wp_error($tags) && !empty($tags) ) {
                // get data from same artist
                $args[ 'tax_query' ] = array();
                foreach ( $tags as $k => $v ) {
                    $term                  = $tags[ $k ];
                    $args[ 'tax_query' ][] = array(
                        'taxonomy' => $tag,
                        'field'    => 'slug',
                        'terms'    => $term->slug
                    );
                }
                if ( ! empty( $args[ 'tax_query' ] ) ) {
                    $args[ 'tax_query' ][ 'relation' ] = 'OR';
                }
            } else {
                // get data from same author
                $post             = get_post( $id );
                $args[ 'author' ] = $post->post_author;
            }

            $_id = get_posts( $args );
            if ( $_id ) {
                $data = $this->get_play_item( $_id[ 0 ] );
            }

            return new WP_REST_Response( apply_filters('play_next', $data, $id, $ids) );
        }

        $type = get_post_meta( $id, 'type', true );

        if ( in_array($type, ['album', 'playlist']) ) {
            if(isset($request[ 'ids' ])){
                $posts = $request[ 'ids' ];
            }else{
                $posts  = get_post_meta( $id, 'post', true );
                $posts = explode( ',', $posts );
            }
            foreach ( $posts as $key => $post_id ) {
                $item = $this->get_play_item( $post_id, $id );
                if ( $item ) {
                    $data[] = $item;
                }
            }
            do_action( 'save_play_count', $id );
        } else {
            $data = $this->get_play_item( $id, isset($request[ 'from' ]) ? (int) $request[ 'from' ] : 0 );
        }

        Play_Utils::instance()->response( $data );
    }

    public function get_data($id){
        $type = get_post_meta( $id, 'type', true );
        $data = [];

        if ( in_array($type, ['album', 'playlist']) ) {
            $posts  = get_post_meta( $id, 'post', true );
            $posts = explode( ',', $posts );
            foreach ( $posts as $key => $post_id ) {
                $item = $this->get_play_item( $post_id, $id );
                if ( $item ) {
                    $data[] = $item;
                }
            }
        } else {
            $data[] = $this->get_play_item( $id );
        }
        return $data;
    }

    public function follow( $request ) {
        do_action('play_follow', $request);
    }

    public function like( $request ) {
        do_action('play_like', $request);
    }

    public function dislike( $request ) {
        do_action('play_dislike', $request);
    }

    public function notification( $request ) {
        do_action('play_notification', $request);
    }

    public function playlist( $request ) {
        do_action('play_playlist', $request);
    }

    public function comments( $request ) {
        do_action('play_comments', $request);
    }

    public function upload( $request ) {
        do_action('play_upload', $request);
    }

    public function upload_stream( $request ) {
        do_action('play_upload_stream', $request);
    }

    public function auth( $request ) {
        do_action('play_auth', $request);
    }

    public function profile( $request ) {
        do_action('play_update_profile', $request);
    }

    public function adtag($request){
        $url = play_get_option( 'ad_tagurl' );
        $response = wp_remote_get($url);
        if (!is_wp_error($response) ) {
            $response_body = wp_remote_retrieve_body($response);
            $response_type = wp_remote_retrieve_header($response, 'content-type');
            
            if(strpos($response_type, 'xml') !== false){
                $data = new WP_REST_Response();
                $data->header( 'Access-Control-Allow-Origin', '*' );
                $data->header( 'Access-Control-Allow-Origin', 'https://imasdk.googleapis.com' );
                $data->header( 'Access-Control-Allow-Credentials', 'true' );
                $data->header( 'Content-Type', $response_type );
                $data->set_data($response_body);
                add_filter( 'rest_pre_serve_request', array( $this, 'server_data' ), 0, 2 );
                return $data;
            }else{
                return new WP_REST_Response($response_body);
            }
        } else {
            return new WP_Error('error');
        }
    }

    public function generatepwd($request){
        wp_send_json_success( wp_generate_password( 24 ) );
    }

    public function proxy( $request ) {
        if(isset($request[ 'url' ])){
            $response = wp_remote_get($request[ 'url' ]);
            if (!is_wp_error($response) ) {
                $response_body = wp_remote_retrieve_body($response);
                $response_type = wp_remote_retrieve_header($response, 'content-type');
                
                if(strpos($response_type, 'image') !== false){
                    $data = new WP_REST_Response();
                    $data->header( 'Accept-Ranges', 'bytes' );
                    $data->header( 'Content-Type', $response_type );
                    $data->header( 'Content-Length', wp_remote_retrieve_header($response, 'content-length') );
                    $data->set_data($response_body);
                    add_filter( 'rest_pre_serve_request', array( $this, 'server_data' ), 0, 2 );
                    return $data;
                }else{
                    return new WP_REST_Response($response_body);
                }
            } else {
                return new WP_Error('error');
            }
        }else{
            return new WP_REST_Response('No request');
        }
    }

    public function server_data( $served, $result ) {
        $data = $result->get_data();
        echo $data;
        return true;
    }

    public function modal( $request ) {
        $modal = sanitize_text_field( $request[ 'name' ] );
        $content = '';
        switch ( $modal ) {
            case 'playlist':
                $content = play_get_template_html( 'blocks/playlist.php' );
                break;
            case 'share':
                $content = play_get_template_html( 'blocks/share.php' );
                break;
            case 'remove':
                $content = play_get_template_html( 'blocks/remove.php' );
                break;
            case 'delete-account':
                $content = play_get_template_html( 'blocks/delete-account.php' );
                break;
            default:
                $content = apply_filters('play_modal_'.$modal, $request);
                break;
        }
        return new WP_REST_Response(
            array( 'content' => $content )
        );
    }

    public function get_play_item( $post_id, $from = 0 ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        $artists     = array();
        $artist      = array();
        $artist_url  = array();

        if( taxonomy_exists('artist') ){
            $artists     = wp_get_post_terms( $post_id, 'artist' );
            $artist      = array_map( function ( $value ) {
                return $value->name;
            }, $artists );
            $artist_url  = array_map( function ( $value ) {
                return get_term_link( $value->term_id );
            }, $artists );
        }

        $artwork_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ) );
        $user        = get_userdata( $post->post_author );
        $src         = get_post_meta( $post_id, 'stream', true );
        $src_url     = get_post_meta( $post_id, 'stream_url', true );
        if ( ! empty( $src_url ) ) {
            $src = $src_url;
        }

        $ext = pathinfo($src, PATHINFO_EXTENSION);
        $provider = 'html5';
        $type     = 'audio';
        if ( strpos( $src, 'youtube.com' ) !== false || strpos( $src, 'youtu.be' ) !== false ) {
            $provider = 'youtube';
            $type     = 'video';
        }
        if ( strpos( $src, 'vimeo.com' ) !== false ) {
            $provider = 'vimeo';
            $type     = 'video';
        }
        if ( strpos( $src, 'soundcloud.com' ) !== false ) {
            // fix old format
            $src = str_replace('/stream', '', $src);
            $provider = 'soundcloud';
            // use iframe
            $type   = 'video';
        }
        if ( strpos( $src, 'mixcloud.com' ) !== false ) {
            $provider = 'mixcloud';
            // use iframe
            $type   = 'video';
        }
        
        $video_types = array('avi','m3u8','mp4','mov','mpg','mpeg','m4v','mkv','ogv','rm','rmvb','webm','wmv','3gp','3g2');
        $video_types = apply_filters( 'play_block_video_types', $video_types );

        $captions = get_post_meta( $post_id, 'captions', true );
        $tracks = [];
        if($captions){
            foreach ( $captions as $key => $caption ) {
                $arr = explode(':', $caption);
                if(count($arr) > 0){
                    $label = $arr[0];
                    $source = str_replace($label.':', '', $caption);
                    if($label !=='' && $source !==''){
                        $tracks[] = array(
                            'kind' => 'captions',
                            'label' => $label,
                            'src'   => $source
                        );
                    }
                }
            }
        }

        if ( in_array( $ext, $video_types ) ) {
            $type = 'video';
        }
        if ( strpos( $src, 'audio' ) !== false ) {
            $type = 'audio';
        }
        if ( strpos( $src, 'video' ) !== false ) {
            $type = 'video';
        }

        $purchase_link = '';
        $post_type     = get_post_type( $post_id );
        if ( 'product' == $post_type && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );
            if ( '' !== $product->get_price_html() && in_array( $product->get_type(), array(
                    'simple',
                    'external'
                ) ) ) {
                $purchase_link = sprintf( '<a rel="nofollow" href="%s" class="button no-ajax add_to_cart_button product_type_%s %s" data-product_id="%s">%s</a>', esc_url( $product->add_to_cart_url() ), $product->get_type(), ( $product->get_type() == 'simple' ? 'ajax_add_to_cart' : '' ), esc_attr( $post_id ), $product->get_price_html() );
            }
        }

        $data = array(
            'id'             => $post_id,
            'uri'            => get_permalink( $post_id ),
            'title'          => $post->post_title,
            'artwork_url'    => $artwork_url == false ? '' : $artwork_url,
            'stream_url'     => apply_filters( 'play_stream_url', $src, $post_id ),
            'provider'       => $provider,
            'type'           => $type,
            'release'        => $post->post_date,
            'duration'       => get_post_meta( $post_id, 'duration', true ),
            'caption'        => get_post_meta( $post_id, 'caption', true ),
            'user'           => $user->display_name,
            'user_url'       => get_author_posts_url( $post->post_author ),
            'artist'         => implode( ',', $artist ),
            'artist_url'     => implode( ',', $artist_url ),
            'downloadable'   => Play_Download::instance()->allow_download( $post_id ),
            'download_url'   => Play_Download::instance()->get_download_url( $post_id ),
            'purchase_title' => get_post_meta( $post_id, 'purchase_title', true ),
            'purchase_url'   => get_post_meta( $post_id, 'purchase_url', true ),
            'purchase_link'  => $purchase_link,
            'like'           => Play_Like::instance()->has_user_liked( get_current_user_id(), $post_id )
        );
        
        if(count($tracks) > 0){
            $data['tracks'] = $tracks;
        }

        if($from){
            $from_item = array(
                'id'    => $from,
                'title' => get_the_title($from),
                'link'  => get_the_permalink($from),
                'type'  => get_post_meta( $from, 'type', true )
            );
            $data['from'] = $from_item;

            // allow override the track link from album link
            if( get_post_meta( $from, 'type', true ) == 'album' && apply_filters('play_from_album', false) ){
                $data['uri'] = $from_item['link'];
            }
        }

        $data = apply_filters( 'play_single_data', $data );

        return $data;
    }

    public function get_items( $request ) {
        $search    = sanitize_text_field( $request[ 'search' ] );
        $post_type = play_get_option( 'play_types' );
        //$post_type[] = 'attachment';
        $query = array(
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            's'              => $search
        );
        $type  = sanitize_text_field( $request[ 'type' ] );
        if ( $type == 'playlist' || $type == 'album' ) {
            $query[ 'meta_key' ]   = 'type';
            $query[ 'meta_value' ] = 'single';
        }
        $id = (int) $request[ 'id' ];
        if ( ! empty( $id ) ) {
            $query[ 'post__in' ] = explode( ',', $id );
            $query[ 'orderby' ]  = 'post__in';
        }
        $items = get_posts( $query );
        $data  = [];
        $i     = 0;
        foreach ( $items as $key => $item ) {
            $data[] = $item->ID . ':' . $item->post_title;
        }

        $data = apply_filters( 'play_block_get_items', $data, $search, $request );

        return new WP_REST_Response( $data );
    }

    public function rest_prepare_post( $data, $post, $context ) {
        $_data = $data->data;
        if ( isset( $_data[ 'meta' ][ 'post' ] ) && ! empty( $_data[ 'meta' ][ 'post' ] ) ) {
            $ids = explode( ',', $_data[ 'meta' ][ 'post' ] );
            if ( is_array( $ids ) && ! empty( $ids ) ) {
                $items = get_posts( array(
                    'post_type'   => 'any',
                    'post_status' => 'any',
                    'post__in'    => $ids,
                    'orderby'     => 'post__in',
                    'numberposts' => - 1
                ) );
                $arr   = [];
                foreach ( $items as $key => $item ) {
                    $arr[] = $item->ID . ':' . $item->post_title;
                }
                $_data[ 'meta' ][ 'items' ] = $arr;
            }
        }
        $data->data = $_data;

        return $data;
    }

    public function filter_search( $posts_search, $q ) {
        if ( empty( $posts_search ) ) {
            return $posts_search;
        }
        $search = esc_sql($q->query[ 's' ]);

        global $wpdb;

        add_filter( 'pre_user_query', array( $this, 'filter_user_query' ) );
        $args  = array(
            'count_total'   => false,
            'search'        => sprintf( '*%s*', $search ),
            'search_fields' => array(
                'display_name',
                'user_login',
            ),
            'fields'        => 'ID',
        );
        $users = get_users( $args );
        remove_filter( 'pre_user_query', array( $this, 'filter_user_query' ) );

        if ( ! empty( $users ) ) {
            $posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.post_author IN (" . implode( ',', array_map( 'absint', $users ) ) . ")))", $posts_search );
        }

        $post_query = $wpdb->prepare("SELECT tr.object_id 
                        FROM {$wpdb->term_relationships} tr 
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                        WHERE t.name LIKE %s OR t.slug LIKE %s", '%'.$wpdb->esc_like( $search ).'%', '%'.$search.'%');
        $ids = $wpdb->get_results( $post_query );
        $posts = [];
        foreach($ids as $id){
            $posts[] = $id->object_id;
        }

        if ( ! empty( $posts ) ) {
            $posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.ID IN (" . implode( ',', array_map( 'absint', $posts ) ) . ")))", $posts_search );
        }

        return apply_filters('play_posts_search', $posts_search);
    }

    public function filter_user_query( &$user_query ) {
        if ( is_object( $user_query ) ) {
            $user_query->query_where = str_replace( "user_nicename LIKE", "display_name LIKE", $user_query->query_where );
        }

        return $user_query;
    }
}

Play_API::instance();
