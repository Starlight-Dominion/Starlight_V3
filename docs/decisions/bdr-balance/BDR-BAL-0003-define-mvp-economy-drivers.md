# BDR-BAL-0003: Define MVP economy drivers around production, replacement, and plunder pressure

- Status: accepted
- Date: 2026-04-15
- Owner: Balance Lead
- Approvers: Balance Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: balance
- Imported from: old starlight V3 corpus

## Context

Unit-commitment combat only works if the surrounding economy creates meaningful replacement costs, recovery pacing, and strategic pressure. A shallow economy would let players spam attacks with little downside, while an overly punitive economy would make failed commitments unrecoverable and intensify runaway leads.

The MVP economy therefore needs to support three simultaneous goals:

- reward consistent empire development
- make losses and plunder matter
- preserve comeback space after setbacks

The economy must also leave room for later logistics systems such as cargo carriers without requiring a rewrite of the basic resource loop.

## Decision

The MVP economy will be defined around production, replacement, and plunder pressure.

The core rules are:

- structures and population output create the primary resource income loop
- military replacement, structural progression, and defensive preparation act as the primary resource sinks
- plunder is a meaningful but bounded transfer mechanism, not the dominant long-term resource source
- recovery pacing must allow a damaged player to re-enter meaningful decision-making without trivializing the attacker's victory
- anti-hoarding and anti-farming controls must bound repeated extraction from the same target
- any future transport mechanic must extend this economy rather than replace its fundamentals

For MVP, economy design should prioritize clarity over breadth. More resource types or specialized hauling mechanics may be added later, but the base loop must first prove that production, loss, and recovery remain in balance under sustained conflict.

## Consequences

Positive outcomes:

- combat results matter because replacement has real cost
- resource generation remains strategically tied to empire management rather than only raiding
- plunder can create pressure without becoming the only winning strategy
- later logistics additions can layer onto a stable base economy

Tradeoffs and risks:

- economy tuning becomes tightly coupled to combat tuning and must be simulated together
- if recovery is too fast, victories feel hollow
- if recovery is too slow, defeated players churn or turtle indefinitely
- explicit anti-farming rules are required early, not as an afterthought

## Alternatives Considered

- Option A: Let plunder serve as the dominant high-level resource engine.
  - Rejected because it encourages exploit loops, repeated victimization, and unstable balance.

- Option B: Center the economy on production with bounded plunder and explicit recovery pacing.
  - Chosen because it supports both strategic building play and meaningful warfare.

## Rollout Plan

1. Define the production sources, sink categories, and plunder bounds in game-design docs.
2. Pair this decision with casualty/population and anti-abuse decisions before implementation.
3. Add simulation scenarios for recovery time, repeated raiding, and replacement pacing.
4. Expose all economic tuning knobs through governed config keys when the balance config is introduced.
5. Revisit transport-specific economic extensions in a post-MVP record.

## Validation Plan

- Telemetry target: the majority of resource growth over time should come from production and development, not from repeated plunder.
- Telemetry target: a player who suffers a major raid should still regain meaningful agency within the target recovery window defined in follow-up specs.
- Threshold: if repeated attacks on the same target produce materially better returns than expanding or developing, anti-farming protections are insufficient.
- Threshold: if average recovery time after a major defeat exceeds the accepted target window for competitive re-entry, replacement and sink values must be adjusted.
- Rollback condition: if simulation shows a stable runaway state where early military lead reliably converts into unrecoverable economic lead, reopen the economy model before release.