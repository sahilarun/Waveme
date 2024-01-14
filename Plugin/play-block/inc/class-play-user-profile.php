<?php

defined( 'ABSPATH' ) || exit;

class Play_User_Profile {

    protected static $_instance = null;
    private $users_can_register = null;
    private $page_login = null;
    private $email_activation = false;
    private $meta_key_activate_code = 'activation_code';
    private $meta_key_activate_link = 'activation_link';
    private $meta_key_activated = 'email_activated';
    private $form_wrapper = '<div id="login-form">%1$s</div>';
    private $rp_cookie    = 'wp-resetpass-' . COOKIEHASH;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        $this->users_can_register = get_option( 'users_can_register' ) ? true : false;
        $this->email_activation   = play_get_option( 'email_activation' ) ? true : false;
        $this->page_login         = play_get_option( 'page_login' );

        add_action( 'admin_notices', array( $this, 'admin_notices' ), 1 );
        add_action( 'user_register', array( $this, 'activate_email' ), 10, 1 );
        add_filter( 'wp_authenticate_user', array( $this, 'authenticate_user' ), 10, 2 );
        add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );

        add_action( 'init', array( $this, 'set_cookie' ), 9 );
        add_action( 'set_logged_in_cookie', array( $this, 'set_logged_in_cookie' ) );

        // login page
        add_filter( 'login_url', array( $this, 'login_url' ), 10, 3 );
        add_filter( 'register_url', array( $this, 'register_url' ), 10 );
        add_filter( 'lostpassword_url', array( $this, 'lostpassword_url' ), 99, 2 );

        // add_filter( 'wp_mail', array( $this, 'wp_mail' ), 10 );
        add_filter( 'wp_new_user_notification_email', array( $this, 'new_user_notification_email' ), 10, 3 );

        add_filter( 'retrieve_password_title', array( $this, 'retrieve_password_title' ), 10, 3 );
        add_filter( 'retrieve_password_message', array( $this, 'retrieve_password_message' ), 10, 4 );

        add_filter( 'play_modal_login_form', array( $this, 'get_login_form' ) );

        add_shortcode( 'wp_login_form', array( $this, 'wp_login_form_shortcode' ) );
        add_shortcode( 'wp_register_form', array( $this, 'wp_register_form_shortcode' ) );
        add_shortcode( 'wp_lostpassword_form', array( $this, 'wp_lostpassword_form_shortcode' ) );
        add_shortcode( 'play_login_form', array( $this, 'login_form_shortcode' ) );
        add_shortcode( 'play_profile_form', array( $this, 'profile_form_shortcode' ) );

        add_action( 'template_redirect', array( $this, 'logged_in_redirect' ), 5 );
        add_action( 'template_redirect', array( $this, 'activate_user' ) );

        add_action( 'play_auth',  array( $this, 'play_auth' ) );
        add_action( 'play_update_profile',  array( $this, 'update_profile' ) );

        add_filter( 'manage_users_custom_column', array( $this, 'user_row' ), 10, 3 );
        add_filter( 'manage_users_columns', array( $this, 'user_column' ), 10, 1 );

        function wp_register_form( $args = array() ) {
            return Play_User_Profile::instance()->wp_register_form( $args );
        }

        function wp_lostpassword_form( $args = array() ) {
            return Play_User_Profile::instance()->wp_lostpassword_form( $args );
        }

        function wp_profile_form( $user_id ) {
            return Play_User_Profile::instance()->wp_profile_form( $user_id );
        }

        do_action( 'play_block_user_profile_init', $this );
    }

    public function wp_mail( $arr ) {
        $mail = get_option('_mail');
        update_option('_mail', $mail.json_encode($arr));
        return $arr;
    }

    public function site_url( $url, $path, $scheme, $blog_id ){
        if($this->page_login && (strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) == false) && $path == 'wp-login.php' && $scheme == 'login_post'){
            $url = add_query_arg( 'action', 'login', get_permalink( $this->page_login ) );
        }
        return $url;
    }

    public function new_user_notification_email($email, $user, $blogname){
        if( play_get_option( 'email_newuser' ) ){
            $email['subject'] = $this->replace_token( play_get_option( 'email_newuser_subject' ), $user );
            $email['message'] = $this->replace_token( play_get_option( 'email_newuser_content' ), $user );
        }
        return apply_filters('play_block_newuser_email', $email);
    }

    public function retrieve_password_title($title, $user_login, $user_data){
        if( $this->page_login && play_get_option( 'email_retrievepwd' ) ){
            $title = $this->replace_token( play_get_option( 'email_retrieve_password_title' ), $user_data );
        }
        return apply_filters('play_block_retrieve_password_title', $title, $user_login, $user_data);
    }

    public function retrieve_password_message($message, $key, $user_login, $user_data){
        if( $this->page_login && play_get_option( 'email_retrievepwd' ) ){
            $locale = get_user_locale( $user_data );
            $url = get_permalink( $this->page_login ).'?action=rp&key='.$key.'&login='.rawurlencode( $user_login ).'&wp_lang='.$locale;
            $message = $this->replace_token( play_get_option( 'email_retrieve_password_message' ), $user_data, array('resetpassword.url' => $url) );
        }
        return apply_filters('play_block_retrieve_password_message', $message, $key, $user_login, $user_data);
    }

    public function replace_token($str, $user, $arr = array()){
        $activation_url = get_user_meta($user->id, $this->meta_key_activate_link, true);
        $tokens = array(
            'site.name'  => get_bloginfo( 'name' ),
            'site.url'   => home_url(),
            'login.url'  => wp_login_url(),
            'activation.url' => $activation_url,
            'user.name'  => $user->user_login,
            'user.email' => $user->user_email
        );
        $tokens = wp_parse_args($tokens, $arr);
        foreach ($tokens as $key => $value) {
            $str = str_replace('{'.$key.'}', $value, $str);
        }
        return $str;
    }

    public function authenticate_user( $user, $password ) {
        $activated = get_user_meta( $user->ID, $this->meta_key_activated, true );
        if ( '' === $activated ) {
            return $user;
        }
        if ( $this->email_activation && ( 1 !== (int) $activated ) ) {
            return new WP_Error( 'user_not_activated', play_get_text( 'not-activated' ) );
        }
        return $user;
    }

    public function activate_email( $user_id ) {
        if ( !$this->email_activation ) {
            return;
        }

        $user = get_userdata( $user_id );

        $code = md5( time() );
        update_user_meta( $user_id, $this->meta_key_activated, 0 );
        update_user_meta( $user_id, $this->meta_key_activate_code, $code );

        $str = '/?act=' . $code . '-' . $user_id;
        $url = get_site_url() . $str;

        if ( $this->page_login ) {
            $url = get_permalink( $this->page_login ) . $str;
        }

        update_user_meta( $user_id, $this->meta_key_activate_link, $url );
    }

    public function activate_user() {
        if ( isset( $_GET[ 'act' ] ) ) {
            $data = explode('-', $_GET[ 'act' ]);
            if(count($data) == 2){
                $user_id    = $data[1];
                $data_code  = $data[0];
                $code = get_user_meta( $user_id, $this->meta_key_activate_code, true );
                $activated = get_user_meta( $user_id, $this->meta_key_activated, true );
                if ( $code == $data_code ) {
                    if($activated == '1'){
                        play_add_message( play_get_text( 'already-activated' ), 'success' );
                    }else{
                        update_user_meta( $user_id, $this->meta_key_activated, 1 );
                        play_add_message( play_get_text( 'activated' ), 'success' );
                    }
                }
            }
        }
    }

    public function play_auth( $request ){
        $action = isset($request[ 'form-action' ]) ? $request[ 'form-action' ] : '';

        switch ( $action ) {
            case 'login':
                $this->wp_login($request);
                break;
            case 'register':
                $this->wp_register($request);
                break;
            case 'lostpwd':
                $this->wp_lostpwd($request);
                break;
            case 'resetpwd':
                $this->wp_resetpwd($request);
                break;
        }
    }

    public function logged_in_redirect() {
        if ( is_user_logged_in() && $this->page_login && is_page( $this->page_login ) ) {
            wp_redirect( get_author_posts_url( get_current_user_id() ) );
            exit;
        }
    }

    public function wp_login($request){
        if(isset( $request[ 'log' ] ) && isset( $request[ 'pwd' ] ) ) {
            $secure_cookie = '';
            // If the user wants ssl but the session is not ssl, force a secure cookie.
            if ( !empty($request['log']) && !force_ssl_admin() ) {
                $user_name = sanitize_user($request['log']);
                $user = get_user_by( 'login', $user_name );

                if ( ! $user && strpos( $user_name, '@' ) ) {
                    $user = get_user_by( 'email', $user_name );
                }

                if ( $user ) {
                    if ( get_user_option('use_ssl', $user->ID) ) {
                        $secure_cookie = true;
                        force_ssl_admin(true);
                    }
                }
            }

            $credentials = array();
            if ( ! empty( $request['log'] ) ) {
                $credentials['user_login'] = wp_unslash( $request['log'] );
            }
            if ( ! empty( $request['pwd'] ) ) {
                $credentials['user_password'] = $request['pwd'];
            }
            if ( ! empty( $request['rememberme'] ) ) {
                $credentials['remember'] = $request['rememberme'];
            }
            if ( ! empty( $credentials['remember'] ) ) {
                $credentials['remember'] = true;
            } else {
                $credentials['remember'] = false;
            }

            $errors = new WP_Error();
            $errors = apply_filters( 'play_pre_login_errors', $errors, $request, $credentials, $secure_cookie );
            if ( $errors->has_errors() ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'   => apply_filters( 'play_pre_login_error_msg', $this->get_error($errors) )
                    )
                );
            }

            $user = wp_signon( $credentials, $secure_cookie );

            if ( is_wp_error( $user ) ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'   => apply_filters( 'play_login_error', $this->get_error($user) )
                    )
                );
            }

            do_action( 'play_user_login', $user );
            wp_set_current_user( $user->ID );

            $url = get_author_posts_url( $user->ID );
            if( current_user_can( 'activate_plugins' ) ){
                $url = admin_url();
            }

            $data = array(
                'status'  => 'success',
                'msg'     => apply_filters( 'play_login_success', play_get_text( 'login-success' ) ),
                'redirect'=> apply_filters( 'play_login_redirect_url', $url, $user->ID ),
                'nonce'   => wp_create_nonce('wp_rest')
            );

            return Play_Utils::instance()->response( $data );
        }
    }

    public function wp_register($request) {
        if(isset( $request[ 'user_login' ] ) && isset( $request[ 'user_email' ] ) && isset( $request[ 'pwd' ] ) ) {
            $user_login = wp_unslash( $request[ 'user_login' ] );
            $user_email = wp_unslash( $request[ 'user_email' ] );
            $user_pass  = $request[ 'pwd' ];

            $errors = new WP_Error();
            $errors = apply_filters( 'play_pre_register_errors', $errors, $request, $user_login, $user_email, $user_pass );
            if ( $errors->has_errors() ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'   => apply_filters( 'play_pre_register_error_msg', $this->get_error($errors) )
                    )
                );
            }

            $user_id = wp_create_user( $user_login, $user_pass, $user_email );

            if ( is_wp_error( $user_id ) ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'    => apply_filters( 'play_register_error', $this->get_error($user_id) )
                    )
                );
            }

            do_action( 'play_user_register', $user_id );

            $redirect = '';
            $msg = apply_filters( 'play_register_complete', play_get_text( 'register-complete' ) );
            if($this->email_activation){
                $msg .= apply_filters( 'play_register_activate_email', play_get_text( 'activate-email' ) );
            }else{
                $redirect = apply_filters( 'play_register_redirect_url', get_author_posts_url( $user_id ), $user_id );
                $this->autologin($user_id);
            }

            wp_send_new_user_notifications($user_id, apply_filters('play_new_user_email_to', 'both'));

            return Play_Utils::instance()->response(
                array(
                    'status'   => 'success',
                    'msg'      => $msg,
                    'redirect' => $redirect,
                    'nonce'    => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
                )
            );

        }
    }

    public function wp_lostpwd($request){
        if(isset( $request[ 'user_login' ] ) ) {

            $errors = new WP_Error();
            $errors = apply_filters( 'play_pre_lostpwd_errors', $errors, $request, $credentials, $secure_cookie );
            if ( $errors->has_errors() ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'   => apply_filters( 'play_pre_lostpwd_error_msg', $this->get_error($errors) )
                    )
                );
            }

            $error = retrieve_password();
            if ( is_wp_error( $error ) ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'    => apply_filters( 'play_lostpwd_error', $this->get_error($error) )
                    )
                );
            } else {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'success',
                        'msg'    => apply_filters( 'play_lostpwd_complete', play_get_text( 'lost-complete' ) )
                    )
                );
            }
        }
    }

    public function wp_resetpwd($request){
        if(isset( $request[ 'rp_key' ] ) && isset( $request[ 'rp_login' ] ) ) {
            $rp_key = sanitize_text_field( $request['rp_key'] );
            $rp_login = sanitize_text_field( $request['rp_login'] );

            $user = check_password_reset_key( $rp_key, $rp_login );

            if ( is_wp_error( $user ) ) {
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'error',
                        'msg'    => apply_filters( 'play_reset_error', $this->get_error($user) )
                    )
                );
            } else {
                reset_password( $user, $request['pwd'] );
                $redirect = $this->page_login ? get_permalink($this->page_login) : '';
                return Play_Utils::instance()->response(
                    array(
                        'status' => 'success',
                        'msg'    => apply_filters( 'play_reset_complete', play_get_text( 'reset-complete' ) ),
                        'redirect' => $redirect
                    )
                );
            }
        }
    }

    public function autologin( $user_id ) {
        if ( ! is_user_logged_in() ) {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
        }
    }

    public function get_error( $error ){
        $errors = '';
        if ( $error->has_errors() ) {
            foreach ( $error->get_error_codes() as $code ) {
                $severity = $error->get_error_data( $code );
                foreach ( $error->get_error_messages( $code ) as $error_message ) {
                    if($code == 'incorrect_password'){
                        $errors .= play_get_text('login-error') . "<br />";
                    }else{
                        $errors .= $error_message . "<br />";
                    }
                }
            }
        }
        return $errors;
    }

    public function user_row( $val, $column_name, $user_id ) {
        switch ( $column_name ) {
            case 'activate' :
                $activated = get_user_meta( $user_id, $this->meta_key_activated, true );
                if ( 1 == $activated ) {
                    return play_get_text( 'yes' );
                } else {
                    $link = get_user_meta( $user_id, $this->meta_key_activate_link, true );
                    return play_get_text( 'no' ) . '<br>' . ( $link ? sprintf( '<a href="%s">%s</a>', esc_url( $link ), play_get_text( 'activate' ) ) : '' );
                }
            case 'verified' :
                $verified = get_user_meta( $user_id, 'verified', true );
                if ( 'true' == $verified ) {
                    return play_get_text( 'yes' );
                } else {
                    return play_get_text( 'no' );
                }
            default:
        }

        return $val;
    }

    public function user_column( $columns ) {
        $columns['verified'] = play_get_text( 'verified' );

        if ( $this->email_activation ) {
            $user_activate = array(
                'activate' => play_get_text( 'activate' )
            );
            $pos           = array_search( 'email', array_keys( $columns ), true );
            $result        = array_slice( $columns, 0, $pos + 1 );
            $result        = array_merge( $result, $user_activate );

            return array_merge( $result, array_slice( $columns, $pos ) );
        }

        return $columns;
    }

    public function admin_notices() {
        remove_action( 'admin_notices', 'default_password_nag' );
    }

    public function get_login_btn() {
        $url = sprintf( ' <a href="%s" data-target="#loginform" class="btn-login no-ajax">%s</a>', esc_url( wp_login_url() ), play_get_text( 'login' ) );
        return apply_filters('play_login_link', $url);
    }

    public function get_register_btn() {
        if ( ! $this->users_can_register ) {
            return;
        }
        $url = sprintf( ' <a href="%s" data-target="#registerform" class="btn-register no-ajax">%s</a>', esc_url( wp_registration_url() ), play_get_text( 'register' ) );
        return apply_filters('play_register_link', $url);
    }

    public function login_form_shortcode() {
        return $this->get_login_form($_REQUEST);
    }

    public function profile_form_shortcode() {
        if(!is_user_logged_in()){
            return;
        }
        $user_id = get_current_user_id();
        return $this->wp_profile_form($user_id);
    }

    public function get_login_form( $request ) {
        if ( is_user_logged_in() ) {
            return;
        }
        $action   = isset( $request[ 'action' ] ) ? sanitize_text_field( $request[ 'action' ] ) : 'login';
        
        // reset password
        if( $action == 'rp' && isset( $_COOKIE[ $this->rp_cookie ] ) && 0 < strpos( $_COOKIE[ $this->rp_cookie ], ':' ) ){
            $form = $this->wp_resetpassword_form();
            return sprintf( $this->form_wrapper, $form );
        }

        $action = ($action == 'rp' ? 'login' : $action);
        $form = $this->wp_login_form() . wp_register_form() . wp_lostpassword_form();
        $form = str_replace( '<form ', '<form style="display:none" ', $form );
        $form = str_replace( ' style="display:none" name="' . $action . 'form"', ' name="' . $action . 'form"', $form );

        $login_form = sprintf( $this->form_wrapper, $form );

        return apply_filters('play_login_form', $login_form, $request);
    }

    public function set_cookie() {
        if($this->page_login){
            if ( isset( $_GET['action'] ) && $_GET['action']=='rp' && isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {
                list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
                $value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
                setcookie( $this->rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
                // multisite
                if ( is_multisite() ) {
                    $rp_path = wp_make_link_relative( network_site_url( 'wp-login.php' ) );
                    setcookie( $this->rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
                }
                wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
                exit;
            }
        }
    }

    public function set_logged_in_cookie( $logged_in_cookie ){
        $_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
    }

    public function login_url( $login_url, $redirect, $force_reauth ) {
        if ( $this->page_login ) {
            $login_url = add_query_arg( 'action', 'login', get_permalink( $this->page_login ) );
            if(!isset($_REQUEST['interim-login']) && $redirect){
                $login_url = add_query_arg( 'redirect_to', $redirect, $login_url );
            }
        }

        return apply_filters( 'play_login_url', $login_url, $redirect, $force_reauth );
    }

    public function register_url( $register_url ) {
        if ( $this->page_login ) {
            $register_url = add_query_arg( 'action', 'register', get_permalink( $this->page_login ) );
        }

        return apply_filters( 'play_register_url', $register_url );
    }

    public function lostpassword_url( $lostpassword_url, $redirect ) {
        if ( $this->page_login ) {
            $lostpassword_url = add_query_arg( 'action', 'lostpassword', get_permalink( $this->page_login ) );
            if($redirect){
                $lostpassword_url = add_query_arg( 'redirect_to', $redirect, $lostpassword_url );
            }
        }

        return apply_filters( 'play_lostpassword_url', $lostpassword_url, $redirect );
    }

    public function update_profile($request) {
        if(!is_user_logged_in()){
            return;
        }

        $user_id = get_current_user_id();
        $current_user = get_user_by( 'id', $user_id );

        $first_name   = ! empty( $request[ 'first_name' ] ) ? sanitize_text_field( wp_unslash( $request[ 'first_name' ] ) ) : '';
        $last_name    = ! empty( $request[ 'last_name' ] ) ? sanitize_text_field( wp_unslash( $request[ 'last_name' ] ) ) : '';
        $display_name = ! empty( $request[ 'display_name' ] ) ? sanitize_text_field( wp_unslash( $request[ 'display_name' ] ) ) : '';
        $description  = ! empty( $request[ 'description' ] ) ? wp_kses( $request[ 'description' ], array(
                'em'     => array(),
                'strong' => array(),
                'small'  => array(),
                'a'      => array(
                    'href' => array(),
                )
            )
        ) : '';
        $url          = ! empty( $request[ 'url' ] ) ? sanitize_text_field( wp_unslash( $request[ 'url' ] ) ) : '';
        $email        = ! empty( $request[ 'email' ] ) ? sanitize_text_field( wp_unslash( $request[ 'email' ] ) ) : '';
        $pass0        = ! empty( $request[ 'pass' ] ) ? wp_unslash( $request[ 'pass' ] ) : '';
        $pass1        = ! empty( $request[ 'pass1' ] ) ? wp_unslash( $request[ 'pass1' ] ) : '';
        $pass2        = ! empty( $request[ 'pass2' ] ) ? wp_unslash( $request[ 'pass2' ] ) : '';

        $facebook  = ! empty( $request[ 'facebook' ] ) ? sanitize_text_field( wp_unslash( $request[ 'facebook' ] ) ) : '';
        $twitter   = ! empty( $request[ 'twitter' ] ) ? sanitize_text_field( wp_unslash( $request[ 'twitter' ] ) ) : '';
        $youtube   = ! empty( $request[ 'youtube' ] ) ? sanitize_text_field( wp_unslash( $request[ 'youtube' ] ) ) : '';
        $instagram = ! empty( $request[ 'instagram' ] ) ? sanitize_text_field( wp_unslash( $request[ 'instagram' ] ) ) : '';
        $snapchat  = ! empty( $request[ 'snapchat' ] ) ? sanitize_text_field( wp_unslash( $request[ 'snapchat' ] ) ) : '';
        $whatsapp  = ! empty( $request[ 'whatsapp' ] ) ? sanitize_text_field( wp_unslash( $request[ 'whatsapp' ] ) ) : '';

        $thumbnail_pos_y = ! empty( $request[ 'thumbnail_pos_y' ] ) ? absint( wp_unslash( $request[ 'thumbnail_pos_y' ] ) ) : 50;

        $user     = new stdClass();
        $user->ID = $user_id;

        $pass = true;
        $msg  = '';
        /* Update user password. */
        if ( ! empty( $pass0 ) && ! empty( $pass1 ) && ! empty( $pass2 ) ) {
            if ( wp_check_password( $pass0, $current_user->user_pass, $current_user->ID ) ) {
                if ( $pass1 == $pass2 ) {
                    $user->user_pass = $pass1;
                } else {
                    $pass = false;
                    $msg .= apply_filters('play_update_profile_error_mismatch', play_get_text( 'error-pwd-mismatch' ));
                }
            } else {
                $pass = false;
                $msg .= apply_filters('play_update_profile_error_pass', play_get_text( 'error-pwd' ));
            }
        }

        /* Update user information. */
        if ( ! empty( $email ) ) {
            if ( ! is_email( $email ) ) {
                $pass = false;
                $msg .= apply_filters('play_update_profile_error_email', play_get_text( 'error-mail' ));
            } elseif ( email_exists( $email ) && $email !== $current_user->user_email ) {
                $pass = false;
                $msg .= apply_filters('play_update_profile_error_email_exist', play_get_text( 'error-mail-exist' ));
            } else {
                $user->user_email = $email;
            }
        }

        if ( ! empty( $first_name ) ) {
            $user->first_name = $first_name;
        }
        if ( ! empty( $last_name ) ) {
            $user->last_name = $last_name;
        }
        if ( ! empty( $display_name ) ) {
            $user->display_name = $display_name;
        }
        if ( ! empty( $description ) ) {
            $user->description = $description;
        }

        $user->user_url = esc_url( $url );
        update_user_meta( $user->ID, 'facebook', $facebook );
        update_user_meta( $user->ID, 'twitter', $twitter );
        update_user_meta( $user->ID, 'youtube', $youtube );
        update_user_meta( $user->ID, 'instagram', $instagram );
        update_user_meta( $user->ID, 'snapchat', $snapchat );
        update_user_meta( $user->ID, 'whatsapp', $whatsapp );
        update_user_meta( $user->ID, 'thumbnail_pos_y', $thumbnail_pos_y );

        do_action( 'save_user_avatar', $user->ID, 9 );
        
        do_action( 'profile_form_before_update', $user->ID );
        if ( $pass ) {
            wp_update_user( $user );
            do_action( 'profile_form_update', $user->ID );

            $msg .= apply_filters('play_update_profile_saved', play_get_text( 'saved' ));

            return Play_Utils::instance()->response(
                array(
                    'status' => 'success',
                    'msg'    => $msg
                )
            );
        }else{
            return Play_Utils::instance()->response(
                array(
                    'status' => 'error',
                    'msg'    => $msg
                )
            );
        }
        do_action( 'profile_form_after_update', $user->ID );
    }

    public function wp_login_form_shortcode() {
        return sprintf( $this->form_wrapper, $this->wp_login_form() );
    }

    public function wp_register_form_shortcode() {
        return sprintf( $this->form_wrapper, $this->wp_register_form() );
    }

    public function wp_lostpassword_form_shortcode() {
        return sprintf( $this->form_wrapper, $this->wp_lostpassword_form() );
    }

    public function wp_login_form() {
        return play_get_template_html( 'form/login.php' );
    }

    public function wp_register_form( $args = array() ) {
        if ( ! $this->users_can_register ) {
            return;
        }
        return play_get_template_html( 'form/register.php' );
    }

    public function wp_lostpassword_form() {
        return play_get_template_html( 'form/lostpassword.php' );
    }

    public function wp_resetpassword_form() {
        wp_enqueue_script( 'utils' );
        wp_enqueue_script( 'user-profile' );

        list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $this->rp_cookie ] ), 2 );

        return play_get_template_html( 'form/resetpassword.php', array(
            'rp_login' => $rp_login,
            'rp_key'   => $rp_key
        ) );
    }

    public function wp_profile_form( $user_id ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( $user_id !== $user->ID ) {
            return;
        }

        return play_get_template_html( 'form/profile.php', array(
                'user'     => $user,
                'redirect' => get_author_posts_url( $user->ID )
            )
        );
    }
}

Play_User_Profile::instance();
