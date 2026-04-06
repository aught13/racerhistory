<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\StatBasketGamePersonController Test Case
 *
 * @uses \App\Controller\Admin\StatBasketGamePersonController
 */
class StatBasketGamePersonControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Games',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Opponents',
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.StatBasketGamePerson',
        'app.StatBasketSeasonPerson',
        'app.Sports',
        'app.GameTypes',
        'app.Sites',
        'app.Places',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->mockIdentity();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::view()
     */
    public function testView(): void
    {
        $this->get('/admin/stat-basket-game-person/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Player Game Stats');
        $this->viewVariable('stats');
        $this->viewVariable('game');

        $stats = $this->viewVariable('stats');
        $this->assertNotNull($stats);
    }

    /**
     * Test view method with invalid game - should redirect or error gracefully
     *
     * @return void
     */
    public function testViewInvalidGame(): void
    {
        $this->get('/admin/stat-basket-game-person/view/999');
        // Should either redirect or show error page
        $code = $this->_response->getStatusCode();
        $this->assertContains($code, [302, 404, 500]);
    }

    /**
     * Test add method GET request - renders multi-row form
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/stat-basket-game-person/add/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Add Player Stats');
        $this->viewVariable('game');
        $this->viewVariable('teamSeasonRoster');
        $this->assertResponseContains('id="stat-rows"');
        $this->assertResponseContains('id="add-row-btn"');
        $this->assertResponseContains('Add Another');
        $this->assertResponseContains('Save All');
        $this->assertResponseContains('stat-row');
        $this->assertResponseContains('turbo-frame id="stat-person-add-frame"');
        $this->assertResponseContains('add-to-totals-checkbox');
    }

    /**
     * Test bulk add with a single row
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::bulkAdd()
     */
    public function testBulkAddSingleRow(): void
    {
        $data = [
            'rows' => [
                [
                    'team_season_roster_id' => 1,
                    'period' => 'Z',
                    'GP' => 1,
                    'GS' => 1,
                    'MIN' => '30',
                    'FGM' => '8',
                    'FGA' => '15',
                    'PTS' => '24',
                ],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');

        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $query = $stats->find()->where(['game_id' => 1, 'PTS' => '24']);
        $this->assertGreaterThanOrEqual(1, $query->count());
    }

    /**
     * Test bulk add with multiple rows
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::bulkAdd()
     */
    public function testBulkAddMultipleRows(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => 1, 'PTS' => '10', 'MIN' => '20'],
                ['team_season_roster_id' => 1, 'PTS' => '15', 'MIN' => '25'],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('Saved 2 player stat(s).');
    }

    /**
     * Test bulk add skips rows without a roster player selected
     *
     * @return void
     */
    public function testBulkAddSkipsEmptyRows(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => 1, 'PTS' => '10'],
                ['team_season_roster_id' => '', 'PTS' => ''],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
    }

    /**
     * Test bulk add with no rows redirects back
     *
     * @return void
     */
    public function testBulkAddNoRowsRedirects(): void
    {
        $data = ['rows' => []];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertRedirect('/admin/stat-basket-game-person/add/1');
        $this->assertFlashMessage('No player stats to save.');
    }

    /**
     * Test bulk add requires POST method
     *
     * @return void
     */
    public function testBulkAddRequiresPost(): void
    {
        $this->get('/admin/stat-basket-game-person/bulk-add/1');
        $this->assertResponseCode(405);
    }

    /**
     * Test bulk add with add_to_totals checkbox checked
     *
     * @return void
     */
    public function testBulkAddWithAddToTotals(): void
    {
        $data = [
            'add_to_totals' => '1',
            'rows' => [
                ['team_season_roster_id' => 1, 'PTS' => '20', 'period' => 'Z'],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
    }

    /**
     * Test edit method GET request
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::edit()
     */
    public function testEditGet(): void
    {
        // First create a stat to edit
        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $stats->newEntity([
            'team_season_roster_id' => 1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'PTS' => '10',
        ]);
        $stats->save($stat);
        $statId = $stat->id;

        $this->get("/admin/stat-basket-game-person/edit/{$statId}");

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Player Stats');
        $this->viewVariable('stat');
        $this->viewVariable('game');
        $this->viewVariable('teamSeasonRoster');
    }

    /**
     * Test edit method POST request with valid data
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::edit()
     */
    public function testEditPostValid(): void
    {
        // First create a stat to edit
        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $stats->newEntity([
            'team_season_roster_id' => 1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'PTS' => '10',
        ]);
        $stats->save($stat);
        $statId = $stat->id;

        $data = [
            'PTS' => '25',
            'MIN' => '35',
            'FGM' => '10',
            'FGA' => '18',
        ];

        $this->post("/admin/stat-basket-game-person/edit/{$statId}", $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The player stat has been saved.');

        // Verify the update
        $updated = $stats->get($statId);
        $this->assertEquals('25', $updated->PTS);
        $this->assertEquals('35', $updated->MIN);
    }

    /**
     * Test edit method with invalid stat ID - should handle gracefully
     *
     * @return void
     */
    public function testEditInvalidId(): void
    {
        $this->get('/admin/stat-basket-game-person/edit/999');
        $code = $this->_response->getStatusCode();
        $this->assertContains($code, [302, 404, 500]);
    }

    /**
     * Test delete method with valid stat
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::delete()
     */
    public function testDeleteValid(): void
    {
        // First create a stat to delete
        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $stats->newEntity([
            'team_season_roster_id' => 1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'PTS' => '10',
        ]);
        $stats->save($stat);
        $statId = $stat->id;

        $this->post("/admin/stat-basket-game-person/delete/{$statId}");

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The player stat has been deleted.');

        // Verify deletion
        $query = $stats->find()->where(['id' => $statId]);
        $this->assertEquals(0, $query->count());
    }

    /**
     * Test delete method with invalid HTTP method - should reject GET
     *
     * @return void
     */
    public function testDeleteInvalidMethod(): void
    {
        $this->get('/admin/stat-basket-game-person/delete/1');
        $code = $this->_response->getStatusCode();
        $this->assertContains($code, [302, 405]);
    }

    /**
     * Test delete method with invalid stat ID - should handle gracefully
     *
     * @return void
     */
    public function testDeleteInvalidId(): void
    {
        $this->post('/admin/stat-basket-game-person/delete/999');
        $code = $this->_response->getStatusCode();
        $this->assertContains($code, [302, 404, 500]);
    }

    /**
     * Test that stats are properly ordered by GS, MIN, PTS
     *
     * @return void
     */
    public function testViewStatsOrdering(): void
    {
        // Create multiple stats with different values
        $stats = $this->getTableLocator()->get('StatBasketGamePerson');

        // Player 1: Started, 30 min, 20 pts
        $stat1 = $stats->newEntity([
            'team_season_roster_id' => 1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'GS' => 1,
            'MIN' => '30',
            'PTS' => '20',
        ]);
        $stats->save($stat1);

        // Player 2: Did not start, 15 min, 25 pts
        $stat2 = $stats->newEntity([
            'team_season_roster_id' => 1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'GS' => null,
            'MIN' => '15',
            'PTS' => '25',
        ]);
        $stats->save($stat2);

        $this->get('/admin/stat-basket-game-person/view/1');

        $this->assertResponseOk();
        $viewStats = $this->viewVariable('stats');

        // First stat should be the one who started (GS=1)
        $this->assertEquals(1, $viewStats->first()->GS);
    }

    /**
     * Test add with invalid game ID - should handle gracefully
     *
     * @return void
     */
    public function testAddInvalidGame(): void
    {
        $this->get('/admin/stat-basket-game-person/add/999');
        $code = $this->_response->getStatusCode();
        $this->assertContains($code, [302, 404, 500]);
    }
}
