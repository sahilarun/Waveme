<?php

defined( 'ABSPATH' ) || exit;

class Play_Like {

    private $site_id;
    private $meta_key;
    private $meta_key_like = 'like';
    private $meta_key_dislike = 'dislike';
    private $type = 'post';
    private $user_likes = array();

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        $this->site_id  = get_current_blog_id();
        $this->meta_key = $this->meta_key_like;

        add_action( 'init', array( $this, 'set_user_likes' ), 10 );
        add_action( 'the_like_button', array( $this, 'the_like_button' ), 10, 2 );
        add_action( 'the_dislike_button', array( $this, 'the_dislike_button' ), 10, 2 );

        add_filter( 'user_likes', array( $this, 'get_user_likes_array' ) );

        add_action( 'play_like', array( $this, 'process_request' ) );
        add_action( 'play_dislike', array( $this, 'process_request' ) );

        add_filter( 'comment_reply_link', array( $this, 'comment_like_dislike' ), 10, 4 );
        add_action( 'woocommerce_review_after_comment_text', array( $this, 'comment_woo_like_dislike' ) );

        do_action( 'play_block_like_init', $this );
    }

    /**
     * Set the user likes
     *
     * This must be done on init or get_current_user_id user fails.
     *
     * This saves us A LOT of queries on the has_user_liked() method.
     */
    public function set_user_likes() {
      if ( is_user_logged_in() ) {
        $likes = play_get_likes( array(
            'number'             => false,
            'user_id'            => get_current_user_id(),
            'orderby'            => 'id',
            'order'              => 'DESC'
        ) );

        if ( $likes ) {
            foreach( $likes as $like ) {
                $this->user_likes[ $like->object_type ][ $like->action ][ $like->object_id ] = $like->object_id;
            }
        }
      }
    }

    /**
     * like
     *
     * David - renamed to make more sense
     */
    public function process_request( $request ) {
        $id                     = absint( $request[ 'id' ] );
        $this->type             = isset( $request[ 'type' ] )   ? sanitize_text_field( $request[ 'type' ] )   : 'post';
        $this->meta_key         = isset( $request[ 'action' ] ) ? sanitize_text_field( $request[ 'action' ] ) : 'like';

        if ( ! is_user_logged_in() ) {
            Play_Utils::instance()->response( array(
                'error' => 'You need to login',
                'url'   => wp_login_url( get_permalink( $id ) )
            ) );
        }
        if ( Play_Utils::instance()->validate_nonce() ) {
            $user_id = get_current_user_id();
            if ( $this->has_user_liked( $user_id, $id ) ) {
                $this->remove_like( $user_id, $id );
                $status = 0;
                do_action( 'play_block_dislike_after_save', $user_id, $id );
            } else {
                $this->add_like( $user_id, $id );
                $status = 1;
                do_action( 'play_block_like_after_save', $user_id, $id );
            }
            Play_Utils::instance()->response( array(
                'status' => $status,
                'type'   => $this->type,
                'count'  => Play_Utils::instance()->format_count( $this->get_like_count( $id ) )
            ) );
        }
    }

    /**
     * Display like button
     */
    public function the_like_button( $id, $type = 'post', $echo = true ) {
      $this->type     = $type;
      $this->meta_key = $this->meta_key_like;
        $button       = $this->get_button( $id );
        if ( $echo ) {
            echo $button;

            return;
        }

        return $button;
    }

    /**
     * Display dislike button
     */
    public function the_dislike_button( $id, $type = 'post', $echo = true ) {
        $this->type     = $type;
        $this->meta_key = $this->meta_key_dislike;
        $button         = $this->get_button( $id );
        if ( $echo ) {
            echo $button;

            return;
        }

        return $button;
    }

    public function comment_like_dislike( $link, $args, $comment, $post ) {
        $el = sprintf( '<div class="comment-toolbar">%s</div>', $this->the_like_button( $comment->comment_ID, 'comment', false ) . $this->the_dislike_button( $comment->comment_ID, 'comment', false ) . $link );

        return apply_filters( 'play_comment_reply_link', $el, $link, $comment, $post );
    }

    public function comment_woo_like_dislike( $comment ) {
        $el = sprintf( '<div class="comment-toolbar">%s</div>', $this->the_like_button( $comment->comment_ID, 'comment', false ) . $this->the_dislike_button( $comment->comment_ID, 'comment', false ) );
        echo apply_filters( 'play_comment_woo_reply_link', $el, $comment );
    }

    /**
     * Get like button
     */
    public function get_button( $id ) {
        $count = Play_Utils::instance()->format_count( $this->get_like_count( $id ) );
        $liked = $this->has_user_liked( get_current_user_id(), $id );

        $ret = sprintf( '<button data-id="%1$s" data-action="%2$s" data-type="%3$s" class="btn-like %4$s">%5$s<span class="count">%6$s</span></button>', esc_attr( $id ), esc_attr( $this->meta_key ), esc_attr( $this->type ), $liked ? 'active' : '', $this->get_button_svg(), esc_html( $count ) );

        return apply_filters( 'play_block_get_like_button', $ret, $id, $count, $liked, $this );
    }

    public function get_button_svg() {
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
        if ( 'post' === $this->type ) {
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
        }
        if ( 'dislike' === $this->meta_key ) {
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>';
        }

        return apply_filters( 'like_button_svg', $icon, $this->type, $this->meta_key );
    }

    /**
     * Add a like
     */
    private function add_like( $user_id, $id ) {
      play_add_like( array(
        'user_id'     => $user_id,
        'object_id'   => $id,
        'object_type' => $this->type,
        'action'      => $this->meta_key
      ) );

      // Update object user likes
      $this->user_likes[ $this->type ][ $this->meta_key ][ $id ] = $id;

      // update count
      $like_count = $this->get_like_count( $id );

      $this->update_meta( $id, $this->meta_key . '_count', ++ $like_count );

      return apply_filters( 'play_add_like', $id, $user_id, $this );
    }

    /**
     * Remove a like
     */
    private function remove_like( $user_id, $id ) {
      $likes = play_get_likes( array(
        'number'        => 1,
        'object_id'   => $id,
        'object_type' => $this->type,
        'user_id'       => $user_id,
        'action'      => $this->meta_key
      ) );

      if ( ! empty( $likes ) ) {

        // Remove 'like' from database
        play_delete_like( current( $likes )->id );

        // update count
        $like_count = $this->get_like_count( $id );

        $this->update_meta( $id, $this->meta_key . '_count', max( -- $like_count, 0 ) );

        // Update object user likes
        unset( $this->user_likes[ $this->type ][ $this->meta_key ][ $id ] );

      }

      return apply_filters( 'play_remove_like', $id, $user_id, $this );
    }

    /**
     * Check if a user like a post
     */
    public function get_user_likes_raw() {
      if ( isset( $this->user_likes[ $this->type ][ $this->meta_key ] ) ) {
        $like_ids = array_map( 'absint', $this->user_likes[ $this->type ][ $this->meta_key ] );
        asort( $like_ids );
        return array_filter( array_values( $like_ids ) );
      }

      return array();
    }

    /**
     * Check if a user like a post
     */
    public function has_user_liked( $user_id, $id ) {
      return in_array( $id, $this->get_user_likes_raw() );
    }

    /**
     * Get post like count
     */
    public function get_like_count( $id ) {
        if ( $this->type === 'comment' ) {
            $count = get_comment_meta( $id, $this->meta_key . '_count', true );
        } else {
            $count = get_post_meta( $id, $this->meta_key . '_count', true );
        }
        if ( $count == '' ) {
            $count = 0;
        }

        // Make it possible to use the DB counts (not recommended for larger sites)
        $force_count = (bool) apply_filters( 'play_force_db_count', false, $count, $id, $this );

        if ( $force_count ) {
          $count = play_count_likes( array(
            'object_id'   => $id,
            'object_type' => $this->type,
            'action'      => $this->meta_key
          ) );
        }

        return apply_filters( 'play_like_count', $count, $id, $this );
    }

    /**
     * Get likes for a user
     */
    public function get_user_likes( $user_id ) {
      $likes = play_get_likes( array(
        'number'      => false,
        'user_id'     => $user_id,
        'object_type' => $this->type,
        'fields'      => 'object_id',
        'orderby'     => 'id',
        'order'       => 'DESC',
      ) );

      $likes = ! empty( $likes ) ? array_unique( array_values( $likes ) ) : array();

      return apply_filters( 'play_get_user_likes', $likes, $user_id, $this->type, $this );
    }

    /**
     * update meta
     */
    public function update_meta( $id, $key, $value ) {
      if ( 'comment' === $this->type ) {
          update_comment_meta( $id, $key, $value );
      } else {
          update_post_meta( $id, $key, $value );
      }
    }

    /**
     * Get likes for a user as array data
     */
    public function get_user_likes_array( $user_id ) {
      $likes = $this->get_user_likes( $user_id );

      return $this->remove_invalid_like( $likes );
    }

    /**
     * Remove invalid like
     */
    private function remove_invalid_like( $likes ) {
      foreach ( $likes as $key => $like ) {
        if ( ! $this->exists( $like ) ) {
          unset( $likes[ $key ] );
        }
      }

      return $likes;
    }

    /**
     * Post exists
     */
    private function exists( $id ) {
      if ( $this->type == 'comment' ) {
          $status = wp_get_comment_status( $id );
      } else {
          $status = get_post_status( $id );
      }

      return ( $status && ( 'publish' === $status || 'approved' === $status ) ) ? true : false;
    }

}

Play_Like::instance();
