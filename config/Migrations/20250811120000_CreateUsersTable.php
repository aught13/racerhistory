<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateUsersTable migration
 *
 * Adds the minimal users table needed by the application & tests.
 * Uses adapter-aware datetime defaults for SQLite vs MySQL and includes
 * unique constraints on username & email.
 */
class CreateUsersTable extends BaseMigration
{
    /**
     * Disable automatic id field; we add primary key manually.
     */
    public bool $autoId = false;

    public function up(): void
    {
        $driver = $this->getAdapter()->getAdapterType();
        $table = $this->table('users');
        if ($table->exists()) {
            return; // Idempotency safeguard for test reruns
        }

        $table
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('username', 'string', [
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('email', 'string', [
                'limit' => 190, // room for longer emails + unique idx
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'user',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'inactive',
            ])
            ->addColumn('created', $driver === 'sqlite' ? 'text' : 'datetime', [
                'null' => false,
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
            ])
            ->addColumn('modified', $driver === 'sqlite' ? 'text' : 'datetime', [
                'null' => false,
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
            ])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('users')) {
            $this->table('users')->drop()->save();
        }
    }
}
