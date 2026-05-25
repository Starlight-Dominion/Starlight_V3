# Contributing

## Branch and PR Workflow

- Use one branch per optimization concern.
- Use Conventional Commits for every commit.
- Keep PRs small and reviewable; target one logical change per PR.
- Prefer additive and reversible database migrations.

## Required Validation

- Backend tests: `composer test`
- Migration status check: `composer migrate:status`
- Frontend production build: `npm run build`

## Code Standards

- Use `declare(strict_types=1);` in PHP source files.
- Add explicit return types for public methods when practical.
- Keep service logic focused and avoid query duplication.
- Prefer batched query patterns to avoid N+1 loops.

## SQL Performance Expectations

- Index filter+sort patterns together for hot paths.
- Verify index-oriented changes with query plans in PR notes.
- Avoid loading unbounded datasets in user-facing flows.

## PR Checklist

- [ ] Change is scoped to one concern.
- [ ] Commit message follows Conventional Commits.
- [ ] Tests/build run for affected areas.
- [ ] Migration is reversible when schema is changed.
- [ ] Rollback risk is documented in PR description.
