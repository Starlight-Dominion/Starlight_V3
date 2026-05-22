# ADR-0010: Backend HTTP Routing and View-Shell Boundary

- Status: accepted
- Date: 2026-04-21
- Owner: Rihoj
- Approvers: mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight_V3 still uses a FastRoute bootstrap in `public/index.php`, a central
route table in `config/routes.php`, and a PHP-rendered app shell for browser
requests. The rewrite is not API-only; some routes render HTML, while others
return JSON or redirect-style responses through their controllers.

The current code already shows the architectural tension: the backend owns both
route dispatch and the shell rendering path, while the frontend code is
embedded in the same repository. This decision codifies that hybrid shape so it
does not drift back toward an all-JSON assumption.

## Decision

### 1. Backend router — `nikic/fast-route` (retained)

- `nikic/fast-route ^1.3` remains the canonical backend router.
- `public/index.php` remains the dispatch bootstrap for the rewrite.
- `config/routes.php` remains the canonical route registry for the current
  application shape.

Rationale: FastRoute is already in use, has no transitive deps, and matches the
current application bootstrap. Replacing it would not simplify the current
rewrite.

### 2. Route registration — centralized route table

- `config/routes.php` remains the central place where route definitions are
  registered.
- `public/index.php` pulls that table into the dispatcher.
- Route edits should stay in one place until a future ADR justifies a provider
  split.

Rationale: the rewrite already uses a single route table, and the current goal
is to keep the bootstrap predictable rather than introduce a new abstraction
layer too early.

### 3. Response shape — hybrid browser and controller responses

- Browser requests may still receive rendered HTML or plain text, depending on
  which controller handled the route.
- Controller-level JSON responses continue to use the existing project style
  where a route is explicitly meant for machine consumption.
- A future rewrite to a pure API surface would require a separate decision.

### 4. Backend owns the app shell

- The backend still owns the HTML shell for the current rewrite.
- `BaseController::render()` remains the bridge between controller state and the
  rendered app view.
- The HTML shell is not a separate frontend service.

### 5. Minimal bootstrap

- The bootstrap should remain small.
- New routing or error-handler abstractions should be introduced only when the
  rewrite actually needs them.

## Consequences

Positive:

- The current router path remains simple and predictable.
- The rewrite keeps shell rendering and JSON responses in the same codebase.
- No new dispatch layer needs to be introduced before the current game loops
  stabilize.

Negative:

- Plain browser and JSON responses remain mixed.
- The route table still lives in one file, which can become crowded as the app
  grows.

## Alternatives Considered

- Move to a provider-per-feature route registry.
- Switch to a full middleware stack.
- Make the backend API-only and move all HTML rendering out of PHP.

## Rollout Plan

1. Keep the current FastRoute bootstrap and centralized route table in place.
2. Document any future move to route providers or a formal error handler in a
   follow-up decision instead of spreading the change through the bootstrap.
3. Revisit the shell/API split only if the rewrite actually becomes API-only.

## Validation Plan

- Existing routes continue to dispatch through the current front controller.
- Browser routes that render the app shell still return HTML successfully.
- Controller-owned JSON routes continue to work without a separate API layer.
- The backend still owns the HTML shell for the current rewrite.
- `BaseController::render()` remains the bridge between controller state and the
  rendered app view.
- The HTML shell is not a separate frontend service.
  (cross-reference ADR-0005).
### 5. Supporting infrastructure — minimal bootstrap, no extra router layer
  are owned by the frontend route tree, not by backend templates.
- The bootstrap should remain small.
- New routing or error-handler abstractions should be introduced only when the
  rewrite actually needs them.
- `App\Core\Clock\ClockInterface` is bound with `App\Core\Clock\SystemClock`
Rationale: the current code already has a working route bootstrap, so the best
near-term decision is to stabilize it rather than replatform it.
cheapest time and avoids a separate dependency-only ADR later. PSR-3 is the
PHP-FIG standard interface; keeping the default implementation in-repo
avoids pulling in a transitive logger framework and the pinning workflow
from ADR-0004. Upgrading to Monolog (or similar) is a non-breaking change
— swap the DI binding.
- The current router path remains simple and predictable.
- The rewrite keeps shell rendering and JSON responses in the same codebase.
- No new dispatch layer needs to be introduced before the current game loops
  stabilize.
  to end users.
- A new compliance test can walk every registered route and refuse to ship
  a broken handler.
- Plain browser and JSON responses remain mixed.
- The route table still lives in one file, which can become crowded as the app
  grows.
- Adds one runtime dependency (`psr/log`, interfaces-only package).
- Developers must remember to register new controllers' routes in a
  provider; mitigated by the audit (no registration = no routes; new
- Move to a provider-per-feature route registry.
- Switch to a full middleware stack.
- Make the backend API-only and move all HTML rendering out of PHP.
  frontend for pages that the SPA already owns.
- **Pull Monolog as the default PSR-3 implementation.** Deferred: would
  introduce a new pinned dependency per ADR-0004 with no immediate benefit
1. Keep the current FastRoute bootstrap and centralized route table in place.
2. Document any future move to route providers or a formal error handler in a
   follow-up decision instead of spreading the change through the bootstrap.
3. Revisit the shell/API split only if the rewrite actually becomes API-only.
   (DI pattern closed by ADR-0002; router + view model closed by this ADR).
4. Update `docs/decisions/INDEX.md`.

- Existing routes continue to dispatch through the current front controller.
- Browser routes that render the app shell still return HTML successfully.
- Controller-owned JSON routes continue to work without a separate API layer.
  `curl /does-not-exist`, `curl -X PATCH /health` — each returns the JSON
  envelope with the expected status code.
- Before/after check: every existing route (`/api/auth/*`, `/api/mfa/*`,
  `/api/combat/*`, `/api/espionage/*`, `/health`,
  `/api/bootstrap-status`) continues to respond identically.
