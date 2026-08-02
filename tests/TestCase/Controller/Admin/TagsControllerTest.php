<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TagsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.Places',
        'app.Sites',
        'app.Opponents',
        'app.GameTypes',
        'app.Games',
    ];

    /**
     * Test that the modal action requires authentication.
     */
    public function testModalRequiresAuth(): void
    {
        $this->get('/admin/tags/modal/images/1');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test that the modal action returns HTML for an image.
     */
    public function testModalReturnsHtmlForImage(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tags/modal/images/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('data-tag-modal-fields="1"', $body);
        $this->assertStringContainsString('data-apply-url="/admin/tags/apply/images/1"', $body);
    }

    /**
     * Test that the apply action attaches an image tag.
     */
    public function testApplyAttachesImageTag(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/images/1', [
            'tags' => 'extra-tag',
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue((bool)($payload['success'] ?? false));

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $tag = $tagsTable->find()->where(['slug' => 'extra-tag'])->first();
        $this->assertNotNull($tag);

        $junction = TableRegistry::getTableLocator()->get('ImagesImageTags');
        $count = $junction->find()->where(['image_tag_id' => $tag->id, 'image_id' => 1])->count();
        $this->assertSame(1, (int)$count);
    }

    /**
     * Test that the modal action returns HTML for a blog post.
     */
    public function testModalReturnsHtmlForBlogPost(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tags/modal/blogposts/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('data-tag-modal-fields="1"', $body);
        $this->assertStringContainsString('data-apply-url="/admin/tags/apply/blogposts/1"', $body);
    }

    /**
     * Test that the apply action allows an AJAX POST without a security token.
     */
    public function testApplyAllowsAjaxPostWithoutSecurityToken(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        $this->post('/admin/tags/apply/blogposts/1', []);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertSame([], $payload['applied'] ?? null);
        $this->assertSame([], $payload['tags'] ?? null);
    }

    /**
     * Test that the apply action attaches a blog tag.
     */
    public function testApplyAttachesBlogTag(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/blogposts/1', [
            'tags' => 'team-1',
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue((bool)($payload['success'] ?? false));

        $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        $tag = $tagsTable->find()->where(['slug' => 'team-1'])->first();
        $this->assertNotNull($tag);

        $junction = TableRegistry::getTableLocator()->get('BlogPostsBlogTags');
        $count = $junction->find()->where(['blog_tag_id' => $tag->id, 'blog_post_id' => 1])->count();
        $this->assertSame(1, (int)$count);
    }
}
