# ADR-0003: Adopt Phinx for Database Migrations and Schema Versioning

- Status: accepted
- Date: 2026-04-14
- Owner: Technical Lead
- Approvers: Backend Lead, Database Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

v3 requires a structured, repeatable schema versioning strategy from the start. v1 managed schema as a single dump file with no migration history, making incremental changes hard to track and deploy safely. v2 adopted Phinx and Phinx migrations with good results. v3 must formalise this before any tables are created.

## Decision

Adopt Phinx as the database migration and schema versioning tool for v3.

Key rules:
- All schema changes must be expressed as Phinx migration files.
- Migrations are run automatically in CI and on deployment.
- No manual `ALTER TABLE` or ad-hoc SQL schema changes outside of migrations.
- Rollback migrations must be provided for every destructive change.

## Consequences

Positive:
- Schema history is fully auditable in source control.
- Safe rollback for destructive migrations.
- Consistent schema across all environments (dev, staging, prod).
- Familiar to the team from v2.

Negative:
- Migration files must be maintained alongside code changes.
- Complex data migrations require careful rollback design.

## Alternatives Considered

- Doctrine Migrations (heavier, more framework-coupled)
- Flyway (JVM-based, adds non-PHP tooling dependency)
- Manual SQL dump versioning (v1 approach — rejected due to poor traceability)

## Rollout Plan

1. Add `robmorgan/phinx` to `composer.json`.
2. Create `config/phinx.php` with environment-aware DB config.
3. Create `database/migrations/` directory for all migration files.
4. Seed initial migration from schema design baseline.

## Validation Plan

- `composer phinx migrate` must run cleanly in Docker and CI with exit code 0.
- `composer phinx rollback` must run cleanly for all destructive migrations.
- CI workflow runs migrations before integration tests.
