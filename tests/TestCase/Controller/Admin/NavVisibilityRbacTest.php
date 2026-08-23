<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class NavVisibilityRbacTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.Sports',
        'app.Teams',
        'app.Games',
        'app.Images',
    ];

    /**
     * Contributor should not see nav items for entities with no read access.
     */
    public function testContributorNavHidesRestrictedItems(): void
    {
        $this->mockIdentity([
            'id' => 5,
            'username' => 'contributor',
            'role' => 'contributor',
            'role_id' => 4,
            'status' => 'active',
            'active' => true,
        ]);

        $this->get('/admin');

        $this->assertResponseOk();
        $this->assertResponseContains('/admin/games');
        $this->assertResponseContains('/admin/images');
        $this->assertResponseNotContains('href="/admin/roles"');
        $this->assertResponseNotContains('/admin/site-options/edit');
    }

    /**
     * Blogger should still see links for allowed create/read flows.
     */
    public function testBloggerNavShowsAllowedItems(): void
    {
        $this->mockIdentity([
            'id' => 3,
            'username' => 'blogger',
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ]);

        $this->get('/admin');

        $this->assertResponseOk();
        $this->assertResponseContains('/admin/blog-posts');
        $this->assertResponseContains('/admin/images');
        $this->assertResponseContains('/admin/images/bulk-upload-form');
    }
}
