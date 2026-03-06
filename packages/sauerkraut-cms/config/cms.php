<?php

return [
    // Override theme directory (relative to project root).
    // Falls back to app.theme if not set.
    // 'theme' => 'theme',

    // Override asset mode: 'inline' or 'link'.
    // Falls back to app.assets if not set.
    // 'assets' => 'inline',

    // Tables excluded from the CMS entirely
    'hidden_tables' => [
        'sqlite_sequence',
    ],

    // Per-table overrides
    'tables' => [
        // Example:
        // 'posts' => [
        //     'label' => 'Blog Posts',
        //     'label_column' => 'title',
        //     'hidden_columns' => ['slug'],
        //     'readonly_columns' => ['created_at'],
        //     'columns' => [
        //         'body' => ['label' => 'Content', 'type' => 'richtext'],
        //         'published' => ['label' => 'Published?', 'type' => 'boolean'],
        //     ],
        // ],
    ],
];
