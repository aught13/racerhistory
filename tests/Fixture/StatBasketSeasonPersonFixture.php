<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketSeasonPersonFixture
 */
class StatBasketSeasonPersonFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_season_person';

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
                'GP' => '10',
                'GS' => '8',
                'MIN' => '250',
                'FGM' => '45',
                'FGA' => '90',
                'TPM' => '12',
                'TPA' => '30',
                'FTM' => '18',
                'FTA' => '24',
                'ORB' => '15',
                'DRB' => '35',
                'RB' => '50',
                'AST' => '22',
                'STL' => '10',
                'BS' => '5',
                'TRN' => '12',
                'PF' => '18',
                'TF' => '1',
                'PTS' => 120,
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
