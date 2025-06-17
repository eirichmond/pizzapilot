<?php

/**
 * Fired during plugin activation
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pizzapilot_order_slots';

		$sql = "CREATE TABLE $table_name (
			slot_id bigint(20) NOT NULL AUTO_INCREMENT,
			date date NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			availability int(11) NOT NULL DEFAULT 0,
			order_id bigint(20) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (slot_id),
			KEY date (date),
			KEY order_id (order_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
