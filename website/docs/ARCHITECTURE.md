# Personal-blog Architecture

## 1. Architecture Style

The application follows Clean Architecture with modular domain boundaries.

Layers:
- Domain: business entities, value objects, domain rules.
- Application: use cases, use case interfaces, DTOs and application contracts.
- Infrastructure: Eloquent persistence, external services and contract implementations.
- Presentation: Laravel HTTP controllers, web routes, API routes and Filament integration.

Dependencies must point inward. Domain and Application must not depend on Eloquent, HTTP, Blade, Filament or external service SDKs.

## 2. Domain Modules

- Identity: users and authentication-related domain behavior.
- AccessControl: platform roles, permissions and authorization.
- Security: account restrictions and security audit events.
- Blog: blog ownership, domains, settings and design configuration.
- Content: posts, pages, categories, tags and content reports.
- Template: template definitions, publication and categorization.
- Media: media metadata and Cloudinary integration.
- Platform: platform settings, notifications and administrative auditing.

Database tables remain unchanged unless a separately reviewed schema change is approved.

## 3. Application Layer

Each use case exposes an interface and a concrete implementation.

Use cases must:
- Accept explicit input DTOs where appropriate.
- Return explicit output DTOs or domain results.
- Depend on abstractions for persistence and external services.
- Enforce application-level authorization and workflow rules.
- Avoid HTTP request, response and framework-specific types.

Interfaces are created at meaningful boundaries, not for every class.

## 4. Domain Layer

Domain objects contain business rules and invariants.

Domain must not depend on:
- Laravel or Eloquent
- HTTP, Blade, Livewire or Filament
- Database connections
- Redis or Cloudinary SDKs

Use value objects and domain services only when they represent actual domain concepts.

## 5. Infrastructure Layer

Infrastructure implements application and domain-facing contracts.

Eloquent models and database queries belong here. External integrations such as JWT, Redis and Cloudinary are accessed through explicit adapters.

Infrastructure implementations are bound through Laravel's service container.

## 6. Presentation Layer

Web, API and Admin are separate presentation entry points.

- Web uses session authentication.
- API uses token authentication.
- Admin requires authentication and explicit authorization.
- Blog ownership is checked independently of platform administrator permissions.
- Controllers validate transport input, invoke use cases and map results to responses.

Filament authorization must be configured independently for panel access, resources and actions.

## 7. Security

- Deny access by default.
- Enforce permissions server-side.
- Never trust UI visibility as authorization.
- Never expose password hashes, two-factor secrets or recovery codes in responses.
- Audit security-sensitive and administrative actions.
- Do not log credentials, tokens or secrets.

## 8. Testing and Quality

- Domain tests verify business rules.
- Application tests verify use case behavior.
- Infrastructure tests verify database and integration behavior.
- Feature tests verify web, API and Admin authorization.
- Laravel Pint must pass in CI.
- Database integration tests use an isolated test database.

## 9. Migration Policy

Existing migrations are the source of truth for the current schema.

Do not delete, rewrite or rename existing migrations as part of application refactoring.

Any schema change requires a new migration and explicit review.
