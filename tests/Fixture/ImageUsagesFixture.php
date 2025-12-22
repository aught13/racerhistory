<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImageUsagesFixture extends TestFixture
{
    public string $table = 'image_usages';

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'image_id' => 1,
                'model' => 'Persons',
                'foreign_key' => 1,
                'field' => 'image',
                'context' => 'profile-photo',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
