<?php
/**
 * Block registration for the PizzaPilot plugin.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.2.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers all PizzaPilot Gutenberg blocks.
 *
 * Every block lives in its own folder under blocks/ at the plugin root and
 * is registered from its block.json via register_block_type(). Server-side
 * render markup lives alongside each block in render.php and is included
 * from the render callback so each block stays self-contained.
 *
 * Adding a new block: drop a new folder under blocks/, give it a block.json
 * + render.php (and any view/editor/style files), then add a single
 * register_block_type() call to register_blocks().
 *
 * @since      1.2.0
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Blocks {

	/**
	 * Register all PizzaPilot blocks.
	 *
	 * Hooked to the WordPress init action so registration runs after
	 * core has loaded the block API and before the editor or front-end
	 * needs to resolve the block.
	 *
	 * @since    1.2.0
	 * @return   void
	 */
	public function register_blocks() {
		register_block_type(
			PIZZAPILOT_PLUGIN_DIR . 'blocks/delivery-checker',
			array(
				'render_callback' => array( $this, 'render_delivery_checker' ),
			)
		);
	}

	/**
	 * Server-side render callback for the delivery-checker block.
	 *
	 * Includes the block's render template so block markup stays in
	 * blocks/delivery-checker/render.php alongside the rest of the block.
	 * The included file inherits this method's scope, so $attributes,
	 * $content, and $block are all available inside the template.
	 *
	 * @since    1.2.0
	 * @param    array     $attributes Block attributes.
	 * @param    string    $content    Inner block content.
	 * @param    \WP_Block $block      Block instance.
	 * @return   string                 Rendered block HTML.
	 */
	public function render_delivery_checker( $attributes = array(), $content = '', $block = null ) {
		unset( $content, $block ); // Reserved for future use; suppress unused-arg notices.

		ob_start();
		require PIZZAPILOT_PLUGIN_DIR . 'blocks/delivery-checker/render.php';
		return (string) ob_get_clean();
	}
}
