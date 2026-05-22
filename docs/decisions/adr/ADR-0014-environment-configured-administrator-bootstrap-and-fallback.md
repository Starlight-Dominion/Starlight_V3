# ADR-0014: Environment-Configured Administrator Bootstrap and Fallback

- Status: accepted
- Date: 2026-05-21
- Owner: Platform Team
- Approvers: Technical Lead, Security Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture

## Context

The rewrite includes an emergency/admin override path in `AuthService` that
checks `$_ENV['ADMIN_USERNAME']` before falling back to the stored `is_admin`
flag on the user record. That means there are effectively two admin bootstrap
mechanisms in play: a persisted privilege flag and an environment-configured
username override.

This is a meaningful architecture decision because it changes who can reach
high-privilege code paths during development, recovery, and operations. The
policy must be explicit so the fallback does not remain an accidental side
effect of authentication code.

## Decision

Adopt an environment-configured administrator bootstrap fallback for the
rewrite.

Rules:

- `is_admin` remains the canonical persisted privilege flag.
- `ADMIN_USERNAME` is treated as an explicit operational override for a single
  privileged account name.
- The override must be environment-driven and not hard-coded in source.
- Production deployments must document the override value and the reason it is
  enabled.
- Application code must continue to check the persisted admin flag when the
  environment override is absent.

## Consequences

Positive:

- Gives operations a recovery path if the normal admin account is unavailable.
- Keeps the override visible and environment-scoped instead of burying it in a
  database seed or code constant.

Negative:

- Expands the attack surface if the override is misconfigured or leaked.
- Requires explicit runbook discipline so the fallback does not remain enabled
  unnecessarily.

## Alternatives Considered

- Hard-code a superuser in code.
- Remove all bootstrap overrides and rely only on persisted privileges.
- Store the fallback in the database instead of the environment.

## Rollout Plan

1. Keep the current `AuthService` logic while documenting the override policy.
2. Add runbook notes for enabling, disabling, and auditing the override.
3. Revisit the mechanism if role-based admin bootstrap becomes more formal.

## Validation Plan

- Authentication tests cover both the persisted admin flag and the environment
  override path.
- Deployment docs state whether `ADMIN_USERNAME` is present.
- Security review confirms the fallback is intentional and monitored.