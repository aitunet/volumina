/**
 * The audiobook player.
 *
 * The page arrives with a working native audio element. This script takes the
 * native controls away only once it is certain it can replace them, so a
 * listener whose JavaScript failed to load gets a plain player rather than a
 * dead one.
 *
 * No jQuery, no framework, no build step.
 */
( function () {
	'use strict';

	const settings = window.voluminaPlayer || {};
	const strings = settings.strings || {};
	const SAVE_EVERY = 10000;

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

	/**
	 * Per-browser memory. Wrapped because a browser set to refuse storage
	 * throws on access rather than returning nothing, and a listener who has
	 * blocked cookies should still get a working player.
	 */
	const local = {
		read( key ) {
			try {
				const raw = window.localStorage.getItem( key );
				return raw ? JSON.parse( raw ) : null;
			} catch {
				return null;
			}
		},
		write( key, value ) {
			try {
				window.localStorage.setItem( key, JSON.stringify( value ) );
			} catch {
				// A listener with storage disabled loses their place between
				// visits. Nothing else about the player should suffer for it.
			}
		},
	};

	function setup( root ) {
		const payload = root.querySelector( '[data-volumina-chapters]' );
		const audio = root.querySelector( '[data-volumina-audio]' );

		if ( ! payload || ! audio ) {
			return;
		}

		let data;

		try {
			data = JSON.parse( payload.textContent );
		} catch {
			return;
		}

		const chapters = data.chapters || [];

		if ( ! chapters.length ) {
			return;
		}

		const bookKey = 'volumina:book:' + data.book;
		const speedKey = 'volumina:speed';

		const els = {
			now: root.querySelector( '[data-volumina-now]' ),
			transport: root.querySelector( '[data-volumina-transport]' ),
			seekRow: root.querySelector( '[data-volumina-seek-row]' ),
			seek: root.querySelector( '[data-volumina-seek]' ),
			elapsed: root.querySelector( '[data-volumina-elapsed]' ),
			total: root.querySelector( '[data-volumina-total]' ),
			options: root.querySelector( '[data-volumina-options]' ),
			speed: root.querySelector( '[data-volumina-speed]' ),
			sleep: root.querySelector( '[data-volumina-sleep]' ),
			status: root.querySelector( '.volumina-player-status' ),
			icon: root.querySelector( '[data-volumina-toggle-icon]' ),
			label: root.querySelector( '[data-volumina-toggle-label]' ),
		};

		let index = 0;
		let scrubbing = false;
		let lastSaved = 0;
		let sleepTimer = null;
		let stopAfterChapter = false;

		function announce( message ) {
			if ( ! els.status ) {
				return;
			}

			els.status.textContent = '';
			window.requestAnimationFrame( function () {
				els.status.textContent = message;
			} );
		}

		function current() {
			return chapters[ index ];
		}

		/**
		 * Turns chapter names into buttons, but only the ones this player can
		 * reach. A chapter list can appear on a page with no player at all, and
		 * a button that plays nothing is worse than plain text.
		 */
		function upgradeChapterNames() {
			document
				.querySelectorAll( '[data-volumina-chapter]' )
				.forEach( function ( name ) {
					if ( name.dataset.voluminaUpgraded ) {
						return;
					}

					const id = parseInt( name.dataset.voluminaChapter, 10 );

					if ( ! chapters.some( ( chapter ) => chapter.id === id ) ) {
						return;
					}

					const button = document.createElement( 'button' );
					button.type = 'button';
					button.className = 'volumina-chapter-play';
					button.dataset.voluminaPlay = String( id );
					button.textContent = name.textContent.trim();

					name.textContent = '';
					name.appendChild( button );
					name.dataset.voluminaUpgraded = 'true';
				} );
		}

		function markList() {
			document
				.querySelectorAll( '[data-volumina-play]' )
				.forEach( function ( button ) {
					const id = parseInt( button.dataset.voluminaPlay, 10 );

					if ( ! chapters.some( ( chapter ) => chapter.id === id ) ) {
						return;
					}

					const isCurrent = id === current().id;

					button.setAttribute(
						'aria-current',
						isCurrent ? 'true' : 'false'
					);
					button
						.closest( 'li' )
						?.classList.toggle( 'is-playing', isCurrent );
				} );
		}

		function describePosition() {
			const total = audio.duration || current().duration || 0;
			const spoken = text(
				'position',
				format( audio.currentTime ),
				format( total )
			);

			if ( els.seek ) {
				els.seek.setAttribute( 'aria-valuetext', spoken );
			}
		}

		function paint() {
			const total = audio.duration || current().duration || 0;

			if ( els.seek && ! scrubbing ) {
				els.seek.max = String( Math.max( 1, Math.floor( total ) ) );
				els.seek.value = String( Math.floor( audio.currentTime || 0 ) );
			}

			if ( els.elapsed ) {
				els.elapsed.textContent = format( audio.currentTime );
			}

			if ( els.total ) {
				els.total.textContent = format( total );
			}

			describePosition();
		}

		function paintToggle() {
			const playing = ! audio.paused && ! audio.ended;

			if ( els.icon ) {
				els.icon.textContent = playing ? '⏸' : '▶';
			}

			if ( els.label ) {
				els.label.textContent = playing
					? strings.pause || 'Pause'
					: strings.play || 'Play';
			}
		}

		function remember( playing ) {
			local.write( bookKey, {
				book: data.book,
				title: data.title || '',
				url: data.url || '',
				chapter: current().id,
				chapterTitle: current().title,
				position: Math.floor( audio.currentTime || 0 ),
				playing: !! playing,
				updated: Date.now(),
			} );

			// A guest's Continue listening block has no server to ask, so the
			// browser keeps its own index of the books that have been started.
			const started = local.read( 'volumina:books' ) || { books: [] };

			if ( ! started.books.includes( data.book ) ) {
				started.books.push( data.book );
				local.write( 'volumina:books', started );
			}
		}

		function sync() {
			if ( ! settings.canSync || ! settings.restUrl ) {
				return;
			}

			const body = new window.FormData();
			body.append( 'chapter', current().id );
			body.append( 'position', Math.floor( audio.currentTime || 0 ) );

			window
				.fetch( settings.restUrl + data.book, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': settings.nonce },
					body,
					keepalive: true,
				} )
				.catch( function () {
					// The browser still has it; that is enough to carry on.
				} );
		}

		function save( force ) {
			remember( ! audio.paused && ! audio.ended );

			const now = Date.now();

			if ( ! force && now - lastSaved < SAVE_EVERY ) {
				return;
			}

			lastSaved = now;
			sync();
		}

		function mediaSession() {
			if ( ! ( 'mediaSession' in window.navigator ) ) {
				return;
			}

			try {
				window.navigator.mediaSession.metadata =
					new window.MediaMetadata( {
						title: current().title,
						album: data.title || '',
					} );
			} catch {
				// Metadata is a nicety; the handlers below are the useful part.
			}
		}

		function load( next, position, play ) {
			index = Math.max( 0, Math.min( chapters.length - 1, next ) );

			audio.src = current().src || current().url;
			audio.load();

			const start = Math.max( 0, position || 0 );

			function applyStart() {
				if ( start > 0 && start < ( audio.duration || Infinity ) ) {
					audio.currentTime = start;
				}
				paint();
			}

			// Usually the metadata is still on its way, but a chapter the
			// browser already holds is ready at once, and waiting for an event
			// that has already happened would lose the position silently.
			if ( audio.readyState >= 1 ) {
				applyStart();
			} else {
				audio.addEventListener( 'loadedmetadata', applyStart, {
					once: true,
				} );
			}

			if ( els.now ) {
				els.now.textContent = current().title;
			}

			upgradeChapterNames();
			markList();
			mediaSession();
			paint();

			if ( play ) {
				startPlaying();
			}
		}

		function startPlaying() {
			const attempt = audio.play();

			if ( attempt && typeof attempt.catch === 'function' ) {
				attempt.catch( function ( error ) {
					// Only one kind of refusal is worth telling a listener
					// about: the browser wanting a gesture before it makes a
					// sound. That is correct behaviour, not an error, so say
					// where we are and wait to be asked. An AbortError just
					// means a new chapter replaced this one mid-load, which is
					// the player doing as it was told.
					if ( error && error.name === 'NotAllowedError' ) {
						announce( strings.resumeBlocked || '' );
					}
				} );
			}
		}

		function toggle() {
			if ( audio.paused || audio.ended ) {
				startPlaying();
			} else {
				audio.pause();
			}
		}

		function nudge( seconds ) {
			const total = audio.duration || current().duration || 0;
			audio.currentTime = Math.max(
				0,
				Math.min( total, ( audio.currentTime || 0 ) + seconds )
			);
			paint();
		}

		function go( step ) {
			const next = index + step;

			if ( next < 0 || next >= chapters.length ) {
				return;
			}

			load( next, 0, ! audio.paused );
			announce( text( 'nowPlaying', chapters[ next ].title ) );
			save( true );
		}

		function clearSleep() {
			if ( sleepTimer ) {
				window.clearTimeout( sleepTimer );
				sleepTimer = null;
			}

			stopAfterChapter = false;
		}

		function applySleep( value ) {
			clearSleep();

			if ( 'off' === value ) {
				announce( strings.sleepOff || '' );
				return;
			}

			if ( 'chapter' === value ) {
				stopAfterChapter = true;
				announce( strings.sleepChapter || '' );
				return;
			}

			const minutes = parseInt( value, 10 );

			if ( ! minutes ) {
				return;
			}

			sleepTimer = window.setTimeout( function () {
				audio.pause();
				clearSleep();

				if ( els.sleep ) {
					els.sleep.value = 'off';
				}

				announce( strings.sleepFired || '' );
			}, minutes * 60000 );

			const label = els.sleep
				? els.sleep.options[
						els.sleep.selectedIndex
				  ].textContent.trim()
				: value;

			announce( text( 'sleepSet', label ) );
		}

		// --- Wiring -----------------------------------------------------

		// Only now, with a script that has got this far, is it safe to take
		// the native controls away.
		audio.removeAttribute( 'controls' );
		audio.classList.add( 'volumina-player-audio-hidden' );

		[ els.transport, els.seekRow, els.options ].forEach( function ( el ) {
			if ( el ) {
				el.hidden = false;
			}
		} );

		root.addEventListener( 'click', function ( event ) {
			const button = event.target.closest( '[data-volumina-action]' );

			if ( ! button || ! root.contains( button ) ) {
				return;
			}

			event.preventDefault();

			switch ( button.dataset.voluminaAction ) {
				case 'toggle':
					toggle();
					break;
				case 'back':
					nudge( -( settings.skipBack || 15 ) );
					break;
				case 'forward':
					nudge( settings.skipForward || 30 );
					break;
				case 'previous':
					go( -1 );
					break;
				case 'next':
					go( 1 );
					break;
			}
		} );

		if ( els.seek ) {
			els.seek.addEventListener( 'input', function () {
				scrubbing = true;

				const wanted = parseFloat( els.seek.value );

				if ( els.elapsed ) {
					els.elapsed.textContent = format( wanted );
				}

				if ( els.seek ) {
					els.seek.setAttribute(
						'aria-valuetext',
						text(
							'position',
							format( wanted ),
							format( audio.duration || current().duration || 0 )
						)
					);
				}
			} );

			els.seek.addEventListener( 'change', function () {
				scrubbing = false;
				audio.currentTime = parseFloat( els.seek.value );
			} );
		}

		if ( els.speed ) {
			const remembered = local.read( speedKey );

			if ( remembered && remembered.rate ) {
				els.speed.value = String( remembered.rate );
				audio.playbackRate = parseFloat( remembered.rate );
			}

			els.speed.addEventListener( 'change', function () {
				const rate = parseFloat( els.speed.value );
				audio.playbackRate = rate;
				local.write( speedKey, { rate: els.speed.value } );
				announce( text( 'speedSet', els.speed.value ) );
			} );
		}

		if ( els.sleep ) {
			els.sleep.addEventListener( 'change', function () {
				applySleep( els.sleep.value );
			} );
		}

		audio.addEventListener( 'play', function () {
			paintToggle();
			announce( text( 'nowPlaying', current().title ) );
			save( true );
		} );

		audio.addEventListener( 'pause', function () {
			paintToggle();
			save( true );
		} );

		audio.addEventListener( 'seeked', function () {
			paint();
			save( true );
		} );

		audio.addEventListener( 'timeupdate', function () {
			paint();
			save( false );
		} );

		audio.addEventListener( 'ended', function () {
			if ( stopAfterChapter ) {
				clearSleep();

				if ( els.sleep ) {
					els.sleep.value = 'off';
				}

				announce( strings.sleepFired || '' );
				paintToggle();
				return;
			}

			if ( index + 1 < chapters.length ) {
				load( index + 1, 0, true );
				announce( text( 'nowPlaying', chapters[ index ].title ) );
			} else {
				announce( strings.finished || '' );
				paintToggle();
			}

			save( true );
		} );

		// The chapter list on the page is the chapter navigation.
		document.addEventListener( 'click', function ( event ) {
			const button = event.target.closest( '[data-volumina-play]' );

			if ( ! button ) {
				return;
			}

			const wanted = parseInt( button.dataset.voluminaPlay, 10 );
			const at = chapters.findIndex( function ( chapter ) {
				return chapter.id === wanted;
			} );

			if ( at === -1 ) {
				return;
			}

			event.preventDefault();
			load( at, 0, true );
			announce( text( 'nowPlaying', chapters[ at ].title ) );
		} );

		// Keyboard shortcuts, but never on top of a control that already uses
		// the key: a range input owns its arrows, a button owns its space.
		root.addEventListener( 'keydown', function ( event ) {
			if ( event.altKey || event.ctrlKey || event.metaKey ) {
				return;
			}

			const tag = ( event.target.tagName || '' ).toLowerCase();

			if ( [ 'input', 'select', 'textarea', 'button' ].includes( tag ) ) {
				return;
			}

			switch ( event.key ) {
				case ' ':
				case 'k':
					event.preventDefault();
					toggle();
					break;
				case 'j':
				case 'ArrowLeft':
					event.preventDefault();
					nudge( -( settings.skipBack || 15 ) );
					break;
				case 'l':
				case 'ArrowRight':
					event.preventDefault();
					nudge( settings.skipForward || 30 );
					break;
				case 'p':
					event.preventDefault();
					go( -1 );
					break;
				case 'n':
					event.preventDefault();
					go( 1 );
					break;
			}
		} );

		if ( 'mediaSession' in window.navigator ) {
			const handlers = {
				play: () => startPlaying(),
				pause: () => audio.pause(),
				previoustrack: () => go( -1 ),
				nexttrack: () => go( 1 ),
				seekbackward: () => nudge( -( settings.skipBack || 15 ) ),
				seekforward: () => nudge( settings.skipForward || 30 ),
			};

			Object.keys( handlers ).forEach( function ( action ) {
				try {
					window.navigator.mediaSession.setActionHandler(
						action,
						handlers[ action ]
					);
				} catch {
					// Not every browser offers every action.
				}
			} );
		}

		// Leaving the page is the most likely moment to lose a position, so it
		// is the one moment worth insisting on.
		window.addEventListener( 'pagehide', function () {
			save( true );
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				save( true );
			}
		} );

		// --- Where were we? ---------------------------------------------

		const stored = local.read( bookKey );
		const server = data.resume || { chapter: 0, position: 0 };

		let startAt = 0;
		let startPosition = 0;
		let wasPlaying = false;

		if ( settings.canSync && server.chapter ) {
			// The account is the truth across devices.
			const at = chapters.findIndex( ( c ) => c.id === server.chapter );
			if ( at !== -1 ) {
				startAt = at;
				startPosition = server.position;
			}
		} else if ( stored && stored.chapter ) {
			const at = chapters.findIndex( ( c ) => c.id === stored.chapter );
			if ( at !== -1 ) {
				startAt = at;
				startPosition = stored.position;
				wasPlaying = !! stored.playing;
			}
		}

		load( startAt, startPosition, false );
		paintToggle();

		if ( wasPlaying ) {
			// Whether this is allowed is the browser's call, not ours.
			startPlaying();
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '[data-volumina-player]' ).forEach( setup );
	} );
} )();
