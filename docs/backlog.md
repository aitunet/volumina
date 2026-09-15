# BACKLOG — Volumina

> Everything that is not in the six slices of v1.0.
> When something worth doing appears mid-slice, it is written here and left alone.
> Nothing moves from here into a slice that has already started.

---

## Candidates for after v1.0

- Bookmarks and notes per chapter, using the chapter ID already available.
- Per-book listening statistics for the author.
- Import chapters in bulk from a folder or a ZIP.
- Automatic duration extraction from the audio file on upload.
- Chapter transcripts and search inside a book.
- Playlists and "up next" across books.
- Offline caching via a service worker.
- Podcast-style RSS feed per book.
- Additional shipped translations beyond `es_ES` and `pt_BR`.

## Noticed while building, deliberately not done

- Chapter ordering and parenthood use the `volumina_order` and `volumina_book_id` meta
  that the data model in `CLAUDE.md` specifies. The idiomatic WordPress alternative
  is `post_parent` plus `menu_order`, which sorts without a meta query. Worth
  revisiting if chapter queries ever show up in profiling; it is a schema change,
  so it needs an entry in `docs/decisions.md` before anyone touches it.
- Deleting or trashing a book leaves its chapters behind. They should follow it.
- The book has no featured image, by decision: `volumina_cover_id` is the single
  cover. If a theme or a block later needs a real featured image, mirroring it into
  `_thumbnail_id` is the follow-up, and it is a schema decision, not a tweak.
- Chapters have no front end of their own by design, so a stray direct URL 404s.
  Fine for now; worth a friendly redirect to the parent book later.
- The chapter screen picks its book from a plain select listing every book. That
  is right for a library of tens and wrong for one of hundreds. If it ever needs
  to scale, the replacement is a search-backed control, not a truncated list: a
  select that silently omits books is worse than a long one.
- Audio is served from `?volumina_audio={id}` rather than a pretty URL. A rewrite
  rule would be prettier and friendlier to a CDN, at the cost of a flush that, when
  it does not happen, makes the plugin look broken on activation. Revisit when there
  is a reason beyond tidiness.
- The player restores a position across page loads; it does not keep audio playing
  through a navigation. Nothing short of a persistent frame or a single-page shell
  can, and neither belongs in a plugin that has to sit inside anyone's theme.
- A guest's position lives only in their own browser. Carrying it to an account on
  sign-in would be a nice touch and needs a decision about whose value wins.
- Chapter positions are contiguous only because the reorder endpoint renumbers
  the whole book. Deleting a chapter leaves a gap until the next drag. Harmless,
  since order is only ever compared, never counted on to be dense.
- A chapter play button is 34px tall on a phone, because it is styled as a line of
  text rather than as a control. That clears WCAG 2.2 AA's 24px minimum and it is
  under the 44px the transport controls hold themselves to. Measured in Twenty
  Twenty-One at 390px; the same in every theme, since the height comes from the
  line box. Worth revisiting with a padded hit area that does not turn the chapter
  list into a column of boxes.
- `build/` is git-ignored, so a clone has no blocks until `npm run build` runs.
  That is right for the repository and wrong for the plugin directory, which
  receives built files. The release packaging in S6 has to include it.

- A multi-range request (`Range: bytes=0-1,5-10`) is answered with the first range
  as a single-part 206 rather than a multipart/byteranges body. No browser sends
  one, and a media element never will, so this is correctness rather than a bug
  anybody will meet. Answering 200 and ignoring the header would also be right.
## Belongs to `volumina-pro`, not here

- Selling, carts, checkout, coupons, tax.
- WooCommerce and EDD access providers.
- Signed, expiring audio URLs.
- Licence handling and updates.

## Rejected

- DRM of any kind. Browser audio cannot be truly protected; promising it is a lie
  that gets punished in reviews.

## Found 2026-09-11, while writing the user guide

- **A screen for granting a restricted book by hand.** `Storage\Grants` and
  `ManualProvider` exist and work, but nothing in the admin calls
  `Grants::grant()`: the only ways to give a listener a restricted book today
  are code, WP-CLI (`wp eval 'TUNET\Volumina\Storage\Grants::grant( $user, $book );'`)
  or an extension. A "Listeners" box on the book screen — search a user, add,
  remove — is the obvious shape. Noted in the user guide until it exists.
- **A block template for single audiobooks.** Twenty Twenty-Five's single
  template prints "Written by … in" with an empty terms list under an
  audiobook's title, because it was written for posts. The plugin could
  register its own `single-volumina_book` template (`register_block_template`,
  WordPress 6.7+) with title, cover, details, player and chapters, and skip the
  post byline. Until then the theme's template is used as it is, with the
  featured image withheld so the cover shows once.

## Asked for 2026-09-14, by the account holder

- **A Pro screen that catches the eye.** The presentational Pro screen (S5)
  is correct but plain. Make it attractive: a hero, the mark, screenshots or
  illustrations of what Pro adds, one clear call to action. Still purely
  presentational — the directory forbids shipping locked functionality.
- **Templates and player styling, free and Pro.** Free: one basic audiobook
  template, customisable in the basics (which details show, where the cover
  sits, the accent colour through the theme's palette). Pro: a set of
  featured templates with advanced customisation — colours, typography,
  layouts and styles for the Audiobook block and the player. This pairs with
  the single-audiobook block template noted on 2026-09-11; the free template
  is the foundation Pro's templates build on, through a documented filter or
  a template registry in `docs/api.md`. Needs a design pass before code: what
  is "basic" and what is "advanced" has to be decided once, not per release.
- **A welcome page.** One screen shown after activation — what the plugin is,
  what it is not (no store, no DRM), the three steps to a first audiobook, a
  link to the setup and to the guide. Distinct from the setup wizard, which
  asks questions; this one explains. Registered through the screen registry
  so Pro can add its own section.

> The three above entered 1.0 on 2026-09-14 as S7 — Presentation. See `docs/decisions.md`.

## Asked for 2026-09-14, by the account holder, while S7 was in flight

- **An animated directory icon.** The mark in motion: the shelf appears, then
  the five spines rise from it like a volume meter. The directory takes
  `icon-256x256.gif` and `icon-128x128.gif`, so an animated GIF is allowed
  (banners are PNG or JPG only). Two better routes than a GIF: `icon.svg`
  with a CSS animation inside it — the directory accepts `icon.svg` and
  renders it as an `<img>`, where CSS and SMIL animation run and scripts do
  not; crisp at every size, and the PNG stays as the required fallback — or
  the same animated SVG on the welcome page (S7) and as the player's
  "playing" indicator, where it is seen far more often than in a search
  result. Whichever, it plays once and stops: a looping icon in the plugin
  list is a distraction, and the directory's tiles are seen dozens at a time.
  **Scope settled by the account holder on 2026-09-14:** yes, a one-shot
  animation with an end, and for the directory listing only — the plugin
  search and the plugin page — never in the dashboard. **Done 2026-09-15:**
  `.wordpress-org/icon.svg`, CSS animation, plays once; see HISTORY.
- **A guided tour of the admin.** Three or four steps, once, dismissible,
  through WordPress's own pointers (`wp-pointer`, stored per user in
  `dismissed_wp_pointers`): the menu entry, the audiobook screen, the
  chapter list, the blocks. Native, no dependency. Doubt worth settling
  first: S5 already ships a setup wizard, help tabs and a description under
  every field, and S7 adds a welcome page with the three steps to a first
  audiobook; a fourth onboarding layer is one more thing every new user has
  to dismiss. Decide after the welcome page exists and has been looked at.
