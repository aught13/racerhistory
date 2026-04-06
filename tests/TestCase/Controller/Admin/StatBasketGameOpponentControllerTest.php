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
        $this->assertResponseContains('turbo-frame id="stat-opponent-add-frame"');
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
        $this->assertRedirect(['action' => 'view', 1]);
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
        $this->assertRedirect(['action' => 'view', 1]);
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
        $this->assertRedirect(['action' => 'view', 1]);
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
