# Architecture Overview

Starlight Dominion is a high-performance, strategic RPG built on a robust Model-View-Controller (MVC) foundation. The system is designed for high-volume scalability and maintainability through a clear separation of concerns.

## 🏗️ System Layers

### 1. Presentation (View)
- **Frontend:** Svelte 5 utilizing Runes (`$state`, `$derived`, `$props`) for granular reactivity.
- **State:** Centralized `gameStore.svelte.js` for global dominion telemetry and resource tracking.
- **Communication:** Lean Fetch patterns to the PHP backend via JSON-REST or Page Initial State.

### 2. Controllers (Controller)
- **Entry Points:** Focused controllers in `src/Controllers/` that handle HTTP request validation and response shaping.
- **MVC Role:** Dispatches commands to the Service layer and returns either rendered PHP views or JSON payloads.

### 3. Services (Logic & Orchestration)
- **Domain Logic:** The core business logic resides in `src/Services/`.
- **Administrative Services:** Dedicated `AdminPlayerService`, `AdminGameDataService`, and `AdminSystemService` handle high-privileged operations with auditing.
- **Orchestration:** Services coordinate between multiple Repositories and models to fulfill complex game mechanics.

### 4. Data Access (Repositories & Models)
- **Repositories:** Abstraction layer in `src/Repositories/` for domain-specific data access (e.g., `UserRepository`, `DominionRepository`).
- **Models:** Eloquent entities in `src/Models/` for active-record style persistence and relationship mapping.
- **DTOs:** Data Transfer Objects in `src/Dto/` for type-safe data movement across the service layer.

### 5. Infrastructure
- **Framework Adapters:** Redis session handling, CSRF protection, and dependency injection (PHP-DI) configuration.
- **Transaction Management:** Centralized `TransactionManager` ensures atomic operations during complex state changes (e.g., combat or resource processing).

## ⚡ Data and Runtime

- **Persistence:** MariaDB 11.4 stores the primary game state and audit logs.
- **Caching & Streaming:** Redis supports session management and the `discord-action-worker` stream.
- **Concurrency:** Tick processing is split into `tick-dispatcher.php` (queueing) and `tick-processor.php` (execution) for parallel scalability.

## 📜 Maintainability Rules

- **MVC Integrity:** Never bypass the Controller-Service-Repository chain.
- **Surgical Logic:** Keep service methods cohesive, deterministic, and free of side effects where possible.
- **SQL Optimization:** Index filter and sort patterns together. Always verify query plans for read/write hot paths.
- **Failure Loudly:** Use custom exceptions in `src/Exceptions/` for validation and domain-level failures.

