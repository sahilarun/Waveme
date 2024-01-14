<?php
/**
 * Lost Password Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/lostpassword.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'play_before_lostpassword_form' );
?>

<form name="lostpasswordform" id="lostpasswordform" method="post" action="">
    <h2><?php play_get_text('lost-password-title', true); ?></h2>

    <div class="form-message"></div>

    <?php echo apply_filters( 'lostpassword_form_top', '' ); ?>
    <?php do_action( 'play_lostpassword_form_start' ); ?>
    <p><?php play_get_text('lost-password-tip', true); ?></p>
    <p>
        <label><?php play_get_text('username-or-email', true); ?></label>
        <input type="text" name="user_login" class="input" value="" required />
    </p>
    <?php echo apply_filters( 'lostpassword_form_middle', '' ); ?>
    <?php do_action( 'play_lostpassword_form' ); ?>

    <p>
        <button type="submit" name="wp-submit" class="button button-primary">
            <?php play_get_text('get-new-password', true); ?>
        </button>
        <input type="hidden" name="form-action" value="lostpwd">
    </p>

    <?php echo apply_filters( 'lostpassword_form_bottom', '' ); ?>
    <?php do_action( 'play_lostpassword_form_end' ); ?>

    <p><?php play_get_text('return-to', true); ?> <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn-login text-primary no-ajax"><?php play_get_text('sign-in', true); ?></a></p>
</form>

<?php do_action( 'play_after_lostpassword_form' ); ?>
