# Testing

PHPUnit 12.5 test suite.

## Running Tests

```bash
php sauerkraut test
# or
vendor/bin/phpunit
```

## Test Structure

```
tests/
  Unit/              # Unit tests (isolated, no side effects)
    Auth/
    Cache/
    Config/
    Console/
    Database/
    Event/
    Http/
    Log/
    Mail/
    Validation/
    PipelineTest.php
    RequestTest.php
    ResponseTest.php
    RouterTest.php
  Feature/           # Feature/integration tests
```

## Writing Tests

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomethingWorks(): void
    {
        $result = 1 + 1;

        $this->assertSame(2, $result);
    }
}
```

## Testing with In-Memory Database

For database tests, use an in-memory SQLite connection:

```php
protected function setUp(): void
{
    $this->db = Connection::fromConfig([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $this->db->execute('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
}
```

## Test Conventions

- Test class names end with `Test`
- Test method names start with `test`
- One assertion concept per test
- Use `setUp()` for shared setup, `tearDown()` for cleanup
- Place tests in the namespace matching the source: `Tests\Unit\Validation\` for `Sauerkraut\Validation\`

## Configuration

`phpunit.xml` at the project root defines test suites and coverage source.
