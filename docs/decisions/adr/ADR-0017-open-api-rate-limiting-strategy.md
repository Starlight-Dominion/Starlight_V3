# ADR-0017: Open API Rate Limiting Strategy

- Status: accepted
- Date: 2026-05-22
- Owner: Platform Team
- Approvers: Technical Lead, Operations Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture

## Context

Open API traffic is now gated by per-key limits checked in middleware. The implementation uses Redis with a fixed one-minute window and a per-key counter, with a default of 60 requests per minute.

This is a platform behavior decision because it defines fairness, abuse resistance, and integration UX.

## Decision

Adopt Redis-backed fixed-window per-key rate limiting for Open API v1.

Rules:

- Limits are evaluated per API key, per minute window.
- Default key limit is 60 requests per minute unless overridden by admin.
- Exceeding limit returns HTTP 429.
- Counter storage uses Redis keys scoped by API key ID and minute bucket.

## Consequences

Positive:

- Simple and predictable enforcement for API consumers.
- Low operational complexity and fast lookup performance in Redis.
- Per-key quotas allow differentiated access policies.

Negative:

- Fixed windows can produce boundary bursts.
- Requires Redis availability for enforcement path.

## Alternatives Considered

- Sliding-window limiter.
- Token-bucket limiter.
- Database-backed request counting.

## Rollout Plan

1. Keep middleware rate checks in front of controller execution.
2. Keep default at 60 RPM and adjust per key through admin controls.
3. Reassess algorithm choice before broader API endpoint expansion.

## Validation Plan

- Keys over limit receive 429 consistently.
- Keys under limit pass without false positives.
- Redis TTL and minute bucket behavior are verified under load tests.
