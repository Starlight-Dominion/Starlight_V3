# BDR-BIZ-0004: Open API Access Program and Approval Policy

- Status: accepted
- Date: 2026-05-22
- Owner: Product Team
- Approvers: Product Lead, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: business

## Context

The rewrite introduces an application-based API access flow where users submit project intent, admins review pending requests, and approved users receive scoped API keys.

This is a product policy decision, not just implementation detail, because it defines who may access programmatic game data and under what governance.

## Decision

Operate Open API access as an application-and-approval program.

Rules:

- Users must submit project name and justification before key issuance.
- One pending application per user is allowed at a time.
- High Command (admin) reviews each application and approves or rejects with notes.
- Approval may include a custom per-key rate limit.
- Rejected requests do not receive keys and may be resubmitted as a new request.

## Consequences

Positive:

- Controlled rollout of external integrations.
- Human review reduces abuse and low-value automation.
- Gives operations a clear gate for risk-sensitive access.

Negative:

- Manual review adds turnaround time.
- Policy consistency depends on admin process discipline.

## Alternatives Considered

- Open self-service key issuance.
- Invite-only API access without user-facing application flow.
- Permanent denial of external API access.

## Rollout Plan

1. Keep settings page application flow available to logged-in users.
2. Keep admin queue and processing endpoints as the approval authority.
3. Publish response-time expectations and rejection guidance in product docs.

## Validation Plan

- Pending applications are visible in admin review queue.
- Approved applications produce active keys.
- Rejected applications store review notes and remain auditable.
- Duplicate pending submissions are blocked.
