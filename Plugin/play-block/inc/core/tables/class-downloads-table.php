<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Downloads_Table extends \BerlinDB\Database\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'play_block_downloads';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'play_block_downloads_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Play Block Downloads';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = '1.0.0';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array();

	/**
	 * Setup this database table.
	 *
	 * @since 1.0.0
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) UNSIGNED NOT NULL auto_increment,
			object_id bigint(20) UNSIGNED NOT NULL default '0',
			object_type varchar(20) NOT NULL default '',
			user_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			url longtext NOT NULL default '',
			ip varchar(100) NOT NULL default '',
			user_agent varchar(255) NOT NULL default '',
			custom_amount decimal(18,9) NOT NULL default '0',
			custom_data longtext NOT NULL default '',
			date_created datetime NOT NULL,
			PRIMARY KEY (id),
			KEY `object` (object_id,object_type),
			KEY `user` (user_id)";
	}
}
