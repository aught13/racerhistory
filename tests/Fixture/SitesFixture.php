<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SitesFixture extends TestFixture
{
    public string $table = 'sites';
    public $import = ['table' => 'sites'];

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'site_name' => 'CFSB Center',
                'place_id' => 1,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
            ],
        ];

        parent::init();
    }
}
