<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Downloads_Schema extends \BerlinDB\Database\Schema {

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
			'validate'   => 'sanitize_text_field',
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

		// url
		array(
			'name'       => 'url',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
			'in'         => false,
			'not_in'     => false,
			'validate'   => 'esc_url_raw',
		),

		// ip
		array(
			'name'       => 'ip',
			'type'       => 'varchar',
			'length'     => '100',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true,
			'validate'   => 'sanitize_text_field',
		),

		// user_agent
		array(
			'name'       => 'user_agent',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true,
			'validate'   => 'sanitize_text_field',
		),

		// custom_amount
		array(
			'name'       => 'custom_amount',
			'type'       => 'decimal',
			'length'     => '18,9',
			'default'    => '0',
			'sortable'   => true,
		),

		// custom_data
		array(
			'name'       => 'custom_data',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
			'in'         => false,
			'not_in'     => false,
			'validate'   => 'play_sanitize_custom_data',
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
