# ARTEMIS Portal

ARTEMIS Portal is a Laravel-based port of the ARIADNE Portal.

This repository reimplements the portal stack with standard Laravel components, replacing the former legacy runtime bridge with native Laravel services, OpenSearch integration, a Filament admin panel, and the frontend application published directly from the Laravel project.

## Project Scope

- Laravel backend for search, aggregations, records, and supporting portal APIs
- OpenSearch-backed data layer for `Data Resources` and `Heritage Entities`
- Filament admin panel for curated linking and import workflows
- Frontend SPA maintained inside the same Laravel repository
- Docker setup for local development with Laravel and OpenSearch

## Architecture

- `app/Services/PortalSearchService.php`
  Native Laravel search service for portal data resources.
- `app/Services/HeritageEntitySearchService.php`
  Native Laravel search service for heritage entities.
- `app/Services/PortalResourceImportService.php`
  Import workflow for ARIADNE data resources into the local portal index.
- `app/Services/GraphDbHeritageEntityImportService.php`
  GraphDB-to-OpenSearch importer for heritage entities.
- `app/Filament/Pages/LinkResources.php`
  Filament page for linking ARIADNE data resources.
- `app/Filament/Pages/HeritageEntities.php`
  Filament page for importing and managing heritage entities.
- `frontend/`
  SPA source code maintained inside this repository.
- `public/`
  Published frontend bundle and static assets served by Laravel.
- `resources/opensearch/`
  OpenSearch mappings and bootstrap data shipped with the project.

## Current Status

- No legacy runtime bridge is used in the active backend.
- `Data Resources` and `Heritage Entities` are both handled inside Laravel.
- `mail` and `updateServices` endpoints are no longer part of the runtime portal.
- The admin area is implemented with Filament.
- The published frontend is served by Laravel from `public/index.html`.

## Local Development

Install dependencies and bootstrap the application:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Run the Laravel application locally:

```bash
php artisan serve --host=127.0.0.1 --port=8099
```

Default local URLs:

- Portal: `http://127.0.0.1:8099`
- Filament admin: `http://127.0.0.1:8099/admin/login`
- OpenSearch: `http://127.0.0.1:9200`

Default local admin credentials:

- email: `admin@admin.local`
- password: `admin`

## Frontend Build

Build the frontend from the internal SPA source directory:

```bash
cd frontend
npm install
npm run build-local
```

Then publish the build output into Laravel `public/`:

```bash
rsync -a dist/ ../public/
```

## Docker

The repository includes a Docker setup for:

- Laravel application runtime
- OpenSearch
- OpenSearch bootstrap
- seeded Filament admin access

Start the full stack:

```bash
docker compose up --build
```

Stop the stack:

```bash
docker compose down
```

Remove containers and persisted volumes:

```bash
docker compose down -v
```

## Notes

- This repository is the Laravel port of the ARIADNE Portal, adapted for ARTEMIS.
- New project documentation should be kept in English.
- New code comments should be written in English.
- OpenSearch mappings and minimal bootstrap data are stored inside the Laravel repository.
