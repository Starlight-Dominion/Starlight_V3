# BDR-BAL-0007: Define MVP espionage baseline around intelligence and sabotage

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

Espionage is a key strategic layer, but broad mission scope at MVP would increase exploit risk and balancing complexity while combat and economy are still stabilizing.

The team direction is to keep MVP espionage focused and interpretable, with optional future expansion into visible infiltration/defection systems after MVP.

## Decision

MVP espionage includes two mission classes only:

- Intelligence: gather targeted state information with probabilistic success
- Sabotage: apply bounded disruption to selected systems with counterplay

MVP excludes hidden unit-defection conversion mechanics. Any infiltration/defection design is deferred to post-MVP as a visible non-spy system through a separate decision.

Espionage rules for MVP:

- success and detection are both probability-based and derived from offense-defense spy power comparison
- mission outcomes are bounded with hard caps to avoid extreme one-shot impact
- repeated espionage against the same target enters diminishing effectiveness bands
- counter-intelligence investment must produce meaningful mitigation
- espionage cannot bypass anti-total-loss safeguards defined for warfare systems

## Consequences

Positive outcomes:

- espionage adds strategic depth without destabilizing MVP
- counterplay is preserved through defensive spy investment
- system is simpler to tune and communicate than a broad mission tree

Tradeoffs and risks:

- players seeking deeper covert gameplay may find MVP mission variety limited
- sabotage boundaries must be tuned carefully to avoid either irrelevance or abuse
- intelligence quality must be useful enough to justify mission cost

## Alternatives Considered

- Option A: include broad covert mission set including hidden defection in MVP.
  - Rejected because it adds high exploit surface before core systems stabilize.

- Option B: launch with intelligence + sabotage baseline and defer defection mechanics.
  - Chosen because it keeps MVP coherent and controllable.

## Rollout Plan

1. Define intelligence/sabotage formulas and output bounds in game-design docs.
2. Add espionage event and report entities to data-model baseline.
3. Add API contracts for mission submission and report retrieval.
4. Simulate repeated-use and counterplay scenarios.
5. Revisit post-MVP expansion after core combat/economy telemetry stabilizes.

## Validation Plan

- Telemetry target: espionage usage shows both offensive and defensive strategic value.
- Telemetry target: sabotage outcomes remain inside bounded disruption windows.
- Threshold: if one mission type dominates espionage utility across most states, rebalance mission value/cost.
- Threshold: if repeat espionage enables effective lockout of target agency, safeguards are insufficient.
- Rollback condition: if mission abuse paths bypass anti-farming and protection systems, halt rollout and revise mission constraints.