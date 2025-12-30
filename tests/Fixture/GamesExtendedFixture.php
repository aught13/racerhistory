<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GamesExtendedFixture
 *
 * Provides a richer dataset for service layer tests (pagination, search, bulk delete) without
 * impacting other tests that rely on the lean default GamesFixture.
 */
class GamesExtendedFixture extends TestFixture
{
    public string $table = 'games';

    public array $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'null' => false, 'autoIncrement' => true],
        'team_season_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'game_date' => ['type' => 'date', 'null' => false],
        'game_time' => ['type' => 'time', 'null' => true],
        'game_duration' => ['type' => 'string', 'length' => 10, 'null' => true],
        'game_type_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'opponent_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'place_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'site_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'hrn' => ['type' => 'integer', 'length' => 11, 'null' => true, 'default' => 0],
        'post' => ['type' => 'boolean', 'null' => true, 'default' => 0],
        'w' => ['type' => 'string', 'length' => 1, 'null' => true],
        'l' => ['type' => 'string', 'length' => 1, 'null' => true],
        'pts_mur' => ['type' => 'string', 'length' => 3, 'null' => true],
        'pts_opp' => ['type' => 'string', 'length' => 3, 'null' => true],
        'mur_rk' => ['type' => 'integer', 'length' => 11, 'null' => true],
        'opp_rk' => ['type' => 'integer', 'length' => 11, 'null' => true],
        'periods' => ['type' => 'string', 'length' => 2, 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
        ],
    ];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'team_season_id' => 1,
                'game_date' => new \DateTime('2025-01-15'),
                'game_time' => '19:00',
                'game_duration' => '2:05',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 1, // Home
                'post' => 0,
                'w' => 'W',
                'l' => 'L',
                'pts_mur' => '80',
                'pts_opp' => '70',
                'mur_rk' => 5,
                'opp_rk' => 12,
                'periods' => '2',
            ],
            [
                'id' => 2,
                'team_season_id' => 1,
                'game_date' => new \DateTime('2025-01-16'),
                'game_time' => '19:30',
                'game_duration' => '2:10',
                'game_type_id' => 2,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 0, // treat as non-home (maps to '-')
                'post' => 1,
                'w' => 'L',
                'l' => 'W',
                'pts_mur' => '65',
                'pts_opp' => '72',
                'mur_rk' => 7,
                'opp_rk' => 9,
                'periods' => '2',
            ],
            [
                'id' => 3,
                'team_season_id' => 1,
                'game_date' => new \DateTime('2025-01-17'),
                'game_time' => '18:00',
                'game_duration' => '2:00',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 1, // another Home
                'post' => 0,
                'w' => 'W',
                'l' => 'L',
                'pts_mur' => '77',
                'pts_opp' => '77', // Tie scenario
                'mur_rk' => null,
                'opp_rk' => null,
                'periods' => '2',
            ],
        ];

        parent::init();
    }
}
