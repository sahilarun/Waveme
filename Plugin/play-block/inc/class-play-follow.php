<?php

defined( 'ABSPATH' ) || exit;

class Play_Follow {

    protected static $_instance = null;

    private $user_id;
    private $meta_key_follower = 'follower';
    private $meta_key_following = 'following';

    private $type = 'user';

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
        add_action( 'the_follow_button', array( $this, 'the_follow_button' ), 10, 2 );
        add_action( 'play_follow', array( $this, 'process_request' ), 10 );

        add_filter( 'user_follow', array( $this, 'get_followers' ), 10, 3 );
        add_filter( 'user_following', array( $this, 'get_following' ), 10, 2 );
        do_action( 'play_block_follow_init', $this );
    }

    /**
     * follow
     *
     * David - Renamed to make more sense...
     */
    public function process_request( $request ) {
        $this->user_id = get_current_user_id();

        $user_id = (int) $request[ 'id' ];

        if ( ! is_user_logged_in() ) {
            Play_Utils::instance()->response( array(
                'error' => 'You need to login',
                'url'   => wp_login_url( get_author_posts_url( $user_id ) )
            ) );
        }

        if ( Play_Utils::instance()->validate_nonce( $request['nonce'] ) && $this->user_exists( $user_id ) ) {
            if ( $this->is_current_user_following( $user_id ) ) {
                $this->remove_follow( $user_id );
                $status = 0;
                do_action( 'play_block_unfollow_after_save', $user_id, $this->user_id );
            } else {
                $this->add_follow( $user_id );
                $status = 1;
                do_action( 'play_block_follow_after_save', $user_id, $this->user_id );
            }

            Play_Utils::instance()->response( array(
                'status' => $status,
                'count'  => Play_Utils::instance()->format_count( count( $this->get_followers( $user_id ) ) )
            ) );
        }
    }

    /**
     * Add a follow
     */
    public function add_follow( $user_id ) {
      $follow_id = play_add_follow( array(
        'user_id'     => $this->user_id,
        'object_id'   => $user_id,
        'object_type' => $this->type,
      ) );


      // update counts
      $following_count  = $this->get_following_count( $this->user_id );
      $follower_count   = $this->get_follower_count( $user_id );

      update_user_meta( $this->user_id, $this->meta_key_following . '_count', ++ $following_count );
      update_user_meta( $user_id, $this->meta_key_follower . '_count', ++ $follower_count );

      return apply_filters( 'play_add_follow', $follow_id, $user_id, $this );
    }

    /**
     * Remove a follow
     */
    public function remove_follow( $user_id ) {
      $follow_id = $this->is_current_user_following( $user_id );

      $ret = false;

      if ( $follow_id ) {
        $ret = play_delete_follow( $follow_id );
      }

      return apply_filters( 'play_remove_follow', $ret, $user_id, $this );
    }

    /**
     * Check if a user following
     */
    public function is_current_user_following( $user_id ) {
        $follows = play_get_follows( array(
          'number'      => 1,
          'object_id'   => $user_id,
          'object_type' => $this->type,
          'user_id'         => get_current_user_id()
        ) );

        if ( ! empty( $follows ) ) {
          return current( $follows )->id;
        }

        return false;
    }

    /**
     * Get following
     */
    public function get_following( $user_id = null, $formated = false, $show_empty = false ) {
        $user_id = ( isset( $user_id ) ) ? $user_id : get_current_user_id();

        $follows = play_get_follows( array(
          'number'      => false,
          'user_id'     => $user_id,
          'object_type' => $this->type,
          'fields'      => 'object_id',
          'orderby'     => 'id',
          'order'       => 'DESC',
        ) );

        $users = $this->sanitize_users( $follows );

        if ( empty( $users ) && $show_empty ) {
          return '0';
        }

        $users = apply_filters( 'play_get_following', $users, $user_id, $formated, $show_empty, $this );

        return $formated ? Play_Utils::instance()->format_count( count( $users ) ) : $users;
    }

    /**
     * Get follow
     */
    public function get_followers( $user_id = null, $formated = false, $show_empty = false ) {
        $user_id = ( isset( $user_id ) ) ? $user_id : get_current_user_id();

        $follows = play_get_follows( array(
          'number'      => false,
          'object_id'   => $user_id,
          'object_type' => $this->type,
          'fields'      => 'user_id',
          'orderby'     => 'id',
          'order'       => 'DESC',
        ) );

        $users = $this->sanitize_users( $follows );

        if ( empty( $users ) && $show_empty ) {
          return '0';
        }

        $users = apply_filters( 'play_get_following', $users, $user_id, $formated, $show_empty, $this );

        return $formated ? Play_Utils::instance()->format_count( count( $users ) ) : $users;
    }

    /**
     * Get post like count
     */
    public function get_follower_count( $user_id = null, $formated = false, $show_empty = false ) {
        $user_id = ( isset( $user_id ) ) ? $user_id : get_current_user_id();
        $count   = get_user_meta( $user_id, $this->meta_key_follower . '_count', true );
        if ( $count == '' && $show_empty ) {
            return '0';
        }

        // Make it possible to use the DB counts (not recommended)
        $force_count = (bool) apply_filters( 'play_force_db_follower_count', false, $count, $user_id, $this );

        if ( $force_count ) {
          $count = play_count_follows( array(
            'object_id'   => $user_id,
            'object_type' => $this->type,
          ) );
        }

        return apply_filters( 'play_follower_count', $count, $user_id, $formated, $show_empty, $this );
    }

    /**
     * Get post like count
     */
    public function get_following_count( $user_id = null, $formated = false, $show_empty = false ) {
        $user_id = ( isset( $user_id ) ) ? $user_id : get_current_user_id();
        $count   = get_user_meta( $user_id, $this->meta_key_following . '_count', true );
        if ( $count == '' && $show_empty ) {
            return '0';
        }

        // Make it possible to use the DB counts (not recommended)
        $force_count = (bool) apply_filters( 'play_force_db_following_count', false, $count, $user_id, $this );

        if ( $force_count ) {
          $count = play_count_follows( array(
            'user_id'     => $user_id,
            'object_type' => $this->type,
          ) );
        }

        return apply_filters( 'play_following_count', $count, $user_id, $formated, $show_empty, $this );
    }

    /**
     * Remove invalid
     */
    public function sanitize_users( $ids ) {

        $users = ! empty( $follows ) ? array_values( $follows ) : array();

        if ( ! empty( $users ) ) {
          foreach ( $ids as $key => $id ) {
              if ( ! $this->user_exists( $id ) ) {
                  unset( $ids[ $key ] );
              }
          }
        }

        return $ids;
    }

    /**
     * User exists
     */
    public function user_exists( $id ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d LIMIT 1", $id ) );
        if ( $count == 1 ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Display follow button
     */
    public function the_follow_button( $user_id, $class = '' ) {
        if ( $user_id == get_current_user_id() || !get_option( 'users_can_register' ) ) {
            return;
        }
        echo $this->get_follow_button( $user_id, $class );
    }

    /**
     * Get follow button
     */
    public function get_follow_button( $user_id, $class = '' ) {
        $following = $this->is_current_user_following( $user_id );
        $button       = sprintf( '<button class="btn-follow button-primary %s %s" data-id="%d" data-action="follow" data-type="user">%s</button>', esc_attr( $class ), $following ? ' active' : '', (int) $user_id, Play_Utils::instance()->get_template_html( 'blocks/follow.php' ) );

        return apply_filters( 'play_follow_button', $button, $user_id );
    }

}

Play_Follow::instance();
