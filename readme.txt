=== Volumina — Audiobook Player and Library ===
Contributors: tunetdesign
Tags: audiobook, audio player, chapters, audio, library
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish audiobooks on WordPress with a real listening experience: chapters, remembered position, playback speed and a sleep timer.

== Description ==

Volumina turns WordPress into an audiobook library. A book holds its chapters in
order, and the player remembers where each listener stopped — so a nine-hour book
can be finished across a week of commutes, on the sofa and then on the train,
without anybody having to remember a number.

It is built for the phone first, because that is where people listen.

= What a listener gets =

* A player that keeps their place, chapter and second, and offers it back next time.
* Chapter navigation, and a chapter list that doubles as it.
* Playback speed, from 0.75× to 2×.
* Skip back 15 seconds, skip forward 30.
* A sleep timer, including "stop at the end of this chapter".
* Full keyboard control, and labels a screen reader can read.
* Lock-screen controls on a phone, through the browser's own media session.

A signed-in listener's place follows their account from device to device. A guest's
stays in their own browser: a listening position is not worth inventing an identity
for somebody who never asked to be identified.

= What a publisher gets =

* Audiobooks and chapters as ordinary WordPress content, with covers, narrator,
  publisher, ISBN, language, genres and series.
* Chapters reordered by dragging.
* Audio attached from the media library, with the running time read out of the file.
* Three blocks: **Audiobook**, **Chapter list** and **Continue listening**, all of
  which render the same markup the book page does.
* Books that are public, or restricted to listeners you have given them to.
* HTTP range requests, so seeking in a long file works properly and a phone can
  resume mid-chapter without downloading what it already heard.

= Honest about what it does not do =

Volumina does not stop anyone from copying your audio, and neither does anything
else. A browser has to be able to play a file in order to play it, and a file a
browser can play can be saved. Anybody who tells you otherwise is selling something
that does not exist.

What it does do is serve audio through its own endpoint, which asks on every single
request — including every range request of the same file — whether this listener may
hear this book.

= Extending it =

Access is resolved through a documented interface: a plugin can register an
`AccessProvider` and decide for itself who may listen, without touching anything
inside Volumina. Every audio URL passes through a filter, so it can be replaced with
a signed or expiring one. Admin screens are added through a registry rather than by
patching menus. The whole contract is in `docs/api.md` in the plugin.

**Volumina Pro** is a separate plugin that uses that contract to sell audiobooks
through WooCommerce or Easy Digital Downloads. Nothing in this free plugin is
disabled, locked or waiting for a licence key.

== Installation ==

1. Install the plugin from the WordPress plugin directory, or upload it to
   `/wp-content/plugins/volumina`.
2. Activate it on the Plugins screen.
3. Answer the two questions on the Setup screen, or skip them — the defaults suit
   most sites.
4. Add your first audiobook under Audiobooks, then add its chapters and attach an
   audio file to each one.

== Frequently Asked Questions ==

= Does it stop people downloading my audio? =

No, and no plugin honestly can. A browser has to be able to play a file to play it,
and a file a browser can play can be saved. Volumina serves audio through an
endpoint that checks access on every request, which is a real protection against
somebody who has not paid or has not been given the book. It is not a protection
against somebody who has, and no such protection exists.

= Where is a listener's place kept? =

For a signed-in listener, in a table of its own, so it follows their account to any
device. For a guest, in their own browser and nowhere else. Nothing about a
listener leaves your site.

= Do I need to use the blocks? =

No. An audiobook page shows its player and chapters by itself. The blocks are for
putting an audiobook, a chapter list or a "Continue listening" list anywhere else —
a landing page, a series page, a sidebar. If you would rather place everything
yourself, turn off "Book pages" in Settings and nothing is added automatically.

= What happens to my audiobooks if I delete the plugin? =

Nothing, unless you have asked for them to go. Deleting the plugin removes its
books, chapters, settings and both of its tables **only** if you tick the uninstall
setting first. Otherwise everything is left exactly where it is, including every
listener's place in every book.

= Does it work with my theme? =

It is tested against a block theme and a classic one, and it declares no colours of
its own: every piece of text inherits your theme's palette, and its contrast with
it. The player is laid out for a phone first and adapts upward.

= Is it accessible? =

The player is fully keyboard-operable, announces what it is doing to a screen
reader, and passes an automated WCAG 2.1 AA check with no violations on both a
block and a classic theme. Every touch target in the transport controls is at least
44 pixels.

= Can I sell audiobooks with it? =

Not with this plugin, which publishes and plays them. Volumina Pro is a separate
plugin for selling, and it works entirely through Volumina's public API — the same
one any other developer can use.

== Screenshots ==

1. An audiobook on the front end: cover, details, player and chapters, on a phone.
2. The player, with speed, sleep timer and the chapter it is in.
3. The three blocks in the editor, previewed exactly as the front end renders them.
4. The audiobook screen, with its chapters ordered by dragging.
5. Settings: four of them, each one explaining itself.

== Changelog ==

= 1.0.0 =
* First release.
* Audiobook and chapter content types, with covers, narrator, publisher, ISBN,
  language, genres and series.
* Chapter ordering by dragging, and audio attached from the media library.
* A player with remembered position, chapter navigation, playback speed, 15- and
  30-second skips and a sleep timer.
* Audio served through the plugin's own endpoint, with HTTP range support and an
  access check on every request.
* Audiobook, Chapter list and Continue listening blocks.
* An access layer with a documented provider interface, a chapter audio URL filter
  and an admin screen registry.
* Settings, a log of notable events, help tabs on every screen, and a one-time
  setup screen.
* Spanish and Brazilian Portuguese translations.

== Upgrade Notice ==

= 1.0.0 =
First release.
