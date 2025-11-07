<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketGameTeamFixture
 */
class StatBasketGameTeamFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_game_team';

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
                'game_id' => 1,
                'opp' => 0,
                'ORB' => '8',
                'DRB' => '25',
                'RB' => '33',
                'TRN' => '12',
                'TF' => '1',
                'PTS' => '65',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
            [
                'id' => 2,
                'game_id' => 1,
                'opp' => 1,
                'ORB' => '6',
                'DRB' => '28',
                'RB' => '34',
                'TRN' => '15',
                'TF' => '0',
                'PTS' => '58',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
