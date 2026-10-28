<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TeamSeasonService;
use App\Service\TeamSportContextService;
use App\Test\TestCase\Support\TaggingServiceFactoryTestTrait;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class TaggingServiceTest extends TestCase
{
    use TaggingServiceFactoryTestTrait;

    protected array $fixtures = [
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
     * Tests apply from data generates friendly labels for image.
     */
    public function testApplyFromDataGeneratesFriendlyLabelsForImage(): void
    {
        $service = $this->imageTaggingService();
        $images = TableRegistry::getTableLocator()->get('Images');

        $image = $images->newEntity([
            'filename' => 'tagtest.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/tagtest.jpg',
            'original_name' => 'tagtest.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'tagtest-' . time(),
            'status' => 'active',
        ]);
        $images->save($image);

        $data = [
            'person_select' => 1,
            'teamseason_select' => 1,
            'roster_select' => 1,
            'tags' => 'extra, John Doe',
        ];

        $applied = $service->applyFromData((int)$image->id, $data);

        $this->assertContains('person-1', $applied);
        $this->assertContains('team_season_roster-1', $applied);
        $this->assertNotContains('teamseason-1', $applied);

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');

        $personTag = $tagsTable->find()
            ->matching('Images', function ($q) use ($image) {
                return $q->where(['Images.id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'person-1'])
            ->first();

        $rosterTag = $tagsTable->find()
            ->matching('Images', function ($q) use ($image) {
                return $q->where(['Images.id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'team_season_roster-1'])
            ->first();

        $tsTag = $tagsTable->find()
            ->matching('Images', function ($q) use ($image) {
                return $q->where(['Images.id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'teamseason-1'])
            ->first();

        $extraTag = $tagsTable->find()
            ->matching('Images', function ($q) use ($image) {
                return $q->where(['Images.id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'extra'])
            ->first();

        $this->assertNotNull($personTag);
        $this->assertNotNull($rosterTag);
        $this->assertNull($tsTag);
        $this->assertNotNull($extraTag);
        $this->assertNotEmpty($personTag->name);
        $this->assertNotEmpty($rosterTag->name);
        $this->assertStringContainsString('John', $personTag->name);
        $this->assertMatchesRegularExpression('/(Lakers|Basketball)/', $rosterTag->name);
        $this->assertSame('extra', $extraTag->slug);
    }

    /**
     * Tests parse tags from request adds friendly team season label.
     */
    public function testParseTagsFromRequestAddsFriendlyTeamSeasonLabel(): void
    {
        $service = $this->imageTaggingService();
        $images = TableRegistry::getTableLocator()->get('Images');

        $image = $images->newEntity([
            'filename' => 'upload-teamseason.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/upload-teamseason.jpg',
            'original_name' => 'upload-teamseason.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'upload-teamseason-' . time(),
            'status' => 'active',
        ]);
        $images->save($image);

        $request = new ServerRequest([
            'post' => [
                'context' => json_encode(['type' => 'teamseason', 'id' => 1]),
            ],
        ]);

        $tags = $service->parseTagsFromRequest($request);
        $this->assertNotEmpty($tags);

        $service->attachTags((int)$image->id, $tags);
        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $tsTag = $tagsTable->find()->where(['slug' => 'teamseason-1'])->first();

        $this->assertNotNull($tsTag);
        $this->assertSame((new TeamSeasonService())->getDisplayLabel(1), (string)$tsTag->name);
    }

    /**
     * Team tags use the canonical default display name.
     */
    public function testBuildTagsFromDataUsesTeamDisplayName(): void
    {
        $team = TableRegistry::getTableLocator()->get('Teams')->get(1);
        $tags = $this->imageTaggingService()->buildTagsFromData(['team_select' => 1]);

        $this->assertSame([
            ['slug' => 'team-1', 'name' => $team->getDisplayName()],
        ], $tags);
    }

    /**
     * Sport tag names resolve from configuration instead of the legacy Sports table.
     */
    public function testBuildTagsFromDataUsesConfiguredSportName(): void
    {
        $sportOptions = (new TeamSportContextService())->getLegacySportOptions();
        $sportId = (int)array_search('Basketball', $sportOptions, true);
        $this->assertGreaterThan(0, $sportId);

        $tags = $this->blogPostTaggingService()->buildTagsFromData(['sport_select' => $sportId]);

        $this->assertSame([
            ['slug' => 'sport-' . $sportId, 'name' => 'Basketball'],
        ], $tags);
    }

    /**
     * Blog-post preset tags use their canonical slugs and display names.
     */
    public function testBuildTagsFromDataAddsCanonicalBlogPostPresetTags(): void
    {
        $tags = $this->blogPostTaggingService()->buildTagsFromData([
            'preset_tags' => ['bio', 'preview', 'recap', 'unknown'],
        ]);

        $this->assertSame([
            ['slug' => 'bio', 'name' => 'Bio'],
            ['slug' => 'preview', 'name' => 'Preview'],
            ['slug' => 'recap', 'name' => 'Recap'],
        ], $tags);
    }

    /**
     * Preset tags are only accepted in the blog-post tagging context.
     */
    public function testBuildTagsFromDataIgnoresBlogPostPresetsForImages(): void
    {
        $tags = $this->imageTaggingService()->buildTagsFromData([
            'preset_tags' => ['bio'],
        ]);

        $this->assertSame([], $tags);
    }

    /**
     * A preset selected with a matching freeform tag is applied only once.
     */
    public function testBuildTagsFromDataDeduplicatesPresetFromFreeformTags(): void
    {
        $tags = $this->blogPostTaggingService()->buildTagsFromData([
            'tags' => 'bio',
            'preset_tags' => ['bio'],
        ]);

        $this->assertSame([
            ['slug' => 'bio', 'name' => 'Bio'],
        ], $tags);
    }

    /**
     * Saving a preset canonicalizes a legacy case-variant tag slug.
     */
    public function testApplyFromDataCanonicalizesLegacyPresetTagSlug(): void
    {
        $service = $this->blogPostTaggingService();
        $service->replaceTags(1, [
            ['slug' => 'Bio', 'name' => 'Bio'],
        ]);

        $service->applyFromData(1, ['preset_tags' => ['bio']]);
        $post = TableRegistry::getTableLocator()->get('BlogPosts')->get(
            1,
            contain: ['BlogTags'],
        );
        $slugs = array_map(
            static fn($tag): string => (string)$tag->slug,
            $post->blog_tags,
        );

        $this->assertSame(['bio'], $slugs);
    }

    /**
     * Applying an empty tag selection clears existing tags on an existing subject.
     */
    public function testApplyFromDataClearsExistingTagsWhenSelectionIsEmpty(): void
    {
        $service = $this->blogPostTaggingService();
        $service->replaceTags(1, ['bio']);

        $this->assertSame([], $service->applyFromData(1, [
            'tags' => '',
            'preset_tags' => [],
        ]));
        $this->assertSame(
            0,
            TableRegistry::getTableLocator()->get('BlogPostsBlogTags')
                ->find()
                ->where(['blog_post_id' => 1])
                ->count(),
        );
    }

    /**
     * Empty tag data remains a no-op when no subject has been created yet.
     */
    public function testApplyFromDataWithNoSubjectAndNoTagsIsNoop(): void
    {
        $this->assertSame([], $this->blogPostTaggingService()->applyFromData(0, []));
    }

    /**
     * Opponent tags use the canonical default display name.
     */
    public function testBuildTagsFromDataUsesOpponentDisplayName(): void
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        if (!$opponents->getSchema()->hasColumn('opponent_mascot')) {
            $this->markTestSkipped('Test schema does not include opponent_mascot.');
        }
        $opponent = $opponents->get(1);
        $opponent->opponent_mascot = 'Bruins';
        $opponents->saveOrFail($opponent);

        $tags = $this->imageTaggingService()->buildTagsFromData(['opponent_select' => 1]);

        $this->assertSame([
            ['slug' => 'opponent-1', 'name' => 'BEL Bruins'],
        ], $tags);
    }

    /**
     * Tests replace tags for blog post creates linkage.
     */
    public function testReplaceTagsForBlogPostCreatesLinkage(): void
    {
        $service = $this->blogPostTaggingService();
        $service->replaceTags(1, ['team-1']);

        $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        $tag = $tagsTable->find()->where(['slug' => 'team-1'])->first();
        $this->assertNotNull($tag);

        $junction = TableRegistry::getTableLocator()->get('BlogPostsBlogTags');
        $count = $junction->find()->where(['blog_tag_id' => $tag->id, 'blog_post_id' => 1])->count();
        $this->assertSame(1, $count);
    }

    /**
     * Tests apply from data allows multiple persons when no roster selected.
     */
    public function testApplyFromDataAllowsMultiplePersonsWhenNoRosterSelected(): void
    {
        $service = $this->imageTaggingService();
        $images = TableRegistry::getTableLocator()->get('Images');

        $image = $images->newEntity([
            'filename' => 'tagtest-multi-person.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/tagtest-multi-person.jpg',
            'original_name' => 'tagtest-multi-person.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'tagtest-multi-person-' . time(),
            'status' => 'active',
        ]);
        $images->save($image);

        $data = [
            'person_select' => [1, 2],
            'tags' => 'extra',
        ];

        $applied = $service->applyFromData((int)$image->id, $data);

        $this->assertContains('person-1', $applied);
        $this->assertContains('person-2', $applied);
        $this->assertContains('extra', $applied);
    }

    /**
     * Tests apply from data ignores multiple persons when roster selected.
     */
    public function testApplyFromDataIgnoresMultiplePersonsWhenRosterSelected(): void
    {
        $service = $this->imageTaggingService();
        $images = TableRegistry::getTableLocator()->get('Images');

        $image = $images->newEntity([
            'filename' => 'tagtest-roster-lock.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/tagtest-roster-lock.jpg',
            'original_name' => 'tagtest-roster-lock.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'tagtest-roster-lock-' . time(),
            'status' => 'active',
        ]);
        $images->save($image);

        $data = [
            'person_select' => [1, 2],
            'roster_select' => 1,
        ];

        $applied = $service->applyFromData((int)$image->id, $data);

        $this->assertContains('team_season_roster-1', $applied);
        $this->assertContains('person-1', $applied);
        $this->assertNotContains('person-2', $applied);
    }

    /**
     * Tests apply from data generates friendly game label.
     */
    public function testApplyFromDataGeneratesFriendlyGameLabel(): void
    {
        $service = $this->imageTaggingService();
        $images = TableRegistry::getTableLocator()->get('Images');

        $image = $images->newEntity([
            'filename' => 'tagtest-game.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/tagtest-game.jpg',
            'original_name' => 'tagtest-game.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'tagtest-game-' . time(),
            'status' => 'active',
        ]);
        $images->save($image);

        $applied = $service->applyFromData((int)$image->id, [
            'game_select' => 1,
        ]);

        $this->assertContains('game-1', $applied);

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $gameTag = $tagsTable->find()->where(['slug' => 'game-1'])->first();
        $this->assertNotNull($gameTag);
        $this->assertNotSame('1', (string)$gameTag->name);
        $this->assertStringContainsString('2025-01-15', (string)$gameTag->name);
        $this->assertStringContainsString('Belmont', (string)$gameTag->name);
    }

    /**
     * Tests parse tags from request adds friendly game label.
     */
    public function testParseTagsFromRequestAddsFriendlyGameLabel(): void
    {
        $service = $this->imageTaggingService();

        $request = new ServerRequest([
            'post' => [
                'context' => json_encode(['type' => 'game', 'id' => 1]),
            ],
        ]);

        $tags = $service->parseTagsFromRequest($request);
        $this->assertNotEmpty($tags);

        $found = null;
        foreach ($tags as $tag) {
            if (is_array($tag) && ($tag['slug'] ?? null) === 'game-1') {
                $found = $tag;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertStringContainsString('2025-01-15', (string)$found['name']);
        $this->assertStringContainsString('Belmont', (string)$found['name']);
    }
}
