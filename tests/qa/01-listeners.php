<?php
/**
 * QA — the Listeners box gives a restricted book by hand and takes it back.
 * Run: wp eval-file tests/qa/01-listeners.php
 * Ends in "Success: ALL OK" or lists the failures. Leaves nothing behind.
 *
 * @package TUNET\Volumina
 */

use TUNET\Volumina\Access\AccessManager;
use TUNET\Volumina\Access\Mode;
use TUNET\Volumina\Admin\ListenersBox;
use TUNET\Volumina\Storage\Grants;

$fails  = 0;
$assert = function ( $ok, $label ) use ( &$fails ) {
	WP_CLI::log( ( $ok ? '  ok   ' : '  FAIL ' ) . $label );
	if ( ! $ok ) {
		++$fails;
	}
};

$admin = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
wp_set_current_user( $admin );

$book     = wp_insert_post( array( 'post_type' => 'volumina_book', 'post_status' => 'publish', 'post_title' => 'QA01 book' ) );
$listener = wp_insert_user( array( 'user_login' => 'qa01-listener', 'user_email' => 'qa01@example.com', 'user_pass' => wp_generate_password(), 'role' => 'subscriber', 'display_name' => 'QA01 Listener' ) );
$at_login = wp_insert_user( array( 'user_login' => 'qa01@login', 'user_email' => 'qa01b@example.com', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
update_post_meta( $book, Mode::META_KEY, Mode::RESTRICTED );
// can_listen() remembers its answer for the request; a grant and a listen are
// separate requests in real use, so each check here asks the providers afresh.
$listens = fn() => AccessManager::decide( AccessManager::instance()->providers(), $listener, $book );

$assert( ! $listens(), 'before: a restricted book is closed to the listener' );

$empty = ListenersBox::give( $book, '   ' );
$assert( ! $empty['ok'], 'an empty field is refused' );

$nobody = ListenersBox::give( $book, 'nobody-qa01@example.com' );
$assert( ! $nobody['ok'] && ! Grants::has( $listener, $book ), 'an address with no account is refused and grants nothing' );

$by_mail = ListenersBox::give( $book, ' QA01@example.com ' );
$assert( $by_mail['ok'] && $by_mail['added'], 'given by e-mail, case and spaces ignored' );
$assert( Grants::has( $listener, $book ), 'the grant is stored' );
$assert( $listens(), 'the listener can now listen' );
$assert( str_contains( ListenersBox::row( get_userdata( $listener ) ), 'data-user="' . $listener . '"' ), 'the row names the listener' );

$again = ListenersBox::give( $book, 'qa01-listener' );
$assert( $again['ok'] && ! $again['added'], 'given again by username: no second row' );
$assert( 1 === count( array_filter( Grants::for_book( $book ), fn( $id ) => $id === $listener ) ), 'still one grant' );

$odd = ListenersBox::give( $book, 'qa01@login' );
$assert( $odd['ok'] && Grants::has( $at_login, $book ), 'a username containing an @ is found' );

$taken = ListenersBox::take( $book, $listener );
$assert( $taken['ok'] && ! Grants::has( $listener, $book ), 'taken back' );
$assert( ! $listens(), 'after: closed again' );

$xss = wp_insert_user( array( 'user_login' => 'qa01-xss', 'user_email' => 'qa01c@example.com', 'user_pass' => wp_generate_password(), 'display_name' => '<script>alert(1)</script>' ) );
$assert( ! str_contains( ListenersBox::row( get_userdata( $xss ) ), '<script>' ), 'a hostile display name is escaped' );

// Leave nothing behind.
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( array( $listener, $at_login, $xss ) as $id ) {
	Grants::revoke( $id, $book );
	wp_delete_user( $id );
}
wp_delete_post( $book, true );

if ( 0 === $fails ) {
	WP_CLI::success( 'ALL OK' );
} else {
	WP_CLI::error( $fails . ' failure(s)' );
}
