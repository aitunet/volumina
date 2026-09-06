# PUBLIC API — Volumina

> The contract `volumina-pro` and any third-party extension may rely on.
> Everything not documented here is internal and may change without notice.

**Status: stable from 1.0.** Everything below is a promise. Breaking one requires a
major version bump and an entry in `docs/decisions.md`.

---

## Rules

- Every extension point is an action, a filter or an interface, and is documented here
  **at the moment it is created**, not afterwards.
- Extensions never call internal classes, never read the progress table directly, and
  never patch admin menus.
- The free plugin holds no privileged position: it registers its own access providers
  through the same action an extension uses. If the action were not enough, the free
  plugin would be the first thing to break.
- **Never name a Volumina class at file scope.** Plugins load in alphabetical order by
  path, so yours may well load before this one: a file that says
  `class My_Provider implements AccessProvider` at the top level is a fatal error on
  every site where it sorts first, and `volumina-anything` sorts before `volumina`.
  Declare your classes from inside the hook — the examples below do — or on
  `plugins_loaded` at the earliest. It costs one `require_once` and it is also what
  keeps your plugin alive when Volumina is deactivated.

---

## Access

Whether somebody may hear a book is one question with one answer, worked out in
`TUNET\Volumina\Access\AccessManager`. It asks every registered provider and combines
what they say:

> **A single refusal denies. Otherwise a single grant allows. Otherwise the answer is
> no.**

Default deny is deliberate. A restricted book whose provider fails to load should be
silent, not open.

The answer is worked out again on every request, including every range request of the
same file. A URL is never a permission.

### `AccessProvider`

Interface, `TUNET\Volumina\Access\AccessProvider`.

```php
interface AccessProvider {
	public function id(): string;                                  // unique machine name
	public function label(): string;                               // shown to a person
	public function can_listen( int $user_id, int $book_id ): ?bool;
}
```

`can_listen()` has three answers and the difference between them matters:

| Answer  | Meaning                                                                     |
| ------- | --------------------------------------------------------------------------- |
| `true`  | Grant. This provider vouches for the listener.                              |
| `false` | Refusal. Outranks every grant. Only for a real reason to keep somebody out. |
| `null`  | No opinion. Almost always right when your own condition is not met.         |

`$user_id` is `0` for somebody who is not signed in.

Registering a provider under an `id()` already taken replaces the one there. That is how
an extension deliberately supersedes another; prefix your id with your own slug unless
you mean to.

### `volumina_register_access_providers`

Action. Fires **once**, the first time access is resolved, with the `AccessManager` as
its only argument.

```php
add_action( 'volumina_register_access_providers', function ( $manager ) {
	$manager->register( new My_Provider() );
} );
```

Add your hook no later than `init`. This fires on the first request that resolves
access, and a hook added after it has fired is ignored for that request.

`AccessManager` also offers `unregister( string $id )` and `providers()`.

### `AccessManager::instance()->can_listen()`

```php
AccessManager::instance()->can_listen( int $book_id, ?int $user_id = null ): bool
```

Ask it whenever you need the same answer the plugin uses — to show a badge, to decide
what a shortcode renders. `null` for `$user_id` means whoever is asking. Answers are
cached for the request.

### `volumina_can_listen`

Filter. The last word, after every provider has spoken.

```php
apply_filters( 'volumina_can_listen', bool $allowed, int $book_id, int $user_id )
```

Prefer a provider: a provider says why it answered and shows up where a person can see
it. This is for what a provider cannot express.

### `Mode` — a book's access mode

`TUNET\Volumina\Access\Mode`. The mode lives in the book's own meta, so an extension
reads and writes the same field the editor sets rather than inventing one.

| Constant             | Value             | Meaning                                |
| -------------------- | ----------------- | -------------------------------------- |
| `Mode::PUBLIC`       | `public`          | Anyone may listen. The default.        |
| `Mode::RESTRICTED`   | `restricted`      | Only listeners a provider vouches for. |
| `Mode::META_KEY`     | `volumina_access` | The meta key holding it.               |

```php
Mode::of( int $book_id ): string        // the mode; a book with none stored reads as public
Mode::sanitize( mixed $value ): string  // anything unrecognised becomes public, never restricted
Mode::all(): array                      // the modes
Mode::labels(): array                   // mode => a name a person can read
```

A restricted book still appears on the site with its cover, its details and its chapter
list. Only the audio is held back.

### `Storage\Grants` — grants made by hand

`TUNET\Volumina\Storage\Grants` is public API, for an extension that wants to give
somebody a book rather than answer for them live.

```php
Grants::has( int $user_id, int $book_id ): bool
Grants::grant( int $user_id, int $book_id, int $granted_by = 0 ): bool
Grants::revoke( int $user_id, int $book_id ): bool
Grants::for_book( int $book_id, int $limit = 200 ): array   // listener IDs
Grants::for_user( int $user_id, int $limit = 200 ): array   // book IDs
```

The shipped `ManualProvider` turns a grant into access. A plugin that sells books should
register a provider instead: a purchase that is later refunded is a question to ask
live, not a row to remember to delete.

---

## Audio

### `volumina_chapter_audio_url`

Filter. Every audio URL the plugin emits passes through here.

```php
apply_filters( 'volumina_chapter_audio_url', string $url, int $chapter_id )
```

Return a signed, expiring URL, a CDN URL, anything. Whatever comes back is used as it
stands.

**Replacing the URL does not replace the access check.** This plugin's own endpoint asks
`AccessManager` on every request whatever the URL looks like, and an endpoint you
substitute is responsible for asking too. A URL nobody checks is a file anybody can
share.

---

## Admin

### `Screen`

Interface, `TUNET\Volumina\Admin\Screen`.

```php
interface Screen {
	public function slug(): string;        // ends up in admin.php?page=<slug>; prefix it
	public function title(): string;       // the page title
	public function menu_title(): string;  // the menu entry, usually shorter
	public function capability(): string;  // required to see it at all
	public function applies(): bool;       // false and it is never registered
	public function render(): void;        // everything it echoes must be escaped
}
```

A screen that does not apply, or that the person looking may not use, is not registered:
no menu entry, no route, no empty page explaining that there is nothing here.

The capability is checked twice — once to decide whether to add the page, and again at
the moment of drawing. The check that hid the menu entry is not the one that protects
the page.

### `volumina_register_admin_screens`

Action. Fires on `admin_menu`, with the `ScreenRegistry` as its only argument. Screens
are added under the Audiobooks menu.

```php
add_action( 'volumina_register_admin_screens', function ( $registry ) {
	$registry->add( new My_Screen() );
} );
```

---

## A complete example

A plugin that lets every signed-in reader hear every restricted book, and adds a screen
saying so. It touches nothing internal. Two files, because of the load-order rule above:
the entry file names no Volumina class, and the classes are pulled in from the hooks,
by which time this plugin has certainly loaded.

**`volumina-subscribers.php`**

```php
<?php
/**
 * Plugin Name: Volumina Subscribers
 */

defined( 'ABSPATH' ) || exit;

add_action( 'volumina_register_access_providers', function ( $manager ) {
	require_once __DIR__ . '/classes.php';

	$manager->register( new Subscriber_Provider() );
} );

add_action( 'volumina_register_admin_screens', function ( $registry ) {
	require_once __DIR__ . '/classes.php';

	$registry->add( new Subscriber_Screen() );
} );

add_filter( 'volumina_chapter_audio_url', function ( $url, $chapter_id ) {
	return my_signed_url( $url, $chapter_id );
}, 10, 2 );
```

**`classes.php`**

```php
<?php

use TUNET\Volumina\Access\AccessProvider;
use TUNET\Volumina\Admin\Screen;

defined( 'ABSPATH' ) || exit;

final class Subscriber_Provider implements AccessProvider {
	public function id(): string    { return 'subscribers/all'; }
	public function label(): string { return 'Subscribers'; }

	public function can_listen( int $user_id, int $book_id ): ?bool {
		if ( $user_id <= 0 ) {
			return null;
		}

		// A grant, or nothing. Never a refusal: this plugin knows no reason to
		// keep anybody out, only a reason to let somebody in.
		return user_can( $user_id, 'read' ) ? true : null;
	}
}

final class Subscriber_Screen implements Screen {
	public function slug(): string       { return 'subscribers-access'; }
	public function title(): string      { return 'Subscriber access'; }
	public function menu_title(): string { return 'Subscribers'; }
	public function capability(): string { return 'manage_options'; }
	public function applies(): bool      { return true; }

	public function render(): void {
		echo '<div class="wrap"><h1>Subscriber access</h1>';
		echo '<p>Every signed-in reader can hear every book.</p></div>';
	}
}
```

This exact plugin is what S4 was verified with: activated on a site with a restricted
book, it let a subscriber with no grant hear the audio, added its screen under
Audiobooks, and swapped the audio URL. Deactivated, the same listener got the locked
notice and a 404 from the audio endpoint.

---

## Not public

Named here so nobody has to guess:

- Everything under `src/Frontend/`, `src/Blocks/`, `src/Player/` and `src/Support/`.
- The templates in `templates/`. They are internal partials, not theme overrides.
- The progress table and `Storage\Progress`. A listening position is the plugin's own
  record; read it through the REST route as its owner.
- The block names and their attributes, until a block declares otherwise.
