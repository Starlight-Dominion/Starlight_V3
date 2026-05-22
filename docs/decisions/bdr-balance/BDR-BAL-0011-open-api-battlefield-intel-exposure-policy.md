# BDR-BAL-0011: Open API Battlefield Intel Exposure Policy

- Status: accepted
- Date: 2026-05-22
- Owner: Balance Team
- Approvers: Product Lead, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: balance

## Context

Open API v1 exposes battlefield and sector intelligence to authenticated API clients, including structures, manpower-related views, and target lists. This affects scouting asymmetry, automation potential, and anti-farming posture.

Data exposure scope is therefore a balance decision, not only an API implementation choice.

## Decision

Permit authenticated Open API access to selected battlefield and sector intelligence surfaces in v1.

Rules:

- Only authenticated and active API keys can access battlefield intelligence endpoints.
- Exposed data remains read-only in v1.
- Exposure is subject to per-key rate limits and admin revocation.
- New high-sensitivity intel fields require explicit balance review before publication.

## Consequences

Positive:

- Enables community tools and tactical dashboards.
- Improves usability for analytical and planning workflows.
- Keeps access controlled through auth and rate limiting.

Negative:

- Easier aggregation can compress information advantage gaps.
- Can amplify coordinated farming if additional safeguards are not monitored.

## Alternatives Considered

- No battlefield API exposure.
- Fully public unauthenticated battlefield endpoints.
- Expose only personal kingdom data.

## Rollout Plan

1. Keep current v1 read-only intel endpoints behind key auth.
2. Monitor abuse indicators and key revocation frequency.
3. Require balance sign-off for any expansion of exposed combat fields.

## Validation Plan

- Battlefield endpoints require valid active API keys.
- Rate limiting constrains high-volume scraping behavior.
- Abuse review cadence includes API-driven intel usage patterns.
