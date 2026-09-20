<?php
/**
 * Writes the JSON translation files the block editor reads.
 *
 * `wp i18n make-json` splits a locale's strings by source file and names each
 * file after the source path's md5, but a built block bundles several source
 * files under one script handle, so those files would never be found. This
 * writes one file per locale and handle instead — every string a JavaScript
 * file uses, named `{domain}-{locale}-{handle}.json`, which is the first name
 * WordPress looks for — for each block's editor script.
 *
 * Run after the .po files change:  php bin/make-script-json.php
 *
 * It runs outside WordPress, on the command line, which is why the sniffs
 * about WordPress's filesystem wrappers and escaped output do not apply.
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.Security.EscapeOutput
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

$volumina_root    = dirname( __DIR__ );
$volumina_domain  = 'volumina';
$volumina_handles = array(
	'volumina-audiobook-editor-script',
	'volumina-chapter-list-editor-script',
	'volumina-continue-listening-editor-script',
	'volumina-library-editor-script',
);

/**
 * Parses a .po file into the entries JavaScript files use.
 *
 * @param string $file The .po file.
 * @return array{lang: string, plural: string, entries: array<string, array<int, string>>}
 */
function volumina_js_entries( string $file ): array {
	$lines   = file( $file, FILE_IGNORE_NEW_LINES );
	$entries = array();
	$lang    = '';
	$plural  = 'nplurals=2; plural=n != 1;';

	$refs = array();
	$id   = null;
	$idp  = null;
	$strs = array();
	$key  = null;

	$flush = static function () use ( &$entries, &$refs, &$id, &$idp, &$strs, &$key ): void {
		if ( null !== $id && '' !== $id && array() !== $strs && preg_grep( '/\.js\b/', $refs ) ) {
			ksort( $strs );
			$entries[ $id ] = array_values( $strs );
		}

		$refs = array();
		$id   = null;
		$idp  = null;
		$strs = array();
		$key  = null;
	};

	foreach ( $lines as $line ) {
		if ( '' === trim( $line ) ) {
			$flush();
			continue;
		}

		if ( str_starts_with( $line, '#: ' ) ) {
			$refs[] = substr( $line, 3 );
			continue;
		}

		if ( str_starts_with( $line, '#' ) ) {
			continue;
		}

		if ( 1 === preg_match( '/^(msgid|msgid_plural|msgstr(?:\[(\d+)\])?) "(.*)"$/', $line, $m ) ) {
			$value = stripcslashes( $m[3] );

			if ( 'msgid' === $m[1] ) {
				$id  = $value;
				$key = 'id';
			} elseif ( 'msgid_plural' === $m[1] ) {
				$idp = $value;
				$key = 'idp';
			} else {
				$key          = (int) ( $m[2] ?? 0 );
				$strs[ $key ] = $value;
			}

			continue;
		}

		if ( 1 === preg_match( '/^"(.*)"$/', $line, $m ) ) {
			$value = stripcslashes( $m[1] );

			if ( 'id' === $key ) {
				$id .= $value;
			} elseif ( 'idp' === $key ) {
				$idp .= $value;
			} elseif ( is_int( $key ) ) {
				$strs[ $key ] .= $value;
			}

			// The header is the empty msgid; its msgstr carries the language.
			if ( '' === $id && is_int( $key ) ) {
				if ( 1 === preg_match( '/^Language: (.+)$/', trim( $value ), $h ) ) {
					$lang = trim( $h[1] );
				}

				if ( 1 === preg_match( '/^Plural-Forms: (.+)$/', trim( $value ), $h ) ) {
					$plural = trim( $h[1] );
				}
			}
		}
	}

	$flush();

	return array(
		'lang'    => $lang,
		'plural'  => $plural,
		'entries' => $entries,
	);
}

$volumina_written = 0;

foreach ( glob( $volumina_root . '/languages/' . $volumina_domain . '-*.po' ) as $volumina_po ) {
	$volumina_locale = substr( basename( $volumina_po, '.po' ), strlen( $volumina_domain ) + 1 );
	$volumina_parsed = volumina_js_entries( $volumina_po );

	$volumina_data = array(
		'translation-revision-date' => gmdate( 'Y-m-d H:i:sO' ),
		'generator'                 => 'bin/make-script-json.php',
		'source'                    => 'languages/' . basename( $volumina_po ),
		'domain'                    => 'messages',
		'locale_data'               => array(
			'messages' => array_merge(
				array(
					'' => array(
						'domain'       => 'messages',
						'lang'         => $volumina_parsed['lang'],
						'plural-forms' => $volumina_parsed['plural'],
					),
				),
				$volumina_parsed['entries']
			),
		),
	);

	$volumina_json = json_encode( $volumina_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

	foreach ( $volumina_handles as $volumina_handle ) {
		file_put_contents(
			$volumina_root . '/languages/' . $volumina_domain . '-' . $volumina_locale . '-' . $volumina_handle . '.json',
			$volumina_json . "\n"
		);
		++$volumina_written;
	}

	printf( "%s: %d strings\n", $volumina_locale, count( $volumina_parsed['entries'] ) );
}

printf( "%d files written\n", $volumina_written );
