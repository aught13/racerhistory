<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * StatBasketGameOpponentController Test Case
 *
 * Tests basketball opponent player game statistics CRUD operations.
 *
 * @link \App\Controller\Admin\StatBasketGameOpponentController
 */
class StatBasketGameOpponentControllerTest extends TestCase
{
    use AuthTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.StatBasketGameOpponent',
        'app.Games',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Sports',
        'app.Opponents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockIdentity();
    }

    /**
     * Test view method - displays opponent stats for a game
     *
     * @return void
     */
    public function testView(): void
    {
        $this->get('/admin/stat-basket-game-opponent/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Opponent Player Stats');
    }

    /**
     * Test add method GET - displays multi-row form to add opponent stats
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->get('/admin/stat-basket-game-opponent/add/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent Player');
        $this->assertResponseContains('id="stat-rows"');
        $this->assertResponseContains('id="add-row-btn"');
        $this->assertResponseContains('Add Another');
        $this->assertResponseContains('Save All');
        $this->assertResponseContains('stat-row');
        $this->assertResponseContains('turbo-frame id="stat-opponent-add-frame" target="_top"');
    }

    /**
     * Test bulk add with a single row
     *
     * @return void
     */
    public function testBulkAddSingleRow(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'rows' => [
                [
                    'name' => 'Jane Smith',
                    'jersey' => '24',
                    'position' => 'G',
                    'period' => 'Z',
                    'GP' => 1,
                    'GS' => 1,
                    'MIN' => '35',
                    'PTS' => '25',
                    'FGM' => '10',
                    'FGA' => '18',
                ],
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 opponent stat(s).');

        $stats = $this->getTableLocator()->get('StatBasketGameOpponent');
        $stat = $stats->find()->where(['name' => 'Jane Smith'])->first();
        $this->assertNotNull($stat);
        $this->assertEquals('25', $stat->PTS);
    }

    /**
     * Test bulk add with multiple rows
     *
     * @return void
     */
    public function testBulkAddMultipleRows(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'rows' => [
                ['name' => 'Player A', 'jersey' => '10', 'PTS' => '12'],
                ['name' => 'Player B', 'jersey' => '20', 'PTS' => '18'],
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 2 opponent stat(s).');
    }

    /**
     * Test bulk add skips rows without a name
     *
     * @return void
     */
    public function testBulkAddSkipsEmptyRows(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'rows' => [
                ['name' => 'Player A', 'PTS' => '12'],
                ['name' => '', 'PTS' => ''],
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 opponent stat(s).');
    }

    /**
     * Test bulk add with no rows redirects back
     *
     * @return void
     */
    public function testBulkAddNoRowsRedirects(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = ['rows' => []];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertRedirect('/admin/stat-basket-game-opponent/add/1');
        $this->assertFlashMessage('No opponent stats to save.');
    }

    /**
     * Test bulk add requires POST method
     *
     * @return void
     */
    public function testBulkAddRequiresPost(): void
    {
        $this->get('/admin/stat-basket-game-opponent/bulk-add/1');
        $this->assertResponseCode(405);
    }

    /**
     * Test bulk add skips duplicate names within the same batch
     *
     * @return void
     */
    public function testBulkAddSkipsDuplicateNameInBatch(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'Player A', 'PTS' => '12'],
                ['name' => 'Player A', 'PTS' => '20'], // duplicate in same batch
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 opponent stat(s).');
        $this->assertFlashMessage('Skipped 1 opponent player(s) that already have stats for this game.');
    }

    /**
     * Test bulk add skips duplicate names case-insensitively within batch
     *
     * @return void
     */
    public function testBulkAddSkipsCaseInsensitiveDuplicateInBatch(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'Player A', 'PTS' => '12'],
                ['name' => 'player a', 'PTS' => '20'], // same name different case
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 opponent stat(s).');
        $this->assertFlashMessage('Skipped 1 opponent player(s) that already have stats for this game.');
    }

    /**
     * Test bulk add skips a name that already exists in the database for this game
     *
     * The fixture has 'John Doe' for game_id=1.
     *
     * @return void
     */
    public function testBulkAddSkipsAlreadyExistingName(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'John Doe', 'PTS' => '99'], // already exists in fixture
                ['name' => 'New Player', 'PTS' => '15'], // new, should be saved
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Saved 1 opponent stat(s).');
        $this->assertFlashMessage('Skipped 1 opponent player(s) that already have stats for this game.');

        // Ensure the PTS=99 row was NOT saved
        $stats = $this->getTableLocator()->get('StatBasketGameOpponent');
        $this->assertEquals(0, $stats->find()->where(['game_id' => 1, 'PTS' => '99'])->count());
    }

    /**
     * Test bulk add skips existing name case-insensitively from database
     *
     * @return void
     */
    public function testBulkAddSkipsExistingNameCaseInsensitive(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'john doe', 'PTS' => '5'], // same as fixture 'John Doe' but lowercase
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        // All rows skipped (saved=0) → redirects to game view
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
        $this->assertFlashMessage('Skipped 1 opponent player(s) that already have stats for this game.');
    }

    /**
     * Test bulk add with save failure falls back to add page with errored rows
     *
     * name is provided but PTS (required) is missing, triggering a validation failure.
     *
     * @return void
     */
    public function testBulkAddFailureFallsBackToAddPage(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'Bad Player', 'jersey' => '99'], // missing required PTS
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        // Should render the add template (not redirect)
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent Player');
        $this->assertFlashMessage('Row 1: could not save.');

        // Verify failedRows is passed to the view
        $failedRows = $this->viewVariable('failedRows');
        $this->assertNotEmpty($failedRows);
        $this->assertEquals('Bad Player', $failedRows[0]['name']);
    }

    /**
     * Test bulk add with partial success and partial failure redirects to game view
     *
     * @return void
     */
    public function testBulkAddPartialSuccessRedirectsToGameView(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'rows' => [
                ['name' => 'Good Player', 'PTS' => '10'], // will succeed
                ['name' => 'Bad Player', 'jersey' => '99'], // missing PTS, will fail
            ],
        ];

        $this->post('/admin/stat-basket-game-opponent/bulk-add/1', $data);
        // Partial success: has errors, so fall back to add page
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent Player');
        $this->assertFlashMessage('Saved 1 opponent stat(s).');
        $this->assertFlashMessage('Row 2: could not save.');

        $failedRows = $this->viewVariable('failedRows');
        $this->assertCount(1, $failedRows);
        $this->assertEquals('Bad Player', $failedRows[0]['name']);
    }

    /**
     * Test edit method GET - displays form to edit opponent stat
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->get('/admin/stat-basket-game-opponent/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent Player');
    }

    /**
     * Test edit method POST - successfully updates opponent stat
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Jane Smith',
            'PTS' => '30',
        ];

        $this->post('/admin/stat-basket-game-opponent/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The opponent player stat has been saved.');

        // Verify the stat was updated
        $stats = $this->getTableLocator()->get('StatBasketGameOpponent');
        $stat = $stats->get(1);
        $this->assertEquals('Jane Smith', $stat->name);
        $this->assertEquals('30', $stat->PTS);
    }

    /**
     * Test edit method POST with validation errors
     *
     * @return void
     */
    public function testEditPostValidationErrors(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'name' => '', // Required field
            'PTS' => '', // Required field
        ];

        $this->post('/admin/stat-basket-game-opponent/edit/1', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent Player');
        $this->assertFlashMessage('The opponent player stat could not be saved. Please, try again.');
    }

    /**
     * Test delete method - successfully removes opponent stat
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/stat-basket-game-opponent/delete/1');
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The opponent player stat has been deleted.');

        // Verify the stat was deleted
        $stats = $this->getTableLocator()->get('StatBasketGameOpponent');
        $this->assertFalse($stats->exists(['id' => 1]));
    }
}
