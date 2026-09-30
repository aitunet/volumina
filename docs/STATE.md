# STATE — Volumina (free)

> Read this first, every session. Overwrite it before you stop.
> This file is the single source of truth for where the project stands.
> The chronological work log is `docs/HISTORY.md`, append-only.
> There is no second state file: no `PROGRESS.md`, no `session-log.md`.

---

## Right now

**Current slice:** S11 — Appearance tab — DONE 2026-09-29, final-review fixes applied the same day (branch `s11-appearance`, not merged), plan `C:/Proyectos/volumina-pro/docs/superpowers/plans/2026-09-29-appearance-tab.md`. Part 2 (Tasks 6-9) is Volumina Pro's, done on its branch `p3-templates` (0.5.0 built, final-review fixes applied).
**Next action (2026-09-30):** colour picker always in view, live on wp2.desarrollotunet.com/tunet-tests; account holder re-checks custom accent and background there; then merge `s11-appearance` (and Pro's `p3-templates`), then the submission.
**Done 2026-09-30, agreed with the account holder before submission — custom colours:** free: the accent is a select (template's / none / palette / Custom… with WP's colour picker), stored as a slug or `#rrggbb`; Pro: Look fields as selects, Background with Custom… painting the whole panel in Pulse/Vinyl/Stage (player only elsewhere), inks computed to AA, glows toned down where text sits, Contrast card and preview in step. Verified on testing-site.local (dark red + yellow and cream on Stage, Vinyl, Pulse at 390/1280, axe 0) and live on wp2.desarrollotunet.com/tunet-tests (fields, saved values kept, preview and Contrast). Zips rebuilt and uploaded (Volumina 127 files, Pro 77).
**Done 2026-09-30, account holder's review of S11 (polish):** all seven points fixed and verified on testing-site.local (tunet-starter 390/1280, Twenty Twenty-Five 390, all four templates, nothing outside the book; Appearance preview of all four inside its card; axe 0), both repos committed, zips rebuilt (Volumina 125 files, Pro 0.5.0 75 files). testing-site.local back on Basic and tunet-starter. WP-CLI tip: `option patch` needs `< /dev/null` or it hangs on stdin. **Open:** `phpstan.neon` is missing from both working trees (vanished mid-session, not by a command here; Windows refuses to recreate it, EPERM, a pending delete held by some process) — run `git restore -- phpstan.neon` in both repos after closing editors or a reboot; nothing committed deletes it. **Next:** upload both zips to wp2.desarrollotunet.com/tunet-tests and purge Cloudflare (the CSS keeps `?ver=1.0.0`, so the old files would be served), then the account holder's acceptance, then merge.
**2026-09-20:** pre-submission security pass done and Plugin Check run against the release zip: zero errors, one warning kept on purpose (`load_plugin_textdomain`, needed for the shipped translations). Four security fixes landed — unpublished and trashed chapters no longer stream or list for listeners, a chapter joins a book only by somebody who may edit the book, chapters are out of REST, a crafted settings array no longer raises a warning. **One behaviour changed that the account holder should know:** a chapter saved as a draft is now invisible to listeners until published; the book's editors still see and hear it. See `docs/decisions.md`. Zip rebuilt, 114 files, `composer.json` now inside as the directory asks. Later the same day: the Live Link audio failure traced to `X-Local-Host` on the audio response, fixed with `header_remove()` in `Stream::headers()`, verified byte-for-byte through the tunnel; zip rebuilt with it (`bin/build-release.php --composer="C:/Users/mage2/tools/php83/php.exe C:/Users/mage2/tools/composer.phar"`).
**Decided 2026-09-14:** the three ideas go into 1.0 before submission, as S7. See `docs/decisions.md`.
**Blocked on:** nothing. `testing-site.local` (Local by Flywheel — nginx, MySQL, PHP 8.2) runs the current code, copied file by file from the working tree. WP-CLI reaches it from here with `php -d extension=mysqli -d mysqli.default_port=10053 wp-cli.phar` from the site's `public/`, read-only use only. Docker is still absent, so `wp-env` and the WordPress integration suite have never run; the PHPUnit suite is plain unit tests over `src/Support/` and `src/Access/`. See `docs/decisions.md`.
**Last updated:** 2026-09-30, TUNET, colour picker always in view
**File scope for this slice** — do not open files outside this list:
`src/Support/Settings/`, `src/Admin/Settings.php`, `src/Admin/SettingsScreen.php`, `src/Admin/AppearancePreview.php`, `src/Frontend/Template.php`, `src/Frontend/Templates.php`, `assets/css/admin-presentation.css`, `assets/js/appearance-preview.js`, `assets/images/templates/`, `tests/php/`, `docs/api.md`, `docs/decisions.md`, `docs/backlog.md`, `languages/`, `readme.txt`

---

## Release target

**v1.0 is finished when every slice below is checked and the release checklist passes.** S7 was added on 2026-09-14 by decision; see `docs/decisions.md`.
Anything else belongs in `docs/backlog.md`. Nothing is added to a slice once it has started.

Progress: 11 of 11 slices complete (S10 and S11 added after S9); S9 added 2026-09-20. What is left is the account holder's: their own checks, the guide's URL, and the submission.

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
- [x] One-time setup wizard — **removed on 2026-09-14** by decision: the settings are the setup, see `docs/decisions.md`
- [x] Presentational Pro screen: screenshots and illustrations only. The directory's Detailed Plugin Guidelines forbid shipping functionality that is locked and unlocked by paying, so no real code sits disabled waiting for a licence
- [x] Screens that do not apply are not registered

**Verification:** met. From a clean install: the notice offers setup, setup asks two questions and then disappears for good — its URL answers 403 afterwards, because the page is never registered. A new audiobook screen shows seven fields each with its own explanation and four help tabs, and the empty chapter list explains what a chapter is and offers "Add a chapter", which lands on a chapter form with the book already chosen. Settings saves with a confirmation; the Log screen appears the moment logging is on; the Pro screen carries zero inputs and zero disabled controls.

`uninstall.php` was run both ways against a copy of the database — nothing touched with the opt-in off, everything gone with it on — and the copy restored.

## S6 — Release

- [x] `readme.txt` with short description under 150 characters — 130
- [x] Icon — an animated GIF, 256×256 and 128×128, plays once — banner 1544×500, screenshots — six, of real content, in `.wordpress-org/`
- [x] Security pass against the checklist in `CLAUDE.md` — every item, and then a real audit; **repeated on 2026-09-20 over the whole plugin, four fixes, see `docs/HISTORY.md`**: stored XSS in every editable field, a `</script>` break-out through the player payload, a valid nonce in a subscriber's hands, six SQL payloads through every id, an attachment pointed outside uploads, blocks asked for a draft book, a crafted settings form, and range header abuse. Nothing gave way. See `docs/HISTORY.md`, 2026-09-06.
- [x] `.pot` regenerated — 287 strings at 1.0.0
- [x] `es_ES` translation complete — 283 of 287, verified rendering
- [x] `pt_BR` translation complete — 283 of 287, verified rendering
- [x] Tested against the current WordPress release — WordPress 7.1, plugin active at 1.0.0, no notices
- [x] Release zip built by a script that lives in the repository — `bin/build-release.php`, from `.distignore`; 121 files on 2026-09-20 with S8 (91 at first build), opened and read, and installed from the zip itself on the local WordPress: active, both tables, all three blocks, a book page rendered, a 206 from the audio endpoint, no `debug.log`
- [ ] Submitted to the plugin directory — **only the account holder can do this.** The zip is built and checked; see the next action.

**Verification:** the checklist below passes but for the submission itself, which is not something this repository can do.

---

## S7 — Presentation (added 2026-09-14, before submission; see `docs/decisions.md`)

Three things the account holder wants in the first version people see. Each begins with a design pass agreed with them, then code. Order: Pro screen, welcome page, template.

- [x] **Pro screen, eye-catching.** Purely presentational: a hero, the mark, what Pro adds shown with illustrations or screenshots, one call to action. Zero inputs, zero disabled controls — the directory forbids locked functionality. Built and verified 2026-09-14: dark hero, five illustrated cards, the note, one call to action as a link; zero axe violations in the real admin, phone layout checked, translated. The account holder looked at it in their own admin and said yes on 2026-09-14.
- [x] **Welcome page.** Shown once after activation, reachable later from the menu: what the plugin is and is not (no store, no DRM), three steps to a first audiobook, links to the setup and the guide. Registered through the screen registry so Pro can add a section. Built and verified 2026-09-14: redirect on activation seen on the Local site, zero axe violations, translated; Pro adds a section through `volumina_welcome_sections`. Accepted by the account holder on 2026-09-14.
- [x] **Basic audiobook template, customisable, with the seam for Pro's.** One free template for a book page — which details show, where the cover sits, an accent from the theme's palette — and a documented registry or filter in `docs/api.md` through which Pro registers its featured templates with advanced styling. Settle the shape of "basic" versus "advanced" once, in the design pass. Settled and built 2026-09-14: Basic (cover beside the details) with cover position and accent in Settings and per block; `volumina_register_templates` and the stylesheet contract in `docs/api.md`; a throwaway "Paper" template registered from outside. Accepted by the account holder on 2026-09-14.
- [x] `.pot` regenerated — 329 strings — `es_ES` and `pt_BR` complete for every new string, the block editor's JSON generated, zip rebuilt.

**Done when:** all three render correctly in Twenty Twenty-Five and in one classic theme where they touch the front end; the admin screens pass axe; the account holder has looked at each and said yes; the release checklist below still passes.

**File scope:** `src/Admin/`, `src/Support/` (screen registry only), `src/Blocks/`, `templates/`, `assets/`, `blocks/`, `docs/api.md`, `languages/`, `readme.txt`.

## S8 — Library block (added 2026-09-20, before submission; see `docs/decisions.md`)

- [x] `volumina/library` block: rows of published books — cover, linked title, narrator, running time; count, order, genre, series, three toggles, presentation panel
- [x] Editor preview is the server's render (`ServerSideRender`, as the other three); controls translated, every panel string in the editor JSON. Looked at by the account holder on 2026-09-20: the genre and series pickers did nothing there, fixed the same day (a float term id), accepted
- [x] `volumina_library_query`, `volumina_library_item` and `volumina_book_details` in `docs/api.md`, each exercised live with a throwaway callback
- [x] Renders correctly in Twenty Twenty-Five and Twenty Twenty-One, 390px and 1280px; zero axe violations, no sideways scroll
- [x] `.pot` regenerated — 347 entries — `es_ES` and `pt_BR` complete (0 untranslated), editor JSON for the four blocks, zip rebuilt

**Done when:** a page with the block lists the library and each row reaches its book; the checklist below still passes.

---

## S9 — The API Pro needs (added 2026-09-20; see `docs/decisions.md`)

- [x] `volumina_progress_saved` action, verified live
- [x] `volumina_stream_access` filter, verified live
- [x] `VOLUMINA_PRO_VERSION` documented (the code was already there)
- [x] Zip rebuilt

**Done when:** all three are in `api.md` and the release checklist still passes.

## S10 — Settings tabs (added 2026-09-25; see `docs/decisions.md`)

- [x] Design of the tabs agreed in the Pro P3 mockups — accepted by the account holder 2026-09-25
- [x] Public API: an action that hands a tab registry to extensions, same rules as `volumina_welcome_sections`; documented in `api.md` — `volumina_settings_tabs`, `TUNET\Volumina\Admin\Tabs::current()`, `docs/api.md` written
- [x] Settings draws tabs only when there is more than one; the plugin's own settings are the first tab; each tab its own form — `SettingsScreen::render()` / `render_general()`, verified on `testing-site.local`
- [x] Tests; `.pot`; zip rebuilt — PHPCS and PHPStan level 6 silent, `npm run lint` clean, 149 tests/282 assertions; `.pot` regenerated at 349 entries, two new strings ("General", "Settings sections"); zip rebuilt, 122 files, `diff -rq` of the unzipped `src/ templates/ assets/ languages/` against the tree clean (only `.gitkeep` differs)
- [x] axe (WCAG 2.1 AA) and keyboard check on Audiobooks → Settings with several tabs — done 2026-09-29 in S11 Task 5: zero violations on General and Appearance, keyboard passes

**Done when:** a throwaway plugin adds a tab through `api.md` alone and Volumina Pro's settings move into one, with the release checklist still passing.

## S11 — Appearance tab (added 2026-09-29)

- [x] Task 1 Partial saves in Support\Settings\Group
- [x] Task 2 cards field type and Template preview picture (basic.png made 2026-09-29, 480x300, 36 KB)
- [x] Task 3 The Appearance tab (site check Step 7 passed 2026-09-29)
- [x] Task 4 The live preview (site check done, sideways scroll fixed)
- [x] Task 5 S11 wrap-up (.pot 353 entries, readme, axe zero violations + keyboard, zip 125 files)
- [x] Final-review fixes: preview cut to a sample (no audio source, no audio bar, three chapters, button above), crafted `_sections` keeps stored values, api.md (form selector, `APPEARANCE_GROUP` marker, event timing), readme, decisions, backlog — zip 125 files

---

## Release checklist

- [x] `composer lint` and `npm run lint` clean — PHPCS and PHPStan level 6 both silent
- [x] Full test suite green — 164 tests, 312 assertions (2026-09-29, S11 final-review fixes)
- [x] Plugin Check (2026-09-20, 1.7, every check including experimental) against the release zip's contents — zero errors; the one warning is `load_plugin_textdomain()`, kept because the shipped translations need it
- [x] Zero PHP notices on a clean install — `debug.log` absent after exercising the front end, the admin and WP-CLI
- [x] Zero axe violations on all front-end output — WCAG 2.1 AA, block theme and classic, 390px and desktop; the Pro screen, the welcome page and the settings screen too, zero to review
- [x] Every string translatable — 353 in the `.pot` after S11, JSON for the block editor, and forced-locale renders of the front end, the settings, the notice and the welcome page left nothing in English
- [x] Uninstall removes tables and options behind the opt-in — run both ways against a copy of the database, again on 2026-09-14: zero left
- [x] No functionality locked behind payment inside this plugin — the Pro screen has zero inputs and zero disabled controls
- [x] Version bumped in header and `readme.txt`, changelog written — 1.0.0 in four places

---

## Left for the account holder

Updated 2026-09-20 from the account holder's own report:

- **Listen on a real phone.** Done: a nine-hour book through Live Link, with the
  screen locked, plays and resumes.
- **Real audio files.** MP3s tried and fine. The 200 MB file was never found;
  **decided:** not pursued — if anybody ever reports a problem with a file that
  size, it is looked at then.
- **PHP 8.1.** **Decided:** accepted as declared; 8.2 and 8.3 have run it and
  nothing in the code is newer than 8.1 (PHPStan is set to 8.1).
- **A second real person.** The tester's guide exists (`docs/tester-guide-es.html`);
  whether it was handed out is not recorded.

Still open, and none of it is code:

- **Install on real hosting.** A shared host: a different PHP build,
  `open_basedir`, an object cache, and a proxy that may buffer or strip range
  requests, which is where a nine-hour book stops resuming.
- **The guide's URL.** "Read the guide" points at `https://tunetdesign.com/volumina/`
  until the site is live; put the real address in `WelcomeScreen::GUIDE_URL`
  and rebuild the zip. The account holder says the URL is coming soon.
- **The submission** to the plugin directory.

Meanwhile, work moves to `volumina-pro` — `https://github.com/aitunet/volumina-pro`,
checked out beside this one — which extends this plugin only through `docs/api.md`.

## Open decisions not blocking code

- None. The icon's centre block was settled on 2026-09-06: the wide one, drawn both ways and compared.
