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
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pizzapilot-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pizzapilot-public.js', array( 'jquery' ), $this->version, false );

	}


	public function pizzapilot_register_checkout_fields() {
		$settings = new Pizzapilot_Settings( PIZZAPILOT_NAME, PIZZAPILOT_VERSION );
		$slots = $settings->get_formatted_delivery_slots();

		// Convert to options format
		$options = array();
		foreach ( $slots as $timestamp => $label ) {
			$options[] = array(
				'value' => $timestamp,
				'label' => $label
			);
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'          => 'pizzapilot/delivery-type',
				'label'       => __( 'Delivery Options', 'pizzapilot' ),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				'placeholder' => 'Select a delivery type',
				'options'     => array(
					array(
						'value' => 'delivery',
						'label' => 'Delivery'
					),
					array(
						'value' => 'collect',
						'label' => 'Collect'
					)
				)
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'          => 'pizzapilot/delivery-time',
				'label'       => __( 'Delivery Time', 'pizzapilot' ),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				'placeholder' => 'Select a time',
				'options'     => $options
			)
		);

		
	}

}
