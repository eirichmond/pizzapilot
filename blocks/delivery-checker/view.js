/**
 * PizzaPilot delivery-checker block — front-end view script.
 *
 * Handles modal open/close, focus management, focus trapping, escape and
 * click-outside dismissal, the reset-to-prompt action shown in the
 * success/failure states, and the postcode submit -> AJAX check ->
 * success/failure state transition.
 *
 * AJAX is delegated to the shared helper exposed by
 * `pizzapilot-postcode-api.js` (registered as a viewScript dependency in
 * block.json) so the same logic backs both the checkout postcode checker
 * and this block.
 *
 * Each block instance is independent: opening one block's modal does not
 * affect any other instance on the page.
 *
 * @package Pizzapilot
 */
( function () {
	'use strict';

	var FOCUSABLE_SELECTOR = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join( ',' );

	var SCROLL_LOCK_CLASS = 'pizzapilot-delivery-checker--scroll-locked';

	function setupBlock( block ) {
		var trigger = block.querySelector( '[data-pizzapilot-open]' );
		var modal = block.querySelector( '.pizzapilot-delivery-checker__modal' );

		if ( ! trigger || ! modal ) {
			return;
		}

		var states = modal.querySelectorAll( '[data-pizzapilot-state]' );
		var closeButtons = modal.querySelectorAll( '[data-pizzapilot-close]' );
		var resetButtons = modal.querySelectorAll( '[data-pizzapilot-reset]' );
		var input = modal.querySelector( 'input[name="postcode"]' );
		var errorEl = modal.querySelector( '[data-pizzapilot-error]' );
		var form = modal.querySelector( '[data-pizzapilot-form]' );
		var submitButton = form ? form.querySelector( 'button[type="submit"]' ) : null;
		var submitOriginalLabel = submitButton ? submitButton.textContent : '';

		var lastFocusedBeforeOpen = null;
		var inFlight = false;

		function showState( name ) {
			Array.prototype.forEach.call( states, function ( state ) {
				state.hidden = state.getAttribute( 'data-pizzapilot-state' ) !== name;
			} );
		}

		function clearError() {
			if ( ! errorEl ) {
				return;
			}
			errorEl.hidden = true;
			errorEl.textContent = '';
		}

		function showError( message ) {
			if ( ! errorEl ) {
				return;
			}
			errorEl.textContent = message;
			errorEl.hidden = false;
		}

		function setLoading( loading ) {
			inFlight = !! loading;
			if ( submitButton ) {
				submitButton.disabled = inFlight;
				submitButton.setAttribute( 'aria-busy', inFlight ? 'true' : 'false' );
			}
			if ( input ) {
				input.disabled = inFlight;
			}
			modal.classList.toggle( 'is-loading', inFlight );
		}

		function resetToPrompt() {
			showState( 'prompt' );
			if ( input ) {
				input.value = '';
			}
			clearError();
			setLoading( false );
			if ( submitButton && submitOriginalLabel ) {
				submitButton.textContent = submitOriginalLabel;
			}
		}

		function handleSubmit( event ) {
			event.preventDefault();
			if ( inFlight ) {
				return;
			}
			clearError();

			var value = input ? input.value : '';
			var trimmed = value ? value.replace( /^\s+|\s+$/g, '' ) : '';
			if ( ! trimmed ) {
				if ( input ) {
					input.focus();
				}
				return;
			}

			if ( ! window.pizzapilotPostcode || typeof window.pizzapilotPostcode.check !== 'function' ) {
				showError( 'Postcode checker is unavailable. Please refresh and try again.' );
				return;
			}

			setLoading( true );

			window.pizzapilotPostcode.check( trimmed ).then( function ( result ) {
				setLoading( false );
				showState( result.eligible ? 'success' : 'failure' );
				focusFirstInModal();
			} ).catch( function ( err ) {
				setLoading( false );
				var message = ( err && err.serverMessage ) ? err.serverMessage : 'Unable to check delivery availability. Please try again.';
				showError( message );
				if ( input ) {
					input.focus();
				}
			} );
		}

		function focusFirstInModal() {
			var focusable = getFocusable();
			var preferred = modal.querySelector(
				'[data-pizzapilot-state]:not([hidden]) input, [data-pizzapilot-state]:not([hidden]) button, [data-pizzapilot-state]:not([hidden]) [tabindex]:not([tabindex="-1"])'
			);
			var target = preferred || focusable[ 0 ] || modal;
			if ( target && typeof target.focus === 'function' ) {
				target.focus();
			}
		}

		function getFocusable() {
			return Array.prototype.filter.call(
				modal.querySelectorAll( FOCUSABLE_SELECTOR ),
				isVisible
			);
		}

		function isVisible( el ) {
			if ( el.hidden ) {
				return false;
			}
			// Walk up — any hidden ancestor inside the modal disqualifies us.
			var node = el;
			while ( node && node !== modal ) {
				if ( node.hidden ) {
					return false;
				}
				node = node.parentNode;
			}
			return el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0;
		}

		function trapFocus( event ) {
			var focusable = getFocusable();
			if ( ! focusable.length ) {
				event.preventDefault();
				return;
			}
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];
			var active = document.activeElement;

			if ( event.shiftKey && ( active === first || ! modal.contains( active ) ) ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				first.focus();
			}
		}

		function onKeyDown( event ) {
			if ( event.key === 'Escape' || event.key === 'Esc' ) {
				event.preventDefault();
				closeModal();
				return;
			}
			if ( event.key === 'Tab' ) {
				trapFocus( event );
			}
		}

		function openModal() {
			if ( ! modal.hidden ) {
				return;
			}
			lastFocusedBeforeOpen = document.activeElement;
			resetToPrompt();
			modal.hidden = false;
			trigger.setAttribute( 'aria-expanded', 'true' );
			document.body.classList.add( SCROLL_LOCK_CLASS );
			document.addEventListener( 'keydown', onKeyDown );
			// Defer focus to ensure the dialog is paint-ready (some browsers
			// otherwise drop the call when the element transitions from hidden).
			window.requestAnimationFrame( focusFirstInModal );
		}

		function closeModal() {
			if ( modal.hidden ) {
				return;
			}
			modal.hidden = true;
			trigger.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( SCROLL_LOCK_CLASS );
			document.removeEventListener( 'keydown', onKeyDown );
			resetToPrompt();
			if ( lastFocusedBeforeOpen && typeof lastFocusedBeforeOpen.focus === 'function' ) {
				lastFocusedBeforeOpen.focus();
			}
			lastFocusedBeforeOpen = null;
		}

		trigger.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			openModal();
		} );

		Array.prototype.forEach.call( closeButtons, function ( btn ) {
			btn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				closeModal();
			} );
		} );

		Array.prototype.forEach.call( resetButtons, function ( btn ) {
			btn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				resetToPrompt();
				if ( input ) {
					input.focus();
				}
			} );
		} );

		if ( form ) {
			form.addEventListener( 'submit', handleSubmit );
		}

		// Expose a small per-block API so the AJAX wiring in Task 16.4 can
		// move the modal between states without re-implementing this logic.
		block.pizzapilotDeliveryChecker = {
			showState: showState,
			resetToPrompt: resetToPrompt,
			close: closeModal,
			focusInput: function () {
				if ( input ) {
					input.focus();
				}
			},
			elements: {
				modal: modal,
				input: input,
				errorEl: errorEl,
				states: states
			}
		};
	}

	function init() {
		var blocks = document.querySelectorAll( '.pizzapilot-delivery-checker' );
		Array.prototype.forEach.call( blocks, setupBlock );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
