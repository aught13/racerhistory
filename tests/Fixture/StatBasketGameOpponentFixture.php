<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatBasketGameOpponentFixture
 */
class StatBasketGameOpponentFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'stat_basket_game_opponent';

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
                'period' => 'Z',
                'name' => 'John Doe',
                'jersey' => '23',
                'position' => 'G',
                'GP' => '1',
                'GS' => '1',
                'MIN' => '28',
                'FGM' => '7',
                'FGA' => '12',
                'TPM' => '1',
                'TPA' => '3',
                'FTM' => '3',
                'FTA' => '4',
                'ORB' => '1',
                'DRB' => '4',
                'RB' => '5',
                'AST' => '3',
                'STL' => '1',
                'BS' => '0',
                'BD' => '1',
                'TRN' => '2',
                'PF' => '2',
                'TF' => '0',
                'FD' => '3',
                'PTS' => '18',
                'created_at' => '2025-01-01 12:00:00',
                'updated_at' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
