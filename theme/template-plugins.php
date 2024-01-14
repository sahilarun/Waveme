<?php
/**
 * Plugins
 */

// install requried plugins
require_once get_template_directory() . '/includes/libs/TGMPA/class-tgm-plugin-activation.php';
function ffl_register_required_plugins() {
	$code = get_option('envato_purchase_code');
	$theme = get_file_data( get_template_directory().'/style.css', array('Plugin' => 'Plugin', 'Support' => 'Support') );
	$version = $theme['Plugin'];
	$url = $theme['Support'].'wp-json/wp/v2/update/?code='.$code.'&version='.$version.'&site='.site_url();

    $plugins = array(
    	array(
    		'name'      => 'WordPress Importer',
            'slug'      => 'wordpress-importer',
            'required'  => true
    	),
        array(
            'name'      => 'Play Block',
            'slug'      => 'play-block',
            'source'    => $url.'&plugin=play-block',
            'version'   => $version,
            'required'  => true
        ),
        array(
            'name'      => 'Loop Block',
            'slug'      => 'loop-block',
            'source'    => $url.'&plugin=loop-block',
            'version'   => $version,
            'required'  => true
        )
    );
    $config = array(
		'id'           => 'ffl',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'has_notices'  => true,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
	);

	tgmpa( $plugins, $config );

	// import demo data
	if(is_admin() && current_user_can('manage_options') && isset( $_REQUEST['import'] ) && isset( $_REQUEST['demo'] )){
	    if (class_exists('WP_Import') && class_exists('Play_Block')) {
			if (!function_exists('wp_insert_category')) {
                require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
            }
            if (!function_exists('post_exists')) {
                require_once ABSPATH . 'wp-admin/includes/post.php';
            }
            if (!function_exists('comment_exists')) {
                require_once ABSPATH . 'wp-admin/includes/comment.php';
            }
            
            require_once ABSPATH . 'wp-admin/includes/file.php';
		    require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			$file = download_url('https://www.dropbox.com/s/v8q8mtqkakqwzi0/waveme.demo_.xml?dl=1');

			if(is_wp_error($file)){
				add_action('admin_notices', function(){
			        echo '<div class="notice notice-error is-dismissible"><p>Can not import demo data</p></div>';
			    });
			}else{
				if(is_file($file) && (filesize( $file ) > 0)){
					// remove current menus
					$menus = wp_get_nav_menus();
					if (!empty($menus)) {
						foreach ($menus as $menu) {
							wp_delete_nav_menu($menu);
						}
					}
					
					$wp_import = new WP_Import();
					
					$wp_import->fetch_attachments = true;

					ob_start();
					$wp_import->import($file);
					ob_end_clean();
					add_action('admin_notices', function(){
				        echo '<div class="notice notice-success is-dismissible"><p>Demo data imported</p></div>';
				    });
					
				    // Set menu
					$locations = get_theme_mod('nav_menu_locations');
					$menus = wp_get_nav_menus();
					if (!empty($menus)) {
						foreach ($menus as $menu) {
							if (is_object($menu) && $menu->name == 'Before login') {
								$locations['before_login'] = $menu->term_id;
							}
							if (is_object($menu) && $menu->name == 'After login') {
								$locations['after_login'] = $menu->term_id;
							}
							if (is_object($menu) && $menu->name == 'Browse') {
								$locations['primary'] = $menu->term_id;
							}
							if (is_object($menu) && $menu->name == 'Secondary') {
								$locations['secondary'] = $menu->term_id;
							}
						}
					}
					set_theme_mod('nav_menu_locations', $locations);

					// set home
					$page = get_page_by_title('discover');
				    if ($page && $page->ID) {
				         update_option('show_on_front', 'page');
				         update_option('page_on_front', $page->ID);
				    }

				    // set page area
				    $page = get_page_by_title('footer');
				    if ($page && $page->ID) {
				         update_option('page_footer', $page->ID);
				    }

				    $page = get_page_by_title('sidebar');
				    if ($page && $page->ID) {
				         update_option('page_sidebar', $page->ID);
				    }

				    $page = get_page_by_title('side footer');
				    if ($page && $page->ID) {
				    	 update_option('page_sidefooter', $page->ID);
				    }
				}else{
					add_action('admin_notices', function(){
				        echo '<div class="notice notice-error is-dismissible"><p>Import failed, Empty demo data.</p></div>';
				    });
				}
			}
		}else{
			add_action('admin_notices', function(){
		        echo '<div class="notice notice-error is-dismissible"><p>Install & Activate the required plugins first.</p></div>';
		    });
		}
	}
}
add_action( 'tgmpa_register', 'ffl_register_required_plugins' );
