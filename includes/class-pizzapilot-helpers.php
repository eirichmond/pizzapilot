<?php

/**
 * Helper functions for the PizzaPilot plugin
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 * @package    Pizzapilot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Helper functions for the PizzaPilot plugin
 *
 * @since      1.0.0
 * @package    Pizzapilot
 */
class Pizzapilot_Helpers {

	/**
	 * Check if a pro version class exists
	 *
	 * @since    1.0.0
	 * @param    string    $class_name    The name of the class to check
	 * @return   bool                     True if the class exists, false otherwise
	 */
	public static function pizzapilot_is_pro_active( $class_name ) {
		return class_exists( $class_name );
	}

	/**
	 * Generate a message indicating that a feature is only available in the Pro version.
	 *
	 * @since    1.0.0
	 * @param    string    $message    The message to display.
	 * @return   string               The formatted upgrade message.
	 */
	public static function pizzapilot_pro_upgrade_message() {
		return sprintf(
			/* translators: %s: opening and closing anchor tags for the upgrade link */
			__( 'Upgrade to %1$sPizzaPilot Pro%2$s for more advanced features.', 'pizzapilot' ),
			'<a href="https://pizzapilotpro.com/downloads/pizzapilotpro/">',
			'</a>'
		);
	}

	/**
	 * Get an order's delivery slot start time.
	 *
	 * Both checkouts save it to `_pizzapilot_delivery_time`, which is the one
	 * PizzaPilot keeps in step with the order. The block checkout's own copy
	 * (`_wc_other/pizzapilot/delivery-time`) is only a fallback, for orders
	 * placed before that copy was made.
	 *
	 * @since    1.2.5
	 * @param    WC_Order $order The order.
	 * @return   int             Unix timestamp, or 0 when the order has no slot.
	 */
	public static function get_order_delivery_time( $order ) {
		$delivery_time = $order->get_meta( '_pizzapilot_delivery_time', true );

		if ( empty( $delivery_time ) ) {
			$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );
		}

		return is_numeric( $delivery_time ) ? (int) $delivery_time : 0;
	}

	/**
	 * Get an order's delivery type ("delivery" or "collect").
	 *
	 * @since    1.2.5
	 * @param    WC_Order $order The order.
	 * @return   string          Empty when the order has none.
	 */
	public static function get_order_delivery_type( $order ) {
		$delivery_type = $order->get_meta( '_pizzapilot_delivery_type', true );

		if ( empty( $delivery_type ) ) {
			$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );
		}

		return (string) $delivery_type;
	}
}
