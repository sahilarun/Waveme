<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Likes_Table extends \BerlinDB\Database\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'play_block_likes';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'play_block_likes_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Play Block Likes';

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
			action varchar(20) NOT NULL default 'like',
			date_created datetime NOT NULL,
			PRIMARY KEY (id),
			KEY `object` (object_id,object_type),
			KEY `user` (user_id)";
	}
}
