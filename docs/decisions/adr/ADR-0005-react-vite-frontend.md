# ADR-0005: Serve the Frontend with Svelte 5 and Vite

- Status: accepted
- Date: 2026-04-14
- Owner: Technical Lead
- Approvers: Frontend Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight_V3 is a unified PHP 8.4 + Svelte 5 rewrite. The frontend lives inside the same repository, under `src/Resources/`, and is built with Vite as part of the same application unit. The decision must fit the current PHP shell, shared session state, and the existing game UI patterns already present in the rewrite.

The team needs a clear decision now so that directory layout, build tooling, deployment expectations, and UI implementation patterns stay aligned with the rewrite instead of drifting back toward the old separate-frontend model.

## Decision

Adopt Svelte 5 as the frontend UI layer and Vite as the frontend build and development toolchain for Starlight_V3.

Architecture rules:

- The frontend application will be built with Svelte 5 runes and component modules.
- Vite will be used for local development, bundling, and production asset generation.
- Frontend source code will live in `src/Resources/` and ship with the same application.
- The frontend is not a separately deployed workspace; it is part of the same PHP application shell.
- The PHP backend continues to own the HTML entry shell and server-side page assembly where needed.
- Dependency pinning and lockfile requirements from ADR-0004 apply to all frontend tooling and packages.

## Consequences

Positive:

- Fast local development with Vite improves frontend iteration speed.
- Svelte 5 keeps the UI layer compact while still supporting stateful game interfaces.
- The rewrite keeps the frontend colocated with the backend, reducing cross-repo coordination.

Negative:

- Adds Node-based tooling alongside the PHP stack.
- Requires explicit integration decisions for auth, CSRF, and page-shell rendering.
- Keeps frontend build and deployment concerns tied to the main application lifecycle.

## Alternatives Considered

- Server-rendered PHP views only, with minimal JavaScript enhancements.
- React, Vue, or another SPA framework with a similar build pipeline.

## Rollout Plan

1. Keep the Svelte 5 frontend under `src/Resources/` and ensure Vite builds it as part of the rewrite.
2. Define backend integration points for the PHP shell, session state, and form submissions.
3. Establish frontend package management, lockfile policy, and environment configuration.
4. Document auth, CSRF, and page-shell interaction patterns required for Svelte-driven state changes.

## Validation Plan

- Frontend scaffolding runs locally through Vite with deterministic dependency installation.
- Production frontend assets can be built reproducibly in CI.
- CSRF-protected state-changing flows work correctly between the Svelte UI and backend routes.
- The chosen layout does not break MVC-S responsibilities in backend code.