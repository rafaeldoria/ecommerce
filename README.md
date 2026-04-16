# Game Items E-commerce

Laravel 13 skeleton for the MVP of a game items e-commerce focused on Dota 2 and Counter-Strike.

## Foundation assumptions

- Application stack: Laravel 13, PHP 8.3, PostgreSQL, Docker
- Architecture direction: modular monolith by domain with Actions, implemented in later tasks
- Current scope: repository foundation only
- Default application language: English
- `pt_BR` should remain available for future localization work

## Local bootstrap

This repository runs inside WSL on Windows and the application is executed through Docker containers. Prefer executing project commands from the Linux path:

```bash
cd /home/r00t/projects/ecommerce/src
```

Initialize the Laravel application:

```bash
cp .env.example .env
composer install
php artisan key:generate
```

When the containers are already running, prefer executing Laravel checks through the app container instead of the raw host shell.

## Database assumptions

The project baseline targets PostgreSQL. The example environment file is configured with these local defaults:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SSLMODE=prefer
```

Adjust these values in `.env` to match your local Docker or PostgreSQL setup before running database-dependent commands.

## Minimal verification

Run only the baseline checks required for repository foundation work from the running app container:

```bash
docker exec ecommerce-app-1 php artisan about
docker exec ecommerce-app-1 php artisan test
docker exec ecommerce-app-1 vendor/bin/pint --test
```

## Notes for the next tasks

- Do not introduce domain modules during foundation work
- Do not add business migrations in this phase
- Keep changes close to Laravel defaults unless a task explicitly requires otherwise
