<?php
class class_name_Gateway extends Paymob_Payment {

	public $id;
	public $method_title;
	public $method_description;
	public $has_fields;
	public function __construct() {
		$this->id                 = 'gateway_id';
		$this->method_title       = $this->title = __( 'Edit Payment Method - checkout_title', 'paymob-woocommerce' ) . ' ( ' . $this->get_option( 'single_integration_id' ) . ' )';
		$this->method_description = $this->description = __( 'checkout_description', 'paymob-woocommerce' );
		parent::__construct();
		// config
		$this->init_settings();
	}
}
