# Personal Blog

Laravel blog application with MySQL running through Docker Compose.

## Requirements

- PHP 8.3 or later
- Composer
- Node.js and npm
- Docker Desktop with Docker Compose

## First-time setup (PowerShell)

Open PowerShell in the repository root and run:

```powershell
Set-Location .\website
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Open `website\.env` and set the database values as follows:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog
DB_USERNAME=root
DB_PASSWORD=
```

Start MySQL with Docker Compose, then prepare Laravel and the frontend:

```powershell
docker compose up -d mysql
php artisan key:generate
php artisan migrate
npm install
```

The local Docker setup allows an empty MySQL root password for development. Do not use this configuration in production.

## Run the application

From `website`:

```powershell
composer run dev
```

Open http://localhost:8000 in a browser. The command starts the Laravel server, queue worker, and Vite development server.

To start phpMyAdmin as well:

```powershell
docker compose --profile dev up -d
```

phpMyAdmin is available at http://localhost:8080.

## Code quality

From `website`:

```powershell
npm run lint
npm run format:check
npm run format:php:check
npm run build
```

Run `npm run format` to format the frontend files and project configuration. Use `npm run format:php` to format PHP and Blade files, or `npm run format:php:check` to check them without changing files. Husky runs lint and frontend format checks automatically before each commit.
