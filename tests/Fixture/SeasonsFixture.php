<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SeasonsFixture extends TestFixture
{
    public $import = ['table' => 'seasons'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'start' => 2023,
                'end' => 2024,
                'created_at' => '2025-08-01 00:00:00',
                'updated_at' => '2025-08-01 00:00:00',
            ],
            [
                'id' => 2,
                'start' => 2024,
                'end' => 2025,
                'created_at' => '2025-08-01 00:00:00',
                'updated_at' => '2025-08-01 00:00:00',
            ],
        ];

        parent::init();
    }
}
