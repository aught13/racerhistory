<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BlogPostService;
use Cake\TestSuite\TestCase;

class BlogPostServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    private BlogPostService $service;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BlogPostService();
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Tests create published sets slug and published at.
     */
    public function testCreatePublishedSetsSlugAndPublishedAt(): void
    {
        $data = [
            'title' => 'First Post', // duplicate slug base
            'body' => 'New body',
            'is_published' => true,
            'status' => 'published',
            'tags' => 'News',
        ];
        $post = $this->service->createPost($data);
        $this->assertNotFalse($post);
        $this->assertStringStartsWith('first-post', (string)$post->slug);
        $this->assertNotEquals('first-post', $post->slug, 'Slug should de-dupe existing slug');
        $this->assertNotEmpty($post->published_at);
        $this->assertTrue((bool)$post->is_published);
    }

    /**
     * Tests update post changes title.
     */
    public function testUpdatePostChangesTitle(): void
    {
        $updated = $this->service->updatePost(1, [
            'title' => 'Updated Title',
            'body' => 'Updated body',
        ]);
        $this->assertNotFalse($updated);
        $this->assertSame('Updated Title', $updated->title);
    }
}
