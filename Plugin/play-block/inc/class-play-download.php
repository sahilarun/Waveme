<?php

defined( 'ABSPATH' ) || exit;

class Play_Download {

    private $user_id;
    private $meta_key = 'download_count';
    private $endpoint = 'd';
    private $type = 'post';

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Initialize the actions.
     */
    public function __construct() {
        $this->user_id  = get_current_user_id();
        $this->endpoint = apply_filters( 'play_download_endpoint', $this->endpoint );
        add_action( 'init', array( $this, 'add_rewrite' ) );
        add_action( 'template_redirect', array( $this, 'download' ) );
        add_action( 'the_download_button', array( $this, 'the_download_button' ), 10, 1 );
        add_action( 'the_purchase_button', array( $this, 'the_purchase_button' ), 10, 1 );

        add_filter( 'user_download', array( $this, 'get_user_downloads' ), 10, 3 );

        add_shortcode( 'play_download', array( $this, 'play_download_shortcode' ) );

        do_action( 'play_block_download_init', $this );
    }

    public function add_rewrite() {
        add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
    }

    public function the_download_button( $id ) {
        echo $this->download_button( $id );
    }

    public function the_purchase_button( $id ) {
        echo $this->purchase_button( $id );
    }

    public function purchase_button( $id ) {
        $url = get_post_meta( $id, 'purchase_url', true );
        $txt = get_post_meta( $id, 'purchase_title', true );
        if ( empty( $url ) || empty( $txt ) ) {
            return false;
        }
        return sprintf( '<a href="%1$s" target="_blank" class="btn-purchase no-ajax"><span>%2$s</span></a>', esc_url( $url ), esc_html( $txt ) );
    }

    public function download_button( $id ) {
        if ( ! $this->allow_download( $id ) ) {
            return false;
        }
        $class = ( play_get_option( 'downloadable' ) && ! is_user_logged_in() ) ? 'btn-ajax-login' : '';
        $attr = apply_filters('play_download_button_attr', $id);

        return sprintf( '<a href="%s" target="_blank" class="btn-download no-ajax %s" data-url="%s" %s>%s <span class="count">%s</span></a>', $this->get_download_url( $id ), esc_attr( $class ), esc_attr( get_permalink( $id ) ), $attr, $this->get_download_button_svg(), $this->get_download_count( $id ) );
    }

    public function get_user_downloads( $user_id = null, $formated = false, $show_empty = false ) {
        $user_id   = ( isset( $user_id ) ) ? $user_id : get_current_user_id();

        $downloads = play_get_downloads( array(
          'number'      => false,
          'user_id'     => $user_id,
          'object_type' => $this->type,
          'fields'      => 'object_id',
          'orderby'     => 'id',
          'order'       => 'DESC',
        ) );

        $downloads = array_unique( $downloads );

        $downloads = apply_filters( 'play_user_download', $downloads, $user_id, $formated, $show_empty, $this );

        return $formated ? Play_Utils::instance()->format_count( count( $downloads ) ) : $downloads;
    }

    private function get_download_count( $id ) {
        return Play_Utils::instance()->format_count( (int) get_post_meta( $id, $this->meta_key, true ) );
    }

    public function get_download_button_svg() {
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="svg-icon"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>';

        return apply_filters( 'download_button_svg', $icon );
    }

    public function get_download_url( $id ) {
        $url = '';
        if ( play_get_option( 'downloadable' ) && ! is_user_logged_in() ) {
            $url = wp_login_url( get_permalink( $id ) );
        } elseif ( play_get_option( 'page_download' ) ) {
            $url = get_permalink( play_get_option( 'page_download' ) ) . '?id=' . $id . '&nonce=' . wp_create_nonce( 'wp_rest' );
        } else {
            $url = $this->get_download_permalink( $id );
        }

        return apply_filters( 'play_block_download_url', $url, $id );
    }

    private function get_download_file( $id ) {
        $url = false;
        if ( $this->allow_download( $id ) ) {
            $url          = get_post_meta( $id, 'stream', true );
            $download_url = get_post_meta( $id, 'download_url', true );
            if ( trim( $download_url ) !== '' ) {
                $url = $download_url;
            }
        }

        return apply_filters( 'play_block_download_file', $url, $id );
    }

    private function get_download_permalink( $id ) {
        $permalink = get_permalink( $id );
        if ( get_option( 'permalink_structure' ) ) {
            $url = trailingslashit( $permalink ) . $this->endpoint;
        } else {
            $url = add_query_arg( $this->endpoint, '', $permalink );
        }
        $url = add_query_arg( array( 'nonce' => wp_create_nonce( 'wp_rest' ) ), $url );

        return apply_filters( 'play_block_download_permalink', $url, $id );
    }

    public function allow_download( $id ) {
        $downloadable = get_post_meta( $id, 'downloadable', true );
        $author = get_post_field( 'post_author', $id );
        if( $downloadable ){
            $type = get_post_type( $id );
            $user_id = get_current_user_id();

            $role = play_get_option( 'download_role' );
            $role = is_array($role) ? array_filter( $role ) : $role;
            if( $role ){
                $user = wp_get_current_user();
                if( count( array_intersect($role, $user->roles) ) > 0 ){
                    $downloadable = true;
                }else{
                    $downloadable = false;
                }
            }

            if( 'product' === $type  && function_exists( 'wc_customer_bought_product' ) ){
                if( !is_user_logged_in() ){
                    $downloadable = false;
                }
                $user = wp_get_current_user();
                $user_id = get_current_user_id();
               if( !wc_customer_bought_product( $user->email, $user_id, $id ) ){
                    $downloadable = false;
               }
            }

            if( 'download' === $type && function_exists( 'edd_has_user_purchased' ) ){
                if( !is_user_logged_in() ){
                    $downloadable = false;
                }
                $user_id = get_current_user_id();
                if( !edd_has_user_purchased( $user_id, $id ) ) {
                    $downloadable = false;
                }
            }

            if( $user_id == $author ){
                $downloadable = true;
            }
        }

        return apply_filters( 'play_block_downloadable', $downloadable, $id );
    }

    public function play_download_shortcode($attr = [], $content = null, $tag = '') {
        $id = isset( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : ( isset($attr['id']) ? $attr['id'] : false );
        if ( $id ) {
            $url = $this->get_download_permalink( (int)$id );
            if(isset($attr['button'])){
                return sprintf('<p><a href="%s" class="button">%s</a></p>', esc_url($url), esc_html( isset($attr['download']) ? $attr['download'] : play_get_text('download') ) );
            }else{
                $delay = isset($attr['delay']) ? (int) $attr['delay'] : 5000;
                return '<script> jQuery(document).ready(function(){ setTimeout( function(){window.location="' . esc_url( $url ) . '"}, '.esc_html($delay).') }); </script> ';
            }
        }
    }

    public function download() {
        global $wp_query;

        if ( ! isset( $wp_query->query_vars[ $this->endpoint ] ) || ! is_singular() || ! isset( $_REQUEST[ 'nonce' ] ) ) {
            return;
        }

        $id = get_the_ID();

        $type = get_post_meta($id, 'type', true);
        if(in_array($type, ['playlist', 'album'])){
            $download_url = get_post_meta( $id, 'download_url', true );
            if($download_url){
                $this->download_file( $id );
            }else{
                ob_clean();
                $posts = get_post_meta( $id, 'post', true );
                $posts = explode(',', $posts);
                foreach($posts as $post_id){
                    echo sprintf('<iframe height="0" src="%s" frameBorder="0" scrolling="no"></iframe>', esc_url( $this->get_download_url($post_id)) );
                }
                exit;
            }
        }else{
            $this->download_file( $id );
        }
    }

    public function get_filename($url, $type){
        $path = parse_url($url, PHP_URL_PATH);
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        if($ext){
            return basename($url);
        }

        $types = wp_get_mime_types();
        $types['m4a'] = 'audio/x-m4a';
        $types = apply_filters('play_download_types', $types);
        foreach($types as $k => $v){
            if($v == $type){
                $arr = explode('|', $k);
                $ext = $arr[0];
                break;
            }
        }

        return basename($url).'.'.$ext;
    }

    public function download_file( $id ) {
        $should_allow_download = apply_filters( 'play_block_should_allow_download_file', true, $id, $this );
        if ( false === $should_allow_download ) {
          return;
        }

        $url = $this->get_download_file( $id );

        // update the downloads
        $count = (int) get_post_meta( $id, $this->meta_key, true );
        update_post_meta( $id, $this->meta_key, $count + 1 );

        if ( is_user_logged_in() ) {
            $user_id   = get_current_user_id();

            $download_id = play_add_download( array(
              'user_id'     => $user_id,
              'object_id'   => $id,
              'object_type' => $this->type,
              'url'         => $url,
              'ip'          => isset( $_SERVER['REMOTE_ADDR'] )     ? $_SERVER['REMOTE_ADDR']     : '',
              'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ) );

            do_action( 'play_block_download_after_save', $user_id, $id, $download_id );
        }

        $uri = Play_Utils::instance()->fixURL( $url );

        if ( $uri ) {
            $url = $uri;
            global $wp_filesystem;
            require_once( ABSPATH . '/wp-admin/includes/file.php' );
            WP_Filesystem();

            $fileinfo        = pathinfo( $url );
            $not_allowed_ext = apply_filters( 'play_block_download_disallowed_exts', array( 'php' ) );
            if ( in_array( $fileinfo[ 'extension' ], $not_allowed_ext ) ) {
                die( 'This file type is forbidden.' );
            }

            $path = Play_Utils::instance()->getPath( $url );
            $path = apply_filters('play_block_download_file_path', $path);

            if ( $wp_filesystem->exists( $path ) ) {
                $url = $path;
            }
            header( 'Content-type: application/x-file-to-save');
            header( 'Content-disposition: attachment; filename="' . basename( $url ) . '"' );
            ob_clean();
            flush();
            echo $wp_filesystem->get_contents( $url );
            exit();
        } else {
            // get file from remote
            if(apply_filters('play_download_remote_url', true)){
                $response = wp_remote_get($url);
                if (!is_wp_error($response) ) {
                    // wp_get_mime_types
                    $type = wp_remote_retrieve_header($response, 'content-type');
                    $filename = $this->get_filename($url, $type);
                    header( 'Content-type: application/x-file-to-save');
                    header( 'Content-disposition: attachment; filename="' . $filename . '"' );
                    ob_clean();
                    flush();
                    echo $response['body'];
                    exit();
                }
            }
            // rediect
            header( 'Location: ' . $url );
            exit();
        }
    }

}

Play_Download::instance();
