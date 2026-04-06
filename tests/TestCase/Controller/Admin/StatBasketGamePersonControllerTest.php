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
     * Additional roster IDs created in setUp for tests needing players
     * without pre-existing game stats.
     */
    protected int $extraRosterId1;
    protected int $extraRosterId2;

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

        // Create extra roster entries so tests can use players with no existing game stats.
        // Fixture has roster_id=1 (person 1) already with stats for game_id=1.
        $rosterTable = $this->getTableLocator()->get('TeamSeasonRosters');

        $roster2 = $rosterTable->newEntity([
            'team_season_id' => 1,
            'person_id' => 2,
            'roster_year' => '2024',
            'roster_number' => '22',
            'roster_position' => 'F',
        ]);
        $rosterTable->save($roster2);
        $this->extraRosterId1 = $roster2->id;

        $roster3 = $rosterTable->newEntity([
            'team_season_id' => 1,
            'person_id' => 2,
            'roster_year' => '2024',
            'roster_number' => '77',
            'roster_position' => 'C',
        ]);
        $rosterTable->save($roster3);
        $this->extraRosterId2 = $roster3->id;
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
        $this->assertResponseContains('turbo-frame id="stat-person-add-frame" target="_top"');
        $this->assertResponseContains('add-to-totals-checkbox');

        // Fixture has roster_id=1 already with stats for game_id=1 → notice should appear
        $this->assertResponseContains('already has stats recorded for this game');
    }

    /**
     * Test bulk add with a single row - uses roster_id=2 (no existing stats for game 1)
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::bulkAdd()
     */
    public function testBulkAddSingleRow(): void
    {
        $data = [
            'rows' => [
                [
                    'team_season_roster_id' => $this->extraRosterId1,
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
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');

        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $query = $stats->find()->where(['game_id' => 1, 'team_season_roster_id' => $this->extraRosterId1, 'PTS' => '24']);
        $this->assertGreaterThanOrEqual(1, $query->count());
    }

    /**
     * Test bulk add with multiple different players
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::bulkAdd()
     */
    public function testBulkAddMultipleRows(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '10', 'MIN' => '20'],
                ['team_season_roster_id' => $this->extraRosterId2, 'PTS' => '15', 'MIN' => '25'],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
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
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '10'],
                ['team_season_roster_id' => '', 'PTS' => ''],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
    }

    /**
     * Test bulk add skips a duplicate roster ID within the same batch
     *
     * @return void
     */
    public function testBulkAddSkipsDuplicateRosterInBatch(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '10'],
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '20'], // duplicate in same batch
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
        $this->assertFlashMessage('Skipped 1 player(s) that already have stats for this game.');
    }

    /**
     * Test bulk add skips a roster ID that already has stats for this game
     *
     * The fixture has team_season_roster_id=1 already saved for game_id=1.
     *
     * @return void
     */
    public function testBulkAddSkipsAlreadyExistingRoster(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => 1, 'PTS' => '99'], // already exists in fixture
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '15'], // new, should be saved
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
        $this->assertFlashMessage('Skipped 1 player(s) that already have stats for this game.');

        // Ensure the PTS=99 row was NOT saved
        $statsTable = $this->getTableLocator()->get('StatBasketGamePerson');
        $this->assertEquals(0, $statsTable->find()->where(['game_id' => 1, 'PTS' => '99'])->count());
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
     * Uses roster_id=2 which does not have existing stats for game_id=1.
     *
     * @return void
     */
    public function testBulkAddWithAddToTotals(): void
    {
        $data = [
            'add_to_totals' => '1',
            'rows' => [
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '20', 'period' => 'Z'],
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 player stat(s).');
    }

    /**
     * Test bulk add with save failure falls back to add page with errored rows
     *
     * PTS is required on create; omitting it triggers a validation failure.
     *
     * @return void
     */
    public function testBulkAddFailureFallsBackToAddPage(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => $this->extraRosterId1, 'MIN' => '20'], // missing PTS
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);
        // Should render the add template (not redirect)
        $this->assertResponseOk();
        $this->assertResponseContains('Add Player Stats');
        $this->assertFlashMessage('Row 1: could not save.');

        // Verify failedRows is passed to the view
        $failedRows = $this->viewVariable('failedRows');
        $this->assertNotEmpty($failedRows);
        $this->assertEquals($this->extraRosterId1, $failedRows[0]['team_season_roster_id']);
    }

    /**
     * Test bulk add with partial success and partial failure falls back to add page
     *
     * @return void
     */
    public function testBulkAddPartialSuccessFallsBackToAddPage(): void
    {
        $data = [
            'rows' => [
                ['team_season_roster_id' => $this->extraRosterId1, 'PTS' => '10'], // will succeed
                ['team_season_roster_id' => $this->extraRosterId2, 'MIN' => '20'], // missing PTS, will fail
            ],
        ];

        $this->post('/admin/stat-basket-game-person/bulk-add/1', $data);
        // Partial success with errors: fall back to add page
        $this->assertResponseOk();
        $this->assertResponseContains('Add Player Stats');
        $this->assertFlashMessage('Saved 1 player stat(s).');
        $this->assertFlashMessage('Row 2: could not save.');

        $failedRows = $this->viewVariable('failedRows');
        $this->assertCount(1, $failedRows);
        $this->assertEquals($this->extraRosterId2, $failedRows[0]['team_season_roster_id']);
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
     * Test deleteConfirm GET renders confirmation page with stat details
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::deleteConfirm()
     */
    public function testDeleteConfirmGet(): void
    {
        // Create a stat to confirm-delete
        $statsTable = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $statsTable->newEntity([
            'team_season_roster_id' => $this->extraRosterId1,
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'PTS' => '18',
        ]);
        $statsTable->save($stat);

        $this->get("/admin/stat-basket-game-person/delete-confirm/{$stat->id}");

        $this->assertResponseOk();
        $this->assertResponseContains('Delete Player Stat');
        $this->assertResponseContains('deduct-from-totals-checkbox');
        $this->assertResponseContains('confirm-delete-btn');
    }

    /**
     * Test deleteConfirm only shows deduct checkbox for period Z stats
     *
     * @return void
     */
    public function testDeleteConfirmNoDeductCheckboxForNonZPeriod(): void
    {
        $statsTable = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $statsTable->newEntity([
            'team_season_roster_id' => $this->extraRosterId1,
            'game_id' => 1,
            'period' => 'H1',
            'GP' => 1,
            'PTS' => '10',
        ]);
        $statsTable->save($stat);

        $this->get("/admin/stat-basket-game-person/delete-confirm/{$stat->id}");

        $this->assertResponseOk();
        $this->assertResponseContains('Delete Player Stat');
        $this->assertResponseNotContains('deduct-from-totals-checkbox');
    }

    /**
     * Test delete method with valid stat - no deduction
     *
     * @return void
     * @uses \App\Controller\Admin\StatBasketGamePersonController::delete()
     */
    public function testDeleteValid(): void
    {
        // First create a stat to delete
        $stats = $this->getTableLocator()->get('StatBasketGamePerson');
        $stat = $stats->newEntity([
            'team_season_roster_id' => $this->extraRosterId1,
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
     * Test delete with deduct_from_totals subtracts from season totals
     *
     * Uses the pre-existing season totals fixture (roster_id=1, PTS=120).
     *
     * @return void
     */
    public function testDeleteWithDeductFromTotals(): void
    {
        // The fixture has roster_id=1, game_id=1, period=Z, PTS=22
        $statId = 1; // from StatBasketGamePersonFixture

        $seasonTable = $this->getTableLocator()->get('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $beforeSeason */
        $beforeSeason = $seasonTable->find()->where(['team_season_roster_id' => 1])->first();
        $ptsBefore = (int)$beforeSeason->PTS;

        $this->post("/admin/stat-basket-game-person/delete/{$statId}", [
            'deduct_from_totals' => '1',
        ]);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The player stat has been deleted.');

        // Season totals should have been reduced by 22 PTS
        $afterSeason = $seasonTable->find()->where(['team_season_roster_id' => 1])->first();
        $this->assertEquals($ptsBefore - 22, (int)$afterSeason->PTS);
    }

    /**
     * Test delete WITHOUT deduct_from_totals leaves season totals unchanged
     *
     * @return void
     */
    public function testDeleteWithoutDeductFromTotals(): void
    {
        $statId = 1; // from StatBasketGamePersonFixture (roster_id=1, game_id=1, PTS=22)

        $seasonTable = $this->getTableLocator()->get('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $beforeSeason */
        $beforeSeason = $seasonTable->find()->where(['team_season_roster_id' => 1])->first();
        $ptsBefore = (int)$beforeSeason->PTS;

        $this->post("/admin/stat-basket-game-person/delete/{$statId}");

        $this->assertFlashMessage('The player stat has been deleted.');

        // Season totals should be unchanged
        $afterSeason = $seasonTable->find()->where(['team_season_roster_id' => 1])->first();
        $this->assertEquals($ptsBefore, (int)$afterSeason->PTS);
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
