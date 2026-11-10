<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateRoleDataScopes extends BaseMigration
{
    /**
     * Create or complete role data scope storage and preserve existing role access.
     */
    public function up(): void
    {
        $table = $this->table('role_data_scopes');
        if (!$this->hasTable('role_data_scopes')) {
            $table
                ->addColumn('role_id', 'integer', ['null' => false])
                ->addColumn('scope_type', 'string', ['limit' => 16, 'null' => false])
                ->addColumn('sport_key', 'string', ['limit' => 64, 'null' => true])
                ->addColumn('team_id', 'integer', ['null' => true, 'signed' => false])
                ->create();
        } else {
            $table
                ->changeColumn('team_id', 'integer', ['null' => true, 'signed' => false])
                ->update();
        }

        $table = $this->table('role_data_scopes');
        if (!$table->hasIndex(['role_id'])) {
            $table->addIndex(['role_id'])->update();
        }
        if (!$table->hasIndex(['team_id'])) {
            $table->addIndex(['team_id'])->update();
        }
        if (!$table->hasForeignKey('role_id')) {
            $table->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
        }
        if (!$table->hasForeignKey('team_id')) {
            $table->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
        }

        $this->execute(
            "INSERT INTO role_data_scopes (role_id, scope_type)
            SELECT roles.id, 'all'
            FROM roles
            LEFT JOIN (
                SELECT DISTINCT role_id FROM role_data_scopes
            ) AS existing_scopes ON existing_scopes.role_id = roles.id
            WHERE existing_scopes.role_id IS NULL",
        );
    }

    /**
     * Drop role data scope storage.
     */
    public function down(): void
    {
        if ($this->hasTable('role_data_scopes')) {
            $this->table('role_data_scopes')->drop()->save();
        }
    }
}
