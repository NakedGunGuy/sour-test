---
name: clean-refactor
description: Refactor PHP code to follow clean code principles. Use when asked to clean up, refactor, or improve code quality.
argument-hint: "[file or directory path]"
---

# Clean Code Refactor

Refactor the specified files to follow clean code PHP principles. Make changes directly — don't just suggest them.

## Refactoring Checklist

Apply these in order of impact:

### 1. Structure (highest impact)
- [ ] Split classes with multiple responsibilities into focused classes
- [ ] Extract methods that mix abstraction levels
- [ ] Replace raw associative arrays with typed value objects (readonly properties)
- [ ] Replace deep nesting with early returns / guard clauses

### 2. DRY
- [ ] Extract duplicated logic into shared private methods
- [ ] Unify near-identical methods that differ only in a parameter (pass the varying part as an argument)
- [ ] Extract repeated conditionals into named methods

### 3. Naming & Clarity
- [ ] Rename cryptic variables to reveal intent
- [ ] Replace magic numbers with named constants
- [ ] Ensure function names describe what they do

### 4. Type Safety
- [ ] Add `declare(strict_types=1)` if missing
- [ ] Add missing type hints (params, returns, properties)
- [ ] Use `===` instead of `==`

### 5. Simplification
- [ ] Remove dead code (unused methods, commented-out blocks)
- [ ] Replace `isset()` checks with `??`
- [ ] Replace boolean flag parameters with separate methods
- [ ] Reduce function arguments — introduce parameter objects if > 2

## Sauerkraut Conventions

When refactoring, follow the established patterns:

- **Value objects**: Use `readonly` properties with constructor promotion (see `Column`, `ForeignKey`, `Table`)
- **Helpers on data objects**: Put derived data methods on the value object itself (see `Table::primaryKeyName()`)
- **Return types**: Methods that detect/discover should return typed value objects, not raw arrays
- **Constants**: Class-level `private const` for magic values (see `CmsController::PER_PAGE`)
- **Asset methods**: Component asset handling uses unified private helpers with extension parameter (see `Component::getAssetContent()`)
- **Configuration**: Magic numbers that might vary per deployment belong in `config/`, not constants

## Rules

- Preserve all existing behavior — refactoring must not change functionality
- Run `/clean-review` mentally before and after to verify improvement
- Keep changes minimal — only fix what violates the principles
- Update Obsidian docs if the refactor changes architecture

## Target

Refactor: $ARGUMENTS

If no target specified, refactor files changed since the last commit.
