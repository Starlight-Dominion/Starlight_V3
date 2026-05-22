# BDR-BAL-0009: Identity Edit Cost and Rebranding Pricing

- Status: accepted
- Date: 2026-05-21
- Owner: Balance Team
- Approvers: Product Lead, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: balance

## Context

`SettingsService` currently charges 1,000,000 credits when a player changes
their identity handle. That cost is doing real balance work: it adds friction to
rebranding, prevents casual identity churn, and converts a cosmetic change into
an economic decision.

The code already treats the fee as a meaningful gating mechanism. The decision
needs to be documented so the price does not drift silently.

## Decision

Adopt a 1,000,000 credit fee for username rebranding in the rewrite.

Rules:

- Handle changes are not free.
- The fee applies when the requested username differs from the current one.
- Email changes remain separately validated but do not incur this fee.
- Any future change to the fee must come through a new balance decision.

## Consequences

Positive:

- Reduces identity churn and discourages opportunistic rebranding.
- Makes the handle change feel consequential rather than cosmetic spam.
- Creates a sink for surplus credits.

Negative:

- Can block legitimate rebrands for lower-resource players.
- Creates a potentially frustrating cost if the player only wants a minor
  cleanup.

## Alternatives Considered

- Free handle changes.
- A lower fixed fee.
- A fee that scales with account age or progression.

## Rollout Plan

1. Keep the current fee in `SettingsService` as the baseline.
2. Document the cost in player-facing copy where the handle change is offered.
3. Revisit the price if telemetry shows it is either too punitive or too cheap.

## Validation Plan

- Settings tests confirm the fee is enforced when the username changes.
- Economy telemetry tracks how often players hit the fee gate.
- Balance review checks whether the 1,000,000 credit price remains appropriate.