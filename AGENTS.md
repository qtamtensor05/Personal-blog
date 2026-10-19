# Personal-blog Agent Instructions

These instructions apply to the entire repository, including `website/`.

## Architecture

- Follow the refactored Clean Architecture and modular domain boundaries documented in `website/docs/ARCHITECTURE.md`.
- Keep dependencies pointing inward:
  - `Domain` contains business rules, entities, value objects and enums. It must not depend on Laravel, HTTP, Blade, databases or external SDKs.
  - `Application` contains use cases, DTOs and contracts. It must not depend on controllers, Blade or persistence implementations.
  - `Infrastructure` implements Application contracts and contains persistence and external-service adapters.
  - `Presentation` contains controllers, middleware, requests, views, CSS and JavaScript. Controllers validate transport input, call Application use cases and map results to responses.
- Preserve module boundaries. Add or update service-container bindings in `app/Providers` when an Infrastructure implementation satisfies an Application contract.
- Do not bypass use cases by placing business logic in controllers, routes, Blade templates or JavaScript.

## Persistence

- Use Laravel Query Builder through `DB::table(...)` or query-builder instances for all database access.
- Do not create or use Eloquent queries, Eloquent relationships, model scopes or active-record persistence.
- Keep Query Builder repositories and data mapping in the Infrastructure layer.
- Return Domain objects or Application DTOs across architecture boundaries instead of database rows.
- Do not rewrite existing migrations. Create a new migration for every schema change.

## Repository scope

- Do not modify Dockerfiles, Docker Compose files or Docker-related configuration.
- Do not modify CI workflows, pipeline definitions or CI-related configuration.
- Preserve unrelated and uncommitted user changes.

## Frontend interaction

- Do not use native browser dialogs: `alert()`, `prompt()` or `confirm()`.
- Use the shared modal component and modal helpers for errors, text input and confirmation flows.
- Keep mobile and desktop navigation responsive, accessible and free of duplicate controls.

## Branch naming

- Use one of these branch patterns:
  - `feat/<module_name>`
  - `refactor/<module_name>`
  - `fix/<module_name>`
  - `docs/<module_name>`
- Use a concise lowercase module name; hyphens or underscores are accepted consistently with the existing repository branches.
- Do not perform feature work on a branch that does not follow this convention.

## Validation

- After changing PHP code, run `./vendor/bin/pint` to format it. Do not use `npm run format`.
- Run focused tests for the changed behavior, then the relevant broader suite when warranted.
- For frontend changes, run `npm run lint` and `npm run build`.
- Run `git diff --check` before reporting completion.
- Report source checks, automated tests, local runtime and browser verification separately. Do not claim browser, deployment or external-service success without direct evidence.
