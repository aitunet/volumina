# PUBLIC API — Volumina

> The contract `volumina-pro` and any third-party extension may rely on.
> Everything not documented here is internal and may change without notice.

**Status: not yet stable.** This file is written for real in slice S4. Until S4 is
checked off in `docs/STATE.md`, nothing below is a promise.

---

## Rules

- Every extension point is an action, a filter or an interface, and is documented here
  **at the moment it is created**, not afterwards.
- Anything documented here is a contract. Breaking it requires a major version bump and
  an entry in `docs/decisions.md`.
- Extensions never call internal classes, never read the progress table directly, and
  never patch admin menus.

## Planned extension points (S4)

### `AccessManager` and `AccessProvider`

`TUNET\Volumina\Access\AccessManager` resolves whether a user may listen to a book. It
holds a registry of `TUNET\Volumina\Access\AccessProvider` implementations. The free
plugin ships `PublicProvider` and `ManualProvider`; Pro registers WooCommerce and EDD
providers.

### `volumina_register_access_providers`

Action. Fired once, with the `AccessManager` as its argument, so an extension can
register its own provider without touching internals.

### `volumina_chapter_audio_url`

Filter. Wraps every audio URL the plugin emits, so Pro can substitute a signed,
expiring URL.

### Admin screen registry

Accepts new admin screens, so an extension adds its own screen without patching the
menu. Screens that do not apply are not registered at all.

---

## Reference

To be written in S4, one section per extension point: signature, arguments, return
value, when it fires, and a minimal working example.
