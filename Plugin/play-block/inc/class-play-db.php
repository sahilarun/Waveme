<?php

defined( 'ABSPATH' ) || exit;

class Play_DB {

    protected static $_instance = null;

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
      $this->include_engine();
      $this->include_components();
      $this->include_helpers();
      $this->register_tables();

      do_action( 'play_block_db_init', $this );
    }

    /**
     * Require BerlinDB Files.
     *
     * These MUST be required in sequence.
     *
     * For now, BerlinDB files are manually required.
     */
    public function include_engine() {
      if(class_exists('\BerlinDB\Database\Base')){
        return;
      }
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/base.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/column.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/queries/meta.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/queries/compare.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/queries/date.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/query.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/row.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/schema.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/engine/table.php' );

    }

    /**
     * Require Components.
     *
     * Load all the needed tables, schemas, rows and queries.
     */
    public function include_components() {

      // Table Classes. This specifies the table name, manages table version and upgrade routines, and builds the table.
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/tables/class-likes-table.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/tables/class-follows-table.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/tables/class-downloads-table.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/tables/class-stats-table.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/tables/class-notifications-table.php' );

      // Table Schemas. This specifies how the table can be queried, and what columns are in the table.
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/schemas/class-likes-schema.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/schemas/class-follows-schema.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/schemas/class-downloads-schema.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/schemas/class-stats-schema.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/schemas/class-notifications-schema.php' );

      // Table Row Instances. When queried, each record that is retrieved is returned as an instance of this class.
      // BerlinDB refers to this as "shaping"
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/rows/class-like.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/rows/class-follow.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/rows/class-download.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/rows/class-stat.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/rows/class-notification.php' );

      // Query Classes. This is the class you call to interact with the database class. Sort-of like an advanced WP_Query.
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/queries/class-like-query.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/queries/class-follow-query.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/queries/class-download-query.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/queries/class-stat-query.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/queries/class-notification-query.php' );

    }

    /**
     * Require Helper Files.
     *
     * A collection of query wrappers and helper functions.
     */
    public function include_helpers() {

      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/likes.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/follows.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/downloads.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/stats.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/notifications.php' );
      require_once( PLAYBLOCK_PLUGIN_PATH . 'inc/core/helpers/sanitization.php' );

    }

    /**
     * Register table classes.
     */
    public function register_tables() {

      // Database tables
      new Play_Block_Downloads_Table();
      new Play_Block_Follows_Table();
      new Play_Block_Likes_Table();
      new Play_Block_Stats_Table();
      new Play_Block_Notifications_Table();
      
    }



}

Play_DB::instance();
