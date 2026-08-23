<?php
declare(strict_types=1);

use Migrations\BaseSeed;

class RbacBootstrapSeed extends BaseSeed
{
    /**
     * Upsert one row by id.
     *
     * @param string $table Table name.
     * @param array<string,mixed> $row Column values.
     */
    private function upsertById(string $table, array $row): void
    {
        $columns = array_keys($row);
        $quotedColumns = array_map(static fn(string $column): string => sprintf('`%s`', $column), $columns);
        $placeholders = array_fill(0, count($columns), '?');

        $this->execute(
            sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $table,
                implode(', ', $quotedColumns),
                implode(', ', $placeholders),
                implode(', ', array_map(static fn(string $column): string => sprintf('`%s` = VALUES(`%s`)', $column, $column), $columns)),
            ),
            array_values($row),
        );
    }

    /**
     * Run seed data insertions.
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            1 => 'Admin',
            2 => 'Blogger',
            3 => 'Editor',
            4 => 'Contributor',
        ];

        foreach ($roles as $id => $name) {
            $this->upsertById('roles', [
                'id' => $id,
                'name' => $name,
                'created' => $now,
                'modified' => $now,
            ]);
        }

        $users = [
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => password_hash('administrator', PASSWORD_BCRYPT),
                'role' => 'admin',
                'role_id' => 1,
                'status' => 'active',
                'active' => 1,
                'is_superuser' => 1,
            ],
            [
                'id' => 2,
                'username' => 'blogger',
                'email' => 'blogger@example.com',
                'password' => password_hash('blogblogger', PASSWORD_BCRYPT),
                'role' => 'blogger',
                'role_id' => 2,
                'status' => 'active',
                'active' => 1,
                'is_superuser' => 0,
            ],
            [
                'id' => 3,
                'username' => 'editor',
                'email' => 'editor@example.com',
                'password' => password_hash('editeditor', PASSWORD_BCRYPT),
                'role' => 'editor',
                'role_id' => 3,
                'status' => 'active',
                'active' => 1,
                'is_superuser' => 0,
            ],
            [
                'id' => 4,
                'username' => 'contributor',
                'email' => 'contributor@example.com',
                'password' => password_hash('contributor', PASSWORD_BCRYPT),
                'role' => 'contributor',
                'role_id' => 4,
                'status' => 'active',
                'active' => 1,
                'is_superuser' => 0,
            ],
        ];

        foreach ($users as $user) {
            $this->upsertById('users', $user + [
                'created' => $now,
                'modified' => $now,
            ]);
        }

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

        $this->execute('DELETE FROM permissions WHERE role_id IN (1, 2, 3, 4)');

        $rows = [];
        foreach ([1, 2, 3, 4] as $roleId) {
            foreach ($models as $modelName) {
                $rows[] = $this->buildPermissionRow($roleId, $modelName, $now);
            }
        }

        $this->table('permissions')->insert($rows)->saveData();
    }

    /**
     * Build one permission row for role defaults.
     *
     * @param int $roleId RBAC role id.
     * @param string $modelName Canonical RBAC model name.
     * @param string $now Timestamp.
     * @return array<string,mixed>
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

            return $row;
        }

        return $row;
    }
}
