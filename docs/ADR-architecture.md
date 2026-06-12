# Architecture Decision Record (ADR)

## Context

Proyecto PHP MVC en transición a una arquitectura más explícita, testeable y escalable.

## Decision

Adoptar una arquitectura por capas con `Application / Domain / Infrastructure / Presentation` y un `Composition Root` único.

### Capas

- `public/`: entrada HTTP única.
- `Presentation`: controllers, request/response mapping, views.
- `Application`: use cases, orchestration, DTOs, validators.
- `Domain`: entidades, value objects, domain services, domain rules.
- `Infrastructure`: PDO repositories, external API clients, email, cache, crypto, filesystem, session/cookie adapters.

### Estructura propuesta

```text
src/
  Domain/
    User/
      User.php
      Email.php
      Password.php
      UserRepository.php
  Application/
    Auth/
      LoginUser.php
      RegisterUser.php
      LogoutUser.php
      LoginRequest.php
      RegisterRequest.php
      LoginResult.php
  Infrastructure/
    Persistence/
      PdoUserRepository.php
      PdoRememberTokenRepository.php
    Security/
      PasswordHasher.php
      CsrfTokenManager.php
      SessionManager.php
    External/
      TmdbClient.php
  Presentation/
    Http/
      Controllers/
      Middleware/
      Views/
      ViewModels/
public/
config/
tests/
```

## Rules

### Dependency direction

- `Presentation -> Application -> Domain`
- `Infrastructure -> Application` via interfaces
- `Domain` no depende de `Application` ni `Infrastructure`
- `Application` no depende de `Presentation`

### Allowed dependencies

- Controllers -> Use cases
- Use cases -> Repository interfaces, domain objects, clocks, ID generators, validators
- Repositories -> PDO / ORM / HTTP clients
- Views -> ViewModels only

### Prohibited dependencies

- Controllers -> SQL
- Views -> Session, Database, HTTP clients, business rules
- Domain -> `$_GET`, `$_POST`, `$_SESSION`, `$_COOKIE`, `header()`
- Infrastructure -> Controllers, Views
- Static global helper calls from everywhere

### Business rules

- All domain invariants in `Domain`.
- Workflow/orchestration in `Application`.
- Input validation at edge + invariant validation in `Domain`.
- AuthN/AuthZ decisions in middleware + application policies, not in views.

### Data access

- SQL only in repositories.
- No raw `PDO` in controllers, services, helpers, or views.
- Transaction boundaries in use cases.
- Repository methods return domain objects or DTOs, never super-arrays with mixed concerns.

### Integrations

- External APIs encapsulated behind interfaces.
- No direct `curl`, `file_get_contents`, or raw Guzzle calls in controllers.
- Resilience concerns (timeouts, retries, circuit breaking) in Infrastructure.

### Validation

- Request validation: Presentation/Application boundary.
- Business validation: Domain.
- Database uniqueness checks: repository/application.

### Session / cookie / CSRF

- Managed by dedicated adapters in Infrastructure.
- Controllers consume abstractions, not globals.

### Views

- No session reads in views.
- No CSRF generation in views.
- No role checks in views.
- Views render only data passed by controller/view model.

### Naming

- Use cases: verb + noun, e.g. `RegisterUser`, `CreateRememberMeToken`.
- Repositories: noun + `Repository`.
- Controllers: noun + `Controller`.
- ViewModels: noun + `ViewModel`.
- Entities: singular noun.

### Testing

- Unit tests for domain/application.
- Integration tests for repositories and external clients.
- Feature tests for HTTP flows.
- Every new use case requires at least one happy-path + one failure-path test.

### Refactor policy

- Move logic in small slices.
- Preserve behavior first.
- Extract interfaces before swapping implementations.
- No new global helpers.

## Consequences

- More files, but lower coupling.
- Better test isolation.
- Clearer ownership of logic.
- Slightly higher initial refactor cost.

## Recommendation

Use this as the target architecture. Keep MVC only as the presentation shell.
