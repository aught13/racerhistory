<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BlogPostsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.Users',
    ];

    public function testIndexRequiresAuth(): void
    {
        $this->get('/admin/blog-posts');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts');
        $this->assertResponseOk();
        $this->assertResponseContains('Blog Posts');
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Post Details');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'title' => 'New Blog',
            'body' => 'Content',
            'status' => 'published',
            'is_published' => 1,
        ];
        $this->post('/admin/blog-posts/add', $data);
        $this->assertRedirectContains('/admin/blog-posts/edit/');
        $this->assertFlashMessage('The blog post has been saved.');
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Post Details');
    }

    public function testEditPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'title' => 'Edited',
            'body' => 'Edited body',
        ];
        $this->post('/admin/blog-posts/edit/1', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'BlogPosts', 'action' => 'edit', 1]);
        $this->assertFlashMessage('The blog post has been saved.');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/blog-posts/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'BlogPosts', 'action' => 'index']);
    }

    /**
     * Test admin blog posts pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }
}
