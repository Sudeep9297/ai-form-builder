# Decisions

## Assumptions

- React via Inertia satisfies the React + Vite requirement while keeping Laravel routing, auth and validation cohesive.
- MySQL is the production database; SQLite is supported for automated tests, so MySQL-only full-text indexes are conditional.
- OpenAI is optional. The app must remain usable without a paid key, so deterministic fallback generation is productionized rather than mocked.

## Part D Choices

1. Multi-tenant isolation
   - Problem: assignment systems often become unsafe once multiple demo users exist.
   - Implementation: `tenant_id` on forms, submissions, AI jobs, imports, templates and webhooks; controllers enforce tenant scope.
   - Trade-off: no tenant invite UI yet.
   - More time: roles, invitations and tenant audit logs.

2. Form versioning and rollback
   - Problem: schema editors need recovery from accidental changes.
   - Implementation: immutable `form_versions` snapshots on every create/update and rollback route.
   - Trade-off: full snapshots use more storage than diffs.
   - More time: visual diff and named releases.

3. Cached compiled validation
   - Problem: public submissions should validate from schema without recompiling on every request at scale.
   - Implementation: `FormService::compiledRules` caches rules per form version.
   - Trade-off: cache invalidation depends on save/rollback paths.
   - More time: precompile to a dedicated table and warm on publish.

4. Webhooks and submissions API
   - Problem: collected data often needs to flow into CRMs or analytics.
   - Implementation: Sanctum API route and queued signed webhook delivery.
   - Trade-off: webhook endpoint management UI is not built.
   - More time: retries dashboard, endpoint CRUD and replay.

## Trade-Offs

- The import mapping screen focuses on the high-impact corrections: label, type and options. It does not yet expose confidence scores.
- AI/import status uses straightforward interval polling instead of websockets or SSE.
- Render live deployment and GitHub push require external credentials.

## Next Two Weeks

- Visual import mapping wizard with confidence scores.
- Real-time job polling and one-click apply for completed AI/import schemas.
- Conditional logic runtime in the public fill page.
- Webhook endpoint management UI.
- Browser-level accessibility audit and Playwright coverage.
