/**
 * The color_select settings field (Support\Settings\Field): WordPress's colour
 * picker beside the select, always in view, so any colour is one click away.
 * It shows the colour the select stands for; choosing a named colour moves
 * the picker to it, and picking a colour in the picker turns the select to
 * "Custom…". Clearing the picker goes back to the first choice. Without this
 * script the field still works: a select and a plain hex box.
 *
 * Every change is announced as a bubbling `change` on the hex box, so a
 * form listening for changes — the Appearance preview — hears the picker too.
 *
 * @param {Function|undefined} $ jQuery, which WordPress's colour picker needs; the field works without it.
 */
( function ( $ ) {
	'use strict';

	function init( wrap ) {
		const select = wrap.querySelector( 'select' );
		const input = wrap.querySelector( '.volumina-color-picker' );
		if ( ! select || ! input ) {
			return;
		}

		const swatch = wrap.querySelector( '.volumina-color-swatch' );

		const hasPicker = !! ( $ && $.fn && $.fn.wpColorPicker );

		// True while this script moves the picker, so its change is not the user's.
		let syncing = false;

		function announce() {
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		// The colour of the chosen option, `#rrggbb` or ''.
		function named() {
			const option = select.options[ select.selectedIndex ];
			return ( option && option.getAttribute( 'data-color' ) ) || '';
		}

		// Shows a named choice's colour in the picker, or empties it.
		function show( color ) {
			syncing = true;
			if ( hasPicker && color ) {
				$( input ).wpColorPicker( 'color', color );
			} else {
				input.value = color;
				if ( hasPicker ) {
					$( input )
						.closest( '.wp-picker-container' )
						.find( '.wp-color-result' )
						.css( 'background-color', '' );
				}
			}
			syncing = false;
		}

		if ( hasPicker ) {
			// The picker's own button shows the colour; the swatch is for the no-picker case.
			if ( swatch ) {
				swatch.hidden = true;
			}

			$( input ).wpColorPicker( {
				change( event, ui ) {
					input.value = ui.color.toString();
					if ( ! syncing && 'custom' !== select.value ) {
						select.value = 'custom';
					}
					if ( ! syncing ) {
						announce();
					}
				},
				clear() {
					// The picker calls this again on the `change` announce() sends.
					if ( syncing ) {
						return;
					}
					input.value = '';
					if ( 'custom' === select.value ) {
						select.selectedIndex = 0;
						show( named() );
					}
					syncing = true;
					announce();
					syncing = false;
				},
			} );
		}

		select.addEventListener( 'change', function () {
			if ( 'custom' !== select.value ) {
				show( named() );
			}
			if ( swatch && ! hasPicker ) {
				swatch.style.background = named() || 'transparent';
			}
		} );

		// A named choice starts the picker at its colour; a custom one is already there.
		if ( 'custom' !== select.value ) {
			show( named() );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document
			.querySelectorAll( '[data-volumina-color-select]' )
			.forEach( init );
	} );
} )( window.jQuery );
