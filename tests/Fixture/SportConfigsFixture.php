<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SportConfigsFixture
 */
class SportConfigsFixture extends TestFixture
{
    /**
     * Table name
     */
    public string $table = 'sport_configs';

    /**
     * Init method
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'sport_id' => 1,
                'config_key' => 'period_name_2',
                'config_value' => 'Half',
                'description' => 'Period name for 2-period games',
                'created' => '2025-10-03 12:00:00',
                'modified' => '2025-10-03 12:00:00',
            ],
            [
                'id' => 2,
                'sport_id' => 1,
                'config_key' => 'period_name_4',
                'config_value' => 'Quarter',
                'description' => 'Period name for 4-period games',
                'created' => '2025-10-03 12:00:00',
                'modified' => '2025-10-03 12:00:00',
            ],
            [
                'id' => 3,
                'sport_id' => 1,
                'config_key' => 'officials',
                'config_value' => '["Referee 1","Referee 2","Official 3"]',
                'description' => 'Basketball officials',
                'created' => '2025-10-03 12:00:00',
                'modified' => '2025-10-03 12:00:00',
            ],
            [
                'id' => 4,
                'sport_id' => 1,
                'config_key' => 'scoring_type',
                'config_value' => 'cumulative',
                'description' => 'Use cumulative scoring for tests',
                'created' => '2025-10-03 12:00:00',
                'modified' => '2025-10-03 12:00:00',
            ],
        ];
        parent::init();
    }
}
