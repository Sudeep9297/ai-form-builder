# Architecture

The application uses Laravel as the backend and Inertia React as the frontend delivery layer.

## Request Flow

1. Authenticated users create/edit forms in React.
2. The browser sends the full schema to Laravel.
3. `StoreFormRequest` validates request shape.
4. `FormService` normalizes and validates the schema with `FormSchemaService`.
5. The form row is saved and a `FormVersion` snapshot is written.
6. Public submissions compile validation rules from the same schema, never from browser hints.

## Async Flow

- AI generation creates an `ai_generations` row and dispatches `GenerateFormWithAi`.
- Imports create an `import_batches` row and dispatch `ProcessImportBatch`; the builder polls status and shows a mapping preview before applying the detected schema.
- Submissions dispatch `FormSubmitted`; `QueueSubmissionWebhooks` fans out webhook jobs.

## Schema Contract

Top-level schema:

```json
{
  "title": "Form title",
  "description": "",
  "steps": [
    {
      "id": "uuid",
      "title": "Step",
      "fields": []
    }
  ],
  "logic": []
}
```

Each field includes `id`, `type`, `label`, `key`, `placeholder`, `helpText`, `default`, `required`, `options`, `validation` and optional `visibility`.

## Part D Improvements

- Multi-tenant isolation: every form, submission, import, AI job and webhook is scoped by `tenant_id`.
- Versioning and rollback: every save snapshots schema/settings.
- Cached compiled validation: compiled Laravel validation rules are cached per form/version.
- Public submissions API and webhooks: downstream systems can consume submissions.
- Analytics: builder shows field count, submissions and completion counter.
