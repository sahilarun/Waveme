<?php

defined( 'ABSPATH' ) || exit;

class Play_User {

    protected static $_instance = null;
    private $users_can_register = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {

        $this->user_id = get_current_user_id();

        add_action( 'init', array( $this, 'add_endpoint' ) );
        add_filter( 'query_vars', array( $this, 'add_vars' ) );

        add_filter( 'get_user_endpoints', array( $this, 'get_user_endpoints' ), 10, 1 );
        add_filter( 'get_endpoint_url', array( $this, 'get_endpoint_url' ), 10, 3 );
        add_filter( 'wp_nav_menu_objects', array( $this, 'nav_menu_items' ), 5, 2 );
        add_filter( 'pass_user_endpoints', array( $this, 'pass_user_endpoints' ), 10, 3 );

        add_filter( 'init', array( $this, 'user_base' ), 0 );
        add_action( 'the_user_links', array( $this, 'user_links' ) );
        add_filter( 'comment_author', array( $this, 'comment_author' ) );
        add_filter( 'get_comment_author_url', array( $this, 'user_uri' ), 10, 1 );

        // add_filter( 'login_url', array( $this, 'login_url' ), 10, 2 );
        add_action( 'admin_head-nav-menus.php', array( $this, 'add_menu_meta_boxes' ) );

        add_filter( 'show_admin_bar', array( $this, 'admin_bar' ), 9999 );

        add_filter( 'template_include', array( $this, 'author_template' ), 99 );
        add_filter( 'theme_page_templates', array( $this, 'add_template_to_select' ), 10, 4 );

        add_action( 'admin_init', array( $this, 'no_dashboard' ) );

        add_filter( 'nav_count', array( $this, 'nav_count' ), 10, 2 );

        do_action( 'play_block_user_init', $this );
    }

    /**
     * user base
     */
    public function user_base() {
        global $wp_rewrite;
        $user_base = trim( play_get_option( 'user_base' ) );
        $wp_rewrite->author_base = apply_filters( 'user_base', $user_base ? $user_base : 'user' );
    }

    public function no_dashboard() {
        if ( ! current_user_can( apply_filters( 'hide_admin_role', 'publish_posts' ) ) && play_get_option( 'hide_admin' ) && ! defined( 'DOING_AJAX' ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * get user endpoints
     */
    public function get_user_endpoints( $id = null ) {
        $endpoints = array(
            'stations' => array(
                'id' => 'stations',
                'name' => play_get_text( 'stations' ),
                'public' => true
            )
        );

        if(apply_filters('play_register_audio_type', true)){
            $endpoints['albums'] = array(
                'id' => 'albums',
                'name' => play_get_text( 'albums' ),
                'public' => true
            );
        }

        if(apply_filters('play_register_video_type', false)){
            $endpoints['shots'] = array(
                'id' => 'shots',
                'name' => play_get_text( 'shots' ),
                'public' => true
            );
        }

        $epts = array(
            'playlists' => array(
                'id' => 'playlists',
                'name' => play_get_text( 'playlists' ),
                'public' => true
            ),
            'likes' => array(
                'id' => 'likes',
                'name' => play_get_text( 'likes' ),
                'public' => true
            ),
            'followers' => array(
                'id' => 'followers',
                'name' => play_get_text( 'followers' ),
                'public' => true
            ),
            'following' => array(
                'id' => 'following',
                'name' => play_get_text( 'following' ),
                'public' => true
            ),
            'download' => array(
                'id' => 'download',
                'name' => play_get_text( 'downloads' ),
                'public' => false
            ),
            'profile' => array(
                'id' => 'profile',
                'name' => play_get_text( 'profile' ),
                'public' => false
            ),
            'upload' => array(
                'id' => 'upload',
                'name' => play_get_text( 'upload' ),
                'public' => false
            ),
            'notifications' => array(
                'id' => 'notifications',
                'name' => play_get_text( 'notifications' ),
                'public' => false
            ),
            'logout' => array(
                'id' => 'logout',
                'name' => play_get_text( 'logout' ),
                'public' => false
            ),
            'delete-my-account' => array(
                'id' => 'delete-my-account',
                'name' => play_get_text( 'delete-account' ),
                'public' => false
            ),
        );



        $endpoints = apply_filters( 'user_endpoints', array_merge($endpoints, $epts) );

        $end = null;
        if($id){
            $end = $id;
        }

        foreach ( $endpoints as $key => $var ) {
            $endpoint = play_get_option($key.'_endpoint');
            if($endpoint){
                $endpoints[$key]['id'] = $endpoint;
                if($id && $endpoint === $id){
                    $end = $key;
                }
            }
        }

        if($end){
            return $end;
        }

        return $endpoints;
    }

    /**
     * add query vars
     */
    public function add_vars( $vars ) {
        if ( is_author() ) {
            $_vars = $this->get_user_endpoints();
            foreach ( $_vars as $key => $var ) {
                $vars[] = isset($var['id']) ? $var['id'] : $key;
            }
        }

        return $vars;
    }

    /**
     * add user endpoints
     */
    public function add_endpoint() {
        $_vars = $this->get_user_endpoints();
        foreach ( $_vars as $key => $var ) {
            if ( ! empty( $key ) ) {
                $endpoint = isset($var['id']) ? $var['id'] : $key;
                add_rewrite_endpoint( $endpoint, EP_AUTHORS );
            }
        }

        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'logout' ) ) {
            wp_logout();
            wp_redirect( home_url() );
            exit;
        }

        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'delete-my-account' ) ) {
            if (is_user_logged_in()) {
                require_once(ABSPATH.'wp-admin/includes/user.php');
                $user_id = get_current_user_id();
                wp_delete_user($user_id);
                wp_logout();
                wp_safe_redirect( site_url() );
                exit;
            }
        }
    }

    /**
     * get endpoint url
     */
    public function get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
        if ( ! $permalink ) {
            $permalink = get_permalink();
        }

        if ( get_option( 'permalink_structure' ) ) {
            if ( strstr( $permalink, '?' ) ) {
                $query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
                $permalink    = current( explode( '?', $permalink ) );
            } else {
                $query_string = '';
            }
            $url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
        } else {
            $url = add_query_arg( $endpoint, $value, $permalink );
        }

        if ( (int) play_get_option( 'page_upload' ) && $endpoint === 'upload' ) {
            $url = get_permalink( play_get_option( 'page_upload' ) );
        }

        return apply_filters( 'user_endpoint_url', $url, $endpoint, $value, $permalink );
    }

    /**
     * comment author url
     */
    public function comment_author( $author ) {
        global $comment;
        if ( $comment->user_id ) {
            $author = '<a href="' . get_author_posts_url( $comment->user_id ) . '">' . esc_html( $author ) . '</a>';
        }

        return $author;
    }

    /**
     * login url
     */
    public function login_url( $login_url, $redirect ) {
        if ( play_get_option( 'login_page' ) ) {
            return apply_filters( 'play_login_url', get_permalink( play_get_option( 'login_page' ) ), $redirect );
        }

        return apply_filters( 'play_login_url', $login_url, $redirect );
    }

    public function add_template_to_select( $post_templates, $wp_theme, $post, $post_type ) {
        $post_templates[ 'author.php' ] = __( 'User', 'play-block' );

        return $post_templates;
    }

    public function author_template( $template ) {
        if ( is_author() || ( get_page_template_slug() === 'author.php' ) ) {
            $new_template = Play_Utils::instance()->locate_template( 'author.php' );
            if ( ! empty( $new_template ) ) {
                return $new_template;
            }
        }

        return $template;
    }

    public function user_links( $user ) {
        $facebook  = get_user_meta( $user->ID, 'facebook', true );
        $twitter   = get_user_meta( $user->ID, 'twitter', true );
        $youtube   = get_user_meta( $user->ID, 'youtube', true );
        $instagram = get_user_meta( $user->ID, 'instagram', true );
        $whatsapp  = get_user_meta( $user->ID, 'whatsapp', true );
        $snapchat  = get_user_meta( $user->ID, 'snapchat', true );
        $url       = $user->user_url;
        $el        = '';
        if ( ! empty( $url ) ) {
            $link_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-globe"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>';
            $link_icon = apply_filters( 'play_user_link_icon', $link_icon );
            $el        .= sprintf( '<li class="website-link"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $url ), esc_url( $url ), $link_icon );
        }
        if ( ! empty( $facebook ) ) {
            $facebook_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>';
            $facebook_icon = apply_filters( 'play_user_facebook_icon', $facebook_icon );
            $el            .= sprintf( '<li class="social-facebook"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $facebook ), esc_url( $facebook ), $facebook_icon );
        }
        if ( ! empty( $twitter ) ) {
            $twitter_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-twitter"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>';
            $twitter_icon = apply_filters( 'play_user_twitter_icon', $twitter_icon );
            if (filter_var($twitter, FILTER_VALIDATE_URL) == false) {
                $twitter = 'https://twitter.com/'.$twitter;
            }
            $el           .= sprintf( '<li class="social-twitter"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $twitter ), esc_url( $twitter ), $twitter_icon );
        }
        if ( ! empty( $youtube ) ) {
            $youtube_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-youtube"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>';
            $youtube_icon = apply_filters( 'play_user_youtube_icon', $youtube_icon );
            $el           .= sprintf( '<li class="social-youtube"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $youtube ), esc_url( $youtube ), $youtube_icon );
        }
        if ( ! empty( $instagram ) ) {
            $instagram_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon feather feather-instagram"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>';
            $instagram_icon = apply_filters( 'play_user_instagram_icon', $instagram_icon );
            $el             .= sprintf( '<li class="social-instagram"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $instagram ), esc_url( $instagram ), $instagram_icon );
        }
        if ( ! empty( $whatsapp ) ) {
            $whatsapp_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" class="svg-icon" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg>';
            $whatsapp_icon = apply_filters( 'play_user_whatsapp_icon', $whatsapp_icon );
            $el            .= sprintf( '<li class="social-whatsapp"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $whatsapp ), esc_url( $whatsapp ), $whatsapp_icon );
        }
        if ( ! empty( $snapchat ) ) {
            $snapchat_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" class="svg-icon" viewBox="0 0 512 512"><path fill="currentColor" d="M510.846 392.673c-5.211 12.157-27.239 21.089-67.36 27.318-2.064 2.786-3.775 14.686-6.507 23.956-1.625 5.566-5.623 8.869-12.128 8.869l-.297-.005c-9.395 0-19.203-4.323-38.852-4.323-26.521 0-35.662 6.043-56.254 20.588-21.832 15.438-42.771 28.764-74.027 27.399-31.646 2.334-58.025-16.908-72.871-27.404-20.714-14.643-29.828-20.582-56.241-20.582-18.864 0-30.736 4.72-38.852 4.72-8.073 0-11.213-4.922-12.422-9.04-2.703-9.189-4.404-21.263-6.523-24.13-20.679-3.209-67.31-11.344-68.498-32.15a10.627 10.627 0 0 1 8.877-11.069c69.583-11.455 100.924-82.901 102.227-85.934.074-.176.155-.344.237-.515 3.713-7.537 4.544-13.849 2.463-18.753-5.05-11.896-26.872-16.164-36.053-19.796-23.715-9.366-27.015-20.128-25.612-27.504 2.437-12.836 21.725-20.735 33.002-15.453 8.919 4.181 16.843 6.297 23.547 6.297 5.022 0 8.212-1.204 9.96-2.171-2.043-35.936-7.101-87.29 5.687-115.969C158.122 21.304 229.705 15.42 250.826 15.42c.944 0 9.141-.089 10.11-.089 52.148 0 102.254 26.78 126.723 81.643 12.777 28.65 7.749 79.792 5.695 116.009 1.582.872 4.357 1.942 8.599 2.139 6.397-.286 13.815-2.389 22.069-6.257 6.085-2.846 14.406-2.461 20.48.058l.029.01c9.476 3.385 15.439 10.215 15.589 17.87.184 9.747-8.522 18.165-25.878 25.018-2.118.835-4.694 1.655-7.434 2.525-9.797 3.106-24.6 7.805-28.616 17.271-2.079 4.904-1.256 11.211 2.46 18.748.087.168.166.342.239.515 1.301 3.03 32.615 74.46 102.23 85.934 6.427 1.058 11.163 7.877 7.725 15.859z"></path></svg>';
            $snapchat_icon = apply_filters( 'play_user_snapchat_icon', $snapchat_icon );
            $el            .= sprintf( '<li class="social-whatsapp"><a href="%s" title="%s" target="_blank">%s</a></li>', esc_url( $snapchat ), esc_url( $snapchat ), $snapchat_icon );
        }
        if ( ! empty( $el ) ) {
            echo sprintf( '<ul class="user-links">%s</ul>', apply_filters( 'user_social', $el ) );
        }
    }

    /**
     * admin bar
     */
    public function admin_bar() {
        $show = false;
        if ( play_get_option( 'show_admin_bar' ) && current_user_can( 'administrator' ) ) {
            $show = true;
        }

        return apply_filters( 'play_admin_bar', $show );
    }

    /**
     *
     */
    public function user_uri( $uri ) {
        global $comment;

        if ( empty ( $comment )
             or ! is_object( $comment )
             or empty ( $comment->comment_author_email )
             or ! $user = get_user_by( 'email', $comment->comment_author_email )
        ) {
            return $uri;
        }

        return get_author_posts_url( $user->ID );
    }

    public function nav_count( $user_id, $endpoint ) {
        $count = '';
        switch ( $endpoint ) {
            case 'stations':
            case 'product':
            case 'downloads':
            case 'playlists':
            case 'albums':
            case 'shots':
                $type = 'single';
                if ( 'playlists' === $endpoint ) {
                    $type = 'playlist';
                }
                if ( 'albums' === $endpoint ) {
                    $type = 'album';
                }
                if ( 'shots' === $endpoint ) {
                    $type = 'shot';
                }
                $post_type = play_get_option( 'play_types' );
                if ( in_array( $endpoint, array( 'station', 'product', 'download' ) ) ) {
                    $post_type = $endpoint;
                }
                $args = array(
                    'post_type'      => $post_type,
                    'author'         => $user_id,
                    'post_status'    => 'publish',
                    'meta_key'       => 'type',
                    'meta_value'     => $type,
                    'posts_per_page' => - 1
                );

                $query = new WP_Query( $args );
                $count = $query->found_posts;
                break;
            case 'followers':
                $count = count( apply_filters( 'user_follow', $user_id ) );
                break;
            case 'following':
                $count = count( apply_filters( 'user_following', $user_id ) );
                break;
            case 'likes':
                $count = count( apply_filters( 'user_likes', $user_id ) );
                break;
            case 'download':
                $count = count( apply_filters( 'user_download', $user_id ) );
                break;
            default:
                # code...
                break;
        }

        $count = apply_filters( 'play_nav_count', $count, $user_id, $endpoint, $this );

        if ( $count !== '' ) {
            $count = '<span>' . Play_Utils::instance()->format_count( $count ) . '</span>';
        }

        return $count;
    }

    public function pass_user_endpoints( $endpoint, $user_id, $item = NULL ) {
        $pass = false;

        // pass logout
        if ( in_array($endpoint, array('logout','delete-my-account')) ) {
            $pass = true;
        }

        // other user can not see
        if ( get_current_user_id() !== $user_id && in_array( $endpoint, array( 'profile', 'upload', 'download', 'notifications' ) ) ) {
            $pass = true;
        }

        // user can use the upload and station
        $role = play_get_option( 'upload_role' );
        $roles = is_array($role) ? array_filter( $role ) : array('administrator','editor','author','contributor');
        
        $user = get_userdata($user_id);
        $can  = false;
        
        if($user && count( array_intersect($roles, $user->roles) ) > 0 ){
            $can = true;
        }

        if ( !$can && in_array( $endpoint, apply_filters('play_pass_user_endpoints', array('stations','albums','upload', 'product') ) ) ) {
            $pass = true;
        }

        if ( get_current_user_id() !== $user_id && $item && $item->object == 'page' ) {
            $pass = true;
            if( in_array('menu-public', $item->classes) ){
                $pass = false;
            }
        }

        return apply_filters( 'pass_user_endpoint', $pass, $endpoint, $user_id, $item );
    }

    public function nav_menu_items( $items, $args ) {
        global $wp_rewrite;
        foreach ( $items as $key => $item ) {
            if ( strpos( $item->url, '%site_url%' ) !== false ) {
                $item->url = str_replace( array( 'http://', 'https://' ), '', $item->url );
                $item->url = str_replace( '%site_url%', site_url(), $item->url );
            }

            if ( strpos( $item->url, '%user_base%' ) !== false ) {
                $item->url = str_replace( '%user_base%', $wp_rewrite->author_base, $item->url );
            }

            if ( strpos( $item->title, '%avatar%' ) !== false ) {
                if ( is_user_logged_in() ) {
                    $user            = wp_get_current_user();
                    $item->classes[] = 'menu-avatar';
                    $item->title     = '<span class="user-display-name">' . $user->display_name . '</span>' . get_avatar( $user->ID );
                }
            }

            $icon = apply_filters('icon_cart_svg', '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>');

            if ( class_exists( 'Easy_Digital_Downloads' ) ) {
              if ( strpos( $item->url, '%edd-cart%' ) !== false ) {
                $item->url        = edd_get_checkout_uri();
                $item->classes[]  = 'menu-cart';
                $item->title      = sprintf( '%s <span class="cart-quantity edd-cart-quantity">%s</span>', $icon, esc_attr( edd_get_cart_quantity() ) );
              }
            }

            if( class_exists( 'WooCommerce' ) ){
              if ( strpos( $item->url, '%woo-cart%' ) !== false ) {
                $item->url        = wc_get_cart_url();
                $item->classes[]  = 'menu-cart';
                $item->title      = sprintf( '%s <span class="cart-quantity woo-cart-quantity">%s</span>', $icon, esc_attr( wc()->cart->get_cart_contents_count() ) );
              }
            }

            if ( in_array( $args->theme_location, array( 'user', 'after_login' ) ) ) {
                $user_id = get_queried_object_id();
                // reset to current user_id
                if ( false === is_author() || 'after_login' === $args->theme_location ) {
                    if ( is_user_logged_in() ) {
                        $user_id = get_current_user_id();
                    }
                }
                $user     = get_userdata( $user_id );
                $endpoint = basename( $item->url );
                $pass     = $this->pass_user_endpoints( $endpoint, $user_id, $item );

                if ( $pass ) {
                    if ( 'logout' === $endpoint && 'after_login' === $args->theme_location ) {
                        $item->classes[] = 'no-ajax';
                        $item->url       = str_replace( '%user%', $user->user_nicename, $item->url );
                        continue;
                    }
                    unset( $items[ $key ] );
                    continue;
                }

                global $wp;
                if ( $user && 'user' === $args->theme_location ) {
                    $active          = isset( $wp->query_vars[ $endpoint ] ) ? 'active' : '';
                    $count           = apply_filters( 'nav_count', $user_id, $endpoint );
                    $item->title     .= $count;
                    $item->classes[] = 'sub-ajax ' . $active;
                    $item->url       = str_replace( '%user%', $user->user_nicename, $item->url );
                }
            }

            if ( strpos( $item->url, '%user%' ) !== false ) {
                if ( is_user_logged_in() ) {
                    $user      = wp_get_current_user();
                    $item->url = str_replace( '%user%', $user->user_nicename, $item->url );

                    if( (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] == $item->url ){
                        $item->classes[] = 'current-menu-item';
                    }
                } else {
                    $item->url = get_the_permalink( play_get_option( 'page_login' ) );
                }
            }
        }

        return $items;
    }

    public function add_menu_meta_boxes() {
        add_meta_box( 'user_endpoints_nav_link', __( 'User endpoints' ), array(
            $this,
            'menu_links'
        ), 'nav-menus', 'side', 'low' );
    }

    /**
     * Output menu links.
     */
    public function menu_links() {
        // Get items from account menu.
        $endpoints = $this->get_user_endpoints();

        $endpoints = apply_filters( 'user_menu_items', $endpoints );

        global $wp_rewrite;
        
        ?>
        <div id="posttype-user-endpoints" class="posttypediv">
            <div id="tabs-panel-user-endpoints" class="tabs-panel tabs-panel-active">
                <ul id="user-endpoints-checklist" class="categorychecklist form-no-clear">
                    <?php
                    $i = - 1;
                    foreach ( $endpoints as $key => $var ) :
                        if($key == 'delete-my-account'){
                            continue;
                        }
                        $endpoint = isset($var['id']) ? $var['id'] : $key;
                        $name = isset($var['name']) ? $var['name'] : $var;
                        $url = $this->get_endpoint_url( $endpoint, '', get_author_posts_url( 0 ) . '%user%' );
                        $url = str_replace('/'.$wp_rewrite->author_base.'/', '/%user_base%/', $url);
                        $url = str_replace( site_url(), '%site_url%', $url );

                        ?>
                        <li>
                            <label class="menu-item-title">
                                <input type="checkbox" class="menu-item-checkbox"
                                       name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]"
                                       value="<?php echo esc_attr( $i ); ?>"/> <?php echo esc_html( $name ); ?>
                            </label>
                            <input type="hidden" class="menu-item-type"
                                   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom"/>
                            <input type="hidden" class="menu-item-title"
                                   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]"
                                   value="<?php echo esc_html( $name ); ?>"/>
                            <input type="hidden" class="menu-item-url"
                                   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]"
                                   value="<?php echo esc_url( $url ); ?>"/>
                            <input type="hidden" class="menu-item-classes"
                                   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]"/>
                        </li>
                        <?php
                        $i --;
                    endforeach;
                    ?>
                </ul>
            </div>
            <p class="button-controls">
                <span class="list-controls">
                    <a href="<?php echo esc_url( admin_url( 'nav-menus.php?page-tab=all&selectall=1#posttype-user-endpoints' ) ); ?>"
                       class="select-all"><?php esc_html_e( 'Select all' ); ?></a>
                </span>
                <span class="add-to-menu">
                    <button type="submit" class="button-secondary submit-add-to-menu right"
                            value="<?php esc_attr_e( 'Add to menu' ); ?>" name="add-post-type-menu-item"
                            id="submit-posttype-user-endpoints"><?php esc_html_e( 'Add to menu' ); ?></button>
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }

}

Play_User::instance();
