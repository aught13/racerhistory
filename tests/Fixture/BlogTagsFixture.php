<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BlogTagsFixture extends TestFixture
{
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'autoIncrement' => true],
        'name' => ['type' => 'string', 'length' => 150, 'null' => false],
        'slug' => ['type' => 'string', 'length' => 150, 'null' => false],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'slug' => ['type' => 'unique', 'columns' => ['slug']],
        ],
    ];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'News',
                'slug' => 'news',
                'created' => '2025-01-01 12:00:00',
                'modified' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
