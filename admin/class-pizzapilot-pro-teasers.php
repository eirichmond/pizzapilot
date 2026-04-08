<?php
/**
 * Pro feature teaser functionality.
 *
 * Displays upgrade prompts, Pro feature previews, and a dedicated
 * upgrade page to encourage users to upgrade to PizzaPilot Pro.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.1.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 */

/**
 * Pro feature teaser class.
 *
 * Manages the "Upgrade to Pro" admin page and related UI elements
 * that showcase Pro features in the free version.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class PizzaPilot_Pro_Teasers {

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
	 * Register the Upgrade to Pro submenu page.
	 *
	 * Adds a highlighted submenu item under the PizzaPilot settings menu.
	 * Only shown when Pro is not active.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function add_upgrade_menu() {
		if ( Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' ) ) {
			return;
		}

		add_submenu_page(
			'pizzapilot-kitchen',
			__( 'Upgrade to Pro', 'pizzapilot' ),
			'<span style="color: #f0b849;">' . esc_html__( 'Upgrade to Pro', 'pizzapilot' ) . '</span>',
			'manage_options',
			'pizzapilot-upgrade',
			array( $this, 'render_upgrade_page' )
		);
	}

	/**
	 * Enqueue styles for the upgrade page.
	 *
	 * @since    1.1.0
	 * @param    string $hook_suffix    The current admin page hook suffix.
	 * @return   void
	 */
	public function enqueue_upgrade_styles( $hook_suffix ) {
		if ( 'kitchen-orders_page_pizzapilot-upgrade' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-upgrade',
			plugin_dir_url( __FILE__ ) . 'css/pizzapilot-upgrade.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Render the Upgrade to Pro page.
	 *
	 * Displays a feature comparison between Free and Pro versions
	 * with clear upgrade call-to-action.
	 *
	 * @since    1.1.0
	 * @return   void
	 */
	public function render_upgrade_page() {
		$upgrade_url = 'https://pizzapilot.co.uk/pricing/';

		?>
		<div class="wrap pizzapilot-upgrade-wrap">
			<h1><?php echo esc_html__( 'Upgrade to PizzaPilot Pro', 'pizzapilot' ); ?></h1>

			<div class="pizzapilot-upgrade-hero">
				<p class="pizzapilot-upgrade-tagline">
					<?php echo esc_html__( 'Take your pizzeria to the next level with advanced ordering, kitchen management, and delivery tools.', 'pizzapilot' ); ?>
				</p>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary button-hero" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'Get PizzaPilot Pro', 'pizzapilot' ); ?>
				</a>
			</div>

			<div class="pizzapilot-upgrade-features">
				<h2><?php echo esc_html__( 'Free vs Pro', 'pizzapilot' ); ?></h2>

				<table class="pizzapilot-comparison-table widefat">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Feature', 'pizzapilot' ); ?></th>
							<th><?php echo esc_html__( 'Free', 'pizzapilot' ); ?></th>
							<th class="pizzapilot-pro-col"><?php echo esc_html__( 'Pro', 'pizzapilot' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$features = $this->get_feature_comparison();
						foreach ( $features as $feature ) {
							echo '<tr>';
							echo '<td>' . esc_html( $feature['name'] ) . '</td>';
							echo '<td>' . esc_html( $feature['free'] ) . '</td>';
							echo '<td class="pizzapilot-pro-col">' . esc_html( $feature['pro'] ) . '</td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="pizzapilot-upgrade-cta">
				<a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary button-hero" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'Upgrade Now', 'pizzapilot' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the feature comparison data for the upgrade page.
	 *
	 * @since    1.1.0
	 * @return   array    Array of feature arrays with 'name', 'free', and 'pro' keys.
	 */
	private function get_feature_comparison() {
		return array(
			array(
				'name' => __( 'Time Slot Scheduling', 'pizzapilot' ),
				'free' => __( 'Same-day only', 'pizzapilot' ),
				'pro'  => __( 'Future dates, auto-generation, recurring', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Slot Capacity', 'pizzapilot' ),
				'free' => __( 'Unlimited', 'pizzapilot' ),
				'pro'  => __( 'Set limits by order count or pizza qty', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Delivery Radius', 'pizzapilot' ),
				'free' => __( 'Single postcode, fixed radius', 'pizzapilot' ),
				'pro'  => __( 'Multiple origins, variable radii, overrides', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Geocoding', 'pizzapilot' ),
				'free' => __( 'UK postcodes (postcodes.io)', 'pizzapilot' ),
				'pro'  => __( 'Global (Google Maps API)', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Delivery Maps', 'pizzapilot' ),
				'free' => '—',
				'pro'  => __( 'Interactive Mapbox maps with What3Words', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Kitchen UI', 'pizzapilot' ),
				'free' => __( 'Order cards with completion toggle', 'pizzapilot' ),
				'pro'  => __( 'Live queue, drag & drop, ticket printing', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Driver Tools', 'pizzapilot' ),
				'free' => '—',
				'pro'  => __( 'Delivery interface with maps and navigation', 'pizzapilot' ),
			),
			array(
				'name' => __( 'Notifications', 'pizzapilot' ),
				'free' => '—',
				'pro'  => __( 'SMS and email order updates', 'pizzapilot' ),
			),
		);
	}

	/**
	 * Get the URL to the upgrade page.
	 *
	 * @since    1.1.0
	 * @return   string    Admin URL to the upgrade page.
	 */
	public static function get_upgrade_page_url() {
		return admin_url( 'admin.php?page=pizzapilot-upgrade' );
	}

	/**
	 * Get the external upgrade URL.
	 *
	 * @since    1.1.0
	 * @return   string    External URL to pricing page.
	 */
	public static function get_upgrade_url() {
		return 'https://pizzapilot.co.uk/pricing/';
	}
}
