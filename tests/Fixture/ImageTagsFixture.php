<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImageTagsFixture extends TestFixture
{
    public string $table = 'image_tags';

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Person 1',
                'slug' => 'person-1',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'name' => 'Team Season 1',
                'slug' => 'teamseason-1',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'name' => 'Roster',
                'slug' => 'roster',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
