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
	 * Run activation tasks for PizzaPilot.
	 *
	 * Checks that WooCommerce is active before proceeding. If WooCommerce
	 * is not found, sets a transient to display an admin notice and bails
	 * without creating the database table. When WooCommerce is present,
	 * creates the order slots table and sets default plugin options.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function activate() {

		// Check WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			set_transient( 'pizzapilot_missing_woocommerce', true, 30 );
			return;
		}

		self::create_tables();
		self::set_default_options();

		// Set activation flag for welcome notice.
		set_transient( 'pizzapilot_activation_notice', true, 30 );
	}

	/**
	 * Create the order slots database table.
	 *
	 * Uses dbDelta to create the wp_pizzapilot_order_slots table
	 * for storing delivery time slot data.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function create_tables() {
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

	/**
	 * Set default plugin options on first activation.
	 *
	 * Only sets defaults if the options do not already exist,
	 * preserving any existing configuration on reactivation.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	private static function set_default_options() {
		if ( false === get_option( 'pizzapilot_general_settings' ) ) {
			update_option(
				'pizzapilot_general_settings',
				array( 'enabled' => true )
			);
		}

		if ( false === get_option( 'pizzapilot_delivery_settings' ) ) {
			update_option(
				'pizzapilot_delivery_settings',
				array(
					'delivery_postcode'   => '',
					'delivery_radius'     => 5,
					'radius_unit'         => 'km',
					'delivery_start_time' => '10:00',
					'delivery_end_time'   => '17:30',
				)
			);
		}

		if ( false === get_option( 'pizzapilot_advanced_settings' ) ) {
			update_option(
				'pizzapilot_advanced_settings',
				array( 'same_day_only' => true )
			);
		}
	}
}
