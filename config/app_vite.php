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
        // Default to built assets in non-dev-server environments (CI, e2e, prod).
        // Set VITE_FORCE_PRODUCTION_MODE=0 locally when using Vite dev server.
        'forceProductionMode' => filter_var((string)env('VITE_FORCE_PRODUCTION_MODE', '1'), FILTER_VALIDATE_BOOL),
    ],
];
