<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

use Cake\ORM\TableRegistry;

/**
 * Provides a deterministic baseline seed for tests relying on Users and SiteOptions.
 */
trait BaselineSeedTrait
{
    /**
     * Runs the seed baseline routine.
     *
     * @param bool $users
     * @param bool $siteOptions
     */
    protected function seedBaseline(bool $users = true, bool $siteOptions = true): void
    {
        if ($users) {
            $usersTable = TableRegistry::getTableLocator()->get('Users');
            $usersTable->deleteAll([]);
            $baselineUsers = [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                    'role' => 'admin',
                    'status' => 'active',
                ],
                [
                    'id' => 2,
                    'username' => 'user',
                    'email' => 'user@example.com',
                    'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                    'role' => 'user',
                    'status' => 'inactive',
                ],
            ];
            foreach ($baselineUsers as $row) {
                $entity = $usersTable->newEntity($row, ['accessibleFields' => ['*' => true]]);
                $usersTable->saveOrFail($entity);
            }
        }

        if ($siteOptions) {
            $siteOptionsTable = TableRegistry::getTableLocator()->get('SiteOptions');
            $siteOptionsTable->deleteAll(['option_key' => 'registration']);
            $option = $siteOptionsTable->newEntity([
                'option_key' => 'registration',
                'value' => 'true',
            ], ['accessibleFields' => ['*' => true]]);
            $siteOptionsTable->saveOrFail($option);
        }
    }
}
