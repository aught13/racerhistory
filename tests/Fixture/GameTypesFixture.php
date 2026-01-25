<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GameTypesFixture extends TestFixture
{
    public string $table = 'game_types';
    public $import = ['table' => 'game_types'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'game_type_name' => 'Conference',
                'post' => 0,
                'conf' => 1,
                'abr' => null,
            ],
            [
                'id' => 2,
                'game_type_name' => 'NCAA Tournament',
                'post' => 1,
                'conf' => 0,
                'abr' => 'NCAA',
            ],
        ];

        parent::init();
    }
}
