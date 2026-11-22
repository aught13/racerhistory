<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AdvertisingSettingsFixture extends TestFixture
{
    public string $table = 'advertising_settings';
    public $import = ['table' => 'advertising_settings'];

    /**
     * Initialize a blank provider settings row.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'global_script' => '',
                'publisher_id' => '',
                'created' => '2026-11-12 00:00:00',
                'modified' => '2026-11-12 00:00:00',
            ],
        ];
        parent::init();
    }
}
