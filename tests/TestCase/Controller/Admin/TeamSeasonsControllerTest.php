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
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Sports',
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
        // Image presence now handled client-side; ensure basic page content loads instead
        $this->assertResponseContains('Basic Information');
    }

    public function testViewContainsRosterManagementElement(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Assert roster management element is present
        $this->assertResponseContains('Team Roster');
        $this->assertResponseContains('Add Roster Entry');
        $this->assertResponseContains('bulk-action-form-rosters');
        $this->assertResponseContains('rosters-table');
    }

    public function testViewWithRosterEntriesShowsTable(): void
    {
        $this->mockIdentity();
        // First create a roster entry
        $rostersTable = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $rostersTable->newEntity([
            'team_season_id' => 1,
            'person_id' => 1,
            'roster_number' => '10',
            'roster_position' => 'Forward',
        ]);
        $rostersTable->save($roster);

        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Should show the roster table with data
        $this->assertResponseContains('rosters-table');
        $this->assertResponseContains('roster-checkbox');
        $this->assertResponseContains('select-all-rosters');
    }

    public function testViewWithNoRosterEntriesShowsEmptyMessage(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Should show empty message when no rosters exist
        $this->assertResponseContains('No roster entries have been created for this team season yet');
        $this->assertResponseContains('Add the first roster entry');
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

    public function testRosterBulkDeleteThroughTeamSeasonsView(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Create roster entries to delete
        $rostersTable = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster1 = $rostersTable->newEntity(['team_season_id' => 1, 'person_id' => 1]);
        $roster2 = $rostersTable->newEntity(['team_season_id' => 1, 'person_id' => 1]);
        $rostersTable->save($roster1);
        $rostersTable->save($roster2);

        // Perform bulk delete via TeamSeasonRosters bulk action
        $this->post('/admin/team-season-rosters/bulk', [
            'bulk_action' => 'delete',
            'team_season_roster_ids' => [$roster1->id, $roster2->id],
        ]);

        // Should redirect back to team season view
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        
        // Verify rosters were deleted
        $remaining = $rostersTable->find()->where(['id IN' => [$roster1->id, $roster2->id]])->count();
        $this->assertEquals(0, $remaining);
    }

    public function testRosterBulkDeleteEmptySelectionThroughTeamSeasonsView(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        // Try bulk delete with no selections
        $this->post('/admin/team-season-rosters/bulk', [
            'bulk_action' => 'delete',
            'team_season_roster_ids' => [''],
        ]);

        // Should redirect and show error message
        $this->assertRedirect();
        $this->assertFlashMessage('No team season rosters selected for deletion.');
    }
}
