<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketGamePersonFixture
 */
class StatBasketGamePersonFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_game_person';

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
                'team_season_roster_id' => 1,
                'game_id' => 1,
                'period' => 'Z',
                'GP' => '1',
                'GS' => '1',
                'MIN' => '30',
                'FGM' => '8',
                'FGA' => '15',
                'TPM' => '2',
                'TPA' => '5',
                'FTM' => '4',
                'FTA' => '5',
                'ORB' => '2',
                'DRB' => '6',
                'RB' => '8',
                'AST' => '5',
                'STL' => '2',
                'BS' => '1',
                'BD' => '0',
                'TRN' => '2',
                'PF' => '3',
                'TF' => '0',
                'FD' => '4',
                'PTS' => '22',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
