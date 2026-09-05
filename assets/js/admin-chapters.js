/**
 * Chapter reordering on the audiobook editing screen.
 *
 * Drag and drop for a mouse, arrow buttons for everyone else. No jQuery, no
 * build step. The order is saved as soon as it changes; nothing is lost to a
 * forgotten Save button.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const root = document.getElementById( 'volumina-chapter-list' );

		if ( ! root ) {
			return;
		}

		const strings = window.voluminaChapters || {};

		const list = root.querySelector( '.volumina-chapters' );

		if ( ! list ) {
			return;
		}

		const status = root.querySelector( '.volumina-chapters-status' );

		let dragging = null;

		function announce( message ) {
			if ( ! status ) {
				return;
			}

			// A live region speaks only when its text changes. Clearing it and
			// setting the message on the next frame makes the same message twice
			// in a row audible both times.
			status.textContent = '';

			window.requestAnimationFrame( function () {
				status.textContent = message;
			} );
		}

		function announcePosition( item ) {
			if ( ! strings.moved ) {
				return;
			}

			const items = Array.prototype.slice.call(
				list.querySelectorAll( '.volumina-chapter' )
			);

			announce(
				strings.moved
					.replace( '%1$d', items.indexOf( item ) + 1 )
					.replace( '%2$d', items.length )
			);
		}

		function save() {
			const ids = [];

			list.querySelectorAll( '.volumina-chapter' ).forEach(
				function ( item ) {
					ids.push( item.dataset.id );
				}
			);

			const body = new FormData();
			body.append( 'action', root.dataset.ajaxAction );
			body.append( 'nonce', root.dataset.nonce );
			body.append( 'book_id', root.dataset.book );

			ids.forEach( function ( id ) {
				body.append( 'order[]', id );
			} );

			window
				.fetch( strings.ajaxUrl || window.ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body,
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( result && result.success ) {
						announce( result.data.message );
						return;
					}

					announce(
						result && result.data && result.data.message
							? result.data.message
							: strings.error
					);
				} )
				.catch( function () {
					announce( strings.error );
				} );
		}

		list.addEventListener( 'dragstart', function ( event ) {
			const item = event.target.closest( '.volumina-chapter' );

			if ( ! item ) {
				return;
			}

			dragging = item;
			item.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			// Firefox will not start a drag without data on the transfer.
			event.dataTransfer.setData( 'text/plain', item.dataset.id );
		} );

		list.addEventListener( 'dragover', function ( event ) {
			if ( ! dragging ) {
				return;
			}

			event.preventDefault();
			event.dataTransfer.dropEffect = 'move';

			const over = event.target.closest( '.volumina-chapter' );

			if ( ! over || over === dragging ) {
				return;
			}

			const box = over.getBoundingClientRect();
			const below = event.clientY > box.top + box.height / 2;

			list.insertBefore( dragging, below ? over.nextSibling : over );
		} );

		list.addEventListener( 'drop', function ( event ) {
			event.preventDefault();
		} );

		list.addEventListener( 'dragend', function () {
			if ( ! dragging ) {
				return;
			}

			dragging.classList.remove( 'is-dragging' );
			dragging = null;
			save();
		} );

		list.addEventListener( 'click', function ( event ) {
			const button = event.target.closest( '[data-move]' );

			if ( ! button ) {
				return;
			}

			event.preventDefault();

			const item = button.closest( '.volumina-chapter' );

			if ( ! item ) {
				return;
			}

			if ( 'up' === button.dataset.move && item.previousElementSibling ) {
				list.insertBefore( item, item.previousElementSibling );
			} else if (
				'down' === button.dataset.move &&
				item.nextElementSibling
			) {
				list.insertBefore( item.nextElementSibling, item );
			} else {
				return;
			}

			// Keep focus with the chapter that just moved, not with the row
			// that happens to have taken its place.
			button.focus();
			announcePosition( item );
			save();
		} );
	} );
} )();
