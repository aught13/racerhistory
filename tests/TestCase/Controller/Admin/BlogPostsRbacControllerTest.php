<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BlogPostsRbacControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    /**
     * Authenticate as the seeded blogger role.
     */
    private function loginAsBlogger(): void
    {
        $this->mockIdentity([
            'id' => 3,
            'username' => 'blogger',
            'role' => 'blogger',
            'role_id' => 2,
            'email' => 'blogger@example.com',
            'status' => 'active',
            'active' => true,
        ]);
    }

    /**
     * Authenticate as the seeded editor role.
     */
    private function loginAsEditor(): void
    {
        $this->mockIdentity([
            'id' => 4,
            'username' => 'editor',
            'role' => 'editor',
            'role_id' => 3,
            'email' => 'editor@example.com',
            'status' => 'active',
            'active' => true,
        ]);
    }

    /**
     * Ensure blogger index allows read-all visibility per default role matrix.
     */
    public function testBloggerIndexShowsOwnedAndOtherPosts(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/blog-posts');

        $this->assertResponseOk();
        $this->assertResponseContains('Blogger Owned Post');
        $this->assertResponseContains('First Post');
    }

    /**
     * Ensure a blogger cannot open another author's edit form.
     */
    public function testBloggerCannotEditAnotherUsersPost(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/blog-posts/edit/1');

        $this->assertResponseCode(404);
    }

    /**
     * Ensure a blogger's own edit form hides pin controls they cannot use.
     */
    public function testBloggerOwnEditHidesPinControls(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/blog-posts/edit/2');

        $this->assertResponseOk();
        $this->assertResponseNotContains('Pin this post');
        $this->assertResponseContains('Owner');
        $this->assertResponseContains('blogger');
    }

    /**
     * Editors can edit foreign posts but cannot reassign ownership unless they
     * have delete-all access for that post.
     */
    public function testEditorForeignEditDoesNotExposeOwnerReassignment(): void
    {
        $this->loginAsEditor();
        $this->get('/admin/blog-posts/edit/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('name="user_id"');
        $this->assertResponseContains('Owner');
        $this->assertResponseContains('admin');
    }
}
