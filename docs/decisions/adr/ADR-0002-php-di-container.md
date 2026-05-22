# ADR-0002: Adopt PHP-DI as Dependency Injection Container

- Status: accepted
- Date: 2026-04-14
- Owner: Technical Lead
- Approvers: Backend Lead
- Supersedes: none
- Superseded by: none
- Scope: architecture
- Imported from: old starlight V3 corpus

## Context

Strict MVC-S requires that Controllers and Services never manually instantiate their own dependencies (`new` keyword). A DI container must wire all dependencies automatically. v2 uses PHP-DI with autowiring successfully; v3 needs to pick a container before scaffolding begins to avoid re-wiring costs later.

## Decision

Adopt PHP-DI as the DI container for v3, carrying the pattern from v2.

Key configuration rules:
- Autowiring enabled by default.
- Explicit bindings declared in a `ContainerFactory` for interfaces and config-backed dependencies.
- No manual `new` calls inside Controllers or Services; all dependencies injected via constructor.

## Consequences

Positive:
- Consistent with v2; team already knows the tool.
- Autowiring reduces boilerplate.
- Container can be used to inject config values and PDO cleanly.

Negative:
- PHP-DI adds a vendor dependency.
- Misconfigured bindings are runtime errors, not compile-time.

## Alternatives Considered

- Laravel's IoC container (too coupled to Laravel ecosystem)
- Plain constructor injection with a manual factory (workable but brittle at scale)
- Symfony DependencyInjection (more powerful but significantly more configuration overhead)

## Rollout Plan

1. Add PHP-DI to `composer.json`.
2. Create `app/Core/ContainerFactory.php` modelled on v2 equivalent.
3. Wire all first-wave Controllers and Services through the container.

## Validation Plan

- Compliance test `verify_di_resolution.php` must pass for all Controllers.
- No unresolved bindings on container boot in unit test bootstrap.
