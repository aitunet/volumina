/**
 * The Appearance tab's preview: the newest audiobook, restyled as the form
 * changes. Template, cover, accent and panel are Volumina's; an extension listens
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

	// A colour field: a hex, or '' for the template's.
	function colorOf( form, key ) {
		const input = form.querySelector(
			'[name="' + OPTION + '[' + key + ']"]'
		);
		return normalize( input ? input.value : '' );
	}

	// The controls' colour: the one chosen, else the template's palette slug.
	function accentOf( form, fallback ) {
		const chosen = colorOf( form, 'accent' );
		if ( chosen ) {
			return chosen;
		}
		const slug = fallback || '';
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

	// Same as Color::mix() in PHP: `color-mix(in srgb, one share, two)`.
	function mix( one, two, share ) {
		let out = '#';
		for ( let i = 1; i < 7; i += 2 ) {
			const a = parseInt( one.slice( i, i + 2 ), 16 );
			const b = parseInt( two.slice( i, i + 2 ), 16 );
			out += Math.round( a * share + b * ( 1 - share ) )
				.toString( 16 )
				.padStart( 2, '0' );
		}
		return out;
	}

	const INKS = [
		[ '#16181d', '#f7f7f5' ],
		[ '#000000', '#ffffff' ],
	];
	const AA = 4.5;

	function points( one, two ) {
		const out = [];
		for ( let i = 0; i <= 20; i++ ) {
			out.push( mix( one, two, i / 20 ) );
		}
		return out;
	}

	function worst( color, list ) {
		return list.reduce( function ( low, point ) {
			return Math.min( low, contrast( color, point ) );
		}, 21 );
	}

	function bestInk( list ) {
		let best = '';
		for ( const pair of INKS ) {
			best =
				worst( pair[ 0 ], list ) >= worst( pair[ 1 ], list )
					? pair[ 0 ]
					: pair[ 1 ];
			if ( worst( best, list ) >= AA ) {
				return best;
			}
		}
		return best;
	}

	// Same as Color::panel() in PHP.
	function panel( start, end ) {
		let first = start;
		let list = [];
		let text = '';
		for ( let step = 20; step >= 0; step-- ) {
			first = mix( start, end, step / 20 );
			list = points( first, end );
			text = bestInk( list );
			if ( worst( text, list ) >= AA ) {
				break;
			}
		}
		let soft = text;
		for ( let fade = 7; fade > 0; fade-- ) {
			const candidate = mix( end, text, fade / 20 );
			if ( worst( candidate, list ) >= AA ) {
				soft = candidate;
				break;
			}
		}
		return {
			start: first,
			end,
			ink: text,
			soft,
			line: mix( end, text, 0.78 ),
		};
	}

	// Same as Templates::panel() in PHP: null when neither colour is chosen.
	function panelOf( form, defaults ) {
		let start = colorOf( form, 'panel_start' );
		let end = colorOf( form, 'panel_end' );
		if ( ! start && ! end ) {
			return null;
		}
		start = start || normalize( defaults.panel_start ) || end;
		end = end || normalize( defaults.panel_end ) || start;
		return panel( start, end );
	}

	const PANEL = [ 'start', 'end', 'ink', 'soft', 'line' ];

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

		const colors = panelOf( form, defaults );
		book.classList.toggle( 'volumina-has-panel', null !== colors );
		PANEL.forEach( function ( part ) {
			if ( colors ) {
				book.style.setProperty(
					'--volumina-panel-' + part,
					colors[ part ]
				);
			} else {
				book.style.removeProperty( '--volumina-panel-' + part );
			}
		} );

		document.dispatchEvent(
			new CustomEvent( 'volumina:appearance-change', {
				detail: {
					template,
					cover,
					accent,
					ink: accentInk,
					panel: colors,
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
