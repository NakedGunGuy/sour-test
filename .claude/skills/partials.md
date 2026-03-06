# Partials

Partials are reusable PHP template fragments in `pages/partials/`. They are used both for initial page rendering and htmx responses.

## Rendering a partial

Always use `View::partial()` — never raw `include`. This gives each partial its own variable scope via `extract()` + `ob_start()`, preventing variable leakage.

```php
<?= \Sauerkraut\View\View::partial('partials/my-partial', ['key' => $value]) ?>
```

## Writing a partial

Place partials in `pages/partials/`. Props are available as local variables (passed via the second argument to `View::partial()`).

```php
<!-- pages/partials/user-row.php -->
<?php open('table/row', array_filter(['id' => "user-{$id}", 'target' => $target ?? null])) ?>
    <td><?= e($name) ?></td>
<?php close() ?>
```

## htmx pattern

The same partial should render both the initial page and the htmx response. Use an optional `$target` prop to control OOB wrapping:

- **Page render:** Call without `$target` — renders the component inline.
- **htmx endpoint:** Pass `$target` — the component wraps itself for OOB swap.

### Route example

```php
$router->get('/htmx/row', function (\Sauerkraut\Request $request) {
    $id = $request->query('id');

    return Response::html(\Sauerkraut\View\View::partial('partials/user-row', [
        'id' => $id,
        'name' => $request->query('name'),
        'target' => "#user-{$id}",
    ]));
});
```

### Page example

```php
<?php foreach ($users as $u): ?>
    <?= \Sauerkraut\View\View::partial('partials/user-row', ['id' => $u->id, 'name' => $u->name]) ?>
<?php endforeach; ?>
```

## Rules

1. **Always use `View::partial()`** — never `include` directly
2. **One source of truth** — don't duplicate partial markup in pages
3. **Use `$target ?? null`** in `array_filter()` so OOB props are only set when provided
4. **Escape output** — always use `e()` for user-facing data
