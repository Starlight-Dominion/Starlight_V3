# Architecture Overview

Starlight Dominion uses a service-centered architecture with Eloquent models, DI wiring, and focused HTTP controllers.

## Layers

- Controllers: request entrypoints and response shaping.
- Services: game/business logic and orchestration.
- Models: persistence entities and relationship mapping.
- Repositories: selective data access abstractions for specific domains.
- Infrastructure: framework and runtime adapters.

## Request Flow

1. Route maps to controller action.
2. Controller delegates to one or more services.
3. Services use models/repositories for persistence.
4. Controller returns rendered page state or JSON.

## Data and Runtime

- MariaDB stores game state and logs.
- Redis supports session and worker integrations.
- Tick and worker scripts in `bin/` run asynchronous game tasks.

## Maintainability Rules

- Keep service methods cohesive and deterministic.
- Prefer shared helpers over duplicated query joins.
- Use explicit relation return types in models.
- Add indexes for read/write hot paths before scale pain appears.
