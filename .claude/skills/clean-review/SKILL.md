---
name: clean-review
description: Review PHP code against clean code principles. Use when asked to review code quality, check for clean code violations, or audit files.
argument-hint: "[file or directory path]"
---

# Clean Code PHP Review

Review the specified files (or recent changes if no path given) against these clean code PHP principles. For each violation found, cite the **file:line**, the **principle violated**, and a **concrete fix**.

## Principles to Check

### Variables
1. **Meaningful names** — No cryptic abbreviations. Names should reveal intent.
2. **Consistent vocabulary** — One term per concept across the codebase.
3. **Searchable names** — No magic numbers or unnamed constants. Use `const` or config.
4. **Explanatory variables** — Break complex expressions into named intermediates.
5. **No mental mapping** — `$user` not `$u`, `$subscription` not `$s`.
6. **No unneeded context** — Don't repeat the class name in property names (`$user->userName` → `$user->name`).

### Functions
7. **2 or fewer arguments** — Group related params into an object/DTO when exceeding 2.
8. **Names say what they do** — `getActiveUsersByEmail()` not `getUsers()`.
9. **Single level of abstraction** — Don't mix high-level orchestration with low-level details.
10. **No flag parameters** — Split into separate functions instead of `bool $isAdmin`.
11. **No side effects** — A function that reads shouldn't also write.
12. **No global functions** — Encapsulate in classes (except the minimal helpers.php set).

### Control Flow
13. **Strict comparison** — Always `===` / `!==`, never `==` / `!=`.
14. **Null coalescing** — Use `??` instead of `isset()` checks.
15. **Early returns** — Guard clauses at the top, avoid deep nesting.
16. **Encapsulate conditionals** — Extract complex boolean logic into named methods.
17. **Positive conditionals** — `isActive()` not `isNotDisabled()`.

### Classes & Objects
18. **Single Responsibility** — Each class has one reason to change.
19. **Private/protected members** — Don't expose internals. Use readonly properties or getters.
20. **Composition over inheritance** — Prefer injecting collaborators over extending base classes.
21. **Prefer final classes** — Mark final unless designed for extension.
22. **Object encapsulation** — Return typed value objects, not raw associative arrays.

### SOLID
23. **Open/Closed** — Extend behavior without modifying existing code.
24. **Liskov Substitution** — Subclasses must be substitutable for their parents.
25. **Interface Segregation** — Small, focused interfaces. No fat interfaces.
26. **Dependency Inversion** — Depend on abstractions, not concretions.

### General
27. **DRY** — No copy-pasted logic. Extract shared code.
28. **Remove dead code** — Delete unused functions/variables. Don't comment them out.
29. **Type safety** — `declare(strict_types=1)` in every file. Use type hints everywhere.

## Output Format

For each file reviewed, output:

```
### filename.php

- **Line XX** — [Principle]: Description of violation
  → Fix: concrete suggestion

- **Line YY** — [Principle]: Description of violation
  → Fix: concrete suggestion
```

If a file is clean, say so briefly. End with a **Summary** counting total violations by category.

## Target

Review: $ARGUMENTS

If no target specified, review files changed since the last commit (`git diff --name-only HEAD`).
