/**
 * Cover picker for the audiobook editing screen.
 *
 * Uses the media frame WordPress already ships. No jQuery, no build step.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.getElementById( 'volumina-cover-field' );

		if ( ! root || ! window.wp || ! window.wp.media ) {
			return;
		}

		var input = root.querySelector( '[data-volumina-cover-input]' );
		var preview = root.querySelector( '[data-volumina-cover-preview]' );
		var select = root.querySelector( '[data-volumina-cover-select]' );
		var clear = root.querySelector( '[data-volumina-cover-clear]' );
		var frame = null;

		if ( ! input || ! preview || ! select || ! clear ) {
			return;
		}

		function show( attachment ) {
			var url = attachment.url;

			if ( attachment.sizes && attachment.sizes.medium ) {
				url = attachment.sizes.medium.url;
			}

			preview.textContent = '';

			var image = document.createElement( 'img' );
			image.src = url;
			image.alt = attachment.alt || '';
			image.style.maxWidth = '200px';
			image.style.height = 'auto';
			preview.appendChild( image );
		}

		select.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = window.wp.media( {
					title: root.dataset.frameTitle,
					button: { text: root.dataset.frameButton },
					library: { type: 'image' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();

					input.value = attachment.id;
					show( attachment );
				} );
			}

			frame.open();
		} );

		clear.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			input.value = '0';
			preview.textContent = '';
		} );
	} );
}() );
