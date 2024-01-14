<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Notification extends BerlinDB\Database\Row {

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
		$this->id              = (int) $this->id;
		$this->user_id         = (int) $this->user_id;
		$this->notifier_id     = (int) $this->notifier_id;
		$this->item_id   	   = (int) $this->item_id;
		$this->item_related_id = (int) $this->item_related_id;
		$this->action 		   = (string) $this->action;
		$this->description 	   = (string) $this->description;
		$this->status  		   = (int) $this->status;
		$this->date_notified   = (string) $this->date_notified;
	}

}
