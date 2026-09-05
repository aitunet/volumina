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
