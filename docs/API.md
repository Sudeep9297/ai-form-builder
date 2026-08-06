# API Documentation

## Web Routes

- `GET /forms`: authenticated form dashboard.
- `GET /forms/create`: builder for a new form.
- `POST /forms`: create a form. Body: `title`, `description`, `schema`, `settings`, `is_published`.
- `GET /forms/{id}/edit`: builder for an existing form with versions and submissions.
- `PUT /forms/{id}`: update and snapshot a form.
- `POST /forms/{id}/rollback/{versionId}`: restore a previous schema snapshot.
- `GET /forms/{id}/submissions.csv`: CSV export.
- `POST /ai-generations`: queue AI generation/edit. Body: `prompt`, `mode=create|edit`, optional `form_id`.
- `GET /ai-generations/{id}`: JSON status/result.
- `POST /imports`: upload `.docx` or `.xlsx`.
- `GET /imports/{id}`: JSON import status, detected schema, warnings and mapping data.
- `GET /f/{token}`: public fill page.
- `POST /f/{token}`: public submit. Body: `answers`.

## Token API

Use a Sanctum bearer token.

`GET /api/forms/{form}/submissions`

Query params:

- `page`: page number.
- `per_page`: 1-100, default 25.

Response: paginated submissions with `payload`, respondent metadata and timestamps. Tenant scoping is enforced server-side.

## Webhooks

When a submission is stored, active webhook endpoints receive:

```json
{
  "event": "form.submitted",
  "form_id": 1,
  "submission_id": 10,
  "submitted_at": "2026-08-06T07:30:00+00:00",
  "answers": {}
}
```

Signature header: `X-FormBuilder-Signature`, HMAC-SHA256 of the JSON payload with the endpoint secret.
