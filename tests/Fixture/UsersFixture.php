<?php

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    // Define the schema directly instead of importing
    public array $schema = [];

    public function init(): void
    {
        $this->schema = [
            'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
            'username' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'email' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'first_name' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'last_name' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'password' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'token' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'token_expires' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'api_token' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'activation_date' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'tos_date' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'active' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => false, 'comment' => '', 'precision' => null],
            'is_superuser' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => false, 'comment' => '', 'precision' => null],
            'secret' => ['type' => 'string', 'length' => 32, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'secret_verified' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
            'additional_data' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
            'last_login' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
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
                'collation' => 'utf8mb4_general_ci',
            ],
        ];
        parent::init();
    }

    public array $records = [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'first_name' => null,
            'last_name' => null,
            'password' => '$2y$12$tzb5nIRATtKk8wrMqrD5u.6DXh5/vkq9XtnCpI67oJA5jYHuxhsMS', // 'password'
            'token' => null,
            'token_expires' => null,
            'api_token' => null,
            'activation_date' => '2025-01-01 00:00:00',
            'tos_date' => null,
            'active' => true,
            'is_superuser' => true,
            'secret' => null,
            'secret_verified' => null,
            'additional_data' => null,
            'last_login' => null,
            'role' => 'admin',
            'status' => 'active',
            'created' => '2025-01-01 00:00:00',
            'modified' => '2025-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'username' => 'user',
            'email' => 'user@example.com',
            'first_name' => null,
            'last_name' => null,
            'password' => '$2y$12$tzb5nIRATtKk8wrMqrD5u.6DXh5/vkq9XtnCpI67oJA5jYHuxhsMS', // 'password'
            'token' => null,
            'token_expires' => null,
            'api_token' => null,
            'activation_date' => null,
            'tos_date' => null,
            'active' => false,
            'is_superuser' => false,
            'secret' => null,
            'secret_verified' => null,
            'additional_data' => null,
            'last_login' => null,
            'role' => 'user',
            'status' => 'inactive',
            'created' => '2025-01-01 00:00:00',
            'modified' => '2025-01-01 00:00:00',
        ],
    ];
}
