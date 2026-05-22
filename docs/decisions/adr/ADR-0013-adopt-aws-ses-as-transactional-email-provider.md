# ADR-0013: Adopt AWS SES as Transactional Email Provider

- Status: accepted
- Date: 2026-04-25
- Owner: Platform team
- Approvers: Product lead, CTO
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

BDR-BIZ-0003 proposes a cost-control policy that limits outbound email to three
critical use cases only:

- Password reset and account recovery
- Multi-factor authentication-related messages
- Daily battle report summaries

Given that narrow scope, the platform still needs one reliable transactional
email provider for authenticated sending, bounce/complaint handling,
deliverability controls, and operational monitoring. The provider choice should
keep implementation simple for MVP while supporting production-grade security
and reputation controls.

Starlight v3 already uses a backend API architecture and strict service
boundaries. Email delivery should therefore be encapsulated behind a service
port so application logic remains stable while provider integration details stay
isolated.

## Decision

Starlight v3 will standardize on AWS Simple Email Service (SES) as the only
transactional email provider for MVP and early live operations.

The architectural rules are:

- All transactional outbound email is sent through SES.
- Email use remains constrained by BDR-BIZ-0003 policy (security and daily
  digest categories only).
- Application services will depend on a provider-agnostic email gateway
  interface, while the initial concrete adapter is SES-backed.
- Provider-specific concerns (AWS SDK usage, template IDs, rate/backoff,
  suppression handling) are isolated to infrastructure adapters.
- Credentials, region, sender identity, and configuration set settings are
  environment-driven and not hard-coded.

This yields a single-provider operational model with clean architectural
boundaries.

## Consequences

Positive:

- Reduces delivery architecture complexity by operating one provider.
- Benefits from SES transactional feature set (domain verification, reputation,
  feedback loops, identity controls).
- Simplifies support, runbooks, and observability for email incidents.
- Aligns technical implementation with BDR-BIZ-0003 cost-reduction objective.

Negative:

- Introduces provider lock-in risk if SES pricing or policy changes.
- Requires AWS account governance and SES-specific operational knowledge.
- Regional outages or account-level restrictions can affect all outbound email.

## Alternatives Considered

- Multi-provider abstraction with active failover from day one.
  - Rejected for MVP: adds significant complexity and cost before clear need.

- Non-AWS transactional providers (for example Postmark, SendGrid, Mailgun).
  - Deferred: viable alternatives, but SES chosen for current platform fit and
    operational simplicity.

- Self-hosted SMTP relay.
  - Rejected: higher deliverability and abuse-management risk; larger
    maintenance burden for a small team.

## Rollout Plan

1. Create `EmailGateway` port in application layer with send primitives for
   password reset, MFA, and daily summary categories.
2. Implement `SesEmailGateway` adapter in infrastructure layer and wire through
   DI/container configuration.
3. Configure SES identities (domain/sender), DKIM/SPF/DMARC prerequisites, and
   environment variables per deployment target.
4. Route existing and new transactional email sends through the gateway,
   enforcing BDR-BIZ-0003 category restrictions.
5. Add bounce/complaint handling and suppression-list policy in operations
   workflow.
6. Document runbooks for throttling, sandbox/production transitions,
   deliverability incidents, and credential rotation.

## Validation Plan

- Unit tests for email service and gateway interface usage in application
  services (no direct SES SDK usage outside adapter).
- Integration tests for SES adapter request mapping and error handling.
- Compliance checks that non-approved notification categories do not trigger
  outbound email.
- Operational smoke tests in non-production for password reset, MFA, and daily
  summary sends.
- Success metrics over first 30 days:
  - Delivery success rate at or above 99% for approved transactional categories
  - Complaint and bounce rates within SES-recommended thresholds
  - No unauthorized email category emissions relative to BDR-BIZ-0003
