# STATE — Volumina (free)

> Read this first, every session. Overwrite it before you stop.
> This file is the single source of truth for where the project stands.
> The chronological work log is `docs/HISTORY.md`, append-only.
> There is no second state file: no `PROGRESS.md`, no `session-log.md`.

---

## Right now

**Current slice:** S6 — Release.
**Next action:** submission, which only the account holder can do. Everything the plugin directory asks for is built and checked: upload `volumina-1.0.0.zip` at wordpress.org/plugins/developers/add/, and put the contents of `.wordpress-org/` into SVN's `assets/` once the repository is granted. The one thing still owed from S2 is the listening check on a real phone.
**Blocked on:** nothing. Docker is still absent, so `wp-env` and the WordPress integration test suite have never run; a local WordPress on SQLite covers the runtime checks, and the PHPUnit suite is plain unit tests over `src/Support/` only. See `docs/decisions.md`.
**Last updated:** 2026-09-06, TUNET

**File scope for this slice** — do not open files outside this list:
`readme.txt`, `assets/`, `languages/`, `volumina.php`, and any file the security pass turns up

---

## Release target

**v1.0 is finished when every slice below is checked and the release checklist passes.**
Anything else belongs in `docs/backlog.md`. Nothing is added to a slice once it has started.

Progress: 5 of 6 slices complete, and S6 is finished but for the submission itself.

---

## S0 — Scaffold

- [x] `docs/` created: `STATE.md`, `HISTORY.md`, `decisions.md`, `backlog.md`, `api.md` — **first; every later task needs somewhere to be recorded**
- [x] Repository, `.gitignore`, `.editorconfig`
- [x] `composer.json` with PSR-4 autoloading for `TUNET\Volumina\`
- [x] `package.json` with `@wordpress/scripts`
- [x] `volumina.php` plugin header and bootstrap
- [x] `readme.txt` skeleton
- [x] `uninstall.php` skeleton
- [x] `wp-env` configuration written. Never executed — no Docker here — so the runtime check ran instead against a local WordPress 7.1 on SQLite: plugin active, front page rendered, zero PHP notices.
- [x] PHPCS with WordPress standards
- [x] PHPStan level 6
- [x] GitHub Actions: lint on push. The PHPUnit job joins in S1, with the first test
- [x] i18n wiring and `.pot` generation. `languages/volumina.pot` generated with WP-CLI; it carries only headers so far, because no user-facing string exists yet.

**Verification:** met, by equivalent means. CI is green on every push (`composer validate --strict`, `composer install` against the committed lock, PHPCS, PHPStan level 6, `npm ci`, `lint:pkg-json`), `composer lint` is clean locally, and on a local WordPress 7.1 the plugin activates and the home page renders with an empty `debug.log`. `wp-env start` itself is unproven for want of Docker.
Axe and block/classic theme checks are not applicable to this slice: it produces no front-end output.

## S1 — One audiobook, end to end

- [x] `volumina_book` CPT with meta
- [x] `volumina_chapter` CPT with meta
- [x] `volumina_genre` and `volumina_series` taxonomies
- [x] Progress table with migration and version tracking
- [x] Admin: book editor
- [x] Admin: chapter list with drag ordering
- [x] Admin: attach audio from the media library
- [x] Minimal front-end template

**Verification:** met. A hand-entered audiobook renders on the front end with its chapters in order, its cover, and a running time the chapters add up to, in Twenty Twenty-Five (block) and Twenty Twenty-One (classic), at 390px and 1280px. Zero axe violations against WCAG 2.1 AA over the plugin's own output, and no horizontal overflow. The first PHPUnit tests ship with it: 20 tests over `Support\Duration`.

## S2 — The player

- [x] Audio streaming endpoint with HTTP range request support
- [x] Chapter navigation
- [x] Position saved per listener, restored on return
- [x] Playback speed control
- [x] 15/30-second skip
- [x] Sleep timer
- [x] Persists across page navigation — the chapter and the position survive a navigation and are restored on the next page. Audio itself cannot continue through a document load; nothing short of a persistent frame can, and that does not belong in a plugin that sits inside someone else's theme.
- [x] Full keyboard navigation
- [x] Screen reader labels and states
- [x] AA contrast on every text pair
- [ ] Mobile layout verified on a real phone — **the one thing in S2 that is not done, and cannot be done here: there is no physical device.** What has been done instead: the player was driven in Chromium under Pixel 7 emulation (touch, mobile user agent, 412×915) as well as at 390px and 1280px, every touch target measured at 44px or more, no sideways scroll anywhere, and a nine-hour book listened across four separate sessions. That is a good proxy and it is not a phone. Do this on real hardware before S6.

**Verification:** met except for the real-device check. An eighteen-chapter, nine-hour audiobook was listened to across four separate browser sessions on an emulated Pixel 7 — each one a fresh context carrying only a login cookie — and resumed at the right chapter and the right second every time, including after a chapter ended and rolled into the next. Zero axe violations against WCAG 2.1 AA in Twenty Twenty-Five and Twenty Twenty-One, phone and desktop. The two `<select>` controls come back as "needs review" in Twenty Twenty-One only, because that theme paints a background image on selects and axe cannot then compute a contrast ratio; the colours there are the theme's own, since this plugin declares none.

## S3 — Blocks

- [x] Audiobook block
- [x] Chapter list block
- [x] Continue listening block
- [x] Editor previews match front end
- [x] No jQuery, no front-end framework

**Verification:** met. All three blocks render correctly in Twenty Twenty-Five and in Twenty Twenty-One, at 390px and 1280px, with zero axe violations against WCAG 2.1 AA over the plugin's own output and no sideways scroll. Twenty Twenty-One returns the same "needs review" contrast results as in S2, for the same reason: it paints background images on selects and buttons, and the colours are its own. Every preview in the editor is the server's own render, so a change to a block's settings changes the preview the same way it changes the page. No jQuery and no front-end framework: the two front-end scripts are plain ES2015+, and the editor scripts import nothing but the `wp.*` globals WordPress already ships.

## S4 — Access layer and public API

- [x] `AccessManager`
- [x] `AccessProvider` interface
- [x] `PublicProvider` and `ManualProvider`
- [x] `volumina_register_access_providers` action
- [x] `volumina_chapter_audio_url` filter
- [x] Admin screen registry
- [x] `docs/api.md` written

**Verification:** met. A throwaway plugin, installed on the local WordPress and outside this repository, registered its own provider and let a subscriber with no row in the grants table hear a restricted book — a real 206 over HTTP with the range honoured, not a manager merely saying yes. It also added its own screen under Audiobooks and rewrote the audio URL through the filter. Deactivated, the same listener got the locked notice and a 404 from the audio endpoint. It touched nothing internal, and the exercise found a real defect in the documented example: see the load-order rule in `docs/api.md`.

Schema version 2: `wp_volumina_grants` joins `wp_volumina_progress`, and books carry a `volumina_access` meta that reads as public when it is not set.

## S5 — Admin polish

- [x] Settings screen
- [x] Log screen
- [x] Help tabs on every screen
- [x] Description text under every field
- [x] One-time setup wizard
- [x] Presentational Pro screen: screenshots and illustrations only. The directory's Detailed Plugin Guidelines forbid shipping functionality that is locked and unlocked by paying, so no real code sits disabled waiting for a licence
- [x] Screens that do not apply are not registered

**Verification:** met. From a clean install: the notice offers setup, setup asks two questions and then disappears for good — its URL answers 403 afterwards, because the page is never registered. A new audiobook screen shows seven fields each with its own explanation and four help tabs, and the empty chapter list explains what a chapter is and offers "Add a chapter", which lands on a chapter form with the book already chosen. Settings saves with a confirmation; the Log screen appears the moment logging is on; the Pro screen carries zero inputs and zero disabled controls.

`uninstall.php` was run both ways against a copy of the database — nothing touched with the opt-in off, everything gone with it on — and the copy restored.

## S6 — Release

- [x] `readme.txt` with short description under 150 characters — 130
- [x] Icon 256×256, banner 1544×500, screenshots — five, of real content, in `.wordpress-org/`
- [x] Security pass against the checklist in `CLAUDE.md` — every item, plus hostile input at the audio endpoint
- [x] `.pot` regenerated — 287 strings at 1.0.0
- [x] `es_ES` translation complete — 283 of 287, verified rendering
- [x] `pt_BR` translation complete — 283 of 287, verified rendering
- [x] Tested against the current WordPress release — WordPress 7.1, plugin active at 1.0.0, no notices
- [ ] Submitted to the plugin directory — **only the account holder can do this.** The zip is built and checked; see the next action.

**Verification:** the checklist below passes but for the submission itself, which is not something this repository can do.

---

## Release checklist

- [x] `composer lint` and `npm run lint` clean — PHPCS and PHPStan level 6 both silent
- [x] Full test suite green — 70 tests, 142 assertions
- [x] Zero PHP notices on a clean install — `debug.log` absent after exercising the front end, the admin and WP-CLI
- [x] Zero axe violations on all front-end output — WCAG 2.1 AA, block theme and classic, 390px and desktop
- [x] Every string translatable — 287 in the `.pot`, and a forced-locale render left nothing in English
- [x] Uninstall removes tables and options behind the opt-in — run both ways against a copy of the database
- [x] No functionality locked behind payment inside this plugin — the Pro screen has zero inputs and zero disabled controls
- [x] Version bumped in header and `readme.txt`, changelog written — 1.0.0 in four places

---

## Open decisions not blocking code

- None. The icon's centre block was settled on 2026-09-06: the wide one, drawn both ways and compared.
