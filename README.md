# AI Form Builder

Live demo: pending Render deployment.

Demo credentials after seeding:

- Email: `demo@example.com`
- Password: `password`

AI Form Builder is a Laravel 11, MySQL-ready, React/Vite and TailwindCSS application for building, generating, importing, publishing and analyzing forms from a JSON schema source of truth.

## Features

- Manual form builder with click-to-add fields, drag reorder, duplicate, inline label editing, deletion and field settings.
- 13 field types: text, textarea, number, email, phone, date, dropdown, radio, checkbox, file, section, rating and URL.
- Per-field configuration for label, key, placeholder, help text, default, required flag, options and validation JSON.
- Raw JSON schema editor with two-way canvas sync and server-side schema validation before persistence.
- Public fill URLs at `/f/{token}` with server-side validation compiled from the saved schema.
- Submission storage, paginated/searchable listing and CSV export.
- Queued AI form generation and AI editing jobs with visible status rows, model/tokens/latency/error logging and deterministic fallback generation.
- DOCX/XLSX import jobs with deterministic parsing, warnings and a mapping-ready detected schema.
- Part D improvements: multi-tenant isolation, form versioning/rollback, Redis/database cached compiled validation rules, submission webhooks/API, analytics counters and spam honeypot/rate limiting.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
php artisan queue:work --queue=ai,imports,webhooks,default
```

For local SQLite testing, keep `DB_CONNECTION=sqlite`. For production, set MySQL variables as shown in `.env.example`.

## Environment

Important variables:

- `APP_URL`: public Render URL.
- `DB_*`: MySQL connection.
- `QUEUE_CONNECTION`: `database` by default; `redis` is supported when Redis is provisioned.
- `CACHE_STORE`: `database` locally; `redis` recommended on Render.
- `OPENAI_API_KEY`: optional. Without it, the deterministic fallback AI provider still returns schema-valid forms.
- `OPENAI_MODEL`: defaults to `gpt-4o-mini`.

## Architecture

The backend is split into controllers, form requests, services, jobs and events:

- `FormSchemaService`: schema normalization, validation and Laravel rule compilation.
- `FormService`: create/update/rollback, version snapshots and compiled-rule caching.
- `AiFormService`: prompt contract, OpenAI call, JSON repair/extraction and fallback generation.
- `ImportParserService`: deterministic DOCX/XLSX parsing into mapping-ready schema.
- Jobs: `GenerateFormWithAi`, `ProcessImportBatch`, `DispatchSubmissionWebhook`.
- Event: `FormSubmitted`, consumed by `QueueSubmissionWebhooks`.

The JSON schema is the single source of truth. The builder canvas and raw JSON editor both mutate the same object; the server re-normalizes and validates before save.

## Database And Indexes

Core tables: tenants, users, forms, form_versions, submissions, ai_generations, import_batches, webhook_endpoints, templates, personal_access_tokens, jobs/cache/session tables.

Scale-facing indexes:

- `forms`: unique `(tenant_id, slug)`, `public_token`, `(tenant_id, is_published)`, `(tenant_id, updated_at)`.
- `form_versions`: unique `(form_id, version)`, `(form_id, created_at)`.
- `submissions`: `(tenant_id, created_at)`, `(form_id, created_at)`, `(form_id, respondent_email)`, MySQL full-text on respondent name/email.
- `ai_generations`: `(tenant_id, status)`, `(form_id, created_at)`.
- `import_batches`: `(tenant_id, status)`, `(user_id, created_at)`.
- `webhook_endpoints`: `(tenant_id, is_active)`, `(form_id, is_active)`.

## AI Prompt Strategy

System prompt: act as a form schema compiler and return only valid JSON matching `{title, description, steps, logic}`.

Output contract: only supported field types are accepted; hallucinated types are normalized to `text`. Labels, keys, placeholders, options, required flags and validation rules must be included.

Malformed JSON handling: the service strips markdown fences, extracts the outer JSON object, decodes it, normalizes it and validates it. If the provider fails or returns broken schema, the deterministic fallback builds a schema from prompt keywords and still passes validation.

Existing-form editing: edit jobs pass the current schema plus the instruction. The fallback appends or adjusts sections such as emergency contact and Hindi label translation without overwriting the full form.

## API

See `docs/API.md`.

## ERD

See `docs/ERD.md`.

## Deployment

Render blueprint is in `render.yaml`; deployment instructions are in `docs/DEPLOYMENT.md`.

## Known Limitations

- The UI exposes AI/import job IDs and JSON status endpoints, but does not yet auto-poll and apply completed schemas back into the editor.
- The DOCX/XLSX import screen returns a mapping-ready schema; a richer visual mapping wizard would be the next iteration.
- Live Render deployment and GitHub push require account credentials/PAT.
