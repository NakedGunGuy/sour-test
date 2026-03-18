# Views & Components

PHP-based templates with a component system and automatic asset bundling.

## Rendering Views

From a controller:

```php
return $this->view('posts/show', ['post' => $post]);
```

Views are PHP files in your app's pages directory (e.g., `frontend/pages/posts/show.php`).

## Layouts

Views are wrapped in a layout. The page content is injected as `$content`:

```php
<!-- frontend/pages/layout.php -->
<!DOCTYPE html>
<html>
<head>
    <style><?= \Sauerkraut\View\Component::inlineStyles() ?></style>
</head>
<body>
    <?= $content ?>
    <?= \Sauerkraut\View\Component::inlineScripts() ?>
</body>
</html>
```

## Components

Components are directories with a PHP template, optional CSS, and optional JS.

### Using Components

**Inline:**
```php
<?= component('button', ['variant' => 'primary'], 'Click Me') ?>
<?= component('badge', ['variant' => 'success'], 'Active') ?>
```

**Block (with slot content):**
```php
<?php open('card') ?>
    <h2>Card Title</h2>
    <p>Card content goes here.</p>
<?php close() ?>
```

### Creating Components

Create a directory in your components folder:

```
frontend/components/
  my-widget/
    my-widget.php     # Required — template
    my-widget.css     # Optional — styles
    my-widget.js      # Optional — scripts
```

`my-widget.php`:
```php
<?php
$variant = $variant ?? '';
$classes = ['my-widget'];
if ($variant) {
    $classes[] = "my-widget-{$variant}";
}
?>
<div class="<?= e(implode(' ', $classes)) ?>">
    <?= $slot ?>
</div>
```

`my-widget.css`:
```css
@layer components {
    .my-widget { /* styles */ }
    .my-widget-primary { /* variant */ }
}
```

### Sub-Components

Nest directories for sub-components:

```
table/
  table.php
  table.css
  head/
    head.php
  row/
    row.php
```

Usage: `component('table/head', [...])`, `component('table/row', [...])`

## Template Helpers

| Helper | Description |
|--------|-------------|
| `component($name, $props, $slot)` | Render a component |
| `open($name, $props)` / `close()` | Block syntax for components |
| `e($value)` | HTML escape |
| `config($key, $default)` | Read config |
| `route($name, $params)` | Generate named route URL |
| `url($path)` | Generate URL |
| `csrf_field()` | CSRF hidden input |
| `method_field($method)` | Method spoofing hidden input |
| `db()` | Database connection |
| `env($key, $default)` | Environment variable |
| `auth()` | Current user array |
| `auth_check()` | Is logged in? |
| `auth_id()` | User ID |
