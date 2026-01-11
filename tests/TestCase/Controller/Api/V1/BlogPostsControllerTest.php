<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BlogPostsControllerTest extends TestCase
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
        $this->get('/api/v1/blog-posts');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertCount(1, $payload['data']);
        $this->assertSame('first-post', $payload['data'][0]['slug'] ?? null);
    }

    public function testView(): void
    {
        $this->get('/api/v1/blog-posts/first-post');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame('First Post', $payload['data']['title'] ?? null);
        $this->assertSame('Body content', $payload['data']['body'] ?? null);
    }

    public function testViewNotFound(): void
    {
        $this->get('/api/v1/blog-posts/missing-post');
        $this->assertResponseCode(404);
    }
}
