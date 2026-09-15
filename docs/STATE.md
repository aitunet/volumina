# STATE — Volumina (free)

> Read this first, every session. Overwrite it before you stop.
> This file is the single source of truth for where the project stands.
> The chronological work log is `docs/HISTORY.md`, append-only.
> There is no second state file: no `PROGRESS.md`, no `session-log.md`.

---

## Right now

**Current slice:** S7 — Presentation, the last before submission. All three tasks are built and verified; the Pro screen and the welcome page were accepted by the account holder, the template awaits their look.
**Next action:** (1) the account holder looks at the template on `testing-site.local` — a book page, and Audiobooks → Settings for Cover and Accent colour, and the Presentation panel on the Audiobook block — and says yes or asks for changes; (2) they publish `docs/guide-es.md` and the URL goes into `WelcomeScreen::GUIDE_URL`; (3) the tour was judged unnecessary once the welcome page existed — they can overrule. Then S7 closes and the release checklist runs again. After S7: the account holder's checks (real phone, real hosting) and the submission — upload `dist/volumina-1.0.0.zip` at wordpress.org/plugins/developers/add/, then `.wordpress-org/` into SVN's `assets/`. Rebuild the zip with `php bin/build-release.php` (`--composer="php /path/to/composer.phar"` where Composer is not on the PATH) after any change.
**Decided 2026-09-14:** the three ideas go into 1.0 before submission, as S7. See `docs/decisions.md`.
**Blocked on:** nothing. `testing-site.local` (Local by Flywheel — nginx, MySQL, PHP 8.2) runs the current code, copied file by file from the working tree. WP-CLI reaches it from here with `php -d extension=mysqli -d mysqli.default_port=10053 wp-cli.phar` from the site's `public/`, read-only use only. Docker is still absent, so `wp-env` and the WordPress integration suite have never run; the PHPUnit suite is plain unit tests over `src/Support/` and `src/Access/`. See `docs/decisions.md`.
**Last updated:** 2026-09-14, TUNET, template done
**File scope for this slice** — do not open files outside this list (S7's own list is below):
`readme.txt`, `assets/`, `languages/`, `volumina.php`, `bin/build-release.php`, `.distignore`

---

## Release target

**v1.0 is finished when every slice below is checked and the release checklist passes.** S7 was added on 2026-09-14 by decision; see `docs/decisions.md`.
Anything else belongs in `docs/backlog.md`. Nothing is added to a slice once it has started.

Progress: 6 of 7 slices complete. S6 is done but for the checks only the account holder can make and the submission itself, which now waits for S7.

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
- [x] Security pass against the checklist in `CLAUDE.md` — every item, and then a real audit: stored XSS in every editable field, a `</script>` break-out through the player payload, a valid nonce in a subscriber's hands, six SQL payloads through every id, an attachment pointed outside uploads, blocks asked for a draft book, a crafted settings form, and range header abuse. Nothing gave way. See `docs/HISTORY.md`, 2026-09-06.
- [x] `.pot` regenerated — 287 strings at 1.0.0
- [x] `es_ES` translation complete — 283 of 287, verified rendering
- [x] `pt_BR` translation complete — 283 of 287, verified rendering
- [x] Tested against the current WordPress release — WordPress 7.1, plugin active at 1.0.0, no notices
- [x] Release zip built by a script that lives in the repository — `bin/build-release.php`, from `.distignore`; 91 files, opened and read, and installed from the zip itself on the local WordPress: active, both tables, all three blocks, a book page rendered, a 206 from the audio endpoint, no `debug.log`
- [ ] Submitted to the plugin directory — **only the account holder can do this.** The zip is built and checked; see the next action.

**Verification:** the checklist below passes but for the submission itself, which is not something this repository can do.

---

## S7 — Presentation (added 2026-09-14, before submission; see `docs/decisions.md`)

Three things the account holder wants in the first version people see. Each begins with a design pass agreed with them, then code. Order: Pro screen, welcome page, template.

- [x] **Pro screen, eye-catching.** Purely presentational: a hero, the mark, what Pro adds shown with illustrations or screenshots, one call to action. Zero inputs, zero disabled controls — the directory forbids locked functionality. Built and verified 2026-09-14: dark hero, five illustrated cards, the note, one call to action as a link; zero axe violations in the real admin, phone layout checked, translated. The account holder looked at it in their own admin and said yes on 2026-09-14.
- [x] **Welcome page.** Shown once after activation, reachable later from the menu: what the plugin is and is not (no store, no DRM), three steps to a first audiobook, links to the setup and the guide. Registered through the screen registry so Pro can add a section. Built and verified 2026-09-14: redirect on activation seen on the Local site, zero axe violations, translated; Pro adds a section through `volumina_welcome_sections`. Awaiting the account holder's look, and the guide URL.
- [x] **Basic audiobook template, customisable, with the seam for Pro's.** One free template for a book page — which details show, where the cover sits, an accent from the theme's palette — and a documented registry or filter in `docs/api.md` through which Pro registers its featured templates with advanced styling. Settle the shape of "basic" versus "advanced" once, in the design pass. Settled and built 2026-09-14: Basic (cover beside the details) with cover position and accent in Settings and per block; `volumina_register_templates` and the stylesheet contract in `docs/api.md`; a throwaway "Paper" template registered from outside. Awaiting the account holder's look.
- [x] `.pot` regenerated — 336 strings — `es_ES` and `pt_BR` complete for every new string, the block editor's JSON generated, zip rebuilt.

**Done when:** all three render correctly in Twenty Twenty-Five and in one classic theme where they touch the front end; the admin screens pass axe; the account holder has looked at each and said yes; the release checklist below still passes.

**File scope:** `src/Admin/`, `src/Support/` (screen registry only), `src/Blocks/`, `templates/`, `assets/`, `blocks/`, `docs/api.md`, `languages/`, `readme.txt`.

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

## Left for the account holder

Everything that can be verified on this machine has been. These cannot be, and
none of them is a formality:

- **Listen on a real phone.** Owed since S2. A nine-hour book, across two
  sessions, with the screen locked and the lock-screen controls used. Emulation
  got the layout and the touch targets right; it cannot tell you whether the
  audio survives a phone call, a backgrounded tab or a change of network.
- **Install on real hosting.** Local by Flywheel (nginx, MySQL, PHP 8.2) is
  now covered, range requests included. A shared host still is not: a
  different PHP build, `open_basedir`, an object cache, and a proxy that may
  buffer or strip range requests, which is where a nine-hour book stops
  resuming.
- **Real audio files.** Every test here used generated WAV — one 30-minute file, then 24 short ones. Try an MP3, an
  M4A, a 200 MB file and a file with an unusual sample rate, and check the
  running time WordPress reads out of each.
- **A second real person.** Give somebody a restricted book, have them listen,
  take it away, and confirm they are turned out.
- **PHP 8.1.** The floor is declared as 8.1; 8.2 and 8.3 have now run it.
- **The guide's URL.** "Read the guide" on the welcome page points at
  `https://tunetdesign.com/volumina/` until the guide is published; put the
  real address in `WelcomeScreen::GUIDE_URL` before the zip is built for
  submission.

## Open decisions not blocking code

- None. The icon's centre block was settled on 2026-09-06: the wide one, drawn both ways and compared.
