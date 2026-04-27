/**
 * PizzaPilot delivery-checker block — editor entry.
 *
 * Renders a server-side preview in the editor so the block matches its
 * front-end output without duplicating markup. No build step is required:
 * everything is plain ES5 + wp.element.createElement.
 *
 * @package Pizzapilot
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.serverSideRender ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor && wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;
	var createElement = wp.element.createElement;
	var __ = wp.i18n && wp.i18n.__ ? wp.i18n.__ : function ( s ) { return s; };

	registerBlockType( 'pizzapilot/delivery-checker', {
		edit: function () {
			var blockProps = useBlockProps
				? useBlockProps( { className: 'pizzapilot-delivery-checker__editor-wrap' } )
				: { className: 'pizzapilot-delivery-checker__editor-wrap' };

			return createElement(
				'div',
				blockProps,
				createElement( ServerSideRender, {
					block: 'pizzapilot/delivery-checker',
					EmptyResponsePlaceholder: function () {
						return createElement(
							'p',
							null,
							__( 'Delivery postcode checker preview unavailable.', 'pizzapilot' )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp ) );
