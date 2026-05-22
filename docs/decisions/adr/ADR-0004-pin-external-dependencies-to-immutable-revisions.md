# ADR-0004: Pin External Dependencies to Immutable Revisions

- Status: accepted
- Date: 2026-04-14
- Owner: Technical Lead
- Approvers: Security Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight v3 will depend on external packages, runtimes, container images, and CI automation from multiple ecosystems including Composer, npm, Docker, and GitHub Actions. Floating references such as broad version ranges, mutable tags, or unpinned action versions increase supply-chain risk and make builds less reproducible. If upstream content changes unexpectedly, the same repository revision can produce different outcomes across environments.

The project needs a consistent policy before feature work begins so that dependency upgrades are deliberate, reviewable, and reversible.

## Decision

Adopt an immutable pinning policy for all external dependencies and automation inputs.

Rules:

- GitHub Actions must be pinned to full commit SHAs, not moving tags alone.
- Docker base images and pulled images must be pinned to immutable digests where feasible.
- Composer and npm dependencies must use deterministic lockfiles committed to the repository.
- Direct VCS dependencies must be pinned to immutable commit SHAs.
- Runtime versions used in CI and local tooling must be pinned to explicit versions, not floating `latest` aliases.

Where an ecosystem does not practically support SHA pinning for normal package consumption, the project will use the strongest available immutable mechanism, such as exact version pinning plus a committed lockfile.

## Consequences

Positive:

- Builds become reproducible across developer machines and CI.
- Supply-chain exposure from silent upstream changes is reduced.
- Dependency updates become explicit review events.

Negative:

- Upgrades require more deliberate maintenance.
- Dependency refresh work becomes slightly slower.
- Tooling and review checklists must enforce the policy consistently.

## Alternatives Considered

- Use moving major-version tags for convenience and upgrade automatically with upstream changes.
- Pin only high-risk inputs such as GitHub Actions while allowing floating package ranges elsewhere.

## Rollout Plan

1. Update CI workflows to pin third-party GitHub Actions to full commit SHAs.
2. Require committed lockfiles for every package manager introduced in the repo.
3. Pin Docker images and runtime versions in workflows and local setup files.
4. Add lightweight review or CI checks to flag newly introduced floating references.

## Validation Plan

- CI workflows contain only SHA-pinned third-party actions.
- Any introduced package manager includes a committed lockfile.
- Dependency review checklist explicitly verifies immutable pinning before merge.
- Periodic dependency update PRs demonstrate that upgrades are deliberate and isolated.