<?php
/**
 * Paymob Gateway Table
 */
class WC_Paymob_Tables {

	public static function create_paymob_gateways_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}paymob_gateways (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            gateway_id varchar(100) NOT NULL,
            file_name varchar(100) DEFAULT '' NOT NULL,
            class_name varchar(100) DEFAULT '' NOT NULL,
            checkout_title varchar(100) DEFAULT '' NOT NULL,
            checkout_description LONGTEXT DEFAULT '' NOT NULL,
            integration_id varchar(3000) DEFAULT '' NOT NULL,
            is_manual varchar(56) DEFAULT '' NOT NULL,
            ordering int(10) DEFAULT 0 NOT NULL,
            PRIMARY KEY (id),
            KEY gateway_id (gateway_id),
            UNIQUE (gateway_id)
        ) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}paymob_cards_token (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			token varchar(56) DEFAULT '' NOT NULL,
			masked_pan varchar(19) DEFAULT '' NOT NULL,
			card_subtype varchar(56) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
}
