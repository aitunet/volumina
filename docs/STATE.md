# STATE — Volumina (free)

> Read this first, every session. Overwrite it before you stop.
> This file is the single source of truth for where the project stands.
> The chronological work log is `docs/HISTORY.md`, append-only.
> There is no second state file: no `PROGRESS.md`, no `session-log.md`.

---

## Right now

**Current slice:** S2 — The player. This is the product.
**Next action:** S3 — the Audiobook, Chapter list and Continue listening blocks. Before starting, decide whether the blocks render through the same `Player::render()` the front end already uses, or whether S3 replaces `Frontend\BookContent` outright.
**Blocked on:** nothing. Docker is still absent, so `wp-env` and the WordPress integration test suite have never run; a local WordPress on SQLite covers the runtime checks, and the PHPUnit suite is plain unit tests over `src/Support/` only. See `docs/decisions.md`.
**Last updated:** 2026-09-05, TUNET

**File scope for this slice** — do not open files outside this list:
`src/Player/`, `src/Api/`, `src/Storage/`, `src/Support/`, `src/Frontend/`, `templates/`, `tests/php/`, `assets/`

---

## Release target

**v1.0 is finished when every slice below is checked and the release checklist passes.**
Anything else belongs in `docs/backlog.md`. Nothing is added to a slice once it has started.

Progress: 2 of 6 slices complete.

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

- [ ] Audiobook block
- [ ] Chapter list block
- [ ] Continue listening block
- [ ] Editor previews match front end
- [ ] No jQuery, no front-end framework

**Verification:** all three blocks correct in Twenty Twenty-Five and in one classic theme.

## S4 — Access layer and public API

- [ ] `AccessManager`
- [ ] `AccessProvider` interface
- [ ] `PublicProvider` and `ManualProvider`
- [ ] `volumina_register_access_providers` action
- [ ] `volumina_chapter_audio_url` filter
- [ ] Admin screen registry
- [ ] `docs/api.md` written

**Verification:** a throwaway test plugin registers its own provider and grants access without touching internals.

## S5 — Admin polish

- [ ] Settings screen
- [ ] Log screen
- [ ] Help tabs on every screen
- [ ] Description text under every field
- [ ] One-time setup wizard
- [ ] Presentational Pro screen: screenshots and illustrations only. The directory's Detailed Plugin Guidelines forbid shipping functionality that is locked and unlocked by paying, so no real code sits disabled waiting for a licence
- [ ] Screens that do not apply are not registered

**Verification:** a first-time user publishes an audiobook without reading documentation.

## S6 — Release

- [ ] `readme.txt` with short description under 150 characters
- [ ] Icon 256×256, banner 1544×500, screenshots
- [ ] Security pass against the checklist in `CLAUDE.md`
- [ ] `.pot` regenerated
- [ ] `es_ES` translation complete
- [ ] `pt_BR` translation complete
- [ ] Tested against the current WordPress release
- [ ] Submitted to the plugin directory

**Verification:** release checklist passes.

---

## Release checklist

- [ ] `composer lint` and `npm run lint` clean
- [ ] Full test suite green
- [ ] Zero PHP notices on a clean install
- [ ] Zero axe violations on all front-end output
- [ ] Every string translatable
- [ ] Uninstall removes tables and options behind the opt-in
- [ ] No functionality locked behind payment inside this plugin
- [ ] Version bumped in header and `readme.txt`, changelog written

---

## Open decisions not blocking code

- Icon centre block: square or narrow spine. Affects `assets/` only.
