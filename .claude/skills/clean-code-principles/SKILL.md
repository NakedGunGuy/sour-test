---
name: clean-code-principles
description: Clean code PHP principles and Sauerkraut conventions. Auto-loaded when writing or modifying PHP code to ensure clean code compliance.
user-invocable: false
---

# Clean Code PHP — Active Principles

When writing or modifying PHP code in this project, follow these principles:

## Always

- `declare(strict_types=1)` in every PHP file
- Type hints on all parameters, return types, and properties
- Strict comparison (`===` / `!==`) — never loose equality
- Null coalescing (`??`) instead of `isset()` ternaries
- Escape output with `e()` in templates, prepared statements for SQL
- `@layer components { }` wrapping in all component CSS

## Naming

- Variables and methods: descriptive, no abbreviations (`$user` not `$u`)
- Constants for magic values: `private const PER_PAGE = 25`
- Methods describe what they do: `loadRelatedRecords()` not `process()`
- Consistent vocabulary: pick one term per concept across the codebase
- No redundant context: `$user->name` not `$user->userName`

## Functions & Methods

- **2 or fewer arguments** — use a value object / DTO if more are needed
- **Single level of abstraction** — don't mix orchestration with detail
- **No flag parameters** — split `save(bool $validate)` into `save()` and `saveWithoutValidation()`
- **No side effects** — a getter shouldn't also mutate state
- **Early returns** — guard clauses at the top, reduce nesting

## Classes

- **Single Responsibility** — one reason to change
- **Private by default** — expose only the public API
- **Composition over inheritance** — inject dependencies, don't extend base classes for reuse
- **Return value objects** — typed readonly classes, not raw arrays
- **Named constants** — `private const` for any repeated or magic value
- **No Singleton (in new code)** — use constructor injection. The existing `App` singleton is legacy.

## DRY

- Extract duplicated logic into private methods
- Unify near-identical methods by parameterizing the difference
- Extract repeated conditionals into named methods (e.g., `Table::primaryKeyName()`)

## Control Flow

- Early returns / guard clauses to flatten nesting
- Encapsulate complex conditions: `if ($this->isJunctionTable($t))` not inline checks
- Positive conditionals: `isValid()` not `isNotInvalid()`

## Sauerkraut Patterns

- **Value objects**: `readonly` constructor-promoted properties (see `Column`, `ForeignKey`, `Table`, `OneToManyRelation`, `ManyToManyRelation`, `DetectedRelationships`)
- **Derived data**: Put computed helpers on the value object itself (`Table::primaryKeyName()`)
- **Asset methods**: Use unified private helpers with extension parameter (`Component::getAssetContent($name, 'css')`)
- **Config vs constants**: Values that might change per deployment → config. Values fixed by design → `private const`.
- **Helper extraction**: When a controller method exceeds ~30 lines, extract focused private methods (`resolveMtoLabels()`, `loadRelatedRecords()`, `extractFieldData()`)
