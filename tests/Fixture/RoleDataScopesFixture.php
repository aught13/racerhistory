<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

class RoleDataScopesFixture extends TestFixture
{
    public string $table = 'role_data_scopes';

    public array $fields = [
        'id' => ['type' => 'integer', 'autoIncrement' => true],
        'role_id' => ['type' => 'integer', 'null' => false],
        'scope_type' => ['type' => 'string', 'length' => 16, 'null' => false],
        'sport_key' => ['type' => 'string', 'length' => 64, 'null' => true],
        'team_id' => ['type' => 'integer', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    /**
     * Initialize unrestricted legacy role grants.
     */
    public function init(): void
    {
        $this->tableAlias = 'RoleDataScopes';
        $this->_schema = new TableSchema('role_data_scopes', [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'role_id' => ['type' => 'integer', 'null' => false],
            'scope_type' => ['type' => 'string', 'length' => 16, 'null' => false],
            'sport_key' => ['type' => 'string', 'length' => 64, 'null' => true],
            'team_id' => ['type' => 'integer', 'null' => true],
        ]);
        $this->_schema->addConstraint('primary', [
            'type' => 'primary',
            'columns' => ['id'],
        ]);
        $this->records = [
            ['id' => 1, 'role_id' => 1, 'scope_type' => 'all'],
            ['id' => 2, 'role_id' => 2, 'scope_type' => 'all'],
            ['id' => 3, 'role_id' => 3, 'scope_type' => 'all'],
            ['id' => 4, 'role_id' => 4, 'scope_type' => 'all'],
        ];
    }
}
