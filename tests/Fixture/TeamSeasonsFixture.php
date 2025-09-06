<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TeamSeasonsFixture extends TestFixture
{
    // Migration creates the table as 'team_season' (singular). Set the
    // fixture table explicitly and import that name so schema reflection
    // and insertion use the correct DB table.
    public string $table = 'team_season';
    public $import = ['table' => 'team_season'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'team_id' => 1,
                'season_id' => 1,
                // Migrations define semester as integer; use 1 for Fall.
                'semester' => 1,
                'team_season_image' => 1,
                'created_at' => '2025-08-01 00:00:00',
                // Migration uses updated_at column name
                'updated_at' => '2025-08-01 00:00:00',
            ],
        ];

        parent::init();
    }
}
