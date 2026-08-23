<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PermissionsFixture extends TestFixture
{
    public string $table = 'permissions';

    /**
     * Initialize the permission fixture rows.
     */
    public function init(): void
    {
        $now = '2026-08-22 00:00:00';
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

        $records = [];
        $id = 1;
        foreach ([1, 2, 3, 4] as $roleId) {
            foreach ($models as $modelName) {
                $records[] = $this->buildRow($id++, $roleId, $modelName, $now);
            }
        }

        $this->records = $records;
        parent::init();
    }

    /**
     * Build one permission row for the fixture data.
     *
     * @param int $id Synthetic fixture id.
     * @param int $roleId RBAC role id.
     * @param string $modelName Canonical RBAC model name.
     * @param string $now Timestamp string for created/modified.
     * @return array<string, mixed>
     */
    private function buildRow(int $id, int $roleId, string $modelName, string $now): array
    {
        $row = [
            'id' => $id,
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
            if ($modelName === 'Users') {
                $row['can_create'] = false;
                $row['can_read'] = 'own';
                $row['can_update'] = 'own';
                $row['can_delete'] = 'none';
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

        if ($roleId === 4 && in_array($modelName, ['Games', 'TeamSeasons', 'TeamSeasonRosters', 'Persons', 'Opponents', 'Places', 'Sites', 'GameTypes'], true)) {
            $row['can_read'] = 'all';
            $row['can_update'] = 'all';
            $row['can_delete'] = 'none';
        }

        return $row;
    }
}
