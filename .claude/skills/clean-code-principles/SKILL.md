---
name: clean-code-principles
description: Clean code PHP principles and Sauerkraut conventions. Auto-loaded when writing or modifying PHP code to ensure clean code compliance.
user-invocable: false
---

# Clean Code PHP — Active Principles

Reference: https://github.com/piotrplenik/clean-code-php

When writing or modifying PHP code in this project, follow these principles:

## Always

- `declare(strict_types=1)` in every PHP file
- Type hints on all parameters, return types, and properties
- Strict comparison (`===` / `!==`) — never loose equality
- Null coalescing (`??`) instead of `isset()` ternaries
- Escape output with `e()` in templates, prepared statements for SQL
- `@layer components { }` wrapping in all component CSS

## Variables

- **Meaningful, pronounceable names** — `$user` not `$u`, `$match` not `$m`, `$package` not `$pkg`
- **No mental mapping** — avoid abbreviations that force the reader to translate
- **Consistent vocabulary** — pick one term per concept across the codebase
- **No redundant context** — `$user->name` not `$user->userName`
- **Searchable names** — constants for magic values: `private const SECONDS_PER_DAY = 86400`
- **Explanatory variables** — extract complex expressions into named intermediates
- **Remove dead variables** — unused loop vars like `$name =>` when only the value is needed

## Comparison

- **Identical comparison** — always `===` / `!==`, never `==` / `!=`
- **Null coalescing** — `$value ?? $default` instead of `isset()` ternaries

## Functions & Methods

- **2 or fewer arguments** — use a value object / DTO if more are needed
- **Function names say what they do** — `loadRelatedRecords()` not `process()`
- **Single level of abstraction** — don't mix orchestration with detail
- **No flag parameters** — split `save(bool $validate)` into `save()` and `saveWithoutValidation()`
- **No side effects** — a getter shouldn't also mutate state; name destructive reads explicitly (e.g., `consumeFlash()` not `getFlash()`)
- **Early returns** — guard clauses at the top, reduce nesting
- **Don't write to globals** — avoid modifying global state
- **Encapsulate conditionals** — extract compound conditions into named methods: `if ($this->isValidToken(...))` not `if (!$a || !$b || !hash_equals(...))`
- **Avoid negative conditionals** — prefer `isValid()` over `isNotInvalid()`
- **Remove dead code** — delete unused functions, methods, and imports

## Classes

- **Single Responsibility** — one reason to change
- **Open/Closed** — open for extension, closed for modification
- **Liskov Substitution** — subtypes must be substitutable for their base types
- **Interface Segregation** — smaller, specific interfaces over large general ones
- **Dependency Inversion** — depend on abstractions, not concretions
- **Private by default** — expose only the public API
- **Composition over inheritance** — inject dependencies, don't extend base classes for reuse
- **Return value objects** — typed readonly classes, not raw arrays
- **Named constants** — `private const` for any repeated or magic value
- **No Singleton (in new code)** — use constructor injection. The existing `App` singleton is legacy.
- **Prefer final classes** — unless designed for extension

## DRY

- Extract duplicated logic into shared methods or factory methods on App
- Unify near-identical methods by parameterizing the difference
- Extract repeated conditionals into named methods (e.g., `Table::primaryKeyName()`)

## Sauerkraut Patterns

- **Value objects**: `readonly` constructor-promoted properties (see `Column`, `ForeignKey`, `Table`, `Signature`, `Schedule`, `ValidationResult`)
- **Derived data**: Put computed helpers on the value object itself (`Table::primaryKeyName()`)
- **Static factories**: Use static methods for clean APIs (`Validator::validate()`, `Response::html()`, `Rules::email()`)
- **Interface for opt-in behavior**: `Schedulable` interface rather than nullable methods on base class
- **Config vs constants**: Values that might change per deployment → config. Values fixed by design → `private const`
- **Helper extraction**: When a method exceeds ~30 lines, extract focused private methods
- **Shared infrastructure**: Put reusable factory methods on `App` (e.g., `App::migrator()`, `App::buildRouter()`) to avoid duplication across commands
