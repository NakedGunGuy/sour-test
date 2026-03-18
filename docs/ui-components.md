# UI Components

The `sauerkraut/ui` package provides 19 ready-to-use components.

## Basic Components

### Button
```php
<?= component('button', ['variant' => 'primary'], 'Save') ?>
<?= component('button', ['variant' => 'danger', 'type' => 'submit'], 'Delete') ?>
<?= component('button/icon', ['icon' => '★', 'variant' => 'ghost'], 'Favorite') ?>
```
Variants: `primary`, `danger`, `ghost`

### Badge
```php
<?= component('badge', ['variant' => 'success'], 'Active') ?>
<?= component('badge', ['variant' => 'warning'], 'Pending') ?>
```
Variants: `primary`, `success`, `danger`, `warning`

### Alert
```php
<?php open('alert', ['variant' => 'success']) ?>
    Operation completed successfully.
<?php close() ?>
```

### Avatar
```php
<?= component('avatar', ['name' => 'John Doe']) ?>
<?= component('avatar', ['src' => '/img/john.jpg', 'name' => 'John', 'size' => 'lg']) ?>
```
Sizes: `sm`, `lg`, `xl`

### Progress
```php
<?= component('progress', ['value' => 75, 'max' => 100, 'label' => true]) ?>
<?= component('progress', ['value' => 30, 'variant' => 'danger']) ?>
```
Variants: `success`, `danger`, `warning`

### Skeleton
```php
<?= component('skeleton', ['variant' => 'heading']) ?>
<?= component('skeleton', ['variant' => 'text']) ?>
<?= component('skeleton', ['variant' => 'circle']) ?>
<?= component('skeleton', ['variant' => 'image', 'height' => '200px']) ?>
```
Variants: `text`, `heading`, `circle`, `card`, `image`

## Layout Components

### Card
```php
<?php open('card') ?>
    <div class="card-body">
        <h3 class="card-title">Title</h3>
        <p>Content</p>
    </div>
<?php close() ?>
```

### Table
```php
<?php open('table') ?>
    <?php open('table/head') ?>
        <th>Name</th><th>Email</th>
    <?php close() ?>
    <?php foreach ($users as $user): ?>
        <?php open('table/row') ?>
            <td><?= e($user['name']) ?></td>
            <td><?= e($user['email']) ?></td>
        <?php close() ?>
    <?php endforeach; ?>
<?php close() ?>
```

### Nav
```php
<?php open('nav') ?>
    <a href="/" class="nav-link">Home</a>
    <a href="/about" class="nav-link">About</a>
<?php close() ?>
```

## Interactive Components

### Modal
```php
<button onclick="openModal('my-modal')">Open</button>

<?php open('modal', ['id' => 'my-modal', 'title' => 'Confirm', 'size' => 'sm']) ?>
    <p>Are you sure?</p>
<?php close() ?>
```
Sizes: `sm`, `lg`, `xl`. Closes on overlay click or Escape key.

### Dropdown
```php
<?php open('dropdown', ['label' => 'Actions', 'align' => 'right']) ?>
    <a href="#" class="dropdown-item">Edit</a>
    <a href="#" class="dropdown-item">Delete</a>
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item">Archive</a>
<?php close() ?>
```

### Tabs
```php
<?php open('tabs') ?>
    <?php open('tabs/tab', ['label' => 'Overview', 'index' => 0]) ?>
        <p>Overview content</p>
    <?php close() ?>
    <?php open('tabs/tab', ['label' => 'Details', 'index' => 1]) ?>
        <p>Details content</p>
    <?php close() ?>
<?php close() ?>
```

### Accordion
```php
<?php open('accordion') ?>
    <?php open('accordion/item', ['title' => 'Section 1', 'open' => true]) ?>
        <p>Content for section 1</p>
    <?php close() ?>
    <?php open('accordion/item', ['title' => 'Section 2']) ?>
        <p>Content for section 2</p>
    <?php close() ?>
<?php close() ?>
```
Set `multiple` prop to allow multiple panels open simultaneously.

### Toast
```php
<!-- Server-rendered toast -->
<?php open('toast', ['variant' => 'success', 'duration' => 5000]) ?>
    Record saved successfully.
<?php close() ?>

<!-- JavaScript API -->
<script>
showToast('Hello!', 'info', 3000);
showToast('Error!', 'error');
</script>
```
Variants: `info`, `success`, `error`, `warning`

## Navigation Components

### Breadcrumb
```php
<?= component('breadcrumb', ['items' => [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Posts', 'url' => '/posts'],
    ['label' => 'Current Post'],
]]) ?>
```

### Pagination
```php
<?= component('pagination', ['page' => $page, 'total' => $totalPages, 'url' => '/posts?page=']) ?>
```

## Other

### Form Field
Used by the CMS. Renders labels, inputs, validation errors for different field types.

### Theme Toggle
Dark/light mode switch.

### Media Grid
Responsive image/media grid.
