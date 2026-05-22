# ADR-0011: Profile Identity Field Editability and Validation Rules

- Status: accepted
- Date: 2026-04-21
- Owner: Platform Team
- Approvers: Architecture Lead, Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

The rewrite already exposes a settings surface in `Settings.svelte` and
`SettingsService.php` that lets players update their identity handle, email,
password cipher, stasis state, and avatar file. That is materially different
from the old profile decision, which treated username and email as immutable
and described avatar URL fields instead of uploaded sigils.

The current code therefore needs a record that describes the actual mutable
fields and keeps class/race decisions deferred until their gameplay specs land.

**Related decisions:**
- ADR-0006: Authentication and session management (identity stability)
- ADR-0009: Persistence baseline (entity layout and user settings storage)
- ADR-0010: Backend HTTP routing and error envelope (validation response pattern)

**Dependency constraints:**
- Feature #4 (Race specs) and #5 (Class specs) are not-started in playable-milestone.md
- Class/race editing logic is deferred until those specs are complete and design decisions are finalized (pending future ADR or BDR-BAL)
- Frontend and backend MVC-S pattern is already established (ADR-0006, ADR-0007, ADR-0009, ADR-0010)

**Goals:**
- Keep the settings surface aligned with the current rewrite.
- Treat handle/email edits as service-validated identity changes rather than
   immutable profile fields.
- Keep class/race changes deferred until those gameplay decisions exist.
- Keep file uploads and cipher changes in separate flows.

## Decision

### Editable Fields (Settings Update Scope)

The following fields are user-editable in the current settings flow:

1. **username** — identity handle validated by the service layer
   - Must remain unique.
   - Handle changes currently cost credits in `SettingsService`.
   - Consequence: players can rebrand without introducing a second account.

2. **email** — contact field validated by the service layer
   - Must remain unique.
   - Consequence: account recovery and comms identity stay consistent.

3. **password cipher** — separate change flow
   - Current implementation verifies the existing password and updates the
     stored cipher through `SettingsService`.
   - Consequence: password changes remain isolated from identity edits.

4. **stasis state** — separate toggle flow
   - The settings page can engage or interrupt stasis.
   - Consequence: operational status is managed independently from identity.

5. **avatar file** — local sigil upload
   - Stored as a binary file upload, not a URL.
   - Allowed types and dimensions are enforced in `SettingsService`.

### View-Only Fields (Immutable Identity)

The following fields are read-only and never updated by the profile endpoint:

- **class_slug** — current class selection; see Deferred Decision section
- **race_slug** — current race selection; see Deferred Decision section
- **created_at** — account creation timestamp; immutable
- **mfa_enabled**, **mfa_type**, **recovery_codes_count** — MFA status; immutable via profile (use separate MFA endpoints)
- **password_hash** — immutable via profile (use separate password-change endpoint)

**Rationale for immutability:**
- Class/race data remains governed by separate gameplay specs.
- Creation timestamps and MFA state should not be edited through settings.
- Password changes must remain isolated from the identity edit flow.

### Deferred Decision: Class/Race Editability

Class/race editing is still deferred until design specs #4 and #5 are complete.
The current settings surface shows the existing values read-only.

**Deferred choices (pending future ADR or BDR-BAL):**
- One-time class/race change at account creation vs. free re-roll or race-locked-class architecture
- Race/class availability gating (e.g., per-server caps, population balancing)
- Class/race change cost (free, in-game currency, real-money only, or disabled after grace period)
- Leaderboard reset or character-alt strategy on class/race change

**Unblocking rationale:** Profile landing can ship with read-only display; editing rules are captured in future balance/spec decisions once gameplay implications are modeled.

## Consequences

### Positive
- **Reduced ambiguity:** the record now matches the current settings flow.
- **Player agency:** players can update handle, email, password, stasis, and
   avatar without mixing those concerns into one endpoint.
- **Spec decoupling:** class/race work remains separate from identity edits.

### Negative
- **Handle changes cost credits:** identity edits are not free.
- **Avatar upload is local-path based:** changing storage later will require a
   separate storage decision.
- **No class/race editability yet:** future gameplay work is still pending.

## Alternatives Considered

1. **Make username and email immutable**
   - Rejected: the rewrite already allows handle and email edits in settings.
2. **Switch avatar uploads to external URLs**
   - Rejected: the rewrite stores local files today.
3. **Allow class/race changes now**
   - Rejected: the gameplay specs are still pending.
4. **Combine password, stasis, and identity changes into one endpoint**
   - Rejected: separate flows are easier to validate and maintain.

## Rollout Plan

1. Keep the current settings flow aligned with the Svelte page and
   `SettingsService` implementation.
2. Split future class/race editability into a separate decision after the
   gameplay specs are accepted.
3. Revisit avatar storage abstraction only if the upload strategy changes.

## Validation Plan

### Unit Tests (SettingsService)
- `test_handle_change_uniqueness` — reject duplicate usernames.
- `test_email_change_uniqueness` — reject duplicate emails.
- `test_password_rotation_requires_current_cipher` — reject wrong current password.
- `test_avatar_upload_constraints` — enforce file type, dimensions, and size.
- `test_stasis_toggle_flips_state` — engage and interrupt stasis correctly.

### Integration Tests (SettingsController)
- `test_identity_update_syncs_username_and_email` — POST `/settings/identity`
   persists both fields and updates the in-memory user model.
- `test_cipher_update_rejects_bad_current_password` — POST `/settings/cipher`
   returns a generic failure.
- `test_avatar_upload_returns_public_path` — POST `/settings/avatar` stores a
   local file and returns the public path.
- `test_stasis_toggle_changes_state` — POST `/settings/stasis` toggles the
   stasis flag and message.

### Compliance Audit
- SettingsController calls only SettingsService for persistence work.
- CSRF token validation still applies to POST settings routes.
- No raw exception output to end user.

### Success Metrics
- All settings tests passing for identity, cipher, stasis, and avatar flows.
- Compliance audit passing for controller/service separation.
- Zero security findings on CSRF, SQL injection, and file upload handling in
   code review.

## Related Documents

- [User Entity Specification](../../src/Models/User.php)
- [Settings Service Implementation](../../src/Services/SettingsService.php)
- [Settings Controller](../../src/Controllers/SettingsController.php)
