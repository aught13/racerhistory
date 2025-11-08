<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketSeasonOpponentFixture
 */
class StatBasketSeasonOpponentFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_season_opponent';

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
                'team_season_id' => 1,
                'GP' => '10',
                'MIN' => '400',
                'FGM' => '170',
                'FGA' => '360',
                'TPM' => '40',
                'TPA' => '110',
                'FTM' => '85',
                'FTA' => '120',
                'ORB' => '75',
                'DRB' => '130',
                'RB' => '205',
                'AST' => '88',
                'STL' => '45',
                'BS' => '22',
                'TRN' => '80',
                'PF' => '105',
                'TF' => '3',
                'PTS' => '465',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
