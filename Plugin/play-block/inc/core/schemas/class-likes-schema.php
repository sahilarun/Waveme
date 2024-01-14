<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Likes_Schema extends \BerlinDB\Database\Schema {

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

		// object_id
		array(
			'name'       => 'object_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true
		),

		// object_type
		array(
			'name'       => 'object_type',
			'type'       => 'varchar',
			'length'     => '20',
			'default'    => '',
			'sortable'   => true,
			'validate'   => 'play_sanitize_object_type',
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

		// action
		array(
			'name'       => 'action',
			'type'       => 'varchar',
			'length'     => '20',
			'default'    => 'like',
			'sortable'   => true,
			'validate'   => 'play_sanitize_action',
		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true
		),

	);

}
