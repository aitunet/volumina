/**
 * The color_select settings field (Support\Settings\Field): WordPress's colour
 * picker for "Custom…", shown only while it is chosen, and a swatch that
 * follows the named choice. Without this script the field still works: a
 * select and a plain hex box.
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
		const custom = wrap.querySelector( '.volumina-color-custom' );
		const input = wrap.querySelector( '.volumina-color-picker' );
		const swatch = wrap.querySelector( '.volumina-color-swatch' );

		if ( ! select || ! custom || ! input || ! swatch ) {
			return;
		}

		// The last named colour shown, `#rrggbb` or '': where Custom… starts.
		let lastNamed = '';

		function paint() {
			const isCustom = 'custom' === select.value;
			const option = select.options[ select.selectedIndex ];
			const color =
				( option && option.getAttribute( 'data-color' ) ) || '';

			custom.hidden = ! isCustom;
			// The picker's own button shows a custom colour; the swatch shows a named one.
			swatch.hidden = isCustom;

			if ( ! isCustom ) {
				swatch.style.background = color || 'transparent';
				lastNamed = color;
			}
		}

		function announce() {
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		if ( $ && $.fn && $.fn.wpColorPicker ) {
			$( input ).wpColorPicker( {
				change( event, ui ) {
					input.value = ui.color.toString();
					announce();
				},
				clear() {
					input.value = '';
					announce();
				},
			} );
		}

		select.addEventListener( 'change', function () {
			const from = lastNamed;

			paint();

			// Something to start from: the colour that was showing.
			if ( 'custom' === select.value && '' === input.value && from ) {
				if ( $ && $.fn && $.fn.wpColorPicker ) {
					$( input ).wpColorPicker( 'color', from );
				} else {
					input.value = from;
					announce();
				}
			}
		} );

		paint();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document
			.querySelectorAll( '[data-volumina-color-select]' )
			.forEach( init );
	} );
} )( window.jQuery );
