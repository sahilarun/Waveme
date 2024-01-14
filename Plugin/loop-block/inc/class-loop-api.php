<?php

defined( 'ABSPATH' ) || exit;

class Loop_API {

    public $namespace = 'loop';

    protected static $_instance = null;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'init' ) );
        add_action( 'loop_block_api_init', $this );
    }

    public function init() {
        register_rest_route( $this->namespace, '/terms', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_terms' ),
            'permission_callback' => array( $this, 'privileged_permission_callback' ),
        ) );
        register_rest_route( $this->namespace, '/filter', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_filter' ),
            'permission_callback' => array( $this, 'privileged_permission_callback' ),
        ) );
        register_rest_route( $this->namespace, '/ids', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_ids' ),
            'permission_callback' => array( $this, 'privileged_permission_callback' ),
        ) );
        register_rest_route( $this->namespace, '/more', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'more' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function more ( WP_REST_Request $request ) {
        do_action('loop_more', $request);
    }

    public function get_terms( WP_REST_Request $request ) {
        $search = sanitize_text_field( $request[ 'search' ] );
        $type   = sanitize_text_field( $request[ 'type' ] );

        $objs = get_terms( array(
            'hide_empty' => false,
            'search'     => $search
        ) );
        $data = [];
        if ( ! empty( $objs ) ) {
            foreach ( $objs as $key => $obj ) {
                $data[] = $obj->taxonomy . ':' . $obj->slug;
            }
        }

        $data = apply_filters( 'loop_block_get_terms', $data, $request );

        return new WP_REST_Response( $data, 200 );
    }

    public function get_filter( WP_REST_Request $request ) {
        $search = sanitize_text_field( $request[ 'search' ] );
        $type   = sanitize_text_field( $request[ 'type' ] );

        $objs = get_terms( array(
            'hide_empty' => false,
            'search'     => $search
        ) );
        $data = [
            'orderby::all[All Time]',
            'orderby::week[This Week]',
            'orderby::month[This Month]',
            'orderby::year[This Year]'
        ];
        if ( ! empty( $objs ) ) {
            foreach ( $objs as $key => $obj ) {
                $data[] = 'taxQuery::' . $obj->taxonomy . '__' . $obj->slug . '[' . $obj->name . ']';
            }
        }

        $data = apply_filters( 'loop_block_get_filter', $data, $request );

        return new WP_REST_Response( $data, 200 );
    }

    public function get_ids( WP_REST_Request $request ) {
        $search = sanitize_text_field( $request[ 'search' ] );
        $type   = sanitize_text_field( $request[ 'type' ] );

        $data = [];
        if ( $type == 'user' ) {
            $objs = get_users( array(
                'search' => $search
            ) );
            if ( ! empty( $objs ) ) {
                foreach ( $objs as $key => $obj ) {
                    $data[] = $obj->ID . ':' . $obj->display_name;
                }
            }
        } elseif ( $type == 'taxonomy' ) {
            $objs = get_terms( array(
                'search' => $search
            ) );
            if ( ! empty( $objs ) ) {
                foreach ( $objs as $key => $obj ) {
                    $data[] = $obj->ID . ':' . $obj->name;
                }
            }
        } else {
            $objs = get_posts( array(
                'post_type' => $type,
                'search'    => $search
            ) );
            if ( ! empty( $objs ) ) {
                foreach ( $objs as $key => $obj ) {
                    $data[] = $obj->ID . ':' . $obj->post_title;
                }
            }
        }

        $data = apply_filters( 'loop_block_get_ids', $data, $request );

        return new WP_REST_Response( $data, 200 );
    }

    public function privileged_permission_callback() {
        return current_user_can( 'manage_options' );
    }
}

Loop_API::instance();
