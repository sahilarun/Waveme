<?php

defined( 'ABSPATH' ) || exit;

class Play_Block {

    protected static $_instance = null;
    private $name;
    private $version;
    private $build_url;
    private $setting_page;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        if ( ! function_exists( 'register_block_type' ) ) {
            // Gutenberg is not active.
            return;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $plugin          = get_plugin_data( dirname( dirname( __FILE__ ) ) . '/play-block.php' );
        $this->url       = $plugin['PluginURI'];
        $this->name      = str_replace( ' ', '-', strtolower( $plugin[ 'Name' ] ) );
        $this->version   = $plugin[ 'Version' ];
        $this->build_url = plugin_dir_url( dirname( __FILE__ ) ) . 'build/';
        $this->base_file = trailingslashit( basename( dirname( __DIR__ ) ) ) . 'play-block.php';
        $this->setting_page = apply_filters('play_setting_page_url', 'edit.php?post_type=station');

        add_action( 'init', array( $this, 'register' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'register_play_block' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 999 );
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        add_filter( 'template_include', array( $this, 'play_template' ), 99 );
        add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
        add_filter( 'ffl_play_types', array( $this, 'get_play_types' ) );

        add_action( 'init', array( $this, 'setup_pages' ), 99 );
        add_action( 'admin_menu', array( $this, 'add_options_link' ) );

        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
        add_filter( 'play_enable', array( $this, 'play_enable' ) );

        add_filter( 'plugin_action_links_'.$this->base_file, array( $this, 'plugin_action_links' ));

        do_action( 'play_block_init', $this );

        function play_get_option($key = '', $default = false) {
            $options = get_option( 'play_settings', [] );

            $value = $default;
            if ( isset( $options[ $key ] ) ) {
                if ( is_numeric( $options[ $key ] ) ) {
                    $value = $options[ $key ];
                } else {
                    $value = ! empty( $options[ $key ] ) ? $options[ $key ] : $default;
                }
            }

            if(empty($key)){
                return (is_array($options) ? $options : []);
            }

            if($key == 'play_types' && !$value){
                $value = array('station');
            }

            $value = apply_filters( 'play_get_option', $value, $key, $default );
            return apply_filters( 'play_get_option_' . $key, $value, $key, $default );
        }

        function play_add_settings($settings){

        }

        function play_enable(){
            return apply_filters( 'play_enable', false);
        }
    }

    public function add_options_link() {
        add_submenu_page( $this->setting_page, ' ', ' ', 'manage_options', 'play-separate');
        add_submenu_page( $this->setting_page, esc_html__( 'Settings', 'play-block' ), esc_html__( 'Settings', 'play-block' ), 'manage_options', 'play-settings', [$this, 'play_settings_page']);
    }

    public function plugin_action_links( $actions ) {
        $actions[] = '<a href="' . admin_url( $this->setting_page.'&page=play-settings' ) . '">' . esc_html__( 'Settings', 'play-block' ) . '</a>';
        return $actions;
    }

    public function admin_scripts() {
        wp_enqueue_media();
        wp_enqueue_style( 'play-admin-style', $this->build_url . 'editor.css' );
        wp_enqueue_script( 'play-admin-script', $this->build_url . 'admin.js', array(), $this->version, true );
        wp_add_inline_script( 'play-admin-script', '(function ($, window) { $(document).on("change", ".play-settings-form input[type=\'checkbox\']", function(e){ var isChecked = $(this).is(":checked"), tr = $(this).closest("tr"); isChecked ? tr.addClass("checked") : tr.removeClass("checked"); }); })(jQuery, window);' );
    }

    public function register() {
        wp_register_style(
            $this->name . '-editor',
            $this->build_url . 'editor.css',
            array(),
            $this->version
        );
        wp_register_script(
            $this->name . '-waveform',
            $this->build_url . 'libs/plyr/plyr.waveform.js',
            array(),
            $this->version,
            true
        );
        wp_register_script(
            $this->name . '-sortable',
            $this->build_url . 'libs/html.sortable.min.js',
            array(),
            $this->version,
            true
        );
        wp_register_script(
            $this->name . '-play',
            $this->build_url . 'play.js',
            array(),
            $this->version,
            true
        );
        wp_register_script(
            $this->name . '-editor',
            $this->build_url . 'editor.min.js',
            array(
                $this->name . '-waveform',
                $this->name . '-sortable',
                $this->name . '-play',
                'lodash',
                'wp-i18n',
                'wp-compose',
                'wp-element',
                'wp-components',
                'wp-editor',
                'wp-edit-post',
                'wp-plugins',
                'wp-data',
                'wp-rich-text',
                'wp-hooks',
                'jquery'
            ),
            $this->version,
            true
        );
        $script_data = array(
            'youtube_api_key'     => play_get_option( 'youtube_api_key' ),
            'rest' => array(
                'endpoints'        => [
                    'proxy'        => Play_API::instance()->get_play_api_url('proxy')
                ],
                'timeout' => 30000
            ),
            'types' => $this->get_play_block_types()
        );
        wp_localize_script( $this->name . '-play', 'play', $script_data );

        register_meta( 'post', 'type', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => array( $this, 'sanitize_type' )
        ) );
        register_meta( 'post', 'auto_type', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'boolean',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'post', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'stream', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ) );
        register_meta( 'post', 'stream_url', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ) );
        register_meta( 'post', 'waveform_data', array(
            'show_in_rest' => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'integer',
                    ),
                ),
            ),
            'single'       => true,
            'type'         => 'array',
            'default'      => [],
            'items'        => [
                'type' => 'integer'
            ]
        ) );
        register_meta( 'post', 'duration', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'integer',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'bpm', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'integer',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'downloadable', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'boolean',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'download_url', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ) );
        register_meta( 'post', 'purchase_title', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'purchase_url', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ) );
        register_meta( 'post', 'post-count-all', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'integer',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'like_count', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'integer',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'download_count', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'integer',
            'sanitize_callback' => 'sanitize_text_field'
        ) );
        register_meta( 'post', 'editor_note', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'wp_kses_post'
        ) );
        register_meta( 'post', 'copyright', array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'sanitize_callback' => 'wp_kses_post'
        ) );
        register_meta( 'post', 'captions', array(
            'show_in_rest' => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
            'single'       => true,
            'type'         => 'array',
            'items'        => [
                'type' => 'string'
            ]
        ) );

        // add show_in_rest and custom-fields to support Gutenberg
        $types = play_get_option( 'play_types' );
        global $wp_post_types;
        if ( ! empty( $types ) ) {
            foreach ( $types as $type ) {
                if ( post_type_exists( $type ) ) {
                    $wp_post_types[ $type ]->show_in_rest = true;
                    add_post_type_support( $type, 'custom-fields' );
                }
            }
        }
    }

    public function sanitize_type( $value ) {
      return ! empty( $value ) ? sanitize_text_field( $value ) : 'single';
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'play-block', false, basename( dirname( __DIR__ ) ) . '/languages' );
    }

    public function register_play_block() {
        $post_type = get_post_type();
        $types     = play_get_option( 'play_types' );
        $types     = apply_filters( 'play_block_type', $types );
        if ( is_array( $types ) && in_array( $post_type, $types ) ) {
            wp_enqueue_script( $this->name . '-editor' );
            wp_enqueue_style( $this->name . '-editor' );
        }
    }

    public function enqueue_scripts() {
        $suffix = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
        wp_register_style(
            'plyr',
            $this->build_url . 'libs/plyr/plyr.css'
        );

        wp_register_style(
            'playlist',
            $this->build_url . 'libs/plyr/plyr.playlist.css'
        );

        wp_register_style(
            $this->name . '-style',
            $this->build_url . 'style' . $suffix . '.css',
            ( $suffix == '' ? array( 'plyr', 'playlist' ) : array() ),
            $this->version
        );

        wp_enqueue_style( $this->name . '-style' );

        wp_register_script(
            'play-popper',
            $this->build_url . 'libs/popper.min.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'play-bootstrap',
            $this->build_url . 'libs/strap.min.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'play-hooks',
            $this->build_url . 'libs/wp.hooks.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'html5sortable',
            $this->build_url . 'libs/html.sortable.min.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'plyr',
            $this->build_url . 'libs/plyr/plyr.polyfilled.min.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'plyr-waveform',
            $this->build_url . 'libs/plyr/plyr.waveform.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'plyr-playlist',
            $this->build_url . 'libs/plyr/plyr.playlist.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'plyr-hls',
            $this->build_url . 'libs/plyr/hls.js',
            array(),
            $this->version,
            true
        );

        wp_register_script(
            'player',
            $this->build_url . 'player.js',
            array(),
            $this->version,
            true
        );

        $min_password_strength = (int) apply_filters( "play_min_password_strength", 3 );
        $js = array('jquery');
        if($min_password_strength > 0 && !is_user_logged_in()){
            $js[] = 'zxcvbn-async';
        }

        wp_register_script(
            $this->name,
            $this->build_url . 'play' . $suffix . '.js',
            ( $suffix == '' ? array_merge($js, array(
                'play-hooks',
                'play-popper',
                'play-bootstrap',
                'html5sortable',
                'plyr',
                'plyr-waveform',
                'plyr-playlist',
                'plyr-hls',
                'player'
            )) : $js ),
            $this->version,
            true
        );

        $script_data = array(
            'url'                  => $this->build_url,
            'login_url'            => wp_login_url(),
            'edit_url'             => Play_Upload::instance()->get_upload_edit_link(),
            'site_url'             => home_url(),
            'nonce'                => wp_create_nonce( 'wp_rest' ),
            'rest'                 => [
                'endpoints'        => [
                    'play'         => Play_API::instance()->get_play_api_url('play'),
                    'playlist'     => Play_API::instance()->get_play_api_url('playlist'),
                    'search'       => Play_API::instance()->get_play_api_url('search'),
                    'like'         => Play_API::instance()->get_play_api_url('like'),
                    'dislike'      => Play_API::instance()->get_play_api_url('dislike'),
                    'follow'       => Play_API::instance()->get_play_api_url('follow'),
                    'commments'    => Play_API::instance()->get_play_api_url('comments'),
                    'modal'        => Play_API::instance()->get_play_api_url('modal'),
                    'notification' => Play_API::instance()->get_play_api_url('notification'),
                    'upload'       => Play_API::instance()->get_play_api_url('upload'),
                    'proxy'        => Play_API::instance()->get_play_api_url('proxy'),
                    'upload_stream'=> Play_API::instance()->get_play_api_url('upload/stream'),
                    'profile'      => Play_API::instance()->get_play_api_url('profile'),
                    'auth'         => Play_API::instance()->get_play_api_url('auth'),
                    'generatepwd'  => Play_API::instance()->get_play_api_url('generatepwd')
                ],
                'timeout_notify'   => (int) apply_filters( "play_rest_timeout_notify", 30000 ),
                'timeout_count'    => (int) apply_filters( "play_rest_timeout_count", 10000 ),
                'timeout_redirect' => (int) apply_filters( "play_rest_timeout_redirect", 2000 ),
            ],
            'is_user_logged_in'    => is_user_logged_in(),
            'login_to_play'        => play_get_option( 'login_to_play' ),
            'disable_login_modal'  => play_get_option( 'disable_login_modal' ),
            'youtube_api_key'      => play_get_option( 'youtube_api_key' ),
            'ad_tagurl'            => play_get_option( 'ad_tagurl' ) && Play_Utils::instance()->fixURL( play_get_option( 'ad_tagurl' ) ) ? Play_API::instance()->get_play_api_url('adtag') : play_get_option( 'ad_tagurl' ),
            'ad_interval'          => play_get_option( 'ad_interval' ),
            'el_more'              => Play_Utils::instance()->get_template_html( 'blocks/more.php' ),
            'min_password_strength'=> $min_password_strength,
            'waveform'             => apply_filters( 'play_waveform', true ),
            'waveform_theme'       => apply_filters( 'play_waveform_theme', 1 ),
            'default_id'           => play_get_option( 'default_id' ) ? play_get_data(play_get_option('default_id')) : false,
            'player_history'       => (bool) play_get_option( 'player_history' ),
            'player_theme'         => apply_filters( "player_theme", '2' ),
            'i18n'                 => apply_filters( 'play_player_i18n', array(
                'clear'            => play_get_text( 'clear' ),
                'queue'            => play_get_text( 'queue' ),
                'nextup'           => play_get_text( 'queue-title' ),
                'empty'            => play_get_text( 'queue-empty' ),
                'speed'            => play_get_text( 'speed' ),
                'normal'           => play_get_text( 'normal' ),
                'quality'          => play_get_text( 'quality' ),
                'captions'         => play_get_text( 'captions' ),
                'disabled'         => play_get_text( 'disabled' ),
                'enabled'          => play_get_text( 'enabled' ),
                'advertisement'    => play_get_text( 'advertisement' ),
                'live'             => play_get_text( 'live' ),
                'error'            => play_get_text( 'player-error' ),
                'pwd'              => array(
                    'hint'         => play_get_text( 'pwd_hint' ),
                    'unknown'      => play_get_text( 'pwd_unknown' ),
                    'short'        => play_get_text( 'pwd_short' ),
                    'bad'          => play_get_text( 'pwd_bad' ),
                    'good'         => play_get_text( 'pwd_good' ),
                    'strong'       => play_get_text( 'pwd_strong' ),
                    'mismatch'     => play_get_text( 'pwd_mismatch' )
                )
            ) )
        );

        $role = play_get_option( 'ad_free_role' );
        $role = is_array($role) ? array_filter( $role ) : $role;
        if( $role ){
            $user = wp_get_current_user();
            if( count( array_intersect($role, $user->roles) ) > 0 ){
                unset( $script_data['ad_tagurl'] );
                unset( $script_data['ad_interval'] );
            }
        }

        $script_data = apply_filters( 'play_script_data', $script_data);

        wp_localize_script( $this->name, 'play', $script_data );

        wp_enqueue_script( $this->name );
    }

    public function play_template( $template ) {
        $default_file = $this->get_template_default_file();
        if ( $default_file ) {
            $template = locate_template( $default_file );
            if ( ! $template ) {
                $template = plugin_dir_path( dirname( __FILE__ ) ) . '/templates/' . $default_file;
            }
        }

        return $template;
    }

    public function use_product_template() {
        if ( is_singular( 'product' ) && class_exists( 'WooCommerce' ) ) {
            $product = wc_get_product( get_the_ID() );
            if ( $product->is_type( 'digital' ) || $product->is_type( 'external' ) ) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function get_play_types() {
        return play_get_option( 'play_types' );
    }

    public function get_template_default_file() {
        $default_file = '';

        if ( is_singular( 'station' ) || ( is_singular() && apply_filters( 'is_playable', get_the_ID() ) ) ) {
            $default_file = 'single-station.php';
        } elseif ( is_tax( get_object_taxonomies( 'station' ) ) || is_tax( get_object_taxonomies( 'product' ) ) || is_tax( get_object_taxonomies( 'download' ) ) ) {
            $object = get_queried_object();
            if ( is_tax( 'station_tag' ) || is_tax( 'genre' ) || is_tax( 'artist' ) || is_tax( 'mood' ) || is_tax( 'activity' ) ) {
                $default_file = 'taxonomy-' . $object->taxonomy . '.php';
            } else {
                $default_file = 'archive-station.php';
            }
        } elseif ( is_post_type_archive( 'station' ) || is_post_type_archive( 'product' ) || is_post_type_archive( 'download' ) ) {
            $default_file = 'archive-station.php';
        }

        return apply_filters( 'play_station_template_file', $default_file );
    }

    public function add_display_post_states( $post_states, $post ) {
        if ( (int) play_get_option( 'page_login' ) === $post->ID ) {
            $post_states[ 'page_for_login' ] = __( 'Login' );
        }

        if ( (int) play_get_option( 'page_upload' ) === $post->ID ) {
            $post_states[ 'page_for_upload' ] = __( 'Upload' );
        }

        if ( (int) play_get_option( 'page_download' ) === $post->ID ) {
            $post_states[ 'page_for_download' ] = __( 'Download' );
        }

        return $post_states;
    }

    public function setup_pages() {
        if ( is_admin() ) {
            if ( (int) play_get_option( 'page_login' ) ) {
                $id   = play_get_option( 'page_login' );
                $post = get_post( $id );
                if ( $post && strpos( $post->post_content, 'play_login_form' ) === false ) {
                    $post->post_content = $post->post_content . '<!-- wp:shortcode -->[play_login_form]<!-- /wp:shortcode -->';
                    wp_update_post( $post );
                }
            }

            if ( (int) play_get_option( 'page_upload' ) ) {
                $id   = play_get_option( 'page_upload' );
                $post = get_post( $id );
                if ( $post && strpos( $post->post_content, 'play_upload_form' ) === false ) {
                    $post->post_content = $post->post_content . '<!-- wp:shortcode -->[play_upload_form]<!-- /wp:shortcode -->';
                    wp_update_post( $post );
                }
            }

            if ( (int) play_get_option( 'page_download' ) ) {
                $id   = play_get_option( 'page_download' );
                $post = get_post( $id );
                if ( $post && strpos( $post->post_content, 'play_download' ) === false ) {
                    $post->post_content = $post->post_content . '<!-- wp:shortcode -->[play_download]<!-- /wp:shortcode -->';
                    wp_update_post( $post );
                }
            }
        }
    }

    public function register_settings() {
        $play_settings = $this->get_registered_settings();
        $options = array();
        foreach ( $play_settings as $section => $sections ) {
            $settings = isset( $sections['settings'] ) ? $sections['settings'] : array();
            $page = "play_settings_{$section}";

            add_settings_section(
                $page,
                __return_null(),
                '__return_false',
                $page
            );

            foreach ( $settings as $option ) {
                $callback     = array($this, 'play_' . $option['type'] . '_callback' );

                if ( !isset( $option['label_for'] ) ) {
                    $option['label_for'] = 'play_settings[' . $option['id'] . ']';
                }

                // move the old value
                $old_value = get_option( $option['id'] );
                if ( $old_value ) {
                    $options[$option['id']] = $old_value;
                    delete_option($option['id']);
                }

                if( $option['type'] === 'checkbox' && play_get_option($option['id']) ){
                    $class = 'checked ';
                    if(isset($option['class'])){
                        $class .= $option['class'];
                    }
                    $option['class'] = $class;
                }

                // Add the settings field
                add_settings_field(
                    'play_settings[' . $option['id'] . ']',
                    isset($option['name']) ? $option['name'] : '',
                    $callback,
                    $page,
                    $page,
                    $option
                );
            }
        }

        // update old values
        if( !empty($options) ){
            update_option( 'play_settings', $options );
        }
        register_setting( 'play_settings', 'play_settings', array($this, 'settings_sanitize' ) );
    }

    public function display_notices() {
        if ( ! empty( $_GET['page'] ) && ( 'play-settings' === $_GET['page'] ) ) {

            // Settings updated
            if ( ! empty( $_GET['settings-updated'] ) ) {
                echo sprintf('<div class="notice notice-success is-dismissible"> <p>%s</p> </div>', __('Settings updated', 'play-block'));
            }
        }
    }

    public function get_play_block_types() {
        $arr = array(
            'Single' => 'single',
            'Playlist' => 'playlist'
        );
        if(apply_filters('play_register_audio_type', true)){
            $arr['Album'] = 'album';
        }
        if(apply_filters('play_register_video_type', false)){
            $arr['Shot'] = 'shot';
        }
        return apply_filters('play_block_types', $arr);
    }

    public function play_enable() {
        return get_option(get_option(strrev('edoc_esahcrup_otavne')));
    }

    public function get_registered_settings() {
        $pages_options = array( '' => '- Select Page -' );
        $pages = get_pages();
        if ( $pages ) {
            foreach ( $pages as $page ) {
                $pages_options[ $page->ID ] = $page->post_title;
            }
        }

        $types_options = array( '' => '- Select Post Types -' );
        $_types_options = array();
        foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $id => $type ) {
            if ( ! empty( $type->labels->name ) ) {
                $types_options[$id] = $type->labels->name;
                $_types_options[$id] = $type->labels->name;
            }
        }

        global $wp_roles;
        $wp_roles_names = array_reverse( $wp_roles->role_names );
        $roles_options = array();
        foreach ($wp_roles_names as $role_slug => $role_name ) {
            $roles_options[$role_slug] = $role_name;
        }

        $play_settings = array(
            'general' => array(
                'title' => __( 'General', 'play-block' ),
                'settings' => array(
                    'play_types' => array(
                        'id'          => 'play_types',
                        'name'        => __( 'Post Types', 'play-block' ),
                        'desc'        => __( 'Select the post types that will have Play Block functionality.', 'play-block' ),
                        'type'        => 'multicheck',
                        'multiple'    => true,
                        'options'     => $_types_options
                    ),
                    'post_type' => array(
                        'id'          => 'post_type',
                        'name'        => __( 'Upload Post Type', 'play-block' ),
                        'desc'        => __( 'Select the upload post type for frontend submissions.', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $types_options
                    ),
                    'post_playlist_type' => array(
                        'id'          => 'post_playlist_type',
                        'name'        => __( 'Playlist Post Type', 'play-block' ),
                        'desc'        => __( 'Select the playlist post type for frontend submissions.', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $types_options
                    ),
                    'post_public' => array(
                        'id'          => 'post_public',
                        'name'        => __( 'Auto Approve Posts', 'play-block' ),
                        'label'       => __( 'Check this option to allow users to publish frontend submitted posts without approval.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => __( 'User submitted posts will be <code>private</code> by default.', 'play-block' ),
                    ),
                    'post_playlist_public' => array(
                        'id'          => 'post_playlist_public',
                        'name'        => __( 'Auto Approve Playlists', 'play-block' ),
                        'label'       => __( 'Check this option to allow users to publish frontend submitted playlists without approval.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => __( 'User submitted playlists will be <code>private</code> by default.', 'play-block' ),
                    ),
                    'post_verified_public' => array(
                        'id'          => 'post_verified_public',
                        'name'        => __( 'Auto Approve Verified Users', 'play-block' ),
                        'label'       => __( 'Check this option to allow verified users to publish posts and playlist without approval.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => sprintf( __( 'Verify by editing a user and checking the Verified checkbox. <a href="%s">View Users</a>.', 'play-block' ), admin_url( 'users.php' ) ),
                    ),
                    'post_upload' => array(
                        'id'          => 'post_upload',
                        'name'        => __( 'Allow File Uploads', 'play-block' ),
                        'label'       => __( 'Check this option to allow users to upload files on frontend submissions.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => __( 'Supports WordPress compatible audio and video file formats.', 'play-block' ),
                    ),
                    'post_upload_online' => array(
                        'id'          => 'post_upload_online',
                        'name'        => __( 'Allow Online Stream URLs', 'play-block' ),
                        'label'       => __( 'Check this option to allow users to enter online stream URLs on frontend submissions.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => __( 'Supports <code>YouTube.com</code> and <code>HeartThis.at</code>.', 'play-block' ),
                    ),
                    'purchaseable' => array(
                        'id'          => 'purchaseable',
                        'name'        => __( 'Allow Purchase URLs', 'play-block' ),
                        'label'       => __( 'Check this option to allow users to enter purchase link URLs on frontend submissions.', 'play-block' ),
                        'type'        => 'checkbox',
                        'desc'        => __( 'Supports Apple iTunes, Amazon, BeatPort and custom purchase URLs.', 'play-block' ),
                    ),
                    'upload_role' => array(
                        'id'          => 'upload_role',
                        'name'        => __( 'Upload Roles', 'play-block' ),
                        'type'        => 'multicheck',
                        'multiple'    => true,
                        'options'     => $roles_options,
                        'desc'        => __( 'Select the user roles who can submit frontend submissions.', 'play-block' ),
                    ),
                )
            ),
            'page' => array(
                'title' => __( 'Pages', 'play-block' ),
                'settings' => array(
                    'disable_login_modal' => array(
                        'id'          => 'disable_login_modal',
                        'name'        => __( 'Disable login modal', 'play-block' ),
                        'label'       => __( 'Check this option to disable login modal popup.', 'play-block' ),
                        'type'        => 'checkbox'
                    ),
                    'page_upload' => array(
                        'id'          => 'page_upload',
                        'name'        => __( 'Upload Page', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $pages_options,
                        'desc'        => __( 'This is the page where users will upload their files.<br> The <code>[play_upload_form]</code> shortcode must be on this page.', 'play-block' ),
                    ),
                    'page_download' => array(
                        'id'          => 'page_download',
                        'name'        => __( 'Download Page', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $pages_options,
                        'desc'        => __( 'This is the page where users will be redirected to download files.<br> The <code>[play_download]</code> shortcode must be on this page.', 'play-block' ),
                    ),
                    'page_login' => array(
                        'id'          => 'page_login',
                        'name'        => __( 'Login Page', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $pages_options,
                        'desc'        => __( 'This is the page where users will register and login.<br> The <code>[play_login_form]</code> shortcode must be on this page.', 'play-block' ),
                    ),
                    'page_rank' => array(
                        'id'          => 'page_rank',
                        'name'        => __( 'Rank Page', 'play-block' ),
                        'type'        => 'select',
                        'options'     => $pages_options,
                        'desc'        => __( 'This is the page where the ranks link to.<br> The <code>Loop Block</code> sorted by play count must be on this page.', 'play-block' ),
                    )
                )
            ),
            'player' => array(
                'title' => __( 'Player', 'play-block' ),
                'settings' => array(
                    'default_id' => array(
                        'id'          => 'default_id',
                        'name'        => __( 'Default Play ID', 'play-block' ),
                        'type'        => 'text',
                    ),
                    'youtube_api_key' => array(
                        'id'          => 'youtube_api_key',
                        'name'        => __( 'Youtube API Key', 'play-block' ),
                        'type'        => 'text',
                        'desc'        => sprintf( __( 'This your own personal Youtube API key to allow importing of watch URLs. You can obtain a key from the Google <a href="%s">Developer Console</a>.', 'play-block' ), 'https://console.developers.google.com/' ),
                    ),
                    'ad_tagurl' => array(
                        'id'          => 'ad_tagurl',
                        'name'        => __( 'VAST Ad Tag URL', 'play-block' ),
                        'type'        => 'text',
                        'mediaUpload' => true,
                        'desc'        => __( 'Enter a VAST compatible ad tag URL here. Test <a href="https://developers.google.com/interactive-media-ads/docs/sdks/html5/client-side/tags" target="_blank">Google Sample Tags</a>', 'play-block' ),
                    ),
                    'ad_interval' => array(
                        'id'          => 'ad_interval',
                        'name'        => __( 'Ad Interval', 'play-block' ),
                        'type'        => 'number',
                        'desc'        => __( 'Play advertisment after every x number of streams.', 'play-block' ),
                    ),
                    'ad_free_role' => array(
                        'id'          => 'ad_free_role',
                        'name'        => __( 'Ad-Free Roles', 'play-block' ),
                        'type'        => 'multicheck',
                        'multiple'    => true,
                        'options'     => $roles_options,
                        'desc'        => __( 'Select the user roles exempt from advertisments.', 'play-block' ),
                    ),
                    'login_to_play' => array(
                        'id'          => 'login_to_play',
                        'name'        => __( 'Login To Play', 'play-block' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Check this option to disable unregistered user to play.', 'play-block' ),
                    ),
                    'player_history' => array(
                        'id'          => 'player_history',
                        'name'        => __( 'Player History', 'play-block' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Check this option to enable player history.', 'play-block' ),
                    ),
                    'php_stream' => array(
                        'id'          => 'php_stream',
                        'name'        => __( 'Stream Media Files', 'play-block' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Check this option to use PHP to stream locally hosted media files. This helps mask the location of your media files.', 'play-block' ),
                    ),
                    'preview_length' => array(
                        'id'          => 'preview_length',
                        'name'        => __( 'Preview Length (Seconds)', 'play-block' ),
                        'type'        => 'number',
                        'desc'        => __( 'Only play x number of seconds for users.<br> <em>Stream Media Files must be checked</em>.', 'play-block' ),
                    ),
                    'preview_free_role' => array(
                        'id'          => 'preview_free_role',
                        'name'        => __( 'Preview-Free Roles', 'play-block' ),
                        'type'        => 'multicheck',
                        'multiple'    => true,
                        'options'     => $roles_options,
                        'desc'        => __( 'Select the user roles exempt from the preview limit.', 'play-block' ),
                    ),
                )
            ),
            'downloads' => array(
                'title' => __( 'Downloads', 'play-block' ),
                'settings' => array(
                    'downloadable' => array(
                        'id'            => 'downloadable',
                        'name'          => __( 'Require Registration', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to allow only registered users to download files.', 'play-block' ),
                    ),
                    'download_role' => array(
                        'id'          => 'download_role',
                        'name'        => __( 'Download Roles', 'play-block' ),
                        'type'        => 'multicheck',
                        'multiple'    => true,
                        'options'     => $roles_options,
                        'desc'        => __( 'Select the user roles that can download files.', 'play-block' ),
                    ),
                )
            ),
            'emails' => array(
                'title' => __( 'Emails', 'play-block' ),
                'settings' => array(
                    'email_activation' => array(
                        'id'            => 'email_activation',
                        'name'          => __( 'Require Email Activation', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to require email activation for new user registrations.<br> Put the <code>{activation.url}</code> in new user email content, Make sure the wp_mail function works', 'play-block' ),
                    ),
                    'email_newuser' => array(
                        'id'            => 'email_newuser',
                        'name'          => __( 'Customize New User Email', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to customize new user email.', 'play-block' ),
                        'class'         => 'play-option-group-newuser',
                        'group'         => 'play-option-group-newuser',
                    ),
                    'email_newuser_subject' => array(
                        'id'            => 'email_newuser_subject',
                        'name'          => __( 'New User Email Subject', 'play-block' ),
                        'type'          => 'text',
                        'std'           => __( 'Your {site.name} account has been created!', 'play-block' ),
                        'class'         => 'play-option-group-newuser',
                    ),
                    'email_newuser_content' => array(
                        'id'            => 'email_newuser_content',
                        'name'          => __( 'New User Email Content', 'play-block' ),
                        'type'          => 'rich_editor',
                        'std'           => __( 'Welcome to {site.name} <br> Click below link to login.<br>{login.url}', 'play-block' ),
                        'desc'          => __( 'Supported tokens: <code>{site.name}</code> <code>{site.url}</code> <code>{user.name}</code> <code>{user.email}</code> <code>{login.url}</code> <code>{activation.url}</code>', 'play-block' ),
                        'class'         => 'play-option-group-newuser',
                    ),
                    'email_retrievepwd' => array(
                        'id'            => 'email_retrievepwd',
                        'name'          => __( 'Customize Retrieve Password Email', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to customize retrieve password email.', 'play-block' ),
                        'class'         => 'play-option-group-retrievepwd',
                        'group'         => 'play-option-group-retrievepwd',
                    ),
                    'email_retrieve_password_title' => array(
                        'id'            => 'email_retrieve_password_title',
                        'name'          => __( 'Retrieve Password Email Subject', 'play-block' ),
                        'type'          => 'text',
                        'std'           => __( '[{site.name}] Password Reset', 'play-block' ),
                        'class'         => 'play-option-group-retrievepwd',
                    ),
                    'email_retrieve_password_message' => array(
                        'id'            => 'email_retrieve_password_message',
                        'name'          => __( 'Retrieve Password Email Content', 'play-block' ),
                        'type'          => 'rich_editor',
                        'std'           => __( 'It looks like you need to reset your password on the site. If this is correct, simply click the link below. If you were not the one responsible for this request, ignore this email and nothing will happen.<br>{resetpassword.url}', 'play-block' ),
                        'class'         => 'play-option-group-retrievepwd',
                        'desc'          => __( 'Supported tokens: <code>{site.name}</code> <code>{site.url}</code> <code>{user.name}</code> <code>{user.email}</code> <code>{login.url}</code> <code>{resetpassword.url}</code>', 'play-block' )
                    ),
                )
            ),
            'endpoints' => array(
                'title' => __( 'Endpoints', 'play-block' ),
                'settings' => array(
                    'title-1' => array(
                        'id'          => 'title-1',
                        'title'       => __( 'Endpoints', 'play-block' ),
                        'desc'        => __( 'Play Block offers you the ability to create a custom URL structure for your permalinks and archives. Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links.', 'play-block' ),
                        'type'        => 'html',
                        'class'       => 'section-placeholder',
                    ),
                    'station_base' => array(
                        'id'          => 'station_base',
                        'name'        => __( 'Station base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'station',
                        'desc'        => '%genre%, %author%, %artist%'
                    ),
                    'genre_base' => array(
                        'id'          => 'genre_base',
                        'name'        => __( 'Genre base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'genre',
                    ),
                    'artist_base' => array(
                        'id'          => 'artist_base',
                        'name'        => __( 'Artist base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'artist',
                        'class'       => apply_filters('play_register_audio_type', true) ? '' : 'play-option-hide',
                    ),
                    'mood_base' => array(
                        'id'          => 'mood_base',
                        'name'        => __( 'Mood base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'mood',
                        'class'       => apply_filters('play_register_audio_type', true) ? '' : 'play-option-hide',
                    ),
                    'activity_base' => array(
                        'id'          => 'activity_base',
                        'name'        => __( 'Activity base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'activity',
                        'class'       => apply_filters('play_register_audio_type', true) ? '' : 'play-option-hide',
                    ),
                    'tag_base' => array(
                        'id'          => 'tag_base',
                        'name'        => __( 'Tag base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'station_tag',
                    ),
                    'title-2' => array(
                        'id'          => 'title-2',
                        'title'       => __( 'User endpoints', 'play-block' ),
                        'desc'        => __( 'Endpoints are appended to user page URLs to handle specific actions. They should be unique. <br>NOTE: Go to "Appearance > Menus" to change the user URLS after you change those endpoints.', 'play-block' ),
                        'type'        => 'html',
                        'class'       => 'section-placeholder',
                    ),
                    'user_base' => array(
                        'id'          => 'user_base',
                        'name'        => __( 'User base', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'user',
                    ),
                    'stations_endpoint' => array(
                        'id'          => 'stations_endpoint',
                        'name'        => __( 'Stations', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'stations',
                    ),
                    'albums_endpoint' => array(
                        'id'          => 'albums_endpoint',
                        'name'        => __( 'Albums', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'albums',
                        'class'       => apply_filters('play_register_audio_type', true) ? '' : 'play-option-hide',
                    ),
                    'playlists_endpoint' => array(
                        'id'          => 'playlists_endpoint',
                        'name'        => __( 'Playlists', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'playlists',
                    ),
                    'likes_endpoint' => array(
                        'id'          => 'likes_endpoint',
                        'name'        => __( 'Likes', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'likes',
                    ),
                    'followers_endpoint' => array(
                        'id'          => 'followers_endpoint',
                        'name'        => __( 'Followers', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'followers',
                    ),
                    'following_endpoint' => array(
                        'id'          => 'following_endpoint',
                        'name'        => __( 'Following', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'following',
                    ),
                    'download_endpoint' => array(
                        'id'          => 'downloads_endpoint',
                        'name'        => __( 'Downloads', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'download',
                    ),
                    'profile_endpoint' => array(
                        'id'          => 'profile_endpoint',
                        'name'        => __( 'Profile', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'profile',
                    ),
                    'upload_endpoint' => array(
                        'id'          => 'upload_endpoint',
                        'name'        => __( 'Upload', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'upload',
                    ),
                    'notifications_endpoint' => array(
                        'id'          => 'notifications_endpoint',
                        'name'        => __( 'Notifications', 'play-block' ),
                        'type'        => 'text',
                        'std'         => 'notifications',
                    ),
                )
            ),
            'advanced' => array(
                'title' => __( 'Advanced', 'play-block' ),
                'settings' => array(
                    'title-1' => array(
                        'id'          => 'title-1',
                        'title'       => __( 'Notifications', 'play-block' ),
                        'type'        => 'html',
                        'class'       => 'section-placeholder',
                    ),
                    'upload_notification' => array(
                        'id'            => 'upload_notification',
                        'name'          => __( 'Upload', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify followers when upload something.', 'play-block' ),
                    ),
                    'download_notification' => array(
                        'id'            => 'download_notification',
                        'name'          => __( 'Download', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify user when upload get downloaded.', 'play-block' ),
                    ),
                    'playlist_notification' => array(
                        'id'            => 'playlist_notification',
                        'name'          => __( 'Playlist', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify user when post added to playlist.', 'play-block' ),
                    ),
                    'follow_notification' => array(
                        'id'            => 'follow_notification',
                        'name'          => __( 'Follow', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify user when get followed.', 'play-block' ),
                    ),
                    'like_notification' => array(
                        'id'            => 'like_notification',
                        'name'          => __( 'Like', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify user when post get liked.', 'play-block' ),
                    ),
                    'comment_notification' => array(
                        'id'            => 'comment_notification',
                        'name'          => __( 'Comment', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify user when post get commented', 'play-block' ),
                    ),
                    'post_notification' => array(
                        'id'            => 'post_notification',
                        'name'          => __( 'Post', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to notify followers when post something.', 'play-block' ),
                    ),
                    'title-2' => array(
                        'id'          => 'title-2',
                        'title'       => __( 'Admin dashboard', 'play-block' ),
                        'type'        => 'html',
                        'class'       => 'section-placeholder',
                    ),
                    'hide_admin' => array(
                        'id'            => 'hide_admin',
                        'name'          => __( 'Disable Dashboard Access', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to disable <code>wp-admin</code> dashboard acccess for all users excluding site administrators.', 'play-block' ),
                    ),
                    'show_admin_bar' => array(
                        'id'            => 'show_admin_bar',
                        'name'          => __( 'Display Admin Bar', 'play-block' ),
                        'type'          => 'checkbox',
                        'label'         => __( 'Check this option to display the admin top bar for site administrators.', 'play-block' ),
                    ),
                )
            )
        );

        return apply_filters('play_settings', $play_settings);
    }

    public function play_settings_page() {
        do_action('play_maybe_flush_rewrite_rules');
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Settings' ); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php
                $tabs = $this->get_registered_settings();
                foreach ( $tabs as $tab_id => $tab ) {
                    if(isset($tab['hide'])){
                        continue;
                    }
                    $admin_url = admin_url( 'edit.php' );
                    $tab_url = add_query_arg(
                        array(
                            'settings-updated' => false,
                            'post_type' => 'station',
                            'page' => 'play-settings',
                            'tab'  => $tab_id,
                        ),
                        $admin_url
                    );
                    $active = ( $active_tab === $tab_id )
                        ? ' nav-tab-active'
                        : '';

                    echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $tab['title'] ) . '</a>';
                }
                ?>
            </nav>
            <form method="post" action="options.php" class="play-settings-form">
                <?php

                settings_fields( 'play_settings' );

                do_action( 'play_settings_tab_top_' . $active_tab );

                do_settings_sections( 'play_settings_' . $active_tab );

                do_action( 'play_settings_tab_bottom_' . $active_tab  );

                submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function settings_sanitize( $input = array() ) {
        update_option( 'play_queue_flush_rewrite_rules', 'yes' );

        $options = play_get_option();
        $output = array_merge( $options, $input );

        array_walk_recursive($output, 'sanitize_text_field');

        if ( ! empty( $output ) && is_array( $output ) ) {
          foreach( $output as $key => $value ) {
            if( is_array( $value ) ) {
                $output[ $key ] = array_filter( $value );
            }
            // endpoints
            if(is_string($value) && (strpos($key, 'endpoint') != false || strpos($key, 'user_base') != false) ){
                $output[ $key ] = sanitize_title( $value );
            }
          }
        }

        return $output;
    }

    public function play_html_callback( $args ){
        $html = '</tbody></table>';
        if ( isset($args['title']) ) {
            $html .= '<h3>' . esc_html( $args['title'] ) . '</h3>';
        }
        if(isset($args['desc'])){
            $html .= '<div class="description"> ' . wp_kses_post( $args['desc'] ) . '</div>';
        }
        $html .= '<style>.section-placeholder{display: none;}</style><table class="form-table"><tbody>';
        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_checkbox_callback( $args ) {
        $value = play_get_option( $args['id'] );
        $name = 'play_settings[' . esc_attr( $args['id'] ) . ']';
        $checked = $value ? 'checked="checked"' : '';

        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html    .= '<input type="hidden" name="' . esc_attr( $name ) . '" '.$checked.' value="0" />';
        $html    .= '<input type="checkbox" id="'.esc_attr( $name ).'" name="' . esc_attr( $name ) . '" '.$checked.' value="1" />';
        if(isset($args['label'])){
            $html .= '<label for="'.esc_attr( $name ).'">'.wp_kses_post( $args['label'] ).'</label>';
        }
        if(isset($args['group'])){
            $html .= '<style>.'.$args['group'].' ~ .'.$args['group'].'{display: none}.'.$args['group'].'.checked ~ .'.$args['group'].'{display: table-row}</style>';
        }
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_text_callback( $args ){
        $value = play_get_option( $args['id'] );
        if ( !$value && isset($args['std']) ) {
            $value = $args['std'];
        }
        $name = 'name="play_settings[' . esc_attr( $args['id'] ) . ']"';

        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

        $disabled = isset( $args['disabled'] ) ? ' disabled="disabled"' : '';

        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html .= '<input class="regular-text" type="text" id="play_settings['.esc_attr( $args['id'] ). ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $disabled . $placeholder . ' />';

        if(isset($args['mediaUpload'])){
            $html .= '<button type="button" class="button upload-btn">Upload</button>';
        }
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }
        if(isset($args['class']) && strpos($args['class'], 'hide') !== false){
            $html .= '<style>.'.$args['class'].'{display: none}</style>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_textarea_callback( $args ){
        $value = play_get_option( $args['id'] );
        if ( !$value && isset($args['std']) ) {
            $value = $args['std'];
        }
        $name = 'name="play_settings[' . esc_attr( $args['id'] ) . ']"';

        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

        $disabled = isset( $args['disabled'] ) ? ' disabled="disabled"' : '';
        $rows     = ( isset( $args['rows'] ) && ! is_null( $args['rows'] ) ) ? esc_attr( $args['rows'] ) : '5';
        
        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html .= '<textarea class="large-text" rows="'.$rows.'" id="play_settings['.esc_attr( $args['id'] ). ']" ' . $name . $disabled . $placeholder . '>'.esc_textarea( stripslashes( $value ) ).'</textarea>';
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_number_callback( $args ){
        $value = play_get_option( $args['id'] );
        $name = 'name="play_settings[' . esc_attr( $args['id'] ) . ']"';

        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

        $disabled = isset( $args['disabled'] ) ? ' disabled="disabled"' : '';

        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html .= '<input class="small-text" type="number" min="0" id="play_settings['.esc_attr( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $disabled . $placeholder . ' />';
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_select_callback( $args ) {
        $value = play_get_option( $args['id'] );
        if(!$value){
            $value = isset($args['multiple']) ? array() : '';
        }

        // If the Select Field allows Multiple values, save as an Array
        $name_attr = 'play_settings[' . esc_attr( $args['id'] ) . ']';
        $name_attr = ( isset($args['multiple']) ) ? $name_attr . '[]' : $name_attr;

        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html .= '<select class="regular-text" id="play_settings[' . $args['id']. ']" name="' . $name_attr .'" '. ( isset( $args['multiple'] ) ? 'multiple="true"' : '' ) . '>';

        foreach ( $args['options'] as $option => $name ) {

            if ( isset($args['multiple']) ) {
                // Do an in_array() check to output selected attribute for Multiple
                $html .= '<option value="' . esc_attr( $option ) . '" ' . ( ( in_array( $option, $value ) ) ? 'selected="true"' : '' ) . '>' . esc_html( $name ) . '</option>';
            } else {
                $selected = selected( $option, $value, false );
                $html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
            }

        }

        $html .= '</select>';
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_multicheck_callback( $args ) {
        $value = play_get_option( $args['id'] );
        if(!$value){
            $value = array();
        }

        $name_attr = 'play_settings[' . esc_attr( $args['id'] ) . '][]';

        $html = '';
        if ( isset($args['subname']) ) {
            $html .= '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }
        $html .= '<fieldset>';
        if ( isset( $args['options'] ) && ! empty( $args['options'] ) ) {
          foreach ( $args['options'] as $option => $name ) {
            $checked = isset( $value[ $option ] ) || in_array( $option, $value );
            $html .= '<label><input name="' . esc_attr( $name_attr ) . '" type="checkbox" value="' . esc_attr( $option ) . '" ' . ( $checked ? 'checked="true"' : '' ) . '/>&nbsp;';
            $html .= esc_html( $name ) . '</label><br>';
          }
        }

        $html .= '<input name="' . esc_attr( $name_attr ) . '" type="hidden" value=""/>&nbsp;';

        $html .= '</fieldset>';
        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

    public function play_rich_editor_callback( $args ) {
        $value = play_get_option( $args['id'] );
        if ( !$value && isset($args['std']) ) {
            $value = $args['std'];
        }

        $rows = isset( $args['size'] ) ? $args['size'] : 10;

        ob_start();

        if ( isset($args['subname']) ) {
            echo '<h4>' . esc_html( $args['subname'] ) . '</h4>';
        }

        wp_editor( stripslashes( $value ), 'play_settings_' . esc_attr( $args['id'] ), array(
            'textarea_name' => 'play_settings[' . esc_attr( $args['id'] ) . ']',
            'textarea_rows' => absint( $rows )
        ) );

        $html = ob_get_clean();

        if(isset($args['desc'])){
            $html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';
        }

        echo apply_filters( 'play_after_setting_output', $html, $args );
    }

}

Play_Block::instance();
