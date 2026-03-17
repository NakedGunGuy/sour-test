---
name: frontend-components
description: How to create and use Sauerkraut frontend components, pages, and layouts. Covers the component system, sub-components, CSS rules, design tokens, theming, and htmx integration. Use when building UI, creating components, writing page templates, or styling anything.
user-invocable: false
---

# Sauerkraut Frontend Components

## Architecture Overview

Zero-dependency frontend: pure PHP templates + modern CSS. No JS frameworks, no bundlers, no build step. Components auto-register from vendor packages and can be overridden at the project level. Styles are collected automatically — either inlined or served as bundled link/script tags depending on config.

### File Structure

```
packages/sauerkraut-ui/components/   <- vendor components (installed via composer)
  button/
    button.php          <- PHP template (required)
    button.css          <- Scoped stylesheet (optional, auto-collected)
    icon/               <- sub-component directory
      icon.php          <- registered as "button/icon"
      icon.css
  table/
    table.php           <- registered as "table"
    table.css
    head/
      head.php          <- sub-component: "table/head"
    row/
      row.php           <- sub-component: "table/row"

frontend/components/                  <- project-level overrides (same structure, higher priority)

frontend/pages/
  layout.php            <- Default layout wrapper
  home.php              <- Page template
  partials/
    htmx-row.php        <- Reusable partial for pages + htmx responses

theme/
  layers.css            <- @layer order: reset, tokens, base, components, utilities
  reset.css             <- CSS reset
  tokens.css            <- Design tokens (colors, spacing, typography)
```

### Package system

Components ship in Composer packages (e.g. `sauerkraut/ui`). The framework discovers packages via `extra.sauerkraut` in `composer.json` and auto-registers their components, pages, CSS, and routes. Project-level files always override vendor equivalents.

Packages can namespace components with a prefix (e.g. `cms:button` for the CMS package). Components belong to a group (`frontend`, `cms`, etc.) which controls which CSS gets included in which app's layout.

## Creating a Component

### 1. Create the directory and files

```
frontend/components/{name}/{name}.php   <- required (project override)
frontend/components/{name}/{name}.css   <- optional
```

Or for a vendor package:
```
packages/{pkg}/components/{name}/{name}.php
packages/{pkg}/components/{name}/{name}.css
```

Components auto-register at boot via `App::bootstrapView()`. If a directory contains `{name}.php` it's a component; otherwise it's a namespace that recurses deeper.

### 2. Sub-components

A directory inside a component directory creates a sub-component:

```
components/button/
  button.php            <- "button"
  button.css
  icon/
    icon.php            <- "button/icon"
    icon.css            <- auto-collected with parent
```

Render with `component('button/icon', [...])` or `open('button/icon')`. The parent's CSS is always included when a sub-component is used. Sub-components can have their own CSS too.

### 3. Write the PHP template

Component templates receive `$props` as extracted variables plus an optional `$slot` for nested content.

**Simple component (no slot):**
```php
<?php
// components/badge/badge.php
$variant = $variant ?? 'default';
?>
<span class="badge badge-<?= e($variant) ?>"><?= e($label) ?></span>
```

**Component with slot:**
```php
<?php
// components/card/card.php
?>
<article class="card">
    <?= $slot ?>
</article>
```

Rules:
- Always escape output with `e()` (htmlspecialchars wrapper)
- Use `??` to default optional props — never assume a prop exists
- `$slot` contains pre-rendered HTML — echo it raw (not through `e()`)
- Keep templates minimal — logic belongs in controllers

### 4. Write the CSS

**All component CSS MUST be wrapped in `@layer components`:**

```css
@layer components {
    .card {
        background: var(--surface-1);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
    }
}
```

The layer is mandatory — it prevents specificity wars with reset, tokens, and base styles. The layer order is: `reset, tokens, base, components, utilities`.

## Using Components

### Three ways to render

**1. Inline render** — for components without complex slot content:
```php
<?= component('badge', ['label' => 'New', 'variant' => 'primary']) ?>
<?= component('button', ['variant' => 'primary', 'type' => 'submit'], 'Save') ?>
```

**2. Slot buffering** — for components wrapping rich HTML:
```php
<?php open('card') ?>
    <div class="card-body">
        <h2 class="card-title"><?= e($title) ?></h2>
        <p class="card-description"><?= e($description) ?></p>
    </div>
<?php close() ?>
```

**3. With props and slot:**
```php
<?php open('table/row', ['id' => "user-{$id}"]) ?>
    <td><?= e($name) ?></td>
    <td><?= e($role) ?></td>
<?php close() ?>
```

### Available helpers (from `framework/View/helpers.php`)

| Helper | Purpose |
|--------|---------|
| `component($name, $props, $slot)` | Render a component |
| `open($name, $props)` / `close()` | Buffered slot rendering |
| `e($string)` | HTML-escape |
| `config($key, $default)` | Read config value (checks current app config, then app.php) |
| `route($name, $params)` | Generate named route URL |
| `url($path)` | Ensure leading slash |
| `csrf_token()` / `csrf_field()` | CSRF protection |
| `method_field($method)` | Hidden input for PUT/DELETE form spoofing |
| `theme_css($file)` | Read a CSS file from the theme directory |
| `cms_css()` | Get the CSS for the CMS app |

## Pages and Layouts

### Page templates

Pages live in `frontend/pages/` and are rendered via `View::render('page-name', $data, $layout)`:

```php
// In a controller:
return View::render('home', ['title' => 'Home']);
// Uses frontend/pages/home.php with frontend/pages/layout.php
```

The page template receives `$data` as extracted variables. It renders first (so components queue their CSS), then the layout wraps it with `$content`.

### Layout templates

Layouts are also in `frontend/pages/`. They receive `$content` (the rendered page) and any data passed through. They MUST include the CSS/JS pipeline:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        let t = localStorage.getItem('theme')
            || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = t;
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sauerkraut') ?></title>

    <style>
        <?= theme_css('layers.css') ?>
        <?= theme_css('reset.css') ?>
        <?= theme_css('tokens.css') ?>
        <?= \Sauerkraut\View\Component::inlineStyles() ?>
    </style>
    <?= \Sauerkraut\View\Component::linkTags() ?>
    <?= \Sauerkraut\View\Component::scriptTags() ?>
    <?php $inlineJs = \Sauerkraut\View\Component::inlineScripts(); if ($inlineJs): ?>
    <script><?= $inlineJs ?></script>
    <?php endif; ?>
    <script src="https://unpkg.com/htmx.org@2.0.4" defer></script>
    <script src="https://unpkg.com/htmx-ext-head-support@2.0.3" defer></script>
</head>
<body hx-ext="head-support">
    <?= $content ?>
</body>
</html>
```

Asset mode (`config('assets')`) controls output:
- `'inline'` — `inlineStyles()` / `inlineScripts()` embed CSS/JS directly in the page
- `'link'` — `linkTags()` / `scriptTags()` output `<link>` / `<script>` tags that hit bundling routes

### Partials and htmx

Partials are reusable PHP template fragments in `frontend/pages/partials/`. Use `View::partial()` — never raw `include`. This gives each partial its own variable scope.

```php
<?= \Sauerkraut\View\View::partial('partials/my-partial', ['key' => $value]) ?>
```

The same partial renders both the initial page and htmx responses. Use an optional `$target` prop for OOB wrapping:

```php
<!-- frontend/pages/partials/htmx-row.php -->
<?php open('table/row', array_filter(['id' => "user-{$id}", 'target' => $target ?? null])) ?>
    <td><?= e($name) ?></td>
<?php close() ?>
```

**Page render:** Call without `$target` — renders inline.
**htmx endpoint:** Pass `$target` — the component wraps itself for OOB swap.

```php
// Route
$router->get('/htmx/row', function (\Sauerkraut\Request $request) {
    $id = $request->query('id');
    return Response::html(\Sauerkraut\View\View::partial('partials/htmx-row', [
        'id' => $id,
        'name' => $request->query('name'),
        'target' => "#user-{$id}",
    ]));
});
```

When `View::partial()` detects new component CSS/JS was queued, it wraps the response in `<html><head>...</head><body>...</body></html>` so htmx head-support can merge the styles.

## CSS Rules

### MUST follow

1. **All component CSS in `@layer components { ... }`** — no exceptions
2. **Use design tokens** — never hardcode colors, spacing, font sizes, or radii
3. **Use `light-dark()` for any new color values** — automatic dark mode support
4. **Use `var(--token)` not raw values** — `var(--space-md)` not `1rem`
5. **No `!important`** — the layer system handles specificity
6. **No CSS-in-JS, no Tailwind, no utility frameworks** — pure CSS only

### Design Tokens Reference

```css
/* Surfaces and text */
--surface-0    /* page background */
--surface-1    /* card/panel background */
--surface-2    /* hover/elevated background */
--text-0       /* primary text */
--text-1       /* secondary text */
--text-2       /* muted/tertiary text */

/* Brand colors */
--primary          /* primary action color */
--primary-soft     /* primary tinted background */
--primary-text     /* text on primary-soft background */
--danger           /* destructive action color */
--success          /* success state color */
--warning          /* warning state color */

/* Borders and shadows */
--border           /* default border color */
--border-focus     /* focus ring color (= --primary) */
--shadow-sm        /* subtle shadow */
--shadow-md        /* elevated shadow */

/* Spacing scale */
--space-xs: 0.25rem
--space-sm: 0.5rem
--space-md: 1rem
--space-lg: 1.5rem
--space-xl: 2.5rem

/* Border radius */
--radius-sm: 0.25rem
--radius-md: 0.5rem
--radius-lg: 0.75rem
--radius-full: 9999px

/* Typography */
--font-sans    /* system-ui stack */
--font-mono    /* monospace stack */
--text-sm: 0.875rem
--text-base: 1rem
--text-lg: 1.125rem
--text-xl: 1.25rem
--text-2xl: 1.5rem
--text-3xl: 2rem

/* Motion */
--ease-out: cubic-bezier(0.16, 1, 0.3, 1)
--duration: 0.15s
```

### Dark mode

Dark mode uses `color-scheme: light dark` and the `light-dark()` CSS function. Users override with `data-theme="light"` or `data-theme="dark"` on `<html>`. A script in the layout reads the preference from localStorage before paint.

When you need a custom color, always use `light-dark()`:

```css
/* CORRECT */
background: light-dark(hsl(145 60% 94%), hsl(145 30% 14%));

/* WRONG — breaks in dark mode */
background: hsl(145 60% 94%);
```

### CSS patterns to use

**Nesting** (native CSS nesting, no preprocessor):
```css
.nav {
    display: flex;
    .nav-link {
        color: var(--text-1);
        &:hover { color: var(--text-0); }
        &.active { color: var(--primary-text); }
    }
}
```

**Container queries** for responsive components:
```css
.card {
    container-type: inline-size;
    @container (min-width: 500px) {
        display: grid;
        grid-template-columns: 250px 1fr;
    }
}
```

**Transitions** — use tokens for consistent timing:
```css
transition: all var(--duration) var(--ease-out);
```

### Inline styles in templates

For one-off layout that doesn't warrant a CSS class, inline styles using tokens are acceptable in page templates (not components):

```php
<div style="display: flex; gap: var(--space-md); padding: var(--space-lg);">
```

Component templates should use CSS classes from their `.css` file instead.

## Existing Components (sauerkraut-ui)

| Component | Props | Slot | Purpose |
|-----------|-------|------|---------|
| `button` | `variant` (primary/danger/ghost), `type` | Button label | Standard button |
| `button/icon` | `icon`, `variant` | Button label | Button with icon prefix |
| `card` | — | Card content | Content card with hover shadow |
| `nav` | — | Nav items | Top navigation bar |
| `theme-toggle` | — | — | Light/dark mode toggle |
| `alert` | `type` (success/error), `dismissable` | Message text | Flash message display |
| `form-field` | `field` (Field object), `value`, `error` | — | Auto-generated form input |
| `media-grid` | — | Grid items | CSS grid for media cards |
| `table` | `id`, `class` | `<thead>` + `<tbody>` | Data table wrapper |
| `table/head` | — | `<th>` cells | Table header row |
| `table/row` | `id`, `target`, `swap` | `<td>` cells | Table row with OOB swap support |

## Checklist for New Components

1. Create `frontend/components/{name}/{name}.php` (project) or `packages/{pkg}/components/{name}/{name}.php` (vendor)
2. Create matching `.css` file with styles inside `@layer components { ... }`
3. Use design tokens — no hardcoded values
4. Use `light-dark()` for any custom colors
5. Default all optional props with `??`
6. Escape all dynamic text with `e()`
7. Raw-echo `$slot` (it's pre-rendered HTML)
8. Test in both light and dark mode
