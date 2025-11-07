<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * StatBasketGameTeamController Test Case
 *
 * Tests basketball team-level game statistics CRUD operations.
 */
class StatBasketGameTeamControllerTest extends TestCase
{
    use AuthTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.StatBasketGameTeam',
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
     * Test view method - displays team stats for a game
     *
     * @return void
     */
    public function testView(): void
    {
        $this->get('/admin/stat-basket-game-team/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Team Stats');
    }

    /**
     * Test edit method GET - displays form to edit team stats
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->get('/admin/stat-basket-game-team/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Stats');
    }

    /**
     * Test edit method POST - successfully creates team stats (both team and opponent)
     *
     * @return void
     */
    public function testEditPostCreate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => [
                'ORB' => '10',
                'DRB' => '28',
                'RB' => '38',
                'TRN' => '14',
                'TF' => '2',
                'PTS' => '75',
            ],
            'opponent' => [
                'ORB' => '8',
                'DRB' => '24',
                'RB' => '32',
                'TRN' => '18',
                'TF' => '1',
                'PTS' => '68',
            ],
        ];

        $this->post('/admin/stat-basket-game-team/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The team stats have been saved.');

        // Verify the stats were created
        $stats = $this->getTableLocator()->get('StatBasketGameTeam');
        $teamStat = $stats->find()->where(['game_id' => 1, 'opp' => 0])->first();
        $this->assertNotNull($teamStat);
        $this->assertEquals('75', $teamStat->PTS);

        $oppStat = $stats->find()->where(['game_id' => 1, 'opp' => 1])->first();
        $this->assertNotNull($oppStat);
        $this->assertEquals('68', $oppStat->PTS);
    }

    /**
     * Test edit method POST - successfully updates existing team stats
     *
     * @return void
     */
    public function testEditPostUpdate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => [
                'ORB' => '12',
                'DRB' => '30',
                'RB' => '42',
                'PTS' => '85',
            ],
            'opponent' => [
                'ORB' => '9',
                'DRB' => '26',
                'RB' => '35',
                'PTS' => '72',
            ],
        ];

        $this->post('/admin/stat-basket-game-team/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('The team stats have been saved.');

        // Verify the stats were updated
        $stats = $this->getTableLocator()->get('StatBasketGameTeam');
        $teamStat = $stats->get(1);
        $this->assertEquals('85', $teamStat->PTS);
        $this->assertEquals('42', $teamStat->RB);
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
            'team' => [
                'TRN' => 'not-a-number',
            ],
        ];

        $this->post('/admin/stat-basket-game-team/edit/1', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Stats');
        $this->assertFlashMessage('The team stats could not be saved. Please, try again.');
    }
}
