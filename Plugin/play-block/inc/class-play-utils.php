<?php

defined( 'ABSPATH' ) || exit;

class Play_Utils {

    protected static $_instance = null;
    private $msg = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        $this->msg = new WP_Error();

        add_action( 'wp', array( $this, 'setup_message' ) );

        function play_add_message( $message, $type = '' ) {
            return Play_Utils::instance()->add_message( $message, $type );
        }

        function play_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
            return Play_Utils::instance()->get_template( $template_name, $args, $template_path, $default_path );
        }

        function play_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
            return Play_Utils::instance()->get_template_html( $template_name, $args, $template_path, $default_path );
        }

        do_action( 'play_block_utils_init', $this );
    }

    /**
     * Validate the Nonce
     */
    public function validate_nonce( $nonce = '' ) {
        $nonce = isset( $_REQUEST[ 'nonce' ] ) ? $_REQUEST[ 'nonce' ] : $nonce;
        if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
            return self::send_error();
        }

        return true;
    }

    /**
     * Send an Error Response
     *
     * @param $error string
     */
    public function send_error( $error = null ) {
        $error = ( $error ) ? $error : 'Invalid form field';

        return self::response( array(
            'status'  => 'error',
            'message' => $error
        ) );
    }

    /**
     * Send a response
     */
    public function response( $response ) {
        return wp_send_json( $response );
    }

    /**
     * Get template
     */
    public function get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        ob_start();
        $this->get_template( $template_name, $args, $template_path, $default_path );

        return ob_get_clean();
    }

    /**
     * Get template html
     */
    public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        $cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path ) ) );
        $template  = (string) wp_cache_get( $cache_key, 'play' );

        if ( ! $template ) {
            $template = $this->locate_template( $template_name, $template_path, $default_path );
            wp_cache_set( $cache_key, $template, 'play' );
        }

        if ( ! empty( $args ) && is_array( $args ) ) {
            extract( $args );
        }

        do_action( 'play_before_template_part', $template_name, $template_path, $template, $args );
        include( $template );
        do_action( 'play_before_template_part', $template_name, $template_path, $template, $args );
    }

    public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
        if ( ! $template_path ) {
            $template_path = '/templates/';
        }

        if ( ! $default_path ) {
            $default_path = plugin_dir_path( dirname( __FILE__ ) ) . '/templates/';
        }

        // Look within passed path within the theme - this is priority.
        $template = locate_template(
            array(
                trailingslashit( $template_path ) . $template_name,
                $template_name,
            )
        );

        // Get default template/.
        if ( ! $template ) {
            //$template = $default_path . $template_name;
            if ( 'php' != pathinfo( $template_name, PATHINFO_EXTENSION ) ) {
                $info = pathinfo( $template_name );
                $template_name = $info['filename'] . '.' . 'php';
            }
            
            $template = realpath( $default_path . $template_name );
        }

        // Return what we found.
        return apply_filters( 'play_locate_template', $template, $template_name, $template_path );
    }

    public function format_count( $number, $precision = 1 ) {
        if ( (int) $number == 0 ) {
            return '';
        }

        $base_num = $number;
        
        $precision = apply_filters( 'play_count_precision', $precision );

        $abbrevs = apply_filters( 'play_count_abbrevs', [ 12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => '' ] );

        foreach ( $abbrevs as $exponent => $abbrev ) {
            if ( abs( $number ) >= pow( 10, $exponent ) ) {
                $display  = $number / pow( 10, $exponent );
                $decimals = ( $exponent >= 3 && round( $display ) < 100 ) ? $precision : 0;
                $number   = number_format( $display, $decimals ) . $abbrev;
                break;
            }
        }

        return apply_filters( 'play_format_count', $number, $base_num, $precision );
    }

    public function add_message( $message, $type = 'success', $code = 'play_message' ) {
        $this->msg->add( $code, $message, $type ) ;
    }

    public function setup_message() {
        add_action( 'template_message', array( $this, 'render_message' ) );
        add_action( 'template_notices', array( $this, 'render_message' ) );
    }

    public function render_message() {
        $msg = '';
        if ( $this->msg->has_errors() ) {
            foreach ( $this->msg->get_error_codes() as $code ) {
                $type = $this->msg->get_error_data( $code );
                foreach ( $this->msg->get_error_messages( $code ) as $message ) {
                    $msg .= sprintf('<div class="%s">%s</div>', $type, $message );
                }
            }
        }
        echo $msg;
    }

    /**
     * Time duration
     *
     * @param $seconds int
     * @param $use     string
     * @param $simple  boolean
     */
    public function duration( $seconds, $use = null, $simple = false ) {
        if ( empty( $seconds ) || $seconds == 0 ) {
            return;
        }
        $periods = array(
            'years'   => 31556926,
            'Months'  => 2629743,
            'weeks'   => 604800,
            'days'    => 86400,
            'hours'   => 3600,
            'minutes' => 60,
            'seconds' => 1
        );

        $seconds  = (float) $seconds;
        $segments = array();
        foreach ( $periods as $period => $value ) {
            if ( $use && strpos( $use, $period[ 0 ] ) === false ) {
                continue;
            }
            $count = floor( $seconds / $value );
            if ( $count == 0 && ! $simple ) {
                continue;
            }
            if ( $count == 0 && $simple && !in_array($period, array('minutes', 'seconds')) ) {
                continue;
            }
            $segments[ strtolower( $period ) ] = $count;
            $seconds                           = $seconds % $value;
        }

        $string = array();
        foreach ( $segments as $key => $value ) {
            $segment_name = substr( $key, 0, - 1 );
            $segment      = $simple ? $value : ( $value . ' ' . $segment_name );
            if ( $value != 1 && ! $simple ) {
                $segment .= 's';
            }
            if ( $value < 10 && $simple ) {
                $segment = '0' . $segment;
            }
            $string[] = $segment;
        }

        $string = apply_filters( 'play_duration', $string, $seconds, $use, $simple );

        return $simple ? implode( ':', $string ) : implode( ', ', $string );
    }

    public function timeToMS( $time ) {
        $sec = 0;
        foreach ( array_reverse( explode(':', $time) ) as $k => $v ){
            $sec += pow( 60, (int)$k ) * (int)$v;
        }
        return $sec * 1000;
    }

    public function fixURL( $url ) {
        $site_url = site_url();
        if( parse_url($url, PHP_URL_HOST) == parse_url($site_url, PHP_URL_HOST) ){
            if(strpos($site_url, 'https://') !== false){
                $url = str_replace('http://', 'https://', $url);
            }else{
                $url = str_replace('https://', 'http://', $url);
            }
            return $url;
        }else{
            return false;
        }
    }

    public function getPath( $url ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        // root path with subfolder
        $root_path = get_home_path();
        // url start with site url
        $file = str_replace(site_url(), $root_path, $url );
        if($file == $url && is_multisite()){
            // url start with network site url
            $file = str_replace(network_site_url(), $root_path, $url );
        }
        return $file;
    }
}

Play_Utils::instance();
