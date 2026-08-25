<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DOMDocument;
use DOMXPath;

/**
 * App\Controller\SeasonsController Test Case
 *
 * @link \App\Controller\SeasonsController
 */
class SeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('Seasons');
        $this->assertResponseContains('Team Seasons');
        // Ensure the table and controls are present in the rendered HTML
        $this->assertResponseContains('id="seasons-table"');
        $this->assertResponseContains('id="seasons-controls"');
        $this->assertResponseContains('id="searchbuilder-panel"');
        $this->assertResponseContains('id="seasons-table-frame"');
        $this->assertResponseContains('data-controller="seasons-page"');
    }

    /**
     * Tests index displays team seasons.
     */
    public function testIndexDisplaysTeamSeasons(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();

        // Check that view variable is set
        $teamSeasons = $this->viewVariable('teamSeasons');
        $this->assertIsArray($teamSeasons);
        $recordSummaries = $this->viewVariable('recordSummaries');
        $this->assertIsArray($recordSummaries);
    }

    /**
     * Tests index postseason type label replaces pct.
     */
    public function testIndexPostseasonTypeLabelReplacesPct(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();

        $html = (string)$this->_response->getBody();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cell = $xpath->query('//table[@id="seasons-table"]/tbody/tr[1]/td[last()]')->item(0);
        $this->assertNotNull($cell);
        $this->assertSame('-', trim((string)$cell->textContent));
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Log');
        $this->assertResponseContains('data-controller="season-view"');
    }

    /**
     * Tests view with invalid id.
     */
    public function testViewWithInvalidId(): void
    {
        $this->get('/seasons/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * Tests view sets variables.
     */
    public function testViewSetsVariables(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();

        $teamSeason = $this->viewVariable('teamSeason');
        $this->assertNotNull($teamSeason);

        $images = $this->viewVariable('images');
        $this->assertIsArray($images);

        $previewPosts = $this->viewVariable('previewPosts');
        $this->assertIsArray($previewPosts);

        $reviewPosts = $this->viewVariable('reviewPosts');
        $this->assertIsArray($reviewPosts);

        $otherPosts = $this->viewVariable('otherPosts');
        $this->assertIsArray($otherPosts);

        $games = $this->viewVariable('games');
        $this->assertIsArray($games);

        $roster = $this->viewVariable('roster');
        $this->assertIsArray($roster);

        $recordSummary = $this->viewVariable('recordSummary');
        $this->assertIsArray($recordSummary);
    }

    /**
     * Tests public season view includes previous/next team-season navigation.
     */
    public function testViewIncludesPreviousAndNextTeamSeasonNavigation(): void
    {
        $seasonsTable = $this->getTableLocator()->get('Seasons');
        $teamSeasonsTable = $this->getTableLocator()->get('TeamSeasons');

        $previousSeason = $seasonsTable->newEntity(['start' => 2021, 'end' => 2022]);
        $nextSeason = $seasonsTable->newEntity(['start' => 2025, 'end' => 2026]);
        $seasonsTable->saveOrFail($previousSeason);
        $seasonsTable->saveOrFail($nextSeason);

        $teamSeasonsTable->saveOrFail($teamSeasonsTable->newEntity([
            'team_id' => 1,
            'season_id' => $previousSeason->id,
            'semester' => 1,
        ]));
        $teamSeasonsTable->saveOrFail($teamSeasonsTable->newEntity([
            'team_id' => 1,
            'season_id' => $nextSeason->id,
            'semester' => 1,
        ]));

        $this->get('/seasons/1');
        $this->assertResponseOk();

        $previousTeamSeason = $this->viewVariable('previousTeamSeason');
        $this->assertNotNull($previousTeamSeason);
        $this->assertSame(2022, (int)$previousTeamSeason->season->end);

        $nextTeamSeason = $this->viewVariable('nextTeamSeason');
        $this->assertNotNull($nextTeamSeason);
        $this->assertSame(2026, (int)$nextTeamSeason->season->end);
    }

    /**
     * Tests authorization skipped.
     */
    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/seasons');
        $this->assertResponseOk();

        $this->get('/seasons/1');
        $this->assertResponseOk();
    }

    /**
     * Tests splits.
     */
    public function testSplits(): void
    {
        $this->get('/seasons/splits?team=all');
        $this->assertResponseOk();
        $this->assertResponseContains('Season Splits');
        $this->assertResponseContains('id="season-splits-table"');
        $this->assertResponseContains('id="seasons-table-frame"');
        $this->assertResponseContains('data-controller="seasons-page"');
    }

    /**
     * Tests splits turbo frame.
     */
    public function testSplitsTurboFrame(): void
    {
        $this->configRequest([
            'headers' => ['Turbo-Frame' => 'seasons-table-frame'],
        ]);
        $this->get('/seasons/splits?team=all');
        $this->assertResponseOk();
        $this->assertResponseContains('id="seasons-table-frame"');
        $this->assertResponseContains('id="season-splits-table"');
    }

    /**
     * Tests splits postseason type shows dash when missing.
     */
    public function testSplitsPostseasonTypeShowsDashWhenMissing(): void
    {
        $this->get('/seasons/splits?team=all');
        $this->assertResponseOk();

        $html = (string)$this->_response->getBody();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cell = $xpath->query('//table[@id="season-splits-table"]/tbody/tr[1]/td[last()]')->item(0);
        $this->assertNotNull($cell);
        $this->assertSame('-', trim((string)$cell->textContent));
    }

    /**
     * Test that games are sorted by game_date ascending in view
     */
    public function testViewGamesSortedByDateAscending(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();

        $games = $this->viewVariable('games');
        $this->assertIsArray($games);

        $dates = [];
        foreach ($games as $game) {
            if ($game->game_date) {
                $dates[] = $game->game_date->format('Y-m-d');
            }
        }

        // Verify dates are in ascending order
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates, 'Games should be sorted by date ascending');
    }

    /**
     * Tests season view uses direct storage-backed image URLs for billboards and roster avatars.
     */
    public function testViewUsesProfileBasedImageUrls(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();

        $teamSeason = $this->viewVariable('teamSeason');
        if (!empty($teamSeason?->team_season_image)) {
            $this->assertResponseContains('/img/storage/');
        }

        $roster = $this->viewVariable('roster');
        $hasRosterImage = false;
        foreach ($roster as $entry) {
            if (!empty($entry?->person?->person_image)) {
                $hasRosterImage = true;
                break;
            }
        }
        if ($hasRosterImage) {
            $this->assertResponseContains('/img/storage/');
        }
    }
}
