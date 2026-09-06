<?php
/**
 * The audio streaming endpoint.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Player;

use TUNET\Volumina\Access\AccessManager;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Support\ByteRange;
use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Serves a chapter's audio, one byte range at a time.
 *
 * Range requests are not a nicety here. Without them a listener cannot seek,
 * a browser will not scrub, and iOS refuses to play at all. They are the
 * difference between an audio file on a page and an audiobook.
 *
 * The chapter ID in the URL is a question, never an answer: it is resolved to
 * a real chapter, the chapter to its book, and the book is what access is
 * checked against.
 */
final class Stream implements Registrable {

	/**
	 * Query variable carrying the chapter ID.
	 */
	public const QUERY_VAR = 'volumina_audio';

	/**
	 * How much to push out at a time. Small enough that a listener who seeks
	 * away is not waiting on a megabyte they no longer want.
	 */
	private const CHUNK = 65536;

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_stream' ) );
	}

	/**
	 * Registers the query variable.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * The URL a player should use for a chapter.
	 *
	 * Every audio URL in the plugin comes from here, so there is exactly one
	 * place for S4 to hang `volumina_chapter_audio_url` on when Pro needs to
	 * substitute a signed, expiring URL. The filter itself belongs to S4; this
	 * is only the single seam it will need.
	 *
	 * @param int $chapter_id Chapter to play.
	 */
	public static function url( int $chapter_id ): string {
		$url = add_query_arg( self::QUERY_VAR, $chapter_id, home_url( '/' ) );

		/**
		 * Filters the URL a chapter's audio is served from.
		 *
		 * Every audio URL the plugin emits passes through here, so an
		 * extension can serve the file from somewhere else — a signed,
		 * expiring URL, or a CDN. Whatever comes back is used as it stands.
		 *
		 * Replacing the URL does not replace the access check: this plugin's
		 * own endpoint still asks `AccessManager` on every request, and a
		 * substitute endpoint is responsible for asking too.
		 *
		 * @param string $url        The URL of this plugin's own endpoint.
		 * @param int    $chapter_id The chapter being played.
		 */
		return (string) apply_filters( 'volumina_chapter_audio_url', $url, $chapter_id );
	}

	/**
	 * Streams the chapter when the request asks for one.
	 */
	public function maybe_stream(): void {
		$chapter_id = (int) get_query_var( self::QUERY_VAR );

		if ( $chapter_id <= 0 ) {
			return;
		}

		$file = $this->resolve( $chapter_id );

		if ( null === $file ) {
			// Deliberately indistinguishable from a chapter that never existed.
			// Telling a stranger which IDs are real and merely forbidden is an
			// invitation to enumerate the library.
			status_header( 404 );
			nocache_headers();
			exit;
		}

		$this->send( $file[0], $file[1] );
	}

	/**
	 * Resolves a chapter ID to a readable file, or to nothing.
	 *
	 * @param int $chapter_id Chapter the request asked for.
	 * @return array{0: string, 1: string}|null Path and MIME type, or null.
	 */
	private function resolve( int $chapter_id ): ?array {
		if ( Chapter::POST_TYPE !== get_post_type( $chapter_id ) ) {
			return null;
		}

		$book_id = (int) get_post_meta( $chapter_id, 'volumina_book_id', true );

		if ( ! $this->can_listen( $book_id ) ) {
			return null;
		}

		$attachment_id = (int) get_post_meta( $chapter_id, 'volumina_attachment_id', true );

		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return null;
		}

		$mime = get_post_mime_type( $attachment_id );

		if ( ! is_string( $mime ) || ! str_starts_with( $mime, 'audio/' ) ) {
			return null;
		}

		$path = get_attached_file( $attachment_id );

		if ( ! is_string( $path ) || '' === $path ) {
			return null;
		}

		$real = realpath( $path );

		if ( false === $real || ! is_file( $real ) || ! is_readable( $real ) ) {
			return null;
		}

		// The path came from the database rather than from the request, but it
		// still has to be inside the uploads directory. A stored path is only
		// as trustworthy as whatever wrote it.
		$uploads = wp_get_upload_dir();
		$base    = realpath( $uploads['basedir'] );

		if ( false === $base || ! str_starts_with( $real, $base . DIRECTORY_SEPARATOR ) ) {
			return null;
		}

		return array( $real, $mime );
	}

	/**
	 * Whether the current visitor may listen to this book.
	 *
	 * Every byte of audio this plugin sends passes through here, and it asks
	 * `AccessManager` rather than deciding anything itself. A URL is not a
	 * permission: the answer is worked out again on every request, including
	 * every range request of the same file.
	 *
	 * @param int $book_id The book the chapter belongs to.
	 */
	private function can_listen( int $book_id ): bool {
		if ( Book::POST_TYPE !== get_post_type( $book_id ) ) {
			return false;
		}

		return AccessManager::instance()->can_listen( $book_id );
	}

	/**
	 * Sends the file, honouring a range request if there is one.
	 *
	 * @param string $path Absolute path to the audio file.
	 * @param string $mime Its MIME type.
	 */
	private function send( string $path, string $mime ): void {
		$size     = (int) filesize( $path );
		$modified = (int) filemtime( $path );
		$etag     = '"' . md5( $path . '|' . $size . '|' . $modified ) . '"';

		if ( $this->is_unchanged( $etag, $modified ) ) {
			$this->headers( $mime, $etag, $modified );
			status_header( 304 );
			exit;
		}

		$range = ByteRange::parse( $this->requested_range( $etag, $modified ), $size );

		if ( null === $range ) {
			$this->headers( $mime, $etag, $modified );
			header( 'Content-Range: bytes */' . $size );
			status_header( 416 );
			exit;
		}

		[ $start, $end ] = $range;
		$length          = $end - $start + 1;

		$this->headers( $mime, $etag, $modified );
		header( 'Content-Length: ' . $length );

		if ( $length !== $size ) {
			header( sprintf( 'Content-Range: bytes %d-%d/%d', $start, $end, $size ) );
			status_header( 206 );
		} else {
			status_header( 200 );
		}

		if ( 'HEAD' === $this->method() ) {
			exit;
		}

		$this->push( $path, $start, $length );

		exit;
	}

	/**
	 * The common headers, sent whatever the outcome.
	 *
	 * @param string $mime     MIME type of the file.
	 * @param string $etag     Entity tag.
	 * @param int    $modified Last modified timestamp.
	 */
	private function headers( string $mime, string $etag, int $modified ): void {
		// Anything already buffered would be sent as part of the audio.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header( 'Content-Type: ' . $mime );
		header( 'Accept-Ranges: bytes' );
		header( 'Content-Disposition: inline' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'ETag: ' . $etag );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified ) . ' GMT' );
		header( 'Cache-Control: private, max-age=3600' );
	}

	/**
	 * Whether the client already holds this exact file.
	 *
	 * @param string $etag     Entity tag of the file.
	 * @param int    $modified Last modified timestamp.
	 */
	private function is_unchanged( string $etag, int $modified ): bool {
		$none_match = $this->server( 'HTTP_IF_NONE_MATCH' );

		if ( '' !== $none_match ) {
			return in_array( $etag, array_map( 'trim', explode( ',', $none_match ) ), true );
		}

		$since = $this->server( 'HTTP_IF_MODIFIED_SINCE' );

		if ( '' === $since ) {
			return false;
		}

		$stamp = strtotime( $since );

		return false !== $stamp && $modified <= $stamp;
	}

	/**
	 * The Range header, unless `If-Range` says the client's copy is stale.
	 *
	 * A client that asks for a range of a file it no longer has must be given
	 * the whole thing, not a slice measured against the wrong bytes.
	 *
	 * @param string $etag     Entity tag of the file.
	 * @param int    $modified Last modified timestamp.
	 */
	private function requested_range( string $etag, int $modified ): string {
		$range = $this->server( 'HTTP_RANGE' );

		if ( '' === $range ) {
			return '';
		}

		$if_range = $this->server( 'HTTP_IF_RANGE' );

		if ( '' === $if_range ) {
			return $range;
		}

		if ( str_starts_with( $if_range, '"' ) || str_starts_with( $if_range, 'W/' ) ) {
			return $if_range === $etag ? $range : '';
		}

		$stamp = strtotime( $if_range );

		return false !== $stamp && $stamp === $modified ? $range : '';
	}

	/**
	 * Pushes the bytes out, giving up if the listener has gone.
	 *
	 * WP_Filesystem is the usual answer for reading a file, and it is the
	 * wrong one here: it reads whole files into memory. A nine-hour audiobook
	 * has to leave in pieces.
	 *
	 * @param string $path   Absolute path to the file.
	 * @param int    $start  First byte to send.
	 * @param int    $length How many bytes to send.
	 */
	private function push( string $path, int $start, int $length ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return;
		}

		if ( $start > 0 ) {
			fseek( $handle, $start );
		}

		$remaining = $length;

		while ( $remaining > 0 && ! feof( $handle ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$buffer = fread( $handle, (int) min( self::CHUNK, $remaining ) );

			if ( false === $buffer || '' === $buffer ) {
				break;
			}

			// Binary audio. There is nothing here to escape, and escaping it
			// would corrupt the file.
			echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$remaining -= strlen( $buffer );

			flush();

			if ( 0 !== connection_aborted() ) {
				break;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	}

	/**
	 * The request method, uppercased.
	 */
	private function method(): string {
		return strtoupper( $this->server( 'REQUEST_METHOD' ) );
	}

	/**
	 * A request header, sanitised, or an empty string.
	 *
	 * @param string $key Key in `$_SERVER`.
	 */
	private function server( string $key ): string {
		if ( ! isset( $_SERVER[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
	}
}
