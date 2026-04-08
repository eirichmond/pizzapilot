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
            '<a href="https://pizzapilot.co.uk/pricing/">',
            '</a>'
        );
    }
} 
