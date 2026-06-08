# Starlight Dominion

Starlight Dominion is a high-performance, strategic military RPG built for deep tactical gameplay. It adheres to a strict **Model-View-Controller (MVC)** architecture, designed for maximum maintainability and high-volume scalability.

## 🚀 Core Mechanics

### ⚔️ Military Power Math
The system operates on a strict **1:1 Unit-to-Item** military power ratio. For a division to reach its maximum tactical efficiency, every soldier must be equipped with the appropriate gear (e.g., 100 Rangers require 100 Compound Bows).

### ⏳ Server Heartbeat (The Tick)
The world of Starlight Dominion evolves every hour on the hour, synchronized to **EST (New York)**. The "Tick" handles:
- Resource generation and consumption.
- Completion of structural upgrades.
- Military training cycles.
- Economic interest and upkeep.

### 🏛️ Kingdom Management
- **The Armory:** Dynamic procurement system for equipping your military divisions.
- **The Bank:** Secure storage for your kingdom's treasury, generating interest and facilitating transfers.
- **Training Grounds:** Convert citizens into specialized military units.
- **Battlefield:** Real-time tactical engagements and intelligence gathering.

---

## 🛠️ Tech Stack

### Backend: PHP 8.4+
- **Architecture:** Strict MVC utilizing a Service-Repository pattern for clear separation of concerns and deterministic business logic.
- **Scalability:** Stateless backend designed for horizontal expansion, utilizing Redis for session management and distributed caching.
- **Dependency Injection:** PHP-DI for decoupled service management.
- **ORM:** Eloquent for fluid, type-safe database interactions.
- **Routing:** FastRoute for high-performance request handling.

### Frontend: Svelte 5 (Runes)
- **Reactivity:** Full migration to Svelte 5 Runes (`$state`, `$derived`, `$props`) for granular reactivity.
- **State Management:** Centralized `gameStore.svelte.js` managing global resource and dominion state.
- **Styling:** Tailwind CSS for a modern, responsive UI.
- **Build System:** Vite for fast HMR and optimized production bundles.

### Infrastructure
- **Database:** MariaDB 11.4 (Managed via Phinx migrations).
- **Caching:** Redis for high-speed data access.
- **Containerization:** Docker & Docker Compose for a consistent dev/prod environment.

---

## 📂 Project Structure

```text
├── bin/                # CLI Utilities (Tick worker, Bot generator)
├── config/             # Application configuration (Routes, Units, Housing)
├── db/                 # Database migrations and seeders
├── public/             # Entry point and static assets
├── src/
│   ├── Controllers/    # HTTP Request handling (MVC: Controller)
│   ├── Dto/            # Data Transfer Objects
│   ├── Exceptions/     # Custom Exception classes
│   ├── Infrastructure/ # Core framework, DB setup, and Middleware
│   ├── Models/         # Eloquent database models (MVC: Model)
│   ├── Repositories/   # Data access abstraction
│   ├── Services/       # Core business logic (Armory, Bank, Combat)
│   ├── Resources/      # Svelte components, JS stores, and CSS
│   ├── ViewModels/     # Frontend-focused data transformers
│   └── Views/          # Main application layout and view files (MVC: View)
└── tests/              # PHPUnit tests and Architecture guards
```

---

## ⌨️ CLI Operations

### Setup & Migrations
```bash
# Run migrations
vendor/bin/phinx migrate

# Seed initial data
vendor/bin/phinx seed:run -s InitialDataSeeder
```

### Background Workers
```bash
# Process a game tick manually
php bin/tick-dispatcher.php # Queues the tick
php bin/tick-processor.php  # Processes the queued tick logic

# Process automated bot actions
php bin/bot-processor.php

# Process Discord link/unlink action requests from Redis streams
php bin/discord-action-worker.php

# Generate AI bot dominions
php bin/generate-bots.php
```

### Testing
```bash
# Run all unit tests
vendor/bin/phpunit

# Run MVC integrity checks
bash tests/Architecture/test_mvc_integrity.sh

# Install Playwright browsers
npm run test:e2e:install

# Run E2E suite (requires DB, Redis, migrations, and seed data)
npm run test:e2e
```

### E2E Preparation
```bash
# Build frontend assets on the host
npm run build

# Run app and dependencies in containers
docker compose up -d app db redis

# Prepare schema + baseline data inside PHP 8.4 app container
docker compose exec -T app php vendor/bin/phinx migrate -e development
docker compose exec -T app php vendor/bin/phinx seed:run -e development -s InitialDataSeeder
docker compose exec -T app php vendor/bin/phinx seed:run -e development -s ArmorySeeder

# Run Playwright against the already-running containerized app
PLAYWRIGHT_USE_EXTERNAL_SERVER=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:8080 npm run test:e2e
```

---

## 📚 Additional Documentation
For deeper dives into the system's design and features, refer to:
- [Architecture Overview](ARCHITECTURE.md) - Deep dive into layers and request flow.
- [OpenAPI Specification](OPEN_API.md) - Detailed documentation of the game's API endpoints.
- [Admin Suite Guide](admin_suite_readme.md) - Documentation for the administrative management tools.
- [Admin Suite E2E Testing](ADMIN_TESTING.md) - Exhaustive technical documentation of the administrative verification matrix.

---

## 📜 Development Protocol
Contributors must adhere to the standards defined in `gemini.md`:
- **Constructor Property Promotion** for all PHP classes.
- **Strict Types** in all PHP files.
- **Svelte 5 Runes** for all reactive frontend logic.
- **Surgical Updates:** Minimize context usage and maintain high-signal communication.

---

## 🏁 Quick Start

1. `cp .env.example .env`
2. `docker-compose up -d`
3. `composer install && npm install`
4. `npm run dev` (Vite)
5. `vendor/bin/phinx migrate && vendor/bin/phinx seed:run`
