<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * This class handles all the settings page functionality including:
 * - Tabbed interface for different setting categories
 * - Registration and sanitization of settings
 * - Rendering of settings fields and sections
 * - Integration with WordPress Settings API
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/admin
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Settings {

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
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the admin menu for PizzaPilot settings.
	 * 
	 * Adds a submenu page under WooCommerce if active, otherwise under Settings.
	 * This ensures the settings are easily accessible in the appropriate context.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function ppilot_admin_menu() {
		// Check if WooCommerce is active
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$parent_slug = 'woocommerce';
		} else {
			$parent_slug = 'options-general.php';
		}
		add_submenu_page(
			$parent_slug,
			'PizzaPilot Settings',
			'PizzaPilot',
			'manage_options',
			'pizzapilot-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Render the PizzaPilot settings page with tabbed interface.
	 * 
	 * Creates a tabbed interface for different setting categories:
	 * - General: Basic plugin configuration
	 * - Delivery: Delivery-related settings
	 * - Advanced: Pro features and advanced options
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function settings_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'PizzaPilot Settings', 'pizzapilot' ); ?></h1>
			
			<h2 class="nav-tab-wrapper">
				<a href="?page=pizzapilot-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'General', 'pizzapilot' ); ?>
				</a>
				<a href="?page=pizzapilot-settings&tab=delivery" class="nav-tab <?php echo $active_tab == 'delivery' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Delivery', 'pizzapilot' ); ?>
				</a>
				<?php
				/**
				 * Filter to add additional tabs to the PizzaPilot settings page.
				 * 
				 * @param string $active_tab The currently active tab.
				 */
				do_action( 'pizzapilot_settings_tabs', $active_tab );
				?>
				<a href="?page=pizzapilot-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Advanced', 'pizzapilot' ); ?>
				</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				if ( $active_tab == 'general' ) {
					settings_fields( 'pizzapilot_general_settings' );
					do_settings_sections( 'pizzapilot-settings-general' );
				} elseif ( $active_tab == 'delivery' ) {
					settings_fields( 'pizzapilot_delivery_settings' );
					do_settings_sections( 'pizzapilot-settings-delivery' );
				} elseif ( $active_tab == 'advanced' ) {
					settings_fields( 'pizzapilot_advanced_settings' );
					do_settings_sections( 'pizzapilot-settings-advanced' );
				}
				
				/**
				 * Filter to add additional settings sections to the PizzaPilot settings page.
				 * 
				 * @param string $active_tab The currently active tab.
				 */
				do_action( 'pizzapilot_settings_sections', $active_tab );
				
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register all settings and their sections.
	 * 
	 * Registers settings with WordPress Settings API including:
	 * - Setting types and defaults
	 * - Sanitization callbacks
	 * - Settings sections and fields
	 * - Field callbacks for rendering
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function ppilot_register_settings() {
		$pro_active = Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' );
		$upgrade_message = Pizzapilot_Helpers::pizzapilot_pro_upgrade_message();

		// Register General Settings
		register_setting(
			'pizzapilot_general_settings',
			'pizzapilot_general_settings',
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_general_settings' )
			)
		);

		// Register Delivery Settings
		register_setting(
			'pizzapilot_delivery_settings',
			'pizzapilot_delivery_settings',
			array(
				'type'              => 'array',
				'default'           => array(
					'radius_unit'     => 'km',
					'delivery_postcode' => '',
					'delivery_radius' => 5,
				),
				'sanitize_callback' => array( $this, 'sanitize_delivery_settings' )
			)
		);

		// Register Advanced Settings
		register_setting(
			'pizzapilot_advanced_settings',
			'pizzapilot_advanced_settings',
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_advanced_settings' )
			)
		);

		// General Settings Tab - Basic plugin configuration
		add_settings_section( 
			'pizzapilot_general', 
			esc_html__( 'General Settings', 'pizzapilot' ), 
			array( $this, 'section_callback' ), 
			'pizzapilot-settings-general' 
		);

		add_settings_field( 
			'pizzapilot_enabled', 
			esc_html__( 'Enable PizzaPilot', 'pizzapilot' ), 
			array( $this, 'enabled_callback' ), 
			'pizzapilot-settings-general', 
			'pizzapilot_general' 
		);

		// Delivery Settings Tab - Delivery configuration
		add_settings_section( 
			'pizzapilot_delivery', 
			esc_html__( 'Delivery Settings', 'pizzapilot' ), 
			array( $this, 'delivery_section_callback' ), 
			'pizzapilot-settings-delivery' 
		);

		add_settings_field( 
			'pizzapilot_delivery_postcode', 
			esc_html__( 'Delivery Postcode', 'pizzapilot' ), 
			array( $this, 'delivery_postcode_callback' ), 
			'pizzapilot-settings-delivery', 
			'pizzapilot_delivery' 
		);

		add_settings_field( 
			'pizzapilot_delivery_radius', 
			esc_html__( 'Delivery Radius', 'pizzapilot' ), 
			array( $this, 'delivery_radius_callback' ), 
			'pizzapilot-settings-delivery', 
			'pizzapilot_delivery' 
		);

		add_settings_field( 
			'pizzapilot_radius_unit', 
			esc_html__( 'Radius Unit', 'pizzapilot' ), 
			array( $this, 'radius_unit_callback' ), 
			'pizzapilot-settings-delivery', 
			'pizzapilot_delivery' 
		);

		/**
		 * Callback to render the Delivery Start Time input field.
		 *
		 * Outputs a time input for the delivery start time setting on the Delivery tab.
		 * Uses the value from settings or defaults to '09:30'.
		 *
		 * @since    1.0.0
		 * @return   void
		 */
		if ( !$pro_active ) {
			add_settings_field(
				'pizzapilot_delivery_start_time',
				esc_html__( 'Delivery Start Time', 'pizzapilot' ),
				array( $this, 'delivery_start_time_callback' ),
				'pizzapilot-settings-delivery',
				'pizzapilot_delivery'
			);
		}
		/**
		 * Callback to render the Delivery End Time input field.
		 *
		 * Outputs a time input for the delivery end time setting on the Delivery tab.
		 * Uses the value from settings or defaults to '17:30'.
		 *
		 * @since    1.0.0
		 * @return   void
		 */
		if ( !$pro_active ) {
			add_settings_field(
				'pizzapilot_delivery_end_time',
				esc_html__( 'Delivery End Time', 'pizzapilot' ),
				array( $this, 'delivery_end_time_callback' ),
				'pizzapilot-settings-delivery',
				'pizzapilot_delivery'
			);
		}

		// Advanced Settings Tab - Pro features and advanced options
		add_settings_section( 
			'pizzapilot_advanced', 
			esc_html__( 'Advanced Settings', 'pizzapilot' ),
			array( $this, 'advanced_section_callback' ),
			'pizzapilot-settings-advanced'
		);

		add_settings_field( 
			'pizzapilot_same_day_only', 
			esc_html__( 'Same-Day Delivery Only', 'pizzapilot' ),
			array( $this, 'same_day_only_callback' ),
			'pizzapilot-settings-advanced',
			'pizzapilot_advanced'
		);

		/**
		 * Allow Pro plugin to add its own settings fields
		 * 
		 * @param bool $pro_active Whether the Pro plugin is active
		 * @param string $settings_page The settings page slug
		 * @param string $section_id The section ID
		 */
		do_action( 'pizzapilot_register_pro_settings', $pro_active, 'pizzapilot-settings-advanced', 'pizzapilot_advanced' );
	}

	/**
	 * Sanitize general settings
	 *
	 * @param array $input The settings input array
	 * @return array Sanitized settings
	 */
	public function sanitize_general_settings( $input ) {
		$sanitized = array();
		// For checkboxes, we need to explicitly check if the key exists in the input
		// If it doesn't exist, it means the checkbox was unchecked
		$sanitized['enabled'] = isset( $input['enabled'] ) ? true : false;
		return $sanitized;
	}

	/**
	 * Sanitize delivery settings
	 *
	 * @param array $input The settings input array
	 * @return array Sanitized settings
	 */
	public function sanitize_delivery_settings( $input ) {
		$sanitized = array();
		$sanitized['radius_unit'] = isset( $input['radius_unit'] ) && in_array( $input['radius_unit'], array( 'km', 'miles' ) ) ? $input['radius_unit'] : 'km';
		$sanitized['delivery_postcode'] = isset( $input['delivery_postcode'] ) ? sanitize_text_field( $input['delivery_postcode'] ) : '';
		$sanitized['delivery_radius'] = isset( $input['delivery_radius'] ) ? absint( $input['delivery_radius'] ) : 5;
		
		// Sanitize delivery start time
		if ( isset( $input['delivery_start_time'] ) ) {
			// Validate time format (HH:mm)
			if ( preg_match( '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $input['delivery_start_time'] ) ) {
				$sanitized['delivery_start_time'] = $input['delivery_start_time'];
			} else {
				$sanitized['delivery_start_time'] = '10:00'; // Default if invalid
			}
		} else {
			$sanitized['delivery_start_time'] = '10:00';
		}

		// Sanitize delivery end time
		if ( isset( $input['delivery_end_time'] ) ) {
			if ( preg_match( '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $input['delivery_end_time'] ) ) {
				$sanitized['delivery_end_time'] = $input['delivery_end_time'];
			} else {
				$sanitized['delivery_end_time'] = '17:30';
			}
		} else {
			$sanitized['delivery_end_time'] = '17:30';
		}
		
		return $sanitized;
	}
	

	/**
	 * Sanitize advanced settings
	 *
	 * @param array $input The settings input array
	 * @return array Sanitized settings
	 */
	public function sanitize_advanced_settings( $input ) {
		$sanitized = array();
		// For checkboxes, we need to explicitly check if the key exists in the input
		// If it doesn't exist, it means the checkbox was unchecked
		$sanitized['same_day_only'] = isset( $input['same_day_only'] ) ? true : false;
		
		/**
		 * Allow Pro plugin to add its own sanitization
		 * 
		 * @param array $sanitized The sanitized array so far
		 * @param array $input The raw input array
		 */
		$sanitized = apply_filters( 'pizzapilot_sanitize_pro_settings', $sanitized, $input );
		
		return $sanitized;
	}

	/**
	 * Get a specific setting value
	 *
	 * @param string $key The setting key
	 * @param mixed $default Default value if setting doesn't exist
	 * @return mixed The setting value
	 */
	public function get_setting( $key, $default = null ) {
		// Determine which option group the key belongs to
		$general_keys = array( 'enabled' );
		$delivery_keys = array( 'radius_unit', 'delivery_postcode', 'delivery_radius', 'delivery_start_time', 'delivery_end_time' );
		$advanced_keys = array( 'same_day_only' );

		/**
		 * Allow Pro plugin to add its own setting keys
		 * 
		 * @param array $advanced_keys Current advanced setting keys
		 */
		$advanced_keys = apply_filters( 'pizzapilot_advanced_setting_keys', $advanced_keys );

		if ( in_array( $key, $general_keys ) ) {
			$settings = get_option( 'pizzapilot_general_settings', array() );
		} elseif ( in_array( $key, $delivery_keys ) ) {
			$settings = get_option( 'pizzapilot_delivery_settings', array() );
		} elseif ( in_array( $key, $advanced_keys ) ) {
			$settings = get_option( 'pizzapilot_advanced_settings', array() );
		} else {
			return $default;
		}

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Section callback for the general settings section.
	 * 
	 * Displays a description of the general settings section.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure the general settings for PizzaPilot.', 'pizzapilot' ) . '</p>';
	}

	/**
	 * Section callback for the delivery settings section.
	 * 
	 * Displays a description of the delivery settings section.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function delivery_section_callback() {
		echo '<p>' . esc_html__( 'Configure delivery-related settings.', 'pizzapilot' ) . '</p>';
	}

	/**
	 * Section callback for the advanced settings section.
	 * 
	 * Displays a description of the advanced settings section.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function advanced_section_callback() {
		echo '<p>' . esc_html__( 'Configure advanced settings and pro features.', 'pizzapilot' ) . '</p>';
	}

	/**
	 * Callback to render the Enable PizzaPilot checkbox.
	 */
	public function enabled_callback() {
		$option = $this->get_setting( 'enabled', true );
		// Use checked() with the actual boolean value
		echo '<input type="checkbox" id="pizzapilot_enabled" name="pizzapilot_general_settings[enabled]" value="1"' . checked( true, $option, false ) . ' />';
		echo '<label for="pizzapilot_enabled"> ' . esc_html__( 'Enable or disable the PizzaPilot plugin.', 'pizzapilot' ) . '</label>';
	}

	/**
	 * Callback to render the Delivery Postcode text input.
	 */
	public function delivery_postcode_callback() {
		$option = $this->get_setting( 'delivery_postcode', '' );
		echo '<input type="text" id="pizzapilot_delivery_postcode" name="pizzapilot_delivery_settings[delivery_postcode]" value="' . esc_attr( $option ) . '" class="regular-text" />';
		echo '<label for="pizzapilot_delivery_postcode"> ' . esc_html__( 'Enter the delivery from postcode.', 'pizzapilot' ) . '</label>';
	}

	/**
	 * Callback to render the Delivery Radius text input.
	 */
	public function delivery_radius_callback() {
		$option = $this->get_setting( 'delivery_radius', 5 );
		$units = $this->get_setting( 'radius_unit', 'km' );
		echo '<input type="number" id="pizzapilot_delivery_radius" name="pizzapilot_delivery_settings[delivery_radius]" value="' . esc_attr( $option ) . '" class="small-text" min="1" step="1" />';
		echo '<label for="pizzapilot_delivery_radius"> ' . esc_html__( 'Enter the delivery radius. (' . esc_attr( $units ) . ')', 'pizzapilot' ) . '</label>';
	}

	/**
	 * Callback to render the Radius Unit select dropdown.
	 */
	public function radius_unit_callback() {
		$option = $this->get_setting( 'radius_unit', 'km' );
		?>
		<select id="pizzapilot_radius_unit" name="pizzapilot_delivery_settings[radius_unit]">
			<option value="km" <?php selected( $option, 'km' ); ?>><?php echo esc_html__( 'Kilometers', 'pizzapilot' ); ?></option>
			<option value="miles" <?php selected( $option, 'miles' ); ?>><?php echo esc_html__( 'Miles', 'pizzapilot' ); ?></option>
		</select>
		<label for="pizzapilot_radius_unit"> <?php echo esc_html__( 'Select the unit for delivery radius.', 'pizzapilot' ); ?></label>
		<?php
	}

	/**
	 * Callback to render the Delivery Start Time input field.
	 *
	 * Outputs a time input for the delivery start time setting on the Delivery tab.
	 * Uses the value from settings or defaults to '09:30'.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function delivery_start_time_callback() {
		$pro_active = Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' );
		$upgrade_message = Pizzapilot_Helpers::pizzapilot_pro_upgrade_message();

		$option = $this->get_setting( 'delivery_start_time', '09:30' );

		echo '<input type="time" id="pizzapilot_delivery_start_time" name="pizzapilot_delivery_settings[delivery_start_time]" value="' . esc_attr( $option ) . '" class="regular-text" />';
		echo '<label for="pizzapilot_delivery_start_time"> ' . esc_html__( 'Enter the delivery start time.', 'pizzapilot' ) . '</label>';
		if ( !$pro_active ) {
			echo '<p class="description"> ' . wp_kses_post( __( $upgrade_message, 'pizzapilot' ) ) . '</p>';
		}
	}

	public function delivery_end_time_callback() {
		$pro_active = Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' );
		$upgrade_message = Pizzapilot_Helpers::pizzapilot_pro_upgrade_message();

		$option = $this->get_setting( 'delivery_end_time', '17:30' );

		echo '<input type="time" id="pizzapilot_delivery_end_time" name="pizzapilot_delivery_settings[delivery_end_time]" value="' . esc_attr( $option ) . '" class="regular-text" />';
		echo '<label for="pizzapilot_delivery_end_time"> ' . esc_html__( 'Enter the delivery end time.', 'pizzapilot' ) . '</label>';
		if ( !$pro_active ) {
			echo '<p class="description"> ' . wp_kses_post( __( $upgrade_message, 'pizzapilot' ) ) . '</p>';
		}

	}

	/**
	 * Callback to render the Same-Day Delivery Only checkbox.
	 */
	public function same_day_only_callback() {
		$pro_active = Pizzapilot_Helpers::pizzapilot_is_pro_active( 'Pizzapilot_Pro' );
		$upgrade_message = Pizzapilot_Helpers::pizzapilot_pro_upgrade_message();
		if ( $pro_active ) {
			$option = $this->get_setting( 'same_day_only', true );
			// Use checked() with the actual boolean value
			echo '<input type="checkbox" id="pizzapilot_same_day_only" name="pizzapilot_advanced_settings[same_day_only]" value="1"' . checked( true, $option, false ) . ' />';
			echo '<label for="pizzapilot_same_day_only"> ' . esc_html__( 'Only allow same-day delivery orders.', 'pizzapilot' ) . '</label>';
			echo '<p class="description">' . esc_html__( 'Note: Changing this setting will require saving and refreshing the page to show/hide slot countdown options.', 'pizzapilot' ) . '</p>';
		} else {
			echo '<input type="checkbox" id="pizzapilot_same_day_only" name="pizzapilot_advanced_settings[same_day_only]" value="1" class="regular-text" disabled checked/>';
			echo '<label for="pizzapilot_same_day_only"> ' . wp_kses_post( __( $upgrade_message, 'pizzapilot' ) ) . '</label>';
		}
	}

	/**
	 * Get available delivery time slots between start and end times
	 *
	 * Generates an array of 30-minute time slots for today between the configured
	 * delivery start and end times. Returns unix timestamps as keys and formatted
	 * date/time strings as values. Only returns time slots that haven't elapsed
	 * with a 20-minute buffer (slots are removed 20 minutes after they start).
	 *
	 * @since    1.0.0
	 * @return   array    Array of time slots with timestamp keys and formatted values
	 */
	private function get_delivery_time_slots() {

		$start_time = $this->get_setting( 'delivery_start_time', '09:30' );
		$end_time   = $this->get_setting( 'delivery_end_time', '17:30' );

		$slots = array();

		// Get WordPress timezone
		$timezone = wp_timezone();

		// Create DateTime objects for today with the configured times
		$today = new DateTime( 'today', $timezone );
		$start = DateTime::createFromFormat( 'H:i', $start_time, $timezone );
		$end   = DateTime::createFromFormat( 'H:i', $end_time, $timezone );

		if ( false === $start || false === $end ) {
			return $slots; // Return empty array if invalid times
		}

		// Set the date to today for start and end times
		$start->setDate( $today->format( 'Y' ), $today->format( 'm' ), $today->format( 'd' ) );
		$end->setDate( $today->format( 'Y' ), $today->format( 'm' ), $today->format( 'd' ) );

		// Get current time in the same timezone
		$now = new DateTime( 'now', $timezone );

		// Add 30 minutes until we reach or exceed end time
		$current = clone $start;
		while ( $current < $end ) {
			$slot_end = clone $current;
			$slot_end->modify( '+30 minutes' );

			// Don't create a slot if it would go beyond the end time
			if ( $slot_end > $end ) {
				break;
			}

			// Calculate the cutoff time (20 minutes into the slot, or 10 minutes before slot ends)
			$slot_cutoff = clone $current;
			$slot_cutoff->modify( '+20 minutes' );

			// Only include slots where current time hasn't passed the 20-minute cutoff
			if ( $now < $slot_cutoff ) {
				// Create unix timestamp key
				$timestamp = $current->getTimestamp();

				// Create formatted value: 'Tuesday 24th Jun from 9:30am to 10:30am'
				$day_name = $current->format( 'l' ); // Tuesday
				$day_number = $current->format( 'jS' ); // 24th
				$month = $current->format( 'M' ); // Jun
				$start_time_formatted = $current->format( 'g:ia' ); // 9:30am
				$end_time_formatted = $slot_end->format( 'g:ia' ); // 10:00am

				$value = sprintf( '%s %s %s from %s to %s', $day_name, $day_number, $month, $start_time_formatted, $end_time_formatted );

				$slots[ $timestamp ] = $value;
			}

			$current->modify( '+30 minutes' );
		}

		return $slots;
	}

	/**
	 * Get formatted delivery time slots for display
	 *
	 * Returns an array of time slots with both 24-hour and 12-hour formats
	 * suitable for display in dropdowns or other UI elements.
	 *
	 * @since    1.0.0
	 * @return   array    Array of arrays with 'value' (24h) and 'label' (12h) format times
	 */
	public function get_formatted_delivery_slots() {
		$slots = $this->get_delivery_time_slots();
		
		// die early if no slots are found
		if ( empty( $slots ) ) {
			return array();
		}
		
		return apply_filters( 'pizzapilot_time_slots', $slots );
	}
}
