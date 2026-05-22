# BDR-BAL-0002: Adopt unit-commitment combat as the primary attack model

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

The legacy turn-cost attack model creates optimization behavior around turn efficiency rather than battlefield commitment. Team discussion identified several resulting problems:

- the most efficient attack size can become a solved spreadsheet problem
- population damage can feel disconnected from actual military risk
- plunder potential scales with abstract turn spending rather than committed force
- the system does not support a more war-simulator-like feel where force allocation meaningfully changes risk, damage, and reward

The desired replacement direction is a high-risk/high-reward model where players choose how much of their army to commit. Sending a full army should increase offensive reach and plunder capacity but also expose more value to counter-loss. Sending a marginal force should reduce upside and increase the chance of an inefficient trade.

## Decision

Starlight v3 MVP will use unit commitment, not attack-turn expenditure, as the primary cost driver for combat actions.

The combat model must follow these rules:

- players choose a specific force package to deploy into an attack
- offensive outcome, plunder ceiling, and casualty exposure all scale from committed force and matchup conditions
- full-force attacks are high upside but high exposure
- marginal-force attacks may still win, but should be less efficient in casualties and resource return
- the system must support population pressure as a consequence of successful warfare, while preserving anti-runaway protections
- combat randomness must be bounded; player commitment and force composition remain the dominant factors

The legacy concept of spending multiple attack turns to amplify the same action is not the primary combat lever for MVP. If time-based pacing is still required operationally, it must appear as cooldown or deployment resolution behavior rather than as a stackable turn-cost multiplier.

## Consequences

Intended outcomes:

- combat decisions become more legible and strategically expressive
- risk and reward are tied to military commitment rather than abstract turn math
- population damage and plunder can scale from battle circumstances more naturally
- the system is better aligned with later logistics and escort mechanics

Risks and side effects:

- implementation complexity is higher than a linear turn multiplier
- attack outcome formulas must be carefully clamped to avoid deterministic snowballing
- anti-farming and target-protection rules become more important
- legacy attack-turn-oriented formulas cannot be reused unchanged

## Alternatives Considered

- Option A: Keep turn-cost attacks as the core combat model.
  - Rejected because it preserves the efficiency-mining behavior the team wants to remove.

- Option B: Use unit commitment as the primary attack model, with pacing controlled separately.
  - Chosen because it makes battlefield commitment the core strategic choice while leaving room for cooldown and resolution controls.

## Rollout Plan

1. Define the casualty, plunder, and population-loss models that make committed-force tradeoffs explicit.
2. Define anti-abuse guardrails before combat implementation starts.
3. Reflect the decision in game-design baselines and combat simulation scenarios.
4. Build deployment-centric data and API contracts only after the supporting balance decisions are drafted.
5. Revisit logistics extensions such as cargo carriers in post-MVP decisions rather than bundling them into MVP scope.

## Validation Plan

- Telemetry target: committed force size should correlate positively with outcome swing and casualty exposure.
- Telemetry target: there should be no dominant attack size that outperforms across most target classes with materially lower risk.
- Threshold: if a narrow force-commitment band becomes the clear optimal attack package across most engagements, the model requires rebalance before release.
- Threshold: if recovery time from one failed full-commitment attack becomes so severe that players are effectively eliminated from meaningful play, anti-runaway protections must be strengthened.
- Rollback condition: if bounded-randomness simulations cannot produce stable outcome bands across representative matchups, reopen the decision with a replacement BDR-BAL.