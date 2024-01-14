<?php
/**
 * Profile Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/profile.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<form class="form" name="your-profile" id="your-profile" action method="post" enctype="multipart/form-data">
    <div class="flex-row">
        <div>
            <div class="file-upload" style="width: 10rem">
                <input type="file" name="avatar" id="avatar" accept="image/*" />
                <div class="post-thumbnail circle"><?php echo get_avatar( $user->ID, 1 ); ?></div>
            </div>
        </div>
        <div class="sep"></div>
        <div>
            <div class="form-message">
                <?php do_action('template_notices'); ?>
            </div>
            <?php do_action( 'profile_form_top' ); ?>
            <div class="flex-row">
                <p>
                    <label><?php play_get_text('first-name', 'true'); ?> <span class="required">*</span></label>
                    <input type="text" name="first_name" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'first_name', true ) ); ?>" required />
                </p>
                <div class="sep"></div>
                <p>
                    <label><?php play_get_text('last-name', 'true'); ?> <span class="required">*</span></label>
                    <input type="text" name="last_name" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'last_name', true ) ); ?>" required />
                </p>
            </div>
            <p>
                <label><?php play_get_text('display-name', 'true'); ?> <span class="required">*</span></label>
                <input type="text" name="display_name" class="input" value="<?php echo esc_attr( $user->display_name ); ?>" required />
            </p>
            <p>
                <label><?php play_get_text('email', 'true'); ?> <span class="required">*</span></label>
                <input type="text" name="email" class="input" value="<?php echo esc_attr( $user->user_email ); ?>" required />
            </p>
            <p>
                <label><?php play_get_text('website', 'true'); ?></label>
                <input type="text" name="url" class="input" value="<?php echo esc_attr( $user->user_url ); ?>" />
            </p>
            <p>
                <label><?php play_get_text('description', 'true'); ?></label>
                <textarea name="description" class="input" rows="4" /><?php echo wp_kses_post( $user->description ); ?></textarea>
            </p>
            <p class="separator"><span><?php play_get_text('social-networks', 'true'); ?></span></p>
            <p class="input-facebook">
                <label><?php play_get_text('facebook', 'true'); ?></label>
                <input type="text" name="facebook" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'facebook', true ) ); ?>" />
            </p>
            <p class="input-twitter">
                <label><?php play_get_text('twitter', 'true'); ?></label>
                <input type="text" name="twitter" class="input" placeholder="<?php play_get_text('twitter-username', 'true'); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, 'twitter', true ) ); ?>" />
            </p>
            <p class="input-youtube">
                <label><?php play_get_text('youtube', 'true'); ?></label>
                <input type="text" name="youtube" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'youtube', true ) ); ?>" />
            </p>
            <p class="input-instagram">
                <label><?php play_get_text('instagram', 'true'); ?></label>
                <input type="text" name="instagram" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'instagram', true ) ); ?>" />
            </p>
            <p class="input-whatsapp">
                <label><?php play_get_text('whatsapp', 'true'); ?></label>
                <input type="text" name="whatsapp" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'whatsapp', true ) ); ?>" />
            </p>
            <p class="input-snapchat">
                <label><?php play_get_text('snapchat', 'true'); ?></label>
                <input type="text" name="snapchat" class="input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'snapchat', true ) ); ?>" />
            </p>
            <?php do_action( 'profile_form_middle' ); ?>
            <p class="separator"><span><?php play_get_text('password-change', 'true'); ?></span></p>
            <p class="form-pwd">
                <label><?php play_get_text('current-password', 'true'); ?></label>
                <input type="password" name="pass" class="input" />
            </p>
            <p class="form-pwd">
                <label><?php play_get_text('new-password', 'true'); ?></label>
                <input type="password" name="pass1" class="input" />
            </p>
            <p class="form-pwd">
                <label><?php play_get_text('comfirm-new-password', 'true'); ?></label>
                <input type="password" name="pass2" class="input" />
            </p>
            <p>
                <input type="submit" name="wp-submit" class="button button-primary" value="<?php play_get_text('update', 'true'); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>" />
                <input type="hidden" name="action" value="update-profile" />
            </p>
            <?php do_action( 'profile_form_bottom' ); ?>
            <?php if(apply_filters('play_block_allow_delete_account', true)){ ?>
            <a href="#" class="btn-delete-account"><small><?php play_get_text('delete-account', 'true'); ?></small></a>
            <?php } ?>
        </div>
    </div>
</form>
