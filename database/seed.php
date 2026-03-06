<?php

require __DIR__ . '/../vendor/autoload.php';

$app = Sauerkraut\App::boot(dirname(__DIR__));
$db = $app->db();

// Load schema
$db->pdo()->exec(file_get_contents(__DIR__ . '/schema.sql'));
echo "Schema loaded.\n";

// Seed some data
$db->execute("INSERT OR IGNORE INTO categories (name, slug) VALUES (?, ?)", ['Technology', 'technology']);
$db->execute("INSERT OR IGNORE INTO categories (name, slug) VALUES (?, ?)", ['Design', 'design']);
$db->execute("INSERT OR IGNORE INTO categories (name, slug) VALUES (?, ?)", ['Business', 'business']);

$db->execute("INSERT OR IGNORE INTO tags (name) VALUES (?)", ['php']);
$db->execute("INSERT OR IGNORE INTO tags (name) VALUES (?)", ['css']);
$db->execute("INSERT OR IGNORE INTO tags (name) VALUES (?)", ['htmx']);
$db->execute("INSERT OR IGNORE INTO tags (name) VALUES (?)", ['javascript']);

$existing = $db->queryOne("SELECT COUNT(*) as cnt FROM posts");
if ($existing['cnt'] == 0) {
    $db->execute(
        "INSERT INTO posts (title, slug, body, category_id, published) VALUES (?, ?, ?, ?, ?)",
        ['Getting Started with Sauerkraut', 'getting-started', 'A quick intro to the framework.', 1, 1]
    );
    $db->execute(
        "INSERT INTO posts (title, slug, body, category_id, published) VALUES (?, ?, ?, ?, ?)",
        ['Design Tokens in CSS', 'design-tokens', 'How to use HSL-based design tokens for theming.', 2, 1]
    );
    $db->execute(
        "INSERT INTO posts (title, slug, body, category_id, published) VALUES (?, ?, ?, ?, ?)",
        ['HTMX Patterns', 'htmx-patterns', 'Common patterns for htmx with server-rendered partials.', 1, 0]
    );

    // Tag some posts
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [1, 1]);
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [1, 3]);
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [2, 2]);
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [3, 1]);
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [3, 3]);
    $db->execute("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [3, 4]);
}

echo "Seed complete.\n";
