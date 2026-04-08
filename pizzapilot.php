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
 * Version:           1.0.0
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
define( 'PIZZAPILOT_VERSION', '1.0.0' );
define( 'PIZZAPILOT_NAME', 'PizzaPilot' );
define( 'PIZZAPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIZZAPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pizzapilot-activator.php
 */
function activate_pizzapilot() {
	require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-activator.php';
	Pizzapilot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pizzapilot-deactivator.php
 */
function deactivate_pizzapilot() {
	require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-deactivator.php';
	Pizzapilot_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pizzapilot' );
register_deactivation_hook( __FILE__, 'deactivate_pizzapilot' );

/**
 * Add action links to the plugins list page.
 *
 * Adds Settings and Upgrade links next to the plugin name.
 *
 * @since    1.1.0
 * @param    array $links    Existing plugin action links.
 * @return   array           Modified links array.
 */
function pizzapilot_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-settings' ) ) . '">' . esc_html__( 'Settings', 'pizzapilot' ) . '</a>',
	);

	if ( ! class_exists( 'Pizzapilot_Pro' ) ) {
		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=pizzapilot-upgrade' ) ) . '" style="color: #f0b849; font-weight: 600;">' . esc_html__( 'Upgrade to Pro', 'pizzapilot' ) . '</a>';
	}

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pizzapilot_plugin_action_links' );

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
function run_pizzapilot() {

	$plugin = new Pizzapilot();
	$plugin->run();

}
run_pizzapilot();
