<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BlogPostsBlogTagsFixture extends TestFixture
{
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'autoIncrement' => true],
        'blog_post_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false],
        'blog_tag_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
        '_indexes' => [
            'blog_post_id' => ['type' => 'index', 'columns' => ['blog_post_id']],
            'blog_tag_id' => ['type' => 'index', 'columns' => ['blog_tag_id']],
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
                'blog_post_id' => 1,
                'blog_tag_id' => 1,
                'created' => '2025-01-01 12:00:00',
                'modified' => '2025-01-01 12:00:00',
            ],
        ];
        parent::init();
    }
}
