<?php
/**
 * Reset Password Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/resetpassword.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'play_before_register_form' );
?>

<form name="resetpasswordform" id="resetpasswordform" method="post" action="" autocomplete="off">
    <h2><?php play_get_text('reset-password-title', true); ?></h2>

    <div class="form-message"></div>

    <?php echo apply_filters( 'resetpassword_form_top', '' ); ?>
    <?php do_action( 'play_resetpassword_form_start' ); ?>

    <p class="form-pwd">
        <label><?php play_get_text('new-password', true); ?></label>
        <input type="password" name="pwd" autocomplete="off" class="input" />
    </p>

    <?php echo apply_filters( 'resetpassword_form_middle', '' ); ?>
    <?php do_action( 'play_resetpassword_form' ); ?>

    <p>
        <button type="button" class="button btn-generate-pwd hide-if-no-js"><?php play_get_text('generate-password', true); ?></button>
        <button type="submit" class="button button-primary"><?php play_get_text('save-password', true); ?></button>

        <input type="hidden" name="form-action" value="resetpwd">
        <input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
    </p>

    <?php echo apply_filters( 'resetpassword_form_bottom', '' ); ?>
    <?php do_action( 'play_resetpassword_form_end' ); ?>
</form>

<?php do_action( 'play_after_resetpassword_form' ); ?>
