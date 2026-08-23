<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RolesFixture extends TestFixture
{
    public string $table = 'roles';

    /**
     * Initialize the role fixture rows.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Admin',
                'created' => '2026-08-22 00:00:00',
                'modified' => '2026-08-22 00:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Blogger',
                'created' => '2026-08-22 00:00:00',
                'modified' => '2026-08-22 00:00:00',
            ],
            [
                'id' => 3,
                'name' => 'Editor',
                'created' => '2026-08-22 00:00:00',
                'modified' => '2026-08-22 00:00:00',
            ],
            [
                'id' => 4,
                'name' => 'Contributor',
                'created' => '2026-08-22 00:00:00',
                'modified' => '2026-08-22 00:00:00',
            ],
        ];

        parent::init();
    }
}
