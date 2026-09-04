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
- Chapters have no front end of their own by design, so a stray direct URL 404s.
  Fine for now; worth a friendly redirect to the parent book later.

## Belongs to `volumina-pro`, not here

- Selling, carts, checkout, coupons, tax.
- WooCommerce and EDD access providers.
- Signed, expiring audio URLs.
- Licence handling and updates.

## Rejected

- DRM of any kind. Browser audio cannot be truly protected; promising it is a lie
  that gets punished in reviews.
