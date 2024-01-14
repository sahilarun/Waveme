<?php

defined( 'ABSPATH' ) || exit;

class Loop {

    private $name;
    private $version;
    private $build_url;
    private $args;
    private $http_query_arr;

    protected static $_instance = null;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        if ( ! function_exists( 'register_block_type' ) ) {
            // Gutenberg is not active.
            return;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $plugin          = get_plugin_data( dirname( dirname( __FILE__ ) ) . '/loop-block.php' );
        $this->name      = str_replace( ' ', '-', strtolower( $plugin[ 'Name' ] ) );
        $this->version   = $plugin[ 'Version' ];
        $this->build_url = plugin_dir_url( dirname( __FILE__ ) ) . 'build/';
        $this->http_query_arr = apply_filters('loop_block_http_query_arr_filter', ['post__in', 'include', 'author__in', 'ids']);

        add_action( 'init', array( $this, 'register' ), 999 );
        add_action( 'the_loop_block', array( $this, 'the_loop_block' ), 10, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'loop_more', array( $this, 'loop_more' ) );
        // find_in_set
        add_action( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
        // groupby
        add_action( 'loop_block_before_wp_query', array( $this, 'add_group_by' ), 10, 1 );
        add_action( 'loop_block_after_wp_query', array( $this, 'remove_group_by' ), 10, 1 );

        add_action( 'ffl_loop_template', array( $this, 'get_loop_block_template' ) );
        
        do_action( 'loop_block_init', $this );
    }

    public function register() {
        wp_register_style(
            $this->name . '-editor',
            $this->build_url . 'editor.css',
            array(),
            $this->version
        );
        wp_register_script(
            $this->name . '-editor',
            $this->build_url . 'editor.min.js',
            array(
                'lodash',
                'wp-i18n',
                'wp-compose',
                'wp-element',
                'wp-components',
                'wp-editor',
                'wp-edit-post',
                'wp-plugins',
                'wp-data',
                'wp-rich-text',
                'wp-hooks',
                'jquery'
            ),
            $this->version,
            true // Enqueue in the footer.
        );

        if ( is_admin() ) {
            $this->enqueue_styles();
            wp_localize_script( $this->name . '-editor', 'loop', array(
                'types'      => $this->get_loop_types(),
                'taxonomies' => $this->get_loop_taxonomies(),
                'templates'  => $this->get_loop_tpls(),
                'user_roles' => $this->get_user_roles()
            ), '', true );
        }

        // Here we actually register the block with WP, again using our namespacing.
        // We also specify the editor script to be used in the Gutenberg interface.
        register_block_type(
            'block/loop',
            array(
                'editor_script'   => $this->name . '-editor',
                'editor_style'    => $this->name . '-editor',
                'render_callback' => array( $this, 'render_loop_block' ),
                'attributes'      => array(
                    'title'        => array(
                        'type' => 'string'
                    ),
                    'subtitle'     => array(
                        'type' => 'string'
                    ),
                    'link' => array(
                        'type' => 'string'
                    ),
                    'linkText' => array(
                        'type' => 'string'
                    ),
                    'adTitle'        => array(
                        'type' => 'string'
                    ),
                    'adSubtitle'     => array(
                        'type' => 'string'
                    ),
                    'adImage'     => array(
                        'type' => 'string'
                    ),
                    'adSponsor'     => array(
                        'type' => 'string'
                    ),
                    'adOrder' => array(
                        'type' => 'string'
                    ),
                    'adLink' => array(
                        'type' => 'string'
                    ),
                    'type'         => array(
                        'type' => 'string'
                    ),
                    'taxonomy'     => array(
                        'type' => 'string'
                    ),
                    'ids'          => array(
                        'type'    => 'array',
                        'default' => [],
                        'items'   => [
                            'type' => 'string'
                        ],
                    ),
                    'taxQuery'     => array(
                        'type'    => 'array',
                        'default' => [],
                        'items'   => [
                            'type' => 'string'
                        ],
                    ),
                    'metaQuery'    => array(
                        'type'    => 'array',
                        'default' => [],
                        'items'   => [
                            'type' => 'string'
                        ],
                    ),
                    'date'         => array(
                        'type' => 'object'
                    ),
                    'query'        => array(
                        'type' => 'string'
                    ),
                    'orderby'      => array(
                        'type' => 'string'
                    ),
                    'order'        => array(
                        'type' => 'string'
                    ),
                    'template'     => array(
                        'type' => 'string'
                    ),
                    'slider'       => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'sliderOption' => array(
                        'type'    => 'string',
                        'default' => ''
                    ),
                    'sliderArrows' => array(
                        'type'    => 'boolean',
                        'default' => true
                    ),
                    'sliderDots' => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'sliderAutoplay' => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'sliderLoop' => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'cols'         => array(
                        'type' => 'number'
                    ),
                    'rows'         => array(
                        'type' => 'number'
                    ),
                    'gap'          => array(
                        'type' => 'number'
                    ),
                    'ratio' => array(
                        'type'    => 'number',
                        'default' => 1
                    ),
                    'pages'        => array(
                        'type'    => 'number',
                        'default' => 5
                    ),
                    'pager'        => array(
                        'type'    => 'string',
                        'default' => ''
                    ),
                    'filter'       => array(
                        'type'    => 'array',
                        'default' => [],
                        'items'   => [
                            'type' => 'string'
                        ]
                    ),
                    'empty'        => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'emptyContent' => array(
                        'type'    => 'string',
                        'default' => ''
                    ),
                    'debug'        => array(
                        'type'    => 'boolean',
                        'default' => false
                    ),
                    'className'    => array(
                        'type'    => 'string',
                        'default' => ''
                    ),
                    'align'        => array(
                        'type'    => 'string',
                        'default' => ''
                    )
                )
            )
        );

        register_block_type(
            'block/permission', 
            array(
                'render_callback' => array( $this, 'render_permission_block' ),
            )
        );
    }

    public function get_user_roles(){
        global $wp_roles;
        $wp_roles_names = array_reverse( $wp_roles->role_names );
        return array_keys($wp_roles_names);
    }


    public function isPermissionFiltered($atts){
        if ( !isset($atts['displayPermission']) )
            return true;

        if ( empty($atts['displayPermission']) )
            return true;

        if ( $atts['displayPermission'] == 'users' ) {
            return is_user_logged_in();
        }

        else if ( $atts['displayPermission'] == 'user_roles' ) {
            if ( !is_user_logged_in() ) {
                return null;
            }

            if ( isset( $atts['userRoles'] ) ) {
                $role = $atts['userRoles'];
                $role = is_array($role) ? array_filter( $role ) : $role;
                if( $role ){
                    $user = wp_get_current_user();
                    if( count( array_intersect($role, $user->roles) ) > 0 ){
                        return true;
                    }
                }
            }
        }
        else if ($atts['displayPermission'] == 'php_func') {
            if (isset($atts['phpFunc'])) {
                if (function_exists($atts['phpFunc'])) {
                    return call_user_func($atts['phpFunc']);
                }
            }
            return true;
        }
        return false;
    }

    public function render_permission_block( $atts, $content ){
        $display = !( isset($atts['display']) && !empty( $atts['display']) );
        $filtered = $this->isPermissionFiltered($atts);

        if ( $filtered === null ) {
            return '';
        }

        if ( $display ^ $filtered ) {
            return '';
        }

        return $content;
    }

    public function enqueue_styles() {
        $suffix = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';

        wp_register_style(
            $this->name . '-style',
            $this->build_url . 'style' . $suffix . '.css',
            array(),
            $this->version
        );

        wp_enqueue_style( $this->name . '-style' );
    }

    public function enqueue_scripts() {
        $this->enqueue_styles();

        $suffix = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';

        wp_register_script(
            $this->name,
            $this->build_url . 'loop' . $suffix . '.js',
            array(),
            $this->version,
            true
        );

        wp_enqueue_script( $this->name );
    }

    public function render_loop_block( $attributes ) {
        $block_content = $className = $slick = $jscroll = $more = $pager = $title = $filter = $items = '';

        $query = false;

        $attributes = apply_filters( 'loop_block_attributes', $attributes );

        do_action( 'loop_block_before_render', $attributes );

        if ( empty( $attributes[ 'type' ] ) ) {
            $block_content = __( 'No loop type.' );
        } else {

            $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
            parse_str( $query_string, $q );

            $attributes = array_merge( $attributes, $q );
            array_walk_recursive($attributes, 'sanitize_text_field');
            
            $query_args = $this->get_loop_query_params( apply_filters( 'loop_block_get_query_params_attributes', $attributes ) );

            $total_pages = 0;
            if ( $attributes[ 'type' ] == 'user' ) {
                $query       = new WP_User_Query( $query_args );
                $total_pages = $query->query_vars[ 'number' ] !== 0 ? ceil( $query->total_users / $query->query_vars[ 'number' ] ) : 1;
            } elseif ( $attributes[ 'type' ] == 'taxonomy' ) {
                $query = new WP_Term_Query( $query_args );
                if ( isset( $query_args[ 'taxonomy' ] ) ) {
                    $offset = 0;
                    if ( isset( $query_args[ 'offset' ] ) ) {
                        $offset = $query_args[ 'offset' ];
                        unset( $query_args[ 'offset' ] );
                    }
                    $total                  = wp_count_terms( $query_args[ 'taxonomy' ], $query_args );
                    $total_pages = $query->query_vars[ 'number' ] !== 0 ? ceil( $total / $query->query_vars[ 'number' ] ) : 1;
                    $query_args[ 'offset' ] = $offset;
                }
            } elseif ( is_string($attributes[ 'type' ]) && strpos( strtolower( $attributes[ 'type' ] ), 'custom_type' ) !== false ) {

                $query = $this->get_loop_sql_query( $attributes );
                if( $query ){
                    $total_pages = $query->max_num_pages;
                }
            } else {
                do_action( 'loop_block_before_wp_query', $query_args );
                $query       = new WP_Query( $query_args );
                do_action( 'loop_block_after_wp_query', $query_args );

                $total_pages = $query->max_num_pages;
            }

            if ( isset( $attributes[ 'className' ] ) ) {
                $className = $attributes[ 'className' ];
            }
            if ( isset( $attributes[ 'align' ] ) && $attributes[ 'align' ] !=='' ) {
                $className .= ' align' . $attributes[ 'align' ];
            }
            if ( $total_pages == 0 ) {
                $className .= ' wp-block-loop-empty';
            }

            $title = '';
            if ( isset( $attributes[ 'title' ] ) && $attributes[ 'title' ] ) {
                $subtitle = $link = '';
                $attributes[ 'title' ] = $this->kses_post( $attributes[ 'title' ], 'loop_title' );
                if ( isset( $attributes[ 'subtitle' ] ) && $attributes[ 'subtitle' ] ) {
                    $attributes[ 'subtitle' ] = $this->kses_post( $attributes[ 'subtitle' ], 'loop_subtitle' );
                    $subtitle                 = sprintf( '<span class="block-loop-subtitle">%s</span>', $attributes[ 'subtitle' ] );
                }
                if ( isset( $attributes[ 'linkText' ] ) && $attributes[ 'linkText' ] ) {
                    $attributes[ 'linkText' ] = $this->kses_post( $attributes[ 'linkText' ], 'loop_linkText' );
                    $url = isset( $attributes[ 'link' ] ) ? $attributes[ 'link' ] : '';
                    $link = sprintf( '<a class="block-loop-more" href="%s">%s</a>', esc_url($url), $attributes[ 'linkText' ] );
                }
                $title = sprintf( '<h3 class="block-loop-title"><span class="block-loop-heading">%s</span>%s %s</h3>', $attributes[ 'title' ], $link, $subtitle );
            }

            if ( isset( $attributes[ 'slider' ] ) && $attributes[ 'slider' ] ) {
                $option = isset($attributes[ 'sliderOption' ]) ? $attributes[ 'sliderOption' ] : '';

                $arrows = (isset($attributes[ 'sliderArrows' ]) && $attributes[ 'sliderArrows' ]) ? 'true' : 0;
                $dots = (isset($attributes[ 'sliderDots' ]) && $attributes[ 'sliderDots' ]) ? 'true' : 0;
                $autoplay = (isset($attributes[ 'sliderAutoplay' ]) && $attributes[ 'sliderAutoplay' ]) ? 'true' : 0;
                $loop = (isset($attributes[ 'sliderLoop' ]) && $attributes[ 'sliderLoop' ]) ? 'true' : 0;

                if( $dots || $autoplay || $loop ){
                    $option = sprintf( '{arrows: %s, dots: %s, autoplay: %s, loop: %s}', $arrows, $dots, $autoplay, $loop );
                }
                $slick = 'data-plugin="slider" data-option="' . esc_attr( $option ) . '"';
                $className .= ' wp-block-loop-slider';
            }

            $filter = $this->get_filter( $attributes );
            // columns and rows

            $cols = isset( $attributes[ 'cols' ] ) ? ( $attributes[ 'cols' ] > 2 ? '--grid-cols:' : '--grid-columns:' ) . (int) $attributes[ 'cols' ] . ';' : '';
            $rows = isset( $attributes[ 'rows' ] ) ? '--grid-rows:' . (int) $attributes[ 'rows' ] . ';' : '';
            $gap  = isset( $attributes[ 'gap' ] ) ? '--grid-gap:' . (int) $attributes[ 'gap' ] . 'px;' : '';
            $ratio = isset( $attributes[ 'ratio' ] ) ? '--loop-ratio:' . (float) $attributes[ 'ratio' ] : '';
            // wrapper
            $wrapper = sprintf( '<div class="wp-block-loop wp-block-loop-%1$s %2$s"', esc_attr( is_array( $attributes[ 'type' ] ) ? implode( '-', $attributes[ 'type' ] ) : $attributes[ 'type' ] ), esc_attr( $className ) ) . '>' . $title . '%6$s<div class="block-loop-items" style="' . $cols . $rows . $gap . $ratio . '" %1$s %2$s>'.$this->get_ad_content($attributes).'%3$s%4$s%5$s';

            if (isset($attributes[ 'pager' ]) && $attributes[ 'pager' ] == 'pagination' ) {
                $pager = $this->get_loop_pagination( $total_pages, $query_args[ 'paged' ] );
            }

            if ( $total_pages > 0 ) {
                unset($attributes[ 'emptyContent' ]);
                $items = $this->get_loop_content( $query, $attributes, $query_args );
            } else {
                if ( isset( $attributes[ 'emptyContent' ] ) ) {
                    $attributes[ 'emptyContent' ] = $this->kses_post( $attributes[ 'emptyContent' ], 'loop_empty_content' );
                    $items = sprintf( '<div class="block-loop-empty">%s</div>', $attributes[ 'emptyContent' ] );
                }
            }
            if ( isset($attributes[ 'pager' ]) && ( $attributes[ 'pager' ] == 'more' || $attributes[ 'pager' ] == 'scroll' ) && $total_pages > 1 ) {
                if ( ! isset( $attributes[ 'ajax' ] ) ) {
                    $jscroll = 'data-plugin="scroller" data-option="' . sprintf( '{autoTrigger: %1$s}"', $attributes[ 'pager' ] == 'scroll' ? 'true' : 'false' );
                } else {
                    // overide wrapper
                    $wrapper = '%1$s%2$s%3$s%4$s%5$s';
                }
                if ( $total_pages > $query_args[ 'paged' ] ) {
                    $attributes[ 'paged' ] = $query_args[ 'paged' ] + 1;
                    $attributes            = $this->normalize_load_more_attributes( $attributes );
                    $url                   = get_rest_url( null, '/loop/more' ). sprintf( '?%s', $this->http_build_query( $attributes ) );
                    $url                   = apply_filters( 'loop_block_more_link', $url, $attributes, $this ); 
                    $more                  = '<a href="' . esc_url( $url ) . '" class="scroller no-ajax button"><span>• • •</span><span class="screen-reader-text">' . __( 'More' ) . '</span></a>';
                    $more                  = apply_filters( 'loop_block_more_button', $more, $url, $this ); 
                }
            }

            if ( $total_pages > 0 || ( isset( $attributes[ 'empty' ] ) && $attributes[ 'empty' ] ) ) {
                $wrapper = $wrapper . '</div></div>';
                $block_content = sprintf( $wrapper, $jscroll, $slick, $items, $more, $pager, $filter );
            }

            if ( $total_pages == 0 ) {
                if ( isset( $_GET[ 'context' ] ) && $_GET[ 'context' ] == 'edit' ) {
                    $block_content = __( 'No loop item.' );
                }
            }

            if ( isset( $attributes[ 'debug' ] ) && $attributes[ 'debug' ] && current_user_can( 'edit_posts' ) ) {
                $block_content .= '<pre style="font-size: 11px">' . print_r( $query_args, true ) . '</pre>';
            }

            wp_reset_query();
        }

        do_action( 'loop_block_after_render', $attributes, $query );

        return apply_filters( 'loop_block_content', $block_content );
    }

    public function the_loop_block( $attributes, $echo = true ) {
        if ( $echo ) {
            echo $this->render_loop_block( $attributes );
        } else {
            return $this->render_loop_block( $attributes );
        }
    }

    private function get_filter( $attributes ) {
        if ( ! isset( $attributes[ 'filter' ] ) || ( isset( $_GET[ 'context' ] ) && $_GET[ 'context' ] == 'edit' ) ) {
            return '';
        }
        $filter = $attributes[ 'filter' ];

        // parse query string
        $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
        parse_str( $query_string, $query );

        array_walk_recursive($query, 'sanitize_text_field');

        $q = [];
        foreach ($query as $key => $value) {
            if(is_array($value)){
                continue;
            }
            $term = preg_split('/\+/', $value);
            $tax = [];
            foreach ($term as $k => $v) {
                $m = preg_split('/__/', $v);
                $i = isset($m[1]) ? $m[0] : '';
                $j = isset($m[1]) ? $m[1] : $m[0];
                $arr = explode(',',$j);
                foreach ($arr as $kk => $vv) {
                    $tax[$i][$vv] = '';
                }
            }
            $q[$key] = $tax;
        }

        // parse the filter::value|slug__value[name]
        $filters = [];
        foreach ($filter as $key => $value) {
            $i = explode('::', $value);
            if( count($i) < 2 ){
                continue;
            }
            $k = $i[0];
            $v = $i[1];
            $r = '/(.*?)\[(.*?)\]/';
            $s = '/__/';

            if( strpos( strtolower($value), '__all' ) ){
                // get all the taxonomy list
                $reg = '/(.*?)__all\[(.*?)\]/';
                if( preg_match($reg, $v, $m) ){
                    $i = $m[1];
                    $default = (object) array( 'name' => (isset($m[2]) ? $m[2] : 'All'), 'slug' => '', 'count' => '' );
                    $terms = get_terms( $i );
                    if( !empty($terms) && !is_wp_error( $terms ) ){
                        array_unshift($terms, $default);
                        foreach ($terms as $t) {
                            $item = array( 'name' => $t->name, 'count' => $t->count );
                            $j = $t->slug;
                            $this->build_filter_item($item, $k, $i, $j, $q);
                            $filters[$k][$i][$j] = $item;
                        }
                    }
                }
                continue;
            }

            if( preg_match($r, $v, $ma) ){
                // fix genre_
                $term = ( strpos( $ma[1], '_' ) !== false && strpos( $ma[1], '__' ) == false ) ? $ma[1].'_' : $ma[1];
                $term = preg_split($s, $term);
                $i = isset($term[1]) ? $term[0] : '';
                $j = isset($term[1]) ? $term[1] : $term[0];

                $item = array( 'name' => $ma[2] );
                if( $k == 'range' ){
                    if(isset( $q[$k] ) && isset( $q[$k][$i] )){
                        $item['value'] = key( $q[$k][$i] );
                    }else{
                        $item['value'] = $j;
                    }
                }
                $this->build_filter_item($item, $k, $i, $j, $q);

                $filters[$k][$i][$j] = $item;
            }
        }

        if( empty($filters) ){
            return;
        }

        $tpl     = 'templates/filter.php';
        ob_start();
        if ( $template = locate_template( $tpl ) ) {
            include $template;
        } else {
            include plugin_dir_path( dirname( __FILE__ ) ) . $tpl;
        }

        return ob_get_clean();
    }

    private function build_filter_item(&$item, $key, $term, $value, $q){
        if( isset( $q[$key][$term] ) ){
            if($value == ''){
                unset( $q[$key][$term] );
            }else{
                if( isset( $q[$key][$term][$value]) ){
                    $item['active'] = true;
                    unset( $q[$key][$term][$value] );
                }else{
                    if( in_array( $key, apply_filters('loop_block_filter_single', array('order','orderby') ) ) ){
                        unset( $q[$key][$term] );
                    }
                    $q[$key][$term][$value] = '';
                }
            }
        }else{
            $q[$key][$term][$value] = '';
        }

        $query = [];
        foreach ($q as $key => $value) {
            foreach ($value as $k => $v) {
                $keys = implode(',', array_keys($v));
                if($keys !== ''){
                    $query[$key][] = ($k !=='' ? $k.'__' : '' ).$keys;
                }
            }
        }

        $str = [];
        foreach ($query as $key => $value) {
            $str[$key] = implode('+', $value);
        }

        $url = get_permalink() . '?' . http_build_query($str);
        $url = apply_filters( 'loop_block_filter_link', $url, $item, $key, $term, $value, $q, $str );
        $item['url'] = $url;
    }

    private function get_ad_content($attributes){
        if(!isset($attributes['adTitle']) || $attributes['adTitle']===''){
            return;
        }

        $defaults = array(
            'adImage' => '',
            'adTitle' => '',
            'adSubtitle' => '',
            'adSponsor' => '',
            'adLink' => '',
            'adOrder' => 0
        );

        $data = wp_parse_args( $attributes, $defaults );

        $tpl     = 'templates/ad.php';
        ob_start();
        if ( $template = locate_template( $tpl ) ) {
            include $template;
        } else {
            include plugin_dir_path( dirname( __FILE__ ) ) . $tpl;
        }

        return ob_get_clean();
    }

    private function get_loop_content( $query, $attributes, $query_args ) {
        $template = '';
        if ( isset( $attributes[ 'template' ] ) ) {
            $template = $attributes[ 'template' ];
        }
        $tpl = $this->get_loop_template( $template, $attributes[ 'type' ] );

        ob_start();
        global $post;

        if ( $attributes[ 'type' ] == 'user' ) {
            foreach ( $query->get_results() as $user ) {
                include $tpl;
            }
        } elseif ( $attributes[ 'type' ] == 'taxonomy' ) {
            foreach ( $query->get_terms() as $term ) {
                include $tpl;
            }
        } elseif ( is_string($attributes[ 'type' ]) && strpos( strtolower( $attributes[ 'type' ] ), 'custom_type' ) !== false  ) {
            foreach ( $query->items as $item ) {
                include $tpl;
            }
        } else {
            foreach ( $query->posts as $post ) {
                // avoid loop in loop
                $post->post_content = str_replace( "wp:block/loop", "wp:block/loop-disabled", $post->post_content );
                setup_postdata( $post );
                include $tpl;
            }
            wp_reset_postdata();
        }
        wp_reset_query();

        return ob_get_clean();
    }

    public function get_loop_block_template() {
        $attributes = '';
        $tpl        = apply_filters( 'loop_block_template', '' );
        include $this->get_loop_template( $tpl, '' );
    }

    private function get_loop_template( $tpl, $type ) {
        if ( $tpl && ( $template = $this->locate_template( $tpl ) ) ) {
            return $template;
        }

        $default_tpl = 'loop.php';
        if ( $type == 'user' ) {
            $default_tpl = 'loop-user.php';
        } elseif ( $type == 'taxonomy' ) {
            $default_tpl = 'loop-taxonomy.php';
        } elseif ( $type == 'attachment' ) {
            $default_tpl = 'loop-attachment.php';
        } elseif ( $type == 'post' || $type == 'page' ) {
            $default_tpl = 'loop-post.php';
        }

        return $this->locate_template( $default_tpl );
    }

    public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
        if ( ! $template_path ) {
            $template_path = '/templates/';
        }

        if ( ! $default_path ) {
            $default_path = plugin_dir_path( dirname( __FILE__ ) );
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

            $tpl_name = basename( $template_name );

            // Force .php extension if template name is tampered with
            if ( 'php' != pathinfo( $tpl_name, PATHINFO_EXTENSION ) ) {
              $info = pathinfo( $tpl_name );
              $tpl_name = $info['filename'] . '.' . 'php';
            }

            // Rebuild the template path removing anything nasty
            $tpl = $default_path . trailingslashit( $template_path ) . $tpl_name;

            if ( file_exists( $tpl ) ) {
                $template = $tpl;
            }
        }

        // Return what we found.
        return apply_filters( 'loop_locate_template', $template, $template_name, $template_path );
    }

    private function get_loop_query_params( $args ) {
        $query_args = array();

        if ( $args[ 'type' ] == 'user' || $args[ 'type' ] == 'taxonomy' ) {
            $query_args[ 'number' ] = $args[ 'pages' ];
            $include                = 'include';
            if ( $args[ 'type' ] == 'taxonomy' && isset($args[ 'taxonomy' ]) ) {
                $query_args[ 'taxonomy' ]   = $args[ 'taxonomy' ];
                $query_args[ 'hide_empty' ] = false;
            }
        } else {
            $query_args[ 'post_type' ]      = $args[ 'type' ];
            $query_args[ 'posts_per_page' ] = $args[ 'pages' ];
            $include                        = 'post__in';
            // ignore sticky post
            $query_args[ 'ignore_sticky_posts' ] = 1;
        }

        if ( $args[ 'type' ] == 'attachment' ) {
            $unsupported_mimes  = apply_filters('loop_block_unsupported_mimes', array( 'application/x-gzip', 'application/zip', 'application/rar', 'application/x-7z-compressed' ));
            $all_mimes          = get_allowed_mime_types();
            $accepted_mimes     = array_diff( $all_mimes, $unsupported_mimes );

            $query_args[ 'post_mime_type' ] = $accepted_mimes;
            $query_args[ 'post_status' ] = 'inherit';
        }

        // ids
        if ( isset( $args[ 'ids' ] ) && !empty( $args[ 'ids' ] ) ) {
            $query_args[ $include ] = $args[ 'ids' ];
        }

        // taxonomy
        if ( isset( $args[ 'taxQuery' ] ) && ! empty( $args[ 'taxQuery' ] ) ) {
            $queries = ( is_array( $args[ 'taxQuery' ] ) ? $args[ 'taxQuery' ] : explode( '+', $args[ 'taxQuery' ] ) );
            $query_args[ 'tax_query' ] = array();

            // multiple taxonomies, same tax using OR, diff using AND
            $ts = [];
            
            foreach ($queries as $q) {
                $matches = preg_split( '/\:|__/', $q );
                if(count( $matches ) < 2){
                    continue;
                }
                $key = $matches[ 0 ];
                array_shift($matches);
                $ts[$key][] = implode(':', $matches);
            }
            
            foreach ( $ts as $key => $value ) {
                $a = [];
                foreach($value as $k => $v){
                    $matches = preg_split( '/\:/', $v );

                    $terms = explode( ',', $matches[ 0 ] );
                    foreach($terms as $term){
                        $arr = array(
                            'taxonomy' => $key,
                            'field'    => 'slug',
                            'terms'    => $term
                        );

                        if ( isset( $matches[ 1 ] ) && $matches[ 1 ] ) {
                            $arr[ 'operator' ] = $matches[ 1 ];
                        }
                        if ( isset( $matches[ 2 ] ) && $matches[ 2 ] ) {
                            $arr[ 'include_children' ] = $matches[ 2 ];
                        }

                        $a[] = $arr;
                    }
                    if(count($a) > 1){
                        $a[ 'relation' ] = apply_filters( 'loop_block_query_taxonomy_'.$key.'_relation', 'OR', $args[ 'taxQuery' ] );
                    }
                }
                $query_args[ 'tax_query' ][] = $a;
            }
        }

        // meta
        if ( (isset( $args[ 'metaQuery' ] ) && ! empty( $args[ 'metaQuery' ] ) ) || (isset( $args[ 'range' ] ) && ! empty( $args[ 'range' ] ) ) ) {
            $type = '';
            if( isset( $args[ 'metaQuery' ] ) ){
                $type = '';
                $queries = ( is_array( $args[ 'metaQuery' ] ) ? $args[ 'metaQuery' ] : explode( '+', $args[ 'metaQuery' ] ) );
            }

            if( isset( $args[ 'range' ] ) ){
                $type = 'range';
                $queries = ( is_array( $args[ 'range' ] ) ? $args[ 'range' ] : explode( '+', $args[ 'range' ] ) );
            }
            
            $query_args[ 'meta_query' ] = array();
            foreach ( $queries as $q ) {
                //match : or __
                //eg: key:value:compare:type
                $matches = preg_split( '/\:|__/', $q );
                if ( $matches && isset( $matches[ 0 ] ) && $matches[ 0 ] && isset( $matches[ 1 ] ) && $matches[ 1 ] ) {
                    $sep = ',';
                    if($type == 'range'){
                        $sep = '-';
                    }
                    $arr = array(
                        'key'   => $matches[ 0 ],
                        'value' => strpos( $matches[ 1 ], $sep ) !== false ? explode( $sep, $matches[ 1 ] ) : $matches[ 1 ]
                    );

                    if($type == 'range'){
                        $arr[ 'compare' ] = 'BETWEEN';
                        $arr[ 'type' ] = 'numeric';
                    }

                    if ( isset( $matches[ 2 ] ) && $matches[ 2 ] ) {
                        $arr[ 'compare' ] = $matches[ 2 ];
                        if ( strpos( $arr[ 'compare' ], 'EXISTS' ) !== false ) {
                            unset( $arr[ 'value' ] );
                        }
                    }
                    if ( isset( $matches[ 3 ] ) && $matches[ 3 ] ) {
                        $arr[ 'type' ] = $matches[ 3 ];
                    }
                    $query_args[ 'meta_query' ][] = $arr;
                }
            }
        }

        // date
        if ( ! empty( $args[ 'date' ] ) && ! empty( $args[ 'date' ][ 'type' ] ) && $args[ 'date' ][ 'type' ] !== '0' ) {
            $query_args[ 'date_query' ] = array(
                'after'  => $args[ 'date' ][ 'after' ],
                'before' => $args[ 'date' ][ 'before' ]
            );
            if ( $args[ 'date' ][ 'type' ] == '2' ) {
                $query_args[ 'date_query' ] = array(
                    'after'  => $args[ 'date' ][ 'afterNum' ] . ' ' . $args[ 'date' ][ 'afterUnit' ],
                    'before' => $args[ 'date' ][ 'beforeNum' ] . ' ' . $args[ 'date' ][ 'beforeUnit' ]
                );
            }
        }

        // order
        if ( ! empty( $args[ 'order' ] ) ) {
            $query_args[ 'order' ] = $args[ 'order' ];
        }
        // orderby
        if ( ! empty( $args[ 'orderby' ] ) ) {
            $query_args[ 'orderby' ] = $args[ 'orderby' ];
            if ( $query_args[ 'orderby' ] == 'meta_value_num' ) {
                $query_args[ 'meta_type' ] = 'NUMERIC';
            }
        }

        // page
        $query_args[ 'paged' ] = isset( $args[ 'paged' ] ) ? intval( $args[ 'paged' ] ) : 1;
        if ( isset( $args[ 'pager' ] ) ) {
            $query_args[ 'paged' ] = $this->get_loop_paged();
            // use offset for taxonomy
            if ( $args[ 'type' ] == 'taxonomy' ) {
                $query_args[ 'offset' ] = ( $this->get_loop_paged() - 1 ) * $query_args[ 'number' ];
            }
        }

        $query_args = apply_filters( 'loop_block_query_args', $query_args, $args );

        // query
        if ( ! empty( $args[ 'query' ] ) ) {
            $query_args = wp_parse_args( $args[ 'query' ], $query_args );
        }

        // fix long query string
        foreach ($this->http_query_arr as $value) {
            if( isset($query_args[$value]) && !is_array($query_args[$value]) ){
                $query_args[$value] = array_filter( explode( ',', $query_args[$value] ), 'intval' );
            }
        }

        if ( isset( $query_args[ 'tax_query' ] ) && count($query_args[ 'tax_query' ]) > 1 ) {
            $query_args[ 'tax_query' ][ 'relation' ] = apply_filters( 'loop_block_query_taxonomy_relation', 'AND', $query_args[ 'tax_query' ] );
        }
        if ( isset( $query_args[ 'meta_query' ] ) && count($query_args[ 'meta_query' ]) > 1 ) {
            $query_args[ 'meta_query' ][ 'relation' ] = apply_filters( 'loop_block_query_meta_relation', 'AND', $query_args[ 'meta_query' ] );
        }

        $query_args = apply_filters( 'loop_block_query_final_args', $query_args, $args );

        return $query_args;
    }

    private function get_loop_sql_query( $args = array() ) {
        $query = apply_filters('loop_block_sql_query', $args);
        return $query;
    }

    private function get_loop_types() {
        $types = [ array( 'label' => '— Select —' ) ];
        foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $id => $type ) {
            if ( ! empty( $type->labels->name ) ) {
                $types[] = array( 'label' => $type->labels->name, 'value' => $id );
            }
        }
        $types[] = array( 'label' => '-' );
        $types[] = array( 'label' => 'Users', 'value' => 'user' );
        $types[] = array( 'label' => 'Taxonomies', 'value' => 'taxonomy' );

        return apply_filters( 'loop_block_types', $types, $this );
    }

    private function get_loop_taxonomies() {
        $taxonomies = [];
        foreach ( get_taxonomies( array( 'public' => true, '_builtin' => false ) ) as $taxonomy ) {
            $taxonomies[] = array( 'label' => $taxonomy, 'value' => $taxonomy );
        }

        return apply_filters( 'loop_block_taxonomies', $taxonomies, $this );
    }

    private function get_loop_tpls() {
        $tpls      = [ array( 'label' => '— Select —' ) ];
        $tpl_files = apply_filters( 'loop_block_template_files', array(
            '*/content*.php',
            '*/*/content*.php',
            '*/loop*.php',
            '*/*/loop*.php',
        ));
        $tpl_dirs  = apply_filters( 'loop_block_template_dirs', array(
            plugin_dir_path( dirname( __FILE__ ) ),
            get_template_directory(),
            get_stylesheet_directory()
        ));
        $tpl_dirs  = array_unique( $tpl_dirs );
        foreach ( $tpl_dirs as $dir ) {
            foreach ( $tpl_files as $tpl_file ) {
                foreach ( (array) glob( $dir . '/' . $tpl_file ) as $file ) {
                    if ( file_exists( $file ) ) {
                        $dir_file = str_replace( $dir . '/', '', $file );
                        //$tpls[]   = array( 'label' => $dir_file, 'value' => $dir_file );

                        if ( preg_match( '|Template Name:(.*)$|mi', file_get_contents( $file ), $header ) ) {
                          $tpl_name = _cleanup_header_comment( $header[1] );
                        } else {
                          $tpl_name = $dir_file;
                        }

                        $tpls[]   = array( 'label' => esc_html( $tpl_name ), 'value' => esc_attr( $dir_file ) );
                        
                    }
                }
            }
        }
        sort( $tpls );

        return apply_filters( 'loop_block_templates', $tpls );
    }

    private function get_loop_pagination( $total, $paged ) {
        if ( get_option( 'permalink_structure' ) ) {
            $base   = str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) );
            $format = '?paged=%#%';
        } else {
            $base   = @add_query_arg( 'paged', '%#%' );
            $format = 'page/%#%/';
        }

        $pagination = paginate_links( apply_filters( 'loop_pagination_args', array(
            'base'      => $base,
            'format'    => $format,
            'current'   => max( 1, $paged ),
            'total'     => $total,
            'prev_text' => sprintf(
                '%s <span class="nav-prev-text">%s</span>',
                '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" class="svg-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img" focusable="false"><polyline points="15 18 9 12 15 6"></polyline></svg>',
                __( 'Newer posts' )
            ),
            'next_text' => sprintf(
                '<span class="nav-next-text">%s</span> %s',
                __( 'Older posts' ),
                '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" class="svg-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img" focusable="false"><polyline points="9 18 15 12 9 6"></polyline></svg>'
            ),
            'type'      => '',
            'end_size'  => 1,
            'mid_size'  => 1
        ) ) );
        if ( ! empty( $pagination ) ) {
            $pagination = preg_replace( '/\r|\n/', ' ', $pagination );
            $pagination = sprintf( '<nav class="navigation pagination"><div class="nav-links">%1$s</div></nav>', $pagination );
        }

        return apply_filters( 'loop_block_pagination', $pagination, $total, $paged, $this );
    }

    private function get_loop_paged() {
        global $wp_rewrite, $wp;
        $url = home_url( $wp->request );
        if ( $wp_rewrite->using_permalinks() ) {
            if ( get_query_var( 'paged' ) ) {
                // sub page
                $paged = get_query_var( 'paged' );
            } elseif ( strpos( $url, '/page/' ) !== false ) {
                // home page
                preg_match( '/\/page\/([0-9]+)/', $url, $matches );
                if ( ! empty( $matches[ 1 ] ) ) {
                    $paged = intval( $matches[ 1 ] );
                } else {
                    $paged = 1;
                }
            } else {
                $paged = 1;
            }
        } else {
            $paged = isset( $_GET[ 'paged' ] ) ? intval( $_GET[ 'paged' ] ) : 1;
        }

        if( isset( $_GET[ 'paged' ] ) ){
            $paged = intval( $_GET[ 'paged' ] );
        }

        return apply_filters( 'loop_block_get_loop_paged', $paged, $this );
    }

    public function loop_more( $request ) {
        if ( isset( $request[ 'paged' ] ) ) {
            $args = $request->get_params();
            $args[ 'ajax' ] = 1;
            return wp_send_json( 
                array( 'content' => $this->render_loop_block( apply_filters( 'loop_ajax_loop_more', $args, $this ) ) )
            );
        }
    }

    public function add_group_by( $query_args ){
        $this->args = $query_args;
        if( isset($this->args['groupby']) ){
            add_filter( 'posts_groupby', array($this, 'posts_groupby') );
        }
    }

    public function remove_group_by( $query_args ){
        $this->args = $query_args;
        if( isset($this->args['groupby']) ){
            remove_filter( 'posts_groupby', array($this, 'posts_groupby') );
        }
    }

    public function posts_groupby(){
        if( $this->args && isset($this->args['groupby']) ){
            global $wpdb;
            $groupby = "{$wpdb->posts}.".$this->args['groupby'];
            return $groupby;
        }
    }
    
    public function posts_where( $where, $query ) {
        global $wpdb;
        foreach ( $query->meta_query->queries as $index => $meta_query ) {
            if ( isset( $meta_query[ 'compare' ] ) && 'find_in_set' == strtolower( $meta_query[ 'compare' ] ) ) {
                $regex = "#\( ({$wpdb->postmeta}.meta_key = '" . preg_quote( $meta_query[ 'key' ] ) . "')" . " AND ({$wpdb->postmeta}.meta_value) = ('" . preg_quote( $meta_query[ 'value' ] ) . "') \)#";
                $where = preg_replace( $regex, "($1 AND FIND_IN_SET($3,$2))", $where );
            }
        }

        return $where;
    }

    public function normalize_load_more_attributes( $attributes = array() ) {

      $exclusions = apply_filters( 'loop_block_attribute_exclusions', array(
        'title',
        'subtitle',
        'filter',
        'slider',
        'sliderOption',
        'align',
        'action' // added automatically so can be removed to avoid duplicates
      ) );

      if ( ! empty( $attributes ) && is_array( $attributes ) ) {
        foreach( $attributes as $key => $value ) {

          if ( in_array( $key, $exclusions ) ) {
            unset( $attributes[ $key ] );
          }

          if ( isset( $attributes[ $key ] ) && empty( $attributes[ $key ] ) ) {
            unset( $attributes[ $key ] );
          }

        }
      }

      return array_filter( $attributes );
    }

    function http_build_query($query_args){

        foreach ($this->http_query_arr as $value) {
            if( isset($query_args[$value]) && is_array($query_args[$value]) ){
                $query_args[$value] = implode( ',', $query_args[$value] );
            }
            if( isset($query_args['query'][$value]) && is_array($query_args['query'][$value]) ){
                $query_args['query'][$value] = implode( ',', $query_args['query'][$value] );
            }
        }

        return http_build_query($query_args);
    }

    function kses_post( $string = '', $context = '' ) {
        return wp_kses( $string, apply_filters( 'play_kses_allowed_html', array(
            'br'     => array('class' => array()),
            'em'     => array('class' => array()),
            'strong' => array('class' => array()),
            'small'  => array('class' => array()),
            'span'   => array('class' => array()),
            'ul'     => array('class' => array()),
            'li'     => array('class' => array()),
            'ol'     => array('class' => array()),
            'p'      => array('class' => array()),
            'a'      => array(
                'href' => array(),
                'class' => array()
            )
        ), $string, $context ) );
    }
}

Loop::instance();
