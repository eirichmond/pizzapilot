(function( $ ) {
	'use strict';

	/**
	 * Postcode delivery radius checker.
	 *
	 * Checks if customer's postcode is within delivery radius
	 * and shows appropriate message.
	 */
	$(document).ready(function() {
		var $messageContainer = null;
		var checkTimeout = null;

		// Create message container if it doesn't exist
		function createMessageContainer() {
			if ($messageContainer) {
				return;
			}

			// Try to find shipping postcode field first, then billing
			var $targetField = $('#shipping_postcode_field');
			if (!$targetField.length) {
				$targetField = $('#billing_postcode_field');
			}

			if ($targetField.length) {
				$messageContainer = $('<div id="pizzapilot-postcode-message" class="woocommerce-info" style="display:none;"></div>');
				$targetField.after($messageContainer);
			}
		}

		// Get current postcode value
		function getCurrentPostcode() {
			return $('#shipping_postcode').val() || $('#billing_postcode').val();
		}

		// Get current delivery type
		function getCurrentDeliveryType() {
			return $('select[name="pizzapilot/delivery-type"]').val();
		}

		// Check postcode via AJAX
		function checkPostcode(postcode) {
			if (!postcode || postcode.length < 3) {
				hideMessage();
				return;
			}

			// Show loading message
			showMessage('Checking delivery availability...', 'loading');

			$.ajax({
				url: pizzapilotPublic.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pizzapilot_check_postcode',
					nonce: pizzapilotPublic.nonce,
					postcode: postcode
				},
				success: function(response) {
					if (response.success && response.data) {
						if (response.data.eligible) {
							showMessage(response.data.message, 'success');
						} else {
							showMessage(response.data.message, 'error');
						}
					} else {
						showMessage(response.data.message || 'Error checking postcode', 'error');
					}
				},
				error: function() {
					showMessage('Unable to check delivery availability. Please try again.', 'error');
				}
			});
		}

		// Show message
		function showMessage(message, type) {
			createMessageContainer();
			if ($messageContainer) {
				$messageContainer.removeClass('woocommerce-info woocommerce-message woocommerce-error');

				if (type === 'success') {
					$messageContainer.addClass('woocommerce-message');
				} else if (type === 'error') {
					$messageContainer.addClass('woocommerce-error');
				} else {
					$messageContainer.addClass('woocommerce-info');
				}

				$messageContainer.html(message).fadeIn();
			}
		}

		// Hide message
		function hideMessage() {
			if ($messageContainer) {
				$messageContainer.fadeOut();
			}
		}

		// Handle postcode change with debounce
		function handlePostcodeChange(postcode) {
			var deliveryType = getCurrentDeliveryType();

			// Only check if delivery is selected
			if (deliveryType !== 'delivery') {
				hideMessage();
				return;
			}

			// Clear previous timeout
			if (checkTimeout) {
				clearTimeout(checkTimeout);
			}

			// Set new timeout (debounce)
			checkTimeout = setTimeout(function() {
				checkPostcode(postcode);
			}, 500);
		}

		// Attach event handlers using event delegation for dynamic content
		$(document.body).on('input change blur', '#shipping_postcode, #billing_postcode', function() {
			handlePostcodeChange($(this).val());
		});

		// Check postcode when delivery type changes
		$(document.body).on('change', 'select[name="pizzapilot/delivery-type"]', function() {
			var deliveryType = $(this).val();

			if (deliveryType === 'delivery') {
				var postcode = getCurrentPostcode();
				if (postcode) {
					checkPostcode(postcode);
				}
			} else {
				hideMessage();
			}
		});

		// Re-check when checkout updates (for WooCommerce Blocks and traditional checkout)
		$(document.body).on('updated_checkout', function() {
			if (getCurrentDeliveryType() === 'delivery') {
				var postcode = getCurrentPostcode();
				if (postcode) {
					checkPostcode(postcode);
				}
			}
		});

		// Handle WooCommerce Blocks checkout update events
		$(document.body).on('checkout_error', function() {
			// Re-check on validation errors
			if (getCurrentDeliveryType() === 'delivery') {
				var postcode = getCurrentPostcode();
				if (postcode) {
					checkPostcode(postcode);
				}
			}
		});

		// Initial check on page load
		setTimeout(function() {
			if (getCurrentDeliveryType() === 'delivery') {
				var initialPostcode = getCurrentPostcode();
				if (initialPostcode) {
					checkPostcode(initialPostcode);
				}
			}
		}, 500);
	});

})( jQuery );
