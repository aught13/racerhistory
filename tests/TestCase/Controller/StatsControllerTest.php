<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\StatsController Test Case
 *
 * @link \App\Controller\StatsController
 */
class StatsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.StatBasketSeasonPerson',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.StatBasketGamePerson',
        'app.StatBasketGameBox',
        'app.StatBasketGameOpponent',
        'app.StatBasketGameTeam',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

    // ——— Index ————————————————————————————

    public function testIndex(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();
        $this->assertResponseContains('Statistics');
        $this->assertResponseContains('stats-type-cards');
    }

    /**
     * Tests index displays stat types.
     */
    public function testIndexDisplaysStatTypes(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();

        $statTypes = $this->viewVariable('statTypes');
        $this->assertIsArray($statTypes);
        $this->assertArrayHasKey('player-season', $statTypes);
        $this->assertArrayHasKey('team-season', $statTypes);
        $this->assertArrayHasKey('player-career', $statTypes);
    }

    /**
     * Tests index sets current sport.
     */
    public function testIndexSetsCurrentSport(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();

        $currentSport = $this->viewVariable('currentSport');
        $this->assertSame('basketball', $currentSport);
    }

    // ——— Player Season ————————————————————

    public function testPlayerSeason(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Season');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests player season json response.
     */
    public function testPlayerSeasonJsonResponse(): void
    {
        $this->configRequest([
            'headers' => ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'],
        ]);
        $this->get('/stats/player-season?sport=basketball');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('data', $json);
    }

    /**
     * Tests player season json via format param.
     */
    public function testPlayerSeasonJsonViaFormatParam(): void
    {
        $this->get('/stats/player-season?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    /**
     * Tests player season sets stat type.
     */
    public function testPlayerSeasonSetsStatType(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();

        $this->assertSame('player-season', $this->viewVariable('statType'));
        $this->assertSame('Player Season', $this->viewVariable('statTypeLabel'));
    }

    // ——— Team Season —————————————————————

    public function testTeamSeason(): void
    {
        $this->get('/stats/team-season');
        $this->assertResponseOk();
        $this->assertResponseContains('Team Season');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests team season json response.
     */
    public function testTeamSeasonJsonResponse(): void
    {
        $this->get('/stats/team-season?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    // ——— Team Season Opponent ——————————————

    public function testTeamSeasonOpponent(): void
    {
        $this->get('/stats/team-season-opponent');
        $this->assertResponseOk();
        $this->assertResponseContains('Team Season Opponent');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests team season opponent json response.
     */
    public function testTeamSeasonOpponentJsonResponse(): void
    {
        $this->get('/stats/team-season-opponent?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    // ——— Player Career ————————————————————

    public function testPlayerCareer(): void
    {
        $this->get('/stats/player-career');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Career');
    }

    /**
     * Tests player career json response.
     */
    public function testPlayerCareerJsonResponse(): void
    {
        $this->get('/stats/player-career?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    // ——— Player Game ——————————————————————

    public function testPlayerGame(): void
    {
        $this->get('/stats/player-game');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Game');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests player game json response.
     */
    public function testPlayerGameJsonResponse(): void
    {
        $this->get('/stats/player-game?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    // ——— Opponent Player Game —————————————

    public function testOpponentPlayerGame(): void
    {
        $this->get('/stats/opponent-player-game');
        $this->assertResponseOk();
        $this->assertResponseContains('Opponent Player Game');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests opponent player game json response.
     */
    public function testOpponentPlayerGameJsonResponse(): void
    {
        $this->get('/stats/opponent-player-game?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    // ——— Team Game ————————————————————————

    public function testTeamGame(): void
    {
        $this->get('/stats/team-game');
        $this->assertResponseOk();
        $this->assertResponseContains('Team Game');
        $this->assertResponseContains('stats-results-table');
    }

    /**
     * Tests team game json response.
     */
    public function testTeamGameJsonResponse(): void
    {
        $this->get('/stats/team-game?format=json');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = (string)$this->_response->getBody();
        $json = json_decode($body, true);
        $this->assertArrayHasKey('data', $json);
    }

    /**
     * Tests team game contains ajax url.
     */
    public function testTeamGameContainsAjaxUrl(): void
    {
        $this->get('/stats/team-game');
        $this->assertResponseOk();
        $this->assertResponseContains('data-ajax-url=');
    }

    // ——— Legacy Season ————————————————————

    public function testSeason(): void
    {
        $this->get('/stats/season/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Stats');
    }

    /**
     * Tests season with invalid id.
     */
    public function testSeasonWithInvalidId(): void
    {
        $this->get('/stats/season/9999');
        $this->assertRedirect();
    }

    /**
     * Tests season sets variables.
     */
    public function testSeasonSetsVariables(): void
    {
        $this->get('/stats/season/1');
        $this->assertResponseOk();

        $teamSeason = $this->viewVariable('teamSeason');
        $this->assertNotNull($teamSeason);

        $playerStats = $this->viewVariable('playerStats');
        $this->assertIsArray($playerStats);
    }

    // ——— Auth & Security ——————————————————

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/stats');
        $this->assertResponseOk();

        $this->get('/stats/player-season');
        $this->assertResponseOk();

        $this->get('/stats/team-season');
        $this->assertResponseOk();

        $this->get('/stats/player-career');
        $this->assertResponseOk();

        $this->get('/stats/player-game');
        $this->assertResponseOk();

        $this->get('/stats/opponent-player-game');
        $this->assertResponseOk();

        $this->get('/stats/team-season-opponent');
        $this->assertResponseOk();

        $this->get('/stats/season/1');
        $this->assertResponseOk();
    }

    // ——— Invalid sort/direction handling ——

    public function testPlayerSeasonInvalidSort(): void
    {
        $this->get('/stats/player-season?sort=INVALID&direction=INVALID');
        $this->assertResponseOk();
    }

    /**
     * Tests player season limit clamp.
     */
    public function testPlayerSeasonLimitClamp(): void
    {
        $this->get('/stats/player-season?limit=500');
        $this->assertResponseOk();
    }

    // ——— Filter form presence ——————————————

    public function testPlayerSeasonContainsAjaxUrl(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('data-ajax-url');
        $this->assertResponseContains('format=json');
    }

    /**
     * Tests team season contains ajax url.
     */
    public function testTeamSeasonContainsAjaxUrl(): void
    {
        $this->get('/stats/team-season');
        $this->assertResponseOk();
        $this->assertResponseContains('data-ajax-url');
    }

    // ——— Page structure ——————————————————————

    public function testPlayerSeasonContainsPageStructure(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Season');
        $this->assertResponseContains('stats-results-table');
    }

    // ——— Script inclusion ——————————————————

    public function testIndexIncludesStatsScript(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();
        $this->assertResponseContains('stats-init-loader');
    }

    /**
     * Tests player season includes stats script.
     */
    public function testPlayerSeasonIncludesStatsScript(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('stats-init-loader');
    }

    // ——— Sub-nav presence ——————————————————

    public function testIndexContainsSubNav(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
        $this->assertResponseContains('Player Season');
        $this->assertResponseContains('Team Season');
    }

    /**
     * Tests player season contains sub nav.
     */
    public function testPlayerSeasonContainsSubNav(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
    }

    /**
     * Tests team season contains sub nav.
     */
    public function testTeamSeasonContainsSubNav(): void
    {
        $this->get('/stats/team-season');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
    }

    /**
     * Tests player career contains sub nav.
     */
    public function testPlayerCareerContainsSubNav(): void
    {
        $this->get('/stats/player-career');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
    }

    /**
     * Tests opponent player game contains sub nav.
     */
    public function testOpponentPlayerGameContainsSubNav(): void
    {
        $this->get('/stats/opponent-player-game');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
    }

    /**
     * Tests team game contains sub nav.
     */
    public function testTeamGameContainsSubNav(): void
    {
        $this->get('/stats/team-game');
        $this->assertResponseOk();
        $this->assertResponseContains('rh-stats-subnav-wrap');
    }

    // ——— Stat types available on all pages ——

    public function testStatTypesSetOnAllActions(): void
    {
        $pages = [
            '/stats',
            '/stats/player-season',
            '/stats/team-season',
            '/stats/team-season-opponent',
            '/stats/player-career',
            '/stats/player-game',
            '/stats/opponent-player-game',
        ];

        foreach ($pages as $url) {
            $this->get($url);
            $this->assertResponseOk();
            $statTypes = $this->viewVariable('statTypes');
            $this->assertIsArray($statTypes, "statTypes not set on {$url}");
            $this->assertNotEmpty($statTypes, "statTypes empty on {$url}");
        }
    }

    // ——— DataTables CSS assets ——————————————

    public function testPlayerSeasonIncludesDataTablesCss(): void
    {
        $this->get('/stats/player-season');
        $this->assertResponseOk();
        $this->assertResponseContains('dataTables.bootstrap5.min.css');
        $this->assertResponseContains('searchBuilder.dataTables.min.css');
        $this->assertResponseContains('scroller.dataTables.min.css');
    }

    /**
     * Tests team season includes data tables css.
     */
    public function testTeamSeasonIncludesDataTablesCss(): void
    {
        $this->get('/stats/team-season');
        $this->assertResponseOk();
        $this->assertResponseContains('dataTables.bootstrap5.min.css');
    }
}
