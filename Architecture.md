# Architecture

See `docs/Architecture.md` for the detailed architecture notes.

Summary:

- Laravel 11 owns routing, auth, validation, queues, events and persistence.
- React/Vite/Inertia owns the authenticated builder and public fill UI.
- MySQL stores forms, schema snapshots, submissions, AI/import status and webhook configuration.
- The JSON schema in `forms.schema` is the source of truth for builder rendering and public submission validation.
- Queues handle AI generation, document imports and webhook delivery.
