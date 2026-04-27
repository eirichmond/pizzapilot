/**
 * PizzaPilot — shared postcode-check API helper.
 *
 * Wraps the `pizzapilot_check_postcode` admin-ajax endpoint as a Promise
 * exposed at `window.pizzapilotPostcode.check( postcode )`. Both the
 * checkout-side script (pizzapilot-public.js) and the delivery-checker
 * block's view.js depend on this helper so AJAX wiring lives in one place.
 *
 * The localized `pizzapilotPublic` object (ajaxUrl + nonce) is attached
 * to *this* script handle in PHP, so wherever this script is enqueued
 * the data is available — no separate localization per consumer.
 *
 * Resolves with `{ eligible: boolean, message: string, data: object }`.
 * Rejects on transport errors or a non-success server response.
 *
 * @package Pizzapilot
 */
( function () {
	'use strict';

	if ( window.pizzapilotPostcode && typeof window.pizzapilotPostcode.check === 'function' ) {
		return;
	}

	function getConfig() {
		return ( typeof window !== 'undefined' && window.pizzapilotPublic ) || null;
	}

	function check( postcode ) {
		var config = getConfig();
		if ( ! config || ! config.ajaxUrl || ! config.nonce ) {
			return Promise.reject( new Error( 'PIZZAPILOT_NOT_CONFIGURED' ) );
		}

		var trimmed = ( postcode == null ? '' : String( postcode ) ).trim();
		if ( ! trimmed ) {
			return Promise.reject( new Error( 'EMPTY_POSTCODE' ) );
		}

		var body = 'action=pizzapilot_check_postcode'
			+ '&nonce=' + encodeURIComponent( config.nonce )
			+ '&postcode=' + encodeURIComponent( trimmed );

		return fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'Accept': 'application/json'
			},
			body: body
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'HTTP_' + response.status );
			}
			return response.json();
		} ).then( function ( payload ) {
			var data = payload && payload.data ? payload.data : null;

			if ( ! payload || ! payload.success || ! data ) {
				var err = new Error( ( data && data.message ) || 'CHECK_FAILED' );
				err.serverMessage = data && data.message ? data.message : '';
				err.payload = payload;
				throw err;
			}

			return {
				eligible: !! data.eligible,
				message: data.message || '',
				data: data
			};
		} );
	}

	window.pizzapilotPostcode = { check: check };
}() );
