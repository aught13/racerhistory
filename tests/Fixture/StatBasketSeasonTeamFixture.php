<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketSeasonTeamFixture
 */
class StatBasketSeasonTeamFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_season_team';

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
                'FGM' => '180',
                'FGA' => '380',
                'TPM' => '45',
                'TPA' => '120',
                'FTM' => '90',
                'FTA' => '130',
                'ORB' => '80',
                'DRB' => '140',
                'RB' => '220',
                'AST' => '95',
                'STL' => '50',
                'BS' => '25',
                'TRN' => '75',
                'PF' => '110',
                'TF' => '5',
                'PTS' => '495',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
