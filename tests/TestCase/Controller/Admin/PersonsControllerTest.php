<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PersonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Persons',
        'app.Users',
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Games',
        'app.Opponents',
        'app.StatBasketGamePerson',
        'app.StatBasketSeasonPerson',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    public function testIndexRequiresAuth(): void
    {
        $this->get('/admin/persons');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexAuthenticated(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons');
        $this->assertResponseOk();
    }

    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Sample biography for John Doe.');
    }

    public function testViewShowsPersonImageWhenSet(): void
    {
        $this->mockIdentity();
        // Create a person with a person_image id referencing fixture image id 1
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Pic', 'last' => 'Owner', 'display' => 'Pic Owner', 'person_image' => 1,
        ]);
        $saved = $persons->save($person);
        if ($saved === false) {
            $errors = $person->getErrors();
            $this->fail('Failed to save person: ' . json_encode($errors));
        }
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<img', $body);
        $this->assertStringContainsString('/images/serve/1', $body);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/add');
        $this->assertResponseOk();
    }

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $data = [
            'first' => 'Alan',
            'last' => 'Turing',
            'display' => 'Alan Turing',
        ];
        $this->post('/admin/persons/add', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [ 'first' => '', 'last' => '' ];
        $this->post('/admin/persons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The person could not be saved. Please, try again.');
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPost(): void
    {
        $this->mockIdentity();
        $data = [ 'display' => 'Updated Display' ];
        $this->post('/admin/persons/edit/1', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/delete/1');
        $this->assertRedirect('/admin/persons');
    }

    public function testBulkDeleteNoneSelected(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['']]);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('No persons selected for deletion.');
    }

    public function testBulkDeleteSome(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    public function testBulkDispatcherInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'nonsense']);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('Invalid bulk action.');
    }

    public function testBulkDispatcherDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'delete', 'person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/ajaxAdd');
        $this->assertResponseOk();
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
    }

    public function testAjaxAddValid(): void
    {
        $this->mockIdentity();
        $data = [ 'first' => 'Grace', 'last' => 'Hopper', 'display' => 'Grace Hopper' ];
        $this->post('/admin/persons/ajaxAdd', $data);
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('The person has been saved.', $body['message']);
    }

    public function testAjaxSearch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/ajaxSearch?q=John');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('results', $data);
    }

    public function testViewWithRosterEntries(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for roster entries section
        $this->assertStringContainsString('Roster Entries', $body);
    }

    public function testViewWithBasketballStats(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for game stats table
        $this->assertStringContainsString('Game Stats', $body);
        $this->assertStringContainsString('Season Totals', $body);
    }

    public function testViewWithCareerStats(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for career statistics section
        $this->assertStringContainsString('Career Statistics', $body);
        $this->assertStringContainsString('Career Totals', $body);
    }

    public function testViewWithoutRosterEntries(): void
    {
        $this->mockIdentity();
        // Create a new person with no roster entries
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'No',
            'last' => 'Stats',
            'display' => 'No Stats Person',
        ]);
        $persons->save($person);

        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Should not show roster/stats sections
        $this->assertStringNotContainsString('Roster Entries', $body);
        $this->assertStringNotContainsString('Career Statistics', $body);
    }

    public function testViewWithSupportedSportButNoStatsShowsFallbacks(): void
    {
        $this->mockIdentity();

        // Create a new person
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Empty',
            'last' => 'Stats',
            'display' => 'Empty Stats Person',
        ]);
        $this->assertNotFalse($persons->save($person));

        // Create a roster for basketball team season id 1 without any stat rows
        $rosters = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $rosters->newEmptyEntity();
        $roster = $rosters->patchEntity($roster, [
            'team_season_id' => 1,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($roster));

        // Ensure there are no game or season person stats for this roster id
        $seasonStats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $gameStats = $this->getTableLocator()->get('StatBasketGamePerson');
        $this->assertSame(0, $seasonStats->find()->where(['team_season_roster_id' => $roster->id])->count());
        $this->assertSame(0, $gameStats->find()->where(['team_season_roster_id' => $roster->id])->count());

        // View the person page
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Should show roster section
        $this->assertStringContainsString('Roster Entries', $body);

        // Should render friendly fallbacks for stats and career
        $this->assertStringContainsString('No stats available for this roster yet.', $body);
        $this->assertStringContainsString('No career statistics have been recorded yet.', $body);
    }

    public function testViewMultiSportFallbacksSupportedVsUnsupported(): void
    {
        $this->mockIdentity();

        // Create a new person
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Multi',
            'last' => 'Sport',
            'display' => 'Multi Sport Person',
        ]);
        $this->assertNotFalse($persons->save($person));

        // Basketball roster on existing team season id 1 (no stats present)
        $rosters = $this->getTableLocator()->get('TeamSeasonRosters');
        $basketRoster = $rosters->newEmptyEntity();
        $basketRoster = $rosters->patchEntity($basketRoster, [
            'team_season_id' => 1,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($basketRoster));

        // Ensure no basketball stats rows exist for this roster
        $seasonStats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $gameStats = $this->getTableLocator()->get('StatBasketGamePerson');
        $this->assertSame(0, $seasonStats->find()->where(['team_season_roster_id' => $basketRoster->id])->count());
        $this->assertSame(0, $gameStats->find()->where(['team_season_roster_id' => $basketRoster->id])->count());

        // Create a non-basketball team season (unsupported sport for stats)
        $teamSeasons = $this->getTableLocator()->get('TeamSeasons');
        $newSeason = $teamSeasons->newEmptyEntity();
        // Use team_id = 3 from TeamsFixture (Football), season_id = 1 existing
        $newSeason = $teamSeasons->patchEntity($newSeason, [
            'team_id' => 3,
            'season_id' => 1,
            'semester' => 1,
            'team_season_image' => 1,
        ]);
        $this->assertNotFalse($teamSeasons->save($newSeason));

        // Create roster entry for unsupported sport
        $footballRoster = $rosters->newEmptyEntity();
        $footballRoster = $rosters->patchEntity($footballRoster, [
            'team_season_id' => $newSeason->id,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($footballRoster));

        // View the person page
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Shows roster section overall
        $this->assertStringContainsString('Roster Entries', $body);

        // For supported sport (basketball) with no stats, fallbacks appear
        $this->assertStringContainsString('No stats available for this roster yet.', $body);
        $this->assertStringContainsString('No career statistics have been recorded yet.', $body);

        // Per-roster stats fallback should render for each roster (2 total)
        $this->assertSame(2, substr_count($body, 'No stats available for this roster yet.'));
        // Career fallback should render only for supported sports (basketball -> 1 total)
        $this->assertSame(1, substr_count($body, 'No career statistics have been recorded yet.'));
    }
}
