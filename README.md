# Starlight Dominion

Starlight Dominion is a strategic military RPG built with a high-performance PHP backend and a reactive Svelte 5 frontend. Manage your kingdom, train specialized divisions, equip your army through a dynamic armory, and engage in tactical warfare with other sovereigns.

## Tech Stack

- **Backend:** PHP 8.4+ (FastRoute, PHP-DI, Eloquent ORM, Predis)
- **Frontend:** Svelte 5 (Runes), Tailwind CSS, Vite
- **Database:** MariaDB 11.4 (Managed via Phinx)
- **Cache/Session:** Redis
- **Containerization:** Docker & Docker Compose

## Quick Start: Fresh Instance Setup

Follow these steps to get a fresh instance of Starlight Dominion running locally.

### 1. Prerequisites
- Docker & Docker Compose
- Composer
- Node.js & npm

### 2. Installation

```bash
# Clone the repository
git clone <repository-url>
cd sdo

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Setup environment variables
cp .env.example .env