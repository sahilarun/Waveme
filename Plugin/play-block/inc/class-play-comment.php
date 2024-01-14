<?php

defined( 'ABSPATH' ) || exit;

class Play_Comment {

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
        add_filter( 'comments_attr', array( $this, 'comments_attr' ) );
        add_filter( 'comments', array( $this, 'comments' ) );
        add_filter( 'comments_pagination', array( $this, 'comments_pagination' ) );

        add_action( 'play_comment', array( $this, 'comment' ), 10 );
        add_action( 'play_comments', array( $this, 'rest_comments' ) );
    }

    public function comments_attr() {
        echo ' data-plugin="scroller"';
    }

    public function comments_pagination() {
        return '';
    }

    public function comments( $comments ) {
        return $this->get_comment( $comments );
    }

    public function comment() {
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
    }

    public function rest_comments( $request ) {
        $content = $this->get_comment(null, $request);
        return wp_send_json( array( 'content' => $content ) );
    }
    
    public function get_comment( $comments = null, $request = null ) {
        $post_id = get_the_ID();
        if ( isset( $request[ 'post_id' ] ) ) {
            $post_id = intval( $request[ 'post_id' ] );
        }

        $cpage = get_query_var( 'cpage' ) ? get_query_var( 'cpage' ) : 1;
        if ( isset( $request[ 'cpage' ] ) ) {
            $cpage = intval( $request[ 'cpage' ] );
        }
        $page     = $cpage;
        $per_page = (int) get_option( 'comments_per_page' );
        if ( 0 === $per_page ) {
            $per_page = 1;
        }
        $default_comments_page = get_option( 'default_comments_page' );
        if ( $default_comments_page == 'oldest' ) {
            $page = ++ $page;
        } else {
            $page = -- $page;
        }
        global $wpdb;
        $comment_sql = $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent = 0 AND comment_approved = 1 AND comment_post_ID = %d", $post_id );
        $comment_count = $wpdb->get_var( $comment_sql );
        $pages         = ceil( $comment_count / $per_page );
        $more          = '';
        if ( $pages > 1 && $page > 0 && $page <= $pages ) {
            $more = '<a href="' . Play_API::instance()->get_play_api_url('comments') . '?cpage=' . esc_attr( $page ) . '&post_id=' . esc_attr( $post_id ) . '" class="scroller no-ajax button"><span>• • •</span><span class="screen-reader-text">' . __( 'More', 'Play-Block' ) . '</span></a>';
        }
        if ( ! $comments && $post_id ) {
            global $post;
            $post = get_post( $post_id );
            setup_postdata( $post );
            $comments = wp_list_comments( array(
                'echo'       => false,
                'page'       => $cpage,
                'per_page'   => $per_page,
                'style'      => 'ol',
                'short_ping' => true
            ) );
            wp_reset_postdata();
        }

        return $comments . $more;
    }

}

Play_Comment::instance();
