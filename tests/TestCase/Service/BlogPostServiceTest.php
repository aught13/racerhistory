<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BlogPostService;
use App\Test\TestCase\Support\TaggingServiceFactoryTestTrait;
use Cake\TestSuite\TestCase;

class BlogPostServiceTest extends TestCase
{
    use TaggingServiceFactoryTestTrait;

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
        $this->service = new BlogPostService($this->taggingServiceFactory());
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

    /**
     * Person biography posts are separated from all published stories.
     */
    public function testGetPublishedForPersonSeparatesBioPostsAndStories(): void
    {
        $this->blogPostTaggingService()->replaceTags(1, [
            'person-1',
            ['slug' => 'Bio', 'name' => 'Bio'],
        ]);

        for ($index = 1; $index <= 11; $index++) {
            $post = $this->service->createPost([
                'title' => 'Person Story ' . $index,
                'slug' => 'person-story-' . $index,
                'body' => 'Story body',
                'is_published' => true,
                'tags' => 'person-1',
            ]);
            $this->assertNotFalse($post);
        }

        $draft = $this->service->createPost([
            'title' => 'Unpublished Person Story',
            'slug' => 'unpublished-person-story',
            'body' => 'Draft body',
            'is_published' => false,
            'tags' => 'person-1',
        ]);
        $this->assertNotFalse($draft);

        $posts = $this->service->getPublishedForPerson(1);

        $this->assertCount(1, $posts['bioPosts']);
        $this->assertSame('first-post', (string)$posts['bioPosts'][0]->slug);
        $this->assertCount(11, $posts['stories']);
        $storySlugs = array_map(
            static fn($story): string => (string)$story->slug,
            $posts['stories'],
        );
        $expectedSlugs = array_map(
            static fn(int $index): string => 'person-story-' . $index,
            range(1, 11),
        );
        $this->assertEqualsCanonicalizing($expectedSlugs, $storySlugs);
        $this->assertNotContains('unpublished-person-story', $storySlugs);
    }
}
