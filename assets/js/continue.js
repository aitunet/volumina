/**
 * Continue listening, for a listener with no account.
 *
 * A signed-in listener's list is rendered by the server from the progress
 * table, and this script leaves it alone. A guest's place never leaves their
 * browser, so their list has to be built here — which also means the page can
 * still be cached, because it leaves the server the same for everyone.
 *
 * No jQuery, no framework, no build step.
 */
( function () {
	'use strict';

	const settings = window.voluminaContinue || {};
	const strings = settings.strings || {};

	function text( key, ...values ) {
		let out = strings[ key ] || '';
		values.forEach( function ( value, index ) {
			out = out
				.replace( '%' + ( index + 1 ) + '$s', value )
				.replace( '%s', value );
		} );
		return out;
	}

	function format( seconds ) {
		const whole = Math.max( 0, Math.floor( seconds || 0 ) );
		const hours = Math.floor( whole / 3600 );
		const minutes = Math.floor( ( whole % 3600 ) / 60 );
		const rest = whole % 60;
		const pad = ( n ) => ( n < 10 ? '0' + n : String( n ) );

		return hours > 0
			? hours + ':' + pad( minutes ) + ':' + pad( rest )
			: minutes + ':' + pad( rest );
	}

	function read( key ) {
		try {
			const raw = window.localStorage.getItem( key );
			return raw ? JSON.parse( raw ) : null;
		} catch {
			// A browser set to refuse storage throws rather than returning
			// nothing. A guest who has blocked it simply has no list.
			return null;
		}
	}

	/**
	 * Every book this browser has played, most recently played first.
	 */
	function started() {
		const index = read( 'volumina:books' );
		const ids = index && Array.isArray( index.books ) ? index.books : [];

		return ids
			.map( ( id ) => read( 'volumina:book:' + id ) )
			.filter( ( entry ) => entry && entry.url && entry.title )
			.sort( ( a, b ) => ( b.updated || 0 ) - ( a.updated || 0 ) );
	}

	function item( entry, covers ) {
		const li = document.createElement( 'li' );
		li.className = 'volumina-continue-item';

		if ( covers && entry.cover ) {
			const cover = document.createElement( 'span' );
			cover.className = 'volumina-continue-cover';

			const image = document.createElement( 'img' );
			image.src = entry.cover;
			image.alt = '';
			image.loading = 'lazy';

			cover.appendChild( image );
			li.appendChild( cover );
		}

		const wrap = document.createElement( 'span' );
		wrap.className = 'volumina-continue-text';

		const link = document.createElement( 'a' );
		link.className = 'volumina-continue-title';
		link.href = entry.url;
		link.textContent = entry.title;

		const place = document.createElement( 'span' );
		place.className = 'volumina-continue-place';
		place.textContent = entry.chapterTitle
			? text( 'inChapter', entry.chapterTitle ) +
			  ' ' +
			  text( 'atTime', format( entry.position ) )
			: text( 'atTime', format( entry.position ) );

		wrap.appendChild( link );
		wrap.appendChild( place );
		li.appendChild( wrap );

		return li;
	}

	function fill( root ) {
		if ( root.dataset.voluminaContinue !== 'guest' ) {
			return;
		}

		const list = root.querySelector( '[data-volumina-continue-list]' );

		if ( ! list ) {
			return;
		}

		const count = parseInt( root.dataset.voluminaCount, 10 ) || 3;
		const covers = root.dataset.voluminaCovers === '1';
		const entries = started().slice( 0, count );

		list.textContent = '';

		if ( ! entries.length ) {
			const empty = document.createElement( 'p' );
			empty.className = 'volumina-continue-empty';
			empty.textContent = text( 'empty' );
			root.appendChild( empty );
			return;
		}

		entries.forEach( function ( entry ) {
			list.appendChild( item( entry, covers ) );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( settings.active === false ) {
			return;
		}

		document.querySelectorAll( '[data-volumina-continue]' ).forEach( fill );
	} );
} )();
