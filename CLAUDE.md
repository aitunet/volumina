# CLAUDE.md — Volumina

Place at the root of the `volumina` repository. Read this file at the start of every session.

---

## Session protocol — do this every time, without being asked

**The state lives in `docs/STATE.md` and the work log lives in `docs/HISTORY.md`. There is no second state file.** `docs/STATE.md` is overwritten and always describes the present; `docs/HISTORY.md` is append-only. Never create a `PROGRESS.md`, a `session-log.md`, or any other parallel progress file.

**First action of every session:**
1. Read `docs/STATE.md`. It tells you the current slice, the next action, and anything blocked.
2. Read the last entries of `docs/HISTORY.md` for recent context.
3. Read `docs/decisions.md` only if the task touches an architectural choice.
4. Do not read the whole repository. Do not summarise the codebase back to the user.

**During the session:**
- Append a line to `docs/HISTORY.md` after **every completed task**, not at the end of the session. Power cuts and dropped connections are expected; at most one task of work should ever be lost.
- Update the task's checkbox in `docs/STATE.md` the moment it passes its verification.

**Last action of every session:**
1. Update `docs/STATE.md`: current slice, next action, blockers, `Last updated`.
2. Append a session summary to `docs/HISTORY.md`.
3. Commit code, `docs/STATE.md` and `docs/HISTORY.md` together.

**Never ask the user "what should we work on next?"** `docs/STATE.md` answers that. If it is ambiguous, fix `docs/STATE.md` first, then proceed.

## Token discipline

Context is the scarce resource. These are rules, not suggestions.

- Work only inside the current slice's file scope, listed in `docs/STATE.md`. Do not open files outside it "for context".
- Search before reading: use grep to find the symbol, then read only the relevant region of the file. Never read a file whole to find one function.
- Never re-read a file you already read this session unless you changed it.
- Never paste file contents back to the user to show what you read.
- Never restate the plan, the architecture, or this file. The user has read them.
- Prefer targeted edits over rewriting whole files.
- Run the narrowest test that proves the change. Full suite only before a slice is marked done.
- Keep replies short. Report what changed and what is next, nothing else.
- When a task is genuinely ambiguous, ask one short question. Do not write three paragraphs of options.

## Product

**Volumina — Audiobook Player and Library.** Publish audiobooks on WordPress and give listeners a real listening experience: chapters, resumed position, speed, sleep timer.

- Slug: `volumina`
- Namespace: `TUNET\Volumina`
- Prefix (functions, hooks, options, tables, CSS): `volumina_` / `volumina-`
- Text domain: `volumina`
- Author: TUNET · https://tunetdesign.com · ai@tunetdesign.com
- `Contributors: tunetdesign`
- License: GPL-2.0-or-later
- Requires: WordPress 6.6, PHP 8.1

Code, comments, commit messages and source strings in **English**. Shipped translations: `es_ES` and `pt_BR`. Every user-facing string wrapped for translation from the first commit — retrofitting i18n is a hundred-string nightmare.

## What this plugin is and is not

**Is:** publishing and playback. A long-form listening experience that music players do not provide.

**Is not:** a store. No cart, no checkout, no coupons, no tax handling. Selling, protected streaming and store integrations live in the separate `volumina-pro` plugin.

**Never promise DRM.** Browser audio cannot be truly protected. The free plugin serves files normally; Pro adds signed, expiring URLs that deter casual sharing. Any copy claiming more than that is a lie that gets punished in reviews.

**Mobile first.** Smartphones are the dominant listening device. The player is designed for a phone and adapted upward, never the reverse.

## Architecture

### Data model — settle this before any UI

```
volumina_book      CPT   one audiobook
  meta: narrator, publisher, isbn, language, total_duration, cover_id
  tax:  volumina_genre, volumina_series

volumina_chapter   CPT   one chapter, child of a book
  meta: book_id, attachment_id, duration, order

wp_volumina_progress   custom table
  user_id, book_id, chapter_id, position_seconds, updated_at
  unique key (user_id, book_id)
```

Progress goes in a custom table, not post meta: it is high-write, per-user, machine data with no editorial life, and it would poison `wp_postmeta`.

Chapters are a CPT rather than repeater meta so each chapter has its own ID for progress, bookmarks and future per-chapter features.

Store durations and positions as integers in seconds. Never floats.

### Modularity

One responsibility per class, one class per file, autoloaded via Composer PSR-4.

```
volumina/
├── volumina.php              header + bootstrap only, no logic
├── readme.txt
├── CLAUDE.md
├── uninstall.php
├── docs/
│   ├── STATE.md              where the project stands, overwritten
│   ├── HISTORY.md            work log, append-only
│   ├── decisions.md          architectural choices, append-only
│   ├── backlog.md            everything out of scope for v1.0
│   └── api.md                the public extension API Pro consumes
├── src/
│   ├── Plugin.php            wiring only
│   ├── PostTypes/
│   ├── Storage/              custom table, migrations
│   ├── Access/               access contract + manual grants
│   ├── Player/               server side of the player
│   ├── Blocks/               PHP render callbacks
│   ├── Admin/
│   ├── Api/                  REST routes
│   └── Support/              extractable scaffolding, see below
├── blocks/                   block.json + JS per block
├── assets/
├── languages/
└── tests/{php,e2e}/
```

`src/Support/` is **TUNET scaffolding built to be extracted**: settings framework, admin notices, help tabs, logger, license screen shell. It must have zero knowledge of audiobooks. The next TUNET plugin inherits this directory unchanged. If a class in `Support/` mentions a book, a chapter or a player, it is in the wrong place.

### The public extension API — the most important design constraint

`volumina-pro` is a **separate plugin** that extends this one from the outside. It may only use the documented API. If Pro ever reaches into internals, both break on every release.

Therefore:
- Every extension point is an action, a filter, or an interface, documented in `docs/api.md` at the moment it is created.
- Access resolution runs through `TUNET\Volumina\Access\AccessManager`, which holds a registry of `AccessProvider` implementations. Free ships `ManualProvider` and `PublicProvider`. Pro registers WooCommerce and EDD providers through `volumina_register_access_providers`.
- Audio URL generation runs through a filter, `volumina_chapter_audio_url`, so Pro can substitute signed URLs.
- The admin screen registry accepts new screens, so Pro adds its own without patching menus.
- Anything in `docs/api.md` is a contract. Breaking it requires a major version bump and an entry in `docs/decisions.md`.

## Start and end points

**Start:** an empty repository.

**End — v1.0 is finished when all six slices below are checked off in `docs/STATE.md` and the release checklist passes.** Not when it feels complete. Not when someone thinks of another feature.

Anything not in these six slices goes to `docs/backlog.md`. Nothing enters a slice mid-flight. This rule is what makes the project finishable.

### Slices

**S0 — Scaffold.** First the `docs/` system itself — `STATE.md`, `HISTORY.md`, `decisions.md`, `backlog.md`, `api.md` — so that every task after it has somewhere to be recorded. Then Composer with PSR-4, `@wordpress/scripts`, `wp-env`, PHPCS with WordPress standards, PHPStan level 6, GitHub Actions running lint and tests, and i18n wiring with `.pot` generation.
*Done when:* `wp-env start` gives a running site with the plugin active and zero notices, and CI is green.

**S1 — One audiobook, end to end.** Both CPTs, meta registration, the progress table with migration, an admin editor that creates a book and orders its chapters by drag, and a minimal front-end render.
*Done when:* a real audiobook entered by hand renders on the front end with its chapters in order.

**S2 — The player.** This is the product. Chapter navigation, position saved per listener and restored, playback speed, 15/30-second skips, sleep timer, survives page navigation, HTTP range requests supported.
*Done when:* a nine-hour book is listenable across three sessions on a phone, resuming correctly each time; keyboard navigation complete; no axe violations; all text pairs meet AA contrast.

**S3 — Blocks.** Audiobook, Chapter list, Continue listening. Block theme native, no jQuery, no front-end framework.
*Done when:* all three render correctly in Twenty Twenty-Five and in one classic theme, and the editor previews match the front end.

**S4 — Access layer and public API.** `AccessManager`, the provider interface, `ManualProvider`, `PublicProvider`, the audio URL filter, the screen registry, and `docs/api.md` written.
*Done when:* a throwaway test plugin can register its own access provider and grant access without touching Volumina internals.

**S5 — Admin polish.** Settings, log screen, native Help tabs on every screen, field descriptions, a one-time setup wizard, and the presentational Pro screen. The directory's Detailed Plugin Guidelines forbid shipping functionality that is locked and unlocked by paying, so the Pro screen in the free plugin is purely presentational: screenshots and illustrations, never real code sitting disabled waiting for a licence.
*Done when:* a first-time user publishes an audiobook without reading documentation, and screens that do not apply are not registered at all.

**S6 — Release.** `readme.txt`, 256×256 icon, 1544×500 banner, screenshots, full security pass, `.pot` regenerated, `es_ES` and `pt_BR` translations complete.
*Done when:* the release checklist below passes and the plugin is submitted.

## Security — a condition of every task, not a final pass

The directory reviews every version you publish. Build it in.

- Capability check **and** nonce on every write. No exceptions for admin-only code.
- Sanitize on input, escape on output, at the point of use.
- `$wpdb->prepare` for every query with a variable. No exceptions.
- `defined('ABSPATH') || exit;` at the top of every PHP file.
- No `eval`, no `extract`, no unserialize of user input.
- No remote requests without explicit user consent.
- Enqueue scripts and styles properly; never echo tags.
- Audio streaming endpoint validates access on every request, never trusts a URL parameter alone.
- File uploads restricted to audio MIME types, validated server side.
- `uninstall.php` removes tables and options, behind an explicit opt-in setting.

## Definition of done for any task

1. `composer lint` and `npm run lint` clean.
2. Tests pass; new behaviour has a test.
3. New strings translatable, `.pot` regenerated.
4. Front-end output passes axe — **only for tasks that produce visible front-end output**. Scaffolding tasks mark this not applicable.
5. Verified on a block theme and a classic theme — **only for tasks that produce visible front-end output**. Scaffolding tasks mark this not applicable.
6. `docs/STATE.md` checkbox updated and `docs/HISTORY.md` appended.

## Working style

- Settle the schema before the UI. If a task requires a schema change, stop and say so.
- Prefer core APIs. Every new dependency needs a stated reason recorded in `docs/decisions.md`.
- Small commits, conventional messages, one slice per branch.
- When you notice something worth doing that is not in the current slice, write it in `docs/backlog.md` and keep going. Do not do it.
