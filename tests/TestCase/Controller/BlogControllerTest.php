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
    ];

    public function testIndex(): void
    {
        $this->get('/blog');
        $this->assertResponseOk();
        $this->assertResponseContains('Blog');
    }

    public function testView(): void
    {
        $this->get('/blog/first-post');
        $this->assertResponseOk();
        $this->assertResponseContains('First Post');
    }

    public function testViewNotFound(): void
    {
        $this->get('/blog/missing-post');
        $this->assertResponseCode(404);
    }
}
