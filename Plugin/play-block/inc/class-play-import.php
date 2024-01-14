<?php

defined( 'ABSPATH' ) || exit;

class Play_Import {

    protected static $_instance = null;
    private $max_import = 10000;
    private $count = 0;

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
        $this->setting_page = apply_filters('play_setting_page_url', 'edit.php?post_type=station');
        add_action( 'admin_menu', array( $this, 'add_options_link' ) );

        do_action( 'play_block_import_init', $this );
    }

    public function add_options_link() {
        add_submenu_page( $this->setting_page, esc_html__( 'Import', 'play-block' ), esc_html__( 'Import', 'play-block' ), 'manage_options', 'play-inport', [$this, 'play_import_page']);
    }

    public function play_import_page(){
        $steps = array(
            array(
                'header' => __( 'Upload file', 'play-block' ),
                'title' => __( 'Import posts from a file', 'play-block' ),
                'subtitle' => __( 'This tool allows you to import (or merge) data to your site from a file.', 'play-block' ),
            ),
            array(
                'header' => __( 'Column mapping', 'play-block' ),
                'title' => __( 'Map fields', 'play-block' ),
                'subtitle' => __( 'Select fields from your file to map against posts fields, or to ignore during import.', 'play-block' ),
            ),
            array(
                'header' => __( 'Import', 'play-block' ),
                'title' => __( 'Importing', 'play-block' ),
                'subtitle' => __( 'Your posts are now being imported...', 'play-block' ),
            )
        );
        $active = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 0;
        $step  = $active + 1;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Import' ); ?></h1>
            <div class="import-wrap">
                <ul class="progress">
                    <?php
                        foreach($steps as $key => $val){
                            echo sprintf('<li class="%s">%s</li>', esc_attr($active > $key ? 'done' : ($active == $key ? 'active' : '')), $val['header'] );
                        }
                    ?>
                </ul>
                <div class="import-dialog">
                    <form enctype="multipart/form-data" method="post">
                        <header class="import-header">
                            <h2><?php echo $steps[$active]['title']; ?></h2>
                            <p class="description"><?php echo $steps[$active]['subtitle']; ?></p>
                        </header>
                        <div class="import-content">
                            <table class="form-table">
                                <tbody>
                                    <?php
                                    $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'station';

                                    if(isset($_REQUEST['file'])){
                                        $url = $_REQUEST['file'];
                                        $response = wp_remote_get($url);
                                        $file_type = wp_remote_retrieve_header($response,'content-type');
                                        $data = wp_remote_retrieve_body($response);
                                    }

                                    if($active == 0){ ?>
                                    <tr>
                                        <th><?php esc_html_e('Choose a file','play-block'); ?></th>
                                        <td>
                                            <input type="text" name="file"><button type="button" class="button upload-btn">Upload</button>
                                            <p class="description"><?php esc_html_e('Maximum size: ','play-block'); echo size_format(wp_max_upload_size()); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Import to post type','play-block'); ?></th>
                                        <td>
                                            <select name="type">
                                                <?php
                                                foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $id => $type ) {
                                                    if ( ! empty( $type->labels->name ) ) {
                                                        echo sprintf('<option value="%s">%s</option>', esc_attr($id), esc_html($type->labels->name));
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php }

                                    if($active == 1){
                                        $content  = '<input type="hidden" name="file" value="'.esc_attr($url).'">';
                                        $content .= '<input type="hidden" name="type" value="'.esc_attr($type).'">';
                                        // Map start from field
                                        if( in_array($file_type, array('application/json')) && !isset($_REQUEST['mapping']) ){
                                            $fields = $this->flatObject(json_decode($data));
                                            $fields = array_keys($fields);
                                            $options = '';
                                            foreach($fields as $field){
                                                $options .= sprintf('<option value="%s">%s</option>', esc_attr($field), esc_html($field));
                                            }
                                            $step = 1;
                                            $content .= sprintf('<tr><th>%s</th><td><select name="map_start">%s</select></td></tr>', __('Map data from','play-block'), $options);
                                            $content .= '<input type="hidden" name="mapping" value="1">';
                                        }else{
                                            $columns = array();
                                            // text/csv application/json application/xml
                                            if($file_type == 'text/csv'){
                                                // parse csv
                                                $file = fopen($url, 'r');
                                                if ( false !== $file ) {
                                                    $columns = fgetcsv($file);
                                                    fclose($file);
                                                }
                                            }

                                            if($file_type == 'application/json'){
                                                $data = json_decode($data);
                                                $map_start = $_REQUEST['map_start'];
                                                if(!empty($map_start)){
                                                    $keys = explode('.', $map_start);
                                                    foreach($keys as $key){
                                                        if(is_object($data)){
                                                            $data = $data->$key;
                                                        }elseif(is_array($data)){
                                                            $data = $data[$key];
                                                        }
                                                    }
                                                }
                                                if(is_array($data)){
                                                    $data = $data[0];
                                                }
                                                $columns = array_keys($this->flatObject($data));
                                                $content .= '<input type="hidden" name="map_start" value="'.esc_attr($map_start).'">';
                                            }

                                            foreach($columns as $key => $column){
                                                $input = sprintf('<input type="hidden" name="map_from[%s]" value="%s">', $key, ($file_type == 'application/json' ? $column : $key));
                                                $content .= sprintf('<tr><th>%s</th><td>%s <select name="map_to[%s]">%s</select></td></tr>', $column, $input, $key, $this->getMapFields($type));
                                            }
                                        }
                                        echo $content;
                                    }

                                    if($active == 2){
                                        $map_start = isset($_REQUEST['map_start']) ? $_REQUEST['map_start'] : '';
                                        $map_from = $_REQUEST['map_from'];
                                        $map_to = $_REQUEST['map_to'];
                                        $parse_data = array('post_type'=>$type, 'post_status'=>'publish');
                                        $mapping = array_filter(array_combine($map_from, $map_to));
                                        
                                        if( in_array($file_type, array('application/json')) ){
                                            $data = json_decode($data);
                                            $keys = explode('.', $map_start);
                                            if(!empty($map_start)){
                                                foreach($keys as $key){
                                                    if(is_object($data)){
                                                        $data = $data->$key;
                                                    }elseif(is_array($data)){
                                                        $data = $data[$key];
                                                    }
                                                }
                                            }
                                            foreach($data as $item){
                                                $item = $this->flatObject($item);
                                                foreach ($item as $key => $value) {
                                                    if(isset($mapping[$key]) && !empty($mapping[$key])){
                                                        $parse_data[$mapping[$key]] = $value;
                                                    }
                                                }
                                                $this->import($parse_data);
                                            }
                                        }

                                        if($file_type == 'text/csv'){
                                            $file = fopen($url, 'r');
                                            if ( false !== $file ) {
                                                $header = fgetcsv($file);
                                                $row = fgetcsv($file);
                                                while ( false !== $row ) {
                                                    foreach ($row as $key => $value) {
                                                        if(isset($mapping[$key]) && !empty($mapping[$key])){
                                                            $parse_data[$mapping[$key]] = $value;
                                                        }
                                                    }
                                                    $this->import($parse_data);
                                                    $row = fgetcsv($file);
                                                }
                                                fclose($file);
                                            }
                                        }
                                        $content = sprintf(__('%s posts imported'), esc_html($this->count));
                                        echo $content;
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="import-footer">
                            <input type="hidden" name="step" value="<?php esc_attr_e($step); ?>">
                            <?php if( $step < count($steps)){ ?>
                            <input type="submit" value="<?php esc_attr_e('Continue','play-block'); ?>" class="button button-primary">
                            <?php }else{ ?>
                            <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type='.$type)); ?>"><?php esc_attr_e('View','play-block'); ?></a>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function import($post){
        @ini_set('max_execution_time', '300');
        
        $this->count++;
        if($this->count > $this->max_import ){
            return;
        }

        $taxonomies = get_object_taxonomies($post['post_type'], 'names');
        foreach($taxonomies as $taxonomy){
            if(isset($post[$taxonomy])){
                $id = str_replace('&', ',', $post[$taxonomy]);
                if(is_taxonomy_hierarchical($taxonomy)){
                    $id = wp_create_term($post[$taxonomy], $taxonomy);
                }
                $post['tax_input'][$taxonomy] = $id;
                unset($post[$taxonomy]);
            }
        }

        $meta_keys  = array_keys( get_registered_meta_keys('post', '') );
        foreach($meta_keys as $meta_key){
            if(isset($post[$meta_key])){
                $post['meta_input'][$meta_key] = $post[$meta_key];
                unset($post[$meta_key]);
            }
        }
        $post['meta_input']['type'] = 'single';

        $post_id = wp_insert_post( apply_filters( 'play_import_post', $post ) );

        if( isset($post['thumbnail']) && !empty($post['thumbnail']) ){
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $image = media_sideload_image($post['thumbnail'], $post_id, $post['post_title'], 'id');
            set_post_thumbnail( $post_id, $image );
        }
    }

    public function getDefaultFields(){
        $fields = array(
            'ID', 
            'post_title', 
            'post_excerpt',
            'post_content',
            'post_author',
            'post_date',
            'post_status',
            'comment_status',
            'comment_count'
        );
        return $fields;
    }

    public function getMapFields($type){
        $fields = $this->getDefaultFields();
        $taxonomies = get_object_taxonomies($type, 'names');
        $meta_keys  = array_keys( get_registered_meta_keys('post', '') );

        $fields = apply_filters('play_import_fields', array_merge(array(''), $fields, array('Media','thumbnail'), array('Taxonomies'), $taxonomies, array('Meta_keys'), $meta_keys));

        $options = '';
        foreach($fields as $field){
            $options .= sprintf('<option %s>%s</option>', in_array(strtolower($field), array('media','taxonomies','meta_keys')) ? 'disabled' : 'value="'.esc_attr($field).'"', esc_html($field));
        }
        return $options;
    }

    public function flatObject($array, $prefix = '') {
        $flat = array();
        $sep = ".";
        
        if (is_array($array) && sizeof($array) > 0) {
            $flat[$prefix] = '';
            $array = array($array[0]);
        }

        if (!is_array($array)) $array = (array)$array;
        
        foreach($array as $key => $value)
        {
            $_key = ltrim($prefix.$sep.$key, ".");
            
            if (is_array($value) || is_object($value))
            {
                $flat = array_merge($flat, $this->flatObject($value, $_key));
            }
            else
            {
                $flat[$_key] = $value;
            }
        }
        
        return $flat;
    }
}

Play_Import::instance();
