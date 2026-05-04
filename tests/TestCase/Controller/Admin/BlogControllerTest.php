<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\BlogController
 */
class BlogControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Tests index redirects to blog posts.
     */
    public function testIndexRedirectsToBlogPosts(): void
    {
        $this->mockIdentity();

        $this->get('/admin/blog');

        $this->assertRedirectContains('/admin/blog-posts');
    }

    /**
     * Tests unauthenticated access redirects to login.
     */
    public function testUnauthenticatedAccessRedirectsToLogin(): void
    {
        $this->get('/admin/blog');

        $this->assertRedirectContains('/users/login');
    }
}
