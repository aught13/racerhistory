<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class OpponentsFixture extends TestFixture
{
    public string $table = 'opponents';
    public $import = ['table' => 'opponents'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'opponent_name' => 'Belmont',
                'opponent_short' => 'BEL',
                'place_id' => 1,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
                'opponent_current' => null,
            ],
        ];

        parent::init();
    }
}
