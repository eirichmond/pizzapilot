<?php
/**
 * Delivery radius checker functionality.
 *
 * Handles postcode validation and delivery eligibility checking
 * based on configured delivery radius using geocoding via postcodes.io
 * and Haversine distance calculation.
 *
 * @link       https://elliottrichmond.co.uk
 * @since      1.0.0
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 */

/**
 * Delivery radius checker class.
 *
 * Validates customer postcodes against configured delivery radius
 * using geocoding and Haversine formula.
 *
 * Default geocoding uses postcodes.io (UK postcodes, free, no API key).
 * The geocoding provider can be swapped via the 'pizzapilot_geocode_postcode'
 * filter, allowing Pro or third-party plugins to use Google Maps or other
 * global geocoding services.
 *
 * @package    Pizzapilot
 * @subpackage Pizzapilot/includes
 * @author     Elliott Richmond <elliott@squareonemd.co.uk>
 */
class Pizzapilot_Delivery_Checker {

	/**
	 * Initialize the delivery checker.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// AJAX handler for postcode checking (logged in and logged out users)
		add_action( 'wp_ajax_pizzapilot_check_postcode', array( $this, 'ajax_check_delivery_eligibility' ) );
		add_action( 'wp_ajax_nopriv_pizzapilot_check_postcode', array( $this, 'ajax_check_delivery_eligibility' ) );

		// Checkout validation - Traditional/Shortcode checkout
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_delivery_eligibility' ), 10, 2 );

		// Checkout validation - Block checkout (Store API)
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'validate_delivery_eligibility_block' ), 10, 2 );
	}

	/**
	 * AJAX handler to check if postcode is within delivery radius.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function ajax_check_delivery_eligibility() {
		// Verify nonce
		check_ajax_referer( 'pizzapilot_postcode_check', 'nonce' );

		// Get and sanitize postcode
		$customer_postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';

		if ( empty( $customer_postcode ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter a postcode.', 'pizzapilot' ),
				)
			);
		}

		// Get store settings
		$settings       = get_option( 'pizzapilot_delivery_settings', array() );
		$store_postcode = isset( $settings['delivery_postcode'] ) ? $settings['delivery_postcode'] : '';
		$max_radius     = isset( $settings['delivery_radius'] ) ? absint( $settings['delivery_radius'] ) : 5;
		$radius_unit    = isset( $settings['radius_unit'] ) ? $settings['radius_unit'] : 'km';

		if ( empty( $store_postcode ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Delivery postcode not configured. Please contact the store.', 'pizzapilot' ),
				)
			);
		}

		// Calculate distance using geocoding
		$distance = $this->calculate_postcode_distance( $store_postcode, $customer_postcode, $radius_unit );

		if ( false === $distance ) {
			wp_send_json_error(
				array(
					'message' => __( 'We could not verify your postcode. Please check it and try again.', 'pizzapilot' ),
				)
			);
		}

		// Check if within radius
		if ( $distance <= $max_radius ) {
			wp_send_json_success(
				array(
					'eligible' => true,
					'distance' => $distance,
					'unit'     => $radius_unit,
					'message'  => sprintf(
					/* translators: 1: distance, 2: unit (km/miles) */
						__( 'Great! We deliver to your area (approximately %1$s %2$s away).', 'pizzapilot' ),
						number_format( $distance, 1 ),
						$radius_unit
					),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'eligible' => false,
					'distance' => $distance,
					'unit'     => $radius_unit,
					'message'  => sprintf(
					/* translators: 1: distance, 2: unit (km/miles) */
						__( 'Sorry, your postcode is outside our delivery area (approximately %1$s %2$s away). Please select "Collection" instead.', 'pizzapilot' ),
						number_format( $distance, 1 ),
						$radius_unit
					),
				)
			);
		}
	}

	/**
	 * Geocode a postcode to coordinates.
	 *
	 * Uses the 'pizzapilot_geocode_postcode' filter to allow alternative
	 * geocoding providers (e.g. Google Maps in Pro). Falls back to
	 * postcodes.io (UK postcodes, free, no API key required).
	 *
	 * Results are cached using WordPress transients to reduce API calls.
	 *
	 * @since    1.0.0
	 * @param    string    $postcode    The postcode to geocode.
	 * @return   array|false            Array with 'latitude' and 'longitude' keys, or false on failure.
	 */
	private function geocode_postcode( $postcode ) {
		// Normalize postcode for cache key
		$normalized = strtoupper( str_replace( ' ', '', $postcode ) );
		$cache_key  = 'pizzapilot_geo_' . md5( $normalized );

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		/**
		 * Filter the geocoding result for a postcode.
		 *
		 * Allows Pro or third-party plugins to replace the default
		 * postcodes.io geocoder with an alternative provider (e.g.
		 * Google Maps Geocoding API for global support).
		 *
		 * Return an array with 'latitude' and 'longitude' keys to
		 * override the default. Return null to use the default
		 * postcodes.io lookup.
		 *
		 * @since 1.0.0
		 * @param array|null $coords   Coordinates array or null to use default.
		 * @param string     $postcode The postcode being geocoded.
		 */
		$coords = apply_filters( 'pizzapilot_geocode_postcode', null, $postcode );

		if ( null === $coords ) {
			$coords = $this->geocode_postcode_via_postcodes_io( $postcode );
		}

		if ( false === $coords || ! is_array( $coords ) ) {
			return false;
		}

		// Cache for 30 days — postcodes don't move
		set_transient( $cache_key, $coords, 30 * DAY_IN_SECONDS );

		return $coords;
	}

	/**
	 * Geocode a postcode using the postcodes.io API (default provider).
	 *
	 * Free, no API key required. Supports UK postcodes only.
	 *
	 * @since    1.0.0
	 * @param    string    $postcode    The postcode to geocode.
	 * @return   array|false            Array with 'latitude' and 'longitude' keys, or false on failure.
	 */
	private function geocode_postcode_via_postcodes_io( $postcode ) {
		$url      = 'https://api.postcodes.io/postcodes/' . rawurlencode( $postcode );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['status'] ) || 200 !== $body['status'] || empty( $body['result'] ) ) {
			return false;
		}

		return array(
			'latitude'  => floatval( $body['result']['latitude'] ),
			'longitude' => floatval( $body['result']['longitude'] ),
		);
	}

	/**
	 * Calculate the distance between two points using the Haversine formula.
	 *
	 * @since    1.0.0
	 * @param    float     $lat1    Latitude of point 1.
	 * @param    float     $lon1    Longitude of point 1.
	 * @param    float     $lat2    Latitude of point 2.
	 * @param    float     $lon2    Longitude of point 2.
	 * @param    string    $unit    Unit of measurement: 'km', 'miles', or 'N' (nautical).
	 * @return   float              Distance in the specified unit.
	 */
	private function haversine_distance( $lat1, $lon1, $lat2, $lon2, $unit = 'km' ) {
		if ( ( $lat1 === $lat2 ) && ( $lon1 === $lon2 ) ) {
			return 0.0;
		}

		$theta = $lon1 - $lon2;
		$dist  = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) )
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
		$dist  = acos( $dist );
		$dist  = rad2deg( $dist );
		$miles = $dist * 60 * 1.1515;

		if ( 'km' === $unit ) {
			return $miles * 1.609344;
		}

		return $miles;
	}

	/**
	 * Calculate distance between two postcodes using geocoding and Haversine.
	 *
	 * Geocodes both postcodes via postcodes.io, then calculates the
	 * real-world distance using the Haversine formula.
	 *
	 * @since    1.0.0
	 * @param    string    $postcode1    Store postcode.
	 * @param    string    $postcode2    Customer postcode.
	 * @param    string    $unit         Unit of measurement: 'km' or 'miles'.
	 * @return   float|false             Distance in the specified unit, or false on failure.
	 */
	private function calculate_postcode_distance( $postcode1, $postcode2, $unit = 'km' ) {
		$coords1 = $this->geocode_postcode( $postcode1 );
		$coords2 = $this->geocode_postcode( $postcode2 );

		if ( false === $coords1 || false === $coords2 ) {
			return false;
		}

		return $this->haversine_distance(
			$coords1['latitude'],
			$coords1['longitude'],
			$coords2['latitude'],
			$coords2['longitude'],
			$unit
		);
	}

	/**
	 * Validate delivery eligibility at checkout.
	 *
	 * Blocks checkout if "Delivery" is selected but postcode is out of range.
	 *
	 * @since    1.0.0
	 * @param    array     $data     Checkout data.
	 * @param    WP_Error  $errors   Validation errors object.
	 * @return   void
	 */
	public function validate_delivery_eligibility( $data, $errors ) {
		// Get delivery type from checkout fields
		$delivery_type = isset( $data['pizzapilot/delivery-type'] ) ? $data['pizzapilot/delivery-type'] : '';

		// Only validate if "Delivery" is selected
		if ( 'delivery' !== $delivery_type ) {
			return;
		}

		// Get customer postcode (try shipping first, then billing)
		$customer_postcode = ! empty( $data['shipping_postcode'] ) ? $data['shipping_postcode'] : $data['billing_postcode'];

		if ( empty( $customer_postcode ) ) {
			$errors->add( 'postcode_required', __( 'Postcode is required for delivery orders.', 'pizzapilot' ) );
			return;
		}

		// Get store settings
		$settings       = get_option( 'pizzapilot_delivery_settings', array() );
		$store_postcode = isset( $settings['delivery_postcode'] ) ? $settings['delivery_postcode'] : '';
		$max_radius     = isset( $settings['delivery_radius'] ) ? absint( $settings['delivery_radius'] ) : 5;
		$radius_unit    = isset( $settings['radius_unit'] ) ? $settings['radius_unit'] : 'km';

		if ( empty( $store_postcode ) ) {
			$errors->add( 'delivery_not_configured', __( 'Delivery is not available at this time. Please contact the store.', 'pizzapilot' ) );
			return;
		}

		// Calculate distance using geocoding
		$distance = $this->calculate_postcode_distance( $store_postcode, $customer_postcode, $radius_unit );

		if ( false === $distance ) {
			$errors->add(
				'postcode_lookup_failed',
				__( 'We could not verify your postcode. Please check it and try again.', 'pizzapilot' )
			);
			return;
		}

		// Check if within radius
		if ( $distance > $max_radius ) {
			$errors->add(
				'postcode_out_of_range',
				sprintf(
					/* translators: %s: postcode */
					__( 'Sorry, we cannot deliver to postcode %s. Please select "Collection" instead.', 'pizzapilot' ),
					esc_html( $customer_postcode )
				)
			);
		}
	}

	/**
	 * Validate delivery eligibility for block-based checkout.
	 *
	 * Blocks checkout if "Delivery" is selected but postcode is out of range.
	 * This method is for the new WooCommerce Blocks checkout.
	 *
	 * @since    1.0.0
	 * @param    WC_Order  $order    The order object.
	 * @param    object    $request  The request object from Store API.
	 * @return   void
	 * @throws   Exception  If validation fails, throws exception to block checkout.
	 */
	public function validate_delivery_eligibility_block( $order, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $request required by WC action signature.
		// Get delivery type from order meta
		$delivery_type = $order->get_meta( '_wc_other/pizzapilot/delivery-type', true );

		// Only validate if "Delivery" is selected
		if ( 'delivery' !== $delivery_type ) {
			return;
		}

		// Get customer postcode (try shipping first, then billing)
		$customer_postcode = $order->get_shipping_postcode();
		if ( empty( $customer_postcode ) ) {
			$customer_postcode = $order->get_billing_postcode();
		}

		if ( empty( $customer_postcode ) ) {
			throw new Exception(
				esc_html__( 'Postcode is required for delivery orders.', 'pizzapilot' )
			);
		}

		// Get store settings
		$settings       = get_option( 'pizzapilot_delivery_settings', array() );
		$store_postcode = isset( $settings['delivery_postcode'] ) ? $settings['delivery_postcode'] : '';
		$max_radius     = isset( $settings['delivery_radius'] ) ? absint( $settings['delivery_radius'] ) : 5;
		$radius_unit    = isset( $settings['radius_unit'] ) ? $settings['radius_unit'] : 'km';

		if ( empty( $store_postcode ) ) {
			throw new Exception(
				esc_html__( 'Delivery is not available at this time. Please contact the store.', 'pizzapilot' )
			);
		}

		// Calculate distance using geocoding
		$distance = $this->calculate_postcode_distance( $store_postcode, $customer_postcode, $radius_unit );

		if ( false === $distance ) {
			throw new Exception(
				esc_html__( 'We could not verify your postcode. Please check it and try again.', 'pizzapilot' )
			);
		}

		// Check if within radius
		if ( $distance > $max_radius ) {
			throw new Exception(
				sprintf(
					/* translators: %s: postcode */
					esc_html__( 'Sorry, we cannot deliver to postcode %s. Please select "Collection" instead.', 'pizzapilot' ),
					esc_html( $customer_postcode )
				)
			);
		}
	}
}
