# ADR-0007: Frontend Shell Navigation and Page Switching

- Status: accepted
- Date: 2026-04-20
- Owner: @Rihoj
- Approvers: @mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

ADR-0005 established Svelte 5 + Vite as the frontend stack, and the rewrite
uses a component-map shell rather than a dedicated router library. The active
page is selected from `game.component` inside `src/Resources/js/App.svelte`,
with public and authenticated shells branching on `game.user`.

The current shell already hosts landing, auth, dashboard, settings, combat,
bank, armory, and support pages. The decision here is how page switching works
in the rewrite, not whether a React router should be imported.

## Decision

Adopt component-map page switching as the frontend navigation model for
Starlight_V3.

Rules:

1. `game.component` is the canonical page selector for the shell.
2. `game.user` determines whether the app renders the public shell or the
   authenticated shell.
3. Page components stay feature-local and are registered in the shell map.
4. Public pages (`home`, `auth/login`, `auth/register`, `pages/about`,
   `pages/terms`, `pages/contact`) are rendered through the same Svelte shell.
5. Authenticated pages (`dashboard/index`, `admin/index`, `structures/index`,
   `battlefield/index`, `settings/index`, `spy/index`, `training/index`,
   `combat/recruit`) are rendered once the user payload is present.
6. A future router or history abstraction must be justified by a separate ADR
   rather than added implicitly.

## Consequences

Positive:

- The shell stays compact and easy to reason about.
- Page ownership is obvious from the component map in `App.svelte`.
- No extra client-side router dependency is needed for the rewrite.

Negative:

- Deep-link and history handling stay coupled to the shell implementation.
- Large shell maps can become harder to scan as more pages are added.

## Alternatives Considered

- react-router-dom.
- TanStack Router.
- Hash routing.
- A separate history/router service.

## Rollout Plan

1. Keep the component-map shell in `src/Resources/js/App.svelte` as the
   canonical page-switching layer.
2. Add new page components by registering them in the shell map.
3. Revisit router libraries only if the rewrite eventually needs deep-link
   history management that the current shell cannot provide.

## Validation Plan

- The shell renders the correct page component for each `game.component`
  value.
- Public pages render when `game.user` is absent.
- Authenticated pages render when `game.user` is present.
