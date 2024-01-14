<?php
/**
 * Upload Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/upload.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'before_upload_form' );

?>

<form class="form form-validate" name="upload" id="upload" action method="post" enctype="multipart/form-data">
	<div class="form-message"><?php do_action('template_notices'); ?></div>
	<div class="flex-row">
	    <div class="form-upload-thumbnail">
	    	<label><?php play_get_text('poster', true); ?> <span class="required">*</span></label>
	        <div class="file-upload" style="width: 15rem">
	            <input type="file" name="image" accept="image/*" <?php echo esc_attr( $post->ID ? '' : 'required') ?> />
	            <div class="post-thumbnail rounded"><img width="240" height="240" src="<?php echo esc_attr($post->ID > 0 ? get_the_post_thumbnail_url($post->ID) : '') ?>"></div>
	        </div>
	    </div>
	    <div class="sep"></div>
	    <div class="form-upload-info">
	        <?php do_action( 'upload_form_top', $post ); ?>
	        <p class="form-upload-title">
	            <label><?php play_get_text('title', true); ?> <span class="required">*</span></label>
	            <input type="text" name="title" class="input" size="20" value="<?php echo esc_attr($post->post_title); ?>" required />
	        </p>

	        <?php if( $post->type == 'single' ){ ?>
	        <div class="form-upload-media">
	            <label><?php play_get_text('stream', true); ?> <span class="required">*</span></label>
	            <div class="file-upload-wrap">
		            <input type="text" name="stream" class="input" placeholder="http://" required value="<?php echo esc_attr( get_post_meta($post->ID, 'stream', true) ); ?>" />
		            <?php if( $user_can_upload_stream ){ ?>
		            <div class="file-upload">
		            	<input type="file" name="upload_file" data-type="single" accept="video/mp4,video/x-m4v,video/*,audio/mp3,audio/x-m4a,audio/*," />
		            	<span class="progress"><span class="progress-bar"></span></span>
		            	<button class="input"><?php play_get_text('upload', true); ?></button>
		            </div>
		        	<?php } ?>
	            </div>
	        </div>
	        <?php }?>
	        <div class="flex-row">
	            <p class="form-upload-genre">
	                <label><?php play_get_text('genre', true); ?></label>
	                <?php 
	                	$cat = 'category';
	                	$tag = 'post_tag';
	                	if( $post->post_type == 'station' ){
	                		$cat = 'genre';
	                		$tag = 'station_tag';
	                	}elseif( $post->post_type == 'product' ){
	                		$cat = 'product_cat';
	                		$tag = 'product_tag';
	                	}elseif( $post->post_type == 'download' ){
	                		$cat = 'download_category';
	                		$tag = 'download_tag';
						}

						if(apply_filters('play_use_genre', true)){
				            $cat = 'genre';
				        }

						$arr = array(
	                        'taxonomy' => $cat,
	                        'hide_empty' => false,
	                        'hierarchical' => true,
	                        'name' => 'cat',
	                        'class' => 'input',
	                        'selected' => join( ',', wp_get_post_terms( $post->ID, $cat, array("fields" => "ids") ) )
	                    );
						$arr = apply_filters('play_block_form_upload_categories', $arr);
	                	wp_dropdown_categories( $arr );
	                ?>
	            </p>
	            <div class="sep"></div>
	            <p class="form-upload-type">
	            	<?php if( $post->type == 'album' || $post->type == 'playlist' ){ ?>
	                	<label><?php play_get_text('playlist-type', true); ?></label>
	                	<select name="type" class="input">
					        <?php
					        $types = array( 'playlist' => play_get_text('playlist') );
					        if($user_can_upload){
					        	if(apply_filters('play_register_audio_type', true)){
						            $types['album'] = play_get_text('album');
						        }
						        if(apply_filters('play_register_video_type', false)){
						            $types['shot'] = play_get_text('shot');
						        }
					        }
					        foreach ( $types  as $k => $v ) {
					           echo '<option value="'.$k .'" '.selected( $k, $post->type ).'>'.$v.'</option>';
					        }
					        ?>
					    </select>
	                <?php } else { ?>
	                	<label><?php play_get_text('duration', true); ?></label>
	                	<input type="text" name="duration" class="input" value="<?php echo esc_attr( Play_Utils::instance()->duration( get_post_meta($post->ID, 'duration', true)/1000, '', true ) ); ?>" />
	                <?php } ?>
	            </p>
	            <div class="sep"></div>
	            <p class="form-upload-date">
	                <label><?php play_get_text('release-date', true); ?></label>
	                <input type="date" name="post_date" class="input" value="<?php echo esc_attr( date("Y-m-d", strtotime($post->post_date)) ); ?>" />
	            </p>
            </div>
            <p class="form-upload-tag">
                <label><?php play_get_text('tags', true);?> <?php play_get_text('tags-tip', true); ?></label>
                <input type="text" name="tag" class="input" value="<?php echo esc_attr( join(',', wp_get_post_terms($post->ID, $tag, array("fields" => "names"))) ); ?>">
            </p>
            <?php if(apply_filters('play_register_audio_type', true)){ ?>
	        <p class="form-upload-artist">
                <label><?php play_get_text('artists', true);?> <?php play_get_text('artists-tip', true); ?></label>
                <input type="text" name="artist" class="input" value="<?php echo esc_attr( join(',', wp_get_post_terms($post->ID, 'artist', array("fields" => "names"))) ); ?>">
            </p>
        	<?php } ?>
	        <p class="form-upload-content">
	            <label><?php play_get_text('content', true); ?></label>
	            <textarea name="content" class="input" rows="4" /><?php echo wp_kses_post( $post->post_content ); ?></textarea>
	        </p>

	        <?php if( $post->post_type == 'product' && class_exists( 'WooCommerce' ) && $user_can_upload ){ ?>
	        	<div class="flex-row form-upload-price">
		        	<p>
			            <label><?php play_get_text('regular-price', true); ?> (<?php esc_html_e( get_woocommerce_currency_symbol() ); ?>)</label>
			            <input type="text" name="_regular_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, '_regular_price', true)); ?>" />
			        </p>
			        <div class="sep"></div>
		            <p>
		                <label><?php play_get_text('sale-price', true); ?> (<?php esc_html_e( get_woocommerce_currency_symbol() ); ?>)</label>
			            <input type="text" name="_sale_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, '_sale_price', true)); ?>" />
		            </p>
		        </div>
	        <?php }?>

	        <?php if( $post->post_type == 'download' && class_exists( 'Easy_Digital_Downloads' ) && $user_can_upload ){ ?>
				<div class="flex-row form-upload-download">
					<p>
						<label><?php play_get_text('regular-price', true); ?> (<?php esc_html_e( edd_currency_filter( '' ) ); ?>)</label>
						<input type="text" name="_regular_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, 'edd_price', true)); ?>" />
					</p>
					<div class="sep"></div>
					<p>
						<label><?php play_get_text('sale-price', true); ?> (<?php esc_html_e( edd_currency_filter( '' ) ); ?>)</label>
						<input type="text" name="_sale_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, 'edd_sale_price', true)); ?>" />
					</p>
				</div>
			<?php }?>

	        <?php if( play_get_option('purchaseable') && $user_can_upload ){ ?>
	        <div class="flex-row form-upload-purchase">
		        <p>
		            <label><?php play_get_text('purchase-title', true); ?></label>
		            <input type="text" name="purchase_title" class="input" value="<?php echo esc_attr( get_post_meta($post->ID, 'purchase_title', true) ); ?>" />
		        </p>
		        <div class="sep"></div>
		        <p>
		            <label><?php play_get_text('purchase-url', true); ?></label>
		            <input type="text" name="purchase_url" class="input" placeholder="http://" value="<?php echo esc_url( get_post_meta($post->ID, 'purchase_url', true) ); ?>" />
		        </p>
		    </div>
	        <?php } ?>

	        <p class="form-upload-copyright">
	            <label><?php play_get_text('copyright', true); ?></label>
	            <input type="text" name="copyright" class="input" value="<?php echo esc_attr( get_post_meta($post->ID, 'copyright', true) ); ?>" />
	        </p>

	        <?php if( apply_filters('play_block_form_upload_downloadable', true) && $user_can_upload ){ ?>
	        <div class="checkable form-upload-download">
	            <input type="checkbox" name="downloadable" value="1" id="downloadable" <?php echo (get_post_meta($post->ID, 'downloadable', true) ? 'checked="checked"' : ''); ?> /> 
	            <div class="flex">
	            	<label for="downloadable"><?php play_get_text('downloadable', true); ?></label>
	            	<div class="hide" style="display: none;">
		            	<div class="file-upload-wrap">
			            	<input type="text" name="download_url" class="input" placeholder="http://" value="<?php echo esc_attr( get_post_meta($post->ID, 'download_url', true) ); ?>" />
			            	<?php if( $user_can_upload_stream ){ ?>
			            	<div class="file-upload">
				            	<input type="file" name="upload_file" />
				            	<span class="progress"><span class="progress-bar"></span></span>
				            	<button class="input"><span class="progress"></span> <?php play_get_text('upload', true); ?></button>
				            </div>
				        	<?php } ?>
			            </div>
		            </div>
	            </div>
	        </div>
	    	<?php } ?>
	        
	        <?php if($user_can_post_public){ ?>
	        <div class="checkable form-upload-publish">
	            <input type="radio" name="post_status" id="public" value="publish" <?php echo ($post->post_status == 'publish' ? 'checked="checked"' : ''); ?> />
	            <div>
	            	<label for="public"><?php play_get_text('public', true); ?></label>
	            	<span class="hide" style="display:none"><?php play_get_text('public-tip', true); ?></span>
	            </div>
	        </div>
	        <?php } ?>
	        <div class="checkable form-upload-private">
	            <input type="radio" name="post_status" id="private" value="private" <?php echo ($post->post_status == 'private' ? 'checked="checked"' : ''); ?> />
	            <div>
	            	<label for="private"><?php play_get_text('private', true); ?></label>
	            	<span class="hide" style="display:none"><?php play_get_text('private-tip', true); ?></span>
	            </div>
	        </div>

	        <?php do_action( 'upload_form_middle', $post ); ?>
	    </div>
	</div>
	<div class="tracks">
        <?php if( $post->type == 'album' || $post->type == 'playlist' ){ ?>
        	<?php $posts = get_post_meta($post->ID, 'post', true); 
        	$query = array(
	          'post_type' => 'any',
	          'post_status' => 'any',
	          'posts_per_page' => -1,
	          'post__in' => explode(',', $posts),
	          'orderby' => 'post__in'
	        );
	        $items = get_posts( $query );
	        $list = '';
	        foreach ($items as $key => $item) {
	            $list .= sprintf( '<li id="%d" class="input"><span class="handle"></span><span class="track-list-title">%s</span><span class="remove">&times;</span></li>', esc_attr($item->ID), esc_html($item->post_title) );
	        }
        	?>
        	<label><?php play_get_text('tracks', true); ?></label>
        	<ul class="track-list"><?php echo $list; ?></ul>
        	<input type="hidden" name="post" value="<?php echo esc_attr($posts); ?>">
        <?php }?>
    </div>

    <p class="form-action">
    	<span class="file-uploading"><?php play_get_text('uploading-files', true); ?></span>
    	<span class="file-uploaded"><?php play_get_text('files-uploaded', true); ?></span>
    	<span class="sep-1"></span>
    	<button class="button-link" data-dismiss="modal"><?php play_get_text('cancel', true); ?></button>
        <input type="submit" name="wp-submit" class="button button-primary" value="<?php play_get_text('save', true); ?>" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>" />
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>" />
        <input type="hidden" name="post_type" value="<?php echo esc_attr($post->post_type); ?>" />
        <input type="hidden" name="action" value="frontend-upload" />
    </p>
    <?php do_action( 'upload_form_bottom', $post ); ?>
</form>
<?php do_action( 'after_upload_form' ); ?>
