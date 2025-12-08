<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImagesImageTagsFixture extends TestFixture
{
    public string $table = 'images_image_tags';

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'image_id' => 1,
                'image_tag_id' => 1,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'image_id' => 1,
                'image_tag_id' => 3,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
