# ADR-0008: First-party Multi-factor Authentication (WebAuthn + TOTP)

- Status: accepted
- Date: 2026-04-20
- Owner: @Rihoj
- Approvers: @mungus451
- Supersedes: none
- Superseded by: none
- Scope: architecture, security
- Imported from: old starlight V3 corpus

## Context

ADR-0006 defines password-based authentication, session management, CSRF, and
login rate-limiting. It does not cover multi-factor authentication. The
playable milestone (`docs/milestones/playable-milestone.md`) is about to ship
password login, logout, password reset, and in-profile password change. The
product ask is to make MFA a **first-party** feature rather than a later
bolt-on, and to support three factor types:

1. **Security keys** — roaming FIDO2 authenticators (e.g. YubiKey) that
   present as non-discoverable credentials over USB/NFC.
2. **Passkeys** — platform and syncing WebAuthn credentials (iCloud Keychain,
   Google Password Manager, 1Password, Windows Hello) that are discoverable
   and usable for passwordless sign-in.
3. **TOTP apps** — RFC 6238 authenticator apps (Google Authenticator, Authy,
   1Password, Aegis) keyed by an RFC 4226 shared secret.

The security baseline (`docs/security/security-baseline.md`) already mandates
account-takeover protection; MFA is the next lever. The
`docs/milestones/playable-milestone.md` build order treats auth hardening as
pre-launch work, so this record must land (or be superseded) before public
beta.

Constraints:

- PHP 8.4, no framework beyond FastRoute + PHP-DI (ADR-0002). New runtime
  dependencies must comply with ADR-0004 (pinned).
- No native PHP implementation of WebAuthn attestation verification exists;
  rolling our own is unsafe. A maintained, pinned library is required.
- MVC-S boundaries must be preserved (ADR-0006): Controllers stay
  SQL-free; Services own orchestration and policy; Repositories own
  persistence; Entities stay immutable DTOs.
- Sessions are DB-backed (ADR-0006); MFA state must be representable in
  the session payload without leaking secrets into cookies.
- The frontend is a React SPA served by Vite (ADR-0005) and routed with
  react-router-dom (ADR-0007); MFA UX must live inside the existing
  `/login`, `/app/profile`, and (new) `/app/settings/security` flows.

Prior art:
- v1 (`../Stellar-Dominion-Game`) and v2 (`../starlight_v2`) have no MFA
  implementation to port.
- The upcoming password-reset flow already invalidates all sessions for a
  user; MFA lifecycle events will reuse that session-invalidation primitive
  via `SessionRepository::destroyByUserId(Except)`.

## Decision

Ship MFA as a first-party capability with **WebAuthn (FIDO2)** as the
preferred factor and **TOTP** as the universal fallback, backed by
single-use recovery codes. Passkeys and security keys are the same WebAuthn
factor type at the protocol level and share one storage schema; they are
distinguished to the user by credential metadata, not by a separate code
path.

### Factor model

| Factor | Protocol | Storage | Primary use |
|---|---|---|---|
| Security key (cross-platform) | WebAuthn L3, `authenticatorAttachment=cross-platform` | `webauthn_credentials` | Second factor, hardware-bound |
| Passkey (platform / synced) | WebAuthn L3, `authenticatorAttachment=platform` or synced, `residentKey=required` | `webauthn_credentials` with `discoverable=1` | Second factor; Phase 2: passwordless |
| TOTP | RFC 6238, 30s step, SHA-1, 6 digits, ±1 step drift | `totp_secrets` | Second factor, universal fallback |
| Recovery codes | 10 × 10-char base32, single use | `mfa_recovery_codes` | Account recovery when factors are lost |

Phase 1 scope (this ADR, ships with the playable milestone):
- WebAuthn as **second factor** (after password).
- TOTP as second factor.
- Recovery codes.
- Self-service enrolment, listing, rename, and removal in
  `/app/settings/security`.

Phase 2 (future ADR, tracked as an open question): passwordless sign-in using
discoverable WebAuthn credentials (conditional UI, `isConditionalMediationAvailable`).

### Dependencies

- **`lbuchs/webauthn`** (pinned per ADR-0004) for WebAuthn ceremony
  construction, attestation verification, and assertion validation. Chosen
  over `web-auth/webauthn-lib` because it is pure PHP with zero Symfony /
  PSR runtime dependencies, which keeps the v3 dependency surface small and
  auditable. Trade-off: no built-in FIDO MDS client; the MDS metadata cadence
  becomes an operational doc item (see Open Questions) rather than a library
  feature. If FIDO MDS enforcement is later required, this choice is
  revisitable behind the `WebAuthnServerFactory` boundary without touching
  any repository or controller.
- **`pragmarx/google2fa`** (pinned per ADR-0004) for TOTP secret generation
  and verification. Chosen for zero runtime deps, strict RFC 6238 behaviour,
  and wide deployment footprint.
- **`paragonie/constant_time_encoding`** (pulled in by google2fa) for base32
  handling of TOTP secrets and recovery codes.
- No new infrastructure (no Redis, no external MFA SaaS).

### Data model (Phinx migrations)

New tables:

```
webauthn_credentials
  id                 BIGINT UNSIGNED PK
  user_id            BIGINT UNSIGNED NOT NULL  FK users.id ON DELETE CASCADE
  credential_id      VARBINARY(255) NOT NULL   UNIQUE
  public_key         BLOB NOT NULL             -- COSE-encoded
  attestation_format VARCHAR(32) NOT NULL
  aaguid             BINARY(16) NULL
  sign_count         BIGINT UNSIGNED NOT NULL DEFAULT 0
  transports         JSON NULL
  discoverable       TINYINT(1) NOT NULL DEFAULT 0
  backup_eligible    TINYINT(1) NOT NULL DEFAULT 0
  backup_state       TINYINT(1) NOT NULL DEFAULT 0
  nickname           VARCHAR(80) NOT NULL
  last_used_at       DATETIME NULL
  created_at         DATETIME NOT NULL
  INDEX (user_id)

totp_secrets
  id                 BIGINT UNSIGNED PK
  user_id            BIGINT UNSIGNED NOT NULL  FK users.id ON DELETE CASCADE  UNIQUE
  secret_cipher      VARBINARY(512) NOT NULL   -- encrypted at rest, see Crypto
  secret_nonce       VARBINARY(24) NOT NULL
  confirmed_at       DATETIME NULL             -- null until first successful code
  last_used_step     BIGINT NOT NULL DEFAULT 0 -- replay protection
  created_at         DATETIME NOT NULL

mfa_recovery_codes
  id                 BIGINT UNSIGNED PK
  user_id            BIGINT UNSIGNED NOT NULL  FK users.id ON DELETE CASCADE
  code_hash          CHAR(64) NOT NULL UNIQUE   -- SHA-256 of raw code
  used_at            DATETIME NULL
  created_at         DATETIME NOT NULL
  INDEX (user_id, used_at)

mfa_challenges
  id                 BIGINT UNSIGNED PK
  user_id            BIGINT UNSIGNED NULL       -- null for pre-auth attestation
  challenge          VARBINARY(64) NOT NULL
  purpose            ENUM('register_webauthn','assert_webauthn','enroll_totp') NOT NULL
  expires_at         DATETIME NOT NULL
  consumed_at        DATETIME NULL
  INDEX (user_id, purpose)
```

`users` gains:
- `mfa_required TINYINT(1) NOT NULL DEFAULT 0` — true once any factor is
  confirmed. Used as the "MFA is on" flag; removing the last factor sets it
  back to 0 only if recovery codes are also cleared.
- `mfa_prompt_deferred_until DATETIME NULL` — registration-time and
  post-login nudge cadence only. Never gates authentication. Set when the
  user explicitly skips the registration-time MFA offer; cleared when any
  factor is confirmed.

### Crypto

- TOTP secrets are encrypted with **libsodium** `crypto_secretbox` using a
  32-byte key loaded from `config/auth.php` via `Config` (env var
  `MFA_SECRET_KEY`, 32 bytes base64). Nonce is 24 random bytes per row.
- Recovery codes are stored as SHA-256 hashes (not encrypted — they are
  single-use and short-lived; the hash preserves verification without
  requiring key access).
- WebAuthn public keys are stored as-is (they are already public).
- Key rotation: `config/auth.php` supports `mfa.secret_keys` as an ordered
  list; the first entry encrypts, all entries decrypt. Rotation procedure
  documented in operations baseline.

### Flow — enrolment (Phase 1)

1. `POST /api/auth/mfa/webauthn/register/options` (authenticated, CSRF).
   Server records a challenge in `mfa_challenges`, returns
   `PublicKeyCredentialCreationOptions`.
2. `POST /api/auth/mfa/webauthn/register/verify` with the client response
   and a user-supplied nickname. Service validates attestation, stores the
   credential, marks `users.mfa_required = 1`, and — if this is the first
   factor — issues 10 recovery codes and returns them exactly once.
3. TOTP symmetric flow: `.../totp/enroll` returns the secret + QR URI;
   `.../totp/confirm` verifies the first code and activates the secret.
4. `GET /api/auth/mfa/factors` returns the user's factor inventory (no
   secrets).
5. `DELETE /api/auth/mfa/factors/{id}` removes a factor after re-auth with
   another factor (or password + recovery code if it's the last one).
6. Recovery codes can be regenerated; regeneration invalidates the old set.

### Flow — registration-time MFA offer

The registration wizard is restructured from three steps (Account / Class /
Race) to three steps (Account / Identity / Secure):

1. **Step 1 — Account.** Email, callsign, password, confirm. Unchanged from
   the current wizard.
2. **Step 2 — Identity.** Class **and** race are chosen on a single screen.
   Both selections must be made to advance. The step-2 page renders the
   existing `choice-grid` twice (one per axis) in side-by-side columns on
   wide viewports and stacked on narrow viewports. Rationale: class and
   race are each a one-from-N pick with no inter-dependency, no server
   round-trip between them, and no gameplay reason to separate them at
   signup. Collapsing them reclaims the step budget for MFA without adding
   wizard length. The underlying `POST /api/auth/register` payload is
   unchanged (still accepts `class_id` and `race_id`).
3. **Step 3 — Secure (optional, skippable).** After a successful
   `register` call the user is already authenticated; the wizard routes to
   a new `/register/secure` screen offering:
   - "Add a passkey / security key" — invokes the WebAuthn enrolment flow
     defined above. On success, reveals recovery codes with a "I saved
     these" checkbox required to continue.
   - "Add a TOTP app" — invokes the TOTP enrolment flow, with QR + manual
     secret, code confirmation, then the same recovery-code reveal.
   - "Skip for now" — explicit button, not just closing the tab. Stamps
     `users.mfa_prompt_deferred_until = NOW() + INTERVAL 7 DAY` and routes
     to `/app`.

   Skipping is never destructive and never blocks play. Users who skip see
   a dismissible banner in `AppShell` ("Secure your account with MFA →")
   and a persistent amber dot on the Profile/Security nav entry until at
   least one factor is enrolled. After `mfa_prompt_deferred_until` has
   passed, the next successful login re-renders the offer once (still
   skippable). Staff / admin accounts remain subject to the separate
   mandatory-MFA decision (see Open Questions).

   This reuses the enrolment endpoints above without modification — the
   registration-time flow is only a UX entry point into the same service.

`users` therefore also gains:
- `mfa_prompt_deferred_until DATETIME NULL` — nudge cadence only; never
  used to allow or block auth. Cleared when any factor is confirmed.

### Flow — login (Phase 1, second-factor)

1. `POST /api/auth/login` behaves as today but, when `user.mfa_required = 1`,
   returns `success=true, data.mfa_required=true, data.challenge_id=<opaque>`
   **without** calling `SessionService::login`. A short-lived "pending MFA"
   session row is created (same `sessions` table, `user_id=NULL`, payload
   carrying the pending user id, TTL 10 minutes).
2. Client chooses a factor. Offered options:
   - `POST /api/auth/mfa/webauthn/assert/options` → server returns an
     assertion challenge scoped to the user's credentials (or a resident-key
     challenge in Phase 2).
   - `POST /api/auth/mfa/webauthn/assert/verify` → verifies, consumes the
     pending session, regenerates session id, and promotes to a full
     authenticated session.
   - `POST /api/auth/mfa/totp/verify` → `{ challenge_id, code }`. Validates
     the code, enforces `last_used_step` to prevent replay.
   - `POST /api/auth/mfa/recovery/verify` → consumes one recovery code and
     nudges the user to enrol a new factor on next page view.
3. Rate limiting: at most 5 failed MFA attempts per pending-session id;
   thresholds in `config/auth.php` (`rate_limit.mfa_max_failures`,
   `rate_limit.mfa_window_seconds`). Exceeding the limit destroys the
   pending session and requires restarting from password login.

### Flow — password reset and change

- Password reset (`AuthService::resetPassword`) **does not** disable MFA; a
  reset token proves control of the email account but not of the second
  factor. The new session prompts for MFA as usual.
- Password change (`AuthService::changePassword`) similarly does not affect
  MFA state.
- Account recovery "I lost my second factor": handled entirely through
  recovery codes in Phase 1. Operator-assisted recovery is an open question.

### MVC-S placement

- `app/Services/MfaService.php` — orchestration, validation, policy,
  transactions. Delegates protocol work to the WebAuthn and TOTP libraries.
- `app/Core/WebAuthnServerFactory.php` — thin factory over
  `web-auth/webauthn-lib`, loads RP id/origin from `config/auth.php`.
- `app/Controllers/MfaController.php` — HTTP boundary only; CSRF validation,
  JSON in/out, no SQL.
- Repositories: `WebAuthnCredentialRepository`, `TotpSecretRepository`,
  `MfaRecoveryCodeRepository`, `MfaChallengeRepository` (each with a
  `Pdo*` implementation).
- Entities: `WebAuthnCredential`, `TotpSecret`, `MfaRecoveryCode`,
  `MfaChallenge` — all immutable DTOs with `public readonly` properties.
- `AuthService` gains a `requiresMfa(User)` helper and emits the
  `mfa_required` branch on `login`; the existing `sessionService->login` is
  only called after MFA is satisfied.

### Frontend (ADR-0007 routing)

- `/register` — three-step wizard (Account / Identity / Secure). Step 2
  (Identity) renders class and race choice grids on one screen. Step 3
  (Secure) is the registration-time MFA offer described above; it lives at
  `/register/secure` so a refresh mid-enrolment returns to the same place
  until the user completes or explicitly skips.
- `/login` — on `mfa_required`, renders an `MfaChallengePage` with tabs for
  each enrolled factor; defaults to the factor used most recently.
- `/app/settings/security` — new route containing:
  - WebAuthn factor list (nickname, created, last used, remove).
  - "Add security key" and "Add passkey" buttons (both call the same
    WebAuthn registration flow; `authenticatorAttachment` is set from the
    button the user pressed).
  - TOTP enrol / disable.
  - Recovery code reveal + regenerate.
- `AppShell` renders a dismissible "Secure your account with MFA" banner
  and an amber dot on the Profile/Security nav entry for any account with
  zero confirmed factors. Both clear as soon as a factor is confirmed.
- The existing `/app/profile` links to `/app/settings/security` under a
  "Security" section. Profile continues to own change-password only.

### Compliance tests

- `StrictArchitectureAudit` already enforces controller-has-no-SQL and
  service-has-no-HTML; new classes conform.
- New compliance assertion: `MfaService` must not echo secrets (TOTP secret,
  recovery codes) outside the single enrolment-response pathway. A regex
  audit in `tests/Compliance/run_compliance_suite.php` checks that
  `secret_cipher`, `code_hash`, and raw `secret` never appear in
  `MfaController` responses except through whitelisted endpoints.

## Consequences

Positive:

- Phishing-resistant primary factor (WebAuthn) from day one.
- Single WebAuthn storage and code path serves both "security key" and
  "passkey" product labels, so we do not fork two implementations.
- TOTP fallback keeps low-end users covered without SMS or email OTP.
- Recovery codes give users self-service lockout recovery without opening a
  support channel on launch day.
- Session-invalidation primitive built for password reset/change is reused
  for MFA lifecycle events; no new session machinery.
- Future passwordless (passkey-only) sign-in is unlocked by Phase 2 without
  a schema change.

Negative:

- Two pinned runtime dependencies and one new env-sourced encryption key
  expand the secrets-management surface.
- WebAuthn correctness depends on RP id and origin configuration per
  deployment; misconfiguration silently breaks registration. Mitigated by
  boot-time assertion in `WebAuthnServerFactory` and a documented runbook.
- Login UX adds a second step for ~all users once MFA is enrolled;
  unenrolled users are unchanged.
- TOTP secrets are high-value; encryption-at-rest with libsodium adds a
  key-rotation burden.
- Phase 1 omits passwordless sign-in; we must clearly label passkeys as
  "second factor only" until Phase 2 lands.

## Alternatives Considered

- **SMS / email OTP.** Rejected. SMS is phishable and SIM-swappable; email
  OTP collapses to single-factor if email is compromised.
- **Third-party MFA (Auth0, Authsignal, Clerk).** Rejected. Violates the
  "first-party" product directive, adds vendor runtime dependency and data
  egress, and prices per MAU.
- **Push-based MFA (Duo, custom app).** Rejected for MVP. Requires a mobile
  app surface we do not have.
- **WebAuthn only, no TOTP.** Rejected. Cuts off users without a WebAuthn
  authenticator (older Android, desktops without platform authenticator,
  hardened/work devices that disable WebAuthn).
- **Roll our own WebAuthn stack.** Rejected. Attestation verification,
  CBOR/COSE parsing, and FIDO MDS integration are easy to get subtly wrong.
- **Per-device trust ("remember this browser for 30 days").** Deferred to a
  later BDR-BIZ; complicates threat model and is not required for MVP.
- **Mandatory MFA at registration.** Rejected for Phase 1. Hard-requiring a
  second factor during signup measurably depresses conversion, excludes
  users without a TOTP app or WebAuthn-capable browser, and is redundant
  once passwordless (Phase 2) lands. The chosen "offer + skip + 7-day
  re-nudge" model captures most of the adoption lift of inline enrolment
  without the abandonment cost. A future BDR-BIZ may mandate MFA for staff
  / privileged accounts (see Open Questions).
- **Four-step registration wizard (Account / Class / Race / Secure).**
  Rejected. Class and race have no inter-dependency and no server round
  trip between them, so a single Identity step is sufficient and keeps the
  wizard at three steps after adding the Secure step.

## Rollout Plan

1. Land this ADR in `accepted` status.
2. Pin `web-auth/webauthn-lib` and `pragmarx/google2fa` in `composer.json`
   (per ADR-0004) and update `composer.lock`.
3. Add config keys to `config/auth.php`:
   `webauthn.rp_id`, `webauthn.rp_name`, `webauthn.origins`,
   `mfa.secret_keys`, `mfa.totp.issuer`, `mfa.recovery_code_count`,
   `mfa.challenge_ttl_seconds`, rate-limit keys.
4. Phinx migrations for the four new tables and the `users.mfa_required`
   and `users.mfa_prompt_deferred_until` columns.
5. Entities, repositories, service, and controller, in that order; each
   gets a unit test file before wiring.
6. Wire routes in `public/index.php`; register repositories and services in
   `ContainerFactory`.
7. Integration test in `tests/Integration/MfaFlowTest.php` covering
   TOTP enrolment, login-with-TOTP, recovery-code use, and factor removal.
   WebAuthn is covered at the service layer via fixture assertion/attestation
   bundles (the `web-auth/webauthn-lib` test kit).
8. Frontend:
   - Restructure `RegisterPage` wizard to Account / Identity / Secure,
     merging the class and race selection into a single Identity step.
   - Add the `/register/secure` step that offers WebAuthn and TOTP
     enrolment with an explicit "Skip for now" action.
   - Add `/app/settings/security` route and the MFA challenge step on
     `/login`. Update `RequireAuth` to honour the "pending MFA" session
     state and redirect to the challenge page.
   - Render the "Secure your account with MFA" banner and nav indicator
     in `AppShell` while the account has zero confirmed factors.
9. Update `docs/security/security-baseline.md` `Related decisions` to cite
   ADR-0008, and update the milestone doc's build order and any mockups /
   screenshots that reference the old three-step (Account / Class / Race)
   wizard.
10. Open a follow-up ADR stub for Phase 2 (passwordless / conditional UI).

## Validation Plan

- Unit tests:
  - `MfaServiceTest` — enrolment happy path and failure modes per factor,
    replay rejection (TOTP `last_used_step`), recovery-code single-use,
    "last factor removal clears `mfa_required`" invariant, rate-limit
    trigger.
  - `WebAuthnServerFactoryTest` — RP id/origin validation at boot,
    misconfiguration produces a loud failure.
- Integration tests:
  - Full login → MFA challenge → session promotion.
  - Password reset does not bypass MFA.
  - Password change preserves MFA state and keeps the caller signed in.
  - Registration-time MFA offer:
    - Skip path stamps `mfa_prompt_deferred_until` and lets the user reach
      `/app` without enrolling.
    - Enrol path clears `mfa_prompt_deferred_until`, sets
      `mfa_required = 1`, and issues exactly 10 recovery codes once.
    - Refreshing mid-enrolment returns to `/register/secure`.
- Compliance:
  - `StrictArchitectureAudit` green; new MVC-S boundaries respected.
  - `verify_di_resolution.php` resolves new bindings.
  - New compliance assertion: MFA secrets never appear in non-enrolment
    responses.
- Manual:
  - Chrome + YubiKey (cross-platform security key) registration and assertion.
  - macOS Safari platform passkey registration and assertion.
  - Android Chrome platform passkey registration and assertion.
  - 1Password desktop passkey (synced) registration and assertion.
  - TOTP via Google Authenticator, Aegis, and 1Password.
- Security:
  - Verify WebAuthn challenge rows are consumed exactly once and expire.
  - Verify TOTP `last_used_step` prevents replay inside the ±1 step window.
  - Verify recovery codes are invalidated on use and on regeneration.
  - Verify encryption key rotation: re-encrypt with new first-key entry,
    confirm decryption via older keys still works for one release cycle.

## Open Questions

- **Passwordless sign-in.** Tracked as Phase 2. Requires discoverable
  credentials, conditional UI in the browser, and a separate session
  promotion path. New ADR before build.
- **Admin / operator-assisted recovery.** If a user loses every factor
  including recovery codes, what is the flow? Candidate: manual support
  ticket with identity proofing. Decide before public launch.
- **Enforced MFA for privileged accounts.** Developer/staff accounts likely
  require MFA-on by default. Defer to a BDR-BIZ alongside the admin model.
- **Remember-this-browser / trusted-device bypass.** Not in scope;
  product-level decision.
- **FIDO MDS metadata refresh cadence** (how often we pull updated
  authenticator metadata) — operational question; document in operations
  baseline once the service ships.
- **MFA-required account export / deletion flows.** Coordinate with the
  privacy and account-closure policy (not yet written).
