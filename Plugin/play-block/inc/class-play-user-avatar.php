<?php

defined( 'ABSPATH' ) || exit;

class Play_User_Avatar {

    protected static $_instance = null;
    private $user_id;
    private $key = '_avatar';

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
        add_action( 'save_user_avatar', array( $this, 'save_avatar' ), 10, 2 );
        add_action( 'delete_user_avatar', array( $this, 'delete_avatar' ), 10, 1 );

        add_action( 'show_user_profile', array( $this, 'edit_user_profile' ) );
        add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ) );

        add_action( 'personal_options_update', array( $this, 'save_avatar' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_avatar' ) );
        add_action( 'personal_options_update', array( $this, 'save_option' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_option' ) );
        
        add_filter( 'pre_get_avatar', array( $this, 'pre_get_avatar' ), 10, 3 );

        add_action( 'the_verified_button', array( $this, 'the_verified_button' ), 10, 1 );
        add_filter( 'verified_button', array( $this, 'verified_button' ), 10, 1 );

        do_action( 'play_block_user_avatar_init', $this );
    }

    public function save_avatar( $user_id, $file = NULL ) {
        if(isset( $_FILES[ 'avatar' ] ) && $_FILES[ 'avatar' ][ "size" ] > 0){
            $file = $_FILES;
        }
        if ( empty( $file[ 'avatar' ][ 'name' ] ) ) {
            return;
        }

        $mimes = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif'          => 'image/gif',
            'png'          => 'image/png',
        );

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $this->delete_avatar( $user_id );
        $this->user_id = $user_id;
        $avatar        = wp_handle_upload( $file[ 'avatar' ], array(
            'mimes'                    => $mimes,
            'test_form'                => false,
            'unique_filename_callback' => array(
                $this,
                'unique_avatar_filename_callback'
            )
        ) );

        if ( empty( $avatar[ 'file' ] ) ) {
            wp_die( $avatar[ 'error' ] );

            return;
        }

        $uploads  = wp_get_upload_dir();
        $new_path = str_replace( $uploads[ 'basedir' ], '', $avatar[ 'file' ] );
        $new_path = ltrim( $new_path, '/' );

        do_action( 'play_save_avatar', $user_id, $this->key, $new_path, $this );

        update_user_meta( $user_id, $this->key, array( 'full' => $new_path ) );
    }

    public function save_option( $user_id ) {
        if( is_admin() ){
            $verified = isset($_POST[ 'verified' ]) ? 'true' : 'false';
            update_user_option( $user_id, 'verified', $verified, true );
        }
    }

    public function delete_avatar( $user_id ) {
        $old_avatars = get_user_meta( $user_id, $this->key, true );
        $uploads     = wp_get_upload_dir();

        if ( is_array( $old_avatars ) ) {
            foreach ( $old_avatars as $old_avatar ) {
                $old_avatar_path = $uploads[ 'basedir' ] . '/' . $old_avatar;
                @unlink( $old_avatar_path );
            }
        }

        do_action( 'play_delete_avatar', $user_id, $this->key, $old_avatars, $this );

        delete_user_meta( $user_id, $this->key );
    }

    /**
     * Display follow button
     */
    public function the_verified_button( $user_id ) {
        echo $this->get_verified_button( $user_id );
    }

    /**
     * Get follow button
     */
    public function verified_button( $user_id ) {
        return $this->get_verified_button( $user_id );
    }

    /**
     * Get follow button
     */
    public function get_verified_button( $user_id ) {
        $verified = get_user_meta( $user_id, 'verified', true );
        $verified = apply_filters('play_user_verified', $verified);
        if ( $verified === 'true' ) {
            return Play_Utils::instance()->get_template_html( 'blocks/verified.php' );
        }
        
        return;
    }

    public function pre_get_avatar( $avatar, $id_or_email, $args ) {
        $uploads     = wp_get_upload_dir();
        $avatar_wrap = '<span class="avatar avatar-%s"><span class="avatar-name">%s</span></span>';
        if ( is_numeric( $id_or_email ) ) {
            $user_id = (int) $id_or_email;
        } elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) {
            $user_id = $user->ID;
        } elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
            $user_id = (int) $id_or_email->user_id;
        }

        // return unregister icon
        if ( empty( $user_id ) ) {
            if ( is_object( $id_or_email ) && isset( $id_or_email->comment_author ) ) {
                $author = substr( $id_or_email->comment_author, 0, 1 );

                return sprintf( $avatar_wrap, strtolower( $author ), $author );
            }

            return sprintf( $avatar_wrap, '', '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>' );
        }

        $attr = '';
        if ( isset( $args[ 'attr' ] ) ) {
            $attrs = $args[ 'attr' ];
            foreach ( $attrs as $name => $value ) {
                $attr .= "$name=" . '"' . $value . '"';
            }
        }
        // return user name first character
        $local_avatars = get_user_meta( $user_id, $this->key, true );

        if ( empty( $local_avatars ) || empty( $local_avatars[ 'full' ] ) ) {
            $author = substr( get_the_author_meta( 'display_name', $user_id ), 0, 1 );

            return sprintf( $avatar_wrap, strtolower( $author ), $author );
        }
        $alt = get_the_author_meta( 'display_name', $user_id );
        $size = $args[ 'size' ];
        if ( $size == 1 ) {
            if ( substr( $local_avatars[ 'full' ], 0, 4 ) != 'http' ) {
                $local_avatars[ 'full' ] = $uploads[ 'baseurl' ] . '/' . $local_avatars[ 'full' ];
            }

            return '<img alt="' . esc_attr( $alt ) . '" src="' . $local_avatars[ 'full' ] . '" ' . $attr . '/>';
        }

        $size = (int) $size;

        if ( empty( $local_avatars[ $size ] ) ) {
            $avatar_full_path = $uploads[ 'basedir' ] . '/' . $local_avatars[ 'full' ];
            $image            = wp_get_image_editor( $avatar_full_path );

            $local_avatars[ $size ] = $local_avatars[ 'full' ];

            if ( ! is_wp_error( $image ) ) {
                $image->resize( $size * 2, $size * 2, true );
                $image_sized            = $image->save();
                $local_avatars[ $size ] = str_replace( $uploads[ 'basedir' ], '', $image_sized[ 'path' ] );
                $local_avatars[ $size ] = ltrim( $local_avatars[ $size ], '/' );
            }

            update_user_meta( $user_id, $this->key, $local_avatars );
        }

        if ( substr( $local_avatars[ $size ], 0, 4 ) != 'http' ) {
            $local_avatars[ $size ] = $uploads[ 'baseurl' ] . '/' . $local_avatars[ $size ];
        }

        $author_class = is_author( $user_id ) ? ' current-author' : '';
        $avatar       = "<img alt='" . esc_attr( $alt ) . "' src='" . $local_avatars[ $size ] . "' class='avatar avatar-{$size}{$author_class} photo' height='{$size}' width='{$size}'  " . $attr . " />";

        if ( ! empty( $id_or_email->comment_ID ) ) {
            if ( "" < ( $url = get_comment_author_url( $id_or_email->comment_ID ) ) ) {
                $avatar = sprintf(
                    '<a href="%s" rel="external nofollow" class="ajax avatar avatar-url">%s</a>',
                    $url,
                    $avatar
                );
            }
        }

        return apply_filters( $this->key, $avatar );
    }

    public function edit_user_profile( $user ) {
        if ( empty( $user ) ) {
            $user = get_queried_object();
        }

        ?>
        <table class="form-table">
            <?php if ( current_user_can( 'manage_options' ) ) { ?>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php $verified = get_user_meta( $user->ID, 'verified', true ); ?>
                        <label><input type="checkbox" name="verified"
                                      value="true" <?php checked( 'true', $verified ); ?> /> Verified</label>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th></th>
                <td>
                    <?php echo get_avatar( $user->ID ); ?>
                    <input type="file" name="avatar" id="avatar"/>
                </td>
            </tr>
        </table>
        <script type="text/javascript">var form = document.getElementById('your-profile');
            form.encoding = 'multipart/form-data';
            form.setAttribute('enctype', 'multipart/form-data');</script>
        <?php
    }

    public function unique_avatar_filename_callback( $dir, $name, $ext ) {
        $user   = get_user_by( 'id', (int) $this->user_id );
        $name   = $base_name = sanitize_file_name( $user->display_name . '_avatar' );
        $number = 1;

        while ( file_exists( $dir . "/$name$ext" ) ) {
            $name = $base_name . '_' . $number;
            $number ++;
        }

        return $name . $ext;
    }
}

Play_User_Avatar::instance();
