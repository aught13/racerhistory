<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TaggingService;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class TaggingServiceTest extends TestCase
{
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

    public function testApplyFromDataGeneratesFriendlyLabelsForImage(): void
    {
        $service = TaggingService::forImages();
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

    public function testParseTagsFromRequestAddsFriendlyTeamSeasonLabel(): void
    {
        $service = TaggingService::forImages();
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
        $this->assertStringContainsString("Men's Basketball", (string)$tsTag->name);
    }

    public function testReplaceTagsForBlogPostCreatesLinkage(): void
    {
        $service = TaggingService::forBlogPosts();
        $service->replaceTags(1, ['team-1']);

        $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
        $tag = $tagsTable->find()->where(['slug' => 'team-1'])->first();
        $this->assertNotNull($tag);

        $junction = TableRegistry::getTableLocator()->get('BlogPostsBlogTags');
        $count = $junction->find()->where(['blog_tag_id' => $tag->id, 'blog_post_id' => 1])->count();
        $this->assertSame(1, $count);
    }

    public function testApplyFromDataAllowsMultiplePersonsWhenNoRosterSelected(): void
    {
        $service = TaggingService::forImages();
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

    public function testApplyFromDataIgnoresMultiplePersonsWhenRosterSelected(): void
    {
        $service = TaggingService::forImages();
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

    public function testApplyFromDataGeneratesFriendlyGameLabel(): void
    {
        $service = TaggingService::forImages();
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

    public function testParseTagsFromRequestAddsFriendlyGameLabel(): void
    {
        $service = TaggingService::forImages();

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
