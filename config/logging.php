<?php

return [
    'default' => 'app',

    'channels' => [
        'app' => [
            'path' => 'storage/logs',
            'level' => 'debug',
        ],

        'auth' => [
            'path' => 'storage/logs',
            'level' => 'info',
        ],

        'query' => [
            'path' => 'storage/logs',
            'level' => 'debug',
        ],

        'security' => [
            'path' => 'storage/logs',
            'level' => 'warning',
        ],
    ],
];
