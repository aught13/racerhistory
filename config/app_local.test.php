<?php

/**
 * Test-specific configuration
 * This file is used during automated testing
 */

return [
    'debug' => true,

    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => '127.0.0.1',
            'username' => 'test_user',
            'password' => 'test_password',
            'database' => 'racerhistory_test',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'flags' => [],
            'cacheMetadata' => true,
            'log' => false,
            'url' => env('DATABASE_TEST_URL', null),
        ],
        'test' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => '127.0.0.1',
            'username' => 'test_user',
            'password' => 'test_password',
            'database' => 'racerhistory_test',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'className' => 'Debug'
        ],
    ],

    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => 'you@localhost',
        ],
    ],

    'Security' => [
        'salt' => 'test_security_salt_for_testing_only_12345',
    ],
];
