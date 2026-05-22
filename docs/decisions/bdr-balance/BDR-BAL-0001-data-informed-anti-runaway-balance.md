# BDR-BAL-0001: Data-Informed, Anti-Runaway Balance Policy

- Status: accepted
- Date: 2026-04-14
- Owner: Balance Lead
- Approvers: Backend Lead, Product Owner
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

v1 and v2 contain extensive configurable balance mechanics, but rationale and guardrails are fragmented. v3 needs explicit balance policy to prevent runaway economics and discouraging player gaps.

## Decision

Balance changes in v3 must follow policy:

1. Prefer anti-runaway mechanics over winner acceleration.
2. Require metric-backed rationale before major tuning changes.
3. Define rollback thresholds for each major balance change.
4. Keep tunable constants centralized in configuration.

## Consequences

Positive:

- Reduced risk of unstable economy/combat shifts.
- Faster incident response through predefined rollback thresholds.

Negative:

- Slower approval for urgent tuning changes without telemetry.

## Alternatives Considered

- Manual balance tuning without formal records
- One-off tuning hotfixes without thresholds

## Rollout Plan

1. Publish balance policy baseline doc.
2. Require BDR-BAL for major tuning or mechanic changes.
3. Add balance review checkpoints to release prep.

## Validation Plan

- Each major balance change includes telemetry and thresholds.
- Monthly balance log review confirms compliance.
