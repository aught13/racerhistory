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

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $data = [
            'team_season_id' => 1,
            'person_id' => 1,
            'roster_number' => '22',
        ];
        $this->post('/admin/team-season-rosters/add', $data);
        $this->assertRedirectContains('/admin/team-seasons/view/1');
        $this->assertFlashMessage('The team season roster has been saved.');
    }

    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [ 'team_season_id' => '', 'person_id' => '' ];
        $this->post('/admin/team-season-rosters/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The team season roster could not be saved. Please, try again.');
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
}
