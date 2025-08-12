<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SportsFixture
 */
class SportsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'sport_name' => 'Basketball',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
            [
                'id' => 2,
                'sport_name' => 'Football',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
            [
                'id' => 3,
                'sport_name' => 'Baseball',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
