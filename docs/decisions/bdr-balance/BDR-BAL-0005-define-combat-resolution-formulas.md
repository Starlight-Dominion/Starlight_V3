# BDR-BAL-0005: Define MVP combat resolution formulas and randomness bounds

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

With unit commitment adopted, combat must no longer depend on stacked turn multipliers. The system needs transparent formulas that reward meaningful force commitment, preserve composition strategy, and avoid deterministic outcomes that remove player agency.

The formula model must also integrate with anti-runaway goals: attackers should gain value for successful commitment, but defenders must not face routine total-collapse states from a small number of raids.

## Decision

MVP combat will use a bounded-randomness score model with explicit casualty, plunder, and pressure outputs.

Combat score formula family:

- `AttackScore = (CommittedOffense * CommitEfficiency * RandomBand) - DefenderFortification`
- `DefenseScore = (DefenderDefense * DefensiveReadiness * RandomBand) + DefenderFortification`
- `OutcomeRatio = AttackScore / max(1, DefenseScore)`

Randomness rule:

- `RandomBand` is bounded to a narrow envelope around 1.0, tuned so commitment and composition dominate outcomes.

Casualty model rules:

- attackers always risk losses when they commit meaningful force
- decisive wins reduce attacker losses and increase defender losses
- marginal wins increase attacker inefficiency (higher losses per gain)
- decisive losses produce high attacker loss but are clamped to prevent near-total destruction in a single event under normal conditions

Plunder model rules:

- plunder is outcome-ratio-scaled and capped by anti-farming safeguards
- high-commitment successful attacks increase potential plunder, but only within bounded extraction limits

Population-pressure rule:

- population pressure can trigger only on successful attacks over threshold outcome quality
- population pressure has hard caps per time window to prevent repeated zero-agency suppression

## Consequences

Positive outcomes:

- combat outcomes become understandable and tunable
- commitment choices clearly affect risk and reward
- formulas can be simulated before endpoint implementation
- bounded randomness keeps combat dynamic without making it opaque

Tradeoffs and risks:

- formulas require calibration against real distributions, not just point estimates
- narrow randomness can make metas solve quickly if anti-abuse controls are weak
- tuning requires close coupling with economy and anti-farming decisions

## Alternatives Considered

- Option A: broad random swings with lighter formula weighting.
  - Rejected because it weakens strategic commitment and creates volatility-based frustration.

- Option B: bounded-randomness score model with explicit clamps and anti-abuse integration.
  - Chosen because it preserves strategy while still allowing uncertainty.

## Rollout Plan

1. Add canonical formula tables and worked examples to game-design docs.
2. Define simulation test matrix for low, medium, and high commitment cases.
3. Verify no single commitment band dominates across representative matchup classes.
4. Integrate formulas into service-layer design and API contract docs.

## Validation Plan

- Telemetry target: outcome variance remains inside accepted band while still showing strategic spread by composition and commitment.
- Telemetry target: marginal-force attacks should show lower average efficiency than well-committed attacks against similar targets.
- Threshold: if one narrow commitment band dominates win-rate-adjusted ROI across most matchups, rebalance is mandatory.
- Threshold: if a single combat event can routinely force effective total-loss states, clamps or protection controls are insufficient.
- Rollback condition: if simulation cannot stabilize casualty/plunder distributions without extreme manual exceptions, reopen formula model.