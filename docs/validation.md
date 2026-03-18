# Validation

Validate data with composable rule objects.

## Basic Usage

```php
use Sauerkraut\Validation\{Validator, Rules};

$result = Validator::validate($request->input(), [
    'name'  => [Rules::required(), Rules::string(), Rules::max(255)],
    'email' => [Rules::required(), Rules::email()],
    'age'   => [Rules::required(), Rules::integer(), Rules::between(18, 120)],
]);

if ($result->failed()) {
    // $result->errors() — all errors: ['field' => ['message', ...]]
    // $result->error('email') — first error for a field
    return $this->redirect('/form');
}

$data = $result->validated(); // Only validated fields, no extras
```

## Available Rules

| Factory Method | Description |
|---------------|-------------|
| `Rules::required()` | Must not be null, empty string, or empty array |
| `Rules::string()` | Must be a string |
| `Rules::integer()` | Must be an integer |
| `Rules::email()` | Must be a valid email |
| `Rules::min(3)` | String: min length. Number: min value |
| `Rules::max(255)` | String: max length. Number: max value |
| `Rules::between(1, 100)` | Number must be in range |
| `Rules::in(['a', 'b'])` | Must be one of the allowed values |
| `Rules::confirmed()` | Field `{name}_confirmation` must match |
| `Rules::nullable()` | Allow null/empty (skips other rules) |
| `Rules::boolean()` | Must be true, false, 1, 0, '1', or '0' |
| `Rules::date()` | Must be a parseable date string |
| `Rules::url()` | Must be a valid URL |
| `Rules::regex('/^\d+$/')` | Must match the pattern |
| `Rules::alpha()` | Must contain only letters |
| `Rules::alphaNum()` | Must contain only letters and numbers |
| `Rules::unique($db, 'users', 'email')` | Must not exist in the database |

## Unique Rule with Exceptions

For updates, exclude the current record:

```php
Rules::unique($db, 'users', 'email', except: $userId)
```

## Custom Rules

Implement the `Rule` interface:

```php
use Sauerkraut\Validation\Rule;

readonly class Slug implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value) || !preg_match('/^[a-z0-9-]+$/', $value)) {
            return "{$field} must be a valid slug.";
        }

        return null;
    }
}

// Usage
$rules = ['slug' => [Rules::required(), new Slug()]];
```

## ValidationResult

| Method | Returns | Description |
|--------|---------|-------------|
| `passed()` | `bool` | True if no errors |
| `failed()` | `bool` | True if has errors |
| `errors()` | `array` | All errors: `['field' => ['msg', ...]]` |
| `error($field)` | `?string` | First error for a field |
| `validated()` | `array` | Only the fields that had rules and passed |
