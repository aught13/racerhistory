<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\BlogPost;
use App\Model\Table\BlogTagsTable;
use App\Model\Table\ImageTagsTable;
use App\Service\BlogPostsAdminService;
use App\Service\GameService;
use App\Service\ImagesAdminService;
use App\Service\OpponentService;
use App\Service\SiteService;
use App\Service\TaggingService;
use App\Service\TaggingServiceFactory;
use App\Service\TagModalService;
use App\Service\TeamSeasonService;
use App\Service\TeamService;
use App\Service\TeamSportContextService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;

class TagModalServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    private ImagesAdminService $imagesAdminService;

    private BlogPostsAdminService $blogPostsAdminService;

    private TeamService $teamService;

    private TeamSeasonService $teamSeasonService;

    private GameService $gameService;

    private SiteService $siteService;

    private OpponentService $opponentService;

    private TeamSportContextService $teamSportContextService;

    private ImageTagsTable $imageTagsTable;

    private BlogTagsTable $blogTagsTable;

    private TaggingServiceFactory $taggingServiceFactory;

    private TagModalService $service;

    /**
     * Initialize the tag modal service with fixture-backed dependencies.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->imagesAdminService = $this->createMock(ImagesAdminService::class);
        $this->blogPostsAdminService = $this->createMock(BlogPostsAdminService::class);
        $this->teamService = $this->createMock(TeamService::class);
        $this->teamSeasonService = $this->createMock(TeamSeasonService::class);
        $this->gameService = $this->createMock(GameService::class);
        $this->siteService = $this->createMock(SiteService::class);
        $this->opponentService = $this->createMock(OpponentService::class);
        $this->teamSportContextService = $this->createMock(TeamSportContextService::class);
        $imageTagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $blogTagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        if (!$imageTagsTable instanceof ImageTagsTable || !$blogTagsTable instanceof BlogTagsTable) {
            throw new RuntimeException('Tag tables were not configured for this test.');
        }
        $this->imageTagsTable = $imageTagsTable;
        $this->blogTagsTable = $blogTagsTable;
        $this->taggingServiceFactory = $this->createMock(TaggingServiceFactory::class);

        $this->service = new TagModalService(
            $this->imagesAdminService,
            $this->blogPostsAdminService,
            $this->teamService,
            $this->teamSeasonService,
            $this->gameService,
            $this->siteService,
            $this->opponentService,
            $this->teamSportContextService,
            $this->imageTagsTable,
            $this->blogTagsTable,
            $this->taggingServiceFactory,
        );
    }

    /**
     * Verify supported aliases produce canonical modal data.
     *
     * @return void
     */
    public function testModalViewDataCanonicalizesImageAndBlogPostAliases(): void
    {
        $identity = (object)['id' => 1];
        $post = new BlogPost();
        $this->imagesAdminService->expects($this->once())
            ->method('getTagsPageData')
            ->with(5, $identity)
            ->willReturn(['image' => (object)['id' => 5]]);
        $this->blogPostsAdminService->expects($this->once())
            ->method('newEntity')
            ->willReturn($post);
        $this->blogPostsAdminService->expects($this->once())
            ->method('buildFormViewData')
            ->with($post, $identity)
            ->willReturn(['post' => $post, 'currentTags' => []]);

        $imageData = $this->service->modalViewData('image', 5, $identity);
        $postData = $this->service->modalViewData('post', 0, $identity);

        $this->assertSame('images', $imageData['subject']);
        $this->assertSame(5, $imageData['subjectId']);
        $this->assertSame('blogposts', $postData['subject']);
        $this->assertSame($post, $postData['post']);
    }

    /**
     * Verify unknown subjects use the fallback tag data.
     *
     * @return void
     */
    public function testUnknownSubjectUsesEmptyTagFallbackData(): void
    {
        $this->teamService->expects($this->once())->method('getTeamsForSelect')->willReturn([['id' => 1]]);
        $this->teamSeasonService->expects($this->once())
            ->method('getTeamSeasonsForSelect')
            ->willReturn([['id' => 2]]);
        $this->gameService->expects($this->once())
            ->method('getRecentGamesForSelect')
            ->with(200)
            ->willReturn([['id' => 3]]);
        $this->siteService->expects($this->once())->method('getSitesForSelect')->willReturn([['id' => 4]]);
        $this->opponentService->expects($this->once())
            ->method('getOpponentsForSelect')
            ->willReturn([['id' => 5]]);
        $this->teamSportContextService->expects($this->once())
            ->method('getLegacySportOptions')
            ->willReturn([6 => 'Basketball']);

        $data = $this->service->modalViewData('Custom-Subject', 9, null);

        $this->assertSame('custom-subject', $data['subject']);
        $this->assertSame(9, $data['subjectId']);
        $this->assertSame([], $data['currentTags']);
        $this->assertSame('', $data['tagString']);
        $this->assertSame([['id' => 6, 'label' => 'Basketball']], $data['sports']);
    }

    /**
     * Verify image tag updates return labels and permitted form fields.
     *
     * @return void
     */
    public function testApplyImageAliasReturnsLabelsAndOnlyAllowedFields(): void
    {
        $tags = $this->imageTagsTable->newEntity(['slug' => 'sample-tag', 'name' => 'Sample Tag']);
        $this->imageTagsTable->saveOrFail($tags);
        $taggingService = $this->getMockBuilder(TaggingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyFromData'])
            ->getMock();
        $taggingService->expects($this->once())
            ->method('applyFromData')
            ->with(4, ['tags' => 'sample-tag', 'ignored' => 'value'])
            ->willReturn(['sample-tag']);
        $this->taggingServiceFactory->expects($this->once())->method('forImages')->willReturn($taggingService);

        $result = $this->service->apply('image', 4, ['tags' => 'sample-tag', 'ignored' => 'value']);

        $this->assertTrue($result['success']);
        $this->assertSame(['sample-tag'], $result['applied']);
        $this->assertSame([['slug' => 'sample-tag', 'name' => 'Sample Tag']], $result['tags']);
        $this->assertSame(['tags' => 'sample-tag'], $result['formFields']);
    }

    /**
     * Verify blog post preset tags are retained and unsupported subjects fail.
     *
     * @return void
     */
    public function testApplyBlogPostAliasKeepsPresetTagsAndUnknownSubjectsFail(): void
    {
        $tag = $this->blogTagsTable->newEntity(['slug' => 'bio', 'name' => 'Bio']);
        $this->blogTagsTable->saveOrFail($tag);
        $taggingService = $this->getMockBuilder(TaggingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyFromData'])
            ->getMock();
        $taggingService->expects($this->once())
            ->method('applyFromData')
            ->with(8, ['preset_tags' => ['bio']])
            ->willReturn(['bio']);
        $this->taggingServiceFactory->expects($this->once())->method('forBlogPosts')->willReturn($taggingService);

        $result = $this->service->apply('blog_post', 8, ['preset_tags' => ['bio']]);
        $unknownResult = $this->service->apply('unknown', 8, []);

        $this->assertTrue($result['success']);
        $this->assertSame([['slug' => 'bio', 'name' => 'Bio']], $result['tags']);
        $this->assertSame(['preset_tags' => ['bio']], $result['formFields']);
        $this->assertSame(['success' => false], $unknownResult);
    }
}
