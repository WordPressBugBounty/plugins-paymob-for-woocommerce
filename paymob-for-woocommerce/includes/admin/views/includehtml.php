<?php

class MainHtmlInclude {

	public static function get_gateway_list_views() {

		$gatewaysViews = include_once PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_gateway_list.php';
		return $gatewaysViews;
	}

	public static function get_save_card_confirmation_model() {
		$confirmation_model = include_once PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_save_card_confirmation_model.php';
		return $confirmation_model;
	}
}
