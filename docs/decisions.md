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
