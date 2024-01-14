<?php
/**
 * Register Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/register.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'play_before_register_form' );
?>

<form name="registerform" id="registerform" method="post" action="">
    <h2><?php play_get_text('register-title', true); ?></h2>

    <p><?php play_get_text('have-account', true); ?> <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn-login text-primary no-ajax"><?php play_get_text('sign-in', true); ?></a></p>

    <div class="form-message"></div>

    <?php echo apply_filters( 'register_form_top', '' ); ?>
    <?php do_action( 'play_register_form_start' ); ?>
    <p>
        <label><?php play_get_text('username', true); ?></label>
        <input type="text" name="user_login" class="input" value="" required />
    </p>
    <p>
        <label><?php play_get_text('email-address', true); ?></label>
        <input type="email" name="user_email" class="input" required />
    </p>
    <p class="form-pwd">
        <label><?php play_get_text('password', true); ?></label>
        <input type="password" name="pwd" class="input" value="" required autocomplete="off" />
    </p>
    <?php echo apply_filters( 'register_form_middle', '' ); ?>
    <?php do_action( 'play_register_form' ); ?>

    <?php do_action( 'register_form' ); ?>
    
    <p>
        <button type="submit" name="wp-submit" class="button button-primary">
            <?php play_get_text('register-btn', true); ?>
        </button>
        <input type="hidden" name="form-action" value="register">
    </p>

    <?php echo apply_filters( 'register_form_bottom', '' ); ?>
    <?php do_action( 'play_register_form_end' ); ?>
</form>

<?php do_action( 'play_after_register_form' ); ?>
