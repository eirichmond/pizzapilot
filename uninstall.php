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

// Drop the order slots table. DDL cannot be parameterised, but $wpdb->prefix
// is a trusted core property and the table suffix is a hard-coded literal.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pizzapilot_order_slots`" );

// Clear geocoding transients. Object cache is irrelevant during uninstall and
// transient APIs cannot pattern-match keys, so a direct query is required.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_pizzapilot_geo_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_pizzapilot_geo_' ) . '%'
	)
);

// Clear activation transients.
delete_transient( 'pizzapilot_activation_notice' );
delete_transient( 'pizzapilot_missing_woocommerce' );

// Clean up user meta (kitchen banner dismissal) for every user via the
// metadata API, which avoids a direct DB query.
delete_metadata( 'user', 0, 'pizzapilot_kitchen_pro_dismissed', '', true );
