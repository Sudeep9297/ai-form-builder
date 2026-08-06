# Deployment Guide

## Render Blueprint

`render.yaml` defines:

- Web service: `ai-form-builder-web`.
- Free Docker web service: `ai-form-builder-web`.
- MySQL connection via environment variables. Render does not provide a free MySQL private service, so use an external MySQL 8-compatible database if deploying without payment information.
- Queue processing runs inside the free web container when `RUN_QUEUE_WORKER=true`.

## Build And Start

The Dockerfile performs:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
docker-php-ext-install pdo_mysql zip gd
```

Web start command:

```bash
scripts/render-web.sh
```

Worker start command:

```bash
scripts/render-worker.sh
```

The free-tier Blueprint does not define a separate Render background worker because Render's Blueprint spec does not support `plan: free` for worker services. Instead, `scripts/render-web.sh` starts `queue:work` in the background when `RUN_QUEUE_WORKER=true`. For a paid production upgrade, add a separate Docker worker using `dockerCommand: scripts/render-worker.sh`.

## Required Environment Variables

Set these in Render:

- `APP_KEY`: generate locally with `php artisan key:generate --show` and paste into Render.
- `APP_URL`: deployed web URL.
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: external MySQL 8-compatible database credentials.
- `QUEUE_CONNECTION=database`.
- `CACHE_STORE=database` or Redis if provisioned.
- `FILESYSTEM_DISK=public`.
- `OPENAI_API_KEY`: optional. Leave it blank when no LLM provider is available; the app still boots and AI requests return a clear provider-not-configured message.

Render notes: Render Blueprints define non-Postgres services under `services`. Render's Blueprint spec allows `plan: free` for web services, but not for private services or background workers, and omitted plans default to `starter`. This free Blueprint therefore defines only the web service and expects MySQL credentials from an external database provider.

## Railway

`railway.json` pins Railway to the root Dockerfile and starts the app with `scripts/render-web.sh`.

The startup script does not require the `mysql` CLI. It waits for MySQL with PHP PDO, then runs:

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

The script skips `storage:link` when `public/storage` already exists, so repeated Railway deploys remain idempotent.

No SQL schema dump is committed for Railway because Laravel's migrator automatically tries to load `database/schema/mysql-schema.sql` on a fresh MySQL database, and that path depends on the external `mysql` CLI. Database schema creation is handled by Laravel migrations instead.

## Post Deploy

1. Open `/login`.
2. Use `demo@example.com` / `password`.
3. Confirm `RUN_QUEUE_WORKER=true` on the web service so queued jobs are processed in the free deployment.
4. Create or publish a form.
5. Test `/f/{token}` and CSV export.
