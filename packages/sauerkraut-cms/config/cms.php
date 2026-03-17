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

    // Custom field types: 'type_name' => FieldType class
    // These are available to all tables. Set a column's type in the
    // tables config below, and the matching class handles rendering + casting.
    //
    // 'field_types' => [
    //     'markdown' => \App\Cms\Fields\MarkdownField::class,
    //     'color'    => \App\Cms\Fields\ColorField::class,
    // ],

    // Per-table overrides
    'tables' => [
        // Example:
        // 'posts' => [
        //     'label' => 'Blog Posts',
        //     'controller' => \App\Cms\PostsController::class,
        //     'label_column' => 'title',
        //     'hidden_columns' => ['slug'],
        //     'readonly_columns' => ['created_at'],
        //     'columns' => [
        //         'body' => ['label' => 'Content', 'type' => 'markdown'],
        //         'published' => ['label' => 'Published?', 'type' => 'boolean'],
        //     ],
        // ],
    ],
];
