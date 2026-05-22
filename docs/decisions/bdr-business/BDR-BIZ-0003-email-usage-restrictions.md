# BDR-BIZ-0003: Restrict Email Usage to Critical Notifications

- Status: accepted
- Date: 2026-04-25
- Owner: Business team
- Approvers: Product lead, CTO
- Supersedes: none
- Superseded by: none
- Scope: business
- Imported from: old starlight V3 corpus

## Context

Starlight Dominion operates as a free-to-play strategy game with ongoing operational costs. Email services, while valuable for player retention and engagement, represent a significant recurring expense when scaled to a large player base. Third-party email providers charge per message or per contact, and comprehensive notification systems (e.g., battle notifications, research completion, diplomacy updates) can quickly accumulate thousands of messages per day.

We must balance player experience with operational sustainability. Many players already benefit from in-game notifications and browser push notifications. Email is most valuable for time-sensitive account security and high-impact strategic alerts that players might otherwise miss while offline.

## Decision

Restrict outbound email to three critical use cases only:

1. **Account Security**: Password reset tokens and account recovery flows
2. **Multi-Factor Authentication**: One-time passwords (TOTP) and WebAuthn challenges
3. **Strategic Summaries**: Daily digests of completed battle reports (aggregate summary only, not individual notifications)

All other notification types (research completion, diplomacy events, espionage results, resource updates, etc.) will be delivered exclusively through in-game notifications and browser push notifications.

## Consequences

**Positive:**
- Significantly reduced email delivery costs (estimated 70–80% reduction in message volume)
- Operational simplicity and fewer third-party service dependencies
- Cleaner player email inboxes; reduced spam perception
- Easier compliance and reputation management (lower bounce/unsubscribe rates)

**Negative:**
- Players relying on email for strategic coordination may miss real-time updates
- Lower email-based re-engagement for lapsed players
- Reduced opportunity for player retention through email marketing campaigns
- Daily battle report digest may be less timely than individual notifications

**Mitigation:**
- Players can enable in-game notifications and browser push notifications for critical events
- Daily digest summary still allows tactical review of overnight activity
- Future phases may expand email capabilities if revenue/funding permits

## Alternatives Considered

- **Full email suite**: Send notifications for all events (research, espionage, diplomacy, attacks, etc.). *Rejected*: unsustainable cost at scale; high risk of player email fatigue.
- **No email**: Rely entirely on in-game and browser push notifications. *Rejected*: password reset and MFA emails are security essentials for modern accounts; players expect these channels.
- **Opt-in email tiers**: Premium subscribers receive full email suite; free players get only critical emails. *Deferred*: may be revisited post-MVP if monetization model supports it.

## Rollout Plan

1. **Immediate** (Week 1): Implement email restrictions in code; disable non-critical email senders.
2. **Communication** (Week 1–2): Patch notes and in-game banner explaining email scope change and encouraging browser push notifications.
3. **Player support** (Week 2+): FAQ and support articles on how to enable in-game and push notifications as alternative alerting.
4. **Monitoring** (Ongoing): Track email volume, player feedback, and support tickets related to missed alerts.

## Validation Plan

**Success Metrics:**
- Email delivery cost reduced by at least 70% within 30 days
- No increase in account recovery support tickets or password reset failures
- Daily battle report digest adoption rate ≥ 40% of active players
- Browser push notification enablement increases by ≥ 20% post-announcement

**Review Checkpoint:**
- Review at 60 days post-launch to assess player sentiment and retention metrics
- Identify any unintended gaps in critical notifications

**Reversal Criteria:**
- If player churn increases by >5% due to missed alerts, expand email to include key tactical notifications
- If operational costs remain high despite restrictions, escalate to executive team for budget review
