<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data including options, the custom database table,
 * and cached transients. This only runs when the plugin is deleted
 * via the WordPress admin, not on deactivation.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'pizzapilot_general_settings' );
delete_option( 'pizzapilot_delivery_settings' );
delete_option( 'pizzapilot_advanced_settings' );

// Drop the order slots table.
global $wpdb;
$table_name = $wpdb->prefix . 'pizzapilot_order_slots';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

// Clear geocoding transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_pizzapilot_geo_%'
	OR option_name LIKE '_transient_timeout_pizzapilot_geo_%'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Clear activation transients.
delete_transient( 'pizzapilot_activation_notice' );
delete_transient( 'pizzapilot_missing_woocommerce' );

// Clean up user meta (kitchen banner dismissal).
$wpdb->query(
	"DELETE FROM {$wpdb->usermeta}
	WHERE meta_key = 'pizzapilot_kitchen_pro_dismissed'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
