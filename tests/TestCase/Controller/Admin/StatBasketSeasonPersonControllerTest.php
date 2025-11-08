<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\StatBasketSeasonPersonController Test Case
 *
 * @uses \App\Controller\Admin\StatBasketSeasonPersonController
 */
class StatBasketSeasonPersonControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.StatBasketSeasonPerson',
        'app.Sports',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->mockIdentity();
    }

    /**
     * Test add method GET
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->get('/admin/stat-basket-season-person/add/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Player Season Stats');
        $this->assertResponseContains('form');
    }

    /**
     * Test add method POST success
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team_season_roster_id' => 1,
            'GP' => '10',
            'GS' => '8',
            'MIN' => '250',
            'PTS' => '120',
        ];

        $this->post('/admin/stat-basket-season-person/add/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The player season stats have been saved.');

        // Verify the stat was saved
        $stats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $stat = $stats->find()->where(['team_season_roster_id' => 1])->first();
        $this->assertNotNull($stat);
        $this->assertEquals('120', $stat->PTS);
    }

    /**
     * Test add method POST validation error
     *
     * @return void
     */
    public function testAddPostValidationError(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            // Missing required team_season_roster_id
            'GP' => '10',
        ];

        $this->post('/admin/stat-basket-season-person/add/1', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The player season stats could not be saved. Please, try again.');
    }

    /**
     * Test edit method GET
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->get('/admin/stat-basket-season-person/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Player Season Stats');
        $this->assertResponseContains('form');
    }

    /**
     * Test edit method POST success
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team_season_roster_id' => 1,
            'GP' => '12',
            'PTS' => 150,
        ];

        $this->post('/admin/stat-basket-season-person/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The player season stats have been saved.');

        // Verify the stat was updated
        $stats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $stat = $stats->get(1);
        $this->assertEquals('12', $stat->GP);
        $this->assertEquals(150, $stat->PTS);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/stat-basket-season-person/delete/1');
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The player season stats have been deleted.');

        // Verify the stat was deleted
        $stats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $this->assertFalse($stats->exists(['id' => 1]));
    }

    /**
     * Test delete method with invalid ID - expects 404
     *
     * @return void
     */
    public function testDeleteInvalidId(): void
    {
        $this->post('/admin/stat-basket-season-person/delete/999');
        // Record not found should result in 404
        $this->assertResponseError();
    }
}
