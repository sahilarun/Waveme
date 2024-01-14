<?php

defined( 'ABSPATH' ) || exit;

class Play_Count {

    private $meta_key = 'post-count-';
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
        add_filter( 'play_count', array( $this, 'get_play_count' ), 10, 2 );
        add_filter( 'play_rank', array( $this, 'get_play_rank' ), 10, 3 );
        add_filter( 'user_played', array( $this, 'get_user_played' ) );
        add_filter( 'user_total_played', array( $this, 'user_total_played' ) );
        add_filter( 'user_total_liked', array( $this, 'user_total_liked' ) );
        add_filter( 'user_total_downloaded', array( $this, 'user_total_downloaded' ) );
        add_filter( 'is_playable', array( $this, 'is_playable' ), 10, 2 );
        add_filter( 'loop_block_query_args', array( $this, 'add_loop_query_filter' ), 10, 2 );
        
        add_action( 'save_play_count', array( $this, 'save_count' ) );
        add_action( 'the_play_count', array( $this, 'the_play_count' ), 10, 2 );
        add_action( 'the_play_button', array( $this, 'the_play_button' ), 10, 4 );
        add_action( 'the_more_button', array( $this, 'the_more_button' ), 10, 2 );

        do_action( 'play_block_count_init', $this );

        add_action( 'admin_notices', array( $this, 'upgrade_notices' ) );
    }

    public function save_count( $post_id ) {
        $timings = $this->get_timings();
        foreach ( $timings as $time => $date ) {
            if ( $time != 'all' ) {
                $date = '-' . date( $date );
            }
            // Filtered meta key name
            $meta_key_filtered = apply_filters( 'play_count_meta_key', $this->meta_key . $time . $date, $time, $date );
            $count             = (int) get_post_meta( $post_id, $meta_key_filtered, true );
            ++ $count;
            update_post_meta( $post_id, $meta_key_filtered, $count );
            // Normal meta key name
            $meta_key = $this->meta_key . $time . $date;
            if ( $meta_key_filtered != $meta_key ) {
                $count = (int) get_post_meta( $post_id, $meta_key, true );
                ++ $count;
                update_post_meta( $post_id, $meta_key, $count );
            }
        }

        return $this->save_user_played( $post_id );
    }

    public function save_user_played( $post_id ) {
        if( !is_user_logged_in() ){
            return false;
        }
        
        $user_id = get_current_user_id();
        if ( $user_id ) {
          play_add_stat( array(
            'user_id'     => $user_id,
            'object_id'   => $post_id,
            'object_type' => $this->type
          ) );
        }
    }

    public function add_loop_query_filter( $query_args, $args ) {
        if ( ! isset( $args[ 'orderby' ] ) ) {
            return $query_args;
        }
        // order by day/week/month/year/all
        $match = preg_match( '/' . implode( '|', array(
                'day',
                'week',
                'month',
                'year',
                'all'
            ) ) . '/', $args[ 'orderby' ], $matches );
        if ( $match ) {
            $key                      = $this->get_time( $matches[ 0 ] );
            $query_args[ 'orderby' ]  = 'meta_value_num ID';
            $query_args[ 'meta_key' ] = $key;
            $meta                     = array(
                'key'     => $key,
                'type'    => 'NUMERIC',
                'compare' => 'EXISTS'
            );
            if ( isset( $query_args[ 'meta_query' ] ) ) {
                $query_args[ 'meta_query' ][] = $meta;
            } else {
                $query_args[ 'meta_query' ] = array( $meta );
            }
        }

        // current user
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'user' ) && is_user_logged_in() ) {
            if ( is_user_logged_in() ) {
                $query_args[ 'author' ] = get_current_user_id();
            }
        }

        // user likes/played
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'user_played' || $query_args[ 'orderby' ] == 'user_likes' ) ) {
            $user_id = 0;
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
            }
            if( isset($args[ 'user_id' ]) ){
                $user_id = (int) $args[ 'user_id' ];
            }
            $ids = apply_filters( $query_args[ 'orderby' ], $user_id );

            if( empty($ids) ){
                $ids = array(0);
            }
            $query_args[ 'post__in' ] = $ids;
            $query_args[ 'orderby' ]  = 'post__in';
        }

        // user following's feed
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'user_following' ) ) {
            if( is_user_logged_in() ){
                $user_id = get_current_user_id();
            }
            // spec userid
            if( isset($args[ 'user_id' ]) ){
                $user_id = (int) $args[ 'user_id' ];
            }
            if( !isset($user_id) ) return;
            
            $users = apply_filters( 'user_following', $user_id );
            $query_args[ 'author__in' ] = $users;
        }

        // user download
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'user_downloads' ) ) {
            $user_id = 0;
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
            }
            if( isset($args[ 'user_id' ]) ){
                $user_id = (int) $args[ 'user_id' ];
            }
            $ids = apply_filters( 'user_download', $user_id );
            if( empty($ids) ){
                $ids = array(0);
            }
            $query_args[ 'post__in' ] = $ids;
            $query_args[ 'orderby' ]  = 'post__in';
        }

        // follow/following users
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'follow_user' || $query_args[ 'orderby' ] == 'following_user' ) ) {
            $user_id = 0;
            $orderby = 'user_follow';
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
            }
            if( isset($args[ 'user_id' ]) ){
                $user_id = (int) $args[ 'user_id' ];
            }
            if($query_args[ 'orderby' ] == 'following_user'){
                $orderby = 'user_following';
            }
            $follower = apply_filters( $orderby, $user_id );

            $query_args[ 'include' ] = $follower;
            $query_args[ 'orderby' ] = 'include';
        }

        // verified user's posts
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'user_verified' ) ) {
            $users = get_users( array(
                'meta_key'   => 'verified',
                'meta_value' => 'true',
                'fields'     => 'ID'
            ) );
            $query_args[ 'author__in' ] = $users;
        }

        // album playlist
        if ( isset( $query_args[ 'orderby' ] ) && ( $query_args[ 'orderby' ] == 'album' ) ) {
            $id = $args[ 'post_id' ];
            $items = get_post_meta( $id, 'post', true );
            $query_args[ 'post__in' ] = $items;
            $query_args[ 'orderby' ] = 'post__in';
        }

        return apply_filters( 'play_loop_block_query_args', $query_args );
    }

    public function get_user_played( $user_id ) {
        $plays = play_get_stats( array(
          'number'      => false,
          'user_id'     => $user_id,
          'object_type' => $this->type,
          'fields'      => 'object_id',
          'orderby'     => 'id',
          'order'       => 'DESC',
        ) );

        $plays = ! empty( $plays ) ? array_unique( array_values( $plays ) ) : array();

        return apply_filters( 'play_user_played', $plays, $user_id, $this );
    }

    public function get_play_count( $post_id, $time = 'all' ) {
        $time = $this->get_time( $time );

        $count = (int) get_post_meta( $post_id, $time, true );
        $count_html = '';
        
        if($count > 0){
            $count_html = sprintf( '<span class="play-count"><span class="count">%s</span></span>', Play_Utils::instance()->format_count( $count ) );
        }
        return apply_filters( 'play_get_play_count', $count_html, $post_id, $time, $this );
    }

    public function the_play_count( $post_id, $time = 'all' ) {
        echo $this->get_play_count( $post_id, $time );
    }

    public function get_time( $time ) {
        switch ( $time ) {
            case 'day':
                $time = $this->meta_key . 'day-' . date( 'Ymd' );
                break;
            case 'week':
                $time = $this->meta_key . 'week-' . date( 'YW' );
                break;
            case 'month':
                $time = $this->meta_key . 'month-' . date( 'Ym' );
                break;
            case 'year':
                $time = $this->meta_key . 'year-' . date( 'Y' );
                break;
            case 'all':
                $time = $this->meta_key . 'all';
                break;
            default:
                $time = $time;
                break;
        }

        return apply_filters( 'play_get_time', $time, $this );
    }

    public function get_timings() {
        return apply_filters( 'play_count_timings', array(
                'all'   => '',
                //'day'=>'Ymd',
                'week'  => 'YW',
                'month' => 'Ym',
                'year'  => 'Y'
            )
        );
    }

    public function the_play_button( $id, $type = 'play', $class = '', $count = false ) {
        echo $this->get_play_button( $id, $type, $class, $count );
    }

    public function get_play_button( $id, $type, $class, $count ) {
        if ( ! $this->is_playable( $id, $type ) ) {
            return;
        }
        if( get_post_meta( $id, 'auto_type', true ) ){
            $class .= 'btn-play-auto';
        }
        $button = '<button class="btn-play %1$s" data-%2$s-id="%3$s"><span>'.play_get_text('play').'</span></button>';
        if ( $count ) {
            if($type == 'user'){
                $count = '<span class="count">'. Play_Utils::instance()->format_count( (int) $this->user_total_played($id) ) .'</span>';
            }else{
                $count = $this->get_play_count( $id );
            }
            $button = '<span class="btn-play-wrap">' . $button . $count . '</span>';
        }

        $ret = sprintf( $button, $class, $type, $id );

        return apply_filters( 'play_get_play_button', $ret, $id, $button, $class, $type, $count, $this );
    }

    public function is_playable( $id, $type = '' ) {
        $playable = true;
        if ( 'user' === $type ) {
            // user
            $posts = get_posts(
                array(
                    'post_type'  => play_get_option( 'post_type' ),
                    'meta_query' => array(
                        array(
                            'key'     => 'type',
                            'value'   => array( 'album', 'playlist' ),
                            'compare' => 'NOT IN'
                        )
                    ),
                    'author'     => $id,
                    'fields'     => 'ids'
                )
            );
            if ( empty( $posts ) ) {
                $playable = false;
            }
        } else {
            // post
            $post_type = get_post_meta( $id, 'type', true );
            if ( ( $post_type == 'playlist' || $post_type == 'album' ) ) {
                $post = get_post_meta( $id, 'post', true );
                if ( empty( $post ) ) {
                    $playable = false;
                }
                $auto_type = get_post_meta( $id, 'auto_type', true );
                if ( $auto_type && is_single($id) ) {
                    $playable = true;
                }
            } else {
                $stream     = get_post_meta( $id, 'stream', true );
                $stream_url = get_post_meta( $id, 'stream_url', true );
                if ( empty( $stream ) && empty( $stream_url ) ) {
                    $playable = false;
                }
            }
        }

        return apply_filters( 'play_is_playable', $playable, $id, $type );
    }

    public function the_more_button( $id, $type = 'post' ) {
        $attr = $url = '';
        $embed_url = apply_filters('get_endpoint_url', 'embed', $id, home_url() );

        if($type === 'post'){
            $_type = get_post_meta( $id, 'type', true );
            $attr .= sprintf('data-type="%s" ', esc_attr($_type));
            if ( get_current_user_id() === (int) get_post_field( 'post_author', $id ) ) {
                $attr .= 'data-editable="true" ';
            }
            $url = get_permalink( $id );
        }else{
            $url = get_author_posts_url( $id );
            $embed_url .= '?u';
            $attr .= 'data-type="user" ';
        }

        if ( ! $this->is_playable( $id, $type ) ) {
            $attr .= 'data-playable="false" ';
        }

        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-icon"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>';
        $icon = apply_filters( 'more_button_svg', $icon );
        $button = sprintf( '<button class="btn-more" %s data-id="%s" data-url="%s" data-embed-url="%s">%s</button>', $attr, (int) $id, esc_attr( $url ), esc_url($embed_url), $icon );

        echo apply_filters( 'play_get_more_button', $button, $id, $type, $this );
    }

    public function get_play_rank( $post_id, $time = 'all', $type = 'single' ) {
        global $wpdb;

        $h = 'SELECT @i:=@i+1';
        $t = ', (SELECT @i:=0) i';
        if(version_compare( $wpdb->db_version(), '8.0', '>=' )){
            $h = 'SELECT ROW_NUMBER() OVER (ORDER BY meta_value + 0 DESC)';
            $t = '';
        }

        $time = $this->get_time( $time );
        $sql  = $wpdb->prepare(
            "SELECT ranking
            FROM (
                ".$h." AS ranking, post_id, meta_value
                    FROM $wpdb->postmeta AS p
                ".$t."
                    WHERE meta_key = %s
                    GROUP BY post_id, meta_value
                    ORDER BY meta_value + 0 DESC, post_id DESC
            ) AS r
            WHERE post_id = %d",
            $time,
            $post_id
        );

        return $wpdb->get_var( $sql );
    }

    public function user_total_played($user_id, $time = 'all'){
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT
              SUM( pm.meta_value ) AS plays
          FROM
              {$wpdb->users} us
          JOIN
              {$wpdb->posts} po
              ON
              us.ID = po.post_author
          JOIN
              {$wpdb->postmeta} pm
              ON
              po.ID = pm.post_id
          WHERE
              pm.meta_key = %s
              AND
              po.post_status = 'publish'
              AND
              us.ID = %d
          GROUP BY
              us.ID", $this->meta_key . $time, $user_id );

        $played = $wpdb->get_var( $sql );

        update_user_meta( $user_id, 'total_played', $played );
        return absint( $played );
    }

    public function user_total_liked($user_id){
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT
              SUM( pm.meta_value ) AS likes
          FROM
              {$wpdb->users} us
          JOIN
              {$wpdb->posts} po
              ON
              us.ID = po.post_author
          JOIN
              {$wpdb->postmeta} pm
              ON
              po.ID = pm.post_id
          WHERE
              pm.meta_key = 'like_count'
              AND
              po.post_status = 'publish'
              AND
              us.ID = %d
          GROUP BY
              us.ID", $user_id );

        $liked = $wpdb->get_var( $sql );

        update_user_meta( $user_id, 'total_liked', $liked );
        return absint( $liked );
    }

     public function user_total_downloaded($user_id){
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT
              SUM( pm.meta_value ) AS downloads
          FROM
              {$wpdb->users} us
          JOIN
              {$wpdb->posts} po
              ON
              us.ID = po.post_author
          JOIN
              {$wpdb->postmeta} pm
              ON
              po.ID = pm.post_id
          WHERE
              pm.meta_key = 'download_count'
              AND
              po.post_status = 'publish'
              AND
              us.ID = %d
          GROUP BY
              us.ID", $user_id );

        $downloaded = $wpdb->get_var( $sql );

        update_user_meta( $user_id, 'total_downloaded', $downloaded );
        return absint( $downloaded );
    }

    /**
     * upgrade notice
     */

    public function upgrade_notices(){
        if(!current_user_can('manage_options')){
            return;
        }
        if(isset($_REQUEST['play-upgrades'])){
            $this->upgrade();
        }

        $required = false;
        $metas = array('download','played','following','like','dislike');
        foreach ( $metas as $meta ) {
            $users = get_users(
                array(
                    'meta_key' => $meta,
                    'number'   => - 1,
                    'fields'   => 'ids'
                )
            );

            if ( !empty( $users ) ) {
                $required = true;
                break;
            }
        }

        if($required && !isset($_REQUEST['play-upgrades'])){
            printf(
              '<div class="updated"><p>' . __( 'Play Block needs to perform an upgrade. Click <a href="%s">here</a> to start.', 'play-block' ) . '</p></div>',
              admin_url( '?play-upgrades' )
            );
        }
    }

    /**
     * upgrade to new table
     */
    private function upgrade() {
        $metas = array('download','played','following','like','dislike');
        $info = '';
        foreach ( $metas as $meta ) {
            $users = get_users(
                array(
                    'meta_key' => $meta,
                    'number'   => - 1,
                    'fields'   => 'ids'
                )
            );

            if ( empty( $users ) ) {
                continue;
            }
            $info .= $meta.':<br>';

            foreach ( $users as $user_id ) {
                $ids = get_user_meta( $user_id, $meta, true );

                delete_user_meta( $user_id, $meta );

                if( in_array($meta, array('like','dislike')) ){
                    $site_id = get_current_blog_id();
                    if( is_array($ids) ){
                        foreach ( $ids as $item ) {
                            if(isset($item['site_id']) && $item['site_id'] == $site_id){
                                $types = array('posts','comments');
                                foreach($types as $type){
                                    $t = substr($type, 0, -1);
                                    if(isset($item[$type]) && is_array($item[$type])){
                                        foreach ( $item[$type] as $id ) {
                                            $data = array(
                                                'user_id'     => $user_id,
                                                'object_id'   => $id,
                                                'object_type' => $t,
                                                'action'      => $meta,
                                            );
                                            $info .= implode('-',$data).'<br>';

                                            if ( !play_get_like_by_object( $id, $t, $meta, $user_id ) ){
                                                play_add_like( $data );
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }

                if ( is_array( $ids ) ) {
                    foreach ( $ids as $id ) {
                        $data = array(
                            'user_id'     => $user_id,
                            'object_id'   => $id,
                            'object_type' => ($meta=='following' ? 'user' : 'post')
                        );
                        $info .= implode('-',$data).'<br>';
                        if($meta == 'following'){
                            if(!play_get_follow_by_object( $id, 'user', $user_id )){
                                play_add_follow( $data );
                            }
                            continue;
                        }

                        if (!in_array( get_post_status($id), array('publish','approved') ) ) {
                            continue;
                        }

                        if ($meta == 'download' && !play_get_download_by_object( $id, 'post', $user_id ) ) {
                            play_add_download( $data );
                        }

                        if ($meta == 'played' && !play_get_stat_by_object( $id, 'post', $user_id ) ) {
                            play_add_stat( $data );
                        }
                    }
                }
            }
        }

        printf(
          '<div class="updated"><p>' . __( 'Upgrade done.', 'play-block' ) . '</p></div>'
        );
    }

}

Play_Count::instance();
