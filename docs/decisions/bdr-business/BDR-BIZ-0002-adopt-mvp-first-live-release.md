# BDR-BIZ-0002: Adopt MVP-first live release with staged mechanics expansion

- Status: accepted
- Date: 2026-04-15
- Owner: Product Owner
- Approvers: Product Owner, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: business
- Imported from: old starlight V3 corpus

## Context

Starlight v3 needs to return to a playable public state quickly without repeating the pattern of holding the product behind an under-construction page while major systems are still unsettled. The current team discussion also makes clear that the long-term vision includes deeper combat, higher realism, cargo logistics, and visible ideological conversion mechanics, but those systems are not equally mature.

If all of those mechanics are treated as day-one scope, v3 risks long delays, thin testing, and a release that mixes unvalidated systems with unclear player expectations. The project instead needs a disciplined release boundary: ship a smaller but coherent competitive loop, then add additional mechanics through governed post-launch increments.

## Decision

Starlight v3 will release as an MVP-first live product with staged mechanics expansion.

The MVP scope will prioritize:

- a playable economy loop
- unit-commitment combat as the primary attack model
- plunder, casualty, and population-pressure systems
- a reduced espionage set focused on intelligence and sabotage
- core anti-abuse protections and recovery pacing

The following mechanics are explicitly deferred to post-MVP unless a later accepted BDR-BIZ changes scope:

- dedicated cargo/carrier transport units
- visible infiltration or unit-defection systems
- class-specific theological or ideological specializations
- any additional subsystem that lacks decision traceability, validation thresholds, or simulation coverage

All post-MVP mechanics must enter through the existing ADR/BDR workflow and be sequenced as live expansions rather than silently folded into MVP scope.

## Consequences

Positive outcomes:

- v3 can reach a publicly playable state sooner.
- The team can measure real player behavior before adding second-order complexity.
- Balance risk is reduced because the first release contains fewer interacting systems.
- Documentation and implementation scope become easier to align.

Tradeoffs and costs:

- Some high-interest ideas will be intentionally delayed.
- MVP messaging must be explicit so players understand the live roadmap.
- The team must resist scope creep during early implementation.

Product impact:

- Release planning shifts from "finish everything first" to "ship the smallest competitive core that can sustain iteration."

## Alternatives Considered

- Option A: Hold release until most or all target mechanics are complete.
  - Rejected because it increases schedule risk, delays feedback, and encourages undocumented late-stage design changes.

- Option B: Release MVP-first with a fixed post-MVP mechanic queue.
  - Chosen because it supports faster learning, clearer governance, and lower initial balance complexity.

## Rollout Plan

1. Mark MVP mechanics explicitly in the project charter and game-design baseline.
2. Draft the critical-path BDR-BAL records for combat, economy, casualties, and anti-abuse before implementation begins.
3. Build implementation epics only for accepted MVP decisions.
4. Publish a post-MVP mechanic queue for cargo logistics and visible infiltration systems.
5. Communicate in release notes and roadmap materials that v3 is intentionally launching as a governed MVP.

## Validation Plan

- KPI: MVP release candidate contains only mechanics with accepted decision records and linked validation criteria.
- KPI: No post-MVP mechanic enters implementation without a governing BDR and index entry.
- Review checkpoint: scope review before first combat implementation sprint.
- Reversal criteria: if MVP scope fails to produce a coherent competitive loop in prototype/simulation review, reopen scope with a new BDR-BIZ rather than broadening informally.