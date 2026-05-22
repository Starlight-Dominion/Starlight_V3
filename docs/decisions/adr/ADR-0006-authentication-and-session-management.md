# ADR-0006: Authentication and Session Management

- Status: accepted
- Date: 2026-04-20
- Owner: @Rihoj
- Approvers: @mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight_V3 uses a Redis-backed PHP session handler in `public/index.php`, a
shared CSRF helper in `src/Infrastructure/Csrf.php`, and direct form-based login
and logout flows in the auth controller. The rewrite does not use the old DB-
backed session model or the separate frontend deployment model from the legacy
decision corpus.

The security baseline still requires prepared statements, CSRF enforcement on
state-changing operations, session-fixation prevention, and generic error
handling. This decision needs to define how the rewrite handles those rules with
the code that is already in place.

## Decision

Adopt the following authentication and session architecture for Starlight_V3.

### Password hashing

- Use PHP's built-in `password_hash()` and `password_verify()` pair for
  registration and login.
- The User model or its persistence layer owns hashing policy; auth services
  consume stored hashes but do not emit plaintext credentials.
- Minimum password length and any other complexity policy remain config-driven.

### Session storage

- Sessions are backed by a Redis session handler registered before
  `session_start()`.
- The handler is initialized in the front controller and owns the session
  storage details; auth services only read and mutate `$_SESSION`.
- Successful login regenerates the session identifier.
- Logout destroys the active session.

### Session cookie

- Cookie settings are owned by the PHP session runtime and environment
  configuration.
- The session lifecycle must remain compatible with same-origin browser forms
  and the existing `RedisSessionHandler` bootstrap.

### CSRF

- CSRF tokens are generated and verified by `src/Infrastructure/Csrf.php`.
- The front controller rejects POST requests whose `_csrf` field does not
  match the session token.
- The token is delivered through the server-rendered form flow and embedded in
  the page state where needed.

### Login rate limiting

- No login throttling policy is implemented yet in the rewrite.
- If login abuse becomes a shipping concern, add a new follow-up decision
  rather than hiding rate limiting inside the auth controller.

### MVC-S placement

- `src/Services/AuthService.php` owns register, login, logout, and user lookup
  behavior.
- `src/Controllers/AuthController.php` exposes the HTTP boundary and keeps the
  session payload in sync with the authenticated user.
- `src/Infrastructure/Csrf.php` owns token generation and verification.
- `public/index.php` owns session handler bootstrapping before dispatch.

## Consequences

Positive:

- Single pattern for login/logout flows already used by the rewrite.
- Redis session handling keeps the app compatible with the existing front
  controller bootstrap.
- CSRF stays simple and local to the PHP codebase.

Negative:

- The current flow still mixes some auth/session policy across controller,
  infrastructure, and service layers.
- There is no formal login throttling or recovery flow yet.

## Alternatives Considered

- Native PHP file-backed sessions.
- JWT / stateless tokens.
- Moving CSRF validation into a separate API-only service.

## Rollout Plan

1. Keep the Redis session handler bootstrapped in `public/index.php`.
2. Keep auth controller actions aligned with the current form-based login and
   logout routes.
3. Tighten CSRF handling if the frontend flow becomes more API-like.
4. Revisit login throttling as a separate decision if abuse patterns appear.

## Validation Plan

- Auth register/login/logout flows continue to work through the current
  controller and session bootstrap.
- CSRF rejection still blocks unsafe POST requests without a valid `_csrf`
  token.
- Session regeneration still occurs on successful login.

## Open Questions

- Password reset flow.
- Login throttling.
- Whether a future API-only frontend would justify moving CSRF to header-based
  requests.
