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

## Belongs to `volumina-pro`, not here

- Selling, carts, checkout, coupons, tax.
- WooCommerce and EDD access providers.
- Signed, expiring audio URLs.
- Licence handling and updates.

## Rejected

- DRM of any kind. Browser audio cannot be truly protected; promising it is a lie
  that gets punished in reviews.
