<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Deactivator {

	/**
	 * Run deactivation tasks for PizzaPilot.
	 *
	 * Deactivation does not remove data. Full cleanup (deleting options,
	 * dropping the database table, clearing transients) happens in
	 * uninstall.php when the plugin is deleted via the WordPress admin.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function deactivate() {
		// Intentionally empty — cleanup happens in uninstall.php.
	}

}
