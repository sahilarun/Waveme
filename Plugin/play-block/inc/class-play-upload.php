<?php

defined( 'ABSPATH' ) || exit;

class Play_Upload {

    private $user_id;

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
        add_action( 'template_redirect', array( $this, 'remove_upload' ) );

        add_filter( 'wp_dropdown_cats', array( $this, 'wp_dropdown_cats_multiple' ), 10, 2 );
        add_filter( 'get_upload_edit_link', array( $this, 'get_upload_edit_link' ), 10, 1 );

        add_shortcode( 'play_upload_form', array( $this, 'upload_form_shortcode' ) );
        add_filter( 'play_modal_upload_form', array( $this, 'upload_form' ) );

        add_action( 'play_upload_stream', array( $this, 'upload_stream' ) );
        add_action( 'play_upload', array( $this, 'upload' ) );

        add_filter( 'upload_mimes', array( $this, 'mime_types' ));
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'filetype_and_ext' ), 10, 4 );
        add_filter( 'wp_get_attachment_id3_keys', array( $this, 'attachment_id3_keys' ) );

        function play_upload_form() {
            return Play_Upload::instance()->upload_form($_REQUEST);
        }

        do_action( 'play_block_upload_init', $this );
    }

    public function upload( $request ) {
        $this->save_upload( $request );
    }

    public function mime_types( $mimes ) {
        // upload file type
        if(current_user_can('manage_options')){
            $mimes['svg']  = 'image/svg+xml';
            $mimes['vtt']  = 'ext/vtt';
            $mimes['xml']  = 'application/xml';
            $mimes['json'] = 'application/json';
            $mimes['csv']  = 'application/csv';
            $mimes['webp'] = 'image/webp';
        }
        return $mimes;
    }

    public function filetype_and_ext( $types, $file, $filename, $mimes ) {
        // allow select file type
        $filetype = wp_check_filetype( $filename, $mimes );
        if(current_user_can('manage_options') && in_array($filetype['ext'], array('txt','json','csv','xml','vtt','svg','webp'))){
            $filetype = wp_check_filetype( $filename, $mimes );
            $types['ext'] = $filetype['ext'];
            $types['type'] = $filetype['type'];
        }
        return $types;
    }

    public function attachment_id3_keys($fields){
        $fields['bpm'] = __('BPM', 'play-block');
        return $fields;
    }

    public function remove_upload() {
        if ( ! isset( $_REQUEST[ 'post_id' ] ) || ! isset( $_REQUEST[ 'action' ] ) || 'remove' !== $_REQUEST[ 'action' ] ) {
            return;
        }

        $post_id = (int) $_REQUEST[ 'post_id' ];
        $user_id = get_current_user_id();

        if ( !$this->user_can_edit( $post_id ) ) {
            return;
        }

        do_action( 'play_remove_upload', $post_id, $user_id );
        
        if( apply_filters('play_force_delete', true) ){
            wp_delete_post( $post_id );
        }else{
            wp_trash_post( $post_id );
        }
        
        wp_safe_redirect( get_author_posts_url( $user_id ) );
        exit();
    }

    public function save_upload( $request ) {

        if ( !is_user_logged_in() ) {
            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'   => __( 'You need login', 'play-block' )
                )
            );
        }

        $user_id = get_current_user_id();

        $args = $this->get_upload_form_defaults();

        $pass = true;

        $ID          = ! empty( $request[ 'post_id' ] ) ? absint( wp_unslash( $request[ 'post_id' ] ) ) : 0;
        $title       = ! empty( $request[ 'title' ] ) ? sanitize_text_field( wp_unslash( $request[ 'title' ] ) ) : '';
        $content     = ! empty( $request[ 'content' ] ) ? wp_kses( $request[ 'content' ], array(
                'br'     => array(),
                'em'     => array(),
                'strong' => array(),
                'small'  => array(),
                'span'   => array(),
                'ul'     => array(),
                'li'     => array(),
                'ol'     => array(),
                'p'      => array(),
                'a'      => array(
                    'href' => array(),
                )
            )
        ) : '';
        $post_date   = ! empty( $request[ 'post_date' ] ) ? sanitize_text_field( wp_unslash( $request[ 'post_date' ] ) ) : '';
        $post_type   = ! empty( $request[ 'post_type' ] ) ? sanitize_text_field( wp_unslash( $request[ 'post_type' ] ) ) : 'post';
        $post_status = ! empty( $request[ 'post_status' ] ) ? sanitize_text_field( wp_unslash( $request[ 'post_status' ] ) ) : 'private';
        $type        = ! empty( $request[ 'type' ] ) ? sanitize_text_field( wp_unslash( $request[ 'type' ] ) ) : 'single';

        if ( ! $this->user_can_post_public( $type ) && ( $post_status == 'publish' ) ) {
            $post_status = 'private';
        }
        $posts   = ! empty( $request[ 'post' ] ) ? sanitize_text_field( wp_unslash( $request[ 'post' ] ) ) : '';
        $cats    = ! empty( $request[ 'cat' ] ) ? sanitize_text_field( wp_unslash( $request[ 'cat' ] ) ) : '';
        $tags    = ! empty( $request[ 'tag' ] ) ? sanitize_text_field( wp_unslash( $request[ 'tag' ] ) ) : '';
        $artists = ! empty( $request[ 'artist' ] ) ? sanitize_text_field( wp_unslash( $request[ 'artist' ] ) ) : '';

        $stream         = ! empty( $request[ 'stream' ] ) ? esc_url_raw( wp_unslash( $request[ 'stream' ] ) ) : '';
        $stream_url         = ! empty( $request[ 'stream_url' ] ) ? esc_url_raw( wp_unslash( $request[ 'stream_url' ] ) ) : '';
        $tracks         = ! empty( $request[ 'tracks' ] ) ? sanitize_text_field( wp_unslash( $request[ 'tracks' ] ) ) : '';
        $waveform       = ! empty( $request[ 'waveform' ] ) ? sanitize_text_field( wp_unslash( $request[ 'waveform' ] ) ) : '';
        $bpm            = ! empty( $request[ 'bpm' ] ) ? sanitize_text_field( wp_unslash( $request[ 'bpm' ] ) ) : '';
        $duration       = ! empty( $request[ 'duration' ] ) ? sanitize_text_field( wp_unslash( $request[ 'duration' ] ) ) : '';
        $duration       = Play_Utils::instance()->timeToMS($duration);

        $downloadable   = ! empty( $request[ 'downloadable' ] ) ? wp_unslash( $request[ 'downloadable' ] ) : '';
        $download_url   = ! empty( $request[ 'download_url' ] ) ? esc_url_raw( wp_unslash( $request[ 'download_url' ] ) ) : '';
        $purchase_title = ! empty( $request[ 'purchase_title' ] ) ? sanitize_text_field( wp_unslash( $request[ 'purchase_title' ] ) ) : '';
        $purchase_url   = ! empty( $request[ 'purchase_url' ] ) ? esc_url_raw( wp_unslash( $request[ 'purchase_url' ] ) ) : '';

        $copyright = ! empty( $request[ 'copyright' ] ) ? sanitize_text_field( wp_unslash( $request[ 'copyright' ] ) ) : '';

        $regular_price = ! empty( $request[ '_regular_price' ] ) ? sanitize_text_field( wp_unslash( $request[ '_regular_price' ] ) ) : '';
        $sale_price    = ! empty( $request[ '_sale_price' ] ) ? sanitize_text_field( wp_unslash( $request[ '_sale_price' ] ) ) : '';

        if ( $ID > 0 && ! $this->user_can_edit( $ID ) ) {
            return;
        }

        $error = '';

        if ( empty( $title ) ) {
            $pass  = false;
            $error = $args[ 'label_error_title' ];
        }

        if ( empty( $stream ) && ( $type == 'single' ) ) {
            $pass  = false;
            $error = $args[ 'label_error_stream' ];
        }

        $files = $request->get_file_params();
        if ( ( isset( $files[ 'image' ] ) && $files[ 'image' ][ "size" ] == 0 && $ID == 0 ) ) {
            $pass  = false;
            $error = $args[ 'label_error_poster' ];
        }

        $cat    = $args[ 'cat_slug' ];
        $tag    = $args[ 'tag_slug' ];
        $artist = $args[ 'artist_slug' ];
        if ( $post_type == 'station' ) {
            $cat = 'genre';
            $tag = 'station_tag';
        } elseif ( $post_type == 'product' ) {
            $cat = 'product_cat';
            $tag = 'product_tag';
        } elseif ( $post_type == 'download' ) {
            $cat = 'download_category';
            $tag = 'download_tag';
        }

        if(apply_filters('play_use_genre', true)){
            $cat = 'genre';
        }

        // exclude
        $exclude_tags = apply_filters( 'play_exclude_tags', array( 'Featured', 'Editor Choice' ) );
        $tags         = explode( ',', $tags );
        $tags         = array_diff( $tags, $exclude_tags );

        if ( $pass ) {
            $post = array(
                'ID'            => $ID,
                'post_title'    => wp_strip_all_tags( $title ),
                'post_content'  => $content,
                'post_status'   => $post_status,
                'post_author'   => $user_id,
                'post_type'     => $post_type,
                'post_date'     => $post_date,
                'post_date_gmt' => $post_date,
                'comment_status'=> get_default_comment_status($post_type),
                'tax_input'     => array(
                    $cat    => $cats,
                    $tag    => $tags,
                    $artist => explode( ',', $artists )
                ),
                'meta_input'    => array(
                    'type'           => $type,
                    'stream'         => $stream,
                    'stream_url'     => $stream_url,
                    'post'           => $posts,
                    'duration'       => $duration,
                    'downloadable'   => $downloadable,
                    'download_url'   => $download_url,
                    'purchase_title' => $purchase_title,
                    'purchase_url'   => $purchase_url,
                    'copyright'      => $copyright
                )
            );

            if ( ! empty( $waveform ) ) {
                if( strpos($waveform, 'http') !== false ){
                    $wf = wp_remote_get( $waveform );
                    if( ! is_wp_error($wf) ){
                        $waveform = substr( wp_remote_retrieve_body( $wf ), 1, -1);
                    }
                }
                $post[ 'meta_input' ][ 'waveform_data' ] = explode( ',', $waveform );
            }
            if ( ! empty( $bpm ) ) {
                $post[ 'meta_input' ][ 'bpm' ] = $bpm;
            }

            $post_id = wp_insert_post( apply_filters( 'frontend_upload_post', $post, $request ) );

            // post thumbnail
            if ( isset( $files[ 'image' ] ) && $files[ 'image' ][ "size" ] > 0 ){
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                // featured image
                $attach_id = media_handle_upload( 'image', $post_id );
                if ( ! is_wp_error( $attach_id ) ) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            }

            // save tracks when bulk upload
            if ( ! empty( $tracks ) ) {
                $tracks = json_decode( $tracks );
                $posts  = [];
                foreach ( $tracks as $track ) {
                    $post = array(
                        'post_title'    => wp_strip_all_tags( $track->title ),
                        'post_status'   => $post_status,
                        'post_author'   => $user_id,
                        'post_type'     => ( play_get_option( 'post_type' ) ? play_get_option( 'post_type' ) : 'station' ),
                        'post_date'     => $post_date,
                        'post_date_gmt' => $post_date,
                        'meta_input'    => array(
                            'type'          => apply_filters( 'play_block_bulk_upload_single_type', 'single' ),
                            'stream'        => esc_url_raw( $track->url ),
                            'downloadable'  => $downloadable
                        ),
                        'tax_input'     => array(
                            $cat    => $cats
                        ),
                    );

                    if(isset($track->waveform)){
                        $post['meta_input']['waveform_data'] = explode( ',', $track->waveform );
                    }

                    if(isset($track->metadata->length_formatted)){
                        $post['meta_input']['duration'] = Play_Utils::instance()->timeToMS( $track->metadata->length_formatted );
                    }

                    if(isset($track->metadata->artist)){
                        $post['tax_input'][$artist] = explode( ',', $track->metadata->artist );
                    }

                    if(isset($track->metadata->meta->bitrate)){
                        $post['tax_input']['bpm'] = $track->metadata->meta->bitrate;
                    }
                    
                    $id   = wp_insert_post( apply_filters( 'frontend_upload_post_track', $post ) );
                    if ( ! is_wp_error( $attach_id ) ) {
                        set_post_thumbnail($id, $attach_id);
                    }
                    $posts[] = $id;
                }
                $posts = implode( ',', $posts );
                update_post_meta( $post_id, 'post', $posts );
            }

            // do something if it's a Products
            if ( $post_type == 'product' ) {

                update_post_meta( $post_id, '_regular_price', $regular_price );
                update_post_meta( $post_id, '_sale_price', $sale_price );

                // save as virtual and downloable product
                update_post_meta( $post_id, '_virtual', 'yes' );
                if($downloadable !== ''){
                    update_post_meta( $post_id, '_downloadable', 'yes' );
                    // _downloadable_files
                    $file = $download_url === '' ? $stream : $download_url;
                    $item = array(
                        'id' => wp_generate_uuid4(),
                        'name' => basename( $file ),
                        'file' => $file
                    );
                    update_post_meta( $post_id, '_downloadable_files', array( $item ) );
                }else{
                    update_post_meta( $post_id, '_downloadable', 'no' );
                }

                if ( '' !== $sale_price ) {
                    update_post_meta( $post_id, '_price', $sale_price );
                } else {
                    update_post_meta( $post_id, '_price', $regular_price );
                }

                if ( ! empty( $purchase_url ) ) {
                    wp_set_object_terms( $post_id, 'external', 'product_type' );

                    update_post_meta( $post_id, '_product_url', $purchase_url );
                    update_post_meta( $post_id, '_button_text', $purchase_title );
                }
            } elseif ( $post_type == 'download' ) {

                update_post_meta( $post_id, 'edd_price', $regular_price );
                update_post_meta( $post_id, 'edd_sale_price', $sale_price );

                if($downloadable !== ''){
                    // edd_download_files
                    $file = $download_url === '' ? $stream : $download_url;
                    $item = array(
                        'index' => 0,
                        'id' => wp_generate_uuid4(),
                        'thumbnail_size' => false,
                        'attachment_id' => $this->get_attachment_id( $file ),
                        'name' => basename( $file ),
                        'file' => $file,
                        'condition' => 'all'
                    );
                    update_post_meta( $post_id, 'edd_download_files', array( $item ) );
                }
            }

            do_action( 'frontend_upload' );

            $post[ 'post_id' ]   = $post_id;
            $post[ 'permalink' ] = get_permalink( $post_id );
            $post[ 'thumbnail' ] = get_the_post_thumbnail_url( $post_id );

            if($ID == 0){
                do_action( 'play_block_upload_after_insert', $user_id, $post_id );
            }else{
                do_action( 'play_block_upload_after_save', $user_id, $post_id );
            }

            do_action( 'play_block_upload', $user_id, $post_id );
            
            return Play_Utils::instance()->response(
                array(
                    'status'   => 'success',
                    'msg'      => apply_filters('play_upload_saved', play_get_text( 'upload-saved' )),
                    'redirect' => $post['permalink']
                )
            );
        } else {
            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'    => $error
                )
            );
        }
    }

    public function upload_stream($request) {
        if ( !is_user_logged_in() ) {
            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'   => __( 'You need login', 'play-block' )
                )
            );
        }

        $max_upload_size = wp_max_upload_size();

        $files = $request->get_file_params();

        if ( (!empty( $files ) && !empty( $files['file'] ) && $files['file'][ 'size' ] > $max_upload_size) || empty($files) ) {
            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'    => sprintf( __( 'Maximum upload file size: %s.', 'play-block' ), esc_html( size_format( $max_upload_size ) ) )
                )
            );
        }

        do_action( 'play_upload_stream_before' );

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $ext = pathinfo( $files['file']['name'] );
        if( $ext['extension'] === NULL || $ext['extension'] === '' ) {
            $files['file']['name'] .= apply_filters( 'play_block_upload_default_extension', '.mp3' );
        }

        $mimes = apply_filters( 'play_allowed_mime_upload_types', array(
            'mpeg|mpg|mpe'  => 'video/mpeg',
            'mp4|m4v'       => 'video/mp4',
            'wav'           => 'audio/wav',
            'aac'           => 'audio/aac',
            'mp3|m4a|m4b'   => 'audio/mpeg',
            'ogg|oga'       => 'audio/ogg',
        ) );

        $stream_id = media_handle_upload( 'file', false, array(), array( 'test_form' => false, 'mimes' => $mimes ) );

        do_action( 'play_upload_stream_after', $stream_id );

        if ( ! is_wp_error( $stream_id ) ) {
            $metadata = wp_get_attachment_metadata( $stream_id );
            return Play_Utils::instance()->response(
                array(
                    'status'   => 'success',
                    'url'      => wp_get_attachment_url( $stream_id ),
                    'metadata' => $metadata
                )
            );
        } else {
            $err = $stream_id->get_error_message();

            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'    => $err
                )
            );
        }
    }

    public function get_upload_form_defaults() {
        $defaults = array(
            'post_type'          => ( play_get_option( 'post_type' ) ? play_get_option( 'post_type' ) : 'station' ),
            'cat_slug'           => 'genre',
            'tag_slug'           => 'station_tag',
            'artist_slug'        => 'artist',
            'label_error_title'  => play_get_text( 'title-required' ),
            'label_error_poster' => play_get_text( 'poster-required' ),
            'label_error_stream' => play_get_text( 'stream-required' ),
        );

        return apply_filters( 'upload_form_defaults', $defaults );
    }

    public function wp_dropdown_cats_multiple( $output, $r ) {
        if ( isset( $r[ 'multiple' ] ) && $r[ 'multiple' ] ) {
            $output = preg_replace( '/^<select/i', '<select multiple', $output );
            $output = str_replace( "name='{$r['name']}'", "name='{$r['name']}[]'", $output );
            foreach ( array_map( 'trim', explode( ",", $r[ 'selected' ] ) ) as $value ) {
                $output = str_replace( "value=\"{$value}\"", "value=\"{$value}\" selected", $output );
            }
        }

        return $output;
    }

    public function get_upload_edit_link( $post_id = null ) {
        if ( ! is_user_logged_in() ) {
            return;
        }
        if ( play_get_option( 'page_upload' ) ) {
            return get_permalink( play_get_option( 'page_upload' ) ) . '?post_id=' . $post_id;
        }
        $url = apply_filters( 'get_endpoint_url', 'upload', '?post_id=' . $post_id, get_author_posts_url( get_current_user_id() ) );

        return apply_filters( 'upload_edit_link', $url );
    }

    public function user_can_upload() {
        $can = false;
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $role = play_get_option( 'upload_role' );
        $roles = is_array($role) ? array_filter( $role ) : array('administrator','editor','author','contributor');
        
        $user = wp_get_current_user();
        if( count( array_intersect($roles, $user->roles) ) > 0 ){
            $can = true;
        }

        return apply_filters( 'user_can_upload', $can );
    }

    public function user_can_edit($post_id){
        $can = false;
        if ( ! is_user_logged_in() ) {
            $can = false;
        }
        if ( $post_id > 0 ) {
            $author = get_post_field( 'post_author', $post_id );
            if ( (int) get_current_user_id() == (int) $author ) {
                $can = true;
            }
        }
        return apply_filters( 'user_can_edit', $can, $post_id );
    }

    public function user_can_upload_stream() {
        $can = false;
        if( play_get_option( 'post_upload' ) || 'true' === get_user_meta( get_current_user_id(), 'verified', true ) ){
            $can = true;
        }
        return apply_filters( 'user_can_upload_stream', $can );
    }

    public function user_can_upload_online() {
        return apply_filters( 'user_can_upload_online', play_get_option( 'post_upload_online' ) );
    }

    public function user_can_post_public( $type = 'single' ) {
        $public = play_get_option( 'post_public' );
        if ( $type == 'playlist' ) {
            $public = play_get_option( 'post_playlist_public' );
        }
        if ( play_get_option( 'post_verified_public' ) && 'true' === get_user_meta( get_current_user_id(), 'verified', true ) ) {
            $public = true;
        }

        return apply_filters( 'user_can_post_public', $public );
    }

    public function get_attachment_id( $url ) {
        global $wpdb;
        $results = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url ));
        if( !empty($results) ){
            return $results[0];
        }
        return 0;
    }

    public function upload_form_shortcode() {
        return $this->upload_form($_REQUEST);
    }

    public function upload_form($request = null) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $args = $this->get_upload_form_defaults();
        $can_upload = $this->user_can_upload();

        $post_id = isset( $request[ 'post_id' ] ) ? (int) $request[ 'post_id' ] : 0;

        if ( $post_id > 0 && !$this->user_can_edit($post_id) ) {
            return;
        }

        if ($post_id == 0 && $can_upload == false ) {
            return;
        }

        $type = 'single';
        if ( $post_id ) {
            $post       = get_post( $post_id );
            $type       = get_post_meta( $post_id, 'type', true );
            $post->type = $type ? $type : 'single';
        } else {
            $post               = new stdClass();
            $post->ID           = 0;
            $post->post_title   = '';
            $post->post_content = '';
            $post->post_date    = date( "Y-m-d" );
            $post->post_status  = 'publish';
            $post->type         = 'single';
            if ( isset( $request[ 'type' ] ) ) {
                $post->type = sanitize_text_field( $request[ 'type' ] );
            }
            $post->post_type = $args[ 'post_type' ];
        }

        $data = array(
            'post'                   => $post,
            'redirect'               => get_author_posts_url( get_current_user_id() ),
            'user_can_upload_stream' => $this->user_can_upload_stream(),
            'user_can_upload_online' => $this->user_can_upload_online(),
            'user_can_post_public'   => $this->user_can_post_public( $type ),
            'user_can_upload'        => $can_upload
        );

        return ( $post_id || isset( $request[ 'form' ] ) ) ? play_get_template_html( 'form/upload.php', $data ) : play_get_template_html( 'form/upload-start.php', $data );
    }

}

Play_Upload::instance();
