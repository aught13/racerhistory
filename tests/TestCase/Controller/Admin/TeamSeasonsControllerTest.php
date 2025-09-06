<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TeamSeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.TeamSeasons',
        'app.Seasons',
        'app.Teams',
        'app.Users',
    'app.Images',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }

    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
    // Should include image element debug comment now that fixture sets team_season_image
        $this->assertResponseContains('team_season_image =');
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/add');
        $this->assertResponseOk();
        $this->assertResponseContains('hidden-team-form');
        $this->assertResponseContains('hidden-season-form');
    // Rich text editors textareas present
        $this->assertResponseContains('team-season-preview');
        $this->assertResponseContains('team-season-recap');
    }

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

    // semester must be an integer per migrations/schema
        $data = ['team_id' => 1, 'season_id' => 1, 'semester' => 1];
        $this->post('/admin/team-seasons/add', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        // Missing required team_id should fail
        $data = ['team_id' => '', 'season_id' => ''];
        $this->post('/admin/team-seasons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The team season could not be saved. Please, try again.');
        $this->assertResponseContains('The team season could not be saved.');
    }

    public function testDeletePost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/team-seasons/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'delete', 'team_season_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    public function testBulkDeleteEmptySelection(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'delete', 'team_season_ids' => ['']]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        $this->assertFlashMessage('No team seasons selected for deletion.');
    }

    public function testBulkInvalidAction(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'nope', 'team_season_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        $this->assertFlashMessage('Invalid bulk action.');
    }
}
