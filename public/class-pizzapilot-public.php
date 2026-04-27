<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/public
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register stylesheets for the public-facing side of the site.
	 *
	 * Currently a no-op — the legacy public stylesheet was removed once the
	 * checkout postcode messaging adopted standard WooCommerce notice classes
	 * and the delivery-checker block began shipping its own scoped styles.
	 * Hook is retained so any future public CSS can be added here without
	 * editing the loader wiring.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_styles() {
		// Intentionally empty.
	}

	/**
	 * Register and conditionally enqueue the public-facing JavaScript.
	 *
	 * Registration of the shared postcode-check API helper happens on every
	 * front-end request so blocks (which auto-enqueue their viewScript only
	 * on pages where the block is present) can resolve it as a dependency
	 * regardless of which page renders the block.
	 *
	 * The legacy checkout-side script (pizzapilot-public.js) only enqueues
	 * on cart and checkout pages — including the WooCommerce block-checkout
	 * variants — so it doesn't load on every page of the site.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_scripts() {

		// Always register the shared API helper. Localised data is attached
		// here so any consumer that depends on this handle (the checkout
		// script below, or the delivery-checker block's viewScript) gets
		// ajaxUrl + nonce without re-localising.
		wp_register_script(
			'pizzapilot-postcode-api',
			plugin_dir_url( __FILE__ ) . 'js/pizzapilot-postcode-api.js',
			array(),
			$this->version,
			true
		);

		wp_localize_script(
			'pizzapilot-postcode-api',
			'pizzapilotPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pizzapilot_postcode_check' ),
			)
		);

		if ( ! self::is_checkout_context() ) {
			return;
		}

		// Checkout-side script depends on the shared API helper.
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/pizzapilot-public.js',
			array( 'jquery', 'pizzapilot-postcode-api' ),
			$this->version,
			false
		);
	}

	/**
	 * Determine whether the current front-end request is a checkout context.
	 *
	 * Returns true on cart, checkout, or any singular page that contains a
	 * WooCommerce cart or checkout block. Used to gate the legacy public
	 * script so it only loads where it's actually needed.
	 *
	 * @since    1.2.0
	 * @return   bool
	 */
	public static function is_checkout_context() {
		if ( is_admin() ) {
			return false;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( 'has_block' ) && function_exists( 'is_singular' ) && is_singular() ) {
			if ( has_block( 'woocommerce/cart' ) || has_block( 'woocommerce/checkout' ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Register checkout fields for delivery type and time slot selection.
	 *
	 * Uses WooCommerce's additional checkout fields API to add delivery options
	 * and time slot selection to the checkout page.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function pizzapilot_register_checkout_fields() {
		$settings = new Pizzapilot_Settings( PIZZAPILOT_NAME, PIZZAPILOT_VERSION );
		$slots    = $settings->get_formatted_delivery_slots();

		// Convert to options format
		$options = array();
		foreach ( $slots as $timestamp => $label ) {
			$options[] = array(
				'value' => $timestamp,
				'label' => $label,
			);
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'          => 'pizzapilot/delivery-type',
				'label'       => __( 'Delivery Options', 'pizzapilot' ),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				'placeholder' => __( 'Select a delivery type', 'pizzapilot' ),
				'options'     => array(
					array(
						'value' => 'delivery',
						'label' => __( 'Delivery', 'pizzapilot' ),
					),
					array(
						'value' => 'collect',
						'label' => __( 'Collect', 'pizzapilot' ),
					),
				),
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'          => 'pizzapilot/delivery-time',
				'label'       => __( 'Delivery Time', 'pizzapilot' ),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				'placeholder' => __( 'Select a time', 'pizzapilot' ),
				'options'     => $options,
			)
		);
	}

	/**
	 * Validate checkout fields before order is processed.
	 *
	 * Ensures that both delivery type and time slot are selected,
	 * and validates that the selected time slot is still available.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function pizzapilot_validate_checkout_fields() {
		// Nonce verified by WooCommerce via woocommerce_checkout_process.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$delivery_type = isset( $_POST['pizzapilot/delivery-type'] ) ? sanitize_text_field( wp_unslash( $_POST['pizzapilot/delivery-type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$delivery_time = isset( $_POST['pizzapilot/delivery-time'] ) ? absint( $_POST['pizzapilot/delivery-time'] ) : 0;

		// Validate delivery type
		if ( empty( $delivery_type ) ) {
			wc_add_notice( __( 'Please select a delivery type.', 'pizzapilot' ), 'error' );
		}

		// Validate delivery time
		if ( empty( $delivery_time ) ) {
			wc_add_notice( __( 'Please select a delivery time slot.', 'pizzapilot' ), 'error' );
			return;
		}

		// Verify the selected time slot is still valid (hasn't elapsed)
		$settings        = new Pizzapilot_Settings( PIZZAPILOT_NAME, PIZZAPILOT_VERSION );
		$available_slots = $settings->get_formatted_delivery_slots();

		if ( ! isset( $available_slots[ $delivery_time ] ) ) {
			wc_add_notice( __( 'The selected time slot is no longer available. Please select a different time.', 'pizzapilot' ), 'error' );
		}
	}

	/**
	 * Save delivery data to order meta when order is created.
	 *
	 * Stores the delivery type and selected time slot as order metadata,
	 * then updates the slot availability in the database.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    The order ID.
	 * @return   void
	 */
	public function pizzapilot_save_checkout_fields( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Nonce verified by WooCommerce via woocommerce_checkout_update_order_meta.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$delivery_type = isset( $_POST['pizzapilot/delivery-type'] ) ? sanitize_text_field( wp_unslash( $_POST['pizzapilot/delivery-type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$delivery_time = isset( $_POST['pizzapilot/delivery-time'] ) ? absint( $_POST['pizzapilot/delivery-time'] ) : 0;

		// Save to order meta (HPOS-safe)
		if ( ! empty( $delivery_type ) ) {
			$order->update_meta_data( '_pizzapilot_delivery_type', $delivery_type );
		}

		if ( ! empty( $delivery_time ) ) {
			$order->update_meta_data( '_pizzapilot_delivery_time', $delivery_time );

			// Get the formatted slot label for display
			$settings        = new Pizzapilot_Settings( PIZZAPILOT_NAME, PIZZAPILOT_VERSION );
			$available_slots = $settings->get_formatted_delivery_slots();

			if ( isset( $available_slots[ $delivery_time ] ) ) {
				$order->update_meta_data( '_pizzapilot_delivery_time_formatted', $available_slots[ $delivery_time ] );
			}
		}

		$order->save();

		if ( ! empty( $delivery_time ) ) {
			// Update slot availability
			$this->update_slot_availability( $delivery_time, $order_id );
		}
	}

	/**
	 * Save delivery data to order meta when using block-based checkout.
	 *
	 * Stores the delivery type and selected time slot as order metadata,
	 * then updates the slot availability in the database.
	 * This method is for the new WooCommerce Blocks checkout with additional fields API.
	 *
	 * @since    1.0.0
	 * @param    WC_Order  $order    The order object.
	 * @param    object    $request  The request object from Store API.
	 * @return   void
	 */
	public function pizzapilot_save_checkout_fields_block( $order, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $request required by WC action signature.
		// Get order ID
		$order_id = $order->get_id();

		// Get the field values from order meta (already saved by WooCommerce)
		$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );
		$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );

		// Also save to legacy meta keys for backward compatibility (HPOS-safe)
		if ( ! empty( $delivery_type ) ) {
			$order->update_meta_data( '_pizzapilot_delivery_type', $delivery_type );
		}

		if ( ! empty( $delivery_time ) ) {
			$order->update_meta_data( '_pizzapilot_delivery_time', $delivery_time );

			// Get the formatted slot label for display
			$settings        = new Pizzapilot_Settings( PIZZAPILOT_NAME, PIZZAPILOT_VERSION );
			$available_slots = $settings->get_formatted_delivery_slots();

			if ( isset( $available_slots[ $delivery_time ] ) ) {
				$order->update_meta_data( '_pizzapilot_delivery_time_formatted', $available_slots[ $delivery_time ] );
			}
		}

		$order->save();

		if ( ! empty( $delivery_time ) ) {
			// Update slot availability
			$this->update_slot_availability( $delivery_time, $order_id );
		}
	}

	/**
	 * Update slot availability when an order is placed.
	 *
	 * In the free version, slots are unlimited so this just fires
	 * the action for Pro to handle capacity management.
	 *
	 * @since    1.0.0
	 * @param    int    $timestamp    The unix timestamp of the selected slot.
	 * @param    int    $order_id     The order ID.
	 * @return   void
	 */
	private function update_slot_availability( $timestamp, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Prevent double-processing when both classic and block checkout hooks fire.
		$already_processed = $order->get_meta( '_pizzapilot_slot_booking_processed', true );
		if ( $already_processed ) {
			return;
		}
		$order->update_meta_data( '_pizzapilot_slot_booking_processed', '1' );

		// Count items in order (for Pro version capacity tracking)
		$item_count = $this->count_order_items( $order_id );

		// Store item count in order meta for Pro version
		$order->update_meta_data( '_pizzapilot_item_count', $item_count );
		$order->save();

		/**
		 * Allow Pro version to add capacity checking logic.
		 *
		 * Pro version will find available slot rows and mark them as booked.
		 *
		 * @param int $timestamp  The unix timestamp of the slot.
		 * @param int $order_id   The order ID.
		 * @param int $item_count The number of items in the order.
		 */
		do_action( 'pizzapilot_slot_booked', $timestamp, $order_id, $item_count );
	}

	/**
	 * Count items in an order.
	 *
	 * In the free version, this counts all items.
	 * Pro version can filter this to count only specific product types.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    The order ID.
	 * @return   int                 Total item count.
	 */
	private function count_order_items( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return 0;
		}

		$item_count = 0;

		foreach ( $order->get_items() as $item ) {
			$item_count += $item->get_quantity();
		}

		/**
		 * Allow filtering of item count calculation.
		 *
		 * Pro version can use this to count only pizzas, for example.
		 *
		 * @param int      $item_count The calculated item count.
		 * @param int      $order_id   The order ID.
		 * @param WC_Order $order      The order object.
		 */
		return apply_filters( 'pizzapilot_order_item_count', $item_count, $order_id, $order );
	}

	/**
	 * Handle order status changes.
	 *
	 * Releases slot when order is cancelled or refunded.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id     The order ID.
	 * @param    string    $from_status  Previous order status.
	 * @param    string    $to_status    New order status.
	 * @param    WC_Order  $order        The order object.
	 * @return   void
	 */
	public function pizzapilot_handle_order_status_change( $order_id, $from_status, $to_status, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $order required by WC action signature.
		// Release slot if order is cancelled or refunded
		if ( in_array( $to_status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			$this->release_order_slot( $order_id );
		}
	}

	/**
	 * Release a slot when an order is cancelled.
	 *
	 * In the free version with unlimited capacity, this just fires
	 * the action for Pro to handle releasing the booked slot rows.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    The order ID.
	 * @return   void
	 */
	private function release_order_slot( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Get the delivery time for this order
		$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );

		if ( empty( $delivery_time ) ) {
			$delivery_time = $order->get_meta( '_pizzapilot_delivery_time', true );
		}

		if ( empty( $delivery_time ) ) {
			return; // No slot associated with this order
		}

		/**
		 * Allow Pro version to handle capacity adjustments.
		 *
		 * Pro version will find all slot rows with this order_id
		 * and set them back to available (availability = 1, order_id = NULL).
		 *
		 * @param int $order_id      The order ID being cancelled.
		 * @param int $delivery_time The slot timestamp.
		 */
		do_action( 'pizzapilot_slot_released', $order_id, $delivery_time );
	}
}
