<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\ImageTag;
use App\Service\ImageTagUiService;
use App\Service\TeamSeasonRosterService;
use Cake\TestSuite\TestCase;

class ImageTagUiServiceTest extends TestCase
{
    /**
     * Tests format tags for ui builds freeform tag string and reorders.
     */
    public function testFormatTagsForUiBuildsFreeformTagStringAndReorders(): void
    {
        $rosterService = $this->createMock(TeamSeasonRosterService::class);
        $rosterService->expects($this->never())->method('getRosterDisplayData');

        $service = new ImageTagUiService($rosterService);

        $freeform = new ImageTag(['slug' => 'cool', 'name' => 'cool']);
        $entityTag = new ImageTag(['slug' => 'team-1', 'name' => 'Team One']);

        $result = $service->formatTagsForUi([$freeform, $entityTag]);

        $this->assertSame('cool', $result['tagString']);
        $this->assertCount(2, $result['currentTags']);
        $this->assertSame('team-1', (string)($result['currentTags'][0]->slug ?? ''));
        $this->assertSame('cool', (string)($result['currentTags'][1]->slug ?? ''));
    }

    /**
     * Tests format tags for ui rewrites roster display name when available.
     */
    public function testFormatTagsForUiRewritesRosterDisplayNameWhenAvailable(): void
    {
        $rosterService = $this->createMock(TeamSeasonRosterService::class);
        $rosterService->expects($this->once())
            ->method('getRosterDisplayData')
            ->with(5)
            ->willReturn([
                'team_season_label' => 'Lakers (2024-2025)',
            ]);

        $service = new ImageTagUiService($rosterService);

        $rosterTag = new ImageTag(['slug' => 'team_season_roster-5', 'name' => 'Roster #5']);
        $result = $service->formatTagsForUi([$rosterTag]);

        $this->assertCount(1, $result['currentTags']);
        $this->assertSame('Lakers (2024-2025)', (string)($result['currentTags'][0]->name ?? ''));
    }
}
