# HISTORY — Volumina

> Append-only. Never edit or delete an earlier entry.
> One line per completed task, written the moment it passes verification.
> Architectural choices are recorded here with the `DECISIÓN:` prefix and
> expanded in `docs/decisions.md`.

---

## 2026-09

- **2026-09-03** — Repository normalised before any code. `volumina-CLAUDE.md` renamed to `CLAUDE.md`, `volumina-PROGRESS.md` renamed to `docs/STATE.md`, `volumina-pro-CLAUDE.md` moved out of this repository (it belongs to `volumina-pro`).
- **2026-09-03** — **DECISIÓN:** the project adopts the global TUNET state convention. State lives in `docs/STATE.md` (overwritten), the work log in `docs/HISTORY.md` (append-only). No `PROGRESS.md` and no `docs/session-log.md` exist anywhere in the project. See `docs/decisions.md`.
- **2026-09-03** — S0 boot loop fixed: creating `docs/` is now the first task of S0, so every later task has somewhere to be recorded. S0 file scope rewritten to cover everything S0 creates. Axe and block/classic theme checks in the definition of done conditioned to tasks that produce visible front-end output. "Guideline 5" replaced by its content: the directory's Detailed Plugin Guidelines forbid functionality that is locked and unlocked by paying, so the free plugin's Pro screen is purely presentational.
- **2026-09-03** — S0.1 done: `docs/` created with `STATE.md`, `HISTORY.md`, `decisions.md`, `backlog.md` and `api.md`. `decisions.md` seeded with the six choices already settled in `CLAUDE.md` (state convention, progress table, chapters as CPT, integer seconds, Pro as an external consumer of the public API, `src/Support/` extractable). `api.md` created as an explicit not-yet-stable stub; it is written for real in S4.
- **2026-09-03** — S0.2 done: repository initialised on `main` with `user.email = ai@tunetdesign.com`, remote `origin` set to `github.com/aitunet/volumina`, plus `.gitignore` and `.editorconfig` (tabs for PHP/JS/CSS, spaces for YAML/JSON/Markdown, per WordPress standards).
- **2026-09-03** — S0.2 addendum: `.gitattributes` added with `* text=auto eol=lf` and `core.autocrlf` disabled locally, so Windows checkouts keep the LF endings that `.editorconfig` and PHPCS expect.
- **2026-09-03** — Two commits sit on local `main` (`8cac19a`, `299f8e0`). The push to `github.com/aitunet/volumina` is pending: the remote is reachable and empty, but Git Credential Manager needs an interactive GitHub login. Nothing is lost — the commits are the save point.
- **2026-09-03** — Push unblocked and done: `main` is on `github.com/aitunet/volumina` at `39c8313`. The cause was the remote URL lacking the user, so Git Credential Manager looked up `github.com` with no username, found nothing and opened a GUI. Setting `origin` to `https://aitunet@github.com/...`, the form every other TUNET repo already uses, hit the stored credential and pushed without any prompt.
- **2026-09-03** — S0.3 written, not verified: `composer.json` with PSR-4 `TUNET\Volumina\ -> src/`, dev dependencies (WPCS 3, PHPCompatibilityWP, PHPStan + phpstan-wordpress, PHPUnit Polyfills) and a `lint` script running PHPCS then PHPStan, as the definition of done requires. JSON validated with node. **This machine has no PHP, no Composer and no Docker**, so `composer validate`, `composer lint` and `wp-env start` cannot run here and no `composer.lock` exists yet. Recorded as a blocker in `docs/STATE.md`.
