/**
 * Choosing a chapter's audio file on the chapter editing screen.
 *
 * The media frame is restricted to audio, and the running time is filled in
 * from whatever the file says, so nobody has to count seconds by hand. Both
 * are conveniences: the server checks the type and reads the length again on
 * save, because a hidden field carries an ID and an ID proves nothing.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const root = document.getElementById( 'volumina-audio-field' );

		if ( ! root || ! window.wp || ! window.wp.media ) {
			return;
		}

		const strings = window.voluminaChapterAudio || {};

		const input = root.querySelector( '[data-volumina-audio-input]' );
		const name = root.querySelector( '[data-volumina-audio-name]' );
		const select = root.querySelector( '[data-volumina-audio-select]' );
		const clear = root.querySelector( '[data-volumina-audio-clear]' );

		if ( ! input || ! name || ! select || ! clear ) {
			return;
		}

		// These two live outside the field, in the running time row.
		const duration = document.querySelector(
			'[data-volumina-audio-duration]'
		);
		const readable = document.querySelector(
			'[data-volumina-audio-readable]'
		);

		let frame = null;

		function format( seconds ) {
			const whole = Math.max( 0, Math.floor( seconds ) );
			const hours = Math.floor( whole / 3600 );
			const minutes = Math.floor( ( whole % 3600 ) / 60 );
			const rest = whole % 60;
			const pad = function ( value ) {
				return value < 10 ? '0' + value : String( value );
			};

			if ( hours > 0 ) {
				return hours + ':' + pad( minutes ) + ':' + pad( rest );
			}

			return minutes + ':' + pad( rest );
		}

		function show( attachment ) {
			// WordPress reports the length on the attachment as fileLength,
			// and older records keep it under meta.length instead.
			let reported = 0;

			if ( attachment.fileLength ) {
				reported = parseFloat( attachment.fileLength );
			} else if ( attachment.meta && attachment.meta.length ) {
				reported = parseFloat( attachment.meta.length );
			}

			const seconds = Math.floor( reported || 0 );

			input.value = attachment.id;

			const label = attachment.filename || attachment.title || '';

			name.textContent =
				seconds > 0 ? label + ' (' + format( seconds ) + ')' : label;

			// An existing running time is left alone: it may have been
			// corrected by hand because the file itself is wrong.
			if ( duration && seconds > 0 && ! parseInt( duration.value, 10 ) ) {
				duration.value = seconds;

				if ( readable ) {
					readable.textContent = format( seconds );
				}
			}
		}

		select.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					title: strings.frameTitle,
					button: { text: strings.frameButton },
					library: { type: 'audio' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					show( frame.state().get( 'selection' ).first().toJSON() );
				} );
			}

			frame.open();
		} );

		clear.addEventListener( 'click', function () {
			input.value = '0';
			name.textContent = strings.noFile || '';
		} );

		if ( duration && readable ) {
			duration.addEventListener( 'input', function () {
				const seconds = parseInt( duration.value, 10 );

				readable.textContent = seconds > 0 ? format( seconds ) : '';
			} );
		}
	} );
} )();
