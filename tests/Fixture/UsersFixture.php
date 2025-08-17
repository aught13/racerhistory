<?php

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    public $import = ['table' => 'users'];
    public array $records = [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG', // 'password' hashed
            'role' => 'admin',
            'status' => 'active',
            'created' => '2025-01-01 00:00:00',
            'modified' => '2025-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'username' => 'user',
            'email' => 'user@example.com',
            'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG', // 'password' hashed
            'role' => 'user',
            'status' => 'inactive',
            'created' => '2025-01-01 00:00:00',
            'modified' => '2025-01-01 00:00:00',
        ],
    ];
}
