<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\StatBasketSeasonTeamController Test Case
 *
 * @uses \App\Controller\Admin\StatBasketSeasonTeamController
 */
class StatBasketSeasonTeamControllerTest extends TestCase
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
        'app.StatBasketSeasonTeam',
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
        $this->get('/admin/stat-basket-season-team/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Season Stats');
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
        $stats = $this->getTableLocator()->get('StatBasketSeasonTeam');
        $stats->deleteAll(['team_season_id' => 1]);

        $this->get('/admin/stat-basket-season-team/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Season Stats');
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
        $stats = $this->getTableLocator()->get('StatBasketSeasonTeam');
        $stats->deleteAll(['team_season_id' => 1]);

        $data = [
            'team_season_id' => 1,
            'GP' => '10',
            'PTS' => '495',
        ];

        $this->post('/admin/stat-basket-season-team/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The team season stats have been saved.');

        // Verify the stat was created
        $stat = $stats->find()->where(['team_season_id' => 1])->first();
        $this->assertNotNull($stat);
        $this->assertEquals('495', $stat->PTS);
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
            'PTS' => '550',
        ];

        $this->post('/admin/stat-basket-season-team/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The team season stats have been saved.');

        // Verify the stat was updated
        $stats = $this->getTableLocator()->get('StatBasketSeasonTeam');
        $stat = $stats->find()->where(['team_season_id' => 1])->first();
        $this->assertEquals('12', $stat->GP);
        $this->assertEquals('550', $stat->PTS);
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

        $this->post('/admin/stat-basket-season-team/delete/1');
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'TeamSeasons', 'action' => 'view', 1]);
        $this->assertFlashMessage('The team season stats have been deleted.');

        // Verify the stat was deleted
        $stats = $this->getTableLocator()->get('StatBasketSeasonTeam');
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
        $stats = $this->getTableLocator()->get('StatBasketSeasonTeam');
        $stats->deleteAll(['team_season_id' => 1]);

        $this->post('/admin/stat-basket-season-team/delete/1');
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The team season stats could not be deleted. Please, try again.');
    }
}
