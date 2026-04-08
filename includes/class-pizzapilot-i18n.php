<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pizzapilot',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
