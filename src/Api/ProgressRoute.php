<?php
/**
 * REST route for a listener's position in a book.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Api;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Storage\Progress;
use TUNET\Volumina\Support\Registrable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Where a listener had got to, read and written over REST.
 *
 * Only for people who are signed in. A guest's position is kept by their own
 * browser instead: there is no honest way to store it server side without
 * inventing an identifier for someone who has not asked to be identified, and
 * a listening position is not worth a cookie of its own.
 */
final class ProgressRoute implements Registrable {

	/**
	 * REST namespace. Versioned, because it is a promise the player relies on.
	 */
	public const NAMESPACE = 'volumina/v1';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/progress/(?P<book>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'read' ),
					'permission_callback' => array( $this, 'may_use' ),
					'args'                => $this->book_arg(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'write' ),
					'permission_callback' => array( $this, 'may_use' ),
					'args'                => $this->book_arg() + array(
						'chapter'  => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'position' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * The book argument, shared by both methods.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function book_arg(): array {
		return array(
			'book' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Whether the request may touch a listening position at all.
	 *
	 * Being signed in is not enough: the book has to be one this person can
	 * actually read, or the route would confirm that a private book exists to
	 * anyone willing to guess its ID.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return true|WP_Error
	 */
	public function may_use( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'volumina_not_logged_in',
				__( 'Listening positions are only kept for people who are signed in.', 'volumina' ),
				array( 'status' => 401 )
			);
		}

		$book_id = (int) $request['book'];

		if ( Book::POST_TYPE !== get_post_type( $book_id ) ) {
			return new WP_Error(
				'volumina_no_such_book',
				__( 'No such audiobook.', 'volumina' ),
				array( 'status' => 404 )
			);
		}

		if ( 'publish' !== get_post_status( $book_id ) && ! current_user_can( 'read_post', $book_id ) ) {
			return new WP_Error(
				'volumina_no_such_book',
				__( 'No such audiobook.', 'volumina' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	/**
	 * Returns where this listener had got to.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function read( WP_REST_Request $request ): WP_REST_Response {
		$progress = Progress::get( get_current_user_id(), (int) $request['book'] );

		if ( null === $progress ) {
			return new WP_REST_Response(
				array(
					'chapter'  => 0,
					'position' => 0,
					'started'  => false,
				)
			);
		}

		return new WP_REST_Response(
			array(
				'chapter'  => $progress['chapter_id'],
				'position' => $progress['position_seconds'],
				'started'  => true,
				'updated'  => $progress['updated_at'],
			)
		);
	}

	/**
	 * Records where this listener has got to.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function write( WP_REST_Request $request ) {
		$book_id    = (int) $request['book'];
		$chapter_id = (int) $request['chapter'];

		// A chapter that belongs to a different book would record a position
		// the player could never restore, so it is refused rather than stored.
		if ( ! $this->chapter_belongs_to( $chapter_id, $book_id ) ) {
			return new WP_Error(
				'volumina_wrong_chapter',
				__( 'That chapter is not part of that audiobook.', 'volumina' ),
				array( 'status' => 400 )
			);
		}

		$saved = Progress::save(
			get_current_user_id(),
			$book_id,
			$chapter_id,
			(int) $request['position']
		);

		if ( ! $saved ) {
			return new WP_Error(
				'volumina_not_saved',
				__( 'The listening position could not be saved.', 'volumina' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'chapter'  => $chapter_id,
				'position' => (int) $request['position'],
				'saved'    => true,
			)
		);
	}

	/**
	 * Whether a chapter really is part of a book.
	 *
	 * @param int $chapter_id Chapter claimed.
	 * @param int $book_id    Book claimed.
	 */
	private function chapter_belongs_to( int $chapter_id, int $book_id ): bool {
		if ( Chapter::POST_TYPE !== get_post_type( $chapter_id ) ) {
			return false;
		}

		return (int) get_post_meta( $chapter_id, 'volumina_book_id', true ) === $book_id;
	}
}
