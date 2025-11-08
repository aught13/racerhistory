<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\StatBasketSeasonOpponentController Test Case
 *
 * @uses \App\Controller\Admin\StatBasketSeasonOpponentController
 */
class StatBasketSeasonOpponentControllerTest extends TestCase
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
        'app.StatBasketSeasonOpponent',
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
     * Test edit method GET with existing stats
     *
     * @return void
     */
    public function testEditGetExisting(): void
    {
        $this->get('/admin/stat-basket-season-opponent/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent Season Stats');
        $this->assertResponseContains('form');
    }

    /**
     * Test edit method GET creates new entity if none exists
     *
     * @return void
     */
    public function testEditGetCreateNew(): void
    {
        // Delete existing stat to test creation
        $stats = $this->getTableLocator()->get('StatBasketSeasonOpponent');
        $stats->deleteAll(['team_season_id' => 1]);

        $this->get('/admin/stat-basket-season-opponent/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent Season Stats');
    }

    /**
     * Test edit method POST creates new stats
     *
     * @return void
     */
    public function testEditPostCreate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Delete existing to test create
        $stats = $this->getTableLocator()->get('StatBasketSeasonOpponent');
        $stats->deleteAll(['team_season_id' => 1]);

        $data = [
            'team_season_id' => 1,
            'GP' => '10',
            'PTS' => '465',
        ];

        $this->post('/admin/stat-basket-season-opponent/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The opponent season stats have been saved.');

        // Verify the stat was created
        $stat = $stats->find()->where(['team_season_id' => 1])->first();
        $this->assertNotNull($stat);
        $this->assertEquals('465', $stat->PTS);
    }

    /**
     * Test edit method POST updates existing stats
     *
     * @return void
     */
    public function testEditPostUpdate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team_season_id' => 1,
            'GP' => '12',
            'PTS' => '500',
        ];

        $this->post('/admin/stat-basket-season-opponent/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The opponent season stats have been saved.');

        // Verify the stat was updated
        $stats = $this->getTableLocator()->get('StatBasketSeasonOpponent');
        $stat = $stats->find()->where(['team_season_id' => 1])->first();
        $this->assertEquals('12', $stat->GP);
        $this->assertEquals('500', $stat->PTS);
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

        $this->post('/admin/stat-basket-season-opponent/delete/1');
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The opponent season stats have been deleted.');

        // Verify the stat was deleted
        $stats = $this->getTableLocator()->get('StatBasketSeasonOpponent');
        $this->assertFalse($stats->exists(['team_season_id' => 1]));
    }

    /**
     * Test delete method with non-existent stats
     *
     * @return void
     */
    public function testDeleteNonExistent(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Delete all stats first
        $stats = $this->getTableLocator()->get('StatBasketSeasonOpponent');
        $stats->deleteAll(['team_season_id' => 1]);

        $this->post('/admin/stat-basket-season-opponent/delete/1');
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The opponent season stats could not be deleted. Please, try again.');
    }
}
