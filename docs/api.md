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

## Content types

The names below are contract: renaming one is a major version.

| Name               | Kind             | Holds                                                     |
| ------------------ | ---------------- | --------------------------------------------------------- |
| `volumina_book`    | post type        | One audiobook. Cover = featured image.                    |
| `volumina_chapter` | post type        | One chapter of a book.                                    |
| `volumina_genre`   | taxonomy (hier.) | Genres of a book.                                        |
| `volumina_series`  | taxonomy (hier.) | Series of a book. A series may have sub-series.          |

Read them with core functions — `get_posts()`, `get_the_terms()`, `get_ancestors()` —
and add meta boxes with `add_meta_boxes_volumina_book`. A chapter's two links are contract too, read with `get_post_meta( $chapter_id, $key, true )`:

| Meta key                 | On                 | Holds                                             |
| ------------------------ | ------------------ | ------------------------------------------------- |
| `volumina_book_id`       | `volumina_chapter` | The book's post ID (integer).                     |
| `volumina_attachment_id` | `volumina_chapter` | The audio file's attachment ID (integer).         |

The endpoint reads the file with `get_attached_file( $attachment_id )`, so an extension
that moves a file and updates `_wp_attached_file` (core's `update_attached_file()`) keeps
it playable. Other meta keys are not contract, except `Mode::META_KEY` above.

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

### `volumina_stream_access`

Filter. Asked by this plugin's own endpoint on every audio request, after `AccessManager`
has granted the listener the book and before a byte is read.

```php
apply_filters( 'volumina_stream_access', bool $allowed, int $chapter_id, int $book_id )
```

Return `false` and the request answers 404, exactly as a chapter that does not exist.
It is the other half of `volumina_chapter_audio_url`: put a signature and an expiry in
the URL there, verify them here, and a copied link stops working without a second
streaming endpoint. It runs for every range of the same file, so keep it cheap; a
verified signature is one HMAC.

---

## Listening

### `volumina_progress_saved`

Action. Fires after a signed-in listener's position has been saved, once per save, with
the row already written.

```php
do_action( 'volumina_progress_saved', int $user_id, int $book_id, int $chapter_id, int $position )
```

The values are the ones stored: `$position` is whole seconds into `$chapter_id`, clamped
to the table's range. A guest's position never comes through here — it is kept by their
own browser. This is the one way to follow listening from outside: the progress table
and `Storage\Progress` are not public.

---

## Presentation

An audiobook — cover, details, player, chapter list — is always drawn by the same
markup. A **template** dresses it: a stylesheet, plus what it prefers for the two options
every template shares, where the cover goes and which palette colour is the accent. The
free plugin ships one template, `basic`; an extension registers more. A site chooses a
template in Settings, and a block can choose its own.

### `volumina_register_templates`

Action. Fires once, the first time a template is needed, with the `TemplateRegistry` as
its only argument. Register the stylesheet on `init` and the template in the action:

```php
add_action( 'init', function () {
	wp_register_style(
		'my-plugin-paper',
		plugins_url( 'paper.css', __FILE__ ),
		array( 'volumina-book' ),   // build on the base layout; leave this out to replace it
		'1.0.0'
	);
} );

add_action( 'volumina_register_templates', function ( $registry ) {
	$registry->add( new TUNET\Volumina\Frontend\Template(
		'my-plugin-paper',                    // a slug; prefix it with yours
		__( 'Paper', 'my-plugin' ),
		'my-plugin-paper',                    // the stylesheet handle, enqueued when the template is used
		array( 'cover' => 'top', 'accent' => 'contrast' )   // its defaults, both optional
	) );
} );
```

`TUNET\Volumina\Frontend\Template` and `TemplateRegistry` are plain PHP. A template's id
is a slug; its defaults hold only `cover` (`side`, `top` or `none`) and `accent` (a
palette slug); anything else is dropped. The registry keeps insertion order and one
template per id, so adding an id again replaces it where it stands. The first template
registered is what a site starts on, and that is `basic`: do not rely on being first.

```php
final class Template {
	public function __construct( string $id, string $label, string $style = '', array $defaults = array(), string $preview = '' );
	public function id(): string;
	public function label(): string;
	public function style(): string;      // stylesheet handle, or ''
	public function defaults(): array;    // { cover?, accent? }
	public function preview(): string;    // http(s) picture URL, or ''
	public static function covers(): array;
}

final class TemplateRegistry {
	public function add( Template $template ): void;
	public function has( string $id ): bool;
	public function get( string $id ): ?Template;
	public function first(): ?Template;
	public function all(): array;         // id => Template, in order
}
```

### The stylesheet contract

The wrapper of every rendering — `.volumina-book` on an audiobook,
`.volumina-chapter-list` on a chapter list drawn alone — carries:

- `volumina-template-{id}`, so a stylesheet can scope every rule to its own template;
- `volumina-cover-side`, `volumina-cover-top` or `volumina-cover-none`, the resolved
  cover position (the audiobook only);
- with an accent, `volumina-has-accent` and two custom properties in its `style`
  attribute: `--volumina-accent`, the colour as six-digit hex, and
  `--volumina-accent-ink`, `#000000` or `#ffffff`, whichever contrasts more with it —
  one of the two always reaches 4.5:1.

Resolution is, lowest to highest: the template's defaults, the site's settings, the
block's own attributes. The accent is stored as a palette slug everywhere and turned into
a colour at render time, so it follows the theme; a slug the active theme does not have
means no accent.

Inside the wrapper the markup is: `.volumina-book-head` holding `.volumina-book-cover`
and `dl.volumina-book-details`, then the player (`.volumina-player`, see its stylesheet
for its classes), then `h2.volumina-book-heading` and `ol.volumina-chapter-list` with one
`li.volumina-chapter-item` per chapter, the one playing marked `is-playing`. These class
names are part of the contract; the elements' order and nesting are too.

The player, `.volumina-player`, carries `data-volumina-state`: `paused` in the markup and
whenever nothing is sounding (before the first play, after a pause, at the end), `playing`
while sound plays. A stylesheet may use it to animate with playback —
`.volumina-player[data-volumina-state="playing"]` — and must stop every animation under
`@media (prefers-reduced-motion: reduce)`. `is-playing` on a chapter means "the current
chapter", sounding or not; `data-volumina-state` means sound.

**What a template is not:** behaviour. A template that needs JavaScript or data the
plugin does not keep is a feature, not a stylesheet, and needs its own hooks.

The library block's wrapper, `.volumina-library`, carries the same template class and
accent properties; inside it, `ul.volumina-library-list` holds one `li.volumina-library-item`
per book — `.volumina-library-cover`, then `.volumina-library-text` with
`a.volumina-library-title` and `.volumina-library-lines`.

---

## Blocks

The plugin ships four blocks: Audiobook, Chapter list, Continue listening and Audiobook
list. An extension adds blocks of its own by registering them with WordPress; nothing
here is needed for that. To change what the shipped blocks show, three filters. Each
receives plain text and returns plain text; everything is escaped when printed.

### `volumina_book_details`

```php
apply_filters( 'volumina_book_details', array $details, WP_Post $book, array $chapters )
```

The details under a book's cover, on its page and in the Audiobook block: label to
value, in order — "Narrated by", "Running time", "Chapters", "Publisher", "ISBN",
those that are not empty. Add a row, reword one, or `unset()` one. `$chapters` are the
chapters the reader may see.

### `volumina_library_query`

```php
apply_filters( 'volumina_library_query', array $args, array $attributes )
```

The `WP_Query` arguments the Audiobook list block is about to run, after its own
attributes have been applied: post type and status, `posts_per_page`, `orderby` and
`order`, and a `tax_query` when a genre or series was chosen. Change the order, exclude
books, add clauses. `$attributes` are the block's, for a filter that wants to know what
the editor asked for.

### `volumina_library_item`

```php
apply_filters( 'volumina_library_item', array $item, WP_Post $book, array $attributes )
```

One row of the Audiobook list block, before it is printed:

| Key     | Type               | What it is                                                   |
|---------|--------------------|--------------------------------------------------------------|
| `book`  | `WP_Post`          | The book.                                                    |
| `title` | `string`           | The linked title.                                            |
| `url`   | `string`           | Where the title leads.                                       |
| `cover` | `int`              | Attachment id of the cover, or 0 for none.                   |
| `lines` | `array<int,string>`| What appears under the title, in order, joined by " · ".    |

Return the row, or only the keys you changed: what comes back is merged over the
original. A price, "Yours", a badge: append to `lines`. To hide the cover, set it to 0.

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

### `HasHelp`, `HasAssets` and `HasParent`

Interfaces, `TUNET\Volumina\Admin\HasHelp`, `HasAssets` and `HasParent`. All three are
optional additions to `Screen`: a screen that implements none keeps working exactly as
it did, which is why they are separate rather than methods of `Screen`.

```php
interface HasHelp {
	public function help(): void;     // add help tabs; `Support\Help::add()` does the work
}

interface HasAssets {
	public function enqueue(): void;  // wp_enqueue_style() / wp_enqueue_script(); never echo a tag
}

interface HasParent {
	public function parent(): string; // a parent menu slug: `index.php`, `tools.php`…; without it, Audiobooks
}
```

The registry calls the first two on `load-{page}`, and only for that page: `help()` at
the one moment `get_current_screen()` is available and still accepting tabs, `enqueue()`
early enough for a stylesheet to print in the head rather than trail in the footer after
the page has been drawn unstyled. Nothing a screen enqueues reaches any other screen.
`parent()` puts the screen under another menu — the welcome page sits under Dashboard —
and its URL then follows that parent (`index.php?page=<slug>`), not `edit.php`.

### `volumina_welcome_sections`

Action. Fires while the welcome page — Audiobooks → Welcome, and where a new site lands
once after activation — is being drawn, with its `Sections` as the only argument. Add
a section and it is drawn after the plugin's own, as a heading and whatever the
callable echoes.

```php
add_action( 'volumina_welcome_sections', function ( $sections ) {
	$sections->add( 'my-plugin', __( 'What My Plugin adds', 'my-plugin' ), function () {
		echo '<p>' . esc_html__( 'Selling is on. Every purchase grants its book.', 'my-plugin' ) . '</p>';
	} );
} );
```

`TUNET\Volumina\Admin\Sections` is plain PHP: sections come back in the order they were
added; an id names one section only, so adding an id again replaces that section where
it stands; an empty id throws. The id becomes part of the section's `id` attribute, so
prefix it with your own slug. The callable is responsible for escaping everything it
echoes.

```php
final class Sections {
	public function add( string $id, string $title, callable $render ): void;
	public function has( string $id ): bool;
	public function all(): array;   // [ { id, title, render }, … ] in order
}
```

### `volumina_settings_tabs`

Action. Fires while Audiobooks → Settings is being drawn, with a `Sections` of its tabs
as the only argument (the same class as `volumina_welcome_sections`, the same rules).
Volumina's own settings are the tab `general`, always first; an extension cannot replace
it. With two tabs or more the screen draws WordPress's tab bar; with one it draws none.

```php
add_action( 'volumina_settings_tabs', function ( $tabs ) {
	$tabs->add( 'my-plugin', __( 'My Plugin', 'my-plugin' ), function () {
		echo '<form action="options.php" method="post">';
		settings_fields( 'my_plugin_settings' );
		echo '<section class="volumina-card volumina-settings-section"><h2>' . esc_html__( 'Section', 'my-plugin' ) . '</h2>';
		echo '<p class="volumina-settings-lede">' . esc_html__( 'What it is for.', 'my-plugin' ) . '</p>';
		// …fields…
		echo '</section>';
		submit_button();
		echo '</form>';
	} );
} );
```

- A tab's address is `edit.php?post_type=volumina_book&page=volumina-settings&tab=<id>`;
  `general` has none. An unknown `tab` falls back to `general`.
- Each tab draws its own form and saves its own option: saving one tab never touches
  another's settings. `options.php` returns to the page it came from, tab included.
- Volumina confirms a save ("Settings saved.") at the top of the screen for every tab;
  a tab does not print its own confirmation. Errors a tab's sanitize callback adds with
  `add_settings_error()` are shown by the tab itself (`settings_errors( 'my_plugin_settings' )`).
- The card markup above — `section.volumina-card.volumina-settings-section`, its `h2` and
  `p.volumina-settings-lede` — is part of this contract, so a tab looks like the rest.
- The id becomes part of an `id` attribute (`volumina-settings-tab-<id>`): prefix it with
  your own slug. The callable escapes everything it echoes. The capability is the screen's,
  `manage_options`.
- General is always the first tab and Appearance always the second; an extension's tabs
  come after them.

### The Appearance tab

Audiobooks → Settings → Appearance holds how an audiobook looks: template, cover
position and accent colour, with a preview beside them. One form saves all of it.

- **Knowing the tab exists.** The class constant
  `TUNET\Volumina\Admin\Settings::APPEARANCE_GROUP` (value `volumina_appearance`) exists
  from the release that added this tab on. An extension tests
  `defined( 'TUNET\Volumina\Admin\Settings::APPEARANCE_GROUP' )` before hooking into the
  tab, and keeps working without it on an older Volumina.
- **The form.** The tab's single form is `form.volumina-appearance-form`. A script finds
  the form, and reads its fields, through that selector.

- **The option group `volumina_appearance`.** The tab's form posts as this group, so
  `options.php` writes every option registered in it. To have an option saved from the
  tab, `register_setting( 'volumina_appearance', 'my_option', … )`. Every option in the
  group must have its fields drawn in the form: an option registered there and not drawn
  is saved empty.
- **The action `volumina_appearance_sections`.** Fires while the tab is drawn, with a
  `Sections` of cards; they are printed inside the form, after Presentation. A card is
  the card markup of `volumina_settings_tabs` and has **no** `<form>` of its own.

```php
add_action( 'volumina_appearance_sections', function ( $cards ) {
	$cards->add( 'my-plugin-look', __( 'My Plugin', 'my-plugin' ), function () {
		echo '<section class="volumina-card volumina-settings-section"><h2>' . esc_html__( 'My Plugin', 'my-plugin' ) . '</h2>';
		echo '<p class="volumina-settings-lede">' . esc_html__( 'What it is for.', 'my-plugin' ) . '</p>';
		// …fields, named my_option[…]…
		echo '</section>';
	} );
} );
```

- **Volumina's fields** are `volumina_settings[template]`, `volumina_settings[cover_position]`
  and `volumina_settings[accent]`.
- **A template's picture.** `Template` takes a fifth argument, `preview`: an http(s) URL of
  a picture about 480x300, shown at 240x150 on the template's card. Anything else is dropped
  and the card is drawn without a picture.
- **The preview.** `.volumina-appearance-preview .volumina-book` is the newest published
  book, drawn by `Audiobook::render()`. Its sample is inert: it is a picture of the
  player, not a player. Its audio has no source and fetches nothing, the bare audio bar
  is hidden, and only the first three chapters show. The preview's "Show it playing"
  button, above the sample, sets `data-volumina-state` (`paused` or `playing`) on the
  sample's `.volumina-player`.
- **The event `volumina:appearance-change`.** Dispatched on `document` on load and after
  every change of the form, with `detail.template`, `detail.cover`, `detail.accent` (a
  six-digit hex, or `''` for none) and `detail.ink` (the text colour that reads on it).
  An extension's script may set custom properties and classes on the root and on its
  `.volumina-player`, and should re-apply them on every event. The first event fires on
  `DOMContentLoaded`, so the listener must be attached before then: enqueue a normal
  header or footer script for the Settings screen, not one loaded lazily or after load.

### `volumina_register_admin_screens`

Action. Fires on `admin_menu`, with the `ScreenRegistry` as its only argument. Screens
are added under the Audiobooks menu.

```php
add_action( 'volumina_register_admin_screens', function ( $registry ) {
	$registry->add( new My_Screen() );
} );
```

### `VOLUMINA_PRO_VERSION`

Constant, defined by Volumina Pro in its bootstrap. When it is defined, this plugin's
presentational "Volumina Pro" screen — the one that says what Pro adds — is not
registered at all: the real thing has arrived and the advertisement leaves. Nothing else
reads it. Define it with Pro's version string; do not define it from anything else.

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
