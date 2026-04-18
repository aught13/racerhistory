<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SeasonsController Test Case
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
    }

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

    public function testIndexPostseasonTypeLabelReplacesPct(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();

        $html = (string)$this->_response->getBody();
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $cell = $xpath->query('//table[@id="seasons-table"]/tbody/tr[1]/td[last()]')->item(0);
        $this->assertNotNull($cell);
        $this->assertSame('-', trim((string)$cell->textContent));
    }

    public function testView(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Log');
    }

    public function testViewWithInvalidId(): void
    {
        $this->get('/seasons/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

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

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/seasons');
        $this->assertResponseOk();

        $this->get('/seasons/1');
        $this->assertResponseOk();
    }

    public function testSplits(): void
    {
        $this->get('/seasons/splits?team=all');
        $this->assertResponseOk();
        $this->assertResponseContains('Season Splits');
        $this->assertResponseContains('id="season-splits-table"');
        $this->assertResponseContains('id="seasons-table-frame"');
    }

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

    public function testSplitsPostseasonTypeShowsDashWhenMissing(): void
    {
        $this->get('/seasons/splits?team=all');
        $this->assertResponseOk();

        $html = (string)$this->_response->getBody();
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
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
}
