# Game Items E-commerce

Laravel 13 skeleton for the MVP of a game items e-commerce focused on Dota 2 and Counter-Strike.

## Foundation assumptions

- Application stack: Laravel 13, PHP 8.3, PostgreSQL, Docker
- Architecture direction: modular monolith by domain
- Business use cases live in Actions
- Default application language: English
- `pt_BR` remains ready for future localization work

## Architecture guardrails

- Domain code lives under `app/Modules`
- Namespace pattern: `App\Modules\{Module}\{Layer}\ClassName`
- Controllers and Livewire components stay thin
- Actions are the primary use-case boundary
- DTOs are the default input boundary for non-trivial writes
- Query Objects are the default home for complex reads
- Generic folders such as `Services`, `Helpers`, `Common`, and `Shared` must not be introduced
- Prefer Laravel defaults before custom infrastructure

## Current module skeleton

- `Catalog`: `Actions`, `DTOs`, `Models`, `Policies`, `Queries`
- `Cart`: `Actions`, `DTOs`, `Models`
- `Orders`: `Actions`, `DTOs`, `Events`, `Models`, `Queries`
- `Payments`: `Actions`, `DTOs`, `Gateways`
- `Admin`: `Actions`, `Policies`, `Queries`

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

## Localization baseline

- Default locale remains `en`
- `pt_BR` is present as the secondary baseline locale
- Shared backend messages should come from `lang/en` and `lang/pt_BR`
- Do not hardcode user-facing strings when localization is expected

## Testing and validation baseline

- Every significant feature should cover at least one happy path
- Every significant feature should cover at least one failure or business-rule path
- Prefer behavior-focused tests over implementation-detail assertions
- Keep Wave 0 validation limited to the smallest relevant checks

Run the baseline checks from the running app container:

```bash
docker exec ecommerce-app-1 php artisan test
docker exec ecommerce-app-1 vendor/bin/pint --test
```
