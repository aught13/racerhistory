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
     * Test add method GET - displays form to add opponent stat
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->get('/admin/stat-basket-game-opponent/add/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent Player');
    }

    /**
     * Test add method POST - successfully creates opponent stat
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'GS' => 1,
            'name' => 'Jane Smith',
            'jersey' => '24',
            'MIN' => '35',
            'PTS' => '25',
            'FGM' => '10',
            'FGA' => '18',
            'TPM' => '3',
            'TPA' => '7',
            'FTM' => '2',
            'FTA' => '3',
            'ORB' => '2',
            'DRB' => '5',
            'RB' => '7',
            'AST' => '4',
            'STL' => '2',
            'BS' => '1',
            'TRN' => '3',
            'PF' => '2',
        ];

        $this->post('/admin/stat-basket-game-opponent/add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The opponent player stat has been saved.');

        // Verify the stat was created
        $stats = $this->getTableLocator()->get('StatBasketGameOpponent');
        $stat = $stats->find()->where(['name' => 'Jane Smith'])->first();
        $this->assertNotNull($stat);
        $this->assertEquals('25', $stat->PTS);
    }

    /**
     * Test add method POST with validation errors
     *
     * @return void
     */
    public function testAddPostValidationErrors(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'game_id' => 1,
            'period' => 'Z',
            'GP' => 1,
            'name' => '', // Required field missing
            'PTS' => '', // Required field missing
        ];

        $this->post('/admin/stat-basket-game-opponent/add/1', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent Player');
        $this->assertFlashMessage('The opponent player stat could not be saved. Please, try again.');
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
