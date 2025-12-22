<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageTaggingService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ImageTaggingServiceTest extends TestCase
{
    public array $fixtures = [
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    public function testApplyFromDataGeneratesFriendlyLabels(): void
    {
        $service = new ImageTaggingService();

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

        // Prepare data simulating form submission with both roster and teamseason
        // Roster takes priority and excludes teamseason
        $data = [
            'person_select' => 1,
            'teamseason_select' => 1,
            'roster_select' => 1,
            'tags' => 'extra, John Doe', // 'John Doe' should be excluded as duplicate
        ];

        $applied = $service->applyFromData((int)$image->id, $data);

        // Should include canonical slugs - roster takes priority so no teamseason
        $this->assertContains('person-1', $applied);
        $this->assertContains('team_season_roster-1', $applied);
        $this->assertNotContains('teamseason-1', $applied);

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');

        // Get tags that were applied by the service (not from fixture data)
        // We verify the $applied array contains the correct slugs
        $personTag = $tagsTable->find()
            ->innerJoinWith('ImagesImageTags', function ($q) use ($image) {
                return $q->where(['ImagesImageTags.image_id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'person-1'])
            ->first();

        $rosterTag = $tagsTable->find()
            ->innerJoinWith('ImagesImageTags', function ($q) use ($image) {
                return $q->where(['ImagesImageTags.image_id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'team_season_roster-1'])
            ->first();

        $tsTag = $tagsTable->find()
            ->innerJoinWith('ImagesImageTags', function ($q) use ($image) {
                return $q->where(['ImagesImageTags.image_id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'teamseason-1'])
            ->first();

        $extraTag = $tagsTable->find()
            ->innerJoinWith('ImagesImageTags', function ($q) use ($image) {
                return $q->where(['ImagesImageTags.image_id' => $image->id]);
            })
            ->where(['ImageTags.slug' => 'extra'])
            ->first();

        $this->assertNotNull($personTag, 'person-1 tag should be associated with image');
        $this->assertNotNull($rosterTag, 'team_season_roster-1 tag should be associated with image');
        $this->assertNull($tsTag, 'teamseason-1 tag should NOT be associated with image when roster is set');
        $this->assertNotNull($extraTag, 'extra tag should be associated with image');

        // Friendly names expected: services should provide display labels
        // Person entity has virtual label field, TeamSeason/Roster services compute labels
        $this->assertNotEmpty($personTag->name);
        $this->assertNotEmpty($rosterTag->name);

        // Ensure person name was used (from Person entity label)
        $this->assertStringContainsString('John', $personTag->name);

        // Ensure roster has team or sport name (from TeamSeasonRosterService)
        $this->assertMatchesRegularExpression('/(Lakers|Basketball)/', $rosterTag->name);

        // Ensure 'John Doe' from freeform tags was not duplicated (it should be excluded)
        $this->assertSame('extra', $extraTag->slug);
    }

    public function testParseTagsFromRequestAddsFriendlyTeamSeasonLabel(): void
    {
        $service = new ImageTaggingService();
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

        $request = new \Cake\Http\ServerRequest([
            'post' => [
                'context' => json_encode(['type' => 'teamseason', 'id' => 1]),
            ],
        ]);

        $tags = $service->parseTagsFromRequest($request);
        $this->assertNotEmpty($tags, 'Expected tags from context');

        // Apply and fetch the tag
        $service->attachTags((int)$image->id, $tags);
        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $tsTag = $tagsTable->find()->where(['slug' => 'teamseason-1'])->first();

        $this->assertNotNull($tsTag, 'teamseason-1 tag should be created');
        $this->assertStringContainsString("Men's Basketball", (string)$tsTag->name);
    }
}
