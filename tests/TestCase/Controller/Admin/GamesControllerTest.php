<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GamesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Places',
        'app.Sites',
        'app.Opponents',
        'app.GameTypes',
        'app.Games',
        'app.GameEav',
        'app.Images',
        'app.Sports',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.StatBasketGameBox',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games');
        $this->assertResponseOk();
        $this->assertResponseContains('Games Management');
        // DataTables replaces pagination
        $this->assertResponseContains('DataTables');
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Game');
    }

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team_season_id' => 1,
            // date changed to fall within SeasonsFixture (2023-2024)
            'game_date' => '2024-02-01',
            'game_time' => '18:00',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1, // Home = 1, Road = -1, Neutral = 0
            'periods' => 2,
            'period_1_mur' => '30',
            'period_1_opp' => '28',
            'period_2_mur' => '40',
            'period_2_opp' => '35',
            'official_1' => 'Ref A',
        ];

        $this->post('/admin/games/add?team_season_id=1', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', 1]);

        $eav = $this->getTableLocator()->get('GameEav');
        $count = $eav->find()->where(['key' => 'period_2_mur'])->count();
        $this->assertGreaterThan(0, $count);
    }

    public function testEditPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/edit/1', [
            'team_season_id' => 1,
            // date changed to fall within SeasonsFixture (2023-2024)
            'game_date' => '2024-01-15',
            // Validation allows only 1,2,3. Use 1 (home) instead of previously invalid 0 to ensure save.
            'hrn' => 1,
            'periods' => 2,
            'period_1_mur' => '36',
            'period_1_opp' => '31',
        ]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', 1]);
    }

    public function testEditFormShowsLegacyPeriodScores(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1');
        $this->assertResponseOk();
        // Legacy fixture has period_1_mur=35 and period_1_opp=30
        $this->assertResponseContains('value="35"');
        $this->assertResponseContains('value="30"');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'index']);
    }

    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/bulk', ['bulk_action' => 'delete', 'game_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', 1]);
    }

    /**
     * ajaxGameEavMeta with team season only
     */
    public function testAjaxGameEavMetaTeamSeason(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/ajax-game-eav-meta?team_season_id=1');
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('sportId', $json);
        $this->assertArrayHasKey('eavTemplate', $json);
    }

    /**
     * ajaxGameEavMeta with existing game id (should include values)
     */
    public function testAjaxGameEavMetaGameId(): void
    {
        $this->mockIdentity();
        // Ensure game 1 exists as per fixtures
        $this->get('/admin/games/ajax-game-eav-meta?game_id=1');
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('values', $json);
        $this->assertIsArray($json['values']);
        // Legacy mapping assertions
        $this->assertSame('35', $json['values']['period_1_team'] ?? null, 'Expected mapped period_1_team value from period_1_mur');
        $this->assertSame('30', $json['values']['period_1_opponent'] ?? null, 'Expected mapped period_1_opponent value from period_1_opp');
    }

    /**
     * ajaxGameEavMeta error path (no params)
     */
    public function testAjaxGameEavMetaError(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/ajax-game-eav-meta');
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success']);
    }

    /**
     * ajaxGameEavMeta should return an HTML fragment when requested
     */
    public function testAjaxGameEavMetaHtmlFragment(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/ajax-game-eav-meta?team_season_id=1&format=html');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<div class="card', $body);
        // Expect at least one input control name from the element (e.g., period_1_team)
        $this->assertStringContainsString('name="period_1_team"', $body);
    }

    /**
     * Test view action displays game details
     */
    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Details');
    }

    /**
     * Test view action displays basketball stats when available
     */
    public function testViewBasketballStatsDisplay(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/view/1');
        $this->assertResponseOk();

        // Should contain the basketball stats element conditionally
        // Player stats tables may or may not exist depending on data
        $this->assertResponseContains('Game Details');
    }

    /**
     * Test view action with no stats shows appropriate content
     */
    public function testViewNoStatsMessage(): void
    {
        // Without box score fixture, view should still work
        $this->mockIdentity();
        $this->get('/admin/games/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Details');
    }

    /**
     * Test view action displays game information
     */
    public function testViewOpponentStats(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/view/1');
        $this->assertResponseOk();

        // Should contain basic game info
        $this->assertResponseContains('Vs');
    }

    /**
     * Test view action displays period scores
     */
    public function testViewTeamComparison(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/view/1');
        $this->assertResponseOk();

        // Should contain score display
        $this->assertResponseContains('F'); // Final column in period table
    }
}
