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

## 2026-09-05 — The `@wordpress/*` editor packages join as dev dependencies

**Decision.** `@wordpress/blocks`, `block-editor`, `components`, `data`,
`core-data`, `editor`, `i18n`, `html-entities` and `server-side-render` are
installed as dev dependencies. None of them is bundled: `wp-scripts` turns every
one of them into a reference to the `wp.*` global that WordPress already ships,
which is what the generated `index.asset.php` files list as script dependencies.

**Why.** ESLint cannot check an import it cannot resolve, and without them the
lint run fails on every block file with `import/no-unresolved`. Reason enough on
its own; they also give the editor code real definitions to check against.

**Consequence.** `node_modules` grows and `npm ci` takes longer. The shipped
plugin does not change by a single byte: the built bundles contain none of it.

## 2026-09-05 — A block asks for an asset by handle, it does not enqueue one

**Decision.** `Frontend\Assets` registers every front-end handle on `init` and
enqueues nothing. `block.json` names the handles it needs in `style` and
`viewScript`; the content filter enqueues them on a book page.

**Why.** Until now a book page was the only place this plugin drew, so enqueueing
on `is_singular()` was the whole story. A block can put a player on any page on
the site, and it can only ask for a handle that already exists. Registering on
`init` rather than `wp_enqueue_scripts` also covers the editor, which renders
these blocks through the REST API and never reaches a front-end enqueue hook.

**Consequence.** WordPress loads the player's script and stylesheet only on pages
that actually contain a block that asks for them, which is better than the
alternative and better than what a book page did before. `Player::settings()` is
public because the handle it belongs to is now registered somewhere else.

## 2026-09-06 — A refusal denies, a grant allows, silence is not consent

**Decision.** `AccessProvider::can_listen()` answers `true`, `false` or `null`.
`AccessManager` combines them with one rule: a single refusal denies, otherwise a
single grant allows, otherwise the answer is no. Providers are asked in
registration order and the questioning stops at the first refusal.

**Why.** Two providers that disagree have to be settled by something, and the
alternatives are worse. First-answer-wins makes registration order load-bearing,
which is exactly the kind of promise this plugin cannot keep across releases.
Grant-only, with no way to refuse, leaves an extension unable to express a
suspended account or a region it may not serve.

Default deny matters more than the rest of it: a restricted book whose provider
failed to load has to be silent, not open.

**Consequence.** A provider must return `null`, not `false`, when its own
condition is simply not met — `false` is a veto over everybody. The interface
documentation says so twice, and both shipped providers are written that way.
`volumina_can_listen` filters the final answer for what a provider cannot say.

## 2026-09-06 — Grants live in a table, and the mode lives on the book

**Decision.** `wp_volumina_grants` holds `(user_id, book_id, granted_at,
granted_by)` with `(user_id, book_id)` as the primary key and an index on
`book_id`. A book's access mode is a meta, `volumina_access`, holding `public`
or `restricted`, and a book with nothing stored reads as public.

**Why.** Both directions of the grants relation get asked in earnest: the audio
endpoint asks whether this person may hear this book, and an admin screen asks
who may hear it at all. A serialised array in post meta answers the first and
scans every book for the second, and two grants written at once would clobber
each other. Schema version 2.

The mode is a meta rather than a second table because it belongs to the book,
one value per book, edited by hand on the book screen — which is what post meta
is for. It is public API so Pro marks a book as its own without inventing a
field, and an unrecognised value sanitises to `public`: a typo should not take a
published book away from its listeners.

**Consequence.** `uninstall.php` has two tables to drop instead of one. Every
book that existed before this meta did is public, which is what it already was.

## 2026-09-06 — An extension may not name a Volumina class at file scope

**Decision.** `docs/api.md` requires extensions to declare their classes from
inside the registration hook, or on `plugins_loaded` at the earliest, and the
documented example is written that way.

**Why.** Found by running it. WordPress loads active plugins in alphabetical
order by path, and `volumina-subscribers/…` sorts before `volumina/…` because
`-` precedes `/`. The first version of the example plugin said
`implements AccessProvider` at the top of its entry file and took the whole site
down with a fatal error: the interface did not exist yet. Every plugin named
`volumina-something` — which is to say every plugin anybody writes for this one,
starting with Pro — hits this.

**Consequence.** One `require_once` inside the hook. It also means an extension
survives Volumina being deactivated instead of fataling, which is worth having
on its own.

## 2026-09-06 — Settings live in one option, and the log lives in another

**Decision.** All four settings share a single option, `volumina_settings`, and
the form posts to `options.php` so WordPress does the nonce, the capability and
the redirect. The log is a separate non-autoloaded option holding at most two
hundred entries, newest first.

**Why.** A screenful of settings read on nearly every request should be one row
and one sanitize callback, not fifteen of each. `options.php` is the flow the
directory's reviewers expect and the one least likely to be got wrong.

The log is an option rather than a table because it records **notable events** —
a listener turned away, a file gone missing, a schema brought up to date — and
not requests. Two events in the same instant can lose one of each other, since
this reads, appends and writes back. That is the trade for needing no table, and
it is the right trade at this volume. If it ever wants to record something that
happens on every request, it wants a table, and `Support\Logger` should not be
the thing that grows into one.

**Consequence.** `Support\Settings\Group` sanitises the whole option in one
place, and a key nobody declared never reaches the database. A setting missing
from a posted form keeps its default rather than being coerced from nothing —
found by a test, which is exactly what it was written for.

## 2026-09-06 — A screen that does not apply is never registered

**Decision.** The log screen exists only while logging is on; the setup screen
only until setup is finished; the Pro screen only where Pro is not installed.
Not hidden, not greyed out — not registered, so the page 404s or 403s if
somebody reaches for its URL.

**Why.** A plugin that fills the sidebar with pages that explain why they are
empty is a plugin that feels bigger than it is. It also means the capability
check and the applies check are the same gate, rather than a menu that hides
something still reachable.

**Consequence.** `Screen::applies()` is part of the published contract, and both
of the plugin's own conditional screens exercise it. Turning logging off makes
its screen disappear mid-session, which is the intended behaviour and worth
knowing before it surprises somebody.

## 2026-09-06 — A registered default is not an empty value

**Decision.** `Admin\Settings::apply_default_mode()` asks `metadata_exists()`,
never `get_post_meta()`, to decide whether a book has chosen an access mode.

**Why.** Found by running it. `volumina_access` is registered with a default, so
`get_post_meta()` returns `public` for a book that has no row at all, and the
check "has this book already got one?" was true for every book. New books
silently kept `public` however the site default was set. Any registered meta
with a default has this trap.

**Consequence.** The site default now reaches new books and only new books. An
existing book keeps what it had, which is the half that matters: a setting
changed today must not reach back and take a published book away from its
listeners.

## 2026-09-06 — What ships is decided by `.distignore`, not by what is in git

**Decision.** The release zip is built from `.distignore`: everything except the
development tooling, the block sources that have already been compiled into
`build/`, the documents written for whoever works on this, and the directory
art. `vendor/` is rebuilt with `composer install --no-dev` in a staging copy, so
what ships is the autoloader and nothing else.

**Why.** A plugin zip carrying `CLAUDE.md`, `docs/`, `tests/`, PHPUnit and PHPCS
is a plugin that puts a few megabytes of somebody else's business on every
server that installs it. The first build of the zip also carried `.claude/` and
nineteen Playwright artefacts, which is exactly the kind of thing that gets in
when nobody looks: the list is checked by opening the zip, not by trusting it.

**Consequence.** 93 files, and the plugin has no production dependency beyond
PHP itself. `wp dist-archive` would do the same job and is the tool to use where
it can be installed; this machine cannot reach the network, so a small script in
the scratch directory does it with the same rules.

## 2026-09-11 — The release build script lives in the repository

**Decision.** `bin/build-release.php` builds the zip. It reads `.distignore`,
stages into `dist/volumina/`, rebuilds `vendor/` there with `--no-dev`, and
writes `dist/volumina-<version>.zip`. It supersedes the scratch-directory
script mentioned on 2026-09-06.

**Why.** That script went with its session, and so did the zip, which sat at
the repository root where `.gitignore` discards it. A release artefact that
cannot be rebuilt from a clone is not reproducible, and a build that nobody
can rerun is a build nobody can check. Keeping the script in `bin/` — itself
excluded from the zip — means the same 91 files come out on any machine with
PHP and Composer.

**Consequence.** `dist/` is a build directory like `build/`: git-ignored,
PHPCS-ignored, never shipped. The script refuses to package a `build/` older
than `blocks/`, and checks the staged list against its own short deny-list
before zipping, so a mistake in `.distignore` fails loudly instead of shipping.
`wp dist-archive` remains the tool to use where it can be installed.

## 2026-09-11 — The cover is the featured image

**Decision.** A book's cover is its post thumbnail (`_thumbnail_id`), set from
the featured-image panel WordPress already has, relabelled "Cover". The
`volumina_cover_id` meta and the plugin's own media picker are gone. Schema
version 3 moves every existing cover across, once, and deletes the old meta.
`Book::cover_id()` is the one place the cover is read.

**Why.** The account holder asked for it after using the plugin: a second
picker in a meta box, for something every WordPress editor already knows how
to set in the sidebar, was a thing to learn for no gain. It also gives the
cover to everything that understands featured images for free — themes,
archive templates, REST `featured_media`, the block editor's own preview.

**Consequence.** `CLAUDE.md`'s data model listed `cover_id` among the book's
meta; it no longer exists. One admin script fewer (`admin-book.js`), six
strings fewer, four new labels. Anything outside the plugin that wrote
`volumina_cover_id` directly would now be writing to nothing — it was never
in `docs/api.md`, so nothing documented did.

## 2026-09-14 — Script settings are handed over as JSON, never localised

**Decision.** Anything a script reads from the page that is not a translated
string — numbers, booleans, URLs, nonces — is printed with
`wp_add_inline_script()` and `wp_json_encode()`, through `Assets::hand_over()`.
`wp_localize_script()` is not used for settings anywhere in the plugin.

**Why.** `wp_localize_script()` casts every scalar to a string. The forward
skip reached the player as `"30"`, `currentTime + "30"` concatenated instead
of adding, and the button did nothing for three days while the server was
suspected. `canSync` and `continue.js`'s `active` flag carried the same
defect, latent. `wp_json_encode()` keeps the types the PHP declared, and
WordPress's own documentation for `wp_localize_script()` says as much: it is
for strings, and data belongs in an inline script.

**Consequence.** Scripts may trust the types in `window.voluminaPlayer` and
`window.voluminaContinue`. `player.js` still coerces the two skip lengths
with `Number()` at the top, as a second layer, because the cost is one line
and the failure was silent.

## 2026-09-14 — v1.0 grows by one slice before submission

**Decision.** The account holder chose, on being asked, to add three things
to 1.0 before it is submitted rather than ship them as 1.1: an eye-catching
Pro screen, a welcome page, and a basic customisable audiobook template in
free with the seam Pro's templates will build on. They form a seventh slice,
S7 — Presentation, placed between S6's release checks and the submission.
Nothing else enters; the backlog rule stands for everything but these three.

**Why.** The plugin is not yet in the directory, so there is no user to
break and no review cycle to wait for; the account holder wants first
impressions — the Pro screen, the welcome page, the look of a book page — in
the first version people see. Reopening scope once, deliberately, on the
owner's call, with the decision written down, is what `CLAUDE.md` asks for
when scope changes; adding features quietly mid-slice is what it forbids.

**Consequence.** `docs/STATE.md` carries S7 with its own file scope and
done-when. The submission checkbox in S6 waits for S7. Each of the three
begins with a design pass agreed with the account holder before any code,
and every screen stays purely presentational where the directory requires
it: the Pro screen shows and tells, it never disables real code. The `.pot`,
`es_ES` and `pt_BR` are regenerated and the zip rebuilt at the end of S7.

## 2026-09-14 — Templates: the free plugin ships one, Pro's are stylesheets plus options, through a registry

Decided with the account holder in a visual design pass (`.superpowers/brainstorm/4137-*`).

**The free template is one, "Basic", in the "card" layout**: cover beside the
details, the player in its box, the chapter list below — the current
arrangement, kept on purpose because it sits well in any theme. Three things
about it can be changed: which parts show (already per block), where the
cover goes (beside, above, none) and an accent colour chosen from the theme's
palette. The accent colours controls only — the play button, the seek bar,
the current chapter's marker — never text, so no pair of text colours is
ever the plugin's to get wrong.

**A Pro template is a stylesheet plus default values for those same
options**, registered through a template registry the free plugin exposes
(`volumina_register_templates`). The wrapper carries a class per template and
the accent as a CSS custom property, which is the whole contract a stylesheet
needs. Two of the three examples drawn — an editorial serif one and a dark
one with the cover above — are exactly that.

**What a template is not:** new behaviour. The third example drawn — progress
per chapter with finished ones ticked, a mini player fixed to the bottom of
the screen — needs JavaScript and data the free plugin does not keep. Those
are Pro features to be built in Pro, with their own hooks, when Pro is built;
nothing is left "prepared" for them now, because a seam for an unknown feature
is a guess.

## 2026-09-14 — The setup wizard is gone; the settings are the setup

S5 shipped a two-question wizard, and the account holder, using the plugin, saw what
it was: the first two settings asked again on a second screen, with a menu entry of
its own. Asking twice in two places was one place too many.

The settings are the setup now. "Set up" means the settings have been looked at once:
saved, or kept as they are on purpose through "Keep the defaults" on the notice — a
nonce-checked admin-post action, since a notice that can be dismissed without answering
is a notice that comes back. The option name `volumina_setup_done` stays, so a site
that finished the wizard stays set up. The notice, the welcome page's button and its
first step all point at the settings.

Also today, the welcome page moved from the Audiobooks menu to Dashboard → "Welcome
to Volumina", where a welcome is expected and where it does not lengthen the menu people
work in; and the settings screen took the presentation screens' cards, one per section.
`HasParent` is the additive interface that lets a screen name its menu.

## 2026-09-20 — A chapter's status counts: listeners hear published chapters, editors hear them all

**Decision.** The front end lists, and the audio endpoint serves, only chapters
whose status is `publish` — unless the visitor may edit the book, in which case
every chapter that is not in the trash is shown and served, so an author can
preview a book before it is finished. `Chapter::for_listener()` is that rule;
`Chapter::for_book()` stays the editors' complete list. A chapter in the trash
streams for nobody.

**Why.** Until today a chapter's status meant nothing: a draft chapter was listed
on the public page and its audio answered any request, and so did a chapter that
had been trashed. The chapter form has a Publish box and a "Save Draft" button,
and anyone who used them was told one thing by WordPress and given another by
the plugin. A pre-submission security pass found it and it is the kind of thing
that reads as a leak of unpublished content, which it is.

**Consequence.** A chapter saved as a draft is invisible to listeners until it is
published. The account holder's own preview is unchanged, because they edit the
book. The chapter post type also left the REST API (`show_in_rest` is now
`false`): nothing read it there, and the endpoint listed every published
chapter's title to anybody, restricted and unpublished books included.

Also today: putting a chapter into a book takes the right to edit that book,
not only the chapter, both in the chooser and on save. A contributor could
otherwise add their own audio to anybody's audiobook, and — with the rule above
not yet in place — have it listed on the public page at once.

## 2026-09-20 — v1.0 grows by one more slice: S8, the library block

**Decision.** A fourth block, `volumina/library` ("Audiobook list"), enters 1.0
before submission, as S8, the same way S7 entered on 2026-09-14. The account
holder asked for it after the Live Link case closed: the plugin could show one
book, or the books a listener had started, but not the library itself — a list
to get from to each book's page.

**What it is.** A server-rendered list of published books in rows — small cover,
title linked to the book page, "Narrated by X · 9:12:40" beneath — with the
count, the order (title or newest), an optional genre or series, three toggles
(covers, narrator, running time) and the same presentation panel the chapter
list has, so Pro's templates dress it. No pagination: that is in the backlog.
The account holder chose rows over a grid of covers, and said in the same
breath that Pro should improve this block; so the block is built with two
seams, `volumina_library_query` and `volumina_library_item`, and the book page
gets the seam it was missing, `volumina_book_details`. All three are documented
in `docs/api.md` and are contract from the day they ship.

**Why not core's Query Loop.** It can list `volumina_book` posts, but it knows
nothing of narrators, running times or covers as a library shows them, and its
output is the theme's, not the plugin's; a listener should get the same rows
whatever theme is on.

**Consequence.** The release checklist is re-run when S8 closes; the `.pot`, both
translations and the zip are regenerated. Pro registers blocks of its own
through WordPress directly — no hook of this plugin is needed for that — and
improves the shipped ones through the filters above and the template registry.

## 2026-09-20 — S9: three contract additions for Pro, before submission

**Decision.** Volumina gains `volumina_progress_saved` (an action after every saved
listening position), `volumina_stream_access` (a filter the audio endpoint asks after
access is granted) and the documented `VOLUMINA_PRO_VERSION` constant. All three are
contract in `docs/api.md` from this day.

**Why.** Pro's listening reports need to know when a position is saved and the progress
table is not public; Pro's signed URLs need the endpoint to verify a signature without a
second streamer; and the presentational Pro screen must leave when Pro is installed.
Shipping 1.0 without them would mean a 1.0.1 the day Pro appears.

**Consequence.** No new strings, no schema change, no behaviour change for a site
without Pro. The zip is rebuilt.

## 2026-09-25 — S10: extensions add tabs to Settings, before submission

**Context.** Volumina Pro has its own "Pro settings" menu entry (signed links, protected
folder) and P3 adds player styles. Two settings entries under Audiobooks, styled
separately, is what the account holder asked to avoid. Settings has no extension point:
only the welcome page does (`volumina_welcome_sections`), and Pro may use only `api.md`.

**Decision.** A public tab registry for Settings, modelled on `Sections`: an action hands
it to extensions, each tab an id, a title and a render callback; Volumina's own settings
are the first tab; tabs show only when there are two or more. Added now, before the zip
goes to the directory, because an API added after 1.0 costs a release on both sides.
Decided with the account holder.

**Consequence.** An addition, nothing breaks: a site without an extension sees Settings
exactly as today. Pro drops its "Pro settings" menu entry and draws inside Volumina's
cards, so it inherits their look instead of copying it.

## 2026-09-29 — S11: an Appearance tab, owned by Volumina

Everything visual moves out of General into Volumina's own second tab, "Appearance": template (cards with preview images when more than one template exists), cover, accent, and a live preview of a sample book drawn by the real partial. An extension joins that tab's single form through the option group `volumina_appearance` (`register_setting`) and the action `volumina_appearance_sections`; each option keeps its own sanitize callback. `Support\Settings\Group` learns partial saves (a form names the sections it drew; fields of other sections keep their stored value) so splitting one option across two tabs cannot reset either. `Template` gains an optional preview image. Asked for by the account holder after seeing Pro's separate "Player styles" tab live, which duplicated the accent as "Button colour". Spec: `volumina-pro/docs/superpowers/specs/2026-09-29-appearance-tab-design.md`.

## 2026-09-29 — S11: the Appearance preview is the newest real book

**Context.** The spec asked for a sample book of three chapters with no audio source. A
`WP_Post` built in memory cannot carry what the partial draws from: the cover is an
attachment (`_thumbnail_id`) and the durations are post meta of chapter posts, both read
from the database. A fake book would show no cover and no times, which is not what a
listener gets.

**Decision.** The preview draws the site's newest published audiobook with
`Audiobook::render()`, and is cut down to a sample: the audio loses its `src` (and any
`<source>`) and gets `preload="none"` before it is printed, so loading the tab fetches no
stream; the bare audio bar is hidden; only the first three chapters show; the "Show it
playing" button sits above the sample, where it is seen. With no published book the card
says to publish one. The preview's root, `.volumina-appearance-preview .volumina-book`, is
contract in `docs/api.md`.

**Consequence.** What the preview shows is what the front end draws, with a real cover
and real durations, at the cost of needing one published book. No schema change.

## 2026-09-30 — Asset versions follow the file, not only the plugin

**Context.** Every script and stylesheet was enqueued with the plugin's version. The test
site sits behind Cloudflare, and the host sends `Cache-Control: max-age=14400`: after a
rebuilt zip of the same version, Cloudflare and every browser kept the old files for hours,
and the account holder's review saw fixes that were not there. Nobody on the team can purge
that Cloudflare; the habit elsewhere is to bump the version by hand.

**Decision.** `Support\AssetVersion::of( $path, $plugin_file, $version )` returns the
plugin's version, a dot, and the file's modification time (`1.0.0.1790777806`); the plugin
version alone for a file that is not there or lies outside the plugin's folder. Every
`wp_register_*` / `wp_enqueue_*` of a file of ours uses it, in Volumina and in Pro (the
same class, copied into Pro's `Support/`). Block editor scripts keep the hash
`@wordpress/scripts` writes.

**Consequence.** A changed file gets a new URL on the next page load, whatever the version
says, so no CDN or browser cache can serve a stale copy, and no version bump is needed to
ship a CSS fix. One `filemtime()` per asset per request, from the stat cache.
