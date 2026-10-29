/**
 * The listeners box on the audiobook editing screen.
 *
 * Gives a book to an account and takes it back, saved at once. No jQuery, no
 * build step. Every answer is read aloud through the box's live region.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const root = document.getElementById( 'volumina-listeners-app' );

		if ( ! root ) {
			return;
		}

		const strings = window.voluminaListeners || {};
		const list = root.querySelector( '.volumina-listeners' );
		const empty = root.querySelector( '.volumina-listeners-empty' );
		const input = root.querySelector( '#volumina-listener' );
		const give = root.querySelector( '.volumina-listeners-give' );
		const status = root.querySelector( '.volumina-listeners-status' );

		function announce( message ) {
			// A live region speaks only when its text changes; clearing it
			// first makes the same message twice in a row audible both times.
			status.textContent = '';

			window.requestAnimationFrame( function () {
				status.textContent = message;
			} );
		}

		function refreshEmpty() {
			empty.hidden = null !== list.querySelector( '.volumina-listener' );
		}

		function send( action, fields ) {
			const body = new FormData();
			body.append( 'action', action );
			body.append( 'nonce', root.dataset.nonce );
			body.append( 'book_id', root.dataset.book );

			Object.keys( fields ).forEach( function ( key ) {
				body.append( key, fields[ key ] );
			} );

			return window
				.fetch( strings.ajaxUrl || window.ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body,
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( ! result || ! result.data ) {
						throw new Error( 'empty' );
					}

					return result;
				} );
		}

		function giveAccess() {
			const who = input.value.trim();

			give.disabled = true;

			send( root.dataset.give, { who } )
				.then( function ( result ) {
					announce( result.data.message );

					if ( ! result.success ) {
						input.focus();
						return;
					}

					if ( result.data.row ) {
						list.insertAdjacentHTML( 'beforeend', result.data.row );
						refreshEmpty();
					}

					input.value = '';
					input.focus();
				} )
				.catch( function () {
					announce( strings.error );
				} )
				.finally( function () {
					give.disabled = false;
				} );
		}

		give.addEventListener( 'click', giveAccess );

		// Enter inside the box gives access instead of submitting the editor.
		input.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				giveAccess();
			}
		} );

		list.addEventListener( 'click', function ( event ) {
			const button = event.target.closest( '.volumina-listener-remove' );

			if ( ! button ) {
				return;
			}

			const item = button.closest( '.volumina-listener' );

			button.disabled = true;

			send( root.dataset.take, { user_id: item.dataset.user } )
				.then( function ( result ) {
					announce( result.data.message );

					if ( ! result.success ) {
						button.disabled = false;
						return;
					}

					item.remove();
					refreshEmpty();
					input.focus();
				} )
				.catch( function () {
					button.disabled = false;
					announce( strings.error );
				} );
		} );
	} );
} )();
