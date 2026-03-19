<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * AddCakeDcUsersFields migration
 *
 * Adds additional fields required by CakeDC/Users plugin to the existing users table.
 * Converts 'status' field to 'active' boolean and adds other optional fields.
 */
class AddCakeDcUsersFields extends BaseMigration
{
    /**
     * Disable automatic id field
     */
    public bool $autoId = false;

    public function up(): void
    {
        $table = $this->table('users');

        // Add new columns required by CakeDC/Users
        $table
            ->addColumn('first_name', 'string', [
                'after' => 'email',
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('last_name', 'string', [
                'after' => 'first_name',
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('token', 'string', [
                'after' => 'password',
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('token_expires', 'datetime', [
                'after' => 'token',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('api_token', 'string', [
                'after' => 'token_expires',
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('activation_date', 'datetime', [
                'after' => 'api_token',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('tos_date', 'datetime', [
                'after' => 'activation_date',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'after' => 'tos_date',
                'default' => false,
                'null' => false,
            ])
            ->addColumn('is_superuser', 'boolean', [
                'after' => 'active',
                'default' => false,
                'null' => false,
            ])
            ->addColumn('secret', 'string', [
                'after' => 'is_superuser',
                'default' => null,
                'limit' => 32,
                'null' => true,
            ])
            ->addColumn('secret_verified', 'boolean', [
                'after' => 'secret',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('additional_data', 'text', [
                'after' => 'secret_verified',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('last_login', 'datetime', [
                'after' => 'additional_data',
                'default' => null,
                'null' => true,
            ])
            ->update();

        // Migrate existing 'status' data to 'active' field
        // status='active' -> active=1, status='inactive' -> active=0
        $this->execute("UPDATE users SET active = CASE WHEN status = 'active' THEN 1 ELSE 0 END");

        // Set is_superuser based on role
        $this->execute("UPDATE users SET is_superuser = CASE WHEN role = 'admin' THEN 1 ELSE 0 END");

        // Set activation_date for already active users
        $this->execute("UPDATE users SET activation_date = created WHERE status = 'active'");
    }

    public function down(): void
    {
        $table = $this->table('users');

        // Remove columns added by this migration
        $table
            ->removeColumn('first_name')
            ->removeColumn('last_name')
            ->removeColumn('token')
            ->removeColumn('token_expires')
            ->removeColumn('api_token')
            ->removeColumn('activation_date')
            ->removeColumn('tos_date')
            ->removeColumn('active')
            ->removeColumn('is_superuser')
            ->removeColumn('secret')
            ->removeColumn('secret_verified')
            ->removeColumn('additional_data')
            ->removeColumn('last_login')
            ->update();
    }
}
