<?php

defined( 'ABSPATH' ) || exit;

class Play_Playlist {

    protected static $_instance = null;
    private $meta_key = 'post';
    private $post_type = 'post';
    private $post_publish = 'publish';

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
        add_action( 'play_playlist', array( $this, 'playlist' ) );
        do_action( 'play_block_playlist_init', $this );
    }

    /**
     * Save music
     */
    public function playlist( $request ) {
        $post_id = (int) $request[ 'post_id' ];
        $action  = sanitize_text_field( $request[ 'type' ] );
        $user_id = get_current_user_id();

        $this->post_type    = play_get_option( 'post_playlist_type' ) ? play_get_option( 'post_playlist_type' ) : play_get_option( 'post_type' );
        $this->post_publish = play_get_option( 'post_playlist_public' ) ? 'publish' : 'private';

        if ( play_get_option( 'post_verified_public' ) && 'true' === get_user_meta( $user_id, 'verified', true ) ) {
            $this->post_publish = 'publish';
        }

        if ( ! is_user_logged_in() ) {
            Play_Utils::instance()->response( array(
                'error' => 'You need to login',
                'url'   => wp_login_url( get_permalink( $post_id ) )
            ) );
        }

        if ( Play_Utils::instance()->validate_nonce() ) {
            switch ( $action ) {
                case 'c':
                    $data = $this->add( $post_id, $user_id, $request['title'] );
                    break;
                case 'r':
                    $data = $this->get( $user_id );
                    break;
                case 'u':
                    $data = $this->save( $post_id );
                    break;
                case 'd':
                    $data = $this->remove( $post_id );
                    break;
                default:
                    $data = $this->get( $user_id );
                    break;
            };
            Play_Utils::instance()->response(
                array(
                    'status' => 'success',
                    'data'   => $data
                )
            );
        }
    }

    /**
     * create music
     */
    private function add( $post_id, $user_id, $title ) {
        $post_title = sanitize_text_field( $title );
        if ( empty( $post_title ) ) {
            return array();
        }
        $post = array(
            'post_title'  => $post_title,
            'post_status' => $this->post_publish,
            'post_author' => (int) $user_id,
            'post_type'   => $this->post_type
        );

        $id = wp_insert_post( $post );

        $thumbnail = get_post_thumbnail_id( $post_id );
        set_post_thumbnail( $id, $thumbnail );
        update_post_meta( $id, $this->meta_key, $post_id );
        update_post_meta( $id, 'type', 'playlist' );
        $obj = array(
            'id'    => $id,
            'title' => sanitize_text_field( $post_title ),
            'url'   => get_permalink( $id ),
            'thumb' => wp_get_attachment_thumb_url( $thumbnail ),
            'post'  => array( (string) $post_id )
        );
        
        do_action( 'play_block_playlist_after_insert', $user_id, $id );
        
        return apply_filters( 'play_playlist_add', $obj, $post_id, $user_id, $this );
    }

    /**
     * read music
     */
    public function get( $user_id ) {
        $args = array(
            'post_type'      => 'any',
            'author'         => $user_id,
            'post_status'    => array( 'publish', 'private' ),
            'meta_key'       => 'type',
            'meta_value'     => 'playlist',
            'posts_per_page' => - 1
        );

        $obj   = array();
        $query = query_posts( $args );
        foreach ( $query as $post ) {
            $posts = trim( get_post_meta( $post->ID, $this->meta_key, true ) );
            if ( $posts ) {
                $posts = explode( ',', $posts );
            } else {
                $posts = array();
            }
            $obj[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
                'thumb' => wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) ),
                'post'  => $posts,
                'url'   => get_permalink( $post->ID )
            );
        }

        // Reset Query
        wp_reset_query();

        return apply_filters( 'play_playlist_get', $obj, $user_id, $this );
    }

    /**
     * update music
     */
    public function save( $post_id ) {
        $post = isset( $_REQUEST[ 'post' ] ) ? implode( ',', array_filter( $_REQUEST[ 'post' ], 'intval' ) ) : '';
        if ( $this->user_can( $post_id ) ) {
            update_post_meta( $post_id, 'post', $post );
            $user_id = get_current_user_id();
            do_action( 'play_block_playlist_after_save', $user_id, $post_id );

            return true;
        }

        return false;
    }

    /**
     * delete music
     */
    public function remove( $post_id ) {
        if ( $this->user_can( $post_id ) ) {
            wp_delete_post( $post_id, true );

            return true;
        }

        return false;
    }

    /**
     * check if the user can do
     */
    public function user_can( $post_id ) {
        $can    = false;
        $author = get_post_field( 'post_author', $post_id );
        if ( get_current_user_id() == (int) $author ) {
            $can = true;
        }

        return apply_filters( 'user_can_playlist', $can, $post_id );
    }

}

Play_Playlist::instance();
