<?php

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SiteOptionsFixture extends TestFixture
{
    public $import = ['table' => 'site_options'];

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'option_key' => 'registration',
                'value' => 'true', // registration enabled by default so tests expecting registration work
                'created' => '2024-01-01 00:00:00',
                'modified' => '2024-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
