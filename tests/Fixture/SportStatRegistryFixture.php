<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SportStatRegistryFixture extends TestFixture
{
    public string $table = 'sport_stat_registry';

    public array $fields = [
        'id' => ['type' => 'integer', 'autoIncrement' => true],
        'sport_id' => ['type' => 'integer', 'null' => false],
        'context' => ['type' => 'string', 'length' => 20, 'null' => false],
        'entity_type' => ['type' => 'string', 'length' => 20, 'null' => false],
        'display_name' => ['type' => 'string', 'length' => 100, 'null' => false],
        'table_name' => ['type' => 'string', 'length' => 100, 'null' => false],
        'field_mapping' => ['type' => 'text', 'null' => true],
        'primary_key' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => 'id'],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'sport_context_entity_unique' => ['type' => 'unique', 'columns' => ['sport_id', 'context', 'entity_type']],
        ],
    ];

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'sport_id' => 1, // Basketball
                'context' => 'game',
                'entity_type' => 'team',
                'display_name' => 'Basketball Game Team Stats',
                'table_name' => 'stat_basket_game_team',
                'field_mapping' => json_encode([
                    'FGM' => ['label' => 'Field Goals Made', 'type' => 'numeric'],
                    'FGA' => ['label' => 'Field Goals Attempted', 'type' => 'numeric'],
                    '3PM' => ['label' => '3-Point Field Goals Made', 'type' => 'numeric'],
                    '3PA' => ['label' => '3-Point Field Goals Attempted', 'type' => 'numeric'],
                    'FTM' => ['label' => 'Free Throws Made', 'type' => 'numeric'],
                    'FTA' => ['label' => 'Free Throws Attempted', 'type' => 'numeric'],
                ]),
                'primary_key' => 'id',
                'created' => '2025-10-12 12:00:00',
                'modified' => '2025-10-12 12:00:00',
            ],
            [
                'id' => 2,
                'sport_id' => 1, // Basketball
                'context' => 'game',
                'entity_type' => 'player',
                'display_name' => 'Basketball Game Player Stats',
                'table_name' => 'stat_basket_game_person',
                'field_mapping' => json_encode([
                    'MIN' => ['label' => 'Minutes', 'type' => 'numeric'],
                    'FGM' => ['label' => 'Field Goals Made', 'type' => 'numeric'],
                    'FGA' => ['label' => 'Field Goals Attempted', 'type' => 'numeric'],
                    'PTS' => ['label' => 'Points', 'type' => 'numeric'],
                ]),
                'primary_key' => 'id',
                'created' => '2025-10-12 12:00:00',
                'modified' => '2025-10-12 12:00:00',
            ],
            [
                'id' => 3,
                'sport_id' => 2, // Football
                'context' => 'game',
                'entity_type' => 'team',
                'display_name' => 'Football Game Team Stats',
                'table_name' => 'stat_football_game_team',
                'field_mapping' => json_encode([
                    'TD' => ['label' => 'Touchdowns', 'type' => 'numeric'],
                    'FG' => ['label' => 'Field Goals', 'type' => 'numeric'],
                    'YDS' => ['label' => 'Total Yards', 'type' => 'numeric'],
                ]),
                'primary_key' => 'id',
                'created' => '2025-10-12 12:00:00',
                'modified' => '2025-10-12 12:00:00',
            ],
        ];

        parent::init();
    }
}
