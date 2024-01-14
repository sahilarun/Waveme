<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Stat extends BerlinDB\Database\Row {

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
		$this->date_created   = (string) $this->date_created;
	}

}
