/**
 * The Appearance tab's preview: the newest audiobook, restyled as the form
 * changes. Template, cover and accent are Volumina's; an extension listens
 * for `volumina:appearance-change` to add its own. See docs/api.md.
 */
( function () {
	'use strict';

	const config = window.voluminaAppearance || { palette: {}, defaults: {} };
	const OPTION = 'volumina_settings';

	function checked( form, key ) {
		const input = form.querySelector(
			'[name="' + OPTION + '[' + key + ']"]:checked'
		);
		return input ? input.value : '';
	}

	// Same rule as Color::normalize() in PHP: `#rrggbb`, lower case, or ''.
	function normalize( value ) {
		const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(
			( value || '' ).trim()
		);
		if ( ! m ) {
			return '';
		}
		const hex =
			3 === m[ 1 ].length ? m[ 1 ].replace( /./g, '$&$&' ) : m[ 1 ];
		return '#' + hex.toLowerCase();
	}

	// The accent field: a select of named colours, or "custom" and a hex.
	function accentOf( form, fallback ) {
		const select = form.querySelector(
			'[name="' + OPTION + '[accent][choice]"]'
		);
		const choice = select ? select.value : '';

		if ( 'custom' === choice ) {
			const custom = form.querySelector(
				'[name="' + OPTION + '[accent][custom]"]'
			);
			return normalize( custom ? custom.value : '' );
		}

		const slug = choice || fallback || '';
		return 'none' === slug ? '' : config.palette[ slug ] || '';
	}

	function channel( value ) {
		const c = value / 255;
		return c <= 0.03928
			? c / 12.92
			: Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	}

	function luminance( hex ) {
		const r = parseInt( hex.slice( 1, 3 ), 16 );
		const g = parseInt( hex.slice( 3, 5 ), 16 );
		const b = parseInt( hex.slice( 5, 7 ), 16 );
		return (
			0.2126 * channel( r ) +
			0.7152 * channel( g ) +
			0.0722 * channel( b )
		);
	}

	function contrast( a, b ) {
		const x = luminance( a ),
			y = luminance( b );
		return ( Math.max( x, y ) + 0.05 ) / ( Math.min( x, y ) + 0.05 );
	}

	// Same rule as Color::ink() in PHP: whichever of black and white reads more.
	function ink( hex ) {
		return contrast( hex, '#000000' ) >= contrast( hex, '#ffffff' )
			? '#000000'
			: '#ffffff';
	}

	function swap( el, prefix, value ) {
		Array.prototype.slice.call( el.classList ).forEach( function ( name ) {
			if ( 0 === name.indexOf( prefix ) ) {
				el.classList.remove( name );
			}
		} );
		if ( value ) {
			el.classList.add( prefix + value );
		}
	}

	function update() {
		const form = document.querySelector( '.volumina-appearance-form' );
		const book = document.querySelector(
			'.volumina-appearance-preview .volumina-book'
		);

		if ( ! form || ! book ) {
			return;
		}

		// The template field is not drawn when there is only one template.
		const template =
			checked( form, 'template' ) ||
			( book.className.match( /volumina-template-([a-z0-9-]+)/ ) ||
				[] )[ 1 ] ||
			'basic';
		const defaults = config.defaults[ template ] || {};
		const cover =
			checked( form, 'cover_position' ) || defaults.cover || 'side';
		const accent = accentOf( form, defaults.accent );
		const accentInk = accent ? ink( accent ) : '';

		swap( book, 'volumina-template-', template );
		swap( book, 'volumina-cover-', cover );
		book.classList.toggle( 'volumina-has-accent', '' !== accent );
		book.style.setProperty( '--volumina-accent', accent );
		book.style.setProperty( '--volumina-accent-ink', accentInk );
		if ( ! accent ) {
			book.style.removeProperty( '--volumina-accent' );
			book.style.removeProperty( '--volumina-accent-ink' );
		}

		document.dispatchEvent(
			new CustomEvent( 'volumina:appearance-change', {
				detail: {
					template,
					cover,
					accent,
					ink: accentInk,
				},
			} )
		);
	}

	function togglePlaying( button ) {
		const player = document.querySelector(
			'.volumina-appearance-preview .volumina-player'
		);
		if ( ! player ) {
			return;
		}
		const playing =
			'playing' !== player.getAttribute( 'data-volumina-state' );
		player.setAttribute(
			'data-volumina-state',
			playing ? 'playing' : 'paused'
		);
		button.setAttribute( 'aria-pressed', playing ? 'true' : 'false' );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		const form = document.querySelector( '.volumina-appearance-form' );
		const button = document.querySelector(
			'[data-volumina-preview-toggle]'
		);

		if ( form ) {
			form.addEventListener( 'change', update );
			// A hex typed by hand restyles as it is typed.
			form.addEventListener( 'input', update );
		}
		if ( button ) {
			button.addEventListener( 'click', function () {
				togglePlaying( button );
			} );
		}
		update();
	} );
} )();
