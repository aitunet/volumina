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
