<?php

defined( 'ABSPATH' ) || exit;

class Play_Extension {

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
        $this->init();
    }

    public function init() {
        $this->setting_page = apply_filters('play_setting_page_url', 'edit.php?post_type=station');
        add_action( 'admin_menu', array( $this, 'add_options_link' ) );

        do_action( 'play_block_import_init', $this );
    }

    public function add_options_link() {
        add_submenu_page( $this->setting_page, esc_html__( 'Import', 'play-block' ), esc_html__( 'Extensions', 'play-block' ), 'manage_options', 'play-extension', [$this, 'play_extension_page']);
    }

    public function play_extension_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Extensions' ); ?></h1>
            <p><?php esc_html_e( 'These add functionality to your site.' ); ?></p>
            
        </div>
        <?php
    }

}

Play_Extension::instance();
