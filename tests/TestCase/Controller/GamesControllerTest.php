<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\TaggingServiceFactoryTestTrait;
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
    use TaggingServiceFactoryTestTrait;

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
     * Configure legacy specialty-view tests with an explicit Basketball team scope.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'query' => [
                'sport' => 'basketball',
                'team' => '1',
            ],
        ]);
    }

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/games');
        $this->assertResponseOk();
        $this->assertResponseContains('Games');
        $this->assertResponseNotContains('Explore Men\'s Basketball game history');
        $this->assertResponseContains('data-controller="nav-accordion"');
        $this->assertResponseContains('data-controller="games-search"');
        $this->assertResponseContains('/games/series-history');
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
     * Unscoped Games automatically selects the only team with results.
     *
     * @return void
     */
    public function testIndexWithoutSelectedTeamSelectsOnlyTeamWithGames(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'basketball', 'team' => 'all']]);
        $this->get('/games?sport=basketball&team=all');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('publicScope')['teamId']);
        $this->assertArrayHasKey('ranked', $this->viewVariable('searchTypes'));
        $this->assertArrayHasKey('streaks', $this->viewVariable('searchTypes'));
        $this->assertArrayHasKey('series', $this->viewVariable('searchTypes'));
    }

    /**
     * Basketball-only search views are hidden for a selected non-Basketball team.
     *
     * @return void
     */
    public function testFootballTeamHidesHundredPointView(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'football', 'team' => '3']]);
        $this->get('/games?sport=football&team=3');

        $this->assertResponseOk();
        $this->assertArrayNotHasKey('hundred-point', $this->viewVariable('searchTypes'));
        $this->assertResponseNotContains('100 Point Games');
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->get('/games/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Box Score');
        $this->assertResponseContains('data-controller="game-view"');
        $this->assertResponseContains('<p class="text-muted mb-2">Belmont</p>');
        $this->assertResponseContains('<th scope="row" class="text-nowrap">Lakers</th>');
        $this->assertResponseContains('<th scope="row" class="text-nowrap">Belmont</th>');
        $this->assertResponseContains(h("Men's Los Angeles Lakers"));
        $this->assertResponseContains('property="og:image"');
        $this->assertTrue(
            str_contains((string)$this->_response->getBody(), '<meta property="og:image" content="/img/logo.png"')
            || str_contains((string)$this->_response->getBody(), '<meta property="og:image" content="/img/storage/'),
        );
        $this->assertTrue(
            str_contains((string)$this->_response->getBody(), '<meta property="twitter:image" content="/img/logo.png"')
            || str_contains((string)$this->_response->getBody(), '<meta property="twitter:image" content="/img/storage/'),
        );
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
     * Games without a result are excluded from public lists and detail pages.
     *
     * @return void
     */
    public function testPublicGamesHideGamesWithoutResults(): void
    {
        $gamesTable = $this->fetchTable('Games');
        $pendingGame = $gamesTable->saveOrFail($gamesTable->newEntity([
            'team_season_id' => 1,
            'game_date' => '2024-01-21',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
        ]));

        $this->get('/games/all?format=json&sport=basketball&team=1');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertCount(4, $payload['data']);

        $this->get('/games/' . $pendingGame->id . '?sport=basketball&team=1');
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

        $gameRecapPosts = $this->viewVariable('gameRecapPosts');
        $this->assertIsArray($gameRecapPosts);

        $gamePreviewPosts = $this->viewVariable('gamePreviewPosts');
        $this->assertIsArray($gamePreviewPosts);

        $seriesRecord = $this->viewVariable('seriesRecord');
        $this->assertTrue(is_array($seriesRecord) || $seriesRecord === null);
    }

    /**
     * Recap-tagged posts render in the game recap section, not game stories.
     */
    public function testViewRendersRecapPostsInGameRecapSection(): void
    {
        $this->blogPostTaggingService()->replaceTags(1, ['game-1', 'recap']);

        $this->get('/games/1');

        $this->assertResponseOk();
        $this->assertResponseContains('id="game-recap"');
        $this->assertResponseContains('>Game Recap</h2>');
        $this->assertResponseContains('First Post');
        $this->assertResponseNotContains('id="game-stories"');

        $html = (string)$this->_response->getBody();
        $gameInfoPosition = strpos($html, 'class="game-hero');
        $seriesPosition = strpos($html, 'id="game-series-history"');
        $recapPosition = strpos($html, 'id="game-recap"');
        $boxScorePosition = strpos($html, '>Box Score</h2>');
        $this->assertNotFalse($gameInfoPosition);
        $this->assertNotFalse($seriesPosition);
        $this->assertNotFalse($recapPosition);
        $this->assertNotFalse($boxScorePosition);
        $this->assertLessThan($seriesPosition, $gameInfoPosition);
        $this->assertLessThan($recapPosition, $seriesPosition);
        $this->assertLessThan($boxScorePosition, $recapPosition);
        $this->assertStringNotContainsString('All Games vs', $html);

        $gameRecapPosts = $this->viewVariable('gameRecapPosts');
        $this->assertCount(1, $gameRecapPosts);
        $this->assertSame('first-post', (string)$gameRecapPosts[0]->slug);
    }

    /**
     * Preview-tagged posts render above the game box score.
     */
    public function testViewRendersPreviewPostsAboveBoxScore(): void
    {
        $this->blogPostTaggingService()->replaceTags(1, ['game-1', 'preview']);

        $this->get('/games/1');

        $this->assertResponseOk();
        $this->assertResponseContains('id="game-preview"');
        $this->assertResponseContains('>Game Preview</h2>');

        $html = (string)$this->_response->getBody();
        $previewPosition = strpos($html, 'id="game-preview"');
        $boxScorePosition = strpos($html, '>Box Score</h2>');
        $this->assertNotFalse($previewPosition);
        $this->assertNotFalse($boxScorePosition);
        $this->assertLessThan($boxScorePosition, $previewPosition);
    }

    /**
     * The opponent summary is collapsed after a final score is available.
     */
    public function testViewCollapsesSeriesSummaryForFinalGame(): void
    {
        $this->get('/games/1');

        $this->assertResponseOk();
        $this->assertResponseContains('class="accordion-button collapsed"');
        $this->assertResponseContains('aria-expanded="false"');
        $this->assertResponseContains('id="game-series-history-collapse"');
        $this->assertResponseContains('class="accordion-collapse collapse"');
        $this->assertResponseNotContains('class="accordion-collapse collapse show"');
    }

    /**
     * Games without a result are not publicly viewable.
     */
    public function testViewHidesUnplayedGame(): void
    {
        $this->fetchTable('Games')->updateAll([
            'pts_mur' => null,
            'pts_opp' => null,
            'w' => null,
            'l' => null,
        ], ['id' => 1]);

        $this->get('/games/1');

        $this->assertResponseCode(404);
    }

    /**
     * Tests public game view includes previous/next navigation within the same team season.
     */
    public function testViewIncludesPreviousAndNextGameNavigation(): void
    {
        $this->get('/games/2');
        $this->assertResponseOk();

        $previousGame = $this->viewVariable('previousGame');
        $this->assertNotNull($previousGame);
        $this->assertSame(1, (int)$previousGame->id);

        $nextGame = $this->viewVariable('nextGame');
        $this->assertNotNull($nextGame);
        $this->assertSame(3, (int)$nextGame->id);
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
     * Tests opponent GS displays the manual position or a star when unset.
     */
    public function testStatsFrameDefaultsOpponentGsToStarWithoutPosition(): void
    {
        $this->get('/games/stats/1');
        $this->assertResponseOk();
        $this->assertResponseContains('>G</td>');

        $opponentStats = $this->fetchTable('StatBasketGameOpponent');
        $opponentStat = $opponentStats->get(1);
        $opponentStat->position = null;
        $opponentStats->saveOrFail($opponentStat);

        $this->get('/games/stats/1');
        $this->assertResponseOk();
        $this->assertResponseContains('>*</td>');

        $opponentStat->position = '';
        $opponentStats->saveOrFail($opponentStat);

        $this->get('/games/stats/1');
        $this->assertResponseOk();
        $this->assertResponseContains('>*</td>');
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

    /**
     * Opponent search returns the default composed display label.
     */
    public function testSearchOpponentsUsesDefaultDisplayName(): void
    {
        $opponents = $this->getTableLocator()->get('Opponents');
        $opponent = $opponents->get(1);
        $opponent->opponent_short = 'Belmont';
        $hasMascotColumn = $opponents->getSchema()->hasColumn('opponent_mascot');
        if ($hasMascotColumn) {
            $opponent->opponent_mascot = 'Bruins';
        }
        $opponents->saveOrFail($opponent);
        $expectedLabel = $opponents->get(1)->getDisplayName();

        $this->get('/games/search-opponents?q=' . ($hasMascotColumn ? 'Bruins' : 'Belmont'));

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($expectedLabel, $payload['opponents'][0]['label']);
        $this->assertSame($expectedLabel, $payload['opponents'][0]['name']);
        $this->assertSame('Belmont', $payload['opponents'][0]['opponent_name']);
    }

    /**
     * Ensure a Game detail page cannot escape the selected public scope.
     */
    public function testViewRejectsGameOutsideSelectedScope(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'football', 'team' => '3']]);
        $this->get('/games/1?sport=football&team=3');

        $this->assertResponseCode(404);
    }

    // ─── New game search actions ──────────────────────────────────────

    public function testRankedPage(): void
    {
        $this->get('/games/ranked');
        $this->assertResponseOk();
        $this->assertResponseContains('Ranked Games');
    }

    /**
     * Specialty game views use the only team with results as their scope.
     */
    public function testRankedPageUsesTheOnlyTeamWithGames(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'all', 'team' => 'all']]);
        $this->get('/games/ranked?sport=all&team=all');

        $this->assertResponseOk();
        $this->assertResponseContains('Ranked Games');
    }

    /**
     * The 100-point view uses the only team with results as its scope.
     *
     * @return void
     */
    public function testHundredPointUsesTheOnlyTeamWithGames(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'basketball', 'team' => 'all']]);
        $this->get('/games/hundred-point?sport=basketball&team=all');

        $this->assertResponseOk();
        $this->assertResponseContains('100 Point Games');
    }

    /**
     * The Series opponents endpoint uses the only team with results as its scope.
     */
    public function testSeriesOpponentsUsesTheOnlyTeamWithGames(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'all', 'team' => 'all']]);
        $this->get('/games/series-opponents?format=json&sport=all&team=all');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
    }

    /**
     * The 100-point view is available only when Basketball is selected.
     */
    public function testHundredPointRedirectsForUnsupportedSport(): void
    {
        $this->replaceRequest(['query' => ['sport' => 'football', 'team' => 'all']]);
        $this->get('/games/hundred-point?sport=football&team=all');

        $this->assertRedirectContains('/games?sport=football&team=all');
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
        $this->assertResponseContains('data-controller="games-search"');
        $this->assertResponseContains('<th>Team</th>');
        $this->assertResponseContains('data-result-column="3"');
        $this->assertResponseContains('data-weekday-column="12"');

        $bounds = $this->viewVariable('gamesDateBounds');
        $this->assertIsArray($bounds);
        $this->assertNotEmpty($bounds['min'] ?? null);
        $this->assertNotEmpty($bounds['max'] ?? null);
    }

    /**
     * Tests all games alias page.
     */
    public function testAllGamesAliasPage(): void
    {
        $this->get('/games/all-games');
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
        $this->assertSame('LAL', (string)$data['data'][0][1]);
        $this->assertSame('Conf', (string)$data['data'][0][10]);
        $this->assertStringContainsString('Monday, January 20, 2025', $body);
        $this->assertSame('Monday', (string)$data['data'][0][12]);
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
        $this->assertStringContainsString('/games/series-history?opponent_id=1', (string)$row[1]);
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
     * Tests openers alias page.
     */
    public function testOpenersAliasPage(): void
    {
        $this->get('/games/openers/conference-home');
        $this->assertResponseOk();
        $this->assertResponseContains('Conference Home Opener');
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
            $this->assertStringContainsString('/games/series-history?opponent_id=', (string)$row[1]);
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
     * Tests ranked alias page.
     */
    public function testRankedAliasPage(): void
    {
        $this->get('/games/ranked/team');
        $this->assertResponseOk();
        $this->assertResponseContains('Team Ranked');
    }

    /**
     * Tests hundred point alias page.
     */
    public function testHundredPointAliasPage(): void
    {
        $this->get('/games/hundred-point/opponent');
        $this->assertResponseOk();
        $this->assertResponseContains('Opponent 100+ (Pts Against)');
    }

    /**
     * Tests series history alias page.
     */
    public function testSeriesHistoryAliasPage(): void
    {
        $this->get('/games/series-history');
        $this->assertResponseOk();
        $this->assertResponseContains('Series History');
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
        $this->assertResponseContains('data-controller="games-search series-opponents"');
        $this->assertResponseContains('Select an opponent');
        $this->assertResponseContains('series-opponents-search');
        $this->assertResponseContains('series-opponents-table');
        $this->assertResponseContains('series-opponents-picker-panel');
    }

    /**
     * Tests series with opponent.
     */
    public function testSeriesWithOpponent(): void
    {
        $this->get('/games/series?opponent_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Series History');
        $this->assertResponseContains('Change opponent');
        $this->assertResponseContains('series-opponents-picker-panel');
        $this->assertResponseContains('d-none');
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
            $this->assertResponseContains('data-controller="nav-accordion"');
            $this->assertResponseContains('rh-navbar');
        }
    }
}
