# DECISIONS — Volumina

> Append-only. One entry per architectural choice, with its reason.
> Breaking anything documented in `docs/api.md` requires a major version bump
> and an entry here.

Format: `## YYYY-MM-DD — Title` / **Decision** / **Why** / **Consequence**.

---

## 2026-09-03 — State lives in `docs/STATE.md`, the log in `docs/HISTORY.md`

**Decision.** The project adopts the global TUNET convention instead of its own.
`docs/STATE.md` is the single source of truth for where the project stands and is
overwritten on every update; `docs/HISTORY.md` is the append-only chronological log.
No `PROGRESS.md` and no `docs/session-log.md` exist.

**Why.** Power cuts are frequent and the machine can die without warning. Two
competing state conventions — the project's and the global one — would produce two
files that drift apart, and a new session would not know which one to trust.

**Consequence.** Every reference in `CLAUDE.md` points to these two files. A second
progress file appearing anywhere is a bug, not a convenience.

## 2026-09-03 — Progress is stored in a custom table, not post meta

**Decision.** Listening positions go in `wp_volumina_progress`
(`user_id`, `book_id`, `chapter_id`, `position_seconds`, `updated_at`, unique key
`user_id, book_id`), not in `wp_postmeta`.

**Why.** It is high-write, per-user, machine-generated data with no editorial life.
In `wp_postmeta` it would grow without bound and slow down every meta query on the site.

**Consequence.** The table needs a migration with version tracking, and `uninstall.php`
must drop it behind the explicit opt-in setting.

## 2026-09-03 — Chapters are a CPT, not repeater meta

**Decision.** `volumina_chapter` is a real post type, child of `volumina_book`.

**Why.** Each chapter needs its own ID to hang progress, bookmarks and future
per-chapter features on. Repeater meta has no stable identity per row.

**Consequence.** Ordering is explicit (`order` meta) and the admin needs a dedicated
chapter list with drag ordering rather than a metabox of repeated fields.

## 2026-09-03 — Durations and positions are integers in seconds

**Decision.** Never floats, anywhere: storage, REST payloads, JS state.

**Why.** Float seconds accumulate rounding error across resume cycles and compare
badly. Second precision is all a listener can perceive.

**Consequence.** The player converts to and from display formats at the edges only.

## 2026-09-03 — Pro is a separate plugin consuming a documented API

**Decision.** `volumina-pro` extends this plugin from the outside, through actions,
filters and interfaces documented in `docs/api.md`. It never touches internals.
Access resolution goes through `AccessManager` and its `AccessProvider` registry;
audio URLs go through the `volumina_chapter_audio_url` filter.

**Why.** If Pro reaches into internals, both plugins break on every release, and the
free plugin can never be refactored.

**Consequence.** Each extension point is documented in `docs/api.md` at the moment it
is created, and is a contract from then on.

## 2026-09-03 — `src/Support/` knows nothing about audiobooks

**Decision.** The settings framework, admin notices, help tabs, logger and licence
screen shell live in `src/Support/` and must be extractable unchanged into the next
TUNET plugin.

**Why.** This scaffolding is the reusable part. Coupling it to books makes it
disposable instead.

**Consequence.** A class in `Support/` that mentions a book, a chapter or a player is
in the wrong directory.

## 2026-09-03 — Local development runs on portable PHP and SQLite, not Docker

**Decision.** The documented environment stays `wp-env`, and `.wp-env.json` is
kept, but development and verification on this machine run on a portable PHP
8.3 with `composer.phar` and WP-CLI under `C:\Users\mage2\tools`, against a
WordPress installed with the official SQLite database integration drop-in at
`C:\Users\mage2\tools\wp-site`, with the repository joined to it by a directory
junction.

**Why.** Docker Desktop needs administrator rights and WSL, and this machine has
neither. Waiting for Docker would have meant writing every slice blind, since the
verification of S1 onwards is "it renders on the front end". Portable PHP needs no
elevation, and the SQLite drop-in removes the need for a database server.

**Consequence.** `wp-env start` remains unproven, and anything specific to it is
unverified. Everything else is testable locally: `composer lint`, WP-CLI, plugin
activation, front-end rendering, `.pot` generation. Nothing of this lives in the
repository, so a machine with Docker can ignore it entirely and use `wp-env`.

## 2026-09-03 — The Composer lock is resolved for PHP 8.1, not for the local PHP

**Decision.** `config.platform.php` is `8.1` in `composer.json`.

**Why.** The lock was generated on PHP 8.3. Without pinning the platform, Composer
could select packages requiring 8.2 or later, and `composer install` would then
fail on the 8.1 that the plugin declares and that CI runs.

**Consequence.** The lock always matches the declared minimum. Raising the minimum
PHP version means changing both the header and this setting, and regenerating the lock.

## 2026-09-05 — The minimal front end renders through `the_content`, not a template file

**Decision.** A single book's cover, details and chapter list are appended to its
content by a filter, from `src/Frontend/BookContent.php` and the internal partial
`templates/book.php`. There is no `single-volumina_book.php`, no theme override
path and no filter on the output.

**Why.** S1 has to prove the data model on the front end in both a block theme and
a classic one. A template file has to have an opinion about the page around it,
and the two theme families disagree about what that page is; a content filter has
none, so the same code lands correctly inside whatever wrapper the theme provides.
Verified against Twenty Twenty-Five and Twenty Twenty-One.

**Consequence.** `src/Frontend/` joins the directories listed in `CLAUDE.md`. The
blocks in S3 are the real presentation layer and will supersede this. Nothing here
is an extension point on purpose: the public API is written in S4, and an
extension point invented early is a promise made by accident — one that would have
to be broken when the blocks arrive.

## 2026-09-05 — The unit suite loads no WordPress

**Decision.** `phpunit.xml.dist` runs plain PHPUnit over `tests/php` with a
bootstrap that defines `ABSPATH` and loads the Composer autoloader, nothing more.
Only code that depends on PHP alone is tested there.

**Why.** The WordPress test suite needs a database server and, in practice, the
`wp-env` that this machine cannot run. Waiting for it would mean shipping S1 with
no tests at all. Meanwhile the code that does call WordPress is exercised against
a real running install, which is stronger evidence than a mock of the very thing
under test.

**Consequence.** Coverage is honest but narrow: `Support/` is unit tested, the rest
is verified by hand against a running WordPress and recorded in `docs/HISTORY.md`.
When a machine with Docker appears, a WordPress integration suite is the follow-up,
and it does not invalidate anything here.

## 2026-09-05 — Audio is a query variable, not a rewrite rule

**Decision.** The streaming endpoint answers `?volumina_audio={chapter}`. Every
audio URL in the plugin is built by `Player\Stream::url()`.

**Why.** A rewrite rule has to be flushed, and a flush that does not happen is a
plugin that looks broken the moment it is activated. The query variable needs no
flush, no `.htaccess`, and no cooperation from the host. Nothing about range
requests, caching or access control is any different either way.

**Consequence.** URLs are less pretty and less CDN-friendly. `Stream::url()` is
the single seam S4 needs for the `volumina_chapter_audio_url` filter, so Pro can
replace these with signed, expiring URLs without touching anything else. Pretty
URLs are in `docs/backlog.md`.

## 2026-09-05 — A guest's listening position stays in their own browser

**Decision.** `wp_volumina_progress` holds rows for signed-in listeners only.
Everyone else is remembered by `localStorage`, and the REST route answers 401.

**Why.** Storing a guest's position server side means inventing an identifier for
someone who never asked to be identified, and then keeping it. A listening
position is not worth a tracking cookie, and the table's primary key is
`(user_id, book_id)` precisely because a listener is a user.

**Consequence.** A guest who changes browser loses their place; a signed-in
listener does not. The account is treated as the truth when both exist, because
it is the only one that can be right on a second device. Carrying a guest's
position into an account at sign-in is in `docs/backlog.md`.

## 2026-09-05 — One renderer serves both the content filter and the blocks

**Decision.** `Frontend\Audiobook` is the only place an audiobook becomes markup.
The single book page reaches it through `the_content`; the S3 blocks call it
directly with options. The templates `book.php` and `chapters.php` are shared and
internal, not theme override points. `Frontend\BookContent` stays and stands down
whenever a block has already rendered the same book, through `Support\RenderOnce`.

**Why.** The alternative considered was to let S3 replace `BookContent` outright.
It cannot: a classic theme has no block template for a custom post type, so a
book page in one would render empty. Keeping both without a shared renderer means
two code paths for one object, and the first anyone hears about the drift is a
bug report saying the block looks wrong.

**Consequence.** The block callbacks are thin. Anything visible on a book page is
changed in one place. `RenderOnce` is generic scaffolding and knows nothing about
audiobooks, so it leaves with `src/Support/`. The chapter list renders chapter
names as plain text and the player upgrades to buttons only the ones it can
actually reach, because a chapter list can appear on a page that has no player.

## 2026-09-05 — The `@wordpress/*` editor packages join as dev dependencies

**Decision.** `@wordpress/blocks`, `block-editor`, `components`, `data`,
`core-data`, `editor`, `i18n`, `html-entities` and `server-side-render` are
installed as dev dependencies. None of them is bundled: `wp-scripts` turns every
one of them into a reference to the `wp.*` global that WordPress already ships,
which is what the generated `index.asset.php` files list as script dependencies.

**Why.** ESLint cannot check an import it cannot resolve, and without them the
lint run fails on every block file with `import/no-unresolved`. Reason enough on
its own; they also give the editor code real definitions to check against.

**Consequence.** `node_modules` grows and `npm ci` takes longer. The shipped
plugin does not change by a single byte: the built bundles contain none of it.

## 2026-09-05 — A block asks for an asset by handle, it does not enqueue one

**Decision.** `Frontend\Assets` registers every front-end handle on `init` and
enqueues nothing. `block.json` names the handles it needs in `style` and
`viewScript`; the content filter enqueues them on a book page.

**Why.** Until now a book page was the only place this plugin drew, so enqueueing
on `is_singular()` was the whole story. A block can put a player on any page on
the site, and it can only ask for a handle that already exists. Registering on
`init` rather than `wp_enqueue_scripts` also covers the editor, which renders
these blocks through the REST API and never reaches a front-end enqueue hook.

**Consequence.** WordPress loads the player's script and stylesheet only on pages
that actually contain a block that asks for them, which is better than the
alternative and better than what a book page did before. `Player::settings()` is
public because the handle it belongs to is now registered somewhere else.

## 2026-09-06 — A refusal denies, a grant allows, silence is not consent

**Decision.** `AccessProvider::can_listen()` answers `true`, `false` or `null`.
`AccessManager` combines them with one rule: a single refusal denies, otherwise a
single grant allows, otherwise the answer is no. Providers are asked in
registration order and the questioning stops at the first refusal.

**Why.** Two providers that disagree have to be settled by something, and the
alternatives are worse. First-answer-wins makes registration order load-bearing,
which is exactly the kind of promise this plugin cannot keep across releases.
Grant-only, with no way to refuse, leaves an extension unable to express a
suspended account or a region it may not serve.

Default deny matters more than the rest of it: a restricted book whose provider
failed to load has to be silent, not open.

**Consequence.** A provider must return `null`, not `false`, when its own
condition is simply not met — `false` is a veto over everybody. The interface
documentation says so twice, and both shipped providers are written that way.
`volumina_can_listen` filters the final answer for what a provider cannot say.

## 2026-09-06 — Grants live in a table, and the mode lives on the book

**Decision.** `wp_volumina_grants` holds `(user_id, book_id, granted_at,
granted_by)` with `(user_id, book_id)` as the primary key and an index on
`book_id`. A book's access mode is a meta, `volumina_access`, holding `public`
or `restricted`, and a book with nothing stored reads as public.

**Why.** Both directions of the grants relation get asked in earnest: the audio
endpoint asks whether this person may hear this book, and an admin screen asks
who may hear it at all. A serialised array in post meta answers the first and
scans every book for the second, and two grants written at once would clobber
each other. Schema version 2.

The mode is a meta rather than a second table because it belongs to the book,
one value per book, edited by hand on the book screen — which is what post meta
is for. It is public API so Pro marks a book as its own without inventing a
field, and an unrecognised value sanitises to `public`: a typo should not take a
published book away from its listeners.

**Consequence.** `uninstall.php` has two tables to drop instead of one. Every
book that existed before this meta did is public, which is what it already was.

## 2026-09-06 — An extension may not name a Volumina class at file scope

**Decision.** `docs/api.md` requires extensions to declare their classes from
inside the registration hook, or on `plugins_loaded` at the earliest, and the
documented example is written that way.

**Why.** Found by running it. WordPress loads active plugins in alphabetical
order by path, and `volumina-subscribers/…` sorts before `volumina/…` because
`-` precedes `/`. The first version of the example plugin said
`implements AccessProvider` at the top of its entry file and took the whole site
down with a fatal error: the interface did not exist yet. Every plugin named
`volumina-something` — which is to say every plugin anybody writes for this one,
starting with Pro — hits this.

**Consequence.** One `require_once` inside the hook. It also means an extension
survives Volumina being deactivated instead of fataling, which is worth having
on its own.

## 2026-09-06 — Settings live in one option, and the log lives in another

**Decision.** All four settings share a single option, `volumina_settings`, and
the form posts to `options.php` so WordPress does the nonce, the capability and
the redirect. The log is a separate non-autoloaded option holding at most two
hundred entries, newest first.

**Why.** A screenful of settings read on nearly every request should be one row
and one sanitize callback, not fifteen of each. `options.php` is the flow the
directory's reviewers expect and the one least likely to be got wrong.

The log is an option rather than a table because it records **notable events** —
a listener turned away, a file gone missing, a schema brought up to date — and
not requests. Two events in the same instant can lose one of each other, since
this reads, appends and writes back. That is the trade for needing no table, and
it is the right trade at this volume. If it ever wants to record something that
happens on every request, it wants a table, and `Support\Logger` should not be
the thing that grows into one.

**Consequence.** `Support\Settings\Group` sanitises the whole option in one
place, and a key nobody declared never reaches the database. A setting missing
from a posted form keeps its default rather than being coerced from nothing —
found by a test, which is exactly what it was written for.

## 2026-09-06 — A screen that does not apply is never registered

**Decision.** The log screen exists only while logging is on; the setup screen
only until setup is finished; the Pro screen only where Pro is not installed.
Not hidden, not greyed out — not registered, so the page 404s or 403s if
somebody reaches for its URL.

**Why.** A plugin that fills the sidebar with pages that explain why they are
empty is a plugin that feels bigger than it is. It also means the capability
check and the applies check are the same gate, rather than a menu that hides
something still reachable.

**Consequence.** `Screen::applies()` is part of the published contract, and both
of the plugin's own conditional screens exercise it. Turning logging off makes
its screen disappear mid-session, which is the intended behaviour and worth
knowing before it surprises somebody.

## 2026-09-06 — A registered default is not an empty value

**Decision.** `Admin\Settings::apply_default_mode()` asks `metadata_exists()`,
never `get_post_meta()`, to decide whether a book has chosen an access mode.

**Why.** Found by running it. `volumina_access` is registered with a default, so
`get_post_meta()` returns `public` for a book that has no row at all, and the
check "has this book already got one?" was true for every book. New books
silently kept `public` however the site default was set. Any registered meta
with a default has this trap.

**Consequence.** The site default now reaches new books and only new books. An
existing book keeps what it had, which is the half that matters: a setting
changed today must not reach back and take a published book away from its
listeners.
