# BDR-BAL-0008: Bank Liquidity Controls and Daily Deposit Limits

- Status: accepted
- Date: 2026-05-21
- Owner: Economy Team
- Approvers: Product Lead, Technical Lead
- Supersedes: none
- Superseded by: none
- Scope: balance

## Context

The bank flow in `Bank.svelte` and `BankService` currently enforces two hard
controls that shape player liquidity behavior:

- deposits are capped at 80% of current liquid credits in the client UI
- total deposits are limited to 6 per day in the service layer

These are not just UI affordances. They control how quickly players can move
currency into safety, how much friction bank usage has, and how much liquidity
can be shielded from raids or other losses.

## Decision

Adopt the current bank liquidity controls as the balance baseline.

Rules:

- Players may not deposit more than 80% of current liquid credits in a single
  action.
- Players may not exceed 6 deposits per day.
- Withdrawals remain limited by the amount currently banked.
- The UI must reflect the same caps enforced by the service layer.

## Consequences

Positive:

- Prevents the bank from becoming an unconditional instant-safe storage slot.
- Preserves liquidity risk and keeps banking a strategic choice.
- Limits spammy deposit behavior and reduces repetitive bank churn.

Negative:

- Players lose some flexibility when moving large amounts of currency.
- The deposit cap can feel punitive if the player expects a pure storage vault.

## Alternatives Considered

- Unlimited deposits.
- A lower deposit cap.
- A higher daily deposit allowance.

## Rollout Plan

1. Keep the current client and service caps aligned.
2. Surface the bank limits in player-facing copy.
3. Revisit the limit if liquidity data shows it is too restrictive.

## Validation Plan

- Bank tests confirm the client and service enforce the same cap.
- Economic telemetry tracks deposit frequency and cap-triggered failures.
- Balance review checks whether the 80% / 6-per-day rule remains healthy.