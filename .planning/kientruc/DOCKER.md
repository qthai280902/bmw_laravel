# Docker

Last updated: 2026-06-17 +07:00

## Purpose

The Docker setup is for local Laravel development and verification. It mirrors the current CI runtime direction with PHP 8.4 and Node.js 24 while keeping real secrets outside version control.

## Files

- `Dockerfile`: builds the PHP-FPM application image.
- `docker-compose.yml`: defines `app`, `nginx`, and `mysql` services.
- `docker/php/php.ini`: local PHP runtime overrides.
- `docker/nginx/default.conf`: Nginx virtual host for Laravel public files and PHP-FPM.
- `.env.docker.example`: safe local Docker environment template.
- `.dockerignore`: trims the Docker build context and excludes local secrets/dependencies.

## Services

- `app`
  - PHP 8.4 FPM on Debian Bookworm.
  - Composer 2 copied from the official Composer image.
  - Node.js 24 installed for Vite builds.
  - Project mounted at `/var/www/html`.
- `nginx`
  - Nginx 1.27 Alpine.
  - Exposes `${APP_PORT:-8080}` on the host.
  - Serves Laravel from `/var/www/html/public`.
- `mysql`
  - MySQL 8.4.
  - Exposes `${FORWARD_DB_PORT:-3307}` on the host.
  - Uses the `bmw-mysql-data` named volume.

## Local setup

```powershell
Copy-Item .env.docker.example .env
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm ci
docker compose exec app npm run build
```

Open:

```text
http://localhost:8080
```

Run tests:

```powershell
docker compose exec app php artisan test
```

Stop:

```powershell
docker compose down
```

Reset the Docker database volume only when a fresh database is intended:

```powershell
docker compose down -v
```

## Security

- `.env` remains ignored.
- `.env.docker.example` contains placeholders only.
- AI is disabled by default in `.env.docker.example`.
- Real Gemini/API keys must only be placed in a local `.env`.
- `.dockerignore` excludes local dependency folders and secret-like files from the build context.

## Verification status

- Docker CLI was not available on the local machine during the 2026-06-17 check, so `docker compose config`, image build, container boot, and containerized tests could not be executed.
- Non-Docker verification passed on the host:
  - `composer validate --no-check-publish`
  - `vendor\bin\pint --test`
  - `php artisan view:cache`
  - `npm.cmd run build`
  - `php artisan test --compact` with 108 tests / 1128 assertions
- Final Docker runtime status remains `CHUA PASS` until Docker Desktop/Compose is available and the Docker commands above are executed successfully.
