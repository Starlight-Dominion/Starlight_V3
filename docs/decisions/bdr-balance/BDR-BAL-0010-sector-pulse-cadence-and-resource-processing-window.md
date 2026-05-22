# BDR-BAL-0010: Sector Pulse Cadence and Resource Processing Window

- Status: accepted
- Date: 2026-05-22
- Owner: Balance Team
- Approvers: Product Lead, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: balance

## Context

Current gameplay messaging and client countdown behavior assume a 15-minute sector pulse. The pulse cadence directly controls production timing, planning pressure, and combat tempo.

Cadence is a primary balance lever and must be explicit to avoid accidental drift.

## Decision

Adopt a 15-minute sector pulse as the baseline processing window.

Rules:

- Core resource and progression updates are modeled around 15-minute processing intervals.
- Player-facing countdown and pacing communications must align to this interval.
- Future cadence changes require a new balance decision and communication plan.

## Consequences

Positive:

- Predictable pacing for planning attacks, upgrades, and resource spending.
- Shared player expectations across UI timers and operational rules text.

Negative:

- Slower cadence may feel less responsive for short-session players.
- Faster cohorts may perceive idle gaps between decision points.

## Alternatives Considered

- 5-minute pulse cadence.
- 10-minute pulse cadence.
- Dynamic cadence by activity level.

## Rollout Plan

1. Keep countdown and documentation aligned to 15-minute intervals.
2. Monitor economy and engagement metrics for cadence fit.
3. Re-evaluate only with a bundled balance proposal.

## Validation Plan

- UI timer behavior and rules text both reflect 15-minute cadence.
- Economy telemetry remains stable under current cadence.
- Support volume does not indicate systemic pacing confusion.
