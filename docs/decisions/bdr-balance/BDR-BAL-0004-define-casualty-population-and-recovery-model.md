# BDR-BAL-0004: Define casualty, population, and recovery model for MVP warfare

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

Unit-commitment combat and a bounded-plunder economy require a third supporting layer: a clear model for military losses, population pressure, and post-battle recovery. Without this layer, combat either becomes low-stakes attrition that players can brute-force repeatedly or becomes so punishing that a single failed attack removes meaningful agency.

The team direction is to make warfare feel more consequential and more realistic than abstract turn spending, while still preserving anti-runaway principles. That means the system must model three things at once:

- committed attackers should risk meaningful military losses
- defenders should be able to suffer military and population damage from successful attacks
- recovery windows must preserve strategic agency after defeat

The model must also avoid a degenerate state where the safest or most optimal strategy is always tiny probing attacks or, conversely, where full-send attacks create unrecoverable snowballs.

## Decision

The MVP warfare model will treat casualties, population pressure, and recovery pacing as linked systems.

The rules are:

- every combat action must expose the attacker to non-trivial loss when meaningful force is committed
- battle outcomes must distribute losses asymmetrically based on matchup strength and outcome quality, but never reduce combat to all-or-nothing wipes in ordinary cases
- successful attacks may apply bounded population pressure to the defender as part of overall war impact
- population damage must be lower-frequency and harder-capped than direct military casualties so it cannot become the dominant snowball mechanic
- recovery pacing must be designed so a defeated player can return to meaningful strategic choice inside a defined target window
- comeback protections, target protection, or diminishing-return rules may reduce repeat punishment against recently damaged players

For MVP, the design target is a system where military defeat hurts immediately, population pressure compounds only in bounded ways, and recovery remains possible through production, defensive play, and reduced repeat extraction.

## Consequences

Positive outcomes:

- attacks feel consequential because committed forces and population stability are both at risk
- war outcomes can matter beyond simple resource transfer
- defenders retain a recoverable path instead of facing immediate strategic elimination
- anti-runaway safeguards can be measured against explicit recovery targets rather than vague fairness goals

Risks and tradeoffs:

- if population pressure is too strong, dominant players can permanently suppress weaker ones
- if casualty recovery is too fast, high-risk attacks stop feeling meaningful
- if recovery protection is too generous, successful aggression feels unrewarding
- formulas will need careful simulation against both even and uneven matchups

## Alternatives Considered

- Option A: Keep population damage mostly flat and loosely tied to combat outcome.
  - Rejected because it weakens the relationship between battlefield commitment and war consequences.

- Option B: Tie casualties and bounded population pressure to combat quality and enforce explicit recovery targets.
  - Chosen because it supports meaningful warfare while keeping anti-runaway protections measurable.

## Rollout Plan

1. Define casualty bands for attacker win, marginal win, marginal loss, and decisive loss scenarios.
2. Define hard caps and trigger conditions for population damage.
3. Define the target recovery window that later economy tuning must satisfy.
4. Validate the model through simulation before any combat endpoint is treated as release-ready.
5. Pair the model with anti-farming and target-protection rules in the next mechanics decision.

## Validation Plan

- Telemetry target: decisive victories should produce meaningfully higher defender losses than marginal victories, but not create routine total wipes.
- Telemetry target: attackers who barely clear the win threshold should show materially worse efficiency than attackers who enter with stronger commitment and better composition.
- Threshold: if repeat attacks can keep a damaged player below meaningful recovery indefinitely, the model fails anti-runaway requirements.
- Threshold: if population damage contributes more to long-term suppression than military loss and economic plunder combined, the mechanic is overtuned.
- Rollback condition: if simulations cannot establish a defensible recovery window for losing players across representative matchup bands, reopen the casualty model before implementation.