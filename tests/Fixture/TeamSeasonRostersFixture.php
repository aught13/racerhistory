<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TeamSeasonRostersFixture extends TestFixture
{
    public string $table = 'team_season_roster';

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'team_season_id' => 1,
                'person_id' => 1,
                'roster_year' => '2024',
                'roster_number' => '12',
                'roster_position' => 'G',
                'roster_height' => '6-1',
                'roster_weight' => '180',
                'created_at' => '2025-01-01 10:00:00',
                'updated_at' => '2025-01-01 10:00:00',
            ],
        ];
        parent::init();
    }
}
