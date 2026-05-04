<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BlogPostsFixture extends TestFixture
{
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'autoIncrement' => true],
        'title' => ['type' => 'string', 'length' => 190, 'null' => false],
        'slug' => ['type' => 'string', 'length' => 190, 'null' => false],
        'excerpt' => ['type' => 'text', 'null' => true],
        'body' => ['type' => 'text', 'null' => false],
        'status' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'draft'],
        'is_published' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'is_pinned' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'pinned_rank' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'pinned_until' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'published_at' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'hero_image_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'slug' => ['type' => 'unique', 'columns' => ['slug']],
        ],
    ];

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'title' => 'First Post',
                'slug' => 'first-post',
                'excerpt' => 'Excerpt text',
                'body' => 'Body content',
                'status' => 'published',
                'is_published' => true,
                'is_pinned' => false,
                'pinned_rank' => null,
                'pinned_until' => null,
                'published_at' => '2025-01-01 12:00:00',
                'hero_image_id' => 1,
                'created' => '2025-01-01 12:00:00',
                'modified' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
