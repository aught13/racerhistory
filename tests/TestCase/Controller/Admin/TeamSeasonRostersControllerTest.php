<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TeamSeasonRostersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Persons',
        'app.Sports',
        'app.Users',
        'app.Places',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    public function testAddGetRequiresAuth(): void
    {
        $this->get('/admin/team-season-rosters/add');
        $this->assertRedirectContains('/users/login');
    }

    public function testAddGetShowsMultiRowForm(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/add?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="roster-rows"');
        $this->assertResponseContains('id="add-row-btn"');
        $this->assertResponseContains('Add Another');
        $this->assertResponseContains('Save All');
        $this->assertResponseContains('roster-row');
        $this->assertResponseContains('turbo-frame id="roster-add-frame"');
        $this->assertResponseContains('data-person-search-url');
        $this->assertResponseContains('roster-person-search');
    }

    public function testAddGetShowsTeamSeasonSelect(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/add');
        $this->assertResponseOk();
        $this->assertResponseContains('team_season_id');
    }

    public function testBulkAddSingleRow(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['person_id' => 2, 'roster_number' => '22', 'roster_position' => 'G'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-add', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('Saved 1 roster entry/entries.');
    }

    public function testBulkAddMultipleRows(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['person_id' => 1, 'roster_number' => '10', 'roster_position' => 'F'],
                ['person_id' => 2, 'roster_number' => '22', 'roster_position' => 'G'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-add', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('Saved 2 roster entry/entries.');
    }

    public function testBulkAddSkipsEmptyRows(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['person_id' => 2, 'roster_number' => '22'],
                ['person_id' => '', 'roster_number' => ''],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-add', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('Saved 1 roster entry/entries.');
    }

    public function testBulkAddNoRowsRedirects(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [
            'team_season_id' => 1,
            'rows' => [],
        ];
        $this->post('/admin/team-season-rosters/bulk-add', $data);
        $this->assertRedirectContains('/admin/team-season-rosters/add');
        $this->assertFlashMessage('No roster entries to save.');
    }

    public function testBulkAddRequiresPost(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-add');
        $this->assertResponseCode(405);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPost(): void
    {
        $this->mockIdentity();
        $data = [ 'roster_position' => 'F' ];
        $this->post('/admin/team-season-rosters/edit/1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('The team season roster has been saved.');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/team-season-rosters/delete/1');
        $this->assertRedirectContains('/admin/team-seasons/view/1');
    }

    public function testBulkDeleteNoneSelected(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/team-season-rosters/bulkDelete', ['team_season_roster_ids' => ['']]);
        $this->assertRedirect();
        $this->assertFlashMessage('No team season rosters selected for deletion.');
    }

    public function testBulkDeleteSome(): void
    {
        $this->mockIdentity();
        // create a second roster to ensure deletion path
        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $entity = $table->newEntity(['team_season_id' => 1, 'person_id' => 1]);
        $table->save($entity);
        $this->post('/admin/team-season-rosters/bulkDelete', ['team_season_roster_ids' => ['1', (string)$entity->id]]);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
    }

    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/ajaxAdd');
        $this->assertResponseOk();
        $res = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($res['success']);
    }

    public function testAjaxAddValid(): void
    {
        $this->mockIdentity();
        $data = [ 'team_season_id' => 1, 'person_id' => 1 ];
        $this->post('/admin/team-season-rosters/ajaxAdd', $data);
        $this->assertResponseOk();
        $res = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($res['success']);
    }

    public function testAddFormContainsRosterYearField(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/add?team_season_id=1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('roster_year', $body);
    }

    public function testAddFormContainsHiddenPersonForm(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/add?team_season_id=1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('hidden-person-form', $body);
        $this->assertStringContainsString('/admin/persons/ajax-add', $body);
    }

    public function testEditFormContainsRosterYearField(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('roster_year', $body);
        $this->assertStringContainsString('Year', $body);
    }

    public function testEditFormContainsHiddenPersonForm(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('hidden-person-form', $body);
    }

    public function testViewShowsRosterYear(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('Year', $body);
        $this->assertStringContainsString('2024', $body);
    }

    public function testBulkAddWithRosterYear(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['person_id' => 2, 'roster_number' => '33', 'roster_position' => 'C', 'roster_year' => 'Jr.'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-add', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $table->find()->where(['person_id' => 2, 'roster_number' => '33'])->firstOrFail();
        $this->assertSame('Jr.', $roster->roster_year);
    }

    // ── Bulk Edit (GET) ──────────────────────────────────────────────

    public function testBulkEditGetRequiresAuth(): void
    {
        $this->get('/admin/team-season-rosters/bulk-edit?team_season_id=1');
        $this->assertRedirectContains('/users/login');
    }

    public function testBulkEditGetLoadsExistingRoster(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-edit?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Season Roster');
        $this->assertResponseContains('roster-row');
        $this->assertResponseContains('id="roster-rows"');
        $this->assertResponseContains('id="add-row-btn"');
        $this->assertResponseContains('Save All');
    }

    public function testBulkEditGetPrePopulatesExistingEntries(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-edit?team_season_id=1');
        $this->assertResponseOk();
        // Fixture record has person_id=1, number=12, position=G
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('rows[0][id]', $body);
        $this->assertStringContainsString('value="12"', $body);
        $this->assertStringContainsString('value="G"', $body);
    }

    public function testBulkEditGetShowsEditAllButton(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit All');
        $this->assertResponseContains('bulk-edit');
    }

    public function testBulkEditGetNoTeamSeason(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-edit');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Team Season Roster');
    }

    public function testBulkEditGetContainsTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-edit?team_season_id=1');
        $this->assertResponseOk();
        $this->assertResponseContains('turbo-frame id="roster-edit-frame"');
    }

    // ── Bulk Edit (POST – update) ────────────────────────────────────

    public function testBulkEditPostUpdatesExistingRecord(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['id' => 1, 'person_id' => 1, 'roster_number' => '99', 'roster_position' => 'C', 'roster_year' => 'Sr.'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $table->get(1);
        $this->assertSame('99', $roster->roster_number);
        $this->assertSame('C', $roster->roster_position);
        $this->assertSame('Sr.', $roster->roster_year);
    }

    public function testBulkEditPostAddsNewRow(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['id' => 1, 'person_id' => 1, 'roster_number' => '12', 'roster_position' => 'G'],
                ['person_id' => 2, 'roster_number' => '22', 'roster_position' => 'F'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $count = $table->find()->where(['team_season_id' => 1])->count();
        $this->assertSame(2, $count);
    }

    public function testBulkEditPostDeletesRemovedRecord(): void
    {
        $this->mockIdentity();
        // Create a second roster entry
        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $extra = $table->newEntity(['team_season_id' => 1, 'person_id' => 2]);
        $table->save($extra);

        // POST only the first entry, omitting the second => second should be deleted
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['id' => 1, 'person_id' => 1, 'roster_number' => '12', 'roster_position' => 'G'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $remaining = $table->find()->where(['team_season_id' => 1])->count();
        $this->assertSame(1, $remaining);
        $this->assertTrue($table->exists(['id' => 1]));
        $this->assertFalse($table->exists(['id' => $extra->id]));
    }

    public function testBulkEditPostDeletesAllAndAddsNew(): void
    {
        $this->mockIdentity();
        // Submit with no existing IDs — should delete fixture record, add new
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['person_id' => 2, 'roster_number' => '55', 'roster_position' => 'PG'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $this->assertFalse($table->exists(['id' => 1]));
        $newRoster = $table->find()->where(['person_id' => 2, 'roster_number' => '55'])->firstOrFail();
        $this->assertSame('PG', $newRoster->roster_position);
    }

    public function testBulkEditPostShowsDeletedCountInFlash(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        // Create a second entry
        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $extra = $table->newEntity(['team_season_id' => 1, 'person_id' => 2]);
        $table->save($extra);

        // Submit with only one row — one is deleted
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['id' => 1, 'person_id' => 1, 'roster_number' => '12', 'roster_position' => 'G'],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('Saved 1 roster entry/entries. Removed 1 roster entry/entries.');
    }

    public function testBulkEditPostInvalidTeamSeason(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [
            'team_season_id' => 0,
            'rows' => [],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirect();
        $this->assertFlashMessage('Invalid team season.');
    }

    public function testBulkEditPostSkipsEmptyRows(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'rows' => [
                ['id' => 1, 'person_id' => 1, 'roster_number' => '12', 'roster_position' => 'G'],
                ['person_id' => '', 'roster_number' => ''],
            ],
        ];
        $this->post('/admin/team-season-rosters/bulk-edit?team_season_id=1', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        $table = $this->getTableLocator()->get('TeamSeasonRosters');
        $count = $table->find()->where(['team_season_id' => 1])->count();
        $this->assertSame(1, $count);
    }

    public function testBulkEditFormContainsPersonSearchAndPopup(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-season-rosters/bulk-edit?team_season_id=1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('data-person-search-url', $body);
        $this->assertStringContainsString('hidden-person-form', $body);
        $this->assertStringContainsString('roster-multi-add.mjs', $body);
    }
}
