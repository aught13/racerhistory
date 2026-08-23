<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImagesFixture extends TestFixture
{
    public string $table = 'images';

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'filename' => 'seed.png',
                'storage_subdir' => date('Y') . '/' . date('m'),
                'storage_path' => date('Y') . '/' . date('m') . '/seed.png',
                'original_name' => 'seed.png',
                'mime' => 'image/png',
                'ext' => 'png',
                'byte_size' => 10,
                'width' => 1,
                'height' => 1,
                'variants' => json_encode([]),
                'hash' => sha1('seed'),
                'status' => 'active',
                'user_id' => 1,
                'photo_credit' => null,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'filename' => 'blogger-owned.png',
                'storage_subdir' => date('Y') . '/' . date('m'),
                'storage_path' => date('Y') . '/' . date('m') . '/blogger-owned.png',
                'original_name' => 'blogger-owned.png',
                'mime' => 'image/png',
                'ext' => 'png',
                'byte_size' => 12,
                'width' => 1,
                'height' => 1,
                'variants' => json_encode([]),
                'hash' => sha1('blogger-owned'),
                'status' => 'active',
                'user_id' => 3,
                'photo_credit' => null,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
