# BDR-BAL-0006: Define anti-farming safeguards and anti-total-loss protections

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

Repeated mass attacks can push players into a perceived futile state where they cannot recover meaningful agency. That experience directly increases churn risk and undermines competitive integrity.

MVP combat must therefore include explicit safeguards that limit repeated extraction and preserve recovery opportunity, while still allowing warfare to matter.

## Decision

MVP will enforce anti-farming and anti-total-loss safeguards as first-class mechanics, not optional post-launch patches.

Required safeguards:

- Pairwise raid diminishing returns
  - repeated attacks by the same attacker/alliance toward the same target decay in effective plunder and pressure yield across a rolling time window

- Target recovery shield
  - after severe loss thresholds, temporary protection reduces incoming extraction and pressure effects for a bounded period

- Protected reserve floor
  - a non-zero resource reserve floor cannot be extracted by raid plunder, ensuring minimal rebuild capacity

- Population pressure caps
  - hard caps on cumulative population pressure per rolling time window to prevent repeated suppression loops

- Cooldown tracking
  - attacker-target cooldown state records prevent rapid serial exploitation patterns

- Repeat-victim penalties
  - if attacker behavior concentrates excessively on one target, payout efficiency decays faster than normal and may hit hard stop thresholds

The safeguards must be visible enough that players understand recovery is possible, but bounded enough that successful attackers still gain meaningful war value.

## Consequences

Positive outcomes:

- reduced churn from perceived hopelessness after mass attacks
- stronger anti-runaway posture
- clearer incentives to diversify targets and strategic choices
- better long-term retention in competitive cohorts

Tradeoffs and risks:

- aggressive players may perceive reduced short-term reward for repeat pressure
- safeguards can be exploited if thresholds are too generous or too predictable
- implementation complexity increases due to per-pair tracking and rolling windows

## Alternatives Considered

- Option A: rely only on social diplomacy and alliance response to counter farming.
  - Rejected because it does not reliably protect unaffiliated or weaker players.

- Option B: codify explicit diminishing-return, reserve-floor, and cooldown protections.
  - Chosen because it provides systemic retention protection without removing meaningful conflict.

## Rollout Plan

1. Add safeguard formulas and examples to game-design docs.
2. Add cooldown and protection-state entities to data-model baseline.
3. Add deployment and battle-report API contracts that surface diminishing-return states.
4. Simulate repeated-attack scenarios before combat endpoints are finalized.
5. Tune thresholds with staged rollout and telemetry review.

## Validation Plan

- Telemetry target: repeat attacks against the same target show measurable payout decay over the configured window.
- Telemetry target: players crossing severe-loss thresholds still retain measurable re-entry actions in the recovery window.
- Threshold: if protected players remain extractable to near-zero effective agency through repeated raids, safeguards fail.
- Threshold: if safeguards trivialize warfare by eliminating reward for successful attacks, retune decay curves and caps.
- Rollback condition: if abuse simulations show attackers can bypass protection through simple rotation exploits, pause rollout and revise controls.