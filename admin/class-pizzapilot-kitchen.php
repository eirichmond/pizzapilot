<?php
/**
 * The kitchen order interface functionality.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.1.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 */

/**
 * Kitchen order interface for viewing and managing orders grouped by time slot.
 *
 * Provides an admin page for kitchen staff to view today's orders,
 * grouped by delivery time slot, with the ability to mark orders as completed.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class PizzaPilot_Kitchen {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.1.0
	 * @param    string $plugin_name    The name of this plugin.
	 * @param    string $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the kitchen orders admin menu page.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function add_kitchen_menu() {
		add_menu_page(
			__( 'Kitchen Orders', 'pizzapilot' ),
			__( 'Kitchen Orders', 'pizzapilot' ),
			'edit_shop_orders',
			'pizzapilot-kitchen',
			array( $this, 'render_kitchen_page' ),
			'dashicons-food',
			56
		);
	}

	/**
	 * Render the kitchen orders admin page.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function render_kitchen_page() {
		$today = current_time( 'Y-m-d' );

		$grouped_orders = $this->get_orders_grouped_by_slot( $today );

		echo '<div class="wrap pizzapilot-kitchen-wrap">';
		echo '<h1>' . esc_html__( 'Kitchen Orders', 'pizzapilot' ) . '</h1>';

		echo '<p class="pizzapilot-kitchen-actions">';
		echo esc_html__( 'Orders for today, grouped by time slot.', 'pizzapilot' ) . ' ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-kitchen' ) ) . '" class="button">';
		echo esc_html__( 'Refresh', 'pizzapilot' );
		echo '</a>';
		echo '</p>';

		if ( empty( $grouped_orders ) ) {
			echo '<div class="pizzapilot-kitchen-empty">';
			echo '<p>' . esc_html__( 'No orders for today.', 'pizzapilot' ) . '</p>';
			echo '</div>';
		} else {
			foreach ( $grouped_orders as $slot_label => $orders ) {
				echo '<h2>' . esc_html( $slot_label ) . ' (' . count( $orders ) . ')</h2>';
				foreach ( $orders as $order ) {
					echo '<p>' . esc_html( sprintf( __( 'Order #%d', 'pizzapilot' ), $order->get_id() ) ) . '</p>';
				}
			}
		}

		echo '</div>';
	}

	/**
	 * Get orders for a given date, grouped by time slot.
	 *
	 * Queries WooCommerce orders that have PizzaPilot delivery time meta
	 * for the specified date, and groups them by their time slot.
	 *
	 * @since    1.1.0
	 * @param    string $date    Date in Y-m-d format.
	 * @return   array           Associative array of slot_label => array of WC_Order objects.
	 */
	public function get_orders_grouped_by_slot( $date ) {
		$timezone  = wp_timezone();
		$date_obj  = new DateTime( $date, $timezone );
		$day_start = (int) $date_obj->setTime( 0, 0, 0 )->format( 'U' );
		$day_end   = (int) $date_obj->setTime( 23, 59, 59 )->format( 'U' );

		// Query orders with PizzaPilot delivery time within today's range.
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => array( 'wc-processing', 'wc-on-hold', 'wc-completed' ),
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => '_wc_other/pizzapilot/delivery-time',
						'value'   => array( $day_start, $day_end ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_pizzapilot_delivery_time',
						'value'   => array( $day_start, $day_end ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
				'orderby'    => 'meta_value_num',
				'meta_key'   => '_wc_other/pizzapilot/delivery-time',
				'order'      => 'ASC',
			)
		);

		$grouped    = array();
		$slot_times = array();

		foreach ( $orders as $order ) {
			$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );
			if ( empty( $delivery_time ) ) {
				$delivery_time = $order->get_meta( '_pizzapilot_delivery_time', true );
			}

			if ( empty( $delivery_time ) || ! is_numeric( $delivery_time ) ) {
				continue;
			}

			$slot_datetime = new DateTime( '@' . $delivery_time );
			$slot_datetime->setTimezone( $timezone );

			$end_datetime = clone $slot_datetime;
			$end_datetime->modify( '+30 minutes' );

			$slot_label = $slot_datetime->format( 'g:i A' ) . ' - ' . $end_datetime->format( 'g:i A' );

			if ( ! isset( $grouped[ $slot_label ] ) ) {
				$grouped[ $slot_label ]    = array();
				$slot_times[ $slot_label ] = (int) $delivery_time;
			}

			$grouped[ $slot_label ][] = $order;
		}

		// Sort slot groups by time-of-day (hour and minute).
		uksort(
			$grouped,
			function ( $a, $b ) use ( $slot_times, $timezone ) {
				$dt_a = new DateTime( '@' . $slot_times[ $a ] );
				$dt_a->setTimezone( $timezone );
				$dt_b = new DateTime( '@' . $slot_times[ $b ] );
				$dt_b->setTimezone( $timezone );

				$minutes_a = (int) $dt_a->format( 'H' ) * 60 + (int) $dt_a->format( 'i' );
				$minutes_b = (int) $dt_b->format( 'H' ) * 60 + (int) $dt_b->format( 'i' );

				return $minutes_a - $minutes_b;
			}
		);

		return $grouped;
	}
}
