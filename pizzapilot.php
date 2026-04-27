<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://elliottrichmond.co.uk
 * @since             1.0.0
 * @package           Pizzapilot
 *
 * @wordpress-plugin
 * Plugin Name:       PizzaPilot
 * Plugin URI:        https://elliottrichmond.co.uk
 * Description:       PizzaPilot is designed to help small pizzerias set up time slot-based ordering and delivery radius checks within WooCommerce. It includes a basic frontend and backend interface for order management during kitchen hours
 * Version:           1.2.0
 * Author:            Elliott Richmond
 * Author URI:        https://elliottrichmond.co.uk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pizzapilot
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PIZZAPILOT_VERSION', '1.2.0' );
define( 'PIZZAPILOT_NAME', 'PizzaPilot' );
define( 'PIZZAPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIZZAPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pizzapilot-activator.php
 */
function pizzapilot_activate() {
	require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-activator.php';
	Pizzapilot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pizzapilot-deactivator.php
 */
function pizzapilot_deactivate() {
	require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-deactivator.php';
	Pizzapilot_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'pizzapilot_activate' );
register_deactivation_hook( __FILE__, 'pizzapilot_deactivate' );

/**
 * Display admin notices for plugin activation and dependency issues.
 *
 * Shows a warning if WooCommerce is not active, and a welcome message
 * on first activation. Notices are one-time via transients.
 *
 * @since    1.1.0
 * @return   void
 */
function pizzapilot_admin_notices() {
	// WooCommerce missing notice.
	if ( get_transient( 'pizzapilot_missing_woocommerce' ) ) {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>' . esc_html__( 'PizzaPilot', 'pizzapilot' ) . '</strong>: ';
		echo esc_html__( 'WooCommerce is required for PizzaPilot to work. Please install and activate WooCommerce.', 'pizzapilot' );
		echo '</p></div>';
		delete_transient( 'pizzapilot_missing_woocommerce' );

		// Deactivate the plugin.
		deactivate_plugins( plugin_basename( __FILE__ ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		return;
	}

	// Welcome notice on first activation.
	if ( get_transient( 'pizzapilot_activation_notice' ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo '<strong>' . esc_html__( 'PizzaPilot activated!', 'pizzapilot' ) . '</strong> ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-settings' ) ) . '">';
		echo esc_html__( 'Configure your settings', 'pizzapilot' );
		echo '</a> ';
		echo esc_html__( 'to get started.', 'pizzapilot' );
		echo '</p></div>';
		delete_transient( 'pizzapilot_activation_notice' );
	}
}
add_action( 'admin_notices', 'pizzapilot_admin_notices' );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 *
 * @since    1.1.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

require PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-helpers.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot.php';



/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function pizzapilot_run() {

	$plugin = new Pizzapilot();
	$plugin->run();
}
pizzapilot_run();
