# STATE — Volumina (free)

> Read this first, every session. Overwrite it before you stop.
> This file is the single source of truth for where the project stands.
> The chronological work log is `docs/HISTORY.md`, append-only.
> There is no second state file: no `PROGRESS.md`, no `session-log.md`.

---

## Right now

**Current slice:** S0 — Scaffold. 11 of 12 tasks done and green in CI; the twelfth cannot be run here.
**Next action:** install Docker Desktop, then `npm run env:start` and confirm the plugin activates with zero PHP notices. That is the last thing standing between S0 and done. If Docker is not going to happen on this machine, the call is the user’s: close S0 on CI evidence alone and open S1, or leave S0 open.
**Blocked on:** Docker. `wp-env start` has never run, so the runtime half of the S0 verification is unproven. PHP and Composer are also absent locally, but CI covers both: `composer validate --strict`, PHPCS and PHPStan level 6 all pass on GitHub.
**Last updated:** 2026-09-03, TUNET

**File scope for this slice** — do not open files outside this list:
`docs/` (`STATE.md`, `HISTORY.md`, `decisions.md`, `backlog.md`, `api.md`), `.gitignore`, `.editorconfig`, `composer.json`, `package.json`, `volumina.php`, `readme.txt`, `uninstall.php`, `phpcs.xml`, `phpstan.neon`, `.wp-env.json`, `.github/workflows/`

---

## Release target

**v1.0 is finished when every slice below is checked and the release checklist passes.**
Anything else belongs in `docs/backlog.md`. Nothing is added to a slice once it has started.

Progress: 0 of 6 slices complete.

---

## S0 — Scaffold

- [x] `docs/` created: `STATE.md`, `HISTORY.md`, `decisions.md`, `backlog.md`, `api.md` — **first; every later task needs somewhere to be recorded**
- [x] Repository, `.gitignore`, `.editorconfig`
- [x] `composer.json` with PSR-4 autoloading for `TUNET\Volumina\`
- [x] `package.json` with `@wordpress/scripts`
- [x] `volumina.php` plugin header and bootstrap
- [x] `readme.txt` skeleton
- [x] `uninstall.php` skeleton
- [ ] `wp-env` configuration — **written, never run**: no Docker on this machine
- [x] PHPCS with WordPress standards
- [x] PHPStan level 6
- [x] GitHub Actions: lint on push. The PHPUnit job joins in S1, with the first test
- [x] i18n wiring. `languages/volumina.pot` is not generated yet: `make-pot` needs Docker

**Verification:** `wp-env start` runs, plugin active, zero PHP notices, CI green. CI is green on `6b6b8b3` (composer validate --strict, PHPCS, PHPStan level 6, npm ci, lint:pkg-json). The `wp-env` half is unproven.
Axe and block/classic theme checks are not applicable to this slice: it produces no front-end output.

## S1 — One audiobook, end to end

- [ ] `volumina_book` CPT with meta
- [ ] `volumina_chapter` CPT with meta
- [ ] `volumina_genre` and `volumina_series` taxonomies
- [ ] Progress table with migration and version tracking
- [ ] Admin: book editor
- [ ] Admin: chapter list with drag ordering
- [ ] Admin: attach audio from the media library
- [ ] Minimal front-end template

**Verification:** a hand-entered audiobook renders on the front end with chapters in order.

## S2 — The player

- [ ] Audio streaming endpoint with HTTP range request support
- [ ] Chapter navigation
- [ ] Position saved per listener, restored on return
- [ ] Playback speed control
- [ ] 15/30-second skip
- [ ] Sleep timer
- [ ] Persists across page navigation
- [ ] Full keyboard navigation
- [ ] Screen reader labels and states
- [ ] AA contrast on every text pair
- [ ] Mobile layout verified on a real phone

**Verification:** a nine-hour book listened across three sessions on a phone resumes correctly every time; zero axe violations.

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
