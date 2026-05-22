# Decision Index

This file is the canonical index of all ADR and BDR records.

Legacy records from the old starlight V3 corpus are being normalized into this repository.
Where a decision no longer matches the rewrite, the current record is updated to the
rewrite-era architecture and should be treated as the active decision for this codebase.

## ADR

- ADR-0001: Adopt strict decision governance for v3 (`accepted`)
- ADR-0002: Adopt PHP-DI as dependency injection container (`accepted`)
- ADR-0003: Adopt Phinx for database migrations and schema versioning (`accepted`)
- ADR-0004: Pin external dependencies to immutable revisions (`accepted`)
- ADR-0005: Serve the frontend with React and Vite (`accepted`)
- ADR-0006: Authentication and session management (`accepted`)
- ADR-0007: Frontend routing with react-router-dom (`accepted`)
- ADR-0008: First-party multi-factor authentication (WebAuthn + TOTP) (`accepted`)
- ADR-0009: Persistence baseline for playable milestone (`accepted`)
- ADR-0010: Backend HTTP routing and error envelope (`accepted`)
- ADR-0011: Profile identity field editability and validation rules (`accepted`)
- ADR-0012: Upload storage policy and adapter strategy (`accepted`)
- ADR-0013: Adopt AWS SES as transactional email provider (`accepted`)
- ADR-0014: Environment-configured administrator bootstrap and fallback (`accepted`)
- ADR-0015: Client-side shell transport and heartbeat refresh (`accepted`)
- ADR-0016: Open API authentication and key lifecycle (`accepted`)
- ADR-0017: Open API rate limiting strategy (`accepted`)

## BDR-BIZ

- BDR-BIZ-0001: Define v3 mission and player value priorities (`accepted`)
- BDR-BIZ-0002: Adopt MVP-first live release with staged mechanics expansion (`accepted`)
- BDR-BIZ-0003: Restrict email usage to critical notifications (`accepted`)
- BDR-BIZ-0004: Define Open API access program and approval policy (`accepted`)
- BDR-BIZ-0005: Define official rules content governance and source of truth (`accepted`)

## BDR-BAL

- BDR-BAL-0001: Balance decisions must be data-informed and anti-runaway (`accepted`)
- BDR-BAL-0002: Adopt unit-commitment combat as the primary attack model (`accepted`)
- BDR-BAL-0003: Define MVP economy drivers around production, replacement, and plunder pressure (`accepted`)
- BDR-BAL-0004: Define casualty, population, and recovery model for MVP warfare (`accepted`)
- BDR-BAL-0005: Define MVP combat resolution formulas and randomness bounds (`accepted`)
- BDR-BAL-0006: Define anti-farming safeguards and anti-total-loss protections (`accepted`)
- BDR-BAL-0007: Define MVP espionage baseline around intelligence and sabotage (`accepted`)
- BDR-BAL-0008: Define bank liquidity controls and daily deposit limits (`accepted`)
- BDR-BAL-0009: Define identity edit cost and rebranding pricing (`accepted`)
- BDR-BAL-0010: Define sector pulse cadence and resource processing window (`accepted`)
- BDR-BAL-0011: Define Open API battlefield intel exposure policy (`accepted`)

## Rules

- Add new records in sequence.
- Never reuse IDs.
- Keep status up to date.
- Do not delete historical records.
