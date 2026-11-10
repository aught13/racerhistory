<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\BlogController
 */
class BlogControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.Images',
    ];

    /**
     * Tests the blog index page.
     */
    public function testIndex(): void
    {
        $this->get('/blog');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="blog"');
        $this->assertResponseContains('blog-featured-frame');
        $this->assertResponseContains('/img/storage/');
    }

    /**
     * Tests viewing a published blog post.
     */
    public function testView(): void
    {
        $this->get('/blog/first-post');
        $this->assertResponseOk();
        $this->assertResponseContains('First Post');
        $this->assertResponseContains('<turbo-frame id="blog-post-view"');
        $this->assertResponseContains('/img/storage/');
        $this->assertResponseContains('<meta property="og:image" content="/img/storage/');
        $this->assertResponseContains('<meta property="twitter:image" content="/img/storage/');
    }

    /**
     * Entity tags link to their corresponding public pages while ordinary tags remain badges.
     */
    public function testViewLinksEntityTagsToTheirPublicPages(): void
    {
        $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        $linksTable = TableRegistry::getTableLocator()->get('BlogPostsBlogTags');
        $tags = [
            ['name' => 'Player Tag', 'slug' => 'person-12', 'href' => '/people/12'],
            ['name' => 'Season Tag', 'slug' => 'teamseason-34', 'href' => '/seasons/34'],
            ['name' => 'Game Tag', 'slug' => 'game-56', 'href' => '/games/56'],
        ];

        foreach ($tags as $tagData) {
            $tag = $tagsTable->saveOrFail($tagsTable->newEntity([
                'name' => $tagData['name'],
                'slug' => $tagData['slug'],
            ]));
            $linksTable->saveOrFail($linksTable->newEntity([
                'blog_post_id' => 1,
                'blog_tag_id' => (int)$tag->get('id'),
            ]));
        }

        $this->get('/blog/first-post');
        $this->assertResponseOk();

        foreach ($tags as $tagData) {
            $this->assertResponseContains(
                '<a href="' . $tagData['href'] . '" class="blog-tag badge bg-secondary text-decoration-none">'
                . $tagData['name']
                . '</a>',
            );
        }
        $this->assertResponseContains('<span class="blog-tag badge bg-secondary">News</span>');
    }

    /**
     * Tests the not found response for a missing post.
     */
    public function testViewNotFound(): void
    {
        $this->get('/blog/missing-post');
        $this->assertResponseCode(404);
    }
}
