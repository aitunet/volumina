/**
 * The color settings field (Support\Settings\Field): WordPress's colour
 * picker on the hex box, with the field's swatches. Empty means none chosen;
 * the picker's Clear empties it. Without this script the field still works:
 * a plain hex box.
 *
 * Every change is announced as a bubbling `change` on the hex box, so a
 * form listening for changes — the Appearance preview — hears the picker too.
 *
 * @param {Function|undefined} $ jQuery, which WordPress's colour picker needs; the field works without it.
 */
( function ( $ ) {
	'use strict';

	function init( input ) {
		let swatches = [];
		try {
			swatches = JSON.parse(
				input.getAttribute( 'data-swatches' ) || '[]'
			);
		} catch {
			swatches = [];
		}

		// True while announcing, so the picker's reaction to our own event is not taken for the user's.
		let announcing = false;

		function announce() {
			announcing = true;
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			announcing = false;
		}

		$( input ).wpColorPicker( {
			palettes: swatches.length ? swatches : true,
			change( event, ui ) {
				input.value = ui.color.toString();
				if ( ! announcing ) {
					announce();
				}
			},
			clear() {
				// The picker calls this again on the `change` announce() sends.
				if ( announcing ) {
					return;
				}
				input.value = '';
				announce();
			},
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! $ || ! $.fn || ! $.fn.wpColorPicker ) {
			return;
		}
		document.querySelectorAll( '[data-volumina-color]' ).forEach( init );
	} );
} )( window.jQuery );
