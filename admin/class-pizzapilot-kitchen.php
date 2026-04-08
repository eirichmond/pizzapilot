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
	 * Register the kitchen orders submenu page under PizzaPilot.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function add_kitchen_menu() {
		add_submenu_page(
			'pizzapilot-settings',
			__( 'Kitchen Orders', 'pizzapilot' ),
			__( 'Kitchen Orders', 'pizzapilot' ),
			'edit_shop_orders',
			'pizzapilot-kitchen',
			array( $this, 'render_kitchen_page' )
		);
	}

	/**
	 * Enqueue styles for the kitchen page.
	 *
	 * @since    1.1.0
	 * @param    string $hook_suffix    The current admin page hook suffix.
	 * @return   void
	 */
	public function enqueue_kitchen_styles( $hook_suffix ) {
		if ( 'pizzapilot_page_pizzapilot-kitchen' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-kitchen',
			plugin_dir_url( __FILE__ ) . 'css/pizzapilot-kitchen.css',
			array(),
			$this->version,
			'all'
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

		$this->render_pro_upsell_banner();

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
				$this->render_slot_group( $slot_label, $orders );
			}
		}

		echo '</div>';
	}

	/**
	 * Render a group of orders under a time slot heading.
	 *
	 * @since    1.1.0
	 * @param    string $slot_label    The time slot label (e.g., "2:00 PM - 2:30 PM").
	 * @param    array  $orders        Array of WC_Order objects in this slot.
	 * @return   void
	 */
	private function render_slot_group( $slot_label, $orders ) {
		$order_count = count( $orders );

		echo '<div class="pizzapilot-slot-group">';
		echo '<h2 class="pizzapilot-slot-heading">';
		echo esc_html( $slot_label );
		echo ' <span class="pizzapilot-slot-count">';
		echo '(' . esc_html(
			sprintf(
				/* translators: %d: number of orders */
				_n( '%d order', '%d orders', $order_count, 'pizzapilot' ),
				$order_count
			)
		) . ')';
		echo '</span>';
		echo '</h2>';

		echo '<div class="pizzapilot-order-cards">';
		foreach ( $orders as $order ) {
			$this->render_order_card( $order );
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render a single order card.
	 *
	 * @since    1.1.0
	 * @param    WC_Order $order    The WooCommerce order object.
	 * @return   void
	 */
	private function render_order_card( $order ) {
		$order_id      = $order->get_id();
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$completed     = 'yes' === $order->get_meta( '_pizzapilot_kitchen_completed', true );

		$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );
		if ( empty( $delivery_type ) ) {
			$delivery_type = $order->get_meta( '_pizzapilot_delivery_type', true );
		}

		$card_class = 'pizzapilot-order-card';
		if ( $completed ) {
			$card_class .= ' pizzapilot-order-completed';
		}

		echo '<div class="' . esc_attr( $card_class ) . '">';

		echo '<div class="pizzapilot-order-card-header">';
		echo '<h3>';
		echo '<a href="' . esc_url( $order->get_edit_order_url() ) . '">';
		/* translators: %d: order number */
		echo esc_html( sprintf( __( 'Order #%d', 'pizzapilot' ), $order_id ) );
		echo '</a>';
		echo ' &mdash; ' . esc_html( $customer_name );
		echo '</h3>';
		if ( ! empty( $delivery_type ) ) {
			echo '<span class="pizzapilot-delivery-type pizzapilot-delivery-type--' . esc_attr( $delivery_type ) . '">';
			echo esc_html( ucfirst( $delivery_type ) );
			echo '</span>';
		}
		echo '</div>';

		echo '<ul class="pizzapilot-order-items">';
		foreach ( $order->get_items() as $item ) {
			echo '<li>';
			echo esc_html( $item->get_quantity() . 'x ' . $item->get_name() );
			echo '</li>';
		}
		echo '</ul>';

		echo '<div class="pizzapilot-order-card-footer">';
		$this->render_completion_toggle( $order_id, $completed );
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render the completion toggle form for an order.
	 *
	 * @since    1.1.0
	 * @param    int  $order_id     The order ID.
	 * @param    bool $completed    Whether the order is marked as completed.
	 * @return   void
	 */
	private function render_completion_toggle( $order_id, $completed ) {
		$action_url = admin_url( 'admin-post.php' );

		echo '<form method="post" action="' . esc_url( $action_url ) . '">';
		echo '<input type="hidden" name="action" value="pizzapilot_mark_kitchen_completed">';
		echo '<input type="hidden" name="order_id" value="' . esc_attr( $order_id ) . '">';
		echo '<input type="hidden" name="completed" value="' . esc_attr( $completed ? 'no' : 'yes' ) . '">';
		wp_nonce_field( 'pizzapilot_kitchen_toggle_' . $order_id, 'pizzapilot_kitchen_nonce' );

		if ( $completed ) {
			echo '<button type="submit" class="button pizzapilot-btn-incomplete">';
			echo esc_html__( 'Mark Incomplete', 'pizzapilot' );
			echo '</button>';
		} else {
			echo '<button type="submit" class="button button-primary pizzapilot-btn-complete">';
			echo esc_html__( 'Mark Completed', 'pizzapilot' );
			echo '</button>';
		}

		echo '</form>';
	}

	/**
	 * Handle the mark completed/incomplete form submission.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function handle_mark_completed() {
		if ( ! isset( $_POST['pizzapilot_kitchen_nonce'], $_POST['order_id'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'pizzapilot' ) );
		}

		$order_id = absint( $_POST['order_id'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pizzapilot_kitchen_nonce'] ) ), 'pizzapilot_kitchen_toggle_' . $order_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pizzapilot' ) );
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'pizzapilot' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'pizzapilot' ) );
		}

		$new_status = isset( $_POST['completed'] ) ? sanitize_text_field( wp_unslash( $_POST['completed'] ) ) : 'no';
		$new_status = 'yes' === $new_status ? 'yes' : 'no';

		$order->update_meta_data( '_pizzapilot_kitchen_completed', $new_status );
		$order->save();

		/**
		 * Fires when an order's kitchen completion status is toggled.
		 *
		 * @since 1.1.0
		 * @param int    $order_id   The order ID.
		 * @param string $new_status The new completion status ('yes' or 'no').
		 */
		do_action( 'pizzapilot_kitchen_order_toggled', $order_id, $new_status );

		wp_safe_redirect( admin_url( 'admin.php?page=pizzapilot-kitchen' ) );
		exit;
	}

	/**
	 * Render the Pro upsell banner on the kitchen page.
	 *
	 * Shows a dismissible notice encouraging upgrade to Pro for advanced
	 * kitchen features. Tracks dismissal in user meta so it stays hidden.
	 * Only shown when Pro is not active.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	private function render_pro_upsell_banner() {
		if ( Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' ) ) {
			return;
		}

		$user_id   = get_current_user_id();
		$dismissed = get_user_meta( $user_id, 'pizzapilot_kitchen_pro_dismissed', true );

		if ( $dismissed ) {
			return;
		}

		$upgrade_url = admin_url( 'admin.php?page=pizzapilot-upgrade' );
		$dismiss_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'pizzapilot_dismiss_kitchen_pro',
					'page'   => 'pizzapilot-kitchen',
				),
				admin_url( 'admin.php' )
			),
			'pizzapilot_dismiss_kitchen_pro'
		);

		echo '<div class="notice notice-info pizzapilot-pro-banner">';
		echo '<p>';
		echo '<strong>' . esc_html__( 'PizzaPilot Pro', 'pizzapilot' ) . '</strong> &mdash; ';
		echo esc_html__( 'Upgrade for live-updating orders, drag-and-drop reordering, and kitchen ticket printing.', 'pizzapilot' );
		echo ' <a href="' . esc_url( $upgrade_url ) . '">' . esc_html__( 'Learn more', 'pizzapilot' ) . '</a>';
		echo '</p>';
		echo '<a href="' . esc_url( $dismiss_url ) . '" class="notice-dismiss-link" style="text-decoration:none;float:right;margin-top:-28px;">';
		echo '<span class="dashicons dashicons-dismiss"></span>';
		echo '</a>';
		echo '</div>';
	}

	/**
	 * Handle dismissal of the kitchen Pro upsell banner.
	 *
	 * Saves a user meta flag so the banner stays hidden for that user.
	 * Verifies nonce and redirects back to the kitchen page.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function handle_dismiss_kitchen_pro() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'pizzapilot_dismiss_kitchen_pro' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pizzapilot' ) );
		}

		update_user_meta( get_current_user_id(), 'pizzapilot_kitchen_pro_dismissed', '1' );

		wp_safe_redirect( admin_url( 'admin.php?page=pizzapilot-kitchen' ) );
		exit;
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
