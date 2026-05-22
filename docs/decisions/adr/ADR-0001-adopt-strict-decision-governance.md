# ADR-0001: Adopt Strict Decision Governance for v3

- Status: accepted
- Date: 2026-04-14
- Owner: Architecture Lead
- Approvers: Product Owner, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight v3 is being built with v1 and v2 as references. Prior versions include strong implementation detail but uneven decision traceability. Without strict governance, architecture and gameplay direction can drift and become inconsistent across teams.

## Decision

Adopt a strict decision framework with three mandatory streams:

- ADR for technical architecture
- BDR-BIZ for business and product decisions
- BDR-BAL for game balance decisions

All major baseline documents and major implementation changes must reference accepted decision IDs.

## Consequences

Positive:

- Decisions become auditable and searchable.
- Cross-team alignment improves.
- Contradictory changes are reduced.

Negative:

- Additional process overhead for major changes.
- Initial documentation workload increases.

## Alternatives Considered

- Keep informal decisions in issue comments
- Use ADR only and merge business/balance into one stream

## Rollout Plan

1. Create governance docs and templates.
2. Seed first accepted records.
3. Require decision references in all base docs.
4. Enforce decision references in major PRs.

## Validation Plan

- Verify first-wave base docs include ADR/BDR references.
- Run monthly audit for missing references.
