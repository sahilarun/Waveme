<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Notifications_Table extends \BerlinDB\Database\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'play_block_notifications';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'play_block_notifications_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Play Block Notifications';

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
			user_id bigint(20) UNSIGNED NOT NULL default '0',
			notifier_id bigint(20) UNSIGNED NOT NULL default '0',
			item_id bigint(20) UNSIGNED NOT NULL default '0',
			item_related_id bigint(20) UNSIGNED NOT NULL default '0',
			action varchar(20) NOT NULL default '',
			description longtext default NULL,
			status tinyint(1) NOT NULL,
			date_notified datetime NOT NULL,
			PRIMARY KEY (id),
			KEY `object` (user_id,notifier_id),
			KEY `user` (user_id)";
	}
}
