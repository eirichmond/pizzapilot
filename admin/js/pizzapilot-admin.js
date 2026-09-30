(function( $ ) {
	'use strict';

	/**
	 * Hide PizzaPilot delivery info from the order screen's address display.
	 *
	 * WooCommerce prints the additional checkout fields as "Delivery Options:"
	 * and "Delivery Time:" paragraphs inside the address columns. PizzaPilot
	 * shows the same data in its own meta box, so these duplicates are hidden.
	 * The paragraphs carry no identifying class, so they can only be matched
	 * on their text content.
	 *
	 * Only enqueued on the WooCommerce order edit screens (legacy and HPOS).
	 */
	$( function() {
		$( '.order_data_column .address p' ).each( function() {
			var text = $( this ).text();

			if ( text.indexOf( 'Delivery Options:' ) !== -1 || text.indexOf( 'Delivery Time:' ) !== -1 ) {
				$( this ).hide();
			}
		} );
	} );

})( jQuery );
