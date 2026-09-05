<?php
/**
 * Bootstrap for the plain unit suite.
 *
 * No WordPress is loaded. Only classes that depend on nothing but PHP belong
 * here; anything that calls WordPress is verified against a running install.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

// Every source file guards on this constant, so the suite has to satisfy it.
// It is WordPress's own constant, which is exactly why it carries no prefix.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
