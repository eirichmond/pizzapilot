<?php


/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pizzapilot_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PIZZAPILOT_VERSION' ) ) {
			$this->version = PIZZAPILOT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'pizzapilot';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_settings_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pizzapilot_Loader. Orchestrates the hooks of the plugin.
	 * - Pizzapilot_i18n. Defines internationalization functionality.
	 * - Pizzapilot_Admin. Defines all hooks for the admin area.
	 * - Pizzapilot_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the settings area.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'settings/class-pizzapilot-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'admin/class-pizzapilot-admin.php';

		/**
		 * The class responsible for the kitchen order interface.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'admin/class-pizzapilot-kitchen.php';

		/**
		 * The class responsible for Pro feature teasers and upgrade page.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'admin/class-pizzapilot-pro-teasers.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'public/class-pizzapilot-public.php';

		/**
		 * The class responsible for delivery radius checking and validation.
		 */
		require_once PIZZAPILOT_PLUGIN_DIR . 'includes/class-pizzapilot-delivery-checker.php';

		$this->loader = new Pizzapilot_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pizzapilot_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Pizzapilot_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the settings area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_settings_hooks() {

		$plugin_settings = new Pizzapilot_Settings( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_settings, 'ppilot_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'ppilot_register_settings' );
		$this->loader->add_action( 'admin_head', $plugin_settings, 'add_settings_help_tabs' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Pizzapilot_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Plugin action links on Plugins page.
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( PIZZAPILOT_PLUGIN_DIR . 'pizzapilot.php' ), $plugin_admin, 'pizzapilot_plugin_action_links' );

		// Kitchen order interface.
		$plugin_kitchen = new PizzaPilot_Kitchen( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_kitchen, 'add_kitchen_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_kitchen, 'enqueue_kitchen_styles' );
		$this->loader->add_action( 'admin_head', $plugin_kitchen, 'add_kitchen_help_tab' );
		$this->loader->add_action( 'admin_post_pizzapilot_mark_kitchen_completed', $plugin_kitchen, 'handle_mark_completed' );
		$this->loader->add_action( 'admin_action_pizzapilot_dismiss_kitchen_pro', $plugin_kitchen, 'handle_dismiss_kitchen_pro' );

		// Pro feature teasers and upgrade page.
		$plugin_pro_teasers = new PizzaPilot_Pro_Teasers( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_pro_teasers, 'add_upgrade_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_pro_teasers, 'enqueue_upgrade_styles' );

		// Display PizzaPilot delivery info on order edit page
		$this->loader->add_action( 'woocommerce_admin_order_data_after_billing_address', $plugin_admin, 'pizzapilot_display_order_meta' );

		// Hide PizzaPilot fields from default WooCommerce additional fields display
		$this->loader->add_filter( 'woocommerce_order_data_store_cpt_display_additional_field', $plugin_admin, 'pizzapilot_hide_checkout_field_display', 10, 3 );

		// Add CSS to hide fields as backup
		$this->loader->add_action( 'admin_head', $plugin_admin, 'pizzapilot_hide_meta_css' );

		// Add PizzaPilot column to orders list (CPT storage)
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'pizzapilot_add_order_column' );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'pizzapilot_order_column_content', 10, 2 );

		// Add PizzaPilot column to orders list (HPOS storage)
		$this->loader->add_filter( 'manage_woocommerce_page_wc-orders_columns', $plugin_admin, 'pizzapilot_add_order_column' );
		$this->loader->add_action( 'manage_woocommerce_page_wc-orders_custom_column', $plugin_admin, 'pizzapilot_order_column_content_hpos', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Pizzapilot_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register checkout fields
		$this->loader->add_action( 'woocommerce_init', $plugin_public, 'pizzapilot_register_checkout_fields' );

		// Validate checkout fields
		$this->loader->add_action( 'woocommerce_checkout_process', $plugin_public, 'pizzapilot_validate_checkout_fields' );

		// Save checkout fields to order meta (for traditional checkout)
		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $plugin_public, 'pizzapilot_save_checkout_fields' );

		// Save checkout fields to order meta (for block-based checkout with additional fields API)
		$this->loader->add_action( 'woocommerce_store_api_checkout_update_order_from_request', $plugin_public, 'pizzapilot_save_checkout_fields_block', 10, 2 );

		// Handle order status changes (cancellations, refunds)
		$this->loader->add_action( 'woocommerce_order_status_changed', $plugin_public, 'pizzapilot_handle_order_status_change', 10, 4 );

		// Initialize delivery radius checker
		new Pizzapilot_Delivery_Checker();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pizzapilot_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
