<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Service\TaggingService;
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
        $this->assertStringNotContainsString('name="preset_tags[]"', $body);
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
        $this->assertStringContainsString('name="preset_tags[]"', $body);
        $this->assertStringContainsString('>Bio</label>', $body);
        $this->assertStringContainsString('>Preview</label>', $body);
        $this->assertStringContainsString('>Recap</label>', $body);
    }

    /**
     * Selected preset tags are checked and excluded from the freeform input.
     */
    public function testModalChecksExistingPresetTags(): void
    {
        $this->mockIdentity();
        TaggingService::forBlogPosts()->replaceTags(1, [
            ['slug' => 'Bio', 'name' => 'Bio'],
        ]);

        $this->get('/admin/tags/modal/blogposts/1');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertMatchesRegularExpression(
            '/id="preset-tag-bio"[\s\S]*?value="bio"[\s\S]*?checked/',
            $body,
        );
        $this->assertStringNotContainsString('>Bio</textarea>', $body);
    }

    /**
     * Test that blog-post aliases map to canonical blogposts subject.
     */
    public function testModalAliasPostReturnsBlogPostsModal(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tags/modal/post/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('data-apply-url="/admin/tags/apply/blogposts/1"', $body);
    }

    /**
     * Test that unknown subjects render fallback modal content.
     */
    public function testModalFallbackForUnknownSubject(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tags/modal/custom-subject/9');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        $this->assertStringContainsString('data-tag-modal-fields="1"', $body);
        $this->assertStringContainsString('data-apply-url="/admin/tags/apply/custom-subject/9"', $body);
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

    /**
     * Preset tags are attached with canonical names and slugs.
     */
    public function testApplyAttachesBlogPostPresetTags(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/blogposts/1', [
            'preset_tags' => ['bio', 'recap'],
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertEqualsCanonicalizing(['bio', 'recap'], $payload['applied'] ?? []);

        $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        $bio = $tagsTable->find()->where(['slug' => 'bio'])->first();
        $recap = $tagsTable->find()->where(['slug' => 'recap'])->first();
        $this->assertNotNull($bio);
        $this->assertNotNull($recap);
        $this->assertSame('Bio', (string)$bio->name);
        $this->assertSame('Recap', (string)$recap->name);

        $junction = TableRegistry::getTableLocator()->get('BlogPostsBlogTags');
        $this->assertSame(1, $junction->find()
            ->where(['blog_tag_id' => $bio->id, 'blog_post_id' => 1])
            ->count());
        $this->assertSame(1, $junction->find()
            ->where(['blog_tag_id' => $recap->id, 'blog_post_id' => 1])
            ->count());
    }

    /**
     * Unchecking the only preset tag removes it from an existing blog post.
     */
    public function testApplyRemovesUnselectedPresetTags(): void
    {
        $this->mockIdentity();
        TaggingService::forBlogPosts()->applyFromData(1, [
            'preset_tags' => ['bio'],
        ]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/blogposts/1', [
            'tags' => '',
            'preset_tags' => [],
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertSame([], $payload['applied'] ?? null);
        $this->assertSame(
            0,
            TableRegistry::getTableLocator()->get('BlogPostsBlogTags')
                ->find()
                ->where(['blog_post_id' => 1])
                ->count(),
        );
    }

    /**
     * Test that blog_post alias applies tags and returns filtered form fields.
     */
    public function testApplyBlogPostAliasReturnsFilteredFormFields(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/blog_post/1', [
            'tags' => 'team-1',
            'person_select' => 42,
            'ignored' => 'drop-me',
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertSame('team-1', $payload['formFields']['tags'] ?? null);
        $this->assertSame('42', (string)($payload['formFields']['person_select'] ?? ''));
        $this->assertArrayNotHasKey('ignored', $payload['formFields'] ?? []);
    }

    /**
     * Test unsupported apply subjects return success false JSON.
     */
    public function testApplyUnknownSubjectReturnsSuccessFalse(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/tags/apply/unknown/1', ['tags' => 'no-op']);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse((bool)($payload['success'] ?? true));
    }
}
