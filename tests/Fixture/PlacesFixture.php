<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PlacesFixture extends TestFixture
{
    public string $table = 'places';
    public $import = ['table' => 'places'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'place_name' => 'Murray, KY',
                'place_city' => 'Murray',
                'place_state' => 'KY',
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
            ],
        ];

        parent::init();
    }
}
