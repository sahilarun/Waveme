<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Notifications_Schema extends \BerlinDB\Database\Schema {

	public $columns = array(

		// id
		array(
			'name'       => 'id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'extra'      => 'auto_increment',
			'primary'    => true,
			'sortable'   => true
		),

		// user_id
		array(
			'name'       => 'user_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true
		),

		// notifier_id
		array(
			'name'       => 'notifier_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true
		),

		// item_id
		array(
			'name'       => 'item_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true
		),

		// item_related_id
		array(
			'name'       => 'item_related_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true
		),

		// action
		array(
			'name'       => 'action',
			'type'       => 'varchar',
			'length'     => '20',
			'default'    => '',
			'sortable'   => true
		),

		// description
		array(
			'name'       => 'description',
			'type'       => 'longtext',
			'default'    => NULL
		),

		// status
		array(
			'name'       => 'status',
			'type'       => 'tinyint(1)',
			'default'    => 0
		),

		// date_notified
		array(
			'name'       => 'date_notified',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true
		),

	);

}
