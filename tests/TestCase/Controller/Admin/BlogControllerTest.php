<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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

    public function testIndexRedirectsToBlogPosts(): void
    {
        $this->mockIdentity();

        $this->get('/admin/blog');

        $this->assertRedirectContains('/admin/blog-posts');
    }

    public function testUnauthenticatedAccessRedirectsToLogin(): void
    {
        $this->get('/admin/blog');

        $this->assertRedirectContains('/users/login');
    }
}
