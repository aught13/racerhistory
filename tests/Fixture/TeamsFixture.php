<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TeamsFixture
 */
class TeamsFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'teams';

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
                'sport_id' => 1,
                'team_name' => 'Los Angeles Lakers',
                'team_description' => 'Professional basketball team',
                'abbr' => 'LAL',
                'gender' => 'M',
                'created_at' => '2025-08-13 12:00:00',
                'updated_at' => '2025-08-13 12:00:00',
            ],
            [
                'id' => 2,
                'sport_id' => 1,
                'team_name' => 'Boston Celtics',
                'team_description' => 'Historic basketball franchise',
                'abbr' => 'BOS',
                'gender' => 'M',
                'created_at' => '2025-08-13 12:00:00',
                'updated_at' => '2025-08-13 12:00:00',
            ],
            [
                'id' => 3,
                'sport_id' => 2,
                'team_name' => 'New York Giants',
                'team_description' => 'Professional football team',
                'abbr' => 'NYG',
                'gender' => 'M',
                'created_at' => '2025-08-13 12:00:00',
                'updated_at' => '2025-08-13 12:00:00',
            ],
        ];
        parent::init();
    }
}
