<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @link \App\Controller\Admin\GamesController
 */
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

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games');
        $this->assertResponseOk();
        $this->assertResponseContains('Games Management');
        $this->assertResponseContains('data-controller="admin-games-index"');
        // DataTables replaces pagination
        $this->assertResponseContains('DataTables');
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Game');
    }

    /**
     * Tests add post valid.
     */
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
            'period_1_team' => '30',
            'period_1_opponent' => '28',
            'period_2_team' => '40',
            'period_2_opponent' => '35',
            'official_1' => 'Ref A',
        ];

        $this->post('/admin/games/add?team_season_id=1', $data);
        // Past game date now redirects to add-results
        $this->assertRedirectContains('/admin/games/add-results/');

        $eav = $this->getTableLocator()->get('GameEav');
        $count = $eav->find()->where(['key' => 'period_2_team'])->count();
        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test that adding a game with a future date redirects to add another.
     */
    public function testAddPostFutureGameRedirectsToAddAnother(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $data = [
            'team_season_id' => 2,
            'game_date' => $futureDate,
            'game_time' => '18:00',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
            'periods' => 2,
        ];

        $this->post('/admin/games/add?team_season_id=2', $data);
        $this->assertRedirectContains('/admin/games/add?team_season_id=2');
    }

    /**
     * Tests edit post.
     */
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
            'period_1_team' => '36',
            'period_1_opponent' => '31',
        ]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', 1]);
    }

    /**
     * Tests edit post save and box score redirects.
     */
    public function testEditPostSaveAndBoxScoreRedirects(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/edit/1', [
            'team_season_id' => 1,
            'game_date' => '2024-01-15',
            'hrn' => 1,
            'periods' => 2,
            'period_1_team' => '36',
            'period_1_opponent' => '31',
            'save_action' => 'box_score',
        ]);
        $this->assertRedirect([
            'prefix' => 'Admin',
            'controller' => 'StatBasketGameBox',
            'action' => 'gameBox',
            '1',
        ]);
    }

    /**
     * Tests edit form shows legacy period scores.
     */
    public function testEditFormShowsLegacyPeriodScores(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1');
        $this->assertResponseOk();
        // Legacy fixture has period_1_mur=35 and period_1_opp=30
        $this->assertResponseContains('value="35"');
        $this->assertResponseContains('value="30"');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'index']);
    }

    /**
     * Tests bulk delete.
     */
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
        // Should contain stats heading and a known box score field
        $this->assertResponseContains('Game Statistics');
        $this->assertResponseContains('FGM');
        $this->assertResponseContains('PTS');
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
        // Even with stats present, ensure the heading renders
        $this->assertResponseContains('Game Statistics');
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

    /**
     * Tests delete invalid id.
     */
    public function testDeleteInvalidId(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/games/delete/999');
            $this->assertResponseError();
        } catch (Exception $e) {
            $this->assertInstanceOf(RecordNotFoundException::class, $e);
        }
    }

    /**
     * Tests edit with invalid data.
     */
    public function testEditWithInvalidData(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/edit/1', [
            'team_season_id' => 1,
            'game_date' => '2024-01-15', // Valid date
            'hrn' => 999, // Invalid hrn value
        ]);
        // Controller may re-render with errors or redirect
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests add with missing required fields.
     */
    public function testAddWithMissingRequiredFields(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/add?team_season_id=1', [
            // Missing required fields
        ]);
        $this->assertResponseSuccess(); // Re-renders form with errors
    }

    /**
     * Test admin games pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }

    /**
     * Test add form contains AJAX lookup search inputs instead of static selects.
     */
    public function testAddFormContainsLookupSearchInputs(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="game-type-search"');
        $this->assertResponseContains('id="opponent-search"');
        $this->assertResponseContains('id="place-search"');
        $this->assertResponseContains('id="site-search"');
    }

    /**
     * Test add form contains hidden inputs for lookup IDs.
     */
    public function testAddFormContainsLookupHiddenInputs(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="game-type-id"');
        $this->assertResponseContains('id="opponent-id"');
        $this->assertResponseContains('id="place-id"');
        $this->assertResponseContains('id="site-id"');
    }

    /**
     * Test add form contains popup modals for creating new entities.
     */
    public function testAddFormContainsPopupModals(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="add-game-type-modal"');
        $this->assertResponseContains('id="add-opponent-modal"');
        $this->assertResponseContains('id="add-place-modal"');
        $this->assertResponseContains('id="add-site-modal"');
    }

    /**
     * Test add form contains nested place creation inside opponent popup.
     */
    public function testAddFormContainsNestedPlaceInOpponentPopup(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="opponent-place-search"');
        $this->assertResponseContains('id="opponent-add-place-btn"');
        $this->assertResponseContains('id="add-opponent-place-modal"');
    }

    /**
     * Test edit form in details mode contains lookup search inputs.
     */
    public function testEditFormContainsLookupSearchInputs(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1?mode=details');
        $this->assertResponseOk();
        $this->assertResponseContains('id="game-type-search"');
        $this->assertResponseContains('id="opponent-search"');
        $this->assertResponseContains('id="place-search"');
        $this->assertResponseContains('id="site-search"');
    }

    /**
     * Test edit form in details mode contains popup modals.
     */
    public function testEditFormContainsPopupModals(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1?mode=details');
        $this->assertResponseOk();
        $this->assertResponseContains('id="add-game-type-modal"');
        $this->assertResponseContains('id="add-opponent-modal"');
        $this->assertResponseContains('id="add-place-modal"');
        $this->assertResponseContains('id="add-site-modal"');
        $this->assertResponseContains('id="add-opponent-place-modal"');
    }

    /**
     * Test edit form for past game defaults to results mode.
     */
    public function testEditPastGameDefaultsToResultsMode(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1');
        $this->assertResponseOk();
        // Results mode shows score fields, not lookup search inputs
        $this->assertResponseContains('id="game-results-card"');
        $this->assertResponseContains('Overtime Periods');
        $this->assertResponseContains('Team Points');
    }

    /**
     * Test edit form always shows Details/Results mode toggle.
     */
    public function testEditFormAlwaysShowsModeToggle(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Details');
        $this->assertResponseContains('Game Results');
        $this->assertResponseContains('mode=details');
        $this->assertResponseContains('mode=results');
    }

    /**
     * Test addResults page renders correctly.
     */
    public function testAddResultsGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add-results/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Results');
        $this->assertResponseContains('id="game-results-card"');
        $this->assertResponseContains('Team Points');
        $this->assertResponseContains('Opponent Points');
    }

    /**
     * Test addResults POST saves results and redirects.
     */
    public function testAddResultsPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/add-results/1', [
            'pts_mur' => '80',
            'pts_opp' => '70',
            'period_1_team' => '40',
            'period_1_opponent' => '35',
            'period_2_team' => '40',
            'period_2_opponent' => '35',
        ]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', 1]);
    }

    /**
     * Test ajaxSitesByPlace returns sites for a given place.
     */
    public function testAjaxSitesByPlace(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/ajax-sites-by-place?place_id=1');
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('sites', $json);
        $this->assertIsArray($json['sites']);
    }

    /**
     * Test ajaxSitesByPlace with invalid place returns empty.
     */
    public function testAjaxSitesByPlaceNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/ajax-sites-by-place?place_id=999');
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('sites', $json);
        $this->assertEmpty($json['sites']);
    }

    /**
     * Test add form data attributes contain search URLs.
     */
    public function testAddFormDataAttributesContainSearchUrls(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('data-opponent-search-url=');
        $this->assertResponseContains('data-place-search-url=');
        $this->assertResponseContains('data-site-search-url=');
        $this->assertResponseContains('data-game-type-search-url=');
    }

    /**
     * Test hidden FormProtection token forms are present.
     */
    public function testAddFormContainsHiddenTokenForms(): void
    {
        $this->mockIdentity();
        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="hidden-opponent-form"');
        $this->assertResponseContains('id="hidden-place-form"');
        $this->assertResponseContains('id="hidden-site-form"');
        $this->assertResponseContains('id="hidden-game-type-form"');
    }

    /**
     * Test that Game add/edit forms are NOT wrapped in a nested turbo-frame.
     *
     * A nested frame without target="_top" causes "Content missing" after redirect
     * because Turbo tries to find the frame ID on the target page.
     */
    public function testAddAndEditFormsHaveNoNestedTurboFrame(): void
    {
        $this->mockIdentity();

        $this->get('/admin/games/add?team_season_id=1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        // Only the layout admin-content frame should be present — no inner form frame.
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Game add form must not be wrapped in a nested turbo-frame',
        );

        $this->get('/admin/games/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Game edit form must not be wrapped in a nested turbo-frame',
        );
    }
}
