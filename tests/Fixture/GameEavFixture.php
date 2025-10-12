<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GameEavFixture extends TestFixture
{
    public string $table = 'game_eav';
    public $import = ['table' => 'game_eav'];

    public function init(): void
    {
        $this->records = [
            ['id' => 1, 'game_id' => 1, 'key' => 'period_1_mur', 'value' => '35'],
            ['id' => 2, 'game_id' => 1, 'key' => 'period_1_opp', 'value' => '30'],
            ['id' => 3, 'game_id' => 1, 'key' => 'period_2_mur', 'value' => '40'],
            ['id' => 4, 'game_id' => 1, 'key' => 'period_2_opp', 'value' => '38'],
            ['id' => 5, 'game_id' => 1, 'key' => 'official_1', 'value' => 'Ref A'],
            ['id' => 6, 'game_id' => 1, 'key' => 'official_2', 'value' => 'Ref B'],
            ['id' => 7, 'game_id' => 1, 'key' => 'official_3', 'value' => 'Ref C'],
        ];

        parent::init();
    }
}
