<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\BlogPostsController
 */
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

    /**
     * Tests index requires auth.
     */
    public function testIndexRequiresAuth(): void
    {
        $this->get('/admin/blog-posts');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts');
        $this->assertResponseOk();
        $this->assertResponseContains('Blog Posts');
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Post Details');
    }

    /**
     * Tests add post.
     */
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

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Post Details');
    }

    /**
     * Tests edit post.
     */
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

    /**
     * Tests delete.
     */
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

    /**
     * Test edit page contains the unset hero button.
     */
    public function testEditPageContainsUnsetHeroButton(): void
    {
        $this->mockIdentity();
        $this->get('/admin/blog-posts/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('data-action="unset-hero"');
        $this->assertResponseContains('unset-hero-btn');
    }

    /**
     * Test that saving with empty hero_image_id clears the hero image.
     */
    public function testEditPostClearsHeroImage(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'title' => 'No Hero',
            'body' => 'Body text',
            'hero_image_id' => '',
        ];
        $this->post('/admin/blog-posts/edit/1', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'BlogPosts', 'action' => 'edit', 1]);
        $this->assertFlashMessage('The blog post has been saved.');
    }
}
