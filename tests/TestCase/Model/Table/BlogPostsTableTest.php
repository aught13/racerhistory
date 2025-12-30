<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BlogPostsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class BlogPostsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    private BlogPostsTable $BlogPosts;

    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('BlogPosts') ? [] : ['className' => BlogPostsTable::class];
        /** @var \App\Model\Table\BlogPostsTable $table */
        $table = TableRegistry::getTableLocator()->get('BlogPosts', $config);
        $this->BlogPosts = $table;
    }

    public function tearDown(): void
    {
        unset($this->BlogPosts);
        parent::tearDown();
    }

    public function testValidationRequiresTitleAndBody(): void
    {
        $entity = $this->BlogPosts->newEntity([
            'title' => '',
            'body' => '',
            'slug' => '',
        ]);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('title', $entity->getErrors());
        $this->assertArrayHasKey('body', $entity->getErrors());
    }

    public function testUniqueSlugRule(): void
    {
        $entity = $this->BlogPosts->newEntity([
            'title' => 'Duplicate',
            'body' => 'body',
            'slug' => 'first-post',
        ]);
        $this->BlogPosts->save($entity);
        $this->assertNotEmpty($entity->getErrors()['slug'] ?? []);
    }
}
