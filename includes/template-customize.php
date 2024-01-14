<?php

// add theme class on body
function ffl_body_class($classes){
    if( (is_singular() || is_author()) && ffl_can_show_post_thumbnail() ){
        $classes[] = 'featured-image';
    }
    if( is_singular() ){
        $classes[] = 'single';
        $classes[] = get_post_meta( get_the_ID(), 'add_classes', true );
        if( ffl_sidebar() ){
            $classes[] = 'with-sidebar';
        }
    }
    $menu_name = 'primary';
    if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_name ] ) ) {
        $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
        if ( $menu ) {
            $items = wp_get_nav_menu_items($menu->term_id);
            if ( $items ) {
                foreach ( $items as $key => $item ) {
                    $c = implode(' ', $item->classes);
                    if(strpos($c, 'icon-') !== false){
                        $classes[] = 'primary-menu-has-icon';
                        break;
                    }
                }
            }
        }
    }
    return array_unique( array_map( 'esc_attr', $classes ) );
}
add_filter( 'body_class', 'ffl_body_class');

add_action( 'admin_init' , 'ffl_register_fields' );
function ffl_register_fields(){
    register_setting( 'general', 'envato_purchase_code', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
    add_settings_field('ffl-code-id', 'Envato Purchase Code', 'ffl_fields', 'general', 'default');
}
function ffl_fields(){
    $envato_purchase_code = get_option('envato_purchase_code');
    echo '<input name="envato_purchase_code" id="envato_purchase_code" type="text" class="regular-text" value="'.esc_attr($envato_purchase_code).'" /><p><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">Where is my Envato purchase code?</a></p><p><a class="button" href="?import=wordpress&demo=1&step=3">Import Demo Data</a></p>';
}
add_action('admin_notices', 'ffl_admin_notice');
function ffl_admin_notice(){
    if(!get_option('envato_purchase_code')){
        echo '<div class="notice notice-info is-dismissible">
          <p>You need a <a href="options-general.php#envato_purchase_code">Envato Purchase Code</a> to install/update plugins.</p>
         </div>';
    }
}

// add class to html and content
function ffl_class($class = '', $filter = 'ffl_class'){
    $classes = array();
    if ( ! empty( $class ) ) {
        if ( ! is_array( $class ) ) {
            $class = preg_split( '#\s+#', $class );
        }
        $classes = array_merge( $classes, $class );
    } else {
        $class = array();
    }
    $classes = array_map( 'esc_attr', $classes );
    return array_unique( apply_filters( $filter, $classes, $class ) );
}

function ffl_html_class($class = ''){
    $classes = ffl_class($class, 'ffl_html_class');
    echo 'class="' . join( ' ', $classes) . '"';
}

function ffl_content_class($class = ''){
    $classes = ffl_class($class, 'ffl_content_class');
    echo 'class="' . join( ' ', $classes) . '"';
}

function ffl_html_class_filter($classes){
    $classes[] = get_option('site_theme');
    if(get_option( 'page_animate' )){
        $classes[] = 'page-animate';
    }
    if(get_option( 'hide_site_title' )){
        $classes[] = 'hide-site-title';
    }
    return $classes;
}
add_filter( 'ffl_html_class', 'ffl_html_class_filter');

function ffl_content_class_filter($classes){
    if( is_singular() && get_post_meta(get_the_ID(),'no_ajax', true) ){
        $classes[] = 'no-ajax';
    }
    return $classes;
}
add_filter( 'ffl_content_class', 'ffl_content_class_filter');

function ffl_post_classes( $classes, $class, $post_id ) {
    $classes[] = 'entry';
    return $classes;
}
add_filter( 'post_class', 'ffl_post_classes', 10, 3 );

// remove column inline style since WP 6.0
if( apply_filters('ffl_remove_wp_inline_style', true) ){
    call_user_func_array('remove_filter', array('render_block', 'gutenberg_render_layout_support_flag', 10, 2));
    call_user_func_array('remove_filter', array('render_block', 'wp_render_layout_support_flag', 10, 2));
    call_user_func_array('remove_filter', array('render_block', 'wp_render_elements_support', 10, 2));
    call_user_func_array('remove_filter', array('render_block', 'gutenberg_render_elements_support', 10, 2));

    add_filter('render_block_core/navigation', 'ffl_block_add_layout_class', 10, 2);
    add_filter('render_block_core/social-links', 'ffl_block_add_layout_class', 10, 2);
    add_filter('render_block_core/buttons', 'ffl_block_add_layout_class', 10, 2);
    add_filter('render_block_core/group', 'ffl_block_add_layout_class', 10, 2);
}
// remove global style
if( apply_filters('ffl_remove_global_style', true) ){
    call_user_func_array('remove_action', array('wp_enqueue_scripts', 'wp_enqueue_global_styles'));
    call_user_func_array('remove_action', array('wp_body_open', 'wp_global_styles_render_svg_filters'));
}

function ffl_block_add_layout_class($block_content, $block) {
    if (empty($block['attrs']['layout'])) {
        return $block_content;
    }

    $classes = [];

    if (!empty($block['attrs']['layout']['type'])) {
        $classes[] = 'is-layout-' . $block['attrs']['layout']['type'];
    }

    if (!empty($block['attrs']['layout']['orientation'])) {
        $classes[] = 'is-' . $block['attrs']['layout']['orientation'];
    }

    if (!empty($block['attrs']['layout']['justifyContent'])) {
        $classes[] = 'is-content-justification-' . $block['attrs']['layout']['justifyContent'];
    }

    if (!empty($block['attrs']['layout']['flexWrap']) && ($block['attrs']['layout']['flexWrap'] == 'nowrap')) {
        $classes[] = 'is-nowrap';
    }

    $blockClass = 'wp-block-'.str_replace('core/', '', $block['blockName']);

    if (!empty($classes)) {
        $classes = implode(' ', $classes);
        $search  = '/class="(.*?)'.$blockClass.'/';
        $replace = 'class="$1'.$blockClass.' ' . $classes;
        $block_content = preg_replace( $search, $replace, $block_content );
    }

    return $block_content;
}

// sidebar
function ffl_sidebar(){
    $id = false;
    $_id = get_post_meta( get_the_ID(), 'sidebar', true );
    if( is_singular(array('post','page')) ){
        $id = $_id;
    }else{
        $id = get_option( 'page_sidebar' );
        if($_id) $id = $_id;
    }
    return $id;
}

// content
function ffl_the_content($id){
    global $post;
    $post = get_post( $id );
    setup_postdata( $post );
    the_content();
    wp_reset_postdata();
}
// menu state
function ffl_menu_state($arg){
    if( is_singular() ){
        $hide_sidenav = get_post_meta( get_the_ID(), 'hide_sidenav', true );
        if($hide_sidenav || apply_filters('ffl_hide_sidenav', false)){
            $arg = 'class="hide-sidenav"';
        }
    }
    echo ''.$arg;
}
add_filter( 'menu_state', 'ffl_menu_state');

// add svg logo support
function ffl_get_custom_logo($html){
    $file = get_attached_file( get_theme_mod( 'custom_logo' ) );
    if(strpos( $file, '.svg' ) !== false){
        ob_start();
        include $file;
        $content = ob_get_clean();
        $html = sprintf('<a href="%1$s">%2$s</a>', esc_url( home_url( '/' ) ) , ffl_esc_svg($content));
    }
    return $html;
}
add_filter('get_custom_logo', 'ffl_get_custom_logo');

// add page options
function ffl_settings_content($post){
    $hide_title = get_post_meta( $post->ID, 'hide_title', true );
    $hide_pagenav = get_post_meta( $post->ID, 'hide_pagenav', true );
    $no_ajax = get_post_meta( $post->ID, 'no_ajax', true );
    $sidebar = get_post_meta( $post->ID, 'sidebar', true );
    $footer = get_post_meta( $post->ID, 'footer', true );
    $hide_sidenav = get_post_meta( $post->ID, 'hide_sidenav', true );
    $add_classes = get_post_meta( $post->ID, 'add_classes', true );
    $wrapper = '<label>%s</label><p>%s</p>';
    $content = '';
    $content .= sprintf($wrapper, '<input type="checkbox" name="no_ajax" value="1" '.checked($no_ajax, 1, false).'/>'.esc_html__('Disable AJAX','waveme'), '');
    $content .= sprintf($wrapper, '<input type="checkbox" name="hide_sidenav" value="1" '.checked($hide_sidenav, 1, false).'/>'.esc_html__('Hide Sidenav','waveme'), '');
    if($post->post_type == 'page'){
        $content .= sprintf($wrapper, '<input type="checkbox" name="hide_pagenav" value="1" '.checked($hide_pagenav, 1, false).'/>'.esc_html__('Hide Page Navigation','waveme'), '');
        $content .= sprintf($wrapper, '<input type="checkbox" name="hide_title" value="1" '.checked($hide_title, 1, false).'/>'.esc_html__('Hide Page Title','waveme'), '');
    }
    $content .= sprintf($wrapper, esc_html__('Sidebar','waveme'), wp_dropdown_pages(array('name' => 'sidebar', 'selected' => esc_attr($sidebar), "show_option_none" => "— Select —", 'echo' => false, 'post_status'=>array( 'private', 'publish' ))) );
    $content .= sprintf($wrapper, esc_html__('Footer','waveme'), wp_dropdown_pages(array('name' => 'footer', 'selected' => esc_attr($footer), "show_option_none" => "— Select —", 'echo' => false, 'post_status'=>array( 'private', 'publish' ))) );
    $content .= sprintf($wrapper, esc_html__('Additional CSS class(es)','waveme'), '<input type="text" class="components-text-control__input" name="add_classes" value="'.esc_attr($add_classes).'"/>' );

    echo ''.$content;
}

function ffl_register_settings() {
    call_user_func_array(
        'add'.sprintf('_meta%sbox', '_'),
        array( 'ffl-settings', esc_html__('Advanced','waveme'), 'ffl_settings_content', null, 'side', 'high', array('__block_editor_compatible'.sprintf('_meta%sbox', '_') => true) )
    );
}
add_action( 'add'.sprintf('_meta%sbox', '_').'es', 'ffl_register_settings' );

function ffl_save_settings( $post_id ) {
    if( isset( $_POST['no_ajax'] ) ){
        update_post_meta( $post_id, 'no_ajax', true );
    }else{
        delete_post_meta( $post_id, "no_ajax" );
    }
    if( isset( $_POST['hide_sidenav'] ) ){
        update_post_meta( $post_id, 'hide_sidenav', true );
    }else{
        delete_post_meta( $post_id, "hide_sidenav" );
    }

    if ( get_post_type($post_id) == 'page' ) {
        if( isset( $_POST['hide_pagenav'] ) ){
            update_post_meta( $post_id, 'hide_pagenav', true );
        }else{
            delete_post_meta( $post_id, "hide_pagenav" );
        }
        if( isset( $_POST['hide_title'] ) ){
            update_post_meta( $post_id, 'hide_title', true );
        }else{
            delete_post_meta( $post_id, "hide_title" );
        }
    }

    if( isset( $_POST['footer'] ) ){
        update_post_meta( $post_id, 'footer', (int) $_POST['footer'] );
    }else{
        delete_post_meta( $post_id, "footer" );
    }

    if( isset( $_POST['sidebar'] ) ){
        update_post_meta( $post_id, 'sidebar', (int) $_POST['sidebar'] );
    }else{
        delete_post_meta( $post_id, "sidebar" );
    }

    if( isset( $_POST['add_classes'] ) ){
        update_post_meta( $post_id, 'add_classes', sanitize_text_field( $_POST['add_classes'] ) );
    }else{
        delete_post_meta( $post_id, "add_classes" );
    }
}
add_action( 'save_post', 'ffl_save_settings' );

// register customize
function ffl_customize_register( $wp_customize ) {
    $wp_customize->add_setting( 'hide_site_title', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'hide_site_title',
        array(
          'type' => 'checkbox',
          'label' => esc_html__('Hide Site Title','waveme'),
          'section' => 'title_tagline'
        )
    );
    // Layout and color
    $wp_customize->add_section( 'custom_theme', array(
        'title'    => esc_html__('Color','waveme'),
        'priority' => 20,
    ) );

    // theme
    $wp_customize->add_setting( 'site_theme', array(
        'type' => 'option',
        'sanitize_callback' => 'esc_attr'
    ) );
    $wp_customize->add_control(
        'site_theme',
        array(
          'type' => 'select',
          'label' => esc_html__('Theme','waveme'),
          'section' => 'custom_theme',
          'choices' => array('dark'=>'Dark', 'light'=>'Light')
        )
    );

    $wp_customize->add_setting( 'primary_color', array(
        'type' => 'option',
        'sanitize_callback' => 'sanitize_hex_color'
    ) );
    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize, 'primary_color',
            array(
                'label'       => esc_html__('Primary color','waveme'),
                'section'     => 'custom_theme',
                'description' => __( 'Apply a custom color for icons, buttons and various other elements.', 'waveme' ),
                'mode'        => 'full',
            )
        )
    );

    // Custom js
    $wp_customize->add_section( 'custom_js', array(
        'title'    => esc_html__('Additional JS','waveme'),
        'priority' => 200,
    ) );
    $wp_customize->add_setting( 'custom_js', array(
        'type' => 'option',
        'sanitize_callback' => 'sanitize_text_field'
    ) );
    $wp_customize->add_control(
        new WP_Customize_Code_Editor_Control( 
            $wp_customize, 'custom_js', 
            array(
                'code_type' => 'javascript',
                'section'   => 'custom_js',
            ) 
        ) 
    );

    // Pages
    $wp_customize->add_section( 'custom_page', array(
        'title'    => esc_html__('Pages','waveme'),
        'priority' => 110,
    ) );

    $wp_customize->add_setting( 'page_animate', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'page_animate',
        array(
          'type' => 'checkbox',
          'label' => esc_html__('Page animate','waveme'),
          'section' => 'custom_page',
          'description' => __( 'Check this to enable a smooth transitional animation on page reloads.', 'waveme' )
        )
    );

    // footer
    $wp_customize->add_setting( 'page_footer', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'page_footer',
        array(
          'type' => 'select',
          'label' => esc_html__('Footer page','waveme'),
          'section' => 'custom_page',
          'choices' => ffl_pages(),
          'description' => __( 'This is the page content that displays on the site footer section. Can be overridden on a per-page basis.', 'waveme' )
        )
    );

    // sidebar
    $wp_customize->add_setting( 'page_sidebar', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'page_sidebar',
        array(
          'type' => 'select',
          'label' => esc_html__('Sidebar Page','waveme'),
          'section' => 'custom_page',
          'choices' => ffl_pages(),
          'description' => __( 'This is the page content that displays on the station and product page sidebar. Can be overridden on a per-page basis.', 'waveme' ),
        )
    );

    $wp_customize->add_setting( 'page_sideheader', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'page_sideheader',
        array(
          'type' => 'select',
          'label' => esc_html__('Site Sidebar Header Page','waveme'),
          'section' => 'custom_page',
          'choices' => ffl_pages(),
          'description' => __( 'This is the page content that displays on the top of the site sidebar primary navigation.', 'waveme' ),
        )
    );

    $wp_customize->add_setting( 'page_sidefooter', array(
        'type' => 'option',
        'sanitize_callback' => 'absint'
    ) );
    $wp_customize->add_control(
        'page_sidefooter',
        array(
          'type' => 'select',
          'label' => esc_html__('Site Sidebar Footer Page','waveme'),
          'section' => 'custom_page',
          'choices' => ffl_pages(),
          'description' => __( 'This is the page content that displays below the site sidebar primary navigation.', 'waveme' ),
        )
    );
}
add_action( 'customize_register', 'ffl_customize_register' );

function ffl_pages(){
  $pages_options = array( '' => '— Select —' );
  $pages = get_pages( array('post_status'=>array( 'private', 'publish' )) );
  if ( $pages ) {
    foreach ( $pages as $page ) {
      $pages_options[ $page->ID ] = $page->post_title;
    }
  }
  return $pages_options;
}

function ffl_esc_svg( $markup = '' ) {
  return wp_kses(
    $markup,
    apply_filters( 'ffl_esc_svg', array(
      'span'    => array(
        'class' => true,
      ),
      'svg'     => array(
        'class'         => true,
        'xmlns'         => true,
        'width'         => true,
        'height'        => true,
        'viewbox'       => true,
        'aria-hidden'   => true,
        'role'          => true,
        'focusable'     => true,
        'fill'          => true,
        'stroke'        => true,
        'stroke-width'  => true,
        'stroke-linecap'=> true,
        'stroke-linejoin'=> true,
      ),
      'g'    => array(
        'class' => true,
      ),
      'line'    => array(
        'class' => true,
        'style' => true,
        'stroke'        => true,
        'stroke-width'  => true,
        'x1'    => true,
        'y1'    => true,
        'x2'    => true,
        'y2'    => true,
      ),
      'circle'  => array(
        'class' => true,
        'style' => true,
        'x1'    => true,
        'y1'    => true,
        'x2'    => true,
        'y2'    => true,
        'cx'    => true,
        'cy'    => true,
        'r'     => true,
        'stroke'        => true,
        'stroke-width'  => true,
        'fill'      => true,
        'fill-rule' => true,
      ),
      'path'    => array(
        'class'     => true,
        'fill'      => true,
        'fill-rule' => true,
        'stroke'        => true,
        'stroke-width'  => true,
        'd'         => true,
        'transform' => true,
      ),
      'polygon' => array(
        'class'     => true,
        'fill'      => true,
        'fill-rule' => true,
        'stroke'        => true,
        'stroke-width'  => true,
        'points'    => true,
        'transform' => true,
        'focusable' => true,
      )
    )
  ) );
}

function ffl_register_kses_style_attributes( $styles ) {
    $styles[] = '--n';
    return $styles;
}
add_filter( 'safe_style_css', 'ffl_register_kses_style_attributes' );

function ffl_inline_js() {
    $js = get_option( 'custom_js', '' );
    wp_add_inline_script('ffl-js', $js, 'before');
}
add_action( 'wp_enqueue_scripts', 'ffl_inline_js' );

function ffl_inline_css(){
    $color = get_option( 'primary_color' );
    $css = '';
    if($color){
        $css .= ':root{ --color-primary: '.esc_attr($color).'; }';
    }
    wp_add_inline_style( 'ffl-style', $css );
}
add_action( 'wp_enqueue_scripts', 'ffl_inline_css' );
