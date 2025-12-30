<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GamesFixture extends TestFixture
{
    public string $table = 'games';
    public array $schema = [];

    public function init(): void
    {
        $this->schema = [
            'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
            'team_season_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'game_date' => ['type' => 'date', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
            'game_time' => ['type' => 'time', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
            'game_duration' => ['type' => 'string', 'length' => 10, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'game_type_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'opponent_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'place_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'site_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'hrn' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => 0, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'post' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
            'w' => ['type' => 'string', 'length' => 1, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'l' => ['type' => 'string', 'length' => 1, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'pts_mur' => ['type' => 'string', 'length' => 3, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'pts_opp' => ['type' => 'string', 'length' => 3, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            'mur_rk' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'opp_rk' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'periods' => ['type' => 'string', 'length' => 2, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            ],
            '_options' => [
                'engine' => 'InnoDB',
                'collation' => 'utf8mb4_general_ci',
            ],
        ];

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
                'hrn' => 1,
                'post' => 0,
                'w' => 'W',
                'l' => 'L',
                'pts_mur' => '75',
                'pts_opp' => '68',
                'mur_rk' => null,
                'opp_rk' => null,
                'periods' => '2',
            ],
            [
                'id' => 2,
                'team_season_id' => 1,
                'game_date' => '2025-01-16',
                'game_time' => '19:00',
                'game_duration' => '2:05',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 2,
                'post' => 0,
                'w' => 'L',
                'l' => 'W',
                'pts_mur' => '60',
                'pts_opp' => '61',
                'mur_rk' => null,
                'opp_rk' => null,
                'periods' => '2',
            ],
            [
                'id' => 3,
                'team_season_id' => 1,
                'game_date' => '2025-01-17',
                'game_time' => '19:00',
                'game_duration' => '2:05',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 3,
                'post' => 0,
                'w' => 'W',
                'l' => 'L',
                'pts_mur' => '80',
                'pts_opp' => '70',
                'mur_rk' => null,
                'opp_rk' => null,
                'periods' => '2',
            ],
        ];

        parent::init();
    }
}
