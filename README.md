# Starlight Dominion

Starlight Dominion is a high-performance, strategic military RPG built for deep tactical gameplay. It features a robust PHP 8.4 backend and a highly reactive Svelte 5 frontend, designed to handle complex economic and military simulations with precision.

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
- **Architecture:** Clean Service-Repository pattern with strict typing.
- **Dependency Injection:** PHP-DI for decoupled service management.
- **ORM:** Eloquent for fluid, type-safe database interactions.
- **Routing:** FastRoute for high-performance request handling.
- **Session:** Redis-backed session management for stateless scalability.

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
│   ├── Controllers/    # HTTP Request handling
│   ├── Models/         # Eloquent database models
│   ├── Repositories/   # Data access abstraction
│   ├── Services/       # Core business logic (Armory, Bank, Combat)
│   ├── Resources/      # Svelte components, JS stores, and CSS
│   └── ViewModels/     # Frontend-focused data transformers
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
php bin/tick-worker.php

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
```

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
