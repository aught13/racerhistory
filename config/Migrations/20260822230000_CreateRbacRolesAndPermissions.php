<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateRbacRolesAndPermissions extends BaseMigration
{
    public function up(): void
    {
        $roles = $this->table('roles');
        if (!$this->hasTable('roles')) {
            $roles
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addColumn('modified', 'datetime', ['null' => false])
                ->addIndex(['name'], ['unique' => true])
                ->create();
        }

        $permissions = $this->table('permissions');
        if (!$this->hasTable('permissions')) {
            $permissions
                ->addColumn('role_id', 'integer', ['null' => false])
                ->addColumn('model_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('can_create', 'boolean', ['default' => false, 'null' => false])
                ->addColumn('can_read', 'string', ['limit' => 10, 'default' => 'none', 'null' => false])
                ->addColumn('can_update', 'string', ['limit' => 10, 'default' => 'none', 'null' => false])
                ->addColumn('can_delete', 'string', ['limit' => 10, 'default' => 'none', 'null' => false])
                ->addColumn('custom_rules', 'json', ['null' => true])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addColumn('modified', 'datetime', ['null' => false])
                ->addIndex(['role_id', 'model_name'], ['unique' => true])
                ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        } else {
            $permissions->changeColumn('role_id', 'integer', ['null' => false]);

            if (!$permissions->hasColumn('modified')) {
                $permissions->addColumn('modified', 'datetime', ['null' => false, 'after' => 'created']);
            }

            $permissions->update();

            if (!$permissions->hasIndex(['role_id', 'model_name'])) {
                $permissions->addIndex(['role_id', 'model_name'], ['unique' => true])->update();
            }

            if (!$permissions->hasForeignKey('role_id')) {
                $permissions->addForeignKey('role_id', 'roles', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])->update();
            }
        }

        $users = $this->table('users');
        if (!$users->hasColumn('role_id')) {
            $users
                ->addColumn('role_id', 'integer', ['null' => true, 'after' => 'role'])
                ->addIndex(['role_id'])
                ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->update();
        } else {
            $users->changeColumn('role_id', 'integer', ['null' => true])->update();

            if (!$users->hasIndex(['role_id'])) {
                $users->addIndex(['role_id'])->update();
            }

            if (!$users->hasForeignKey('role_id')) {
                $users->addForeignKey('role_id', 'roles', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])->update();
            }
        }

        $now = date('Y-m-d H:i:s');

        $this->execute(sprintf(
            "INSERT INTO roles (id, name, created, modified) VALUES "
            . "(1, 'Admin', '%s', '%s'), "
            . "(2, 'Blogger', '%s', '%s'), "
            . "(3, 'Editor', '%s', '%s'), "
            . "(4, 'Contributor', '%s', '%s') "
            . "ON DUPLICATE KEY UPDATE name = VALUES(name), modified = VALUES(modified)",
            $now,
            $now,
            $now,
            $now,
            $now,
            $now,
            $now,
            $now,
        ));

        $this->execute("UPDATE users SET role_id = 1 WHERE LOWER(role) = 'admin'");
        $this->execute("UPDATE users SET role_id = 2 WHERE LOWER(role) IN ('author', 'blogger')");
        $this->execute("UPDATE users SET role_id = 3 WHERE LOWER(role) = 'editor'");
        $this->execute("UPDATE users SET role_id = 4 WHERE LOWER(role) = 'contributor'");

        $models = [
            'BlogPosts',
            'Images',
            'Games',
            'TeamSeasons',
            'TeamSeasonRosters',
            'Persons',
            'Opponents',
            'Places',
            'Sites',
            'GameTypes',
            'Teams',
            'Seasons',
            'Users',
            'SiteOptions',
            'Roles',
        ];

        $rows = [];
        foreach ([1, 2, 3, 4] as $roleId) {
            foreach ($models as $modelName) {
                $rows[] = $this->buildPermissionRow($roleId, $modelName, $now);
            }
        }

        $this->execute('DELETE FROM permissions WHERE role_id IN (1, 2, 3, 4)');
        $this->table('permissions')->insert($rows)->saveData();
    }

    public function down(): void
    {
        if ($this->hasTable('permissions')) {
            $this->table('permissions')->drop()->save();
        }

        $users = $this->table('users');
        if ($users->hasColumn('role_id')) {
            $users->dropForeignKey('role_id')->removeIndex(['role_id'])->removeColumn('role_id')->update();
        }

        if ($this->hasTable('roles')) {
            $this->table('roles')->drop()->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPermissionRow(int $roleId, string $modelName, string $now): array
    {
        $row = [
            'role_id' => $roleId,
            'model_name' => $modelName,
            'can_create' => false,
            'can_read' => 'none',
            'can_update' => 'none',
            'can_delete' => 'none',
            'custom_rules' => null,
            'created' => $now,
            'modified' => $now,
        ];

        if ($roleId === 1) {
            $row['can_create'] = true;
            $row['can_read'] = 'all';
            $row['can_update'] = 'all';
            $row['can_delete'] = 'all';
            if ($modelName === 'BlogPosts') {
                $row['custom_rules'] = json_encode([
                    'can_pin_posts' => true,
                    'can_manage_pin_settings' => true,
                ]);
            }

            return $row;
        }

        if ($roleId === 2) {
            if ($modelName === 'BlogPosts') {
                $row['can_create'] = true;
                $row['can_read'] = 'all';
                $row['can_update'] = 'own';
                $row['can_delete'] = 'own';
                $row['custom_rules'] = json_encode([
                    'can_pin_posts' => false,
                    'can_manage_pin_settings' => false,
                ]);
            }
            if ($modelName === 'Images') {
                $row['can_create'] = true;
                $row['can_read'] = 'all';
                $row['can_update'] = 'own';
                $row['can_delete'] = 'none';
            }
            if ($modelName === 'Games') {
                $row['can_create'] = false;
                $row['can_read'] = 'none';
            }
            if ($modelName === 'Users') {
                $row['can_read'] = 'own';
                $row['can_update'] = 'own';
            }

            return $row;
        }

        if ($roleId === 3) {
            if ($modelName === 'BlogPosts') {
                $row['can_create'] = true;
                $row['can_read'] = 'all';
                $row['can_update'] = 'all';
                $row['can_delete'] = 'own';
                $row['custom_rules'] = json_encode([
                    'can_pin_posts' => true,
                    'can_manage_pin_settings' => true,
                ]);
            }
            if ($modelName === 'Images') {
                $row['can_create'] = true;
                $row['can_read'] = 'all';
                $row['can_update'] = 'all';
                $row['can_delete'] = 'none';
            }
            if (in_array($modelName, ['Games', 'GameTypes', 'Opponents', 'Persons', 'Places', 'Seasons', 'Sites', 'Teams', 'TeamSeasonRosters', 'TeamSeasons'], true)) {
                $row['can_read'] = 'all';
            }
            if ($modelName === 'Users') {
                $row['can_read'] = 'own';
                $row['can_update'] = 'own';
            }

            return $row;
        }

        if ($roleId === 4) {
            if ($modelName === 'BlogPosts') {
                $row['can_read'] = 'all';
                $row['custom_rules'] = json_encode([
                    'can_pin_posts' => false,
                    'can_manage_pin_settings' => false,
                ]);
            }
            if ($modelName === 'Images') {
                $row['can_read'] = 'all';
            }
            if (in_array($modelName, ['Games', 'GameTypes', 'Opponents', 'Persons', 'Places', 'Sites', 'TeamSeasonRosters', 'TeamSeasons'], true)) {
                $row['can_create'] = true;
                $row['can_read'] = 'all';
                $row['can_update'] = 'all';
            }
            if (in_array($modelName, ['Teams', 'Seasons'], true)) {
                $row['can_read'] = 'all';
            }
            if ($modelName === 'Users') {
                $row['can_read'] = 'own';
                $row['can_update'] = 'own';
            }
        }

        if (in_array($modelName, ['SiteOptions', 'Roles'], true) && $roleId !== 1 && $roleId !== 2) {
            $row['can_read'] = 'none';
            $row['can_update'] = 'none';
            $row['can_delete'] = 'none';
            $row['can_create'] = false;
        }

        if ($roleId === 4 && in_array($modelName, ['Games', 'TeamSeasons', 'TeamSeasonRosters', 'Persons', 'Opponents', 'Places', 'Sites', 'GameTypes'], true)) {
            $row['can_read'] = 'all';
            $row['can_update'] = 'all';
            $row['can_delete'] = 'none';
        }

        return $row;
    }
}
