<?php
declare(strict_types=1);

return [
    'CakeVite' => [
        'devServer' => [
            'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
            'hostHints' => ['localhost', '127.0.0.1', '.local', '.test'],
            'entries' => [
                'script' => ['js/main.js'],
                'style' => [],
            ],
        ],
        'build' => [
            'manifestPath' => WWW_ROOT . 'dist' . DS . 'manifest.json',
            'outDirectory' => 'dist',
        ],
    ],
];
