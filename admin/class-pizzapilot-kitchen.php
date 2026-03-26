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
		echo '<div class="wrap pizzapilot-kitchen-wrap">';
		echo '<h1>' . esc_html__( 'Kitchen Orders', 'pizzapilot' ) . '</h1>';

		echo '<p class="pizzapilot-kitchen-actions">';
		echo esc_html__( 'Orders for today, grouped by time slot.', 'pizzapilot' ) . ' ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-kitchen' ) ) . '" class="button">';
		echo esc_html__( 'Refresh', 'pizzapilot' );
		echo '</a>';
		echo '</p>';

		echo '<div class="pizzapilot-kitchen-empty">';
		echo '<p>' . esc_html__( 'No orders for today.', 'pizzapilot' ) . '</p>';
		echo '</div>';

		echo '</div>';
	}
}
