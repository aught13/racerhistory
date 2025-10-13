<?php

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    // Define the schema directly instead of importing
    public function init(): void
    {
        $this->schema = [
            'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
            'username' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'email' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'password' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'role' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'user', 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'status' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'inactive', 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
                'username' => ['type' => 'unique', 'columns' => ['username'], 'length' => []],
                'email' => ['type' => 'unique', 'columns' => ['email'], 'length' => []],
            ],
            '_options' => [
                'engine' => 'InnoDB',
                'collation' => 'utf8mb4_general_ci'
            ],
        ];
        parent::init();
    }

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
