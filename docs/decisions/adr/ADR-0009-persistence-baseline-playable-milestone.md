# ADR-0009: Persistence Baseline for Playable Milestone

- Status: accepted
- Date: 2026-04-20
- Owner: Rihoj
- Approvers: mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight_V3 already uses Eloquent models, Phinx migrations, and service-owned
write boundaries. The rewrite needs a persistence baseline that keeps auth,
settings, combat, economy, and logging data consistent without hiding schema
changes in controllers or ad hoc SQL.

The current codebase also has a broad set of gameplay tables in use or expected
by services: users, dominions, structures, units, armory items, bank-ledger
rows, battle logs, game settings, sessions, and related supporting tables.
Those tables need a clear ownership model so future features do not widen the
hot `users` row or bypass migrations.

## Decision

### 1. Eloquent models are the canonical entity layer

- Application reads and writes should go through models under `src/Models`.
- Controllers do not own persistence rules.
- Services may coordinate multi-table writes, but the schema remains modelled in
  Eloquent and the migration layer.

### 2. Phinx remains the only schema-change path

- All schema changes must land as Phinx migrations under `migrations/`.
- No ad hoc SQL changes, controller DDL, or runtime schema mutation.
- Migration files must keep the rewrite reproducible from a clean database.

### 3. Core domains stay separated by table

- Auth and identity state stay in user/session-related tables.
- Dominion and profile state stay in domain tables rather than on the auth row
  alone.
- Structures, units, armory, economy, and logging each retain their own tables
  and model boundaries.
- Audit-style rows and logs remain append-heavy and should not be soft-mixed
  into unrelated profile records.

### 4. Services own transactions and coordination

- Multi-table writes belong in Services, not Controllers or Repositories.
- Settings, combat, economy, and tick-driven updates may use transactions when a
  single user action spans multiple tables.

### 5. Keep hot rows narrow

- Avoid widening the `users` row with unrelated gameplay counters.
- Prefer dedicated tables for logs, ledgers, and domain-specific counters.
- Keep mutable gameplay state in the table that owns that domain.

## Consequences

Positive:

- Schema changes stay explicit and reviewable.
- The rewrite keeps persistence concerns aligned with the current service
  boundaries.
- Narrower hot rows reduce contention as the game state grows.

Negative:

- More tables and joins than a single wide-row design.
- Schema evolution requires migration work before feature code lands.

## Alternatives Considered

- Put all state on `users`.
- Use runtime schema mutation.
- Let controllers write directly to tables.

## Rollout Plan

1. Keep the current Eloquent models and Phinx migrations in sync with the
   services.
2. Add new tables only through migrations.
3. Add service tests whenever schema changes affect multi-table writes.

## Validation Plan

- Fresh-database migrations run cleanly.
- Compliance checks continue to pass for controller/service boundaries.
- The current auth, settings, combat, and economy flows continue to work after
  schema changes.

