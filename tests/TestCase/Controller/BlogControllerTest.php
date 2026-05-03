<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BlogControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.Images',
    ];

    public function testIndex(): void
    {
        $this->get('/blog');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="blog">');
        $this->assertResponseContains('blog-featured-as-list');
        $this->assertResponseContains('/images/serve/1?w=200&amp;h=150&amp;fit=cover');
        $this->assertResponseContains('fm=webp');
    }

    public function testView(): void
    {
        $this->get('/blog/first-post');
        $this->assertResponseOk();
        $this->assertResponseContains('First Post');
        $this->assertResponseContains('<turbo-frame id="blog-post-view-first-post"');
        $this->assertResponseContains('/images/serve/1?w=1200&amp;fit=contain');
        $this->assertResponseContains('type="image/webp"');
    }

    public function testViewNotFound(): void
    {
        $this->get('/blog/missing-post');
        $this->assertResponseCode(404);
    }
}
