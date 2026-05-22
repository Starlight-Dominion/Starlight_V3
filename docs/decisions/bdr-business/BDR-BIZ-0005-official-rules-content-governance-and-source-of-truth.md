# BDR-BIZ-0005: Official Rules Content Governance and Source of Truth

- Status: accepted
- Date: 2026-05-22
- Owner: Product Team
- Approvers: Product Lead, Operations Lead
- Supersedes: none
- Superseded by: none
- Scope: business

## Context

The official rules/manual content is now stored in game_settings and rendered on the public rules page. The content can be updated operationally without code deploys.

This changes policy governance from static docs to runtime-managed content and requires explicit ownership and control.

## Decision

Treat the game_settings official_rules value as the runtime source of truth for player-facing rules text.

Rules:

- The public /rules page renders from the official_rules configuration value.
- Changes to official rules content are operational policy changes and must be approved by product ownership.
- Significant policy shifts must reference or create a decision record.
- Migration-seeded baseline content is the initial canonical version for launch.

## Consequences

Positive:

- Policy communication can be updated quickly without waiting for a deploy.
- Rules page stays aligned with current operational policy.

Negative:

- Runtime editing can introduce unreviewed policy drift if governance is weak.
- Historical diff visibility is weaker than file-based versioned docs unless change logging is maintained.

## Alternatives Considered

- Keep rules as hard-coded static page content.
- Keep rules only in repository markdown docs.
- Render rules from an external CMS.

## Rollout Plan

1. Keep seeded manual content as baseline.
2. Restrict settings updates to admins.
3. Add operational runbook guidance for when a rules content change requires a new decision record.

## Validation Plan

- /rules reflects current official_rules content.
- Admin settings updates are required to change runtime content.
- Product review process is followed for major policy edits.
