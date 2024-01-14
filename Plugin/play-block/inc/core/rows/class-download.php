<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Download extends BerlinDB\Database\Row {

	/**
	 * Book constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id             = (int) $this->id;
		$this->object_id      = (string) $this->object_id;
		$this->object_type    = (string) $this->object_type;
		$this->user_id        = (int) $this->user_id;
		$this->ip    		  = (string) $this->ip;
		$this->user_agent     = (string) $this->user_agent;
		$this->custom_amount  = (float) $this->custom_amount;
		$this->custom_data    = $this->custom_data;
		$this->date_created   = (string) $this->date_created;
	}

}
