# Agent instructions

These instructions apply to the active application at the repository root. `backend/` and `frontend/` are tracked legacy directories excluded from the root build. Make application changes in the root Laravel and Vue sources.

## Boundaries and contracts

- Backend flow is `routes/` → `app/Http/Controllers/` → `app/Services/` → `app/Repositories/` → `app/Models/`. Keep validation and HTTP response formatting in controllers, business rules and transaction boundaries in services, and persistence queries in repositories.
- Mutating services use `App\Services\UnitOfWork`. Wrap the operation in `run()`, then explicitly return `commit($result)` on success. `rollback($error)` rethrows so Laravel rolls back the transaction. Board/card mutations lock the accessible board and check the required permission through `BoardRepository::lock()`.
- Boards, columns, and cards use soft deletes. Preserve ownership and board scoping when adding repository queries. Card order uses a floating-point `position`. Move operations validate neighboring cards and may compact positions when no representable gap remains. Keep schema changes additive because migrations may already have run in deployed environments.
- Board updates broadcast `BoardChanged` on the recipients’ private `users.{userId}` channels. Keep recipient scoping and authorization consistent with `routes/channels.php`.
- Built-in and custom role permissions are defined by `BoardRole::PERMISSIONS`. Validate new permission names there and keep role administration owner-only. Card templates belong to one board and are applied through the template service.
- Frontend application code lives in `resources/js/`. Use Vue 3 Composition API with `<script setup>`, TypeScript, and Pinia. Keep browser-independent board and ordering logic in the neighboring TypeScript modules.
- OpenAPI source annotations live in `app/OpenApi/`. `docs/openapi.yaml` is the checked-in contract generated from those annotations. Update annotations, generate the YAML, and run `python scripts/check-contract.py`; do not hand-edit generated `storage/api-docs/` output.

## Checks

Use the setup and verification commands in `README.md`. The full check sequence is in `.github/workflows/ci.yml`. PHP tests use PostgreSQL and the separate `kanban_testing` database configured in `phpunit.xml`. CI uses PCOV for coverage. Playwright tests are under `tests/e2e/`. OpenAPI comparison requires a generated YAML at `storage/api-docs/api-docs.yaml`.

## Repository hygiene

- Read `.env.example` for local configuration. Never commit `.env`, credentials, generated bundles, or runtime output. `.gitignore` and `.dockerignore` define the project exclusions.
- For architecture lookup and the configured code index, consult [docs/code-index.md](docs/code-index.md). Follow its indexing and exclusion rules when updating the code index.
