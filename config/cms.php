<?php

return [
    'hidden_tables' => [
        'sqlite_sequence',
        'post_tags',
    ],

    // Custom field types
    'field_types' => [
        'markdown' => \App\Cms\Fields\MarkdownField::class,
    ],

    // Per-table overrides
    'tables' => [
        'posts' => [
            'label' => 'Blog Posts',
            'controller' => \App\Cms\PostsController::class,
            'label_column' => 'title',
            'columns' => [
                'body' => ['label' => 'Content', 'type' => 'markdown'],
                'published' => ['label' => 'Published?', 'type' => 'boolean'],
            ],
        ],
    ],
];
