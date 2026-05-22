# ADR-0016: Open API Authentication and Key Lifecycle

- Status: accepted
- Date: 2026-05-22
- Owner: Platform Team
- Approvers: Technical Lead, Security Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture

## Context

The rewrite introduces public API routes under /api/v1 with bearer token authentication, admin-issued keys, key activation flags, and API key lifecycle operations. Browser routes continue to rely on cookie sessions and CSRF protection.

Without a decision record, this split trust model can drift and introduce inconsistent protections.

## Decision

Adopt a dual-channel trust model:

- Browser session endpoints use session authentication and CSRF for state-changing requests.
- Open API endpoints use Authorization: Bearer token authentication and do not require CSRF.
- API keys are issued as 64-hex opaque tokens generated from cryptographic randomness.
- Key lifecycle operations (issue, update limits/status, revoke/delete) are restricted to admin APIs.
- Disabled keys must fail authentication immediately.

## Consequences

Positive:

- API integrations can authenticate without browser cookie coupling.
- Trust boundaries are explicit by channel, reducing accidental middleware overlap.
- Admins can control and revoke API access without touching user credentials.

Negative:

- Token handling now creates a separate secret management surface.
- API key abuse risk shifts to issuance, storage, and revocation discipline.

## Alternatives Considered

- Reuse browser session cookies for API clients.
- Require CSRF for API bearer-token requests.
- Use a single shared global API key.

## Rollout Plan

1. Keep API middleware enforcement at the front controller for /api routes.
2. Keep admin-only key lifecycle endpoints as the operational control path.
3. Revisit token hashing-at-rest and rotation workflow in a follow-on ADR if threat model or compliance requirements tighten.

## Validation Plan

- Requests without a bearer token return 401.
- Requests with inactive or invalid tokens return 401.
- State-changing browser POST routes continue to enforce CSRF.
- Admin key lifecycle actions are exercised in integration checks.
