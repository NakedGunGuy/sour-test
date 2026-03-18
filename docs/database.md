# Database

Raw PDO wrapper supporting SQLite, MySQL, and PostgreSQL. No ORM — just clean SQL with prepared statements.

## Configuration

`config/database.php`:

```php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../storage/database.sqlite',
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'sauerkraut',
            'username' => 'root',
            'password' => '',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'sauerkraut',
            'username' => 'postgres',
            'password' => '',
        ],
    ],
];
```

## Querying

```php
$db = $this->app->db();

// Single row
$user = $db->queryOne('SELECT * FROM users WHERE id = ?', [42]);

// Multiple rows
$posts = $db->queryAll('SELECT * FROM posts WHERE published = ? ORDER BY id DESC', [1]);

// Insert/Update/Delete (returns affected row count)
$db->execute('INSERT INTO posts (title, body) VALUES (?, ?)', ['Hello', 'World']);
$db->execute('UPDATE posts SET title = ? WHERE id = ?', ['Updated', 42]);
$db->execute('DELETE FROM posts WHERE id = ?', [42]);

// Last insert ID
$id = $db->lastInsertId();
```

## Transactions

```php
$db->transaction(function () use ($db) {
    $db->execute('INSERT INTO orders (user_id, total) VALUES (?, ?)', [1, 99.99]);
    $orderId = $db->lastInsertId();
    $db->execute('INSERT INTO order_items (order_id, product_id) VALUES (?, ?)', [$orderId, 5]);
});
// Automatically commits on success, rolls back on exception
```

## Schema Inspection

```php
$inspector = $this->app->make(Inspector::class);

$tableNames = $inspector->tableNames();        // ['users', 'posts', ...]
$table = $inspector->table('posts');            // Table value object
$table->primaryKeyName();                       // 'id'
$table->column('title');                        // Column value object
$table->foreignKeyFor('category_id');           // ForeignKey or null
```

## View Helper

```php
// In templates
<?php $posts = db()->queryAll('SELECT * FROM posts'); ?>
```
