<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GameEavFixture extends TestFixture
{
    public string $table = 'game_eav';

    // Define fields schema explicitly since import might not work in test environment
    public array $fields = [
        'id' => ['type' => 'integer', 'autoIncrement' => true],
        'game_id' => ['type' => 'integer', 'null' => false],
        'key' => ['type' => 'string', 'length' => 100, 'null' => false],
        'value' => ['type' => 'text', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public function init(): void
    {
        $this->records = [
            ['id' => 1, 'game_id' => 1, 'key' => 'period_1_team', 'value' => '35'],
            ['id' => 2, 'game_id' => 1, 'key' => 'period_1_opponent', 'value' => '30'],
            ['id' => 3, 'game_id' => 1, 'key' => 'period_2_team', 'value' => '40'],
            ['id' => 4, 'game_id' => 1, 'key' => 'period_2_opponent', 'value' => '38'],
            ['id' => 5, 'game_id' => 1, 'key' => 'official_1', 'value' => 'Ref A'],
            ['id' => 6, 'game_id' => 1, 'key' => 'official_2', 'value' => 'Ref B'],
            ['id' => 7, 'game_id' => 1, 'key' => 'official_3', 'value' => 'Ref C'],
        ];

        parent::init();
    }
}
