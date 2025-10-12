<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GamesFixture extends TestFixture
{
    public string $table = 'games';
    public $import = ['table' => 'games'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'team_season_id' => 1,
                'game_date' => '2025-01-15',
                'game_time' => '19:00',
                'game_duration' => '2:05',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 0,
                'post' => 0,
                'w' => 'W',
                'l' => 'L',
                'pts_mur' => '75',
                'pts_opp' => '68',
                'mur_rk' => null,
                'opp_rk' => null,
                'periods' => '2',
            ],
        ];

        parent::init();
    }
}
