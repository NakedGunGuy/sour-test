---
name: new-class
description: Scaffold a new PHP class following clean code principles and Sauerkraut conventions. Use when creating new classes, services, middleware, controllers, or value objects.
argument-hint: "[ClassName] [description of purpose]"
---

# Create New Class

Create a new PHP class for: $ARGUMENTS

## Requirements

Every new class MUST follow these rules:

### File Structure
```php
<?php

declare(strict_types=1);

namespace Sauerkraut\...; // or App\...

class ClassName
{
    // 1. Constants
    // 2. Properties (private/protected, typed)
    // 3. Constructor
    // 4. Public methods
    // 5. Private/protected methods
}
```

### Clean Code Rules

1. **Single Responsibility** — The class does one thing. If you're writing "and" in the description, split it.

2. **Constructor injection** — Dependencies come through the constructor, not `App::getInstance()` calls scattered through methods. Exception: controllers that need the app singleton.

3. **2 or fewer arguments per method** — Group related params into a value object if needed.

4. **Typed everything** — All properties, parameters, and return types must have type declarations.

5. **Private by default** — Only make methods public if they're part of the class's API. Properties are always private/protected (or `readonly` on value objects).

6. **No side effects** — Methods that read don't write. Methods that compute don't print.

7. **Meaningful names** — The class name, method names, and variable names should make the code read like prose.

8. **Return typed objects** — Return value objects or DTOs, not raw arrays. If a method builds structured data, create a class for it.

9. **Early returns** — Guard clauses first, happy path last. No deep nesting.

10. **Named constants** — No magic numbers or strings. Use `private const`.

### Value Object Pattern (for data classes)
```php
class Thing
{
    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly ?string $description = null,
    ) {}

    // Derived data as methods
    public function isEmpty(): bool
    {
        return $this->count === 0;
    }
}
```

### Service Pattern (for logic classes)
```php
class ThingService
{
    public function __construct(
        private readonly Connection $db,
        private readonly Inspector $inspector,
    ) {}

    public function doSomething(Thing $thing): Result
    {
        // ...
    }
}
```

### Middleware Pattern
```php
class MyMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Before...
        $response = $next($request);
        // After...
        return $response;
    }
}
```

## Namespace Conventions

| Location | Namespace |
|----------|-----------|
| `framework/` | `Sauerkraut\` |
| `framework/Database/` | `Sauerkraut\Database\` |
| `framework/Database/Schema/` | `Sauerkraut\Database\Schema\` |
| `framework/View/` | `Sauerkraut\View\` |
| `framework/Http/` | `Sauerkraut\Http\` |
| `app/Controllers/` | `App\Controllers\` |
| `app/Middleware/` | `App\Middleware\` |
| `app/Cms/` | `App\Cms\` |
| `packages/sauerkraut-cms/src/` | `Sauerkraut\CMS\` |

## After Creating

- Register in the DI container (`App::singleton()`) if it's a shared service
- Add routes if it's a controller
- Update Obsidian docs if it introduces a new architectural concept
