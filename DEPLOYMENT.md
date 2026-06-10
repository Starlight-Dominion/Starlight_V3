# Deployment Guide: Starlight Dominion

Starlight Dominion can be deployed either via a native Linux Apache2 environment or using Docker. Both methods are outlined below.

## Option 1: Docker Deployment (Recommended)

The easiest way to run the application is using Docker and Docker Compose. This automatically provisions PHP 8.4, Apache, Node.js, MariaDB, and Redis, as well as the background workers.

### Prerequisites
- Docker
- Docker Compose

### Steps

1. **Clone the repository:**
   ```bash
   git clone <repository_url> sdo
   cd sdo
   ```

2. **Configure Environment:**
   Copy the example environment file and adjust if necessary.
   ```bash
   cp .env.example .env
   ```

3. **Build and Start Containers:**
   ```bash
   docker-compose up -d --build
   ```

4. **Install Dependencies & Build Frontend:**
   Execute these commands inside the `app` container:
   ```bash
   docker exec -it sdo_app composer install
   docker exec -it sdo_app npm install
   docker exec -it sdo_app npm run build
   ```

5. **Run Database Migrations & Seeds:**
   Essential game data (races, units, structures) must be seeded for the application to function correctly.
   ```bash
   docker exec -it sdo_app php vendor/bin/phinx migrate -e development
   docker exec -it sdo_app php vendor/bin/phinx seed:run -e development
   ```

The application will now be accessible at `http://localhost:8080`.

---

## Option 2: Native Linux Apache2 Deployment

### Prerequisites
- **OS**: Ubuntu/Debian recommended
- **Web Server**: Apache2
- **PHP**: 8.4+ with extensions: `pdo_mysql`, `redis`, `intl`, `zip`
- **Database**: MariaDB 11.4+
- **Cache**: Redis Server
- **Node.js**: v20+ & NPM (for Vite/Svelte build)
- **Composer**

### 1. System Setup

Install required system packages (Ubuntu/Debian example):
```bash
sudo apt update
sudo apt install apache2 mariadb-server redis-server npm git unzip
sudo apt install php8.4 php8.4-cli php8.4-mysql php8.4-redis php8.4-intl php8.4-zip libapache2-mod-php8.4
```

Install Node.js (v20+):
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Install Composer globally:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Codebase Setup

Clone the repository into your web root:
```bash
cd /var/www
sudo git clone <repository_url> html
cd html
```

Set appropriate permissions:
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 775 /var/www/html/public/uploads
```

### 3. Database Configuration

Secure MariaDB and create the database/user:
```sql
CREATE DATABASE sdo;
CREATE USER 'sdo_admin'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON sdo.* TO 'sdo_admin'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Backend & Frontend Configuration

1. **Environment Variables**:
   ```bash
   cp .env.example .env
   # Edit .env with your DB credentials and App Settings
   nano .env
   ```

2. **Install PHP Dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Run Migrations & Seeds**:
   ```bash
   php vendor/bin/phinx migrate -e production
   php vendor/bin/phinx seed:run -e production
   ```

4. **Build Frontend**:
   ```bash
   npm install
   npm run build
   ```

### 5. Apache2 Configuration

Create a VirtualHost configuration:
```bash
sudo nano /etc/apache2/sites-available/sdo.conf
```

Add the following configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sdo_error.log
    CustomLog ${APACHE_LOG_DIR}/sdo_access.log combined
</VirtualHost>
```

Enable the site and `mod_rewrite`:
```bash
sudo a2enmod rewrite
sudo a2ensite sdo.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

### 6. Background Workers (Systemd)

Starlight Dominion requires background workers for game ticks. We recommend using systemd services.

Create a service file for the dispatcher:
```bash
sudo nano /etc/systemd/system/sdo-tick-dispatcher.service
```
```ini
[Unit]
Description=SDO Tick Dispatcher
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php bin/tick-dispatcher.php
Restart=always

[Install]
WantedBy=multi-user.target
```

Create a service file for the processor (you can run multiple of these):
```bash
sudo nano /etc/systemd/system/sdo-tick-processor.service
```
```ini
[Unit]
Description=SDO Tick Processor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php bin/tick-processor.php
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start the services:
```bash
sudo systemctl daemon-reload
sudo systemctl enable sdo-tick-dispatcher sdo-tick-processor
sudo systemctl start sdo-tick-dispatcher sdo-tick-processor
```
