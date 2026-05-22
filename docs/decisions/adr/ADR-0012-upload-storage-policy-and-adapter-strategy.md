# ADR-0012: Upload Storage Policy and Adapter Strategy

- Status: accepted
- Date: 2026-04-21
- Owner: Rihoj
- Approvers: @Rihoj, @mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Starlight_V3 currently stores uploaded sigils through `SettingsService` into a
local public path under `public/uploads/avatars/`. That is fine for the rewrite
today, but the old storage decision no longer matches the implementation because
the current code is not adapter-driven and does not use S3/CDN backends yet.

The decision here should describe the storage policy that actually exists now
while leaving room for a future abstraction if the rewrite grows beyond the
local filesystem.

## Decision

Starlight_V3 will use direct local filesystem storage for uploads in the current
rewrite.

The concrete decisions are:

- `SettingsService` owns validation and persistence for avatar uploads.
- Uploaded files are stored under `public/uploads/avatars/`.
- The service returns a public path that the frontend can render directly.
- File type, size, and dimension checks stay in the service layer.
- Storage abstraction can be introduced later, but it is not part of the
  current implementation.

This keeps the current implementation simple and matches the code that exists in
the rewrite now.

## Consequences

Positive:

- Avatar uploads work with no additional infrastructure.
- The service layer fully controls validation and file naming.
- The current settings UI can preview and persist local sigils directly.

Negative:

- The storage strategy is coupled to the local filesystem.
- Moving to S3 or CDN-backed uploads later will require a separate decision and
  refactor.

## Alternatives Considered

- S3 or CDN-backed storage.
- Adapter-based storage ports.
- External upload URLs only.

## Rollout Plan

1. Keep the current local avatar upload path in `SettingsService`.
2. Preserve the service-level validation rules for file type, size, and
   dimensions.
3. Revisit storage abstraction only if the rewrite starts supporting multiple
   upload backends.

## Validation Plan

- Unit tests cover upload type, size, and dimension constraints.
- Integration tests verify avatar uploads persist and return a public path.
- Compliance checks confirm upload handling stays inside the service layer.