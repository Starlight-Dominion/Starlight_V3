# ADR-0015: Client-Side Shell Transport and Heartbeat Refresh

- Status: accepted
- Date: 2026-05-21
- Owner: Frontend Team
- Approvers: Technical Lead, Product Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture

## Context

The rewrite uses a client-side transport layer in `src/Resources/js/app.js` that
intercepts in-app links, fetches the next page state with the
`X-Requested-With: XMLHttpRequest` header, updates the Svelte store, and pushes
history state manually. The same shell also starts a heartbeat timer for logged
in users and refreshes the state when the countdown reaches zero.

That behavior is not just implementation detail. It defines how the browser
shell talks to the backend, how page transitions work, and how live game state
is kept current without a full page reload.

## Decision

Adopt client-side shell transport with a polling heartbeat for the rewrite.

Rules:

- In-app navigation uses `window.navigate()` to fetch the target URL and update
  shell state without a full reload.
- Clicks on same-host anchors are intercepted unless the user explicitly opens a
  new tab or uses a modifier key.
- The shell uses `history.pushState()` for in-app navigation and `popstate` to
  restore previous state.
- Logged-in sessions start a heartbeat timer that counts down to the next tick
  refresh.
- Heartbeat refreshes fetch the current page state and resync resources when the
  countdown reaches zero.

## Consequences

Positive:

- Smooth page transitions without a full browser reload.
- Live state stays reasonably fresh without requiring a dedicated push channel.
- The shell remains simple enough to fit the current Svelte architecture.

Negative:

- Browser history and shell state must remain carefully synchronized.
- The polling heartbeat adds periodic traffic and can drift if the client tab is
  suspended.

## Alternatives Considered

- Full-page reloads for every navigation.
- A dedicated SPA router library.
- Server-pushed updates through websockets or SSE.

## Rollout Plan

1. Keep the current in-app navigation and heartbeat behavior in the shell.
2. Revisit the transport if the app needs multi-tab synchronization or
   real-time push updates.
3. Document any future router or realtime transport change as a separate ADR.

## Validation Plan

- In-app links fetch and update state without breaking the shell.
- Back/forward navigation restores the expected page state.
- Logged-in users refresh state at the expected heartbeat interval.