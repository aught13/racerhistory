<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Advertisement;
use App\Model\Entity\BlogPost;
use App\Model\Entity\BlogTag;
use App\Model\Entity\Game;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use App\Service\AdvertisingService;
use Cake\TestSuite\TestCase;

class AdvertisingServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Advertisements',
        'app.AdvertisingSettings',
        'app.Teams',
        'app.Sports',
        'app.TeamSeasons',
        'app.Games',
    ];

    private AdvertisingService $service;

    /**
     * Build the service after the advertising fixtures are loaded.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdvertisingService();
    }

    /**
     * Site sponsors are global; content sponsors match the current sport and team.
     */
    public function testGetAdvertisementsForPlacementMatchesTargetContext(): void
    {
        $advertisements = $this->service->getAdvertisementsForPlacement('below_nav', [
            'sportKey' => 'basketball',
            'teamId' => 1,
        ]);

        $this->assertSame(
            [1, 2, 3],
            array_map(static fn(Advertisement $ad): int => (int)$ad->id, $advertisements),
        );
    }

    /**
     * When both content filters are set, a page must match both of them.
     */
    public function testGetAdvertisementsForPlacementRequiresBothFiltersToMatch(): void
    {
        $otherBasketballTeam = $this->service->getAdvertisementsForPlacement('below_nav', [
            'sportKey' => 'basketball',
            'teamId' => 2,
        ]);
        $football = $this->service->getAdvertisementsForPlacement('below_nav', [
            'sportKey' => 'football',
            'teamId' => 3,
        ]);

        $this->assertSame([1, 2], array_map(static fn(Advertisement $ad): int => (int)$ad->id, $otherBasketballTeam));
        $this->assertSame([1], array_map(static fn(Advertisement $ad): int => (int)$ad->id, $football));
    }

    /**
     * Unknown placements and pages without content context should not show content sponsors.
     */
    public function testGetAdvertisementsForPlacementHandlesUnknownAndUnscopedPages(): void
    {
        $this->assertSame([], $this->service->getAdvertisementsForPlacement('not-a-placement'));

        $unscoped = $this->service->getAdvertisementsForPlacement('below_nav');
        $this->assertSame([1], array_map(static fn(Advertisement $ad): int => (int)$ad->id, $unscoped));
    }

    /**
     * Valid content campaigns persist their sport and team criteria.
     */
    public function testSaveAdvertisementPersistsContentTarget(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Basketball coverage',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => 'basketball',
            'team_id' => '1',
            'is_active' => '1',
            'html' => '<a href="/news">Basketball coverage</a>',
            'mode' => 'custom',
            'display_order' => '4',
        ]);

        $this->assertTrue($this->service->saveAdvertisement($advertisement), print_r($advertisement->getErrors(), true));
        $saved = $this->service->getAdvertisement((int)$advertisement->id);

        $this->assertInstanceOf(Advertisement::class, $saved);
        $this->assertSame('content', $saved->target_type);
        $this->assertSame('basketball', $saved->sport_key);
        $this->assertSame(1, (int)$saved->team_id);
        $this->assertSame(4, (int)$saved->display_order);
    }

    /**
     * A content campaign can target a team without selecting its sport.
     */
    public function testTeamOnlySponsorMatchesJustTheSelectedTeam(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Team-only sponsor',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => '',
            'team_id' => '1',
            'is_active' => '1',
            'html' => '<div>Team only</div>',
            'mode' => 'custom',
            'display_order' => '10',
        ]);
        $this->assertTrue($this->service->saveAdvertisement($advertisement));

        $matching = $this->service->getAdvertisementsForPlacement('below_nav', [
            'sportKey' => 'basketball',
            'teamId' => 1,
        ]);
        $otherTeam = $this->service->getAdvertisementsForPlacement('below_nav', [
            'sportKey' => 'basketball',
            'teamId' => 2,
        ]);

        $this->assertContains((int)$advertisement->id, array_map(
            static fn(Advertisement $ad): int => (int)$ad->id,
            $matching,
        ));
        $this->assertNotContains((int)$advertisement->id, array_map(
            static fn(Advertisement $ad): int => (int)$ad->id,
            $otherTeam,
        ));
    }

    /**
     * Content campaigns need at least one sport or team filter.
     */
    public function testSaveAdvertisementRejectsUnfilteredContentTarget(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Unfiltered content campaign',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => '',
            'team_id' => '',
            'is_active' => '0',
            'html' => '',
            'mode' => 'custom',
            'display_order' => '0',
        ]);

        $this->assertFalse($this->service->saveAdvertisement($advertisement));
        $this->assertArrayHasKey('sport_key', $advertisement->getErrors());
    }

    /**
     * A selected team must belong to a selected sport.
     */
    public function testSaveAdvertisementRejectsTeamSportMismatch(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Mismatched campaign',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => 'basketball',
            'team_id' => '3',
            'is_active' => '0',
            'html' => '',
            'mode' => 'custom',
            'display_order' => '0',
        ]);

        $this->assertFalse($this->service->saveAdvertisement($advertisement));
        $this->assertArrayHasKey('team_id', $advertisement->getErrors());
    }

    /**
     * An unknown sport key cannot be saved as a content filter.
     */
    public function testSaveAdvertisementRejectsUnknownSport(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Unknown sport',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => 'curling',
            'team_id' => '',
            'is_active' => '0',
            'html' => '',
            'mode' => 'custom',
            'display_order' => '0',
        ]);

        $this->assertFalse($this->service->saveAdvertisement($advertisement));
        $this->assertArrayHasKey('sport_key', $advertisement->getErrors());
    }

    /**
     * Active custom campaigns require HTML markup.
     */
    public function testSaveAdvertisementRequiresCustomMarkupWhenActive(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Empty custom campaign',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'is_active' => '1',
            'html' => '',
            'mode' => 'custom',
            'display_order' => '0',
        ]);

        $this->assertFalse($this->service->saveAdvertisement($advertisement));
        $this->assertArrayHasKey('html', $advertisement->getErrors());
    }

    /**
     * Active GPT campaigns require a unit path even when fallback HTML is supplied.
     */
    public function testSaveAdvertisementRequiresGptUnitPathWhenActive(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Missing GPT path',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'is_active' => '1',
            'html' => '<div>Fallback</div>',
            'mode' => 'gpt',
            'gpt_unit_path' => '',
            'display_order' => '0',
        ]);

        $this->assertFalse($this->service->saveAdvertisement($advertisement));
        $this->assertArrayHasKey('gpt_unit_path', $advertisement->getErrors());
    }

    /**
     * Site-wide campaigns discard sport and team values.
     */
    public function testSiteCampaignClearsContentFilters(): void
    {
        $advertisement = $this->service->patchAdvertisement($this->service->newAdvertisement(), [
            'title' => 'Global campaign',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'sport_key' => 'basketball',
            'team_id' => '1',
            'is_active' => '1',
            'html' => '<div>Global campaign</div>',
            'mode' => 'custom',
            'display_order' => '0',
        ]);

        $this->assertTrue($this->service->saveAdvertisement($advertisement));
        $this->assertNull($advertisement->sport_key);
        $this->assertNull($advertisement->team_id);
    }

    /**
     * Provider settings persist independently from general site options.
     */
    public function testSaveAndReadProviderSettings(): void
    {
        $this->assertTrue($this->service->saveSettings([
            'global_script' => '<script src="/provider.js"></script>',
            'publisher_id' => 'ca-pub-1234567890',
        ]));
        $this->assertSame([
            'global_script' => '<script src="/provider.js"></script>',
            'publisher_id' => 'ca-pub-1234567890',
        ], $this->service->getSettings());

        $this->assertFalse($this->service->saveSettings([
            'global_script' => ['not' => 'text'],
            'publisher_id' => 'ca-pub-9876543210',
        ]));
        $this->assertSame(
            'ca-pub-1234567890',
            $this->service->getSettings()['publisher_id'],
        );
    }

    /**
     * Blog post game/team-season tags resolve to sport and team targeting context.
     */
    public function testGetBlogPostContextUsesTaggedTeamSeasonAndGame(): void
    {
        $teamSeasonPost = new BlogPost([
            'blog_tags' => [new BlogTag(['slug' => 'teamseason-1'])],
        ]);
        $gamePost = new BlogPost([
            'blog_tags' => [new BlogTag(['slug' => 'game-1'])],
        ]);

        $this->assertSame(
            ['sportKey' => 'basketball', 'teamId' => 1],
            $this->service->getBlogPostContext($teamSeasonPost),
        );
        $this->assertSame(
            ['sportKey' => 'basketball', 'teamId' => 1],
            $this->service->getBlogPostContext($gamePost),
        );
    }

    /**
     * Stats pages use the selected sport and team filter as targeting context.
     */
    public function testResolvePageContextUsesPublicFiltersAndCurrentSport(): void
    {
        $this->assertSame(
            ['sportKey' => 'basketball', 'teamId' => 2],
            $this->service->resolvePageContext([
                'publicScope' => ['sportKey' => 'basketball', 'teamId' => 2],
            ]),
        );
        $this->assertSame(
            ['sportKey' => 'football', 'teamId' => null],
            $this->service->resolvePageContext(['currentSport' => 'football']),
        );

        $team = new Team(['sport_key' => 'football']);
        $team->set('id', 3, ['guard' => false]);
        $teamSeason = new TeamSeason();
        $teamSeason->set('team', $team, ['guard' => false]);
        $game = new Game();
        $game->set('team_season', $teamSeason, ['guard' => false]);
        $this->assertSame(
            ['sportKey' => 'football', 'teamId' => 3],
            $this->service->resolvePageContext(['game' => $game]),
        );
    }

    /**
     * Publisher ID normalization covers raw, canonical, legacy, and invalid values.
     */
    public function testNormalizePublisherId(): void
    {
        $this->assertSame('ca-pub-123', $this->service->normalizePublisherId('123'));
        $this->assertSame('ca-pub-123', $this->service->normalizePublisherId('CA-PUB-123'));
        $this->assertSame('ca-pub-123', $this->service->normalizePublisherId('pub-123'));
        $this->assertSame('custom-value', $this->service->normalizePublisherId('custom-value'));
        $this->assertSame('', $this->service->normalizePublisherId(''));
    }

    /**
     * ads.txt writes are limited to a valid publisher ID and the supplied directory.
     */
    public function testWriteAdsTxtWritesAndRejectsInvalidPublisherIds(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'racerhistory-ads-' . uniqid('', true);
        mkdir($directory);

        try {
            $this->assertFalse($this->service->writeAdsTxt($directory));
            $this->assertFileDoesNotExist($directory . DIRECTORY_SEPARATOR . 'ads.txt');

            $this->assertTrue($this->service->saveSettings(['publisher_id' => '1234567890']));
            $this->assertTrue($this->service->writeAdsTxt($directory));
            $this->assertSame(
                "google.com, ca-pub-1234567890, DIRECT, f08c47fec0942fa0\n",
                file_get_contents($directory . DIRECTORY_SEPARATOR . 'ads.txt'),
            );
        } finally {
            $adsTxt = $directory . DIRECTORY_SEPARATOR . 'ads.txt';
            if (file_exists($adsTxt)) {
                unlink($adsTxt);
            }
            rmdir($directory);
        }
    }
}
