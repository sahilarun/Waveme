<?php

defined( 'ABSPATH' ) || exit;

class Play_Notification {

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
        if( apply_filters('play_notification', true) ){
            $this->init();
        }
    }

    public function init() {
        add_action( 'play_block_like_after_save', array($this, 'like_notification'), 10, 2 );
        add_action( 'play_block_follow_after_save', array($this, 'follow_notification'), 10, 2 );
        add_action( 'play_block_upload_after_insert', array($this, 'upload_notification'), 10, 2 );
        add_action( 'play_block_download_after_save', array($this, 'download_notification'), 10, 2 );
        add_action( 'play_block_playlist_after_insert', array($this, 'playlist_notification'), 10, 2 );
        add_action( 'play_block_playlist_after_save', array($this, 'playlist_notification'), 10, 2 );
        add_action( 'comment_post', array($this, 'comment_notification'), 10, 3 );
        add_action( 'transition_post_status', array($this, 'post_notification'), 10, 3 );

        add_filter( 'play_notification_actions', array($this, 'get_notification_actions') );

        add_filter( 'loop_block_sql_query', array($this, 'loop_block_sql_query'), 10, 1 );
        add_filter( 'loop_locate_template', array($this, 'loop_template'), 10, 3 );
        add_action( 'loop_block_after_render', array($this, 'loop_block_after_render'), 10, 2 );

        do_action( 'play_block_notification_init', $this );
    }

    public function like_notification( $user_id, $post_id ) {
        if( play_get_option('like_notification') ){
            $author_id = get_post_field( 'post_author', $post_id );
            $data = array(
                'user_id' => (int) $author_id,
                'item_id' => (int) $post_id,
                'notifier_id' => (int) $user_id,
                'action' => 'like',
            );
            $data = apply_filters('play_block_like_notification_data', $data);
            $this->save( $data );
        }
    }

    public function follow_notification( $user_id, $notifier_id ) {
        if( play_get_option('follow_notification') ){
            $data = array(
                'user_id' => (int) $user_id,
                'notifier_id' => (int) $notifier_id,
                'action' => 'follow',
            );
            $data = apply_filters('play_block_follow_notification_data', $data);
            $this->save( $data );
        }
    }

    public function upload_notification( $user_id, $post_id ) {
        if( play_get_option('upload_notification') && get_post_status($post_id) == 'publish' ){
            $data = array(
                'item_id' => (int) $post_id,
                'notifier_id' => (int) $user_id,
                'action' => 'upload',
            );
            // get followers
            $follower = apply_filters( 'user_follow', $user_id );
            foreach ( $follower as $k => $v ) {
                $data['user_id'] = (int) $v;
                $data = apply_filters('play_block_upload_notification_data', $data);
                $this->save( $data );
            }
        }
    }

    public function playlist_notification( $user_id, $post_id ) {
        if( play_get_option('playlist_notification') && get_post_status($post_id) == 'publish' ){
            $data = array(
                'item_related_id' => (int) $post_id,
                'notifier_id' => (int) $user_id,
                'action' => 'playlist',
            );
            
            $posts = get_post_meta( $post_id, 'post', true);
            $posts = explode(',', $posts);
            foreach ( $posts as $k => $v ) {
                $data['user_id'] = (int) get_post_field( 'post_author', $v );
                $data['item_id'] = (int) $v;
                $data = apply_filters('play_block_playlist_notification_data', $data);
                $this->save( $data );
            }
        }
    }

    public function download_notification( $user_id, $post_id ) {
        if( play_get_option('download_notification') ){
            $author_id = get_post_field( 'post_author', $post_id );
            $data = array(
                'user_id' => (int) $author_id,
                'item_id' => (int) $post_id,
                'notifier_id' => (int) $user_id,
                'action' => 'download',
            );
            $data = apply_filters('play_block_download_notification_data', $data);
            $this->save( $data );
        }
    }

    public function comment_notification( $comment_id, $comment_approved, $commentdata ) {
        if( play_get_option('comment_notification') ){
            if($comment_approved == 0 || !isset($commentdata['user_id']) ){
                return;
            }

            $data = array(
                'user_id' => (int) get_post_field( 'post_author', $commentdata['comment_post_ID'] ),
                'item_id' => (int) $commentdata['comment_post_ID'],
                'notifier_id' => (int) $commentdata['user_id'],
                'action' => 'comment',
                'description' => $commentdata['comment_content']
            );

            $data = apply_filters('play_block_comment_notification_data', $data);
            $this->save( $data );
        }
    }

    public function post_notification( $new_status, $old_status, $post ) {
        if( play_get_option('post_notification') && 'publish' == $new_status && 'publish' != $old_status ){
            $types = play_get_option( 'play_types' );
            $types[] = 'post';
            $types = apply_filters('play_block_post_notification_types', $types);
            if(!in_array($post->post_type, $types)){
                return;
            }
            $user_id = $post->post_author;
            $data = array(
                'item_id' => (int) $post->ID,
                'notifier_id' => (int) $user_id,
                'action' => 'post'
            );

            $follower = apply_filters( 'user_follow', $user_id );
            foreach ( $follower as $k => $v ) {
                $data['user_id'] = (int) $v;
                $data = apply_filters('play_block_post_notification_data', $data);
                $this->save( $data );
            }
        }
    }
    
    public function save( $args = array() ) {
        do_action_ref_array( 'play_block_notification_before_save', array( &$this ) );

        if((int)$args['user_id'] !== (int)$args['notifier_id']){
            play_add_notification($args);
        }
        
        do_action_ref_array( 'play_block_notification_after_save', array( &$this ) );
    }

    public function get( $args = array() ) {
        global $wpdb;
        do_action( 'play_block_notification_before_get', $args );

        if( isset( $args['action'] ) && in_array( $args['action'], array('ajax_loop_more', 'notification') ) ){
            unset( $args['action'] );
        }

        $query = new stdClass();
        $args['number'] = absint( $args['pages'] );
        $args['offset'] = absint( $args['pages'] ) * ((isset($args['paged']) ? absint($args['paged']) : 1)  - 1 );
        
        $query->max_num_pages = ceil(play_count_notifications($args)/$args['pages']);
        
        $query->items = play_get_notifications($args);

        return $query;
    }

    public function loop_block_sql_query( $args = array() ) {
        if( is_string( $args['type'] ) && strpos( strtolower($args['type']), 'notification' ) ){
            return $this->get( $args );
        }
    }

    public function loop_template( $template, $template_name, $template_path ){
        if( $template_name == 'notification' ){
            $tpl = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/user/notification.php';
            if ( file_exists( $tpl ) ) {
                $template = $tpl;
            }
        }
        return $template;
    }

    public function loop_block_after_render( $args, $query ) {
        // update to status 1
        if(isset($args['type']) && is_string( $args['type'] ) && strpos( strtolower($args['type']), 'notification' ) ){
            if( $args['pager'] !== '' && $query->items ){
                foreach($query->items as $item){
                    if((int)$item->status == 0){
                        play_update_notification($item->id, array('status'=>1));
                    }
                }
            }
        }
    }

    public function get_notification_actions( ) {
        $actions = array(
            'like'     => play_get_text( 'notification-like' ),
            'follow'   => play_get_text( 'notification-follow' ),
            'comment'  => play_get_text( 'notification-comment' ),
            'upload'   => play_get_text( 'notification-upload' ),
            'download' => play_get_text( 'notification-download' ),
            'playlist' => play_get_text( 'notification-playlist' ),
            'post'     => play_get_text( 'notification-post' ),
        );
        return apply_filters('play_notification_actions_filter', $actions);
    }

}

Play_Notification::instance();
