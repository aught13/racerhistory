<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\GamesController Test Case
 *
 * @link \App\Controller\GamesController
 */
class GamesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
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
        'app.StatBasketGameBox',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.TeamSeasonRosters',
        'app.Persons',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/games');
        $this->assertResponseOk();
        $this->assertResponseContains('Games');
        $this->assertResponseContains('Explore Men\'s Basketball game history');
    }

    /**
     * Tests index displays search types.
     */
    public function testIndexDisplaysSearchTypes(): void
    {
        $this->get('/games');
        $this->assertResponseOk();

        $searchTypes = $this->viewVariable('searchTypes');
        $this->assertIsArray($searchTypes);
        $this->assertArrayHasKey('all', $searchTypes);
        $this->assertArrayHasKey('ranked', $searchTypes);
        $this->assertArrayHasKey('overtime', $searchTypes);
        $this->assertArrayHasKey('series', $searchTypes);
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->get('/games/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Box Score');
    }

    /**
     * Tests view with invalid id.
     */
    public function testViewWithInvalidId(): void
    {
        $this->get('/games/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * Tests view sets variables.
     */
    public function testViewSetsVariables(): void
    {
        $this->get('/games/1');
        $this->assertResponseOk();

        $game = $this->viewVariable('game');
        $this->assertNotNull($game);

        $statsElement = $this->viewVariable('statsElement');
        $this->assertTrue($statsElement === null || is_string($statsElement));

        $teamBoxStats = $this->viewVariable('teamBoxStats');
        $this->assertTrue(is_array($teamBoxStats) || $teamBoxStats === null);

        $images = $this->viewVariable('images');
        $this->assertIsArray($images);

        $blogPosts = $this->viewVariable('blogPosts');
        $this->assertIsArray($blogPosts);
    }

    /**
     * Tests stats frame.
     */
    public function testStatsFrame(): void
    {
        $this->get('/games/stats/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Stats');
    }

    /**
     * Tests authorization skipped.
     */
    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/games');
        $this->assertResponseOk();

        $this->get('/games/1');
        $this->assertResponseOk();
    }

    // ─── New game search actions ──────────────────────────────────────

    public function testRankedPage(): void
    {
        $this->get('/games/ranked');
        $this->assertResponseOk();
        $this->assertResponseContains('Ranked Games');
    }

    /**
     * Tests ranked json.
     */
    public function testRankedJson(): void
    {
        $this->configRequest([
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->get('/games/ranked?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = (string)$this->_response->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Tests all page.
     */
    public function testAllPage(): void
    {
        $this->get('/games/all');
        $this->assertResponseOk();
        $this->assertResponseContains('All Games');
    }

    /**
     * Tests all json.
     */
    public function testAllJson(): void
    {
        $this->get('/games/all?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = (string)$this->_response->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data']);
        $this->assertSame('Conf', (string)$data['data'][0][9]);
    }

    /**
     * Tests overtime page.
     */
    public function testOvertimePage(): void
    {
        $this->get('/games/overtime');
        $this->assertResponseOk();
        $this->assertResponseContains('Overtime Games');
    }

    /**
     * Tests overtime json.
     */
    public function testOvertimeJson(): void
    {
        $this->get('/games/overtime?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
    }

    /**
     * Tests hundred point page.
     */
    public function testHundredPointPage(): void
    {
        $this->get('/games/hundred-point');
        $this->assertResponseOk();
        $this->assertResponseContains('100 Point Games');
        $this->assertResponseContains('Overall Record:');
        $this->assertResponseContains('Game Type');
        $this->assertResponseContains('All 100+ Games');
        $this->assertResponseContains('Team 100+ (Pts For)');
        $this->assertResponseContains('Opponent 100+ (Pts Against)');
        $this->assertResponseNotContains('Season Type');
        $this->assertResponseNotContains('<th>Score</th>');
    }

    /**
     * Tests hundred point json.
     */
    public function testHundredPointJson(): void
    {
        $this->get('/games/hundred-point?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);

        $row = $payload['data'][0];
        $this->assertCount(11, $row);
        $this->assertSame('W', strip_tags((string)$row[2]));
        $this->assertSame('7', (string)$row[3]);
        $this->assertSame('105', (string)$row[4]);
        $this->assertSame('98', (string)$row[5]);
        $this->assertSame('2', strip_tags((string)$row[6]));
        $this->assertSame('H', strip_tags((string)$row[7]));
        $this->assertStringContainsString('/games/series?opponent_id=1', (string)$row[1]);
    }

    /**
     * Tests hundred point json team filter.
     */
    public function testHundredPointJsonTeamFilter(): void
    {
        $this->get('/games/hundred-point?format=json&filter=team');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);
        $this->assertSame('105', (string)$payload['data'][0][4]);
    }

    /**
     * Tests hundred point json opponent filter.
     */
    public function testHundredPointJsonOpponentFilter(): void
    {
        $this->get('/games/hundred-point?format=json&filter=opponent');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(0, $payload['data']);
    }

    /**
     * Tests openers page.
     */
    public function testOpenersPage(): void
    {
        $this->get('/games/openers');
        $this->assertResponseOk();
        $this->assertResponseContains('Season Openers');
        $this->assertResponseContains('Overall Record:');
        $this->assertResponseContains('Game Type');
        $this->assertResponseNotContains('Season Type');
        $this->assertResponseNotContains('<th>Score</th>');
        $this->assertResponseNotContains('&amp;amp;type=');
    }

    /**
     * Tests openers with type.
     */
    public function testOpenersWithType(): void
    {
        $this->get('/games/openers?type=home');
        $this->assertResponseOk();
        $this->assertResponseContains('Season Openers');
    }

    /**
     * Tests openers json.
     */
    public function testOpenersJson(): void
    {
        $this->get('/games/openers?format=json&type=season');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertIsArray($payload['data']);

        if (!empty($payload['data'])) {
            $row = $payload['data'][0];
            $this->assertCount(11, $row);
            $this->assertStringContainsString('/games/series?opponent_id=', (string)$row[1]);
            $this->assertContains(strip_tags((string)$row[8]), ['Y', 'N']);
        }
    }

    /**
     * Tests streaks page.
     */
    public function testStreaksPage(): void
    {
        $this->get('/games/streaks');
        $this->assertResponseOk();
        $this->assertResponseContains('Winning');
        $this->assertResponseContains('Streaks');
    }

    /**
     * Tests streaks losing.
     */
    public function testStreaksLosing(): void
    {
        $this->get('/games/streaks?result=L&filter=home');
        $this->assertResponseOk();
        $this->assertResponseContains('Losing');
    }

    /**
     * Tests margins page.
     */
    public function testMarginsPage(): void
    {
        $this->get('/games/margins');
        $this->assertResponseOk();
        $this->assertResponseContains('Biggest Wins');
        $this->assertResponseContains('<th>#</th>');
        $this->assertResponseContains('<th>Date</th>');
        $this->assertResponseContains('<th>Opponent</th>');
        $this->assertResponseContains('<th>Margin</th>');
        $this->assertResponseContains('<th>Game Type</th>');
        $this->assertResponseContains('<th>Conf</th>');
        $this->assertResponseNotContains('<th>Result</th>');
        $this->assertResponseNotContains('<th>OT</th>');
        $this->assertResponseNotContains('<th>Score</th>');
    }

    /**
     * Tests margins loss.
     */
    public function testMarginsLoss(): void
    {
        $this->get('/games/margins?type=loss&filter=road');
        $this->assertResponseOk();
        $this->assertResponseContains('Largest Losses');
    }

    /**
     * Tests margins uses dense ranking and tie date sort.
     */
    public function testMarginsUsesDenseRankingAndTieDateSort(): void
    {
        $this->get('/games/margins?type=win&filter=overall');
        $this->assertResponseOk();

        $games = $this->viewVariable('games');
        $this->assertIsArray($games);
        $this->assertGreaterThanOrEqual(3, count($games));

        // Highest margin first (id 3: 80-70, margin 10).
        $this->assertSame(3, (int)$games[0]->id);
        $this->assertSame(1, (int)($games[0]->rank ?? 0));

        // Tied margins (7) share rank and are ordered by most recent date.
        $this->assertSame(4, (int)$games[1]->id);
        $this->assertSame(1, (int)$games[1]->game_date->format('n'));
        $this->assertSame(20, (int)$games[1]->game_date->format('j'));
        $this->assertSame(2, (int)($games[1]->rank ?? 0));

        $this->assertSame(1, (int)$games[2]->id);
        $this->assertSame(1, (int)$games[2]->game_date->format('n'));
        $this->assertSame(15, (int)$games[2]->game_date->format('j'));
        $this->assertSame(2, (int)($games[2]->rank ?? 0));
    }

    /**
     * Tests series page.
     */
    public function testSeriesPage(): void
    {
        $this->get('/games/series');
        $this->assertResponseOk();
        $this->assertResponseContains('Series History');
        $this->assertResponseContains('Select an opponent');
        $this->assertResponseContains('series-opponents-search');
        $this->assertResponseContains('series-opponents-table');
    }

    /**
     * Tests series with opponent.
     */
    public function testSeriesWithOpponent(): void
    {
        $this->get('/games/series?opponent_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Series History');
    }

    /**
     * Tests series json.
     */
    public function testSeriesJson(): void
    {
        $this->get('/games/series?opponent_id=1&format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
    }

    /**
     * Tests series opponents json.
     */
    public function testSeriesOpponentsJson(): void
    {
        $this->get('/games/series-opponents?format=json&draw=1&start=0&length=25');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('recordsTotal', $payload);
        $this->assertArrayHasKey('recordsFiltered', $payload);
        $this->assertArrayHasKey('draw', $payload);
        $this->assertSame(1, (int)$payload['draw']);
        $this->assertNotEmpty($payload['data']);
        $this->assertStringContainsString('Belmont', (string)$payload['data'][0][0]);
    }

    /**
     * Tests series opponents json with search.
     */
    public function testSeriesOpponentsJsonWithSearch(): void
    {
        $this->get('/games/series-opponents?format=json&draw=2&start=0&length=25&search[value]=BEL');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertSame(2, (int)$payload['draw']);
        $this->assertGreaterThan(0, (int)$payload['recordsFiltered']);
        $this->assertStringContainsString('Belmont', (string)$payload['data'][0][0]);
    }

    /**
     * Tests sub nav on all pages.
     */
    public function testSubNavOnAllPages(): void
    {
        $pages = [
            '/games',
            '/games/ranked',
            '/games/overtime',
            '/games/hundred-point',
            '/games/openers',
            '/games/streaks',
            '/games/margins',
            '/games/series',
        ];
        foreach ($pages as $url) {
            $this->get($url);
            $this->assertResponseOk();
            $this->assertResponseContains('rh-games-subnav-wrap');
            $this->assertResponseContains('rh-games-navbar');
        }
    }
}
