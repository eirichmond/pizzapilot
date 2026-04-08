<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pizzapilot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pizzapilot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pizzapilot-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pizzapilot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pizzapilot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pizzapilot-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Display PizzaPilot delivery information in the order admin area.
	 *
	 * Shows the delivery type and time slot in a custom meta box on the order edit page.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    The order post object.
	 * @return   void
	 */
	public function pizzapilot_display_order_meta( $post ) {
		$order = wc_get_order( $post->ID );

		if ( ! $order ) {
			return;
		}

		// Get delivery type from WooCommerce additional checkout fields
		$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );

		// Get delivery time from WooCommerce additional checkout fields
		$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );

		// Also check legacy meta keys in case they exist
		if ( empty( $delivery_type ) ) {
			$delivery_type = $order->get_meta( '_pizzapilot_delivery_type', true );
		}

		if ( empty( $delivery_time ) ) {
			$delivery_time = $order->get_meta( '_pizzapilot_delivery_time', true );
		}

		// Don't display anything if no PizzaPilot data
		if ( empty( $delivery_type ) && empty( $delivery_time ) ) {
			return;
		}

		echo '<div class="pizzapilot-order-meta" style="margin-top: 15px; padding: 12px; background: #f0f0f1; border-left: 4px solid #2271b1;">';
		echo '<h3 style="margin-top: 0;">' . esc_html__( 'PizzaPilot Delivery Details', 'pizzapilot' ) . '</h3>';

		if ( ! empty( $delivery_type ) ) {
			echo '<p><strong>' . esc_html__( 'Delivery Type:', 'pizzapilot' ) . '</strong> ';
			echo esc_html( ucfirst( $delivery_type ) ) . '</p>';
		}

		if ( ! empty( $delivery_time ) ) {
			// Convert timestamp to readable format
			$formatted_time = $this->format_delivery_time( $delivery_time );

			echo '<p><strong>' . esc_html__( 'Delivery Time:', 'pizzapilot' ) . '</strong> ';
			echo esc_html( $formatted_time ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Format delivery time timestamp into human-readable format.
	 *
	 * Converts unix timestamp to formatted date and time string.
	 *
	 * @since    1.0.0
	 * @param    int    $timestamp    Unix timestamp of the delivery slot.
	 * @return   string               Formatted delivery time string.
	 */
	private function format_delivery_time( $timestamp ) {
		if ( empty( $timestamp ) || ! is_numeric( $timestamp ) ) {
			return __( 'Not set', 'pizzapilot' );
		}

		// Get WordPress timezone
		$timezone = wp_timezone();
		$datetime = new DateTime( '@' . $timestamp );
		$datetime->setTimezone( $timezone );

		// Calculate slot end time (30 minutes later)
		$end_datetime = clone $datetime;
		$end_datetime->modify( '+30 minutes' );

		// Format: "Tuesday 24th Jun from 9:30am to 10:00am"
		$day_name = $datetime->format( 'l' );
		$day_number = $datetime->format( 'jS' );
		$month = $datetime->format( 'M' );
		$start_time = $datetime->format( 'g:ia' );
		$end_time = $end_datetime->format( 'g:ia' );

		return sprintf(
			'%s %s %s from %s to %s',
			$day_name,
			$day_number,
			$month,
			$start_time,
			$end_time
		);
	}

	/**
	 * Hide PizzaPilot fields from default WooCommerce order meta display.
	 *
	 * Prevents the checkout fields from showing in the shipping section
	 * since we display them in our custom meta box instead.
	 *
	 * @since    1.0.0
	 * @param    bool     $display   Whether to display the field.
	 * @param    string   $key       The field key.
	 * @param    object   $order     The order object.
	 * @return   bool                False to hide the field, true to show.
	 */
	public function pizzapilot_hide_checkout_field_display( $display, $key, $order ) {
		// Hide PizzaPilot fields from the default display
		$fields_to_hide = array(
			'pizzapilot/delivery-type',
			'pizzapilot/delivery-time',
		);

		if ( in_array( $key, $fields_to_hide, true ) ) {
			return false;
		}

		return $display;
	}

	/**
	 * Add CSS and JavaScript to hide PizzaPilot meta from shipping address display.
	 *
	 * Hides the "Delivery Options" and "Delivery Time" from the shipping address section
	 * since we display them in our custom meta box.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function pizzapilot_hide_meta_css() {
		$screen = get_current_screen();

		if ( ! $screen || ( $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders' ) ) {
			return;
		}

		?>
		<script>
			jQuery(document).ready(function($) {
				// Hide PizzaPilot delivery info from the shipping address display
				$('.order_data_column .address p').each(function() {
					var text = $(this).text();
					if (text.includes('Delivery Options:') || text.includes('Delivery Time:')) {
						$(this).hide();
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Add PizzaPilot column to orders list in admin.
	 *
	 * Adds a column showing delivery time slot to the orders table.
	 *
	 * @since    1.0.0
	 * @param    array    $columns    Existing columns.
	 * @return   array                Modified columns array.
	 */
	public function pizzapilot_add_order_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			// Insert after order status column
			if ( $key === 'order_status' ) {
				$new_columns['pizzapilot_delivery'] = __( 'Delivery Slot', 'pizzapilot' );
			}
		}

		return $new_columns;
	}

	/**
	 * Populate PizzaPilot column content in orders list (CPT storage).
	 *
	 * Displays delivery type and time in the custom column.
	 *
	 * @since    1.0.0
	 * @param    string    $column    Column name.
	 * @param    int       $post_id   Order post ID.
	 * @return   void
	 */
	public function pizzapilot_order_column_content( $column, $post_id ) {
		if ( $column !== 'pizzapilot_delivery' ) {
			return;
		}

		$order = wc_get_order( $post_id );
		$this->render_delivery_column( $order );
	}

	/**
	 * Populate PizzaPilot column content in orders list (HPOS storage).
	 *
	 * Displays delivery type and time in the custom column for
	 * WooCommerce High-Performance Order Storage.
	 *
	 * @since    1.1.0
	 * @param    string    $column    Column name.
	 * @param    WC_Order  $order     The order object.
	 * @return   void
	 */
	public function pizzapilot_order_column_content_hpos( $column, $order ) {
		if ( $column !== 'pizzapilot_delivery' ) {
			return;
		}

		$this->render_delivery_column( $order );
	}

	/**
	 * Render the delivery slot column content for an order.
	 *
	 * Shared rendering logic used by both CPT and HPOS column callbacks.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @param    WC_Order|false    $order    The order object or false.
	 * @return   void
	 */
	private function render_delivery_column( $order ) {
		if ( ! $order ) {
			echo '—';
			return;
		}

		// Get delivery type
		$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );
		if ( empty( $delivery_type ) ) {
			$delivery_type = $order->get_meta( '_pizzapilot_delivery_type', true );
		}

		// Get delivery time
		$delivery_time = $order->get_meta( '_wc_other/pizzapilot/delivery-time', true );
		if ( empty( $delivery_time ) ) {
			$delivery_time = $order->get_meta( '_pizzapilot_delivery_time', true );
		}

		if ( empty( $delivery_time ) ) {
			echo '—';
			return;
		}

		// Convert timestamp to short format
		$timezone = wp_timezone();
		$datetime = new DateTime( '@' . $delivery_time );
		$datetime->setTimezone( $timezone );

		$end_datetime = clone $datetime;
		$end_datetime->modify( '+30 minutes' );

		// Short format: "2:00pm-2:30pm"
		$display = sprintf(
			'<strong>%s</strong><br><small>%s - %s</small>',
			esc_html( ucfirst( $delivery_type ) ),
			esc_html( $datetime->format( 'g:ia' ) ),
			esc_html( $end_datetime->format( 'g:ia' ) )
		);

		echo wp_kses_post( $display );
	}

	/**
	 * Add action links to the plugins list page.
	 *
	 * Adds Settings and Upgrade to Pro links next to the plugin name
	 * on the WordPress Plugins page.
	 *
	 * @since    1.1.0
	 * @param    array $links    Existing plugin action links.
	 * @return   array           Modified links array.
	 */
	public function pizzapilot_plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-settings' ) ) . '">' . esc_html__( 'Settings', 'pizzapilot' ) . '</a>',
		);

		if ( ! Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' ) ) {
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-upgrade' ) ) . '" style="color: #f0b849; font-weight: 600;">' . esc_html__( 'Upgrade to Pro', 'pizzapilot' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}

}
